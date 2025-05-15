<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

ob_start();

require_once __DIR__ . '/../../../includes/db.php';

header('Content-Type: application/json');

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type");

if (isset($_GET['action']) && $_GET['action'] == 'getUsers') {
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';

    try {
        if (empty($search)) {
            $stmt = $pdo->query("SELECT id, CONCAT(firstname, ' ', lastname) as name, username FROM users ORDER BY firstname, lastname");
        } else {
            $stmt = $pdo->prepare("SELECT id, CONCAT(firstname, ' ', lastname) as name, username FROM users 
                                  WHERE firstname LIKE ? OR lastname LIKE ? OR username LIKE ?
                                  ORDER BY firstname, lastname");
            $searchTerm = "%$search%";
            $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
        }

        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($users);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error']);
    }
    exit;
}

if (isset($_GET['action']) && $_GET['action'] == 'getCubicles') {
    try {
        $stmt = $pdo->query("SELECT cubicle_id, cubicle_number FROM study_cubicles 
                            WHERE status = 'available' 
                            ORDER BY cubicle_number ASC");
        $cubicles = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($cubicles);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error']);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'startTimer') {
    $userId = filter_var($_POST['userId'], FILTER_VALIDATE_INT);
    $cubicleId = filter_var($_POST['cubicleId'], FILTER_VALIDATE_INT);
    $timeSelected = filter_var($_POST['timeSelected'], FILTER_VALIDATE_INT);
    $initialPrice = filter_var($_POST['initialPrice'], FILTER_VALIDATE_FLOAT);

    if (!$userId || !$cubicleId || !$timeSelected || $initialPrice === false) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid input']);
        exit;
    }

    try {
        $pdo->beginTransaction();

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
        $existingSession = $checkStmt->fetch();

        if ($existingSession) {
            $pdo->rollBack();
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'User is already in a session (cubicle or room)']);
            exit;
        }

        $cubicleStmt = $pdo->prepare("SELECT status FROM study_cubicles WHERE cubicle_id = ?");
        $cubicleStmt->execute([$cubicleId]);
        if ($cubicleStmt->fetchColumn() !== 'available') {
            $pdo->rollBack();
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Cubicle is already occupied']);
            exit;
        }

        $startTime = date('Y-m-d H:i:s');
        $endTime = date('Y-m-d H:i:s', strtotime("+$timeSelected seconds"));

        $stmt = $pdo->prepare("INSERT INTO timer_sessions 
                      (user_id, cubicle_id, start_time, end_time, time_remaining, status, initial_price, total_bill) 
                      VALUES (?, ?, ?, ?, ?, 'active', ?, ?)");
        $stmt->execute([$userId, $cubicleId, $startTime, $endTime, $timeSelected, $initialPrice, $initialPrice]);

        $stmt = $pdo->prepare("UPDATE study_cubicles SET status = 'occupied' WHERE cubicle_id = ?");
        $stmt->execute([$cubicleId]);

        $pdo->commit();
        echo json_encode(['success' => true, 'sessionId' => $pdo->lastInsertId()]);
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

if (isset($_GET['action']) && $_GET['action'] == 'getActiveSessions') {
    try {
        $stmt = $pdo->prepare("SELECT ts.id, ts.user_id, u.firstname, u.lastname, 
                      sc.cubicle_number, ts.start_time, ts.end_time, 
                      ts.time_remaining, ts.exceeding_time, ts.initial_price, ts.total_bill
                      FROM timer_sessions ts
                      JOIN users u ON ts.user_id = u.id
                      JOIN study_cubicles sc ON ts.cubicle_id = sc.cubicle_id
                      WHERE ts.status = 'active'");
        $stmt->execute();
        $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $currentTime = time();
        foreach ($sessions as &$session) {
            $endTimestamp = strtotime($session['end_time']);

            if ($currentTime < $endTimestamp) {

                $session['time_remaining'] = $endTimestamp - $currentTime;
                $session['exceeding_time'] = 0;
            } else {

                $session['time_remaining'] = 0;
                $session['exceeding_time'] = $currentTime - $endTimestamp;

                $tenMinuteBlocks = floor($session['exceeding_time'] / 600);
                $additionalCharges = $tenMinuteBlocks * 40;
                $expectedTotal = floatval($session['initial_price']) + $additionalCharges;

                if (floatval($session['total_bill']) < $expectedTotal) {
                    $session['total_bill'] = $expectedTotal;

                    $updateStmt = $pdo->prepare("UPDATE timer_sessions 
                                       SET exceeding_time = ?, total_bill = ?
                                       WHERE id = ?");
                    $updateStmt->execute([$session['exceeding_time'], $session['total_bill'], $session['id']]);
                } else {

                    $updateStmt = $pdo->prepare("UPDATE timer_sessions 
                                       SET exceeding_time = ?
                                       WHERE id = ?");
                    $updateStmt->execute([$session['exceeding_time'], $session['id']]);
                }
            }
        }

        echo json_encode($sessions);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error']);
    }
    exit;
}

// End a timer session
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'endSession') {
    $sessionId = filter_var($_POST['sessionId'], FILTER_VALIDATE_INT);

    if (!$sessionId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid session ID']);
        exit;
    }

    try {
        $pdo->beginTransaction();

        // Get session details
        $stmt = $pdo->prepare("SELECT cubicle_id FROM timer_sessions WHERE id = ?");
        $stmt->execute([$sessionId]);
        $session = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$session) {
            throw new Exception("Session not found");
        }

        // Update timer session to expired
        $stmt = $pdo->prepare("UPDATE timer_sessions SET status = 'expired' WHERE id = ?");
        $stmt->execute([$sessionId]);

        // Update cubicle status to available
        $stmt = $pdo->prepare("UPDATE study_cubicles SET status = 'available' WHERE cubicle_id = ?");
        $stmt->execute([$session['cubicle_id']]);

        $pdo->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error']);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'removeSession') {
    $sessionId = filter_var($_POST['sessionId'], FILTER_VALIDATE_INT);

    if (!$sessionId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid session ID']);
        exit;
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("SELECT cubicle_id FROM timer_sessions WHERE id = ?");
        $stmt->execute([$sessionId]);
        $session = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$session) {
            throw new Exception("Session not found");
        }

        // Update session status to expired instead of deleting
        $stmt = $pdo->prepare("UPDATE timer_sessions SET status = 'expired' WHERE id = ?");
        $stmt->execute([$sessionId]);

        // Update cubicle status to available
        $stmt = $pdo->prepare("UPDATE study_cubicles SET status = 'available' WHERE cubicle_id = ?");
        $stmt->execute([$session['cubicle_id']]);

        $pdo->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error']);
    }
    exit;
}

// Clean output buffer and send response
ob_end_flush();