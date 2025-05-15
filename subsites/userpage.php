<?php
require_once '../php/auth.php';
$currentUser = verifyUserSession();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <!-- Replace existing CSP with -->
    <meta http-equiv="Content-Security-Policy" content="default-src 'self' data: gap: https://ssl.gstatic.com;
              style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net;
              font-src 'self' https://cdn.jsdelivr.net data:;
              img-src 'self' data: blob:;
              script-src 'self' 'unsafe-inline' 'unsafe-eval';
              connect-src 'self' *;
              media-src 'self' blob:;
              frame-src 'none'">
    <title>Homepage</title>
    <link rel="stylesheet" href="../css/userpage.css" />
    <link rel="stylesheet" href="../bootstrap-5.2.3-dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        /* Modal header and footer styling */
        .custom-header,
        .custom-footer {
            background-color: #003f22;
            color: white;
            border: none;
        }

        /* Section titles */
        .section-title {
            color: #003f22;
            font-weight: bold;
            margin-top: 1rem;
            margin-bottom: 0.5rem;
        }

        /* Scrollable table container */
        .table-wrapper {
            max-height: 200px;
            overflow-y: auto;
            margin-bottom: 1.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        /* Table header */
        .table thead {
            background-color: #003f22;
            color: white;
        }

        /* Make modal responsive on small devices */
        @media (max-width: 576px) {
            .modal-xl {
                width: 95% !important;
                margin: auto;
            }
        }

        #notificationList {
            scrollbar-width: thin;
            scrollbar-color: #888 transparent;
        }

        #notificationList::-webkit-scrollbar {
            width: 6px;
        }

        #notificationList::-webkit-scrollbar-track {
            background: transparent;
        }

        #notificationList::-webkit-scrollbar-thumb {
            background-color: #888;
            border-radius: 3px;
        }

        .notification-item {
            transition: background-color 0.2s;
            border-left: 3px solid transparent;
        }

        .notification-item.unread {
            border-left-color: #0d6efd;
            background-color: #f8f9fa;
        }

        .list-group-item small {
            font-size: 0.75rem;
        }

        /* Add to your main CSS file */
        .toast {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: rgba(0, 0, 0, 0.9);
            color: white;
            padding: 15px 25px;
            border-radius: 8px;
            z-index: 10000;
            animation: slideIn 0.3s ease-out;
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
            }

            to {
                transform: translateX(0);
            }
        }

        .profile-pic {
            border-radius: 50%;
            width: 100px;
            height: 100px;
            object-fit: cover;
        }

        #notificationList .unread {
            background-color: #f8f9fa;
            border-left: 4px solid #0d6efd;
        }
    </style>
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-md bg-body-tertiary fixed-top" style="padding: 0 15px; background-color: #003f22">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <img src="../resources/studyhubLogoHomepage.jpg" alt="ForrestHubLogo" width="100px" height="58px"
                    style="margin-right: 70px" />
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                data-bs-target="#navbarSupportedContent">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <ul class="navbar navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a href="#"><button class="btn btnActive" id="navButton">Home</button></a>
                    </li>
                    <li class="nav-item">
                        <a href="userpage_subpage/shop.php"><button class="btn" id="navButton">Shop</button></a>
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
                        <a href="userpage_subpage/about.php"><button class="btn" id="navButton">About Us</button></a>
                    </li>

                </ul>
                <form class="d-flex" role="search">
                    <input id="searchBar" class="form-control me-2" type="search" placeholder="Search" />
                </form>
                <div class="d-flex align-items-center">
                    <!-- Bootstrap & Icons already included -->
                    <div class="dropdown" style="position: relative;">
                        <!-- Bell Icon Trigger -->
                        <a href="#" id="notificationsButton" role="button" data-bs-toggle="dropdown"
                            aria-expanded="false">
                            <img id="navIcon" src="../resources/icons/bell-3-xxl.png" alt="Notifications" width="50"
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
                            src="../resources/icons/contacts-xxl.png" alt="Profile" width="50" height="50" />
                    </a>
                    <div class="dropdown" style="position: relative;">
                        <a href="#" id="settingsButton" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <img id="navIcon" src="../resources/icons/settings-10-xxl.png" alt="Settings" width="50"
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

    <!-- Page Content -->
    <div class="content">
        <div class="background_image">
            <img src="../resources/banner.PNG" alt="Forest Hub Logo" />
            <div class="timer-container">
                <h3 style="color: black;">Your Study Session</h3>
                <div id="userTimerDisplay"></div>
                <div id="sessionInfo"></div>
                <div class="progress" style="height: 10px; margin-top: 10px;">
                    <div id="timeProgressBar" class="progress-bar" role="progressbar"></div>
                </div>
            </div>
        </div>

        <div class="main_content">

            <div class="cardContent d-none d-md-flex">
                <div class="row" id="desktopTopProducts">
                    <!-- Dynamic content will be inserted here -->
                </div>
            </div>

            <!-- Mobile Carousel -->
            <div id="mobileTopProducts" class="carousel slide d-md-none" data-bs-ride="carousel">
                <div class="carousel-inner">
                    <!-- Dynamic content will be inserted here -->
                </div>
                <button class="carousel-control-prev" type="button" data-bs-target="#mobileTopProducts"
                    data-bs-slide="prev">
                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Previous</span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#mobileTopProducts"
                    data-bs-slide="next">
                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Next</span>
                </button>
            </div>
        </div>

        <div class="forstHubFaqs container mt-5">
            <h1 class="text-center mb-4">ForestHub Facts and Rules</h1>

            <div class="row mb-5 mt-5 text-center text-md-start">
                <div
                    class="col-md-5 mb-4 mb-md-0 d-flex justify-content-center justify-content-md-end align-items-center fqsImg">
                    <img src="/ForrestStudy_Hub/resources/rules.jpg" class="img-fluid rounded" alt="Study Hub Rules"
                        width="230px" height="130px">
                </div>
                <div class="col-md-7 fqsText">
                    <h4>Study Hub Rules</h4>
                    <p>1. All users must log in to their account to book and use any service.</p>
                    <p>2. Study cubicles and rooms must be booked in advance through the system.</p>
                    <p>3. Users must check out immediately after their session to avoid excess time charges.</p>
                    <p>4. Only the person who placed the food order may claim it, unless otherwise authorized.</p>
                    <p>5. Keep the space clean and take care of the cubicle or room you’re using.</p>
                </div>
            </div>
            <hr>
            <div class="row mb-5 mt-5 text-center text-md-start">
                <div
                    class="col-md-5 mb-4 mb-md-0 d-flex justify-content-center justify-content-md-end align-items-center fqsImg">
                    <img src="/ForrestStudy_Hub/resources/timer.PNG" class="img-fluid rounded"
                        alt="Timer, Rates, and Exceeding Time" width="430" height="230">
                </div>

                <!-- Text and Tables Section -->
                <div class="col-md-7 fqsText">
                    <h4>Timer, Rates, and Exceeding Time</h4>

                    <div class="row mt-3">
                        <!-- Cubicle Time Rates Table -->
                        <div class="col-sm-6 mb-3">
                            <h5 class="fw-semibold">Cubicle Time Rates</h5>
                            <table class="table table-bordered table-sm text-center">
                                <thead class="table" style="background-color:rgba(0, 154, 82, 0.59); color: black;">
                                    <tr>
                                        <th>Duration</th>
                                        <th>Rate (₱)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>1 Hour</td>
                                        <td>₱70</td>
                                    </tr>
                                    <tr>
                                        <td>1.5 Hours</td>
                                        <td>₱80</td>
                                    </tr>
                                    <tr>
                                        <td>2 Hours</td>
                                        <td>₱90</td>
                                    </tr>
                                    <tr>
                                        <td>2.5 Hours</td>
                                        <td>₱100</td>
                                    </tr>
                                    <tr>
                                        <td>3 Hours</td>
                                        <td>₱110</td>
                                    </tr>
                                    <tr>
                                        <td>3.5 Hours</td>
                                        <td>₱120</td>
                                    </tr>
                                    <tr>
                                        <td>4 Hours</td>
                                        <td>₱130</td>
                                    </tr>
                                </tbody>
                            </table>
                            <p class="small text-muted">If time is exceeded, an additional ₱40 is charged every 10
                                minutes.</p>
                        </div>

                        <!-- Room Time Rates Table -->
                        <div class="col-sm-6 mb-3">
                            <h5 class="fw-semibold">Room Time Rates</h5>
                            <table class="table table-bordered table-sm text-center">
                                <thead class="table" style="background-color:rgba(0, 154, 82, 0.59); color: black;">
                                    <tr>
                                        <th>Duration</th>
                                        <th>Rate (₱)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>1 Hour</td>
                                        <td>₱85</td>
                                    </tr>
                                    <tr>
                                        <td>1.5 Hours</td>
                                        <td>₱95</td>
                                    </tr>
                                    <tr>
                                        <td>2 Hours</td>
                                        <td>₱105</td>
                                    </tr>
                                    <tr>
                                        <td>2.5 Hours</td>
                                        <td>₱115</td>
                                    </tr>
                                    <tr>
                                        <td>3 Hours</td>
                                        <td>₱125</td>
                                    </tr>
                                    <tr>
                                        <td>3.5 Hours</td>
                                        <td>₱135</td>
                                    </tr>
                                    <tr>
                                        <td>4 Hours</td>
                                        <td>₱145</td>
                                    </tr>
                                </tbody>
                            </table>
                            <p class="small text-muted">If time is exceeded, an additional ₱40 is charged every 10
                                minutes.</p>
                        </div>
                    </div>
                </div>
            </div>
            <hr>
            <div class="row mb-5 mt-5 text-center text-md-start">
                <div
                    class="col-md-5 mb-4 mb-md-0 d-flex justify-content-center justify-content-md-end align-items-center fqsImg">
                    <img src="/ForrestStudy_Hub/resources/payment.PNG" class="img-fluid rounded" alt="Study Hub Rules"
                        width="230px" height="130px">
                </div>
                <div class="col-md-7 fqsText">
                    <h4>How to Pay for Your Time</h4>
                    <p>After booking, the system will display a summary of your <br> reservation along with the total
                        amount
                        due.
                        To complete <br> your payment, please proceed to the front desk and pay <br> in cash only.
                        The total cost will include your reserved time <br> and any additional charges if you exceed the
                        time
                        limit.</p>
                </div>
            </div>
            <hr>
            <div class="row mb-5 mt-5 text-center text-md-start">
                <div
                    class="col-md-5 mb-4 mb-md-0 d-flex justify-content-center justify-content-md-end align-items-center fqsImg">
                    <img src="/ForrestStudy_Hub/resources/shop.PNG" class="img-fluid rounded" alt="Study Hub Rules"
                        width="480px" height="130px">
                </div>
                <div class="col-md-7 fqsText">
                    <h4>How to Order and Pay</h4>
                    <p>To order products, go to the Shop page and browse the available items. Once you find what you
                        need, add the products to your cart. When you're ready, open your cart and proceed to checkout.
                        You can choose to pay using GCash or cash at the counter, depending on your preference. Before
                        placing your order, you also have the option to leave a note with any special instructions or
                        requests.
                    <p>
                </div>
            </div>
            <hr>
            <div class="row mb-5 mt-5 text-center text-md-start">
                <div
                    class="col-md-5 mb-4 mb-md-0 d-flex justify-content-center justify-content-md-end align-items-center fqsImg">
                    <img src="/ForrestStudy_Hub/resources/booking.PNG" class="img-fluid rounded" alt="Study Hub Rules"
                        width="280px" height="130px">
                </div>
                <div class="col-md-7 fqsText">
                    <h4>How to Book a Cubicle or Study Room</h4>
                    <p>1. Click the "Booking" button on the navigation bar.</p>
                    <p>2. Choose between “Study Cubicle” or “Study Room”.</p>
                    <p>3. Select your cubicle number, desired duration, and booking date/time.</p>
                    <p>4. Click the “Book Cubicle” button to finalize your reservation.</p>
                </div>
            </div>
            <hr>
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

    <!-- History Modal -->
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

    <script src="../bootstrap-5.2.3-dist/js/bootstrap.bundle.min.js"></script>
    <script src="userpage_subpage/backend/userpage.js"></script>
    <script src="userpage_subpage/backend/booking.js"></script>
    <script src="userpage_subpage/backend/user_history.js"></script>
    <script src="userpage_subpage/backend/notifications.js"></script>
    <script src="userpage_subpage/backend/session-timer.js"></script>
    <script src="userpage_subpage/backend/settings.js"></script>

    <script>
        // ================== Logout Function ==================
        window.logout = function () {
            fetch("../php/logout.php", {
                method: "POST",
                credentials: "include",
                headers: {
                    'Content-Type': 'application/json',
                    'Cache-Control': 'no-cache'
                }
            })
                .then(response => {
                    if (!response.ok) throw new Error('Logout failed');
                    window.location.href = '../index.php?logout=' + Date.now();
                })
                .catch(error => {
                    console.error('Logout error:', error);
                    window.location.href = '../index.php?logout=force';
                });
        };
    </script>
</body>

</html>