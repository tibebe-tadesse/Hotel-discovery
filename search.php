<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$base_path = '';
require_once 'config/db_connect.php';
require_once 'includes/language.php';

// Build search query
$query = "
    SELECT 
        h.*,
        COALESCE(AVG(r.rating), 0) as rating,
        COUNT(DISTINCT r.id) as review_count,
        MIN(rt.base_price) as price_range,
        (SELECT image_url FROM hotel_images WHERE hotel_id = h.id AND is_primary = TRUE LIMIT 1) as image_url,
        ho.company_name as owner_company,
        GROUP_CONCAT(DISTINCT a.name) as amenities
    FROM hotels h
    LEFT JOIN reviews r ON h.id = r.hotel_id
    LEFT JOIN room_types rt ON h.id = rt.hotel_id
    LEFT JOIN hotel_owners ho ON h.owner_id = ho.id
    LEFT JOIN hotel_amenities ha ON h.id = ha.hotel_id
    LEFT JOIN amenities a ON ha.amenity_id = a.id
    WHERE h.status = 'active' 
    AND h.deleted_at IS NULL
";

$params = [];

// Handle location filter
if (isset($_GET['location']) && !empty($_GET['location'])) {
    $location = '%' . $_GET['location'] . '%';
    $query .= " AND (h.city LIKE ? OR h.country LIKE ? OR h.address LIKE ?)";
    $params[] = $location;
    $params[] = $location;
    $params[] = $location;
}

// Handle price range filter
if (isset($_GET['min_price']) && !empty($_GET['min_price'])) {
    $query .= " AND rt.base_price >= ?";
    $params[] = (float)$_GET['min_price'];
}

if (isset($_GET['max_price']) && !empty($_GET['max_price'])) {
    $query .= " AND rt.base_price <= ?";
    $params[] = (float)$_GET['max_price'];
}

// Add star rating filter
if (isset($_GET['star_rating']) && !empty($_GET['star_rating'])) {
    $query .= " AND h.star_rating = ?";
    $params[] = (int)$_GET['star_rating'];
}

// Add hotel name filter to the query
if (isset($_GET['hotel_name']) && !empty($_GET['hotel_name'])) {
    $hotel_name = '%' . $_GET['hotel_name'] . '%';
    $query .= " AND h.name LIKE ?";
    $params[] = $hotel_name;
}

// Add sorting options
$sort = $_GET['sort'] ?? 'rating';
$query .= " GROUP BY h.id ";
switch ($sort) {
    case 'price_low':
        $query .= "ORDER BY price_range ASC";
        break;
    case 'price_high':
        $query .= "ORDER BY price_range DESC";
        break;
    case 'rating':
    default:
        $query .= "ORDER BY rating DESC, review_count DESC";
        break;
}

try {
    // Execute search query
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $hotels = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Store search parameters for form values
    $location = $_GET['location'] ?? '';
    $min_price = $_GET['min_price'] ?? '';
    $max_price = $_GET['max_price'] ?? '';
    $hotel_name = $_GET['hotel_name'] ?? '';
} catch (PDOException $e) {
    // Handle database errors
    error_log("Database error: " . $e->getMessage());
    $hotels = [];
    $_SESSION['error_message'] = __('db_error');
}
?>

