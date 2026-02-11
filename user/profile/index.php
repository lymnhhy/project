<?php
// user/profile/index.php
$page_title = 'Thông tin cá nhân';
require_once '../includes/header.php';

$user_id = $_SESSION['id'];

// Lấy thông tin user
$sql = "SELECT * FROM users WHERE id = '$user_id'";
$result = mysqli_query($conn, $sql);
$user = mysqli_fetch_assoc($result);
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">Thông tin cá nhân</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active">Hồ sơ</li>
            </ol>
        </nav>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <img src="../../uploads/avatar/<?php echo $user['anh_dai_dien'] ?? 'default-avatar.png'; ?>" 
                         class="rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover;">
                    <h5><?php echo $user['hoten']; ?></h5>
                    <p class="text-muted mb-3">@<?php echo $user['username']; ?></p>
                    <div class="d-grid gap-2">
                        <a href="edit.php" class="btn btn-primary">
                            <i class="fas fa-edit"></i> Cập nhật hồ sơ
                        </a>
                        <a href="password.php" class="btn btn-outline-primary">
                            <i class="fas fa-key"></i> Đổi mật khẩu
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-info-circle me-1"></i>
                    Chi tiết tài khoản
                </div>
                <div class="card-body">
                    <table class="table">
                        <tr>
                            <th style="width: 200px;">Họ và tên</th>
                            <td><?php echo $user['hoten']; ?></td>
                        </tr>
                        <tr>
                            <th>Tên đăng nhập</th>
                            <td><?php echo $user['username']; ?></td>
                        </tr>
                        <tr>
                            <th>Email</th>
                            <td><?php echo $user['email']; ?></td>
                        </tr>
                        <tr>
                            <th>Số điện thoại</th>
                            <td><?php echo $user['sdt'] ?? 'Chưa cập nhật'; ?></td>
                        </tr>
                        <tr>
                            <th>Ngày tạo</th>
                            <td><?php echo date('d/m/Y H:i', strtotime($user['ngaytao'])); ?></td>
                        </tr>
                        <tr>
                            <th>Trạng thái</th>
                            <td>
                                <span class="badge bg-success">Hoạt động</span>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once '../includes/footer.php';
?>