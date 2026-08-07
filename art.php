<?php
// Market Sculpt — Client Art Approval (token-gated, public)
// Shows a workbook's uploaded artwork and lets the client Approve or
// Request Changes. Mirrors portal.php (quote approval) but renders art
// files instead of a line-item table. On a decision it records the result
// on the token, reflects it into the workbook's Art Status, emails the
// internal team, and fires a Slack ping.

$DB_HOST = 'localhost';
$DB_NAME = 'markewq4_workbook';
$DB_USER = 'markewq4_workbook';
$DB_PASS = 'MarketFun123';

try {
    $pdo = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4", $DB_USER, $DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('<p style="font-family:sans-serif;padding:40px;color:#c00;">Service temporarily unavailable. Please try again later.</p>');
}

$pdo->exec("CREATE TABLE IF NOT EXISTS art_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    token CHAR(64) NOT NULL,
    workbook_id INT NOT NULL,
    art_snapshot LONGTEXT NOT NULL,
    client_name VARCHAR(255) DEFAULT '',
    client_email VARCHAR(255) DEFAULT '',
    status ENUM('active','approved','changes_requested') NOT NULL DEFAULT 'active',
    client_comment TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    UNIQUE KEY uq_art_token (token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// ── Validate token ──────────────────────────────────────────────────────────
$token = trim($_GET['t'] ?? '');
if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
    artPage('Invalid Link', artError('This art approval link is not valid. Please contact your Market Sculpt representative.'));
    exit;
}
$stmt = $pdo->prepare("SELECT * FROM art_tokens WHERE token = ?");
$stmt->execute([$token]);
$row = $stmt->fetch();
if (!$row) {
    artPage('Link Expired', artError('This link has expired or no longer exists. Please contact your Market Sculpt representative.'));
    exit;
}

$snap    = json_decode($row['art_snapshot'], true) ?: [];
$product = $snap['product'] ?? 'Your Artwork';
$files   = is_array($snap['files'] ?? null) ? $snap['files'] : [];
$notes   = trim((string)($snap['notes'] ?? ''));
$clName  = $row['client_name'];

// ── Token-gated file proxy ───────────────────────────────────────────────────
// The client has no app session, so it can't load /uploads/... the way the
// operator can. Serve the art bytes THROUGH this page (token already
// validated above), reading straight off disk. ?img=N streams the file;
// &dl=1 forces a download. Only files named in THIS token's snapshot, only
// from the uploads dir — never an arbitrary path.
if (isset($_GET['img'])) {
    $rel = $files[(int)$_GET['img']] ?? '';
    if ($rel === '') { http_response_code(404); exit; }
    $path = realpath(__DIR__ . '/' . ltrim($rel, '/'));
    $base = realpath(__DIR__ . '/uploads');
    if (!$path || !$base || strpos($path, $base) !== 0 || !is_file($path)) { http_response_code(404); exit; }
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    $types = ['jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png','gif'=>'image/gif','webp'=>'image/webp',
              'svg'=>'image/svg+xml','bmp'=>'image/bmp','tif'=>'image/tiff','tiff'=>'image/tiff','heic'=>'image/heic',
              'heif'=>'image/heif','avif'=>'image/avif','pdf'=>'application/pdf'];
    header('Content-Type: ' . ($types[$ext] ?? 'application/octet-stream'));
    header('Content-Length: ' . filesize($path));
    if (!empty($_GET['dl'])) header('Content-Disposition: attachment; filename="' . basename($path) . '"');
    header('Cache-Control: private, max-age=3600');
    readfile($path);
    exit;
}

// Already resolved?
if ($row['status'] !== 'active') {
    $approved = $row['status'] === 'approved';
    artPage($approved ? 'Artwork Approved' : 'Changes Requested',
        artDone($approved,
            $approved
                ? 'This artwork has already been approved. Thank you!'
                : 'Your change request has been received — the Market Sculpt team will follow up shortly.',
            $product));
    exit;
}

// ── Handle POST (approve / request changes) ─────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act     = $_POST['action']  ?? '';
    $comment = trim($_POST['comment'] ?? '');
    if (in_array($act, ['approve', 'request_changes'], true)) {
        $newStatus = $act === 'approve' ? 'approved' : 'changes_requested';
        $pdo->prepare("UPDATE art_tokens SET status=?, resolved_at=NOW(), client_comment=? WHERE token=?")
            ->execute([$newStatus, $comment, $token]);

        // Reflect into the workbook's Art Status (targeted read-modify-write,
        // only the art fields — approval is low-frequency so this is safe).
        try {
            $wsel = $pdo->prepare("SELECT detail_json FROM workbooks WHERE id = ?");
            $wsel->execute([(int)$row['workbook_id']]);
            $wrow = $wsel->fetch();
            if ($wrow) {
                $d = json_decode($wrow['detail_json'] ?: '{}', true);
                if (!is_array($d)) $d = [];
                $d['artStatus']        = $approved = ($newStatus === 'approved') ? 'approved' : 'revision-needed';
                $d['artClientComment'] = $comment;
                $d['artApprovedAt']    = gmdate('c');
                $pdo->prepare("UPDATE workbooks SET detail_json = ?, updated_at = NOW(), updated_by = ? WHERE id = ?")
                    ->execute([json_encode($d), 'client', (int)$row['workbook_id']]);
            }
        } catch (PDOException $e) { /* non-fatal — token status already recorded */ }

        artNotify($newStatus, $product, $clName, $comment, count($files), $snap['appUrl'] ?? '');

        artPage($newStatus === 'approved' ? 'Artwork Approved' : 'Changes Requested',
            artDone($newStatus === 'approved',
                $newStatus === 'approved'
                    ? 'Your artwork is approved! The Market Sculpt team will proceed to the next step.'
                    : "Your change request has been submitted. We'll revise the artwork and send an updated version.",
                $product));
        exit;
    }
}

