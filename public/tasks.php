<?php
require_once '../src/helpers/auth.php';
require_once '../src/config/database.php';
require_once '../src/helpers/validation.php';

requireLogin();
$userId = $_SESSION['user_id'];
$isAdmin = $_SESSION['role'] === 'Administrator';

// 1. Fetch data for filter dropdowns
$projectsQuery = $isAdmin 
    ? "SELECT id, name FROM projects WHERE status = 'Active'"
    : "SELECT p.id, p.name FROM projects p JOIN project_users pu ON p.id = pu.project_id WHERE pu.user_id = ? AND p.status = 'Active'";
$projStmt = $isAdmin ? $pdo->query($projectsQuery) : $pdo->prepare($projectsQuery);
if (!$isAdmin) $projStmt->execute([$userId]);
$filterProjects = $projStmt->fetchAll(PDO::FETCH_ASSOC);

$filterUsers = $pdo->query("SELECT id, name FROM users WHERE status = 'Active'")->fetchAll(PDO::FETCH_ASSOC);

// 2. Handle Search & Filtering securely using prepared statements
$selectedProject = $_GET['project_id'] ?? '';
$selectedAssignee = $_GET['assignee_id'] ?? '';
$selectedPriority = $_GET['priority'] ?? '';
$selectedStatus = $_GET['status'] ?? '';
$selectedDueDate = $_GET['due_date'] ?? '';

$query = "
    SELECT t.*, p.name as project_name, u.name as assignee_name 
    FROM tasks t 
    JOIN projects p ON t.project_id = p.id 
    JOIN users u ON t.assigned_user_id = u.id 
    WHERE 1=1
";
$params = [];

if (!$isAdmin) {
    $query .= " AND t.project_id IN (SELECT project_id FROM project_users WHERE user_id = ?)";
    $params[] = $userId;
}
if (!empty($selectedProject)) { $query .= " AND t.project_id = ?"; $params[] = $selectedProject; }
if (!empty($selectedAssignee)) { $query .= " AND t.assigned_user_id = ?"; $params[] = $selectedAssignee; }
if (!empty($selectedPriority)) { $query .= " AND t.priority = ?"; $params[] = $selectedPriority; }
if (!empty($selectedStatus)) { $query .= " AND t.status = ?"; $params[] = $selectedStatus; }
if (!empty($selectedDueDate)) { $query .= " AND t.due_date = ?"; $params[] = $selectedDueDate; }

$query .= " ORDER BY t.created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

