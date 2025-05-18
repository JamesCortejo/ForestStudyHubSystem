<?php
declare(strict_types=1);
error_reporting(0);
header('Content-Type: application/json');
require_once __DIR__ . '/../../../includes/db.php';

$timestamp_file = __DIR__ . '/admin_order_notifications_time.json';

try {
    $fp = fopen($timestamp_file, 'c+');
    flock($fp, LOCK_EX);

    $data = json_decode(file_get_contents($timestamp_file), true) ?: ['last_check' => 0];
    $lastCheckDateTime = date('Y-m-d H:i:s', $data['last_check']);

    // Updated query with status check
    $order_stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM orders 
        WHERE status = 'pending' 
        AND created_at > ?
    ");
    $order_stmt->execute([$lastCheckDateTime]);
    $order_count = $order_stmt->fetchColumn();

    $new_timestamp = time();
    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, json_encode(['last_check' => $new_timestamp]));
    fflush($fp);

    flock($fp, LOCK_UN);
    fclose($fp);

    $response = ['success' => true];
    if ($order_count > 0) {
        $response['order'] = [
            'message' => "$order_count pending order" .
                ($order_count > 1 ? 's' : '') .
                " awaiting processing"
        ];
    }

    echo json_encode($response);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Order notification error']);
}