// ── Render approval page ─────────────────────────────────────────────────────
artPage($product . ' — Art Approval', artMain($product, $files, $notes, $clName, $token));
exit;

// ════════════════════════════════════════════════════════════════════════════
// Helpers
// ════════════════════════════════════════════════════════════════════════════
function artIsImage(string $url): bool {
    return (bool)preg_match('/\.(jpe?g|png|gif|webp|svg|bmp|tiff?|heic|heif|avif)(\?|$)/i', $url);
}
function artIsPdf(string $url): bool { return (bool)preg_match('/\.pdf(\?|$)/i', $url); }
function artFileName(string $url): string {
    $p = parse_url($url, PHP_URL_PATH) ?: $url;
    return rawurldecode(basename($p));
}

function artMain(string $product, array $files, string $notes, string $clName, string $token): string {
    $tiles = '';
    if (empty($files)) {
        $tiles = '<div style="padding:40px;text-align:center;color:#9ba3c0;font-size:14px;">No art files were attached to this request.</div>';
    } else {
        $tk = urlencode($token);
        // uploads/ is public (see uploads/.htaccess) — so the client can load
        // each file DIRECTLY by its absolute URL, exactly like the operator's
        // app does. Absolute avoids any relative-base ambiguity on art.php.
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? 'wb.marketsculpt.com';
        $absUrl = function ($rel) use ($scheme, $host) {
            $rel = (string)$rel;
            if (preg_match('#^https?://#i', $rel)) return $rel;
            return $scheme . '://' . $host . '/' . ltrim($rel, '/');
        };
        foreach ($files as $i => $f) {
            $url   = (string)$f;
            $name  = htmlspecialchars(artFileName($url));
            $src   = htmlspecialchars($absUrl($url));    // direct public URL
            $dl    = "art.php?t={$tk}&img={$i}&dl=1";     // proxy → forces download
            $dlBtn = "<a class='art-dl' href='{$dl}'>&#8681; Download</a>";
            if (artIsImage($url)) {
                // Direct public URL first; if that fails on this host, fall
                // back to the token-gated proxy stream. Covers both setups.
                $proxy = "art.php?t={$tk}&amp;img={$i}";
                $tiles .= "<figure class='art-tile'><a href='{$src}' target='_blank' rel='noopener'><img src='{$src}' alt='{$name}' loading='lazy' onerror=\"this.onerror=null;this.src='{$proxy}';\"></a><figcaption><span class='art-name'>{$name}</span>{$dlBtn}</figcaption></figure>";
            } elseif (artIsPdf($url)) {
                $tiles .= "<figure class='art-tile art-doc'><a href='{$src}' target='_blank' rel='noopener'><div class='art-doc-badge'>PDF</div><span>{$name}</span></a><figcaption>{$dlBtn}</figcaption></figure>";
            } else {
                $tiles .= "<figure class='art-tile art-doc'><a href='{$dl}'><div class='art-doc-badge'>FILE</div><span>{$name}</span></a><figcaption>{$dlBtn}</figcaption></figure>";
            }
        }
    }

    return '<div class="card">'
         . '<div class="card-head">'
         . "<h1 class='page-title'>" . htmlspecialchars($product) . "</h1>"
         . "<p class='page-sub'>" . htmlspecialchars($clName ? "Prepared for {$clName}. " : '')
         . "Please review the artwork below, then <strong>Approve</strong> it or <strong>Request Changes</strong>.</p>"
         . '</div>'
         . '<div class="card-body">'
         . '<div class="art-grid">' . $tiles . '</div>'
         . ($notes !== '' ? "<div class='art-notes'><div class='art-notes-label'>Notes from Market Sculpt</div>" . nl2br(htmlspecialchars($notes)) . "</div>" : '')
         . '</div>'
         . '<form method="POST" action="?t=' . urlencode($token) . '" id="art-form" onsubmit="onArtSubmit(event)">'
         . '<input type="hidden" name="action" id="art-action" value="approve">'
         . '<div class="comment-section">'
         . '<div class="comment-label">Comments <span class="comment-sub">(required if requesting changes)</span></div>'
         . '<textarea name="comment" id="art-comment" class="comment-area" placeholder="Anything to change — colors, placement, sizing, text…"></textarea>'
         . '</div>'
         . '<div class="action-bar">'
         . '<button type="button" class="btn-changes" onclick="submitArt(\'request_changes\')">Request Changes</button>'
         . '<button type="button" class="btn-approve" onclick="submitArt(\'approve\')">✓ Approve Artwork</button>'
         . '</div>'
         . '</form>'
         . '</div>'
         . '<script>'
         . 'function submitArt(a){var c=document.getElementById("art-comment");'
         . 'if(a==="request_changes" && !c.value.trim()){c.focus();c.style.borderColor="#E8751A";alert("Please describe the changes you\'d like.");return;}'
         . 'document.getElementById("art-action").value=a;document.getElementById("art-form").submit();}'
         . 'function onArtSubmit(e){var bs=document.querySelectorAll(".action-bar button");bs.forEach(function(b){b.disabled=true;b.style.opacity="0.6";});}'
         . '</script>';
}

