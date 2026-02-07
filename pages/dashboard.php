<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistem Peternakan Lele</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php
    session_start();

    // Include database config FIRST and test connection
    require_once '../config/database.php';

    // Test database connection immediately
    try {
        $db = getDB();
        // Test basic query
        $test_stmt = $db->query("SELECT 1");
    } catch (Exception $e) {
        die("Database Error: " . $e->getMessage() . ". Please run setup-mysql.php first.");
    }

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
    $username = $user['username'];
    $full_name = $user['full_name'];

    // $db already connected above, no need to reconnect

    // Check if user is admin
    $is_admin = false;
    try {
        $stmt = $db->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user_role = $stmt->fetch()['role'] ?? 'user';
        $is_admin = ($user_role === 'admin');
    } catch (PDOException $e) {
        // Default to user if error
    }

    // Get statistics (remove duplicate $db = getDB();)

    // Total Ponds
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM ponds WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $total_ponds = $stmt->fetch()['total'];

    // Total Fish
    $stmt = $db->prepare("SELECT SUM(fi.quantity) as total FROM fish_inventory fi 
                         JOIN ponds p ON fi.pond_id = p.id 
                         WHERE p.user_id = ?");
    $stmt->execute([$user_id]);
    $total_fish = $stmt->fetch()['total'] ?? 0;

    // Average Water pH
    $stmt = $db->prepare("SELECT AVG(hm.ph_level) as avg_ph FROM health_monitoring hm
                         JOIN ponds p ON hm.pond_id = p.id
                         WHERE p.user_id = ? AND hm.ph_level IS NOT NULL");
    $stmt->execute([$user_id]);
    $avg_ph = round($stmt->fetch()['avg_ph'] ?? 0, 2);

    // Total Feed Cost
    $stmt = $db->prepare("SELECT SUM(fm.cost) as total_cost FROM feed_management fm
                         JOIN ponds p ON fm.pond_id = p.id
                         WHERE p.user_id = ?");
    $stmt->execute([$user_id]);
    $total_cost = $stmt->fetch()['total_cost'] ?? 0;

    // Mortality Count
    $stmt = $db->prepare("SELECT SUM(hm.mortality_count) as total_mortality FROM health_monitoring hm
                         JOIN ponds p ON hm.pond_id = p.id
                         WHERE p.user_id = ?");
    $stmt->execute([$user_id]);
    $total_mortality = $stmt->fetch()['total_mortality'] ?? 0;

    // Total Feed Given
    $stmt = $db->prepare("SELECT SUM(fm.quantity_kg) as total_feed FROM feed_management fm
                         JOIN ponds p ON fm.pond_id = p.id
                         WHERE p.user_id = ?");
    $stmt->execute([$user_id]);
    $total_feed = round($stmt->fetch()['total_feed'] ?? 0, 2);

    // Chart Data - Fish per Pond
    $fish_per_pond_labels = [];
    $fish_per_pond_data = [];
    $stmt = $db->prepare("SELECT p.pond_name, SUM(f.quantity) as total_fish FROM ponds p LEFT JOIN fish_inventory f ON p.id = f.pond_id WHERE p.user_id = ? GROUP BY p.id ORDER BY p.pond_name");
    $stmt->execute([$user_id]);
    while ($row = $stmt->fetch()) {
        $fish_per_pond_labels[] = $row['pond_name'];
        $fish_per_pond_data[] = (int)$row['total_fish'];
    }

    // Chart Data - Health Status
    $health_status_labels = ['Sehat', 'Sakit', 'Observasi'];
    $health_status_data = [0, 0, 0];
    $stmt = $db->prepare("SELECT hm.condition_status, SUM(fi.quantity) as total 
                         FROM fish_inventory fi 
                         JOIN health_monitoring hm ON fi.pond_id = hm.pond_id
                         JOIN ponds p ON fi.pond_id = p.id
                         WHERE p.user_id = ? 
                         GROUP BY hm.condition_status");
    $stmt->execute([$user_id]);
    while ($row = $stmt->fetch()) {
        if ($row['condition_status'] === 'healthy') {
            $health_status_data[0] = (int)$row['total'];
        } elseif ($row['condition_status'] === 'sick') {
            $health_status_data[1] = (int)$row['total'];
        } elseif ($row['condition_status'] === 'observation') {
            $health_status_data[2] = (int)$row['total'];
        }
    }

    // Chart Data - Monthly Feed Cost (Last 6 months)
    $feed_cost_labels = [];
    $feed_cost_data = [];
    $stmt = $db->prepare("SELECT DATE_FORMAT(fm.feed_date, '%M %Y') as month, SUM(fm.cost) as total_cost 
                         FROM feed_management fm
                         JOIN ponds p ON fm.pond_id = p.id
                         WHERE p.user_id = ? AND fm.feed_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH) 
                         GROUP BY YEAR(fm.feed_date), MONTH(fm.feed_date) 
                         ORDER BY fm.feed_date");
    $stmt->execute([$user_id]);
    while ($row = $stmt->fetch()) {
        $feed_cost_labels[] = $row['month'];
        $feed_cost_data[] = (float)$row['total_cost'];
    }

    // Chart Data - pH Levels Over Time (Last 10 records)
    $ph_labels = [];
    $ph_data = [];
    $stmt = $db->prepare("SELECT DATE_FORMAT(hm.monitoring_date, '%d/%m') as date, hm.ph_level 
                         FROM health_monitoring hm
                         JOIN ponds p ON hm.pond_id = p.id
                         WHERE p.user_id = ? AND hm.ph_level IS NOT NULL 
                         ORDER BY hm.monitoring_date DESC LIMIT 10");
    $stmt->execute([$user_id]);
    $ph_results = $stmt->fetchAll();
    $ph_results = array_reverse($ph_results); // Reverse to show chronological order
    foreach ($ph_results as $row) {
        $ph_labels[] = $row['date'];
        $ph_data[] = (float)$row['ph_level'];
    }
    ?>

    <!-- Navigation -->
    <nav class="navbar">
        <div class="navbar-container">
            <h2 class="navbar-brand">🐠 Peternakan Lele</h2>
            <ul class="nav-menu">
                <li><a href="dashboard.php" class="active">Dashboard</a></li>
                <li><a href="ponds.php">Kolam</a></li>
                <li><a href="fish.php">Inventori Ikan</a></li>
                <li><a href="feed.php">Pakan</a></li>
                <li><a href="health.php">Kesehatan</a></li>
                <li><a href="reports.php">Laporan</a></li>
                <?php if ($is_admin): ?>
                    <li><a href="admin.php" class="admin-link">Admin</a></li>
                <?php endif; ?>
                <li><a href="profile.php">Profil</a></li>
                <li><a href="logout.php" class="btn-logout">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="dashboard-header">
            <h1>Selamat Datang, <?php echo htmlspecialchars($full_name); ?>! 👋</h1>
            <p>Kelola sistem peternakan lele Anda dengan mudah dan efisien</p>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">🏊</div>
                <h3>Total Kolam</h3>
                <p class="stat-value"><?php echo $total_ponds; ?></p>
                <a href="ponds.php" class="stat-link">Lihat Kolam →</a>
            </div>

            <div class="stat-card">
                <div class="stat-icon">🐟</div>
                <h3>Total Ikan</h3>
                <p class="stat-value"><?php echo number_format($total_fish); ?></p>
                <a href="fish.php" class="stat-link">Kelola Ikan →</a>
            </div>

            <div class="stat-card">
                <div class="stat-icon">💧</div>
                <h3>Rata-rata pH Air</h3>
                <p class="stat-value"><?php echo $avg_ph; ?></p>
                <a href="health.php" class="stat-link">Monitor Kesehatan →</a>
            </div>

            <div class="stat-card">
                <div class="stat-icon">🎯</div>
                <h3>Total Biaya Pakan</h3>
                <p class="stat-value">Rp <?php echo number_format($total_cost); ?></p>
                <a href="feed.php" class="stat-link">Lihat Pakan →</a>
            </div>

            <div class="stat-card">
                <div class="stat-icon">⚠️</div>
                <h3>Total Kematian</h3>
                <p class="stat-value"><?php echo $total_mortality; ?></p>
                <a href="health.php" class="stat-link">Cek Laporan →</a>
            </div>

            <div class="stat-card">
                <div class="stat-icon">🥗</div>
                <h3>Total Pakan Diberikan</h3>
                <p class="stat-value"><?php echo $total_feed; ?> kg</p>
                <a href="feed.php" class="stat-link">Riwayat Pakan →</a>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="charts-section">
            <h2><i class="fas fa-chart-bar"></i> Analisis & Grafik</h2>
            <div class="charts-grid">
                <!-- Fish per Pond Chart -->
                <div class="chart-card">
                    <h3><i class="fas fa-fish"></i> Distribusi Ikan per Kolam</h3>
                    <canvas id="fishPerPondChart" width="400" height="200"></canvas>
                </div>

                <!-- Health Status Chart -->
                <div class="chart-card">
                    <h3><i class="fas fa-heartbeat"></i> Status Kesehatan Ikan</h3>
                    <canvas id="healthStatusChart" width="400" height="200"></canvas>
                </div>

                <!-- Feed Cost Chart -->
                <div class="chart-card">
                    <h3><i class="fas fa-dollar-sign"></i> Biaya Pakan Bulanan</h3>
                    <canvas id="feedCostChart" width="400" height="200"></canvas>
                </div>

                <!-- pH Level Chart -->
                <div class="chart-card">
                    <h3><i class="fas fa-water"></i> Tren pH Air</h3>
                    <canvas id="phLevelChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>

        <!-- Features Section -->
        <div class="features-section">
            <h2>📋 Fitur Utama</h2>
            <div class="features-grid">
                <div class="feature-item">
                    <h4>🏊 Manajemen Kolam</h4>
                    <p>Kelola informasi kolam lele Anda termasuk lokasi, ukuran, dan kapasitas</p>
                </div>
                <div class="feature-item">
                    <h4>🐟 Inventori Ikan</h4>
                    <p>Catat dan pantau jumlah, spesies, dan kesehatan ikan di setiap kolam</p>
                </div>
                <div class="feature-item">
                    <h4>🥗 Manajemen Pakan</h4>
                    <p>Catatan pakan yang diberikan, biaya, dan supplier untuk efisiensi budidaya</p>
                </div>
                <div class="feature-item">
                    <h4>💧 Monitoring Kesehatan</h4>
                    <p>Pantau kondisi air (pH, suhu, DO) dan kesehatan ikan secara berkala</p>
                </div>
                <div class="feature-item">
                    <h4>📊 Laporan & Analisis</h4>
                    <p>Buat laporan dalam format PDF dan Excel untuk analisis produktivitas</p>
                </div>
                <div class="feature-item">
                    <h4>👤 Profil & Keamanan</h4>
                    <p>Kelola profil pengguna dengan sistem keamanan berbasis cookie session</p>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <h2>⚡ Aksi Cepat</h2>
            <div class="action-buttons">
                <a href="ponds.php?action=add" class="btn btn-primary">+ Tambah Kolam</a>
                <a href="fish.php?action=add" class="btn btn-secondary">+ Tambah Ikan</a>
                <a href="feed.php?action=add" class="btn btn-success">+ Catat Pakan</a>
                <a href="health.php?action=add" class="btn btn-warning">+ Monitor Kesehatan</a>
                <a href="reports.php" class="btn btn-dark">📊 Buat Laporan</a>
                <?php if ($is_admin): ?>
                    <a href="admin.php" class="btn btn-danger">👑 Admin Panel</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; Copyright by 23552011319_ZulfaArifqi_23CNS-A_UASWEB1</p>
    </footer>

    <script>
        // Chart.js Configuration
        const chartConfig = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        usePointStyle: true
                    }
                }
            }
        };

        // Fish per Pond Chart
        const fishPerPondCtx = document.getElementById('fishPerPondChart').getContext('2d');
        new Chart(fishPerPondCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($fish_per_pond_labels); ?>,
                datasets: [{
                    label: 'Jumlah Ikan',
                    data: <?php echo json_encode($fish_per_pond_data); ?>,
                    backgroundColor: 'rgba(54, 162, 235, 0.6)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                ...chartConfig,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });

        // Health Status Chart
        const healthStatusCtx = document.getElementById('healthStatusChart').getContext('2d');
        new Chart(healthStatusCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($health_status_labels); ?>,
                datasets: [{
                    data: <?php echo json_encode($health_status_data); ?>,
                    backgroundColor: [
                        'rgba(75, 192, 192, 0.6)',  // Healthy - Green
                        'rgba(255, 99, 132, 0.6)',  // Sick - Red
                        'rgba(255, 205, 86, 0.6)'   // Observation - Yellow
                    ],
                    borderColor: [
                        'rgba(75, 192, 192, 1)',
                        'rgba(255, 99, 132, 1)',
                        'rgba(255, 205, 86, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: chartConfig
        });

        // Feed Cost Chart
        const feedCostCtx = document.getElementById('feedCostChart').getContext('2d');
        new Chart(feedCostCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($feed_cost_labels); ?>,
                datasets: [{
                    label: 'Biaya Pakan (Rp)',
                    data: <?php echo json_encode($feed_cost_data); ?>,
                    backgroundColor: 'rgba(153, 102, 255, 0.2)',
                    borderColor: 'rgba(153, 102, 255, 1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                ...chartConfig,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'Rp ' + value.toLocaleString('id-ID');
                            }
                        }
                    }
                }
            }
        });

        // pH Level Chart
        const phLevelCtx = document.getElementById('phLevelChart').getContext('2d');
        new Chart(phLevelCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($ph_labels); ?>,
                datasets: [{
                    label: 'pH Air',
                    data: <?php echo json_encode($ph_data); ?>,
                    backgroundColor: 'rgba(255, 159, 64, 0.2)',
                    borderColor: 'rgba(255, 159, 64, 1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                ...chartConfig,
                scales: {
                    y: {
                        beginAtZero: false,
                        suggestedMin: 6,
                        suggestedMax: 9,
                        ticks: {
                            stepSize: 0.5
                        }
                    }
                },
                plugins: {
                    ...chartConfig.plugins,
                    annotation: {
                        annotations: {
                            optimalPH: {
                                type: 'box',
                                yMin: 6.5,
                                yMax: 8.5,
                                backgroundColor: 'rgba(75, 192, 192, 0.1)',
                                borderColor: 'rgba(75, 192, 192, 0.5)',
                                borderWidth: 1,
                                label: {
                                    content: 'pH Optimal (6.5-8.5)',
                                    enabled: true,
                                    position: 'center'
                                }
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
