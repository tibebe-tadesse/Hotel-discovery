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

// Display success/error messages
if (isset($_SESSION['success_message'])) {
    $success = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

if (isset($_SESSION['error_message'])) {
    $error = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

// Fetch all hotels with their images and average ratings
$stmt = $pdo->query("
    SELECT h.*, 
           MAX(hi.image_url) as image_url,
           COUNT(DISTINCT b.id) as booking_count,
           SUM(CASE WHEN b.status = 'confirmed' THEN 1 ELSE 0 END) as active_bookings,
           ROUND(AVG(r.rating), 1) as avg_rating,
           COUNT(DISTINCT r.id) as review_count
    FROM hotels h
    LEFT JOIN bookings b ON h.id = b.hotel_id
    LEFT JOIN hotel_images hi ON h.id = hi.hotel_id AND hi.is_primary = 1
    LEFT JOIN reviews r ON h.id = r.hotel_id
    WHERE h.deleted_at IS NULL
    GROUP BY h.id, h.owner_id, h.name, h.description, h.address, h.city, 
             h.country, h.postal_code, h.latitude, h.longitude, h.star_rating,
             h.base_price, h.check_in_time, h.check_out_time, h.status,
             h.created_at, h.updated_at, h.deleted_at
    ORDER BY h.created_at DESC
");
$hotels = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Hotels - Admin Panel</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="../css/admin-hotels.css">
</head>
<body>
    <header>
        <?php include '../includes/header.php'; ?>
    </header>
    <main>
        <div class="admin-container">
            <div class="admin-header">
                <h1>Manage Hotels</h1>
                <a href="add_hotel.php" class="add-button">Add New Hotel</a>
            </div>

            <?php if (isset($success)): ?>
                <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="hotels-table">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Location</th>
                            <th>Base Price</th>
                            <th>Rating</th>
                            <th>Status</th>
                            <th>Bookings</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($hotels as $hotel): ?>
                            <tr>
                                <td><?php echo $hotel['id']; ?></td>
                                <td>
                                    <img src="<?php echo $hotel['image_url'] ? htmlspecialchars($hotel['image_url']) : '../images/default-hotel.jpg'; ?>" 
                                         alt="<?php echo htmlspecialchars($hotel['name']); ?>"
                                         class="hotel-thumbnail">
                                </td>
                                <td><?php echo htmlspecialchars($hotel['name']); ?></td>
                                <td><?php echo htmlspecialchars($hotel['city']) . ', ' . htmlspecialchars($hotel['country']); ?></td>
                                <td>$<?php echo htmlspecialchars($hotel['base_price']); ?></td>
                                <td>
                                    <?php if ($hotel['review_count'] > 0): ?>
                                        <?php echo htmlspecialchars($hotel['avg_rating']); ?>/5
                                        <br>
                                        <small>(<?php echo $hotel['review_count']; ?> reviews)</small>
                                    <?php else: ?>
                                        No ratings
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars(ucfirst($hotel['status'])); ?></td>
                                <td>
                                    Total: <?php echo $hotel['booking_count']; ?><br>
                                    Active: <?php echo $hotel['active_bookings']; ?>
                                </td>
                                <td class="actions">
                                    <a href="edit_hotel.php?id=<?php echo $hotel['id']; ?>" class="edit-button">Edit</a>
                                    <form method="POST" action="delete_hotel.php" class="delete-form" 
                                          onsubmit="return confirm('Are you sure you want to delete this hotel? This will also delete all associated bookings.');">
                                        <input type="hidden" name="hotel_id" value="<?php echo $hotel['id']; ?>">
                                        <button type="submit" class="delete-button">Delete</button>
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