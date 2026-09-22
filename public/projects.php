<?php
require_once '../src/helpers/auth.php';
require_once '../src/config/database.php';
require_once '../src/helpers/validation.php';

requireRole(['Administrator']);

requireLogin();
$userId = $_SESSION['user_id'];
$isAdmin = $_SESSION['role'] === 'Administrator';

// Fetch projects based on role
if ($isAdmin) {
    $stmt = $pdo->query("SELECT * FROM projects ORDER BY created_at DESC");
} else {
    $stmt = $pdo->prepare("
        SELECT p.* 
        FROM projects p 
        JOIN project_users pu ON p.id = pu.project_id 
        WHERE pu.user_id = ? 
        ORDER BY p.created_at DESC
    ");
    $stmt->execute([$userId]);
}
$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Projects - Task Portal</title>
    
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
            <?php if ($isAdmin): ?>
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
            <h1 class="fw-bold fs-2 mb-0">Project Management</h1>
            <?php if ($isAdmin): ?>
                <a href="project_create.php" class="btn btn-primary shadow-sm">+ New Project</a>
            <?php endif; ?>
        </div>

        <div class="panel-card p-0 overflow-hidden">
            <div class="table-responsive">
                <table class="table inspired-table mb-0 border-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Project Name</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($projects)): ?>
                            <tr><td colspan="4" class="text-center py-5 text-muted border-0">No projects found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($projects as $project): ?>
                                <tr>
                                    <td class="ps-4 fw-semibold text-dark" style="font-size: 0.95rem;"><?= sanitize($project['name']) ?></td>
                                    <td class="text-muted" style="font-size: 0.9rem;"><?= sanitize($project['description']) ?></td>
                                    <td>
                                        <?php if($project['status'] === 'Active'): ?>
                                            <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3 py-2 fw-medium">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary bg-opacity-10 text-secondary rounded-pill px-3 py-2 fw-medium">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end pe-4">
                                        <?php if ($isAdmin): ?>
                                            <a href="project_edit.php?id=<?= $project['id'] ?>" class="btn btn-sm btn-link text-primary fw-medium text-decoration-none">Edit</a>
                                        <?php endif; ?>
                                        
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