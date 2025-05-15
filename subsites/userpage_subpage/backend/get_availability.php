<?php
require_once __DIR__ . '/../../../includes/db.php';
require_once __DIR__ . '/../../../php/auth.php';
$currentUser = verifyUserSession();

header('Content-Type: application/json');

try {
    // Get cubicle bookings with pending status
    $cubicleQuery = $pdo->prepare("
        SELECT 
            c.cubicle_number AS location_number,
            b.booking_time,
            b.duration,
            b.status,
            b.booking_time AS start,
            DATE_ADD(b.booking_time, INTERVAL b.duration HOUR) AS end
        FROM cubicle_bookings b
        INNER JOIN study_cubicles c ON b.cubicle_id = c.cubicle_id
        WHERE b.status = 'pending'
    ");
    $cubicleQuery->execute();
    $cubicles = $cubicleQuery->fetchAll(PDO::FETCH_ASSOC);

    // Get room bookings with pending status
    $roomQuery = $pdo->prepare("
        SELECT 
            r.room_number AS location_number,
            b.booking_time,
            b.duration,
            b.status,
            b.booking_time AS start,
            DATE_ADD(b.booking_time, INTERVAL b.duration HOUR) AS end
        FROM room_bookings b
        INNER JOIN study_rooms r ON b.room_id = r.room_id
        WHERE b.status = 'pending'
    ");
    $roomQuery->execute();
    $rooms = $roomQuery->fetchAll(PDO::FETCH_ASSOC);

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
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>