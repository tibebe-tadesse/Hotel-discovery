<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$base_path = '';
require_once 'config/db_connect.php';
require_once 'includes/language.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate password match
    if ($password !== $confirm_password) {
        $error = __('passwords_not_match');
    } else {
        // Check if username exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $error = __('username_exists');
        } else {
            // Check if email exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = __('email_exists');
            } else {
                // Create new user
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
                
                try {
                    $stmt->execute([$username, $email, $hashed_password]);
                    $_SESSION['user_id'] = $pdo->lastInsertId();
                    $_SESSION['username'] = $username;
                    header('Location: index.php');
                    exit();
                } catch (PDOException $e) {
                    $error = __('registration_failed');
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang'] ?? 'en'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('register_title'); ?> - <?php echo __('site_name'); ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/auth.css">
</head>
<body>
    <header>
        <?php include 'includes/header.php'; ?>
    </header>

    <main>
        <div class="auth-container">
            <h2><?php echo __('register_title'); ?></h2>
            <?php if (isset($error) && !empty($error)): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <form action="register.php" method="POST">
                <div class="form-group">
                    <label for="username"><?php echo __('username'); ?></label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="email"><?php echo __('email'); ?></label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="password"><?php echo __('password'); ?></label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="form-group">
                    <label for="confirm_password"><?php echo __('confirm_password'); ?></label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                <button type="submit"><?php echo __('register_title'); ?></button>
            </form>
            <p><?php echo __('have_account'); ?> <a href="login.php"><?php echo __('nav_login'); ?></a></p>
        </div>
    </main>

    <footer>
        <p><?php echo __('copyright'); ?></p>
    </footer>
</body>
</html> 