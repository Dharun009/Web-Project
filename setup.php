
<?php
$pdo = new PDO(
    "mysql:host=localhost;charset=utf8mb4",
    "root",
    "",
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$pdo->exec(
    "CREATE DATABASE IF NOT EXISTS smart_campus
     CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
);

$pdo->exec("USE smart_campus");

$pdo->exec("
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('student','admin','staff')
        NOT NULL DEFAULT 'student',
    department VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB
");

$pdo->exec("
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB
");

$pdo->exec("
CREATE TABLE IF NOT EXISTS issues (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    description TEXT NOT NULL,
    location VARCHAR(200) NOT NULL,
    category_id INT NOT NULL,
    priority ENUM('Low','Medium','High','Critical')
        NOT NULL DEFAULT 'Medium',
    status ENUM('Open','Assigned','In Progress','Resolved','Closed')
        NOT NULL DEFAULT 'Open',
    reported_by INT NOT NULL,
    assigned_to INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id),
    FOREIGN KEY (reported_by) REFERENCES users(id),
    FOREIGN KEY (assigned_to) REFERENCES users(id)
        ON DELETE SET NULL
) ENGINE=InnoDB
");

$pdo->exec("
CREATE TABLE IF NOT EXISTS issue_updates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    issue_id INT NOT NULL,
    updated_by INT NOT NULL,
    old_status VARCHAR(30),
    new_status VARCHAR(30) NOT NULL,
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (issue_id) REFERENCES issues(id)
        ON DELETE CASCADE,
    FOREIGN KEY (updated_by) REFERENCES users(id)
) ENGINE=InnoDB
");

$pdo->exec("
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    issue_id INT NULL,
    message VARCHAR(255) NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE,
    FOREIGN KEY (issue_id) REFERENCES issues(id)
        ON DELETE SET NULL
) ENGINE=InnoDB
");

$stmt = $pdo->prepare(
    "INSERT IGNORE INTO categories (name) VALUES (?), (?), (?), (?), (?)"
);
$stmt->execute([
    "Electrical", "Network", "Infrastructure",
    "Cleanliness", "Equipment"
]);

$stmt = $pdo->prepare(
    "INSERT IGNORE INTO users
     (name, email, password_hash, role, department)
     VALUES (?, ?, ?, 'admin', ?)"
);
$stmt->execute([
    "Campus Administrator",
    "admin@campus.local",
    password_hash("Admin@123", PASSWORD_DEFAULT),
    "Administration"
]);

echo "Setup completed successfully!<br>";
echo "Admin email: admin@campus.local<br>";
echo "Admin password: Admin@123<br>";
echo "Delete setup.php after setup and change the default password.";
?>
