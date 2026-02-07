<?php
/**
 * Excel Report Generator
 * Generate actual Excel files with tables using PhpSpreadsheet
 */

session_start();
require_once '../config/database.php';
require_once '../includes/Auth.php';
require_once '../includes/Database.php';
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

$db = getDB();

// Create new Spreadsheet object
$spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Set document properties
$spreadsheet->getProperties()
    ->setCreator('Sistem Peternakan Lele')
    ->setLastModifiedBy('Sistem Peternakan Lele')
    ->setTitle('Laporan Peternakan Lele')
    ->setSubject('Laporan ' . ucfirst($report_type))
    ->setDescription('Laporan dibuat pada ' . date('d-m-Y H:i:s'));

// Generate report content based on type
switch ($report_type) {
    case 'ponds':
        generatePondsReportExcel($sheet, $db, $user_id, $pond_id);
        break;
    case 'fish':
        generateFishReportExcel($sheet, $db, $user_id, $pond_id, $start_date, $end_date);
        break;
    case 'feed':
        generateFeedReportExcel($sheet, $db, $user_id, $pond_id, $start_date, $end_date);
        break;
    case 'health':
        generateHealthReportExcel($sheet, $db, $user_id, $pond_id, $start_date, $end_date);
        break;
    default:
        generateSummaryReportExcel($sheet, $db, $user_id, $pond_id, $start_date, $end_date);
}

