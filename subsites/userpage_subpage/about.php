<?php
require_once __DIR__ . '/../../php/auth.php';
$currentUser = verifyUserSession();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Security-Policy" content="default-src 'self' data: gap: https://ssl.gstatic.com;
              style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net;
              font-src 'self' https://cdn.jsdelivr.net data:;
              img-src 'self' data: blob:;
              script-src 'self' 'unsafe-inline' 'unsafe-eval';
              connect-src 'self' *;
              media-src 'self' blob:;
              frame-src 'none'">
    <title>About US</title>
    <link rel="stylesheet" href="../../css/about.css">
    <link rel="stylesheet" href="../../bootstrap-5.2.3-dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>

<body>
    <nav class="navbar navbar-expand-md bg-body-tertiary fixed-top"
        style="padding-left: 15px; padding-right: 15px; background-color: #003f22;">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <img src="../../resources/studyhubLogoHomepage.jpg" alt="ForrestHubLogo" width="100px" height="58px"
                    style="margin-right: 70px;">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false"
                aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a href="../userpage.php" style="text-decoration: none;"><button class="btn"
                                id="navButton">Home</button></a>
                    </li>
                    <li class="nav-item">
                        <a href="shop.php" style="text-decoration: none;"><button class="btn"
                                id="navButton">Shop</button></a>
                    </li>
                    <li class="nav-item">
                        <button class="btn" id="navButton" data-bs-toggle="modal"
                            data-bs-target="#bookingModal">Booking</button>
                    </li>
                    <li class="nav-item">
                        <button class="btn" id="navButton" data-bs-toggle="modal"
                            data-bs-target="#historyModal">History</button>
                    </li>
                    <li class="nav-item">
                        <a href="#"><button class="btn btnActive" id="navButton">About Us</button></a>
                    </li>
                </ul>
                <form class="d-flex" role="search">
                    <input id="searchBar" class="form-control me-2" type="search" placeholder="Search"
                        aria-label="Search">
                </form>
                <div class="d-flex">
                    <div class="dropdown" style="position: relative;">
                        <!-- Bell Icon Trigger -->
                        <a href="#" id="notificationsButton" role="button" data-bs-toggle="dropdown"
                            aria-expanded="false">
                            <img id="navIcon" src="../../resources/icons/bell-3-xxl.png" alt="Notifications" width="50"
                                height="50" />
                        </a>

                        <!-- Notifications Panel -->
                        <div class="dropdown-menu dropdown-menu-end p-4" style="width: 400px; border-radius: 15px;">
                            <h5 class="dropdown-header mb-3">Notifications</h5>

                            <!-- Scrollable list group -->
                            <ul id="notificationList" class="list-group mb-3"
                                style="max-height: 250px; overflow-y: auto; scrollbar-width: thin;">
                                <!-- Notification Items here-->
                            </ul>

                            <div class="d-grid gap-2">
                                <a href="#" class="btn btn-outline-secondary btn-sm">View all notifications</a>
                                <button type="button" class="btn btn-danger btn-sm" id="clearNotificationsBtn">
                                    Clear Notifications
                                </button>
                            </div>
                        </div>
                    </div>
                    <a href="#" data-bs-toggle="modal" data-bs-target="#profileModal"><img id="navIcon"
                            src="../../resources/icons/contacts-xxl.png" alt="Profile" width="50" height="50" />
                    </a>
                    <div class="dropdown" style="position: relative;">
                        <a href="#" id="settingsButton" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <img id="navIcon" src="../../resources/icons/settings-10-xxl.png" alt="Settings" width="50"
                                height="50" />
                        </a>

                        <ul class="dropdown-menu dropdown-menu-end p-4" style="width: 350px; border-radius: 15px;">
                            <h5 class="dropdown-header">Notification Settings</h5>
                            <li class="form-check">
                                <input class="form-check-input" type="checkbox" id="enablePush">
                                <label class="form-check-label" for="enablePush">
                                    Enable Push Notifications
                                </label>
                            </li>
                            <li class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" id="enableSound">
                                <label class="form-check-label" for="enableSound">
                                    Enable Notification Sounds
                                </label>
                            </li>
                            <h5 class="dropdown-header">Profile Settings</h5>

                            <!-- Profile Picture Upload -->
                            <li class="mb-3 text-center">
                                <img id="profilePreview" src="/ForrestStudy_Hub/resources/pfpFolder/default-avatar.jpg"
                                    alt="Profile Picture" class="rounded-circle mb-2" width="80" height="80" />
                                <input type="file" class="form-control form-control-sm" id="profilePicture"
                                    accept="image/*">
                            </li>
                            <li class="mt-3">
                                <button type="button" class="btn btn-success btn-sm w-100" id="saveSettings">
                                    Save Settings
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="container about-section" style="margin-top: 30px;">
        <div class="row w-100 align-items-center">
            <!-- Image on the Left -->
            <div class="col-md-6 mb-4 mb-md-0">
                <h1 class="section-title mb-4">About ForRest Co-Working Space</h1>
                <p class="lead">
                    Welcome to <span class="highlight">ForRest Co-Working Space</span> — your destination for
                    <strong>productivity</strong>, <strong>comfort</strong>, and <strong>convenience</strong>.
                </p>
                <img src="/ForrestStudy_Hub/resources/about.PNG" alt="ForRest Workspace" class="about-image">
            </div>

            <!-- Text on the Right -->
            <div class="col-md-6">
                <p>
                    We believe that <strong>work, study, and rest</strong> should be balanced effortlessly, all within a
                    single, welcoming space. To make this vision possible, we created <span
                        class="highlight">TimeWise</span> — an easy-to-use online system that lets you:
                </p>
                <ul class="list-unstyled">
                    <li><span class="icon">📅</span>Book spaces in real-time</li>
                    <li><span class="icon">⏱️</span>Track your time efficiently</li>
                    <li><span class="icon">☕</span>Order coffee or snacks without leaving your desk</li>
                </ul>
                <p>No more lines, no more confusion — just focus on what matters most to you.</p>

                <h5 class="mt-4 mb-3">We’re committed to:</h5>
                <ul class="list-unstyled commitments">
                    <li><span class="icon">✅</span>Providing a calm, organized environment</li>
                    <li><span class="icon">✅</span>Simplifying your experience with smart tools</li>
                    <li><span class="icon">✅</span>Helping you work smarter and recharge better</li>
                </ul>

                <p class="mt-4">
                    We’re excited to be part of your journey toward greater productivity and better work-life balance.
                    <br>
                    <strong>Come study, work, and rest — the smart way, with TimeWise.</strong>
                </p>
            </div>
        </div>
    </div>

    <!-- Histry Modal -->
    <div class="modal fade" id="historyModal" tabindex="-1" aria-labelledby="historyModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content custom-modal">

                <div class="modal-header custom-header">
                    <h5 class="modal-title" id="historyModalLabel">User History</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <!-- Purchase History -->
                    <h6 class="section-title">Purchase History</h6>
                    <div class="table-wrapper">
                        <table class="table table-bordered table-sm">
                            <thead>
                                <tr>
                                    <th>Date & Time</th>
                                    <th>Total Amount</th>
                                    <th>Payment Method</th>
                                </tr>
                            </thead>
                            <tbody id="purchase-history-body">
                                <!-- JavaScript will insert rows here -->
                            </tbody>
                        </table>
                    </div>

                    <!-- Study Cubicle Sessions -->
                    <h6 class="section-title">Study Cubicle Sessions</h6>
                    <div class="table-wrapper">
                        <table class="table table-bordered table-sm">
                            <thead>
                                <tr>
                                    <th>Start Date & Time</th>
                                    <th>End Date & Time</th>
                                    <th>Cubicle #</th>
                                    <th>Set Time</th>
                                    <th>Exceeded Time</th>
                                    <th>Total Bill</th>
                                </tr>
                            </thead>
                            <tbody id="cubicle-history-body">
                                <!-- JavaScript will insert rows here -->
                            </tbody>
                        </table>
                    </div>

                    <!-- Study Room Sessions -->
                    <h6 class="section-title">Study Room Sessions</h6>
                    <div class="table-wrapper">
                        <table class="table table-bordered table-sm">
                            <thead>
                                <tr>
                                    <th>Start Date & Time</th>
                                    <th>End Date & Time</th>
                                    <th>Room</th>
                                    <th>Set Time</th>
                                    <th>Exceeded Time</th>
                                    <th>Total Bill</th>
                                </tr>
                            </thead>
                            <tbody id="room-history-body">
                                <!-- JavaScript will insert rows here -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="modal-footer custom-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                </div>

            </div>
        </div>
    </div>

    <!-- Booking Modal -->
    <div class="modal fade" id="bookingModal" tabindex="-1" aria-labelledby="bookingModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="bookingModalLabel">Book a Space</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Sticky Tabs -->
                    <div class="sticky-top bg-white pt-3" style="z-index: 1; top: -1px;">
                        <ul class="nav nav-tabs mb-4" id="bookingTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="cubicle-tab" data-bs-toggle="tab"
                                    data-bs-target="#cubicle" type="button" role="tab">
                                    <i class="bi bi-door-closed me-2"></i>Study Cubicle
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="room-tab" data-bs-toggle="tab" data-bs-target="#room"
                                    type="button" role="tab">
                                    <i class="bi bi-building me-2"></i>Study Room
                                </button>
                            </li>
                        </ul>
                    </div>

                    <!-- Scrollable Content -->
                    <div class="tab-content" style="max-height: 60vh; overflow-y: auto;">
                        <!-- Cubicle Tab -->
                        <div class="tab-pane fade show active" id="cubicle" role="tabpanel">
                            <form id="cubicleForm">
                                <div class="mb-3">
                                    <label for="selectCubicle" class="form-label">Study Cubicle</label>
                                    <select class="form-select" id="selectCubicle" name="location_id" required>
                                        <option value="" selected>Select Cubicle</option>
                                        <!-- Options will be populated dynamically -->
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="selectCubicleTime" class="form-label">Duration (hours)</label>
                                    <select class="form-select" id="selectCubicleTime" name="duration" required>
                                        <option value="1">1 hour</option>
                                        <option value="1.5">1.5 hours</option>
                                        <option value="2">2 hours</option>
                                        <option value="2.5">2.5 hours</option>
                                        <option value="3">3 hours</option>
                                        <option value="3.5">3.5 hours</option>
                                        <option value="4">4 hours</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="cubicleBookingTime" class="form-label">Booking Time</label>
                                    <input type="datetime-local" class="form-control" id="cubicleBookingTime"
                                        name="booking_time" required>
                                </div>
                                <button type="submit" class="btn btn-success w-100 mb-4">
                                    Book Cubicle
                                </button>

                                <h6 class="fw-bold mb-3">Cubicle Availability</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover">
                                        <thead>
                                            <tr>
                                                <th>Cubicle</th>
                                                <th>Status</th>
                                                <th>Date</th>
                                                <th>Time</th>
                                            </tr>
                                        </thead>
                                        <tbody id="cubicleAvailability"></tbody>
                                    </table>
                                </div>
                            </form>
                        </div>

                        <!-- Room Tab -->
                        <div class="tab-pane fade" id="room" role="tabpanel">
                            <form id="roomForm">
                                <div class="mb-3">
                                    <label for="selectRoom" class="form-label">Study Room</label>
                                    <select class="form-select" id="selectRoom" name="location_id" required>
                                        <option value="" selected>Select Room</option>
                                        <!-- Options will be populated dynamically -->
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="selectRoomTime" class="form-label">Duration (hours)</label>
                                    <select class="form-select" id="selectRoomTime" name="duration" required>
                                        <option value="1">1 hour</option>
                                        <option value="1.5">1.5 hours</option>
                                        <option value="2">2 hours</option>
                                        <option value="2.5">2.5 hours</option>
                                        <option value="3">3 hours</option>
                                        <option value="3.5">3.5 hours</option>
                                        <option value="4">4 hours</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="roomBookingTime" class="form-label">Booking Time</label>
                                    <input type="datetime-local" class="form-control" id="roomBookingTime"
                                        name="booking_time" required>
                                </div>
                                <button type="submit" class="btn btn-success w-100 mb-4">
                                    Book Room
                                </button>

                                <h6 class="fw-bold mb-3">Room Availability</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover">
                                        <thead>
                                            <tr>
                                                <th>Room</th>
                                                <th>Status</th>
                                                <th>Date</th>
                                                <th>Time</th>
                                            </tr>
                                        </thead>
                                        <tbody id="roomAvailability"></tbody>
                                    </table>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Profile Modal -->
    <div class="modal fade" id="profileModal" tabindex="-1" aria-labelledby="profileModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="profileModalLabel">Profile Information</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <div class="mb-3">
                        <img src="/ForrestStudy_Hub/resources/pfpFolder/default-avatar.jpg" alt="Profile" width="100"
                            class="rounded-circle profile-image" />
                    </div>
                    <p><strong>First Name:</strong>
                        <?= htmlspecialchars($_SESSION['firstname'] ?? 'Guest'); ?>
                    </p>
                    <p><strong>Username:</strong>
                        <?= htmlspecialchars($_SESSION['username'] ?? 'Not available'); ?>
                    </p>
                    <p><strong>Role:</strong>
                        <?= htmlspecialchars($_SESSION['role'] ?? 'Guest'); ?>
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-danger" onclick="window.logout()">Logout</button>
                </div>
            </div>
        </div>
    </div>


    <footer class="text-light pt-5 pb-4">
        <div class="container text-center text-md-start">
            <div class="row">
                <!-- About the Company -->
                <div class="col-md-4 mb-2">
                    <h5 class="text-uppercase fw-bold mb-3">Solvem Probler</h5>
                    <p>
                        Solvem Probler is a software development company focused on building efficient, user-friendly
                        systems tailored to local businesses. We aim to simplify complex tasks through smart, reliable,
                        and innovative digital solutions.
                    </p>
                </div>

                <!-- Contact Info -->
                <div class="col-md-4 mb-2">
                    <h5 class="text-uppercase fw-bold mb-3">Contact Us</h5>
                    <p><i class="bi bi-geo-alt-fill me-2"></i>2nd Floor, Lim Estrada Building, Cudal st, Malaybalay
                        City, Bukidnon</p>
                    <p><i class="bi bi-telephone-fill me-2"></i>(+63) 929-683-0665</p>
                    <p><i class="bi bi-envelope-fill me-2"></i>2301105714@student.buksu.edu.ph</p>
                </div>

                <!-- Quick Links or Empty Column -->
                <div class="col-md-4 mb-2">
                    <h5 class="text-uppercase fw-bold mb-3">Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="/ForrestStudy_Hub/index.html" class="text-decoration-none text-light">Home</a></li>
                        <li><a href="/ForrestStudy_Hub/about.html" class="text-decoration-none text-light">About</a>
                        </li>
                        <li><a href="/ForrestStudy_Hub/contact.html" class="text-decoration-none text-light">Contact</a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Footer Bottom -->
            <div class="text-center">
                <hr class="border-secondary">
                <p class="mb-0">&copy; 2025 Solvem Probler. All rights reserved.</p>
            </div>
        </div>
    </footer>
    <script src="../../bootstrap-5.2.3-dist/js/bootstrap.bundle.min.js"></script>
    <script src="backend/notifications.js"></script>
    <script src="backend/session-timer.js"></script>
    <script src="backend/booking.js"></script>
    <script src="backend/shop_checkout.js"></script>
    <script src="backend/user_history.js"></script>
    <script src="backend/settings.js"></script>
    <script>
        window.logout = function () {
            fetch("../../php/logout.php", {
                method: "POST",
                credentials: "include",
                headers: {
                    'Content-Type': 'application/json'
                }
            })
                .then(response => {
                    if (!response.ok) throw new Error('Logout failed');
                    // Force cache busting
                    window.location.href = '../../index.php?logout=' + Date.now() + '&cache=' + Math.random();
                })
                .catch(error => {
                    console.error('Logout error:', error);
                    window.location.reload(true);
                });
        };
    </script>
</body>

</html>