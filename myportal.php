<?php
// Market Sculpt — Client Portal (live, per-client dashboard)
// One stable link per client (?t=<64 hex>), gated by a 6-digit PIN emailed
// to the client. Shows LIVE status for ALL of the client's orders — the
// stage (Ordered → In Production → Shipped → In Transit → Arriving →
// Delivered), ETA, and shipment info — read from app_state each visit.
// No pricing or tracking numbers. Mirrors track.php's stage logic, but
// aggregated across every order instead of a single one.
session_start();

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

// Same table api.php's mint_client_portal creates.
$pdo->exec("CREATE TABLE IF NOT EXISTS client_portal_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    token CHAR(64) NOT NULL,
    client_id INT DEFAULT NULL,
    client_name VARCHAR(255) DEFAULT '',
    pin CHAR(6) DEFAULT NULL,
    pin_attempts INT NOT NULL DEFAULT 0,
    locked_until TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_cpt_token (token),
    UNIQUE KEY uq_cpt_client (client_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// ── Validate token ──────────────────────────────────────────────────────────
$token = trim($_GET['t'] ?? '');
if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
    portalPage('Invalid Link', errorContent('This portal link is not valid. Please contact your Market Sculpt representative.'));
    exit;
}
$stmt = $pdo->prepare("SELECT * FROM client_portal_tokens WHERE token = ?");
$stmt->execute([$token]);
$row = $stmt->fetch();
if (!$row) {
    portalPage('Link Not Found', errorContent('This portal link has expired or no longer exists. Please contact your Market Sculpt representative for assistance.'));
    exit;
}

$clientName = (string)$row['client_name'];

// ── PIN gate ──────────────────────────────────────────────────────────────
// Requires the 6-digit PIN once per browser session before any data shows.
// Rate-limited per token (6 misses → 15-min lockout) so it can't be
// brute-forced. Legacy rows without a PIN open directly.
$requiredPin = trim((string)($row['pin'] ?? ''));
$sessOkKey   = 'cportal_ok_' . $token;
if ($requiredPin !== '' && empty($_SESSION[$sessOkKey])) {
    $pinError = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pin'])) {
        $now           = time();
        $lockedUntilTs = !empty($row['locked_until']) ? strtotime((string)$row['locked_until']) : 0;
        if ($lockedUntilTs > $now) {
            $pinError = 'Too many incorrect attempts. Please wait a few minutes and try again.';
        } else {
            $entered = preg_replace('/\D/', '', (string)$_POST['pin']);
            if ($entered !== '' && hash_equals($requiredPin, $entered)) {
                $_SESSION[$sessOkKey] = true;
                try { $pdo->prepare("UPDATE client_portal_tokens SET pin_attempts=0, locked_until=NULL WHERE token=?")->execute([$token]); } catch (PDOException $e) {}
                header('Location: ?t=' . urlencode($token));
                exit;
            }
            $priorAttempts = $lockedUntilTs > 0 ? 0 : (int)($row['pin_attempts'] ?? 0);
            $attempts = $priorAttempts + 1;
            if ($attempts >= 6) {
                try { $pdo->prepare("UPDATE client_portal_tokens SET pin_attempts=?, locked_until=DATE_ADD(NOW(), INTERVAL 15 MINUTE) WHERE token=?")->execute([$attempts, $token]); } catch (PDOException $e) {}
                $pinError = 'Too many incorrect attempts. Please wait 15 minutes and try again.';
            } else {
                try { $pdo->prepare("UPDATE client_portal_tokens SET pin_attempts=? WHERE token=?")->execute([$attempts, $token]); } catch (PDOException $e) {}
                $pinError = "That PIN doesn't match. Please check the code we emailed you.";
            }
        }
    }
    portalPage('Enter Access PIN', pinContent($token, $clientName, $pinError));
    exit;
}

// ── Load the client's portal items ───────────────────────────────────────────
// Board mirrors the internal pipeline (RFQ → Shipments). Each workbook the
// operator toggled "Show in client portal" is placed in the column matching
// its real pipeline stage. The operator's custom message + dates/tracking
// show on the card. No pricing / internal notes.
$orders    = cpLoadState($pdo, 'ms_orders');
$shipments = cpLoadState($pdo, 'ms_shipments');

