<?php
session_start();
require_once '../config/db_connect.php';

// Check if user is admin
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Verify admin status
$stmt = $pdo->prepare("SELECT is_admin FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user || !$user['is_admin']) {
    header('Location: ../index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['hotel_id'])) {
    $hotel_id = (int)$_POST['hotel_id'];
    
    try {
        // First, delete related bookings
        $stmt = $pdo->prepare("DELETE FROM bookings WHERE hotel_id = ?");
        $stmt->execute([$hotel_id]);
        
        // Then delete the hotel
        $stmt = $pdo->prepare("DELETE FROM hotels WHERE id = ?");
        $stmt->execute([$hotel_id]);
        
        $_SESSION['success_message'] = 'Hotel deleted successfully';
    } catch (PDOException $e) {
        $_SESSION['error_message'] = 'Failed to delete hotel';
    }
}

header('Location: index.php');
exit();