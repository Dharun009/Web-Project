
<?php
require 'config.php';
require_role('student');

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $categoryId = filter_var(
        $_POST['category_id'] ?? null,
        FILTER_VALIDATE_INT
    );
    $priority = $_POST['priority'] ?? 'Medium';

    if (
        $title === '' || strlen($title) > 150 ||
        $description === '' || strlen($description) > 10000 ||
        $location === '' || strlen($location) > 200 ||
        !$categoryId ||
        !in_array($priority, ['Low','Medium','High','Critical'], true)
    ) {
        $error = 'Please enter valid issue details.';
    } else {
        $check = $pdo->prepare(
            'SELECT id FROM categories WHERE id = ?'
        );
        $check->execute([$categoryId]);

        if (!$check->fetch()) {
            $error = 'Select a valid category.';
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO issues
                 (title, description, location, category_id,
                  priority, reported_by)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $title,
                $description,
                $location,
                $categoryId,
                $priority,
                $_SESSION['user_id']
            ]);

            $issueId = (int)$pdo->lastInsertId();

            $admins = $pdo->query(
                "SELECT id FROM users WHERE role = 'admin'"
            )->fetchAll();

            $notice = $pdo->prepare(
                'INSERT INTO notifications (user_id, issue_id, message)
                 VALUES (?, ?, ?)'
            );

            foreach ($admins as $admin) {
                $notice->execute([
                    $admin['id'],
                    $issueId,
                    "A new campus issue was submitted: " . $title
                ]);
            }

            header('Location: dashboard.php?submitted=1');
            exit;
        }
    }
}

$categories = $pdo->query(
    'SELECT id, name FROM categories ORDER BY name'
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Report an Issue</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<main class="auth-card wide">
    <h1>Report a Campus Issue</h1>
    <p>Give the campus team enough detail to fix the problem.</p>

    <?php if ($error): ?>
        <p class="error"><?= h($error) ?></p>
    <?php endif; ?>

    <form method="POST">
        <label>Issue Title</label>
        <input name="title" maxlength="150" required>

        <label>Category</label>
        <select name="category_id" required>
            <option value="">Choose a category</option>
            <?php foreach ($categories as $category): ?>
                <option value="<?= (int)$category['id'] ?>">
                    <?= h($category['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label>Campus Location / Block</label>
        <input name="location" maxlength="200" required
               placeholder="Example: Block A, Room 204">

        <label>Priority</label>
        <select name="priority" required>
            <option>Low</option>
            <option selected>Medium</option>
            <option>High</option>
            <option>Critical</option>
        </select>

        <label>Issue Description</label>
        <textarea name="description" rows="5" maxlength="10000"
                  required placeholder="Describe the problem..."></textarea>

        <button type="submit">Submit Issue</button>
        <a href="dashboard.php">Back to Dashboard</a>
    </form>
</main>
</body>
</html>
