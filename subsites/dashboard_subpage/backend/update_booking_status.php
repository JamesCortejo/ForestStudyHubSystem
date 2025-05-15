<?php
require_once __DIR__ . '/../../../includes/db.php';

header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);

    // Validate input parameters
    if (!isset($input['id'], $input['type'], $input['action'])) {
        throw new Exception('Missing required parameters');
    }

    $bookingId = (int) $input['id'];
    $type = $input['type'];
    $action = $input['action'];

    // Validate booking type
    if (!in_array($type, ['cubicle', 'room'])) {
        throw new Exception('Invalid booking type');
    }

    $pdo->beginTransaction();

    $table = $type . '_bookings';
    $status = $action === 'accept' ? 'accepted' : 'declined';

    // Update booking status
    $stmt = $pdo->prepare("UPDATE $table SET status = ? WHERE booking_id = ?");
    $stmt->execute([$status, $bookingId]);

    if ($action === 'accept') {
        if ($type === 'cubicle') {
            // ========== CUBICLE HANDLING ==========
            $stmt = $pdo->prepare("SELECT user_id, cubicle_id, duration FROM cubicle_bookings WHERE booking_id = ?");
            $stmt->execute([$bookingId]);
            $booking = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$booking)
                throw new Exception("Cubicle booking not found");

            $userId = $booking['user_id'];
            $cubicleId = $booking['cubicle_id'];
            $durationHours = (float) $booking['duration'];
            $durationSeconds = $durationHours * 3600;
            $initialPrice = 70 + (($durationHours - 1) / 0.5) * 10;

            // Check existing sessions
            $checkStmt = $pdo->prepare("
                SELECT 1 FROM (
                    SELECT user_id FROM timer_sessions 
                    WHERE user_id = ? AND status = 'active'
                    UNION
                    SELECT ru.user_id FROM room_users ru
                    JOIN study_room_sessions srs ON ru.session_id = srs.session_id
                    WHERE ru.user_id = ? AND srs.status = 'active'
                ) AS active_sessions
                LIMIT 1
            ");
            $checkStmt->execute([$userId, $userId]);
            if ($checkStmt->fetch()) {
                throw new Exception('User is already in an active session');
            }

            // Check cubicle availability
            $cubicleStmt = $pdo->prepare("SELECT status FROM study_cubicles WHERE cubicle_id = ?");
            $cubicleStmt->execute([$cubicleId]);
            if ($cubicleStmt->fetchColumn() !== 'available') {
                throw new Exception('Cubicle is already occupied');
            }

            // Create timer session
            $startTime = date('Y-m-d H:i:s');
            $endTime = date('Y-m-d H:i:s', time() + $durationSeconds);

            $insertStmt = $pdo->prepare("
                INSERT INTO timer_sessions 
                    (user_id, cubicle_id, start_time, end_time, 
                     time_remaining, status, initial_price, total_bill) 
                VALUES (?, ?, ?, ?, ?, 'active', ?, ?)
            ");
            $insertStmt->execute([
                $userId,
                $cubicleId,
                $startTime,
                $endTime,
                $durationSeconds,
                $initialPrice,
                $initialPrice
            ]);

            // Update cubicle status
            $updateStmt = $pdo->prepare("UPDATE study_cubicles SET status = 'occupied' WHERE cubicle_id = ?");
            $updateStmt->execute([$cubicleId]);

        } else {
            // ========== STUDY ROOM HANDLING ==========
            $stmt = $pdo->prepare("SELECT user_id, room_id, duration FROM room_bookings WHERE booking_id = ?");
            $stmt->execute([$bookingId]);
            $booking = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$booking)
                throw new Exception("Room booking not found");

            $userId = $booking['user_id'];
            $roomId = $booking['room_id'];
            $durationHours = (float) $booking['duration'];
            $durationSeconds = $durationHours * 3600;
            $initialPrice = 85 + (($durationHours - 1) / 0.5) * 10;

            // Check existing sessions
            $checkStmt = $pdo->prepare("
                SELECT 1 FROM (
                    SELECT user_id FROM timer_sessions 
                    WHERE user_id = ? AND status = 'active'
                    UNION
                    SELECT ru.user_id FROM room_users ru
                    JOIN study_room_sessions srs ON ru.session_id = srs.session_id
                    WHERE ru.user_id = ? AND srs.status = 'active'
                ) AS active_sessions
                LIMIT 1
            ");
            $checkStmt->execute([$userId, $userId]);
            if ($checkStmt->fetch()) {
                throw new Exception('User is already in an active session');
            }

            // Check room availability
            $roomStmt = $pdo->prepare("
                SELECT status, capacity, current_occupancy 
                FROM study_rooms 
                WHERE room_id = ?
            ");
            $roomStmt->execute([$roomId]);
            $room = $roomStmt->fetch(PDO::FETCH_ASSOC);

            if (!$room)
                throw new Exception("Room not found");
            if ($room['status'] !== 'available') {
                throw new Exception('Room is already occupied');
            }

            // Create room session
            $startTime = date('Y-m-d H:i:s');
            $endTime = date('Y-m-d H:i:s', time() + $durationSeconds);

            $insertStmt = $pdo->prepare("
                INSERT INTO study_room_sessions 
                    (room_id, start_time, end_time, time_remaining, 
                     initial_price, total_bill, status) 
                VALUES (?, ?, ?, ?, ?, ?, 'active')
            ");
            $insertStmt->execute([
                $roomId,
                $startTime,
                $endTime,
                $durationSeconds,
                $initialPrice,
                $initialPrice
            ]);
            $sessionId = $pdo->lastInsertId();

            // Add user to room
            $userStmt = $pdo->prepare("INSERT INTO room_users (session_id, user_id) VALUES (?, ?)");
            $userStmt->execute([$sessionId, $userId]);

            // Update room status
            $newOccupancy = $room['current_occupancy'] + 1;
            $newStatus = $newOccupancy >= $room['capacity'] ? 'fully_occupied' : 'occupied';

            $updateStmt = $pdo->prepare("
                UPDATE study_rooms 
                SET current_occupancy = ?, status = ?
                WHERE room_id = ?
            ");
            $updateStmt->execute([$newOccupancy, $newStatus, $roomId]);
        }
    }

    $pdo->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}