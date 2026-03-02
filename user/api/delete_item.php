<?php
// user/api/delete_item.php
session_start();
require_once '../../config/db.php';

header('Content-Type: application/json');

// Kiểm tra đăng nhập
if (!isset($_SESSION['user']) || !isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
    exit();
}

$user_id = $_SESSION['id'];
$type = isset($_POST['type']) ? $_POST['type'] : '';
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if ($id <= 0 || empty($type)) {
    echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
    exit();
}

$success = false;
$message = '';

switch ($type) {
    case 'congtrinh':
        // Kiểm tra công trình
        $check = mysqli_query($conn, "SELECT ten_cong_trinh FROM congtrinh WHERE id = $id AND user_id = '$user_id'");
        if (mysqli_num_rows($check) == 0) {
            $message = 'Không tìm thấy công trình';
            break;
        }
        
        // Kiểm tra hạng mục
        $hm = mysqli_query($conn, "SELECT COUNT(*) as total FROM hangmucthicong WHERE congtrinh_id = $id");
        $hm_count = mysqli_fetch_assoc($hm)['total'];
        
        if ($hm_count > 0) {
            $message = 'Không thể xóa công trình đã có hạng mục';
            break;
        }
        
        $ct = mysqli_fetch_assoc($check);
        $sql = "DELETE FROM congtrinh WHERE id = $id";
        $log_detail = "Xóa công trình: " . $ct['ten_cong_trinh'];
        break;
        
    case 'hangmuc':
        // Kiểm tra hạng mục
        $check = mysqli_query($conn, "SELECT hm.*, ct.user_id FROM hangmucthicong hm LEFT JOIN congtrinh ct ON hm.congtrinh_id = ct.id WHERE hm.id = $id");
        if (mysqli_num_rows($check) == 0) {
            $message = 'Không tìm thấy hạng mục';
            break;
        }
        
        $hm = mysqli_fetch_assoc($check);
        if ($hm['user_id'] != $user_id) {
            $message = 'Không có quyền xóa';
            break;
        }
        
        // Xóa lịch sử và ghi chú trước
        mysqli_query($conn, "DELETE FROM lichsucapnhat WHERE hangmuc_id = $id");
        mysqli_query($conn, "DELETE FROM ghichuthicong WHERE hangmuc_id = $id");
        
        $sql = "DELETE FROM hangmucthicong WHERE id = $id";
        $log_detail = "Xóa hạng mục: " . $hm['ten_hang_muc'];
        break;
        
    case 'ghichu':
        // Kiểm tra ghi chú
        $check = mysqli_query($conn, "SELECT * FROM ghichuthicong WHERE id = $id AND user_id = '$user_id'");
        if (mysqli_num_rows($check) == 0) {
            $message = 'Không tìm thấy ghi chú';
            break;
        }
        
        $gc = mysqli_fetch_assoc($check);
        
        // Xóa hình ảnh
        if (!empty($gc['hinh_anh']) && file_exists($_SERVER['DOCUMENT_ROOT'] . '/project/' . $gc['hinh_anh'])) {
            unlink($_SERVER['DOCUMENT_ROOT'] . '/project/' . $gc['hinh_anh']);
        }
        
        $sql = "DELETE FROM ghichuthicong WHERE id = $id";
        $log_detail = "Xóa ghi chú ID: $id";
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Loại không hợp lệ']);
        exit();
}

if (isset($sql)) {
    if (mysqli_query($conn, $sql)) {
        $success = true;
        $message = 'Xóa thành công';
        
        // Ghi log
        $ip = $_SERVER['REMOTE_ADDR'];
        $thoi_gian = date('Y-m-d H:i:s');
        $log_sql = "INSERT INTO lichsuhoatdong (user_id, hanh_dong, chi_tiet, ip_address, thoi_gian) 
                    VALUES ($user_id, 'Xóa $type', '$log_detail', '$ip', '$thoi_gian')";
        mysqli_query($conn, $log_sql);
    } else {
        $message = 'Lỗi: ' . mysqli_error($conn);
    }
}

echo json_encode(['success' => $success, 'message' => $message]);
?>