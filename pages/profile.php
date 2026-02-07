<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Pengguna - Sistem Peternakan Lele</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php
    session_start();
    require_once '../config/database.php';
    require_once '../includes/Auth.php';

    $auth = new Auth();
    $user = $auth->verifySession();

    if (!$user) {
        header('Location: ../index.php');
        exit();
    }

    $user_id = $user['user_id'];
    $message = '';
    $message_type = '';

    // Get user data
    $user_data = $auth->getUserData($user_id);

    // Handle profile update
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
        $db = getDB();
        try {
            $stmt = $db->prepare("
                UPDATE users SET 
                full_name = ?, phone = ?, address = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            $stmt->execute([
                $_POST['full_name'] ?? '',
                $_POST['phone'] ?? '',
                $_POST['address'] ?? '',
                $user_id
            ]);
            $message = 'Profil berhasil diperbarui!';
            $message_type = 'success';
            $user_data = $auth->getUserData($user_id);
        } catch (Exception $e) {
            $message = 'Error: ' . $e->getMessage();
            $message_type = 'error';
        }
    }

    // Handle password change
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
        $old_password = $_POST['old_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (!password_verify($old_password, $user_data['password'])) {
            $message = 'Password lama salah!';
            $message_type = 'error';
        } elseif ($new_password !== $confirm_password) {
            $message = 'Password baru tidak sama!';
            $message_type = 'error';
        } else {
            $db = getDB();
            try {
                $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
                $stmt = $db->prepare("UPDATE users SET password = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                $stmt->execute([$hashed_password, $user_id]);
                $message = 'Password berhasil diubah!';
                $message_type = 'success';
                $user_data = $auth->getUserData($user_id);
            } catch (Exception $e) {
                $message = 'Error: ' . $e->getMessage();
                $message_type = 'error';
            }
        }
    }
    ?>

    <nav class="navbar">
        <div class="navbar-container">
            <h2 class="navbar-brand">🐠 Peternakan Lele</h2>
            <ul class="nav-menu">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="ponds.php">Kolam</a></li>
                <li><a href="fish.php">Inventori Ikan</a></li>
                <li><a href="feed.php">Pakan</a></li>
                <li><a href="health.php">Kesehatan</a></li>
                <li><a href="reports.php">Laporan</a></li>
                <li><a href="profile.php" class="active">Profil</a></li>
                <li><a href="logout.php" class="btn-logout">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="page-header">
            <h1>👤 Profil Pengguna</h1>
            <p>Kelola informasi akun dan keamanan Anda</p>
        </div>

        <?php if (!empty($message)) echo '<div class="alert alert-' . $message_type . '">' . htmlspecialchars($message) . '</div>'; ?>

        <div class="profile-container">
            <!-- Update Profile Section -->
            <div class="profile-section">
                <h2>📝 Informasi Profil</h2>
                <form method="POST" class="crud-form">
                    <div class="form-group">
                        <label for="username">Username:</label>
                        <input type="text" id="username" value="<?php echo htmlspecialchars($user_data['username']); ?>" readonly>
                        <small>Username tidak dapat diubah</small>
                    </div>

                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" id="email" value="<?php echo htmlspecialchars($user_data['email']); ?>" readonly>
                        <small>Email tidak dapat diubah</small>
                    </div>

                    <div class="form-group">
                        <label for="full_name">Nama Lengkap:</label>
                        <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user_data['full_name']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="phone">No. Telepon:</label>
                        <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user_data['phone'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="address">Alamat:</label>
                        <textarea id="address" name="address" rows="4"><?php echo htmlspecialchars($user_data['address'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>Anggota Sejak:</label>
                        <p><?php echo date('d-m-Y H:i', strtotime($user_data['created_at'])); ?></p>
                    </div>

                    <div class="form-actions">
                        <button type="submit" name="update_profile" class="btn btn-primary">Simpan Perubahan</button>
                    </div>
                </form>
            </div>

            <!-- Change Password Section -->
            <div class="profile-section">
                <h2>🔒 Ubah Password</h2>
                <form method="POST" class="crud-form">
                    <div class="form-group">
                        <label for="old_password">Password Lama:</label>
                        <input type="password" id="old_password" name="old_password" required>
                    </div>

                    <div class="form-group">
                        <label for="new_password">Password Baru:</label>
                        <input type="password" id="new_password" name="new_password" required>
                        <small>Minimal 6 karakter, kombinasi huruf dan angka</small>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Konfirmasi Password Baru:</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>

                    <div class="form-actions">
                        <button type="submit" name="change_password" class="btn btn-primary">Ubah Password</button>
                    </div>
                </form>
            </div>

            <!-- Account Info Section -->
            <div class="profile-section">
                <h2>ℹ️ Informasi Akun</h2>
                <div class="info-box">
                    <p><strong>Status:</strong> <span class="badge badge-success">Aktif</span></p>
                    <p><strong>Tipe Akun:</strong> Pengguna Biasa</p>
                    <p><strong>Terakhir Login:</strong> <?php echo date('d-m-Y H:i'); ?></p>
                    <p><strong>Sesi:</strong> Aktif (7 hari)</p>
                </div>

                <h3>🔐 Keamanan</h3>
                <div class="info-box">
                    <p>✅ Password dilindungi dengan enkripsi BCrypt</p>
                    <p>✅ Sesi dikelola dengan cookie terenkripsi</p>
                    <p>✅ Akses terbatas hanya untuk pengguna terautentikasi</p>
                    <p>✅ Sesi otomatis habis setelah 30 hari tidak aktif</p>
                </div>

                <h3>⚠️ Tips Keamanan</h3>
                <div class="info-box">
                    <ul>
                        <li>Gunakan password yang kuat dan unik</li>
                        <li>Jangan bagikan credential akun Anda kepada siapa pun</li>
                        <li>Logout dari akun jika menggunakan komputer publik</li>
                        <li>Selalu perbarui password secara berkala</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; Copyright by 23552011319_ZulfaArifqi_23CNS-A_UASWEB1</p>
    </footer>
</body>
</html>
