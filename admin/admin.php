

<?php
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: ../auth/dangnhap.php");
    exit();
}

if ($_SESSION['role'] != 1) { // 1 = admin
    die("Bạn không có quyền truy cập");
}
?>
<h1>hello adminz<h1>
