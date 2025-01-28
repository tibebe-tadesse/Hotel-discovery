<?php
session_start();
require_once 'config/db_connect.php';

// Set base path for includes
$base_path = '';

// Check for database connection error
if (isset($db_error)) {
    die($db_error);
}

if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit();
}

$hotel_id = (int)$_GET['id'];
$stmt = $pdo->prepare("
    SELECT 
        h.*,
        COALESCE(AVG(r.rating), 0) as rating,
        MIN(rt.base_price) as price_range,
        (SELECT image_url FROM hotel_images WHERE hotel_id = h.id AND is_primary = TRUE LIMIT 1) as image_url,
        ho.company_name as owner_company
    FROM hotels h
    LEFT JOIN reviews r ON h.id = r.hotel_id
    LEFT JOIN room_types rt ON h.id = rt.hotel_id
    LEFT JOIN hotel_owners ho ON h.owner_id = ho.id
    WHERE h.id = ? 
    AND h.status = 'active'
    AND h.deleted_at IS NULL
    GROUP BY h.id
");
$stmt->execute([$hotel_id]);
$hotel = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$hotel) {
    header('Location: index.php');
    exit();
}

// Fetch hotel amenities
$amenities_stmt = $pdo->prepare("
    SELECT a.* 
    FROM amenities a
    JOIN hotel_amenities ha ON a.id = ha.amenity_id
    WHERE ha.hotel_id = ?
");
$amenities_stmt->execute([$hotel_id]);
$amenities = $amenities_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch available rooms for selected dates
$stmt = $pdo->prepare("
    SELECT 
        rt.*,
        COUNT(r.id) as available_rooms,
        GROUP_CONCAT(DISTINCT rti.image_url) as room_images
    FROM room_types rt
    JOIN rooms r ON rt.id = r.room_type_id AND r.status = 'available'
    LEFT JOIN room_type_images rti ON rt.id = rti.room_type_id
    WHERE rt.hotel_id = ?
    AND r.deleted_at IS NULL
    AND r.id NOT IN (
        SELECT b.room_id 
        FROM bookings b 
        WHERE b.status IN ('pending', 'confirmed')
        AND b.deleted_at IS NULL
        AND (
            (b.check_in BETWEEN ? AND ?) OR
            (b.check_out BETWEEN ? AND ?) OR
            (check_in <= ? AND check_out >= ?)
        )
    )
    GROUP BY rt.id
    HAVING available_rooms > 0
");

// Add query to fetch reviews
$reviews_stmt = $pdo->prepare("
    SELECT 
        r.*,
        u.first_name,
        u.last_name,
        DATE_FORMAT(r.created_at, '%M %d, %Y') as review_date
    FROM reviews r
    JOIN users u ON r.user_id = u.id
    WHERE r.hotel_id = ?
    ORDER BY r.created_at DESC
    LIMIT 5
");
$reviews_stmt->execute([$hotel_id]);
$reviews = $reviews_stmt->fetchAll(PDO::FETCH_ASSOC);

// Add query to fetch room types with images
$room_types_stmt = $pdo->prepare("
    SELECT 
        rt.*,
        GROUP_CONCAT(DISTINCT rti.image_url) as room_images
    FROM room_types rt
    LEFT JOIN room_type_images rti ON rt.id = rti.room_type_id
    WHERE rt.hotel_id = ?
    GROUP BY rt.id
");
$room_types_stmt->execute([$hotel_id]);
$room_types = $room_types_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang'] ?? 'en'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($hotel['name']); ?> - Hotel Discovery</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/hotel.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <header>
        <?php include 'includes/header.php'; ?>
    </header>

    <main>
        <div class="hotel-details">
            <div class="hotel-content-wrapper">
                <div class="hotel-content">
                    <div class="hotel-header">
                        <img src="<?php echo htmlspecialchars($hotel['image_url'] ?? 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRNk3EkamoduhvQHh2eiogSUxtB7xq9HbgQxQ&s'); ?>" 
                             alt="<?php echo htmlspecialchars($hotel['name']); ?>">
                        <div class="hotel-header-info">
                            <h1><?php echo htmlspecialchars($hotel['name']); ?></h1>
                            <p class="location">
                                <i class="fas fa-map-marker-alt"></i>
                                <?php echo htmlspecialchars($hotel['address']); ?>, 
                                <?php echo htmlspecialchars($hotel['city']); ?>, 
                                <?php echo htmlspecialchars($hotel['country']); ?>
                            </p>
                            <div class="hotel-stats">
                                <div class="rating">
                                    <i class="fas fa-star"></i>
                                    <?php echo htmlspecialchars(number_format($hotel['rating'], 1)); ?>/5
                                </div>
                                <div class="stat-item">
                                    <i class="fas fa-building"></i>
                                    <?php echo htmlspecialchars($hotel['star_rating']); ?> Star Hotel
                                </div>
                            </div>
                            
                            <div class="amenities">
                                <h3><i class="fas fa-concierge-bell"></i> Hotel Amenities</h3>
                                <ul>
                                    <?php foreach($amenities as $amenity): ?>
                                        <li>
                                            <?php if($amenity['icon']): ?>
                                                <i class="<?php echo htmlspecialchars($amenity['icon']); ?>"></i>
                                            <?php endif; ?>
                                            <?php echo htmlspecialchars($amenity['name']); ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="hotel-description">
                        <h2>About this hotel</h2>
                        <p><?php echo nl2br(htmlspecialchars($hotel['description'])); ?></p>
                    </div>

                    <h2>Available Rooms</h2>
                    <div class="room-types">
                        <?php foreach($room_types as $room): ?>
                            <div class="room-card">
                                <?php 
                                $room_images = !empty($room['room_images']) ? explode(',', $room['room_images']) : [];
                                $primary_image = !empty($room_images) ? $room_images[0] : 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRNk3EkamoduhvQHh2eiogSUxtB7xq9HbgQxQ&s';
                                ?>
                                <div class="room-image">
                                    <img src="<?php echo htmlspecialchars($primary_image); ?>" 
                                         alt="<?php echo htmlspecialchars($room['name']); ?>">
                                </div>
                                <div class="room-info">
                                    <div>
                                        <h3><?php echo htmlspecialchars($room['name']); ?></h3>
                                        <div class="room-details">
                                            <span class="room-detail-item">
                                                <i class="fas fa-user"></i>
                                                <?php echo htmlspecialchars($room['capacity']); ?> guests
                                            </span>
                                            <span class="room-detail-item">
                                                <i class="fas fa-bed"></i>
                                                <?php echo htmlspecialchars($room['bed_type']); ?>
                                            </span>
                                            <span class="room-detail-item">
                                                <i class="fas fa-ruler-combined"></i>
                                                <?php echo htmlspecialchars($room['room_size']); ?>m²
                                            </span>
                                        </div>
                                        <p><?php echo htmlspecialchars($room['description']); ?></p>
                                    </div>
                                    <div class="room-price">
                                        $<?php echo htmlspecialchars(number_format($room['base_price'], 2)); ?> per night
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="reviews-section">
                        <h2>Guest Reviews</h2>
                        <?php if (empty($reviews)): ?>
                            <p>No reviews yet. Be the first to review this hotel!</p>
                        <?php else: ?>
                            <?php foreach($reviews as $review): ?>
                                <div class="review-card">
                                    <div class="review-header">
                                        <div class="reviewer-info">
                                            <div class="reviewer-avatar">
                                                <i class="fas fa-user"></i>
                                            </div>
                                            <div class="reviewer-details">
                                                <div class="reviewer-name">
                                                    <?php echo htmlspecialchars($review['first_name'] . ' ' . $review['last_name']); ?>
                                                </div>
                                                <div class="review-date">
                                                    <i class="far fa-calendar-alt"></i>
                                                    <?php echo htmlspecialchars($review['review_date']); ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="review-rating">
                                            <?php echo str_repeat('★', $review['rating']); ?>
                                            <?php echo str_repeat('☆', 5 - $review['rating']); ?>
                                        </div>
                                    </div>
                                    <div class="review-content">
                                        <?php echo nl2br(htmlspecialchars($review['comment'])); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="booking-form">
                        <h2>Book Now</h2>
                        <form action="booking.php" method="POST" id="bookingForm">
                            <input type="hidden" name="hotel_id" value="<?php echo $hotel['id']; ?>">
                            
                            <div class="form-group">
                                <label for="room_type">Select Room</label>
                                <select id="room_type" name="room_type" required class="form-select">
                                    <option value="">Choose a room type</option>
                                    <?php foreach($room_types as $room): ?>
                                        <option value="<?php echo $room['id']; ?>" 
                                                data-price="<?php echo $room['base_price']; ?>"
                                                data-capacity="<?php echo $room['capacity']; ?>"
                                                data-guest-price="<?php echo $room['base_price'] * 0.2; ?>">
                                            <?php echo htmlspecialchars($room['name']); ?> 
                                            ($<?php echo number_format($room['base_price'], 2); ?>/night + $<?php echo number_format($room['base_price'] * 0.2, 2); ?>/extra guest)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="guests">Number of Guests</label>
                                <input type="number" id="guests" name="guests" min="1" max="10" value="1" required>
                                <small class="capacity-warning" style="display: none; color: #dc3545;">
                                    Exceeds room capacity
                                </small>
                            </div>

                            <div class="form-group">
                                <label for="check_in">Check-in Date</label>
                                <input type="date" id="check_in" name="check_in" required min="<?php echo date('Y-m-d'); ?>">
                            </div>

                            <div class="form-group">
                                <label for="check_out">Check-out Date</label>
                                <input type="date" id="check_out" name="check_out" required min="<?php echo date('Y-m-d'); ?>">
                            </div>

                            <div class="price-display" id="totalPrice">
                                Select dates to see total price
                            </div>
                            <input type="hidden" name="total_price" id="totalPriceInput">
                            <button type="submit" disabled>Book Now</button>
                        </form>
                    </div>

                    <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const form = document.getElementById('bookingForm');
                        const roomSelect = document.getElementById('room_type');
                        const checkIn = document.getElementById('check_in');
                        const checkOut = document.getElementById('check_out');
                        const guests = document.getElementById('guests');
                        const totalPriceDisplay = document.getElementById('totalPrice');
                        const totalPriceInput = document.getElementById('totalPriceInput');
                        const submitButton = form.querySelector('button[type="submit"]');
                        const capacityWarning = document.querySelector('.capacity-warning');

                        // Set minimum dates
                        const tomorrow = new Date();
                        tomorrow.setDate(tomorrow.getDate() + 1);
                        checkIn.min = tomorrow.toISOString().split('T')[0];

                        function updateCheckOutMin() {
                            if (checkIn.value) {
                                const minCheckOut = new Date(checkIn.value);
                                minCheckOut.setDate(minCheckOut.getDate() + 1);
                                checkOut.min = minCheckOut.toISOString().split('T')[0];
                                
                                if (checkOut.value && new Date(checkOut.value) <= new Date(checkIn.value)) {
                                    checkOut.value = minCheckOut.toISOString().split('T')[0];
                                }
                            }
                        }

                        function validateCapacity() {
                            const selectedRoom = roomSelect.options[roomSelect.selectedIndex];
                            if (selectedRoom && selectedRoom.value) {
                                const capacity = parseInt(selectedRoom.dataset.capacity);
                                const guestCount = parseInt(guests.value);
                                
                                if (guestCount > capacity) {
                                    capacityWarning.style.display = 'block';
                                    submitButton.disabled = true;
                                    return false;
                                }
                            }
                            capacityWarning.style.display = 'none';
                            return true;
                        }

                        function calculateTotal() {
                            const selectedRoom = roomSelect.options[roomSelect.selectedIndex];
                            
                            if (selectedRoom && selectedRoom.value && checkIn.value && checkOut.value) {
                                const start = new Date(checkIn.value);
                                const end = new Date(checkOut.value);
                                const nights = Math.ceil((end - start) / (1000 * 60 * 60 * 24));
                                
                                if (nights > 0 && validateCapacity()) {
                                    const basePrice = parseFloat(selectedRoom.dataset.price);
                                    const guestPrice = parseFloat(selectedRoom.dataset.guestPrice);
                                    const guestCount = parseInt(guests.value);
                                    const extraGuests = Math.max(0, guestCount - 1); // First guest is included in base price
                                    
                                    const totalBasePrice = basePrice * nights;
                                    const totalGuestPrice = guestPrice * extraGuests * nights;
                                    const total = totalBasePrice + totalGuestPrice;

                                    totalPriceDisplay.innerHTML = `
                                        <div class="price-breakdown">
                                            <div class="price-row">
                                                <span>Base Price (${nights} night${nights > 1 ? 's' : ''})</span>
                                                <span>$${basePrice.toFixed(2)} × ${nights} = $${totalBasePrice.toFixed(2)}</span>
                                            </div>
                                            ${extraGuests > 0 ? `
                                            <div class="price-row">
                                                <span>Extra Guests (${extraGuests})</span>
                                                <span>$${guestPrice.toFixed(2)} × ${extraGuests} × ${nights} = $${totalGuestPrice.toFixed(2)}</span>
                                            </div>
                                            ` : ''}
                                            <div class="price-total">
                                                <span>Total</span>
                                                <span>$${total.toFixed(2)}</span>
                                            </div>
                                        </div>
                                    `;
                                    totalPriceInput.value = total.toFixed(2);
                                    submitButton.disabled = false;
                                } else {
                                    totalPriceDisplay.textContent = 'Please select valid dates';
                                    submitButton.disabled = true;
                                }
                            } else {
                                totalPriceDisplay.textContent = 'Select room and dates to see total price';
                                submitButton.disabled = true;
                            }
                        }

                        roomSelect.addEventListener('change', calculateTotal);
                        checkIn.addEventListener('change', function() {
                            updateCheckOutMin();
                            calculateTotal();
                        });
                        checkOut.addEventListener('change', calculateTotal);
                        guests.addEventListener('change', calculateTotal);

                        form.addEventListener('submit', function(e) {
                            if (!roomSelect.value || !checkIn.value || !checkOut.value || !guests.value) {
                                e.preventDefault();
                                alert('Please fill in all required fields');
                            }
                            if (!validateCapacity()) {
                                e.preventDefault();
                                alert('Number of guests exceeds room capacity');
                            }
                        });

                        // Initial calculation
                        calculateTotal();
                    });
                    </script>
                <?php else: ?>
                    <div class="booking-form">
                        <h2>Want to Book?</h2>
                        <p>Please log in to make a reservation.</p>
                        <a href="login.php" class="btn btn-primary">Log In</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <footer>
        <p><?php echo __('copyright'); ?></p>
    </footer>

    <!-- Existing JavaScript code -->
</body>
</html>