$clientId = (int)($row['client_id'] ?? 0);
if ($clientId <= 0 && $clientName !== '') {
    try {
        $cidStmt = $pdo->prepare("SELECT id FROM clients WHERE name = ? LIMIT 1");
        $cidStmt->execute([$clientName]);
        $clientId = (int)($cidStmt->fetchColumn() ?: 0);
    } catch (PDOException $e) { /* clients table shape may differ */ }
}

$portalItems = [];
if ($clientId > 0) {
    try {
        $wbStmt = $pdo->prepare("SELECT id, product_name, detail_json FROM workbooks WHERE client_id = ? ORDER BY id DESC");
        $wbStmt->execute([$clientId]);
        foreach ($wbStmt->fetchAll() as $wb) {
            $d = json_decode($wb['detail_json'] ?? '{}', true);
            if (!is_array($d)) continue;
            if (($d['showInPortal'] ?? '') !== '1' && ($d['showInPortal'] ?? null) !== true) continue;
            $name  = trim((string)($wb['product_name'] ?? '')) ?: (trim((string)($d['product'] ?? '')) ?: 'Item');
            $stage = cpWbPipelineStage($clientName, (string)$wb['id'], $d, $orders, $shipments);
            $portalItems[] = [
                'name'          => $name,
                'stage'         => $stage,
                'message'       => trim((string)($d['portalMessage'] ?? '')),
                'dateSubmitted' => trim((string)($d['portalDateSubmitted'] ?? '')),
                'dateExpected'  => trim((string)($d['portalDateExpected'] ?? '')),
                'tracking'      => trim((string)($d['portalTracking'] ?? '')),
            ];
        }
    } catch (PDOException $e) { $portalItems = []; }
}

portalPage($clientName !== '' ? $clientName : 'Your Portal', portalItemsContent($clientName, $portalItems));
exit;

// ════════════════════════════════════════════════════════════════════════════
// DATA HELPERS (mirror track.php)
// ════════════════════════════════════════════════════════════════════════════

function cpLoadState(PDO $pdo, string $key): array {
    $st = $pdo->prepare("SELECT value_json FROM app_state WHERE key_name = ?");
    $st->execute([$key]);
    $r = $st->fetch();
    if (!$r || $r['value_json'] === null) return [];
    $d = json_decode($r['value_json'], true);
    if (!is_array($d)) return [];
    if (isset($d['data']) && is_array($d['data'])) return $d['data'];
    return $d;
}

function cpShipmentCarriesOrder(array $s, array $order, string $orderId): bool {
    $orderRefs = [];
    foreach (($order['entries'] ?? []) as $e) {
        if (!is_array($e)) continue;
        $orderRefs[($e['clientName'] ?? '') . '|' . ($e['workbookId'] ?? '')] = true;
    }
    $allEntries = array_merge($s['entries'] ?? [], $s['sampleEntries'] ?? []);
    foreach ($allEntries as $e) {
        if (!is_array($e)) continue;
        if (isset($e['orderId']) && (string)$e['orderId'] === $orderId) return true;
        if (isset($e['clientName'], $e['workbookId']) && isset($orderRefs[$e['clientName'] . '|' . $e['workbookId']])) return true;
        if (isset($e['sampleKey']) && isset($orderRefs[$e['sampleKey']])) return true;
    }
    return false;
}

function cpShipStageIdx(array $s): int {
    switch ($s['status'] ?? 'planning') {
        case 'in_transit':      return 3;
        case 'waiting_arrival': return 4;
        case 'delivered':
        case 'received':        return 5;
        default:                return 2;
    }
}