function artDone(bool $approved, string $msg, string $product): string {
    $iconBg = $approved ? '#dcfce7' : '#fff7ed';
    $iconCol = $approved ? '#16a34a' : '#E8751A';
    $icon = $approved ? '<path d="M20 6L9 17l-5-5"/>' : '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>';
    $head = $approved ? 'Artwork Approved' : 'Changes Submitted';
    return '<div class="card"><div class="card-body" style="text-align:center;padding:64px 24px;">'
         . "<div style='width:64px;height:64px;border-radius:50%;background:{$iconBg};display:flex;align-items:center;justify-content:center;margin:0 auto 18px;'>"
         . "<svg width='28' height='28' viewBox='0 0 24 24' fill='none' stroke='{$iconCol}' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'>{$icon}</svg></div>"
         . "<h1 style='font-size:24px;font-weight:800;color:#1a1d2e;margin:0 0 10px;'>{$head}</h1>"
         . "<p style='font-size:15px;color:#6b7280;max-width:440px;margin:0 auto 8px;line-height:1.6;'>" . htmlspecialchars($msg) . "</p>"
         . ($product ? "<p style='font-size:13px;color:#9ba3c0;'>" . htmlspecialchars($product) . "</p>" : '')
         . '</div></div>';
}

function artError(string $msg): string {
    return '<div class="card"><div class="card-body" style="text-align:center;padding:64px 24px;">'
         . '<div style="width:64px;height:64px;border-radius:50%;background:#fef2f2;display:flex;align-items:center;justify-content:center;margin:0 auto 18px;">'
         . '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg></div>'
         . '<h1 style="font-size:22px;font-weight:800;color:#1a1d2e;margin:0 0 10px;">Link Not Valid</h1>'
         . '<p style="font-size:15px;color:#6b7280;max-width:420px;margin:0 auto;">' . htmlspecialchars($msg) . '</p>'
         . '</div></div>';
}

