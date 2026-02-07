<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Pakan - Sistem Peternakan Lele</title>
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

    $feed_manager = new FeedManager();
    $pond_manager = new PondManager();

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        if (isset($_POST['add_feed'])) {
            $result = $feed_manager->create(
                $user_id,
                $_POST['pond_id'] ?? 0,
                $_POST['feed_type'] ?? '',
                $_POST['quantity_kg'] ?? 0,
                $_POST['feed_date'] ?? date('Y-m-d'),
                $_POST['time_fed'] ?? null,
                $_POST['cost'] ?? null,
                $_POST['supplier'] ?? null,
                $_POST['notes'] ?? ''
            );
            $message = $result['message'];
            $message_type = $result['success'] ? 'success' : 'error';
            if ($result['success']) $action = 'list';
        } elseif (isset($_POST['update_feed'])) {
            $feed_id = $_POST['feed_id'] ?? 0;
            $result = $feed_manager->update(
                $feed_id,
                $user_id,
                $_POST['feed_type'] ?? '',
                $_POST['quantity_kg'] ?? 0,
                $_POST['feed_date'] ?? date('Y-m-d'),
                $_POST['time_fed'] ?? null,
                $_POST['cost'] ?? null,
                $_POST['supplier'] ?? null,
                $_POST['notes'] ?? ''
            );
            $message = $result['message'];
            $message_type = $result['success'] ? 'success' : 'error';
            if ($result['success']) $action = 'list';
        } elseif (isset($_POST['delete_feed'])) {
            $feed_id = $_POST['feed_id'] ?? 0;
            $result = $feed_manager->delete($feed_id, $user_id);
            $message = $result['message'];
            $message_type = $result['success'] ? 'success' : 'error';
            $action = 'list';
        }
    }

    $feeds = $feed_manager->getAll($user_id);
    $ponds = $pond_manager->getPonds($user_id);
    $feed = null;

    if (in_array($action, ['edit']) && isset($_GET['id'])) {
        $feed = $feed_manager->getOne($_GET['id'], $user_id);
        if (!$feed) $action = 'list';
    }
    ?>

    <nav class="navbar">
        <div class="navbar-container">
            <h2 class="navbar-brand">🐠 Peternakan Lele</h2>
            <ul class="nav-menu">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="ponds.php">Kolam</a></li>
                <li><a href="fish.php">Inventori Ikan</a></li>
                <li><a href="feed.php" class="active">Pakan</a></li>
                <li><a href="health.php">Kesehatan</a></li>
                <li><a href="reports.php">Laporan</a></li>
                <li><a href="profile.php">Profil</a></li>
                <li><a href="logout.php" class="btn-logout">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="page-header">
            <h1>🥗 Manajemen Pakan</h1>
            <p>Catat dan pantau pemberian pakan untuk setiap kolam</p>
        </div>

        <?php if (!empty($message)) echo '<div class="alert alert-' . $message_type . '">' . htmlspecialchars($message) . '</div>'; ?>

        <?php if ($action == 'list') { ?>
            <div class="section-actions">
                <a href="?action=add" class="btn btn-primary">+ Catat Pemberian Pakan</a>
            </div>

            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Kolam</th>
                            <th>Tipe Pakan</th>
                            <th>Jumlah (kg)</th>
                            <th>Tanggal</th>
                            <th>Biaya</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if (empty($feeds)) {
                            echo '<tr><td colspan="7" class="text-center">Belum ada data pakan</td></tr>';
                        } else {
                            $no = 1;
                            foreach ($feeds as $f) {
                                echo '<tr>';
                                echo '<td>' . $no++ . '</td>';
                                echo '<td>' . htmlspecialchars($f['pond_name']) . '</td>';
                                echo '<td>' . htmlspecialchars($f['feed_type']) . '</td>';
                                echo '<td>' . $f['quantity_kg'] . '</td>';
                                echo '<td>' . date('d-m-Y', strtotime($f['feed_date'])) . '</td>';
                                echo '<td>Rp ' . number_format($f['cost'] ?? 0) . '</td>';
                                echo '<td class="action-buttons">';
                                echo '<a href="?action=edit&id=' . $f['id'] . '" class="btn-small btn-edit">Edit</a>';
                                echo '<form method="POST" style="display:inline;" onsubmit="return confirm(\'Yakin hapus?\');">';
                                echo '<input type="hidden" name="feed_id" value="' . $f['id'] . '">';
                                echo '<button type="submit" name="delete_feed" class="btn-small btn-delete">Hapus</button>';
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
                <h2>Catat Pemberian Pakan</h2>
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
                        <label for="feed_type">Tipe Pakan:</label>
                        <input type="text" id="feed_type" name="feed_type" placeholder="Contoh: Pellet, Tepung ikan" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="quantity_kg">Jumlah (kg):</label>
                            <input type="number" id="quantity_kg" name="quantity_kg" step="0.1" required>
                        </div>

                        <div class="form-group">
                            <label for="feed_date">Tanggal:</label>
                            <input type="date" id="feed_date" name="feed_date" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="time_fed">Waktu Pemberian:</label>
                            <input type="time" id="time_fed" name="time_fed">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="cost">Biaya (Rp):</label>
                            <input type="number" id="cost" name="cost" step="0.01">
                        </div>

                        <div class="form-group">
                            <label for="supplier">Supplier:</label>
                            <input type="text" id="supplier" name="supplier">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="notes">Catatan:</label>
                        <textarea id="notes" name="notes" rows="3"></textarea>
                    </div>

                    <div class="form-actions">
                        <button type="submit" name="add_feed" class="btn btn-primary">Catat Pakan</button>
                        <a href="feed.php" class="btn btn-secondary">Batal</a>
                    </div>
                </form>
            </div>

        <?php } elseif ($action == 'edit' && $feed) { ?>
            <div class="form-section">
                <h2>Edit Data Pakan</h2>
                <form method="POST" class="crud-form">
                    <input type="hidden" name="feed_id" value="<?php echo $feed['id']; ?>">

                    <div class="form-group">
                        <label for="feed_type">Tipe Pakan:</label>
                        <input type="text" id="feed_type" name="feed_type" value="<?php echo htmlspecialchars($feed['feed_type']); ?>" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="quantity_kg">Jumlah (kg):</label>
                            <input type="number" id="quantity_kg" name="quantity_kg" step="0.1" value="<?php echo $feed['quantity_kg']; ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="feed_date">Tanggal:</label>
                            <input type="date" id="feed_date" name="feed_date" value="<?php echo $feed['feed_date']; ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="time_fed">Waktu Pemberian:</label>
                            <input type="time" id="time_fed" name="time_fed" value="<?php echo $feed['time_fed'] ?? ''; ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="cost">Biaya (Rp):</label>
                            <input type="number" id="cost" name="cost" step="0.01" value="<?php echo $feed['cost'] ?? ''; ?>">
                        </div>

                        <div class="form-group">
                            <label for="supplier">Supplier:</label>
                            <input type="text" id="supplier" name="supplier" value="<?php echo htmlspecialchars($feed['supplier'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="notes">Catatan:</label>
                        <textarea id="notes" name="notes" rows="3"><?php echo htmlspecialchars($feed['notes'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-actions">
                        <button type="submit" name="update_feed" class="btn btn-primary">Simpan</button>
                        <a href="feed.php" class="btn btn-secondary">Batal</a>
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
