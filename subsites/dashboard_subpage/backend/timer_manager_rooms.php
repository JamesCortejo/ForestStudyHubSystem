<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type");

require_once __DIR__ . '/../../../includes/db.php';

try {
    if (!isset($_REQUEST['action'])) {
        throw new Exception("Missing action parameter");
    }

    $action = $_REQUEST['action'];

    switch ($action) {
        case 'searchUsers':
            $search = $_GET['query'] ?? '';
            $searchTerm = "%$search%";
            $sql = "SELECT id, CONCAT(firstname, ' ', lastname) AS name, username FROM users";
            if (!empty($search)) {
                $sql .= " WHERE firstname LIKE ? OR lastname LIKE ? OR username LIKE ?";
            }
            $sql .= " LIMIT 50";
            $stmt = $pdo->prepare($sql);
            if (!empty($search)) {
                $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
            } else {
                $stmt->execute();
            }
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            break;

        case 'getRooms':
            $stmt = $pdo->query("SELECT * FROM study_rooms");
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            break;

        case 'startRoomSession':
            $roomId = filter_var($_POST['roomId'], FILTER_VALIDATE_INT);
            $timeSelected = filter_var($_POST['timeSelected'], FILTER_VALIDATE_INT);
            $initialPrice = filter_var($_POST['initialPrice'], FILTER_VALIDATE_FLOAT);
            $users = json_decode($_POST['users']);

            if (!$roomId || !$timeSelected || !$initialPrice || empty($users)) {
                throw new Exception("Invalid input parameters");
            }

            $pdo->beginTransaction();

            // Check room availability
            $stmt = $pdo->prepare("
        SELECT session_id FROM study_room_sessions 
        WHERE room_id = ? AND status = 'active'
        LIMIT 1
    ");
            $stmt->execute([$roomId]);
            if ($stmt->fetch()) {
                throw new Exception("Study Room already in session");
            }

            // Cross-system user availability check
            $placeholders = implode(',', array_fill(0, count($users), '?'));
            $stmt = $pdo->prepare("
        SELECT u.username FROM (
            SELECT user_id FROM timer_sessions 
            WHERE status = 'active' AND user_id IN ($placeholders)
            UNION
            SELECT ru.user_id FROM room_users ru
            JOIN study_room_sessions s ON ru.session_id = s.session_id
            WHERE s.status = 'active' AND ru.user_id IN ($placeholders)
        ) AS active_users
        JOIN users u ON active_users.user_id = u.id
        LIMIT 1
    ");
            $stmt->execute(array_merge($users, $users));
            if ($existingUser = $stmt->fetch()) {
                throw new Exception("User '{$existingUser['username']}' is already in a session (cubicle or room)");
            }

            // Check capacity
            $stmt = $pdo->prepare("SELECT capacity, current_occupancy FROM study_rooms WHERE room_id = ?");
            $stmt->execute([$roomId]);
            $room = $stmt->fetch();

            if (!$room) {
                throw new Exception("Selected room not found");
            }

            $availableSlots = $room['capacity'] - $room['current_occupancy'];
            if (count($users) > $availableSlots) {
                throw new Exception("Room only has {$availableSlots} available spots");
            }

            // Create session
            $startTime = date('Y-m-d H:i:s');
            $endTime = date('Y-m-d H:i:s', strtotime("+$timeSelected seconds"));

            $stmt = $pdo->prepare("INSERT INTO study_room_sessions 
        (room_id, start_time, end_time, time_remaining, initial_price, total_bill, status)
        VALUES (?, ?, ?, ?, ?, ?, 'active')");
            $stmt->execute([$roomId, $startTime, $endTime, $timeSelected, $initialPrice, $initialPrice]);
            $sessionId = $pdo->lastInsertId();

            // Add users
            foreach ($users as $userId) {
                $userStmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
                $userStmt->execute([$userId]);
                if (!$userStmt->fetch()) {
                    throw new Exception("Invalid user ID: $userId");
                }

                $stmt = $pdo->prepare("INSERT INTO room_users (session_id, user_id) VALUES (?, ?)");
                $stmt->execute([$sessionId, $userId]);
            }

            // Update room status
            $newOccupancy = $room['current_occupancy'] + count($users);
            $status = ($newOccupancy >= $room['capacity']) ? 'fully_occupied' : 'occupied';

            $stmt = $pdo->prepare("UPDATE study_rooms 
        SET current_occupancy = ?, status = ?
        WHERE room_id = ?");
            $stmt->execute([$newOccupancy, $status, $roomId]);

            $pdo->commit();
            echo json_encode(['success' => true]);
            break;

            // Check capacity
            $stmt = $pdo->prepare("SELECT capacity, current_occupancy FROM study_rooms WHERE room_id = ?");
            $stmt->execute([$roomId]);
            $room = $stmt->fetch();

            if (!$room) {
                throw new Exception("Selected room not found");
            }

            $availableSlots = $room['capacity'] - $room['current_occupancy'];
            if (count($users) > $availableSlots) {
                throw new Exception("Room only has {$availableSlots} available spots");
            }

            // Create session
            $startTime = date('Y-m-d H:i:s');
            $endTime = date('Y-m-d H:i:s', strtotime("+$timeSelected seconds"));

            $stmt = $pdo->prepare("INSERT INTO study_room_sessions 
                (room_id, start_time, end_time, time_remaining, initial_price, total_bill, status)
                VALUES (?, ?, ?, ?, ?, ?, 'active')");
            $stmt->execute([$roomId, $startTime, $endTime, $timeSelected, $initialPrice, $initialPrice]);
            $sessionId = $pdo->lastInsertId();

            // Add users
            foreach ($users as $userId) {
                $userStmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
                $userStmt->execute([$userId]);
                if (!$userStmt->fetch()) {
                    throw new Exception("Invalid user ID: $userId");
                }

                $stmt = $pdo->prepare("INSERT INTO room_users (session_id, user_id) VALUES (?, ?)");
                $stmt->execute([$sessionId, $userId]);
            }

            // Update room status
            $newOccupancy = $room['current_occupancy'] + count($users);
            $status = ($newOccupancy >= $room['capacity']) ? 'fully_occupied' : 'occupied';

            $stmt = $pdo->prepare("UPDATE study_rooms 
                SET current_occupancy = ?, status = ?
                WHERE room_id = ?");
            $stmt->execute([$newOccupancy, $status, $roomId]);

            $pdo->commit();
            echo json_encode(['success' => true]);
            break;

        case 'getActiveRoomSessions':
            $stmt = $pdo->prepare("SELECT 
                s.*, 
                r.room_number, 
                GROUP_CONCAT(u.username) AS users 
                FROM study_room_sessions s
                JOIN study_rooms r ON s.room_id = r.room_id
                JOIN room_users ru ON s.session_id = ru.session_id
                JOIN users u ON ru.user_id = u.id
                WHERE s.status = 'active'
                GROUP BY s.session_id");
            $stmt->execute();
            $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $currentTime = time();
            foreach ($sessions as &$session) {
                $session['initial_price'] = (float) $session['initial_price'];
                $session['total_bill'] = (float) $session['total_bill'];
                $endTime = strtotime($session['end_time']);
                $session['users'] = explode(',', $session['users']);

                if ($currentTime < $endTime) {
                    $session['time_remaining'] = $endTime - $currentTime;
                    $session['exceeding_time'] = 0;
                } else {
                    $session['exceeding_time'] = $currentTime - $endTime;
                    $session['time_remaining'] = 0;

                    // Calculate overtime
                    $tenMinBlocks = floor($session['exceeding_time'] / 600);
                    $additional = $tenMinBlocks * 40;
                    $session['total_bill'] = $session['initial_price'] + $additional;

                    $stmt = $pdo->prepare("UPDATE study_room_sessions 
                        SET exceeding_time = ?, total_bill = ?
                        WHERE session_id = ?");
                    $stmt->execute([$session['exceeding_time'], $session['total_bill'], $session['session_id']]);
                }
            }
            echo json_encode($sessions);
            break;

        case 'getSessionDetails':
            $sessionId = filter_var($_GET['sessionId'], FILTER_VALIDATE_INT);
            if (!$sessionId)
                throw new Exception("Invalid session ID");

            try {
                $stmt = $pdo->prepare("
                    SELECT 
                        s.*,
                        r.room_number,
                        u.id AS user_id,
                        u.username,
                        CONCAT(u.firstname, ' ', u.lastname) AS name,
                        TIMESTAMPDIFF(SECOND, s.start_time, s.end_time) AS total_duration,
                        GREATEST(UNIX_TIMESTAMP() - UNIX_TIMESTAMP(s.end_time), 0) AS exceeding_time
                    FROM study_room_sessions s
                    JOIN study_rooms r ON s.room_id = r.room_id
                    JOIN room_users ru ON s.session_id = ru.session_id
                    JOIN users u ON ru.user_id = u.id
                    WHERE s.session_id = ?
                ");
                $stmt->execute([$sessionId]);
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (empty($results)) {
                    throw new Exception("Session not found");
                }

                // Calculate shares
                $userCount = count($results);
                $initialPerUser = $results[0]['initial_price'] / $userCount;
                $overtime = $results[0]['total_bill'] - $results[0]['initial_price'];
                $overtimePerUser = $overtime / $userCount;

                $session = [
                    'room_number' => $results[0]['room_number'],
                    'total_duration' => $results[0]['total_duration'],
                    'exceeding_time' => $results[0]['exceeding_time'],
                    'total_bill' => $results[0]['total_bill'],
                    'users' => []
                ];

                foreach ($results as $row) {
                    $session['users'][] = [
                        'name' => $row['name'],
                        'username' => $row['username'],
                        'initial_share' => $initialPerUser,
                        'overtime_share' => $overtimePerUser
                    ];
                }

                echo json_encode($session);

            } catch (Exception $e) {
                throw new Exception("Session details error: " . $e->getMessage());
            }
            break;

        case 'endRoomSession':
            $sessionId = filter_var($_POST['sessionId'], FILTER_VALIDATE_INT);
            if (!$sessionId)
                throw new Exception("Invalid session ID");

            try {
                $pdo->beginTransaction();

                // Get session details
                $stmt = $pdo->prepare("
                    SELECT s.room_id, COUNT(ru.user_id) AS user_count 
                    FROM study_room_sessions s
                    LEFT JOIN room_users ru ON s.session_id = ru.session_id 
                    WHERE s.session_id = ?
                ");
                $stmt->execute([$sessionId]);
                $sessionData = $stmt->fetch();

                if (!$sessionData) {
                    throw new Exception("Session not found");
                }

                $roomId = $sessionData['room_id'];
                $userCount = $sessionData['user_count'];

                // End session
                $stmt = $pdo->prepare("UPDATE study_room_sessions SET status = 'expired' WHERE session_id = ?");
                $stmt->execute([$sessionId]);

                // Update room
                $stmt = $pdo->prepare("
                    UPDATE study_rooms 
                    SET 
                        current_occupancy = GREATEST(current_occupancy - ?, 0),
                        status = CASE 
                            WHEN (current_occupancy - ?) <= 0 THEN 'available'
                            ELSE 'occupied' 
                        END
                    WHERE room_id = ?
                ");
                $stmt->execute([$userCount, $userCount, $roomId]);

                $pdo->commit();
                echo json_encode(['success' => true, 'message' => 'Session expired successfully']);

            } catch (Exception $e) {
                $pdo->rollBack();
                throw new Exception("Session removal failed: " . $e->getMessage());
            }
            break;

        default:
            throw new Exception("Invalid action: $action");
    }

} catch (Exception $e) {
    error_log("PHP Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
    exit();
}