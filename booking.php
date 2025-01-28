<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$base_path = '';
require_once 'config/db_connect.php';
require_once 'includes/language.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Initialize variables
$error = '';
$hotel = null;

// Check if we have hotel data in POST or GET
$hotel_id = $_POST['hotel_id'] ?? $_GET['hotel_id'] ?? null;
$check_in = $_POST['check_in'] ?? $_GET['check_in'] ?? null;
$check_out = $_POST['check_out'] ?? $_GET['check_out'] ?? null;
$guests = $_POST['guests'] ?? $_GET['guests'] ?? null;
$total_price = $_POST['total_price'] ?? $_GET['total_price'] ?? null;

// Validate required data
if (!$hotel_id || !$check_in || !$check_out || !$guests || !$total_price) {
    $_SESSION['error_message'] = __('booking_error');
    header('Location: index.php');
    exit();
}

// Add this near the top of the file, after the require statements
function logError($message, $data = []) {
    error_log("Booking Error: " . $message . " Data: " . json_encode($data));
}

try {
    // Test database connection
    $pdo->query("SELECT 1");
    
    // Verify hotel exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM hotels WHERE id = ?");
    if (!$stmt->execute([$hotel_id])) {
        throw new PDOException("Failed to verify hotel existence");
    }
    if ($stmt->fetchColumn() == 0) {
        throw new Exception("Hotel not found");
    }
    
    // Verify room exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM rooms WHERE hotel_id = ? AND status = 'available'");
    if (!$stmt->execute([$hotel_id])) {
        throw new PDOException("Failed to verify room existence");
    }
    if ($stmt->fetchColumn() == 0) {
        throw new Exception("No available rooms");
    }

    // Fetch hotel details with primary image
    $stmt = $pdo->prepare("
        SELECT h.*, hi.image_url 
        FROM hotels h
        LEFT JOIN hotel_images hi ON h.id = hi.hotel_id AND hi.is_primary = TRUE
        WHERE h.id = ?
    ");
    $stmt->execute([$hotel_id]);
    $hotel = $stmt->fetch();

    if (!$hotel) {
        throw new Exception(__('booking_error'));
    }

    // Set default image if none found
    if (!$hotel['image_url']) {
        $hotel['image_url'] = 'images/default-hotel.jpg';
    }

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_booking'])) {
        // Validate terms acceptance
        if (!isset($_POST['terms'])) {
            throw new Exception(__('agree_terms'));
        }

        // Start transaction
        $pdo->beginTransaction();

        // Find an available room - Simplified query
        $stmt = $pdo->prepare("
            SELECT r.id 
            FROM rooms r
            JOIN hotels h ON r.hotel_id = h.id
            WHERE h.id = ? 
            AND r.status = 'available'
            AND r.deleted_at IS NULL
            AND NOT EXISTS (
                SELECT 1 
                FROM bookings b 
                WHERE b.room_id = r.id 
                AND b.status = 'confirmed'
                AND b.deleted_at IS NULL
                AND (
                    (b.check_in <= ? AND b.check_out >= ?)
                    OR (b.check_in <= ? AND b.check_out >= ?)
                    OR (? <= b.check_in AND ? >= b.check_out)
                )
            )
            LIMIT 1
        ");

        // Add debug logging
        error_log("Checking room availability for hotel_id: $hotel_id");
        error_log("Check-in: $check_in, Check-out: $check_out");

        if (!$stmt->execute([
            $hotel_id,
            $check_out, $check_in,
            $check_in, $check_in,
            $check_in, $check_out
        ])) {
            throw new PDOException("Failed to execute room availability query");
        }

        $room = $stmt->fetch();
        
        if (!$room) {
            error_log("No available rooms found for hotel_id: $hotel_id");
            throw new Exception(__('no_rooms_available'));
        }

        error_log("Found available room: " . json_encode($room));

        // Create booking record
        $stmt = $pdo->prepare("
            INSERT INTO bookings (
                user_id, hotel_id, room_id, check_in, check_out,
                guests, total_price, trx_no, status, special_requests
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'confirmed', ?)
        ");

        $trx_no = 'TRX-' . uniqid();

        $params = [
            $_SESSION['user_id'],
            $hotel_id,
            $room['id'],
            $check_in,
            $check_out,
            $guests,
            $total_price,
            $trx_no,
            $_POST['special_requests'] ?? ''
        ];

        // Debug log the booking parameters
        error_log("Attempting to create booking with params: " . json_encode($params));

        if (!$stmt->execute($params)) {
            throw new PDOException("Failed to create booking record");
        }

        // Commit transaction
        $pdo->commit();

        // Set success message and redirect
        $_SESSION['success_message'] = __('booking_confirmed');
        header("Location: my-bookings.php?trx_no=$trx_no");
        exit();
    }
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Booking error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    $error = __('booking_error');
} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    // Add more detailed logging
    error_log("=== Database Error Details ===");
    error_log("Error Message: " . $e->getMessage());
    error_log("SQL State: " . $e->errorInfo[0]);
    error_log("Error Code: " . $e->errorInfo[1]);
    error_log("Error Message: " . $e->errorInfo[2]);
    error_log("Query Parameters: " . json_encode([
        'hotel_id' => $hotel_id,
        'check_in' => $check_in,
        'check_out' => $check_out,
        'guests' => $guests,
        'total_price' => $total_price,
        'user_id' => $_SESSION['user_id'] ?? null
    ]));
    error_log("Stack trace: " . $e->getTraceAsString());
    
    $error = __('db_error');
}
?>

<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang'] ?? 'en'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('booking_details'); ?> - <?php echo __('site_name'); ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/booking.css">
</head>
<body>
    <header>
        <?php include 'includes/header.php'; ?>
    </header>

    <main>
        <div class="booking-container">
            <h1><?php echo __('booking_details'); ?></h1>

            <?php if (!empty($error)): ?>
                <div class="error-message">
                    <?php 
                    error_log("Displaying error: " . $error);
                    echo htmlspecialchars(__($error)); 
                    ?>
                </div>
            <?php endif; ?>

            <?php if ($hotel): ?>
                <div class="booking-content">
                    <div class="hotel-summary">
                        <img src="<?php echo htmlspecialchars($hotel['image_url'] ?? 'images/default-hotel.jpg'); ?>" 
                             alt="<?php echo htmlspecialchars($hotel['name'] ?? ''); ?>">
                        <div class="hotel-info">
                            <h2><?php echo htmlspecialchars($hotel['name'] ?? ''); ?></h2>
                            <p class="location">
                                <?php echo htmlspecialchars($hotel['city'] ?? '') . ', ' . htmlspecialchars($hotel['country'] ?? ''); ?>
                            </p>
                        </div>
                    </div>

                    <div class="booking-details">
                        <h3><?php echo __('booking_summary'); ?></h3>
                        <div class="details-grid">
                            <div class="detail-item">
                                <span class="label"><?php echo __('check_in'); ?>:</span>
                                <span class="value"><?php echo date('M d, Y', strtotime($check_in)); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="label"><?php echo __('check_out'); ?>:</span>
                                <span class="value"><?php echo date('M d, Y', strtotime($check_out)); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="label"><?php echo __('guests'); ?>:</span>
                                <span class="value"><?php echo htmlspecialchars($guests); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="label"><?php echo __('total_price'); ?>:</span>
                                <span class="value">$<?php echo htmlspecialchars($total_price); ?></span>
                            </div>
                        </div>

                        <form method="POST" class="booking-form">
                            <input type="hidden" name="hotel_id" value="<?php echo htmlspecialchars($hotel_id); ?>">
                            <input type="hidden" name="check_in" value="<?php echo htmlspecialchars($check_in); ?>">
                            <input type="hidden" name="check_out" value="<?php echo htmlspecialchars($check_out); ?>">
                            <input type="hidden" name="guests" value="<?php echo htmlspecialchars($guests); ?>">
                            <input type="hidden" name="total_price" value="<?php echo htmlspecialchars($total_price); ?>">

                            <div class="form-group">
                                <label for="special_requests"><?php echo __('special_requests'); ?></label>
                                <textarea id="special_requests" name="special_requests" rows="4"></textarea>
                            </div>

                            <div class="form-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" name="terms" required>
                                    <?php echo __('agree_terms'); ?>
                                </label>
                            </div>

                            <div class="button-group">
                                <a href="hotel.php?id=<?php echo htmlspecialchars($hotel_id); ?>" class="back-button">
                                    <?php echo __('return_to_hotel'); ?>
                                </a>
                                <button type="submit" name="confirm_booking" class="confirm-button">
                                    <?php echo __('confirm_booking'); ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <footer>
        <p><?php echo __('copyright'); ?></p>
    </footer>
</body>
</html>
