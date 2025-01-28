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
$hotel = null;

// Get hotel data
if (isset($_GET['id'])) {
    $hotel_id = (int)$_GET['id'];
    $stmt = $pdo->prepare("
        SELECT h.*, hi.image_url 
        FROM hotels h
        LEFT JOIN hotel_images hi ON h.id = hi.hotel_id AND hi.is_primary = 1
        WHERE h.id = ? AND h.deleted_at IS NULL
    ");
    $stmt->execute([$hotel_id]);
    $hotel = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$hotel) {
        header('Location: hotels.php');
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $hotel_id = (int)$_POST['hotel_id'];
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $address = trim($_POST['address']);
    $city = trim($_POST['city']);
    $country = trim($_POST['country']);
    $postal_code = trim($_POST['postal_code']);
    $base_price = (float)$_POST['base_price'];
    $star_rating = (int)$_POST['star_rating'];
    $status = $_POST['status'];
    $check_in_time = $_POST['check_in_time'];
    $check_out_time = $_POST['check_out_time'];
    $image_url = trim($_POST['image_url']);

    if (empty($name) || empty($city) || empty($country)) {
        $error = 'Name, city and country are required fields';
    } else {
        try {
            // Start transaction
            $pdo->beginTransaction();

            // Update hotel details
            $stmt = $pdo->prepare("
                UPDATE hotels 
                SET name = ?, description = ?, address = ?, city = ?, 
                    country = ?, postal_code = ?, base_price = ?, star_rating = ?,
                    status = ?, check_in_time = ?, check_out_time = ?
                WHERE id = ?
            ");

            $stmt->execute([
                $name, $description, $address, $city, 
                $country, $postal_code, $base_price, $star_rating,
                $status, $check_in_time, $check_out_time, $hotel_id
            ]);

            // Update or insert primary image
            if (!empty($image_url)) {
                $stmt = $pdo->prepare("
                    INSERT INTO hotel_images (hotel_id, image_url, is_primary)
                    VALUES (?, ?, 1)
                    ON DUPLICATE KEY UPDATE image_url = ?
                ");
                $stmt->execute([$hotel_id, $image_url, $image_url]);
            }

            $pdo->commit();
            $success = 'Hotel updated successfully!';
            
            // Refresh hotel data
            $stmt = $pdo->prepare("
                SELECT h.*, hi.image_url 
                FROM hotels h
                LEFT JOIN hotel_images hi ON h.id = hi.hotel_id AND hi.is_primary = 1
                WHERE h.id = ?
            ");
            $stmt->execute([$hotel_id]);
            $hotel = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = 'Failed to update hotel. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Hotel - Admin Panel</title>
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
                <h1>Edit Hotel</h1>
                <a href="index.php" class="back-button">Back to Hotels</a>
            </div>

            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <form method="POST" class="hotel-form">
                <input type="hidden" name="hotel_id" value="<?php echo $hotel['id']; ?>">
                
                <div class="form-group">
                    <label for="name">Hotel Name *</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($hotel['name']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="4"><?php echo htmlspecialchars($hotel['description']); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="address">Address</label>
                    <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($hotel['address']); ?>">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="city">City *</label>
                        <input type="text" id="city" name="city" value="<?php echo htmlspecialchars($hotel['city']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="country">Country *</label>
                        <input type="text" id="country" name="country" value="<?php echo htmlspecialchars($hotel['country']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="postal_code">Postal Code</label>
                        <input type="text" id="postal_code" name="postal_code" value="<?php echo htmlspecialchars($hotel['postal_code']); ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="base_price">Base Price per Night ($) *</label>
                        <input type="number" id="base_price" name="base_price" min="0" step="0.01" 
                               value="<?php echo htmlspecialchars($hotel['base_price']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="star_rating">Star Rating (1-5) *</label>
                        <input type="number" id="star_rating" name="star_rating" min="1" max="5" 
                               value="<?php echo htmlspecialchars($hotel['star_rating']); ?>" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="check_in_time">Check-in Time</label>
                        <input type="time" id="check_in_time" name="check_in_time" 
                               value="<?php echo htmlspecialchars($hotel['check_in_time']); ?>">
                    </div>

                    <div class="form-group">
                        <label for="check_out_time">Check-out Time</label>
                        <input type="time" id="check_out_time" name="check_out_time" 
                               value="<?php echo htmlspecialchars($hotel['check_out_time']); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="status">Status *</label>
                    <select id="status" name="status" required>
                        <option value="active" <?php echo $hotel['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo $hotel['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        <option value="maintenance" <?php echo $hotel['status'] === 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="image_url">Primary Image URL</label>
                    <input type="url" id="image_url" name="image_url" value="<?php echo htmlspecialchars($hotel['image_url']); ?>">
                </div>

                <button type="submit" class="submit-button">Update Hotel</button>
            </form>

            <div class="rooms-section">
                <h2>Manage Rooms</h2>
                <button type="button" class="add-button" onclick="showAddRoomModal()">Add New Room</button>

                <div class="rooms-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Room Number</th>
                                <th>Room Type</th>
                                <th>Floor</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Fetch rooms for this hotel
                            $stmt = $pdo->prepare("
                                SELECT r.*, rt.name as room_type_name, rt.base_price
                                FROM rooms r
                                JOIN room_types rt ON r.room_type_id = rt.id
                                WHERE r.hotel_id = ? AND r.deleted_at IS NULL
                                ORDER BY r.room_number
                            ");
                            $stmt->execute([$hotel_id]);
                            $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

                            foreach($rooms as $room): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($room['room_number']); ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($room['room_type_name']); ?>
                                        <br>
                                        <small>$<?php echo htmlspecialchars($room['base_price']); ?>/night</small>
                                    </td>
                                    <td><?php echo htmlspecialchars($room['floor']); ?></td>
                                    <td><?php echo htmlspecialchars(ucfirst($room['status'])); ?></td>
                                    <td class="actions">
                                        <button type="button" class="edit-button" 
                                                onclick="editRoom(<?php echo htmlspecialchars(json_encode($room)); ?>)">
                                            Edit
                                        </button>
                                        <button type="button" class="delete-button" 
                                                onclick="deleteRoom(<?php echo $room['id']; ?>)">
                                            Delete
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Add/Edit Room Modal -->
            <div id="roomModal" class="modal">
                <div class="modal-content">
                    <span class="close">&times;</span>
                    <h2 id="modalTitle">Add New Room</h2>
                    <form id="roomForm" method="POST" action="manage_room.php">
                        <input type="hidden" name="hotel_id" value="<?php echo $hotel_id; ?>">
                        <input type="hidden" name="room_id" id="room_id">
                        <input type="hidden" name="action" id="room_action" value="add">

                        <div class="form-group">
                            <label for="room_number">Room Number *</label>
                            <input type="text" id="room_number" name="room_number" required>
                        </div>

                        <div class="form-group">
                            <label for="room_type_id">Room Type *</label>
                            <select id="room_type_id" name="room_type_id" required>
                                <?php
                                $stmt = $pdo->prepare("SELECT id, name, base_price FROM room_types WHERE hotel_id = ?");
                                $stmt->execute([$hotel_id]);
                                while ($type = $stmt->fetch()) {
                                    echo '<option value="' . $type['id'] . '">' . 
                                         htmlspecialchars($type['name']) . ' ($' . $type['base_price'] . '/night)</option>';
                                }
                                ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="floor">Floor</label>
                            <input type="text" id="floor" name="floor">
                        </div>

                        <div class="form-group">
                            <label for="room_status">Status *</label>
                            <select id="room_status" name="status" required>
                                <option value="available">Available</option>
                                <option value="occupied">Occupied</option>
                                <option value="maintenance">Maintenance</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="notes">Notes</label>
                            <textarea id="notes" name="notes" rows="3"></textarea>
                        </div>

                        <button type="submit" class="submit-button">Save Room</button>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <footer>
        <p>&copy; 2024 Hotel Discovery Platform</p>
    </footer>

    <script>
        const modal = document.getElementById('roomModal');
        const closeBtn = document.getElementsByClassName('close')[0];
        const roomForm = document.getElementById('roomForm');

        function showAddRoomModal() {
            document.getElementById('modalTitle').textContent = 'Add New Room';
            document.getElementById('room_action').value = 'add';
            document.getElementById('room_id').value = '';
            roomForm.reset();
            modal.classList.add('show');
        }

        function editRoom(room) {
            document.getElementById('modalTitle').textContent = 'Edit Room';
            document.getElementById('room_action').value = 'edit';
            document.getElementById('room_id').value = room.id;
            document.getElementById('room_number').value = room.room_number;
            document.getElementById('room_type_id').value = room.room_type_id;
            document.getElementById('floor').value = room.floor;
            document.getElementById('room_status').value = room.status;
            document.getElementById('notes').value = room.notes;
            modal.classList.add('show');
        }

        function deleteRoom(roomId) {
            if (confirm('Are you sure you want to delete this room? This action cannot be undone.')) {
                // Show loading state
                const deleteBtn = event.target;
                const originalText = deleteBtn.textContent;
                deleteBtn.disabled = true;
                deleteBtn.textContent = 'Deleting...';

                // Create and submit form
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'manage_room.php';

                const fields = {
                    room_id: roomId,
                    hotel_id: <?php echo $hotel_id; ?>,
                    action: 'delete'
                };

                for (const [key, value] of Object.entries(fields)) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = key;
                    input.value = value;
                    form.appendChild(input);
                }

                document.body.appendChild(form);
                form.submit();
            }
        }

        closeBtn.onclick = function() {
            modal.classList.remove('show');
        }

        window.onclick = function(event) {
            if (event.target == modal) {
                modal.classList.remove('show');
            }
        }

        // Close modal on escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape' && modal.classList.contains('show')) {
                modal.classList.remove('show');
            }
        });
    </script>

    <style>
        .rooms-section {
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #ddd;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.4);
        }

        /* When modal is active */
        .modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background-color: #fefefe;
            padding: 2rem;
            border: 1px solid #ddd;
            width: 90%;
            max-width: 500px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            position: relative;
        }

        .modal form {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .modal .form-group {
            margin-bottom: 1rem;
        }

        .modal label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #333;
        }

        .modal input,
        .modal select,
        .modal textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
            transition: border-color 0.2s;
        }

        .modal input:focus,
        .modal select:focus,
        .modal textarea:focus {
            outline: none;
            border-color: #4a90e2;
            box-shadow: 0 0 0 2px rgba(74, 144, 226, 0.2);
        }

        .modal select,
        select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%23333' viewBox='0 0 16 16'%3E%3Cpath d='M8 11L3 6h10l-5 5z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 12px;
            padding-right: 2.5rem;
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
            transition: border-color 0.2s;
        }

        .modal select:focus,
        select:focus {
            outline: none;
            border-color: #4a90e2;
            box-shadow: 0 0 0 2px rgba(74, 144, 226, 0.2);
        }

        .modal .submit-button {
            background-color: #4a90e2;
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 4px;
            font-size: 1rem;
            cursor: pointer;
            transition: background-color 0.2s;
            margin-top: 1rem;
        }

        .modal .submit-button:hover {
            background-color: #357abd;
        }

        .close {
            position: absolute;
            right: 1.5rem;
            top: 1rem;
            color: #666;
            font-size: 1.5rem;
            font-weight: bold;
            cursor: pointer;
            transition: color 0.2s;
            line-height: 1;
        }

        .close:hover,
        .close:focus {
            color: #333;
            text-decoration: none;
        }

        #modalTitle {
            margin-top: 0;
            margin-bottom: 1.5rem;
            color: #333;
            font-size: 1.5rem;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .modal-content {
                width: 95%;
                padding: 1.5rem;
                margin: 1rem;
            }
        }
    </style>
</body>
</html> 