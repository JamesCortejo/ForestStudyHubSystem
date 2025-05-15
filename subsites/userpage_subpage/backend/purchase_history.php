<?php
require_once __DIR__ . '/../../../php/auth.php';
require_once __DIR__ . '/../../../includes/db.php';
verifyUserSession();

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT created_at, total_amount, payment_method 
    FROM orders 
    WHERE user_id = ? 
    ORDER BY created_at DESC
");
$stmt->execute([$user_id]);
$purchases = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($purchases);
?>