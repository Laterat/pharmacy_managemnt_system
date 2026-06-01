<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/modules/user_auth.php';

requireAdmin();

$errors = [];
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid CSRF token.';
    } else {
        $action = $_POST['action'] ?? '';
        $id = (int) ($_POST['id'] ?? 0);
        if ($action === 'create') {
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            $confirm = $_POST['confirm_password'] ?? '';
            $role = $_POST['role'] === 'admin' ? 'admin' : 'pharmacist';
            if ($username === '' || strlen($password) < 6 || $password !== $confirm) {
                $errors[] = 'Enter a username and matching password of at least 6 characters.';
            } elseif (create_user($conn, $username, $password, $role)) {
                $message = 'User created.';
            } else {
                $errors[] = 'Unable to create user. Username may already exist.';
            }
        } elseif ($id !== (int) $_SESSION['user_id']) {
            if ($action === 'disable') {
                $message = set_user_status($conn, $id, 0) ? 'User disabled.' : 'Unable to update user.';
            } elseif ($action === 'enable') {
                $message = set_user_status($conn, $id, 1) ? 'User enabled.' : 'Unable to update user.';
            } elseif ($action === 'delete') {
                $message = delete_user($conn, $id) ? 'User deleted.' : 'Unable to delete user.';
            }
        } else {
            $errors[] = 'You cannot modify your own account from this action.';
        }
    }
}

$users = get_all_users($conn);
require_once __DIR__ . '/includes/header.php';
?>
<section class="page-header"><h1>Users</h1></section>
<?php if ($message !== ''): ?><div class="message success-message"><?php echo h($message); ?></div><?php endif; ?>
<?php foreach ($errors as $error): ?><div class="message error"><?php echo h($error); ?></div><?php endforeach; ?>

<form class="panel" method="post" data-validate>
    <input type="hidden" name="csrf_token" value="<?php echo h(generate_csrf_token()); ?>">
    <input type="hidden" name="action" value="create">
    <h2>Add User</h2>
    <div class="form-grid">
        <div class="form-group"><label>Username</label><input name="username" required></div>
        <div class="form-group"><label>Password</label><input type="password" name="password" required></div>
        <div class="form-group"><label>Confirm Password</label><input type="password" name="confirm_password" required></div>
        <div class="form-group"><label>Role</label><select name="role"><option value="pharmacist">Pharmacist</option><option value="admin">Admin</option></select></div>
    </div>
    <button class="btn primary" type="submit">Create User</button>
</form>

<div class="table-wrap">
    <table>
        <thead><tr><th>Username</th><th>Role</th><th>Status</th><th>Created</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($users as $user): ?>
            <tr>
                <td><?php echo h($user['username']); ?></td><td><?php echo h($user['role']); ?></td><td><?php echo (int) $user['is_active'] === 1 ? 'Enabled' : 'Disabled'; ?></td><td><?php echo h($user['created_at']); ?></td>
                <td class="actions">
                    <?php if ((int) $user['id'] !== (int) $_SESSION['user_id']): ?>
                        <form method="post"><input type="hidden" name="csrf_token" value="<?php echo h(generate_csrf_token()); ?>"><input type="hidden" name="id" value="<?php echo h($user['id']); ?>"><input type="hidden" name="action" value="<?php echo (int) $user['is_active'] === 1 ? 'disable' : 'enable'; ?>"><button class="btn secondary" type="submit"><?php echo (int) $user['is_active'] === 1 ? 'Disable' : 'Enable'; ?></button></form>
                        <form method="post"><input type="hidden" name="csrf_token" value="<?php echo h(generate_csrf_token()); ?>"><input type="hidden" name="id" value="<?php echo h($user['id']); ?>"><input type="hidden" name="action" value="delete"><button class="danger-btn" type="submit" data-confirm="Delete this user?">Delete</button></form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
