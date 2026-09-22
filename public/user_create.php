<?php
require_once '../src/helpers/auth.php';
require_once '../src/config/database.php';
require_once '../src/helpers/validation.php';
require_once '../src/helpers/csrf.php';

requireRole(['Administrator']);
$isAdmin = true;

$error = '';
$name = '';
$email = '';
$role = 'Team Member';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $error = "Invalid request token. Please refresh and try again.";
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = $_POST['role'] ?? 'Team Member';
        $password = $_POST['password'] ?? '';

        if (empty($name) || empty($email) || empty($password)) {
            $error = "All fields are required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Please enter a valid email address.";
        } elseif (!in_array($role, ['Administrator', 'Team Member'], true)) {
            $error = "Invalid role selected.";
        } else {
            // Check if email already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = "A user with this email already exists.";
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $insert = $pdo->prepare("
                    INSERT INTO users (name, email, password, role, status)
                    VALUES (?, ?, ?, ?, 'Active')
                ");
                $insert->execute([$name, $email, $hash, $role]);
                header('Location: users.php?success=' . urlencode('User created successfully.'));
                exit;
            }
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
    <title>Add New User - Task Portal</title>
    
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
            <a class="nav-link" href="tasks.php"><span class="material-symbols-outlined">list_alt</span> My Tasks</a>
            <a class="nav-link" href="projects.php"><span class="material-symbols-outlined">folder</span> Projects</a>
            <a class="nav-link active" href="users.php"><span class="material-symbols-outlined">group</span> Users</a>
        </nav>
        <div class="mt-auto">
            <hr style="border-color: #e2e8f0;">
            <a class="nav-link text-danger" href="logout.php"><span class="material-symbols-outlined">logout</span> Sign Out</a>
        </div>
    </aside>

    <main class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="fw-bold fs-2 mb-1">Add New User</h1>
                <p class="text-muted mb-0" style="font-size: 0.9rem;">Provision a new Administrator or Team Member account.</p>
            </div>
            <a href="users.php" class="btn btn-outline-secondary shadow-sm">Back to Users</a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger border-0 bg-danger bg-opacity-10 text-danger rounded-3 mb-4" style="max-width: 850px;">
                <?= sanitize($error) ?>
            </div>
        <?php endif; ?>

        <div class="panel-card" style="max-width: 850px;">
            <form action="user_create.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= sanitize($csrfToken) ?>">

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-medium text-dark" style="font-size: 0.9rem;">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control bg-light border-0" value="<?= sanitize($name) ?>" placeholder="e.g. John Doe" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-medium text-dark" style="font-size: 0.9rem;">Email Address <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control bg-light border-0" value="<?= sanitize($email) ?>" placeholder="e.g. user@example.test" required>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-medium text-dark" style="font-size: 0.9rem;">Role <span class="text-danger">*</span></label>
                        <select name="role" class="form-select bg-light border-0" required>
                            <option value="Team Member" <?= $role === 'Team Member' ? 'selected' : '' ?>>Team Member</option>
                            <option value="Administrator" <?= $role === 'Administrator' ? 'selected' : '' ?>>Administrator</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-medium text-dark" style="font-size: 0.9rem;">Temporary Password <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control bg-light border-0" placeholder="••••••••••••" required>
                    </div>
                </div>

                <div class="pt-2 border-top">
                    <button type="submit" class="btn btn-primary px-4 shadow-sm">Save User</button>
                </div>
            </form>
        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>