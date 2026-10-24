
<?php
require 'config.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('POST requests only.');
}

$issueId = filter_var(
    $_POST['issue_id'] ?? null,
    FILTER_VALIDATE_INT
);

if (!$issueId || $issueId < 1) {
    exit('Invalid issue ID.');
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        'SELECT * FROM issues WHERE id = ? FOR UPDATE'
    );
    $stmt->execute([$issueId]);
    $issue = $stmt->fetch();

    if (!$issue) {
        throw new RuntimeException('Issue not found.');
    }

    $userId = (int)$_SESSION['user_id'];
    $role = $_SESSION['role'];
    $action = $_POST['action'] ?? '';

    if ($role === 'admin' && $action === 'assign') {
        $staffId = filter_var(
            $_POST['staff_id'] ?? null,
            FILTER_VALIDATE_INT
        );

        if (!$staffId || $staffId < 1) {
            throw new RuntimeException('Select a staff member.');
        }

        $check = $pdo->prepare(
            "SELECT id FROM users WHERE id = ? AND role = 'staff'"
        );
        $check->execute([$staffId]);

        if (!$check->fetch()) {
            throw new RuntimeException('Invalid staff account.');
        }

        $oldStatus = $issue['status'];
        $newStatus = $oldStatus === 'Open' ? 'Assigned' : $oldStatus;

        $stmt = $pdo->prepare(
            'UPDATE issues SET assigned_to = ?, status = ? WHERE id = ?'
        );
        $stmt->execute([$staffId, $newStatus, $issueId]);

        $history = $pdo->prepare(
            'INSERT INTO issue_updates
             (issue_id, updated_by, old_status, new_status, comment)
             VALUES (?, ?, ?, ?, ?)'
        );
        $history->execute([
            $issueId, $userId, $oldStatus, $newStatus,
            'Issue assigned or reassigned by administrator.'
        ]);

        $notice = $pdo->prepare(
            'INSERT INTO notifications (user_id, issue_id, message)
             VALUES (?, ?, ?)'
        );
        $notice->execute([
            $staffId, $issueId,
            'You have been assigned issue #' . $issueId
        ]);

    } elseif ($role === 'staff' && $action === 'status') {
        if ((int)$issue['assigned_to'] !== $userId) {
            throw new RuntimeException('This issue is not assigned to you.');
        }

        $newStatus = $_POST['status'] ?? '';
        $allowed = [
            'Assigned' => ['In Progress'],
            'In Progress' => ['Resolved'],
            'Resolved' => []
        ];

        $oldStatus = $issue['status'];

        if (
            !isset($allowed[$oldStatus]) ||
            !in_array($newStatus, $allowed[$oldStatus], true)
        ) {
            throw new RuntimeException('Invalid status transition.');
        }

        $pdo->prepare(
            'UPDATE issues SET status = ?, resolved_at = ? WHERE id = ?'
        )->execute([
            $newStatus,
            $newStatus === 'Resolved' ? date('Y-m-d H:i:s') : null,
            $issueId
        ]);

        $pdo->prepare(
            'INSERT INTO issue_updates
             (issue_id, updated_by, old_status, new_status, comment)
             VALUES (?, ?, ?, ?, ?)'
        )->execute([
            $issueId, $userId, $oldStatus, $newStatus,
            'Status updated by assigned staff.'
        ]);

        $notice = $pdo->prepare(
            'INSERT INTO notifications (user_id, issue_id, message)
             VALUES (?, ?, ?)'
        );
        $notice->execute([
            $issue['reported_by'],
            $issueId,
            'Your issue #' . $issueId . ' is now ' . $newStatus
        ]);

    } else {
        throw new RuntimeException('You cannot perform this action.');
    }

    $pdo->commit();
    header('Location: dashboard.php?updated=1');
    exit;

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    if ($e instanceof RuntimeException) {
        http_response_code(400);
        exit(h($e->getMessage()));
    }

    error_log($e->getMessage());
    http_response_code(500);
    exit('Unable to update the issue.');
}
?>
