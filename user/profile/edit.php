<?php
// user/profile/edit.php
$page_title = 'Cập nhật thông tin';
require_once '../includes/header.php';

$user_id = $_SESSION['id'];

// Lấy thông tin user
$sql = "SELECT * FROM users WHERE id = '$user_id'";
$result = mysqli_query($conn, $sql);
$user = mysqli_fetch_assoc($result);

if (isset($_POST['capnhat'])) {
    $hoten = mysqli_real_escape_string($conn, $_POST['hoten']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $sdt = mysqli_real_escape_string($conn, $_POST['sdt']);
    
    $sql_update = "UPDATE users SET 
                    hoten = '$hoten',
                    email = '$email',
                    sdt = '$sdt'
                    WHERE id = '$user_id'";
    
    if (mysqli_query($conn, $sql_update)) {
        $success = "Cập nhật thông tin thành công!";
        
        // Refresh thông tin
        $result = mysqli_query($conn, $sql);
        $user = mysqli_fetch_assoc($result);
    } else {
        $error = "Lỗi: " . mysqli_error($conn);
    }
}
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">Cập nhật thông tin</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="index.php">Hồ sơ</a></li>
                <li class="breadcrumb-item active">Cập nhật</li>
            </ol>
        </nav>
    </div>

    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-edit me-1"></i>
                    Cập nhật hồ sơ
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    <?php if (isset($success)): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label class="form-label">Họ và tên <span class="text-danger">*</span></label>
                            <input type="text" name="hoten" class="form-control" 
                                   value="<?php echo $user['hoten']; ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" 
                                   value="<?php echo $user['email']; ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Số điện thoại</label>
                            <input type="text" name="sdt" class="form-control" 
                                   value="<?php echo $user['sdt']; ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Tên đăng nhập</label>
                            <input type="text" class="form-control" value="<?php echo $user['username']; ?>" disabled>
                            <small class="text-muted">Không thể thay đổi tên đăng nhập</small>
                        </div>
                        
                        <hr>
                        <button type="submit" name="capnhat" class="btn btn-primary">
                            <i class="fas fa-save"></i> Cập nhật
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