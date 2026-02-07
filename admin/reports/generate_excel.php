<?php
/**
 * Excel Report Generator
 * Generate reports in CSV format (Excel-compatible)
 */

session_start();
require_once '../../config/database.php';
require_once '../../includes/Auth.php';
require_once '../../includes/Database.php';

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

$db = getDB();

// Prepare filename
$filename = 'Laporan_Peternakan_Lele_' . date('Y-m-d_His') . '.csv';
$filepath = dirname(__FILE__) . '/../../temp/' . $filename;

// Create temp directory if not exists
if (!is_dir(dirname(__FILE__) . '/../../temp/')) {
    mkdir(dirname(__FILE__) . '/../../temp/', 0755, true);
}

// Open file for writing
$file = fopen($filepath, 'w');

// Set UTF-8 BOM for Excel
fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

// Get data
$pond_manager = new PondManager();
$fish_manager = new FishInventoryManager();
$feed_manager = new FeedManager();
$health_manager = new HealthManager();

if ($pond_id) {
    $ponds = [$pond_manager->getPond($pond_id, $user_id)];
} else {
    $ponds = $pond_manager->getPonds($user_id);
}

// Write header
fputcsv($file, ['LAPORAN SISTEM INFORMASI PETERNAKAN LELE'], ';');
fputcsv($file, ['Tanggal Laporan: ' . date('d-m-Y H:i:s')], ';');
fputcsv($file, ['Periode: ' . date('d-m-Y', strtotime($start_date)) . ' s/d ' . date('d-m-Y', strtotime($end_date))], ';');
fputcsv($file, ['Pengguna: ' . $user['full_name']], ';');
fputcsv($file, [], ';');

// Statistics
fputcsv($file, ['RINGKASAN STATISTIK'], ';');
fputcsv($file, ['Item', 'Nilai'], ';');

$total_ponds = count($ponds);
$total_fish = $db->prepare("SELECT SUM(quantity) as total FROM fish_inventory WHERE user_id = ?")->execute([$user_id])->fetch()['total'] ?? 0;
$total_feed_cost = $db->prepare("SELECT SUM(cost) as total FROM feed_management WHERE user_id = ? AND feed_date BETWEEN ? AND ?")->execute([$user_id, $start_date, $end_date])->fetch()['total'] ?? 0;
$total_mortality = $db->prepare("SELECT SUM(mortality_count) as total FROM health_monitoring WHERE user_id = ? AND monitoring_date BETWEEN ? AND ?")->execute([$user_id, $start_date, $end_date])->fetch()['total'] ?? 0;

fputcsv($file, ['Total Kolam', $total_ponds], ';');
fputcsv($file, ['Total Ikan', $total_fish], ';');
fputcsv($file, ['Total Biaya Pakan (Rp)', $total_feed_cost], ';');
fputcsv($file, ['Total Kematian Ikan', $total_mortality], ';');
fputcsv($file, [], ';');

// Pond Details
fputcsv($file, ['DETAIL KOLAM'], ';');
fputcsv($file, ['Nama Kolam', 'Lokasi', 'Ukuran (m²)', 'Kapasitas', 'Sumber Air', 'Status', 'Total Ikan', 'Pakan (kg)', 'Biaya Pakan (Rp)', 'Rata-rata pH', 'Rata-rata Suhu (°C)', 'Total Kematian'], ';');

