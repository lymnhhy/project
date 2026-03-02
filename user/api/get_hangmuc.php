<?php
// user/api/get_hangmuc.php
session_start();
require_once '../../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user']) || !isset($_SESSION['id'])) {
    echo json_encode([]);
    exit();
}

$user_id = $_SESSION['id'];
$congtrinh_id = isset($_GET['congtrinh_id']) ? (int)$_GET['congtrinh_id'] : 0;

if ($congtrinh_id <= 0) {
    echo json_encode([]);
    exit();
}

// Kiểm tra công trình thuộc quyền user
$check = mysqli_query($conn, "SELECT id FROM congtrinh WHERE id = $congtrinh_id AND user_id = '$user_id'");
if (mysqli_num_rows($check) == 0) {
    echo json_encode([]);
    exit();
}

// Lấy danh sách hạng mục
$sql = "SELECT id, ten_hang_muc FROM hangmucthicong WHERE congtrinh_id = $congtrinh_id ORDER BY ten_hang_muc";
$result = mysqli_query($conn, $sql);

$data = [];
while($row = mysqli_fetch_assoc($result)) {
    $data[] = $row;
}

echo json_encode($data);
exit();
?>