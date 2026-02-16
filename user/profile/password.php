<?php
// profile/password.php
require_once '../includes/header.php';

$user_id = $_SESSION['id'];
$error = '';
$success = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $current_pass = $_POST['current_password'];
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];
    
    // Lấy mật khẩu hiện tại
    $sql = "SELECT password FROM users WHERE id = '$user_id'";
    $result = mysqli_query($conn, $sql);
    $user = mysqli_fetch_assoc($result);
    
    // Validate
    if(empty($current_pass)) {
        $error = 'Vui lòng nhập mật khẩu hiện tại';
    } elseif(md5($current_pass) != $user['password']) {
        $error = 'Mật khẩu hiện tại không đúng';
    } elseif(empty($new_pass)) {
        $error = 'Vui lòng nhập mật khẩu mới';
    } elseif(strlen($new_pass) < 6) {
        $error = 'Mật khẩu mới phải có ít nhất 6 ký tự';
    } elseif($new_pass != $confirm_pass) {
        $error = 'Mật khẩu xác nhận không khớp';
    } else {
        $new_pass_md5 = md5($new_pass);
        $sql_update = "UPDATE users SET password = '$new_pass_md5' WHERE id = '$user_id'";
        
        if(mysqli_query($conn, $sql_update)) {
            logActivity($conn, $user_id, 'Đổi mật khẩu', 'Thay đổi mật khẩu tài khoản');
            $_SESSION['success'] = 'Đổi mật khẩu thành công!';
            header('Location: index.php');
            exit();
        } else {
            $error = 'Lỗi: ' . mysqli_error($conn);
        }
    }
}
?>

