<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$base_path = '';
require_once 'config/db_connect.php';

// Get the raw POST data
$trx_no = $_GET['trx_no'];

// Check if the data was decoded successfully
if ($trx_no) {
    // Update booking status to 'paid'
    $stmt = $pdo->prepare("UPDATE bookings SET status = 'confirmed' WHERE trx_no = ?");
    $stmt->execute([$trx_no]);

    $_SESSION['success_message'] = __('booking_confirmed');

    // Redirect user to the bookings page
    header("Location: bookings.php?trx_no=$trx_no");
    exit();
} else {
    // Update booking status to 'payment_failed'
    $stmt = $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE trx_no = ?");
    $stmt->execute([$trx_no]);

    // Log the failure for debugging
    $error_message = isset($data['message']) ? $data['message'] : __('error_occurred');
    error_log("Payment failed for trx_no: $trx_no. Reason: $error_message");

    $_SESSION['error_message'] = __('error_occurred');

    // Redirect user to the bookings page
    header("Location: bookings.php");
    exit();
}

// Send an appropriate HTTP response to Chapa (200 OK)
http_response_code(200);
?>
