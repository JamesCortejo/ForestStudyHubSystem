<?php
require_once __DIR__ . '/../../../php/auth.php';
verifyUserSession();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if (isset($input['cart'])) {
        // Store in both session and localStorage
        $_SESSION['checkout_cart'] = $input['cart'];
        echo json_encode(['status' => 'success']);
        exit();
    }
}

echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
?>