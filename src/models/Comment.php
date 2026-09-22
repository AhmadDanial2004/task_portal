<?php

class Comment { //comment class handles operations related to comments in the application, such as retrieving and adding comments associated with tasks
    private PDO $pdo; //private property $pdo of type PDO, used for database interactions

    public function __construct(PDO $pdo) { 
        $this->pdo = $pdo; //constructor method initializes the Comment class with a PDO instance for database operations
    }

    public function getTaskComments(int $taskId): array { //return type array, method retrieves comments associated with a specific task from the database, including the comment text and the name of the user who made the comment
        $stmt = $this->pdo->prepare(//prepare SQL statement to select comments and associated user names for a specific task, ordered by creation date
            "SELECT c.*, u.name as user_name
             FROM comments c 
             JOIN users u ON c.user_id = u.id 
             WHERE c.task_id = ? 
             ORDER BY c.created_at ASC"
        );
        $stmt->execute([$taskId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addComment(int $taskId, int $userId, string $comment): bool { //return type bool, method adds a new comment to the database for a specific task and user, returning true if the insertion was successful and false otherwise
        $stmt = $this->pdo->prepare(
            "INSERT INTO comments (task_id, user_id, comment) VALUES (?, ?, ?)"
        );
        return $stmt->execute([$taskId, $userId, $comment]);
    }
}