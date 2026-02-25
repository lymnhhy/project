<?php
// admin/includes/header.php
session_start();
include dirname(dirname(__DIR__)) . "/config/db.php";

// Kiểm tra đăng nhập và quyền Admin
if (!isset($_SESSION['user']) || $_SESSION['role'] != 1) {
    header("Location: /project/guest.php");
    exit();
}

$current_page = basename($_SERVER['PHP_SELF']);
$page_title = isset($page_title) ? $page_title : 'Admin Panel';

// Lấy thông tin admin
$user_id = $_SESSION['id'];
$sql_user = "SELECT * FROM users WHERE id = '$user_id'";
$result_user = mysqli_query($conn, $sql_user);
$admin = mysqli_fetch_assoc($result_user);

// LẤY THÔNG TIN CẤU HÌNH WEBSITE TỪ BẢNG cauhinhweb
$sql_config = "SELECT * FROM cauhinhweb WHERE id = 1";
$result_config = mysqli_query($conn, $sql_config);
$config = mysqli_fetch_assoc($result_config);

// Nếu chưa có cấu hình, tạo mảng mặc định
if(!$config) {
    $config = [
        'ten_website' => 'ProTrack Admin',
        'logo' => '/project/uploads/logo/default-logo.png',
        'favicon' => '/project/uploads/favicon/favicon.ico',
        'slogan' => 'Hệ thống quản lý dự án chuyên nghiệp'
    ];
}

// Hàm đếm thông báo
function countPendingItems($conn) {
    $count = 0;
    // Đếm user mới
    $result = mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE trangthai = 1 AND DATE(ngaytao) = CURDATE()");
    $row = mysqli_fetch_assoc($result);
    $count += $row['total'];
    
    // Đếm công trình mới
    $result = mysqli_query($conn, "SELECT COUNT(*) as total FROM congtrinh WHERE DATE(ngay_tao) = CURDATE()");
    $row = mysqli_fetch_assoc($result);
    $count += $row['total'];
    
    return $count;
}

