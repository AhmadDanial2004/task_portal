<?php
require_once '../src/helpers/auth.php';
require_once '../src/config/database.php';
require_once '../src/helpers/validation.php';
require_once '../src/helpers/csrf.php';

requireLogin();

$taskId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$taskId) {
    header('Location: tasks.php?error=' . urlencode('Task not found.'));
    exit;
}

// Fetch Task with Project & Assignee Details
$stmt = $pdo->prepare("
    SELECT t.*, p.name AS project_name, u.name AS assignee_name, u.role AS assignee_role, creator.name AS creator_name
    FROM tasks t
    JOIN projects p ON t.project_id = p.id
    JOIN users u ON t.assigned_user_id = u.id
    JOIN users creator ON t.created_by = creator.id
    WHERE t.id = ?
");
$stmt->execute([$taskId]);
$task = $stmt->fetch();

if (!$task) {
    header('Location: tasks.php?error=' . urlencode('Task not found.'));
    exit;
}

$error = '';
$success = '';

// Handle New Comment Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_comment') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $error = "Invalid request token. Please try again.";
    } else {
        $comment = trim($_POST['comment'] ?? '');
        if (empty($comment)) {
            $error = "Comment cannot be empty.";
        } else {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO comments (task_id, user_id, comment, created_at)
                    VALUES (?, ?, ?, NOW())
                ");
                $stmt->execute([$taskId, $_SESSION['user_id'], $comment]);
                header("Location: task_details.php?id={$taskId}&success=" . urlencode('Comment added.'));
                exit;
            } catch (PDOException $e) {
                $error = "Failed to post comment.";
                error_log($e->getMessage());
            }
        }
    }
}

// Fetch Comments for This Task
$stmt = $pdo->prepare("
    SELECT c.*, u.name AS author_name, u.role AS author_role
    FROM comments c
    JOIN users u ON c.user_id = u.id
    WHERE c.task_id = ?
    ORDER BY c.created_at ASC
");
$stmt->execute([$taskId]);
$comments = $stmt->fetchAll();

$csrfToken = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitize($task['title']) ?> - Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,1,0" />
    <link href="style.css" rel="stylesheet">
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-2 sidebar p-3 d-none d-md-block">
            <div class="d-flex align-items-center mb-4 px-3 mt-2">
                <div class="rounded-circle bg-secondary me-2 flex-shrink-0" style="width: 40px; height: 40px;"></div>
                <div>
                    <h6 class="mb-0 text-white"><?= sanitize($_SESSION['name']) ?></h6>
                    <small class="text-muted" style="font-size: 0.75rem;"><?= sanitize($_SESSION['role']) ?></small>
                </div>
            </div>
            <nav class="nav flex-column">
                <a class="nav-link" href="dashboard.php">Home</a>
                <a class="nav-link active" href="tasks.php">My Tasks</a>
                <?php if ($_SESSION['role'] === 'Administrator'): ?>
                    <a class="nav-link" href="projects.php">Projects</a>
                    <a class="nav-link" href="users.php">Users</a>
                <?php endif; ?>
                <hr class="my-4" style="border-color: var(--dashbrd-border);">
                <a class="nav-link mt-2 text-danger" href="logout.php">Logout</a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="col-md-10 p-4 pt-5 px-lg-5">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <span class="badge bg-light text-muted border mb-2"><?= sanitize($task['project_name']) ?></span>
                    <h3 class="fw-bold mb-0"><?= sanitize($task['title']) ?></h3>
                </div>
                <div>
                    <a href="task_edit.php?id=<?= $task['id'] ?>" class="btn btn-outline-primary me-2">Edit Properties</a>
                    <a href="tasks.php" class="btn btn-outline-secondary">Back to Tasks</a>
                </div>
            </div>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success p-3 mb-4 rounded-3"><?= sanitize($_GET['success']) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger p-3 mb-4 rounded-3"><?= sanitize($error) ?></div>
            <?php endif; ?>

            <!-- Task Overview Card -->
            <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
                <div class="row g-3 mb-3">
                    <div class="col-md-3">
                        <small class="text-muted d-block">Status</small>
                        <span class="badge bg-primary-subtle text-primary mt-1"><?= sanitize($task['status']) ?></span>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted d-block">Priority</small>
                        <span class="badge bg-warning-subtle text-warning mt-1"><?= sanitize($task['priority']) ?></span>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted d-block">Assignee</small>
                        <strong class="d-block mt-1"><?= sanitize($task['assignee_name']) ?></strong>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted d-block">Due Date</small>
                        <span class="d-block mt-1"><?= sanitize($task['due_date'] ?? 'No deadline') ?></span>
                    </div>
                </div>

                <hr class="text-muted opacity-25">

                <div>
                    <h6 class="text-muted mb-2">Description</h6>
                    <p class="mb-0 text-secondary" style="white-space: pre-wrap;"><?= !empty($task['description']) ? sanitize($task['description']) : '<em>No description provided.</em>' ?></p>
                </div>
            </div>

            <!-- Activity & Comments Section -->
            <div class="card border-0 shadow-sm rounded-4 p-4">
                <h5 class="fw-bold mb-3 d-flex align-items-center">
                    <span class="material-symbols-outlined text-primary me-2">chat</span>
                    Activity & Comments (<?= count($comments) ?>)
                </h5>

                <?php if (empty($comments)): ?>
                    <p class="text-muted mb-4">No comments recorded yet. Add an update or note progress below.</p>
                <?php else: ?>
                    <div class="d-flex flex-column gap-3 mb-4">
                        <?php foreach ($comments as $c): ?>
                            <div class="border rounded-3 p-3 bg-light-subtle">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <strong><?= sanitize($c['author_name']) ?></strong>
                                    <small class="text-muted"><?= date('M j, Y \a\t g:i A', strtotime($c['created_at'])) ?></small>
                                </div>
                                <p class="mb-0 text-secondary" style="white-space: pre-wrap;"><?= sanitize($c['comment']) ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Post Comment Form -->
                <form action="task_details.php?id=<?= $task['id'] ?>" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= sanitize($csrfToken) ?>">
                    <input type="hidden" name="action" value="add_comment">

                    <div class="mb-3">
                        <textarea name="comment" class="form-control" rows="3" placeholder="Write a comment or status update..." required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary px-4">Post Comment</button>
                </form>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>