<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang'] ?? 'en'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('nav_search'); ?> - <?php echo __('site_name'); ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/search.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <header>
        <?php include 'includes/header.php'; ?>
    </header>

    <main>
        <div class="search-filters">
            <h2><?php echo __('filter_title'); ?></h2>
            <form action="search.php" method="GET" class="filter-form">
                <div class="filter-section">
                    <h3><i class="fas fa-hotel"></i> <?php echo __('hotel_search'); ?></h3>
                    <div class="filter-group">
                        <label for="hotel_name"><?php echo __('hotel_name'); ?></label>
                        <input type="text" id="hotel_name" name="hotel_name" 
                               value="<?php echo htmlspecialchars($hotel_name); ?>"
                               placeholder="<?php echo __('hotel_name_placeholder'); ?>">
                    </div>
                </div>

                <div class="filter-section">
                    <h3><i class="fas fa-map-marker-alt"></i> <?php echo __('location_filters'); ?></h3>
                    <div class="filter-group">
                        <label for="location"><?php echo __('location_label'); ?></label>
                        <input type="text" id="location" name="location" 
                               value="<?php echo htmlspecialchars($location); ?>"
                               placeholder="<?php echo __('location_placeholder'); ?>">
                    </div>
                </div>

                <div class="filter-section">
                    <h3><i class="fas fa-dollar-sign"></i> <?php echo __('price_filters'); ?></h3>
                    <div class="price-range">
                        <div class="filter-group">
                            <label for="min_price"><?php echo __('min_price'); ?></label>
                            <input type="number" id="min_price" name="min_price" 
                                   value="<?php echo htmlspecialchars($min_price); ?>"
                                   min="0" step="10" placeholder="0">
                        </div>
                        <div class="filter-group">
                            <label for="max_price"><?php echo __('max_price'); ?></label>
                            <input type="number" id="max_price" name="max_price" 
                                   value="<?php echo htmlspecialchars($max_price); ?>"
                                   min="0" step="10" placeholder="1000">
                        </div>
                    </div>
                </div>

                <div class="filter-section">
                    <h3><i class="fas fa-star"></i> <?php echo __('hotel_rating'); ?></h3>
                    <div class="star-rating">
                        <?php for ($i = 5; $i >= 1; $i--): ?>
                            <label class="star-option">
                                <input type="radio" name="star_rating" value="<?php echo $i; ?>"
                                       <?php echo (isset($_GET['star_rating']) && $_GET['star_rating'] == $i) ? 'checked' : ''; ?>>
                                <span><?php echo str_repeat('★', $i) . str_repeat('☆', 5-$i); ?></span>
                            </label>
                        <?php endfor; ?>
                    </div>
                </div>

                <div class="filter-section">
                    <h3><i class="fas fa-sort"></i> <?php echo __('sort_by'); ?></h3>
                    <select name="sort" class="sort-select">
                        <option value="rating" <?php echo ($sort === 'rating') ? 'selected' : ''; ?>>
                            <?php echo __('sort_rating'); ?>
                        </option>
                        <option value="price_low" <?php echo ($sort === 'price_low') ? 'selected' : ''; ?>>
                            <?php echo __('sort_price_low'); ?>
                        </option>
                        <option value="price_high" <?php echo ($sort === 'price_high') ? 'selected' : ''; ?>>
                            <?php echo __('sort_price_high'); ?>
                        </option>
                    </select>
                </div>

                <button type="submit" class="search-button">
                    <i class="fas fa-search"></i> <?php echo __('apply_filters'); ?>
                </button>
            </form>
        </div>

        <div class="search-results">
            <?php if (empty($hotels)): ?>
                <div class="no-results">
                    <div class="no-results-icon">
                        <i class="fas fa-search"></i>
                    </div>
                    <h3><?php echo __('no_results_title'); ?></h3>
                    <p><?php echo __('no_results_message'); ?></p>
                    
                    <div class="try-again-tips">
                        <h4><?php echo __('try_again_tips_title'); ?></h4>
                        <ul>
                            <li><?php echo __('tip_check_spelling'); ?></li>
                            <li><?php echo __('tip_different_dates'); ?></li>
                            <li><?php echo __('tip_fewer_filters'); ?></li>
                            <li><?php echo __('tip_broader_location'); ?></li>
                        </ul>
                    </div>

                    <a href="search.php" class="reset-search">
                        <i class="fas fa-redo"></i>
                        <?php echo __('reset_search'); ?>
                    </a>
                </div>
            <?php else: ?>
                <h2><?php echo count($hotels); ?> <?php echo __('results_found'); ?></h2>
                <div class="hotels-grid">
                    <?php foreach($hotels as $hotel): ?>
                        <div class="hotel-card">
                            <div class="hotel-image">
                                <img src="<?php echo htmlspecialchars($hotel['image_url'] ?? 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRNk3EkamoduhvQHh2eiogSUxtB7xq9HbgQxQ&s'); ?>" 
                                     alt="<?php echo htmlspecialchars($hotel['name']); ?>">
                                <?php if ($hotel['star_rating']): ?>
                                    <div class="hotel-stars">
                                        <?php echo str_repeat('★', $hotel['star_rating']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="hotel-info">
                                <div class="hotel-main">
                                    <h3><?php echo htmlspecialchars($hotel['name']); ?></h3>
                                    <p class="location">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <?php echo htmlspecialchars($hotel['city']) . ', ' . htmlspecialchars($hotel['country']); ?>
                                    </p>
                                    
                                    <?php if ($hotel['amenities']): ?>
                                        <?php 
                                        $amenities = explode(',', $hotel['amenities']);
                                        $displayAmenities = array_slice($amenities, 0, 3);
                                        ?>
                                        <div class="amenities">
                                            <?php foreach($displayAmenities as $amenity): ?>
                                                <span class="amenity-tag">
                                                    <?php echo htmlspecialchars($amenity); ?>
                                                </span>
                                            <?php endforeach; ?>
                                            <?php if (count($amenities) > 3): ?>
                                                <span class="amenity-tag">
                                                    +<?php echo count($amenities) - 3; ?> more
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="hotel-price">
                                    <div class="rating-badge">
                                        <i class="fas fa-star"></i>
                                        <?php echo htmlspecialchars(number_format($hotel['rating'], 1)); ?>
                                        <small>(<?php echo $hotel['review_count']; ?>)</small>
                                    </div>
                                    
                                    <div class="price-display">
                                        <span class="price-amount">
                                            $<?php echo htmlspecialchars(number_format($hotel['price_range'], 0)); ?>
                                        </span>
                                        <span class="price-period"><?php echo __('per_night'); ?></span>
                                    </div>

                                    <a href="hotel.php?id=<?php echo $hotel['id']; ?>" class="view-details">
                                        <?php echo __('view_details'); ?>
                                        <i class="fas fa-arrow-right"></i>
                                    </a>
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
</body>
</html> 