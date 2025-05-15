<?php
require_once __DIR__ . '/../../../includes/db.php'; // Contains your existing connection
require_once __DIR__ . '/../../../php/auth.php';

$currentUser = verifyUserSession();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (empty($_FILES['profile_pic'])) {
            throw new Exception('No file uploaded');
        }

        $file = $_FILES['profile_pic'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $maxSize = 2 * 1024 * 1024; // 2MB

        // File validation
        if (!in_array($file['type'], $allowedTypes)) {
            throw new Exception('Invalid file type. Only JPG, PNG, and GIF are allowed.');
        }

        if ($file['size'] > $maxSize) {
            throw new Exception('File size exceeds 2MB limit.');
        }

        // Get existing profile picture
        $stmt = $pdo->prepare("SELECT profile_pic FROM user_settings WHERE user_id = ?");
        $stmt->execute([$currentUser['id']]);
        $oldPic = $stmt->fetchColumn();

        // Delete old picture if exists
        if ($oldPic && $oldPic !== 'default-avatar.jpg') {
            $oldPath = $_SERVER['DOCUMENT_ROOT'] . '/ForrestStudy_Hub/resources/pfpFolder/' . $oldPic;
            if (file_exists($oldPath)) {
                unlink($oldPath);
            }
        }

        // Generate new filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid('pfp_') . '.' . $extension;
        $uploadPath = $_SERVER['DOCUMENT_ROOT'] . '/ForrestStudy_Hub/resources/pfpFolder/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
            throw new Exception('File upload failed');
        }

        // Update database using existing PDO connection
        $stmt = $pdo->prepare(
            "INSERT INTO user_settings (user_id, profile_pic)
             VALUES (?, ?)
             ON DUPLICATE KEY UPDATE profile_pic = VALUES(profile_pic)"
        );

        $success = $stmt->execute([$currentUser['id'], $filename]);

        echo json_encode([
            'success' => $success,
            'profile_pic' => $filename
        ]);

    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    exit;
}