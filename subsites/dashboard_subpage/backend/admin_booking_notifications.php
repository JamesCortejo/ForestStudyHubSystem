<?php
// admin_booking_notifications.php
declare(strict_types=1);
error_reporting(0);
header('Content-Type: application/json');
require_once __DIR__ . '/../../../includes/db.php';

$timestamp_file = __DIR__ . '/admin_notifications_time.json';

try {
    // Atomic file operations
    $fp = fopen($timestamp_file, 'c+');
    flock($fp, LOCK_EX);

    $data = json_decode(file_get_contents($timestamp_file), true) ?: ['last_check' => 0];
    $lastCheckDateTime = date('Y-m-d H:i:s', $data['last_check']);

    // Get counts
    $cubicle_stmt = $pdo->prepare("SELECT COUNT(*) FROM cubicle_bookings WHERE status = 'pending' AND booking_time > ?");
    $cubicle_stmt->execute([$lastCheckDateTime]);
    $cubicle_count = $cubicle_stmt->fetchColumn();

    $room_stmt = $pdo->prepare("SELECT COUNT(*) FROM room_bookings WHERE status = 'pending' AND booking_time > ?");
    $room_stmt->execute([$lastCheckDateTime]);
    $room_count = $room_stmt->fetchColumn();

    // Update timestamp FIRST before response
    $new_timestamp = time();
    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, json_encode(['last_check' => $new_timestamp]));
    fflush($fp);

    flock($fp, LOCK_UN);
    fclose($fp);

    // Build response
    $response = ['success' => true];
    if ($cubicle_count > 0)
        $response['cubicle'] = ['message' => "$cubicle_count cubicle booking" . ($cubicle_count > 1 ? 's' : '') . " pending"];
    if ($room_count > 0)
        $response['room'] = ['message' => "$room_count room booking" . ($room_count > 1 ? 's' : '') . " pending"];

    echo json_encode($response);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Booking notification error']);
}