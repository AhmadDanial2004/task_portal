<?php
require_once '../src/helpers/auth.php';
require_once '../src/config/database.php';
require_once '../src/helpers/validation.php';
require_once '../src/helpers/csrf.php';

requireLogin();
$userId = $_SESSION['user_id'];
$isAdmin = (($_SESSION['role'] ?? '') === 'Administrator');

$taskId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$taskId) {
    header('Location: tasks.php?error=' . urlencode('Task not found'));
    exit;
}

// 1. Fetch Task Details securely
$stmt = $pdo->prepare("
    SELECT t.*, p.name as project_name, u.name as assignee_name 
    FROM tasks t 
    JOIN projects p ON t.project_id = p.id 
    JOIN users u ON t.assigned_user_id = u.id 
    WHERE t.id = ?
");
$stmt->execute([$taskId]);
$task = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$task) {
    header('Location: tasks.php?error=' . urlencode('Task not found'));
    exit;
}

// Access Control: Admins, assignees, or creators
if (!$isAdmin && $task['assigned_user_id'] != $userId && $task['created_by'] != $userId) {
    header('Location: tasks.php?error=' . urlencode('Access Denied'));
    exit;
}

$errorMsg = $_GET['error'] ?? '';
$successMsg = $_GET['success'] ?? '';

// 2. Handle POST Actions (Update, Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF Token
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        header("Location: task_edit.php?id={$taskId}&error=" . urlencode('Invalid request token. Please refresh and try again.'));
        exit;
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        if ($isAdmin || $task['created_by'] == $userId) {
            $delStmt = $pdo->prepare("DELETE FROM tasks WHERE id = ?");
            $delStmt->execute([$taskId]);
            header('Location: tasks.php?success=' . urlencode('Task deleted successfully'));
            exit;
        } else {
            header("Location: task_edit.php?id={$taskId}&error=" . urlencode('Unauthorized to delete task'));
            exit;
        }
    } 
    elseif ($action === 'update') {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $priority = $_POST['priority'] ?? 'Medium';
        $status = $_POST['status'] ?? 'Pending';
        $dueDate = !empty($_POST['due_date']) ? $_POST['due_date'] : null;

        $allowedPriorities = ['Low', 'Medium', 'High'];
        $allowedStatuses = ['Pending', 'In Progress', 'Completed'];

        if (empty($title)) {
            header("Location: task_edit.php?id={$taskId}&error=" . urlencode('Title is required'));
            exit;
        }

        if (!in_array($priority, $allowedPriorities, true)) {
            header("Location: task_edit.php?id={$taskId}&error=" . urlencode('Invalid priority selected'));
            exit;
        }

        if (!in_array($status, $allowedStatuses, true)) {
            header("Location: task_edit.php?id={$taskId}&error=" . urlencode('Invalid status selected'));
            exit;
        }

        $updStmt = $pdo->prepare("
            UPDATE tasks 
            SET title = ?, description = ?, priority = ?, status = ?, due_date = ?, updated_at = NOW() 
            WHERE id = ?
        ");
        $updStmt->execute([$title, $description, $priority, $status, $dueDate, $taskId]);
        header("Location: task_edit.php?id={$taskId}&success=" . urlencode('Task updated successfully'));
        exit;
    }
}

