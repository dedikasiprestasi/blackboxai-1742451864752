<?php
session_start();

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'cbt_system');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
        DB_USER,
        DB_PASS,
        array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
    );
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['admin_id']);
}

// Function to redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

// Function to sanitize input
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Function to generate random string for access codes
function generateRandomString($length = 16) {
    return bin2hex(random_bytes($length));
}

// Function to format time remaining
function formatTimeRemaining($seconds) {
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $secs = $seconds % 60;
    return sprintf("%02d:%02d:%02d", $hours, $minutes, $secs);
}

// Function to check if exam time is expired
function isExamExpired($startTime, $duration) {
    $endTime = strtotime($startTime) + ($duration * 60);
    return time() > $endTime;
}

// Function to calculate score
function calculateScore($participantId) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total_correct
        FROM answers
        WHERE participant_id = ? AND is_correct = 1
    ");
    $stmt->execute([$participantId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total_questions
        FROM answers
        WHERE participant_id = ?
    ");
    $stmt->execute([$participantId]);
    $total = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($total['total_questions'] == 0) return 0;
    
    return ($result['total_correct'] / $total['total_questions']) * 100;
}