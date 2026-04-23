<?php
// Market Sculpt Client Portal — token-gated order approval page
// Public access but requires a valid 64-char hex token in ?t=

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

// Auto-create portal_tokens table
$pdo->exec("CREATE TABLE IF NOT EXISTS portal_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    token CHAR(64) NOT NULL,
    order_snapshot LONGTEXT NOT NULL,
    client_name VARCHAR(255) DEFAULT '',
    client_email VARCHAR(255) DEFAULT '',
    status ENUM('active','approved','changes_requested') NOT NULL DEFAULT 'active',
    client_comment TEXT DEFAULT NULL,
    line_changes LONGTEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    UNIQUE KEY uq_token (token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// ── Validate token ──────────────────────────────────────────────────────────
$token = trim($_GET['t'] ?? '');
if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
    portalPage('Invalid Link', errorContent('This portal link is not valid. Please contact your Market Sculpt representative.'));
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM portal_tokens WHERE token = ?");
$stmt->execute([$token]);
$row = $stmt->fetch();

if (!$row) {
    portalPage('Link Expired', errorContent('This link has expired or no longer exists. Please contact your Market Sculpt representative for assistance.'));
    exit;
}

$snap    = json_decode($row['order_snapshot'], true) ?: [];
$order   = $snap['order']  ?? [];
$items   = $snap['items']  ?? [];
$rate    = (float)($snap['rate'] ?? 7.24);
$clName  = $row['client_name'];
$clEmail = $row['client_email'];

// Already resolved?
if ($row['status'] !== 'active') {
    $approved = $row['status'] === 'approved';
    $msg = $approved
        ? 'This order has already been approved. Thank you for your confirmation!'
        : 'Your change request has been received. The Market Sculpt team will review and follow up shortly.';
    portalPage($approved ? 'Order Approved' : 'Changes Requested',
               doneContent($approved ? 'approved' : 'changes_requested', $msg, $order['name'] ?? 'Your Order'));
    exit;
}

// ── Handle POST ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = $_POST['action'] ?? '';
    $comment    = trim($_POST['comment'] ?? '');

    $changes = [];
    foreach ($items as $idx => $itm) {
        $v = trim($_POST["change_{$idx}"] ?? '');
        if ($v !== '') {
            $changes[] = [
                'idx'     => $idx,
                'product' => $itm['product'] ?? '',
                'item'    => $itm['item']    ?? '',
                'sku'     => $itm['sku']     ?? '',
                'note'    => $v,
            ];
        }
    }

    if (in_array($postAction, ['approve', 'request_changes'])) {
        $newStatus = $postAction === 'approve' ? 'approved' : 'changes_requested';
        $pdo->prepare("UPDATE portal_tokens SET status=?, resolved_at=NOW(), client_comment=?, line_changes=? WHERE token=?")
            ->execute([$newStatus, $comment, json_encode($changes), $token]);

        portalNotify($order, $items, $rate, $clName, $clEmail, $newStatus, $comment, $changes);

        if ($newStatus === 'approved') {
            portalPage('Order Approved',
                doneContent('approved',
                    'Your order has been approved! The Market Sculpt team will be in touch with next steps.',
                    $order['name'] ?? 'Your Order'));
        } else {
            portalPage('Changes Requested',
                doneContent('changes_requested',
                    "Your change request has been submitted. We'll review your feedback and reach out shortly.",
                    $order['name'] ?? 'Your Order'));
        }
        exit;
    }
}

// ── Render portal ───────────────────────────────────────────────────────────
portalPage($order['name'] ?? 'Order Review', mainContent($order, $items, $rate, $clName, $token));
exit;

// ════════════════════════════════════════════════════════════════════════════
// SMTP helper (self-contained, mirrors api.php ms_smtp_send)
// ════════════════════════════════════════════════════════════════════════════
function portalSmtpSend(array $to, string $subject, string $html): void {
    $smtpHost = 'smtp.gmail.com';
    $smtpPort = 587;
    $smtpUser = 'parker@marketsculpt.com';
    $smtpPass = 'gcsgalchcnfnheth';
    $fromName = 'Market Sculpt';

    $fp = @fsockopen('tcp://' . $smtpHost, $smtpPort, $errno, $errstr, 15);
    if (!$fp) return;
    stream_set_timeout($fp, 15);
    fgets($fp, 512);

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

    $bnd   = md5(uniqid('msp', true));
    $plain = wordwrap(strip_tags(preg_replace('/<[^>]+>/', ' ', $html)), 76, "\r\n");
    $msg   = "From: {$fromName} <{$smtpUser}>\r\nTo: " . implode(', ', $to) . "\r\n"
           . "Subject: {$subject}\r\nMIME-Version: 1.0\r\n"
           . "Content-Type: multipart/alternative; boundary=\"{$bnd}\"\r\n\r\n"
           . "--{$bnd}\r\nContent-Type: text/plain; charset=UTF-8\r\n\r\n{$plain}\r\n"
           . "--{$bnd}\r\nContent-Type: text/html; charset=UTF-8\r\n\r\n{$html}\r\n"
           . "--{$bnd}--\r\n";

    fwrite($fp, $msg . ".\r\n"); fgets($fp, 512);
    fwrite($fp, "QUIT\r\n"); fclose($fp);
}

function portalEmailWrap(string $title, string $preheader, string $body): string {
    return '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>' . htmlspecialchars($title) . '</title></head>'
         . '<body style="margin:0;padding:0;background:#f0f2f5;font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,sans-serif;">'
         . '<span style="display:none;max-height:0;overflow:hidden;">' . htmlspecialchars($preheader) . '</span>'
         . '<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f0f2f5;padding:40px 16px;"><tr><td align="center">'
         . '<table width="600" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;width:100%;">'
         . '<tr><td style="background:#181b26;border-radius:12px 12px 0 0;padding:24px 36px;">'
         . '<span style="font-size:20px;font-weight:800;color:#E8751A;border-left:3px solid #E8751A;padding-left:12px;letter-spacing:-0.3px;">Market Sculpt</span>'
         . '</td></tr>'
         . '<tr><td style="background:#E8751A;height:3px;line-height:3px;font-size:3px;">&nbsp;</td></tr>'
         . '<tr><td style="background:#ffffff;padding:40px 36px;">' . $body . '</td></tr>'
         . '<tr><td style="background:#f8f9fb;border:1px solid #e5e7eb;border-top:none;border-radius:0 0 12px 12px;padding:20px 36px;text-align:center;">'
         . '<p style="margin:0;font-size:12px;color:#9ba3c0;line-height:1.8;">Market Sculpt LLC &nbsp;·&nbsp; <a href="https://marketsculpt.com" style="color:#E8751A;text-decoration:none;">marketsculpt.com</a><br>'
         . 'Questions? Reply to this email or contact <a href="mailto:parker@marketsculpt.com" style="color:#E8751A;text-decoration:none;">parker@marketsculpt.com</a></p>'
         . '</td></tr></table></td></tr></table></body></html>';
}

// ── Internal notification email ─────────────────────────────────────────────
function portalNotify(array $order, array $items, float $rate, string $clName, string $clEmail, string $status, string $comment, array $changes): void {
    $internal  = ['jackson@marketsculpt.com', 'parker@marketsculpt.com'];
    $orderName = $order['name'] ?? 'Order';
    $po        = $order['poNumber'] ?? $order['po_number'] ?? '';

    if ($status === 'approved') {
        $subject = "✓ Order Approved — {$orderName}";
        $badge   = "<div style='margin-bottom:20px;'><span style='background:#27ae60;color:#fff;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;padding:5px 14px;border-radius:20px;'>Approved</span></div>";

        $body = $badge
              . "<h1 style='margin:0 0 8px;font-size:24px;font-weight:800;color:#1a1d2e;'>Order Approved by Client</h1>"
              . "<p style='margin:0 0 24px;font-size:15px;color:#6b7280;'>" . htmlspecialchars($clName) . " has reviewed and approved their order.</p>"
              . internalDetailTable($clName, $clEmail, $orderName, $po, '')
              . ($comment
                    ? "<div style='margin:20px 0;padding:16px;background:#f0fdf4;border-left:4px solid #27ae60;border-radius:4px;'>"
                    . "<p style='margin:0 0 6px;font-size:11px;font-weight:700;text-transform:uppercase;color:#9ba3c0;'>Client Comment</p>"
                    . "<p style='margin:0;font-size:14px;color:#374151;'>" . nl2br(htmlspecialchars($comment)) . "</p></div>"
                    : '');

    } else {
        $subject = "⚠ Change Request — {$orderName}";
        $badge   = "<div style='margin-bottom:20px;'><span style='background:#E8751A;color:#fff;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;padding:5px 14px;border-radius:20px;'>Changes Requested</span></div>";

        $changesHtml = '';
        if (!empty($changes)) {
            $changesHtml .= "<h3 style='margin:24px 0 12px;font-size:15px;font-weight:700;color:#1a1d2e;'>Requested Changes</h3>"
                         . '<table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;">'
                         . '<thead><tr style="background:#f8f9fb;">'
                         . '<th style="padding:10px 12px;font-size:11px;font-weight:700;text-transform:uppercase;color:#9ba3c0;text-align:left;">Product / Item</th>'
                         . '<th style="padding:10px 12px;font-size:11px;font-weight:700;text-transform:uppercase;color:#9ba3c0;text-align:left;">SKU</th>'
                         . '<th style="padding:10px 12px;font-size:11px;font-weight:700;text-transform:uppercase;color:#9ba3c0;text-align:left;">Requested Change</th>'
                         . '</tr></thead><tbody>';
            foreach ($changes as $i => $c) {
                $bg    = $i % 2 === 0 ? '#ffffff' : '#f8f9fb';
                $label = $c['product'] ? htmlspecialchars($c['product']) . ' &rsaquo; ' . htmlspecialchars($c['item']) : htmlspecialchars($c['item']);
                $changesHtml .= "<tr style='background:{$bg};border-top:1px solid #f0f2f5;'>"
                             . "<td style='padding:10px 12px;font-size:14px;color:#1a1d2e;'>{$label}</td>"
                             . "<td style='padding:10px 12px;font-size:13px;color:#6b7280;font-family:monospace;'>" . htmlspecialchars($c['sku'] ?? '') . "</td>"
                             . "<td style='padding:10px 12px;font-size:14px;color:#374151;'>" . nl2br(htmlspecialchars($c['note'])) . "</td></tr>";
            }
            $changesHtml .= '</tbody></table>';
        }

        $body = $badge
              . "<h1 style='margin:0 0 8px;font-size:24px;font-weight:800;color:#1a1d2e;'>Change Request Received</h1>"
              . "<p style='margin:0 0 24px;font-size:15px;color:#6b7280;'>" . htmlspecialchars($clName) . " has requested changes to their order. Please review and resend an updated order link.</p>"
              . internalDetailTable($clName, $clEmail, $orderName, $po, '')
              . $changesHtml
              . ($comment
                    ? "<div style='margin:20px 0;padding:16px;background:#fff7ed;border-left:4px solid #E8751A;border-radius:4px;'>"
                    . "<p style='margin:0 0 6px;font-size:11px;font-weight:700;text-transform:uppercase;color:#9ba3c0;'>Additional Comments</p>"
                    . "<p style='margin:0;font-size:14px;color:#374151;'>" . nl2br(htmlspecialchars($comment)) . "</p></div>"
                    : '');
    }

    $html = portalEmailWrap($subject, $subject, $body);
    portalSmtpSend($internal, '[Internal] ' . $subject, $html);
}

function internalDetailTable(string $clName, string $clEmail, string $orderName, string $po, string $total): string {
    $rows = [
        ['Client',    htmlspecialchars($clName)],
        ['Email',     htmlspecialchars($clEmail)],
        ['Order',     htmlspecialchars($orderName)],
    ];
    if ($po)    $rows[] = ['PO Number', htmlspecialchars($po)];
    if ($total) $rows[] = ['Total',     $total];

    $html = '<table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;margin:0 0 20px;">';
    foreach ($rows as $i => [$label, $value]) {
        $bg = $i % 2 === 0 ? '#f8f9fb' : '#ffffff';
        $html .= "<tr style='background:{$bg};'>"
               . "<td style='padding:10px 16px;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:#9ba3c0;width:36%;'>{$label}</td>"
               . "<td style='padding:10px 16px;font-size:14px;color:#1a1d2e;'>{$value}</td></tr>";
    }
    return $html . '</table>';
}

// ════════════════════════════════════════════════════════════════════════════
// PAGE RENDERERS
// ════════════════════════════════════════════════════════════════════════════

function portalPage(string $title, string $content): void {
    $css = '
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f0f2f5; color: #1a1d2e; min-height: 100vh; }
.ms-header { background: #181b26; padding: 0 28px; display: flex; align-items: center; height: 58px; gap: 16px; }
.ms-logo { font-size: 18px; font-weight: 800; color: #E8751A; border-left: 3px solid #E8751A; padding-left: 12px; letter-spacing: -0.3px; }
.ms-logo-sub { font-size: 11px; font-weight: 500; color: #6b7280; letter-spacing: 0.02em; }
.ms-bar { height: 3px; background: #E8751A; }
.ms-wrap { max-width: 900px; margin: 0 auto; padding: 40px 20px 80px; }
.card { background: #fff; border-radius: 12px; border: 1px solid #e5e7eb; overflow: hidden; margin-bottom: 20px; box-shadow: 0 1px 4px rgba(0,0,0,0.04); }
.card-head { padding: 22px 28px; border-bottom: 1px solid #f0f2f5; }
.card-head-flex { display: flex; justify-content: space-between; align-items: center; }
.card-body { padding: 28px; }
.card-body-flush { padding: 0; }
.page-title { font-size: 24px; font-weight: 800; color: #1a1d2e; margin-bottom: 6px; line-height: 1.2; }
.page-sub { font-size: 14px; color: #6b7280; line-height: 1.6; }
.detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0; }
.detail-cell { padding: 12px 0; border-bottom: 1px solid #f5f6f8; }
.detail-cell:nth-last-child(-n+2) { border-bottom: none; }
.detail-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: #9ba3c0; margin-bottom: 4px; }
.detail-value { font-size: 14px; color: #1a1d2e; font-weight: 600; }
.tbl { width: 100%; border-collapse: collapse; font-size: 14px; }
.tbl thead th { padding: 11px 16px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #9ba3c0; background: #f8f9fb; border-bottom: 1px solid #e5e7eb; text-align: left; white-space: nowrap; }
.tbl thead th.r { text-align: right; }
.tbl thead th.c { text-align: center; }
.tbl tbody td { padding: 13px 16px; color: #1a1d2e; vertical-align: middle; border-bottom: 1px solid #f5f6f8; }
.tbl tbody tr:last-child td { border-bottom: none; }
.tbl tbody td.r { text-align: right; }
.tbl tbody td.c { text-align: center; }
.tbl tbody td.muted { color: #6b7280; font-size: 13px; font-family: ui-monospace, monospace; }
.tbl tbody td.total { font-weight: 700; }
.product-hdr td { background: #f8f9fb !important; font-weight: 700; font-size: 12px; color: #6b7280; padding: 8px 16px !important; text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 1px solid #eaecf0 !important; }
.change-toggle { background: none; border: 1px solid #d1d5db; border-radius: 6px; color: #6b7280; font-size: 12px; font-weight: 600; padding: 4px 10px; cursor: pointer; font-family: inherit; transition: all 0.15s; white-space: nowrap; }
.change-toggle:hover { border-color: #E8751A; color: #E8751A; }
.change-toggle.is-open { border-color: #E8751A; color: #E8751A; background: #fff8f5; }
.change-expand { display: none; background: #fff8f5; }
.change-expand td { padding: 6px 16px 14px !important; border-bottom: 1px solid #fde5cc !important; }
.change-textarea { width: 100%; border: 1px solid #E8751A; border-radius: 6px; padding: 9px 12px; font-size: 13px; font-family: inherit; color: #1a1d2e; outline: none; resize: vertical; min-height: 58px; background: #fff; }
.change-textarea:focus { box-shadow: 0 0 0 3px rgba(232,117,26,0.14); }
.comment-section { padding: 24px 28px; border-top: 1px solid #f0f2f5; }
.comment-label { font-size: 13px; font-weight: 700; color: #374151; margin-bottom: 8px; }
.comment-sub { font-size: 12px; color: #9ba3c0; font-weight: 400; }
.comment-area { width: 100%; border: 1px solid #e5e7eb; border-radius: 8px; padding: 11px 14px; font-size: 14px; font-family: inherit; color: #1a1d2e; resize: vertical; min-height: 76px; outline: none; transition: border-color 0.15s; }
.comment-area:focus { border-color: #6b93ff; box-shadow: 0 0 0 3px rgba(107,147,255,0.12); }
.action-bar { display: flex; justify-content: flex-end; gap: 12px; align-items: center; padding: 20px 28px; border-top: 1px solid #f0f2f5; }
.action-hint { font-size: 12px; color: #9ba3c0; margin-right: auto; line-height: 1.5; }
#main-action-btn {
    display: inline-flex; align-items: center; gap: 8px;
    border: none; border-radius: 8px; font-size: 15px; font-weight: 700;
    padding: 12px 28px; cursor: pointer; font-family: inherit;
    transition: background 0.15s, opacity 0.15s;
}
#main-action-btn.approve { background: #27ae60; color: #fff; }
#main-action-btn.approve:hover { background: #229954; }
#main-action-btn.changes { background: #E8751A; color: #fff; }
#main-action-btn.changes:hover { background: #d4661a; }
#main-action-btn:disabled { opacity: 0.6; cursor: not-allowed; }
.total-row td { background: #f8f9fb; font-weight: 700; border-top: 2px solid #e5e7eb !important; }
.footer-bar { text-align: center; padding: 32px 20px; color: #9ba3c0; font-size: 12px; line-height: 1.8; }
.footer-bar a { color: #E8751A; text-decoration: none; }
@media (max-width: 620px) {
  .ms-wrap { padding: 16px 12px 60px; }
  .card-body { padding: 16px; }
  .card-head { padding: 16px; }
  .comment-section { padding: 16px; }
  .action-bar { flex-direction: column; padding: 16px; }
  #main-action-btn { width: 100%; justify-content: center; }
  .action-hint { text-align: center; margin-right: 0; margin-bottom: 8px; }
  .detail-grid { grid-template-columns: 1fr; }
  .detail-cell:nth-last-child(-n+2) { border-bottom: 1px solid #f5f6f8; }
  .detail-cell:last-child { border-bottom: none; }
  .tbl thead th, .tbl tbody td { padding: 9px 10px; }
  .tbl .hide-mobile { display: none; }
}';

    echo '<!DOCTYPE html><html lang="en"><head>'
       . '<meta charset="UTF-8">'
       . '<meta name="viewport" content="width=device-width,initial-scale=1">'
       . '<link rel="icon" type="image/svg+xml" href="favicon.svg">'
       . '<title>' . htmlspecialchars($title) . ' — Market Sculpt Portal</title>'
       . '<style>' . $css . '</style>'
       . '</head><body>'
       . '<header class="ms-header">'
       . '<div class="ms-logo">Market Sculpt</div>'
       . '<div class="ms-logo-sub">Client Portal</div>'
       . '</header>'
       . '<div class="ms-bar"></div>'
       . '<div class="ms-wrap">' . $content . '</div>'
       . '<footer class="footer-bar">Market Sculpt LLC &nbsp;·&nbsp; <a href="https://marketsculpt.com">marketsculpt.com</a><br>'
       . 'Questions? <a href="mailto:parker@marketsculpt.com">parker@marketsculpt.com</a></footer>'
       . '</body></html>';
}

function errorContent(string $msg): string {
    return '<div style="text-align:center;padding:80px 24px;">'
         . '<div style="width:68px;height:68px;border-radius:50%;background:#fef2f2;display:flex;align-items:center;justify-content:center;margin:0 auto 24px;">'
         . '<svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>'
         . '</div>'
         . '<h1 style="font-size:22px;font-weight:800;color:#1a1d2e;margin-bottom:12px;">Link Not Valid</h1>'
         . '<p style="font-size:15px;color:#6b7280;max-width:420px;margin:0 auto;">' . htmlspecialchars($msg) . '</p>'
         . '</div>';
}

function doneContent(string $status, string $msg, string $orderName): string {
    $approved   = $status === 'approved';
    $iconBg     = $approved ? '#dcfce7' : '#fff7ed';
    $iconColor  = $approved ? '#16a34a' : '#E8751A';
    $icon       = $approved
        ? '<path d="M20 6L9 17l-5-5"/>'
        : '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>';
    $headline   = $approved ? 'Order Approved' : 'Changes Submitted';
    $badgeBg    = $approved ? '#dcfce7' : '#fff7ed';
    $badgeColor = $approved ? '#166534' : '#9a3412';
    $badgeText  = $approved ? 'Approved' : 'Changes Requested';

    return '<div style="text-align:center;padding:80px 24px;">'
         . "<div style='width:68px;height:68px;border-radius:50%;background:{$iconBg};display:flex;align-items:center;justify-content:center;margin:0 auto 20px;'>"
         . "<svg width='30' height='30' viewBox='0 0 24 24' fill='none' stroke='{$iconColor}' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'>{$icon}</svg></div>"
         . "<span style='background:{$badgeBg};color:{$badgeColor};font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;padding:5px 14px;border-radius:20px;display:inline-block;'>{$badgeText}</span>"
         . "<h1 style='font-size:26px;font-weight:800;color:#1a1d2e;margin:18px 0 12px;'>{$headline}</h1>"
         . "<p style='font-size:15px;color:#6b7280;max-width:460px;margin:0 auto 20px;line-height:1.7;'>" . htmlspecialchars($msg) . "</p>"
         . ($orderName ? "<p style='font-size:13px;color:#9ba3c0;'>Order: <strong style='color:#374151;'>" . htmlspecialchars($orderName) . "</strong></p>" : '')
         . '</div>';
}

function mainContent(array $order, array $items, float $rate, string $clName, string $token): string {
    $orderName = $order['name'] ?? 'Your Order';
    $po        = $order['poNumber'] ?? $order['po_number'] ?? '';
    $date      = $order['dateCreated'] ?? $order['date'] ?? '';

    // Calculate total USD
    $totalUsd = 0;
    foreach ($items as $itm) {
        $rmb = (float)($itm['priceRmb'] ?? 0);
        $qty = (float)($itm['qty'] ?? 0);
        if ($rmb > 0 && $qty > 0 && $rate > 0) $totalUsd += ($rmb / $rate) * $qty;
    }

    // ── Order meta card ──────────────────────────────────────────────────────
    $detailCells = '';
    $metaFields = [
        ['Order', htmlspecialchars($orderName)],
        ['Client', htmlspecialchars($clName)],
    ];
    if ($po)        $metaFields[] = ['PO Number', htmlspecialchars($po)];
    if ($date)      $metaFields[] = ['Date', htmlspecialchars($date)];
    if ($totalUsd > 0) $metaFields[] = ['Estimated Total', '<strong>$' . number_format($totalUsd, 2) . ' USD</strong>'];

    foreach ($metaFields as [$label, $val]) {
        $detailCells .= "<div class='detail-cell'><div class='detail-label'>{$label}</div><div class='detail-value'>{$val}</div></div>";
    }

    $metaCard = '<div class="card">'
              . '<div class="card-head">'
              . "<h1 class='page-title'>" . htmlspecialchars($orderName) . "</h1>"
              . "<p class='page-sub'>Please review the order details below. You can approve the order or request changes to specific items before approving.</p>"
              . '</div>'
              . '<div class="card-body"><div class="detail-grid">' . $detailCells . '</div></div>'
              . '</div>';

    // ── Items table ──────────────────────────────────────────────────────────
    $tableRows  = '';
    $prevProduct = null;
    $rowIndex   = 0;
    $grandTotal = 0;

    foreach ($items as $idx => $itm) {
        $product  = $itm['product'] ?? '';
        $itemName = htmlspecialchars($itm['item'] ?? '');
        $sku      = htmlspecialchars($itm['sku'] ?? '');
        $qty      = (float)($itm['qty'] ?? 0);
        $priceRmb = (float)($itm['priceRmb'] ?? 0);

        if (!$itemName && !$qty && !$priceRmb) continue;

        $unitUsd = ($priceRmb > 0 && $rate > 0) ? '$' . number_format($priceRmb / $rate, 2) : '—';
        $totUsd  = ($priceRmb > 0 && $qty > 0 && $rate > 0) ? ($priceRmb / $rate) * $qty : 0;
        $totFmt  = $totUsd > 0 ? '$' . number_format($totUsd, 2) : '—';
        $qtyFmt  = $qty > 0 ? number_format($qty) : '—';
        $grandTotal += $totUsd;

        // Product group header
        if ($product !== $prevProduct && $product !== '') {
            $tableRows .= '<tr class="product-hdr"><td colspan="6">'
                        . '<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:-1px;margin-right:6px;opacity:0.5;"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>'
                        . htmlspecialchars($product)
                        . '</td></tr>';
            $prevProduct = $product;
        }

        // Item row
        $tableRows .= "<tr id='item-row-{$idx}'>
            <td>{$itemName}</td>
            <td class='muted hide-mobile'>{$sku}</td>
            <td class='r'>{$qtyFmt}</td>
            <td class='r hide-mobile'>{$unitUsd}</td>
            <td class='r total'>{$totFmt}</td>
            <td class='c' style='white-space:nowrap;'>
                <button type='button' class='change-toggle' id='toggle-{$idx}' onclick='toggleChange({$idx})'>Request Change</button>
            </td>
        </tr>
        <tr class='change-expand' id='expand-{$idx}'>
            <td colspan='6'>
                <div style='display:flex;gap:8px;align-items:flex-start;'>
                    <svg width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='#E8751A' stroke-width='2' style='flex-shrink:0;margin-top:10px;'><path d='M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z'/></svg>
                    <textarea name='change_{$idx}' id='change-{$idx}' class='change-textarea'
                        placeholder='Describe the change needed (e.g., &quot;Change qty to 300&quot; or &quot;Need different color variant&quot;)...'
                        oninput='checkChanges()'></textarea>
                </div>
            </td>
        </tr>";
        $rowIndex++;
    }

    // Grand total row
    if ($grandTotal > 0) {
        $tableRows .= '<tr class="total-row">'
                    . '<td colspan="4" style="text-align:right;font-size:13px;color:#6b7280;font-weight:600;">Estimated Order Total</td>'
                    . '<td class="r" style="font-size:15px;">$' . number_format($grandTotal, 2) . ' <span style="font-size:11px;font-weight:400;color:#9ba3c0;">USD</span></td>'
                    . '<td></td>'
                    . '</tr>';
    }

    $form = '<form method="POST" action="?t=' . urlencode($token) . '" id="portal-form" onsubmit="onSubmit(event)">'
          . '<input type="hidden" name="action" id="action-input" value="approve">'
          . '<div style="overflow-x:auto;">'
          . '<table class="tbl" style="min-width:580px;">'
          . '<thead><tr>'
          . '<th>Item</th>'
          . '<th class="hide-mobile">SKU</th>'
          . '<th class="r">Qty</th>'
          . '<th class="r hide-mobile">Unit (USD)</th>'
          . '<th class="r">Total</th>'
          . '<th class="c">Changes</th>'
          . '</tr></thead>'
          . '<tbody>' . $tableRows . '</tbody>'
          . '</table>'
          . '</div>'
          . '<div class="comment-section">'
          . '<div class="comment-label">Overall Comments <span class="comment-sub">(optional)</span></div>'
          . '<textarea name="comment" class="comment-area" placeholder="Any additional notes, questions, or context for the Market Sculpt team..."></textarea>'
          . '</div>'
          . '<div class="action-bar">'
          . '<div class="action-hint" id="action-hint">Select "Request Change" on any item to flag it, then submit.</div>'
          . '<button type="submit" class="approve" id="main-action-btn">'
          . '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>'
          . 'Approve Order'
          . '</button>'
          . '</div>'
          . '</form>';

    $itemsCard = '<div class="card">'
               . '<div class="card-head card-head-flex">'
               . '<span style="font-size:15px;font-weight:700;color:#1a1d2e;">Order Items</span>'
               . ($grandTotal > 0 ? "<span style='font-size:16px;font-weight:800;color:#1a1d2e;'>\$" . number_format($grandTotal, 2) . " <span style='font-size:12px;font-weight:500;color:#9ba3c0;'>USD</span></span>" : '')
               . '</div>'
               . '<div class="card-body-flush">' . $form . '</div>'
               . '</div>';

    $js = '<script>
function toggleChange(idx) {
    var row  = document.getElementById("expand-" + idx);
    var btn  = document.getElementById("toggle-" + idx);
    var inp  = document.getElementById("change-" + idx);
    var open = row.style.display === "table-row";
    row.style.display = open ? "none" : "table-row";
    if (open && inp) { inp.value = ""; }
    btn.textContent = open ? "Request Change" : "✕ Cancel";
    btn.classList.toggle("is-open", !open);
    checkChanges();
}
function checkChanges() {
    var hasChanges = false;
    document.querySelectorAll(".change-textarea").forEach(function(t) {
        if (t.value.trim()) hasChanges = true;
    });
    var btn  = document.getElementById("main-action-btn");
    var act  = document.getElementById("action-input");
    var hint = document.getElementById("action-hint");
    if (hasChanges) {
        btn.innerHTML = \'<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>Send Change Request\';
        btn.className = "changes";
        act.value     = "request_changes";
        if (hint) hint.innerHTML = \'Change requests will be sent to the Market Sculpt team.\';
    } else {
        btn.innerHTML = \'<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>Approve Order\';
        btn.className = "approve";
        act.value     = "approve";
        if (hint) hint.innerHTML = \'Select "Request Change" on any item to flag it, then submit.\';
    }
}
function onSubmit(e) {
    var btn = document.getElementById("main-action-btn");
    btn.disabled = true;
    btn.style.opacity = "0.65";
}
</script>';

    return $metaCard . $itemsCard . $js;
}
