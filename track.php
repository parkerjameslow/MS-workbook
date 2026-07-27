<?php
// Market Sculpt — Client Order Tracking (live, read-only)
// Public but token-gated (?t=<64 hex>). Shows ONLY the progress of an order:
// Ordered → In Production → Shipped → In Transit → Arriving → Delivered.
// No pricing, no tracking numbers. Reads LIVE order + shipment state from
// app_state on every visit, so the client always sees the current stage.

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

// Same table api.php's mint_order_tracking creates — auto-create so a direct
// hit never fatals if the operator hasn't minted a link through the app yet.
$pdo->exec("CREATE TABLE IF NOT EXISTS tracking_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    token CHAR(64) NOT NULL,
    order_id VARCHAR(32) NOT NULL,
    client_name VARCHAR(255) DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_trk_token (token),
    UNIQUE KEY uq_trk_order (order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// ── Validate token ──────────────────────────────────────────────────────────
$token = trim($_GET['t'] ?? '');
if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
    trackPage('Invalid Link', errorContent('This tracking link is not valid. Please contact your Market Sculpt representative.'));
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM tracking_tokens WHERE token = ?");
$stmt->execute([$token]);
$row = $stmt->fetch();
if (!$row) {
    trackPage('Link Not Found', errorContent('This tracking link has expired or no longer exists. Please contact your Market Sculpt representative for assistance.'));
    exit;
}

$orderId    = (string)$row['order_id'];
$clientName = (string)$row['client_name'];

// ── Load LIVE order + shipment state ─────────────────────────────────────────
$orders    = trackLoadState($pdo, 'ms_orders');
$shipments = trackLoadState($pdo, 'ms_shipments');

$order = $orders[$orderId] ?? null;
if (!$order) {
    // Token is valid but the order is gone (deleted) or not yet saved.
    trackPage('Order Tracking', pendingContent('We\'re getting your order ready. Tracking details will appear here shortly.'));
    exit;
}

if ($clientName === '') $clientName = (string)($order['clientName'] ?? '');
$orderName = (string)($order['name'] ?? 'Your Order');

// Shipments that carry any part of this order
$carrying = [];
foreach ($shipments as $s) {
    if (is_array($s) && trackShipmentCarriesOrder($s, $order, $orderId)) $carrying[] = $s;
}

// Resolve the order's current stage (0..5) + an ETA / delivered date
$stage = trackComputeStage($order, $carrying);

trackPage($orderName, trackerContent($orderName, $clientName, $stage));
exit;

// ════════════════════════════════════════════════════════════════════════════
// DATA HELPERS
// ════════════════════════════════════════════════════════════════════════════

// Read an app_state blob and return its inner map ({id: obj}). Handles both the
// canonical {data:{...}, nextId} envelope and a bare {...} map.
function trackLoadState(PDO $pdo, string $key): array {
    $st = $pdo->prepare("SELECT value_json FROM app_state WHERE key_name = ?");
    $st->execute([$key]);
    $r = $st->fetch();
    if (!$r || $r['value_json'] === null) return [];
    $d = json_decode($r['value_json'], true);
    if (!is_array($d)) return [];
    if (isset($d['data']) && is_array($d['data'])) return $d['data'];
    return $d;
}

// Does shipment $s carry any workbook belonging to this order?
function trackShipmentCarriesOrder(array $s, array $order, string $orderId): bool {
    // Build the set of this order's workbook refs "clientName|workbookId"
    $orderRefs = [];
    foreach (($order['entries'] ?? []) as $e) {
        if (!is_array($e)) continue;
        $orderRefs[($e['clientName'] ?? '') . '|' . ($e['workbookId'] ?? '')] = true;
    }
    $allEntries = array_merge($s['entries'] ?? [], $s['sampleEntries'] ?? []);
    foreach ($allEntries as $e) {
        if (!is_array($e)) continue;
        // Direct order reference
        if (isset($e['orderId']) && (string)$e['orderId'] === $orderId) return true;
        // Direct workbook reference
        if (isset($e['clientName'], $e['workbookId'])
            && isset($orderRefs[$e['clientName'] . '|' . $e['workbookId']])) return true;
        // sampleKey shape: "clientName|workbookId"
        if (isset($e['sampleKey']) && isset($orderRefs[$e['sampleKey']])) return true;
    }
    return false;
}

// Map a shipment's operator status to a tracker stage index (2..5).
function trackShipStageIdx(array $s): int {
    switch ($s['status'] ?? 'planning') {
        case 'in_transit':      return 3; // In Transit
        case 'waiting_arrival': return 4; // Arriving
        case 'delivered':
        case 'received':        return 5; // Delivered
        case 'planning':
        case 'booked':
        default:                return 2; // Shipped
    }
}

// Resolve the order's overall stage. Returns:
//   ['idx' => int 0..5, 'eta' => string|'', 'deliveredOn' => string|'']
// When an order is split across shipments we take the LEAST-progressed one so
// we never tell the client "Delivered" while part is still in transit.
function trackComputeStage(array $order, array $carrying): array {
    if (empty($carrying)) {
        // Not on any shipment yet: notified = In Production, else Ordered.
        $idx = !empty($order['notifiedAt']) ? 1 : 0;
        return ['idx' => $idx, 'eta' => '', 'deliveredOn' => ''];
    }

    $minIdx = 6;
    $etaCandidates  = [];   // [tsOrNull, rawString]
    $deliveredDates = [];
    foreach ($carrying as $s) {
        $si = trackShipStageIdx($s);
        if ($si < $minIdx) $minIdx = $si;

        if ($si >= 5) {
            $d = trim((string)($s['deliveredOn'] ?? $s['receivedAt'] ?? ''));
            if ($d !== '') $deliveredDates[] = $d;
        } else {
            $e = trim((string)($s['eta'] ?? ''));
            if ($e === '' && isset($s['tracking']['eta'])) $e = trim((string)$s['tracking']['eta']);
            if ($e !== '') {
                $ts = strtotime($e);
                $etaCandidates[] = [$ts !== false ? $ts : null, $e];
            }
        }
    }
    if ($minIdx === 6) $minIdx = 2;

    // Soonest parseable ETA wins; otherwise fall back to the first raw string.
    $eta = '';
    if ($etaCandidates) {
        usort($etaCandidates, function ($a, $b) {
            if ($a[0] === null) return 1;
            if ($b[0] === null) return -1;
            return $a[0] <=> $b[0];
        });
        $best = $etaCandidates[0];
        $eta  = $best[0] !== null ? date('M j, Y', $best[0]) : $best[1];
    }

    $deliveredOn = '';
    if ($minIdx >= 5 && $deliveredDates) {
        $d  = $deliveredDates[0];
        $ts = strtotime($d);
        $deliveredOn = $ts !== false ? date('M j, Y', $ts) : $d;
    }

    return ['idx' => $minIdx, 'eta' => $eta, 'deliveredOn' => $deliveredOn];
}

// ════════════════════════════════════════════════════════════════════════════
// PAGE RENDERERS
// ════════════════════════════════════════════════════════════════════════════

function trackerContent(string $orderName, string $clientName, array $stage): string {
    $idx = (int)$stage['idx'];

    $steps = [
        ['Ordered',       'Your order has been placed and confirmed.'],
        ['In Production', 'Your items are being manufactured.'],
        ['Shipped',       'Your order has left the facility.'],
        ['In Transit',    'Your shipment is on its way.'],
        ['Arriving',      'Your shipment is nearing its destination.'],
        ['Delivered',     'Your order has arrived.'],
    ];

    // Headline reflects the current step, enriched with ETA / delivered date.
    $curLabel = $steps[$idx][0];
    $sub      = '';
    if ($idx >= 5 && $stage['deliveredOn'] !== '') {
        $sub = 'Delivered on ' . htmlspecialchars($stage['deliveredOn']);
    } elseif ($idx >= 2 && $idx < 5 && $stage['eta'] !== '') {
        $sub = 'Estimated arrival · ' . htmlspecialchars($stage['eta']);
    } else {
        $sub = htmlspecialchars($steps[$idx][1]);
    }

    $pct   = $idx >= 5 ? 100 : round(($idx / 5) * 100);
    $accent = $idx >= 5 ? '#16a34a' : '#E8751A';

    // ── Header card ──────────────────────────────────────────────────────────
    $badge = '<span class="stage-badge" style="background:' . ($idx >= 5 ? '#dcfce7' : '#fff7ed')
           . ';color:' . ($idx >= 5 ? '#166534' : '#9a3412') . ';">' . htmlspecialchars($curLabel) . '</span>';

    $head = '<div class="card">'
          . '<div class="card-body">'
          . '<div class="track-meta">'
          . ($clientName !== '' ? '<div class="track-client">' . htmlspecialchars($clientName) . '</div>' : '')
          . '<h1 class="track-title">' . htmlspecialchars($orderName) . '</h1>'
          . '</div>'
          . '<div class="track-status-line">' . $badge . '<span class="track-sub">' . $sub . '</span></div>'
          . '<div class="track-bar"><div class="track-bar-fill" style="width:' . $pct . '%;background:' . $accent . ';"></div></div>'
          . '</div></div>';

    // ── Vertical stepper ─────────────────────────────────────────────────────
    $iconDone = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>';

    $stepsHtml = '';
    foreach ($steps as $i => [$label, $desc]) {
        if ($i < $idx)      $state = 'done';
        elseif ($i === $idx) $state = 'current';
        else                 $state = 'todo';

        $isLast   = $i === count($steps) - 1;
        $lineHtml = $isLast ? '' : '<div class="step-line ' . ($i < $idx ? 'is-filled' : '') . '"></div>';

        $dotInner = $state === 'done' ? $iconDone : '<span class="step-dot-inner"></span>';

        // Current step shows its ETA / delivered sub-line; others show the copy.
        $stepSub = htmlspecialchars($desc);
        if ($i === $idx) {
            if ($idx >= 5 && $stage['deliveredOn'] !== '') $stepSub = 'Delivered on ' . htmlspecialchars($stage['deliveredOn']);
            elseif ($idx >= 2 && $idx < 5 && $stage['eta'] !== '') $stepSub = 'Estimated arrival · ' . htmlspecialchars($stage['eta']);
        }

        $stepsHtml .= '<div class="step step-' . $state . '">'
                    . '<div class="step-rail"><div class="step-dot">' . $dotInner . '</div>' . $lineHtml . '</div>'
                    . '<div class="step-body">'
                    . '<div class="step-label">' . htmlspecialchars($label) . ($state === 'current' ? '<span class="step-now">Now</span>' : '') . '</div>'
                    . '<div class="step-desc">' . $stepSub . '</div>'
                    . '</div></div>';
    }

    $stepper = '<div class="card"><div class="card-body">'
             . '<div class="steps-title">Order Progress</div>'
             . '<div class="steps">' . $stepsHtml . '</div>'
             . '</div></div>';

    $note = '<p class="track-footnote">This page updates automatically as your order moves through each stage. '
          . 'Questions about your order? Contact your Market Sculpt representative.</p>';

    return $head . $stepper . $note;
}

function pendingContent(string $msg): string {
    return '<div class="card"><div class="card-body" style="text-align:center;padding:60px 24px;">'
         . '<div class="soft-icon" style="background:#fff7ed;">'
         . '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#E8751A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>'
         . '</div>'
         . '<h1 style="font-size:22px;font-weight:800;color:#1a1d2e;margin:6px 0 10px;">Order Tracking</h1>'
         . '<p style="font-size:15px;color:#6b7280;max-width:420px;margin:0 auto;line-height:1.6;">' . htmlspecialchars($msg) . '</p>'
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

function trackPage(string $title, string $content): void {
    $css = '
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f0f2f5; color: #1a1d2e; min-height: 100vh; }
.ms-header { background: #181b26; padding: 0 28px; display: flex; align-items: center; height: 58px; gap: 16px; }
.ms-logo { font-size: 18px; font-weight: 800; color: #E8751A; border-left: 3px solid #E8751A; padding-left: 12px; letter-spacing: -0.3px; }
.ms-logo-sub { font-size: 11px; font-weight: 500; color: #6b7280; letter-spacing: 0.02em; }
.ms-bar { height: 3px; background: #E8751A; }
.ms-wrap { max-width: 640px; margin: 0 auto; padding: 32px 20px 80px; }
.card { background: #fff; border-radius: 14px; border: 1px solid #e8eaf0; overflow: hidden; margin-bottom: 18px; box-shadow: 0 1px 4px rgba(0,0,0,0.04); }
.card-body { padding: 26px 28px; }
.soft-icon { width: 64px; height: 64px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 18px; }

.track-meta { margin-bottom: 18px; }
.track-client { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #9ba3c0; margin-bottom: 6px; }
.track-title { font-size: 26px; font-weight: 800; color: #1a1d2e; line-height: 1.15; }
.track-status-line { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; margin-bottom: 18px; }
.stage-badge { font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.06em; padding: 6px 14px; border-radius: 20px; white-space: nowrap; }
.track-sub { font-size: 14px; color: #6b7280; font-weight: 500; }
.track-bar { height: 8px; background: #eef0f5; border-radius: 99px; overflow: hidden; }
.track-bar-fill { height: 100%; border-radius: 99px; transition: width 0.5s ease; }

.steps-title { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.07em; color: #9ba3c0; margin-bottom: 20px; }
.steps { position: relative; }
.step { display: flex; gap: 16px; }
.step-rail { position: relative; display: flex; flex-direction: column; align-items: center; width: 30px; flex-shrink: 0; }
.step-dot { width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; z-index: 1; transition: all 0.2s; }
.step-dot-inner { width: 9px; height: 9px; border-radius: 50%; background: #c7ccda; }
.step-line { width: 3px; flex: 1; min-height: 26px; background: #e5e8f0; margin: 2px 0; }
.step-line.is-filled { background: #f5b783; }
.step-body { padding-bottom: 26px; padding-top: 3px; }
.step:last-child .step-body { padding-bottom: 0; }
.step-label { font-size: 16px; font-weight: 700; color: #1a1d2e; display: flex; align-items: center; gap: 10px; }
.step-desc { font-size: 13.5px; color: #8b93a7; margin-top: 3px; line-height: 1.5; }
.step-now { font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.06em; color: #E8751A; background: #fff2e6; padding: 2px 8px; border-radius: 20px; }

/* Done — filled green */
.step-done .step-dot { background: #dcfce7; color: #16a34a; }
/* Current — solid orange, pulsing ring */
.step-current .step-dot { background: #E8751A; box-shadow: 0 0 0 5px rgba(232,117,26,0.16); animation: trackPulse 1.8s ease-in-out infinite; }
.step-current .step-dot-inner { background: #fff; }
.step-current .step-label { color: #1a1d2e; }
.step-current .step-desc { color: #9a3412; font-weight: 600; }
/* Todo — hollow grey */
.step-todo .step-dot { background: #eef0f5; border: 2px solid #dee1ea; }
.step-todo .step-label { color: #a9b0c2; }

@keyframes trackPulse { 0%,100% { box-shadow: 0 0 0 5px rgba(232,117,26,0.16); } 50% { box-shadow: 0 0 0 9px rgba(232,117,26,0.05); } }

.track-footnote { font-size: 12.5px; color: #9ba3c0; line-height: 1.7; text-align: center; padding: 4px 8px; }
.footer-bar { text-align: center; padding: 24px 20px 40px; color: #9ba3c0; font-size: 12px; line-height: 1.8; }
.footer-bar a { color: #E8751A; text-decoration: none; }

@media (max-width: 620px) {
  .ms-wrap { padding: 18px 12px 60px; }
  .card-body { padding: 20px 18px; }
  .track-title { font-size: 22px; }
  .step-label { font-size: 15px; }
}';

    echo '<!DOCTYPE html><html lang="en"><head>'
       . '<meta charset="UTF-8">'
       . '<meta name="viewport" content="width=device-width,initial-scale=1">'
       . '<link rel="icon" type="image/svg+xml" href="favicon.svg">'
       . '<title>' . htmlspecialchars($title) . ' — Order Tracking</title>'
       . '<style>' . $css . '</style>'
       . '</head><body>'
       . '<header class="ms-header">'
       . '<div class="ms-logo">Market Sculpt</div>'
       . '<div class="ms-logo-sub">Order Tracking</div>'
       . '</header>'
       . '<div class="ms-bar"></div>'
       . '<div class="ms-wrap">' . $content . '</div>'
       . '<footer class="footer-bar">Market Sculpt LLC &nbsp;·&nbsp; <a href="https://marketsculpt.com">marketsculpt.com</a><br>'
       . 'Questions? <a href="mailto:parker@marketsculpt.com">parker@marketsculpt.com</a></footer>'
       . '</body></html>';
}
