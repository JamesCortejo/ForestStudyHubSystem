<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration</title>
    <link rel="stylesheet" href="../bootstrap-5.2.3-dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/registration.css">
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
    <div class="register-container">
        <!-- Left Panel -->
        <div class="left-panel">
            <img src="../resources/banner.jpg" alt="Forrest Logo" width="">
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
            <form method="POST" action="../php/register_backend.php">
                <div class="title text-center mb-3">
                    <h3>Registration</h3>
                </div>
                <div class="reg_inputs">
                    <div class="row">
                        <div class="col-md-6">
                            <input type="text" name="firstname" class="form-control mb-3" placeholder="First Name"
                                required>
                        </div>
                        <div class="col-md-6">
                            <input type="text" name="lastname" class="form-control mb-3" placeholder="Last Name"
                                required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <input type="number" name="phone_number" class="form-control mb-3"
                                placeholder="Phone Number" required>
                        </div>
                        <div class="col-md-6">
                            <input type="text" name="username" class="form-control mb-3" placeholder="Username"
                                required>
                        </div>
                    </div>
                    <input type="email" name="email" class="form-control mb-3" placeholder="Email Address" required>
                    <input type="text" name="address" class="form-control mb-3" placeholder="Address" required>
                    <input type="password" name="password" class="form-control mb-3" placeholder="Password" required>
                    <input type="password" name="confirm_password" class="form-control mb-3"
                        placeholder="Confirm Password" required>
                    <div class="register-btn-container">
                        <button type="submit" class="register-btn">Register</button>
                    </div>
                </div>
            </form>
            <p class="signup-link">Already Have an Account? <a style="color: #003f22;" href="../index.php">Sign-in</a>
            </p>
        </div>
    </div>
    <script src="../bootstrap-5.2.3-dist/js/bootstrap.min.js"></script>
</body>

</html>