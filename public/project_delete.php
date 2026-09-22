<?php
require_once '../src/helpers/auth.php';
require_once '../src/config/database.php';
require_once '../src/helpers/validation.php';

requireLogin();

// Enforce Administrator role restriction
if ($_SESSION['role'] !== 'Administrator') {
    header('Location: projects.php?error=Unauthorized action');
    exit;
}

$projectId = $_GET['id'] ?? $_POST['id'] ?? null;

if ($projectId && is_numeric($projectId)) {
    $projectId = (int)$projectId;
    
    try {
        $pdo->beginTransaction();

        // 1. Delete associated comments for tasks under this project first (if comments table exists)
        $stmtComments = $pdo->prepare("DELETE FROM comments WHERE task_id IN (SELECT id FROM tasks WHERE project_id = ?)");
        $stmtComments->execute([$projectId]);

        // 2. Remove project memberships
        $stmtMembers = $pdo->prepare("DELETE FROM project_users WHERE project_id = ?");
        $stmtMembers->execute([$projectId]);

        // 3. Remove associated tasks
        $stmtTasks = $pdo->prepare("DELETE FROM tasks WHERE project_id = ?");
        $stmtTasks->execute([$projectId]);

        // 4. Delete the project
        $stmtProject = $pdo->prepare("DELETE FROM projects WHERE id = ?");
        $stmtProject->execute([$projectId]);

        $pdo->commit();
        header('Location: projects.php?success=Project deleted successfully');
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        $errorMsg = urlencode($e->getMessage());
        header("Location: projects.php?error=Failed to delete project: {$errorMsg}");
        exit;
    }
}

header('Location: projects.php?error=Invalid project ID');
exit;