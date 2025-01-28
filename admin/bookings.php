<?php
session_start();
require_once '../config/db_connect.php';

// Set base path for admin area
$base_path = '../';

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

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['booking_id']) && isset($_POST['status'])) {
    $booking_id = (int)$_POST['booking_id'];
    $status = $_POST['status'];
    
    if (in_array($status, ['confirmed', 'cancelled', 'completed'])) {
        $stmt = $pdo->prepare("UPDATE bookings SET status = ? WHERE id = ?");
        $stmt->execute([$status, $booking_id]);
    }
}

// Fetch all bookings with hotel and user details
$stmt = $pdo->query("
    SELECT b.*, h.name as hotel_name, h.city, h.country,
           u.username, u.email
    FROM bookings b
    JOIN hotels h ON b.hotel_id = h.id
    JOIN users u ON b.user_id = u.id
    ORDER BY b.created_at DESC
");
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Bookings - Admin Panel</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="../css/admin-bookings.css">
</head>
<body>
    <header>
        <?php include '../includes/header.php'; ?>
    </header>

    <main>
        <div class="admin-container">
            <div class="admin-header">
                <h1>Manage Bookings</h1>
            </div>

            <div class="bookings-table">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Hotel</th>
                            <th>Guest</th>
                            <th>Dates</th>
                            <th>Guests</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($bookings as $booking): ?>
                            <tr>
                                <td><?php echo $booking['id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($booking['hotel_name']); ?></strong><br>
                                    <small><?php echo htmlspecialchars($booking['city']) . ', ' . htmlspecialchars($booking['country']); ?></small>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($booking['username']); ?></strong><br>
                                    <small><?php echo htmlspecialchars($booking['email']); ?></small>
                                </td>
                                <td>
                                    Check-in: <?php echo date('M d, Y', strtotime($booking['check_in'])); ?><br>
                                    Check-out: <?php echo date('M d, Y', strtotime($booking['check_out'])); ?>
                                </td>
                                <td><?php echo htmlspecialchars($booking['guests']); ?></td>
                                <td>$<?php echo htmlspecialchars($booking['total_price']); ?></td>
                                <td>
                                    <span class="status-badge <?php echo strtolower($booking['status']); ?>">
                                        <?php echo htmlspecialchars($booking['status']); ?>
                                    </span>
                                </td>
                                <td class="actions">
                                    <form method="POST" class="status-form">
                                        <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                        <select name="status" onchange="this.form.submit()">
                                            <option value="">Change Status</option>
                                            <option value="confirmed">Confirm</option>
                                            <option value="cancelled">Cancel</option>
                                            <option value="completed">Complete</option>
                                        </select>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <footer>
        <p>&copy; 2024 Hotel Discovery Platform</p>
    </footer>
</body>
</html> 