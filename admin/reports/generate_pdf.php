<?php
/**
 * PDF Report Generator
 * Generate reports in HTML format optimized for PDF printing
 */

session_start();
require_once '../../config/database.php';
require_once '../../includes/Auth.php';

// Verify session
$auth = new Auth();
$user = $auth->verifySession();

if (!$user) {
    die('Unauthorized Access');
}

$user_id = $user['user_id'];
$pond_id = $_POST['pond_id'] ?? $_GET['pond_id'] ?? null;
$start_date = $_POST['start_date'] ?? $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_POST['end_date'] ?? $_GET['end_date'] ?? date('Y-m-d');
$report_type = $_POST['report_type'] ?? $_GET['report_type'] ?? 'summary';
$quick = $_GET['quick'] ?? 0;

$db = getDB();

// Get user info
$stmt = $db->prepare("SELECT username, full_name FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user_info = $stmt->fetch(PDO::FETCH_ASSOC);

// Get pond info if specific pond selected
$pond_name = 'Semua Kolam';
if ($pond_id) {
    $stmt = $db->prepare("SELECT pond_name FROM ponds WHERE id = ? AND user_id = ?");
    $stmt->execute([$pond_id, $user_id]);
    $pond = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($pond) {
        $pond_name = $pond['pond_name'];
    }
}

// Generate HTML content for PDF
$html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Laporan Peternakan Lele - ' . htmlspecialchars($report_type) . '</title>
    <style>
        @page { margin: 1cm; }
        body {
            font-family: "Times New Roman", Times, serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .header {
            text-align: center;
            border-bottom: 3px solid #2c3e50;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .header h1 {
            color: #2c3e50;
            margin: 0 0 5px 0;
            font-size: 18px;
            text-transform: uppercase;
        }
        .header p {
            margin: 2px 0;
            font-size: 11px;
        }
        .section {
            margin-bottom: 20px;
            page-break-inside: avoid;
        }
        .section h2 {
            color: #3498db;
            border-bottom: 2px solid #bdc3c7;
            padding-bottom: 5px;
            margin-bottom: 10px;
            font-size: 14px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 11px;
        }
        th, td {
            border: 1px solid #bdc3c7;
            padding: 6px;
            text-align: left;
            vertical-align: top;
        }
        th {
            background-color: #ecf0f1;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 10px;
        }
        .summary {
            background-color: #f8f9fa;
            padding: 12px;
            border-radius: 3px;
            margin-bottom: 15px;
            border: 1px solid #dee2e6;
        }
        .summary h3 {
            margin: 0 0 8px 0;
            font-size: 13px;
            color: #2c3e50;
        }
        .summary p {
            margin: 3px 0;
            font-size: 11px;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            color: #7f8c8d;
            font-size: 10px;
            border-top: 1px solid #bdc3c7;
            padding-top: 10px;
        }
        .total-row {
            font-weight: bold;
            background-color: #ecf0f1;
        }
        .total-row td {
            border-top: 2px solid #2c3e50;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🐠 SISTEM INFORMASI PETERNAKAN LELE</h1>
        <p><strong>LAPORAN ' . strtoupper(htmlspecialchars($report_type)) . '</strong></p>
        <p><strong>Periode:</strong> ' . date('d/m/Y', strtotime($start_date)) . ' - ' . date('d/m/Y', strtotime($end_date)) . '</p>
        <p><strong>Kolam:</strong> ' . htmlspecialchars($pond_name) . '</p>
        <p><strong>Pelapor:</strong> ' . htmlspecialchars($user_info['full_name'] ?? $user_info['username']) . '</p>
        <p><strong>Tanggal Dibuat:</strong> ' . date('d/m/Y H:i') . '</p>
    </div>';
$report_content = "═══════════════════════════════════════════════════════════\n";
$report_content .= "LAPORAN SISTEM INFORMASI PETERNAKAN LELE\n";
$report_content .= "═══════════════════════════════════════════════════════════\n\n";

$report_content .= "Tanggal Laporan: " . date('d-m-Y H:i:s') . "\n";
$report_content .= "Periode: " . date('d-m-Y', strtotime($start_date)) . " s/d " . date('d-m-Y', strtotime($end_date)) . "\n";
$report_content .= "Pengguna: " . htmlspecialchars($user['full_name']) . "\n\n";

// Summary Statistics
$total_ponds = count($ponds);
$total_fish = $db->prepare("SELECT SUM(quantity) as total FROM fish_inventory WHERE user_id = ? AND pond_id IN (SELECT id FROM ponds WHERE user_id = ?)")->execute([$user_id, $user_id])->fetch()['total'] ?? 0;
$total_feed_cost = $db->prepare("SELECT SUM(cost) as total FROM feed_management WHERE user_id = ? AND feed_date BETWEEN ? AND ?")->execute([$user_id, $start_date, $end_date])->fetch()['total'] ?? 0;
$total_mortality = $db->prepare("SELECT SUM(mortality_count) as total FROM health_monitoring WHERE user_id = ? AND monitoring_date BETWEEN ? AND ?")->execute([$user_id, $start_date, $end_date])->fetch()['total'] ?? 0;

$report_content .= "RINGKASAN STATISTIK\n";
$report_content .= "─────────────────────────────────────────────────────────\n";
$report_content .= "Total Kolam: " . $total_ponds . "\n";
$report_content .= "Total Ikan: " . number_format($total_fish) . " ekor\n";
$report_content .= "Total Biaya Pakan (Periode): Rp " . number_format($total_feed_cost) . "\n";
$report_content .= "Total Kematian Ikan (Periode): " . $total_mortality . " ekor\n\n";

// Pond Details
$report_content .= "DETAIL KOLAM\n";
$report_content .= "─────────────────────────────────────────────────────────\n";

foreach ($ponds as $pond) {
    if (!$pond) continue;
    
    $report_content .= "\nKolam: " . htmlspecialchars($pond['pond_name']) . "\n";
    $report_content .= "  Lokasi: " . htmlspecialchars($pond['location']) . "\n";
    $report_content .= "  Ukuran: " . $pond['size_area'] . " m²\n";
    $report_content .= "  Kapasitas: " . $pond['capacity'] . " ekor\n";
    $report_content .= "  Sumber Air: " . htmlspecialchars($pond['water_source'] ?? '-') . "\n";
    $report_content .= "  Status: " . ucfirst($pond['pond_status']) . "\n";

    // Fish data
    $stmt = $db->prepare("SELECT SUM(quantity) as total FROM fish_inventory WHERE pond_id = ? AND user_id = ?");
    $stmt->execute([$pond['id'], $user_id]);
    $pond_fish = $stmt->fetch()['total'] ?? 0;
    $report_content .= "  Total Ikan: " . number_format($pond_fish) . " ekor\n";

    // Feed data
    $stmt = $db->prepare("SELECT SUM(quantity_kg) as total_kg, SUM(cost) as total_cost FROM feed_management WHERE pond_id = ? AND user_id = ? AND feed_date BETWEEN ? AND ?");
    $stmt->execute([$pond['id'], $user_id, $start_date, $end_date]);
    $feed_data = $stmt->fetch();
    $report_content .= "  Pakan Diberikan: " . ($feed_data['total_kg'] ?? 0) . " kg\n";
    $report_content .= "  Biaya Pakan: Rp " . number_format($feed_data['total_cost'] ?? 0) . "\n";

    // Health data
    $stmt = $db->prepare("SELECT AVG(ph_level) as avg_ph, AVG(temperature) as avg_temp, SUM(mortality_count) as total_mortality FROM health_monitoring WHERE pond_id = ? AND user_id = ? AND monitoring_date BETWEEN ? AND ?");
    $stmt->execute([$pond['id'], $user_id, $start_date, $end_date]);
    $health_data = $stmt->fetch();
    $report_content .= "  Rata-rata pH: " . round($health_data['avg_ph'] ?? 0, 2) . "\n";
    $report_content .= "  Rata-rata Suhu: " . round($health_data['avg_temp'] ?? 0, 2) . "°C\n";
    $report_content .= "  Total Kematian: " . ($health_data['total_mortality'] ?? 0) . " ekor\n";
}

// Generate report content based on type
switch ($report_type) {
    case 'summary':
        $html .= generateSummaryReport($db, $user_id, $pond_id, $start_date, $end_date);
        break;
    case 'detailed':
        $html .= generateDetailedReport($db, $user_id, $pond_id, $start_date, $end_date);
        break;
    case 'fish':
        $html .= generateFishReport($db, $user_id, $pond_id, $start_date, $end_date);
        break;
    case 'health':
        $html .= generateHealthReport($db, $user_id, $pond_id, $start_date, $end_date);
        break;
    case 'feed':
        $html .= generateFeedReport($db, $user_id, $pond_id, $start_date, $end_date);
        break;
    default:
        $html .= generateSummaryReport($db, $user_id, $pond_id, $start_date, $end_date);
}

$html .= '
    <div class="footer">
        <p>Dibuat oleh Sistem Informasi Peternakan Lele</p>
        <p>&copy; 2026 - Laporan ini bersifat rahasia dan hanya untuk pengguna resmi</p>
    </div>
</body>
</html>';

// Output HTML for PDF generation
$filename = 'Laporan_Peternakan_Lele_' . date('Y-m-d_His') . '.html';
header('Content-Type: text/html; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
echo $html;

// Helper functions for different report types
function generateSummaryReport($db, $user_id, $pond_id, $start_date, $end_date) {
    $html = '<div class="section"><h2>📊 RINGKASAN UMUM</h2>';

    // Get pond statistics
    $pond_condition = $pond_id ? "AND p.id = ?" : "";
    $params = $pond_id ? [$user_id, $pond_id] : [$user_id];

    $stmt = $db->prepare("SELECT COUNT(*) as total_ponds FROM ponds p WHERE p.user_id = ? $pond_condition");
    $stmt->execute($params);
    $pond_stats = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get fish statistics
    $stmt = $db->prepare("SELECT SUM(fi.quantity) as total FROM fish_inventory fi
                         JOIN ponds p ON fi.pond_id = p.id
                         WHERE p.user_id = ? $pond_condition");
    $stmt->execute($params);
    $fish_stats = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get feed statistics
    $stmt = $db->prepare("SELECT SUM(fm.quantity) as total FROM feed_management fm
                         JOIN ponds p ON fm.pond_id = p.id
                         WHERE p.user_id = ? $pond_condition
                         AND fm.feed_date BETWEEN ? AND ?");
    $stmt->execute(array_merge($params, [$start_date, $end_date]));
    $feed_stats = $stmt->fetch(PDO::FETCH_ASSOC);

    $html .= '<div class="summary">
        <h3>Statistik Utama</h3>
        <p><strong>Total Kolam:</strong> ' . ($pond_stats['total_ponds'] ?? 0) . '</p>
        <p><strong>Total Ikan:</strong> ' . number_format($fish_stats['total'] ?? 0) . ' ekor</p>
        <p><strong>Total Pakan:</strong> ' . number_format($feed_stats['total'] ?? 0, 2) . ' kg</p>
    </div>';

    $html .= '<h3>Data Kolam</h3><table>
        <tr><th>Nama Kolam</th><th>Ukuran</th><th>Jumlah Ikan</th><th>Status</th></tr>';

    $stmt = $db->prepare("SELECT p.pond_name, p.size, COALESCE(SUM(fi.quantity), 0) as fish_count, p.status
                         FROM ponds p
                         LEFT JOIN fish_inventory fi ON p.id = fi.pond_id
                         WHERE p.user_id = ? $pond_condition
                         GROUP BY p.id, p.pond_name, p.size, p.status");
    $stmt->execute($params);

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $html .= '<tr>
            <td>' . htmlspecialchars($row['pond_name']) . '</td>
            <td>' . htmlspecialchars($row['size']) . '</td>
            <td>' . number_format($row['fish_count']) . '</td>
            <td>' . htmlspecialchars($row['status']) . '</td>
        </tr>';
    }

    $html .= '</table></div>';
    return $html;
}

function generateDetailedReport($db, $user_id, $pond_id, $start_date, $end_date) {
    $html = '<div class="section"><h2>📋 LAPORAN DETAIL</h2>';

    // Combine all data
    $html .= generateSummaryReport($db, $user_id, $pond_id, $start_date, $end_date);
    $html .= generateFishReport($db, $user_id, $pond_id, $start_date, $end_date);
    $html .= generateHealthReport($db, $user_id, $pond_id, $start_date, $end_date);
    $html .= generateFeedReport($db, $user_id, $pond_id, $start_date, $end_date);

    $html .= '</div>';
    return $html;
}

function generateFishReport($db, $user_id, $pond_id, $start_date, $end_date) {
    $html = '<div class="section"><h2>🐟 DATA IKAN & INVENTORI</h2>';

    $pond_condition = $pond_id ? "AND p.id = ?" : "";
    $params = $pond_id ? [$user_id, $pond_id] : [$user_id];

    $html .= '<table>
        <tr><th>Kolam</th><th>Jenis Ikan</th><th>Jumlah</th><th>Ukuran Rata-rata</th><th>Tanggal Masuk</th></tr>';

    $stmt = $db->prepare("SELECT p.pond_name, fi.fish_type, fi.quantity, fi.average_size, fi.entry_date
                         FROM fish_inventory fi
                         JOIN ponds p ON fi.pond_id = p.id
                         WHERE p.user_id = ? $pond_condition
                         ORDER BY fi.entry_date DESC");
    $stmt->execute($params);

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $html .= '<tr>
            <td>' . htmlspecialchars($row['pond_name']) . '</td>
            <td>' . htmlspecialchars($row['fish_type']) . '</td>
            <td>' . number_format($row['quantity']) . '</td>
            <td>' . htmlspecialchars($row['average_size']) . '</td>
            <td>' . date('d/m/Y', strtotime($row['entry_date'])) . '</td>
        </tr>';
    }

    $html .= '</table></div>';
    return $html;
}

function generateHealthReport($db, $user_id, $pond_id, $start_date, $end_date) {
    $html = '<div class="section"><h2>💚 MONITORING KESEHATAN</h2>';

    $pond_condition = $pond_id ? "AND p.id = ?" : "";
    $params = $pond_id ? [$user_id, $pond_id] : [$user_id];

    $html .= '<table>
        <tr><th>Kolam</th><th>Tanggal</th><th>pH Air</th><th>Suhu</th><th>Kondisi</th><th>Catatan</th></tr>';

    $stmt = $db->prepare("SELECT p.pond_name, hm.monitoring_date, hm.ph_level, hm.temperature, hm.condition_status, hm.notes
                         FROM health_monitoring hm
                         JOIN ponds p ON hm.pond_id = p.id
                         WHERE p.user_id = ? $pond_condition
                         AND hm.monitoring_date BETWEEN ? AND ?
                         ORDER BY hm.monitoring_date DESC");
    $stmt->execute(array_merge($params, [$start_date, $end_date]));

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $html .= '<tr>
            <td>' . htmlspecialchars($row['pond_name']) . '</td>
            <td>' . date('d/m/Y', strtotime($row['monitoring_date'])) . '</td>
            <td>' . htmlspecialchars($row['ph_level']) . '</td>
            <td>' . htmlspecialchars($row['temperature']) . '°C</td>
            <td>' . htmlspecialchars($row['condition_status']) . '</td>
            <td>' . htmlspecialchars($row['notes'] ?? '-') . '</td>
        </tr>';
    }

    $html .= '</table></div>';
    return $html;
}

function generateFeedReport($db, $user_id, $pond_id, $start_date, $end_date) {
    $html = '<div class="section"><h2>🍽️ MANAJEMEN PAKAN & BIAYA</h2>';

    $pond_condition = $pond_id ? "AND p.id = ?" : "";
    $params = $pond_id ? [$user_id, $pond_id] : [$user_id];

    $html .= '<table>
        <tr><th>Kolam</th><th>Tanggal</th><th>Jenis Pakan</th><th>Jumlah</th><th>Biaya</th><th>Catatan</th></tr>';

    $stmt = $db->prepare("SELECT p.pond_name, fm.feed_date, fm.feed_type, fm.quantity, fm.cost, fm.notes
                         FROM feed_management fm
                         JOIN ponds p ON fm.pond_id = p.id
                         WHERE p.user_id = ? $pond_condition
                         AND fm.feed_date BETWEEN ? AND ?
                         ORDER BY fm.feed_date DESC");
    $stmt->execute(array_merge($params, [$start_date, $end_date]));

    $total_cost = 0;
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $total_cost += $row['cost'];
        $html .= '<tr>
            <td>' . htmlspecialchars($row['pond_name']) . '</td>
            <td>' . date('d/m/Y', strtotime($row['feed_date'])) . '</td>
            <td>' . htmlspecialchars($row['feed_type']) . '</td>
            <td>' . number_format($row['quantity'], 2) . ' kg</td>
            <td>Rp ' . number_format($row['cost'], 0) . '</td>
            <td>' . htmlspecialchars($row['notes'] ?? '-') . '</td>
        </tr>';
    }

    $html .= '<tr class="total-row">
        <td colspan="4"><strong>TOTAL BIAYA PAKAN</strong></td>
        <td><strong>Rp ' . number_format($total_cost, 0) . '</strong></td>
        <td></td>
    </tr>';

    $html .= '</table></div>';
    return $html;
}
