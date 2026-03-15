<?php
session_start();
include "../config/db.php";

if (!$conn) {
    die("Không thể kết nối CSDL!");
}

// Lấy thông tin cấu hình website (logo, tên website)
$sql_config = "SELECT * FROM cauhinhweb WHERE id = 1";
$result_config = mysqli_query($conn, $sql_config);
$config = mysqli_fetch_assoc($result_config);

// Nếu chưa có cấu hình, dùng mặc định
if(!$config) {
    $config = [
        'ten_website' => 'ProTrack',
        'logo' => '/project/uploads/logo/default-logo.png',
        'slogan' => 'Hệ thống theo dõi tiến độ dự án'
    ];
}

$error = "";

if (isset($_POST['dangnhap'])) {

    $username = $_POST['username'];
    $password = md5($_POST['password']);

    $sql = "SELECT * FROM users 
            WHERE username='$username' 
            AND password='$password' 
            AND trangthai=1";

    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);

        // LƯU SESSION
        $_SESSION['user'] = $row['username'];
        $_SESSION['role'] = $row['role_id'];
        $_SESSION['id']   = $row['id'];

        // PHÂN QUYỀN
        if ($row['role_id'] == 1) {
            header("Location: ../admin/dashboard.php");
        } else {
            header("Location: ../user/dashboard.php");
        }
        exit();
    } else {
        $error = "Sai tài khoản hoặc mật khẩu!";
    }
}
?>
    
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập | <?php echo htmlspecialchars($config['ten_website']); ?></title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link rel="icon" type="image/x-icon" href="<?php echo $config['favicon'] ?? '/project/assets/img/favicon.ico'; ?>">
    
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 0;
        }
        
        .login-container {
            max-width: 400px;
            width: 100%;
            padding: 20px;
        }
        
        .login-card {
            background: white;
            border-radius: 20px;
            padding: 40px 30px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.2);
            animation: fadeIn 0.5s ease;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .logo-container {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo-container img {
            max-height: 80px;
            max-width: 200px;
            object-fit: contain;
            margin-bottom: 15px;
        }
        
        /* Style cho input group */
        .input-group {
            margin-bottom: 20px;
            display: flex;
            align-items: stretch;
            width: 100%;
        }
        
        .input-group-text {
            background: #f8f9fa;
            border: 1px solid #e0e0e0;
            border-right: none;
            border-radius: 10px 0 0 10px;
            padding: 12px 15px;
            display: flex;
            align-items: center;
        }
        
        .input-group .form-control {
            border: 1px solid #e0e0e0;
            border-left: none;
            border-radius: 0 10px 10px 0;
            padding: 12px 15px;
            font-size: 14px;
            flex: 1;
        }
        
        .input-group .form-control:focus {
            outline: none;
            border-color: #667eea;
        }
        
        /* Style cho password field với icon mắt */
        .password-input-group {
            display: flex;
            align-items: stretch;
            width: 100%;
            margin-bottom: 20px;
        }
        
        .password-input-group .input-group-text {
            background: #f8f9fa;
            border: 1px solid #e0e0e0;
            border-right: none;
            border-radius: 10px 0 0 10px;
            padding: 12px 15px;
            display: flex;
            align-items: center;
        }
        
        .password-wrapper {
            position: relative;
            flex: 1;
            display: flex;
        }
        
        .password-input {
            border: 1px solid #e0e0e0;
            border-left: none;
            border-right: none;
            padding: 12px 40px 12px 15px;
            font-size: 14px;
            width: 100%;
            height: 100%;
            border-radius: 0;
        }
        
        .password-input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6c757d;
            background: white;
            border: none;
            padding: 0 5px;
            z-index: 5;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .toggle-password:hover {
            color: #667eea;
        }
        
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
            font-size: 16px;
            width: 100%;
            margin-top: 10px;
            transition: all 0.3s;
            cursor: pointer;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }
        
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
            border-radius: 10px;
            padding: 12px;
            margin-bottom: 20px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-error i {
            color: #dc2626;
            font-size: 16px;
        }
        
        .footer-links {
            text-align: center;
            margin-top: 20px;
        }
        
        .footer-links a {
            color: #6b7280;
            text-decoration: none;
            font-size: 13px;
            transition: color 0.3s;
        }
        
        .footer-links a:hover {
            color: #667eea;
        }
        
        .footer-links span {
            color: #e0e0e0;
            margin: 0 8px;
        }
        
        /* Hide number input spinners for fake fields */
        input[type=number]::-webkit-inner-spin-button, 
        input[type=number]::-webkit-outer-spin-button { 
            -webkit-appearance: none; 
            margin: 0; 
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <!-- Logo và thông tin website -->
            <div class="logo-container">
                <?php if(!empty($config['logo'])): ?>
                <img src="<?php echo $config['logo']; ?>" alt="Logo">
                <?php else: ?>
                <i class="fas fa-hard-hat fa-3x text-primary mb-3"></i>
                <?php endif; ?>
            <h2>Đăng nhập</h2>
            <p>Tham gia quản lý dự án với ProTrack</p>
            </div>
            
            <!-- Hiển thị lỗi -->
            <?php if ($error != ""): ?>
            <div class="alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error; ?>
            </div>
            <?php endif; ?>
            
            <!-- Form đăng nhập -->
            <form method="POST" autocomplete="off">
                <!-- Fake fields để tránh autocomplete -->
                <input type="text" name="fakeuser" style="display:none">
                <input type="password" name="fakepass" style="display:none">
                
                <!-- Tên đăng nhập -->
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                    <input type="text" name="username" class="form-control" 
                           placeholder="Tên đăng nhập" required 
                           autocomplete="off" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                </div>
                
                <!-- Mật khẩu với con mắt hiển thị - CẤU TRÚC ĐÃ SỬA -->
                <div class="password-input-group">
                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                    <div class="password-wrapper">
                        <input type="password" name="password" id="password" class="password-input" 
                               placeholder="Mật khẩu" required autocomplete="new-password">
                        <span class="toggle-password" onclick="togglePasswordVisibility()">
                            <i class="fas fa-eye" id="toggleIcon"></i>
                        </span>
                    </div>
                </div>
                
                <!-- Nút đăng nhập -->
                <button type="submit" name="dangnhap" class="btn-login">
                    <i class="fas fa-sign-in-alt me-2"></i>Đăng nhập
                </button>
            </form>
            
            <!-- Liên kết phụ -->
            <div class="footer-links">
                <a href="../home.php"><i class="fas fa-home me-1"></i>Trang chủ</a>
                <span>|</span>
                <a href="dangky.php"><i class="fas fa-user-plus me-1"></i>Đăng ký</a>
            </div>
        </div>
    </div>
    
    <!-- JavaScript cho con mắt hiển thị mật khẩu -->
    <script>
        function togglePasswordVisibility() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }
        
        // Thêm sự kiện enter để submit form
        document.getElementById('password').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                document.querySelector('button[name="dangnhap"]').click();
            }
        });
    </script>
    
    <!-- Bootstrap JS (nếu cần) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>