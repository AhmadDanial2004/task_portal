<?php
require_once '../src/helpers/auth.php';
require_once '../src/config/database.php';
require_once '../src/helpers/validation.php';

requireLogin();

// Enforce access control: Only Administrators can update projects
if ($_SESSION['role'] !== 'Administrator') {
    header('Location: projects.php?error=Unauthorized action');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $status = $_POST['status'] ?? 'Active';

    if ($id && is_numeric($id) && !empty($name)) {
        try {
            // Secure update query using PDO prepared statements
            $stmt = $pdo->prepare("UPDATE projects SET name = ?, description = ?, status = ? WHERE id = ?");
            $stmt->execute([$name, $description, $status, (int)$id]);

            header('Location: projects.php?success=Project updated successfully');
            exit;
        } catch (Exception $e) {
            $errorMsg = urlencode($e->getMessage());
            header("Location: project_edit.php?id={$id}&error=Failed to update project: {$errorMsg}");
            exit;
        }
    } else {
        header("Location: project_edit.php?id={$id}&error=Project name is required");
        exit;
    }
}

header('Location: projects.php');
exit;