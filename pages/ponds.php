<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Kolam - Sistem Peternakan Lele</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php
    session_start();
    require_once '../config/database.php';
    require_once '../includes/Auth.php';
    require_once '../includes/Database.php';

    // Verify session
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

    $pond_manager = new PondManager();

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        if (isset($_POST['add_pond'])) {
            $result = $pond_manager->createPond(
                $user_id,
                $_POST['pond_name'] ?? '',
                $_POST['location'] ?? '',
                $_POST['size_area'] ?? 0,
                $_POST['capacity'] ?? 0,
                $_POST['water_source'] ?? '',
                $_POST['notes'] ?? ''
            );
            $message = $result['message'];
            $message_type = $result['success'] ? 'success' : 'error';
            if ($result['success']) {
                $action = 'list';
            }
        } elseif (isset($_POST['update_pond'])) {
            $pond_id = $_POST['pond_id'] ?? 0;
            $result = $pond_manager->updatePond(
                $pond_id,
                $user_id,
                $_POST['pond_name'] ?? '',
                $_POST['location'] ?? '',
                $_POST['size_area'] ?? 0,
                $_POST['capacity'] ?? 0,
                $_POST['water_source'] ?? '',
                $_POST['status'] ?? 'active',
                $_POST['notes'] ?? ''
            );
            $message = $result['message'];
            $message_type = $result['success'] ? 'success' : 'error';
            if ($result['success']) {
                $action = 'list';
            }
        } elseif (isset($_POST['delete_pond'])) {
            $pond_id = $_POST['pond_id'] ?? 0;
            $result = $pond_manager->deletePond($pond_id, $user_id);
            $message = $result['message'];
            $message_type = $result['success'] ? 'success' : 'error';
            $action = 'list';
        }
    }

    // Get pond data
    $ponds = $pond_manager->getPonds($user_id);
    $pond = null;

    if (in_array($action, ['edit', 'view']) && isset($_GET['id'])) {
        $pond = $pond_manager->getPond($_GET['id'], $user_id);
        if (!$pond) {
            $action = 'list';
        }
    }
    ?>

    <!-- Navigation -->
    <nav class="navbar">
        <div class="navbar-container">
            <h2 class="navbar-brand">🐠 Peternakan Lele</h2>
            <ul class="nav-menu">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="ponds.php" class="active">Kolam</a></li>
                <li><a href="fish.php">Inventori Ikan</a></li>
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
            <h1>🏊 Manajemen Kolam</h1>
            <p>Kelola informasi kolam budidaya lele Anda</p>
        </div>

        <?php if (!empty($message)) {
            echo '<div class="alert alert-' . $message_type . '">' . htmlspecialchars($message) . '</div>';
        } ?>

        <?php if ($action == 'list') { ?>
            <div class="section-actions">
                <a href="?action=add" class="btn btn-primary">+ Tambah Kolam Baru</a>
            </div>

            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Kolam</th>
                            <th>Lokasi</th>
                            <th>Ukuran (m²)</th>
                            <th>Kapasitas</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if (empty($ponds)) {
                            echo '<tr><td colspan="7" class="text-center">Belum ada data kolam</td></tr>';
                        } else {
                            $no = 1;
                            foreach ($ponds as $p) {
                                echo '<tr>';
                                echo '<td>' . $no++ . '</td>';
                                echo '<td>' . htmlspecialchars($p['pond_name']) . '</td>';
                                echo '<td>' . htmlspecialchars($p['location']) . '</td>';
                                echo '<td>' . $p['size_area'] . '</td>';
                                echo '<td>' . $p['capacity'] . '</td>';
                                echo '<td><span class="badge badge-' . ($p['status'] == 'active' ? 'success' : 'warning') . '">' . ucfirst($p['status']) . '</span></td>';
                                echo '<td class="action-buttons">';
                                echo '<a href="?action=edit&id=' . $p['id'] . '" class="btn-small btn-edit">Edit</a>';
                                echo '<a href="?action=view&id=' . $p['id'] . '" class="btn-small btn-view">Lihat</a>';
                                echo '<form method="POST" style="display:inline;" onsubmit="return confirm(\'Yakin hapus?\');">';
                                echo '<input type="hidden" name="pond_id" value="' . $p['id'] . '">';
                                echo '<button type="submit" name="delete_pond" class="btn-small btn-delete">Hapus</button>';
                                echo '</form>';
                                echo '</td>';
                                echo '</tr>';
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </div>

        <?php } elseif ($action == 'add') { ?>
            <div class="form-section">
                <h2>Tambah Kolam Baru</h2>
                <form method="POST" class="crud-form">
                    <div class="form-group">
                        <label for="pond_name">Nama Kolam:</label>
                        <input type="text" id="pond_name" name="pond_name" required>
                    </div>

                    <div class="form-group">
                        <label for="location">Lokasi:</label>
                        <input type="text" id="location" name="location" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="size_area">Ukuran (m²):</label>
                            <input type="number" id="size_area" name="size_area" step="0.1" required>
                        </div>

                        <div class="form-group">
                            <label for="capacity">Kapasitas (ekor):</label>
                            <input type="number" id="capacity" name="capacity" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="water_source">Sumber Air:</label>
                        <input type="text" id="water_source" name="water_source" placeholder="Sumur, sungai, dll">
                    </div>

                    <div class="form-group">
                        <label for="notes">Catatan:</label>
                        <textarea id="notes" name="notes" rows="4"></textarea>
                    </div>

                    <div class="form-actions">
                        <button type="submit" name="add_pond" class="btn btn-primary">Tambah Kolam</button>
                        <a href="ponds.php" class="btn btn-secondary">Batal</a>
                    </div>
                </form>
            </div>

        <?php } elseif ($action == 'edit' && $pond) { ?>
            <div class="form-section">
                <h2>Edit Kolam</h2>
                <form method="POST" class="crud-form">
                    <input type="hidden" name="pond_id" value="<?php echo $pond['id']; ?>">

                    <div class="form-group">
                        <label for="pond_name">Nama Kolam:</label>
                        <input type="text" id="pond_name" name="pond_name" value="<?php echo htmlspecialchars($pond['pond_name']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="location">Lokasi:</label>
                        <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($pond['location']); ?>" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="size_area">Ukuran (m²):</label>
                            <input type="number" id="size_area" name="size_area" step="0.1" value="<?php echo $pond['size_area']; ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="capacity">Kapasitas (ekor):</label>
                            <input type="number" id="capacity" name="capacity" value="<?php echo $pond['capacity']; ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="water_source">Sumber Air:</label>
                        <input type="text" id="water_source" name="water_source" value="<?php echo htmlspecialchars($pond['water_source'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="status">Status:</label>
                        <select id="status" name="status">
                            <option value="active" <?php echo $pond['status'] == 'active' ? 'selected' : ''; ?>>Aktif</option>
                            <option value="inactive" <?php echo $pond['status'] == 'inactive' ? 'selected' : ''; ?>>Tidak Aktif</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="notes">Catatan:</label>
                        <textarea id="notes" name="notes" rows="4"><?php echo htmlspecialchars($pond['notes'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-actions">
                        <button type="submit" name="update_pond" class="btn btn-primary">Simpan Perubahan</button>
                        <a href="ponds.php" class="btn btn-secondary">Batal</a>
                    </div>
                </form>
            </div>

        <?php } elseif ($action == 'view' && $pond) { ?>
            <div class="view-section">
                <div class="view-header">
                    <h2><?php echo htmlspecialchars($pond['pond_name']); ?></h2>
                    <a href="ponds.php" class="btn btn-secondary">Kembali</a>
                </div>

                <div class="view-grid">
                    <div class="view-item">
                        <label>Nama Kolam:</label>
                        <p><?php echo htmlspecialchars($pond['pond_name']); ?></p>
                    </div>
                    <div class="view-item">
                        <label>Lokasi:</label>
                        <p><?php echo htmlspecialchars($pond['location']); ?></p>
                    </div>
                    <div class="view-item">
                        <label>Ukuran:</label>
                        <p><?php echo $pond['size_area']; ?> m²</p>
                    </div>
                    <div class="view-item">
                        <label>Kapasitas:</label>
                        <p><?php echo $pond['capacity']; ?> ekor</p>
                    </div>
                    <div class="view-item">
                        <label>Sumber Air:</label>
                        <p><?php echo htmlspecialchars($pond['water_source'] ?? '-'); ?></p>
                    </div>
                    <div class="view-item">
                        <label>Status:</label>
                        <p><span class="badge badge-<?php echo $pond['status'] == 'active' ? 'success' : 'warning'; ?>"><?php echo ucfirst($pond['status']); ?></span></p>
                    </div>
                    <div class="view-item full-width">
                        <label>Catatan:</label>
                        <p><?php echo htmlspecialchars($pond['notes'] ?? '-'); ?></p>
                    </div>
                    <div class="view-item">
                        <label>Dibuat:</label>
                        <p><?php echo date('d-m-Y H:i', strtotime($pond['created_at'])); ?></p>
                    </div>
                </div>

                <div class="view-actions">
                    <a href="?action=edit&id=<?php echo $pond['id']; ?>" class="btn btn-primary">Edit</a>
                    <a href="ponds.php" class="btn btn-secondary">Kembali</a>
                </div>
            </div>
        <?php } ?>
    </div>

    <footer class="footer">
        <p>&copy; Copyright by 23552011319_ZulfaArifqi_23CNS-A_UASWEB1</p>
    </footer>
</body>
</html>
