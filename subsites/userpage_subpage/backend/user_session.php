<?php
session_start();
require_once __DIR__ . '/../../../includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        (SELECT 
            ts.id AS session_id,
            ts.start_time,
            ts.end_time,
            sc.cubicle_number AS location,
            'cubicle' AS session_type
        FROM timer_sessions ts
        INNER JOIN study_cubicles sc ON ts.cubicle_id = sc.cubicle_id
        WHERE ts.user_id = ? AND ts.status = 'active')
        
        UNION ALL
        
        (SELECT 
            srs.session_id,
            srs.start_time,
            srs.end_time,
            sr.room_number AS location,
            'room' AS session_type
        FROM study_room_sessions srs
        INNER JOIN room_users ru ON srs.session_id = ru.session_id
        INNER JOIN study_rooms sr ON srs.room_id = sr.room_id
        WHERE ru.user_id = ? AND srs.status = 'active')
    ");
    
    $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
    $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $currentTime = time();
    $activeSession = null;
    $foundSessions = [];

    foreach ($sessions as $session) {
        error_log("Raw session data from DB: " . print_r($session, true));
        
        // Validate timestamps
        $startTimestamp = strtotime($session['start_time']);
        $endTimestamp = strtotime($session['end_time']);
        
        if (!$startTimestamp || !$endTimestamp) {
            error_log("Invalid timestamps in session: " . print_r($session, true));
            continue;
        }

        // Calculate times
        $session['time_remaining'] = max($endTimestamp - $currentTime, 0);
        $session['exceeding_time'] = max($currentTime - $endTimestamp, 0);
        $session['total_duration'] = $endTimestamp - $startTimestamp;

        // Validate location
        if (!isset($session['location']) || empty($session['location'])) {
            error_log("Missing location in session: " . print_r($session, true));
            $session['location'] = 'Location not specified';
        }

        // Track all valid sessions
        $foundSessions[] = $session;

        if ($session['time_remaining'] > 0 || $session['exceeding_time'] > 0) {
            $activeSession = $session;
            break;
        }
    }

    error_log("All valid sessions found: " . print_r($foundSessions, true));

    if ($activeSession) {
        // Final validation
        $activeSession['location'] = htmlspecialchars($activeSession['location']);
        $activeSession['session_type'] = strtolower($activeSession['session_type']);
        
        echo json_encode([
            'success' => true,
            'session' => [
                'location' => $activeSession['location'],
                'session_type' => $activeSession['session_type'],
                'start_time' => $activeSession['start_time'],
                'end_time' => $activeSession['end_time'],
                'time_remaining' => (int)$activeSession['time_remaining'],
                'exceeding_time' => (int)$activeSession['exceeding_time'],
                'total_duration' => (int)$activeSession['total_duration']
            ]
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No active sessions found',
            'debug' => $foundSessions
        ]);
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    error_log("Database error: " . $e->getMessage());
    echo json_encode([
        'error' => 'Database error',
        'code' => 500,
        'details' => $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    error_log("General error: " . $e->getMessage());
    echo json_encode([
        'error' => 'Server error',
        'code' => 500,
        'details' => $e->getMessage()
    ]);
}
?>