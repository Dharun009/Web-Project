
<?php
require 'config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $password = $_POST['password'] ?? '';

    if (
        $name === '' ||
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        strlen($password) < 8
    ) {
        $error = 'Enter valid details. Password must have at least 8 characters.';
    } else {
        try {
            $stmt = $pdo->prepare(
                "INSERT INTO users (name, email, password_hash, department, role)
                 VALUES (?, ?, ?, ?, 'student')"
            );
            $stmt->execute([
                $name,
                $email,
                password_hash($password, PASSWORD_DEFAULT),
                $department
            ]);

            header('Location: login.php?registered=1');
            exit;
        } catch (PDOException $e) {
            $error = $e->getCode() === '23000'
                ? 'This email is already registered.'
                : 'Registration failed. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Student Registration</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="auth-card">
    <h1>Join Smart Campus</h1>
    <p>Create your student account</p>

    <?php if ($error): ?>
        <p class="error"><?= h($error) ?></p>
    <?php endif; ?>

    <form method="POST">
        <label>Full Name</label>
        <input name="name" required maxlength="100">

        <label>Email</label>
        <input type="email" name="email" required maxlength="150">

        <label>Department</label>
        <input name="department" required maxlength="100">

        <label>Password (minimum 8 characters)</label>
        <input type="password" name="password" minlength="8" required>

        <button type="submit">Register</button>
    </form>

    <p>Already registered? <a href="login.php">Login</a></p>
</div>
</body>
</html>
