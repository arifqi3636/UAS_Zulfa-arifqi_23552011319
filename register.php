<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi - Sistem Peternakan Lele</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-box register-box">
            <div class="login-header">
                <h1>🐠 Peternakan Lele</h1>
                <p>Daftar Akun Baru</p>
            </div>

            <?php
            session_start();
            require_once 'config/database.php';
            require_once 'includes/Auth.php';

            $message = '';
            $message_type = '';

            if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register'])) {
                $username = trim($_POST['username'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $password = $_POST['password'] ?? '';
                $confirm_password = $_POST['confirm_password'] ?? '';
                $full_name = trim($_POST['full_name'] ?? '');
                $phone = trim($_POST['phone'] ?? '');
                $address = trim($_POST['address'] ?? '');

                if (empty($username) || empty($email) || empty($password) || empty($full_name)) {
                    $message = 'Username, email, password, dan nama lengkap harus diisi!';
                    $message_type = 'error';
                } elseif ($password !== $confirm_password) {
                    $message = 'Password tidak sama!';
                    $message_type = 'error';
                } elseif (strlen($password) < 6) {
                    $message = 'Password minimal 6 karakter!';
                    $message_type = 'error';
                } else {
                    try {
                        $auth = new Auth();
                        $result = $auth->register($username, $email, $password, $full_name, $phone, $address);
                        $message = $result['message'];
                        $message_type = $result['success'] ? 'success' : 'error';

                        if ($result['success']) {
                            header('refresh:3;url=index.php');
                        }
                    } catch (Exception $e) {
                        $message = 'Error: ' . $e->getMessage();
                        $message_type = 'error';
                    }
                }
            }

            if (!empty($message)) {
                echo '<div class="alert alert-' . $message_type . '">' . htmlspecialchars($message) . '</div>';
            }
            ?>

            <form method="POST" class="login-form">
                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" required placeholder="Pilih username">
                </div>

                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required placeholder="Masukkan email">
                </div>

                <div class="form-group">
                    <label for="full_name">Nama Lengkap:</label>
                    <input type="text" id="full_name" name="full_name" required placeholder="Masukkan nama lengkap">
                </div>

                <div class="form-group">
                    <label for="phone">No. Telepon:</label>
                    <input type="tel" id="phone" name="phone" placeholder="Masukkan nomor telepon">
                </div>

                <div class="form-group">
                    <label for="address">Alamat:</label>
                    <textarea id="address" name="address" rows="3" placeholder="Masukkan alamat lengkap"></textarea>
                </div>

                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required placeholder="Buat password yang kuat">
                </div>

                <div class="form-group">
                    <label for="confirm_password">Konfirmasi Password:</label>
                    <input type="password" id="confirm_password" name="confirm_password" required placeholder="Ulangi password">
                </div>

                <button type="submit" name="register" class="btn btn-primary btn-block">Daftar</button>
            </form>

            <div class="login-footer">
                <p>Sudah punya akun? <a href="index.php">Login di sini</a></p>
            </div>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; Copyright by 23552011319_ZulfaArifqi_23CNS-A_UASWEB1</p>
    </footer>
</body>
</html>