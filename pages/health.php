<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitoring Kesehatan - Sistem Peternakan Lele</title>
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

    $health_manager = new HealthManager();
    $pond_manager = new PondManager();

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        if (isset($_POST['add_health'])) {
            $result = $health_manager->create(
                $user_id,
                $_POST['pond_id'] ?? 0,
                $_POST['fish_count'] ?? 0,
                $_POST['health_status'] ?? 'healthy',
                $_POST['symptoms'] ?? null,
                $_POST['treatment_given'] ?? null,
                $_POST['treatment_date'] ?? date('Y-m-d'),
                $_POST['notes'] ?? ''
            );
            $message = $result['message'];
            $message_type = $result['success'] ? 'success' : 'error';
            if ($result['success']) $action = 'list';
        } elseif (isset($_POST['update_health'])) {
            $health_id = $_POST['health_id'] ?? 0;
            $result = $health_manager->update(
                $health_id,
                $user_id,
                $_POST['fish_count'] ?? 0,
                $_POST['health_status'] ?? 'healthy',
                $_POST['symptoms'] ?? null,
                $_POST['treatment_given'] ?? null,
                $_POST['treatment_date'] ?? date('Y-m-d'),
                $_POST['notes'] ?? ''
            );
            $message = $result['message'];
            $message_type = $result['success'] ? 'success' : 'error';
            if ($result['success']) $action = 'list';
        } elseif (isset($_POST['delete_health'])) {
            $health_id = $_POST['health_id'] ?? 0;
            $result = $health_manager->delete($health_id, $user_id);
            $message = $result['message'];
            $message_type = $result['success'] ? 'success' : 'error';
            $action = 'list';
        }
    }

    $healths = $health_manager->getAll($user_id);
    $ponds = $pond_manager->getPonds($user_id);
    $health = null;

    if (in_array($action, ['edit']) && isset($_GET['id'])) {
        $health = $health_manager->getOne($_GET['id'], $user_id);
        if (!$health) $action = 'list';
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
                <li><a href="health.php" class="active">Kesehatan</a></li>
                <li><a href="reports.php">Laporan</a></li>
                <li><a href="profile.php">Profil</a></li>
                <li><a href="logout.php" class="btn-logout">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="page-header">
            <h1>💧 Monitoring Kesehatan</h1>
            <p>Pantau kualitas air dan kesehatan ikan secara berkala</p>
        </div>

        <?php if (!empty($message)) echo '<div class="alert alert-' . $message_type . '">' . htmlspecialchars($message) . '</div>'; ?>

        <?php if ($action == 'list') { ?>
            <div class="section-actions">
                <a href="?action=add" class="btn btn-primary">+ Catat Monitoring</a>
            </div>

            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Kolam</th>
                            <th>Tanggal</th>
                            <th>Jumlah Ikan</th>
                            <th>Status Kesehatan</th>
                            <th>Gejala</th>
                            <th>Perlakuan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if (empty($healths)) {
                            echo '<tr><td colspan="8" class="text-center">Belum ada data monitoring</td></tr>';
                        } else {
                            $no = 1;
                            foreach ($healths as $h) {
                                echo '<tr>';
                                echo '<td>' . $no++ . '</td>';
                                echo '<td>' . htmlspecialchars($h['pond_name']) . '</td>';
                                echo '<td>' . date('d-m-Y', strtotime($h['treatment_date'])) . '</td>';
                                echo '<td>' . ($h['fish_count'] ?? 0) . '</td>';
                                echo '<td>' . htmlspecialchars($h['health_status'] ?? '-') . '</td>';
                                echo '<td>' . htmlspecialchars($h['symptoms'] ?? '-') . '</td>';
                                echo '<td>' . htmlspecialchars($h['treatment_given'] ?? '-') . '</td>';
                                echo '<td class="action-buttons">';
                                echo '<a href="?action=edit&id=' . $h['id'] . '" class="btn-small btn-edit">Edit</a>';
                                echo '<form method="POST" style="display:inline;" onsubmit="return confirm(\'Yakin hapus?\');">';
                                echo '<input type="hidden" name="health_id" value="' . $h['id'] . '">';
                                echo '<button type="submit" name="delete_health" class="btn-small btn-delete">Hapus</button>';
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
                <h2>Catat Monitoring Kesehatan</h2>
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
                        <label for="treatment_date">Tanggal Perlakuan:</label>
                        <input type="date" id="treatment_date" name="treatment_date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>

                    <h3>Data Kesehatan Ikan</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="fish_count">Jumlah Ikan:</label>
                            <input type="number" id="fish_count" name="fish_count" min="1" placeholder="Jumlah ikan dalam kolam" required>
                        </div>

                        <div class="form-group">
                            <label for="health_status">Status Kesehatan:</label>
                            <select id="health_status" name="health_status" required>
                                <option value="healthy">Sehat</option>
                                <option value="sick">Sakit</option>
                                <option value="dead">Mati</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="symptoms">Gejala (jika ada):</label>
                        <textarea id="symptoms" name="symptoms" rows="2" placeholder="Jelaskan gejala yang terlihat pada ikan"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="treatment_given">Perlakuan yang Diberikan:</label>
                        <textarea id="treatment_given" name="treatment_given" rows="3" placeholder="Jelaskan perlakuan atau pengobatan yang dilakukan"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="observation">Observasi Umum:</label>
                        <textarea id="observation" name="observation" rows="4" placeholder="Catatan observasi ikan dan kolam secara keseluruhan"></textarea>
                    </div>

                    <div class="form-actions">
                        <button type="submit" name="add_health" class="btn btn-primary">Catat Monitoring</button>
                        <a href="health.php" class="btn btn-secondary">Batal</a>
                    </div>
                </form>
            </div>

        <?php } elseif ($action == 'edit' && $health) { ?>
            <div class="form-section">
                <h2>Edit Data Monitoring</h2>
                <form method="POST" class="crud-form">
                    <input type="hidden" name="health_id" value="<?php echo $health['id']; ?>">

                    <div class="form-group">
                        <label for="treatment_date">Tanggal Perlakuan:</label>
                        <input type="date" id="treatment_date" name="treatment_date" value="<?php echo $health['treatment_date']; ?>" required>
                    </div>

                    <h3>Data Kesehatan Ikan</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="fish_count">Jumlah Ikan:</label>
                            <input type="number" id="fish_count" name="fish_count" value="<?php echo $health['fish_count']; ?>" min="1" required>
                        </div>

                        <div class="form-group">
                            <label for="health_status">Status Kesehatan:</label>
                            <select id="health_status" name="health_status" required>
                                <option value="healthy" <?php echo $health['health_status'] == 'healthy' ? 'selected' : ''; ?>>Sehat</option>
                                <option value="sick" <?php echo $health['health_status'] == 'sick' ? 'selected' : ''; ?>>Sakit</option>
                                <option value="dead" <?php echo $health['health_status'] == 'dead' ? 'selected' : ''; ?>>Mati</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="symptoms">Gejala:</label>
                        <textarea id="symptoms" name="symptoms" rows="2"><?php echo htmlspecialchars($health['symptoms'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="treatment_given">Perlakuan yang Diberikan:</label>
                        <textarea id="treatment_given" name="treatment_given" rows="3"><?php echo htmlspecialchars($health['treatment_given'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="notes">Catatan:</label>
                        <textarea id="notes" name="notes" rows="3"><?php echo htmlspecialchars($health['notes'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-actions">
                        <button type="submit" name="update_health" class="btn btn-primary">Simpan</button>
                        <a href="health.php" class="btn btn-secondary">Batal</a>
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
