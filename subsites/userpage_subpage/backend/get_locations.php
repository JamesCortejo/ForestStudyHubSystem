<?php
require_once __DIR__ . '/../../../includes/db.php';
require_once __DIR__ . '/../../../php/auth.php';
$currentUser = verifyUserSession();

header('Content-Type: application/json');

try {
    $cubicles = $pdo->query("SELECT cubicle_id AS id, cubicle_number AS number FROM study_cubicles")->fetchAll(PDO::FETCH_ASSOC);
    $rooms = $pdo->query("SELECT room_id AS id, room_number AS number FROM study_rooms")->fetchAll(PDO::FETCH_ASSOC);

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