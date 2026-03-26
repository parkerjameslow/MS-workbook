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

// Auto-create revisions table if not exists
$pdo->exec("CREATE TABLE IF NOT EXISTS workbook_revisions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    workbook_id INT NOT NULL,
    detail_json LONGTEXT,
    changed_by VARCHAR(255) DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_workbook_date (workbook_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

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

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

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
        $deletedBy = $input['deleted_by'] ?? '';
        // Soft-delete client
        $stmt = $pdo->prepare("UPDATE clients SET deleted_at = NOW(), deleted_by = ? WHERE id = ? AND deleted_at IS NULL");
        $stmt->execute([$deletedBy, $input['id']]);
        // Soft-delete all its workbooks
        $stmt = $pdo->prepare("UPDATE workbooks SET deleted_at = NOW(), deleted_by = ? WHERE client_id = ? AND deleted_at IS NULL");
        $stmt->execute([$deletedBy, $input['id']]);
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
        $changedBy = $input['changed_by'] ?? '';

        // Save current version as a revision BEFORE overwriting
        $stmt = $pdo->prepare("SELECT detail_json FROM workbooks WHERE id = ?");
        $stmt->execute([$input['id']]);
        $current = $stmt->fetch();
        if ($current && $current['detail_json'] && $current['detail_json'] !== '[]' && $current['detail_json'] !== 'null') {
            $stmt = $pdo->prepare("INSERT INTO workbook_revisions (workbook_id, detail_json, changed_by) VALUES (?, ?, ?)");
            $stmt->execute([$input['id'], $current['detail_json'], $changedBy]);
        }

        // Now save the new version
        $stmt = $pdo->prepare("UPDATE workbooks SET detail_json = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([json_encode($input['detail']), $input['id']]);

        // Purge revisions older than 30 days
        $pdo->exec("DELETE FROM workbook_revisions WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)");

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
            SELECT id, workbook_id, changed_by, created_at
            FROM workbook_revisions
            WHERE workbook_id = ?
            ORDER BY created_at DESC
            LIMIT 50
        ");
        $stmt->execute([$wbId]);
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
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

    default:
        echo json_encode(['error' => 'Unknown action', 'available' => [
            'get_clients', 'add_client', 'delete_client',
            'get_workbooks', 'get_workbook', 'add_workbook', 'update_workbook',
            'update_flow', 'delete_workbook', 'save_workbook_detail',
            'get_recent', 'get_all_data',
            'get_revisions', 'get_revision_detail', 'restore_revision',
            'get_archived', 'restore_workbook', 'restore_client',
            'permanent_delete_workbook', 'permanent_delete_client'
        ]]);
}