function cpComputeStage(array $order, array $carrying): array {
    if (empty($carrying)) {
        $idx = !empty($order['notifiedAt']) ? 1 : 0;
        return ['idx' => $idx, 'eta' => '', 'deliveredOn' => ''];
    }
    $minIdx = 6; $etaCandidates = []; $deliveredDates = [];
    foreach ($carrying as $s) {
        $si = cpShipStageIdx($s);
        if ($si < $minIdx) $minIdx = $si;
        if ($si >= 5) {
            $d = trim((string)($s['deliveredOn'] ?? $s['receivedAt'] ?? ''));
            if ($d !== '') $deliveredDates[] = $d;
        } else {
            $e = trim((string)($s['eta'] ?? ''));
            if ($e === '' && isset($s['tracking']['eta'])) $e = trim((string)$s['tracking']['eta']);
            if ($e !== '') { $ts = strtotime($e); $etaCandidates[] = [$ts !== false ? $ts : null, $e]; }
        }
    }
    if ($minIdx === 6) $minIdx = 2;
    $eta = '';
    if ($etaCandidates) {
        usort($etaCandidates, function ($a, $b) {
            if ($a[0] === null) return 1; if ($b[0] === null) return -1; return $a[0] <=> $b[0];
        });
        $best = $etaCandidates[0];
        $eta  = $best[0] !== null ? date('M j, Y', $best[0]) : $best[1];
    }
    $deliveredOn = '';
    if ($minIdx >= 5 && $deliveredDates) {
        $d = $deliveredDates[0]; $ts = strtotime($d);
        $deliveredOn = $ts !== false ? date('M j, Y', $ts) : $d;
    }
    return ['idx' => $minIdx, 'eta' => $eta, 'deliveredOn' => $deliveredOn];
}

// ════════════════════════════════════════════════════════════════════════════
// PAGE RENDERERS
// ════════════════════════════════════════════════════════════════════════════

const CP_STEPS = ['Ordered', 'In Production', 'Shipped', 'In Transit', 'Arriving', 'Delivered'];

// Client-facing status options (key → [friendly label, progress %]). Kept
// for the detail-card message fallback.
function cpPortalStatuses(): array {
    return [
        'request'     => ["We're working on your request", 10],
        'quoting'     => ['Sourcing options & preparing your quote', 22],
        'sampling'    => ['Preparing your samples', 38],
        'sample_sent' => ['Samples sent — awaiting your review', 50],
        'approved'    => ['Approved — prepping production', 62],
        'production'  => ['In production', 75],
        'shipped'     => ['Shipped — on the way', 92],
        'delivered'   => ['Delivered', 100],
    ];
}

// Portal board columns — mirror the internal pipeline (RFQ → Shipments).
// key => [label, header-bg, header-fg].
function cpBoardColumns(): array {
    return [
        'rfq'        => ['Sourcing & Quoting', '#dbeafe', '#1e40af'],
        'review'     => ['In Review',          '#e0e7ff', '#3730a3'],
        'samples'    => ['Sampling',           '#dcfce7', '#15803d'],
        'orders'     => ['Order Placed',       '#fef3c7', '#a16207'],
        'production' => ['In Production',       '#ffedd5', '#c2410c'],
        'shipments'  => ['Shipping',           '#cffafe', '#0e7490'],
    ];
}

