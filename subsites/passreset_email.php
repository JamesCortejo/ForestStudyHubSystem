<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link rel="stylesheet" href="bootstrap-5.2.3-dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../bootstrap-5.2.3-dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/resetpass.css">
    <style>
        .alert-container {
            position: absolute;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 9999;
            width: 80%;
            max-width: 500px;
        }
    </style>
</head>

<body>
    <div class="reset-container">
        <!-- Left Panel -->
        <div class="left-panel">
            <img src="../resources/banner.jpg" alt="Forrest Logo">
        </div>

        <!-- Right Panel -->
        <div class="right-panel">
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert-container">
                    <div class="alert alert-danger" role="alert">
                        <?= $_SESSION['error'];
                        unset($_SESSION['error']); ?>
                    </div>
                </div>
            <?php endif; ?>
            <h3>Reset Your Password</h3>
            <p>Enter your email address to receive a password reset link.</p>

            <form action="../php/reset_passwordemail_backend.php" method="POST"
                style="width: 100%; align-items: center;">
                <input type="email" name="email" class="form-control mb-3" placeholder="Enter your email" required>
                <div class="send-btn-container">
                    <button type="submit" class="reset-btn">Send Reset Code</button>
                </div>
            </form>
            <p class="signup-link">Remember your password? <a style="color: #003f22;" href="../index.php">Login</a></p>
        </div>
    </div>
    <script src="bootstrap-5.2.3-dist/js/bootstrap.min.js"></script>
</body>

</html>