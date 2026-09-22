<?php
require_once '../src/config/database.php';

// Section 10.4 Official Demonstration Accounts
$users = [
    'admin@example.test'   => ['name' => 'Admin User', 'role' => 'Administrator', 'password' => 'InternDemo!2026'],
    'member1@example.test' => ['name' => 'Member One', 'role' => 'Team Member',    'password' => 'InternDemo!2026'],
    'member2@example.test' => ['name' => 'Member Two', 'role' => 'Team Member',    'password' => 'InternDemo!2026'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Account Password & Hash Generator</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light p-4">
<div class="container" style="max-width: 800px;">
    <div class="card shadow-sm p-4 mb-4">
        <h3 class="fw-bold mb-3">Sync Demonstration Accounts</h3>
        <p class="text-muted">Applies <code>PASSWORD_BCRYPT</code> hashes for Section 10.4 test accounts directly into MySQL.</p>
        <hr>

        <div class="list-group mb-4">
        <?php
        foreach ($users as $email => $data) {
            $hash = password_hash($data['password'], PASSWORD_BCRYPT);

            $stmt = $pdo->prepare("
                INSERT INTO users (name, email, password, role, status)
                VALUES (?, ?, ?, ?, 'Active')
                ON DUPLICATE KEY UPDATE 
                    name = VALUES(name),
                    password = VALUES(password),
                    role = VALUES(role),
                    status = 'Active'
            ");
            $stmt->execute([$data['name'], $email, $hash, $data['role']]);
            ?>
            <div class="list-group-item p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="mb-1 fw-bold"><?= htmlspecialchars($data['name']) ?> (<?= htmlspecialchars($email) ?>)</h6>
                    <span class="badge <?= $data['role'] === 'Administrator' ? 'bg-primary' : 'bg-secondary' ?>"><?= htmlspecialchars($data['role']) ?></span>
                </div>
                <div class="small mt-1 text-secondary">
                    Password: <code class="text-dark fw-bold"><?= htmlspecialchars($data['password']) ?></code>
                </div>
                <div class="small mt-1 text-muted text-break">
                    Hash: <code><?= htmlspecialchars($hash) ?></code>
                </div>
            </div>
        <?php } ?>
        </div>

        <div class="alert alert-success d-flex justify-content-between align-items-center">
            <span>Sample accounts updated successfully.</span>
            <a href="login.php" class="btn btn-success btn-sm">Proceed to Login</a>
        </div>
    </div>
</div>
</body>
</html>