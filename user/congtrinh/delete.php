<?php
// congtrinh/delete.php - ĐÃ SỬA
session_start(); // Thêm dòng này
require_once '../../config/db.php'; // Sửa đường dẫn, KHÔNG include header

// Kiểm tra đăng nhập
if (!isset($_SESSION['user']) || !isset($_SESSION['id'])) {
    header('Location: ../../auth/dangnhap.php');
    exit();
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Kiểm tra công trình tồn tại và thuộc quyền user
$sql = "SELECT ten_cong_trinh FROM congtrinh WHERE id = $id AND user_id = '{$_SESSION['id']}'";
$result = mysqli_query($conn, $sql);

if(mysqli_num_rows($result) == 0) {
    $_SESSION['error'] = 'Không tìm thấy công trình hoặc không có quyền xóa';
    header('Location: index.php');
    exit();
}

$ct = mysqli_fetch_assoc($result);

// Kiểm tra ràng buộc: có hạng mục không?
$check_hm = mysqli_query($conn, "SELECT COUNT(*) as total FROM hangmucthicong WHERE congtrinh_id = $id");
$hm_count = mysqli_fetch_assoc($check_hm)['total'];

if($hm_count > 0) {
    $_SESSION['error'] = 'Không thể xóa công trình đã có hạng mục thi công';
    header('Location: index.php');
    exit();
}

// Xóa hình ảnh nếu có
$hinh_anh = mysqli_fetch_assoc(mysqli_query($conn, "SELECT hinh_anh FROM congtrinh WHERE id = $id"))['hinh_anh'];
if(!empty($hinh_anh) && file_exists("../../" . $hinh_anh)) {
    unlink("../../" . $hinh_anh);
}

// Xóa công trình
$sql = "DELETE FROM congtrinh WHERE id = $id";
if(mysqli_query($conn, $sql)) {
    // Tự viết hàm log hoặc dùng hàm có sẵn
    $ip = $_SERVER['REMOTE_ADDR'];
    $thoi_gian = date('Y-m-d H:i:s');
    $log_sql = "INSERT INTO lichsuhoatdong (user_id, hanh_dong, chi_tiet, ip_address, thoi_gian) 
                VALUES ('{$_SESSION['id']}', 'Xóa công trình', 'Đã xóa: {$ct['ten_cong_trinh']}', '$ip', '$thoi_gian')";
    mysqli_query($conn, $log_sql);
    
    $_SESSION['success'] = 'Xóa công trình thành công';
} else {
    $_SESSION['error'] = 'Lỗi: ' . mysqli_error($conn);
}

header('Location: index.php');
exit();
?>