<?php

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

session_start();

require_once '../src/config/database.php';

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

$stmt = $pdo->prepare(
    "SELECT *
     FROM users
     WHERE email = ?
       AND status = 'Active'"
);

$stmt->execute([$email]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (
    $user &&
    password_verify(
        $password,
        $user['password']
    )
) {

    session_regenerate_id(true);

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['name'] = $user['name'];
    $_SESSION['role'] = $user['role'];

    header('Location: dashboard.php');
    exit;
}

header('Location: login.php?error=1');
exit;