<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan - Sistem Peternakan Lele</title>
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
    $pond_manager = new PondManager();
    $ponds = $pond_manager->getPonds($user_id);
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
                <li><a href="reports.php" class="active">Laporan</a></li>
                <li><a href="profile.php">Profil</a></li>
                <li><a href="logout.php" class="btn-logout">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="page-header">
            <h1>📊 Laporan & Analisis</h1>
            <p>Buat laporan dalam format PDF dan Excel</p>
        </div>

        <div class="reports-container">
            <!-- PDF Report Section -->
            <div class="report-section">
                <h2>📄 Laporan PDF</h2>
                <p>Buat laporan lengkap dalam format PDF yang dapat dicetak</p>

                <form method="POST" action="generate_pdf.php" class="report-form">
                    <div class="form-group">
                        <label for="pond_id_pdf">Pilih Kolam (opsional):</label>
                        <select id="pond_id_pdf" name="pond_id">
                            <option value="">Semua Kolam</option>
                            <?php foreach ($ponds as $p) {
                                echo '<option value="' . $p['id'] . '">' . htmlspecialchars($p['pond_name']) . '</option>';
                            } ?>
                        </select>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="start_date_pdf">Tanggal Mulai:</label>
                            <input type="date" id="start_date_pdf" name="start_date" value="<?php echo date('Y-m-01'); ?>">
                        </div>

                        <div class="form-group">
                            <label for="end_date_pdf">Tanggal Akhir:</label>
                            <input type="date" id="end_date_pdf" name="end_date" value="<?php echo date('Y-m-d'); ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="report_type_pdf">Tipe Laporan:</label>
                        <select id="report_type_pdf" name="report_type">
                            <option value="summary">Ringkasan Umum</option>
                            <option value="detailed">Detail Lengkap</option>
                            <option value="fish">Data Ikan & Inventori</option>
                            <option value="health">Kesehatan & Monitoring</option>
                            <option value="feed">Manajemen Pakan & Biaya</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">📥 Unduh PDF</button>
                </form>
            </div>

            <!-- Excel Report Section -->
            <div class="report-section">
                <h2>📊 Laporan Excel</h2>
                <p>Buat laporan dalam format Excel dengan multiple sheet</p>

                <form method="POST" action="generate_excel.php" class="report-form">
                    <div class="form-group">
                        <label for="pond_id_excel">Pilih Kolam (opsional):</label>
                        <select id="pond_id_excel" name="pond_id">
                            <option value="">Semua Kolam</option>
                            <?php foreach ($ponds as $p) {
                                echo '<option value="' . $p['id'] . '">' . htmlspecialchars($p['pond_name']) . '</option>';
                            } ?>
                        </select>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="start_date_excel">Tanggal Mulai:</label>
                            <input type="date" id="start_date_excel" name="start_date" value="<?php echo date('Y-m-01'); ?>">
                        </div>

                        <div class="form-group">
                            <label for="end_date_excel">Tanggal Akhir:</label>
                            <input type="date" id="end_date_excel" name="end_date" value="<?php echo date('Y-m-d'); ?>">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-success btn-block">📥 Unduh Excel</button>
                </form>
            </div>

            <!-- Quick Reports Section -->
            <div class="report-section">
                <h2>⚡ Laporan Cepat</h2>
                <div class="quick-reports">
                    <a href="generate_pdf.php?report_type=summary&quick=1" class="quick-report-btn">
                        <span>📋 Ringkasan Bulan Ini</span>
                        <p>Laporan singkat semua data bulan ini</p>
                    </a>

                    <a href="generate_excel.php?report_type=summary&quick=1" class="quick-report-btn">
                        <span>📊 Excel Bulan Ini</span>
                        <p>Data semua kolam dalam format Excel</p>
                    </a>

                    <a href="generate_pdf.php?report_type=detailed&quick=1" class="quick-report-btn">
                        <span>📄 Detail Lengkap</span>
                        <p>Laporan detail semua aktivitas</p>
                    </a>

                    <a href="generate_excel.php?report_type=health&quick=1" class="quick-report-btn">
                        <span>💧 Kesehatan</span>
                        <p>Data monitoring kesehatan dan air</p>
                    </a>
                </div>
            </div>

            <!-- Report Info Section -->
            <div class="report-section">
                <h2>ℹ️ Informasi Laporan</h2>
                <div class="info-box">
                    <h4>📋 Tipe Laporan yang Tersedia:</h4>
                    <ul>
                        <li><strong>Ringkasan Umum</strong> - Statistik dasar semua kolam</li>
                        <li><strong>Detail Lengkap</strong> - Informasi mendalam semua aspek</li>
                        <li><strong>Data Ikan</strong> - Fokus pada inventori dan populasi ikan</li>
                        <li><strong>Kesehatan</strong> - Monitoring air dan kesehatan ikan</li>
                        <li><strong>Pakan & Biaya</strong> - Riwayat pakan dan analisis biaya</li>
                    </ul>
                </div>

                <div class="info-box">
                    <h4>📊 Format Laporan:</h4>
                    <ul>
                        <li><strong>PDF</strong> - Dapat dicetak, format profesional</li>
                        <li><strong>Excel</strong> - Dapat diedit, multiple sheet, analisis lebih lanjut</li>
                    </ul>
                </div>

                <div class="info-box">
                    <h4>🔒 Keamanan:</h4>
                    <ul>
                        <li>Laporan hanya mencakup data pengguna yang login</li>
                        <li>File otomatis dihapus setelah 24 jam</li>
                        <li>Laporan dibuat dengan session verification</li>
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