// Compute a workbook's pipeline stage (PHP port of _pipelineStageFor). Returns
// one of: rfq, review, samples, orders, production, shipments, receiving,
// done, unstaged.
function cpWbPipelineStage(string $clientName, string $wbId, array $detail, array $orders, array $shipments): string {
    $wantKey = $clientName . '|' . $wbId;
    $direct = function ($e) use ($clientName, $wbId) {
        return is_array($e)
            && (string)($e['clientName'] ?? '') === $clientName
            && (string)($e['workbookId'] ?? '') === (string)$wbId;
    };
    // Shipments carrying this workbook (direct / via order / sample).
    $ships = [];
    foreach ($shipments as $s) {
        if (!is_array($s)) continue;
        $hit = false;
        foreach (($s['entries'] ?? []) as $e) {
            if ($direct($e)) { $hit = true; break; }
            if (!empty($e['orderId']) && isset($orders[$e['orderId']])) {
                foreach (($orders[$e['orderId']]['entries'] ?? []) as $oe) { if ($direct($oe)) { $hit = true; break 2; } }
            }
            if (isset($e['sampleKey']) && (string)$e['sampleKey'] === $wantKey) { $hit = true; break; }
        }
        if (!$hit) foreach (($s['sampleEntries'] ?? []) as $e) { if ($direct($e)) { $hit = true; break; } }
        if ($hit) $ships[] = $s;
    }
    if ($ships) {
        $allReceived = true; $anyDelivered = false; $anyActive = false;
        foreach ($ships as $s) {
            $st = (string)($s['status'] ?? '');
            if ($st === 'delivered') $anyDelivered = true;
            if ($st !== 'received') $allReceived = false;
            if ($st !== 'received' && $st !== 'delivered') $anyActive = true;
        }
        if ($anyDelivered) return 'receiving';
        if ($anyActive)    return 'shipments';
        if ($allReceived)  return 'done';
    }
    // Orders for this workbook.
    $inOrder = false; $inFulfillment = false;
    foreach ($orders as $o) {
        if (!is_array($o)) continue;
        foreach (($o['entries'] ?? []) as $e) {
            if ($direct($e)) { $inOrder = true; if (!empty($o['notifiedAt'])) $inFulfillment = true; break; }
        }
    }
    if ($inFulfillment) return 'production';
    if ($inOrder)       return 'orders';
    // Flag-based early stages.
    if (!empty($detail['movedToOrders'])) return 'orders';
    if (!empty($detail['sentForReview'])) return 'review';
    if (!empty($detail['sentToRfq']))     return 'rfq';
    if (!empty($detail['rfqItems']) && is_array($detail['rfqItems'])) {
        foreach ($detail['rfqItems'] as $i) { if (is_array($i) && !empty($i['sample'])) return 'samples'; }
    }
    return 'unstaged';
}

// Map a raw stage to one of the 6 board columns (receiving → Shipments,
// unstaged → RFQ so a freshly-shared item still shows; done → hidden).
function cpStageToColumn(string $stage): string {
    switch ($stage) {
        case 'receiving': return 'shipments';
        case 'unstaged':  return 'rfq';
        case 'done':      return '';
        default:          return $stage;
    }
}

function portalItemsContent(string $clientName, array $items): string {
    $header = '<div class="cp-head">'
            . '<div class="cp-hello">Welcome' . ($clientName !== '' ? ', ' . htmlspecialchars($clientName) : '') . '</div>'
            . '<div class="cp-sub">Here\'s what we\'re working on for you. This page updates as each item moves along.</div>'
            . '</div>';

    if (empty($items)) {
        return $header . '<div class="card"><div class="card-body" style="text-align:center;padding:56px 24px;">'
             . '<div class="soft-icon" style="background:#fff7ed;"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#E8751A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg></div>'
             . '<h2 style="font-size:19px;font-weight:800;color:#1a1d2e;margin:4px 0 8px;">Nothing to show just yet</h2>'
             . '<p style="font-size:14px;color:#6b7280;max-width:420px;margin:0 auto;line-height:1.6;">Your items will appear here with live status as we start working on them. Your Market Sculpt rep will be in touch in the meantime.</p>'
             . '</div></div>';
    }

    // Group items into their pipeline column.
    $cols  = cpBoardColumns();
    $byCol = [];
    foreach ($items as $o) {
        $col = cpStageToColumn((string)($o['stage'] ?? 'unstaged'));
        if ($col === '' || !isset($cols[$col])) continue; // done / unknown → hide
        $byCol[$col][] = $o;
    }
    $shown = array_sum(array_map('count', $byCol));
    $summary = '<div class="cp-summary">' . $shown . ' item' . ($shown === 1 ? '' : 's') . ' in progress</div>';

    $colKeys = array_keys($cols);
    $total   = count($colKeys);
    $colsHtml = '';
    foreach ($colKeys as $idx => $key) {
        [$label, $bg, $fg] = $cols[$key];
        $colItems = $byCol[$key] ?? [];
        $cards = '';
        foreach ($colItems as $o) $cards .= cpPortalCard($o, $idx, $total, $key);
        if ($cards === '') $cards = '<div class="cp-col-empty">—</div>';
        $colsHtml .= '<div class="cp-col">'
                   . '<div class="cp-col-head" style="background:' . $bg . ';color:' . $fg . ';border-color:' . $bg . ';">'
                   . '<span>' . htmlspecialchars($label) . '</span>'
                   . '<span class="cp-col-count" style="color:' . $fg . ';">' . count($colItems) . '</span></div>'
                   . '<div class="cp-col-body">' . $cards . '</div></div>';
    }

    $note = '<div class="cp-watermark" aria-hidden="true">'
          . '<img src="ms-watermark.png" alt="" class="cp-wm-img" onerror="this.style.display=\'none\';var s=this.nextElementSibling;if(s)s.style.display=\'inline-block\';">'
          . '<span class="cp-wm-mark" style="display:none;">market<span class="cp-wm-sculpt">sculpt</span></span>'
          . '</div>';
    $board = '<div class="cp-board-wrap"><div class="cp-board" id="cp-board">' . $colsHtml . '</div>'
           . '<div class="cp-board-fade" id="cp-board-fade"><span>›</span></div></div>';
    // Watermark sits as a background layer BEHIND the board, so the cards
    // render on top of it.
    $boardArea = '<div class="cp-board-area">' . $note . $board . '</div>';
    $script = '<script>(function(){var b=document.getElementById("cp-board"),f=document.getElementById("cp-board-fade");'
            . 'if(!b||!f)return;function u(){f.style.opacity=(b.scrollWidth-b.clientWidth-b.scrollLeft>8)?"1":"0";}'
            . 'b.addEventListener("scroll",u);window.addEventListener("resize",u);setTimeout(u,60);})();</script>';
    return $header . $summary . $boardArea . $script;
}

