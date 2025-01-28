<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set default language
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'en';
}

// Handle language change
if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
}

// Load language file
$translations = include_once dirname(__DIR__) . "/lang/{$_SESSION['lang']}.php";

// Translation helper function
function __($key) {
    global $translations;
    return isset($translations[$key]) ? $translations[$key] : $key;
} 