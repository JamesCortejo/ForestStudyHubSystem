<?php
// Set session parameters BEFORE starting session
session_set_cookie_params([
    'lifetime' => 86400 * 7, // 1 week
    'path' => '/ForrestStudy_Hub/',
    'domain' => '',
    'secure' => isset($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Lax'
]);

// Custom session name
session_name('FSH_SESSION');

// Session garbage collection
ini_set('session.gc_maxlifetime', 86400 * 7);
ini_set('session.gc_probability', 1);
ini_set('session.gc_divisor', 100);
?>