<?php
/**
 * PDF Report Generator
 * Generate actual PDF files using TCPDF
 */

session_start();
require_once '../config/database.php';
require_once '../includes/Auth.php';
require_once '../libraries/autoload.php';

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

// Create new PDF document
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Set document information
$pdf->SetCreator('Sistem Peternakan Lele');
$pdf->SetAuthor($user_info['full_name'] ?? 'User');
$pdf->SetTitle('Laporan Peternakan Lele');
$pdf->SetSubject('Laporan ' . ucfirst($report_type));

// Set margins
$pdf->SetMargins(15, 20, 15);
$pdf->SetHeaderMargin(10);
$pdf->SetFooterMargin(10);

// Set auto page breaks
$pdf->SetAutoPageBreak(TRUE, 15);

// Set font
$pdf->SetFont('helvetica', '', 10);

// Add a page
$pdf->AddPage();

// Title
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 10, 'LAPORAN PETERNAKAN LELE', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 12);
$pdf->Cell(0, 8, 'Sistem Informasi Manajemen', 0, 1, 'C');
$pdf->Ln(5);

// User info
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(30, 6, 'Nama User:', 0, 0);
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 6, $user_info['full_name'] ?? $user_info['username'], 0, 1);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(30, 6, 'Tanggal:', 0, 0);
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 6, date('d-m-Y H:i'), 0, 1);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(30, 6, 'Periode:', 0, 0);
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 6, date('d-m-Y', strtotime($start_date)) . ' s/d ' . date('d-m-Y', strtotime($end_date)), 0, 1);
$pdf->Ln(5);

// Generate report content based on type
switch ($report_type) {
    case 'ponds':
        generatePondsReportPDF($pdf, $db, $user_id, $pond_id);
        break;
    case 'fish':
        generateFishReportPDF($pdf, $db, $user_id, $pond_id, $start_date, $end_date);
        break;
    case 'feed':
        generateFeedReportPDF($pdf, $db, $user_id, $pond_id, $start_date, $end_date);
        break;
    case 'health':
        generateHealthReportPDF($pdf, $db, $user_id, $pond_id, $start_date, $end_date);
        break;
    default:
        generateSummaryReportPDF($pdf, $db, $user_id, $pond_id, $start_date, $end_date);
}

// Output PDF
$filename = 'Laporan_Peternakan_Lele_' . date('Y-m-d_His') . '.pdf';
$pdf->Output($filename, 'D');
exit();

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

// Generate report content based on type
switch ($report_type) {
    case 'ponds':
        generatePondsReportPDF($pdf, $db, $user_id, $pond_id);
        break;
    case 'fish':
        generateFishReportPDF($pdf, $db, $user_id, $pond_id, $start_date, $end_date);
        break;
    case 'feed':
        generateFeedReportPDF($pdf, $db, $user_id, $pond_id, $start_date, $end_date);
        break;
    case 'health':
        generateHealthReportPDF($pdf, $db, $user_id, $pond_id, $start_date, $end_date);
        break;
    default:
        generateSummaryReportPDF($pdf, $db, $user_id, $pond_id, $start_date, $end_date);
}

// Output PDF
$filename = 'Laporan_Peternakan_Lele_' . date('Y-m-d_His') . '.pdf';
$pdf->Output($filename, 'D');
exit();

// PDF Generation Functions

