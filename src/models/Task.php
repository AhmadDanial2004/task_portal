<?php

class Task {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function getTasks(int $userId, bool $isAdmin, array $filters = []): array {
        $sql = "SELECT t.*, p.name as project_name, u.name as assignee_name 
                FROM tasks t 
                JOIN projects p ON t.project_id = p.id 
                JOIN users u ON t.assigned_user_id = u.id 
                WHERE 1=1";
        $params = [];

        // Role restriction
        if (!$isAdmin) {
            $sql .= " AND (t.assigned_user_id = ? OR t.created_by = ?)";
            $params[] = $userId;
            $params[] = $userId;
        }

        // Apply all 5 required filters (FR-11)
        if (!empty($filters['status'])) {
            $sql .= " AND t.status = ?";
            $params[] = $filters['status'];
        }
        if (!empty($filters['priority'])) {
            $sql .= " AND t.priority = ?";
            $params[] = $filters['priority'];
        }
        if (!empty($filters['project_id'])) {
            $sql .= " AND t.project_id = ?";
            $params[] = $filters['project_id'];
        }
        if (!empty($filters['assigned_user_id'])) {
            $sql .= " AND t.assigned_user_id = ?";
            $params[] = $filters['assigned_user_id'];
        }
        if (!empty($filters['due_date'])) {
            $sql .= " AND t.due_date = ?";
            $params[] = $filters['due_date'];
        }

        $sql .= " ORDER BY t.created_at DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createTask(int $projectId, int $assignedTo, int $createdBy, string $title, string $description, string $priority, string $dueDate): bool {
        $stmt = $this->pdo->prepare(
            "INSERT INTO tasks (project_id, assigned_user_id, created_by, title, description, priority, due_date, status) 
             VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending')"
        );
        return $stmt->execute([$projectId, $assignedTo, $createdBy, $title, $description, $priority, $dueDate]);
    }

    public function getTaskById(int $id): ?array {
        $stmt = $this->pdo->prepare(
            "SELECT t.*, p.name as project_name, u.name as assignee_name 
             FROM tasks t 
             JOIN projects p ON t.project_id = p.id 
             JOIN users u ON t.assigned_user_id = u.id 
             WHERE t.id = ?"
        );
        $stmt->execute([$id]);
        $task = $stmt->fetch(PDO::FETCH_ASSOC);
        return $task ?: null;
    }

    public function updateTask(int $id, string $title, string $description, string $priority, string $status, ?string $dueDate): bool {
        $stmt = $this->pdo->prepare(
            "UPDATE tasks SET title = ?, description = ?, priority = ?, status = ?, due_date = ? WHERE id = ?"
        );
        return $stmt->execute([$title, $description, $priority, $status, $dueDate, $id]);
    }

    public function deleteTask(int $id): bool {
        $stmt = $this->pdo->prepare("DELETE FROM tasks WHERE id = ?");
        return $stmt->execute([$id]);
    }
}