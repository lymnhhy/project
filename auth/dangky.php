<?php
session_start();
include "../config/db.php";

// Lấy thông tin cấu hình website
$sql_config = "SELECT * FROM cauhinhweb WHERE id = 1";
$result_config = mysqli_query($conn, $sql_config);
$config = mysqli_fetch_assoc($result_config);

if(!$conn) {
    die("Không thể kết nối CSDL!");
}

$error = "";
$success = "";

if (isset($_POST['dangky'])) {

    // Lấy dữ liệu từ form và escape để chống SQL injection
    $username = mysqli_real_escape_string($conn, trim($_POST['username']));
    $password = $_POST['password']; // Giữ nguyên để xử lý sau
    $hoten    = mysqli_real_escape_string($conn, trim($_POST['hoten']));
    $email    = mysqli_real_escape_string($conn, trim($_POST['email']));
    $sdt      = mysqli_real_escape_string($conn, trim($_POST['sdt'] ?? ''));
    $gioi_tinh = mysqli_real_escape_string($conn, $_POST['gioi_tinh'] ?? '');
    $ngay_sinh = mysqli_real_escape_string($conn, $_POST['ngay_sinh'] ?? '');
    $dia_chi   = mysqli_real_escape_string($conn, trim($_POST['dia_chi'] ?? ''));
    
    // Mặc định
    $role_id = 2; // User thường
    $trangthai = 1; // Hoạt động
    
    // Validate dữ liệu
    $errors = [];
    
    // Kiểm tra username
    if(empty($username)) {
        $errors[] = "Tên đăng nhập không được để trống";
    } elseif(strlen($username) < 3) {
        $errors[] = "Tên đăng nhập phải có ít nhất 3 ký tự";
    } elseif(!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors[] = "Tên đăng nhập chỉ được chứa chữ cái, số và dấu gạch dưới";
    }
    
    // Kiểm tra mật khẩu
    if(empty($password)) {
        $errors[] = "Mật khẩu không được để trống";
    } elseif(strlen($password) < 6) {
        $errors[] = "Mật khẩu phải có ít nhất 6 ký tự";
    }
    
    // Kiểm tra họ tên
    if(empty($hoten)) {
        $errors[] = "Họ tên không được để trống";
    }
    
    // Kiểm tra email
    if(empty($email)) {
        $errors[] = "Email không được để trống";
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Email không hợp lệ";
    }
    
    // Kiểm tra số điện thoại (nếu có)
    if(!empty($sdt) && !preg_match('/^[0-9]{10,11}$/', $sdt)) {
        $errors[] = "Số điện thoại phải có 10-11 chữ số";
    }
    
    // Kiểm tra ngày sinh (nếu có)
    if(!empty($ngay_sinh) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $ngay_sinh)) {
        $errors[] = "Ngày sinh không đúng định dạng (YYYY-MM-DD)";
    }
    
    // Check trùng username
    $check = mysqli_query($conn, "SELECT id FROM users WHERE username='$username'");
    if (mysqli_num_rows($check) > 0) {
        $errors[] = "Tên đăng nhập đã tồn tại!";
    }
    
    // Check trùng email
    $check_email = mysqli_query($conn, "SELECT id FROM users WHERE email='$email'");
    if (mysqli_num_rows($check_email) > 0) {
        $errors[] = "Email đã được sử dụng!";
    }
    
    if(empty($errors)) {
        // Mã hóa mật khẩu - NÊN DÙNG password_hash() thay vì md5
        // $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $password_hash = md5($password); // Tạm dùng md5 cho tương thích với dữ liệu cũ
        
        // Câu INSERT với tất cả các cột trong bảng users
        $sql = "INSERT INTO users (
                    username, 
                    password, 
                    hoten, 
                    gioi_tinh, 
                    ngay_sinh, 
                    email, 
                    sdt, 
                    role_id, 
                    trangthai, 
                    dia_chi,
                    ngaytao
                ) VALUES (
                    '$username', 
                    '$password_hash', 
                    '$hoten', 
                    " . ($gioi_tinh ? "'$gioi_tinh'" : "NULL") . ",
                    " . ($ngay_sinh ? "'$ngay_sinh'" : "NULL") . ",
                    '$email', 
                    " . ($sdt ? "'$sdt'" : "NULL") . ",
                    '$role_id', 
                    '$trangthai',
                    " . ($dia_chi ? "'$dia_chi'" : "NULL") . ",
                    NOW()
                )";
        
        if (mysqli_query($conn, $sql)) {
            $new_user_id = mysqli_insert_id($conn);
            
            // Xử lý upload ảnh đại diện (nếu có)
            if(isset($_FILES['anh_dai_dien']) && $_FILES['anh_dai_dien']['error'] == 0) {
                $target_dir = "../uploads/avatar/";
                if (!file_exists($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }
                
                $file_extension = pathinfo($_FILES["anh_dai_dien"]["name"], PATHINFO_EXTENSION);
                $file_name = "user_" . $new_user_id . "_" . time() . "." . $file_extension;
                $target_file = $target_dir . $file_name;
                
                // Kiểm tra loại file
                $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
                if(in_array(strtolower($file_extension), $allowed_types)) {
                    if(move_uploaded_file($_FILES["anh_dai_dien"]["tmp_name"], $target_file)) {
                        // Cập nhật tên file vào database
                        $update_avatar = "UPDATE users SET anh_dai_dien = '$file_name' WHERE id = '$new_user_id'";
                        mysqli_query($conn, $update_avatar);
                    }
                }
            }
            
            // Log hoạt động đăng ký (nếu có bảng lichsuhoatdong)
            $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
            $log_sql = "INSERT INTO lichsuhoatdong (user_id, hanh_dong, chi_tiet, ip_address, thoi_gian) 
                        VALUES ('$new_user_id', 'Đăng ký tài khoản', 'Đăng ký tài khoản mới', '$ip', NOW())";
            mysqli_query($conn, $log_sql);
            
            $success = "Đăng ký thành công! Vui lòng đăng nhập.";
            
            // Chuyển hướng sau 2 giây
            header("refresh:2;url=dangnhap.php");
        } else {
            $error = "Lỗi đăng ký: " . mysqli_error($conn);
        }
    } else {
        $error = implode("<br>", $errors);
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký | <?php echo htmlspecialchars($config['ten_website'] ?? 'ProTrack'); ?></title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link rel="icon" type="image/x-icon" href="<?php echo $config['favicon'] ?? '/project/assets/img/favicon.ico'; ?>">
    
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 20px;
        }
        
        .register-container {
            max-width: 600px;
            width: 100%;
        }
        
        .register-card {
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
        
        .logo-container h2 {
            color: #2c3e50;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .logo-container p {
            color: #7f8c8d;
            font-size: 14px;
        }
        
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
            color: #667eea;
            width: 45px;
            justify-content: center;
        }
        
        .input-group .form-control,
        .input-group .form-select {
            border: 1px solid #e0e0e0;
            border-left: none;
            border-radius: 0 10px 10px 0;
            padding: 12px 15px;
            font-size: 14px;
            flex: 1;
            transition: all 0.3s;
        }
        
        .input-group .form-control:focus,
        .input-group .form-select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        /* Password field with eye icon */
        .password-input-group {
            display: flex;
            align-items: stretch;
            width: 100%;
            margin-bottom: 20px;
            position: relative;
        }
        
        .password-input-group .input-group-text {
            background: #f8f9fa;
            border: 1px solid #e0e0e0;
            border-right: none;
            border-radius: 10px 0 0 10px;
            padding: 12px 15px;
            display: flex;
            align-items: center;
            width: 45px;
            justify-content: center;
        }
        
        .password-wrapper {
            position: relative;
            flex: 1;
        }
        
        .password-input {
            border: 1px solid #e0e0e0;
            border-left: none;
            border-radius: 0 10px 10px 0;
            padding: 12px 40px 12px 15px;
            font-size: 14px;
            width: 100%;
            height: 100%;
            transition: all 0.3s;
        }
        
        .password-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6c757d;
            background: transparent;
            border: none;
            padding: 0;
            z-index: 10;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: color 0.3s;
        }
        
        .toggle-password:hover {
            color: #667eea;
        }
        
        .avatar-upload {
            margin-bottom: 20px;
            text-align: center;
        }
        
        .avatar-preview {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            border: 3px solid #667eea;
            margin: 0 auto 10px;
            overflow: hidden;
            cursor: pointer;
            position: relative;
        }
        
        .avatar-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .avatar-preview:hover::after {
            content: 'Chọn ảnh';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
        }
        
        .btn-register {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            padding: 14px;
            font-weight: 600;
            font-size: 16px;
            width: 100%;
            margin-top: 10px;
            transition: all 0.3s;
            cursor: pointer;
        }
        
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }
        
        .alert {
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 25px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideIn 0.3s ease;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        
        .alert-success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }
        
        .footer-links {
            text-align: center;
            margin-top: 25px;
            padding-top: 15px;
            border-top: 1px solid #e0e0e0;
        }
        
        .footer-links a {
            color: #6b7280;
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s;
            margin: 0 10px;
        }
        
        .footer-links a:hover {
            color: #667eea;
        }
        
        .password-strength {
            margin-top: 5px;
            margin-bottom: 15px;
        }
        
        .strength-bar {
            height: 4px;
            background: #e0e0e0;
            border-radius: 2px;
            overflow: hidden;
        }
        
        .strength-bar-fill {
            height: 100%;
            width: 0%;
            transition: width 0.3s, background 0.3s;
        }
        
        .text-muted {
            color: #6b7280;
            font-size: 12px;
            margin-top: 5px;
            display: block;
        }
        
        .row {
            display: flex;
            flex-wrap: wrap;
            margin: 0 -10px;
        }
        
        .col-md-6 {
            width: 50%;
            padding: 0 10px;
        }
    </style>