// Generate CSRF Token for forms
$csrfToken = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Task - Task Portal</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,1,0" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        :root {
            --bg-body: #f6f8fb;
            --card-bg: #ffffff;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --brand-primary: #3b82f6;
            --sidebar-width: 260px;
        }
        body { font-family: 'Inter', sans-serif; background-color: var(--bg-body); color: var(--text-main); margin: 0; padding: 0; }
        .dashboard-container { display: flex; min-height: 100vh; }
        
        .sidebar { width: var(--sidebar-width); background-color: var(--card-bg); border-right: 1px solid #e2e8f0; position: fixed; height: 100vh; overflow-y: auto; padding: 1.5rem; display: flex; flex-direction: column; z-index: 10; }
        .brand-logo { font-size: 1.25rem; font-weight: 700; color: var(--text-main); text-decoration: none; display: flex; align-items: center; margin-bottom: 2.5rem; }
        .nav-link { display: flex; align-items: center; color: var(--text-muted); font-weight: 500; padding: 0.75rem 1rem; border-radius: 0.5rem; margin-bottom: 0.25rem; transition: all 0.2s; }
        .nav-link:hover, .nav-link.active { background-color: #f1f5f9; color: var(--text-main); }
        .nav-link .material-symbols-outlined { margin-right: 0.75rem; font-size: 1.25rem; }
        
        .main-content { flex-grow: 1; margin-left: var(--sidebar-width); padding: 2rem 3rem; animation: fadeSlideIn 0.4s cubic-bezier(0.22, 1, 0.36, 1) forwards; will-change: opacity, transform; opacity: 0; }
        .panel-card { background-color: var(--card-bg); border-radius: 1rem; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.05); padding: 1.75rem; margin-bottom: 1.75rem; }

        .fade-out { animation: fadeSlideOut 0.3s cubic-bezier(0.22, 1, 0.36, 1) forwards !important; }
        @keyframes fadeSlideIn { 0% { opacity: 0; transform: translateY(15px); } 100% { opacity: 1; transform: translateY(0); } }
        @keyframes fadeSlideOut { 0% { opacity: 1; transform: translateY(0); } 100% { opacity: 0; transform: translateY(-15px); } }

        @media (max-width: 991px) { .sidebar { transform: translateX(-100%); transition: transform 0.3s; } .main-content { margin-left: 0; padding: 1.5rem; } }
    </style>
</head>
<body>

<div class="dashboard-container">
    <aside class="sidebar">
        <a href="dashboard.php" class="brand-logo">Task Portal</a>
        <div class="text-muted fs-6 fw-semibold mb-2 px-3" style="font-size: 0.75rem;">Main</div>
        <nav class="nav flex-column mb-4">
            <a class="nav-link" href="dashboard.php"><span class="material-symbols-outlined">space_dashboard</span> Default</a>
            <a class="nav-link active" href="tasks.php"><span class="material-symbols-outlined">list_alt</span> My Tasks</a>
            <?php if ($isAdmin): ?>
                <a class="nav-link" href="projects.php"><span class="material-symbols-outlined">folder</span> Projects</a>
                <a class="nav-link" href="users.php"><span class="material-symbols-outlined">group</span> Users</a>
            <?php endif; ?>
        </nav>
        <div class="mt-auto">
            <hr style="border-color: #e2e8f0;">
            <a class="nav-link text-danger" href="logout.php"><span class="material-symbols-outlined">logout</span> Sign Out</a>
        </div>
    </aside>

    <main class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="fw-bold fs-2 mb-1">Edit Task</h1>
                <p class="text-muted mb-0" style="font-size: 0.9rem;">Modify task priority, status, or details.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="task_details.php?id=<?= $task['id'] ?>" class="btn btn-outline-primary shadow-sm">View Details</a>
                <a href="tasks.php" class="btn btn-outline-secondary shadow-sm">Back to Tasks</a>
            </div>
        </div>

        <?php if ($successMsg): ?>
            <div class="alert alert-success border-0 bg-success bg-opacity-10 text-success rounded-3 mb-4" style="max-width: 850px;">
                <?= sanitize($successMsg) ?>
            </div>
        <?php endif; ?>
        <?php if ($errorMsg): ?>
            <div class="alert alert-danger border-0 bg-danger bg-opacity-10 text-danger rounded-3 mb-4" style="max-width: 850px;">
                <?= sanitize($errorMsg) ?>
            </div>
        <?php endif; ?>

        <!-- Task Edit Form -->
        <div class="panel-card" style="max-width: 850px;">
            <form action="task_edit.php?id=<?= $task['id'] ?>" method="POST">
                <input type="hidden" name="csrf_token" value="<?= sanitize($csrfToken) ?>">
                <input type="hidden" name="action" value="update">

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label text-muted fw-medium" style="font-size: 0.85rem;">Project</label>
                        <input type="text" class="form-control bg-light border-0 text-secondary fw-medium" value="<?= sanitize($task['project_name']) ?>" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted fw-medium" style="font-size: 0.85rem;">Assignee</label>
                        <input type="text" class="form-control bg-light border-0 text-secondary fw-medium" value="<?= sanitize($task['assignee_name']) ?>" readonly>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-medium text-dark" style="font-size: 0.9rem;">Task Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control bg-light border-0" value="<?= sanitize($task['title']) ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-medium text-dark" style="font-size: 0.9rem;">Description</label>
                    <textarea name="description" class="form-control bg-light border-0" rows="4"><?= sanitize($task['description'] ?? '') ?></textarea>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label fw-medium text-dark" style="font-size: 0.9rem;">Priority</label>
                        <select name="priority" class="form-select bg-light border-0">
                            <option value="Low" <?= $task['priority'] === 'Low' ? 'selected' : '' ?>>Low</option>
                            <option value="Medium" <?= $task['priority'] === 'Medium' ? 'selected' : '' ?>>Medium</option>
                            <option value="High" <?= $task['priority'] === 'High' ? 'selected' : '' ?>>High</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-medium text-dark" style="font-size: 0.9rem;">Status</label>
                        <select name="status" class="form-select bg-light border-0">
                            <option value="Pending" <?= $task['status'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="In Progress" <?= $task['status'] === 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                            <option value="Completed" <?= $task['status'] === 'Completed' ? 'selected' : '' ?>>Completed</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-medium text-dark" style="font-size: 0.9rem;">Due Date</label>
                        <input type="date" name="due_date" class="form-control bg-light border-0" value="<?= sanitize($task['due_date'] ?? '') ?>">
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                    <button type="submit" class="btn btn-primary px-4 shadow-sm">Update Task</button>
                    
                    <?php if ($isAdmin || $task['created_by'] == $userId): ?>
                        <button type="button" class="btn btn-outline-danger shadow-sm" onclick="if(confirm('Are you sure you want to delete this task?')) { document.getElementById('delete-task-form').submit(); }">
                            Delete Task
                        </button>
                    <?php endif; ?>
                </div>
            </form>

            <?php if ($isAdmin || $task['created_by'] == $userId): ?>
                <form id="delete-task-form" action="task_edit.php?id=<?= $task['id'] ?>" method="POST" style="display: none;">
                    <input type="hidden" name="csrf_token" value="<?= sanitize($csrfToken) ?>">
                    <input type="hidden" name="action" value="delete">
                </form>
            <?php endif; ?>
        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", () => {
        const navLinks = document.querySelectorAll('.sidebar .nav-link');
        const mainContent = document.querySelector('.main-content');
        navLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                if (this.getAttribute('href') === 'logout.php') return;
                e.preventDefault(); 
                const targetUrl = this.href;
                mainContent.classList.add('fade-out');
                setTimeout(() => { window.location.href = targetUrl; }, 300);
            });
        });
    });
</script>
</body>
</html>