<?php
require_once '../includes/auth.php';

if (!Auth::hasRole(Auth::ROLE_ADMIN)) {
    header('Location: ../login.php');
    exit();
}

// Handle owner approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ownerId = $_POST['owner_id'];
    $action = $_POST['action'];
    
    $stmt = $pdo->prepare("
        UPDATE hotel_owners 
        SET status = ? 
        WHERE id = ?
    ");
    
    $stmt->execute([
        $action === 'approve' ? 'approved' : 'rejected',
        $ownerId
    ]);
}

// Fetch pending applications
$stmt = $pdo->prepare("
    SELECT ho.*, u.username, u.email
    FROM hotel_owners ho
    JOIN users u ON ho.user_id = u.id
    WHERE ho.status = 'pending'
    ORDER BY ho.created_at DESC
");
$stmt->execute();
$pendingOwners = $stmt->fetchAll(); 