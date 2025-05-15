<?php
require_once __DIR__ . '/../../../includes/db.php';

header('Content-Type: application/json');

try {
    session_start();
    if ($_SESSION['role'] !== 'admin') {
        http_response_code(403);
        throw new Exception('Access denied');
    }

    $stmt = $pdo->prepare("
        SELECT 
            o.id AS order_id,
            CONCAT(u.firstname, ' ', u.lastname) AS customer_name,
            o.status
        FROM orders o
        JOIN users u ON o.user_id = u.id
        WHERE o.status = 'pending'
        ORDER BY o.created_at DESC
    ");
    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'data' => $orders
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>