<?php

require_once __DIR__ . '/../src/config/database.php';

try {

    echo "<h2>Database Connected Successfully ✅</h2>";

    $stmt = $pdo->query("SHOW TABLES");

    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        echo $row[0] . "<br>";
    }

} catch (Exception $e) {

    echo "Error: " . $e->getMessage();
}