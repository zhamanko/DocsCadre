<?php
$host = "MySQL-8.2";
$user = "root";
$password = "";
$dbname = "work";

try {
    $conn = new PDO("mysql:host=" . $host . ";dbname=" . $dbname, $user, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $conn;
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
