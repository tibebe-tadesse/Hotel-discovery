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

// Handle admin status toggle
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['user_id']) && isset($_POST['action'])) {
    $target_user_id = (int)$_POST['user_id'];
    $action = $_POST['action'];
    
    // Prevent self-demotion
    if ($target_user_id !== $_SESSION['user_id']) {
        if ($action === 'make_admin') {
            $stmt = $pdo->prepare("UPDATE users SET is_admin = TRUE WHERE id = ?");
            $stmt->execute([$target_user_id]);
        } elseif ($action === 'remove_admin') {
            $stmt = $pdo->prepare("UPDATE users SET is_admin = FALSE WHERE id = ?");
            $stmt->execute([$target_user_id]);
        }
    }
}

// Fetch all users with their booking counts
$stmt = $pdo->query("
    SELECT u.*, 
           COUNT(DISTINCT b.id) as booking_count,
           SUM(CASE WHEN b.status = 'confirmed' THEN 1 ELSE 0 END) as active_bookings
    FROM users u
    LEFT JOIN bookings b ON u.id = b.user_id
    GROUP BY u.id
    ORDER BY u.created_at DESC
");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin Panel</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="../css/admin-users.css">
</head>
<body>
    <header>
        <?php include '../includes/header.php'; ?>
    </header>

    <main>
        <div class="admin-container">
            <div class="admin-header">
                <h1>Manage Users</h1>
            </div>

            <div class="users-table">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Joined</th>
                            <th>Bookings</th>
                            <th>Role</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($users as $u): ?>
                            <tr>
                                <td><?php echo $u['id']; ?></td>
                                <td><?php echo htmlspecialchars($u['username']); ?></td>
                                <td><?php echo htmlspecialchars($u['email']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($u['created_at'])); ?></td>
                                <td>
                                    Total: <?php echo $u['booking_count']; ?><br>
                                    Active: <?php echo $u['active_bookings']; ?>
                                </td>
                                <td>
                                    <span class="role-badge <?php echo $u['is_admin'] ? 'admin' : 'user'; ?>">
                                        <?php echo $u['is_admin'] ? 'Admin' : 'User'; ?>
                                    </span>
                                </td>
                                <td class="actions">
                                    <?php if ($u['id'] !== $_SESSION['user_id']): ?>
                                        <form method="POST" class="role-form">
                                            <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                            <?php if ($u['is_admin']): ?>
                                                <button type="submit" name="action" value="remove_admin" 
                                                        class="role-button remove-admin"
                                                        onclick="return confirm('Remove admin privileges from this user?')">
                                                    Remove Admin
                                                </button>
                                            <?php else: ?>
                                                <button type="submit" name="action" value="make_admin" 
                                                        class="role-button make-admin"
                                                        onclick="return confirm('Make this user an admin?')">
                                                    Make Admin
                                                </button>
                                            <?php endif; ?>
                                        </form>
                                    <?php else: ?>
                                        <span class="current-user">Current User</span>
                                    <?php endif; ?>
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