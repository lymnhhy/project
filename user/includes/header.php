<?php
// user/includes/header.php
session_start();

// Đường dẫn gốc
define('BASE_URL', '/project');
define('ROOT_PATH', $_SERVER['DOCUMENT_ROOT'] . '/project');

// Include database
require_once ROOT_PATH . '/config/db.php';

// Kiểm tra kết nối database
if (!$conn) {
    die("Lỗi kết nối database: " . mysqli_connect_error());
}

// Kiểm tra đăng nhập
if (!isset($_SESSION['user']) || !isset($_SESSION['id']) || !isset($_SESSION['role'])) {
    header("Location: " . BASE_URL . "/guest.php");
    exit();
}

// Kiểm tra quyền User
if ($_SESSION['role'] != 2) {
    if ($_SESSION['role'] == 1) {
        header("Location: " . BASE_URL . "/admin/dashboard.php");
    } else {
        header("Location: " . BASE_URL . "/guest.php");
    }
    exit();
}

// Lấy thông tin user hiện tại
$user_id = (int)$_SESSION['id'];
$username = mysqli_real_escape_string($conn, $_SESSION['user']);

$sql_user = "SELECT * FROM users WHERE id = '$user_id' AND username = '$username' AND trangthai = 1";
$result_user = mysqli_query($conn, $sql_user);

if (!$result_user) {
    die("Lỗi truy vấn: " . mysqli_error($conn));
}

if (mysqli_num_rows($result_user) == 0) {
    session_destroy();
    header("Location: " . BASE_URL . "/guest.php");
    exit();
}

$current_user = mysqli_fetch_assoc($result_user);
$current_page = basename($_SERVER['PHP_SELF']);
$page_title = isset($page_title) ? $page_title : '';

// Hàm helper
function formatDate($date) {
    if (empty($date) || $date == '0000-00-00') return 'Chưa cập nhật';
    return date('d/m/Y', strtotime($date));
}

function formatDateTime($datetime) {
    if (empty($datetime)) return 'Chưa cập nhật';
    return date('d/m/Y H:i', strtotime($datetime));
}

function formatMoney($amount) {
    if (!$amount) return '0 đ';
    return number_format($amount, 0, ',', '.') . ' đ';
}

function getProgressColor($percent) {
    $percent = (int)$percent;
    if ($percent < 30) return 'danger';
    if ($percent < 70) return 'warning';
    if ($percent < 100) return 'info';
    return 'success';
}

function getStatusBadge($trangthai) {
    switch(strtolower($trangthai)) {
        case 'chưa thi công':
        case 'chuathi công':
            return 'secondary';
        case 'đang thi công':
        case 'dang thi cong':
            return 'warning';
        case 'hoàn thành':
        case 'hoan thanh':
            return 'success';
        case 'tạm dừng':
        case 'tam dung':
            return 'danger';
        default:
            return 'secondary';
    }
}

function tinhTienDoCongTrinh($conn, $congtrinh_id) {
    $congtrinh_id = (int)$congtrinh_id;
    $sql = "SELECT AVG(phan_tram_tien_do) as avg_progress 
            FROM hangmucthicong 
            WHERE congtrinh_id = '$congtrinh_id'";
    $result = mysqli_query($conn, $sql);
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        return round($row['avg_progress'] ?? 0);
    }
    return 0;
}