function cpFmtDate(string $d): string { $ts = strtotime($d); return $ts ? date('M j, Y', $ts) : htmlspecialchars($d); }

// Client-friendly default line per board column (shown when the operator
// hasn't written a custom message).
function cpColDefaultMsg(string $colKey): string {
    $m = [
        'rfq'        => "We're sourcing options and preparing your quote.",
        'review'     => "Finalizing the details and reviewing everything.",
        'samples'    => "We're preparing your samples.",
        'orders'     => "Your order is placed and queued for production.",
        'production' => "Your order is in production.",
        'shipments'  => "Your order is on its way.",
    ];
    return $m[$colKey] ?? "We're working on it.";
}

// Detailed portal card (name + a client-facing line + progress bar +
// expected date/tracking). Uses the operator's CUSTOM message if set,
// otherwise the column's default sentence.
function cpPortalCard(array $o, int $colIdx = 0, int $total = 6, string $colKey = ''): string {
    $name   = htmlspecialchars((string)($o['name'] ?? 'Item'));
    $pct    = (int)round((($colIdx + 1) / max(1, $total)) * 100);
    $last   = ($colIdx >= $total - 1);
    $accent = $last ? '#0e7490' : '#E8751A';

    // Custom message (anything that isn't one of the stock dropdown defaults)
    // wins; otherwise fall back to the column's default line.
    static $stockMsgs = null;
    if ($stockMsgs === null) { $stockMsgs = []; foreach (cpPortalStatuses() as $r) $stockMsgs[$r[0]] = true; }
    $raw     = trim((string)($o['message'] ?? ''));
    $display = ($raw !== '' && !isset($stockMsgs[$raw])) ? $raw : cpColDefaultMsg($colKey);
    $msgHtml = $display !== '' ? '<div class="cp-order-sub" style="margin:7px 0 0;">' . htmlspecialchars($display) . '</div>' : '';

    $meta = [];
    if (!empty($o['dateExpected'])) $meta[] = 'Expected ' . cpFmtDate((string)$o['dateExpected']);
    if (!empty($o['tracking']))     $meta[] = 'Tracking: <strong>' . htmlspecialchars((string)$o['tracking']) . '</strong>';
    $metaHtml = $meta ? '<div class="cp-order-sub" style="margin-top:12px;font-size:12.5px;">' . implode('<br>', $meta) . '</div>' : '';

    return '<div class="card cp-order">'
         . '<div class="card-body" style="padding:15px 17px;">'
         . '<h2 class="cp-order-name" style="font-size:15px;">' . $name . '</h2>'
         . $msgHtml
         . '<div class="cp-bar" style="margin-top:12px;"><div class="cp-bar-fill" style="width:' . $pct . '%;background:' . $accent . ';"></div></div>'
         . $metaHtml
         . '</div></div>';
}

