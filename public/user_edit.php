<?php
require_once '../src/helpers/auth.php';
require_once '../src/config/database.php';
require_once '../src/helpers/validation.php';
require_once '../src/helpers/csrf.php';

// Enforce admin-only access and verify login session (FR-13)[cite: 2]
requireRole(['Administrator']);

$error = '';
$warning = '';
$success = '';

// Generate CSRF Token
$csrfToken = generateCsrfToken();

// Validate user ID parameter
$userIdToEdit = $_GET['id'] ?? null;
if (!$userIdToEdit || !is_numeric($userIdToEdit)) {
    header('Location: users.php');
    exit;
}
$userIdToEdit = (int) $userIdToEdit;

// Fetch target user record[cite: 1, 2]
$stmt = $pdo->prepare("SELECT id, name, email, role, status FROM users WHERE id = ?");
$stmt->execute([$userIdToEdit]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header('Location: users.php?error=' . urlencode('User not found.'));
    exit;
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $error = "Invalid request token. Please refresh the page and try again.";
    } else {
        $name   = trim($_POST['name'] ?? '');
        $email  = trim($_POST['email'] ?? '');
        $role   = $_POST['role'] ?? '';
        $status = $_POST['status'] ?? '';

        $allowedRoles = ['Administrator', 'Team Member'];
        $allowedStatuses = ['Active', 'Inactive'];

        if (empty($name) || empty($email)) {
            $error = "Full Name and Email Address are required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Please enter a valid email address.";
        } elseif (!in_array($role, $allowedRoles, true)) {
            $error = "Invalid role selected.";
        } elseif (!in_array($status, $allowedStatuses, true)) {
            $error = "Invalid status selected.";
        } elseif (
            $name === $user['name'] &&
            $email === $user['email'] &&
            $role === $user['role'] &&
            $status === $user['status']
        ) {
            $warning = "No changes detected. User information remains unchanged.";
        } else {
            $oldRole = $user['role'];

            try {
                $updateStmt = $pdo->prepare("
                    UPDATE users 
                    SET name = ?, email = ?, role = ?, status = ? 
                    WHERE id = ?
                ");
                
                if ($updateStmt->execute([$name, $email, $role, $status, $userIdToEdit])) {
                    if ($oldRole !== $role) {
                        $success = "User updated successfully. Role changed from '{$oldRole}' to '{$role}'.";
                    } else {
                        $success = "User information updated successfully.";
                    }

                    // Refresh user data for display
                    $stmt->execute([$userIdToEdit]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                } else {
                    $error = "Failed to update user.";
                }
            } catch (PDOException $e) {
                $error = "Database error: Email address may already be in use by another account.";
                error_log($e->getMessage());
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User - Task Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,1,0" />
    <link href="style.css" rel="stylesheet">
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <!-- Single Sidebar -->
        <div class="col-md-2 sidebar p-3 d-none d-md-block">
            <div class="d-flex align-items-center mb-4 px-3 mt-2">
                <div class="rounded-circle bg-secondary me-2 flex-shrink-0" style="width: 40px; height: 40px;"></div>
                <div>
                    <h6 class="mb-0 text-white"><?= sanitize($_SESSION['name'] ?? 'User') ?></h6>
                    <small class="text-muted" style="font-size: 0.75rem;"><?= sanitize($_SESSION['role'] ?? '') ?></small>
                </div>
            </div>
            <nav class="nav flex-column">
                <a class="nav-link" href="dashboard.php">Home</a>
                <a class="nav-link" href="tasks.php">My Tasks</a>
                <a class="nav-link" href="projects.php">Projects</a>
                <a class="nav-link active" href="users.php">Users</a>
                <hr class="my-4" style="border-color: var(--dashbrd-border);">
                <a class="nav-link mt-2 text-danger" href="logout.php">Logout</a>
            </nav>
        </div>

        <!-- Single Main Content Area -->
        <div class="col-md-10 p-4 pt-5 px-lg-5">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="fw-bold">Edit User: <?= sanitize($user['name']) ?></h3>
                <a href="users.php" class="btn btn-outline-secondary">Back to Users</a>
            </div>

            <div class="theme-card p-4 shadow-sm" style="max-width: 800px;">
                <?php if ($success): ?>
                    <div class="alert alert-success bg-success text-white border-0 mb-4"><?= sanitize($success) ?></div>
                <?php endif; ?>
                <?php if ($warning): ?>
                    <div class="alert alert-warning border-0 mb-4"><?= sanitize($warning) ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger rounded-3 p-3 mb-4"><?= sanitize($error) ?></div>
                <?php endif; ?>

                <form action="user_edit.php?id=<?= (int)$user['id'] ?>" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= sanitize($csrfToken) ?>">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Full Name</label>
                            <input type="text" name="name" class="form-control" value="<?= sanitize($user['name']) ?>" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Email Address</label>
                            <input type="email" name="email" class="form-control" value="<?= sanitize($user['email']) ?>" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <label class="form-label text-muted">Role</label>
                            <select name="role" id="roleSelect" class="form-select" data-original-role="<?= sanitize($user['role']) ?>" required>
                                <option value="Team Member" <?= $user['role'] === 'Team Member' ? 'selected' : '' ?>>Team Member</option>
                                <option value="Administrator" <?= $user['role'] === 'Administrator' ? 'selected' : '' ?>>Administrator</option>
                            </select>
                            <div id="roleFeedback" class="form-text mt-1" style="font-size: 0.82rem;"></div>
                        </div>

                        <div class="col-md-6 mb-4">
                            <label class="form-label text-muted">Status</label>
                            <select name="status" class="form-select" required>
                                <option value="Active" <?= $user['status'] === 'Active' ? 'selected' : '' ?>>Active</option>
                                <option value="Inactive" <?= $user['status'] === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                            </select>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary px-4 py-2">Update User</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const roleSelect = document.getElementById('roleSelect');
        const roleFeedback = document.getElementById('roleFeedback');
        const originalRole = roleSelect.dataset.originalRole;

        function updateRoleFeedback() {
            const currentSelected = roleSelect.value;
            if (currentSelected === originalRole) {
                roleFeedback.textContent = `Current role retained: ${originalRole}.`;
                roleFeedback.className = 'form-text mt-1 text-muted';
            } else {
                roleFeedback.textContent = `Role will change from "${originalRole}" to "${currentSelected}" upon saving.`;
                roleFeedback.className = 'form-text mt-1 text-primary fw-medium';
            }
        }

        roleSelect.addEventListener('change', updateRoleFeedback);
        updateRoleFeedback();
    });
</script>
</body>
</html>