$notification_count = countPendingItems($conn);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> | <?php echo htmlspecialchars($config['ten_website']); ?></title>
    
    <!-- FAVICON - LẤY TỪ CẤU HÌNH -->
    <link rel="icon" type="image/x-icon" href="<?php echo $config['favicon'] ?? '/project/assets/img/favicon.ico'; ?>">
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    
    <!-- Admin CSS -->
    <link rel="stylesheet" href="/project/admin/css/admin-style.css">
    
    <style>
        /* Giữ nguyên style cũ */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f4f6f9;
            display: flex;
        }
        
        /* SIDEBAR */
        .sidebar {
            width: 280px;
            height: 100vh;
            background: linear-gradient(180deg, #2c3e50 0%, #1a2634 100%);
            color: #fff;
            position: fixed;
            left: 0;
            top: 0;
            overflow-y: auto;
            transition: all 0.3s;
            z-index: 1000;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        
        .sidebar-header {
            padding: 25px 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 20px;
        }
        
        .sidebar-header h3 {
            color: #fff;
            font-weight: 700;
            font-size: 24px;
            margin: 0;
        }
        
        .sidebar-header h3 i {
            color: #fbbf24;
            margin-right: 10px;
        }
        
        .sidebar-header p {
            color: rgba(255,255,255,0.7);
            font-size: 13px;
            margin: 10px 0 0;
        }
        
        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .sidebar-menu li {
            margin: 5px 10px;
        }
        
        .sidebar-menu li a {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s;
            font-size: 14px;
        }
        
        .sidebar-menu li a i {
            width: 25px;
            margin-right: 10px;
            font-size: 16px;
        }
        
        .sidebar-menu li a:hover {
            background: rgba(255,255,255,0.1);
            color: #fff;
            transform: translateX(5px);
        }
        
        .sidebar-menu li.active a {
            background: rgba(251,191,36,0.15);
            color: #fbbf24;
            border-left: 3px solid #fbbf24;
        }
        
        .sidebar-menu .nav-section {
            color: rgba(255,255,255,0.4);
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 20px 15px 5px;
            font-weight: 600;
        }
        
        /* MAIN CONTENT */
        .main-content {
            flex: 1;
            margin-left: 280px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        /* TOP NAVBAR */
        .top-navbar {
            background: #fff;
            padding: 15px 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 999;
        }
        
        .page-title {
            font-size: 20px;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .page-title i {
            color: #fbbf24;
            margin-right: 10px;
        }
        
        .user-dropdown {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .notification-badge {
            position: relative;
            cursor: pointer;
        }
        
        .notification-badge i {
            font-size: 20px;
            color: #6c757d;
        }
        
        .badge-count {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #dc3545;
            color: #fff;
            font-size: 10px;
            padding: 2px 5px;
            border-radius: 10px;
            min-width: 18px;
            text-align: center;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 5px 15px;
            background: #f8f9fa;
            border-radius: 30px;
            cursor: pointer;
        }
        
        .user-info img {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .user-info span {
            font-weight: 500;
            color: #2c3e50;
        }
        
        /* CONTENT WRAPPER */
        .content-wrapper {
            flex: 1;
            padding: 25px;
            background: #f4f6f9;
        }
        
        /* RESPONSIVE */
        @media (max-width: 768px) {
            .sidebar {
                left: -280px;
            }
            .sidebar.show {
                left: 0;
            }
            .main-content {
                margin-left: 0;
            }
            .top-navbar {
                padding: 15px;
            }
        }
        
        /* DROPDOWN MENU */
        .dropdown-menu {
            border: none;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            border-radius: 10px;
            padding: 8px;
        }
        
        .dropdown-item {
            padding: 8px 15px;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .dropdown-item i {
            width: 20px;
            margin-right: 10px;
            color: #6c757d;
        }
        
        .dropdown-item:hover {
            background: #f8f9fa;
        }
    </style>
</head>
<body>
    <!-- SIDEBAR -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <!-- HIỂN THỊ LOGO TỪ CẤU HÌNH -->
            <?php if(!empty($config['logo'])): ?>
            <img src="<?php echo $config['logo']; ?>" alt="Logo" style="max-height: 50px; margin-bottom: 10px;">
            <?php endif; ?>
            <h3><i class="fas fa-hard-hat"></i> Admin</h3>
            <p><?php echo htmlspecialchars($config['slogan'] ?? 'Quản trị hệ thống'); ?></p>
            <p><?php echo htmlspecialchars($admin['hoten'] ?? $_SESSION['user']); ?></p>
        </div>
        
        <ul class="sidebar-menu">
            <li class="nav-section">Main Menu</li>
            
            <li class="<?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                <a href="/project/admin/dashboard.php">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </li>
            
            <li class="nav-section">Quản lý hệ thống</li>
            
            <li class="<?php echo $current_page == 'users.php' ? 'active' : ''; ?>">
                <a href="/project/admin/functions/users.php">
                    <i class="fas fa-users"></i> Quản lý người dùng
                </a>
            </li>
            
            <!-- <li class="<?php echo $current_page == 'categories.php' ? 'active' : ''; ?>">
                <a href="/project/admin/functions/categories.php">
                    <i class="fas fa-tags"></i> Loại & Trạng thái
                </a>
            </li> -->
            
            <li class="nav-section">Quản lý dữ liệu</li>
            
            <li class="<?php echo $current_page == 'hangmuc.php' ? 'active' : ''; ?>">
                <a href="/project/admin/functions/hangmuc.php">
                    <i class="fas fa-tasks"></i> Hạng mục thi công
                </a>
            </li>
            
            <li class="<?php echo $current_page == 'congtrinh.php' ? 'active' : ''; ?>">
                <a href="/project/admin/functions/congtrinh.php">
                    <i class="fas fa-building"></i> Công trình
                </a>
            </li>
            
            <li class="nav-section">Cấu hình website</li>
            
            <li class="<?php echo $current_page == 'website.php' ? 'active' : ''; ?>">
                <a href="/project/admin/functions/website.php">
                    <i class="fas fa-globe"></i> Cấu hình website
                </a>
            </li>
            
            <li class="<?php echo $current_page == 'content.php' ? 'active' : ''; ?>">
                <a href="/project/admin/functions/content.php">
                    <i class="fas fa-file-alt"></i> Nội dung
                </a>
            </li>
            
            <li class="<?php echo $current_page == 'banner.php' ? 'active' : ''; ?>">
                <a href="/project/admin/functions/banner.php">
                    <i class="fas fa-images"></i> Banner
                </a>
            </li>
            
            <li class="nav-section">Hệ thống</li>
            
            <li>
                <a href="/project/admin/logout.php" class="text-danger">
                    <i class="fas fa-sign-out-alt"></i> Đăng xuất
                </a>
            </li>
        </ul>
    </div>
    
    <!-- MAIN CONTENT -->
    <div class="main-content">
        <!-- TOP NAVBAR -->
        <div class="top-navbar">
            <div class="page-title">
                <i class="fas fa-<?php 
                    if($current_page == 'dashboard.php') echo 'tachometer-alt';
                    elseif($current_page == 'users.php') echo 'users';
                    elseif($current_page == 'categories.php') echo 'tags';
                    elseif($current_page == 'hangmuc.php') echo 'tasks';
                    elseif($current_page == 'congtrinh.php') echo 'building';
                    elseif($current_page == 'website.php') echo 'globe';
                    elseif($current_page == 'content.php') echo 'file-alt';
                    elseif($current_page == 'banner.php') echo 'images';
                    else echo 'cog';
                ?>"></i>
                <?php 
                    if($current_page == 'dashboard.php') echo 'Dashboard - Tổng quan hệ thống';
                    elseif($current_page == 'users.php') echo 'Quản lý người dùng';
                    elseif($current_page == 'categories.php') echo 'Quản lý danh mục';
                    elseif($current_page == 'hangmuc.php') echo 'Quản lý hạng mục thi công';
                    elseif($current_page == 'congtrinh.php') echo 'Quản lý công trình';
                    elseif($current_page == 'website.php') echo 'Cấu hình website';
                    elseif($current_page == 'content.php') echo 'Quản lý nội dung';
                    elseif($current_page == 'banner.php') echo 'Quản lý banner';
                    else echo 'Admin Panel';
                ?>
            </div>
            
            <div class="user-dropdown">
                <div class="notification-badge" data-bs-toggle="dropdown">
                    <i class="fas fa-bell"></i>
                    <?php if($notification_count > 0): ?>
                    <span class="badge-count"><?php echo $notification_count; ?></span>
                    <?php endif; ?>
                </div>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><h6 class="dropdown-header">Thông báo</h6></li>
                    <li><a class="dropdown-item" href="#">
                        <small class="text-muted">Hôm nay</small><br>
                        <strong><?php echo $notification_count; ?></strong> cập nhật mới
                    </a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-center" href="#">Xem tất cả</a></li>
                </ul>
                
                <div class="user-info dropdown-toggle" data-bs-toggle="dropdown">
                    <img src="/project/uploads/avatar/<?php echo $admin['anh_dai_dien'] ?? 'default-avatar.png'; ?>" 
                         alt="Avatar"
                         onerror="this.src='/project/assets/img/default-avatar.png'">
                    <span><?php echo htmlspecialchars($admin['hoten'] ?? $_SESSION['user']); ?></span>
                </div>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="/project/admin/profile.php">
                        <i class="fas fa-user-circle"></i> Hồ sơ
                    </a></li>
                    <li><a class="dropdown-item" href="/project/admin/change-password.php">
                        <i class="fas fa-key"></i> Đổi mật khẩu
                    </a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="/project/admin/logout.php">
                        <i class="fas fa-sign-out-alt"></i> Đăng xuất
                    </a></li>
                </ul>
            </div>
        </div>
        
        <!-- CONTENT WRAPPER -->
        <div class="content-wrapper">