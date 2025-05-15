<?php
require_once __DIR__ . '/../../../php/auth.php';
require_once __DIR__ . '/../../../includes/db.php';

$currentUser = verifyUserSession();
if (!$currentUser) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$paymentMethod = $data['paymentMethod'] ?? '';
$notes = $data['notes'] ?? '';

// Validate input
if (!isset($_SESSION['checkout_cart']) || empty($_SESSION['checkout_cart'])) {
    echo json_encode(['status' => 'error', 'message' => 'Cart is empty']);
    exit();
}

try {
    $pdo->beginTransaction();

    // Calculate total and validate stock
    $totalAmount = 0;
    $validatedItems = [];

    foreach ($_SESSION['checkout_cart'] as $item) {
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? FOR UPDATE");
        $stmt->execute([$item['id']]);
        $product = $stmt->fetch();

        if (!$product) {
            throw new Exception("Product {$item['id']} not found");
        }

        if ($product['stock'] < $item['quantity']) {
            throw new Exception("Insufficient stock for {$product['product_name']}");
        }

        $validatedItems[] = $product;
        $totalAmount += $product['price'] * $item['quantity'];
    }

    // Create order
    $stmt = $pdo->prepare("
        INSERT INTO orders (user_id, total_amount, payment_method, notes, status)
        VALUES (?, ?, ?, ?, 'pending')
    ");
    $stmt->execute([
        $currentUser['id'],
        $totalAmount,
        $paymentMethod,
        $notes
    ]);
    $orderId = $pdo->lastInsertId();

    // Create order items
    foreach ($_SESSION['checkout_cart'] as $item) {
        $stmt = $pdo->prepare("
            INSERT INTO order_items (order_id, product_id, quantity, price)
            VALUES (?, ?, ?, (SELECT price FROM products WHERE id = ?))
        ");
        $stmt->execute([
            $orderId,
            $item['id'],
            $item['quantity'],
            $item['id']
        ]);
    }

    $pdo->commit();
    unset($_SESSION['checkout_cart']);

    echo json_encode(['status' => 'success', 'orderId' => $orderId]);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>