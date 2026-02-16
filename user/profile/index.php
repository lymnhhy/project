<?php
// profile/index.php
require_once '../includes/header.php';

$user_id = $_SESSION['id'];

// Lấy thông tin user
$sql = "SELECT * FROM users WHERE id = '$user_id'";
$result = mysqli_query($conn, $sql);
$user = mysqli_fetch_assoc($result);

// Đếm số công trình
$sql_ct = "SELECT COUNT(*) as total FROM congtrinh WHERE user_id = '$user_id'";
$result_ct = mysqli_query($conn, $sql_ct);
$congtrinh_count = mysqli_fetch_assoc($result_ct)['total'];

// Đếm số hạng mục
$sql_hm = "SELECT COUNT(*) as total FROM hangmucthicong hm 
           LEFT JOIN congtrinh ct ON hm.congtrinh_id = ct.id 
           WHERE ct.user_id = '$user_id'";
$result_hm = mysqli_query($conn, $sql_hm);
$hangmuc_count = mysqli_fetch_assoc($result_hm)['total'];

// Đếm số ghi chú
$sql_gc = "SELECT COUNT(*) as total FROM ghichuthicong WHERE user_id = '$user_id'";
$result_gc = mysqli_query($conn, $sql_gc);
$ghichu_count = mysqli_fetch_assoc($result_gc)['total'];

// Lấy hoạt động gần đây
$sql_activity = "SELECT * FROM lichsuhoatdong 
                 WHERE user_id = '$user_id' 
                 ORDER BY thoi_gian DESC 
                 LIMIT 5";
$activity_list = mysqli_query($conn, $sql_activity);
?>

