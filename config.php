
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host = "localhost";
$dbname = "smart_campus";
$username = "root";
$password = ""; // Default XAMPP setup

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    exit("Database connection failed. Check XAMPP and config.php.");
}

function require_login(): void {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }
}

function require_role(string $role): void {
    require_login();

    if (($_SESSION['role'] ?? '') !== $role) {
        http_response_code(403);
        exit("Access denied.");
    }
}

function h($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>
