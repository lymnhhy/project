<?php
// user/api/get_congtrinh.php
session_start();
require_once '../../config/db.php';

header('Content-Type: application/json');

// Kiểm tra đăng nhập
if (!isset($_SESSION['user']) || !isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
    exit();
}

$user_id = $_SESSION['id'];

$sql = "SELECT id, ma_cong_trinh, ten_cong_trinh, dia_diem 
        FROM congtrinh 
        WHERE user_id = '$user_id' 
        ORDER BY ten_cong_trinh";
$result = mysqli_query($conn, $sql);

$data = [];
while($row = mysqli_fetch_assoc($result)) {
    $data[] = [
        'id' => $row['id'],
        'ma' => $row['ma_cong_trinh'] ?? 'CT-'.str_pad($row['id'], 4, '0', STR_PAD_LEFT),
        'ten' => $row['ten_cong_trinh'],
        'dia_diem' => $row['dia_diem']
    ];
}

echo json_encode(['success' => true, 'data' => $data]);
?>