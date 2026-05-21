<?php
// Market Sculpt Client Intake — token-gated public RFQ intake page.
// Operator clicks "Send RFQ Request" on a client → email goes out with a
// link → recipient lands here → fills Product Overview + RFQ Line Items →
// submission creates a draft workbook tagged "Pending Review" and emails
// the AM + Salesperson on file. Token is one-time use, expires in 30 days.

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

// Mirror the auto-create from api.php so this page can stand on its own
// (e.g. someone hits intake.php before the operator app has booted).
$pdo->exec("CREATE TABLE IF NOT EXISTS intake_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    token CHAR(64) NOT NULL,
    client_id INT NOT NULL,
    client_name VARCHAR(255) NOT NULL DEFAULT '',
    client_email VARCHAR(255) NOT NULL DEFAULT '',
    contact_name VARCHAR(255) NOT NULL DEFAULT '',
    status ENUM('active','submitted','expired') NOT NULL DEFAULT 'active',
    submitted_workbook_id INT DEFAULT NULL,
    created_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    submitted_at TIMESTAMP NULL,
    UNIQUE KEY uq_intake_token (token),
    INDEX idx_intake_client (client_id),
    INDEX idx_intake_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// ── Validate token ──────────────────────────────────────────────────────────
$token = trim($_GET['t'] ?? '');
if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
    intakePage('Invalid Link', errorContent('This link is not valid. Please contact your Market Sculpt representative for a new one.'));
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM intake_tokens WHERE token = ?");
$stmt->execute([$token]);
$row = $stmt->fetch();

if (!$row) {
    intakePage('Link Not Found', errorContent('This link no longer exists. Please contact your Market Sculpt representative for a new one.'));
    exit;
}

// Already submitted?
if ($row['status'] === 'submitted') {
    intakePage('Already Submitted',
        doneContent('Your quote request has already been submitted. The Market Sculpt team is reviewing it now and will follow up with pricing shortly. If you need to send another request, please reach out for a new link.'));
    exit;
}

// Expired?
$expiresAt = strtotime($row['expires_at'] ?? '');
if ($expiresAt && $expiresAt < time()) {
    if ($row['status'] !== 'expired') {
        $pdo->prepare("UPDATE intake_tokens SET status = 'expired' WHERE token = ?")->execute([$token]);
    }
    intakePage('Link Expired',
        errorContent('This intake link has expired. Please contact your Market Sculpt representative for a new one.'));
    exit;
}

if ($row['status'] === 'expired') {
    intakePage('Link Expired',
        errorContent('This intake link has expired. Please contact your Market Sculpt representative for a new one.'));
    exit;
}

$clientId    = (int)$row['client_id'];
$clientName  = (string)$row['client_name'];
$clientEmail = (string)$row['client_email'];
$contactName = (string)$row['contact_name'];

// ── Handle POST ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    handleSubmit($pdo, $token, $clientId, $clientName, $clientEmail, $contactName);
    exit;
}

// ── Render form ─────────────────────────────────────────────────────────────
intakePage("Request a Quote — {$clientName}", formContent($clientName, $contactName, $token));
exit;