foreach ($ponds as $pond) {
    if (!$pond) continue;
    
    // Fish data
    $stmt = $db->prepare("SELECT SUM(quantity) as total FROM fish_inventory WHERE pond_id = ? AND user_id = ?");
    $stmt->execute([$pond['id'], $user_id]);
    $pond_fish = $stmt->fetch()['total'] ?? 0;

    // Feed data
    $stmt = $db->prepare("SELECT SUM(quantity_kg) as total_kg, SUM(cost) as total_cost FROM feed_management WHERE pond_id = ? AND user_id = ? AND feed_date BETWEEN ? AND ?");
    $stmt->execute([$pond['id'], $user_id, $start_date, $end_date]);
    $feed_data = $stmt->fetch();

    // Health data
    $stmt = $db->prepare("SELECT AVG(ph_level) as avg_ph, AVG(temperature) as avg_temp, SUM(mortality_count) as total_mortality FROM health_monitoring WHERE pond_id = ? AND user_id = ? AND monitoring_date BETWEEN ? AND ?");
    $stmt->execute([$pond['id'], $user_id, $start_date, $end_date]);
    $health_data = $stmt->fetch();

    fputcsv($file, [
        $pond['pond_name'],
        $pond['location'],
        $pond['size_area'],
        $pond['capacity'],
        $pond['water_source'] ?? '-',
        ucfirst($pond['pond_status']),
        $pond_fish,
        $feed_data['total_kg'] ?? 0,
        $feed_data['total_cost'] ?? 0,
        round($health_data['avg_ph'] ?? 0, 2),
        round($health_data['avg_temp'] ?? 0, 2),
        $health_data['total_mortality'] ?? 0
    ], ';');
}

fputcsv($file, [], ';');
fputcsv($file, ['DATA IKAN'], ';');
fputcsv($file, ['Kolam', 'Spesies', 'Jumlah', 'Ukuran (cm)', 'Umur (hari)', 'Status Kesehatan'], ';');

$fishes = $fish_manager->getAll($user_id);
foreach ($fishes as $fish) {
    fputcsv($file, [
        $fish['pond_name'],
        $fish['species'],
        $fish['quantity'],
        $fish['size'] ?? '-',
        $fish['age_days'] ?? '-',
        $fish['health_status']
    ], ';');
}

fputcsv($file, [], ';');
fputcsv($file, ['DATA PAKAN'], ';');
fputcsv($file, ['Kolam', 'Tipe Pakan', 'Jumlah (kg)', 'Tanggal', 'Waktu', 'Biaya (Rp)', 'Supplier'], ';');

$feeds = $feed_manager->getAll($user_id);
foreach ($feeds as $feed) {
    if (strtotime($feed['feed_date']) >= strtotime($start_date) && strtotime($feed['feed_date']) <= strtotime($end_date)) {
        fputcsv($file, [
            $feed['pond_name'],
            $feed['feed_type'],
            $feed['quantity_kg'],
            date('d-m-Y', strtotime($feed['feed_date'])),
            $feed['time_fed'] ?? '-',
            $feed['cost'] ?? 0,
            $feed['supplier'] ?? '-'
        ], ';');
    }
}

fputcsv($file, [], ';');
fputcsv($file, ['DATA MONITORING KESEHATAN'], ';');
fputcsv($file, ['Kolam', 'Tanggal', 'pH', 'Suhu (°C)', 'DO (mg/L)', 'Penyakit', 'Status', 'Kematian'], ';');

$healths = $health_manager->getAll($user_id);
foreach ($healths as $health) {
    if (strtotime($health['monitoring_date']) >= strtotime($start_date) && strtotime($health['monitoring_date']) <= strtotime($end_date)) {
        fputcsv($file, [
            $health['pond_name'],
            date('d-m-Y', strtotime($health['monitoring_date'])),
            $health['ph_level'] ?? '-',
            $health['temperature'] ?? '-',
            $health['dissolved_oxygen'] ?? '-',
            $health['disease_name'] ?? '-',
            $health['disease_status'] ?? '-',
            $health['mortality_count'] ?? 0
        ], ';');
    }
}

fputcsv($file, [], ';');
fputcsv($file, ['Laporan ini dihasilkan pada ' . date('d-m-Y H:i:s')], ';');

fclose($file);

// Download
header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($filepath));
readfile($filepath);

// Delete file after download
unlink($filepath);
exit();