</head>
<body>

<div class="register-container">
    <div class="register-card">
        <!-- Logo và thông tin website -->
        <div class="logo-container">
            <?php if(!empty($config['logo'])): ?>
            <img src="<?php echo $config['logo']; ?>" alt="Logo">
            <?php else: ?>
            <i class="fas fa-hard-hat fa-3x text-primary mb-3"></i>
            <?php endif; ?>
            <h2>Đăng ký tài khoản</h2>
            <p>Tham gia quản lý dự án với ProTrack</p>
        </div>
        
        <!-- Hiển thị thông báo lỗi/thành công -->
        <?php if ($error != ""): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <div><?php echo $error; ?></div>
        </div>
        <?php endif; ?>
        
        <?php if ($success != ""): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <div>
                <?php echo $success; ?><br>
                <small>Đang chuyển hướng đến trang đăng nhập...</small>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Form đăng ký -->
        <form method="POST" action="" enctype="multipart/form-data" autocomplete="off" id="registerForm">
            <!-- Avatar upload -->
            <div class="avatar-upload">
                <div class="avatar-preview" onclick="document.getElementById('anh_dai_dien').click()">
                    <img id="avatarPreview" src="../img/img_jpg/avatar.jpg" alt="Avatar">
                </div>
                <small class="text-muted">Click để chọn ảnh đại diện (không bắt buộc)</small>
                <input type="file" name="anh_dai_dien" id="anh_dai_dien" accept="image/*" style="display: none;" onchange="previewAvatar(this)">
            </div>
            
            <div class="row">
                <!-- Cột trái -->
                <div class="col-md-6">
                    <!-- Tên đăng nhập -->
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                        <input type="text" name="username" class="form-control" 
                               placeholder="Tên đăng nhập *" required 
                               autocomplete="off" 
                               value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                               minlength="3">
                    </div>
                    
                    <!-- Mật khẩu -->
                    <div class="password-input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <div class="password-wrapper">
                            <input type="password" name="password" id="password" class="password-input" 
                                   placeholder="Mật khẩu *" required 
                                   autocomplete="new-password"
                                   minlength="6"
                                   oninput="checkPasswordStrength(this.value)">
                            <span class="toggle-password" onclick="togglePassword('password', this)">
                                <i class="fas fa-eye"></i>
                            </span>
                        </div>
                    </div>
                    
                    <!-- Họ tên -->
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-user-circle"></i></span>
                        <input type="text" name="hoten" class="form-control" 
                               placeholder="Họ và tên *" required 
                               autocomplete="off" 
                               value="<?php echo isset($_POST['hoten']) ? htmlspecialchars($_POST['hoten']) : ''; ?>">
                    </div>
                    
                    <!-- Giới tính -->
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-venus-mars"></i></span>
                        <select name="gioi_tinh" class="form-select">
                            <option value="">-- Chọn giới tính --</option>
                            <option value="nam" <?php echo (isset($_POST['gioi_tinh']) && $_POST['gioi_tinh'] == 'nam') ? 'selected' : ''; ?>>Nam</option>
                            <option value="nu" <?php echo (isset($_POST['gioi_tinh']) && $_POST['gioi_tinh'] == 'nu') ? 'selected' : ''; ?>>Nữ</option>
                            <option value="khac" <?php echo (isset($_POST['gioi_tinh']) && $_POST['gioi_tinh'] == 'khac') ? 'selected' : ''; ?>>Khác</option>
                        </select>
                    </div>
                </div>
                
                <!-- Cột phải -->
                <div class="col-md-6">
                    <!-- Email -->
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                        <input type="email" name="email" class="form-control" 
                               placeholder="Email *" required 
                               autocomplete="off" 
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>
                    
                    <!-- Số điện thoại -->
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-phone"></i></span>
                        <input type="tel" name="sdt" class="form-control" 
                               placeholder="Số điện thoại" 
                               autocomplete="off" 
                               value="<?php echo isset($_POST['sdt']) ? htmlspecialchars($_POST['sdt']) : ''; ?>"
                               pattern="[0-9]{10,11}">
                    </div>
                    
                    <!-- Ngày sinh -->
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-birthday-cake"></i></span>
                        <input type="date" name="ngay_sinh" class="form-control" 
                               placeholder="Ngày sinh"
                               value="<?php echo isset($_POST['ngay_sinh']) ? htmlspecialchars($_POST['ngay_sinh']) : ''; ?>">
                    </div>
                    
                    <!-- Địa chỉ -->
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span>
                        <input type="text" name="dia_chi" class="form-control" 
                               placeholder="Địa chỉ"
                               value="<?php echo isset($_POST['dia_chi']) ? htmlspecialchars($_POST['dia_chi']) : ''; ?>">
                    </div>
                </div>
            </div>
            
            <!-- Password strength indicator -->
            <div class="password-strength">
                <div class="strength-bar">
                    <div class="strength-bar-fill" id="strengthBar"></div>
                </div>
                <small class="text-muted" id="strengthText"></small>
            </div>
            
            <!-- Nút đăng ký -->
            <button type="submit" name="dangky" class="btn-register">
                <i class="fas fa-user-plus"></i> Đăng ký
            </button>
        </form>
        
        <!-- Liên kết phụ -->
        <div class="footer-links">
            <a href="../home.php"><i class="fas fa-home"></i> Trang chủ</a>
            <a href="dangnhap.php"><i class="fas fa-sign-in-alt"></i> Đăng nhập</a>
        </div>
    </div>
