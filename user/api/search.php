<?php
// user/api/search.php
session_start();
require_once '../../config/db.php';

header('Content-Type: application/json');

// Kiểm tra đăng nhập
if (!isset($_SESSION['user']) || !isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
    exit();
}

$user_id = $_SESSION['id'];
$keyword = isset($_GET['q']) ? mysqli_real_escape_string($conn, $_GET['q']) : '';

if (empty($keyword) || strlen($keyword) < 2) {
    echo json_encode(['success' => true, 'data' => []]);
    exit();
}

$results = [];

// 1. Tìm kiếm công trình
$sql_ct = "SELECT id, ten_cong_trinh as title, dia_diem as subtitle, 
           'congtrinh' as type, ngay_tao as date
           FROM congtrinh 
           WHERE user_id = '$user_id' 
           AND (ten_cong_trinh LIKE '%$keyword%' OR dia_diem LIKE '%$keyword%')
           LIMIT 5";
$result_ct = mysqli_query($conn, $sql_ct);
while($row = mysqli_fetch_assoc($result_ct)) {
    $row['url'] = '/project/user/congtrinh/detail.php?id=' . $row['id'];
    $row['icon'] = 'building';
    $results[] = $row;
}

// 2. Tìm kiếm hạng mục
$sql_hm = "SELECT hm.id, hm.ten_hang_muc as title, ct.ten_cong_trinh as subtitle,
           'hangmuc' as type, hm.updated_at as date
           FROM hangmucthicong hm
           LEFT JOIN congtrinh ct ON hm.congtrinh_id = ct.id
           WHERE ct.user_id = '$user_id' 
           AND hm.ten_hang_muc LIKE '%$keyword%'
           LIMIT 5";
$result_hm = mysqli_query($conn, $sql_hm);
while($row = mysqli_fetch_assoc($result_hm)) {
    $row['url'] = '/project/user/hangmuc/detail.php?id=' . $row['id'];
    $row['icon'] = 'tasks';
    $results[] = $row;
}

// 3. Tìm kiếm ghi chú
$sql_gc = "SELECT gc.id, gc.noi_dung as title, CONCAT(ct.ten_cong_trinh, ' - ', hm.ten_hang_muc) as subtitle,
           'ghichu' as type, gc.ngay_ghi as date
           FROM ghichuthicong gc
           LEFT JOIN hangmucthicong hm ON gc.hangmuc_id = hm.id
           LEFT JOIN congtrinh ct ON hm.congtrinh_id = ct.id
           WHERE ct.user_id = '$user_id' AND gc.noi_dung LIKE '%$keyword%'
           LIMIT 5";
$result_gc = mysqli_query($conn, $sql_gc);
while($row = mysqli_fetch_assoc($result_gc)) {
    $row['url'] = '/project/user/ghichu/detail.php?id=' . $row['id'];
    $row['icon'] = 'sticky-note';
    $results[] = $row;
}

// Sắp xếp theo ngày mới nhất
usort($results, function($a, $b) {
    return strtotime($b['date']) - strtotime($a['date']);
});

echo json_encode(['success' => true, 'data' => $results]);
?>