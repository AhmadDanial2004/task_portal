<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start(); // Session used for user authentication and access control
}

// Generates a CSRF token and stores it in the session for protection against CSRF attacks
function generateCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        // Generate a random 32-byte token and convert it to hexadecimal representation
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verifies the submitted token against the session token
function verifyCsrfToken(?string $token): bool {
    // If either token is missing or empty, fail immediately
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }

    // Compare tokens safely against timing attacks
    return hash_equals($_SESSION['csrf_token'], $token);
}