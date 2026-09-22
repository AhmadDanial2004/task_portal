<?php

class User {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function getAllUsers(): array {
        $stmt = $this->pdo->query(
            "SELECT id, name, email, role, status, created_at 
             FROM users 
             ORDER BY created_at DESC"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function createUser(string $name, string $email, string $password, string $role): bool {
        // Passwords must be stored as secure hashes and never as plain text
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $this->pdo->prepare(
            "INSERT INTO users (name, email, password, role, status) 
             VALUES (?, ?, ?, ?, 'Active')"
        );
        
        return $stmt->execute([$name, $email, $hashedPassword, $role]);
    }
    public function getUserById(int $id): ?array {
        $stmt = $this->pdo->prepare(
            "SELECT id, name, email, role, status 
             FROM users 
             WHERE id = ?"
        );
        $stmt->execute([$id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $user ?: null;
    }

    public function updateUser(int $id, string $role, string $status): bool {
        $stmt = $this->pdo->prepare(
            "UPDATE users 
             SET role = ?, status = ? 
             WHERE id = ?"
        );
        
        return $stmt->execute([$role, $status, $id]);
    }
}