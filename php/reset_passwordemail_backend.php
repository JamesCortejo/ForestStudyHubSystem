<?php
session_start();

// Include PHPMailer classes
require '../libs/phpmailer/src/PHPMailer.php';
require '../libs/phpmailer/src/SMTP.php';
require '../libs/phpmailer/src/Exception.php';
require('../includes/db.php');

date_default_timezone_set('Asia/Manila');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendResetCode($email, $resetCode) {
    $mail = new PHPMailer(true);
    
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; 
        $mail->SMTPAuth = true;
        $mail->Username = '2301105714@student.buksu.edu.ph';  // Gmail address
        $mail->Password = 'jkiz kmqn bddu yskh';  //Gmail app password (generated app password)
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;  // TCP port for Gmail

        // Recipients
        $mail->setFrom('2301105714@student.buksu.edu.ph', 'ForestStudyHub');  // App Name
        $mail->addAddress($email);  // Recipient's email address

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Reset Your Password';
        $mail->Body    = "To reset your password for ForestStudyHub, please use the following code: <strong>$resetCode</strong>";

        // Send the email
        $mail->send();

        return true; // Email sent successfully
    } catch (Exception $e) {
        // If there's an error sending the email
        $_SESSION['error'] = "Mailer Error: " . $mail->ErrorInfo;
        return false;
    }
}

// Example of usage (Call this function when the user submits their email)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $email = $_POST['email'];

    // Check if the email is valid
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Please enter a valid email address.";
        header("Location: ../subsites/passreset_email.php");
        exit;
    }

    // Generate a random reset code
    $resetCode = rand(100000, 999999);  // 6-digit reset code

    // Set expiration time (e.g., 15 minutes from now)
    $resetCodeExpires = date('Y-m-d H:i:s', strtotime('+15 minutes'));

    // Check if the email exists in the users table
    $sql = "SELECT id FROM users WHERE email = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        // Insert reset code into password_reset_requests table
        $insertSql = "INSERT INTO password_reset_requests (user_id, reset_code, reset_code_expires) 
                      VALUES (?, ?, ?)";
        $insertStmt = $pdo->prepare($insertSql);
        $insertStmt->execute([$user['id'], $resetCode, $resetCodeExpires]);

        // Store reset code in session
        $_SESSION['reset_code'] = $resetCode;  // Save reset code in session for later use
        $_SESSION['reset_email'] = $email;  // Store email in session

        // Send the reset code via email
        if (sendResetCode($email, $resetCode)) {
            // Redirect to the page where the user can enter the reset code
            header("Location: ../subsites/entercode.php");
            exit;
        } else {
            // Redirect back to the form with an error
            $_SESSION['error'] = "Error sending reset code.";
            header("Location: ../subsites/passreset_email.php");
            exit;
        }
    } else {
        // If the email doesn't exist
        $_SESSION['error'] = "Email not found. Please check the email and try again.";
        header("Location: ../subsites/passreset_email.php");
        exit;
    }
}
?>