</div>

<script>
    // Toggle hiển thị mật khẩu
    function togglePassword(inputId, element) {
        const passwordInput = document.getElementById(inputId);
        const icon = element.querySelector('i');
        
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            passwordInput.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }
    
    // Preview avatar trước khi upload
    function previewAvatar(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('avatarPreview').src = e.target.result;
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
    
    // Kiểm tra độ mạnh mật khẩu
    function checkPasswordStrength(password) {
        const strengthBar = document.getElementById('strengthBar');
        const strengthText = document.getElementById('strengthText');
        
        let strength = 0;
        
        // Kiểm tra độ dài
        if (password.length >= 6) strength += 25;
        if (password.length >= 8) strength += 10;
        
        // Kiểm tra có chữ hoa
        if (/[A-Z]/.test(password)) strength += 20;
        
        // Kiểm tra có chữ thường
        if (/[a-z]/.test(password)) strength += 20;
        
        // Kiểm tra có số
        if (/\d/.test(password)) strength += 15;
        
        // Kiểm tra có ký tự đặc biệt
        if (/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password)) strength += 10;
        
        // Giới hạn strength trong khoảng 0-100
        strength = Math.min(strength, 100);
        
        // Cập nhật thanh strength
        strengthBar.style.width = strength + '%';
        
        // Đổi màu và text dựa trên strength
        if (strength < 30) {
            strengthBar.style.background = '#dc2626';
            strengthText.textContent = 'Mật khẩu yếu';
        } else if (strength < 60) {
            strengthBar.style.background = '#f59e0b';
            strengthText.textContent = 'Mật khẩu trung bình';
        } else if (strength < 80) {
            strengthBar.style.background = '#10b981';
            strengthText.textContent = 'Mật khẩu tốt';
        } else {
            strengthBar.style.background = '#059669';
            strengthText.textContent = 'Mật khẩu rất mạnh';
        }
    }
    
    // Validate form trước khi submit
    document.getElementById('registerForm').addEventListener('submit', function(e) {
        const password = document.getElementById('password').value;
        const username = document.querySelector('input[name="username"]').value;
        const email = document.querySelector('input[name="email"]').value;
        const hoten = document.querySelector('input[name="hoten"]').value;
        
        if (username.length < 3) {
            e.preventDefault();
            alert('Tên đăng nhập phải có ít nhất 3 ký tự!');
            return;
        }
        
        if (password.length < 6) {
            e.preventDefault();
            alert('Mật khẩu phải có ít nhất 6 ký tự!');
            return;
        }
        
        if (hoten.trim() === '') {
            e.preventDefault();
            alert('Họ tên không được để trống!');
            return;
        }
        
        if (!email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
            e.preventDefault();
            alert('Email không hợp lệ!');
            return;
        }
    });
</script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>