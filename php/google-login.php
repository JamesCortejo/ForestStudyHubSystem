<?php
require_once '../vendor/autoload.php';
require_once __DIR__ . '/session_config.php';
session_start();

// Clear existing session data
$_SESSION = [];
session_regenerate_id(true);

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

$client = new Google_Client();
$client->setClientId($_ENV['GOOGLE_CLIENT_ID']);
$client->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET']);
$client->setRedirectUri($_ENV['GOOGLE_REDIRECT']);
$client->addScope([
    'email',
    'profile'
]);

// Proper parameter configuration
$client->setAccessType('offline');
$client->setPrompt('select_account');
$client->setIncludeGrantedScopes(true);

header('Location: ' . $client->createAuthUrl());
exit();
?>