function dashboardContent(string $clientName, array $mine): string {
    $activeCount = 0;
    foreach ($mine as $o) if ($o['stage']['idx'] < 5) $activeCount++;

    $header = '<div class="cp-head">'
            . '<div class="cp-hello">Welcome' . ($clientName !== '' ? ', ' . htmlspecialchars($clientName) : '') . '</div>'
            . '<div class="cp-sub">Live status for your orders with Market Sculpt. This page updates on its own as each order moves along.</div>'
            . '</div>';

    if (empty($mine)) {
        return $header . '<div class="card"><div class="card-body" style="text-align:center;padding:56px 24px;">'
             . '<div class="soft-icon" style="background:#fff7ed;"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#E8751A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg></div>'
             . '<h2 style="font-size:19px;font-weight:800;color:#1a1d2e;margin:4px 0 8px;">No active orders yet</h2>'
             . '<p style="font-size:14px;color:#6b7280;max-width:420px;margin:0 auto;line-height:1.6;">When your orders are placed they\'ll appear here with live status. Your Market Sculpt rep will be in touch in the meantime.</p>'
             . '</div></div>';
    }

    $summary = '<div class="cp-summary">' . count($mine) . ' order' . (count($mine) === 1 ? '' : 's')
             . ($activeCount ? ' · <strong>' . $activeCount . ' active</strong>' : '') . '</div>';

    $cards = '';
    foreach ($mine as $o) {
        $cards .= cpOrderCard($o['name'], $o['stage']);
    }

    $note = '<p class="cp-footnote">Questions about an order? Reply to your Market Sculpt email or contact your representative any time.</p>';

    return $header . $summary . $cards . $note;
}

function cpOrderCard(string $orderName, array $stage): string {
    $idx    = (int)$stage['idx'];
    $label  = CP_STEPS[$idx];
    $done   = $idx >= 5;
    $accent = $done ? '#16a34a' : '#E8751A';
    $pct    = $done ? 100 : round(($idx / 5) * 100);

    if ($done && $stage['deliveredOn'] !== '')            $sub = 'Delivered on ' . htmlspecialchars($stage['deliveredOn']);
    elseif ($idx >= 2 && $idx < 5 && $stage['eta'] !== '') $sub = 'Estimated arrival · ' . htmlspecialchars($stage['eta']);
    elseif ($idx === 1)                                    $sub = 'In production now.';
    elseif ($idx === 0)                                    $sub = 'Order placed and confirmed.';
    else                                                   $sub = 'On its way.';

    $badge = '<span class="cp-badge" style="background:' . ($done ? '#dcfce7' : '#fff7ed')
           . ';color:' . ($done ? '#166534' : '#9a3412') . ';">' . htmlspecialchars($label) . '</span>';

    // Compact 6-dot progress rail with connecting fill.
    $dots = '';
    for ($i = 0; $i < 6; $i++) {
        $state = $i < $idx ? 'done' : ($i === $idx ? 'current' : 'todo');
        $line  = $i < 5 ? '<span class="cp-dot-line ' . ($i < $idx ? 'is-filled' : '') . '"></span>' : '';
        $dots .= '<span class="cp-dot cp-dot-' . $state . '"' . ($state === 'current' ? ' style="--cp-accent:' . $accent . ';"' : '') . '></span>' . $line;
    }

    return '<div class="card cp-order">'
         . '<div class="card-body">'
         . '<div class="cp-order-top">'
         . '<h2 class="cp-order-name">' . htmlspecialchars($orderName) . '</h2>' . $badge
         . '</div>'
         . '<div class="cp-order-sub">' . $sub . '</div>'
         . '<div class="cp-bar"><div class="cp-bar-fill" style="width:' . $pct . '%;background:' . $accent . ';"></div></div>'
         . '<div class="cp-rail">' . $dots . '</div>'
         . '<div class="cp-rail-labels"><span>Ordered</span><span>Shipped</span><span>Delivered</span></div>'
         . '</div></div>';
}