// Internal email + Slack when the client decides.
function artNotify(string $status, string $product, string $clName, string $comment, int $fileCount, string $appUrl): void {
    $approved = $status === 'approved';
    $subject  = ($approved ? '✓ Artwork Approved — ' : '⚠ Art Changes Requested — ') . $product;
    $body = '<div style="font-family:-apple-system,Segoe UI,Roboto,sans-serif;font-size:14px;color:#1a1a1a;max-width:560px;">'
          . '<p><strong>' . htmlspecialchars($clName ?: 'The client') . '</strong> '
          . ($approved ? 'approved the artwork.' : 'requested changes to the artwork.') . '</p>'
          . '<table style="border-collapse:collapse;margin:12px 0;font-size:13px;">'
          . '<tr><td style="padding:4px 12px 4px 0;color:#666;">Product</td><td style="padding:4px 0;font-weight:600;">' . htmlspecialchars($product) . '</td></tr>'
          . '<tr><td style="padding:4px 12px 4px 0;color:#666;">Art files</td><td style="padding:4px 0;">' . (int)$fileCount . '</td></tr>'
          . '</table>'
          . ($comment !== '' ? '<p style="background:#f7f7f5;border-radius:6px;padding:10px 12px;"><strong>Client comment:</strong><br>' . nl2br(htmlspecialchars($comment)) . '</p>' : '')
          . ($appUrl ? '<p><a href="' . htmlspecialchars($appUrl) . '" style="display:inline-block;background:#181b26;color:#fff;padding:9px 18px;border-radius:6px;text-decoration:none;font-weight:600;">Open in App</a></p>' : '')
          . '</div>';
    artSmtpSend(['karen@marketsculpt.com', 'parker@marketsculpt.com'], '[Internal] ' . $subject, $body);

    // Proactive Slack ping (no-ops without SLACK_WEBHOOK_URL).
    $webhook = getenv('SLACK_WEBHOOK_URL');
    if (!is_string($webhook) || $webhook === '') $webhook = $_SERVER['SLACK_WEBHOOK_URL'] ?? '';
    if (is_string($webhook) && $webhook !== '' && function_exists('curl_init')) {
        $emoji = $approved ? ':white_check_mark:' : ':warning:';
        $title = ($approved ? 'Client APPROVED artwork — ' : 'Client requested art changes — ') . $product;
        $lines = ['*Client:* ' . ($clName ?: '—'), '*Art files:* ' . (int)$fileCount];
        if ($comment !== '') $lines[] = '*Comment:* ' . mb_substr($comment, 0, 300);
        $lines[] = $approved ? 'Ready to send to the factory.' : 'Revise the art and resend.';
        $payload = json_encode(['text' => $title, 'blocks' => [
            ['type' => 'header',  'text' => ['type' => 'plain_text', 'text' => trim($emoji . ' ' . $title), 'emoji' => true]],
            ['type' => 'section', 'text' => ['type' => 'mrkdwn', 'text' => implode("\n", $lines)]],
        ]]);
        $ch = curl_init($webhook);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true, CURLOPT_POSTFIELDS => $payload, CURLOPT_HTTPHEADER => ['Content-Type: application/json'], CURLOPT_TIMEOUT => 8]);
        curl_exec($ch); curl_close($ch);
    }
}