<div class="content-wrapper">
    <!-- Page Header -->
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h4 class="mb-1">
                    <i class="fas fa-user-circle me-2 text-warning"></i>
                    Thông tin cá nhân
                </h4>
                <p class="text-muted mb-0">Quản lý thông tin tài khoản của bạn</p>
            </div>
            <div class="mt-2 mt-sm-0">
                <a href="edit.php" class="btn btn-primary">
                    <i class="fas fa-edit me-2"></i>Cập nhật thông tin
                </a>
                <a href="password.php" class="btn btn-warning">
                    <i class="fas fa-key me-2"></i>Đổi mật khẩu
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Cột trái: Avatar và thông tin cơ bản -->
        <div class="col-lg-4">
            <!-- Avatar Card -->
            <div class="card mb-4">
                <div class="card-body text-center">
                    <div class="avatar-wrapper mb-3">
                        <?php 
                        $avatar = !empty($user['anh_dai_dien']) ? $user['anh_dai_dien'] : 'default-avatar.png';
                        $avatar_path = BASE_URL . '/uploads/avatar/' . $avatar;
                        ?>
                        <img src="<?php echo $avatar_path; ?>" 
                             class="rounded-circle img-thumbnail" 
                             alt="Avatar"
                             style="width: 150px; height: 150px; object-fit: cover;"
                             onerror="this.src='<?php echo BASE_URL; ?>/assets/img/default-avatar.png'">
                    </div>
                    <h5 class="mb-1"><?php echo htmlspecialchars($user['hoten']); ?></h5>
                    <p class="text-muted mb-2">@<?php echo htmlspecialchars($user['username']); ?></p>
                    <div class="d-flex justify-content-center mb-3">
                        <span class="badge bg-<?php echo $user['trangthai'] == 1 ? 'success' : 'danger'; ?> p-2">
                            <i class="fas fa-circle me-1"></i>
                            <?php echo $user['trangthai'] == 1 ? 'Đang hoạt động' : 'Đã khóa'; ?>
                        </span>
                    </div>
                    <div class="row g-2">
                        <div class="col-4">
                            <div class="border rounded p-2">
                                <h6 class="mb-0"><?php echo $congtrinh_count; ?></h6>
                                <small class="text-muted">Công trình</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="border rounded p-2">
                                <h6 class="mb-0"><?php echo $hangmuc_count; ?></h6>
                                <small class="text-muted">Hạng mục</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="border rounded p-2">
                                <h6 class="mb-0"><?php echo $ghichu_count; ?></h6>
                                <small class="text-muted">Ghi chú</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Thông tin tài khoản -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-info-circle me-2 text-warning"></i>
                    Thông tin tài khoản
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <th width="120"><i class="fas fa-user"></i> Username:</th>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                        </tr>
                        <tr>
                            <th><i class="fas fa-calendar"></i> Ngày tạo:</th>
                            <td><?php echo date('d/m/Y H:i', strtotime($user['ngaytao'])); ?></td>
                        </tr>
                        <tr>
                            <th><i class="fas fa-clock"></i> Lần cuối:</th>
                            <td>
                                <?php 
                                $last_activity = mysqli_fetch_assoc(mysqli_query($conn, 
                                    "SELECT thoi_gian FROM lichsuhoatdong 
                                     WHERE user_id = '$user_id' 
                                     ORDER BY thoi_gian DESC LIMIT 1"
                                ));
                                echo $last_activity ? timeAgo($last_activity['thoi_gian']) : 'Chưa có hoạt động';
                                ?>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- Cột phải: Thông tin chi tiết và hoạt động -->
        <div class="col-lg-8">
            <!-- Thông tin chi tiết -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-address-card me-2 text-warning"></i>
                    Thông tin chi tiết
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <th width="150"><i class="fas fa-user-tag"></i> Họ và tên:</th>
                            <td><?php echo htmlspecialchars($user['hoten'] ?? 'Chưa cập nhật'); ?></td>
                        </tr>
                        <tr>
                            <th><i class="fas fa-envelope"></i> Email:</th>
                            <td>
                                <?php if(!empty($user['email'])): ?>
                                    <a href="mailto:<?php echo $user['email']; ?>">
                                        <?php echo htmlspecialchars($user['email']); ?>
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted">Chưa cập nhật</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th><i class="fas fa-phone"></i> Số điện thoại:</th>
                            <td>
                                <?php if(!empty($user['sdt'])): ?>
                                    <a href="tel:<?php echo $user['sdt']; ?>">
                                        <?php echo htmlspecialchars($user['sdt']); ?>
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted">Chưa cập nhật</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th><i class="fas fa-venus-mars"></i> Giới tính:</th>
                            <td>
                                <?php 
                                $gender = $user['gioi_tinh'] ?? '';
                                if($gender == 'nam') echo 'Nam';
                                elseif($gender == 'nu') echo 'Nữ';
                                else echo '<span class="text-muted">Chưa cập nhật</span>';
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <th><i class="fas fa-map-marker-alt"></i> Địa chỉ:</th>
                            <td><?php echo htmlspecialchars($user['dia_chi'] ?? 'Chưa cập nhật'); ?></td>
                        </tr>
                        <tr>
                            <th><i class="fas fa-birthday-cake"></i> Ngày sinh:</th>
                            <td>
                                <?php 
                                echo !empty($user['ngay_sinh']) ? date('d/m/Y', strtotime($user['ngay_sinh'])) : 'Chưa cập nhật';
                                ?>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Hoạt động gần đây -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fas fa-history me-2 text-warning"></i>
                        Hoạt động gần đây
                    </div>
                    <a href="activity.php" class="btn btn-sm btn-outline-primary">
                        Xem tất cả <i class="fas fa-arrow-right ms-2"></i>
                    </a>
                </div>
                <div class="card-body p-0">
                    <?php if(mysqli_num_rows($activity_list) == 0): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-clock fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Chưa có hoạt động nào</p>
                    </div>
                    <?php else: ?>
                    <div class="timeline p-3">
                        <?php while($activity = mysqli_fetch_assoc($activity_list)): ?>
                        <div class="timeline-item">
                            <div class="timeline-badge">
                                <i class="fas fa-circle"></i>
                            </div>
                            <div class="timeline-content">
                                <div class="d-flex justify-content-between">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($activity['hanh_dong']); ?></h6>
                                    <small class="text-muted"><?php echo timeAgo($activity['thoi_gian']); ?></small>
                                </div>
                                <p class="mb-1 text-muted small"><?php echo htmlspecialchars($activity['chi_tiet']); ?></p>
                                <small class="text-muted">
                                    <i class="fas fa-ip me-1"></i> IP: <?php echo $activity['ip_address']; ?>
                                </small>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.avatar-wrapper {
    position: relative;
    display: inline-block;
}

.avatar-wrapper img {
    border: 3px solid #fbbf24;
    padding: 3px;
    transition: all 0.3s;
}

.avatar-wrapper img:hover {
    transform: scale(1.05);
    box-shadow: 0 10px 25px rgba(251, 191, 36, 0.3);
}

.table-borderless th {
    font-weight: 600;
    color: #475569;
    vertical-align: middle;
}

.table-borderless td {
    vertical-align: middle;
}

.timeline {
    position: relative;
    max-height: 300px;
    overflow-y: auto;
}

.timeline-item {
    position: relative;
    padding-left: 30px;
    padding-bottom: 20px;
    border-left: 2px solid #f1f5f9;
    margin-left: 10px;
}

.timeline-item:last-child {
    border-left-color: transparent;
    padding-bottom: 0;
}

.timeline-badge {
    position: absolute;
    left: -9px;
    top: 0;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    background: #fbbf24;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
}

.timeline-badge i {
    font-size: 8px;
}

.timeline-content {
    background: #f8fafc;
    padding: 12px;
    border-radius: 8px;
    margin-bottom: 5px;
}

.border.rounded {
    transition: all 0.3s;
}

.border.rounded:hover {
    background: #f8fafc;
    transform: translateY(-2px);
}
</style>

<?php require_once '../includes/footer.php'; ?>