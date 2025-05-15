<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "foreststudyhub_sample";

$conn = new mysqli($servername, $username, $password, $dbname);

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
} catch (PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}
?>