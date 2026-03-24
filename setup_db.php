<?php
// Database Setup Script — run once to create tables
// Visit: https://wb.marketsculpt.com/setup_db.php?key=ms-setup-2026

if ($_GET['key'] !== 'ms-setup-2026') {
    die('Unauthorized');
}

$host = 'localhost';
$db   = 'markewq4_workbook';
$user = 'markewq4_workbook';
$pass = 'MarketFun123';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create clients table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS clients (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL UNIQUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // Create workbooks table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS workbooks (
            id INT AUTO_INCREMENT PRIMARY KEY,
            client_id INT NOT NULL,
            product_name VARCHAR(255) NOT NULL,
            description TEXT,
            flow_step INT DEFAULT 0,
            detail_json LONGTEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
            INDEX idx_client (client_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    echo "<h2>Database tables created successfully!</h2>";
    echo "<p>Tables: clients, workbooks</p>";

    // Verify
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "<p>Existing tables: " . implode(', ', $tables) . "</p>";

    // Insert default clients
    $defaultClients = ['BAM', 'Bloom', 'Candy Pan', 'Fresh Her', 'Kids United', 'Nut Garden', 'Salt', 'Tweedle Dee'];
    $stmt = $pdo->prepare("INSERT IGNORE INTO clients (name) VALUES (?)");
    foreach ($defaultClients as $name) {
        $stmt->execute([$name]);
    }
    echo "<p>Default clients inserted.</p>";

    echo "<hr><p><strong>Setup complete! Delete this file from the server for security.</strong></p>";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