// Auto-size columns
foreach (range('A', $sheet->getHighestColumn()) as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Output Excel file
$filename = 'Laporan_Peternakan_Lele_' . date('Y-m-d_His') . '.xlsx';

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
$writer->save('php://output');
exit();

/**
 * Generate Summary Report Excel
 */
function generateSummaryReportExcel($sheet, $db, $user_id, $pond_id, $start_date, $end_date) {
    $sheet->setTitle('Ringkasan');

    // Header
    $sheet->setCellValue('A1', 'LAPORAN RINGKASAN PETERNAKAN LELE');
    $sheet->mergeCells('A1:E1');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

    $sheet->setCellValue('A2', 'Periode: ' . date('d/m/Y', strtotime($start_date)) . ' - ' . date('d/m/Y', strtotime($end_date)));
    $sheet->mergeCells('A2:E2');
    $sheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

    // Get summary data
    $ponds = getPondsData($db, $user_id, $pond_id);
    $fish = getFishData($db, $user_id, $pond_id, $start_date, $end_date);
    $feed = getFeedData($db, $user_id, $pond_id, $start_date, $end_date);
    $health = getHealthData($db, $user_id, $pond_id, $start_date, $end_date);

    $row = 4;

    // Ponds Summary
    $sheet->setCellValue('A'.$row, 'RINGKASAN KOLAM');
    $sheet->mergeCells('A'.$row.':E'.$row);
    $sheet->getStyle('A'.$row)->getFont()->setBold(true);
    $row++;

    $sheet->setCellValue('A'.$row, 'Total Kolam');
    $sheet->setCellValue('B'.$row, count($ponds));
    $sheet->setCellValue('C'.$row, 'Kolam Aktif');
    $sheet->setCellValue('D'.$row, count(array_filter($ponds, fn($p) => $p['status'] === 'active')));
    $row += 2;

    // Fish Summary
    $sheet->setCellValue('A'.$row, 'RINGKASAN IKAN');
    $sheet->mergeCells('A'.$row.':E'.$row);
    $sheet->getStyle('A'.$row)->getFont()->setBold(true);
    $row++;

    $totalFish = array_sum(array_column($fish, 'fish_count'));
    $sheet->setCellValue('A'.$row, 'Total Ikan');
    $sheet->setCellValue('B'.$row, $totalFish);
    $row += 2;

    // Feed Summary
    $sheet->setCellValue('A'.$row, 'RINGKASAN PAKAN');
    $sheet->mergeCells('A'.$row.':E'.$row);
    $sheet->getStyle('A'.$row)->getFont()->setBold(true);
    $row++;

    $totalFeed = array_sum(array_column($feed, 'quantity_kg'));
    $sheet->setCellValue('A'.$row, 'Total Pakan (kg)');
    $sheet->setCellValue('B'.$row, $totalFeed);
    $row += 2;

    // Health Summary
    $sheet->setCellValue('A'.$row, 'RINGKASAN KESEHATAN');
    $sheet->mergeCells('A'.$row.':E'.$row);
    $sheet->getStyle('A'.$row)->getFont()->setBold(true);
    $row++;

    $totalTreatments = count($health);
    $sheet->setCellValue('A'.$row, 'Total Perawatan');
    $sheet->setCellValue('B'.$row, $totalTreatments);
}

/**
 * Generate Ponds Report Excel
 */
function generatePondsReportExcel($sheet, $db, $user_id, $pond_id) {
    $sheet->setTitle('Kolam');

    // Header
    $sheet->setCellValue('A1', 'LAPORAN KOLAM');
    $sheet->mergeCells('A1:F1');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

    // Table headers
    $headers = ['No', 'Nama Kolam', 'Lokasi', 'Ukuran Area', 'Status', 'Tanggal Dibuat'];
    $col = 'A';
    foreach ($headers as $header) {
        $sheet->setCellValue($col.'3', $header);
        $sheet->getStyle($col.'3')->getFont()->setBold(true);
        $sheet->getStyle($col.'3')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('CCCCCC');
        $col++;
    }

    // Get ponds data
    $ponds = getPondsData($db, $user_id, $pond_id);

    $row = 4;
    $no = 1;
    foreach ($ponds as $pond) {
        $sheet->setCellValue('A'.$row, $no++);
        $sheet->setCellValue('B'.$row, $pond['pond_name']);
        $sheet->setCellValue('C'.$row, $pond['location']);
        $sheet->setCellValue('D'.$row, $pond['size_area']);
        $sheet->setCellValue('E'.$row, ucfirst($pond['status']));
        $sheet->setCellValue('F'.$row, date('d/m/Y', strtotime($pond['created_at'])));
        $row++;
    }
}

/**
 * Generate Fish Report Excel
 */
function generateFishReportExcel($sheet, $db, $user_id, $pond_id, $start_date, $end_date) {
    $sheet->setTitle('Ikan');

    // Header
    $sheet->setCellValue('A1', 'LAPORAN IKAN');
    $sheet->mergeCells('A1:G1');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

    $sheet->setCellValue('A2', 'Periode: ' . date('d/m/Y', strtotime($start_date)) . ' - ' . date('d/m/Y', strtotime($end_date)));
    $sheet->mergeCells('A2:G2');
    $sheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

    // Table headers
    $headers = ['No', 'Kolam', 'Jumlah Ikan', 'Ukuran (cm)', 'Umur (hari)', 'Status Kesehatan', 'Tanggal Input', 'Catatan'];
    $col = 'A';
    foreach ($headers as $header) {
        $sheet->setCellValue($col.'4', $header);
        $sheet->getStyle($col.'4')->getFont()->setBold(true);
        $sheet->getStyle($col.'4')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('CCCCCC');
        $col++;
    }

    // Get fish data
    $fish = getFishData($db, $user_id, $pond_id, $start_date, $end_date);

    $row = 5;
    $no = 1;
    foreach ($fish as $record) {
        $sheet->setCellValue('A'.$row, $no++);
        $sheet->setCellValue('B'.$row, $record['pond_name']);
        $sheet->setCellValue('C'.$row, $record['quantity']);
        $sheet->setCellValue('D'.$row, $record['size']);
        $sheet->setCellValue('E'.$row, $record['age_days']);
        $sheet->setCellValue('F'.$row, ucfirst($record['health_status']));
        $sheet->setCellValue('G'.$row, date('d/m/Y', strtotime($record['entry_date'])));
        $sheet->setCellValue('H'.$row, $record['notes'] ?? '');
        $row++;
    }
}

/**
 * Generate Feed Report Excel
 */
function generateFeedReportExcel($sheet, $db, $user_id, $pond_id, $start_date, $end_date) {
    $sheet->setTitle('Pakan');

    // Header
    $sheet->setCellValue('A1', 'LAPORAN PAKAN');
    $sheet->mergeCells('A1:H1');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

    $sheet->setCellValue('A2', 'Periode: ' . date('d/m/Y', strtotime($start_date)) . ' - ' . date('d/m/Y', strtotime($end_date)));
    $sheet->mergeCells('A2:H2');
    $sheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

    // Table headers
    $headers = ['No', 'Kolam', 'Jenis Pakan', 'Jumlah (kg)', 'Tanggal Pemberian', 'Waktu Pemberian', 'Supplier', 'Catatan'];
    $col = 'A';
    foreach ($headers as $header) {
        $sheet->setCellValue($col.'4', $header);
        $sheet->getStyle($col.'4')->getFont()->setBold(true);
        $sheet->getStyle($col.'4')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('CCCCCC');
        $col++;
    }

    // Get feed data
    $feed = getFeedData($db, $user_id, $pond_id, $start_date, $end_date);

    $row = 5;
    $no = 1;
    foreach ($feed as $record) {
        $sheet->setCellValue('A'.$row, $no++);
        $sheet->setCellValue('B'.$row, $record['pond_name']);
        $sheet->setCellValue('C'.$row, $record['feed_type']);
        $sheet->setCellValue('D'.$row, $record['quantity_kg']);
        $sheet->setCellValue('E'.$row, date('d/m/Y', strtotime($record['feed_date'])));
        $sheet->setCellValue('F'.$row, $record['time_fed']);
        $sheet->setCellValue('G'.$row, $record['supplier'] ?? '');
        $sheet->setCellValue('H'.$row, $record['notes'] ?? '');
        $row++;
    }
}

/**
 * Generate Health Report Excel
 */
function generateHealthReportExcel($sheet, $db, $user_id, $pond_id, $start_date, $end_date) {
    $sheet->setTitle('Kesehatan');

    // Header
    $sheet->setCellValue('A1', 'LAPORAN KESEHATAN');
    $sheet->mergeCells('A1:H1');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

    $sheet->setCellValue('A2', 'Periode: ' . date('d/m/Y', strtotime($start_date)) . ' - ' . date('d/m/Y', strtotime($end_date)));
    $sheet->mergeCells('A2:H2');
    $sheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

    // Table headers
    $headers = ['No', 'Kolam', 'Jumlah Ikan', 'Status Kesehatan', 'Gejala', 'Pengobatan', 'Tanggal Perawatan', 'Catatan'];
    $col = 'A';
    foreach ($headers as $header) {
        $sheet->setCellValue($col.'4', $header);
        $sheet->getStyle($col.'4')->getFont()->setBold(true);
        $sheet->getStyle($col.'4')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('CCCCCC');
        $col++;
    }

    // Get health data
    $health = getHealthData($db, $user_id, $pond_id, $start_date, $end_date);

    $row = 5;
    $no = 1;
    foreach ($health as $record) {
        $sheet->setCellValue('A'.$row, $no++);
        $sheet->setCellValue('B'.$row, $record['pond_name']);
        $sheet->setCellValue('C'.$row, $record['fish_count']);
        $sheet->setCellValue('D'.$row, ucfirst($record['health_status']));
        $sheet->setCellValue('E'.$row, $record['symptoms']);
        $sheet->setCellValue('F'.$row, $record['treatment_given']);
        $sheet->setCellValue('G'.$row, date('d/m/Y', strtotime($record['treatment_date'])));
        $sheet->setCellValue('H'.$row, $record['notes'] ?? '');
        $row++;
    }
}

/**
 * Helper Functions to Get Data
 */
function getPondsData($db, $user_id, $pond_id = null) {
    $sql = "SELECT p.*, u.username FROM ponds p
            JOIN users u ON p.user_id = u.id
            WHERE p.user_id = ?";
    $params = [$user_id];

    if ($pond_id) {
        $sql .= " AND p.id = ?";
        $params[] = $pond_id;
    }

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getFishData($db, $user_id, $pond_id, $start_date, $end_date) {
    $sql = "SELECT fi.*, p.pond_name FROM fish_inventory fi
            JOIN ponds p ON fi.pond_id = p.id
            WHERE p.user_id = ? AND fi.entry_date BETWEEN ? AND ?";
    $params = [$user_id, $start_date, $end_date];

    if ($pond_id) {
        $sql .= " AND fi.pond_id = ?";
        $params[] = $pond_id;
    }

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getFeedData($db, $user_id, $pond_id, $start_date, $end_date) {
    $sql = "SELECT fm.*, p.pond_name FROM feed_management fm
            JOIN ponds p ON fm.pond_id = p.id
            WHERE p.user_id = ? AND fm.feed_date BETWEEN ? AND ?";
    $params = [$user_id, $start_date, $end_date];

    if ($pond_id) {
        $sql .= " AND fm.pond_id = ?";
        $params[] = $pond_id;
    }

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getHealthData($db, $user_id, $pond_id, $start_date, $end_date) {
    $sql = "SELECT hm.*, p.pond_name FROM health_monitoring hm
            JOIN ponds p ON hm.pond_id = p.id
            WHERE p.user_id = ? AND hm.treatment_date BETWEEN ? AND ?";
    $params = [$user_id, $start_date, $end_date];

    if ($pond_id) {
        $sql .= " AND hm.pond_id = ?";
        $params[] = $pond_id;
    }

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
