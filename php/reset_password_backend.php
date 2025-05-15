<?php
session_start();

date_default_timezone_set('Asia/Manila');

require '../includes/db.php'; // make sure this points to your DB connection file

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form values
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];

    // Check if passwords match
    if ($newPassword !== $confirmPassword) {
        $_SESSION['error'] = "Passwords do not match.";
        header("Location: ../subsites/enter_newpass.php");
        exit;
    }

    // Check if session variables are set
    if (!isset($_SESSION['email'], $_SESSION['reset_code'])) {
        $_SESSION['error'] = "Session expired. Please request a new password reset.";
        header("Location: ../subsites/passreset_email.php");
        exit;
    }

    $email = $_SESSION['email'];

    // Get the user ID from the email
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $_SESSION['error'] = "User not found.";
        header("Location: ../subsites/passreset_email.php");
        exit;
    }

    $userId = $user['id'];

    // Hash the new password
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

    // Update the password in the database
    $updateStmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
    if ($updateStmt->execute([$hashedPassword, $userId])) {
        // Optional: Delete the reset request
        $deleteResetStmt = $pdo->prepare("DELETE FROM password_reset_requests WHERE user_id = ?");
        $deleteResetStmt->execute([$userId]);

        // Clear session variables
        unset($_SESSION['reset_code']);
        unset($_SESSION['email']);

        $_SESSION['success'] = "Password successfully updated. You can now log in.";
        header("Location: ../index.php");
        exit;
    } else {
        $_SESSION['error'] = "Failed to update password.";
        header("Location: ../subsites/enter_newpass.php");
        exit;
    }
}
?>
