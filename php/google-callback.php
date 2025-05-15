<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/session_config.php';
session_start();

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

try {
    $client = new Google_Client();
    $client->setClientId($_ENV['GOOGLE_CLIENT_ID']);
    $client->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET']);
    $client->setRedirectUri($_ENV['GOOGLE_REDIRECT']);

    if (!isset($_GET['code'])) {
        throw new Exception('Missing authorization code');
    }

    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);

    if (isset($token['error'])) {
        throw new Exception('Token error: ' . $token['error']);
    }

    $client->setAccessToken($token);
    $oauth2 = new Google_Service_Oauth2($client);
    $userInfo = $oauth2->userinfo->get();

    // Check if user exists in database
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$userInfo->getEmail()]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        // Create new user record
        $stmt = $pdo->prepare('INSERT INTO users 
            (username, email, firstname, lastname, role, registration_type, google_id, avatar_url)
            VALUES (?, ?, ?, ?, "user", "google", ?, ?)');

        $username = str_replace(' ', '_', $userInfo->getName()) . '_' . bin2hex(random_bytes(2));
        $stmt->execute([
            $username,
            $userInfo->getEmail(),
            $userInfo->getGivenName(),
            $userInfo->getFamilyName(),
            $userInfo->getId(),
            $userInfo->getPicture()
        ]);
        $userId = $pdo->lastInsertId();
    } else {
        $userId = $user['id'];
    }

    // Regenerate session ID
    session_regenerate_id(true);

    // Set session data
    $_SESSION = [
        'user_id' => $userId,
        'user_email' => $userInfo->getEmail(),
        'user_name' => $userInfo->getName(),
        'user_image' => $userInfo->getPicture(),
        'logged_in' => true,
        'user_type' => 'google',
        'role' => $user['role'] ?? 'user'
    ];

    // Update session in database
    $stmt = $pdo->prepare('REPLACE INTO user_sessions 
        (session_id, user_id, login_time, last_activity)
        VALUES (?, ?, NOW(), NOW())');
    $stmt->execute([session_id(), $userId]);

    header('Location: ../subsites/userpage.php');
    exit();

} catch (Exception $e) {
    error_log("Google Auth Error: " . $e->getMessage());
    $_SESSION['error'] = 'Google login failed. Please try again.';
    header('Location: ../index.php');
    exit();
}
?>