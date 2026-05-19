<?php
session_start();

$host = 'localhost';
$dbname = 'pet_adoption_system';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Helper function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Helper function to check user type
function isAdmin() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
}

function isShelter() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'shelter';
}

function isAdopter() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'adopter';
}

// Helper function to redirect
function redirect($url) {
    header("Location: $url");
    exit();
}
?>