<?php
require_once __DIR__ . '/session_config.php';
session_start();
require_once __DIR__ . '/../includes/db.php';

try {
    // Get user ID before destroying session
    $userId = $_SESSION['user_id'] ?? null;

    // Update user status FIRST
    if ($userId) {
        $stmt = $pdo->prepare('UPDATE users SET status = "inactive" WHERE id = ?');
        $stmt->execute([$userId]);
    }

    // Revoke Google token
    if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'google') {
        require_once '../vendor/autoload.php';
        $client = new Google_Client();
        $client->setClientId($_ENV['GOOGLE_CLIENT_ID']);
        if (isset($_SESSION['google_token'])) {
            $client->revokeToken($_SESSION['google_token']);
        }
    }

    // Remove database session
    if (isset($_COOKIE[session_name()])) {
        $stmt = $pdo->prepare('DELETE FROM user_sessions WHERE session_id = ?');
        $stmt->execute([session_id()]);
    }

    // Clear all session data
    $_SESSION = [];

    // Destroy session
    if (session_id()) {
        setcookie(session_name(), '', [
            'expires' => time() - 3600,
            'path' => '/',
            'domain' => '',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
        session_destroy();
    }

    // JSON response
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    exit();

} catch (Exception $e) {
    error_log("Logout error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error']);
    exit();
}
?>