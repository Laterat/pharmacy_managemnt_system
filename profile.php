<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

requireLogin();

$errors = [];
$message = '';
$username = $_SESSION['username'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid CSRF token.';
    } else {
        $newUsername = trim($_POST['username'] ?? '');
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if ($newUsername === '') {
            $errors[] = 'Username is required.';
        }

        $changingPassword = $newPassword !== '' || $confirmPassword !== '';
        if ($changingPassword && (strlen($newPassword) < 6 || $newPassword !== $confirmPassword)) {
            $errors[] = 'New password must be at least 6 characters and match confirmation.';
        }

        $stmt = mysqli_prepare($conn, 'SELECT password FROM users WHERE id = ? LIMIT 1');
        mysqli_stmt_bind_param($stmt, 'i', $_SESSION['user_id']);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if (!$user || !password_verify($currentPassword, $user['password'])) {
            $errors[] = 'Current password is incorrect.';
        }

        if (!$errors) {
            if ($changingPassword) {
                $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = mysqli_prepare($conn, 'UPDATE users SET username = ?, password = ? WHERE id = ?');
                mysqli_stmt_bind_param($stmt, 'ssi', $newUsername, $passwordHash, $_SESSION['user_id']);
            } else {
                $stmt = mysqli_prepare($conn, 'UPDATE users SET username = ? WHERE id = ?');
                mysqli_stmt_bind_param($stmt, 'si', $newUsername, $_SESSION['user_id']);
            }

            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['username'] = $newUsername;
                $username = $newUsername;
                $message = 'Account updated.';
            } else {
                $errors[] = 'Unable to update account. Username may already be taken.';
            }
            mysqli_stmt_close($stmt);
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>
<section class="page-header">
    <div>
        <h1>Account</h1>
        <p>Update your username or password.</p>
    </div>
</section>

<?php if ($message !== ''): ?><div class="message success-message"><?php echo h($message); ?></div><?php endif; ?>
<?php foreach ($errors as $error): ?><div class="message error"><?php echo h($error); ?></div><?php endforeach; ?>

<form class="panel" method="post" data-validate>
    <input type="hidden" name="csrf_token" value="<?php echo h(generate_csrf_token()); ?>">
    <div class="form-grid">
        <div class="form-group">
            <label for="username">Username</label>
            <input id="username" name="username" required value="<?php echo h($username); ?>">
        </div>
        <div class="form-group">
            <label for="current_password">Current Password</label>
            <input id="current_password" type="password" name="current_password" required>
        </div>
        <div class="form-group">
            <label for="new_password">New Password</label>
            <input id="new_password" type="password" name="new_password">
        </div>
        <div class="form-group">
            <label for="confirm_password">Confirm New Password</label>
            <input id="confirm_password" type="password" name="confirm_password">
        </div>
    </div>
    <button class="btn primary" type="submit">Update Account</button>
</form>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
