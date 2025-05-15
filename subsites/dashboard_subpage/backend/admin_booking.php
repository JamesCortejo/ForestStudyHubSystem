<?php
require_once __DIR__ . '/../../../includes/db.php';

header('Content-Type: application/json');

try {
    // Get pending cubicle bookings with user info
    $cubicles = $pdo->query("
        SELECT b.booking_id, u.username, c.cubicle_number, 
               b.duration, b.booking_time
        FROM cubicle_bookings b
        JOIN users u ON b.user_id = u.id
        JOIN study_cubicles c ON b.cubicle_id = c.cubicle_id
        WHERE b.status = 'pending'
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Get pending room bookings with user info
    $rooms = $pdo->query("
        SELECT b.booking_id, u.username, r.room_number, 
               b.duration, b.booking_time
        FROM room_bookings b
        JOIN users u ON b.user_id = u.id
        JOIN study_rooms r ON b.room_id = r.room_id
        WHERE b.status = 'pending'
    ")->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'cubicles' => $cubicles,
        'rooms' => $rooms
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
