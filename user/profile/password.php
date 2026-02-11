<?php
// user/profile/password.php
$page_title = 'Đổi mật khẩu';
require_once '../includes/header.php';

$user_id = $_SESSION['id'];
$error = $success = "";

if (isset($_POST['doimatkhau'])) {
    $password_cu = md5($_POST['password_cu']);
    $password_moi = $_POST['password_moi'];
    $password_xacnhan = $_POST['password_xacnhan'];
    
    // Kiểm tra mật khẩu cũ
    $check = mysqli_query($conn, "SELECT id FROM users WHERE id = '$user_id' AND password = '$password_cu'");
    
    if (mysqli_num_rows($check) == 0) {
        $error = "Mật khẩu cũ không đúng!";
    } elseif (strlen($password_moi) < 6) {
        $error = "Mật khẩu mới phải có ít nhất 6 ký tự!";
    } elseif ($password_moi != $password_xacnhan) {
        $error = "Xác nhận mật khẩu không khớp!";
    } else {
        $password_moi_md5 = md5($password_moi);
        $sql = "UPDATE users SET password = '$password_moi_md5' WHERE id = '$user_id'";
        
        if (mysqli_query($conn, $sql)) {
            $success = "Đổi mật khẩu thành công!";
        } else {
            $error = "Lỗi: " . mysqli_error($conn);
        }
    }
}
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">Đổi mật khẩu</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="index.php">Hồ sơ</a></li>
                <li class="breadcrumb-item active">Đổi mật khẩu</li>
            </ol>
        </nav>
    </div>

    <div class="row">
        <div class="col-md-6 mx-auto">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-key me-1"></i>
                    Đổi mật khẩu
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label class="form-label">Mật khẩu cũ <span class="text-danger">*</span></label>
                            <input type="password" name="password_cu" class="form-control" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Mật khẩu mới <span class="text-danger">*</span></label>
                            <input type="password" name="password_moi" class="form-control" required>
                            <small class="text-muted">Tối thiểu 6 ký tự</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Xác nhận mật khẩu <span class="text-danger">*</span></label>
                            <input type="password" name="password_xacnhan" class="form-control" required>
                        </div>
                        
                        <hr>
                        <button type="submit" name="doimatkhau" class="btn btn-primary">
                            <i class="fas fa-save"></i> Cập nhật mật khẩu
                        </button>
                        <a href="index.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Quay lại
                        </a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once '../includes/footer.php';
?>