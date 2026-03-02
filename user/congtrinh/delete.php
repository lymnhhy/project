<?php
// congtrinh/delete.php
session_start();
require_once '../../config/db.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user']) || !isset($_SESSION['id'])) {
    header('Location: ../../auth/dangnhap.php');
    exit();
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user_id = $_SESSION['id'];

if ($id <= 0) {
    $_SESSION['error'] = 'ID không hợp lệ';
    header('Location: index.php');
    exit();
}

// Kiểm tra công trình tồn tại và thuộc quyền user
$sql = "SELECT ten_cong_trinh FROM congtrinh WHERE id = $id AND user_id = '$user_id'";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) == 0) {
    $_SESSION['error'] = 'Không tìm thấy công trình hoặc không có quyền xóa';
    header('Location: index.php');
    exit();
}

$ct = mysqli_fetch_assoc($result);

// BẮT ĐẦU TRANSACTION - ĐẢM BẢO XÓA TOÀN BỘ HOẶC KHÔNG XÓA GÌ CẢ
mysqli_begin_transaction($conn);

try {
    // 1. Lấy tất cả hạng mục của công trình
    $hm_ids = [];
    $result_hm = mysqli_query($conn, "SELECT id FROM hangmucthicong WHERE congtrinh_id = $id");
    while ($row = mysqli_fetch_assoc($result_hm)) {
        $hm_ids[] = $row['id'];
    }
    
    if (!empty($hm_ids)) {
        $hm_ids_str = implode(',', $hm_ids);
        
        // 2. Xóa ghi chú của các hạng mục
        $sql_ghichu = "DELETE FROM ghichuthicong WHERE hangmuc_id IN ($hm_ids_str)";
        if (!mysqli_query($conn, $sql_ghichu)) {
            throw new Exception("Lỗi xóa ghi chú: " . mysqli_error($conn));
        }
        
        // 3. Xóa lịch sử cập nhật của các hạng mục
        $sql_lichsu = "DELETE FROM lichsucapnhat WHERE hangmuc_id IN ($hm_ids_str)";
        if (!mysqli_query($conn, $sql_lichsu)) {
            throw new Exception("Lỗi xóa lịch sử: " . mysqli_error($conn));
        }
        
        // 4. Xóa các hạng mục
        $sql_hm = "DELETE FROM hangmucthicong WHERE congtrinh_id = $id";
        if (!mysqli_query($conn, $sql_hm)) {
            throw new Exception("Lỗi xóa hạng mục: " . mysqli_error($conn));
        }
    }
    
    // 5. Xóa báo cáo liên quan
    $sql_baocao = "DELETE FROM baocao WHERE congtrinh_id = $id";
    if (!mysqli_query($conn, $sql_baocao)) {
        throw new Exception("Lỗi xóa báo cáo: " . mysqli_error($conn));
    }
    
    // 6. Xóa hình ảnh nếu có
    $hinh_anh = mysqli_fetch_assoc(mysqli_query($conn, "SELECT hinh_anh FROM congtrinh WHERE id = $id"))['hinh_anh'];
    if (!empty($hinh_anh) && file_exists("../../" . $hinh_anh)) {
        unlink("../../" . $hinh_anh);
    }
    
    // 7. Xóa công trình
    $sql_ct = "DELETE FROM congtrinh WHERE id = $id AND user_id = '$user_id'";
    if (!mysqli_query($conn, $sql_ct)) {
        throw new Exception("Lỗi xóa công trình: " . mysqli_error($conn));
    }
    
    // Nếu mọi thứ OK, COMMIT transaction
    mysqli_commit($conn);
    
    // Ghi log thành công
    $ip = $_SERVER['REMOTE_ADDR'];
    $log_sql = "INSERT INTO lichsuhoatdong (user_id, hanh_dong, chi_tiet, ip_address, thoi_gian) 
                VALUES ('$user_id', 'Xóa công trình', 'Đã xóa: {$ct['ten_cong_trinh']} và tất cả dữ liệu liên quan', '$ip', NOW())";
    mysqli_query($conn, $log_sql);
    
    $_SESSION['success'] = 'Xóa công trình và tất cả dữ liệu liên quan thành công!';
    
} catch (Exception $e) {
    // Nếu có lỗi, ROLLBACK tất cả
    mysqli_rollback($conn);
    $_SESSION['error'] = 'Lỗi khi xóa: ' . $e->getMessage();
}

header('Location: index.php');
exit();
?>