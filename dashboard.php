
<?php
require 'config.php';
require_login();

$role = $_SESSION['role'];
$userId = (int) $_SESSION['user_id'];

if ($role === 'admin') {
    $stats = $pdo->query(
        "SELECT COUNT(*) total,
         SUM(status = 'Open') open_count,
         SUM(status = 'In Progress') progress_count,
         SUM(status = 'Resolved') resolved_count
         FROM issues"
    )->fetch();

    $stmt = $pdo->query(
        "SELECT i.*, c.name category_name, u.name student_name,
                s.name staff_name
         FROM issues i
         JOIN categories c ON c.id = i.category_id
         JOIN users u ON u.id = i.reported_by
         LEFT JOIN users s ON s.id = i.assigned_to
         ORDER BY i.created_at DESC"
    );
    $issues = $stmt->fetchAll();

    $staff = $pdo->query(
        "SELECT id, name FROM users WHERE role = 'staff' ORDER BY name"
    )->fetchAll();

} else {
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) total,
         COALESCE(SUM(status IN ('Open','Assigned')), 0) open_count,
         COALESCE(SUM(status = 'In Progress'), 0) progress_count,
         COALESCE(SUM(status IN ('Resolved','Closed')), 0) resolved_count
         FROM issues WHERE " .
         ($role === 'student' ? 'reported_by' : 'assigned_to') . " = ?"
    );
    $stmt->execute([$userId]);
    $stats = $stmt->fetch();

    $stmt = $pdo->prepare(
        "SELECT i.*, c.name category_name, u.name student_name,
                s.name staff_name
         FROM issues i
         JOIN categories c ON c.id = i.category_id
         JOIN users u ON u.id = i.reported_by
         LEFT JOIN users s ON s.id = i.assigned_to
         WHERE " .
         ($role === 'student' ? 'i.reported_by' : 'i.assigned_to') . " = ?
         ORDER BY i.created_at DESC"
    );
    $stmt->execute([$userId]);
    $issues = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard - Smart Campus</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header class="topbar">
    <div>
        <h2>Smart Campus</h2>
        <small>Issue Management System</small>
    </div>
    <div>
        <?= h($_SESSION['name']) ?> · <?= h(ucfirst($role)) ?>
        <a class="logout" href="logout.php">Logout</a>
    </div>
</header>

<main class="container">
    <h1>Welcome, <?= h($_SESSION['name']) ?>!</h1>
    <p>Manage and track campus issues from your dashboard.</p>

    <?php if ($role === 'student'): ?>
        <a class="button-link" href="submit_issue.php">+ Report New Issue</a>
    <?php endif; ?>

    <section class="stats">
        <div class="stat-card">
            <span>Total Issues</span>
            <h2><?= (int)($stats['total'] ?? 0) ?></h2>
        </div>
        <div class="stat-card">
            <span>Open / Assigned</span>
            <h2><?= (int)($stats['open_count'] ?? 0) ?></h2>
        </div>
        <div class="stat-card">
            <span>In Progress</span>
            <h2><?= (int)($stats['progress_count'] ?? 0) ?></h2>
        </div>
        <div class="stat-card">
            <span>Resolved / Closed</span>
            <h2><?= (int)($stats['resolved_count'] ?? 0) ?></h2>
        </div>
    </section>

    <section class="panel">
        <h2><?= $role === 'admin' ? 'All Campus Issues' : 'Your Issues' ?></h2>

        <?php if (!$issues): ?>
            <p>No issues found.</p>
        <?php else: ?>
        <div class="table-wrapper">
        <table>
            <thead>
            <tr>
                <th>ID / Issue</th>
                <?php if ($role === 'admin'): ?><th>Reported By</th><?php endif; ?>
                <th>Category</th>
                <th>Priority</th>
                <th>Status</th>
                <?php if ($role === 'admin'): ?><th>Assign Staff</th><?php endif; ?>
                <?php if ($role === 'staff'): ?><th>Update Status</th><?php endif; ?>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($issues as $issue): ?>
                <tr>
                    <td>
                        #<?= (int)$issue['id'] ?><br>
                        <strong><?= h($issue['title']) ?></strong><br>
                        <small><?= h($issue['location']) ?></small>
                    </td>
                    <?php if ($role === 'admin'): ?>
                        <td><?= h($issue['student_name']) ?></td>
                    <?php endif; ?>
                    <td><?= h($issue['category_name']) ?></td>
                    <td><?= h($issue['priority']) ?></td>
                    <td><span class="status"><?= h($issue['status']) ?></span></td>

                    <?php if ($role === 'admin'): ?>
                    <td>
                        <form action="update_issue.php" method="POST">
                            <input type="hidden" name="issue_id"
                                   value="<?= (int)$issue['id'] ?>">
                            <select name="staff_id" required>
                                <option value="">Select staff</option>
                                <?php foreach ($staff as $person): ?>
                                <option value="<?= (int)$person['id'] ?>"
                                    <?= (int)$issue['assigned_to'] === (int)$person['id'] ? 'selected' : '' ?>>
                                    <?= h($person['name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" name="action" value="assign">
                                Assign
                            </button>
                        </form>
                    </td>
                    <?php endif; ?>

                    <?php if ($role === 'staff'): ?>
                    <td>
                        <form action="update_issue.php" method="POST">
                            <input type="hidden" name="issue_id"
                                   value="<?= (int)$issue['id'] ?>">
                            <select name="status" required>
                                <?php foreach (['Assigned','In Progress','Resolved'] as $status): ?>
                                <option value="<?= h($status) ?>"
                                    <?= $issue['status'] === $status ? 'selected' : '' ?>>
                                    <?= h($status) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" name="action" value="status">
                                Update
                            </button>
                        </form>
                    </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    </section>
</main>
</body>
</html>