// ════════════════════════════════════════════════════════════════════════════
// Handle the submission — build a workbook detail_json that matches what
// the operator-side workbook editor reads, insert into `workbooks`, mark
// the token submitted, and email the AM + Salesperson on file.
// ════════════════════════════════════════════════════════════════════════════
function handleSubmit(PDO $pdo, string $token, int $clientId, string $clientName, string $clientEmail, string $contactName): void {
    $productName = trim((string)($_POST['product_name'] ?? ''));
    $productDesc = trim((string)($_POST['product_desc'] ?? ''));
    $materials   = trim((string)($_POST['materials']    ?? ''));
    $colorNotes  = trim((string)($_POST['color_notes']  ?? ''));
    $pantone     = trim((string)($_POST['pantone']      ?? ''));
    $dimL        = trim((string)($_POST['dim_l']        ?? ''));
    $dimW        = trim((string)($_POST['dim_w']        ?? ''));
    $dimH        = trim((string)($_POST['dim_h']        ?? ''));
    $dimUnit     = ($_POST['dim_unit'] ?? 'in') === 'cm' ? 'cm' : 'in';
    $contact     = trim((string)($_POST['contact_name'] ?? $contactName));

    // Minimum: a product name + at least one RFQ row with an item OR qty
    if ($productName === '') {
        intakePage('Quote Request — Missing Info', errorContent('Please tell us the product name before submitting.'));
        return;
    }

    // Parse RFQ rows. Each item may carry a nested `variants` array
    // (added by the "+ Add variant" button on the form). Real variants
    // map straight onto the operator's RFQ table — they render as
    // indented rows under the parent, with their own qtys preserved.
    $items     = (array)($_POST['items'] ?? []);
    $rfqItems  = [];
    foreach ($items as $r) {
        if (!is_array($r)) continue;
        $itemName = trim((string)($r['item']  ?? ''));
        $sku      = trim((string)($r['sku']   ?? ''));
        $qty      = trim((string)($r['qty']   ?? ''));
        $notes    = trim((string)(
            // The new field is `notes`; fall back to the legacy `variant`
            // single-text field so a stale browser submission still parses.
            $r['notes']   ?? $r['variant'] ?? ''
        ));

        // Variants — array of {name, qty}. Skip blank rows, normalize
        // into the {variant, qty, priceRmb, leadTime} shape the operator
        // app expects (priceRmb + leadTime stay blank — the operator
        // fills those during quoting).
        $variants = [];
        $rawVariants = (array)($r['variants'] ?? []);
        foreach ($rawVariants as $v) {
            if (!is_array($v)) continue;
            $vName = trim((string)($v['name'] ?? ''));
            $vQty  = trim((string)($v['qty']  ?? ''));
            if ($vName === '' && $vQty === '') continue;
            $variants[] = [
                'variant'  => $vName,
                'qty'      => $vQty,
                'priceRmb' => '',
                'leadTime' => '',
            ];
        }

        // Skip a row that has nothing in it AT ALL (no item, sku, qty,
        // notes, or variants). Empty rows are usually leftover slots.
        if ($itemName === '' && $sku === '' && $qty === '' && $notes === '' && empty($variants)) continue;

        $rfqItems[] = [
            'item'       => $itemName,
            'sku'        => $sku,
            'qty'        => $qty,
            'priceRmb'   => '',
            'leadTime'   => '',
            'sample'     => false,
            'variants'   => $variants,
            'clientNote' => $notes,
        ];
    }

    if (empty($rfqItems)) {
        intakePage('Quote Request — Missing Info', errorContent('Please add at least one item to your quote request before submitting.'));
        return;
    }

    // Build dimension fields. We store whatever unit the client picked; the
    // operator app will see the value populated and the "in" or "cm" version
    // matching the choice. The other unit stays blank — operator can convert.
    $dim = [
        'dimInL' => $dimUnit === 'in' ? $dimL : '',
        'dimInW' => $dimUnit === 'in' ? $dimW : '',
        'dimInH' => $dimUnit === 'in' ? $dimH : '',
        'dimCmL' => $dimUnit === 'cm' ? $dimL : '',
        'dimCmW' => $dimUnit === 'cm' ? $dimW : '',
        'dimCmH' => $dimUnit === 'cm' ? $dimH : '',
    ];

    // Workbook detail_json — match the shape collectWorkbookDetail() produces
    // in index.php so the operator can open the workbook and see everything
    // populated. Pricing/freight/fees stay blank for the operator to fill.
    $detail = array_merge([
        'client'      => $clientName,
        'product'     => $productName,
        'desc'        => $productDesc,
        'materials'   => $materials,
        'colorNotes'  => $colorNotes,
        'pantone'     => $pantone,
        'rfqItems'    => $rfqItems,
        // Pending Review flag — surfaced on the workbook list.
        'submittedByClient'    => true,
        'submittedByClientAt'  => date('c'),
        'submittedByContact'   => $contact,
        'submittedByEmail'     => $clientEmail,
        'submittedByIntakeToken' => $token,
    ], $dim);

    // Pick a unique product name for this client. If the same name already
    // exists (active workbooks), append " (Pending Review)" — the dedup
    // guard in api.php's add_workbook would otherwise return the existing
    // one and silently overwrite our detail. We want a fresh row.
    $finalName = $productName;
    $check = $pdo->prepare("SELECT 1 FROM workbooks WHERE client_id = ? AND product_name = ? AND deleted_at IS NULL LIMIT 1");
    $check->execute([$clientId, $finalName]);
    if ($check->fetch()) {
        $i = 2;
        while (true) {
            $candidate = "{$productName} (Pending Review #{$i})";
            $check->execute([$clientId, $candidate]);
            if (!$check->fetch()) { $finalName = $candidate; break; }
            $i++;
            if ($i > 50) { $finalName = "{$productName} (Pending " . substr($token, 0, 6) . ')'; break; }
        }
    }

    // Insert workbook
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("INSERT INTO workbooks (client_id, product_name, description, flow_step, detail_json) VALUES (?, ?, ?, 0, ?)");
        $stmt->execute([$clientId, $finalName, $productDesc, json_encode($detail)]);
        $newWorkbookId = (int)$pdo->lastInsertId();

        // Mark token submitted
        $pdo->prepare("UPDATE intake_tokens SET status='submitted', submitted_at=NOW(), submitted_workbook_id=? WHERE token=?")
            ->execute([$newWorkbookId, $token]);

        $pdo->commit();
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        intakePage('Submission Failed', errorContent('We were unable to record your submission. Please try again, or reach out to your Market Sculpt representative.'));
        return;
    }

    // Resolve AM + SP emails for internal notification
    $cstmt = $pdo->prepare("SELECT account_manager, salesperson, primary_contact FROM clients WHERE id = ?");
    $cstmt->execute([$clientId]);
    $cli = $cstmt->fetch() ?: [];

    $internalRecipients = [];
    foreach (['account_manager', 'salesperson'] as $roleKey) {
        $displayName = trim((string)($cli[$roleKey] ?? ''));
        if ($displayName === '') continue;
        $u = $pdo->prepare("SELECT email FROM users WHERE display_name = ? AND email IS NOT NULL AND email != '' LIMIT 1");
        $u->execute([$displayName]);
        $em = $u->fetchColumn();
        if ($em) $internalRecipients[] = (string)$em;
    }
    // Always include the global ops mailbox so nothing falls through cracks
    $internalRecipients = array_values(array_unique(array_merge(
        $internalRecipients,
        ['parker@marketsculpt.com', 'jackson@marketsculpt.com']
    )));

    // Build deep link into the operator app
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'wb.marketsculpt.com';
    $appUrl = "{$scheme}://{$host}/index.php#/client/" . rawurlencode($clientName) . "/workbook/{$newWorkbookId}";

    intakeNotify($internalRecipients, $clientName, $contact, $clientEmail, $finalName, $productDesc, $rfqItems, $appUrl, $clientEmail);

    // Render the success page
    intakePage('Submitted', doneContent("Thank you, {$contact}! Your quote request for <strong>" . htmlspecialchars($finalName) . "</strong> has been received. The Market Sculpt team will review and follow up shortly with pricing."));
}

