<?php
require_once __DIR__ . '/../includes/db.php';

if (!empty($_SESSION['user_id'])) {
    redirect('dashboard.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['fullname'] = $user['fullname'];
        $_SESSION['role'] = $user['role'];
        redirect('dashboard.php');
    }

    $error = 'Invalid username or password.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Pharmacy Inventory</title>
    <link rel="stylesheet" href="<?= e(app_url('css/style.css')) ?>">
</head>
<body class="login-page">
    <section class="login-card">
        <div class="brand login-brand">
            <span class="brand-mark">Rx</span>
            <div>
                <strong>PharmaStock</strong>
                <small>Secure staff access</small>
            </div>
        </div>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= e($error) ?></div>
        <?php endif; ?>
        <form method="POST" class="form validate-form">
            <label>Username
                <input type="text" name="username" required autocomplete="username" placeholder="admin">
            </label>
            <label>Password
                <input type="password" name="password" required autocomplete="current-password" placeholder="admin123">
            </label>
            <button class="btn btn-primary full" type="submit">Login</button>
        </form>
        <p class="hint">Demo accounts: admin/admin123 or staff/admin123</p>
    </section>
<script src="<?= e(app_url('js/app.js')) ?>"></script>
</body>
</html>
