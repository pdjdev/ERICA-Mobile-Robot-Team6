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

    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'Product ID is required']);
        exit;
    }

    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    
    if ($stmt->execute([$id])) {
        echo json_encode(['success' => true, 'message' => 'Product deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error deleting product']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
