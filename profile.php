<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

requireLogin();

$errors = [];
$message = '';

$username = $_SESSION['username'] ?? '';
$fullName = $_SESSION['full_name'] ?? '';

/* Load current user data */
$stmt = mysqli_prepare($conn, 'SELECT username, full_name FROM users WHERE id = ? LIMIT 1');
mysqli_stmt_bind_param($stmt, 'i', $_SESSION['user_id']);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if ($user) {
    $username = $user['username'];
    $fullName = $user['full_name'];
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {

        $errors[] = 'Invalid CSRF token.';
    } else {

        $newUsername = trim($_POST['username'] ?? '');
        $newFullName = trim($_POST['full_name'] ?? '');

        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';


        if ($newUsername === '' || $newFullName === '') {
            $errors[] = 'Full name and username are required.';
        }


        $changingPassword = $newPassword !== '' || $confirmPassword !== '';

        if (
            $changingPassword &&
            (strlen($newPassword) < 6 || $newPassword !== $confirmPassword)
        ) {

            $errors[] = 'New password must be at least 6 characters and match confirmation.';
        }


        // Check current password
        $stmt = mysqli_prepare(
            $conn,
            'SELECT password FROM users WHERE id = ? LIMIT 1'
        );

        mysqli_stmt_bind_param($stmt, 'i', $_SESSION['user_id']);
        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);
        $dbUser = mysqli_fetch_assoc($result);

        mysqli_stmt_close($stmt);


        if (!$dbUser || !password_verify($currentPassword, $dbUser['password'])) {
            $errors[] = 'Current password is incorrect.';
        }



        if (!$errors) {

            if ($changingPassword) {

                $passwordHash = password_hash(
                    $newPassword,
                    PASSWORD_DEFAULT
                );


                $stmt = mysqli_prepare(
                    $conn,
                    'UPDATE users 
                     SET full_name = ?, username = ?, password = ?
                     WHERE id = ?'
                );


                mysqli_stmt_bind_param(
                    $stmt,
                    'sssi',
                    $newFullName,
                    $newUsername,
                    $passwordHash,
                    $_SESSION['user_id']
                );
            } else {


                $stmt = mysqli_prepare(
                    $conn,
                    'UPDATE users 
                     SET full_name = ?, username = ?
                     WHERE id = ?'
                );


                mysqli_stmt_bind_param(
                    $stmt,
                    'ssi',
                    $newFullName,
                    $newUsername,
                    $_SESSION['user_id']
                );
            }


            if (mysqli_stmt_execute($stmt)) {

                $_SESSION['username'] = $newUsername;
                $_SESSION['full_name'] = $newFullName;

                $username = $newUsername;
                $fullName = $newFullName;

                $message = 'Account updated.';
            } else {

                $errors[] =
                    'Unable to update account. Username may already be taken.';
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


<?php if ($message !== ''): ?>
    <div class="message success-message">
        <?php echo h($message); ?>
    </div>
<?php endif; ?>


<?php foreach ($errors as $error): ?>
    <div class="message error">
        <?php echo h($error); ?>
    </div>
<?php endforeach; ?>



<form class="panel" method="post" data-validate>

    <input type="hidden"
        name="csrf_token"
        value="<?php echo h(generate_csrf_token()); ?>">


    <div class="form-grid">


        <div class="form-group">
            <label>Full Name</label>

            <input
                name="full_name"
                required
                value="<?php echo h($fullName); ?>">
        </div>

        <br>
        <br>
        <br>
        <div class="form-group">
            <label>Username</label>

            <input
                name="username"
                required
                value="<?php echo h($username); ?>">
        </div>

        <br>

        <div class="form-group">
            <label>Current Password</label>

            <input
                type="password"
                name="current_password"
                required>
        </div>



        <div class="form-group">
            <label>New Password</label>

            <input
                type="password"
                name="new_password">
        </div>



        <div class="form-group">
            <label>Confirm New Password</label>

            <input
                type="password"
                name="confirm_password">
        </div>



    </div>


    <button class="btn primary" type="submit">
        Update Account
    </button>


</form>


<?php require_once __DIR__ . '/includes/footer.php'; ?>