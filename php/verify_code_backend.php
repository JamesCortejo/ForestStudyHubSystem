<?php
session_start();
require '../libs/phpmailer/src/PHPMailer.php';
require '../libs/phpmailer/src/SMTP.php';
require '../libs/phpmailer/src/Exception.php';
require '../includes/db.php';  // Include your database connection

date_default_timezone_set('Asia/Manila');

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the code entered by the user
    $entered_code = $_POST['reset_code'];

    // Get the email from session or request (assuming it's set when sending the reset code)
    $email = $_SESSION['reset_email'];

    // Fetch the user_id using the email
    $user_sql = "SELECT id FROM users WHERE email = ?";
    $user_stmt = $pdo->prepare($user_sql);
    $user_stmt->execute([$email]);
    $user = $user_stmt->fetch();

    if ($user) {
        // Check if the reset code is valid and exists in the password_reset_requests table
        $sql = "SELECT * FROM password_reset_requests WHERE user_id = ? AND reset_code = ? AND reset_code_expires > NOW()";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user['id'], $entered_code]);

        // If the code matches and is still valid (not expired)
        if ($stmt->rowCount() > 0) {
            // Mark the code as used (optional)
            // You can choose to either remove the record or flag it as used
            $update_sql = "DELETE FROM password_reset_requests WHERE user_id = ? AND reset_code = ?";
            $update_stmt = $pdo->prepare($update_sql);
            $update_stmt->execute([$user['id'], $entered_code]);

            // Redirect to the password reset page
            $_SESSION['email'] = $email;  // Set the email session variable
            header("Location: ../subsites/enter_newpass.php");
            exit();
        } else {
            // If the code doesn't match or has expired
            $_SESSION['error'] = 'Invalid or expired reset code.';
            header("Location: ../subsites/entercode.php");
            exit();
        }
    } else {
        // If no user with that email exists
        $_SESSION['error'] = 'Email address not found.';
        header("Location: enter_reset_code.php");
        exit();
    }
}
?>
