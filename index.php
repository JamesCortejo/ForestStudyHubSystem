<?php
session_start();

require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$siteKey = $_ENV['RECAPTCHA_SITE_KEY'] ?? getenv('RECAPTCHA_SITE_KEY');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="bootstrap-5.2.3-dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/login.css">
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
    <div class="login-container">
        <!-- Left Panel -->
        <div class="left-panel">
            <img src="resources/banner.jpg" alt="Forrest Logo">
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
            <h3>Login To Your Account</h3>
            <div class="social-icons d-flex">
                <a href="php/google-login.php"><img
                        src="resources/icons/4975303_search_web_internet_google search_search engine_icon.png"
                        alt="Gmail">
                </a>
            </div>

            <div class="divider">or</div>

            <form action="php/login_backend.php" method="POST" style="width: 100%; align-items: center;">
                <input type="text" name="username" class="form-control mb-3" placeholder="Username" require>
                <input type="password" name="password" class="form-control mb-3" placeholder="Password" require>
                <div class="d-flex justify-content-center mb-3">
                    <div class="g-recaptcha" data-sitekey="<?= htmlspecialchars($siteKey) ?>"></div>
                </div>
                <div class="login-btn-container">
                    <button type="submit" class="login-btn">Login</button>
                </div>
            </form>
            <div class="mt-2"></div>
            <p class="signup-link">New to Forrest? <a style="color: #003f22;" href="subsites/register.php">Signup</a>
            </p>
            <p class="signup-link">Forgot Password? <a style="color: #003f22;"
                    href="subsites/passreset_email.php">Reset</a></p>
        </div>
    </div>
    <script src="bootstrap-5.2.3-dist/js/bootstrap.min.js"></script>
    <script src="https://www.google.com/recaptcha/api.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('logout')) {
                // Clear client-side storage
                localStorage.clear();
                sessionStorage.clear();

                // Force reload without cache
                window.location.href = window.location.href.split('?')[0];
            }
        });
    </script>

</body>

</html>