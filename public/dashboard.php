<?php
require_once '../src/helpers/auth.php';
require_once '../src/config/database.php'; 
require_once '../src/helpers/validation.php'; 

requireLogin();
$userId = $_SESSION['user_id'];
$isAdmin = $_SESSION['role'] === 'Administrator';

// 1. Fetch Project Count using PDO prepared statements
if ($isAdmin) {
    $stmt = $pdo->query("SELECT COUNT(*) FROM projects");
} else {
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT p.id) 
        FROM projects p
        JOIN project_users pu ON p.id = pu.project_id
        WHERE pu.user_id = ?
    ");
    $stmt->execute([$userId]);
}
$projectCount = $stmt->fetchColumn();

// 2. Fetch Task Status Totals
$taskQuery = $isAdmin 
    ? "SELECT status, COUNT(*) as count FROM tasks GROUP BY status"
    : "SELECT status, COUNT(*) as count FROM tasks WHERE assigned_user_id = ? GROUP BY status";

$stmt = $isAdmin ? $pdo->query($taskQuery) : $pdo->prepare($taskQuery);
if (!$isAdmin) $stmt->execute([$userId]);

$taskCounts = ['Pending' => 0, 'In Progress' => 0, 'Completed' => 0];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $taskCounts[$row['status']] = $row['count'];
}

// 3. Fetch Recent Activity (Last 5 updated tasks)
$recentQuery = $isAdmin
    ? "SELECT title, status, updated_at FROM tasks ORDER BY updated_at DESC LIMIT 5"
    : "SELECT title, status, updated_at FROM tasks WHERE assigned_user_id = ? ORDER BY updated_at DESC LIMIT 5";

