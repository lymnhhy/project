<?php
session_start();

// Xóa toàn bộ session
session_unset();
session_destroy();

// Quay về trang khách
header("Location: ../khach.php");
exit();
