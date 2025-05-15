<?php
require_once __DIR__ . '/../../../php/auth.php';
require_once __DIR__ . '/../../../includes/db.php';
verifyUserSession();

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT s.start_time, s.end_time, r.room_number AS room, 
           s.time_remaining, s.exceeding_time, s.total_bill 
    FROM study_room_sessions s
    JOIN room_users ru ON s.session_id = ru.session_id
    JOIN study_rooms r ON s.room_id = r.room_id
    WHERE ru.user_id = ?
    ORDER BY s.start_time DESC
");
$stmt->execute([$user_id]);
$sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($sessions);
?>