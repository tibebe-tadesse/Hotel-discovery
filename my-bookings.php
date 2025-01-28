<?php
session_start();


$base_path = '';
require_once 'config/db_connect.php';
require_once 'includes/language.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Fetch user's bookings with hotel and room details
$stmt = $pdo->prepare("
    SELECT 
        b.id as booking_id,
        b.check_in,
        b.check_out,
        b.guests,
        b.total_price,
        b.status,
        b.payment_status,
        b.trx_no,
        h.name as hotel_name,
        h.address,
        h.city,
        rt.name as room_type,
        r.room_number,
        hi.image_url as hotel_image
    FROM bookings b
    JOIN hotels h ON b.hotel_id = h.id
    JOIN rooms r ON b.room_id = r.id
    JOIN room_types rt ON r.room_type_id = rt.id
    LEFT JOIN hotel_images hi ON h.id = hi.hotel_id AND hi.is_primary = 1
    WHERE b.user_id = :user_id
    ORDER BY b.check_in DESC
");

$stmt->execute(['user_id' => $_SESSION['user_id']]);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Helper function to get status badge class
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'confirmed':
            return 'bg-success';
        case 'pending':
            return 'bg-warning';
        case 'cancelled':
            return 'bg-danger';
        case 'completed':
            return 'bg-info';
        default:
            return 'bg-secondary';
    }
}
?>

<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang'] ?? 'en'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('my_bookings'); ?> - <?php echo __('site_name'); ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/bookings.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <header>
        <?php include 'includes/header.php'; ?>
    </header>

    <main>
        <div class="container">
            <h1 class="page-title"><?php echo __('my_bookings'); ?></h1>

            <?php if (empty($bookings)): ?>
                <div class="empty-bookings">
                    <i class="fas fa-calendar-times"></i>
                    <h3 class="mt-3"><?php echo __('no_bookings_found'); ?></h3>
                    <p class="text-muted"><?php echo __('no_bookings_message'); ?></p>
                    <a href="index.php" class="btn btn-primary mt-3"><?php echo __('browse_hotels'); ?></a>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($bookings as $booking): ?>
                        <div class="col-12 col-lg-6">
                            <div class="booking-card">
                                <div class="row g-0">
                                    <div class="col-md-4">
                                        <img src="<?php echo htmlspecialchars($booking['hotel_image'] ?? 'images/placeholder.jpg'); ?>" 
                                             class="booking-image" 
                                             alt="<?php echo htmlspecialchars($booking['hotel_name']); ?>">
                                    </div>
                                    <div class="col-md-8">
                                        <div class="card-body">
                                            <h5 class="hotel-name"><?php echo htmlspecialchars($booking['hotel_name']); ?></h5>
                                            <p class="location">
                                                <i class="fas fa-map-marker-alt me-2"></i>
                                                <?php echo htmlspecialchars($booking['city']); ?>
                                            </p>
                                            <div class="booking-details">
                                                <p><strong>Room:</strong> <?php echo htmlspecialchars($booking['room_type']); ?> 
                                                    (<?php echo htmlspecialchars($booking['room_number']); ?>)</p>
                                                <p><strong>Check-in:</strong> <?php echo date('M d, Y', strtotime($booking['check_in'])); ?></p>
                                                <p><strong>Check-out:</strong> <?php echo date('M d, Y', strtotime($booking['check_out'])); ?></p>
                                                <p><strong>Guests:</strong> <?php echo htmlspecialchars($booking['guests']); ?></p>
                                                <p><strong>Total:</strong> $<?php echo number_format($booking['total_price'], 2); ?></p>
                                            </div>
                                            <div class="status-badges d-flex justify-content-between align-items-center">
                                                <span class="badge <?php echo getStatusBadgeClass($booking['status']); ?>">
                                                    <?php echo ucfirst(htmlspecialchars($booking['status'])); ?>
                                                </span>
                                                <span class="badge <?php 
                                                    if (!empty($booking['trx_no'])) {
                                                        echo 'bg-success';
                                                        $paymentStatus = 'Paid';
                                                    } else {
                                                        echo getStatusBadgeClass($booking['payment_status']);
                                                        $paymentStatus = ucfirst(htmlspecialchars($booking['payment_status']));
                                                    }
                                                ?>">
                                                    <?php echo $paymentStatus; ?>
                                                    <?php if (!empty($booking['trx_no'])): ?>
                                                        <i class="fas fa-check-circle ms-1"></i>
                                                    <?php endif; ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <footer>
        <p><?php echo __('copyright'); ?></p>
    </footer>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/js/all.min.js"></script>
</body>
</html> 