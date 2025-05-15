<?php
require_once '../../../includes/db.php';

header('Content-Type: application/json');

try {
    $stmt = $pdo->query("SELECT * FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $users]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error fetching users: ' . $e->getMessage()]);
}
?>