<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Peternakan Lele</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <h1>🐠 Peternakan Lele</h1>
                <p>Sistem Informasi Manajemen Budidaya Lele</p>
            </div>

            <?php
            session_start();
            require_once 'config/database.php';
            require_once 'includes/Auth.php';

            $message = '';
            $message_type = '';

            if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
                $username = $_POST['username'] ?? '';
                $password = $_POST['password'] ?? '';

                $auth = new Auth();
                $result = $auth->login($username, $password);

                if ($result['success']) {
                    header('Location: pages/dashboard.php');
                    exit();
                } else {
                    $message = $result['message'];
                    $message_type = 'error';
                }
            }

            if (!empty($message)) {
                echo '<div class="alert alert-' . $message_type . '">' . htmlspecialchars($message) . '</div>';
            }
            ?>

            <form method="POST" class="login-form">
                <div class="form-group">
                    <label for="username">Username atau Email:</label>
                    <input type="text" id="username" name="username" required placeholder="Masukkan username atau email">
                </div>

                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required placeholder="Masukkan password">
                </div>

                <button type="submit" name="login" class="btn btn-primary btn-block">Login</button>
            </form>

            <div class="login-footer">
                <p>Belum punya akun? <a href="register.php">Daftar di sini</a></p>
            </div>
        </div>
    </div>
</body>
</html>