function getInitials($name) {
    $words = explode(' ', $name);
    $initials = '';
    foreach ($words as $w) { if (!empty($w)) $initials .= strtoupper($w[0]); }
    return substr($initials, 0, 2);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tasks - Task Portal</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,1,0" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        :root { --bg-body: #f6f8fb; --card-bg: #ffffff; --text-main: #1e293b; --text-muted: #64748b; --brand-primary: #3b82f6; --sidebar-width: 260px; }
        body { font-family: 'Inter', sans-serif; background-color: var(--bg-body); color: var(--text-main); margin: 0; padding: 0; }
        .dashboard-container { display: flex; min-height: 100vh; }
        
        .sidebar { width: var(--sidebar-width); background-color: var(--card-bg); border-right: 1px solid #e2e8f0; position: fixed; height: 100vh; overflow-y: auto; padding: 1.5rem; display: flex; flex-direction: column; z-index: 10; }
        .brand-logo { font-size: 1.25rem; font-weight: 700; color: var(--text-main); text-decoration: none; display: flex; align-items: center; margin-bottom: 2.5rem; }
        .nav-link { display: flex; align-items: center; color: var(--text-muted); font-weight: 500; padding: 0.75rem 1rem; border-radius: 0.5rem; margin-bottom: 0.25rem; transition: all 0.2s; }
        .nav-link:hover, .nav-link.active { background-color: #f1f5f9; color: var(--text-main); }
        .nav-link .material-symbols-outlined { margin-right: 0.75rem; font-size: 1.25rem; }
        .main-content { flex-grow: 1; margin-left: var(--sidebar-width); padding: 2rem 3rem; animation: fadeSlideIn 0.4s cubic-bezier(0.22, 1, 0.36, 1) forwards; will-change: opacity, transform; opacity: 0; }
        
        .panel-card { background-color: var(--card-bg); border-radius: 1rem; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.05); margin-bottom: 1.5rem; padding: 1.5rem; }
        
        .fade-out { animation: fadeSlideOut 0.3s cubic-bezier(0.22, 1, 0.36, 1) forwards !important; }
        @keyframes fadeSlideIn { 0% { opacity: 0; transform: translateY(15px); } 100% { opacity: 1; transform: translateY(0); } }
        @keyframes fadeSlideOut { 0% { opacity: 1; transform: translateY(0); } 100% { opacity: 0; transform: translateY(-15px); } }

        /* Fixed Centered Table Headers */
        .inspired-table th { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; color: #94a3b8; font-weight: 600; border-bottom: 1px solid #e2e8f0 !important; padding: 1rem 0.5rem; vertical-align: middle; }
        .inspired-table td { border-bottom: 1px solid #f1f5f9; padding: 1.25rem 0.5rem; vertical-align: middle; }
        .inspired-table tr:last-child td { border-bottom: none; }
        .inspired-table tr:hover td { background-color: #fafaf9; }
        
        .avatar-circle { width: 36px; height: 36px; background-color: #f1f5f9; color: #475569; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.85rem; font-weight: 600; border: 1px solid #e2e8f0; }

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
            <h1 class="fw-bold fs-2 mb-0">Tasks</h1>
            <a href="task_create.php" class="btn btn-primary shadow-sm">+ New Task</a>
        </div>

        <div class="panel-card mb-4 pb-4">
            <h6 class="fs-6 fw-semibold mb-3 text-dark">Filter Tasks</h6>
            <form method="GET" action="tasks.php" class="row g-2 align-items-end">
                <div class="col">
                    <select name="project_id" class="form-select bg-light border-0 text-secondary fw-medium" onchange="this.form.submit()">
                        <option value="">All Projects</option>
                        <?php foreach ($filterProjects as $p): ?>
                            <option value="<?= $p['id'] ?>" <?= $selectedProject == $p['id'] ? 'selected' : '' ?>><?= sanitize($p['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col">
                    <select name="assignee_id" class="form-select bg-light border-0 text-secondary fw-medium" onchange="this.form.submit()">
                        <option value="">All Assignees</option>
                        <?php foreach ($filterUsers as $u): ?>
                            <option value="<?= $u['id'] ?>" <?= $selectedAssignee == $u['id'] ? 'selected' : '' ?>><?= sanitize($u['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col">
                    <select name="priority" class="form-select bg-light border-0 text-secondary fw-medium" onchange="this.form.submit()">
                        <option value="">Any Priority</option>
                        <option value="Low" <?= $selectedPriority == 'Low' ? 'selected' : '' ?>>Low</option>
                        <option value="Medium" <?= $selectedPriority == 'Medium' ? 'selected' : '' ?>>Medium</option>
                        <option value="High" <?= $selectedPriority == 'High' ? 'selected' : '' ?>>High</option>
                    </select>
                </div>
                <div class="col">
                    <select name="status" class="form-select bg-light border-0 text-secondary fw-medium" onchange="this.form.submit()">
                        <option value="">Any Status</option>
                        <option value="Pending" <?= $selectedStatus == 'Pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="In Progress" <?= $selectedStatus == 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                        <option value="Completed" <?= $selectedStatus == 'Completed' ? 'selected' : '' ?>>Completed</option>
                    </select>
                </div>
                <div class="col">
                    <input type="date" name="due_date" class="form-select bg-light border-0 text-secondary fw-medium" value="<?= sanitize($selectedDueDate) ?>" onchange="this.form.submit()">
                </div>
                <div class="col-auto">
                    <a href="tasks.php" class="btn btn-outline-secondary w-100 fw-medium">Clear</a>
                </div>
            </form>
        </div>

        <div class="panel-card p-0 overflow-hidden">
            <div class="table-responsive">
                <table class="table inspired-table mb-0 border-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Title</th>
                            <th>Status</th>
                            <th>Assignee</th>
                            <th>Priority</th>
                            <th>Due Date</th>
                            <th class="text-end pe-4"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($tasks)): ?>
                            <tr><td colspan="6" class="text-center py-5 text-muted border-0">No tasks found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($tasks as $task): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-semibold text-dark mb-1" style="font-size: 0.95rem;"><?= sanitize($task['title']) ?></div>
                                        <div class="text-muted" style="font-size: 0.8rem;">Project: <?= sanitize($task['project_name']) ?></div>
                                    </td>
                                    <td>
                                        <?php if($task['status'] === 'Completed'): ?>
                                            <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3 py-2 fw-medium">Completed</span>
                                        <?php elseif($task['status'] === 'In Progress'): ?>
                                            <span class="badge bg-warning bg-opacity-10 text-warning rounded-pill px-3 py-2 fw-medium">In Progress</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary bg-opacity-10 text-secondary rounded-pill px-3 py-2 fw-medium">Pending</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-circle me-3"><?= getInitials(sanitize($task['assignee_name'])) ?></div>
                                            <span class="text-dark fw-medium" style="font-size: 0.9rem;"><?= sanitize($task['assignee_name']) ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if($task['priority'] === 'High'): ?>
                                            <span class="badge bg-danger bg-opacity-10 text-danger rounded-pill px-3 py-2 fw-medium">High</span>
                                        <?php elseif($task['priority'] === 'Medium'): ?>
                                            <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-3 py-2 fw-medium">Medium</span>
                                        <?php else: ?>
                                            <span class="badge bg-info bg-opacity-10 text-info rounded-pill px-3 py-2 fw-medium">Low</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="text-muted fw-medium" style="font-size: 0.85rem;"><?= sanitize($task['due_date'] ?? 'N/A') ?></span></td>
                                        <!-- Update the action column to include View and Edit -->
                                        <td class="text-end">
                                            <a href="task_details.php?id=<?= (int)$task['id'] ?>" class="btn btn-sm btn-outline-primary me-2">View</a>
                                            <a href="task_edit.php?id=<?= (int)$task['id'] ?>" class="btn btn-sm btn-outline-secondary">Edit</a>
                                        </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
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