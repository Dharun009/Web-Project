
<?php
require 'config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare(
        "SELECT id, name, email, password_hash, role
         FROM users WHERE email = ?"
    );
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['role'] = $user['role'];

        header('Location: dashboard.php');
        exit;
    }

    $error = 'Invalid email or password.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - Smart Campus</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="auth-card">
    <h1>Smart Campus</h1>
    <p>Campus Issue Management System</p>

    <?php if (isset($_GET['registered'])): ?>
        <p class="success">Registration successful. Please log in.</p>
    <?php endif; ?>

    <?php if ($error): ?>
        <p class="error"><?= h($error) ?></p>
    <?php endif; ?>

    <form method="POST">
        <label>Email</label>
        <input type="email" name="email" required>

        <label>Password</label>
        <input type="password" name="password" required>

        <button type="submit">Login</button>
    </form>

    <p>New student? <a href="register.php">Create account</a></p>
</div>
</body>
</html>