// ════════════════════════════════════════════════════════════════════════════
// Email helpers (self-contained so intake.php can run without api.php)
// ════════════════════════════════════════════════════════════════════════════
function intakeSmtpSend(array $to, string $subject, string $html): void {
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

    $bnd   = md5(uniqid('msi', true));
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

function intakeEmailWrap(string $title, string $preheader, string $body): string {
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
         . 'Questions? <a href="mailto:parker@marketsculpt.com" style="color:#E8751A;text-decoration:none;">parker@marketsculpt.com</a></p>'
         . '</td></tr></table></td></tr></table></body></html>';
}

function intakeNotify(array $internal, string $clientName, string $contact, string $clientEmail, string $productName, string $productDesc, array $rfqItems, string $appUrl, string $confirmTo): void {
    $rows = '';
    foreach ($rfqItems as $r) {
        $rows .= '<tr style="border-top:1px solid #f5f6f8;">'
              . "<td style='padding:10px 12px;font-size:14px;color:#1a1d2e;'>" . htmlspecialchars($r['item'] ?? '—') . "</td>"
              . "<td style='padding:10px 12px;font-size:13px;color:#6b7280;font-family:ui-monospace,monospace;'>" . htmlspecialchars($r['sku'] ?? '') . "</td>"
              . "<td style='padding:10px 12px;font-size:14px;color:#6b7280;text-align:center;'>" . htmlspecialchars((string)($r['qty'] ?? '')) . "</td>"
              . "<td style='padding:10px 12px;font-size:14px;color:#6b7280;text-align:center;'>" . htmlspecialchars((string)($r['leadTime'] ?? '')) . "</td>"
              . "<td style='padding:10px 12px;font-size:13px;color:#6b7280;'>" . htmlspecialchars((string)($r['clientNote'] ?? '')) . "</td>"
              . '</tr>';
    }
    $tbl = '<h3 style="margin:24px 0 10px;font-size:15px;font-weight:700;color:#1a1d2e;">Items Requested</h3>'
         . '<table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;">'
         . '<thead><tr style="background:#f8f9fb;">'
         . '<th style="padding:10px 12px;font-size:11px;font-weight:700;text-transform:uppercase;color:#9ba3c0;text-align:left;">Item</th>'
         . '<th style="padding:10px 12px;font-size:11px;font-weight:700;text-transform:uppercase;color:#9ba3c0;text-align:left;">SKU</th>'
         . '<th style="padding:10px 12px;font-size:11px;font-weight:700;text-transform:uppercase;color:#9ba3c0;text-align:center;">Qty</th>'
         . '<th style="padding:10px 12px;font-size:11px;font-weight:700;text-transform:uppercase;color:#9ba3c0;text-align:center;">Lead</th>'
         . '<th style="padding:10px 12px;font-size:11px;font-weight:700;text-transform:uppercase;color:#9ba3c0;text-align:left;">Variant / Notes</th>'
         . '</tr></thead><tbody>' . $rows . '</tbody></table>';

    $details = [
        ['Client',    htmlspecialchars($clientName)],
        ['Contact',   htmlspecialchars($contact ?: '—')],
        ['Email',     htmlspecialchars($clientEmail)],
        ['Product',   htmlspecialchars($productName)],
    ];
    $detailRows = '';
    foreach ($details as $i => [$label, $value]) {
        $bg = $i % 2 === 0 ? '#f8f9fb' : '#ffffff';
        $detailRows .= "<tr style='background:{$bg};'>"
                    . "<td style='padding:10px 16px;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:#9ba3c0;width:38%;'>{$label}</td>"
                    . "<td style='padding:10px 16px;font-size:14px;color:#1a1d2e;'>{$value}</td></tr>";
    }
    $detailTable = "<table width='100%' cellpadding='0' cellspacing='0' style='border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;margin:20px 0;'>{$detailRows}</table>";

    $appBtn = "<div style='text-align:center;margin:32px 0;'>"
            . "<a href='" . htmlspecialchars($appUrl) . "' style='display:inline-block;background:#E8751A;color:#fff;font-size:15px;font-weight:700;text-decoration:none;padding:14px 32px;border-radius:8px;letter-spacing:0.01em;'>"
            . "Open Pending Workbook in App &rarr;"
            . "</a></div>";

    $descBlock = $productDesc !== ''
        ? "<div style='margin:16px 0;padding:14px 16px;background:#f8f9fb;border-left:4px solid #6b93ff;border-radius:4px;'>"
        . "<p style='margin:0 0 4px;font-size:11px;font-weight:700;text-transform:uppercase;color:#9ba3c0;'>Description from client</p>"
        . "<p style='margin:0;font-size:14px;color:#374151;line-height:1.6;'>" . nl2br(htmlspecialchars($productDesc)) . "</p></div>"
        : '';

    $badge = "<div style='margin-bottom:20px;'><span style='background:#6b93ff;color:#fff;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;padding:5px 14px;border-radius:20px;'>Pending Review</span></div>";

    $i_body = $badge
            . "<h1 style='margin:0 0 6px;font-size:26px;font-weight:800;color:#1a1d2e;'>New Quote Request</h1>"
            . "<p style='margin:0 0 24px;font-size:15px;color:#6b7280;'>" . htmlspecialchars($clientName) . " has submitted an RFQ via their intake link.</p>"
            . $detailTable
            . $descBlock
            . $tbl
            . $appBtn;

    $subject  = "[Pending Review] Quote Request — {$clientName} / {$productName}";
    if (!empty($internal)) {
        intakeSmtpSend($internal, $subject, intakeEmailWrap($subject, "New quote request from {$clientName}", $i_body));
    }

    // Confirmation copy to the client
    if ($confirmTo !== '') {
        $c_body = "<h1 style='margin:0 0 6px;font-size:26px;font-weight:800;color:#1a1d2e;'>We received your quote request</h1>"
                . "<p style='margin:0 0 24px;font-size:15px;color:#6b7280;'>Thanks for the details — our team will follow up shortly.</p>"
                . "<p style='margin:0 0 16px;font-size:15px;color:#374151;line-height:1.7;'>"
                . "Hi " . htmlspecialchars($contact ?: 'there') . ",</p>"
                . "<p style='margin:0 0 16px;font-size:15px;color:#374151;line-height:1.7;'>"
                . "We&rsquo;ve received your request for <strong>" . htmlspecialchars($productName) . "</strong> and the team is reviewing the details now. You can expect to hear back from your Market Sculpt rep with pricing soon."
                . "</p>"
                . $tbl
                . "<p style='margin:24px 0 0;font-size:15px;color:#374151;'>Thanks,<br><strong>Market Sculpt Team</strong></p>";
        $sub = "Quote Request Received — {$productName}";
        intakeSmtpSend([$confirmTo], $sub, intakeEmailWrap($sub, "Your quote request for {$productName} was received.", $c_body));
    }
}

// ════════════════════════════════════════════════════════════════════════════
// Page chrome (matches portal.php styling so the brand feels consistent)
// ════════════════════════════════════════════════════════════════════════════
function intakePage(string $title, string $content): void {
    $css = '
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f0f2f5; color: #1a1d2e; min-height: 100vh; }
.ms-header { background: #181b26; padding: 0 28px; display: flex; align-items: center; height: 58px; gap: 16px; }
.ms-logo { font-size: 18px; font-weight: 800; color: #E8751A; border-left: 3px solid #E8751A; padding-left: 12px; letter-spacing: -0.3px; }
.ms-logo-sub { font-size: 11px; font-weight: 500; color: #6b7280; letter-spacing: 0.02em; }
.ms-bar { height: 3px; background: #E8751A; }
.ms-wrap { max-width: 920px; margin: 0 auto; padding: 40px 20px 80px; }
.card { background: #fff; border-radius: 12px; border: 1px solid #e5e7eb; overflow: hidden; margin-bottom: 20px; box-shadow: 0 1px 4px rgba(0,0,0,0.04); }
.card-head { padding: 22px 28px; border-bottom: 1px solid #f0f2f5; }
.card-body { padding: 28px; }
.section-title { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #6b93ff; margin-bottom: 14px; display: flex; align-items: center; gap: 8px; }
.page-title { font-size: 24px; font-weight: 800; color: #1a1d2e; margin-bottom: 6px; line-height: 1.2; }
.page-sub { font-size: 14px; color: #6b7280; line-height: 1.6; }
.field { display: flex; flex-direction: column; gap: 6px; margin-bottom: 16px; }
.field label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: #9ba3c0; }
.field input[type="text"], .field input[type="email"], .field input[type="number"], .field textarea, .field select {
    width: 100%; border: 1px solid #e5e7eb; border-radius: 8px; padding: 11px 14px; font-size: 14px; font-family: inherit; color: #1a1d2e; background: #fff; outline: none; transition: border-color 0.15s, box-shadow 0.15s;
}
.field input:focus, .field textarea:focus, .field select:focus { border-color: #6b93ff; box-shadow: 0 0 0 3px rgba(107,147,255,0.14); }
.field textarea { resize: vertical; min-height: 92px; line-height: 1.5; }
.field-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.field-row-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }
.dim-row { display: grid; grid-template-columns: 1fr 1fr 1fr 90px; gap: 10px; align-items: end; }
.helper { font-size: 12px; color: #9ba3c0; margin-top: 4px; line-height: 1.5; }
table.rfq-table { width: 100%; border-collapse: collapse; }
table.rfq-table thead th { padding: 10px 10px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #9ba3c0; background: #f8f9fb; border-bottom: 1px solid #e5e7eb; text-align: left; white-space: nowrap; }
table.rfq-table tbody td { padding: 8px 6px; vertical-align: top; }
table.rfq-table tbody td:first-child { padding-left: 10px; }
table.rfq-table tbody td:last-child { padding-right: 10px; }
table.rfq-table input { width: 100%; border: 1px solid #e5e7eb; border-radius: 7px; padding: 9px 11px; font-size: 13px; font-family: inherit; color: #1a1d2e; background: #fff; outline: none; transition: border-color 0.15s, box-shadow 0.15s; }
table.rfq-table input:focus { border-color: #6b93ff; box-shadow: 0 0 0 2px rgba(107,147,255,0.12); }
table.rfq-table .num-col { text-align: center; color: #9ba3c0; font-weight: 600; font-size: 13px; padding-top: 16px; }
.rfq-remove { display: inline-flex; width: 22px; height: 22px; border-radius: 50%; background: #f8f9fb; color: #9ba3c0; border: 1px solid #e5e7eb; font-size: 14px; line-height: 1; align-items: center; justify-content: center; cursor: pointer; transition: all 0.15s; margin-top: 8px; }
.rfq-remove:hover { background: #fee2e2; color: #dc2626; border-color: #fecaca; }
.add-row-btn { background: none; border: 1px dashed #d1d5db; border-radius: 8px; color: #6b7280; font-size: 13px; font-weight: 600; padding: 11px 16px; cursor: pointer; font-family: inherit; transition: all 0.15s; display: inline-flex; align-items: center; gap: 8px; margin-top: 12px; }
.add-row-btn:hover { border-color: #E8751A; color: #E8751A; background: #fff8f5; }

/* Import SKUs — button next to RFQ section title + modal */
.import-skus-btn { display: inline-flex; align-items: center; gap: 6px; flex-shrink: 0; background: #fff; border: 1px solid #d1d5db; border-radius: 8px; color: #1a1d2e; font-size: 13px; font-weight: 600; padding: 8px 12px; cursor: pointer; font-family: inherit; transition: all 0.15s; }
.import-skus-btn:hover { border-color: #6b93ff; color: #6b93ff; background: #f5f8ff; }
.import-modal-overlay { position: fixed; inset: 0; background: rgba(15,20,40,0.55); display: none; align-items: flex-start; justify-content: center; padding: 32px 16px; z-index: 100; overflow-y: auto; }
.import-modal-overlay.open { display: flex; }
.import-modal { background: #fff; border-radius: 14px; max-width: 640px; width: 100%; padding: 22px 22px 18px; box-shadow: 0 20px 60px rgba(0,0,0,0.25); }
.import-modal h3 { margin: 0 0 6px; font-size: 18px; font-weight: 700; color: #1a1d2e; }
.import-modal .help { margin: 0 0 14px; font-size: 13px; color: #6b7280; line-height: 1.5; }
.import-modal .file-row { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; flex-wrap: wrap; }
.import-modal .file-label { display: inline-flex; align-items: center; gap: 6px; background: #f8f9fb; border: 1px solid #e5e7eb; border-radius: 7px; padding: 8px 12px; font-size: 13px; font-weight: 600; color: #1a1d2e; cursor: pointer; }
.import-modal .file-label:hover { border-color: #6b93ff; color: #6b93ff; }
.import-modal .file-label input { display: none; }
.import-modal textarea { width: 100%; min-height: 180px; border: 1px solid #e5e7eb; border-radius: 8px; padding: 10px 12px; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 12px; color: #1a1d2e; outline: none; box-sizing: border-box; resize: vertical; }
.import-modal textarea:focus { border-color: #6b93ff; box-shadow: 0 0 0 2px rgba(107,147,255,0.12); }
.import-preview { margin-top: 10px; max-height: 220px; overflow: auto; border: 1px solid #e5e7eb; border-radius: 8px; background: #f8f9fb; padding: 8px 10px; font-size: 12px; }
.import-preview table { width: 100%; border-collapse: collapse; }
.import-preview th { text-align: left; padding: 4px 8px; color: #6b7280; font-weight: 700; font-size: 10px; text-transform: uppercase; letter-spacing: 0.04em; }
.import-preview td { padding: 3px 8px; color: #1a1d2e; border-top: 1px solid #eef0f5; vertical-align: top; }
.import-error { margin-top: 8px; font-size: 12px; color: #dc2626; }
.import-modal .actions { display: flex; gap: 8px; justify-content: flex-end; margin-top: 14px; flex-wrap: wrap; }
.import-modal .btn-secondary { background: #fff; border: 1px solid #d1d5db; border-radius: 8px; color: #1a1d2e; font-size: 13px; font-weight: 600; padding: 9px 14px; cursor: pointer; font-family: inherit; }
.import-modal .btn-secondary:hover { border-color: #9ca3af; }
.import-modal .btn-primary { background: #E8751A; border: 1px solid #E8751A; border-radius: 8px; color: #fff; font-size: 13px; font-weight: 700; padding: 9px 16px; cursor: pointer; font-family: inherit; }
.import-modal .btn-primary[disabled] { opacity: 0.4; cursor: not-allowed; }
.import-modal .btn-primary:not([disabled]):hover { background: #d56214; border-color: #d56214; }
.import-modal .mode-toggle { display: inline-flex; gap: 4px; margin-bottom: 10px; padding: 3px; background: #f8f9fb; border-radius: 8px; }
.import-modal .mode-btn { background: transparent; border: none; padding: 5px 10px; border-radius: 6px; font-size: 12px; font-weight: 600; color: #6b7280; cursor: pointer; font-family: inherit; }
.import-modal .mode-btn.active { background: #fff; color: #1a1d2e; box-shadow: 0 1px 2px rgba(0,0,0,0.06); }

/* Variant section — sub-rows under each parent line item. Indented
   so they read as nested under the parent; smaller font + lighter
   styling to keep visual weight below the parent row. */
.variant-section-row td { border: 0; }
.variant-list { display: flex; flex-direction: column; gap: 6px; padding-left: 12px; }
.variant-list:empty { padding: 0; }
.variant-row {
    display: grid;
    grid-template-columns: 1fr 110px 28px;
    gap: 8px;
    align-items: center;
    padding: 4px 0;
}
.variant-row input {
    width: 100%;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    padding: 8px 10px;
    font-size: 13px;
    font-family: inherit;
    color: #1a1d2e;
    background: #fff;
    outline: none;
    transition: border-color 0.15s, box-shadow 0.15s;
}
.variant-row input:focus {
    border-color: #6b93ff;
    box-shadow: 0 0 0 2px rgba(107,147,255,0.12);
}
.variant-remove {
    display: inline-flex;
    width: 22px; height: 22px;
    border-radius: 50%;
    background: #f8f9fb;
    color: #9ba3c0;
    border: 1px solid #e5e7eb;
    font-size: 14px;
    line-height: 1;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.15s;
}
.variant-remove:hover { background: #fee2e2; color: #dc2626; border-color: #fecaca; }
.add-variant-btn {
    background: none;
    border: 0;
    padding: 4px 8px;
    margin-top: 4px;
    margin-left: 12px;
    font-size: 12px;
    font-weight: 600;
    color: #6b93ff;
    cursor: pointer;
    font-family: inherit;
    border-radius: 6px;
    transition: background 0.15s, color 0.15s;
}
.add-variant-btn:hover { background: rgba(107,147,255,0.10); color: #4a7fff; }
@media (max-width: 720px) {
    .variant-row {
        grid-template-columns: 1fr 80px 24px;
        gap: 6px;
    }
    .variant-row input { padding: 6px 8px; font-size: 12px; }
    .variant-list { padding-left: 8px; }
    .add-variant-btn { margin-left: 8px; }
}
.action-bar { display: flex; justify-content: flex-end; gap: 12px; align-items: center; padding: 20px 28px; border-top: 1px solid #f0f2f5; background: #f8f9fb; }
.action-hint { font-size: 12px; color: #9ba3c0; margin-right: auto; line-height: 1.5; }
.submit-btn {
    display: inline-flex; align-items: center; gap: 8px;
    background: #E8751A; color: #fff; border: none; border-radius: 8px;
    font-size: 15px; font-weight: 700; padding: 13px 32px; cursor: pointer;
    font-family: inherit; transition: background 0.15s, opacity 0.15s;
}
.submit-btn:hover { background: #d4661a; }
.submit-btn:disabled { opacity: 0.6; cursor: not-allowed; }
.footer-bar { text-align: center; padding: 32px 20px; color: #9ba3c0; font-size: 12px; line-height: 1.8; }
.footer-bar a { color: #E8751A; text-decoration: none; }
@media (max-width: 720px) {
    .ms-wrap { padding: 16px 12px 60px; }
    .card-body { padding: 16px; }
    .card-head { padding: 16px; }
    .field-row, .field-row-3 { grid-template-columns: 1fr; gap: 8px; }
    .dim-row { grid-template-columns: 1fr 1fr; }
    .action-bar { flex-direction: column-reverse; padding: 16px; }
    .submit-btn { width: 100%; justify-content: center; }
    .action-hint { text-align: center; margin-right: 0; margin-bottom: 8px; }
    table.rfq-table { font-size: 13px; }
    table.rfq-table thead { display: none; }
    table.rfq-table tbody td { display: block; padding: 6px 0; }
    table.rfq-table tbody tr { display: block; padding: 14px 0; border-bottom: 1px solid #f0f2f5; }
    table.rfq-table .num-col { padding: 0 0 6px; text-align: left; }
    table.rfq-table tbody td::before { content: attr(data-label); display: block; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #9ba3c0; margin-bottom: 4px; }
    .rfq-remove { margin-top: 4px; }
}';

    echo '<!DOCTYPE html><html lang="en"><head>'
       . '<meta charset="UTF-8">'
       . '<meta name="viewport" content="width=device-width,initial-scale=1">'
       . '<link rel="icon" type="image/svg+xml" href="favicon.svg">'
       . '<title>' . htmlspecialchars($title) . ' — Market Sculpt</title>'
       . '<style>' . $css . '</style>'
       . '</head><body>'
       . '<header class="ms-header">'
       . '<div class="ms-logo">Market Sculpt</div>'
       . '<div class="ms-logo-sub">Quote Intake</div>'
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
         . '<h1 style="font-size:22px;font-weight:800;color:#1a1d2e;margin-bottom:12px;">We can&rsquo;t open this link</h1>'
         . '<p style="font-size:15px;color:#6b7280;max-width:460px;margin:0 auto;line-height:1.7;">' . htmlspecialchars($msg) . '</p>'
         . '</div>';
}

function doneContent(string $msgHtml): string {
    return '<div style="text-align:center;padding:80px 24px;">'
         . '<div style="width:68px;height:68px;border-radius:50%;background:#dcfce7;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;">'
         . '<svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg></div>'
         . '<span style="background:#dcfce7;color:#166534;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;padding:5px 14px;border-radius:20px;display:inline-block;">Submitted</span>'
         . '<h1 style="font-size:26px;font-weight:800;color:#1a1d2e;margin:18px 0 12px;">Quote Request Received</h1>'
         . '<p style="font-size:15px;color:#374151;max-width:520px;margin:0 auto;line-height:1.7;">' . $msgHtml . '</p>'
         . '</div>';
}

function formContent(string $clientName, string $contactName, string $token): string {
    $contactSafe = htmlspecialchars($contactName);
    $clientSafe  = htmlspecialchars($clientName);
    $tokSafe     = htmlspecialchars($token);

    ob_start();
    ?>
    <div style="margin-bottom:22px;">
      <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#6b93ff;">For <?= $clientSafe ?></div>
      <h1 class="page-title" style="margin-top:6px;">Tell us what you&rsquo;d like to quote</h1>
      <p class="page-sub">Fill in a quick product overview and the items you&rsquo;d like priced. We&rsquo;ll review and follow up with pricing — usually within a couple business days.</p>
    </div>

    <form method="POST" id="intake-form" autocomplete="off">
      <input type="hidden" name="t" value="<?= $tokSafe ?>" />

      <!-- ── PRODUCT OVERVIEW ────────────────────────────────────────────── -->
      <div class="card">
        <div class="card-head">
          <div class="section-title">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
            Product Overview
          </div>
        </div>
        <div class="card-body">
          <div class="field">
            <label>Your Name</label>
            <input type="text" name="contact_name" value="<?= $contactSafe ?>" placeholder="Who should we follow up with?" required />
          </div>
          <div class="field">
            <label>Product Name</label>
            <input type="text" name="product_name" placeholder="e.g. Custom Tote Bag, Branded Pen Set" required />
          </div>
          <div class="field">
            <label>Product Description</label>
            <textarea name="product_desc" placeholder="Describe what you&rsquo;d like made — intended use, key features, special requirements, references…"></textarea>
          </div>
          <div class="field-row">
            <div class="field">
              <label>Materials / Construction</label>
              <input type="text" name="materials" placeholder="e.g. recycled cotton, 304 stainless, PP plastic" />
            </div>
            <div class="field">
              <label>Pantone / Color Reference</label>
              <input type="text" name="pantone" placeholder="e.g. PMS 158C, custom navy blue" />
            </div>
          </div>
          <div class="field">
            <label>Color Notes (optional)</label>
            <input type="text" name="color_notes" placeholder="Anything else about color, finish, print, or coatings" />
          </div>

          <div style="margin-top:20px;">
            <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#9ba3c0;margin-bottom:10px;">Approximate Dimensions (optional)</div>
            <div class="dim-row">
              <div class="field" style="margin:0;">
                <label>Length</label>
                <input type="text" name="dim_l" placeholder="0" />
              </div>
              <div class="field" style="margin:0;">
                <label>Width</label>
                <input type="text" name="dim_w" placeholder="0" />
              </div>
              <div class="field" style="margin:0;">
                <label>Height</label>
                <input type="text" name="dim_h" placeholder="0" />
              </div>
              <div class="field" style="margin:0;">
                <label>Unit</label>
                <select name="dim_unit">
                  <option value="in">in</option>
                  <option value="cm">cm</option>
                </select>
              </div>
            </div>
            <p class="helper">Approximate is fine — our team will confirm exact specs during the quoting process.</p>
          </div>
        </div>
      </div>

      <!-- ── RFQ LINE ITEMS ──────────────────────────────────────────────── -->
      <div class="card">
        <div class="card-head" style="display:flex; align-items:flex-start; justify-content:space-between; gap:12px; flex-wrap:wrap;">
          <div style="min-width:0; flex:1 1 280px;">
            <div class="section-title">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
              RFQ Line Items
            </div>
            <p class="page-sub" style="margin-top:6px;">List each item you&rsquo;d like priced. Use the <strong>+ Add variant</strong> link under any item to break down qtys by size, color, or finish — those will land as proper variants on our side.</p>
          </div>
          <button type="button" class="import-skus-btn" onclick="openImportSkus()" title="Paste from Excel or upload a CSV to bulk-populate these rows">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
            Import SKUs
          </button>
        </div>
        <div class="card-body" style="padding:0;">
          <table class="rfq-table" id="rfq-table">
            <thead>
              <tr>
                <th style="width:36px;text-align:center;">#</th>
                <th>Item</th>
                <th>SKU (optional)</th>
                <th style="width:110px;">Quantity</th>
                <th>Notes</th>
                <th style="width:32px;"></th>
              </tr>
            </thead>
            <tbody id="rfq-body">
              <tr data-row="0" data-row-type="parent">
                <td class="num-col">1</td>
                <td data-label="Item"><input type="text" name="items[0][item]" placeholder="e.g. Tote Bag" /></td>
                <td data-label="SKU"><input type="text" name="items[0][sku]"  placeholder="optional" /></td>
                <td data-label="Quantity"><input type="text" inputmode="numeric" name="items[0][qty]" placeholder="0" /></td>
                <td data-label="Notes"><input type="text" name="items[0][notes]" placeholder="any extra detail…" /></td>
                <td><span class="rfq-remove" onclick="removeRfqRow(this)" title="Remove">&times;</span></td>
              </tr>
              <tr class="variant-section-row" data-parent="0">
                <td></td>
                <td colspan="5" style="padding:0 6px 12px;">
                  <div class="variant-list" id="variants-0"></div>
                  <button type="button" class="add-variant-btn" onclick="addVariant(0)">
                    + Add variant (size, color, finish&hellip;)
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
          <div style="padding:14px 24px 18px;">
            <button type="button" class="add-row-btn" onclick="addRfqRow()">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
              Add another item
            </button>
          </div>
        </div>
        <div class="action-bar">
          <p class="action-hint">By submitting, you&rsquo;ll receive a confirmation email and our team will follow up with pricing.</p>
          <button type="submit" class="submit-btn" id="submit-btn">
            Submit Quote Request &rarr;
          </button>
        </div>
      </div>
    </form>

    <!-- Import SKUs modal — opens via the Import SKUs button on the
         RFQ section header. Two input modes (Paste or Upload), live
         preview of the parsed rows, then Import appends each parsed
         row to the RFQ table via the existing addRfqRow + name="…"
         input contract so the PHP submit handler doesn't change. -->
    <div class="import-modal-overlay" id="import-modal-overlay" onclick="if(event.target===this)closeImportSkus()">
      <div class="import-modal" role="dialog" aria-labelledby="import-modal-title">
        <h3 id="import-modal-title">Import SKUs</h3>
        <p class="help">
          Bulk-add line items from a spreadsheet. <strong>Paste</strong> directly from Excel / Google Sheets (just select cells and copy), or <strong>upload</strong> a CSV file. We expect columns in this order: <strong>Item · SKU · Quantity · Notes</strong> (a header row is auto-detected and skipped). <em>Tip: if you have an .xlsx, use File → Download → Comma-separated values first.</em>
        </p>
        <div class="mode-toggle" role="tablist">
          <button type="button" class="mode-btn active" id="import-mode-paste" onclick="setImportMode('paste')">Paste from spreadsheet</button>
          <button type="button" class="mode-btn" id="import-mode-file" onclick="setImportMode('file')">Upload CSV</button>
        </div>
        <div id="import-paste-pane">
          <textarea id="import-textarea" placeholder="Paste rows here…&#10;Item	SKU	Qty	Notes&#10;Tote Bag	TB-001	500	natural canvas&#10;Polo Shirt	POL-23	250	embroidered logo" oninput="renderImportPreview()"></textarea>
        </div>
        <div id="import-file-pane" style="display:none;">
          <div class="file-row">
            <label class="file-label">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
              Choose CSV file…
              <input type="file" id="import-file" accept=".csv,text/csv" onchange="onImportFile(event)" />
            </label>
            <span id="import-file-name" style="font-size:12px; color:#6b7280;">No file selected</span>
          </div>
        </div>
        <div id="import-preview-wrap" style="display:none;">
          <div style="margin-top:10px; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:#6b7280;">
            Preview — <span id="import-preview-count">0</span> row<span id="import-preview-s">s</span> to add
          </div>
          <div class="import-preview" id="import-preview-body"></div>
        </div>
        <div class="import-error" id="import-error" style="display:none;"></div>
        <div class="actions">
          <label style="display:inline-flex; align-items:center; gap:6px; font-size:12px; color:#6b7280; margin-right:auto;">
            <input type="checkbox" id="import-replace" />
            Replace existing rows (otherwise we append)
          </label>
          <button type="button" class="btn-secondary" onclick="closeImportSkus()">Cancel</button>
          <button type="button" class="btn-primary" id="import-apply-btn" onclick="applyImport()" disabled>Import 0</button>
        </div>
      </div>
    </div>

    <script>
      // Each parent line item gets a unique numeric index. Variant input
      // names follow PHP's array notation:
      //   items[<parentIdx>][variants][<variantIdx>][name]
      //   items[<parentIdx>][variants][<variantIdx>][qty]
      // The intake.php submit handler walks $_POST['items'][i]['variants']
      // and emits a real variants[] on the rfqItem so the operator's
      // RFQ table renders proper variant rows on the workbook side.
      let _rfqRows = 1;
      // Per-parent variant counter so each variant input name is unique
      // even after rows are removed and re-added.
      const _variantCounters = { 0: 0 };

      function _renumberRfqRows() {
        const tbody = document.getElementById('rfq-body');
        let n = 1;
        tbody.querySelectorAll('tr[data-row-type="parent"]').forEach(row => {
          const numCell = row.querySelector('.num-col');
          if (numCell) numCell.textContent = n++;
        });
      }

      function addRfqRow() {
        const tbody = document.getElementById('rfq-body');
        const idx = _rfqRows++;
        _variantCounters[idx] = 0;
        const parent = document.createElement('tr');
        parent.setAttribute('data-row', idx);
        parent.setAttribute('data-row-type', 'parent');
        parent.innerHTML = `
          <td class="num-col">?</td>
          <td data-label="Item"><input type="text" name="items[${idx}][item]" placeholder="e.g. Polo Shirt" /></td>
          <td data-label="SKU"><input type="text" name="items[${idx}][sku]"  placeholder="optional" /></td>
          <td data-label="Quantity"><input type="text" inputmode="numeric" name="items[${idx}][qty]" placeholder="0" /></td>
          <td data-label="Notes"><input type="text" name="items[${idx}][notes]" placeholder="any extra detail…" /></td>
          <td><span class="rfq-remove" onclick="removeRfqRow(this)" title="Remove">&times;</span></td>
        `;
        const section = document.createElement('tr');
        section.className = 'variant-section-row';
        section.setAttribute('data-parent', idx);
        section.innerHTML = `
          <td></td>
          <td colspan="5" style="padding:0 6px 12px;">
            <div class="variant-list" id="variants-${idx}"></div>
            <button type="button" class="add-variant-btn" onclick="addVariant(${idx})">
              + Add variant (size, color, finish&hellip;)
            </button>
          </td>
        `;
        tbody.appendChild(parent);
        tbody.appendChild(section);
        _renumberRfqRows();
      }

      function removeRfqRow(span) {
        const tbody = document.getElementById('rfq-body');
        const parents = tbody.querySelectorAll('tr[data-row-type="parent"]');
        const tr = span.closest('tr');
        const idx = tr.getAttribute('data-row');
        if (parents.length <= 1) {
          // Don't let them delete the last parent row — just clear inputs
          tr.querySelectorAll('input').forEach(i => i.value = '');
          // Also clear any variants this parent has
          const list = document.getElementById('variants-' + idx);
          if (list) list.innerHTML = '';
          return;
        }
        // Remove the parent row + its variant section row
        const section = tbody.querySelector(`tr.variant-section-row[data-parent="${idx}"]`);
        tr.remove();
        if (section) section.remove();
        delete _variantCounters[idx];
        _renumberRfqRows();
      }

      function addVariant(parentIdx) {
        const list = document.getElementById('variants-' + parentIdx);
        if (!list) return;
        const vIdx = (_variantCounters[parentIdx] = (_variantCounters[parentIdx] || 0) + 1) - 1;
        const row = document.createElement('div');
        row.className = 'variant-row';
        row.setAttribute('data-variant-row', vIdx);
        row.innerHTML = `
          <input type="text" name="items[${parentIdx}][variants][${vIdx}][name]" placeholder="Variant (e.g. Black, Large, Glossy)" />
          <input type="text" inputmode="numeric" name="items[${parentIdx}][variants][${vIdx}][qty]" placeholder="Qty" />
          <span class="variant-remove" onclick="removeVariant(this)" title="Remove variant">&times;</span>
        `;
        list.appendChild(row);
        // Auto-focus the name field so the user can start typing right away
        row.querySelector('input')?.focus();
      }

      function removeVariant(span) {
        const row = span.closest('.variant-row');
        if (row) row.remove();
      }

      // Prevent double-submit
      document.getElementById('intake-form').addEventListener('submit', function(e) {
        const btn = document.getElementById('submit-btn');
        btn.disabled = true;
        btn.textContent = 'Submitting…';
      });

      // ── Import SKUs flow ────────────────────────────────────────
      // Parses TSV (Excel-paste default) or CSV pasted/uploaded by
      // the client, previews the rows, and on Import calls the
      // existing addRfqRow for each parsed row + writes the values
      // into the rendered inputs. No new server contract needed —
      // the PHP submit handler reads the same items[<idx>][...]
      // input names it always has.
      let _importParsed = [];

      function openImportSkus() {
        document.getElementById('import-modal-overlay').classList.add('open');
        renderImportPreview();
      }
      function closeImportSkus() {
        document.getElementById('import-modal-overlay').classList.remove('open');
      }
      function setImportMode(mode) {
        document.getElementById('import-mode-paste').classList.toggle('active', mode === 'paste');
        document.getElementById('import-mode-file').classList.toggle('active',  mode === 'file');
        document.getElementById('import-paste-pane').style.display = mode === 'paste' ? '' : 'none';
        document.getElementById('import-file-pane').style.display  = mode === 'file'  ? '' : 'none';
      }

      function onImportFile(ev) {
        const file = ev.target.files && ev.target.files[0];
        if (!file) return;
        document.getElementById('import-file-name').textContent = file.name;
        const reader = new FileReader();
        reader.onload = e => {
          const text = String(e.target.result || '');
          // Pipe file content through the same parser as paste —
          // ditch the textarea contents so the operator doesn't
          // accidentally import both at once.
          document.getElementById('import-textarea').value = text;
          renderImportPreview();
        };
        reader.onerror = () => showImportError('Could not read that file. Try saving as CSV again.');
        reader.readAsText(file);
      }

      function showImportError(msg) {
        const el = document.getElementById('import-error');
        el.textContent = msg;
        el.style.display = msg ? '' : 'none';
      }

      // Detect the separator (tab beats comma since Excel paste is
      // tab-delimited, but fall back to comma for true CSV files).
      // Then a tiny RFC4180-ish parser handles quoted commas/newlines
      // inside cells — covers the realistic spreadsheet cases without
      // pulling in a full CSV library.
      function _detectSep(text) {
        const firstLine = text.split(/\r?\n/, 1)[0] || '';
        if (firstLine.indexOf('\t') !== -1) return '\t';
        return ',';
      }
      function _parseDelimited(text, sep) {
        const rows = [];
        let field = '', row = [], inQuotes = false;
        for (let i = 0; i < text.length; i++) {
          const c = text[i];
          if (inQuotes) {
            if (c === '"') {
              if (text[i + 1] === '"') { field += '"'; i++; }
              else inQuotes = false;
            } else field += c;
            continue;
          }
          if (c === '"') { inQuotes = true; continue; }
          if (c === sep) { row.push(field); field = ''; continue; }
          if (c === '\n' || c === '\r') {
            if (c === '\r' && text[i + 1] === '\n') i++;
            row.push(field); field = '';
            if (row.some(v => String(v).trim() !== '')) rows.push(row);
            row = [];
            continue;
          }
          field += c;
        }
        if (field !== '' || row.length) {
          row.push(field);
          if (row.some(v => String(v).trim() !== '')) rows.push(row);
        }
        return rows;
      }
      function _looksLikeHeader(cells) {
        const hay = cells.map(c => String(c).toLowerCase().trim()).join('|');
        return /(item|product|sku|qty|quantity|note)/.test(hay);
      }
      // Column-name → field heuristic for header-row mapping; falls
      // back to positional [item, sku, qty, notes] when no header.
      function _mapColumns(headerCells) {
        const map = { item: -1, sku: -1, qty: -1, notes: -1 };
        headerCells.forEach((h, i) => {
          const k = String(h).toLowerCase().trim();
          if (map.item  < 0 && /^(item|product|product\s*name|description|name)/.test(k)) map.item  = i;
          if (map.sku   < 0 && /^(sku|code|item\s*#|item\s*number)/.test(k))             map.sku   = i;
          if (map.qty   < 0 && /^(qty|quantity|units|count)/.test(k))                    map.qty   = i;
          if (map.notes < 0 && /^(notes?|comment|remark)/.test(k))                       map.notes = i;
        });
        return map;
      }
      function parseImportInput() {
        const text = document.getElementById('import-textarea').value || '';
        if (!text.trim()) return [];
        const sep = _detectSep(text);
        const all = _parseDelimited(text, sep);
        if (all.length === 0) return [];
        let map = { item: 0, sku: 1, qty: 2, notes: 3 };
        let body = all;
        if (_looksLikeHeader(all[0])) {
          const mapped = _mapColumns(all[0]);
          // Use the mapped columns when at least item OR sku came back
          if (mapped.item >= 0 || mapped.sku >= 0) map = mapped;
          body = all.slice(1);
        }
        const out = [];
        body.forEach(cells => {
          const item  = (map.item  >= 0 && cells[map.item]  != null) ? String(cells[map.item]).trim()  : '';
          const sku   = (map.sku   >= 0 && cells[map.sku]   != null) ? String(cells[map.sku]).trim()   : '';
          const qty   = (map.qty   >= 0 && cells[map.qty]   != null) ? String(cells[map.qty]).trim()   : '';
          const notes = (map.notes >= 0 && cells[map.notes] != null) ? String(cells[map.notes]).trim() : '';
          if (!item && !sku && !qty && !notes) return;
          out.push({ item, sku, qty, notes });
        });
        return out;
      }

      function renderImportPreview() {
        showImportError('');
        _importParsed = parseImportInput();
        const wrap   = document.getElementById('import-preview-wrap');
        const body   = document.getElementById('import-preview-body');
        const count  = document.getElementById('import-preview-count');
        const plural = document.getElementById('import-preview-s');
        const apply  = document.getElementById('import-apply-btn');
        if (_importParsed.length === 0) {
          wrap.style.display = 'none';
          apply.disabled = true;
          apply.textContent = 'Import 0';
          return;
        }
        wrap.style.display = '';
        count.textContent  = _importParsed.length;
        plural.textContent = _importParsed.length === 1 ? '' : 's';
        const esc = s => String(s == null ? '' : s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
        const head = '<table><thead><tr><th>Item</th><th>SKU</th><th>Qty</th><th>Notes</th></tr></thead><tbody>';
        const rows = _importParsed.slice(0, 50).map(r =>
          `<tr><td>${esc(r.item) || '<em style="color:#9ba3c0">—</em>'}</td><td>${esc(r.sku) || '<em style="color:#9ba3c0">—</em>'}</td><td>${esc(r.qty) || '<em style="color:#9ba3c0">—</em>'}</td><td>${esc(r.notes) || '<em style="color:#9ba3c0">—</em>'}</td></tr>`
        ).join('');
        const tail = _importParsed.length > 50
          ? `<tr><td colspan="4" style="text-align:center; color:#9ba3c0; font-style:italic;">+${_importParsed.length - 50} more not shown</td></tr></tbody></table>`
          : '</tbody></table>';
        body.innerHTML = head + rows + tail;
        apply.disabled = false;
        apply.textContent = `Import ${_importParsed.length}`;
      }

      function applyImport() {
        if (_importParsed.length === 0) return;
        const replace = document.getElementById('import-replace').checked;
        const tbody = document.getElementById('rfq-body');
        if (replace) {
          // Clear all parent rows + their variant sections, then add
          // back a single seed parent that addRfqRow will replace
          // (avoids the "last row can't be removed" guard).
          tbody.querySelectorAll('tr').forEach(tr => tr.remove());
          _rfqRows = 0;
          for (const k of Object.keys(_variantCounters)) delete _variantCounters[k];
        }
        _importParsed.forEach(r => {
          addRfqRow();
          // The row just added is the last parent — populate its inputs.
          const last = tbody.querySelectorAll('tr[data-row-type="parent"]');
          const parent = last[last.length - 1];
          if (!parent) return;
          const setVal = (sel, v) => { const i = parent.querySelector(sel); if (i && v) i.value = v; };
          setVal('input[name$="[item]"]',  r.item);
          setVal('input[name$="[sku]"]',   r.sku);
          setVal('input[name$="[qty]"]',   r.qty);
          setVal('input[name$="[notes]"]', r.notes);
        });
        closeImportSkus();
        // Reset modal state for next open
        document.getElementById('import-textarea').value = '';
        document.getElementById('import-file').value = '';
        document.getElementById('import-file-name').textContent = 'No file selected';
        document.getElementById('import-replace').checked = false;
        renderImportPreview();
      }
    </script>
    <?php
    return ob_get_clean();
}
