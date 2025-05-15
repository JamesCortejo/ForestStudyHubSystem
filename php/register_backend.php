<?php

session_start();
include '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $firstname = trim($_POST["firstname"]);
    $lastname = trim($_POST["lastname"]);
    $username = trim($_POST["username"]);
    $email = trim($_POST["email"]);
    $password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];
    $phone_number = trim($_POST["phone_number"]);

    if (strlen($password) < 8) {
        $_SESSION['error'] = "Password must be at least 8 characters long.";
        header('Location: ../subsites/register.php');
        exit();
    }

    if (strlen($username) < 6) {
        $_SESSION['error'] = "Username must be at least 6 characters long.";
        header('Location: ../subsites/register.php');
        exit();
    }

    if (strlen($phone_number) < 11) {
        $_SESSION['error'] = "Phone number must be at least 11 digits long.";
        header('Location: ../subsites/register.php');
        exit();
    }

    if ($password !== $confirm_password) {
        $_SESSION['error'] = "Passwords do not match.";
        header('Location: ../subsites/register.php');
        exit();
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);

    if ($stmt->rowCount() > 0) {
        $_SESSION['error'] = "Username already exists.";
        header('Location: ../subsites/register.php');
        exit();
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->rowCount() > 0) {
        $_SESSION['error'] = "Email already exists.";
        header('Location: ../subsites/register.php');
        exit();
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("INSERT INTO users (firstname, lastname, username, email, phone_number, password, status, role) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    
    if ($stmt->execute([$firstname, $lastname, $username, $email, $phone_number, $hashedPassword, 'inactive', 'user'])) {
        $_SESSION['success'] = "Your account has been created, you can now log in.";
        header('Location: ../index.php');
        exit();
    } else {
        $_SESSION['error'] = "There was an error. Please try again.";
        header('Location: ../subsites/register.php');
        exit();
    }
}
?>
