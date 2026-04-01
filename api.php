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
    role ENUM('admin','user') NOT NULL DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

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
        $createRevision = !empty($input['create_revision']); // only true on nav saves

        // Save current version as a revision BEFORE overwriting (only when explicitly requested)
        if ($createRevision) {
            $stmt = $pdo->prepare("SELECT detail_json FROM workbooks WHERE id = ?");
            $stmt->execute([$input['id']]);
            $current = $stmt->fetch();
            $currentJson = $current['detail_json'] ?? '';
            $incomingJson = json_encode($input['detail']);

            // Only create a revision if the data actually changed
            if ($currentJson && $currentJson !== '[]' && $currentJson !== 'null' && $currentJson !== $incomingJson) {
                // Also skip if last revision already has this exact content (dedup)
                $lastStmt = $pdo->prepare("SELECT detail_json FROM workbook_revisions WHERE workbook_id = ? ORDER BY created_at DESC LIMIT 1");
                $lastStmt->execute([$input['id']]);
                $lastRev = $lastStmt->fetch();
                if (!$lastRev || $lastRev['detail_json'] !== $currentJson) {
                    $stmt = $pdo->prepare("INSERT INTO workbook_revisions (workbook_id, detail_json, changed_by) VALUES (?, ?, ?)");
                    $stmt->execute([$input['id'], $currentJson, $changedBy]);
                }
            }
        }

        // Now save the new version (also update product_name if provided)
        $newProductName = !empty($input['detail']['product']) ? trim($input['detail']['product']) : null;
        if ($newProductName) {
            $stmt = $pdo->prepare("UPDATE workbooks SET detail_json = ?, product_name = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([json_encode($input['detail']), $newProductName, $input['id']]);
        } else {
            $stmt = $pdo->prepare("UPDATE workbooks SET detail_json = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([json_encode($input['detail']), $input['id']]);
        }

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

    case 'get_users':
        if ($sessionUser['role'] !== 'admin') {
            echo json_encode(['success' => false, 'error' => 'Admin only']);
            break;
        }
        $stmt = $pdo->query("SELECT id, username, display_name, role, created_at, last_login FROM users ORDER BY created_at ASC");
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
            $hash = password_hash($input['password'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, display_name, role) VALUES (?, ?, ?, ?)");
            $stmt->execute([
                trim($input['username']),
                $hash,
                trim($input['display_name'] ?? $input['username']),
                $input['role'] === 'admin' ? 'admin' : 'user'
            ]);
            echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => $e->getCode() == 23000 ? 'Username already exists' : $e->getMessage()]);
        }
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
        $stmt = $pdo->prepare("INSERT INTO workbooks (client_id, product_name, description, flow_step, detail_json) VALUES (?, ?, ?, 0, ?)");
        $stmt->execute([$targetClientId, $newName, $src['description'], json_encode($detail)]);
        echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
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
            'upload_image', 'delete_image',
            'get_users', 'add_user', 'delete_user', 'change_password',
            'duplicate_workbook'
        ]]);
}
