<?php
require_once '../src/helpers/auth.php';
require_once '../src/config/database.php';
require_once '../src/helpers/validation.php';

requireLogin();

// Access Control: Only Admins can manage project members
if ($_SESSION['role'] !== 'Administrator') {
    header("Location: dashboard.php?error=Access Denied");
    exit;
}

$isAdmin = true;
$error = '';
$success = '';

$projectId = $_GET['id'] ?? null;
if (!$projectId) {
    header('Location: projects.php');
    exit;
}

// 1. Fetch Project Details securely
$stmt = $pdo->prepare("SELECT id, name FROM projects WHERE id = ?");
$stmt->execute([$projectId]);
$project = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$project) {
    die("Project not found.");
}

// 2. Handle Add/Remove Actions (FR-05)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $userIdToAdd = $_POST['user_id'] ?? '';
        if (empty($userIdToAdd)) {
            $error = "Please select a user to add.";
        } else {
            try {
                // Check if user is already in the project
                $checkStmt = $pdo->prepare("SELECT * FROM project_users WHERE project_id = ? AND user_id = ?");
                $checkStmt->execute([$projectId, $userIdToAdd]);
                
                if ($checkStmt->rowCount() > 0) {
                    $error = "User is already a member of this project.";
                } else {
                    $addStmt = $pdo->prepare("INSERT INTO project_users (project_id, user_id) VALUES (?, ?)");
                    if ($addStmt->execute([$projectId, $userIdToAdd])) {
                        $success = "Member added successfully.";
                    } else {
                        $error = "Failed to add member.";
                    }
                }
            } catch (PDOException $e) {
                $error = "Database error occurred.";
                error_log($e->getMessage());
            }
        }
    } elseif ($action === 'remove') {
        $userIdToRemove = $_POST['user_id'] ?? '';
        if (!empty($userIdToRemove)) {
            try {
                $remStmt = $pdo->prepare("DELETE FROM project_users WHERE project_id = ? AND user_id = ?");
                if ($remStmt->execute([$projectId, $userIdToRemove])) {
                    $success = "Member removed successfully.";
                } else {
                    $error = "Failed to remove member.";
                }
            } catch (PDOException $e) {
                $error = "Database error occurred.";
                error_log($e->getMessage());
            }
        }
    }
}

// 3. Fetch current members and available users for the dropdown
$membersStmt = $pdo->prepare("
    SELECT u.id, u.name, u.email, u.role 
    FROM users u 
    JOIN project_users pu ON u.id = pu.user_id 
    WHERE pu.project_id = ?
");
$membersStmt->execute([$projectId]);
$members = $membersStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all active users who are NOT currently in the project
$availableUsersStmt = $pdo->prepare("
    SELECT id, name, email 
    FROM users 
    WHERE status = 'Active' 
    AND id NOT IN (SELECT user_id FROM project_users WHERE project_id = ?)
");
$availableUsersStmt->execute([$projectId]);
$availableUsers = $availableUsersStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Members - Task Portal</title>
    <!-- Satisfies responsive layout requirement -->
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
                <div class="rounded-circle bg-secondary me-2" style="width: 40px; height: 40px;"></div>
                <div>
                    <h6 class="mb-0 text-white"><?= sanitize($_SESSION['name']) ?></h6>
                    <small class="text-muted" style="font-size: 0.75rem;"><?= sanitize($_SESSION['role']) ?></small>
                </div>
            </div>
            <nav class="nav flex-column">
                <a class="nav-link" href="dashboard.php">Home</a>
                <a class="nav-link" href="tasks.php">My Tasks</a>
                <a class="nav-link active" href="projects.php">Projects</a>
                <a class="nav-link" href="users.php">Users</a>
                <hr class="my-4" style="border-color: var(--dashbrd-border);">
                <div class="nav-item dropdown px-3 mb-2">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="material-symbols-outlined me-2 fs-6">palette</span> Theme
                    </a>
                    <ul class="dropdown-menu shadow">
                        <li><a class="dropdown-item d-flex align-items-center" href="#" onclick="setTheme('light')"><span class="material-symbols-outlined me-2 fs-6">light_mode</span> Light</a></li>
                        <li><a class="dropdown-item d-flex align-items-center" href="#" onclick="setTheme('dark')"><span class="material-symbols-outlined me-2 fs-6">dark_mode</span> Dark</a></li>
                    </ul>
                </div>
                <a class="nav-link mt-2 text-danger" href="logout.php">Logout</a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="col-md-10 p-4 pt-5 px-lg-5">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="fw-bold">Members for: <?= sanitize($project['name']) ?></h3>
                <a href="projects.php" class="btn btn-outline-secondary">Back to Projects</a>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success bg-success text-white border-0" style="max-width: 800px;"><?= sanitize($success) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger rounded-3 p-2 mb-4" style="max-width: 800px;"><?= sanitize($error) ?></div>
            <?php endif; ?>

            <!-- Add New Member Form -->
            <div class="theme-card p-4 shadow-sm mb-4" style="max-width: 800px;">
                <h6 class="mb-3">Add New Member</h6>
                <form action="project_members.php?id=<?= $project['id'] ?>" method="POST" class="row g-2 align-items-end">
                    <input type="hidden" name="action" value="add">
                    <div class="col-md-8">
                        <select name="user_id" class="form-select" required>
                            <option value="">-- Select a User --</option>
                            <?php foreach ($availableUsers as $user): ?>
                                <option value="<?= $user['id'] ?>"><?= sanitize($user['name']) ?> (<?= sanitize($user['email']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary w-100">Add to Project</button>
                    </div>
                </form>
            </div>

            <!-- Current Members Table -->
            <div class="theme-card p-4 shadow-sm" style="max-width: 800px;">
                <h6 class="mb-3">Current Assigned Members</h6>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($members)): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">No members assigned to this project.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($members as $member): ?>
                                    <tr>
                                        <td class="fw-medium text-white"><?= sanitize($member['name']) ?></td>
                                        <td class="text-secondary"><?= sanitize($member['email']) ?></td>
                                        <td class="text-secondary"><?= sanitize($member['role']) ?></td>
                                        <td>
                                            <form action="project_members.php?id=<?= $project['id'] ?>" method="POST" style="display:inline;">
                                                <input type="hidden" name="action" value="remove">
                                                <input type="hidden" name="user_id" value="<?= $member['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Remove this member from the project?');">Remove</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Theme Switcher Logic
    const currentTheme = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-bs-theme', currentTheme);
    function setTheme(theme) {
        document.documentElement.setAttribute('data-bs-theme', theme);
        localStorage.setItem('theme', theme);
    }
</script>
</body>
</html>