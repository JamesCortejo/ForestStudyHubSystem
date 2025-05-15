<?php
require_once __DIR__ . '/../../../includes/db.php';

header('Content-Type: application/json');

try {
    $stmt = $pdo->query("SELECT 
        p.id,
        p.product_name,
        p.image_path
    FROM products p
    ORDER BY p.created_at DESC
    LIMIT 3");

    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($products);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}