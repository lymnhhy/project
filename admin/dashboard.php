<?php
session_start();

// Chưa login → đá về login
if (!isset($_SESSION['user'])) {
    header("Location: ../auth/khach.php");
    exit();
}

// Đã login nhưng không phải admin → chặn
if ($_SESSION['role'] != 1) {
    die("Bạn không có quyền truy cập");
}
?>

<h1>Trang quản trị Admin</h1>
