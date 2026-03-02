<?php
// user/api/check_duplicate.php
session_start();
require_once '../../config/db.php';

header('Content-Type: application/json');

// Kiểm tra đăng nhập
if (!isset($_SESSION['user']) || !isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
    exit();
}

$user_id = $_SESSION['id'];
$type = isset($_GET['type']) ? $_GET['type'] : '';
$value = isset($_GET['value']) ? mysqli_real_escape_string($conn, $_GET['value']) : '';
$exclude_id = isset($_GET['exclude_id']) ? (int)$_GET['exclude_id'] : 0;

if (empty($type) || empty($value)) {
    echo json_encode(['success' => false, 'message' => 'Thiếu thông tin']);
    exit();
}

$exists = false;
$message = '';

switch ($type) {
    case 'ma_cong_trinh':
        $sql = "SELECT id FROM congtrinh WHERE ma_cong_trinh = '$value' AND user_id = '$user_id'";
        if ($exclude_id > 0) {
            $sql .= " AND id != $exclude_id";
        }
        $result = mysqli_query($conn, $sql);
        $exists = mysqli_num_rows($result) > 0;
        $message = $exists ? 'Mã công trình đã tồn tại' : '';
        break;
        
    case 'ma_hang_muc':
        $sql = "SELECT id FROM hangmucthicong WHERE ma_hang_muc = '$value'";
        if ($exclude_id > 0) {
            $sql .= " AND id != $exclude_id";
        }
        $result = mysqli_query($conn, $sql);
        $exists = mysqli_num_rows($result) > 0;
        $message = $exists ? 'Mã hạng mục đã tồn tại' : '';
        break;
        
    case 'ten_cong_trinh':
        $sql = "SELECT id FROM congtrinh WHERE ten_cong_trinh = '$value' AND user_id = '$user_id'";
        if ($exclude_id > 0) {
            $sql .= " AND id != $exclude_id";
        }
        $result = mysqli_query($conn, $sql);
        $exists = mysqli_num_rows($result) > 0;
        $message = $exists ? 'Tên công trình đã tồn tại' : '';
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Loại kiểm tra không hợp lệ']);
        exit();
}

echo json_encode([
    'success' => true,
    'exists' => $exists,
    'message' => $message
]);
?>