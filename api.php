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

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

switch ($action) {

    // ─── CLIENTS ───────────────────────────────────────

    case 'get_clients':
        $stmt = $pdo->query("SELECT * FROM clients ORDER BY name ASC");
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
        break;

    case 'add_client':
        if (empty($input['name'])) {
            echo json_encode(['success' => false, 'error' => 'Client name required']);
            break;
        }
        try {
            $stmt = $pdo->prepare("INSERT INTO clients (name) VALUES (?)");
            $stmt->execute([trim($input['name'])]);
            echo json_encode(['success' => true, 'id' => $pdo->lastInsertId(), 'name' => trim($input['name'])]);
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
        $stmt = $pdo->prepare("DELETE FROM clients WHERE id = ?");
        $stmt->execute([$input['id']]);
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
                WHERE w.client_id = ?
                ORDER BY w.created_at DESC
            ");
            $stmt->execute([$clientId]);
        } else {
            $stmt = $pdo->query("
                SELECT w.*, c.name as client_name
                FROM workbooks w
                JOIN clients c ON w.client_id = c.id
                ORDER BY w.updated_at DESC
            ");
        }
        $workbooks = $stmt->fetchAll();
        // Decode detail_json for each workbook
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
        $stmt = $pdo->prepare("
            INSERT INTO workbooks (client_id, product_name, description, flow_step, detail_json)
            VALUES (?, ?, ?, 0, ?)
        ");
        $detail = $input['detail'] ?? [];
        $stmt->execute([
            $input['client_id'],
            trim($input['product_name']),
            trim($input['description'] ?? ''),
            json_encode($detail)
        ]);
        echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
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
        $stmt = $pdo->prepare("DELETE FROM workbooks WHERE id = ?");
        $stmt->execute([$input['id']]);
        echo json_encode(['success' => true]);
        break;

    // ─── RECENT WORKBOOKS (for landing page) ──────────

    case 'get_recent':
        $limit = $_GET['limit'] ?? 5;
        $stmt = $pdo->prepare("
            SELECT w.*, c.name as client_name
            FROM workbooks w
            JOIN clients c ON w.client_id = c.id
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

    // ─── SAVE ALL (bulk save from JS) ─────────────────

    case 'save_workbook_detail':
        if (empty($input['id'])) {
            echo json_encode(['success' => false, 'error' => 'Workbook ID required']);
            break;
        }
        $stmt = $pdo->prepare("UPDATE workbooks SET detail_json = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([json_encode($input['detail']), $input['id']]);
        echo json_encode(['success' => true]);
        break;

    // ─── FULL DATA EXPORT (for LocalStorage sync) ─────

    case 'get_all_data':
        $clients = $pdo->query("SELECT * FROM clients ORDER BY name ASC")->fetchAll();
        $workbooks = $pdo->query("
            SELECT w.*, c.name as client_name
            FROM workbooks w
            JOIN clients c ON w.client_id = c.id
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

    default:
        echo json_encode(['error' => 'Unknown action', 'available' => [
            'get_clients', 'add_client', 'delete_client',
            'get_workbooks', 'get_workbook', 'add_workbook', 'update_workbook',
            'update_flow', 'delete_workbook', 'save_workbook_detail',
            'get_recent', 'get_all_data'
        ]]);
}
