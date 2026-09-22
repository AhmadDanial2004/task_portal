<?php

//variables for database connection
$host = 'db'; //
$dbname = 'task_portal';
$username = 'task_user';
$password = 'task_password';

//try-catch block to establish a connection to the database using PDO
try 
{
    $pdo = new PDO
    (
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password
    );

    $pdo->setAttribute
    (
        PDO::ATTR_ERRMODE, //PDO Setting: Set the error reporting mode to throw exceptions for better error handling
        PDO::ERRMODE_EXCEPTION //Database error occurs, it will throw an exception that can be caught and handled appropriately
    );
} catch (PDOException $e) //if PDO connection fails, catch the exception and store in variable $e
    {
        die("Connection Failed: " . $e->getMessage()); //stop program execution and display error message if connection fails
    }
?>