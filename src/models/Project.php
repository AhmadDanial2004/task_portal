<?php

class Project { //project class - handles operations related to projects in the application
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function getAllProjects(): array {
        $stmt = $this->pdo->query(
            "SELECT p.*, COUNT(pu.user_id) AS member_count 
             FROM projects p 
             LEFT JOIN project_users pu ON p.id = pu.project_id 
             GROUP BY p.id 
             ORDER BY p.created_at DESC"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getProjectById(int $id): ?array {
        $stmt = $this->pdo->prepare("SELECT * FROM projects WHERE id = ?");
        $stmt->execute([$id]);
        $project = $stmt->fetch(PDO::FETCH_ASSOC);
        return $project ?: null;
    }

    public function createProject(string $name, string $description, string $status): bool {
        $stmt = $this->pdo->prepare(
            "INSERT INTO projects (name, description, status) VALUES (?, ?, ?)"
        );
        return $stmt->execute([$name, $description, $status]);
    }

    public function updateProject(int $id, string $name, string $description, string $status): bool {
        $stmt = $this->pdo->prepare(
            "UPDATE projects SET name = ?, description = ?, status = ? WHERE id = ?"
        );
        return $stmt->execute([$name, $description, $status, $id]);
    }

    public function getProjectMembers(int $projectId): array {
        $stmt = $this->pdo->prepare(
            "SELECT u.id, u.name, u.email 
             FROM users u 
             JOIN project_users pu ON u.id = pu.user_id 
             WHERE pu.project_id = ?"
        );
        $stmt->execute([$projectId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function assignUser(int $projectId, int $userId): bool {
        $stmt = $this->pdo->prepare(
            "INSERT IGNORE INTO project_users (project_id, user_id) VALUES (?, ?)"
        );
        return $stmt->execute([$projectId, $userId]);
    }

    public function removeUser(int $projectId, int $userId): bool {
        $stmt = $this->pdo->prepare(
            "DELETE FROM project_users WHERE project_id = ? AND user_id = ?"
        );
        return $stmt->execute([$projectId, $userId]);
    }

    public function deleteProject(int $id): bool {
        $stmt = $this->pdo->prepare("DELETE FROM projects WHERE id = ?");
        return $stmt->execute([$id]);
    }
}