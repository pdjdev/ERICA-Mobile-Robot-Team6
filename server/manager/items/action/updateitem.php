<?php
session_start();
require_once '../../db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    $id = $_POST['id'] ?? null;
    $name = $_POST['name'] ?? null;
    $description = $_POST['description'] ?? null;
    $vision_desc = $_POST['vision_desc'] ?? null;
    $price = $_POST['price'] ?? null;
    $category = $_POST['category'] ?? null;
    $position = $_POST['position'] ?? null;

    if (!$id || !$name || !$description || !$vision_desc || !$price || !$category || !$position) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        exit;
    }

    $sql = "UPDATE products SET name=?, description=?, vision_desc=?, price=?, category=?, position=? WHERE id=?";
    $stmt = $pdo->prepare($sql);
    
    if ($stmt->execute([$name, $description, $vision_desc, $price, $category, $position, $id])) {
        echo json_encode(['success' => true, 'message' => 'Product updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error updating product']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
