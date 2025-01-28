<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Store the language preference before clearing session
$lang = $_SESSION['lang'] ?? 'en';

// Clear all session variables
$_SESSION = array();

// Restore language preference
$_SESSION['lang'] = $lang;

// Destroy the session
session_destroy();

// Start new session for language
session_start();
$_SESSION['lang'] = $lang;

// Redirect to home page
header('Location: index.php');
exit();