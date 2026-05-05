<?php
// Market Sculpt Workbook API
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$host = 'localhost';
$db   = 'markewq4_workbook';
$user = 'markewq4_workbook';
$pass = 'MarketFun123';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

require_once __DIR__ . '/auth.php';
requireAuth();
$sessionUser = getSessionUser();

// Auto-create revisions table if not exists
$pdo->exec("CREATE TABLE IF NOT EXISTS workbook_revisions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    workbook_id INT NOT NULL,
    detail_json LONGTEXT,
    changed_by VARCHAR(255) DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_workbook_date (workbook_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Auto-create users table
$pdo->exec("CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    display_name VARCHAR(100) NOT NULL DEFAULT '',
    email VARCHAR(255) DEFAULT NULL,
    role ENUM('admin','user') NOT NULL DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
// Email is a later add — existing deployments missed it on initial create,
// so this idempotent ALTER backfills the column without a real migration.
try {
    $pdo->exec("ALTER TABLE users ADD COLUMN email VARCHAR(255) DEFAULT NULL");
} catch (PDOException $e) { /* column already exists */ }
// Per-user commission rate (%). NULL → use the role default (currently 10%
// for operations / Karen). Stored as percent (10.00 = 10%). DECIMAL(5,2)
// supports 0.00–999.99 for headroom on bonus structures we might layer on
// later. Edited from User Management → user detail pane (admin only).
try {
    $pdo->exec("ALTER TABLE users ADD COLUMN commission_pct DECIMAL(5,2) DEFAULT NULL");
} catch (PDOException $e) { /* column already exists */ }

// Seed default admin if no users exist
$userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
if ($userCount == 0) {
    $hash = password_hash('MarketSculpt2025!', PASSWORD_DEFAULT);
    $pdo->prepare("INSERT INTO users (username, password_hash, display_name, role) VALUES (?, ?, ?, ?)")
        ->execute(['admin', $hash, 'Admin', 'admin']);
}

// Auto-add soft-delete columns if not present
try {
    $pdo->exec("ALTER TABLE workbooks ADD COLUMN deleted_at DATETIME DEFAULT NULL");
} catch (PDOException $e) { /* column already exists */ }
try {
    $pdo->exec("ALTER TABLE workbooks ADD COLUMN deleted_by VARCHAR(255) DEFAULT NULL");
} catch (PDOException $e) { /* column already exists */ }
try {
    $pdo->exec("ALTER TABLE clients ADD COLUMN deleted_at DATETIME DEFAULT NULL");
} catch (PDOException $e) { /* column already exists */ }
try {
    $pdo->exec("ALTER TABLE clients ADD COLUMN deleted_by VARCHAR(255) DEFAULT NULL");
} catch (PDOException $e) { /* column already exists */ }
try {
    $pdo->exec("ALTER TABLE clients ADD COLUMN email VARCHAR(255) DEFAULT NULL");
} catch (PDOException $e) { /* column already exists */ }
try {
    $pdo->exec("ALTER TABLE clients ADD COLUMN phone VARCHAR(100) DEFAULT NULL");
} catch (PDOException $e) { /* column already exists */ }
try {
    $pdo->exec("ALTER TABLE clients ADD COLUMN primary_contact VARCHAR(255) DEFAULT NULL");
} catch (PDOException $e) { /* column already exists */ }
try {
    $pdo->exec("ALTER TABLE clients ADD COLUMN billing_address TEXT DEFAULT NULL");
} catch (PDOException $e) { /* column already exists */ }
try {
    $pdo->exec("ALTER TABLE clients ADD COLUMN shipping_address TEXT DEFAULT NULL");
} catch (PDOException $e) { /* column already exists */ }
try {
    $pdo->exec("ALTER TABLE clients ADD COLUMN notes TEXT DEFAULT NULL");
} catch (PDOException $e) { /* column already exists */ }
try {
    $pdo->exec("ALTER TABLE clients ADD COLUMN account_manager VARCHAR(255) DEFAULT NULL");
} catch (PDOException $e) { /* column already exists */ }
try {
    $pdo->exec("ALTER TABLE clients ADD COLUMN salesperson VARCHAR(255) DEFAULT NULL");
} catch (PDOException $e) { /* column already exists */ }
// Per-client commission rate overrides (stored as percent — 20.00 means 20%).
// NULL = use the default rate at compute time. Decimal(5,2) gives us up to
// 999.99 which is way more than enough headroom.
try {
    $pdo->exec("ALTER TABLE clients ADD COLUMN account_manager_pct DECIMAL(5,2) DEFAULT NULL");
} catch (PDOException $e) { /* column already exists */ }
try {
    $pdo->exec("ALTER TABLE clients ADD COLUMN salesperson_pct DECIMAL(5,2) DEFAULT NULL");
} catch (PDOException $e) { /* column already exists */ }
// Optional third commission role — currently only Karen sits in this slot
// but the column accepts any name so we can grow the dropdown later
// without another migration. Default rate is 5% (resolved at compute time
// when operations_pct is NULL/blank), separate from AM/SP whose defaults
// dropped to 0% per the operator's request.
try {
    $pdo->exec("ALTER TABLE clients ADD COLUMN operations_person VARCHAR(255) DEFAULT NULL");
} catch (PDOException $e) { /* column already exists */ }
try {
    $pdo->exec("ALTER TABLE clients ADD COLUMN operations_pct DECIMAL(5,2) DEFAULT NULL");
} catch (PDOException $e) { /* column already exists */ }
// Secondary contact info — clients with two POCs (e.g. ops + finance, or
// owner + assistant) so we don't have to cram everything into Notes.
try {
    $pdo->exec("ALTER TABLE clients ADD COLUMN email2 VARCHAR(255) DEFAULT NULL");
} catch (PDOException $e) { /* column already exists */ }
try {
    $pdo->exec("ALTER TABLE clients ADD COLUMN phone2 VARCHAR(50) DEFAULT NULL");
} catch (PDOException $e) { /* column already exists */ }
try {
    $pdo->exec("ALTER TABLE clients ADD COLUMN primary_contact2 VARCHAR(255) DEFAULT NULL");
} catch (PDOException $e) { /* column already exists */ }
// Per-client default profit margin (%). NULL → JS treats as 50%.
// Stored as DECIMAL(5,2) so we can support 0.00–999.99% without precision loss.
try {
    $pdo->exec("ALTER TABLE clients ADD COLUMN default_margin_pct DECIMAL(5,2) DEFAULT NULL");
} catch (PDOException $e) { /* column already exists */ }

// Auto-create commissions table.
//   role           = 'account_manager' | 'salesperson' | 'operations'  (which hat earned this row)
//   employee       = the human's name (e.g. 'Parker Low') — denormalized so a
//                    later rename on the client doesn't rewrite history
//   client_total_usd = the dollar amount the client paid for this line, in USD
//                    (this is what gets multiplied by commission_rate)
//   commission_rate  = decimal fraction (0.20 = 20%)
//   commission_amount= client_total_usd * commission_rate, stored so reports
//                    don't have to recompute and so historical rates stick
//   status         = 'pending' | 'paid'   (room to mark payouts later)
//   The UNIQUE KEY makes promote/recompute idempotent — re-promoting the
//   same workbook for the same employee+role won't create a dup row.
$pdo->exec("CREATE TABLE IF NOT EXISTS commissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    client_name VARCHAR(255) DEFAULT NULL,
    workbook_id INT DEFAULT NULL,
    sku VARCHAR(255) DEFAULT NULL,
    product_name VARCHAR(255) DEFAULT NULL,
    role ENUM('account_manager','salesperson','operations') NOT NULL,
    employee VARCHAR(255) NOT NULL,
    client_total_usd DECIMAL(12,2) NOT NULL DEFAULT 0,
    commission_rate DECIMAL(5,4) NOT NULL DEFAULT 0.2000,
    commission_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    status ENUM('pending','paid') NOT NULL DEFAULT 'pending',
    is_estimate TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'true until Client Cost is wired and we use real values',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    paid_at TIMESTAMP NULL,
    UNIQUE KEY uq_wb_role_emp (workbook_id, role, employee),
    INDEX idx_client (client_id),
    INDEX idx_employee (employee),
    INDEX idx_status (status),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
// Existing deployments created the commissions table before 'operations'
// existed in the ENUM; CREATE TABLE IF NOT EXISTS won't widen it. This
// MODIFY is a no-op when the ENUM already includes 'operations'.
try {
    $pdo->exec("ALTER TABLE commissions MODIFY COLUMN role ENUM('account_manager','salesperson','operations') NOT NULL");
} catch (PDOException $e) { /* enum already includes operations or table missing */ }

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

// Auto-create intake_tokens table — token-gated client-portal RFQ intake.
// Operator clicks "Send RFQ Request" on a client → email goes out with a
// link → client fills Product Overview + RFQ Line Items → submission lands
// straight into the client's workbook list with a Pending Review flag.
// One-time use (status flips to 'submitted' on send, or 'expired' after
// expires_at). 30-day expiry enforced at fetch+submit time.
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

// Auto-create inventory table if not exists
$pdo->exec("CREATE TABLE IF NOT EXISTS inventory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sku VARCHAR(255) NOT NULL,
    product_name VARCHAR(255) NOT NULL DEFAULT '',
    variant_name VARCHAR(255) DEFAULT NULL,
    client_name VARCHAR(255) DEFAULT NULL,
    workbook_id INT DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    promoted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_sku (sku)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Key-value store for shared app state (orders, shipments, etc.)
$pdo->exec("CREATE TABLE IF NOT EXISTS app_state (
    key_name VARCHAR(100) NOT NULL PRIMARY KEY,
    value_json LONGTEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// ── One-shot migration: dedup workbooks + enforce uniqueness at DB level ──
// This is the final, un-bypassable safety net against duplicate workbooks.
// Application-layer guards can have bugs; a MySQL UNIQUE INDEX cannot.
// Runs once per deployment (gated via app_state key). Idempotent on re-run.
try {
    $migDone = $pdo->query("SELECT value_json FROM app_state WHERE key_name = 'migration_dedup_wb_001'")->fetchColumn();
    if ($migDone !== '1') {
        $pdo->beginTransaction();
        // Step 1: For each (client_id, product_name) group with >1 active row,
        // keep the oldest id and soft-delete the rest. This removes the
        // accidentally-duplicated workbooks created by the seed-retry bug.
        $dupRows = $pdo->query("
            SELECT client_id, product_name, MIN(id) AS keep_id, GROUP_CONCAT(id ORDER BY id) AS all_ids
            FROM workbooks
            WHERE deleted_at IS NULL
            GROUP BY client_id, product_name
            HAVING COUNT(*) > 1
        ")->fetchAll();
        $deletedCount = 0;
        foreach ($dupRows as $g) {
            $ids = array_map('intval', explode(',', $g['all_ids']));
            $keep = (int)$g['keep_id'];
            $kill = array_values(array_filter($ids, fn($x) => $x !== $keep));
            if (!empty($kill)) {
                $ph = implode(',', array_fill(0, count($kill), '?'));
                $stm = $pdo->prepare("UPDATE workbooks SET deleted_at = NOW(), deleted_by = 'auto-dedup-migration' WHERE id IN ($ph) AND deleted_at IS NULL");
                $stm->execute($kill);
                $deletedCount += $stm->rowCount();
            }
        }
        // Step 2: Enforce uniqueness at DB level via a generated column.
        // We can't just UNIQUE (client_id, product_name, deleted_at) because
        // MySQL treats multiple NULLs as distinct in unique indexes — soft-deleted
        // rows all share deleted_at=NULL... no wait, soft-deleted rows have a
        // timestamp and ACTIVE rows have NULL. We need the opposite: allow many
        // NULLed-out (soft-deleted) rows, but only one active row per group.
        //
        // Solution: a virtual generated column that is NULL for soft-deleted rows
        // and "<client_id>:<product_name>" for active rows. UNIQUE on that column
        // allows unlimited NULLs (soft-deleted) but only one non-NULL per group.
        try {
            $pdo->exec("ALTER TABLE workbooks ADD COLUMN active_dedup_key VARCHAR(300) GENERATED ALWAYS AS (IF(deleted_at IS NULL, CONCAT(client_id, ':', product_name), NULL)) VIRTUAL");
        } catch (PDOException $e) { /* column already exists */ }
        try {
            $pdo->exec("ALTER TABLE workbooks ADD UNIQUE KEY uk_wb_active_dedup (active_dedup_key)");
        } catch (PDOException $e) {
            // Index already exists OR a residual dup prevented creation.
            // Log but don't block — the dedup step above should have cleared it.
            error_log('[ms-migration] UNIQUE INDEX on active_dedup_key could not be added: ' . $e->getMessage());
        }
        // Step 3: Mark migration complete so we never rerun the dedup step
        $pdo->prepare("INSERT INTO app_state (key_name, value_json) VALUES ('migration_dedup_wb_001', ?)
                       ON DUPLICATE KEY UPDATE value_json = VALUES(value_json)")->execute(['1']);
        $pdo->prepare("INSERT INTO app_state (key_name, value_json) VALUES ('migration_dedup_wb_001_log', ?)
                       ON DUPLICATE KEY UPDATE value_json = VALUES(value_json)")->execute([
            json_encode(['ran_at' => date('c'), 'deleted_count' => $deletedCount, 'dup_groups' => count($dupRows)])
        ]);
        $pdo->commit();
    }
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    // Non-fatal: migration can retry on next request. Surface to PHP error log only.
    error_log('[ms-migration] workbook dedup migration failed: ' . $e->getMessage());
}

// Real-time presence: one row per logged-in user, updated every 5 s
$pdo->exec("CREATE TABLE IF NOT EXISTS presence (
    user_id INT NOT NULL PRIMARY KEY,
    display_name VARCHAR(100) NOT NULL DEFAULT '',
    workbook_id INT NOT NULL DEFAULT 0,
    focused_field VARCHAR(255) NOT NULL DEFAULT '',
    color CHAR(7) NOT NULL DEFAULT '#888888',
    last_seen TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Live cell values: every keystroke on a tracked field upserts the latest
// value here so other users polling the workbook can see it before save.
// Rows are keyed by (workbook_id, field_path); only one row per cell.
// updated_at is auto-updated on conflict so polling can use a `since` cursor.
$pdo->exec("CREATE TABLE IF NOT EXISTS cell_values (
    workbook_id INT NOT NULL,
    field_path VARCHAR(255) NOT NULL,
    value TEXT,
    updated_by INT NOT NULL DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (workbook_id, field_path),
    INDEX idx_wb_updated (workbook_id, updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// In-app + Chrome browser notifications. Polled by every connected
// client every 12s for fresh entries; entries persist so a user
// who comes back after a break still sees what they missed (within
// the LIMIT). Uses created_at as the cursor.
$pdo->exec("CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    kind VARCHAR(64) NOT NULL DEFAULT '',
    title VARCHAR(255) NOT NULL DEFAULT '',
    body TEXT NOT NULL,
    url VARCHAR(500) DEFAULT NULL,
    created_at TIMESTAMP(3) DEFAULT CURRENT_TIMESTAMP(3),
    read_at TIMESTAMP NULL,
    INDEX idx_user_created (user_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

// ── Email helpers ─────────────────────────────────────────────────────────────
function ms_smtp_send(array $to, string $subject, string $html): array {
    $host  = 'smtp.gmail.com';
    $port  = 587;
    $user  = 'parker@marketsculpt.com';
    $pass  = 'gcsgalchcnfnheth';
    $fname = 'Market Sculpt';

    $fp = @fsockopen('tcp://' . $host, $port, $errno, $errstr, 15);
    if (!$fp) return ['ok' => false, 'error' => "Connect failed: $errstr"];
    stream_set_timeout($fp, 15);
    fgets($fp, 512);

    fwrite($fp, "EHLO marketsculpt.com\r\n");
    do { $l = fgets($fp, 512); } while (strlen($l) >= 4 && $l[3] !== ' ');

    fwrite($fp, "STARTTLS\r\n"); fgets($fp, 512);
    stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);

    fwrite($fp, "EHLO marketsculpt.com\r\n");
    do { $l = fgets($fp, 512); } while (strlen($l) >= 4 && $l[3] !== ' ');

    fwrite($fp, "AUTH LOGIN\r\n"); fgets($fp, 512);
    fwrite($fp, base64_encode($user) . "\r\n"); fgets($fp, 512);
    fwrite($fp, base64_encode($pass) . "\r\n");
    $auth = fgets($fp, 512);
    if (strpos($auth, '235') === false) {
        fwrite($fp, "QUIT\r\n"); fclose($fp);
        return ['ok' => false, 'error' => "Auth failed: $auth"];
    }

    fwrite($fp, "MAIL FROM:<{$user}>\r\n"); fgets($fp, 512);
    foreach ($to as $addr) { fwrite($fp, "RCPT TO:<{$addr}>\r\n"); fgets($fp, 512); }
    fwrite($fp, "DATA\r\n"); fgets($fp, 512);

    $bnd  = md5(uniqid('ms', true));
    $plain = wordwrap(strip_tags(preg_replace('/<[^>]+>/', ' ', $html)), 76, "\r\n");
    $msg  = "From: {$fname} <{$user}>\r\nTo: " . implode(', ', $to) . "\r\n"
          . "Subject: {$subject}\r\nMIME-Version: 1.0\r\n"
          . "Content-Type: multipart/alternative; boundary=\"{$bnd}\"\r\n"
          . "X-Mailer: MarketSculptWorkbook/1.0\r\n\r\n"
          . "--{$bnd}\r\nContent-Type: text/plain; charset=UTF-8\r\n\r\n{$plain}\r\n"
          . "--{$bnd}\r\nContent-Type: text/html; charset=UTF-8\r\n\r\n{$html}\r\n"
          . "--{$bnd}--\r\n";

    fwrite($fp, $msg . ".\r\n");
    $sent = fgets($fp, 512);
    fwrite($fp, "QUIT\r\n"); fclose($fp);
    return strpos($sent, '250') !== false ? ['ok' => true] : ['ok' => false, 'error' => "Send failed: $sent"];
}

function ms_email_wrap(string $title, string $preheader, string $body): string {
    return '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>' . htmlspecialchars($title) . '</title></head>'
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
    . 'Questions? Reply to this email or reach us at <a href="mailto:parker@marketsculpt.com" style="color:#E8751A;text-decoration:none;">parker@marketsculpt.com</a></p>'
    . '</td></tr></table></td></tr></table></body></html>';
}

function ms_detail_table(array $rows): string {
    $html = '<table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;margin:20px 0;">';
    foreach ($rows as $i => [$label, $value]) {
        $bg = $i % 2 === 0 ? '#f8f9fb' : '#ffffff';
        $html .= "<tr style='background:{$bg};'>"
              . "<td style='padding:10px 16px;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:#9ba3c0;width:38%;'>{$label}</td>"
              . "<td style='padding:10px 16px;font-size:14px;color:#1a1d2e;'>{$value}</td></tr>";
    }
    return $html . '</table>';
}

function ms_rfq_table(array $items, float $rate): string {
    $rows = '';
    foreach ($items as $item) {
        if (!($item['item'] ?? '') && !($item['qty'] ?? '') && !($item['priceRmb'] ?? '')) continue;
        $qty   = isset($item['qty'])      && $item['qty']      > 0 ? number_format((float)$item['qty'])    : '—';
        $rmb   = isset($item['priceRmb']) && $item['priceRmb'] > 0 ? (float)$item['priceRmb']              : 0;
        $usd   = $rmb > 0 ? '$' . number_format($rmb / $rate, 2) : '—';
        $tot   = ($rmb > 0 && isset($item['qty']) && $item['qty'] > 0)
               ? '$' . number_format(($rmb / $rate) * (float)$item['qty'], 2) : '—';
        $rows .= "<tr style='border-top:1px solid #f0f2f5;'>"
              . "<td style='padding:10px 12px;font-size:14px;color:#1a1d2e;'>" . htmlspecialchars($item['item'] ?? '—') . "</td>"
              . "<td style='padding:10px 12px;font-size:14px;color:#6b7280;text-align:center;'>{$qty}</td>"
              . "<td style='padding:10px 12px;font-size:14px;color:#1a1d2e;text-align:right;'>{$usd}</td>"
              . "<td style='padding:10px 12px;font-size:14px;font-weight:700;color:#1a1d2e;text-align:right;'>{$tot}</td></tr>";
    }
    $thead = '<thead><tr style="background:#f8f9fb;">'
           . '<th style="padding:10px 12px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:#9ba3c0;text-align:left;">Item</th>'
           . '<th style="padding:10px 12px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:#9ba3c0;text-align:center;">Qty</th>'
           . '<th style="padding:10px 12px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:#9ba3c0;text-align:right;">Unit (USD)</th>'
           . '<th style="padding:10px 12px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:#9ba3c0;text-align:right;">Total</th>'
           . '</tr></thead>';
    return '<table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;margin:20px 0;">'
         . $thead . '<tbody>' . $rows . '</tbody></table>';
}

// Full order table for client email (Product grouping, USD only)
function ms_order_table_client(array $items, float $rate): string {
    $rows = '';
    $prevProduct = null;
    $grandTotal  = 0;
    foreach ($items as $itm) {
        $product  = $itm['product'] ?? '';
        $itemName = $itm['item']    ?? '';
        $sku      = $itm['sku']     ?? '';
        $qty      = (float)($itm['qty']      ?? 0);
        $priceRmb = (float)($itm['priceRmb'] ?? 0);
        if (!$itemName && !$qty && !$priceRmb) continue;
        if ($product !== $prevProduct && $product !== '') {
            $rows .= '<tr style="background:#f8f9fb;"><td colspan="5" style="padding:8px 12px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:#6b7280;">'
                   . htmlspecialchars($product) . '</td></tr>';
            $prevProduct = $product;
        }
        $unitUsd = ($priceRmb > 0 && $rate > 0) ? '$' . number_format($priceRmb / $rate, 2) : '—';
        $totUsd  = ($priceRmb > 0 && $qty > 0 && $rate > 0) ? ($priceRmb / $rate) * $qty : 0;
        $totFmt  = $totUsd > 0 ? '$' . number_format($totUsd, 2) : '—';
        $qtyFmt  = $qty > 0 ? number_format($qty) : '—';
        $grandTotal += $totUsd;
        $rows .= '<tr style="border-top:1px solid #f0f2f5;">'
               . '<td style="padding:10px 12px;font-size:14px;color:#1a1d2e;">' . htmlspecialchars($itemName) . '</td>'
               . '<td style="padding:10px 12px;font-size:13px;color:#6b7280;font-family:monospace;">' . htmlspecialchars($sku) . '</td>'
               . '<td style="padding:10px 12px;font-size:14px;color:#6b7280;text-align:center;">' . $qtyFmt . '</td>'
               . '<td style="padding:10px 12px;font-size:14px;color:#1a1d2e;text-align:right;">' . $unitUsd . '</td>'
               . '<td style="padding:10px 12px;font-size:14px;font-weight:700;color:#1a1d2e;text-align:right;">' . $totFmt . '</td>'
               . '</tr>';
    }
    if ($grandTotal > 0) {
        $rows .= '<tr style="background:#f8f9fb;border-top:2px solid #e5e7eb;">'
               . '<td colspan="4" style="padding:10px 12px;font-size:13px;color:#6b7280;font-weight:600;text-align:right;">Estimated Total</td>'
               . '<td style="padding:10px 12px;font-size:15px;font-weight:800;color:#1a1d2e;text-align:right;">$' . number_format($grandTotal, 2) . ' <span style="font-size:11px;font-weight:400;color:#9ba3c0;">USD</span></td>'
               . '</tr>';
    }
    $thead = '<thead><tr style="background:#f8f9fb;">'
           . '<th style="padding:10px 12px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:#9ba3c0;text-align:left;">Item</th>'
           . '<th style="padding:10px 12px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:#9ba3c0;text-align:left;">SKU</th>'
           . '<th style="padding:10px 12px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:#9ba3c0;text-align:center;">Qty</th>'
           . '<th style="padding:10px 12px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:#9ba3c0;text-align:right;">Unit (USD)</th>'
           . '<th style="padding:10px 12px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:#9ba3c0;text-align:right;">Total</th>'
           . '</tr></thead>';
    return '<table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;margin:20px 0;">'
         . $thead . '<tbody>' . $rows . '</tbody></table>';
}

// Full order table for internal email (Product grouping, USD + RMB columns)
function ms_order_table_internal(array $items, float $rate): string {
    $rows = '';
    $prevProduct = null;
    $grandUsd    = 0;
    $grandRmb    = 0;
    foreach ($items as $itm) {
        $product  = $itm['product'] ?? '';
        $itemName = $itm['item']    ?? '';
        $sku      = $itm['sku']     ?? '';
        $qty      = (float)($itm['qty']      ?? 0);
        $priceRmb = (float)($itm['priceRmb'] ?? 0);
        $leadTime = (string)($itm['leadTime'] ?? '');
        if (!$itemName && !$qty && !$priceRmb) continue;
        if ($product !== $prevProduct && $product !== '') {
            $rows .= '<tr style="background:#f8f9fb;"><td colspan="7" style="padding:8px 12px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:#6b7280;">'
                   . htmlspecialchars($product) . '</td></tr>';
            $prevProduct = $product;
        }
        $unitUsd  = ($priceRmb > 0 && $rate > 0) ? '$' . number_format($priceRmb / $rate, 2) : '—';
        $unitRmb  = $priceRmb > 0 ? '¥' . number_format($priceRmb, 2) : '—';
        $totUsd   = ($priceRmb > 0 && $qty > 0 && $rate > 0) ? ($priceRmb / $rate) * $qty : 0;
        $totRmb   = ($priceRmb > 0 && $qty > 0) ? $priceRmb * $qty : 0;
        $totUsdFmt = $totUsd > 0 ? '$' . number_format($totUsd, 2) : '—';
        $qtyFmt   = $qty > 0 ? number_format($qty) : '—';
        $leadFmt  = $leadTime ? htmlspecialchars($leadTime) . 'd' : '—';
        $grandUsd += $totUsd;
        $grandRmb += $totRmb;
        $rows .= '<tr style="border-top:1px solid #f0f2f5;">'
               . '<td style="padding:10px 12px;font-size:14px;color:#1a1d2e;">' . htmlspecialchars($itemName) . '</td>'
               . '<td style="padding:10px 12px;font-size:13px;color:#6b7280;font-family:monospace;">' . htmlspecialchars($sku) . '</td>'
               . '<td style="padding:10px 12px;font-size:14px;color:#6b7280;text-align:center;">' . $qtyFmt . '</td>'
               . '<td style="padding:10px 12px;font-size:14px;color:#1a1d2e;text-align:right;">' . $unitUsd . '</td>'
               . '<td style="padding:10px 12px;font-size:14px;color:#6b7280;text-align:right;">' . $unitRmb . '</td>'
               . '<td style="padding:10px 12px;font-size:14px;font-weight:700;color:#1a1d2e;text-align:right;">' . $totUsdFmt . '</td>'
               . '<td style="padding:10px 12px;font-size:13px;color:#6b7280;text-align:center;">' . $leadFmt . '</td>'
               . '</tr>';
    }
    if ($grandUsd > 0) {
        $rows .= '<tr style="background:#f8f9fb;border-top:2px solid #e5e7eb;">'
               . '<td colspan="5" style="padding:10px 12px;font-size:13px;color:#6b7280;font-weight:600;text-align:right;">Total</td>'
               . '<td style="padding:10px 12px;font-size:15px;font-weight:800;color:#1a1d2e;text-align:right;">$' . number_format($grandUsd, 2) . ' USD</td>'
               . '<td style="padding:10px 12px;font-size:13px;color:#6b7280;text-align:right;">¥' . number_format($grandRmb, 2) . '</td>'
               . '</tr>';
    }
    $thead = '<thead><tr style="background:#f8f9fb;">'
           . '<th style="padding:10px 12px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:#9ba3c0;text-align:left;">Item</th>'
           . '<th style="padding:10px 12px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:#9ba3c0;text-align:left;">SKU</th>'
           . '<th style="padding:10px 12px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:#9ba3c0;text-align:center;">Qty</th>'
           . '<th style="padding:10px 12px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:#9ba3c0;text-align:right;">Unit (USD)</th>'
           . '<th style="padding:10px 12px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:#9ba3c0;text-align:right;">Unit (RMB)</th>'
           . '<th style="padding:10px 12px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:#9ba3c0;text-align:right;">Total (USD)</th>'
           . '<th style="padding:10px 12px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:#9ba3c0;text-align:center;">Lead</th>'
           . '</tr></thead>';
    return '<table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;margin:20px 0;">'
         . $thead . '<tbody>' . $rows . '</tbody></table>';
}

// Shared commission writer — used by promote_to_sku AND recompute_commissions
// so both code paths produce identical row shape and totals.
//
// Behavior: UPSERT one row per role (AM and/or SP) the client has set. Paid
// rows are LEFT ALONE (we don't retroactively rewrite history once payout has
// happened). Pending/estimate rows get their totals refreshed each call —
// that's how the backfill corrects rows written before AM/SP was assigned,
// and how the per-workbook sum corrects the old per-item-undercount bug
// (UNIQUE KEY on (workbook_id, role, employee) used to make only the first
// item's amount stick on a multi-item promote).
//
// $client must be a row from `clients` with id, name, account_manager,
// salesperson, operations_person, account_manager_pct, salesperson_pct,
// operations_pct. The _pct columns are per-client overrides for AM/SP
// (stored as percent — 20.00 == 20%). When NULL/blank we fall back to
// the AM/SP default (0%, supplied via $defaultPct).
//
// The OPERATIONS rate is now sourced GLOBALLY from the assigned user's
// users.commission_pct (default 10% when NULL). The per-client
// operations_pct column is ignored — the Client Details UI no longer
// exposes it, and the operator manages the rate from User Management.
// $clientTotalUsd is the workbook-wide total in USD that the client pays us.
function ms_record_commissions_for_workbook(
    PDO $pdo, array $client, int $workbookId, ?string $sku,
    ?string $productName, float $clientTotalUsd,
    int $isEstimate = 1, float $defaultPct = 0.0
): int {
    $am = trim((string)($client['account_manager']   ?? ''));
    $sp = trim((string)($client['salesperson']       ?? ''));
    // Operations is now hardcoded to Karen on every client. The per-client
    // operations_person column is no longer surfaced in the UI — Karen
    // always gets her cut, with the rate sourced from her user record
    // (default 10%). Set operations_person='' on a client only if you
    // explicitly want to opt that client out (no current UI for that).
    $op = 'Karen';
    if ($clientTotalUsd <= 0) return 0;

    // Resolve per-role rate: NULL/'' on the client → role default. Stored
    // as percent → divide by 100 to get the decimal fraction the table
    // holds. AM/SP default to 0% (caller-supplied $defaultPct, which now
    // defaults to 0.0); operations is resolved below from the user record.
    $resolveRate = function ($pct, $roleDefault) {
        $usePct = ($pct === null || $pct === '') ? $roleDefault : (float)$pct;
        return $usePct / 100.0;
    };
    $amRate = $resolveRate($client['account_manager_pct'] ?? null, $defaultPct);
    $spRate = $resolveRate($client['salesperson_pct']     ?? null, $defaultPct);

    // Operations rate: per-user setting from users.commission_pct, falling
    // back to 10% when the user record is missing or commission_pct is
    // NULL. Match by display_name (preferred) or username so the operator
    // can rename Karen later without breaking the join. ms_op_user_rate
    // is a static cache to avoid one query per workbook in
    // recompute_commissions, which walks every promoted/active workbook.
    static $opRateCache = [];
    if (!array_key_exists($op, $opRateCache)) {
        if ($op === '') {
            $opRateCache[$op] = 0.10;
        } else {
            $u = $pdo->prepare("SELECT commission_pct FROM users WHERE display_name = ? OR username = ? LIMIT 1");
            $u->execute([$op, $op]);
            $row = $u->fetch();
            $opRateCache[$op] = ($row && $row['commission_pct'] !== null && $row['commission_pct'] !== '')
                ? ((float)$row['commission_pct'] / 100.0)
                : 0.10;
        }
    }
    $opRate = $opRateCache[$op];

    // ON DUPLICATE KEY UPDATE keeps paid rows frozen via the IF() guard. New
    // rows insert cleanly; pending/estimate rows pick up the latest total
    // AND the latest rate (so editing the % field updates existing rows).
    $sql = "
        INSERT INTO commissions
          (client_id, client_name, workbook_id, sku, product_name,
           role, employee, client_total_usd, commission_rate, commission_amount, is_estimate)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
          client_total_usd  = IF(status = 'paid', client_total_usd,  VALUES(client_total_usd)),
          commission_rate   = IF(status = 'paid', commission_rate,   VALUES(commission_rate)),
          commission_amount = IF(status = 'paid', commission_amount, VALUES(commission_amount)),
          is_estimate       = IF(status = 'paid', is_estimate,       VALUES(is_estimate)),
          sku               = IF(status = 'paid', sku,               COALESCE(VALUES(sku), sku)),
          product_name      = IF(status = 'paid', product_name,      COALESCE(VALUES(product_name), product_name))
    ";
    $stmt = $pdo->prepare($sql);

    $written = 0;
    $totalRounded = round($clientTotalUsd, 2);
    $roles = [];
    if ($am !== '') $roles[] = ['account_manager', $am, $amRate];
    if ($sp !== '') $roles[] = ['salesperson',     $sp, $spRate];
    if ($op !== '') $roles[] = ['operations',      $op, $opRate];
    foreach ($roles as [$role, $emp, $rate]) {
        $stmt->execute([
            (int)$client['id'],
            $client['name'] ?? null,
            $workbookId,
            $sku,
            $productName,
            $role,
            $emp,
            $totalRounded,
            $rate,
            round($clientTotalUsd * $rate, 2),
            $isEstimate,
        ]);
        // rowCount() == 1 on insert, 2 on update via ON DUPLICATE KEY UPDATE,
        // 0 if nothing changed. Either way, count it as a "touch".
        if ($stmt->rowCount() > 0) $written++;
    }
    return $written;
}

// Sum a workbook's rfqItems into a single USD total. Mirrors the front-end's
// USD = priceRmb / 7.24 conversion so totals match what users see on Pricing.
function ms_workbook_total_usd_from_detail(?string $detailJson, float $usdRmbRate = 7.24): float {
    if (!$detailJson) return 0;
    $details = json_decode($detailJson, true);
    if (!is_array($details)) return 0;
    $items = $details['rfqItems'] ?? [];
    if (!is_array($items)) return 0;
    $total = 0;
    foreach ($items as $it) {
        $qty = (float)($it['qty']      ?? 0);
        $rmb = (float)($it['priceRmb'] ?? 0);
        if ($qty > 0 && $rmb > 0 && $usdRmbRate > 0) {
            $total += $qty * ($rmb / $usdRmbRate);
        }
    }
    return $total;
}

switch ($action) {

    // ─── CLIENTS ───────────────────────────────────────

    case 'get_clients':
        $stmt = $pdo->query("SELECT * FROM clients WHERE deleted_at IS NULL ORDER BY name ASC");
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
        break;

    case 'add_client':
        if (empty($input['name'])) {
            echo json_encode(['success' => false, 'error' => 'Client name required']);
            break;
        }
        try {
            // Account manager + salesperson are optional on create. If absent
            // they stay NULL and can be set later via save_client_detail.
            $am  = isset($input['account_manager']) ? trim((string)$input['account_manager']) : '';
            $sp  = isset($input['salesperson'])     ? trim((string)$input['salesperson'])     : '';
            $stmt = $pdo->prepare("INSERT INTO clients (name, account_manager, salesperson) VALUES (?, ?, ?)");
            $stmt->execute([
                trim($input['name']),
                $am !== '' ? $am : null,
                $sp !== '' ? $sp : null,
            ]);
            echo json_encode([
                'success' => true,
                'id' => $pdo->lastInsertId(),
                'name' => trim($input['name']),
                'account_manager' => $am !== '' ? $am : null,
                'salesperson' => $sp !== '' ? $sp : null,
            ]);
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                echo json_encode(['success' => false, 'error' => 'Client already exists']);
            } else {
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
        }
        break;

    case 'delete_client':
        if (empty($input['id'])) {
            echo json_encode(['success' => false, 'error' => 'Client ID required']);
            break;
        }
        $deletedBy = $input['deleted_by'] ?? '';
        // Soft-delete client
        $stmt = $pdo->prepare("UPDATE clients SET deleted_at = NOW(), deleted_by = ? WHERE id = ? AND deleted_at IS NULL");
        $stmt->execute([$deletedBy, $input['id']]);
        // Soft-delete all its workbooks
        $stmt = $pdo->prepare("UPDATE workbooks SET deleted_at = NOW(), deleted_by = ? WHERE client_id = ? AND deleted_at IS NULL");
        $stmt->execute([$deletedBy, $input['id']]);
        echo json_encode(['success' => true]);
        break;

    case 'save_client_detail':
        if (empty($input['id'])) {
            echo json_encode(['success' => false, 'error' => 'Client ID required']);
            break;
        }
        $allowed = ['email', 'phone', 'primary_contact',
                    'email2', 'phone2', 'primary_contact2',
                    'billing_address', 'shipping_address', 'notes',
                    'account_manager', 'salesperson', 'operations_person',
                    'account_manager_pct', 'salesperson_pct', 'operations_pct',
                    'default_margin_pct'];
        // Numeric overrides → NULL when blank so the helper falls back to the
        // default rate. Pre-validate so a stray "abc" can't poison the column.
        $numericCols = ['account_manager_pct' => true, 'salesperson_pct' => true, 'operations_pct' => true, 'default_margin_pct' => true];
        $fields = [];
        $params = [];
        foreach ($allowed as $col) {
            if (array_key_exists($col, $input)) {
                $val = $input[$col];
                if (isset($numericCols[$col])) {
                    if ($val === '' || $val === null) {
                        $val = null;
                    } else if (is_numeric($val)) {
                        $val = (float)$val;
                    } else {
                        // Reject non-numeric — skip this field rather than fail the whole save.
                        continue;
                    }
                }
                $fields[] = "$col = ?";
                $params[] = $val;
            }
        }
        if (empty($fields)) {
            echo json_encode(['success' => false, 'error' => 'No fields to update']);
            break;
        }
        $params[] = $input['id'];
        $pdo->prepare("UPDATE clients SET " . implode(', ', $fields) . " WHERE id = ?")->execute($params);
        echo json_encode(['success' => true]);
        break;

    // ─── WORKBOOKS ─────────────────────────────────────

    case 'get_workbooks':
        $clientId = $_GET['client_id'] ?? null;
        if ($clientId) {
            $stmt = $pdo->prepare("
                SELECT w.*, c.name as client_name
                FROM workbooks w
                JOIN clients c ON w.client_id = c.id
                WHERE w.client_id = ? AND w.deleted_at IS NULL
                ORDER BY w.created_at DESC
            ");
            $stmt->execute([$clientId]);
        } else {
            $stmt = $pdo->query("
                SELECT w.*, c.name as client_name
                FROM workbooks w
                JOIN clients c ON w.client_id = c.id
                WHERE w.deleted_at IS NULL
                ORDER BY w.updated_at DESC
            ");
        }
        $workbooks = $stmt->fetchAll();
        foreach ($workbooks as &$wb) {
            $wb['detail'] = $wb['detail_json'] ? json_decode($wb['detail_json'], true) : null;
            unset($wb['detail_json']);
        }
        echo json_encode(['success' => true, 'data' => $workbooks]);
        break;

    case 'get_workbook':
        $id = $_GET['id'] ?? null;
        if (!$id) {
            echo json_encode(['success' => false, 'error' => 'Workbook ID required']);
            break;
        }
        $stmt = $pdo->prepare("
            SELECT w.*, c.name as client_name
            FROM workbooks w
            JOIN clients c ON w.client_id = c.id
            WHERE w.id = ?
        ");
        $stmt->execute([$id]);
        $wb = $stmt->fetch();
        if ($wb) {
            $wb['detail'] = $wb['detail_json'] ? json_decode($wb['detail_json'], true) : null;
            unset($wb['detail_json']);
        }
        echo json_encode(['success' => true, 'data' => $wb]);
        break;

    case 'add_workbook':
        if (empty($input['client_id']) || empty($input['product_name'])) {
            echo json_encode(['success' => false, 'error' => 'Client ID and product name required']);
            break;
        }
        $productName = trim($input['product_name']);
        $description = trim($input['description'] ?? '');
        // Dedup guard: if a non-deleted workbook for this client already exists with
        // the same product name, return its id instead of inserting a duplicate.
        // Prevents client-side seed/retry bugs from creating parallel copies.
        // Explicit opt-out via allow_duplicate=true (for intentional copies via duplicate_workbook).
        $allowDup = !empty($input['allow_duplicate']);
        if (!$allowDup) {
            $dupStmt = $pdo->prepare("
                SELECT id FROM workbooks
                WHERE client_id = ? AND product_name = ? AND deleted_at IS NULL
                LIMIT 1
            ");
            $dupStmt->execute([$input['client_id'], $productName]);
            $existing = $dupStmt->fetch();
            if ($existing) {
                echo json_encode(['success' => true, 'id' => $existing['id'], 'deduped' => true]);
                break;
            }
        }
        $detail = $input['detail'] ?? [];
        try {
            $stmt = $pdo->prepare("
                INSERT INTO workbooks (client_id, product_name, description, flow_step, detail_json)
                VALUES (?, ?, ?, 0, ?)
            ");
            $stmt->execute([
                $input['client_id'],
                $productName,
                $description,
                json_encode($detail)
            ]);
            echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
        } catch (PDOException $e) {
            // Race-safe fallback: if the UNIQUE (client_id, product_name, deleted_at)
            // constraint fires (concurrent requests slipped past the app-layer check),
            // look up the winner and return its id so the client treats the call as
            // a no-op rather than an error.
            if ($e->getCode() === '23000' && !$allowDup) {
                $recover = $pdo->prepare("SELECT id FROM workbooks WHERE client_id = ? AND product_name = ? AND deleted_at IS NULL LIMIT 1");
                $recover->execute([$input['client_id'], $productName]);
                $winner = $recover->fetch();
                if ($winner) {
                    echo json_encode(['success' => true, 'id' => $winner['id'], 'deduped' => true, 'race_recovered' => true]);
                    break;
                }
            }
            throw $e;
        }
        break;

    case 'update_workbook':
        if (empty($input['id'])) {
            echo json_encode(['success' => false, 'error' => 'Workbook ID required']);
            break;
        }
        $fields = [];
        $params = [];

        if (isset($input['product_name'])) {
            $fields[] = 'product_name = ?';
            $params[] = trim($input['product_name']);
        }
        if (isset($input['description'])) {
            $fields[] = 'description = ?';
            $params[] = trim($input['description']);
        }
        if (isset($input['flow_step'])) {
            $fields[] = 'flow_step = ?';
            $params[] = (int)$input['flow_step'];
        }
        if (isset($input['detail'])) {
            $fields[] = 'detail_json = ?';
            $params[] = json_encode($input['detail']);
        }

        if (empty($fields)) {
            echo json_encode(['success' => false, 'error' => 'No fields to update']);
            break;
        }

        $params[] = $input['id'];
        $sql = "UPDATE workbooks SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        echo json_encode(['success' => true]);
        break;

    case 'update_flow':
        if (!isset($input['id']) || !isset($input['flow_step'])) {
            echo json_encode(['success' => false, 'error' => 'Workbook ID and flow_step required']);
            break;
        }
        $stmt = $pdo->prepare("UPDATE workbooks SET flow_step = ? WHERE id = ?");
        $stmt->execute([(int)$input['flow_step'], $input['id']]);
        echo json_encode(['success' => true]);
        break;

    case 'delete_workbook':
        if (empty($input['id'])) {
            echo json_encode(['success' => false, 'error' => 'Workbook ID required']);
            break;
        }
        $deletedBy = $input['deleted_by'] ?? '';
        $stmt = $pdo->prepare("UPDATE workbooks SET deleted_at = NOW(), deleted_by = ? WHERE id = ? AND deleted_at IS NULL");
        $stmt->execute([$deletedBy, $input['id']]);
        echo json_encode(['success' => true]);
        break;

    // ─── RECENT WORKBOOKS (for landing page) ──────────

    case 'get_recent':
        $limit = $_GET['limit'] ?? 5;
        $stmt = $pdo->prepare("
            SELECT w.*, c.name as client_name
            FROM workbooks w
            JOIN clients c ON w.client_id = c.id
            WHERE w.deleted_at IS NULL
            ORDER BY w.updated_at DESC
            LIMIT ?
        ");
        $stmt->execute([(int)$limit]);
        $workbooks = $stmt->fetchAll();
        foreach ($workbooks as &$wb) {
            $wb['detail'] = $wb['detail_json'] ? json_decode($wb['detail_json'], true) : null;
            unset($wb['detail_json']);
        }
        echo json_encode(['success' => true, 'data' => $workbooks]);
        break;

    // ─── SAVE WITH REVISION TRACKING ────────────────────

    case 'save_workbook_detail':
        if (empty($input['id'])) {
            echo json_encode(['success' => false, 'error' => 'Workbook ID required']);
            break;
        }
        $changedBy      = $input['changed_by'] ?? '';
        $createRevision = !empty($input['create_revision']); // true on nav saves
        $confirmShrink  = !empty($input['confirm_shrink']);  // explicit override after a rejection

        // Pull current state up front — every guard below needs it.
        $stmt = $pdo->prepare("SELECT detail_json FROM workbooks WHERE id = ?");
        $stmt->execute([$input['id']]);
        $current     = $stmt->fetch();
        $currentJson = $current['detail_json'] ?? '';
        $currentArr  = json_decode($currentJson ?: '{}', true);
        if (!is_array($currentArr)) $currentArr = [];

        $incomingArr  = is_array($input['detail'] ?? null) ? $input['detail'] : [];
        $incomingJson = json_encode($incomingArr);

        // ── GUARD HELPERS ──────────────────────────────────────────────────
        // Counts top-level keys whose value is non-empty (non-empty string,
        // non-zero number, non-empty array/object). Booleans don't count.
        $countMeaningful = function ($arr) {
            if (!is_array($arr)) return 0;
            $n = 0;
            foreach ($arr as $v) {
                if ($v === null || $v === '' || $v === false) continue;
                if (is_array($v) && count($v) === 0) continue;
                if (is_string($v) && trim($v) === '') continue;
                $n++;
            }
            return $n;
        };
        $curFields = $countMeaningful($currentArr);
        $newFields = $countMeaningful($incomingArr);

        // Always-on auto-snapshot: every time a save would change content, we
        // store the prior state as a revision — regardless of create_revision.
        // The previous behavior (only on nav saves) is what allowed an
        // autosave race to overwrite data without leaving a recovery point.
        $autoSnapshot = function () use ($pdo, $input, $currentJson, $changedBy) {
            if (!$currentJson || $currentJson === '[]' || $currentJson === 'null') return null;
            $lastStmt = $pdo->prepare("SELECT id, detail_json FROM workbook_revisions WHERE workbook_id = ? ORDER BY created_at DESC LIMIT 1");
            $lastStmt->execute([$input['id']]);
            $lastRev = $lastStmt->fetch();
            if ($lastRev && $lastRev['detail_json'] === $currentJson) return $lastRev['id'];
            $ins = $pdo->prepare("INSERT INTO workbook_revisions (workbook_id, detail_json, changed_by) VALUES (?, ?, ?)");
            $ins->execute([$input['id'], $currentJson, $changedBy ?: 'auto-snapshot']);
            return (int)$pdo->lastInsertId();
        };

        // ── GUARD 1: HARD-FLOOR REFUSAL ───────────────────────────────────
        // Refuse outright to save an empty/near-empty payload over a workbook
        // that has substantial data. No override path — this is always wrong.
        if ($curFields >= 5 && $newFields === 0) {
            $snapId = $autoSnapshot();
            echo json_encode([
                'success' => false,
                'error'   => 'refused_empty_overwrite',
                'message' => "Refused to overwrite a populated workbook ($curFields fields) with an empty payload. This usually means the form hasn't finished loading. No changes written.",
                'pre_save_snapshot_revision_id' => $snapId,
            ]);
            break;
        }

        // ── GUARD 2: SHRINK PROTECTION ────────────────────────────────────
        // If the new payload would drop more than half the meaningful fields
        // OR shrink detail_json by >60 % on a workbook that had real content,
        // require explicit confirm_shrink. Always snapshot first either way.
        $bigDropFields = ($curFields >= 6 && $newFields < $curFields / 2);
        $bigDropBytes  = (strlen($currentJson) > 800
                          && strlen($incomingJson) < strlen($currentJson) * 0.4);
        if (($bigDropFields || $bigDropBytes) && !$confirmShrink) {
            $snapId = $autoSnapshot();
            echo json_encode([
                'success' => false,
                'error'   => 'refused_shrink',
                'message' => "Refused: this save would reduce the workbook from $curFields populated fields ("
                           . strlen($currentJson) . " bytes) to $newFields fields ("
                           . strlen($incomingJson) . " bytes). Resend with confirm_shrink=true to force.",
                'pre_save_snapshot_revision_id' => $snapId,
                'cur_fields' => $curFields,
                'new_fields' => $newFields,
                'cur_bytes'  => strlen($currentJson),
                'new_bytes'  => strlen($incomingJson),
            ]);
            break;
        }

        // ── ALWAYS-SNAPSHOT (replaces the old create_revision-only path) ──
        // Snapshot whenever content actually changed. createRevision still
        // marks "user-initiated" snapshots, but we no longer rely on it for
        // protection. Skip if value is identical to current.
        if ($currentJson && $currentJson !== $incomingJson) {
            $autoSnapshot();
        }

        // ── WRITE ─────────────────────────────────────────────────────────
        $newProductName = !empty($incomingArr['product']) ? trim($incomingArr['product']) : null;
        if ($newProductName) {
            $stmt = $pdo->prepare("UPDATE workbooks SET detail_json = ?, product_name = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$incomingJson, $newProductName, $input['id']]);
        } else {
            $stmt = $pdo->prepare("UPDATE workbooks SET detail_json = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$incomingJson, $input['id']]);
        }

        // ── REVISION RETENTION ────────────────────────────────────────────
        // Old code purged on every save with a 30-day cutoff. That meant a
        // burst of autosaves could age the only good revision past the
        // cutoff. Now: 180-day retention, and the purge only runs ~1 % of
        // the time so it doesn't compete with the save itself.
        if (mt_rand(1, 100) === 1) {
            $pdo->exec("DELETE FROM workbook_revisions WHERE created_at < DATE_SUB(NOW(), INTERVAL 180 DAY)");
        }

        echo json_encode(['success' => true]);
        break;

    // ─── REVISION HISTORY ────────────────────────────────

    case 'get_revisions':
        $wbId = $_GET['workbook_id'] ?? null;
        if (!$wbId) {
            echo json_encode(['success' => false, 'error' => 'Workbook ID required']);
            break;
        }
        $stmt = $pdo->prepare("
            SELECT id, workbook_id, detail_json, changed_by, created_at
            FROM workbook_revisions
            WHERE workbook_id = ?
            ORDER BY created_at DESC
            LIMIT 50
        ");
        $stmt->execute([$wbId]);
        $revisions = $stmt->fetchAll();
        // Decode detail_json and build summary of fields
        foreach ($revisions as &$rev) {
            $detail = json_decode($rev['detail_json'], true);
            // Create a summary of key fields (exclude large data like images)
            $rev['summary'] = [];
            if ($detail && is_array($detail)) {
                $fieldLabels = [
                    'product' => 'Product', 'desc' => 'Description', 'materials' => 'Materials',
                    'pantone' => 'Pantone', 'cmyk' => 'CMYK', 'colorNotes' => 'Color Notes',
                    'qty' => 'Quantity', 'unitPriceRmb' => 'Unit Price (RMB)', 'leadTime' => 'Lead Time',
                    'qcNotes' => 'QC Notes', 'freightMode' => 'Shipping Method',
                    'quoteDate' => 'Quote Date', 'quoteClQty' => 'Quote Qty',
                    'quoteClUnitPrice' => 'Quote Unit Price', 'quoteClShipping' => 'Quote Shipping',
                    'invNumber' => 'Invoice #', 'invStatus' => 'Invoice Status'
                ];
                foreach ($fieldLabels as $key => $label) {
                    if (!empty($detail[$key])) {
                        $rev['summary'][] = ['field' => $label, 'value' => (string)$detail[$key]];
                    }
                }
                // Count tiers
                if (!empty($detail['tiers']) && is_array($detail['tiers'])) {
                    $rev['summary'][] = ['field' => 'Pricing Tiers', 'value' => count($detail['tiers']) . ' tiers'];
                }
                // Has image?
                if (!empty($detail['productImage'])) {
                    $rev['summary'][] = ['field' => 'Product Image', 'value' => 'Yes'];
                }
            }
            unset($rev['detail_json']); // Don't send the full JSON to client
        }
        echo json_encode(['success' => true, 'data' => $revisions]);
        break;

    case 'get_revision_detail':
        $revId = $_GET['revision_id'] ?? null;
        if (!$revId) {
            echo json_encode(['success' => false, 'error' => 'Revision ID required']);
            break;
        }
        $stmt = $pdo->prepare("SELECT * FROM workbook_revisions WHERE id = ?");
        $stmt->execute([$revId]);
        $rev = $stmt->fetch();
        if ($rev) {
            $rev['detail'] = json_decode($rev['detail_json'], true);
            unset($rev['detail_json']);
        }
        echo json_encode(['success' => true, 'data' => $rev]);
        break;

    case 'restore_revision':
        if (empty($input['revision_id']) || empty($input['workbook_id'])) {
            echo json_encode(['success' => false, 'error' => 'Revision ID and Workbook ID required']);
            break;
        }
        $changedBy = $input['changed_by'] ?? '';

        // Get the revision to restore
        $stmt = $pdo->prepare("SELECT detail_json FROM workbook_revisions WHERE id = ?");
        $stmt->execute([$input['revision_id']]);
        $rev = $stmt->fetch();
        if (!$rev) {
            echo json_encode(['success' => false, 'error' => 'Revision not found']);
            break;
        }

        // Save current version as a revision first
        $stmt = $pdo->prepare("SELECT detail_json FROM workbooks WHERE id = ?");
        $stmt->execute([$input['workbook_id']]);
        $current = $stmt->fetch();
        if ($current && $current['detail_json']) {
            $stmt = $pdo->prepare("INSERT INTO workbook_revisions (workbook_id, detail_json, changed_by) VALUES (?, ?, ?)");
            $stmt->execute([$input['workbook_id'], $current['detail_json'], $changedBy . ' (before restore)']);
        }

        // Restore the revision
        $stmt = $pdo->prepare("UPDATE workbooks SET detail_json = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$rev['detail_json'], $input['workbook_id']]);
        echo json_encode(['success' => true]);
        break;

    // ─── ARCHIVE (Soft-deleted items) ────────────────────

    case 'get_archived':
        $archivedWorkbooks = $pdo->query("
            SELECT w.id, w.product_name, w.deleted_at, w.deleted_by, c.name as client_name
            FROM workbooks w
            JOIN clients c ON w.client_id = c.id
            WHERE w.deleted_at IS NOT NULL
            ORDER BY w.deleted_at DESC
        ")->fetchAll();

        $archivedClients = $pdo->query("
            SELECT id, name, deleted_at, deleted_by
            FROM clients
            WHERE deleted_at IS NOT NULL
            ORDER BY deleted_at DESC
        ")->fetchAll();

        echo json_encode([
            'success' => true,
            'workbooks' => $archivedWorkbooks,
            'clients' => $archivedClients
        ]);
        break;

    // ─── DIAGNOSTIC: find missing workbooks ───────────────
    // Searches workbooks across ALL states (active, soft-deleted, orphaned)
    // and cross-references against revision history. Read-only.
    case 'diagnose_workbook':
        $needle      = trim($_GET['name']   ?? $input['name']   ?? '');
        $clientNeedle= trim($_GET['client'] ?? $input['client'] ?? '');
        if ($needle === '' && $clientNeedle === '') {
            echo json_encode(['success' => false, 'error' => 'Provide ?name= and/or ?client= search term']);
            break;
        }

        $result = [
            'name_query'   => $needle,
            'client_query' => $clientNeedle,
            'matched_clients' => [],
            'workbooks_by_name' => [],
            'workbooks_for_matched_clients' => [],
            'orphan_revisions' => [],
            'recent_workbooks_all_clients' => [],
        ];

        // 1) Find all clients matching the client search (active + soft-deleted)
        if ($clientNeedle !== '') {
            $cq = $pdo->prepare("SELECT id, name, deleted_at, deleted_by, created_at FROM clients WHERE name LIKE ? ORDER BY name ASC");
            $cq->execute(['%' . $clientNeedle . '%']);
            $result['matched_clients'] = $cq->fetchAll();
        }

        // 2) Find workbooks where product_name matches needle, across ALL states.
        //    Use LEFT JOIN so soft-deleted client rows still surface.
        if ($needle !== '') {
            $wq = $pdo->prepare("
                SELECT w.id, w.client_id, w.product_name, w.flow_step,
                       w.deleted_at, w.deleted_by, w.created_at, w.updated_at,
                       CHAR_LENGTH(w.detail_json) AS detail_size,
                       c.name AS client_name, c.deleted_at AS client_deleted_at
                FROM workbooks w
                LEFT JOIN clients c ON w.client_id = c.id
                WHERE w.product_name LIKE ?
                   OR REPLACE(LOWER(w.product_name), ' ', '') LIKE ?
                ORDER BY w.updated_at DESC, w.created_at DESC
            ");
            $needleClean = strtolower(str_replace(' ', '', $needle));
            $wq->execute(['%' . $needle . '%', '%' . $needleClean . '%']);
            $result['workbooks_by_name'] = $wq->fetchAll();
        }

        // 3) For each matched client, list ALL their workbooks (active + soft-deleted)
        if (!empty($result['matched_clients'])) {
            $clientIds = array_column($result['matched_clients'], 'id');
            $ph = implode(',', array_fill(0, count($clientIds), '?'));
            $wq2 = $pdo->prepare("
                SELECT id, client_id, product_name, flow_step,
                       deleted_at, deleted_by, created_at, updated_at,
                       CHAR_LENGTH(detail_json) AS detail_size
                FROM workbooks
                WHERE client_id IN ($ph)
                ORDER BY client_id ASC, updated_at DESC
            ");
            $wq2->execute($clientIds);
            $result['workbooks_for_matched_clients'] = $wq2->fetchAll();
        }

        // 4) Orphan-revision check: revisions whose workbook_id no longer exists
        //    in the workbooks table at all (i.e. a permanent_delete_workbook ran
        //    but didn't cascade — shouldn't happen with current code, but worth checking).
        if ($needle !== '') {
            $rq = $pdo->prepare("
                SELECT r.id, r.workbook_id, r.changed_by, r.created_at,
                       CHAR_LENGTH(r.detail_json) AS detail_size
                FROM workbook_revisions r
                LEFT JOIN workbooks w ON r.workbook_id = w.id
                WHERE w.id IS NULL
                ORDER BY r.created_at DESC
                LIMIT 50
            ");
            $rq->execute();
            $result['orphan_revisions'] = $rq->fetchAll();
        }

        // 5) Sanity-check: list 20 most-recently-touched workbooks regardless of
        //    name match, so we can eyeball whether anything else looks off.
        $recent = $pdo->query("
            SELECT w.id, w.product_name, w.deleted_at, w.updated_at,
                   c.name AS client_name
            FROM workbooks w
            LEFT JOIN clients c ON w.client_id = c.id
            ORDER BY w.updated_at DESC
            LIMIT 20
        ")->fetchAll();
        $result['recent_workbooks_all_clients'] = $recent;

        echo json_encode(['success' => true, 'diagnostic' => $result], JSON_PRETTY_PRINT);
        break;

    // Non-destructive merge: pull the largest-with-data revision and merge it
    // INTO the current workbook so missing fields get backfilled but any field
    // the user has touched recently survives. Snapshots current state first so
    // it can always be rolled back via restore_revision.
    case 'merge_recover_workbook':
        $wbId = intval($_GET['workbook_id'] ?? $input['workbook_id'] ?? 0);
        if ($wbId <= 0) {
            echo json_encode(['success' => false, 'error' => 'workbook_id required']);
            break;
        }
        $dryRun = !empty($_GET['dry_run']) || !empty($input['dry_run']);
        $forceRevId = intval($_GET['revision_id'] ?? $input['revision_id'] ?? 0);

        // 1) Load current
        $cs = $pdo->prepare("SELECT id, product_name, detail_json FROM workbooks WHERE id = ?");
        $cs->execute([$wbId]);
        $cur = $cs->fetch();
        if (!$cur) {
            echo json_encode(['success' => false, 'error' => 'Workbook not found']);
            break;
        }
        $curDetail = json_decode($cur['detail_json'], true);
        if (!is_array($curDetail)) $curDetail = [];

        // 2) Pick best revision (or use the forced one)
        if ($forceRevId > 0) {
            $rs = $pdo->prepare("SELECT id, detail_json, created_at, changed_by FROM workbook_revisions WHERE id = ? AND workbook_id = ?");
            $rs->execute([$forceRevId, $wbId]);
        } else {
            $rs = $pdo->prepare("
                SELECT id, detail_json, created_at, changed_by,
                       CHAR_LENGTH(detail_json) AS sz
                FROM workbook_revisions
                WHERE workbook_id = ?
                ORDER BY sz DESC, created_at DESC
                LIMIT 1
            ");
            $rs->execute([$wbId]);
        }
        $rev = $rs->fetch();
        if (!$rev) {
            echo json_encode(['success' => false, 'error' => 'No revisions found for this workbook']);
            break;
        }
        $revDetail = json_decode($rev['detail_json'], true);
        if (!is_array($revDetail)) {
            echo json_encode(['success' => false, 'error' => 'Chosen revision has no decodable detail_json']);
            break;
        }

        // 3) Deep merge: current wins when it has a non-empty value, otherwise
        //    revision fills the gap. Arrays of objects merge by id when ids
        //    exist, otherwise the longer array wins outright.
        $isEmptyVal = function ($v) {
            if ($v === null) return true;
            if (is_string($v) && trim($v) === '') return true;
            if (is_array($v) && count($v) === 0) return true;
            return false;
        };

        $merge = null;
        $merge = function ($current, $revision) use (&$merge, $isEmptyVal) {
            // Both arrays:
            if (is_array($current) && is_array($revision)) {
                // Distinguish associative vs list. PHP doesn't have a perfect check, but
                // if the revision has string keys treat as object-like.
                $curIsList = $current === [] || array_keys($current) === range(0, count($current) - 1);
                $revIsList = $revision === [] || array_keys($revision) === range(0, count($revision) - 1);

                if ($curIsList && $revIsList) {
                    // Lists. Try id-keyed merge first.
                    $hasIds = true;
                    foreach ($current as $r) { if (!is_array($r) || !isset($r['id'])) { $hasIds = false; break; } }
                    if ($hasIds) {
                        foreach ($revision as $r) { if (!is_array($r) || !isset($r['id'])) { $hasIds = false; break; } }
                    }
                    if ($hasIds) {
                        $byId = [];
                        foreach ($revision as $r) $byId[$r['id']] = $r;
                        foreach ($current  as $r) $byId[$r['id']] = is_array($byId[$r['id']] ?? null)
                            ? $merge($r, $byId[$r['id']])
                            : $r;
                        return array_values($byId);
                    }
                    // Otherwise: longer list wins. If equal length, current wins.
                    return (count($current) >= count($revision)) ? $current : $revision;
                }

                // Associative-ish merge: take union of keys, current wins where non-empty.
                $out = $revision; // start from revision so we keep ALL revision keys
                foreach ($current as $k => $v) {
                    if (!array_key_exists($k, $out)) {
                        $out[$k] = $v;
                    } elseif (is_array($v) && is_array($out[$k])) {
                        $out[$k] = $merge($v, $out[$k]);
                    } elseif (!$isEmptyVal($v)) {
                        $out[$k] = $v;
                    } // else: current is empty → keep revision value
                }
                return $out;
            }
            // Scalars / mixed: current wins if non-empty.
            return $isEmptyVal($current) ? $revision : $current;
        };

        $merged = $merge($curDetail, $revDetail);

        // 4) Diff summary so the response shows what changed
        $diff = [
            'current_size' => strlen($cur['detail_json']),
            'revision_size'=> strlen($rev['detail_json']),
            'merged_size'  => strlen(json_encode($merged)),
            'revision_id'  => $rev['id'],
            'revision_at'  => $rev['created_at'],
            'fields_filled' => [],
        ];
        foreach (($revDetail ?? []) as $k => $v) {
            $curEmpty = !array_key_exists($k, $curDetail) || $isEmptyVal($curDetail[$k]);
            $revHas   = !$isEmptyVal($v);
            if ($curEmpty && $revHas) {
                $preview = is_scalar($v) ? (string)$v
                         : (is_array($v) ? '[' . count($v) . ' items]' : '(complex)');
                $diff['fields_filled'][] = ['field' => $k, 'value_preview' => substr($preview, 0, 120)];
            }
        }

        if ($dryRun) {
            echo json_encode(['success' => true, 'dry_run' => true, 'diff' => $diff], JSON_PRETTY_PRINT);
            break;
        }

        // 5) Snapshot current as a new revision BEFORE writing the merge,
        //    so a single restore_revision call can undo this.
        $snap = $pdo->prepare(
            "INSERT INTO workbook_revisions (workbook_id, detail_json, changed_by) VALUES (?, ?, ?)"
        );
        $snap->execute([$wbId, $cur['detail_json'], 'pre-recover-snapshot']);
        $snapId = (int)$pdo->lastInsertId();

        // 6) Write the merged result
        $up = $pdo->prepare("UPDATE workbooks SET detail_json = ?, updated_at = NOW() WHERE id = ?");
        $up->execute([json_encode($merged), $wbId]);

        echo json_encode([
            'success' => true,
            'diff' => $diff,
            'pre_recover_snapshot_revision_id' => $snapId,
            'rollback_hint' => "POST /api.php?action=restore_revision  body: {workbook_id:$wbId, revision_id:$snapId}"
        ], JSON_PRETTY_PRINT);
        break;

    // List every revision of one workbook with its byte-size and short summary
    // so we can spot when data got truncated and pick a good restore point.
    case 'diagnose_revisions':
        $wbId = intval($_GET['workbook_id'] ?? $input['workbook_id'] ?? 0);
        if ($wbId <= 0) {
            echo json_encode(['success' => false, 'error' => 'workbook_id required']);
            break;
        }
        $live = $pdo->prepare("SELECT id, product_name, deleted_at, updated_at, CHAR_LENGTH(detail_json) AS detail_size FROM workbooks WHERE id = ?");
        $live->execute([$wbId]);
        $liveRow = $live->fetch();

        $rq = $pdo->prepare("
            SELECT id, workbook_id, changed_by, created_at,
                   CHAR_LENGTH(detail_json) AS detail_size,
                   detail_json
            FROM workbook_revisions
            WHERE workbook_id = ?
            ORDER BY created_at DESC
            LIMIT 100
        ");
        $rq->execute([$wbId]);
        $revs = $rq->fetchAll();

        // Trim detail_json to a short summary so the response stays small.
        foreach ($revs as &$r) {
            $d = json_decode($r['detail_json'], true);
            $r['has_tiers']   = (is_array($d) && !empty($d['tiers']))   ? count($d['tiers'])   : 0;
            $r['has_rfq']     = (is_array($d) && !empty($d['rfq']))     ? count($d['rfq'])     : 0;
            $r['has_orders']  = (is_array($d) && !empty($d['orders']))  ? count($d['orders'])  : 0;
            $r['has_product'] = (is_array($d) && !empty($d['product'])) ? (string)$d['product'] : '';
            unset($r['detail_json']);
        }

        echo json_encode([
            'success'   => true,
            'workbook'  => $liveRow,
            'revisions' => $revs
        ], JSON_PRETTY_PRINT);
        break;

    case 'restore_workbook':
        if (empty($input['id'])) {
            echo json_encode(['success' => false, 'error' => 'Workbook ID required']);
            break;
        }
        $stmt = $pdo->prepare("UPDATE workbooks SET deleted_at = NULL, deleted_by = NULL WHERE id = ?");
        $stmt->execute([$input['id']]);
        echo json_encode(['success' => true]);
        break;

    case 'restore_client':
        if (empty($input['id'])) {
            echo json_encode(['success' => false, 'error' => 'Client ID required']);
            break;
        }
        // Restore client
        $stmt = $pdo->prepare("UPDATE clients SET deleted_at = NULL, deleted_by = NULL WHERE id = ?");
        $stmt->execute([$input['id']]);
        // Restore all its workbooks
        $stmt = $pdo->prepare("UPDATE workbooks SET deleted_at = NULL, deleted_by = NULL WHERE client_id = ?");
        $stmt->execute([$input['id']]);
        echo json_encode(['success' => true]);
        break;

    case 'permanent_delete_workbook':
        if (empty($input['id'])) {
            echo json_encode(['success' => false, 'error' => 'Workbook ID required']);
            break;
        }
        // Delete revisions first
        $stmt = $pdo->prepare("DELETE FROM workbook_revisions WHERE workbook_id = ?");
        $stmt->execute([$input['id']]);
        // Then delete the workbook
        $stmt = $pdo->prepare("DELETE FROM workbooks WHERE id = ?");
        $stmt->execute([$input['id']]);
        echo json_encode(['success' => true]);
        break;

    case 'permanent_delete_client':
        if (empty($input['id'])) {
            echo json_encode(['success' => false, 'error' => 'Client ID required']);
            break;
        }
        // Delete all workbook revisions for this client's workbooks
        $stmt = $pdo->prepare("DELETE r FROM workbook_revisions r JOIN workbooks w ON r.workbook_id = w.id WHERE w.client_id = ?");
        $stmt->execute([$input['id']]);
        // Delete all workbooks
        $stmt = $pdo->prepare("DELETE FROM workbooks WHERE client_id = ?");
        $stmt->execute([$input['id']]);
        // Delete the client
        $stmt = $pdo->prepare("DELETE FROM clients WHERE id = ?");
        $stmt->execute([$input['id']]);
        echo json_encode(['success' => true]);
        break;

    // ─── FULL DATA EXPORT (for LocalStorage sync) ─────

    case 'get_all_data':
        $clients = $pdo->query("SELECT * FROM clients WHERE deleted_at IS NULL ORDER BY name ASC")->fetchAll();
        $workbooks = $pdo->query("
            SELECT w.*, c.name as client_name
            FROM workbooks w
            JOIN clients c ON w.client_id = c.id
            WHERE w.deleted_at IS NULL
            ORDER BY c.name, w.product_name
        ")->fetchAll();
        foreach ($workbooks as &$wb) {
            $wb['detail'] = $wb['detail_json'] ? json_decode($wb['detail_json'], true) : null;
            unset($wb['detail_json']);
        }
        echo json_encode([
            'success' => true,
            'clients' => $clients,
            'workbooks' => $workbooks,
            'timestamp' => date('c')
        ]);
        break;

    case 'upload_image':
        if (empty($_FILES['image']) || empty($_POST['workbook_id'])) {
            echo json_encode(['success' => false, 'error' => 'Image file and workbook_id required']);
            break;
        }
        $wbId = intval($_POST['workbook_id']);
        $file = $_FILES['image'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (!in_array($ext, $allowed)) {
            echo json_encode(['success' => false, 'error' => 'Invalid file type']);
            break;
        }
        if ($file['size'] > 10 * 1024 * 1024) {
            echo json_encode(['success' => false, 'error' => 'File too large (max 10MB)']);
            break;
        }
        $uploadDir = __DIR__ . '/uploads/' . $wbId . '/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $filename = uniqid() . '_' . time() . '.' . $ext;
        $filepath = $uploadDir . $filename;
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            $url = 'uploads/' . $wbId . '/' . $filename;
            echo json_encode(['success' => true, 'url' => $url, 'filename' => $filename]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Upload failed']);
        }
        break;

    case 'delete_image':
        if (empty($input['url'])) {
            echo json_encode(['success' => false, 'error' => 'Image URL required']);
            break;
        }
        $path = __DIR__ . '/' . $input['url'];
        if (file_exists($path) && strpos(realpath($path), realpath(__DIR__ . '/uploads/')) === 0) {
            unlink($path);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'File not found']);
        }
        break;

    case 'upload_video':
        if (empty($_FILES['video']) || empty($_POST['workbook_id'])) {
            echo json_encode(['success' => false, 'error' => 'Video file and workbook_id required']);
            break;
        }
        $wbId = intval($_POST['workbook_id']);
        $file = $_FILES['video'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['mp4', 'mov', 'webm', 'avi', 'mkv', 'm4v', 'qt'];
        if (!in_array($ext, $allowed)) {
            echo json_encode(['success' => false, 'error' => 'Invalid file type. Allowed: mp4, mov, webm, avi, mkv, m4v']);
            break;
        }
        if ($file['size'] > 500 * 1024 * 1024) {
            echo json_encode(['success' => false, 'error' => 'File too large (max 500MB)']);
            break;
        }
        $uploadDir = __DIR__ . '/uploads/' . $wbId . '/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $filename = uniqid() . '_' . time() . '.' . $ext;
        $filepath = $uploadDir . $filename;
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            $url = 'uploads/' . $wbId . '/' . $filename;
            echo json_encode(['success' => true, 'url' => $url, 'filename' => $filename]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Upload failed']);
        }
        break;

    case 'get_users':
        if ($sessionUser['role'] !== 'admin') {
            echo json_encode(['success' => false, 'error' => 'Admin only']);
            break;
        }
        $stmt = $pdo->query("SELECT id, username, display_name, email, role, commission_pct, created_at, last_login FROM users ORDER BY created_at ASC");
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
        break;

    case 'add_user':
        if ($sessionUser['role'] !== 'admin') {
            echo json_encode(['success' => false, 'error' => 'Admin only']);
            break;
        }
        if (empty($input['username']) || empty($input['password'])) {
            echo json_encode(['success' => false, 'error' => 'Username and password required']);
            break;
        }
        try {
            $hash  = password_hash($input['password'], PASSWORD_DEFAULT);
            // Empty email → store NULL so we don't trip the UNIQUE-style
            // queries later if we ever index on email. Trim defensively.
            $email = trim((string)($input['email'] ?? ''));
            if ($email === '') $email = null;
            $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, display_name, email, role) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                trim($input['username']),
                $hash,
                trim($input['display_name'] ?? $input['username']),
                $email,
                $input['role'] === 'admin' ? 'admin' : 'user'
            ]);
            echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => $e->getCode() == 23000 ? 'Username already exists' : $e->getMessage()]);
        }
        break;

    // Update profile fields on a user — display_name, email, and role.
    // Admins can target any user; non-admins can only target themselves and
    // can't change their own role (no privilege escalation here). Password
    // changes go through the existing change_password action so we keep the
    // password update path narrow and explicit.
    case 'update_user':
        $targetId = (int)($input['id'] ?? 0);
        if (!$targetId) {
            echo json_encode(['success' => false, 'error' => 'User id required']);
            break;
        }
        $isAdmin = ($sessionUser['role'] === 'admin');
        $isSelf  = ($targetId === (int)$sessionUser['id']);
        if (!$isAdmin && !$isSelf) {
            echo json_encode(['success' => false, 'error' => 'Not allowed']);
            break;
        }
        $fields = [];
        $params = [];
        if (array_key_exists('display_name', $input)) {
            $fields[] = 'display_name = ?';
            $params[] = trim((string)$input['display_name']);
        }
        if (array_key_exists('email', $input)) {
            $em = trim((string)$input['email']);
            $fields[] = 'email = ?';
            $params[] = ($em === '' ? null : $em);
        }
        // Role changes: admin only, and we don't let an admin demote
        // themselves — that's how a system ends up with zero admins.
        if (array_key_exists('role', $input) && $isAdmin && !$isSelf) {
            $fields[] = 'role = ?';
            $params[] = ($input['role'] === 'admin' ? 'admin' : 'user');
        }
        // Commission %: admin-only. Empty string clears the override (user
        // falls back to the role default at compute time). Negative or
        // non-numeric input is rejected (treated as no-op).
        if (array_key_exists('commission_pct', $input) && $isAdmin) {
            $raw = trim((string)$input['commission_pct']);
            if ($raw === '') {
                $fields[] = 'commission_pct = NULL';
            } elseif (is_numeric($raw) && (float)$raw >= 0) {
                $fields[] = 'commission_pct = ?';
                $params[] = (float)$raw;
            }
        }
        if (!$fields) {
            echo json_encode(['success' => false, 'error' => 'No changes']);
            break;
        }
        $params[] = $targetId;
        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?";
        $pdo->prepare($sql)->execute($params);
        echo json_encode(['success' => true]);
        break;

    case 'delete_user':
        if ($sessionUser['role'] !== 'admin') {
            echo json_encode(['success' => false, 'error' => 'Admin only']);
            break;
        }
        if (empty($input['id']) || $input['id'] == $sessionUser['id']) {
            echo json_encode(['success' => false, 'error' => 'Cannot delete your own account']);
            break;
        }
        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$input['id']]);
        echo json_encode(['success' => true]);
        break;

    case 'change_password':
        $targetId = $input['user_id'] ?? $sessionUser['id'];
        // Admin can change anyone's password; user can only change own
        if ($targetId != $sessionUser['id'] && $sessionUser['role'] !== 'admin') {
            echo json_encode(['success' => false, 'error' => 'Not allowed']);
            break;
        }
        if (empty($input['new_password'])) {
            echo json_encode(['success' => false, 'error' => 'New password required']);
            break;
        }
        $hash = password_hash($input['new_password'], PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?")->execute([$hash, $targetId]);
        echo json_encode(['success' => true]);
        break;

    case 'duplicate_workbook':
        if (empty($input['id'])) {
            echo json_encode(['success' => false, 'error' => 'Workbook ID required']);
            break;
        }
        $stmt = $pdo->prepare("SELECT client_id, product_name, description, detail_json FROM workbooks WHERE id = ? AND deleted_at IS NULL");
        $stmt->execute([$input['id']]);
        $src = $stmt->fetch();
        if (!$src) {
            echo json_encode(['success' => false, 'error' => 'Workbook not found']);
            break;
        }

        // Resolve target client (may differ from source)
        $targetClientId = $src['client_id'];
        if (!empty($input['target_client'])) {
            $cs = $pdo->prepare("SELECT id FROM clients WHERE name = ? AND deleted_at IS NULL");
            $cs->execute([trim($input['target_client'])]);
            $cl = $cs->fetch();
            if ($cl) $targetClientId = $cl['id'];
        }

        // Merge qty/cost into detail JSON, reset flow to 0
        $detail = json_decode($src['detail_json'] ?? '{}', true) ?: [];
        if (!empty($input['qty']))  $detail['quoteClQty']      = $input['qty'];
        if (!empty($input['cost'])) $detail['quoteClShipping']  = $input['cost'];

        $newName = !empty($input['product_name']) ? trim($input['product_name']) : $src['product_name'] . ' (Copy)';
        $detail['product'] = $newName;
        $stmt = $pdo->prepare("INSERT INTO workbooks (client_id, product_name, description, flow_step, detail_json) VALUES (?, ?, ?, 0, ?)");
        $stmt->execute([$targetClientId, $newName, $src['description'], json_encode($detail)]);
        echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
        break;

    case 'dedup_workbooks':
        // Admin-only one-shot cleanup for accidental duplicates created by the
        // seed-retry bug. Groups non-deleted workbooks by (client_id, product_name),
        // keeps the OLDEST (smallest id) row in each group, soft-deletes the rest.
        // Pass confirm=true to actually delete; otherwise returns a dry-run preview.
        if ($sessionUser['role'] !== 'admin') {
            echo json_encode(['success' => false, 'error' => 'Admin only']);
            break;
        }
        $confirm = !empty($input['confirm']);
        $dupStmt = $pdo->query("
            SELECT client_id, product_name, COUNT(*) AS n, MIN(id) AS keep_id,
                   GROUP_CONCAT(id ORDER BY id) AS ids
            FROM workbooks
            WHERE deleted_at IS NULL
            GROUP BY client_id, product_name
            HAVING n > 1
        ");
        $groups = $dupStmt->fetchAll();
        $toDelete = [];
        foreach ($groups as $g) {
            $ids = explode(',', $g['ids']);
            foreach ($ids as $id) {
                if ((int)$id !== (int)$g['keep_id']) $toDelete[] = (int)$id;
            }
        }
        if (!$confirm) {
            echo json_encode([
                'success' => true,
                'dry_run' => true,
                'groups'  => $groups,
                'would_delete_count' => count($toDelete),
                'would_delete_ids'   => $toDelete,
                'hint' => 'Re-run with {"confirm": true} to apply.',
            ]);
            break;
        }
        $deleted = 0;
        if (!empty($toDelete)) {
            $who = $sessionUser['username'] ?? 'admin-dedup';
            $placeholders = implode(',', array_fill(0, count($toDelete), '?'));
            $del = $pdo->prepare("UPDATE workbooks SET deleted_at = NOW(), deleted_by = ? WHERE id IN ($placeholders) AND deleted_at IS NULL");
            $del->execute(array_merge([$who], $toDelete));
            $deleted = $del->rowCount();
        }
        echo json_encode([
            'success' => true,
            'dry_run' => false,
            'deleted_count' => $deleted,
            'deleted_ids'   => $toDelete,
        ]);
        break;

    case 'get_inventory':
        $stmt = $pdo->query("SELECT * FROM inventory ORDER BY promoted_at DESC");
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
        break;

    case 'promote_to_sku':
        // input: array of items [{sku, product_name, variant_name, client_name,
        //   workbook_id, qty?, unit_price_usd?}]
        // qty + unit_price_usd are optional; when present they get summed per
        // workbook to seed commission rows for AM/SP. Backfill via
        // recompute_commissions reconciles totals later if AM/SP changes
        // or if the workbook's rfqItems get edited.
        if (empty($input['items']) || !is_array($input['items'])) {
            echo json_encode(['success' => false, 'error' => 'Items array required']);
            break;
        }
        $inserted = 0;
        $skipped = 0;
        $invStmt = $pdo->prepare("INSERT IGNORE INTO inventory (sku, product_name, variant_name, client_name, workbook_id) VALUES (?, ?, ?, ?, ?)");

        // Aggregate items per (client_name, workbook_id) so the commission
        // row reflects the WHOLE workbook, not just the first item we hit.
        $wbAgg = []; // "$cName|$wbId" => ['client_name', 'workbook_id', 'total_usd', 'first_sku', 'first_product']

        foreach ($input['items'] as $item) {
            if (empty($item['sku'])) continue;
            $invStmt->execute([
                trim($item['sku']),
                trim($item['product_name'] ?? ''),
                isset($item['variant_name']) && $item['variant_name'] !== '' ? trim($item['variant_name']) : null,
                $item['client_name'] ?? null,
                $item['workbook_id'] ?? null
            ]);
            if ($invStmt->rowCount() > 0) $inserted++;
            else $skipped++;

            $cName = $item['client_name'] ?? null;
            $wbId  = $item['workbook_id'] ?? null;
            if (!$cName || !$wbId) continue;

            $key = $cName . '|' . $wbId;
            if (!isset($wbAgg[$key])) {
                $wbAgg[$key] = [
                    'client_name'   => $cName,
                    'workbook_id'   => (int)$wbId,
                    'total_usd'     => 0,
                    'first_sku'     => trim($item['sku']),
                    'first_product' => trim($item['product_name'] ?? ''),
                ];
            }
            $qty   = isset($item['qty']) ? (float)$item['qty'] : 0;
            $price = isset($item['unit_price_usd']) ? (float)$item['unit_price_usd'] : 0;
            if ($qty > 0 && $price > 0) {
                $wbAgg[$key]['total_usd'] += $qty * $price;
            }
        }

        // Now record commissions one workbook at a time using the helper.
        // Falls back to rfqItems sum from detail_json if the payload didn't
        // carry qty/price (e.g. older client builds).
        $commissionsRecorded = 0;
        $clientCache = [];
        foreach ($wbAgg as $agg) {
            $cName = $agg['client_name'];
            if (!isset($clientCache[$cName])) {
                $cs = $pdo->prepare("SELECT id, name, account_manager, salesperson, operations_person, account_manager_pct, salesperson_pct, operations_pct FROM clients WHERE name = ? AND deleted_at IS NULL LIMIT 1");
                $cs->execute([$cName]);
                $clientCache[$cName] = $cs->fetch() ?: null;
            }
            $client = $clientCache[$cName];
            if (!$client) continue;

            $totalUsd = (float)$agg['total_usd'];
            if ($totalUsd <= 0) {
                // Fall back to recomputing from the workbook's stored rfqItems.
                $ws = $pdo->prepare("SELECT detail_json FROM workbooks WHERE id = ? LIMIT 1");
                $ws->execute([$agg['workbook_id']]);
                $detail = $ws->fetchColumn();
                $totalUsd = ms_workbook_total_usd_from_detail($detail);
            }
            if ($totalUsd <= 0) continue;

            $commissionsRecorded += ms_record_commissions_for_workbook(
                $pdo, $client, $agg['workbook_id'],
                $agg['first_sku'] ?: null,
                $agg['first_product'] ?: null,
                $totalUsd,
                1 // is_estimate (until real Client Cost is wired on Pricing)
            );
        }
        echo json_encode([
            'success' => true,
            'inserted' => $inserted,
            'skipped' => $skipped,
            'commissions_recorded' => $commissionsRecorded,
        ]);
        break;

    // ─── COMMISSIONS ───────────────────────────────────
    // Read all commission rows. Joined back to clients for live AM/SP info
    // (handy if the role assignment changed since the row was written —
    // dashboard can show the current owner alongside the historical one).
    case 'get_commissions':
        $sql = "SELECT cm.*,
                       c.account_manager   AS current_account_manager,
                       c.salesperson       AS current_salesperson,
                       c.operations_person AS current_operations_person
                FROM commissions cm
                LEFT JOIN clients c ON c.id = cm.client_id
                ORDER BY cm.created_at DESC";
        $stmt = $pdo->query($sql);
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
        break;

    // Mark a commission paid / unpaid. Used by the dashboard's payout UI.
    case 'set_commission_status':
        $id     = (int)($input['id'] ?? 0);
        $status = $input['status'] ?? '';
        if (!$id || !in_array($status, ['pending','paid'], true)) {
            echo json_encode(['success' => false, 'error' => 'id + valid status required']);
            break;
        }
        $stmt = $pdo->prepare("UPDATE commissions SET status = ?, paid_at = " . ($status === 'paid' ? 'NOW()' : 'NULL') . " WHERE id = ?");
        $stmt->execute([$status, $id]);
        echo json_encode(['success' => true]);
        break;

    // Backfill / reconcile commissions for every (client, workbook) pair that
    // is currently in inventory OR on a current order. Idempotent — safe to
    // call on every dashboard load. Picks up:
    //   • workbooks that were promoted/ordered before AM/SP was assigned
    //     (the original commission write skipped them)
    //   • workbooks whose rfqItems totals changed since the original write
    //     (UPSERT refreshes pending rows; paid rows stay frozen)
    case 'recompute_commissions':
        $pairs = []; // map "$clientName|$wbId" => ['client_name', 'workbook_id']

        // 1. Pairs from inventory (anything ever promoted)
        $invRows = $pdo->query("
            SELECT DISTINCT client_name, workbook_id
            FROM inventory
            WHERE client_name IS NOT NULL AND workbook_id IS NOT NULL
        ")->fetchAll();
        foreach ($invRows as $r) {
            $key = $r['client_name'] . '|' . $r['workbook_id'];
            $pairs[$key] = [
                'client_name' => $r['client_name'],
                'workbook_id' => (int)$r['workbook_id'],
            ];
        }

        // 2. Pairs from active orders. Orders live as JSON under
        //    app_state['ms_orders'].data[orderId].entries[*].{clientName,workbookId}.
        $os = $pdo->prepare("SELECT value_json FROM app_state WHERE key_name = ?");
        $os->execute(['ms_orders']);
        $ordersJson = $os->fetchColumn();
        if ($ordersJson) {
            $stored    = json_decode($ordersJson, true);
            $orderData = (is_array($stored) && isset($stored['data']) && is_array($stored['data']))
                       ? $stored['data'] : [];
            foreach ($orderData as $order) {
                if (!is_array($order)) continue;
                $orderClient = $order['clientName'] ?? '';
                $entries     = $order['entries']    ?? [];
                if (!is_array($entries)) continue;
                foreach ($entries as $e) {
                    if (!is_array($e)) continue;
                    $cn = $e['clientName']  ?? $orderClient;
                    $wb = $e['workbookId']  ?? null;
                    if (!$cn || !$wb) continue;
                    $key = $cn . '|' . $wb;
                    $pairs[$key] = [
                        'client_name' => $cn,
                        'workbook_id' => (int)$wb,
                    ];
                }
            }
        }

        // 3. Walk pairs, write commission rows.
        $written       = 0;
        $clientCache   = [];
        $workbookCache = [];

        foreach ($pairs as $pair) {
            $cn = $pair['client_name'];
            $wb = $pair['workbook_id'];

            if (!array_key_exists($cn, $clientCache)) {
                $cs = $pdo->prepare("SELECT id, name, account_manager, salesperson, operations_person, account_manager_pct, salesperson_pct, operations_pct FROM clients WHERE name = ? AND deleted_at IS NULL LIMIT 1");
                $cs->execute([$cn]);
                $clientCache[$cn] = $cs->fetch() ?: null;
            }
            $client = $clientCache[$cn];
            if (!$client) continue;

            // Karen (operations) is hardcoded onto every client now, so a
            // commission row is always written. We don't short-circuit on
            // unassigned AM/SP — those just produce no AM/SP rows for this
            // client while Karen still gets hers.

            if (!array_key_exists($wb, $workbookCache)) {
                $ws = $pdo->prepare("SELECT product_name, detail_json FROM workbooks WHERE id = ? LIMIT 1");
                $ws->execute([$wb]);
                $workbookCache[$wb] = $ws->fetch() ?: null;
            }
            $wbRow = $workbookCache[$wb];
            if (!$wbRow) continue;

            $totalUsd = ms_workbook_total_usd_from_detail($wbRow['detail_json'] ?? null);
            if ($totalUsd <= 0) continue;

            $written += ms_record_commissions_for_workbook(
                $pdo, $client, $wb,
                null, // no single SKU at workbook level
                $wbRow['product_name'] ?? null,
                $totalUsd,
                1 // is_estimate
            );
        }

        echo json_encode([
            'success'        => true,
            'pairs_examined' => count($pairs),
            'rows_written'   => $written,
        ]);
        break;

    case 'remove_sku':
        if (empty($input['id'])) {
            echo json_encode(['success' => false, 'error' => 'ID required']);
            break;
        }
        $pdo->prepare("DELETE FROM inventory WHERE id = ?")->execute([$input['id']]);
        echo json_encode(['success' => true]);
        break;

    case 'send_notification':
        $type        = $input['type']         ?? '';
        $clientEmail = $input['client_email'] ?? '';
        $contactName = $input['contact_name'] ?? '';
        $clientName  = $input['client_name']  ?? '';
        $details     = $input['details']      ?? [];
        $rate        = (float)($input['rate'] ?? 7.24);
        $internal    = ['jackson@marketsculpt.com', 'parker@marketsculpt.com'];

        $client_html = $internal_html = $subject = '';
        $portalUrl   = null;

        if ($type === 'quote_ready') {
            $product  = $details['product'] ?? 'your product';
            $subject  = "Your Quote is Ready — {$product}";
            $greeting = $contactName ? "Hi {$contactName}," : "Hi there,";

            // Build flat items list from rfqItems
            $quoteItems = [];
            foreach (($details['rfqItems'] ?? []) as $rfqItem) {
                if (!($rfqItem['item'] ?? '') && !($rfqItem['qty'] ?? 0) && !($rfqItem['priceRmb'] ?? 0)) continue;
                $quoteItems[] = [
                    'product'  => $product,
                    'item'     => $rfqItem['item']     ?? '',
                    'sku'      => $rfqItem['sku']       ?? '',
                    'qty'      => (float)($rfqItem['qty']      ?? 0),
                    'priceRmb' => (float)($rfqItem['priceRmb'] ?? 0),
                    'leadTime' => (string)($rfqItem['leadTime'] ?? ''),
                ];
            }

            $appUrl = $details['app_url'] ?? '';

            // Generate portal token for quote review
            if (!empty($quoteItems)) {
                $portalToken   = bin2hex(random_bytes(32));
                $quoteSnapshot = json_encode([
                    'order' => [
                        'name'        => "Quote — {$product}",
                        'poNumber'    => '',
                        'dateCreated' => date('Y-m-d'),
                        'clientName'  => $clientName,
                        'type'        => 'quote',
                        'appUrl'      => $appUrl,
                    ],
                    'items' => $quoteItems,
                    'rate'  => $rate,
                ]);
                try {
                    $pdo->prepare("INSERT INTO portal_tokens (token, order_snapshot, client_name, client_email) VALUES (?, ?, ?, ?)")
                        ->execute([$portalToken, $quoteSnapshot, $clientName, $clientEmail]);
                    $scheme    = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                    $host      = $_SERVER['HTTP_HOST'] ?? 'wb.marketsculpt.com';
                    $portalUrl = "{$scheme}://{$host}/portal.php?t={$portalToken}";
                } catch (PDOException $e) {
                    $portalUrl = null;
                }
            }

            // Email tables
            $rfq_tbl_client   = !empty($quoteItems) ? ms_order_table_client($quoteItems, $rate)   : '';
            $rfq_tbl_internal = !empty($quoteItems) ? ms_order_table_internal($quoteItems, $rate)  : '';

            // Portal CTA button
            $portal_btn = '';
            if ($portalUrl) {
                $portal_btn = "<div style='text-align:center;margin:32px 0;'>"
                            . "<a href='" . htmlspecialchars($portalUrl) . "' style='display:inline-block;background:#E8751A;color:#fff;font-size:15px;font-weight:700;text-decoration:none;padding:14px 36px;border-radius:8px;letter-spacing:0.01em;'>"
                            . "Review &amp; Approve Your Quote &rarr;"
                            . "</a>"
                            . "<p style='margin:12px 0 0;font-size:12px;color:#9ba3c0;'>This link expires once you approve or request changes.</p>"
                            . "</div>";
            }

            $c_body = "<h1 style='margin:0 0 6px;font-size:26px;font-weight:800;color:#1a1d2e;'>Your Quote is Ready</h1>"
                    . "<p style='margin:0 0 28px;font-size:15px;color:#6b7280;'>We've prepared pricing for your review.</p>"
                    . "<p style='margin:0 0 16px;font-size:15px;color:#374151;line-height:1.7;'>{$greeting}</p>"
                    . "<p style='margin:0 0 4px;font-size:15px;color:#374151;line-height:1.7;'>Your quote for <strong>" . htmlspecialchars($product) . "</strong> is ready. Please find the details below:</p>"
                    . $rfq_tbl_client
                    . $portal_btn
                    . "<p style='margin:16px 0;font-size:15px;color:#374151;line-height:1.7;'>Please review and don't hesitate to reach out with any questions or adjustments.</p>"
                    . "<p style='margin:0;font-size:15px;color:#374151;'>Thanks,<br><strong>Market Sculpt Team</strong></p>";

            $i_detail = [
                ['Client',    htmlspecialchars($clientName)],
                ['Contact',   htmlspecialchars($contactName)],
                ['Product',   htmlspecialchars($product)],
                ['Sent To',   htmlspecialchars($clientEmail)],
            ];
            if ($portalUrl) $i_detail[] = ['Portal Link', '<a href="' . htmlspecialchars($portalUrl) . '" style="color:#E8751A;">' . htmlspecialchars($portalUrl) . '</a>'];

            $appBtn_quote = $appUrl
                ? "<div style='margin:24px 0 0;'><a href='" . htmlspecialchars($appUrl) . "' style='display:inline-flex;align-items:center;gap:8px;background:#181b26;color:#f0f1f5;font-size:13px;font-weight:700;text-decoration:none;padding:10px 20px;border-radius:8px;border:1px solid #3a3f5c;'>"
                . "<svg width='13' height='13' viewBox='0 0 24 24' fill='none' stroke='#E8751A' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><path d='M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z'/><polyline points='7 10 12 15 17 10'/><line x1='12' y1='15' x2='12' y2='3'/></svg>"
                . "Open Workbook in App &rarr;</a></div>"
                : '';

            $i_body = "<h1 style='margin:0 0 6px;font-size:26px;font-weight:800;color:#1a1d2e;'>Quote Sent — " . htmlspecialchars($product) . "</h1>"
                    . "<p style='margin:0 0 24px;font-size:15px;color:#6b7280;'>Internal notification</p>"
                    . ms_detail_table($i_detail)
                    . $rfq_tbl_internal
                    . $appBtn_quote;

            $client_html   = ms_email_wrap($subject, "Your quote for {$product} is ready to review.", $c_body);
            $internal_html = ms_email_wrap("[Internal] " . $subject, "Quote sent to {$clientName}", $i_body);

        } elseif (in_array($type, ['order_confirmed', 'order_in_production', 'order_complete'])) {
            $order_name = $details['order_name'] ?? 'Your Order';
            $po         = $details['po_number']  ?? '';
            $greeting   = $contactName ? "Hi {$contactName}," : "Hi there,";

            $meta = [
                'order_confirmed'     => ['Order Confirmed',     'Your order has been confirmed and is ready for your review.',  '#27ae60'],
                'order_in_production' => ['Order In Production', 'Your order is now in production.',                             '#E8751A'],
                'order_complete'      => ['Order Complete',      'Your order is complete and ready.',                            '#6b93ff'],
            ][$type];
            [$title, $subtitle, $color] = $meta;
            $subject = "{$title} — {$order_name}";

            // Build flat items list from entries
            $orderItems = [];
            foreach (($details['entries'] ?? []) as $entry) {
                $prod = $entry['product'] ?? '';
                foreach (($entry['rfqItems'] ?? []) as $rfqItem) {
                    if (!($rfqItem['item'] ?? '') && !($rfqItem['qty'] ?? 0) && !($rfqItem['priceRmb'] ?? 0)) continue;
                    $orderItems[] = [
                        'product'   => $prod,
                        'item'      => $rfqItem['item']     ?? '',
                        'sku'       => $rfqItem['sku']      ?? '',
                        'qty'       => (float)($rfqItem['qty']      ?? 0),
                        'priceRmb'  => (float)($rfqItem['priceRmb'] ?? 0),
                        'leadTime'  => (string)($rfqItem['leadTime'] ?? ''),
                    ];
                }
            }

            // Generate portal token for order_confirmed
            $appUrl = $details['app_url'] ?? '';
            if ($type === 'order_confirmed' && !empty($orderItems)) {
                $portalToken  = bin2hex(random_bytes(32));
                $orderSnapshot = json_encode([
                    'order' => [
                        'name'        => $order_name,
                        'poNumber'    => $po,
                        'dateCreated' => date('Y-m-d'),
                        'clientName'  => $clientName,
                        'appUrl'      => $appUrl,
                    ],
                    'items' => $orderItems,
                    'rate'  => $rate,
                ]);
                try {
                    $pdo->prepare("INSERT INTO portal_tokens (token, order_snapshot, client_name, client_email) VALUES (?, ?, ?, ?)")
                        ->execute([$portalToken, $orderSnapshot, $clientName, $clientEmail]);
                    $scheme    = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                    $host      = $_SERVER['HTTP_HOST'] ?? 'wb.marketsculpt.com';
                    $portalUrl = "{$scheme}://{$host}/portal.php?t={$portalToken}";
                } catch (PDOException $e) {
                    $portalUrl = null;
                }
            }

            // Client order table
            $order_tbl_client = !empty($orderItems) ? ms_order_table_client($orderItems, $rate) : '';

            // Internal order table (with RMB + lead time)
            $order_tbl_internal = !empty($orderItems) ? ms_order_table_internal($orderItems, $rate) : '';

            $badge = "<div style='margin-bottom:20px;'><span style='background:{$color};color:#fff;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;padding:5px 14px;border-radius:20px;'>{$title}</span></div>";

            $detail_rows = [['Order', htmlspecialchars($order_name)]];
            if ($po) $detail_rows[] = ['PO Number', htmlspecialchars($po)];

            // Portal CTA button for client email
            $portal_btn = '';
            if ($portalUrl && $type === 'order_confirmed') {
                $portal_btn = "<div style='text-align:center;margin:32px 0;'>"
                            . "<a href='" . htmlspecialchars($portalUrl) . "' style='display:inline-block;background:#E8751A;color:#fff;font-size:15px;font-weight:700;text-decoration:none;padding:14px 36px;border-radius:8px;letter-spacing:0.01em;'>"
                            . "View &amp; Approve Your Order &rarr;"
                            . "</a>"
                            . "<p style='margin:12px 0 0;font-size:12px;color:#9ba3c0;'>This link expires once you approve or request changes.</p>"
                            . "</div>";
            }

            $msg_map = [
                'order_confirmed'     => "We're pleased to let you know your order is confirmed. Please review the details below and approve or request any changes.",
                'order_in_production' => "Great news — your order is now in production. We'll keep you updated as it progresses.",
                'order_complete'      => "Your order is complete! Please reach out if you have any questions about delivery or next steps.",
            ];
            $msg = $msg_map[$type] ?? '';

            $c_body = $badge
                    . "<h1 style='margin:0 0 6px;font-size:26px;font-weight:800;color:#1a1d2e;'>" . htmlspecialchars($order_name) . "</h1>"
                    . "<p style='margin:0 0 28px;font-size:15px;color:#6b7280;'>{$subtitle}</p>"
                    . "<p style='margin:0 0 16px;font-size:15px;color:#374151;line-height:1.7;'>{$greeting}</p>"
                    . "<p style='margin:0 0 24px;font-size:15px;color:#374151;line-height:1.7;'>{$msg}</p>"
                    . ms_detail_table($detail_rows)
                    . $order_tbl_client
                    . $portal_btn
                    . "<p style='margin:24px 0 0;font-size:15px;color:#374151;'>Thank you for your business!<br><strong>Market Sculpt Team</strong></p>";

            $i_detail = [
                ['Client',     htmlspecialchars($clientName)],
                ['Contact',    htmlspecialchars($contactName)],
                ['Sent To',    htmlspecialchars($clientEmail)],
                ['Order',      htmlspecialchars($order_name)],
            ];
            if ($po) $i_detail[] = ['PO Number', htmlspecialchars($po)];
            if ($portalUrl) $i_detail[] = ['Portal Link', '<a href="' . htmlspecialchars($portalUrl) . '" style="color:#E8751A;">' . htmlspecialchars($portalUrl) . '</a>'];

            $appBtn_order = $appUrl
                ? "<div style='margin:24px 0 0;'><a href='" . htmlspecialchars($appUrl) . "' style='display:inline-flex;align-items:center;gap:8px;background:#181b26;color:#f0f1f5;font-size:13px;font-weight:700;text-decoration:none;padding:10px 20px;border-radius:8px;border:1px solid #3a3f5c;'>"
                . "<svg width='13' height='13' viewBox='0 0 24 24' fill='none' stroke='#E8751A' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><path d='M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z'/><polyline points='7 10 12 15 17 10'/><line x1='12' y1='15' x2='12' y2='3'/></svg>"
                . "Open Order in App &rarr;</a></div>"
                : '';

            $i_body = $badge
                    . "<h1 style='margin:0 0 6px;font-size:26px;font-weight:800;color:#1a1d2e;'>" . htmlspecialchars($order_name) . "</h1>"
                    . "<p style='margin:0 0 24px;font-size:15px;color:#6b7280;'>Internal — client has been notified.</p>"
                    . ms_detail_table($i_detail)
                    . $order_tbl_internal
                    . $appBtn_order;

            $client_html   = ms_email_wrap($subject, $subtitle, $c_body);
            $internal_html = ms_email_wrap("[Internal] " . $subject, "Sent to {$clientName}", $i_body);
        } else {
            echo json_encode(['success' => false, 'error' => 'Unknown notification type']);
            break;
        }

        $results = [];
        if ($clientEmail) $results['client'] = ms_smtp_send([$clientEmail], $subject, $client_html);
        $results['internal'] = ms_smtp_send($internal, '[Internal] ' . $subject, $internal_html);
        $ok = (!$clientEmail || ($results['client']['ok'] ?? false)) && ($results['internal']['ok'] ?? false);
        $resp = ['success' => $ok, 'results' => $results];
        if ($portalUrl)    $resp['portal_url']   = $portalUrl;
        if (isset($portalToken)) $resp['portal_token'] = $portalToken;
        echo json_encode($resp);
        break;

    case 'notify_watchers':
        // Per-step milestone notification: the workbook has just advanced
        // past `step_key`, so email everyone the operator tagged as a
        // watcher for that step. Emails resolved from the users table
        // by display_name. Watchers without an email on file are
        // skipped silently — frontend already filters them out, this
        // is just a server-side safety net.
        $wbId         = (int)($input['workbook_id'] ?? 0);
        $stepKey      = (string)($input['step_key']     ?? '');
        $stepLabel    = (string)($input['step_label']   ?? $stepKey);
        $productName  = (string)($input['product_name'] ?? 'Workbook');
        $clientName   = (string)($input['client_name']  ?? '');
        $appUrl       = (string)($input['app_url']      ?? '');
        $watcherNames = array_values(array_filter(array_map('strval', (array)($input['watchers'] ?? []))));

        if (!$wbId || $stepKey === '' || empty($watcherNames)) {
            echo json_encode(['success' => false, 'error' => 'workbook_id, step_key, watchers required']);
            break;
        }

        // Resolve emails AND user IDs from the users table — emails feed
        // the SMTP path, user IDs feed the in-app notifications queue
        // (which the watchers' browsers poll for Chrome notifications).
        $placeholders = implode(',', array_fill(0, count($watcherNames), '?'));
        $stmt = $pdo->prepare("SELECT id, display_name, email FROM users
                               WHERE display_name IN ($placeholders)");
        $stmt->execute($watcherNames);
        $emailByName = [];
        $idByName    = [];
        while ($row = $stmt->fetch()) {
            $idByName[$row['display_name']] = (int)$row['id'];
            if (!empty($row['email'])) $emailByName[$row['display_name']] = $row['email'];
        }
        $recipients = [];
        foreach ($watcherNames as $n) { if (!empty($emailByName[$n])) $recipients[] = $emailByName[$n]; }
        $recipients = array_values(array_unique($recipients));

        // Insert in-app notifications for every watcher we know about
        // (even ones with no email). Browser notifications are bonus —
        // they don't require an email to be on file.
        $notifTitle = "Now at: {$stepLabel} — {$productName}";
        $notifBody  = "{$clientName} / {$productName} just moved into {$stepLabel}.";
        try {
            $insertNotif = $pdo->prepare(
                "INSERT INTO notifications (user_id, kind, title, body, url) VALUES (?, 'watcher_milestone', ?, ?, ?)"
            );
            foreach ($watcherNames as $n) {
                $uid = $idByName[$n] ?? 0;
                if ($uid) $insertNotif->execute([$uid, $notifTitle, $notifBody, $appUrl ?: null]);
            }
        } catch (PDOException $e) { /* swallow — emails still sent */ }

        if (empty($recipients)) {
            echo json_encode([
                'success'           => true,
                'recipients'        => [],
                'count'             => 0,
                'browser_notified'  => count(array_filter(array_map(fn($n) => $idByName[$n] ?? 0, $watcherNames))),
                'attempted_names'   => $watcherNames,
                'note'              => 'No emails on file — browser notifications enqueued only.',
            ]);
            break;
        }

        $subject = "Now at: {$stepLabel} — {$productName} ({$clientName})";
        $clientNameSafe  = htmlspecialchars($clientName);
        $productSafe     = htmlspecialchars($productName);
        $stepLabelSafe   = htmlspecialchars($stepLabel);
        $appUrlSafe      = htmlspecialchars($appUrl);

        $appBtn = $appUrl !== ''
            ? "<div style='text-align:center;margin:32px 0;'>"
            . "<a href='{$appUrlSafe}' style='display:inline-block;background:#E8751A;color:#fff;font-size:15px;font-weight:700;text-decoration:none;padding:14px 32px;border-radius:8px;letter-spacing:0.01em;'>"
            . "Open Workbook &rarr;"
            . "</a></div>"
            : '';

        $watcherListSafe = implode(', ', array_map('htmlspecialchars', $watcherNames));

        $body = "<div style='margin-bottom:18px;'><span style='background:#dcfce7;color:#16a34a;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;padding:5px 14px;border-radius:20px;'>Now at this stage</span></div>"
              . "<h1 style='margin:0 0 6px;font-size:24px;font-weight:800;color:#1a1d2e;'>{$stepLabelSafe}</h1>"
              . "<p style='margin:0 0 20px;font-size:15px;color:#6b7280;'>The workbook for <strong>{$productSafe}</strong> ({$clientNameSafe}) just moved into <strong>{$stepLabelSafe}</strong>. This is your heads-up that work on this stage is starting now.</p>"
              . ms_detail_table([
                  ['Client',         $clientNameSafe],
                  ['Product',        $productSafe],
                  ['Current stage',  $stepLabelSafe],
                  ['Tagged',         $watcherListSafe],
              ])
              . $appBtn
              . "<p style='margin:18px 0 0;font-size:13px;color:#9ba3c0;line-height:1.6;'>You were tagged as a watcher for this stage. This is a one-time alert — re-tag yourself on a future stage if you want to be notified again.</p>"
              . "<p style='margin:14px 0 0;font-size:14px;color:#374151;'>— Market Sculpt Workbook</p>";

        $html   = ms_email_wrap($subject, "Now at: {$stepLabel}", $body);
        $result = ms_smtp_send($recipients, $subject, $html);

        echo json_encode([
            'success'    => $result['ok'] ?? false,
            'recipients' => $recipients,
            'count'      => count($recipients),
            'error'      => $result['error'] ?? null,
        ]);
        break;

    case 'create_intake_token':
        // Operator-initiated: create a one-time intake link for a client and
        // email it to the address on file. The recipient lands on intake.php,
        // fills the Product Overview + RFQ Line Items, and submits — which
        // creates a draft workbook with a Pending Review flag (handled in
        // intake.php). Token expires after 30 days OR on first successful
        // submission, whichever comes first.
        $clientId = (int)($input['client_id'] ?? 0);
        if (!$clientId) {
            echo json_encode(['success' => false, 'error' => 'Client ID required']);
            break;
        }
        // Pull canonical client info from DB (don't trust whatever the client
        // sent — emails go to the address on file, not whatever the operator
        // passes in).
        $cstmt = $pdo->prepare("SELECT name, email, primary_contact FROM clients WHERE id = ? AND deleted_at IS NULL");
        $cstmt->execute([$clientId]);
        $client = $cstmt->fetch();
        if (!$client) {
            echo json_encode(['success' => false, 'error' => 'Client not found']);
            break;
        }
        $clientName  = (string)$client['name'];
        $clientEmail = trim((string)($client['email'] ?? ''));
        $contactName = trim((string)($client['primary_contact'] ?? ''));

        if ($clientEmail === '') {
            echo json_encode(['success' => false, 'error' => 'No email on file for this client. Add one in Client Details first.']);
            break;
        }
        if (!filter_var($clientEmail, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'error' => 'Invalid email on file: ' . $clientEmail]);
            break;
        }

        $intakeToken = bin2hex(random_bytes(32));
        $createdBy   = (int)($sessionUser['id'] ?? 0);

        try {
            $pdo->prepare("INSERT INTO intake_tokens
                (token, client_id, client_name, client_email, contact_name, created_by, expires_at)
                VALUES (?, ?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 30 DAY))")
                ->execute([$intakeToken, $clientId, $clientName, $clientEmail, $contactName, $createdBy ?: null]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => 'Could not create intake link: ' . $e->getMessage()]);
            break;
        }

        $scheme    = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host      = $_SERVER['HTTP_HOST'] ?? 'wb.marketsculpt.com';
        $intakeUrl = "{$scheme}://{$host}/intake.php?t={$intakeToken}";

        $greeting = $contactName ? "Hi {$contactName}," : "Hi there,";
        $subject  = "Quote Request — {$clientName}";

        $cta_btn = "<div style='text-align:center;margin:32px 0;'>"
                 . "<a href='" . htmlspecialchars($intakeUrl) . "' style='display:inline-block;background:#E8751A;color:#fff;font-size:15px;font-weight:700;text-decoration:none;padding:14px 36px;border-radius:8px;letter-spacing:0.01em;'>"
                 . "Start Your Quote Request &rarr;"
                 . "</a>"
                 . "<p style='margin:12px 0 0;font-size:12px;color:#9ba3c0;'>This link is one-time use and expires in 30 days.</p>"
                 . "</div>";

        $c_body = "<h1 style='margin:0 0 6px;font-size:26px;font-weight:800;color:#1a1d2e;'>Tell us about your product</h1>"
                . "<p style='margin:0 0 28px;font-size:15px;color:#6b7280;'>We&rsquo;ll prepare a quote for you.</p>"
                . "<p style='margin:0 0 16px;font-size:15px;color:#374151;line-height:1.7;'>" . htmlspecialchars($greeting) . "</p>"
                . "<p style='margin:0 0 16px;font-size:15px;color:#374151;line-height:1.7;'>"
                . "Click the button below to share details on what you&rsquo;d like quoted. You&rsquo;ll be asked to fill in a quick product overview and the items you&rsquo;d like priced. Once you submit, our team will review and follow up with pricing."
                . "</p>"
                . $cta_btn
                . "<p style='margin:24px 0 0;font-size:14px;color:#6b7280;line-height:1.7;'>If the button above doesn&rsquo;t work, copy and paste this link into your browser:<br>"
                . "<span style='word-break:break-all;color:#374151;'>" . htmlspecialchars($intakeUrl) . "</span></p>"
                . "<p style='margin:24px 0 0;font-size:15px;color:#374151;'>Thanks,<br><strong>Market Sculpt Team</strong></p>";

        $i_detail = [
            ['Client',  htmlspecialchars($clientName)],
            ['Contact', htmlspecialchars($contactName ?: '—')],
            ['Sent To', htmlspecialchars($clientEmail)],
            ['Expires', '30 days from now'],
            ['Intake Link', '<a href="' . htmlspecialchars($intakeUrl) . '" style="color:#E8751A;">' . htmlspecialchars($intakeUrl) . '</a>'],
        ];
        $i_body = "<h1 style='margin:0 0 6px;font-size:26px;font-weight:800;color:#1a1d2e;'>Quote Request Sent</h1>"
                . "<p style='margin:0 0 24px;font-size:15px;color:#6b7280;'>An intake link has been emailed to the client.</p>"
                . ms_detail_table($i_detail);

        $client_html   = ms_email_wrap($subject, "Tell us about your product so we can prepare a quote.", $c_body);
        $internal_html = ms_email_wrap("[Internal] " . $subject, "Intake link sent to {$clientName}", $i_body);

        $internal = ['jackson@marketsculpt.com', 'parker@marketsculpt.com'];
        $clientResult   = ms_smtp_send([$clientEmail], $subject, $client_html);
        $internalResult = ms_smtp_send($internal, '[Internal] ' . $subject, $internal_html);
        $ok = ($clientResult['ok'] ?? false) && ($internalResult['ok'] ?? false);

        echo json_encode([
            'success'    => $ok,
            'intake_url' => $intakeUrl,
            'token'      => $intakeToken,
            'results'    => ['client' => $clientResult, 'internal' => $internalResult],
        ]);
        break;

    case 'list_intake_tokens':
        // Returns active intake tokens for a single client (used by the
        // Client Details card to show "Pending — sent X days ago" state on
        // the button after the operator clicks it). Optional client_id filter.
        $cid = (int)($input['client_id'] ?? 0);
        if ($cid) {
            $stmt = $pdo->prepare("SELECT token, client_id, client_email, contact_name, status, created_at, expires_at, submitted_at, submitted_workbook_id
                                   FROM intake_tokens WHERE client_id = ? ORDER BY created_at DESC LIMIT 50");
            $stmt->execute([$cid]);
        } else {
            $stmt = $pdo->query("SELECT token, client_id, client_name, client_email, contact_name, status, created_at, expires_at, submitted_at, submitted_workbook_id
                                 FROM intake_tokens ORDER BY created_at DESC LIMIT 200");
        }
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
        break;

    case 'check_portal_status':
        // Returns { token => status } for all known tokens passed in
        $rawTokens = (array)($input['tokens'] ?? []);
        $tokens    = array_values(array_filter(array_map('strval', $rawTokens)));
        if (empty($tokens)) { echo json_encode([]); break; }
        $placeholders = implode(',', array_fill(0, count($tokens), '?'));
        $stmt = $pdo->prepare("SELECT token, status FROM portal_tokens WHERE token IN ($placeholders)");
        $stmt->execute($tokens);
        $statusMap = [];
        while ($row = $stmt->fetch()) { $statusMap[$row['token']] = $row['status']; }
        echo json_encode($statusMap);
        break;

    case 'get_pending_changes':
        // Returns all portal_tokens with status=changes_requested,
        // with enough info to match against local orders (no token required up front)
        $stmt = $pdo->query("SELECT token, client_name, order_snapshot FROM portal_tokens WHERE status = 'changes_requested'");
        $pending = [];
        while ($row = $stmt->fetch()) {
            $snap = json_decode($row['order_snapshot'], true) ?: [];
            $pending[] = [
                'token'       => $row['token'],
                'client_name' => $row['client_name'],
                'order_name'  => $snap['order']['name'] ?? '',
            ];
        }
        echo json_encode($pending);
        break;

    case 'get_change_request':
        // Returns the full change request detail for an order.
        // Accepts: { token } OR { client_name, order_name }
        $tokenIn     = $input['token']       ?? null;
        $clNameIn    = $input['client_name']  ?? null;
        $orderNameIn = $input['order_name']   ?? null;
        $crRow       = null;

        if ($tokenIn) {
            $s = $pdo->prepare("SELECT * FROM portal_tokens WHERE token = ? AND status = 'changes_requested'");
            $s->execute([$tokenIn]);
            $crRow = $s->fetch();
        } elseif ($clNameIn) {
            // Fetch all changes_requested for this client, match by order name in snapshot
            $s = $pdo->prepare("SELECT * FROM portal_tokens WHERE client_name = ? AND status = 'changes_requested' ORDER BY created_at DESC");
            $s->execute([$clNameIn]);
            while ($candidate = $s->fetch()) {
                $snap      = json_decode($candidate['order_snapshot'], true) ?: [];
                $snapName  = $snap['order']['name'] ?? '';
                if (!$orderNameIn || $snapName === $orderNameIn) { $crRow = $candidate; break; }
            }
        }

        if (!$crRow) { echo json_encode(['success' => false, 'error' => 'No active change request found']); break; }
        $crSnap = json_decode($crRow['order_snapshot'], true) ?: [];
        echo json_encode([
            'success'        => true,
            'line_changes'   => json_decode($crRow['line_changes'] ?? '[]', true) ?: [],
            'client_comment' => $crRow['client_comment'] ?? '',
            'client_name'    => $crRow['client_name'],
            'client_email'   => $crRow['client_email'],
            'items'          => $crSnap['items'] ?? [],
            'submitted_at'   => $crRow['created_at'],
        ]);
        break;

    case 'get_app_state':
        // Retrieve a shared key-value entry (e.g. orders, shipments)
        $stateKey = $input['key'] ?? '';
        if (!$stateKey) { echo json_encode(['success' => false, 'error' => 'key required']); break; }
        $stmtGAS = $pdo->prepare("SELECT value_json FROM app_state WHERE key_name = ?");
        $stmtGAS->execute([$stateKey]);
        $rowGAS = $stmtGAS->fetch();
        echo json_encode(['success' => true, 'value' => $rowGAS ? $rowGAS['value_json'] : null]);
        break;

    case 'save_app_state':
        // Upsert a shared key-value entry
        $stateKey = $input['key']   ?? '';
        $stateVal = $input['value'] ?? null;
        if (!$stateKey) { echo json_encode(['success' => false, 'error' => 'key required']); break; }
        $pdo->prepare(
            "INSERT INTO app_state (key_name, value_json) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE value_json = VALUES(value_json), updated_at = NOW()"
        )->execute([$stateKey, $stateVal]);
        echo json_encode(['success' => true]);
        break;

    case 'get_fx_rate':
        // Returns USD→CNY mid-market rate.
        // Tries XE.com first (parses __NEXT_DATA__ for CNY→USD, then inverts),
        // falls back to open.er-api.com.
        $fxRate   = null;
        $fxSource = '';

        // ① XE.com via curl
        if (function_exists('curl_init')) {
            $ch = curl_init('https://www.xe.com/currencyconverter/convert/?Amount=1&From=CNY&To=USD');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT        => 6,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_HTTPHEADER     => [
                    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/124.0.0.0 Safari/537.36',
                    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Accept-Language: en-US,en;q=0.5',
                ],
            ]);
            $xeHtml = curl_exec($ch);
            curl_close($ch);

            if ($xeHtml) {
                // XE embeds rates in __NEXT_DATA__ JSON — look for "mid":0.1381
                if (preg_match('/"mid"\s*:\s*([\d.]+)/', $xeHtml, $xeMatch)) {
                    $cnyToUsd = floatval($xeMatch[1]);
                    // Sanity check: 1 CNY should be between $0.05 and $1.00 USD
                    if ($cnyToUsd > 0.05 && $cnyToUsd < 1.0) {
                        $fxRate   = round(1.0 / $cnyToUsd, 6); // store as USD→CNY
                        $fxSource = 'xe';
                    }
                }
            }
        }

        // ② Fallback: open.er-api.com (free, reliable, mid-market)
        if (!$fxRate) {
            $erJson = @file_get_contents('https://open.er-api.com/v6/latest/USD');
            $erData = json_decode($erJson, true);
            if (!empty($erData['rates']['CNY'])) {
                $fxRate   = floatval($erData['rates']['CNY']);
                $fxSource = 'open.er-api';
            }
        }

        echo json_encode(['rate' => $fxRate, 'source' => $fxSource]);
        break;

    // ─── PRESENCE ──────────────────────────────────────────
    // Heartbeat: upsert caller's row; called every 5 s from the client.
    case 'update_presence':
        $uid   = $sessionUser['id'];
        $name  = $sessionUser['display_name'] ?: $sessionUser['username'];
        $wbId  = intval($input['workbook_id']   ?? 0);
        $field = substr($input['focused_field'] ?? '', 0, 255);
        $color = preg_match('/^#[0-9a-fA-F]{6}$/', $input['color'] ?? '')
               ? $input['color'] : '#888888';
        $pdo->prepare(
            "INSERT INTO presence (user_id, display_name, workbook_id, focused_field, color)
             VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
               display_name=VALUES(display_name), workbook_id=VALUES(workbook_id),
               focused_field=VALUES(focused_field), color=VALUES(color), last_seen=NOW()"
        )->execute([$uid, $name, $wbId, $field, $color]);
        echo json_encode(['success' => true]);
        break;

    // Poll: return other active users on the same workbook (seen in last 15 s).
    case 'get_presence':
        $uid  = $sessionUser['id'];
        $wbId = intval($input['workbook_id'] ?? 0);
        $stmt = $pdo->prepare(
            "SELECT display_name, focused_field, color FROM presence
             WHERE workbook_id = ? AND user_id != ?
               AND last_seen > NOW() - INTERVAL 15 SECOND"
        );
        $stmt->execute([$wbId, $uid]);
        echo json_encode(['success' => true, 'users' => $stmt->fetchAll()]);
        break;

    // Clear: remove the caller's row immediately (on tab close / navigate away).
    case 'get_notifications':
        // Polled every ~12 s by every connected client. Returns rows for
        // the current user newer than the supplied cursor; the client
        // uses server_now as its next cursor. Limit caps a runaway burst
        // (eg every workbook in the system advancing in a minute) at a
        // sane batch size.
        $since = (string)($input['since'] ?? '');
        if ($since === '' || !preg_match('/^\d{4}-\d{2}-\d{2}/', $since)) {
            $since = '1970-01-01 00:00:00';
        }
        $stmt = $pdo->prepare(
            "SELECT id, kind, title, body, url, created_at
             FROM notifications
             WHERE user_id = ? AND created_at > ?
             ORDER BY created_at ASC LIMIT 30"
        );
        $stmt->execute([$sessionUser['id'], $since]);
        $rows = $stmt->fetchAll();
        // Fractional-second precision so bursts within the same second
        // can still be cursored correctly. fmt: 'Y-m-d H:i:s.u' →
        // mysql DATETIME(3) compares the millisecond fraction.
        $now = (new DateTime('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s.v');
        echo json_encode([
            'success'    => true,
            'data'       => $rows,
            'server_now' => $now,
        ]);
        break;

    case 'mark_notification_read':
        $nid = (int)($input['id'] ?? 0);
        if ($nid) {
            $pdo->prepare("UPDATE notifications SET read_at = NOW() WHERE id = ? AND user_id = ?")
                ->execute([$nid, $sessionUser['id']]);
        }
        echo json_encode(['success' => true]);
        break;

    case 'clear_presence':
        $uid = $sessionUser['id'];
        $pdo->prepare("DELETE FROM presence WHERE user_id = ?")->execute([$uid]);
        echo json_encode(['success' => true]);
        break;

    // ─── LIVE CELL VALUES ─────────────────────────────────────
    // Debounced from the client on every input event. Stores the in-progress
    // value so other viewers see typing before the workbook is saved.
    case 'push_cell_value':
        $uid   = $sessionUser['id'];
        $wbId  = intval($input['workbook_id'] ?? 0);
        $field = substr($input['field_path']  ?? '', 0, 255);
        $value = (string)($input['value'] ?? '');
        if ($wbId <= 0 || $field === '') {
            echo json_encode(['success' => false, 'error' => 'workbook_id and field_path required']);
            break;
        }
        // Cap the value to keep payload bounded — anything past 8 KB is almost
        // certainly a bug, not legitimate cell content.
        if (strlen($value) > 8192) $value = substr($value, 0, 8192);
        $pdo->prepare(
            "INSERT INTO cell_values (workbook_id, field_path, value, updated_by)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
               value=VALUES(value), updated_by=VALUES(updated_by), updated_at=NOW()"
        )->execute([$wbId, $field, $value, $uid]);
        // Probabilistic cleanup: ~2 % of writes purge rows older than 5 min,
        // so the table stays bounded without a cron job. The data here is
        // ephemeral — once the workbook is saved, detail_json is the source
        // of truth and these rows are no longer useful.
        if (mt_rand(1, 50) === 1) {
            $pdo->exec("DELETE FROM cell_values WHERE updated_at < NOW() - INTERVAL 5 MINUTE");
        }
        echo json_encode(['success' => true, 'server_now' => date('Y-m-d H:i:s')]);
        break;

    // Returns every cell value for this workbook updated after `since`,
    // excluding rows last touched by the caller (they already have those locally).
    // `since` is the server-side timestamp returned from the previous poll.
    case 'pull_cell_values':
        $uid   = $sessionUser['id'];
        $wbId  = intval($input['workbook_id'] ?? 0);
        $since = trim($input['since'] ?? '');
        if ($wbId <= 0) {
            echo json_encode(['success' => false, 'error' => 'workbook_id required']);
            break;
        }
        if ($since === '') {
            // First poll: fetch the last 60 s only — we don't want to apply
            // ancient pending values that may already be saved into detail_json.
            $stmt = $pdo->prepare(
                "SELECT field_path, value, updated_by, updated_at
                 FROM cell_values
                 WHERE workbook_id = ?
                   AND updated_by != ?
                   AND updated_at > NOW() - INTERVAL 60 SECOND
                 ORDER BY updated_at ASC"
            );
            $stmt->execute([$wbId, $uid]);
        } else {
            $stmt = $pdo->prepare(
                "SELECT field_path, value, updated_by, updated_at
                 FROM cell_values
                 WHERE workbook_id = ?
                   AND updated_by != ?
                   AND updated_at > ?
                 ORDER BY updated_at ASC"
            );
            $stmt->execute([$wbId, $uid, $since]);
        }
        $rows = $stmt->fetchAll();
        echo json_encode([
            'success'    => true,
            'updates'    => $rows,
            'server_now' => date('Y-m-d H:i:s')
        ]);
        break;

    default:
        echo json_encode(['error' => 'Unknown action', 'available' => [
            'get_clients', 'add_client', 'delete_client',
            'get_workbooks', 'get_workbook', 'add_workbook', 'update_workbook',
            'update_flow', 'delete_workbook', 'save_workbook_detail',
            'get_recent', 'get_all_data',
            'get_revisions', 'get_revision_detail', 'restore_revision',
            'get_archived', 'restore_workbook', 'restore_client',
            'permanent_delete_workbook', 'permanent_delete_client',
            'upload_image', 'delete_image', 'upload_video',
            'get_users', 'add_user', 'update_user', 'delete_user', 'change_password',
            'duplicate_workbook',
            'get_inventory', 'promote_to_sku', 'remove_sku',
            'get_commissions', 'set_commission_status', 'recompute_commissions',
            'update_presence', 'get_presence', 'clear_presence',
            'push_cell_value', 'pull_cell_values',
            'diagnose_workbook', 'diagnose_revisions', 'merge_recover_workbook',
        ]]);
}
