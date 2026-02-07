<?php
require_once '../config/database.php';
require_once '../includes/Auth.php';

// Verify session
Auth::verifySession();

// Get user data
$userData = Auth::getUserData($_COOKIE['user_id']);

// Check if user is admin
if ($userData['role'] !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

// Handle actions
$action = $_GET['action'] ?? 'dashboard';
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = getDB();

    if ($action === 'add_user' || $action === 'edit_user') {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $full_name = trim($_POST['full_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $role = $_POST['role'] ?? 'user';

        if (empty($username) || empty($email) || empty($full_name)) {
            $error = 'Username, email, dan nama lengkap wajib diisi!';
        } elseif ($action === 'add_user' && empty($password)) {
            $error = 'Password wajib diisi untuk user baru!';
        } else {
            try {
                if ($action === 'add_user') {
                    $hashed = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
                    $stmt = $db->prepare("INSERT INTO users (username, email, password, full_name, phone, address, role) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$username, $email, $hashed, $full_name, $phone, $address, $role]);
                    $message = 'User berhasil ditambahkan!';
                } else {
                    $id = $_GET['id'] ?? 0;
                    if (!empty($password)) {
                        $hashed = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
                        $stmt = $db->prepare("UPDATE users SET username=?, email=?, password=?, full_name=?, phone=?, address=?, role=? WHERE id=?");
                        $stmt->execute([$username, $email, $hashed, $full_name, $phone, $address, $role, $id]);
                    } else {
                        $stmt = $db->prepare("UPDATE users SET username=?, email=?, full_name=?, phone=?, address=?, role=? WHERE id=?");
                        $stmt->execute([$username, $email, $full_name, $phone, $address, $role, $id]);
                    }
                    $message = 'User berhasil diperbarui!';
                }
                header('Location: admin.php?action=users&message=' . urlencode($message));
                exit;
            } catch (PDOException $e) {
                $error = 'Error: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'delete_user') {
        $id = $_POST['id'] ?? 0;
        if ($id == $userData['id']) {
            $error = 'Tidak bisa menghapus akun sendiri!';
        } else {
            try {
                $stmt = $db->prepare("DELETE FROM users WHERE id=?");
                $stmt->execute([$id]);
                $message = 'User berhasil dihapus!';
            } catch (PDOException $e) {
                $error = 'Error: ' . $e->getMessage();
            }
        }
    }
}

// Get message from URL
if (isset($_GET['message'])) {
    $message = $_GET['message'];
}

// Get statistics for admin dashboard
$db = getDB();
$stats = [
    'total_users' => 0,
    'total_ponds' => 0,
    'total_fish' => 0
];

try {
    $stmt = $db->query("SELECT COUNT(*) as total FROM users");
    $stats['total_users'] = $stmt->fetch()['total'];

    $stmt = $db->query("SELECT COUNT(*) as total FROM ponds");
    $stats['total_ponds'] = $stmt->fetch()['total'];

    $stmt = $db->query("SELECT SUM(quantity) as total FROM fish_inventory");
    $stats['total_fish'] = $stmt->fetch()['total'] ?? 0;
} catch (PDOException $e) {
    $error = 'Error loading statistics';
}

// Get users data
$users = [];
try {
    $stmt = $db->query("SELECT id, username, email, full_name, phone, role, created_at FROM users ORDER BY created_at DESC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = 'Error loading users';
}

// Get single user for edit
$user = null;
if ($action === 'edit_user' && isset($_GET['id'])) {
    $id = $_GET['id'];
    try {
        $stmt = $db->prepare("SELECT * FROM users WHERE id=?");
        $stmt->execute([$id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error = 'Error loading user';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Catfish Farming System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-brand">
                <i class="fas fa-crown"></i>
                <span>Admin Panel</span>
            </div>
            <div class="nav-menu">
                <a href="admin.php" class="nav-link <?php echo $action === 'dashboard' ? 'active' : ''; ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="admin.php?action=users" class="nav-link <?php echo $action === 'users' || $action === 'add_user' || $action === 'edit_user' ? 'active' : ''; ?>"><i class="fas fa-users-cog"></i> Users</a>
                <a href="dashboard.php" class="nav-link"><i class="fas fa-arrow-left"></i> Back to App</a>
                <a href="logout.php" class="nav-link logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="page-header">
            <h1><i class="fas fa-crown"></i> Admin Panel</h1>
            <p>Kelola sistem dan pengguna</p>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($action === 'dashboard'): ?>
            <!-- Admin Dashboard -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">👥</div>
                    <h3>Total Users</h3>
                    <p class="stat-value"><?php echo $stats['total_users']; ?></p>
                    <a href="?action=users" class="stat-link">Kelola Users →</a>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">🏊</div>
                    <h3>Total Kolam</h3>
                    <p class="stat-value"><?php echo $stats['total_ponds']; ?></p>
                    <span class="stat-link">Semua Kolam</span>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">🐟</div>
                    <h3>Total Ikan</h3>
                    <p class="stat-value"><?php echo number_format($stats['total_fish']); ?></p>
                    <span class="stat-link">Semua Ikan</span>
                </div>
            </div>

            <div class="admin-actions">
                <h2>⚡ Quick Actions</h2>
                <div class="action-buttons">
                    <a href="?action=add_user" class="btn btn-primary">
                        <i class="fas fa-user-plus"></i> Tambah User
                    </a>
                    <a href="reports.php" class="btn btn-info">
                        <i class="fas fa-chart-bar"></i> Laporan Sistem
                    </a>
                </div>
            </div>

        <?php elseif ($action === 'users'): ?>
            <!-- Users Management -->
            <div class="action-bar">
                <a href="?action=add_user" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Tambah User
                </a>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3>Daftar Users</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($users)): ?>
                        <div class="empty-state">
                            <i class="fas fa-users"></i>
                            <h3>Belum ada users</h3>
                            <p>Silakan tambahkan user pertama</p>
                            <a href="?action=add_user" class="btn btn-primary">Tambah User</a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Username</th>
                                        <th>Nama Lengkap</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Dibuat</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $u): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($u['username']); ?></td>
                                            <td><?php echo htmlspecialchars($u['full_name']); ?></td>
                                            <td><?php echo htmlspecialchars($u['email']); ?></td>
                                            <td>
                                                <span class="badge badge-<?php echo $u['role'] === 'admin' ? 'primary' : 'secondary'; ?>">
                                                    <?php echo $u['role'] === 'admin' ? 'Admin' : 'User'; ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('d/m/Y', strtotime($u['created_at'])); ?></td>
                                            <td>
                                                <div class="action-buttons">
                                                    <a href="?action=edit_user&id=<?php echo $u['id']; ?>" class="btn btn-sm btn-warning" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <?php if ($u['id'] != $userData['id']): ?>
                                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Yakin hapus user ini?')">
                                                            <input type="hidden" name="action" value="delete_user">
                                                            <input type="hidden" name="id" value="<?php echo $u['id']; ?>">
                                                            <button type="submit" class="btn btn-sm btn-danger" title="Hapus">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        <?php elseif ($action === 'add_user' || $action === 'edit_user'): ?>
            <!-- Add/Edit User Form -->
            <div class="card">
                <div class="card-header">
                    <h3><?php echo $action === 'add_user' ? 'Tambah User' : 'Edit User'; ?></h3>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="username">Username *</label>
                                <input type="text" id="username" name="username" class="form-control"
                                       value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>" required>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="email">Email *</label>
                                <input type="email" id="email" name="email" class="form-control"
                                       value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="password"><?php echo $action === 'add_user' ? 'Password *' : 'Password (kosongkan jika tidak diubah)'; ?></label>
                                <input type="password" id="password" name="password" class="form-control"
                                       <?php echo $action === 'add_user' ? 'required' : ''; ?>>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="full_name">Nama Lengkap *</label>
                                <input type="text" id="full_name" name="full_name" class="form-control"
                                       value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="phone">Telepon</label>
                                <input type="tel" id="phone" name="phone" class="form-control"
                                       value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                            </div>
                            <div class="form-group col-md-6">
                                <label for="role">Role</label>
                                <select id="role" name="role" class="form-control">
                                    <option value="user" <?php echo ($user['role'] ?? 'user') === 'user' ? 'selected' : ''; ?>>User</option>
                                    <option value="admin" <?php echo ($user['role'] ?? '') === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="address">Alamat</label>
                            <textarea id="address" name="address" class="form-control" rows="3"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> <?php echo $action === 'add_user' ? 'Simpan' : 'Update'; ?>
                            </button>
                            <a href="?action=users" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Batal
                            </a>
                        </div>
                    </form>
                </div>
            </div>

        <?php endif; ?>
    </div>

    <script>
        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 300);
            });
        }, 5000);
    </script>
</body>
</html>