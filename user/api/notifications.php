<?php
// user/api/notifications.php
session_start();
require_once '../../config/db.php';

header('Content-Type: application/json');

// Kiểm tra đăng nhập
if (!isset($_SESSION['user']) || !isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
    exit();
}

$user_id = $_SESSION['id'];

// Tạo bảng thông báo nếu chưa có
$check = mysqli_query($conn, "SHOW TABLES LIKE 'thongbao'");
if (mysqli_num_rows($check) == 0) {
    $sql_create = "CREATE TABLE `thongbao` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) DEFAULT NULL,
        `tieu_de` varchar(200) DEFAULT NULL,
        `noi_dung` text DEFAULT NULL,
        `link` varchar(255) DEFAULT NULL,
        `da_doc` tinyint(4) DEFAULT 0,
        `created_at` datetime DEFAULT current_timestamp(),
        PRIMARY KEY (`id`),
        KEY `user_id` (`user_id`),
        CONSTRAINT `thongbao_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    mysqli_query($conn, $sql_create);
}

// Tạo thông báo mẫu nếu chưa có (dựa trên dữ liệu thực)
$today = date('Y-m-d');

// Kiểm tra công trình sắp đến hạn
$sql_sap_han = "SELECT id, ten_cong_trinh, ngay_ket_thuc 
                FROM congtrinh 
                WHERE user_id = '$user_id' 
                AND trangthaiCT_id != 3 
                AND ngay_ket_thuc BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 3 DAY)
                AND NOT EXISTS (SELECT 1 FROM thongbao WHERE user_id = '$user_id' AND link LIKE '%congtrinh%' AND DATE(created_at) = CURDATE())";
$result_sap_han = mysqli_query($conn, $sql_sap_han);
while($row = mysqli_fetch_assoc($result_sap_han)) {
    $tieu_de = 'Công trình sắp đến hạn';
    $noi_dung = "Công trình {$row['ten_cong_trinh']} sẽ hết hạn vào ngày " . date('d/m/Y', strtotime($row['ngay_ket_thuc']));
    $link = "/project/user/congtrinh/detail.php?id={$row['id']}";
    mysqli_query($conn, "INSERT INTO thongbao (user_id, tieu_de, noi_dung, link) VALUES ($user_id, '$tieu_de', '$noi_dung', '$link')");
}

// Lấy thông báo chưa đọc
$sql = "SELECT * FROM thongbao WHERE user_id = '$user_id' ORDER BY created_at DESC LIMIT 10";
$result = mysqli_query($conn, $sql);

$notifications = [];
$unread_count = 0;

while($row = mysqli_fetch_assoc($result)) {
    if ($row['da_doc'] == 0) $unread_count++;
    
    $notifications[] = [
        'id' => $row['id'],
        'tieu_de' => $row['tieu_de'],
        'noi_dung' => $row['noi_dung'],
        'link' => $row['link'],
        'da_doc' => $row['da_doc'],
        'thoi_gian' => timeAgo($row['created_at'])
    ];
}

echo json_encode([
    'success' => true,
    'unread_count' => $unread_count,
    'notifications' => $notifications
]);

// Hàm timeAgo
function timeAgo($time) {
    $time = strtotime($time);
    $now = time();
    $diff = $now - $time;
    
    if($diff < 60) return 'Vài giây trước';
    if($diff < 3600) return floor($diff / 60) . ' phút trước';
    if($diff < 86400) return floor($diff / 3600) . ' giờ trước';
    if($diff < 2592000) return floor($diff / 86400) . ' ngày trước';
    return date('d/m/Y', $time);
}
?>