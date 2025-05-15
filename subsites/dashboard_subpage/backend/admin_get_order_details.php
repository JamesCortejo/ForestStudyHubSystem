<?php
require_once __DIR__ . '/../../../includes/db.php';

header('Content-Type: application/json');

try {
    session_start();
    if ($_SESSION['role'] !== 'admin' || !isset($_GET['order_id'])) {
        http_response_code(403);
        throw new Exception('Access denied');
    }

    $orderId = filter_input(INPUT_GET, 'order_id', FILTER_VALIDATE_INT);
    if (!$orderId)
        throw new Exception('Invalid order ID');

    // Get order header
    $stmt = $pdo->prepare("
        SELECT o.*, u.username, u.email, u.phone_number 
        FROM orders o
        JOIN users u ON o.user_id = u.id
        WHERE o.id = ?
    ");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order)
        throw new Exception('Order not found');

    // Get order items
    $stmt = $pdo->prepare("
        SELECT p.product_name, p.price, oi.quantity, (p.price * oi.quantity) AS total
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = ?
    ");
    $stmt->execute([$orderId]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'data' => [
            'order' => $order,
            'items' => $items
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>