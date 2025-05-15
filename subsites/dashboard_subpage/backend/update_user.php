<?php
require_once '../../../includes/db.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['id'])) {
    echo json_encode(['success' => false, 'message' => 'User ID required']);
    exit;
}

try {
    // Check for existing username or email
    $stmt = $pdo->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
    $stmt->execute([$data['username'], $data['email'], $data['id']]);
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => false, 'message' => 'Username or email already exists']);
        exit;
    }

    // Update user
    $stmt = $pdo->prepare("UPDATE users SET 
                          firstname = ?, 
                          lastname = ?, 
                          username = ?, 
                          email = ?, 
                          phone_number = ?, 
                          status = ? 
                          WHERE id = ?");

    $stmt->execute([
        $data['firstname'],
        $data['lastname'],
        $data['username'],
        $data['email'],
        $data['phone'] ?? null,
        $data['status'],
        $data['id']
    ]);

    echo json_encode(['success' => true, 'message' => 'User updated successfully']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>