function pinContent(string $token, string $clientName, string $error): string {
    $err = $error !== ''
        ? "<div style='margin:0 0 16px;padding:11px 14px;background:#fef2f2;border:1px solid #fecaca;border-radius:8px;color:#b91c1c;font-size:13px;line-height:1.5;'>" . htmlspecialchars($error) . "</div>"
        : '';
    $who = $clientName !== '' ? htmlspecialchars($clientName) . ', ' : '';
    return '<div class="card" style="max-width:440px;margin:32px auto;">'
         . '<div class="card-body">'
         . '<div class="soft-icon" style="background:#fff7ed;margin:0 0 14px;">'
         . '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#E8751A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>'
         . '</div>'
         . '<h1 style="font-size:20px;font-weight:800;color:#1a1d2e;margin:0 0 6px;">Enter your access PIN</h1>'
         . "<p style='font-size:14px;color:#6b7280;margin:0 0 18px;line-height:1.6;'>{$who}we emailed a 6-digit PIN to open your portal. Enter it below to continue.</p>"
         . $err
         . '<form method="POST" action="?t=' . urlencode($token) . '">'
         . '<input type="text" name="pin" inputmode="numeric" autocomplete="one-time-code" maxlength="6" pattern="[0-9]*" placeholder="••••••" autofocus '
         . 'style="width:100%;text-align:center;font-size:28px;letter-spacing:12px;padding:14px 10px;border:1px solid #d1d5db;border-radius:10px;font-family:inherit;color:#1a1d2e;outline:none;box-sizing:border-box;" />'
         . '<button type="submit" style="width:100%;margin-top:16px;background:#E8751A;color:#fff;border:none;border-radius:8px;font-size:15px;font-weight:700;padding:13px;cursor:pointer;font-family:inherit;">Open portal &rarr;</button>'
         . '</form>'
         . '<p style="margin:16px 0 0;font-size:12px;color:#9ba3c0;text-align:center;line-height:1.6;">Didn\'t get a PIN? Contact your Market Sculpt representative.</p>'
         . '</div></div>';
}

function errorContent(string $msg): string {
    return '<div class="card"><div class="card-body" style="text-align:center;padding:60px 24px;">'
         . '<div class="soft-icon" style="background:#fef2f2;">'
         . '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>'
         . '</div>'
         . '<h1 style="font-size:22px;font-weight:800;color:#1a1d2e;margin:6px 0 10px;">Link Not Valid</h1>'
         . '<p style="font-size:15px;color:#6b7280;max-width:420px;margin:0 auto;line-height:1.6;">' . htmlspecialchars($msg) . '</p>'
         . '</div></div>';
}

