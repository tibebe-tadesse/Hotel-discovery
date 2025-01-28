<?php
session_start();
require_once '../config/db_connect.php';


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

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $address = trim($_POST['address']);
    $city = trim($_POST['city']);
    $country = trim($_POST['country']);
    $price_range = (float)$_POST['price_range'];
    $rating = (float)$_POST['rating'];
    $image_url = trim($_POST['image_url']);

    if (empty($name) || empty($city) || empty($country)) {
        $error = 'Name, city and country are required fields';
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO hotels (name, description, address, city, country, price_range, rating, image_url)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        try {
            $stmt->execute([$name, $description, $address, $city, $country, $price_range, $rating, $image_url]);
            $success = 'Hotel added successfully!';
        } catch (PDOException $e) {
            $error = 'Failed to add hotel. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Hotel - Admin Panel</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="../css/hotel-form.css">
</head>
<body>
    <header>
        <?php include '../includes/header.php'; ?>
    </header>

    <main>
        <div class="admin-container">
            <div class="admin-header">
                <h1>Add New Hotel</h1>
                <a href="index.php" class="back-button">Back to Hotels</a>
            </div>

            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="success-message">
                    <?php echo htmlspecialchars($success); ?>
                    <a href="index.php">Return to hotel list</a>
                </div>
            <?php endif; ?>

            <form method="POST" class="hotel-form">
                <div class="form-group">
                    <label for="name">Hotel Name *</label>
                    <input type="text" id="name" name="name" required>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="4"></textarea>
                </div>

                <div class="form-group">
                    <label for="address">Address</label>
                    <input type="text" id="address" name="address">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="city">City *</label>
                        <input type="text" id="city" name="city" required>
                    </div>

                    <div class="form-group">
                        <label for="country">Country *</label>
                        <input type="text" id="country" name="country" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="price_range">Price per Night ($)</label>
                        <input type="number" id="price_range" name="price_range" min="0" step="0.01">
                    </div>

                    <div class="form-group">
                        <label for="rating">Rating (0-5)</label>
                        <input type="number" id="rating" name="rating" min="0" max="5" step="0.1">
                    </div>
                </div>

                <div class="form-group">
                    <label for="image_url">Image URL</label>
                    <input type="url" id="image_url" name="image_url">
                </div>

                <button type="submit" class="submit-button">Add Hotel</button>
            </form>
        </div>
    </main>

    <footer>
        <p>&copy; 2024 Hotel Discovery Platform</p>
    </footer>
</body>
</html> 