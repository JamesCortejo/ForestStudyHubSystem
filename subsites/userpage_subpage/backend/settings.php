<?php
require_once __DIR__ . '/../../../includes/db.php';
require_once __DIR__ . '/../../../php/auth.php';

$currentUser = verifyUserSession();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $stmt = $pdo->prepare("SELECT * FROM user_settings WHERE user_id = ?");
        $stmt->execute([$currentUser['id']]);
        $settings = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$settings) {
            $settings = [
                'enable_push' => true,
                'enable_sound' => true,
                'profile_pic' => 'default-avatar.jpg'
            ];
        } else {
            $settings['enable_push'] = (bool) $settings['enable_push'];
            $settings['enable_sound'] = (bool) $settings['enable_sound'];
            $settings['profile_pic'] = $settings['profile_pic'] ?? 'default-avatar.jpg';
        }

        echo json_encode($settings);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error']);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = ['success' => false];

    try {
        $pdo->beginTransaction();
        $userId = $currentUser['id'];

        // 1. Get existing settings first
        $stmt = $pdo->prepare("SELECT * FROM user_settings WHERE user_id = ?");
        $stmt->execute([$userId]);
        $existingSettings = $stmt->fetch(PDO::FETCH_ASSOC);

        // 2. Initialize current picture
        $currentPic = $existingSettings['profile_pic'] ?? 'default-avatar.jpg';
        $push = isset($_POST['push']) ? (int) $_POST['push'] : 1;
        $sound = isset($_POST['sound']) ? (int) $_POST['sound'] : 1;

        // 3. Handle file upload if present
        if (!empty($_FILES['profile_pic']['tmp_name'])) {
            $file = $_FILES['profile_pic'];

            // Validate file
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            $maxSize = 2 * 1024 * 1024;

            if (!in_array($file['type'], $allowedTypes)) {
                throw new Exception('Invalid file type. Only JPG, PNG, and GIF are allowed.');
            }

            if ($file['size'] > $maxSize) {
                throw new Exception('File size exceeds 2MB limit.');
            }

            // Delete old file if exists
            if ($currentPic && $currentPic !== 'default-avatar.jpg') {
                $oldPath = $_SERVER['DOCUMENT_ROOT'] . '/ForrestStudy_Hub/resources/pfpFolder/' . $currentPic;
                if (file_exists($oldPath) && is_writable($oldPath)) {
                    unlink($oldPath);
                }
            }

            // Generate new filename
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $newFilename = uniqid('pfp_') . '.' . $extension;
            $uploadPath = $_SERVER['DOCUMENT_ROOT'] . '/ForrestStudy_Hub/resources/pfpFolder/' . $newFilename;

            if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
                throw new Exception('Failed to save uploaded file.');
            }

            $currentPic = $newFilename;
        }

        // 4. Update database
        $stmt = $pdo->prepare(
            "INSERT INTO user_settings (user_id, enable_push, enable_sound, profile_pic)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
             enable_push = VALUES(enable_push),
             enable_sound = VALUES(enable_sound),
             profile_pic = VALUES(profile_pic)"
        );

        $stmt->execute([$userId, $push, $sound, $currentPic]);
        $pdo->commit();

        $response['success'] = true;
        $response['profile_pic'] = $currentPic;
    } catch (Exception $e) {
        $pdo->rollBack();
        $response['error'] = $e->getMessage();
    }

    echo json_encode($response);
    exit;
}