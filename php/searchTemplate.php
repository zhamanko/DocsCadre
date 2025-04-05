<?php
header('Content-Type: application/json; charset=utf-8');
$conn = include 'database.php';

// Отримуємо параметри з GET-запиту
$type = $_GET['type'] ?? '';
$category = $_GET['category'] ?? '';
$search = $_GET['search'] ?? '';

$query = "SELECT * FROM template WHERE 1=1";
$params = [];

if (!empty($type)) {
    $query .= " AND type = :type";
    $params[':type'] = $type;
}

if (!empty($category)) {
    $query .= " AND category = :category";
    $params[':category'] = $category;
}

if (!empty($search)) {
    $query .= " AND (type LIKE :search OR category LIKE :search)";
    $params[':search'] = "%" . $search . "%";
}

$stmt = $conn->prepare($query);
$stmt->execute($params);
$templates = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($templates);
