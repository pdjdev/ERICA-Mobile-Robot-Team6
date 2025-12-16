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
    $id = $_POST['id'] ?? '';
    $name = $_POST['name'] ?? null;
    $description = $_POST['description'] ?? null;
    $vision_desc = $_POST['vision_desc'] ?? null;
    $price = $_POST['price'] ?? null;
    $category = $_POST['category'] ?? null;
    $position = $_POST['position'] ?? null;

    if (!$name || !$description || !$vision_desc || !$price || !$category || !$position) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        exit;
    }

    // ID가 비어있으면 auto_increment 사용
    if (!empty($id)) {
        // Check if ID exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE id = ?");
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            echo json_encode(['success' => false, 'message' => 'Product ID already exists']);
            exit;
        }

        $sql = "INSERT INTO products (id, name, description, vision_desc, price, category, position) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([$id, $name, $description, $vision_desc, $price, $category, $position]);
    } else {
        // ID를 비워두면 auto_increment 사용
        $sql = "INSERT INTO products (name, description, vision_desc, price, category, position) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([$name, $description, $vision_desc, $price, $category, $position]);
    }
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Product added successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error adding product']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
