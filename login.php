<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$base_path = '';
require_once 'config/db_connect.php';
require_once 'includes/language.php';

// Check for database connection error
if (isset($db_error)) {
    die($db_error);
}

// Check if PDO object exists
if (!isset($pdo)) {
    die("Database connection failed");
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['is_admin'] = (bool)$user['is_admin'];
            
            if ($_SESSION['is_admin']) {
                header('Location: admin/index.php');
                exit();
            }  else {
                // Redirect after setting session variables
                header('Location: index.php');
                exit();
            }
           
        } else {
            $error = __('invalid_credentials');
        }
    } catch (PDOException $e) {
        $error = __('db_error');
    }
}

// Only output HTML if we haven't redirected
?>

<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang'] ?? 'en'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('login_title'); ?> - <?php echo __('site_name'); ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/auth.css">
</head>
<body>
    <header>
        <?php include 'includes/header.php'; ?>
    </header>

    <main>
        <div class="auth-container">
            <h2><?php echo __('login_title'); ?></h2>
            <?php if (isset($error) && !empty($error)): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <form action="login.php" method="POST">
                <div class="form-group">
                    <label for="username"><?php echo __('username'); ?></label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="password"><?php echo __('password'); ?></label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit"><?php echo __('login_title'); ?></button>
            </form>
            <p><?php echo __('no_account'); ?> <a href="register.php"><?php echo __('nav_register'); ?></a></p>
        </div>
    </main>

    <footer>
        <p><?php echo __('copyright'); ?></p>
    </footer>
</body>
</html> 