// Minimal SMTP sender (mirrors portal.php's).
function artSmtpSend(array $to, string $subject, string $html): void {
    $smtpHost = 'smtp.gmail.com'; $smtpPort = 587;
    $smtpUser = 'parker@marketsculpt.com'; $smtpPass = 'gcsgalchcnfnheth';
    $fp = @fsockopen('tcp://' . $smtpHost, $smtpPort, $errno, $errstr, 15);
    if (!$fp) return;
    stream_set_timeout($fp, 15); fgets($fp, 512);
    fwrite($fp, "EHLO marketsculpt.com\r\n");
    do { $l = fgets($fp, 512); } while (strlen($l) >= 4 && $l[3] !== ' ');
    fwrite($fp, "STARTTLS\r\n"); fgets($fp, 512);
    stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
    fwrite($fp, "EHLO marketsculpt.com\r\n");
    do { $l = fgets($fp, 512); } while (strlen($l) >= 4 && $l[3] !== ' ');
    fwrite($fp, "AUTH LOGIN\r\n"); fgets($fp, 512);
    fwrite($fp, base64_encode($smtpUser) . "\r\n"); fgets($fp, 512);
    fwrite($fp, base64_encode($smtpPass) . "\r\n");
    $auth = fgets($fp, 512);
    if (strpos($auth, '235') === false) { fwrite($fp, "QUIT\r\n"); fclose($fp); return; }
    fwrite($fp, "MAIL FROM:<{$smtpUser}>\r\n"); fgets($fp, 512);
    foreach ($to as $addr) { fwrite($fp, "RCPT TO:<{$addr}>\r\n"); fgets($fp, 512); }
    fwrite($fp, "DATA\r\n"); fgets($fp, 512);
    $bnd = md5(uniqid('msa', true));
    $plain = wordwrap(strip_tags(preg_replace('/<[^>]+>/', ' ', $html)), 76, "\r\n");
    $msg = "From: Market Sculpt <{$smtpUser}>\r\nTo: " . implode(', ', $to) . "\r\nSubject: {$subject}\r\nMIME-Version: 1.0\r\n"
         . "Content-Type: multipart/alternative; boundary=\"{$bnd}\"\r\n\r\n"
         . "--{$bnd}\r\nContent-Type: text/plain; charset=UTF-8\r\n\r\n{$plain}\r\n"
         . "--{$bnd}\r\nContent-Type: text/html; charset=UTF-8\r\n\r\n{$html}\r\n--{$bnd}--\r\n";
    fwrite($fp, $msg . ".\r\n"); fgets($fp, 512);
    fwrite($fp, "QUIT\r\n"); fclose($fp);
}

