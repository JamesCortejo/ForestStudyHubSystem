<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enter New Password</title>
    <link rel="stylesheet" href="bootstrap-5.2.3-dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../bootstrap-5.2.3-dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/enternewpass.css">

<body>
    <div class="reset-container">
        <!-- Left Panel -->
        <div class="left-panel">
            <img src="../resources/banner.jpg" alt="Forrest Logo">
        </div>

        <!-- Right Panel -->
        <div class="right-panel">
            <h3>Enter New Password</h3>
            <p>Enter your new password and confirm it to reset your password.</p>

            <form action="../php/reset_password_backend.php" method="POST" style="width: 100%; align-items: center;">
                <input type="password" name="new_password" class="form-control mb-3" placeholder="New Password"
                    required>
                <input type="password" name="confirm_password" class="form-control mb-3"
                    placeholder="Confirm New Password" required>

                <div class="send-btn-container">
                    <button type="submit" class="reset-btn">Reset Password</button>
                </div>
            </form>
            <p class="signup-link">Remembered your password? <a style="color: #003f22;" href="../index.php">Login</a>
            </p>
        </div>
    </div>
    <script src="bootstrap-5.2.3-dist/js/bootstrap.min.js"></script>
</body>

</html>