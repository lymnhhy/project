<?php
// user/includes/header.php
session_start();
include "../config/db.php";

// Kiểm tra đăng nhập
if (!isset($_SESSION['user'])) {
    header("Location: ../guest.php");
    exit();
}

// Kiểm tra quyền User
if ($_SESSION['role'] != 2) {
    if ($_SESSION['role'] == 1) {
        header("Location: ../admin/dashboard.php");
    } else {
        header("Location: ../guest.php");
    }
    exit();
}

// Lấy thông tin user hiện tại
$username = $_SESSION['user'];
$user_id = $_SESSION['id'];
$sql_user = "SELECT * FROM users WHERE id = '$user_id'";
$result_user = mysqli_query($conn, $sql_user);
$current_user = mysqli_fetch_assoc($result_user);

// Tên trang
$page_title = isset($page_title) ? $page_title : 'Dashboard';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - ProTrack User</title>
    
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
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f4f7fc;
        }
        
        /* Sidebar */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 260px;
            background: linear-gradient(180deg, #1e2b3c 0%, #0f172a 100%);
            color: white;
            transition: all 0.3s;
            z-index: 1000;
            overflow-y: auto;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        
        .sidebar .brand {
            padding: 25px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar .brand h3 {
            color: white;
            font-weight: 700;
            margin: 0;
        }
        
        .sidebar .brand h3 i {
            color: #ffc107;
            margin-right: 10px;
        }
        
        .sidebar .user-info {
            text-align: center;
            padding: 25px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar .user-info .avatar {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            border: 3px solid #ffc107;
            padding: 3px;
            margin-bottom: 15px;
            object-fit: cover;
            background: white;
        }
        
        .sidebar .user-info h5 {
            color: white;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .sidebar .user-info p {
            color: rgba(255,255,255,0.7);
            margin-bottom: 0;
            font-size: 13px;
        }
        
        .sidebar .nav-section {
            padding: 20px 20px 10px;
            color: rgba(255,255,255,0.5);
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .sidebar .nav-item {
            margin-bottom: 2px;
        }
        
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 25px;
            display: flex;
            align-items: center;
            transition: all 0.3s;
            border-left: 4px solid transparent;
        }
        
        .sidebar .nav-link:hover {
            background: rgba(255,255,255,0.1);
            color: white;
            border-left-color: #ffc107;
        }
        
        .sidebar .nav-link.active {
            background: rgba(255,193,7,0.2);
            color: white;
            border-left-color: #ffc107;
        }
        
        .sidebar .nav-link i {
            width: 25px;
            margin-right: 10px;
            font-size: 18px;
        }
        
        /* Main Content */
        .main-content {
            margin-left: 260px;
            padding: 20px;
            transition: all 0.3s;
        }
        
        /* Navbar */
        .navbar {
            background: white;
            padding: 15px 25px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 25px;
        }
        
        .navbar .search-box {
            position: relative;
            width: 300px;
        }
        
        .navbar .search-box input {
            width: 100%;
            padding: 10px 15px 10px 45px;
            border: 1px solid #e0e0e0;
            border-radius: 30px;
            outline: none;
            transition: all 0.3s;
        }
        
        .navbar .search-box input:focus {
            border-color: #ffc107;
            box-shadow: 0 0 0 3px rgba(255,193,7,0.1);
        }
        
        .navbar .search-box i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
        }
        
        .navbar .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
            object-fit: cover;
        }
        
        /* Cards */
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: all 0.3s;
            margin-bottom: 25px;
        }
        
        .card:hover {
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transform: translateY(-3px);
        }
        
        .card-header {
            background: white;
            border-bottom: 1px solid #f0f0f0;
            padding: 18px 25px;
            border-radius: 15px 15px 0 0 !important;
            font-weight: 600;
        }
        
        .card-body {
            padding: 25px;
        }
        
        /* Progress */
        .progress {
            height: 8px;
            border-radius: 4px;
            background: #f0f0f0;
        }
        
        .progress-bar {
            border-radius: 4px;
        }
        
        /* Badge */
        .badge {
            padding: 6px 12px;
            border-radius: 30px;
            font-weight: 500;
            font-size: 12px;
        }
        
        .badge.bg-success { background: #28a745 !important; }
        .badge.bg-warning { background: #ffc107 !important; color: #000; }
        .badge.bg-danger { background: #dc3545 !important; }
        .badge.bg-info { background: #17a2b8 !important; }
        .badge.bg-secondary { background: #6c757d !important; }
        
        /* Buttons */
        .btn {
            padding: 8px 20px;
            border-radius: 30px;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: #ffc107;
            border-color: #ffc107;
            color: #000;
        }
        
        .btn-primary:hover {
            background: #e0a800;
            border-color: #e0a800;
            color: #000;
        }
        
        .btn-outline-primary {
            border-color: #ffc107;
            color: #000;
        }
        
        .btn-outline-primary:hover {
            background: #ffc107;
            border-color: #ffc107;
            color: #000;
        }
        
        /* Table */
        .table {
            margin-bottom: 0;
        }
        
        .table thead th {
            border-bottom: 2px solid #f0f0f0;
            color: #555;
            font-weight: 600;
            font-size: 14px;
            padding: 15px;
        }
        
        .table tbody td {
            padding: 15px;
            vertical-align: middle;
            border-bottom: 1px solid #f0f0f0;
        }
        
        /* Forms */
        .form-control, .form-select {
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            padding: 10px 15px;
            transition: all 0.3s;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #ffc107;
            box-shadow: 0 0 0 3px rgba(255,193,7,0.1);
        }
        
        .form-label {
            font-weight: 500;
            color: #555;
            margin-bottom: 8px;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                left: -260px;
            }
            .sidebar.active {
                left: 0;
            }
            .main-content {
                margin-left: 0;
            }
        }
        
        /* Animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .fadeIn {
            animation: fadeIn 0.5s ease;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="brand">
            <h3><i class="fas fa-hard-hat"></i> ProTrack</h3>
        </div>
        
        <div class="user-info">
            <img src="../../uploads/avatar/<?php echo $current_user['anh_dai_dien'] ?? 'default-avatar.png'; ?>" 
                 class="avatar" alt="Avatar">
            <h5><?php echo $current_user['hoten']; ?></h5>
            <p><i class="fas fa-envelope me-2"></i><?php echo $current_user['email']; ?></p>
        </div>
        
        <div class="nav-section">MENU CHÍNH</div>
        
        <div class="nav-item">
            <a href="../dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
        </div>
        
        <div class="nav-section">QUẢN LÝ CÔNG TRÌNH</div>
        
        <div class="nav-item">
            <a href="../congtrinh/index.php" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'congtrinh') !== false ? 'active' : ''; ?>">
                <i class="fas fa-building"></i> Danh sách công trình
            </a>
        </div>
        
        <div class="nav-item">
            <a href="../congtrinh/add.php" class="nav-link">
                <i class="fas fa-plus-circle"></i> Thêm công trình
            </a>
        </div>
        
        <div class="nav-section">QUẢN LÝ HẠNG MỤC</div>
        
        <div class="nav-item">
            <a href="../hangmuc/index.php" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'hangmuc') !== false ? 'active' : ''; ?>">
                <i class="fas fa-tasks"></i> Hạng mục thi công
            </a>
        </div>
        
        <div class="nav-section">BÁO CÁO & GHI CHÚ</div>
        
        <div class="nav-item">
            <a href="../ghichu/index.php" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'ghichu') !== false ? 'active' : ''; ?>">
                <i class="fas fa-sticky-note"></i> Ghi chú thi công
            </a>
        </div>
        
        <div class="nav-item">
            <a href="../baocao/index.php" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'baocao') !== false ? 'active' : ''; ?>">
                <i class="fas fa-chart-bar"></i> Báo cáo tiến độ
            </a>
        </div>
        
        <div class="nav-section">TÀI KHOẢN</div>
        
        <div class="nav-item">
            <a href="../profile/index.php" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'profile') !== false ? 'active' : ''; ?>">
                <i class="fas fa-user-circle"></i> Thông tin cá nhân
            </a>
        </div>
        
        <div class="nav-item">
            <a href="../profile/password.php" class="nav-link">
                <i class="fas fa-key"></i> Đổi mật khẩu
            </a>
        </div>
        
        <div class="nav-item">
            <a href="../../auth/dangxuat.php" class="nav-link text-danger">
                <i class="fas fa-sign-out-alt"></i> Đăng xuất
            </a>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Navbar -->
        <nav class="navbar navbar-expand-lg">
            <div class="container-fluid">
                <button class="btn btn-link d-md-none" type="button" onclick="toggleSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
                
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="globalSearch" placeholder="Tìm kiếm công trình, hạng mục...">
                </div>
                
                <div class="d-flex align-items-center">
                    <div class="dropdown me-3">
                        <button class="btn btn-link position-relative" type="button" data-bs-toggle="dropdown">
                            <i class="fas fa-bell fs-5"></i>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                0
                            </span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><h6 class="dropdown-header">Thông báo</h6></li>
                            <li><a class="dropdown-item text-center text-muted" href="#">Không có thông báo mới</a></li>
                        </ul>
                    </div>
                    
                    <div class="dropdown">
                        <button class="btn btn-link dropdown-toggle d-flex align-items-center" type="button" data-bs-toggle="dropdown">
                            <img src="../../uploads/avatar/<?php echo $current_user['anh_dai_dien'] ?? 'default-avatar.png'; ?>" 
                                 class="avatar" alt="Avatar">
                            <span><?php echo $current_user['hoten']; ?></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="../profile/index.php">
                                <i class="fas fa-user-circle me-2"></i> Hồ sơ
                            </a></li>
                            <li><a class="dropdown-item" href="../profile/password.php">
                                <i class="fas fa-key me-2"></i> Đổi mật khẩu
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="../../auth/dangxuat.php">
                                <i class="fas fa-sign-out-alt me-2"></i> Đăng xuất
                            </a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>
        
        <!-- Page Content -->
        <div class="fadeIn">