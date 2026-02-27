<?php
try {
    $pdo = new PDO("mysql:host=127.0.0.1", "root", "");
    $pdo->exec("CREATE DATABASE IF NOT EXISTS selfshop");
    echo "Database 'selfshop' checked/created successfully\n";
} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage());
}
?>
