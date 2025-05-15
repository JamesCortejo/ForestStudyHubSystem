<?php
require_once __DIR__ . '/../../../includes/db.php';

// Start output buffering
ob_start();
header('Content-Type: application/json');

try {
    session_start();

    // Validate admin session
    if (!isset($_SESSION['role'])) {
        throw new Exception('Session expired or unauthorized access');
    }
    if ($_SESSION['role'] !== 'admin') {
        throw new Exception('Admin privileges required');
    }

    // Validate request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Get and validate input
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        throw new Exception('Invalid request data');
    }

    $orderId = filter_var($input['order_id'], FILTER_VALIDATE_INT);
    $status = isset($input['status']) ? trim($input['status']) : '';

    if (!$orderId || $orderId < 1) {
        throw new Exception('Invalid order ID');
    }
    if (!in_array($status, ['confirmed', 'declined'])) {
        throw new Exception('Invalid status value');
    }

    $pdo->beginTransaction();

    // Verify pending order exists
    $stmt = $pdo->prepare("SELECT id FROM orders WHERE id = ? AND status = 'pending'");
    $stmt->execute([$orderId]);
    if (!$stmt->fetch()) {
        throw new Exception('Order not found or already processed');
    }

    // Get order items
    $stmt = $pdo->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
    $stmt->execute([$orderId]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Process stock reduction for confirmed orders
    if ($status === 'confirmed') {
        foreach ($items as $item) {
            // Validate product exists
            $checkStmt = $pdo->prepare("SELECT id FROM products WHERE id = ?");
            $checkStmt->execute([$item['product_id']]);
            if (!$checkStmt->fetch()) {
                throw new Exception("Product ID {$item['product_id']} not found");
            }

            // Update stock (using correct column name 'stock')
            $updateStmt = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
            $updateStmt->execute([$item['quantity'], $item['product_id']]);

            if ($updateStmt->rowCount() === 0) {
                throw new Exception("Failed to update stock for product ID {$item['product_id']}");
            }
        }
    }

    // Update order status
    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->execute([$status, $orderId]);

    if ($stmt->rowCount() === 0) {
        throw new Exception('No changes made to order status');
    }

    $pdo->commit();

    echo json_encode([
        'status' => 'success',
        'message' => 'Order status updated successfully'
    ]);

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
} finally {
    // Clean output buffer and send response
    ob_end_flush();
}
?>