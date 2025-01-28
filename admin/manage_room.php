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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hotel_id = (int)$_POST['hotel_id'];
    $action = $_POST['action'];

    try {
        switch ($action) {
            case 'add':
                $stmt = $pdo->prepare("
                    INSERT INTO rooms (hotel_id, room_type_id, room_number, floor, status, notes)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $hotel_id,
                    $_POST['room_type_id'],
                    $_POST['room_number'],
                    $_POST['floor'],
                    $_POST['status'],
                    $_POST['notes']
                ]);
                $_SESSION['success_message'] = 'Room added successfully!';
                break;

            case 'edit':
                $stmt = $pdo->prepare("
                    UPDATE rooms 
                    SET room_type_id = ?, room_number = ?, floor = ?, status = ?, notes = ?
                    WHERE id = ? AND hotel_id = ?
                ");
                $stmt->execute([
                    $_POST['room_type_id'],
                    $_POST['room_number'],
                    $_POST['floor'],
                    $_POST['status'],
                    $_POST['notes'],
                    $_POST['room_id'],
                    $hotel_id
                ]);
                $_SESSION['success_message'] = 'Room updated successfully!';
                break;

            case 'delete':
                $stmt = $pdo->prepare("
                    UPDATE rooms 
                    SET deleted_at = CURRENT_TIMESTAMP
                    WHERE id = ? AND hotel_id = ?
                ");
                $stmt->execute([$_POST['room_id'], $hotel_id]);
                $_SESSION['success_message'] = 'Room deleted successfully!';
                break;
        }
    } catch (PDOException $e) {
        $_SESSION['error_message'] = 'Error managing room: ' . $e->getMessage();
    }

    header("Location: edit_hotel.php?id=$hotel_id");
    exit();
}

header('Location: hotels.php');
exit(); 