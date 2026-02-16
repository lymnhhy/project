<?php
// hangmuc/delete.php
require_once '../includes/header.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Kiểm tra hạng mục tồn tại
$sql = "SELECT hm.*, ct.ten_cong_trinh 
        FROM hangmucthicong hm
        LEFT JOIN congtrinh ct ON hm.congtrinh_id = ct.id
        WHERE hm.id = $id AND ct.user_id = '{$_SESSION['id']}'";
$result = mysqli_query($conn, $sql);

if(mysqli_num_rows($result) == 0) {
    $_SESSION['error'] = 'Không tìm thấy hạng mục';
    header('Location: index.php');
    exit();
}

$hm = mysqli_fetch_assoc($result);

// Kiểm tra ràng buộc: có ghi chú không?
$check_ghichu = mysqli_query($conn, "SELECT COUNT(*) as total FROM ghichuthicong WHERE hangmuc_id = $id");
$ghichu_count = mysqli_fetch_assoc($check_ghichu)['total'];

if($ghichu_count > 0) {
    $_SESSION['error'] = 'Không thể xóa hạng mục đã có ghi chú';
    header('Location: index.php');
    exit();
}

// Xóa lịch sử cập nhật trước
mysqli_query($conn, "DELETE FROM lichsucapnhat WHERE hangmuc_id = $id");

// Xóa hạng mục
$sql_delete = "DELETE FROM hangmucthicong WHERE id = $id";
if(mysqli_query($conn, $sql_delete)) {
    logActivity($conn, $_SESSION['id'], 'Xóa hạng mục', "Xóa hạng mục: {$hm['ten_hang_muc']}");
    $_SESSION['success'] = 'Xóa hạng mục thành công';
} else {
    $_SESSION['error'] = 'Lỗi: ' . mysqli_error($conn);
}

header('Location: index.php');
exit();
?>