function generateSummaryReportPDF($pdf, $db, $user_id, $pond_id, $start_date, $end_date) {
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 8, 'RINGKASAN UMUM', 0, 1, 'L');
    $pdf->Ln(3);

    // Get pond statistics
    $pond_condition = $pond_id ? "AND p.id = ?" : "";
    $params = $pond_id ? [$user_id, $pond_id] : [$user_id];

    $stmt = $db->prepare("SELECT COUNT(*) as total_ponds FROM ponds p WHERE p.user_id = ? $pond_condition");
    $stmt->execute($params);
    $pond_stats = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $db->prepare("SELECT SUM(fi.quantity) as total_fish FROM fish_inventory fi JOIN ponds p ON fi.pond_id = p.id WHERE p.user_id = ? $pond_condition");
    $stmt->execute($params);
    $fish_stats = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $db->prepare("SELECT SUM(fm.cost) as total_feed_cost FROM feed_management fm JOIN ponds p ON fm.pond_id = p.id WHERE p.user_id = ? $pond_condition AND fm.feed_date BETWEEN ? AND ?");
    $stmt->execute(array_merge($params, [$start_date, $end_date]));
    $feed_stats = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $db->prepare("SELECT SUM(fish_count) as total_mortality FROM health_monitoring hm JOIN ponds p ON hm.pond_id = p.id WHERE p.user_id = ? $pond_condition AND hm.health_status = 'dead' AND hm.treatment_date BETWEEN ? AND ?");
    $stmt->execute(array_merge($params, [$start_date, $end_date]));
    $health_stats = $stmt->fetch(PDO::FETCH_ASSOC);

    // Display statistics
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(60, 6, 'Total Kolam:', 0, 0);
    $pdf->Cell(0, 6, $pond_stats['total_ponds'] . ' kolam', 0, 1);
    $pdf->Cell(60, 6, 'Total Ikan:', 0, 0);
    $pdf->Cell(0, 6, number_format($fish_stats['total_fish'] ?? 0) . ' ekor', 0, 1);
    $pdf->Cell(60, 6, 'Total Biaya Pakan:', 0, 0);
    $pdf->Cell(0, 6, 'Rp ' . number_format($feed_stats['total_feed_cost'] ?? 0), 0, 1);
    $pdf->Cell(60, 6, 'Total Kematian:', 0, 0);
    $pdf->Cell(0, 6, number_format($health_stats['total_mortality'] ?? 0) . ' ekor', 0, 1);
    $pdf->Ln(5);

    // Pond details table
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(0, 6, 'DETAIL KOLAM', 0, 1, 'L');
    $pdf->Ln(2);

    // Table header
    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->SetFillColor(240, 240, 240);
    $pdf->Cell(40, 6, 'Nama Kolam', 1, 0, 'C', true);
    $pdf->Cell(25, 6, 'Lokasi', 1, 0, 'C', true);
    $pdf->Cell(20, 6, 'Ukuran', 1, 0, 'C', true);
    $pdf->Cell(20, 6, 'Kapasitas', 1, 0, 'C', true);
    $pdf->Cell(20, 6, 'Jumlah Ikan', 1, 0, 'C', true);
    $pdf->Cell(20, 6, 'Status', 1, 1, 'C', true);

    // Table data
    $pdf->SetFont('helvetica', '', 8);
    $stmt = $db->prepare("SELECT p.*, COALESCE(SUM(fi.quantity), 0) as fish_count FROM ponds p LEFT JOIN fish_inventory fi ON p.id = fi.pond_id WHERE p.user_id = ? $pond_condition GROUP BY p.id");
    $stmt->execute($params);

    while ($pond = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $pdf->Cell(40, 6, $pond['pond_name'], 1, 0, 'L');
        $pdf->Cell(25, 6, $pond['location'] ?? '-', 1, 0, 'L');
        $pdf->Cell(20, 6, $pond['size_area'] ?? '-', 1, 0, 'C');
        $pdf->Cell(20, 6, $pond['capacity'] ?? '-', 1, 0, 'C');
        $pdf->Cell(20, 6, number_format($pond['fish_count']), 1, 0, 'C');
        $pdf->Cell(20, 6, ucfirst($pond['status'] ?? 'active'), 1, 1, 'C');
    }
}