<div class="content-wrapper">
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h4 class="mb-1">
                    <i class="fas fa-key me-2 text-warning"></i>
                    Đổi mật khẩu
                </h4>
                <p class="text-muted mb-0">Thay đổi mật khẩu để bảo vệ tài khoản</p>
            </div>
            <div class="mt-2 mt-sm-0">
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Quay lại
                </a>
            </div>
        </div>
    </div>

    <?php if($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-lock me-2 text-warning"></i>
                    Form đổi mật khẩu
                </div>
                <div class="card-body">
                    <form method="POST" id="changePasswordForm">
                        <!-- Mật khẩu hiện tại -->
                        <div class="mb-4">
                            <label class="form-label">
                                <i class="fas fa-lock me-2"></i>
                                Mật khẩu hiện tại <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-key"></i></span>
                                <input type="password" name="current_password" 
                                       class="form-control" 
                                       placeholder="Nhập mật khẩu hiện tại"
                                       required>
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword(this)">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Mật khẩu mới -->
                        <div class="mb-4">
                            <label class="form-label">
                                <i class="fas fa-lock me-2"></i>
                                Mật khẩu mới <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-key"></i></span>
                                <input type="password" name="new_password" 
                                       class="form-control" 
                                       placeholder="Nhập mật khẩu mới (ít nhất 6 ký tự)"
                                       required>
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword(this)">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="password-strength mt-2">
                                <div class="progress" style="height: 5px;">
                                    <div class="progress-bar" id="passwordStrength" style="width: 0%;"></div>
                                </div>
                                <small class="text-muted" id="strengthText"></small>
                            </div>
                        </div>

                        <!-- Xác nhận mật khẩu -->
                        <div class="mb-4">
                            <label class="form-label">
                                <i class="fas fa-lock me-2"></i>
                                Xác nhận mật khẩu <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-key"></i></span>
                                <input type="password" name="confirm_password" 
                                       class="form-control" 
                                       placeholder="Nhập lại mật khẩu mới"
                                       required>
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword(this)">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="mt-1">
                                <small class="text-muted" id="matchMessage"></small>
                            </div>
                        </div>

                        <!-- Yêu cầu mật khẩu -->
                        <div class="alert alert-info">
                            <h6 class="alert-heading"><i class="fas fa-info-circle me-2"></i>Yêu cầu mật khẩu:</h6>
                            <ul class="mb-0 small">
                                <li id="req-length">✓ Ít nhất 6 ký tự</li>
                                <li id="req-number">✓ Ít nhất 1 chữ số</li>
                                <li id="req-uppercase">✓ Ít nhất 1 chữ hoa</li>
                                <li id="req-lowercase">✓ Ít nhất 1 chữ thường</li>
                            </ul>
                        </div>

                        <!-- Buttons -->
                        <div class="text-center mt-4">
                            <button type="submit" class="btn btn-primary px-5">
                                <i class="fas fa-save me-2"></i>Cập nhật mật khẩu
                            </button>
                            <button type="reset" class="btn btn-secondary px-5">
                                <i class="fas fa-undo me-2"></i>Reset
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Toggle hiển thị mật khẩu
function togglePassword(btn) {
    const input = btn.previousElementSibling;
    const icon = btn.querySelector('i');
    
    if(input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// Kiểm tra độ mạnh mật khẩu
document.querySelector('input[name="new_password"]').addEventListener('input', function() {
    const password = this.value;
    const strengthBar = document.getElementById('passwordStrength');
    const strengthText = document.getElementById('strengthText');
    
    let strength = 0;
    
    // Kiểm tra độ dài
    if(password.length >= 6) strength += 25;
    // Kiểm tra có số
    if(/\d/.test(password)) strength += 25;
    // Kiểm tra có chữ hoa
    if(/[A-Z]/.test(password)) strength += 25;
    // Kiểm tra có chữ thường
    if(/[a-z]/.test(password)) strength += 25;
    
    strengthBar.style.width = strength + '%';
    
    if(strength < 50) {
        strengthBar.className = 'progress-bar bg-danger';
        strengthText.textContent = 'Mật khẩu yếu';
    } else if(strength < 75) {
        strengthBar.className = 'progress-bar bg-warning';
        strengthText.textContent = 'Mật khẩu trung bình';
    } else {
        strengthBar.className = 'progress-bar bg-success';
        strengthText.textContent = 'Mật khẩu mạnh';
    }
    
    // Cập nhật yêu cầu
    document.getElementById('req-length').innerHTML = password.length >= 6 ? 
        '<span class="text-success">✓ Ít nhất 6 ký tự</span>' : 
        '<span class="text-danger">✗ Ít nhất 6 ký tự</span>';
    
    document.getElementById('req-number').innerHTML = /\d/.test(password) ? 
        '<span class="text-success">✓ Ít nhất 1 chữ số</span>' : 
        '<span class="text-danger">✗ Ít nhất 1 chữ số</span>';
    
    document.getElementById('req-uppercase').innerHTML = /[A-Z]/.test(password) ? 
        '<span class="text-success">✓ Ít nhất 1 chữ hoa</span>' : 
        '<span class="text-danger">✗ Ít nhất 1 chữ hoa</span>';
    
    document.getElementById('req-lowercase').innerHTML = /[a-z]/.test(password) ? 
        '<span class="text-success">✓ Ít nhất 1 chữ thường</span>' : 
        '<span class="text-danger">✗ Ít nhất 1 chữ thường</span>';
});

// Kiểm tra mật khẩu khớp
document.querySelector('input[name="confirm_password"]').addEventListener('input', function() {
    const newPass = document.querySelector('input[name="new_password"]').value;
    const confirmPass = this.value;
    const matchMessage = document.getElementById('matchMessage');
    
    if(confirmPass === '') {
        matchMessage.innerHTML = '';
    } else if(newPass === confirmPass) {
        matchMessage.innerHTML = '<span class="text-success"><i class="fas fa-check-circle"></i> Mật khẩu khớp nhau</span>';
    } else {
        matchMessage.innerHTML = '<span class="text-danger"><i class="fas fa-exclamation-circle"></i> Mật khẩu không khớp</span>';
    }
});

// Validate form trước khi submit
document.getElementById('changePasswordForm').addEventListener('submit', function(e) {
    const newPass = document.querySelector('input[name="new_password"]').value;
    const confirmPass = document.querySelector('input[name="confirm_password"]').value;
    
    if(newPass !== confirmPass) {
        e.preventDefault();
        alert('Mật khẩu xác nhận không khớp!');
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>