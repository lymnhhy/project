<?php
// baocao/export.php
session_start();
require_once '../../config/db.php';
require_once '../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

// Kiểm tra đăng nhập
if(!isset($_SESSION['user']) || !isset($_SESSION['id'])) {
    header('Location: ../../auth/dangnhap.php');
    exit();
}

$user_id = $_SESSION['id'];
$congtrinh_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if($congtrinh_id <= 0) {
    die('ID công trình không hợp lệ');
}

// Lấy thông tin công trình
$sql = "SELECT ct.*, lct.ten_loai, ttct.ten_trang_thai 
        FROM congtrinh ct
        LEFT JOIN loaicongtrinh lct ON ct.loaiCT_id = lct.id
        LEFT JOIN trangthaicongtrinh ttct ON ct.trangthaiCT_id = ttct.id
        WHERE ct.id = $congtrinh_id AND ct.user_id = '$user_id'";
$result = mysqli_query($conn, $sql);

if(mysqli_num_rows($result) == 0) {
    die('Không tìm thấy công trình hoặc bạn không có quyền xem');
}

$ct = mysqli_fetch_assoc($result);

// Tính tiến độ công trình
$sql_tien_do = "SELECT AVG(phan_tram_tien_do) as avg_progress FROM hangmucthicong WHERE congtrinh_id = $congtrinh_id";
$result_tien_do = mysqli_query($conn, $sql_tien_do);
$tien_do_ct = round(mysqli_fetch_assoc($result_tien_do)['avg_progress'] ?? 0);

// Thống kê hạng mục
$sql_thongke = "SELECT 
                COUNT(*) as tong_hm,
                SUM(CASE WHEN trang_thai = 'Hoàn thành' THEN 1 ELSE 0 END) as hm_hoanthanh,
                SUM(CASE WHEN trang_thai = 'Đang thi công' THEN 1 ELSE 0 END) as hm_dangtc,
                SUM(CASE WHEN trang_thai = 'Chưa thi công' THEN 1 ELSE 0 END) as hm_chuatc,
                SUM(CASE WHEN ngay_ket_thuc < CURDATE() AND trang_thai != 'Hoàn thành' THEN 1 ELSE 0 END) as hm_quahan,
                SUM(kinh_phi) as tong_kinhphi_hm,
                AVG(phan_tram_tien_do) as tb_tiendo_hm
                FROM hangmucthicong 
                WHERE congtrinh_id = $congtrinh_id";
$result_thongke = mysqli_query($conn, $sql_thongke);
$thongke = mysqli_fetch_assoc($result_thongke);

// Danh sách hạng mục
$sql_hm = "SELECT hm.*,
           DATEDIFF(hm.ngay_ket_thuc, CURDATE()) as so_ngay_con
           FROM hangmucthicong hm
           WHERE hm.congtrinh_id = $congtrinh_id
           ORDER BY 
               CASE 
                   WHEN hm.trang_thai != 'Hoàn thành' AND hm.ngay_ket_thuc < CURDATE() THEN 1
                   WHEN hm.trang_thai = 'Đang thi công' AND hm.ngay_ket_thuc BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 2
                   WHEN hm.trang_thai = 'Đang thi công' THEN 3
                   WHEN hm.trang_thai = 'Chưa thi công' THEN 4
                   ELSE 5
               END,
               hm.ngay_ket_thuc ASC";
$hangmuc_list = mysqli_query($conn, $sql_hm);

// Tạo spreadsheet mới
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Style header chính
$mainHeaderStyle = [
    'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => '2C3E50']
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER
    ]
];

// Style header bảng
$tableHeaderStyle = [
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => '3498DB']
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER
    ],
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN
        ]
    ]
];

// Style tiêu đề phụ
$subHeaderStyle = [
    'font' => ['bold' => true, 'size' => 12],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => 'F2F2F2']
    ]
];

// Đặt tên sheet
$sheet->setTitle('Báo cáo công trình');

// === HEADER CHÍNH ===
$sheet->setCellValue('A1', 'BÁO CÁO CHI TIẾT CÔNG TRÌNH');
$sheet->mergeCells('A1:H1');
$sheet->getStyle('A1')->applyFromArray($mainHeaderStyle);

