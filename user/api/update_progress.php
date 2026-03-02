<?php
// user/api/update_progress.php
session_start();
require_once '../../config/db.php';

header('Content-Type: application/json');

// Kiểm tra đăng nhập
if (!isset($_SESSION['user']) || !isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
    exit();
}

$user_id = $_SESSION['id'];

// Kiểm tra phương thức POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Phương thức không hợp lệ']);
    exit();
}

// Lấy dữ liệu từ POST
$hangmuc_id = isset($_POST['hangmuc_id']) ? (int)$_POST['hangmuc_id'] : 0;
$phan_tram_moi = isset($_POST['phan_tram']) ? (int)$_POST['phan_tram'] : -1;
$ghi_chu = isset($_POST['ghi_chu']) ? mysqli_real_escape_string($conn, $_POST['ghi_chu']) : '';

if ($hangmuc_id <= 0 || $phan_tram_moi < 0 || $phan_tram_moi > 100) {
    echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
    exit();
}

// Kiểm tra hạng mục thuộc quyền user
$sql = "SELECT hm.*, ct.user_id, hm.phan_tram_tien_do as phan_tram_cu,
               ct.ten_cong_trinh, hm.ten_hang_muc
        FROM hangmucthicong hm
        LEFT JOIN congtrinh ct ON hm.congtrinh_id = ct.id
        WHERE hm.id = $hangmuc_id";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) == 0) {
    echo json_encode(['success' => false, 'message' => 'Không tìm thấy hạng mục']);
    exit();
}

$hm = mysqli_fetch_assoc($result);

if ($hm['user_id'] != $user_id) {
    echo json_encode(['success' => false, 'message' => 'Không có quyền cập nhật']);
    exit();
}

$phan_tram_cu = $hm['phan_tram_cu'];

// Xác định trạng thái mới
if ($phan_tram_moi == 0) {
    $trang_thai = 'Chưa thi công';
} elseif ($phan_tram_moi == 100) {
    $trang_thai = 'Hoàn thành';
} else {
    $trang_thai = 'Đang thi công';
}

// Cập nhật hạng mục
$sql_update = "UPDATE hangmucthicong SET 
               phan_tram_tien_do = $phan_tram_moi,
               trang_thai = '$trang_thai',
               updated_at = NOW()
               WHERE id = $hangmuc_id";

if (mysqli_query($conn, $sql_update)) {
    // Lưu lịch sử
    $sql_history = "INSERT INTO lichsucapnhat (hangmuc_id, phan_tram_cu, phan_tram_moi, ghi_chu, thoi_gian_cap_nhat) 
                    VALUES ($hangmuc_id, $phan_tram_cu, $phan_tram_moi, '$ghi_chu', NOW())";
    mysqli_query($conn, $sql_history);
    
    // Ghi log
    $ip = $_SERVER['REMOTE_ADDR'];
    $thoi_gian = date('Y-m-d H:i:s');
    $log_sql = "INSERT INTO lichsuhoatdong (user_id, hanh_dong, chi_tiet, ip_address, thoi_gian) 
                VALUES ($user_id, 'Cập nhật tiến độ', 
                'Hạng mục: {$hm['ten_hang_muc']} - {$phan_tram_cu}% → {$phan_tram_moi}%', 
                '$ip', '$thoi_gian')";
    mysqli_query($conn, $log_sql);
    
    // Tính lại tiến độ công trình
    $sql_tiendo = "SELECT AVG(phan_tram_tien_do) as tb FROM hangmucthicong WHERE congtrinh_id = {$hm['congtrinh_id']}";
    $result_tiendo = mysqli_query($conn, $sql_tiendo);
    $tb_tiendo = round(mysqli_fetch_assoc($result_tiendo)['tb'] ?? 0);
    
    // Cập nhật tiến độ công trình
    mysqli_query($conn, "UPDATE congtrinh SET phan_tram_tien_do = $tb_tiendo WHERE id = {$hm['congtrinh_id']}");
    
    echo json_encode([
        'success' => true,
        'message' => 'Cập nhật thành công',
        'data' => [
            'phan_tram_moi' => $phan_tram_moi,
            'trang_thai' => $trang_thai,
            'tb_congtrinh' => $tb_tiendo
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Lỗi: ' . mysqli_error($conn)]);
}
?>