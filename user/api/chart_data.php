<?php
// user/api/chart_data.php
session_start();
require_once '../../config/db.php';

header('Content-Type: application/json');

// Kiểm tra đăng nhập
if (!isset($_SESSION['user']) || !isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
    exit();
}

$user_id = $_SESSION['id'];
$type = isset($_GET['type']) ? $_GET['type'] : 'progress';
$congtrinh_id = isset($_GET['congtrinh_id']) ? (int)$_GET['congtrinh_id'] : 0;

$data = [];

if ($type == 'progress') {
    // Biểu đồ tiến độ công trình
    $sql = "SELECT ct.ten_cong_trinh, 
                   AVG(hm.phan_tram_tien_do) as tien_do
            FROM congtrinh ct
            LEFT JOIN hangmucthicong hm ON ct.id = hm.congtrinh_id
            WHERE ct.user_id = '$user_id'
            GROUP BY ct.id
            ORDER BY ct.ngay_tao DESC
            LIMIT 10";
    $result = mysqli_query($conn, $sql);
    
    $labels = [];
    $values = [];
    while($row = mysqli_fetch_assoc($result)) {
        $labels[] = $row['ten_cong_trinh'];
        $values[] = round($row['tien_do'] ?? 0);
    }
    
    $data = [
        'labels' => $labels,
        'datasets' => [[
            'label' => 'Tiến độ (%)',
            'data' => $values,
            'backgroundColor' => '#fbbf24'
        ]]
    ];
    
} elseif ($type == 'status') {
    // Biểu đồ trạng thái
    $sql = "SELECT 
                SUM(CASE WHEN trangthaiCT_id = 1 THEN 1 ELSE 0 END) as chuatc,
                SUM(CASE WHEN trangthaiCT_id = 2 THEN 1 ELSE 0 END) as dangtc,
                SUM(CASE WHEN trangthaiCT_id = 3 THEN 1 ELSE 0 END) as hoanthanh
            FROM congtrinh 
            WHERE user_id = '$user_id'";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
    
    $data = [
        'labels' => ['Chưa thi công', 'Đang thi công', 'Hoàn thành'],
        'datasets' => [[
            'data' => [$row['chuatc'] ?? 0, $row['dangtc'] ?? 0, $row['hoanthanh'] ?? 0],
            'backgroundColor' => ['#94a3b8', '#fbbf24', '#10b981']
        ]]
    ];
    
} elseif ($type == 'monthly' && $congtrinh_id > 0) {
    // Biểu đồ tiến độ theo tháng của 1 công trình
    $sql = "SELECT 
                DATE_FORMAT(ls.thoi_gian_cap_nhat, '%m/%Y') as thang,
                AVG(ls.phan_tram_moi) as tien_do
            FROM lichsucapnhat ls
            LEFT JOIN hangmucthicong hm ON ls.hangmuc_id = hm.id
            WHERE hm.congtrinh_id = $congtrinh_id
            GROUP BY DATE_FORMAT(ls.thoi_gian_cap_nhat, '%Y-%m')
            ORDER BY ls.thoi_gian_cap_nhat ASC
            LIMIT 12";
    $result = mysqli_query($conn, $sql);
    
    $labels = [];
    $values = [];
    while($row = mysqli_fetch_assoc($result)) {
        $labels[] = $row['thang'];
        $values[] = round($row['tien_do']);
    }
    
    $data = [
        'labels' => $labels,
        'datasets' => [[
            'label' => 'Tiến độ (%)',
            'data' => $values,
            'borderColor' => '#3b82f6',
            'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
            'tension' => 0.3
        ]]
    ];
}

echo json_encode(['success' => true, 'data' => $data]);
?>