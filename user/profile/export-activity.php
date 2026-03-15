<?php
// user/profile/export-activity.php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Kiểm tra đường dẫn
$db_path = '../../config/db.php';
if (!file_exists($db_path)) {
    die("Không tìm thấy file db.php tại: " . realpath(dirname(__FILE__)) . "/../../config/db.php");
}

require_once $db_path;

// Kiểm tra kết nối database
if (!isset($conn) || !$conn) {
    die("Lỗi kết nối database");
}

// Kiểm tra đăng nhập
if (!isset($_SESSION['id'])) {
    die("Chưa đăng nhập");
}

$user_id = $_SESSION['id'];
$format = isset($_GET['format']) ? $_GET['format'] : 'csv';

// Xử lý bộ lọc
$where = "WHERE user_id = " . intval($user_id);

if(isset($_GET['action']) && !empty($_GET['action'])) {
    $action = mysqli_real_escape_string($conn, $_GET['action']);
    $where .= " AND hanh_dong LIKE '%$action%'";
}

if(isset($_GET['date']) && !empty($_GET['date'])) {
    $date = mysqli_real_escape_string($conn, $_GET['date']);
    $where .= " AND DATE(thoi_gian) = '$date'";
}

// Lấy dữ liệu
$sql = "SELECT * FROM lichsuhoatdong 
        $where 
        ORDER BY thoi_gian DESC";
$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Lỗi truy vấn: " . mysqli_error($conn));
}

if (mysqli_num_rows($result) == 0) {
    die("Không có dữ liệu để xuất");
}

// Xóa tất cả output buffer
while (ob_get_level()) {
    ob_end_clean();
}

if($format == 'csv') {
    // Xuất file CSV
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="lich_su_hoat_dong_' . date('Y-m-d') . '.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Tạo output stream
    $output = fopen('php://output', 'w');
    
    // Thêm BOM để hỗ trợ tiếng Việt
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Header CSV
    fputcsv($output, ['STT', 'Thời gian', 'Hành động', 'Chi tiết', 'IP Address']);
    
    // Ghi dữ liệu
    $stt = 1;
    while($row = mysqli_fetch_assoc($result)) {
        fputcsv($output, [
            $stt++,
            $row['thoi_gian'],
            $row['hanh_dong'],
            $row['chi_tiet'],
            $row['ip_address']
        ]);
    }
    
    fclose($output);
    exit();
    
} elseif($format == 'excel') {
    // Xuất file Excel
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="lich_su_hoat_dong_' . date('Y-m-d') . '.xls"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    echo '<html>';
    echo '<head>';
    echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
    echo '<style>
        td { mso-number-format:"\@"; }
        .text { mso-number-format:"\@"; }
    </style>';
    echo '</head>';
    echo '<body>';
    echo '<table border="1">';
    echo '<thead>';
    echo '<tr style="background-color: #f2f2f2;">';
    echo '<th>STT</th>';
    echo '<th>Thời gian</th>';
    echo '<th>Hành động</th>';
    echo '<th>Chi tiết</th>';
    echo '<th>IP Address</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    
    $stt = 1;
    mysqli_data_seek($result, 0); // Reset con trỏ về đầu
    while($row = mysqli_fetch_assoc($result)) {
        echo '<tr>';
        echo '<td>' . $stt++ . '</td>';
        echo '<td class="text">' . htmlspecialchars($row['thoi_gian']) . '</td>';
        echo '<td class="text">' . htmlspecialchars($row['hanh_dong']) . '</td>';
        echo '<td class="text">' . htmlspecialchars($row['chi_tiet']) . '</td>';
        echo '<td class="text">' . htmlspecialchars($row['ip_address']) . '</td>';
        echo '</tr>';
    }
    
    echo '</tbody>';
    echo '</table>';
    echo '</body>';
    echo '</html>';
    exit();
}

exit();
?>