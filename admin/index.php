<?php
require_once '../config/db_connect.php';


$base_path = '../';

// Start session at the very beginning
session_start();

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

// Set base path for includes
$base_path = '../';

// Get statistics
$stats = [
    'total_hotels' => $pdo->query("SELECT COUNT(*) FROM hotels")->fetchColumn(),
    'total_users' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'total_bookings' => $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn(),
    'active_bookings' => $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'confirmed'")->fetchColumn(),
    'revenue' => $pdo->query("SELECT SUM(total_price) FROM bookings WHERE status != 'cancelled'")->fetchColumn() ?? 0
];

// Get recent bookings
$stmt = $pdo->query("
    SELECT b.*, h.name as hotel_name, u.username
    FROM bookings b
    JOIN hotels h ON b.hotel_id = h.id
    JOIN users u ON b.user_id = u.id
    ORDER BY b.created_at DESC
    LIMIT 5
");
$recent_bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get recent users
$stmt = $pdo->query("
    SELECT id, username, email, created_at
    FROM users
    ORDER BY created_at DESC
    LIMIT 5
");
$recent_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Admin Panel</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="../css/admin-dashboard.css">
</head>
<body>
    <header>
        <?php include '../includes/header.php'; ?>
    </header>

    <main>
        <div class="admin-container">
            <div class="admin-header">
                <h1>Dashboard</h1>
            </div>

            <div class="stats-grid">
                <a href="hotels.php" class="stat-card clickable">
                    <h3>Total Hotels</h3>
                    <p class="stat-number"><?php echo $stats['total_hotels']; ?></p>
                </a>
                <a href="users.php" class="stat-card clickable">
                    <h3>Total Users</h3>
                    <p class="stat-number"><?php echo $stats['total_users']; ?></p>
                </a>
                <a href="bookings.php" class="stat-card clickable">
                    <h3>Active Bookings</h3>
                    <p class="stat-number"><?php echo $stats['active_bookings']; ?></p>
                </a>
                <div class="stat-card revenue">
                    <h3>Total Revenue</h3>
                    <p class="stat-number">$<?php echo number_format($stats['revenue'], 2); ?></p>
                </div>
            </div>

            <div class="dashboard-grid">
                <div class="dashboard-card">
                    <h2>Recent Bookings</h2>
                    <div class="recent-list">
                        <?php foreach($recent_bookings as $booking): ?>
                            <div class="recent-item">
                                <div class="recent-info">
                                    <strong><?php echo htmlspecialchars($booking['hotel_name']); ?></strong>
                                    <span>by <?php echo htmlspecialchars($booking['username']); ?></span>
                                    <span class="status-badge <?php echo strtolower($booking['status']); ?>">
                                        <?php echo htmlspecialchars($booking['status']); ?>
                                    </span>
                                </div>
                                <div class="recent-meta">
                                    <span>$<?php echo htmlspecialchars($booking['total_price']); ?></span>
                                    <small><?php echo date('M d, Y', strtotime($booking['created_at'])); ?></small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <a href="bookings.php" class="view-all">View All Bookings</a>
                    </div>
                </div>

                <div class="dashboard-card">
                    <h2>Recent Users</h2>
                    <div class="recent-list">
                        <?php foreach($recent_users as $user): ?>
                            <div class="recent-item">
                                <div class="recent-info">
                                    <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                    <span><?php echo htmlspecialchars($user['email']); ?></span>
                                </div>
                                <div class="recent-meta">
                                    <small><?php echo date('M d, Y', strtotime($user['created_at'])); ?></small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <a href="users.php" class="view-all">View All Users</a>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer>
        <p>&copy; 2024 Hotel Discovery Platform</p>
    </footer>
</body>
</html> 