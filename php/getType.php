<?php
$conn = include 'database.php';

$stmt = $conn->prepare("SELECT DISTINCT type FROM template");
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

return $categories;
?>
