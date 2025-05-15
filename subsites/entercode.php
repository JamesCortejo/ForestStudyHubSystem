<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enter Reset Code</title>
    <link rel="stylesheet" href="bootstrap-5.2.3-dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../bootstrap-5.2.3-dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/entercode.css">
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
            <h3>Enter the Code Sent to Your Email</h3>
            <p>Please enter the code you received in your email to proceed with resetting your password.</p>

            <form action="../php/verify_code_backend.php" method="POST" style="width: 100%; align-items: center;">
                <input type="text" name="reset_code" class="form-control mb-3" placeholder="Enter your reset code"
                    required>
                <div class="send-btn-container">
                    <button type="submit" class="reset-btn">Verify Code</button>
                </div>
            </form>
            <p class="signup-link">Didn't receive the code? <a style="color: #003f22;"
                    href="subsites/resetpass.php">Resend</a></p>
        </div>
    </div>
    <script src="bootstrap-5.2.3-dist/js/bootstrap.min.js"></script>
</body>

</html>