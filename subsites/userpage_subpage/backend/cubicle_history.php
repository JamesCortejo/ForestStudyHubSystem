<?php
require_once __DIR__ . '/../../../php/auth.php';
require_once __DIR__ . '/../../../includes/db.php';
verifyUserSession();

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT t.start_time, t.end_time, c.cubicle_number AS cubicle, 
           t.time_remaining, t.exceeding_time, t.total_bill 
    FROM timer_sessions t
    JOIN study_cubicles c ON t.cubicle_id = c.cubicle_id
    WHERE t.user_id = ?
    ORDER BY t.start_time DESC
");
$stmt->execute([$user_id]);
$sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($sessions);
?>