function generatePondsReportPDF($pdf, $db, $user_id, $pond_id) {
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 8, 'LAPORAN KOLAM', 0, 1, 'L');
    $pdf->Ln(3);

    // Table header
    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->SetFillColor(240, 240, 240);
    $pdf->Cell(40, 6, 'Nama Kolam', 1, 0, 'C', true);
    $pdf->Cell(30, 6, 'Lokasi', 1, 0, 'C', true);
    $pdf->Cell(25, 6, 'Ukuran Area', 1, 0, 'C', true);
    $pdf->Cell(25, 6, 'Kapasitas', 1, 0, 'C', true);
    $pdf->Cell(25, 6, 'Sumber Air', 1, 0, 'C', true);
    $pdf->Cell(20, 6, 'Status', 1, 0, 'C', true);
    $pdf->Cell(0, 6, 'Catatan', 1, 1, 'C', true);

    // Table data
    $pdf->SetFont('helvetica', '', 8);
    $pond_condition = $pond_id ? "AND id = ?" : "";
    $params = $pond_id ? [$user_id, $pond_id] : [$user_id];

    $stmt = $db->prepare("SELECT * FROM ponds WHERE user_id = ? $pond_condition");
    $stmt->execute($params);

    while ($pond = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $pdf->Cell(40, 6, $pond['pond_name'], 1, 0, 'L');
        $pdf->Cell(30, 6, $pond['location'] ?? '-', 1, 0, 'L');
        $pdf->Cell(25, 6, $pond['size_area'] ?? '-', 1, 0, 'C');
        $pdf->Cell(25, 6, $pond['capacity'] ?? '-', 1, 0, 'C');
        $pdf->Cell(25, 6, $pond['water_source'] ?? '-', 1, 0, 'L');
        $pdf->Cell(20, 6, ucfirst($pond['status'] ?? 'active'), 1, 0, 'C');
        $pdf->Cell(0, 6, $pond['notes'] ?? '-', 1, 1, 'L');
    }
}

function generateFishReportPDF($pdf, $db, $user_id, $pond_id, $start_date, $end_date) {
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 8, 'LAPORAN INVENTORI IKAN', 0, 1, 'L');
    $pdf->Ln(3);

    // Table header
    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->SetFillColor(240, 240, 240);
    $pdf->Cell(30, 6, 'Kolam', 1, 0, 'C', true);
    $pdf->Cell(25, 6, 'Species', 1, 0, 'C', true);
    $pdf->Cell(20, 6, 'Jumlah', 1, 0, 'C', true);
    $pdf->Cell(20, 6, 'Ukuran', 1, 0, 'C', true);
    $pdf->Cell(20, 6, 'Umur', 1, 0, 'C', true);
    $pdf->Cell(25, 6, 'Kesehatan', 1, 0, 'C', true);
    $pdf->Cell(25, 6, 'Tanggal Entry', 1, 0, 'C', true);
    $pdf->Cell(0, 6, 'Catatan', 1, 1, 'C', true);

    // Table data
    $pdf->SetFont('helvetica', '', 8);
    $pond_condition = $pond_id ? "AND fi.pond_id = ?" : "";
    $params = $pond_id ? [$user_id, $pond_id] : [$user_id];

    $stmt = $db->prepare("SELECT fi.*, p.pond_name FROM fish_inventory fi JOIN ponds p ON fi.pond_id = p.id WHERE fi.user_id = ? $pond_condition AND fi.entry_date BETWEEN ? AND ? ORDER BY fi.entry_date DESC");
    $stmt->execute(array_merge($params, [$start_date, $end_date]));

    while ($fish = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $pdf->Cell(30, 6, $fish['pond_name'], 1, 0, 'L');
        $pdf->Cell(25, 6, $fish['species'] ?? 'Lele', 1, 0, 'L');
        $pdf->Cell(20, 6, number_format($fish['quantity']), 1, 0, 'C');
        $pdf->Cell(20, 6, $fish['size'] ?? '-', 1, 0, 'C');
        $pdf->Cell(20, 6, ($fish['age_days'] ?? 0) . ' hari', 1, 0, 'C');
        $pdf->Cell(25, 6, ucfirst($fish['health_status'] ?? 'healthy'), 1, 0, 'C');
        $pdf->Cell(25, 6, date('d-m-Y', strtotime($fish['entry_date'])), 1, 0, 'C');
        $pdf->Cell(0, 6, $fish['notes'] ?? '-', 1, 1, 'L');
    }
}