function artPage(string $title, string $content): void {
    $css = '
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;background:#f0f2f5;color:#1a1d2e;min-height:100vh;}
.ms-header{background:#181b26;padding:0 28px;display:flex;align-items:center;height:58px;gap:16px;}
.ms-logo{font-size:18px;font-weight:800;color:#E8751A;border-left:3px solid #E8751A;padding-left:12px;letter-spacing:-0.3px;}
.ms-logo-sub{font-size:11px;font-weight:500;color:#6b7280;}
.ms-bar{height:3px;background:#E8751A;}
.ms-wrap{max-width:860px;margin:0 auto;padding:32px 20px 80px;}
.card{background:#fff;border-radius:14px;border:1px solid #e8eaf0;overflow:hidden;margin-bottom:18px;box-shadow:0 1px 4px rgba(0,0,0,0.04);}
.card-head{padding:22px 26px;border-bottom:1px solid #f0f2f5;}
.card-body{padding:22px 26px;}
.page-title{font-size:24px;font-weight:800;color:#1a1d2e;margin-bottom:6px;line-height:1.2;}
.page-sub{font-size:14px;color:#6b7280;line-height:1.6;}
.art-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:14px;}
.art-tile{border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;background:#fafbfc;}
.art-tile img{width:100%;height:200px;object-fit:contain;background:#fff;display:block;}
.art-tile figcaption{font-size:11px;color:#6b7280;padding:8px 10px;border-top:1px solid #f0f2f5;word-break:break-word;display:flex;align-items:center;justify-content:space-between;gap:8px;}
.art-tile .art-name{overflow:hidden;text-overflow:ellipsis;white-space:nowrap;min-width:0;}
.art-dl{flex-shrink:0;font-size:11px;font-weight:700;color:#E8751A;text-decoration:none;white-space:nowrap;}
.art-dl:hover{text-decoration:underline;}
.art-doc a{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:10px;height:180px;text-decoration:none;color:#374151;font-size:12px;padding:12px;text-align:center;word-break:break-word;}
.art-doc-badge{font-size:12px;font-weight:800;letter-spacing:0.06em;color:#E8751A;background:rgba(232,117,26,0.1);border:1px solid rgba(232,117,26,0.3);border-radius:8px;padding:8px 14px;}
.art-notes{margin-top:18px;font-size:14px;color:#374151;background:#f8f9fb;border-left:3px solid #6b93ff;border-radius:6px;padding:12px 14px;line-height:1.6;}
.art-notes-label{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:#9ba3c0;margin-bottom:6px;}
.comment-section{padding:20px 26px;border-top:1px solid #f0f2f5;}
.comment-label{font-size:13px;font-weight:700;color:#374151;margin-bottom:8px;}
.comment-sub{font-size:12px;color:#9ba3c0;font-weight:400;}
.comment-area{width:100%;border:1px solid #e5e7eb;border-radius:8px;padding:11px 14px;font-size:14px;font-family:inherit;color:#1a1d2e;resize:vertical;min-height:76px;outline:none;}
.comment-area:focus{border-color:#6b93ff;box-shadow:0 0 0 3px rgba(107,147,255,0.12);}
.action-bar{display:flex;justify-content:flex-end;gap:12px;padding:18px 26px;border-top:1px solid #f0f2f5;flex-wrap:wrap;}
.btn-approve,.btn-changes{border:none;border-radius:8px;font-size:15px;font-weight:700;padding:12px 26px;cursor:pointer;font-family:inherit;}
.btn-approve{background:#27ae60;color:#fff;}
.btn-approve:hover{background:#229954;}
.btn-changes{background:#fff;color:#E8751A;border:1px solid #E8751A;}
.btn-changes:hover{background:#fff7f2;}
.footer-bar{text-align:center;padding:24px 20px 40px;color:#9ba3c0;font-size:12px;line-height:1.8;}
.footer-bar a{color:#E8751A;text-decoration:none;}
@media(max-width:620px){.ms-wrap{padding:16px 12px 60px;}.card-head,.card-body,.comment-section,.action-bar{padding:16px;}.btn-approve,.btn-changes{width:100%;}}';

    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">'
       . '<meta name="viewport" content="width=device-width,initial-scale=1">'
       . '<link rel="icon" type="image/svg+xml" href="favicon.svg">'
       . '<title>' . htmlspecialchars($title) . ' — Market Sculpt</title>'
       . '<style>' . $css . '</style></head><body>'
       . '<header class="ms-header"><div class="ms-logo">Market Sculpt</div><div class="ms-logo-sub">Art Approval</div></header>'
       . '<div class="ms-bar"></div>'
       . '<div class="ms-wrap">' . $content . '</div>'
       . '<footer class="footer-bar">Market Sculpt LLC &nbsp;·&nbsp; <a href="https://marketsculpt.com">marketsculpt.com</a><br>'
       . 'Questions? <a href="mailto:parker@marketsculpt.com">parker@marketsculpt.com</a></footer>'
       . '</body></html>';
}
