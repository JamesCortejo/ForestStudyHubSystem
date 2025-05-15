<?php
session_start();
require_once __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();
require_once __DIR__ . '/../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $recaptchaSecret = $_ENV['RECAPTCHA_SECRET_KEY'];
    $recaptchaResponse = $_POST['g-recaptcha-response'];
    $verifyUrl = "https://www.google.com/recaptcha/api/siteverify?secret={$recaptchaSecret}&response={$recaptchaResponse}";
    $verifyResponse = file_get_contents($verifyUrl);

    if ($verifyResponse === false) {
        $_SESSION['error'] = "Failed to contact reCAPTCHA service. Please try again.";
        header("Location: /ForrestStudy_Hub/index.php");
        exit();
    }

    $responseData = json_decode($verifyResponse);
    if (!$responseData->success) {
        $_SESSION['error'] = "reCAPTCHA verification failed. Please try again.";
        header("Location: /ForrestStudy_Hub/index.php");
        exit();
    }

    try {
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ? COLLATE utf8mb4_general_ci');
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $newSessionId = bin2hex(random_bytes(32));
            $stmt = $pdo->prepare('INSERT INTO user_sessions 
                                  (session_id, user_id) 
                                  VALUES (?, ?)');
            $stmt->execute([$newSessionId, $user['id']]);
            setcookie('FSH_SESSION', $newSessionId, [
                'expires' => time() + (86400 * 30),
                'path' => '/ForrestStudy_Hub/',
                'secure' => true,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['firstname'] = $user['firstname'];

            $pdo->prepare('UPDATE users SET status = ? WHERE id = ?')
                ->execute(['active', $user['id']]);

            $redirect = ($user['role'] === 'admin')
                ? '../subsites/dashboard.html'
                : '../subsites/userpage.php';

            header("Location: $redirect");
            exit();
        } else {
            $_SESSION['error'] = "Invalid username or password";
        }
    } catch (PDOException $e) {
        error_log("Login error: " . $e->getMessage());
        $_SESSION['error'] = "System error. Please try again later.";
    }

    header('Location: /ForrestStudy_Hub/index.php');
    exit();
}
?>