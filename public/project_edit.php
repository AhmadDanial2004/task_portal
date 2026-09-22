<?php
require_once '../src/helpers/auth.php';
require_once '../src/config/database.php';
require_once '../src/helpers/validation.php';
require_once '../src/helpers/csrf.php';

requireRole(['Administrator']); //ensure only Administrators can access this page

requireLogin();

// Enforce access control: Only Administrators can edit projects
if ($_SESSION['role'] !== 'Administrator') {
    header('Location: projects.php');
    exit;
}

// Generate CSRF token
$csrfToken = generateCsrfToken();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: projects.php');
    exit;
}

$projectId = (int) $_GET['id'];

$stmt = $pdo->prepare(
    "SELECT id, name, description, status
     FROM projects
     WHERE id = ?"
);

$stmt->execute([$projectId]);

$project = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$project) {
    header('Location: projects.php?error=Project not found');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Project - Task Portal</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,1,0" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        :root { --bg-body: #f6f8fb; --card-bg: #ffffff; --text-main: #1e293b; --text-muted: #64748b; --brand-primary: #3b82f6; --sidebar-width: 260px; }
        body { font-family: 'Inter', sans-serif; background-color: var(--bg-body); color: var(--text-main); margin: 0; padding: 0; }
        .dashboard-container { display: flex; min-height: 100vh; }
        
        /* --- Sidebar & Layout --- */
        .sidebar { width: var(--sidebar-width); background-color: var(--card-bg); border-right: 1px solid #e2e8f0; position: fixed; height: 100vh; overflow-y: auto; padding: 1.5rem; display: flex; flex-direction: column; z-index: 10; }
        .brand-logo { font-size: 1.25rem; font-weight: 700; color: var(--text-main); text-decoration: none; display: flex; align-items: center; margin-bottom: 2.5rem; }
        .nav-link { display: flex; align-items: center; color: var(--text-muted); font-weight: 500; padding: 0.75rem 1rem; border-radius: 0.5rem; margin-bottom: 0.25rem; transition: all 0.2s; }
        .nav-link:hover, .nav-link.active { background-color: #f1f5f9; color: var(--text-main); }
        .nav-link .material-symbols-outlined { margin-right: 0.75rem; font-size: 1.25rem; }
        .main-content { flex-grow: 1; margin-left: var(--sidebar-width); padding: 2rem 3rem; animation: fadeSlideIn 0.4s cubic-bezier(0.22, 1, 0.36, 1) forwards; will-change: opacity, transform; opacity: 0; }
        
        .panel-card { background-color: var(--card-bg); border-radius: 1rem; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.05); margin-bottom: 1.5rem; padding: 1.5rem; }
        
        /* --- Smooth Transitions --- */
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
            <a class="nav-link" href="tasks.php"><span class="material-symbols-outlined">list_alt</span> My Tasks</a>
            <a class="nav-link active" href="projects.php"><span class="material-symbols-outlined">folder</span> Projects</a>
            <a class="nav-link" href="users.php"><span class="material-symbols-outlined">group</span> Users</a>
        </nav>
        <div class="mt-auto">
            <hr style="border-color: #1e505c;">
            <a class="nav-link text-danger" href="logout.php"><span class="material-symbols-outlined">logout</span> Sign Out</a>
        </div>
    </aside>

    <main class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="fw-bold fs-2 mb-0">Edit Project: <?= sanitize($project['name']) ?></h1>
            <a href="projects.php" class="btn btn-outline-secondary shadow-sm">Cancel</a>
        </div>

        <div class="panel-card" style="max-width: 800px;">
            <form action="project_update.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= sanitize($csrfToken) ?>">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" value="<?= sanitize($project['id']) ?>">

                <div class="mb-3">
                    <label class="form-label fw-medium text-dark">Project Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control bg-light border-0" value="<?= sanitize($project['name']) ?>" required>
                </div>
                
                <div class="mb-4">
                    <label class="form-label fw-medium text-dark">Description</label>
                    <textarea name="description" class="form-control bg-light border-0" rows="5"><?= sanitize($project['description']) ?></textarea>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-medium text-dark">Status</label>
                    <select name="status" class="form-select bg-light border-0 w-auto">
                        <option value="Active" <?= $project['status'] === 'Active' ? 'selected' : '' ?>>Active</option>
                        <option value="Inactive" <?= $project['status'] === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>

                <div class="d-flex justify-content-between align-items-center pt-3 border-top border-light">
                    <button type="submit" class="btn btn-primary px-4 shadow-sm">Update Project</button>
                    
                    <!-- Administrators may delete projects -->
                    <button type="button" class="btn btn-outline-danger shadow-sm" onclick="if(confirm('Are you sure you want to delete this project?')) { window.location.href='project_delete.php?id=<?= sanitize($project['id']) ?>'; }">
                        Delete Project
                    </button>
                </div>
            </form>
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
                // Prevent intercepting the "Cancel" button if it behaves as a normal link, 
                // but for standard sidebar links, trigger the transition:
                if (!this.classList.contains('nav-link')) return; 

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