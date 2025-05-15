<?php
require_once __DIR__ . '/../../../includes/db.php';

header('Content-Type: application/json');

try {
    if (!isset($_GET['name'])) {
        throw new Exception('Product name not provided');
    }

    $productName = trim($_GET['name']);
    $stmt = $pdo->prepare("SELECT COUNT(*) AS count FROM products WHERE product_name = ?");
    $stmt->execute([$productName]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'exists' => $result['count'] > 0
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}
?>