<?php
// Temporary diagnostic — checks app_state for orders/shipments in DB
// DELETE THIS FILE after use
if (($_GET['k'] ?? '') !== 'ms2026check') { http_response_code(403); die('Forbidden'); }

$pdo = new PDO('mysql:host=localhost;dbname=markewq4_workbook;charset=utf8mb4',
               'markewq4_workbook', 'MarketFun123',
               [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

header('Content-Type: application/json');

$results = [];

// Check app_state table exists and has data
try {
    $rows = $pdo->query("SELECT key_name, LENGTH(value_json) as bytes, updated_at FROM app_state")->fetchAll(PDO::FETCH_ASSOC);
    $results['app_state_rows'] = $rows;
} catch (Exception $e) {
    $results['app_state_error'] = $e->getMessage();
}

// Decode and count orders/shipments
foreach (['ms_orders', 'ms_shipments'] as $key) {
    try {
        $row = $pdo->prepare("SELECT value_json FROM app_state WHERE key_name = ?");
        $row->execute([$key]);
        $val = $row->fetchColumn();
        if ($val) {
            $decoded = json_decode($val, true);
            $results[$key . '_count'] = count($decoded['data'] ?? []);
            $results[$key . '_nextId'] = $decoded['nextId'] ?? null;
            // Show first entry as sample
            $data = $decoded['data'] ?? [];
            if (!empty($data)) {
                $first = array_values($data)[0];
                $results[$key . '_sample'] = [
                    'name'       => $first['name'] ?? '—',
                    'clientName' => $first['clientName'] ?? ($first['containerType'] ?? '—'),
                ];
            }
        } else {
            $results[$key . '_count'] = 0;
            $results[$key . '_status'] = 'not in DB yet';
        }
    } catch (Exception $e) {
        $results[$key . '_error'] = $e->getMessage();
    }
}

// Also check portal_tokens for context
$results['portal_tokens_total']             = (int)$pdo->query("SELECT COUNT(*) FROM portal_tokens")->fetchColumn();
$results['portal_tokens_changes_requested'] = (int)$pdo->query("SELECT COUNT(*) FROM portal_tokens WHERE status='changes_requested'")->fetchColumn();

echo json_encode($results, JSON_PRETTY_PRINT);
