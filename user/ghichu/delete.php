<?php
// user/ghichu/delete.php
session_start();
require_once '../../config/db.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user']) || !isset($_SESSION['id'])) {
    header('Location: ../../auth/dangnhap.php');
    exit();
}

$user_id = $_SESSION['id'];
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    $_SESSION['error'] = 'ID không hợp lệ';
    header('Location: index.php');
    exit();
}

// Kiểm tra ghi chú thuộc quyền user
$check = mysqli_query($conn, "SELECT hinh_anh FROM ghichuthicong WHERE id = $id AND user_id = '$user_id'");
if (mysqli_num_rows($check) == 0) {
    $_SESSION['error'] = 'Không tìm thấy ghi chú hoặc không có quyền xóa';
    header('Location: index.php');
    exit();
}

$gc = mysqli_fetch_assoc($check);

// Xóa hình ảnh nếu có
if(!empty($gc['hinh_anh']) && file_exists($_SERVER['DOCUMENT_ROOT'] . '/project/' . $gc['hinh_anh'])) {
    unlink($_SERVER['DOCUMENT_ROOT'] . '/project/' . $gc['hinh_anh']);
}

// Xóa ghi chú
$sql = "DELETE FROM ghichuthicong WHERE id = $id";

if (mysqli_query($conn, $sql)) {
    $_SESSION['success'] = 'Xóa ghi chú thành công!';
    
    // Ghi log
    $ip = $_SERVER['REMOTE_ADDR'];
    $thoi_gian = date('Y-m-d H:i:s');
    $log_sql = "INSERT INTO lichsuhoatdong (user_id, hanh_dong, chi_tiet, ip_address, thoi_gian) 
                VALUES ('$user_id', 'Xóa ghi chú', 'Đã xóa ghi chú ID: $id', '$ip', '$thoi_gian')";
    @mysqli_query($conn, $log_sql);
} else {
    $_SESSION['error'] = 'Lỗi: ' . mysqli_error($conn);
}

header('Location: index.php');
exit();
?>