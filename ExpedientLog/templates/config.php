<?php
// config.php - Database connection
$host = 'localhost';
$dbname = 'exp_log';  // Change to your DB name
$username = 'root';           // Change to your DB user
$password = '';               // Change to your DB password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Start session
session_start();
?>