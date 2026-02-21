<?php
session_start();
include dirname(dirname(__DIR__)) . "/config/db.php";
// Kiểm tra đăng nhập và quyền Admin
if (!isset($_SESSION['user']) || $_SESSION['role'] != 1) {
    header("Location: ../guest.php");
    exit();
}

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Quản lý công trình</title>
    <!-- Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Admin CSS -->
    <link rel="stylesheet" href="/project/admin/css/admin-style.css">
</head>
<body>

<!-- SIDEBAR - NẰM TRONG HEADER -->
<div class="sidebar">
    <div class="sidebar-header">
        <h3>Admin Panel</h3>
        <p>Xin chào, <?php echo $_SESSION['user']; ?></p>
    </div>
    <!-- SỬA TẤT CẢ CÁC LINK TRONG SIDEBAR -->
<ul class="sidebar-menu">
    <li class="<?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
        <a href="/project/admin/dashboard.php">📊 Dashboard</a>  <!-- THÊM /project/admin/ -->
    </li>
    <li class="<?php echo $current_page == 'users.php' ? 'active' : ''; ?>">
        <a href="/project/admin/functions/users.php">👥 Quản lý người dùng</a>  <!-- SỬA -->
    </li>
    <li class="<?php echo $current_page == 'categories.php' ? 'active' : ''; ?>">
        <a href="/project/admin/functions/categories.php">📋 Loại công trình & Trạng thái</a>  <!-- SỬA -->
    </li>
    <li class="<?php echo $current_page == 'hangmuc.php' ? 'active' : ''; ?>">
        <a href="/project/admin/functions/hangmuc.php">🔨 Hạng mục thi công</a>  <!-- SỬA -->
    </li>
    <li class="<?php echo $current_page == 'website.php' ? 'active' : ''; ?>">
        <a href="/project/admin/functions/website.php">🌐 Cấu hình website</a>  <!-- SỬA -->
    </li>
    <li class="<?php echo $current_page == 'content.php' ? 'active' : ''; ?>">
        <a href="/project/admin/functions/content.php">📄 Nội dung</a>  <!-- SỬA -->
    </li>
    <li class="<?php echo $current_page == 'banner.php' ? 'active' : ''; ?>">
        <a href="/project/admin/functions/banner.php">🖼️ Banner</a>  <!-- SỬA -->
    </li>
    <li>
        <a href="/project/admin/logout.php">🚪 Đăng xuất</a>  <!-- SỬA -->
    </li>
</ul>
</div>

<!-- MAIN CONTENT - MỞ RA -->
<div class="main-content">
    <div class="admin-header">
        <h2><?php 
            if($current_page == 'dashboard.php') echo 'Dashboard - Tổng quan hệ thống';
            elseif($current_page == 'users.php') echo 'Quản lý người dùng';
            elseif($current_page == 'categories.php') echo 'Quản lý loại công trình & trạng thái';
            elseif($current_page == 'hangmuc.php') echo 'Quản lý hạng mục thi công';
            elseif($current_page == 'website.php') echo 'Cấu hình website';
            elseif($current_page == 'content.php') echo 'Quản lý nội dung';
            elseif($current_page == 'banner.php') echo 'Quản lý banner';
            else echo 'Admin Panel';
        ?></h2>
        <div class="user-info">
            Xin chào, <span><?php echo $_SESSION['user']; ?></span>
        </div>
    </div>
    <!-- NỘI DUNG CHÍNH SẼ ĐƯỢC CHÈN VÀO ĐÂY -->