<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if already logged in
if (!empty($_SESSION['user_id'])) {
    header('Location: index.php');
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
    die('Database connection failed.');
}

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

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username && $password) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $userRow = $stmt->fetch();

        if ($userRow && password_verify($password, $userRow['password_hash'])) {
            $_SESSION['user_id']      = $userRow['id'];
            $_SESSION['username']     = $userRow['username'];
            $_SESSION['display_name'] = $userRow['display_name'] ?: $userRow['username'];
            $_SESSION['role']         = $userRow['role'];

            // Update last login
            $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$userRow['id']]);

            header('Location: index.php?new_login=1');
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    } else {
        $error = 'Please enter your username and password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="icon" type="image/svg+xml" href="favicon.svg">
  <title>Market Sculpt — Log In</title>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      background: #0d0f14;
      color: #f0f1f5;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 24px;
    }

    .login-card {
      background: #181b26;
      border: 1px solid #3a3f5c;
      border-radius: 14px;
      padding: 48px 40px 40px;
      width: 100%;
      max-width: 400px;
      box-shadow: 0 8px 40px rgba(0, 0, 0, 0.6);
    }

    .brand {
      text-align: center;
      margin-bottom: 36px;
    }

    .brand-name {
      font-size: 28px;
      font-weight: 800;
      color: #E8751A;
      letter-spacing: -0.5px;
      border-left: 3px solid #E8751A;
      padding-left: 12px;
      display: inline-block;
      text-align: left;
    }

    .brand-sub {
      font-size: 13px;
      color: #9ba3c0;
      margin-top: 6px;
    }

    .form-group {
      margin-bottom: 18px;
    }

    label {
      display: block;
      font-size: 12px;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.06em;
      color: #9ba3c0;
      margin-bottom: 6px;
    }

    input[type="text"],
    input[type="password"] {
      width: 100%;
      background: #222638;
      border: 1px solid #3a3f5c;
      border-radius: 8px;
      color: #f0f1f5;
      font-size: 15px;
      padding: 11px 14px;
      outline: none;
      font-family: inherit;
      transition: border-color 0.15s;
    }

    input[type="text"]:focus,
    input[type="password"]:focus {
      border-color: #6b93ff;
      box-shadow: 0 0 0 3px rgba(107, 147, 255, 0.12);
    }

    .btn-login {
      width: 100%;
      background: #E8751A;
      color: #fff;
      border: none;
      border-radius: 8px;
      font-size: 15px;
      font-weight: 700;
      padding: 12px;
      cursor: pointer;
      font-family: inherit;
      margin-top: 8px;
      transition: background 0.15s, transform 0.1s;
    }

    .btn-login:hover {
      background: #d4661a;
    }

    .btn-login:active {
      transform: scale(0.98);
    }

    .error-msg {
      background: rgba(251, 113, 133, 0.12);
      border: 1px solid rgba(251, 113, 133, 0.35);
      color: #fb7185;
      border-radius: 8px;
      padding: 10px 14px;
      font-size: 13px;
      margin-bottom: 18px;
    }
  </style>
</head>
<body>
  <div class="login-card">
    <div class="brand">
      <div class="brand-name">Market Sculpt</div>
      <div class="brand-sub">Product Workbook</div>
    </div>

    <?php if ($error): ?>
      <div class="error-msg"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" autocomplete="off">
      <div class="form-group">
        <label for="username">Username</label>
        <input id="username" type="text" name="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" autofocus autocomplete="username" />
      </div>
      <div class="form-group">
        <label for="password">Password</label>
        <input id="password" type="password" name="password" autocomplete="current-password" />
      </div>
      <button type="submit" class="btn-login">Log In</button>
    </form>
  </div>
</body>
</html>