$sheet->setCellValue('A2', 'Ngày xuất: ' . date('d/m/Y H:i:s'));
$sheet->mergeCells('A2:H2');
$sheet->getStyle('A2')->getFont()->setItalic(true);

// === THÔNG TIN CÔNG TRÌNH ===
$sheet->setCellValue('A4', 'THÔNG TIN CÔNG TRÌNH');
$sheet->mergeCells('A4:H4');
$sheet->getStyle('A4')->applyFromArray($subHeaderStyle);

// Tạo bảng thông tin
$infoData = [
    ['Tên công trình:', $ct['ten_cong_trinh'], 'Mã công trình:', $ct['ma_cong_trinh'] ?? 'CT-' . str_pad($ct['id'], 4, '0', STR_PAD_LEFT)],
    ['Địa điểm:', $ct['dia_diem'], 'Loại công trình:', $ct['ten_loai']],
    ['Ngày bắt đầu:', date('d/m/Y', strtotime($ct['ngay_bat_dau'])), 'Ngày kết thúc:', date('d/m/Y', strtotime($ct['ngay_ket_thuc']))],
    ['Kinh phí dự kiến:', number_format($ct['kinh_phi'] ?? 0, 0, ',', '.'), 'Trạng thái:', $ct['ten_trang_thai']],
    ['Tiến độ công trình:', $tien_do_ct . '%', '', '']
];

$row = 5;
foreach($infoData as $rowData) {
    $col = 'A';
    foreach($rowData as $cellValue) {
        $sheet->setCellValue($col . $row, $cellValue);
        if($col == 'A' || $col == 'C') {
            $sheet->getStyle($col . $row)->getFont()->setBold(true);
        }
        $col++;
    }
    $row++;
}

// === THỐNG KÊ HẠNG MỤC ===
$row += 2;
$sheet->setCellValue('A' . $row, 'THỐNG KÊ HẠNG MỤC');
$sheet->mergeCells('A' . $row . ':H' . $row);
$sheet->getStyle('A' . $row)->applyFromArray($subHeaderStyle);

$row++;
$statsData = [
    ['Tổng số hạng mục:', $thongke['tong_hm'] ?? 0, 'Hạng mục hoàn thành:', $thongke['hm_hoanthanh'] ?? 0],
    ['Đang thi công:', $thongke['hm_dangtc'] ?? 0, 'Chưa thi công:', $thongke['hm_chuatc'] ?? 0],
    ['Hạng mục quá hạn:', $thongke['hm_quahan'] ?? 0, 'Tiến độ trung bình:', round($thongke['tb_tiendo_hm'] ?? 0) . '%'],
    ['Tổng kinh phí hạng mục:', number_format($thongke['tong_kinhphi_hm'] ?? 0, 0, ',', '.'), '', '']
];

foreach($statsData as $statsRow) {
    $col = 'A';
    foreach($statsRow as $cellValue) {
        $sheet->setCellValue($col . $row, $cellValue);
        if($col == 'A' || $col == 'C') {
            $sheet->getStyle($col . $row)->getFont()->setBold(true);
        }
        $col++;
    }
    $row++;
}

// === DANH SÁCH HẠNG MỤC ===
$row += 2;
$sheet->setCellValue('A' . $row, 'DANH SÁCH HẠNG MỤC THI CÔNG');
$sheet->mergeCells('A' . $row . ':H' . $row);
$sheet->getStyle('A' . $row)->applyFromArray($subHeaderStyle);

$row++;

// Header bảng hạng mục
$headers = ['STT', 'Mã HM', 'Tên hạng mục', 'Tiến độ', 'Trạng thái', 'Ngày bắt đầu', 'Ngày kết thúc', 'Kinh phí'];
$col = 'A';
foreach($headers as $header) {
    $sheet->setCellValue($col . $row, $header);
    $sheet->getStyle($col . $row)->applyFromArray($tableHeaderStyle);
    $col++;
}

$row++;
$stt = 1;

