<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/db_connect.php';

// Fetch hotels from database with average rating and lowest room price
$stmt = $pdo->query("
    SELECT 
        h.*,
        COALESCE(AVG(r.rating), 0) as rating,
        MIN(rt.base_price) as price_range,
        (SELECT image_url FROM hotel_images WHERE hotel_id = h.id AND is_primary = TRUE LIMIT 1) as image_url
    FROM hotels h
    LEFT JOIN reviews r ON h.id = r.hotel_id
    LEFT JOIN room_types rt ON h.id = rt.hotel_id
    WHERE h.status = 'active' 
    AND h.deleted_at IS NULL
    GROUP BY h.id
    ORDER BY rating DESC 
    LIMIT 6
");
$hotels = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Set base path for includes
$base_path = '';
?>

<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang'] ?? 'en'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hotel Discovery Platform</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/home.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <header>
        <?php include 'includes/header.php'; ?>
    </header>

    <main>
        <section class="search-section">
            <h1><?php echo __('search_title'); ?></h1>
            <form action="search.php" method="GET" class="search-form">
                <input type="text" name="location" 
                       placeholder="<?php echo __('search_placeholder'); ?>"
                       aria-label="<?php echo __('search_placeholder'); ?>">
                <button type="submit">
                    <i class="fas fa-search"></i>
                    <?php echo __('search_button'); ?>
                </button>
            </form>
        </section>

        <section class="featured-section">
            <h2><?php echo __('featured_hotels'); ?></h2>
            <div class="hotels-grid">
                <?php foreach($hotels as $hotel): ?>
                    <div class="hotel-card">
                        <img src="<?php echo htmlspecialchars($hotel['image_url'] ?? 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRNk3EkamoduhvQHh2eiogSUxtB7xq9HbgQxQ&s'); ?>" 
                             alt="<?php echo htmlspecialchars($hotel['name']); ?>">
                        <div class="hotel-info">
                            <h3><?php echo htmlspecialchars($hotel['name']); ?></h3>
                            <p class="location">
                                <i class="fas fa-map-marker-alt"></i>
                                <?php echo htmlspecialchars($hotel['city']) . ', ' . htmlspecialchars($hotel['country']); ?>
                            </p>
                            <div class="rating">
                                <i class="fas fa-star"></i>
                                <?php echo htmlspecialchars(number_format($hotel['rating'], 1)); ?>/5
                            </div>
                            <p class="price">
                                <?php echo __('price_from'); ?> 
                                $<?php echo htmlspecialchars(number_format($hotel['price_range'], 2)); ?> 
                                <span class="price-period"><?php echo __('per_night'); ?></span>
                            </p>
                            <a href="hotel.php?id=<?php echo $hotel['id']; ?>" class="view-details">
                                <?php echo __('view_details'); ?>
                                <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </main>

    <footer>
        <p><?php echo __('copyright'); ?></p>
    </footer>
</body>
</html> 