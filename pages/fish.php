<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventori Ikan - Sistem Peternakan Lele</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php
    session_start();
    require_once '../config/database.php';
    require_once '../includes/Auth.php';
    require_once '../includes/Database.php';

    $auth = new Auth();
    $user = $auth->verifySession();

    if (!$user) {
        header('Location: ../index.php');
        exit();
    }

    $user_id = $user['user_id'];
    $action = $_GET['action'] ?? 'list';
    $message = '';
    $message_type = '';

    $fish_manager = new FishInventoryManager();
    $pond_manager = new PondManager();

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        if (isset($_POST['add_fish'])) {
            $result = $fish_manager->create(
                $user_id,
                $_POST['pond_id'] ?? 0,
                $_POST['species'] ?? '',
                $_POST['quantity'] ?? 0,
                $_POST['size'] ?? null,
                $_POST['age_days'] ?? null,
                $_POST['health_status'] ?? 'healthy',
                $_POST['notes'] ?? ''
            );
            $message = $result['message'];
            $message_type = $result['success'] ? 'success' : 'error';
            if ($result['success']) $action = 'list';
        } elseif (isset($_POST['update_fish'])) {
            $fish_id = $_POST['fish_id'] ?? 0;
            $result = $fish_manager->update(
                $fish_id,
                $user_id,
                $_POST['species'] ?? '',
                $_POST['quantity'] ?? 0,
                $_POST['size'] ?? null,
                $_POST['age_days'] ?? null,
                $_POST['health_status'] ?? 'healthy',
                $_POST['notes'] ?? ''
            );
            $message = $result['message'];
            $message_type = $result['success'] ? 'success' : 'error';
            if ($result['success']) $action = 'list';
        } elseif (isset($_POST['delete_fish'])) {
            $fish_id = $_POST['fish_id'] ?? 0;
            $result = $fish_manager->delete($fish_id, $user_id);
            $message = $result['message'];
            $message_type = $result['success'] ? 'success' : 'error';
            $action = 'list';
        }
    }

    $fishes = $fish_manager->getAll($user_id);
    $ponds = $pond_manager->getPonds($user_id);
    $fish = null;

    if (in_array($action, ['edit', 'view']) && isset($_GET['id'])) {
        $fish = $fish_manager->getOne($_GET['id'], $user_id);
        if (!$fish) $action = 'list';
    }
    ?>

    <nav class="navbar">
        <div class="navbar-container">
            <h2 class="navbar-brand">🐠 Peternakan Lele</h2>
            <ul class="nav-menu">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="ponds.php">Kolam</a></li>
                <li><a href="fish.php" class="active">Inventori Ikan</a></li>
                <li><a href="feed.php">Pakan</a></li>
                <li><a href="health.php">Kesehatan</a></li>
                <li><a href="reports.php">Laporan</a></li>
                <li><a href="profile.php">Profil</a></li>
                <li><a href="logout.php" class="btn-logout">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="page-header">
            <h1>🐟 Inventori Ikan</h1>
            <p>Kelola data ikan di setiap kolam</p>
        </div>

        <?php if (!empty($message)) echo '<div class="alert alert-' . $message_type . '">' . htmlspecialchars($message) . '</div>'; ?>

        <?php if ($action == 'list') { ?>
            <div class="section-actions">
                <a href="?action=add" class="btn btn-primary">+ Tambah Data Ikan</a>
            </div>

            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Spesies</th>
                            <th>Kolam</th>
                            <th>Jumlah</th>
                            <th>Ukuran (cm)</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if (empty($fishes)) {
                            echo '<tr><td colspan="7" class="text-center">Belum ada data ikan</td></tr>';
                        } else {
                            $no = 1;
                            foreach ($fishes as $f) {
                                echo '<tr>';
                                echo '<td>' . $no++ . '</td>';
                                echo '<td>' . htmlspecialchars($f['species']) . '</td>';
                                echo '<td>' . htmlspecialchars($f['pond_name']) . '</td>';
                                echo '<td>' . number_format($f['quantity']) . '</td>';
                                echo '<td>' . ($f['size'] ?? '-') . '</td>';
                                echo '<td><span class="badge badge-' . ($f['health_status'] == 'healthy' ? 'success' : 'danger') . '">' . ucfirst($f['health_status']) . '</span></td>';
                                echo '<td class="action-buttons">';
                                echo '<a href="?action=edit&id=' . $f['id'] . '" class="btn-small btn-edit">Edit</a>';
                                echo '<form method="POST" style="display:inline;" onsubmit="return confirm(\'Yakin hapus?\');">';
                                echo '<input type="hidden" name="fish_id" value="' . $f['id'] . '">';
                                echo '<button type="submit" name="delete_fish" class="btn-small btn-delete">Hapus</button>';
                                echo '</form>';
                                echo '</td></tr>';
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </div>

        <?php } elseif ($action == 'add') { ?>
            <div class="form-section">
                <h2>Tambah Data Ikan</h2>
                <form method="POST" class="crud-form">
                    <div class="form-group">
                        <label for="pond_id">Kolam:</label>
                        <select id="pond_id" name="pond_id" required>
                            <option value="">-- Pilih Kolam --</option>
                            <?php foreach ($ponds as $p) {
                                echo '<option value="' . $p['id'] . '">' . htmlspecialchars($p['pond_name']) . '</option>';
                            } ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="species">Spesies Ikan:</label>
                        <input type="text" id="species" name="species" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="quantity">Jumlah (ekor):</label>
                            <input type="number" id="quantity" name="quantity" required>
                        </div>

                        <div class="form-group">
                            <label for="size">Ukuran (cm):</label>
                            <input type="number" id="size" name="size" step="0.1">
                        </div>

                        <div class="form-group">
                            <label for="age_days">Umur (hari):</label>
                            <input type="number" id="age_days" name="age_days">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="health_status">Status Kesehatan:</label>
                        <select id="health_status" name="health_status">
                            <option value="healthy">Sehat</option>
                            <option value="sick">Sakit</option>
                            <option value="observation">Pengamatan</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="notes">Catatan:</label>
                        <textarea id="notes" name="notes" rows="3"></textarea>
                    </div>

                    <div class="form-actions">
                        <button type="submit" name="add_fish" class="btn btn-primary">Tambah Ikan</button>
                        <a href="fish.php" class="btn btn-secondary">Batal</a>
                    </div>
                </form>
            </div>

        <?php } elseif ($action == 'edit' && $fish) { ?>
            <div class="form-section">
                <h2>Edit Data Ikan</h2>
                <form method="POST" class="crud-form">
                    <input type="hidden" name="fish_id" value="<?php echo $fish['id']; ?>">

                    <div class="form-group">
                        <label for="species">Spesies Ikan:</label>
                        <input type="text" id="species" name="species" value="<?php echo htmlspecialchars($fish['species']); ?>" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="quantity">Jumlah (ekor):</label>
                            <input type="number" id="quantity" name="quantity" value="<?php echo $fish['quantity']; ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="size">Ukuran (cm):</label>
                            <input type="number" id="size" name="size" step="0.1" value="<?php echo $fish['size'] ?? ''; ?>">
                        </div>

                        <div class="form-group">
                            <label for="age_days">Umur (hari):</label>
                            <input type="number" id="age_days" name="age_days" value="<?php echo $fish['age_days'] ?? ''; ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="health_status">Status Kesehatan:</label>
                        <select id="health_status" name="health_status">
                            <option value="healthy" <?php echo $fish['health_status'] == 'healthy' ? 'selected' : ''; ?>>Sehat</option>
                            <option value="sick" <?php echo $fish['health_status'] == 'sick' ? 'selected' : ''; ?>>Sakit</option>
                            <option value="observation" <?php echo $fish['health_status'] == 'observation' ? 'selected' : ''; ?>>Pengamatan</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="notes">Catatan:</label>
                        <textarea id="notes" name="notes" rows="3"><?php echo htmlspecialchars($fish['notes'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-actions">
                        <button type="submit" name="update_fish" class="btn btn-primary">Simpan</button>
                        <a href="fish.php" class="btn btn-secondary">Batal</a>
                    </div>
                </form>
            </div>
        <?php } ?>
    </div>

    <footer class="footer">
        <p>&copy; Copyright by 23552011319_ZulfaArifqi_23CNS-A_UASWEB1</p>
    </footer>
</body>
</html>