if(mysqli_num_rows($hangmuc_list) > 0) {
    while($hm = mysqli_fetch_assoc($hangmuc_list)) {
        $is_overdue = ($hm['trang_thai'] != 'Hoàn thành' && $hm['so_ngay_con'] < 0);
        
        $sheet->setCellValue('A' . $row, $stt);
        $sheet->setCellValue('B' . $row, $hm['ma_hang_muc'] ?? 'HM-' . str_pad($hm['id'], 4, '0', STR_PAD_LEFT));
        $sheet->setCellValue('C' . $row, $hm['ten_hang_muc']);
        $sheet->setCellValue('D' . $row, $hm['phan_tram_tien_do'] . '%');
        $sheet->setCellValue('E' . $row, $hm['trang_thai']);
        $sheet->setCellValue('F' . $row, date('d/m/Y', strtotime($hm['ngay_bat_dau'])));
        $sheet->setCellValue('G' . $row, date('d/m/Y', strtotime($hm['ngay_ket_thuc'])));
        $sheet->setCellValue('H' . $row, number_format($hm['kinh_phi'] ?? 0, 0, ',', '.'));
        
        // Tô màu dòng quá hạn
        if($is_overdue) {
            $sheet->getStyle('A' . $row . ':H' . $row)->getFill()
                  ->setFillType(Fill::FILL_SOLID)
                  ->getStartColor()->setRGB('FFE5E5');
        }
        
        // Căn giữa cho cột STT, Tiến độ, Ngày tháng
        $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('D' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('E' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('F' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('G' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('H' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        
        $row++;
        $stt++;
    }
} else {
    $sheet->setCellValue('A' . $row, 'Không có hạng mục nào');
    $sheet->mergeCells('A' . $row . ':H' . $row);
    $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
}

// === TỔNG KẾT CUỐI TRANG ===
$row += 2;
$sheet->setCellValue('A' . $row, 'TỔNG KẾT');
$sheet->mergeCells('A' . $row . ':H' . $row);
$sheet->getStyle('A' . $row)->applyFromArray($subHeaderStyle);

$row++;
$summaryData = [
    ['Tổng kinh phí công trình:', number_format($ct['kinh_phi'] ?? 0, 0, ',', '.')],
    ['Tổng kinh phí hạng mục:', number_format($thongke['tong_kinhphi_hm'] ?? 0, 0, ',', '.')],
    ['Chênh lệch:', number_format(($ct['kinh_phi'] ?? 0) - ($thongke['tong_kinhphi_hm'] ?? 0), 0, ',', '.')],
    ['Tiến độ trung bình:', round($thongke['tb_tiendo_hm'] ?? 0) . '%']
];

foreach($summaryData as $summaryRow) {
    $sheet->setCellValue('A' . $row, $summaryRow[0]);
    $sheet->setCellValue('B' . $row, $summaryRow[1]);
    $sheet->getStyle('A' . $row)->getFont()->setBold(true);
    $sheet->getStyle('B' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    $row++;
}

// Kẻ viền cho toàn bảng
$lastRow = $row - 1;
$sheet->getStyle('A1:H' . $lastRow)->getBorders()->getAllBorders()
      ->setBorderStyle(Border::BORDER_THIN);

// Tự động điều chỉnh độ rộng cột
foreach(range('A','H') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Tạo tên file
$filename = 'Bao_cao_cong_trinh_' . ($ct['ma_cong_trinh'] ?? 'CT'.$congtrinh_id) . '_' . date('Ymd_His') . '.xlsx';

// === LƯU FILE VÀO THƯ MỤC UPLOADS ===
$upload_dir = '../../uploads/baocao/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

$file_path = 'uploads/baocao/' . $filename;
$full_path = '../../' . $file_path;

// Lưu file vào server
$writer = new Xlsx($spreadsheet);
$writer->save($full_path);

// === LƯU THÔNG TIN VÀO BẢNG BAOCAO ===
$ten_bao_cao = 'Báo cáo công trình ' . $ct['ten_cong_trinh'];
$loai_file = 'excel';

$sql_insert = "INSERT INTO baocao (ten_bao_cao, congtrinh_id, user_id, file_path, loai_file, thoi_gian_tao) 
               VALUES ('$ten_bao_cao', $congtrinh_id, $user_id, '$file_path', '$loai_file', NOW())";
mysqli_query($conn, $sql_insert);

// Ghi log hoạt động
if(function_exists('logActivity')) {
    logActivity($conn, $user_id, 'Xuất báo cáo', 'Xuất báo cáo công trình: ' . $ct['ten_cong_trinh']);
}

// Xuất file cho người dùng tải về
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');
readfile($full_path);
exit();
?>