function portalPage(string $title, string $content): void {
    $css = '
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f0f2f5; color: #1a1d2e; min-height: 100vh; }
.ms-header { background: #181b26; padding: 0 28px; display: flex; align-items: center; height: 58px; gap: 16px; }
.ms-logo { font-size: 18px; font-weight: 800; color: #E8751A; letter-spacing: -0.3px; }
.ms-logo-sub { font-size: 11px; font-weight: 500; color: #6b7280; letter-spacing: 0.02em; }
.ms-bar { height: 3px; background: #E8751A; }
.ms-wrap { max-width: none; margin: 0; padding: 28px 28px 80px; }
.card { background: #fff; border-radius: 14px; border: 1px solid #e8eaf0; overflow: hidden; margin-bottom: 16px; box-shadow: 0 1px 4px rgba(0,0,0,0.04); }
.card-body { padding: 22px 24px; }
.soft-icon { width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; }

.cp-head { margin: 4px 2px 18px; }
.cp-hello { font-size: 24px; font-weight: 800; color: #1a1d2e; line-height: 1.15; }
.cp-sub { font-size: 14px; color: #6b7280; margin-top: 6px; line-height: 1.6; }
.cp-summary { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: #9ba3c0; margin: 0 2px 12px; }
.cp-summary strong { color: #E8751A; }

.cp-order-top { display: flex; align-items: center; justify-content: space-between; gap: 12px; }
.cp-order-name { font-size: 18px; font-weight: 800; color: #1a1d2e; line-height: 1.2; min-width: 0; }
.cp-badge { font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; padding: 5px 12px; border-radius: 20px; white-space: nowrap; flex-shrink: 0; }
.cp-order-sub { font-size: 13.5px; color: #6b7280; font-weight: 500; margin: 8px 0 14px; }

.cp-bar { height: 8px; background: #eef0f5; border-radius: 99px; overflow: hidden; }
.cp-bar-fill { height: 100%; border-radius: 99px; transition: width 0.5s ease; }

.cp-rail { display: flex; align-items: center; margin-top: 14px; }
.cp-dot { width: 13px; height: 13px; border-radius: 50%; flex-shrink: 0; background: #dfe3ec; }
.cp-dot-done { background: #16a34a; }
.cp-dot-current { background: var(--cp-accent,#E8751A); box-shadow: 0 0 0 4px rgba(232,117,26,0.15); }
.cp-dot-line { flex: 1; height: 3px; background: #e5e8f0; margin: 0 3px; border-radius: 2px; }
.cp-dot-line.is-filled { background: #86d6a2; }
.cp-rail-labels { display: flex; justify-content: space-between; margin-top: 7px; font-size: 10.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: #a9b0c2; }

.cp-footnote { font-size: 12.5px; color: #9ba3c0; line-height: 1.7; text-align: center; padding: 6px 8px; }
.cp-board-area { position: relative; }
.cp-watermark { position:absolute; left:0; right:0; top:74%; transform:translateY(-50%); display:flex; align-items:center; justify-content:center; z-index:0; margin:0; user-select:none; pointer-events:none; overflow:visible; }
.cp-wm-img { width: min(1000px, 92%); height:auto; opacity: 0.08; }
.cp-wm-mark { font-size: clamp(40px, 9vw, 120px); font-weight: 900; letter-spacing: -3px; color: #E8751A; opacity: 0.07; white-space: nowrap; line-height: 1; }
.cp-wm-sculpt { color: #9ca3af; font-weight: 300; margin-left: 4px; }
.footer-bar { text-align: center; padding: 24px 20px 40px; color: #9ba3c0; font-size: 12px; line-height: 1.8; }
.footer-bar a { color: #E8751A; text-decoration: none; }

/* Pipeline-style board: colored column headers, cards stacked below */
.cp-board-wrap { position:relative; z-index:1; }
.cp-board { display:flex; gap:16px; overflow-x:auto; padding-bottom:14px; align-items:flex-start; scrollbar-width:thin; }
.cp-board::-webkit-scrollbar { height:9px; }
.cp-board::-webkit-scrollbar-thumb { background:#cbd2df; border-radius:99px; }
.cp-board::-webkit-scrollbar-track { background:#e8ebf1; border-radius:99px; }
.cp-board-fade { position:absolute; top:0; right:-4px; bottom:16px; width:56px; pointer-events:none; opacity:0; transition:opacity .2s; background:linear-gradient(to right, rgba(240,242,245,0), #f0f2f5 75%); display:flex; align-items:center; justify-content:flex-end; }
.cp-board-fade span { pointer-events:none; color:#8b93a7; font-size:22px; font-weight:800; margin-right:2px; }
.cp-col { flex:1 0 258px; min-width:258px; }
.cp-col-head { font-size:12px; font-weight:800; text-transform:uppercase; letter-spacing:.03em; padding:10px 14px; border-radius:12px; display:flex; align-items:center; justify-content:space-between; gap:10px; }
.cp-col-head > span:first-child { min-width:0; line-height:1.2; }
.cp-col-count { background:rgba(255,255,255,.7); border-radius:999px; font-size:11.5px; font-weight:800; padding:1px 9px; flex:0 0 auto; }
.cp-col-body { margin-top:12px; }
.cp-col .card { margin-bottom:12px; }
.cp-col .card:last-child { margin-bottom:0; }
.cp-col-empty { font-size:12px; color:#b6bccb; text-align:center; padding:24px 6px; border:1px dashed #e2e6ef; border-radius:12px; }

@media (max-width: 620px) {
  .ms-wrap { padding: 18px 12px 60px; }
  .card-body { padding: 18px 16px; }
  .cp-hello { font-size: 21px; }
  .cp-order-name { font-size: 16px; }
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