function generateFeedReportPDF($pdf, $db, $user_id, $pond_id, $start_date, $end_date) {
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 8, 'LAPORAN PEMAKANAN', 0, 1, 'L');
    $pdf->Ln(3);

    // Table header
    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->SetFillColor(240, 240, 240);
    $pdf->Cell(25, 6, 'Kolam', 1, 0, 'C', true);
    $pdf->Cell(30, 6, 'Jenis Pakan', 1, 0, 'C', true);
    $pdf->Cell(20, 6, 'Jumlah (kg)', 1, 0, 'C', true);
    $pdf->Cell(25, 6, 'Biaya', 1, 0, 'C', true);
    $pdf->Cell(25, 6, 'Tanggal', 1, 0, 'C', true);
    $pdf->Cell(20, 6, 'Waktu', 1, 0, 'C', true);
    $pdf->Cell(0, 6, 'Catatan', 1, 1, 'C', true);

    // Table data
    $pdf->SetFont('helvetica', '', 8);
    $pond_condition = $pond_id ? "AND fm.pond_id = ?" : "";
    $params = $pond_id ? [$user_id, $pond_id] : [$user_id];

    $stmt = $db->prepare("SELECT fm.*, p.pond_name FROM feed_management fm JOIN ponds p ON fm.pond_id = p.id WHERE fm.user_id = ? $pond_condition AND fm.feed_date BETWEEN ? AND ? ORDER BY fm.feed_date DESC, fm.time_fed DESC");
    $stmt->execute(array_merge($params, [$start_date, $end_date]));

    $total_cost = 0;
    while ($feed = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $pdf->Cell(25, 6, $feed['pond_name'], 1, 0, 'L');
        $pdf->Cell(30, 6, $feed['feed_type'], 1, 0, 'L');
        $pdf->Cell(20, 6, number_format($feed['quantity_kg'], 2), 1, 0, 'C');
        $pdf->Cell(25, 6, 'Rp ' . number_format($feed['cost'] ?? 0), 1, 0, 'R');
        $pdf->Cell(25, 6, date('d-m-Y', strtotime($feed['feed_date'])), 1, 0, 'C');
        $pdf->Cell(20, 6, $feed['time_fed'] ?? '-', 1, 0, 'C');
        $pdf->Cell(0, 6, $feed['notes'] ?? '-', 1, 1, 'L');
        $total_cost += $feed['cost'] ?? 0;
    }

    // Total row
    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->SetFillColor(240, 240, 240);
    $pdf->Cell(75, 6, 'TOTAL BIAYA PAKAN', 1, 0, 'R', true);
    $pdf->Cell(0, 6, 'Rp ' . number_format($total_cost), 1, 1, 'R', true);
}

function generateHealthReportPDF($pdf, $db, $user_id, $pond_id, $start_date, $end_date) {
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 8, 'LAPORAN MONITORING KESEHATAN', 0, 1, 'L');
    $pdf->Ln(3);

    // Table header
    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->SetFillColor(240, 240, 240);
    $pdf->Cell(25, 6, 'Kolam', 1, 0, 'C', true);
    $pdf->Cell(25, 6, 'Tanggal', 1, 0, 'C', true);
    $pdf->Cell(20, 6, 'Jumlah Ikan', 1, 0, 'C', true);
    $pdf->Cell(25, 6, 'Status Kesehatan', 1, 0, 'C', true);
    $pdf->Cell(30, 6, 'Gejala', 1, 0, 'C', true);
    $pdf->Cell(0, 6, 'Perlakuan', 1, 1, 'C', true);

    // Table data
    $pdf->SetFont('helvetica', '', 8);
    $pond_condition = $pond_id ? "AND hm.pond_id = ?" : "";
    $params = $pond_id ? [$user_id, $pond_id] : [$user_id];

    $stmt = $db->prepare("SELECT hm.*, p.pond_name FROM health_monitoring hm JOIN ponds p ON hm.pond_id = p.id WHERE hm.user_id = ? $pond_condition AND hm.treatment_date BETWEEN ? AND ? ORDER BY hm.treatment_date DESC");
    $stmt->execute(array_merge($params, [$start_date, $end_date]));

    while ($health = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $pdf->Cell(25, 6, $health['pond_name'], 1, 0, 'L');
        $pdf->Cell(25, 6, date('d-m-Y', strtotime($health['treatment_date'])), 1, 0, 'C');
        $pdf->Cell(20, 6, number_format($health['fish_count']), 1, 0, 'C');
        $pdf->Cell(25, 6, ucfirst($health['health_status']), 1, 0, 'C');
        $pdf->Cell(30, 6, $health['symptoms'] ?? '-', 1, 0, 'L');
        $pdf->Cell(0, 6, $health['treatment_given'] ?? '-', 1, 1, 'L');
    }
}
?>