function countThongBaoChuaDoc($conn, $user_id) {
    $user_id = (int)$user_id;
    $check = mysqli_query($conn, "SHOW TABLES LIKE 'thongbao'");
    if (mysqli_num_rows($check) == 0) {
        return 0;
    }
    $sql = "SELECT COUNT(*) as total FROM thongbao WHERE user_id = '$user_id' AND da_doc = 0";
    $result = mysqli_query($conn, $sql);
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        return $row['total'];
    }
    return 0;
}
// Hàm ghi log hoạt động
function logActivity($conn, $user_id, $action, $detail) {
    $ip = $_SERVER['REMOTE_ADDR'];
    $thoi_gian = date('Y-m-d H:i:s');
    
    // Kiểm tra bảng lichsuhoatdong có tồn tại không
    $check = mysqli_query($conn, "SHOW TABLES LIKE 'lichsuhoatdong'");
    if(mysqli_num_rows($check) == 0) {
        // Tạo bảng nếu chưa có
        $sql_create = "CREATE TABLE IF NOT EXISTS `lichsuhoatdong` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) DEFAULT NULL,
            `hanh_dong` varchar(200) DEFAULT NULL,
            `chi_tiet` text DEFAULT NULL,
            `ip_address` varchar(50) DEFAULT NULL,
            `thoi_gian` datetime DEFAULT current_timestamp(),
            PRIMARY KEY (`id`),
            KEY `user_id` (`user_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
        mysqli_query($conn, $sql_create);
    }
    
    $sql = "INSERT INTO lichsuhoatdong (user_id, hanh_dong, chi_tiet, ip_address, thoi_gian) 
            VALUES ('$user_id', '$action', '$detail', '$ip', '$thoi_gian')";
    @mysqli_query($conn, $sql);
}
$so_thong_bao = countThongBaoChuaDoc($conn, $user_id);
// Thêm vào includes/header.php
function timeAgo($time) {
    if(empty($time)) return 'Không xác định';
    
    $time = strtotime($time);
    $now = time();
    $diff = $now - $time;
    
    if($diff < 60) {
        return 'Vài giây trước';
    } elseif($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . ' phút trước';
    } elseif($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' giờ trước';
    } elseif($diff < 2592000) {
        $days = floor($diff / 86400);
        return $days . ' ngày trước';
    } elseif($diff < 31536000) {
        $months = floor($diff / 2592000);
        return $months . ' tháng trước';
    } else {
        return date('d/m/Y', $time);
    }
}
// Thêm vào includes/header.php

function getTrangThaiByID($id) {
    switch($id) {
        case 1: return 'Chưa thi công';
        case 2: return 'Đang thi công';
        case 3: return 'Hoàn thành';
        default: return 'Không xác định';
    }
}

function getStatusBadgeByID($id) {
    switch($id) {
        case 1: return 'secondary';
        case 2: return 'warning';
        case 3: return 'success';
        default: return 'secondary';
    }
}

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="ProTrack - Hệ thống theo dõi tiến độ dự án chuyên nghiệp">
    <meta name="author" content="ProTrack">
    
    <title><?php echo htmlspecialchars($page_title); ?> | ProTrack</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?php echo BASE_URL; ?>/assets/img/favicon.ico">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.bootstrap5.min.css">
    
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    
    <!-- Select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    <!-- jQuery trước -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Bootstrap sau jQuery -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Các script khác -->
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Flatpickr -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f7fb;
            color: #333;
            overflow-x: hidden;
        }
        
        /* Sidebar */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 280px;
            background: linear-gradient(180deg, #1a2639 0%, #0f172a 100%);
            color: #fff;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 1030;
            overflow-y: auto;
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.1);
        }
        
        .sidebar::-webkit-scrollbar {
            width: 5px;
        }
        
        .sidebar::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05);
        }
        
        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 5px;
        }
        
        .sidebar .brand {
            padding: 25px 24px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 20px;
        }
        
        .sidebar .brand h3 {
            color: #fff;
            font-weight: 700;
            font-size: 24px;
            margin: 0;
        }
        
        .sidebar .brand h3 i {
            color: #fbbf24;
            margin-right: 10px;
            font-size: 28px;
        }
        
        .sidebar .user-info {
            text-align: center;
            padding: 24px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 20px;
        }
        
        .sidebar .user-info .avatar-wrapper {
            position: relative;
            width: 100px;
            height: 100px;
            margin: 0 auto 15px;
        }
        
        .sidebar .user-info .avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            border: 3px solid #fbbf24;
            padding: 3px;
            object-fit: cover;
            background: #fff;
            transition: transform 0.3s;
        }
        
        .sidebar .user-info .status {
            position: absolute;
            bottom: 5px;
            right: 5px;
            width: 15px;
            height: 15px;
            background: #10b981;
            border: 2px solid #fff;
            border-radius: 50%;
        }
        
        .sidebar .user-info h5 {
            color: #fff;
            font-weight: 600;
            font-size: 16px;
            margin-bottom: 5px;
        }
        
        .sidebar .user-info p {
            color: rgba(255, 255, 255, 0.6);
            font-size: 13px;
            margin: 0;
            word-break: break-word;
        }
        
        .sidebar .user-info p i {
            width: 18px;
            color: #fbbf24;
        }
        
        .sidebar .nav-section {
            padding: 16px 24px 8px;
            color: rgba(255, 255, 255, 0.4);
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .sidebar .nav-item {
            margin: 2px 8px;
        }
        
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.7);
            padding: 12px 16px;
            display: flex;
            align-items: center;
            border-radius: 10px;
            transition: all 0.3s;
            font-size: 14px;
            font-weight: 500;
        }
        
        .sidebar .nav-link:hover {
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
            transform: translateX(5px);
        }
        
        .sidebar .nav-link.active {
            background: rgba(251, 191, 36, 0.15);
            color: #fbbf24;
        }
        
        .sidebar .nav-link i {
            width: 24px;
            margin-right: 12px;
            font-size: 18px;
            text-align: center;
        }
        
        .sidebar .nav-link .badge {
            margin-left: auto;
            background: #fbbf24;
            color: #000;
            font-size: 10px;
            padding: 3px 6px;
        }
        
        /* Main Content */
        .main-content {
            margin-left: 280px;
            min-height: 100vh;
            transition: all 0.3s;
            display: flex;
            flex-direction: column;
        }
        
        /* Top Navbar */
        .top-navbar {
            background: #fff;
            padding: 12px 24px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.03);
            position: sticky;
            top: 0;
            z-index: 1020;
            margin-bottom: 24px;
        }
        
        .top-navbar .navbar-toggler {
            border: none;
            color: #64748b;
            font-size: 20px;
            padding: 8px;
            border-radius: 8px;
            transition: all 0.3s;
        }
        
        .top-navbar .navbar-toggler:hover {
            background: #f1f5f9;
            color: #1e293b;
        }
        
        .top-navbar .search-box {
            position: relative;
            width: 320px;
        }
        
        .top-navbar .search-box i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 16px;
            pointer-events: none;
        }
        
        .top-navbar .search-box input {
            width: 100%;
            padding: 10px 16px 10px 45px;
            border: 1px solid #e2e8f0;
            border-radius: 30px;
            font-size: 14px;
            transition: all 0.3s;
            background: #f8fafc;
        }
        
        .top-navbar .search-box input:focus {
            outline: none;
            border-color: #fbbf24;
            background: #fff;
            box-shadow: 0 0 0 3px rgba(251, 191, 36, 0.1);
        }
        
        .top-navbar .nav-item .nav-link {
            color: #64748b;
            padding: 8px 12px;
            border-radius: 8px;
            transition: all 0.3s;
            position: relative;
        }
        
        .top-navbar .nav-item .nav-link:hover {
            background: #f1f5f9;
            color: #1e293b;
        }
        
        .top-navbar .nav-item .nav-link .badge {
            position: absolute;
            top: 2px;
            right: 2px;
            background: #ef4444;
            color: #fff;
            font-size: 9px;
            padding: 2px 5px;
            border-radius: 10px;
            min-width: 18px;
        }
        
        .top-navbar .dropdown-menu {
            border: none;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            border-radius: 12px;
            padding: 8px;
            min-width: 240px;
            margin-top: 10px;
        }
        
        .top-navbar .dropdown-item {
            padding: 10px 16px;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .top-navbar .dropdown-item:hover {
            background: #f1f5f9;
        }
        
        .top-navbar .dropdown-item i {
            width: 20px;
            margin-right: 10px;
            color: #64748b;
        }
        
        .top-navbar .dropdown-divider {
            margin: 8px 0;
            border-color: #e2e8f0;
        }
        
        .top-navbar .avatar-sm {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid transparent;
            transition: all 0.3s;
        }
        
        .top-navbar .avatar-sm:hover {
            border-color: #fbbf24;
        }
        
        /* Content Wrapper */
        .content-wrapper {
            flex: 1;
            padding: 0 24px 24px;
        }
        
        /* Page Header */
        .page-header {
            margin-bottom: 24px;
        }
        
        .page-header h4 {
            font-size: 24px;
            font-weight: 600;
            color: #1e293b;
            margin: 0;
        }
        
        .page-header .breadcrumb {
            background: transparent;
            padding: 0;
            margin: 0;
        }
        
        .page-header .breadcrumb-item {
            font-size: 14px;
        }
        
        .page-header .breadcrumb-item a {
            color: #64748b;
            text-decoration: none;
        }
        
        .page-header .breadcrumb-item a:hover {
            color: #fbbf24;
        }
        
        .page-header .breadcrumb-item.active {
            color: #1e293b;
            font-weight: 500;
        }
        
        /* Cards */
        .card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02);
            transition: all 0.3s;
            margin-bottom: 24px;
            background: #fff;
        }
        
        .card:hover {
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.05);
        }
        
        .card-header {
            background: transparent;
            border-bottom: 1px solid #f1f5f9;
            padding: 20px 24px;
            font-weight: 600;
            color: #1e293b;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-radius: 16px 16px 0 0 !important;
        }
        
        .card-header i {
            color: #fbbf24;
            margin-right: 8px;
        }
        
        .card-body {
            padding: 24px;
        }
        
        /* Progress */
        .progress {
            height: 8px;
            border-radius: 4px;
            background: #f1f5f9;
            overflow: hidden;
        }
        
        .progress-bar {
            border-radius: 4px;
            transition: width 0.6s ease;
        }
        
        .progress-bar.bg-success { background: #10b981 !important; }
        .progress-bar.bg-warning { background: #fbbf24 !important; }
        .progress-bar.bg-danger { background: #ef4444 !important; }
        .progress-bar.bg-info { background: #3b82f6 !important; }
        
        /* Badges */
        .badge {
            padding: 6px 12px;
            border-radius: 30px;
            font-weight: 500;
            font-size: 11px;
        }
        
        .badge.bg-success { background: #10b981 !important; }
        .badge.bg-warning { background: #fbbf24 !important; color: #000; }
        .badge.bg-danger { background: #ef4444 !important; }
        .badge.bg-info { background: #3b82f6 !important; }
        .badge.bg-secondary { background: #94a3b8 !important; }
        
        /* Buttons */
        .btn {
            padding: 8px 20px;
            border-radius: 30px;
            font-weight: 500;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: #fbbf24;
            border-color: #fbbf24;
            color: #000;
        }
        
        .btn-primary:hover {
            background: #f59e0b;
            border-color: #f59e0b;
            color: #000;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(251, 191, 36, 0.3);
        }
        
        .btn-outline-primary {
            border-color: #fbbf24;
            color: #000;
        }
        
        .btn-outline-primary:hover {
            background: #fbbf24;
            border-color: #fbbf24;
            color: #000;
            transform: translateY(-2px);
        }
        
        .btn-sm {
            padding: 5px 12px;
            font-size: 12px;
        }
        
        .btn-group .btn {
            border-radius: 8px;
            margin: 0 2px;
        }
        
        /* Tables */
        .table {
            margin: 0;
        }
        
        .table thead th {
            background: #f8fafc;
            border-bottom: 2px solid #e2e8f0;
            color: #475569;
            font-weight: 600;
            font-size: 13px;
            padding: 16px;
            white-space: nowrap;
        }
        
        .table tbody td {
            padding: 16px;
            vertical-align: middle;
            border-bottom: 1px solid #f1f5f9;
            color: #1e293b;
            font-size: 14px;
        }
        
        .table tbody tr:hover {
            background: #f8fafc;
        }
        
        .table .btn-group {
            opacity: 0.7;
            transition: opacity 0.3s;
        }
        
        .table tr:hover .btn-group {
            opacity: 1;
        }
        
        /* Forms */
        .form-label {
            font-weight: 500;
            color: #475569;
            font-size: 14px;
            margin-bottom: 6px;
        }
        
        .form-control, .form-select {
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 10px 14px;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #fbbf24;
            box-shadow: 0 0 0 3px rgba(251, 191, 36, 0.1);
            outline: none;
        }
        
        /* Alerts */
        .alert {
            border: none;
            border-radius: 12px;
            padding: 14px 20px;
            font-size: 14px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #d1fae5;
            color: #065f46;
        }
        
        .alert-danger {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .alert-warning {
            background: #fef3c7;
            color: #92400e;
        }
        
        .alert-info {
            background: #dbeafe;
            color: #1e40af;
        }
        
        /* Modal */
        .modal-content {
            border: none;
            border-radius: 20px;
        }
        
        .modal-header {
            background: #f8fafc;
            border-bottom: 1px solid #f1f5f9;
            padding: 20px 24px;
            border-radius: 20px 20px 0 0;
        }
        
        .modal-body {
            padding: 24px;
        }
        
        .modal-footer {
            border-top: 1px solid #f1f5f9;
            padding: 20px 24px;
        }
        
        /* Footer */
        .footer {
            background: #fff;
            padding: 20px 24px;
            border-top: 1px solid #f1f5f9;
            margin-top: auto;
        }
        
        .footer p {
            margin: 0;
            color: #64748b;
            font-size: 13px;
        }
        
        /* Responsive */
        @media (max-width: 991.98px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.show {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0;
            }
            .top-navbar .search-box {
                width: 240px;
            }
        }
        
        @media (max-width: 575.98px) {
            .top-navbar {
                padding: 12px 16px;
            }
            .top-navbar .search-box {
                display: none;
            }
            .content-wrapper {
                padding: 0 16px 16px;
            }
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .fade-in {
            animation: fadeIn 0.3s ease-out;
        }
        
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 2px solid #f3f3f3;
            border-top-color: #fbbf24;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="brand">
            <h3><i class="fas fa-hard-hat"></i> ProTrack</h3>
        </div>
        
        <div class="user-info">
            <div class="avatar-wrapper">
                <img src="<?php 
                    $avatar = !empty($current_user['anh_dai_dien']) ? $current_user['anh_dai_dien'] : 'default-avatar.png';
                    echo BASE_URL . '/uploads/avatar/' . $avatar; 
                ?>" 
                class="avatar" 
                alt="Avatar"
                onerror="this.src='<?php echo BASE_URL; ?>/assets/img/default-avatar.png'">
                <span class="status"></span>
            </div>
            <h5><?php echo htmlspecialchars($current_user['hoten']); ?></h5>
            <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($current_user['email']); ?></p>
            <p><i class="fas fa-phone"></i> <?php echo !empty($current_user['sdt']) ? htmlspecialchars($current_user['sdt']) : 'Chưa cập nhật'; ?></p>
        </div>
        
        <!-- MAIN MENU -->
        <div class="nav-section">TRANG CHỦ</div>
        
        <div class="nav-item">
            <a href="<?php echo BASE_URL; ?>/user/dashboard.php" 
               class="nav-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
        </div>
        
        <!-- QUẢN LÝ CÔNG TRÌNH -->
        <div class="nav-section">QUẢN LÝ CÔNG TRÌNH</div>
        
        <div class="nav-item">
            <a href="<?php echo BASE_URL; ?>/user/congtrinh/index.php" 
               class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/user/congtrinh/') !== false && $current_page != 'add.php' && $current_page != 'edit.php' ? 'active' : ''; ?>">
                <i class="fas fa-building"></i> Danh sách công trình
            </a>
        </div>
        
        <div class="nav-item">
            <a href="<?php echo BASE_URL; ?>/user/congtrinh/add.php" 
               class="nav-link <?php echo $current_page == 'add.php' && strpos($_SERVER['REQUEST_URI'], 'congtrinh') !== false ? 'active' : ''; ?>">
                <i class="fas fa-plus-circle"></i> Thêm công trình
            </a>
        </div>
        
        <div class="nav-item">
            <a href="<?php echo BASE_URL; ?>/user/congtrinh/progress.php" 
               class="nav-link <?php echo $current_page == 'progress.php' && strpos($_SERVER['REQUEST_URI'], 'congtrinh') !== false ? 'active' : ''; ?>">
                <i class="fas fa-chart-line"></i> Tiến độ công trình
            </a>
        </div>
        
        <!-- QUẢN LÝ HẠNG MỤC -->
        <div class="nav-section">QUẢN LÝ HẠNG MỤC</div>
        
        <div class="nav-item">
            <a href="<?php echo BASE_URL; ?>/user/hangmuc/index.php" 
               class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/user/hangmuc/') !== false && $current_page != 'add.php' && $current_page != 'edit.php' && $current_page != 'update.php' && $current_page != 'history.php' ? 'active' : ''; ?>">
                <i class="fas fa-tasks"></i> Danh sách hạng mục
            </a>
        </div>
        
        <div class="nav-item">
            <a href="<?php echo BASE_URL; ?>/user/hangmuc/add.php" 
               class="nav-link <?php echo $current_page == 'add.php' && strpos($_SERVER['REQUEST_URI'], 'hangmuc') !== false ? 'active' : ''; ?>">
                <i class="fas fa-plus-circle"></i> Thêm hạng mục
            </a>
        </div>
        
        <div class="nav-item">
            <a href="<?php echo BASE_URL; ?>/user/hangmuc/update.php" 
               class="nav-link <?php echo $current_page == 'update.php' && strpos($_SERVER['REQUEST_URI'], 'hangmuc') !== false ? 'active' : ''; ?>">
                <i class="fas fa-percent"></i> Cập nhật tiến độ
            </a>
        </div>
        
        <div class="nav-item">
            <a href="<?php echo BASE_URL; ?>/user/hangmuc/history.php" 
               class="nav-link <?php echo $current_page == 'history.php' && strpos($_SERVER['REQUEST_URI'], 'hangmuc') !== false ? 'active' : ''; ?>">
                <i class="fas fa-history"></i> Lịch sử cập nhật
            </a>
        </div>
        <div class="nav-section">THEO DÕI TIẾN ĐỘ</div>

<!-- Theo dõi tiến độ tổng thể -->
<div class="nav-item">
    <a href="<?php echo BASE_URL; ?>/user/tiendo/theo-doi.php" 
       class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/user/tiendo/theo-doi') !== false ? 'active' : ''; ?>">
        <i class="fas fa-eye"></i> Theo dõi tiến độ
    </a>
</div>

<!-- Cập nhật tiến độ nhanh -->
<div class="nav-item">
    <a href="<?php echo BASE_URL; ?>/user/tiendo/cap-nhat-nhanh.php" 
       class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/user/tiendo/cap-nhat-nhanh') !== false ? 'active' : ''; ?>">
        <i class="fas fa-bolt"></i> Cập nhật nhanh
    </a>
</div>
        <!-- BÁO CÁO & THỐNG KÊ -->
        <div class="nav-section">BÁO CÁO & THỐNG KÊ</div>
        
        <div class="nav-item">
            <a href="<?php echo BASE_URL; ?>/user/baocao/index.php" 
               class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/user/baocao/') !== false && $current_page != 'export.php' && $current_page != 'congtrinh.php' ? 'active' : ''; ?>">
                <i class="fas fa-chart-pie"></i> Báo cáo tổng hợp
            </a>
        </div>
        
        <div class="nav-item">
            <a href="<?php echo BASE_URL; ?>/user/baocao/congtrinh.php" 
               class="nav-link <?php echo $current_page == 'congtrinh.php' && strpos($_SERVER['REQUEST_URI'], 'baocao') !== false ? 'active' : ''; ?>">
                <i class="fas fa-building"></i> Báo cáo theo công trình
            </a>
        </div>
        
        <div class="nav-item">
            <a href="<?php echo BASE_URL; ?>/user/baocao/export.php" 
               class="nav-link <?php echo $current_page == 'export.php' && strpos($_SERVER['REQUEST_URI'], 'baocao') !== false ? 'active' : ''; ?>">
                <i class="fas fa-file-excel"></i> Xuất báo cáo Excel
            </a>
        </div>
        
        <!-- QUẢN LÝ TÀI KHOẢN -->
        <div class="nav-section">TÀI KHOẢN</div>
        
        <div class="nav-item">
            <a href="<?php echo BASE_URL; ?>/user/profile/index.php" 
               class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/user/profile/') !== false && $current_page != 'edit.php' && $current_page != 'password.php' && $current_page != 'activity.php' ? 'active' : ''; ?>">
                <i class="fas fa-user-circle"></i> Thông tin cá nhân
            </a>
        </div>
        
        <div class="nav-item">
            <a href="<?php echo BASE_URL; ?>/user/profile/edit.php" 
               class="nav-link <?php echo $current_page == 'edit.php' && strpos($_SERVER['REQUEST_URI'], 'profile') !== false ? 'active' : ''; ?>">
                <i class="fas fa-edit"></i> Cập nhật thông tin
            </a>
        </div>
        
        <div class="nav-item">
            <a href="<?php echo BASE_URL; ?>/user/profile/password.php" 
               class="nav-link <?php echo $current_page == 'password.php' && strpos($_SERVER['REQUEST_URI'], 'profile') !== false ? 'active' : ''; ?>">
                <i class="fas fa-key"></i> Đổi mật khẩu
            </a>
        </div>
        
        <div class="nav-item">
            <a href="<?php echo BASE_URL; ?>/user/profile/activity.php" 
               class="nav-link <?php echo $current_page == 'activity.php' && strpos($_SERVER['REQUEST_URI'], 'profile') !== false ? 'active' : ''; ?>">
                <i class="fas fa-history"></i> Lịch sử hoạt động
            </a>
        </div>
        
        <!-- ĐĂNG XUẤT -->
        <div class="nav-item mt-3">
            <a href="<?php echo BASE_URL; ?>/auth/dangxuat.php" class="nav-link text-danger">
                <i class="fas fa-sign-out-alt"></i> Đăng xuất
            </a>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <!-- Top Navbar -->
        <nav class="top-navbar">
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <button class="btn btn-link d-lg-none navbar-toggler" type="button" onclick="toggleSidebar()">
                        <i class="fas fa-bars"></i>
                    </button>
                    
                    <div class="search-box d-none d-md-block">
                        <i class="fas fa-search"></i>
                        <input type="text" id="globalSearch" placeholder="Tìm kiếm công trình, hạng mục..." autocomplete="off">
                    </div>
                </div>
                
                <div class="d-flex align-items-center">
                    <!-- Notifications -->
                    <div class="dropdown me-2">
                        <button class="btn btn-link nav-link position-relative" type="button" data-bs-toggle="dropdown">
                            <i class="fas fa-bell fs-5"></i>
                            <?php if ($so_thong_bao > 0): ?>
                                <span class="badge"><?php echo $so_thong_bao; ?></span>
                            <?php endif; ?>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end">
                            <h6 class="dropdown-header">Thông báo</h6>
                            <div class="dropdown-item text-center text-muted py-3">
                                <i class="fas fa-bell-slash fa-2x mb-2"></i>
                                <p class="mb-0">Chưa có thông báo mới</p>
                            </div>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item text-center small" href="<?php echo BASE_URL; ?>/user/baocao/history.php">Xem tất cả</a>
                        </div>
                    </div>
                    
                    <!-- User Menu -->
                    <div class="dropdown">
                        <button class="btn btn-link nav-link d-flex align-items-center" type="button" data-bs-toggle="dropdown">
                            <img src="<?php 
                                $avatar = !empty($current_user['anh_dai_dien']) ? $current_user['anh_dai_dien'] : 'default-avatar.png';
                                echo BASE_URL . '/uploads/avatar/' . $avatar; 
                            ?>" 
                            class="avatar-sm rounded-circle me-2" 
                            alt="Avatar"
                            onerror="this.src='<?php echo BASE_URL; ?>/assets/img/default-avatar.png'">
                            <span class="d-none d-md-inline"><?php echo htmlspecialchars($current_user['hoten']); ?></span>
                            <i class="fas fa-chevron-down ms-2"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/user/profile/index.php"><i class="fas fa-user-circle me-2"></i> Hồ sơ</a></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/user/profile/edit.php"><i class="fas fa-edit me-2"></i> Cập nhật thông tin</a></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/user/profile/password.php"><i class="fas fa-key me-2"></i> Đổi mật khẩu</a></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/user/profile/activity.php"><i class="fas fa-history me-2"></i> Lịch sử hoạt động</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="<?php echo BASE_URL; ?>/auth/dangxuat.php"><i class="fas fa-sign-out-alt me-2"></i> Đăng xuất</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>
        
        <!-- Content Wrapper -->
        <div class="content-wrapper">
            <!-- Page Header -->
            <div class="page-header d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h4><?php echo htmlspecialchars($page_title); ?></h4>
                </div>
                <div>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb" id="breadcrumb"></ol>
                    </nav>
                </div>
            </div>