<?php
require_once __DIR__ . '/session_config.php';
session_start();
require_once __DIR__ . '/../includes/db.php';

// Cleanup old sessions
$pdo->exec("DELETE FROM user_sessions 
           WHERE last_activity < NOW() - INTERVAL 24 HOUR");

function verifyUserSession()
{
    global $pdo;

    // Check existing session
    if (!empty($_SESSION['user_id'])) {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$_SESSION['user_id']]);

        if ($user = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Update session with latest user data
            $_SESSION['username'] = $user['username'];
            $_SESSION['firstname'] = $user['firstname'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['user_type'] = $user['registration_type'] === 'google' ? 'google' : 'regular';
            return $user;
        }
    }

    // Check persistent cookie
    if (isset($_COOKIE[session_name()])) {
        try {
            $stmt = $pdo->prepare('SELECT u.* FROM user_sessions s 
                                  JOIN users u ON s.user_id = u.id
                                  WHERE s.session_id = ?');
            $stmt->execute([session_id()]);

            if ($user = $stmt->fetch(PDO::FETCH_ASSOC)) {
                // Update session data
                $_SESSION = [
                    'user_id' => $user['id'],
                    'username' => $user['username'],
                    'role' => $user['role'],
                    'firstname' => $user['firstname'],
                    'user_type' => $user['registration_type'] === 'google' ? 'google' : 'regular'
                ];

                // Update last activity
                $pdo->prepare('UPDATE user_sessions 
                             SET last_activity = NOW() 
                             WHERE session_id = ?')
                    ->execute([session_id()]);

                return $user;
            }
        } catch (PDOException $e) {
            error_log("Session verification error: " . $e->getMessage());
        }
    }

    header('Location: ../index.php');
    exit();
}
?>