$stmt = $isAdmin ? $pdo->query($recentQuery) : $pdo->prepare($recentQuery);
if (!$isAdmin) $stmt->execute([$userId]);
$recentTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Task Portal</title>
    
    <!-- Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,1,0" />
    
    <!-- Bootstrap 5 for responsive layout -->
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

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-body);
            color: var(--text-main);
            margin: 0;
            padding: 0;
        }

        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        /* --- Sleek Sidenav --- */
        .sidebar {
            width: var(--sidebar-width);
            background-color: var(--card-bg);
            border-right: 1px solid #e2e8f0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            z-index: 10;
        }

        .brand-logo {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-main);
            text-decoration: none;
            display: flex;
            align-items: center;
            margin-bottom: 2.5rem;
        }

        .nav-link {
            display: flex;
            align-items: center;
            color: var(--text-muted);
            font-weight: 500;
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            margin-bottom: 0.25rem;
            transition: all 0.2s;
        }

        .nav-link:hover, .nav-link.active {
            background-color: #f1f5f9;
            color: var(--text-main);
        }

        .nav-link .material-symbols-outlined {
            margin-right: 0.75rem;
            font-size: 1.25rem;
        }

        /* --- Main Content Area --- */
        .main-content {
            flex-grow: 1;
            margin-left: var(--sidebar-width);
            padding: 2rem 3rem;
        }

        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        /* --- Stat Cards --- */
        .stat-card {
            background-color: var(--card-bg);
            border-radius: 1rem;
            border: 1px solid #e2e8f0;
            padding: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            height: 100%;
        }

        .stat-title {
            font-size: 0.875rem;
            color: var(--text-muted);
            margin-bottom: 0.25rem;
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-main);
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .bg-blue-light { background-color: #dbeafe; color: #2563eb; }
        .bg-yellow-light { background-color: #fef3c7; color: #d97706; }
        .bg-green-light { background-color: #dcfce3; color: #16a34a; }
        .bg-purple-light { background-color: #f3e8ff; color: #9333ea; }

        /* --- Panels --- */
        .panel-card {
            background-color: var(--card-bg);
            border-radius: 1rem;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            margin-bottom: 1.5rem;
            padding: 1.5rem;
        }

        .panel-title {
            font-size: 1.125rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .activity-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px dashed #e2e8f0;
        }

        .activity-item:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }

        @media (max-width: 991px) {
            .sidebar { transform: translateX(-100%); transition: transform 0.3s; }
            .main-content { margin-left: 0; padding: 1.5rem; }
        }
    </style>
</head>
<body>

<div class="dashboard-container">
    
    <!-- Sidebar Navigation -->
    <aside class="sidebar">
        <a href="dashboard.php" class="brand-logo">Task Portal</a>
        
        <div class="text-muted fs-6 fw-semibold mb-2 px-3" style="font-size: 0.75rem;">Main</div>
        <nav class="nav flex-column mb-4">
            <a class="nav-link active" href="dashboard.php"><span class="material-symbols-outlined">space_dashboard</span> Default</a>
            <a class="nav-link" href="tasks.php"><span class="material-symbols-outlined">list_alt</span> My Tasks</a>
            <?php if ($isAdmin): ?>
                <a class="nav-link" href="projects.php"><span class="material-symbols-outlined">folder</span> Projects</a>
                <a class="nav-link" href="users.php"><span class="material-symbols-outlined">group</span> Users</a>
            <?php endif; ?>
        </nav>

        <div class="mt-auto">
            <hr style="border-color: #e2e8f0;">
            <a class="nav-link text-danger" href="logout.php">
                <span class="material-symbols-outlined">logout</span> Sign Out
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        
        <!-- Header -->
        <div class="header-top">
            <div>
                <h1 class="fw-bold fs-2 mb-1">Hello, <?= sanitize($_SESSION['name']) ?></h1>
                <p class="text-muted mb-0">Here's a summary of your team's activity for this week.</p>
            </div>
            <div class="d-none d-md-flex align-items-center bg-white border rounded-pill px-3 py-2 shadow-sm">
                <span class="material-symbols-outlined text-primary me-2">public</span>
                <span class="text-muted fw-medium fs-6"><?= sanitize($_SESSION['role']) ?></span>
            </div>
        </div>

        <!-- 4 Stat Cards -->
        <div class="row g-4 mb-4">
            <div class="col-12 col-md-6 col-xl-3">
                <div class="stat-card">
                    <div>
                        <div class="stat-title">Total Projects</div>
                        <div class="stat-value"><?= $projectCount ?></div>
                    </div>
                    <div class="stat-icon bg-blue-light">
                        <span class="material-symbols-outlined">folder_open</span>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <div class="stat-card">
                    <div>
                        <div class="stat-title">Pending Tasks</div>
                        <div class="stat-value"><?= $taskCounts['Pending'] ?></div>
                    </div>
                    <div class="stat-icon bg-yellow-light">
                        <span class="material-symbols-outlined">pending_actions</span>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <div class="stat-card">
                    <div>
                        <div class="stat-title">In Progress</div>
                        <div class="stat-value"><?= $taskCounts['In Progress'] ?></div>
                    </div>
                    <div class="stat-icon bg-purple-light">
                        <span class="material-symbols-outlined">trending_up</span>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <div class="stat-card">
                    <div>
                        <div class="stat-title">Completed</div>
                        <div class="stat-value"><?= $taskCounts['Completed'] ?></div>
                    </div>
                    <div class="stat-icon bg-green-light">
                        <span class="material-symbols-outlined">check_circle</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity List spanning full width -->
        <div class="row g-4">
            <div class="col-12">
                <div class="panel-card">
                    <div class="panel-title">Recent Task Activity</div>
                    
                    <?php if (empty($recentTasks)): ?>
                        <p class="text-muted text-center py-4">No recent activity found.</p>
                    <?php else: ?>
                        <?php foreach ($recentTasks as $task): ?>
                            <div class="activity-item">
                                <div class="stat-icon bg-blue-light me-3" style="width: 40px; height: 40px;">
                                    <span class="material-symbols-outlined fs-5">task</span>
                                </div>
                                <div>
                                    <div class="fw-semibold text-main mb-1"><?= sanitize($task['title']) ?></div>
                                    <div class="fs-6 text-muted mb-1">Status updated to 
                                        <?php if($task['status'] === 'Completed'): ?>
                                            <span class="text-success fw-medium">Completed</span>
                                        <?php elseif($task['status'] === 'In Progress'): ?>
                                            <span class="text-info fw-medium">In Progress</span>
                                        <?php else: ?>
                                            <span class="text-warning fw-medium">Pending</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-muted" style="font-size: 0.75rem;"><?= sanitize($task['updated_at']) ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js">
</script>
<script>
    document.addEventListener("DOMContentLoaded", () => {
        const navLinks = document.querySelectorAll('.sidebar .nav-link');
        const mainContent = document.querySelector('.main-content');

        navLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                // Ignore the logout link so it processes immediately
                if (this.getAttribute('href') === 'logout.php') return;

                // Stop the browser's hard refresh glitch
                e.preventDefault(); 
                const targetUrl = this.href;

                // Trigger the smooth exit animation
                mainContent.classList.add('fade-out');

                // Wait 300ms for the animation to finish, then load the new PHP page
                setTimeout(() => {
                    window.location.href = targetUrl;
                }, 300);
            });
        });
    });
</script>
</body>
</html>