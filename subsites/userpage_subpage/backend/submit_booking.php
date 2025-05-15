<?php
require_once __DIR__ . '/../../../includes/db.php';
require_once __DIR__ . '/../../../php/auth.php';
$currentUser = verifyUserSession();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

try {
    // Validate required parameters
    $requiredParams = ['type', 'location_id', 'duration', 'booking_time'];
    foreach ($requiredParams as $param) {
        if (!isset($_POST[$param])) {
            throw new Exception("Missing required parameter: $param");
        }
    }

    // Sanitize inputs
    $type = $_POST['type'];
    $locationId = (int)$_POST['location_id'];
    $duration = (float)$_POST['duration'];
    $bookingTime = $_POST['booking_time'];

    // Validate input values
    if (!in_array($type, ['cubicle', 'room'])) {
        throw new Exception('Invalid booking type');
    }

    if ($locationId < 1) {
        throw new Exception('Invalid location ID');
    }

    if ($duration < 0.5 || $duration > 4) {
        throw new Exception('Duration must be between 0.5 and 4 hours');
    }

    // Validate booking time
    $currentTime = new DateTime();
    $startTime = new DateTime($bookingTime);
    $endTime = clone $startTime;
    $endTime->modify("+{$duration} hours");

    if ($startTime < $currentTime) {
        throw new Exception('Booking time must be in the future');
    }

    // Check for overlapping PENDING bookings only
    $table = $type === 'cubicle' ? 'cubicle_bookings' : 'room_bookings';
    $column = $type === 'cubicle' ? 'cubicle_id' : 'room_id';

    $stmt = $pdo->prepare("SELECT * 
        FROM $table 
        WHERE 
            $column = :location_id AND
            status = 'pending' AND
            (
                (booking_time BETWEEN :start_time AND :end_time) OR
                (DATE_ADD(booking_time, INTERVAL duration HOUR) BETWEEN :start_time AND :end_time) OR
                (booking_time <= :start_time AND DATE_ADD(booking_time, INTERVAL duration HOUR) >= :end_time)
            )
        LIMIT 1");

    $stmt->execute([
        ':location_id' => $locationId,
        ':start_time' => $startTime->format('Y-m-d H:i:s'),
        ':end_time' => $endTime->format('Y-m-d H:i:s')
    ]);

    if ($stmt->rowCount() > 0) {
        throw new Exception('Selected time slot is already booked');
    }

    // Create new booking
    $insertStmt = $pdo->prepare("INSERT INTO $table 
        (user_id, $column, duration, booking_time, status) 
        VALUES (:user_id, :location_id, :duration, :booking_time, 'pending')");

    $insertStmt->execute([
        ':user_id' => $_SESSION['user_id'],
        ':location_id' => $locationId,
        ':duration' => $duration,
        ':booking_time' => $startTime->format('Y-m-d H:i:s')
    ]);

    // Verify successful insertion
    if ($insertStmt->rowCount() === 0) {
        throw new Exception('Failed to create booking');
    }

    echo json_encode([
        'success' => true,
        'message' => 'Booking created successfully',
        'booking_id' => $pdo->lastInsertId()
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    error_log("Database Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error occurred'
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}