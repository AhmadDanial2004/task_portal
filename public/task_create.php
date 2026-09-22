<?php
require_once '../src/helpers/auth.php';
require_once '../src/config/database.php';
require_once '../src/helpers/validation.php';
require_once '../src/helpers/csrf.php';

requireLogin();
$userId = $_SESSION['user_id'];
$isAdmin = (($_SESSION['role'] ?? '') === 'Administrator');

// Fetch active projects and team members for dropdowns
$projectsStmt = $pdo->query("SELECT id, name FROM projects ORDER BY name ASC");
$projects = $projectsStmt->fetchAll(PDO::FETCH_ASSOC);

$usersStmt = $pdo->query("SELECT id, name, role FROM users WHERE status = 'Active' ORDER BY name ASC");
$users = $usersStmt->fetchAll(PDO::FETCH_ASSOC);

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $error = "Invalid request token. Please refresh and try again.";
    } else {
        $title = trim($_POST['title'] ?? '');
        $projectId = filter_input(INPUT_POST, 'project_id', FILTER_VALIDATE_INT);
        $assignedUserId = filter_input(INPUT_POST, 'assigned_user_id', FILTER_VALIDATE_INT);
        $description = trim($_POST['description'] ?? '');
        $priority = $_POST['priority'] ?? 'Medium';
        $status = $_POST['status'] ?? 'Pending';
        $dueDate = !empty($_POST['due_date']) ? $_POST['due_date'] : null;

        if (empty($title)) {
            $error = "Task title is required.";
        } elseif (!$projectId) {
            $error = "Please select a valid project.";
        } elseif (!$assignedUserId) {
            $error = "Please select an assignee.";
        } elseif (!in_array($priority, ['Low', 'Medium', 'High'], true)) {
            $error = "Invalid priority selected.";
        } elseif (!in_array($status, ['Pending', 'In Progress', 'Completed'], true)) {
            $error = "Invalid status selected.";
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO tasks (project_id, assigned_user_id, created_by, title, description, priority, status, due_date)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$projectId, $assignedUserId, $userId, $title, $description, $priority, $status, $dueDate]);
            header('Location: tasks.php?success=' . urlencode('Task created successfully.'));
            exit;
        }
    }
}

$csrfToken = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New Task - Task Portal</title>
    
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
                <h1 class="fw-bold fs-2 mb-1">Create New Task</h1>
                <p class="text-muted mb-0" style="font-size: 0.9rem;">Add a new task and assign it to a team member.</p>
            </div>
            <a href="tasks.php" class="btn btn-outline-secondary shadow-sm">Back to Tasks</a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger border-0 bg-danger bg-opacity-10 text-danger rounded-3 mb-4" style="max-width: 850px;">
                <?= sanitize($error) ?>
            </div>
        <?php endif; ?>

        <div class="panel-card" style="max-width: 850px;">
            <form action="task_create.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= sanitize($csrfToken) ?>">

                <div class="mb-3">
                    <label class="form-label fw-medium text-dark" style="font-size: 0.9rem;">Task Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control bg-light border-0" placeholder="e.g. Design wireframes" required>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-medium text-dark" style="font-size: 0.9rem;">Description</label>
                    <textarea name="description" class="form-control bg-light border-0" rows="4" placeholder="Task description and deliverables..."></textarea>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-medium text-dark" style="font-size: 0.9rem;">Project <span class="text-danger">*</span></label>
                        <select name="project_id" class="form-select bg-light border-0" required>
                            <option value="">Select Project</option>
                            <?php foreach ($projects as $p): ?>
                                <option value="<?= $p['id'] ?>"><?= sanitize($p['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-medium text-dark" style="font-size: 0.9rem;">Assign To <span class="text-danger">*</span></label>
                        <select name="assigned_user_id" class="form-select bg-light border-0" required>
                            <option value="">Select User</option>
                            <?php foreach ($users as $u): ?>
                                <option value="<?= $u['id'] ?>"><?= sanitize($u['name']) ?> (<?= sanitize($u['role']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label fw-medium text-dark" style="font-size: 0.9rem;">Priority</label>
                        <select name="priority" class="form-select bg-light border-0">
                            <option value="Low">Low</option>
                            <option value="Medium" selected>Medium</option>
                            <option value="High">High</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-medium text-dark" style="font-size: 0.9rem;">Status</label>
                        <select name="status" class="form-select bg-light border-0">
                            <option value="Pending" selected>Pending</option>
                            <option value="In Progress">In Progress</option>
                            <option value="Completed">Completed</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-medium text-dark" style="font-size: 0.9rem;">Due Date</label>
                        <input type="date" name="due_date" class="form-control bg-light border-0">
                    </div>
                </div>

                <div class="pt-2 border-top">
                    <button type="submit" class="btn btn-primary px-4 shadow-sm">Save Task</button>
                </div>
            </form>
        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>