<?php
require_once '../../../includes/db.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

// Validate required fields
$required = ['firstname', 'lastname', 'username', 'password', 'email', 'role', 'status'];
foreach ($required as $field) {
    if (empty($data[$field])) {
        echo json_encode(['success' => false, 'message' => "$field is required"]);
        exit;
    }
}

// Map status
$status = ($data['status'] === 'online') ? 'active' : 'inactive';

try {
    // Check for existing username or email
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$data['username'], $data['email']]);
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => false, 'message' => 'Username or email already exists']);
        exit;
    }

    // Hash password
    $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

    // Insert new user
    $stmt = $pdo->prepare("INSERT INTO users (firstname, lastname, username, password, email, phone_number, role, status) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $data['firstname'],
        $data['lastname'],
        $data['username'],
        $hashedPassword,
        $data['email'],
        $data['phone'] ?? null,
        $data['role'],
        $status
    ]);

    echo json_encode(['success' => true, 'message' => 'User created successfully']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>