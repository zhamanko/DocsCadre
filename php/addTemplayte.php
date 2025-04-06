<?php
header('Content-Type: application/json; charset=utf-8');
$conn = include_once './database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['file'];
        $type = $_POST['type'];
        $category = $_POST['category'];

        if (empty($type) || empty($category)) {
            echo json_encode(['success' => false, 'message' => 'Тип та категорія не можуть бути порожніми']);
            exit;
        }

        $fileName = uniqid() . '-' . basename($file['name']);
        $uploadDir = './../src/template/';
        $filePath = $uploadDir . $fileName;

        $stmt = $conn->prepare("SELECT * FROM template WHERE type = :type AND category = :category");
        $stmt->bindParam(':type', $type);
        $stmt->bindParam(':category', $category);
        $stmt->execute();
        $existingTemplate = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existingTemplate) {
            $oldFilePath = $existingTemplate['path'];
            if (file_exists($oldFilePath)) {
                unlink($oldFilePath);
            }

            $stmt = $conn->prepare("UPDATE template SET path = :path WHERE type = :type AND category = :category");
            $stmt->bindParam(':type', $type);
            $stmt->bindParam(':category', $category);
            $stmt->bindParam(':path', $fileName);

            if ($stmt->execute() && move_uploaded_file($file['tmp_name'], $fileName)) {
                echo json_encode(['success' => true, 'message' => 'Шаблон успішно перезаписано']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Не вдалося перезаписати шаблон']);
            }
        } else {
            if (move_uploaded_file($file['tmp_name'], $fileName)) {
                $stmt = $conn->prepare("INSERT INTO template (type, category, path) VALUES (:type, :category, :path)");
                $stmt->bindParam(':type', $type);
                $stmt->bindParam(':category', $category);
                $stmt->bindParam(':path', $fileName);

                if ($stmt->execute()) {
                    echo json_encode(['success' => true, 'message' => 'Шаблон успішно додано']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Не вдалося зберегти шаблон']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Помилка при завантаженні файлу']);
            }
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Файл не було завантажено']);
    }
}
