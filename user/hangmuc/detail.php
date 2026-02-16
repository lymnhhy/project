<?php
// hangmuc/detail.php
require_once '../includes/header.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Lấy thông tin hạng mục
$sql = "SELECT hm.*, ct.ten_cong_trinh, ct.ma_cong_trinh, ct.dia_diem, ct.user_id,
        DATEDIFF(hm.ngay_ket_thuc, CURDATE()) as so_ngay_con
        FROM hangmucthicong hm
        LEFT JOIN congtrinh ct ON hm.congtrinh_id = ct.id
        WHERE hm.id = $id AND ct.user_id = '{$_SESSION['id']}'";
$result = mysqli_query($conn, $sql);

if(mysqli_num_rows($result) == 0) {
    $_SESSION['error'] = 'Không tìm thấy hạng mục';
    echo '<script>window.location.href="index.php";</script>';
    exit();
}

$hm = mysqli_fetch_assoc($result);
$is_overdue = ($hm['trang_thai'] != 'Hoàn thành' && $hm['so_ngay_con'] < 0);

// Lấy lịch sử cập nhật
$sql_lichsu = "SELECT * FROM lichsucapnhat WHERE hangmuc_id = $id ORDER BY thoi_gian_cap_nhat DESC LIMIT 10";
$lichsu_list = mysqli_query($conn, $sql_lichsu);

// Lấy ghi chú thi công
$sql_ghichu = "SELECT gc.*, u.hoten 
               FROM ghichuthicong gc
               LEFT JOIN users u ON gc.user_id = u.id
               WHERE gc.hangmuc_id = $id
               ORDER BY gc.ngay_ghi DESC";
$ghichu_list = mysqli_query($conn, $sql_ghichu);

// Ghi log
logActivity($conn, $_SESSION['id'], 'Xem chi tiết hạng mục', "Xem hạng mục: {$hm['ten_hang_muc']}");
?>

<div class="content-wrapper">
    <!-- Page Header -->
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h4 class="mb-1">
                    <i class="fas fa-tasks me-2 text-warning"></i>
                    <?php echo htmlspecialchars($hm['ten_hang_muc']); ?>
                </h4>
                <div class="d-flex gap-2 mt-2">
                    <span class="badge bg-info">Mã: <?php echo $hm['ma_hang_muc'] ?? 'HM-'.str_pad($hm['id'], 4, '0', STR_PAD_LEFT); ?></span>
                    <span class="badge bg-<?php echo getStatusBadge($hm['trang_thai']); ?>">
                        <?php echo $hm['trang_thai']; ?>
                    </span>
                    <?php if($is_overdue): ?>
                        <span class="badge bg-danger">Quá hạn</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="mt-2 mt-sm-0">
                <a href="update.php?id=<?php echo $id; ?>" class="btn btn-primary">
                    <i class="fas fa-pen me-2"></i>Cập nhật
                </a>
                <a href="edit.php?id=<?php echo $id; ?>" class="btn btn-warning">
                    <i class="fas fa-edit me-2"></i>Sửa
                </a>
                <a href="../ghichu/add.php?hangmuc_id=<?php echo $id; ?>" class="btn btn-info">
                    <i class="fas fa-sticky-note me-2"></i>Ghi chú
                </a>
                <div class="btn-group">
                    <a href="../congtrinh/detail.php?id=<?php echo $hm['congtrinh_id']; ?>" class="btn btn-secondary">
                        <i class="fas fa-building me-2"></i>Công trình
                    </a>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-list me-2"></i>Danh sách
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Thông tin hạng mục -->
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-info-circle me-2 text-warning"></i>
                    Thông tin hạng mục
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <th width="120">Công trình:</th>
                            <td>
                                <a href="../congtrinh/detail.php?id=<?php echo $hm['congtrinh_id']; ?>" class="text-decoration-none">
                                    <?php echo htmlspecialchars($hm['ten_cong_trinh']); ?>
                                </a>
                            </td>
                        </tr>
                        <tr>
                            <th>Địa điểm:</th>
                            <td>
                                <i class="fas fa-map-marker-alt text-muted me-2"></i>
                                <?php echo htmlspecialchars($hm['dia_diem']); ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Kinh phí:</th>
                            <td>
                                <i class="fas fa-coins text-muted me-2"></i>
                                <?php echo formatMoney($hm['kinh_phi']); ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Ngày bắt đầu:</th>
                            <td>
                                <i class="far fa-calendar-alt text-muted me-2"></i>
                                <?php echo date('d/m/Y', strtotime($hm['ngay_bat_dau'])); ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Ngày kết thúc:</th>
                            <td>
                                <i class="far fa-calendar-check text-muted me-2"></i>
                                <?php echo date('d/m/Y', strtotime($hm['ngay_ket_thuc'])); ?>
                                <?php if(!$is_overdue && $hm['so_ngay_con'] > 0): ?>
                                    <span class="badge bg-info ms-2">Còn <?php echo $hm['so_ngay_con']; ?> ngày</span>
                                <?php elseif($is_overdue): ?>
                                    <span class="badge bg-danger ms-2">Chậm <?php echo abs($hm['so_ngay_con']); ?> ngày</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Tiến độ:</th>
                            <td>
                                <div class="progress mb-2" style="height: 20px;">
                                    <div class="progress-bar bg-<?php echo getProgressColor($hm['phan_tram_tien_do']); ?>" 
                                         style="width: <?php echo $hm['phan_tram_tien_do']; ?>%">
                                        <?php echo $hm['phan_tram_tien_do']; ?>%
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th>Trạng thái:</th>
                            <td>
                                <span class="badge bg-<?php echo getStatusBadge($hm['trang_thai']); ?>" style="font-size: 1rem;">
                                    <?php echo $hm['trang_thai']; ?>
                                </span>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Ghi chú chung -->
            <?php if(!empty($hm['ghi_chu'])): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-file-alt me-2 text-warning"></i>
                    Ghi chú
                </div>
                <div class="card-body">
                    <?php echo nl2br(htmlspecialchars($hm['ghi_chu'])); ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Lịch sử cập nhật -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-history me-2 text-warning"></i>
                    Lịch sử cập nhật
                </div>
                <div class="card-body p-0">
                    <?php if(mysqli_num_rows($lichsu_list) == 0): ?>
                    <div class="text-center py-4">
                        <p class="text-muted">Chưa có lịch sử cập nhật</p>
                    </div>
                    <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php while($ls = mysqli_fetch_assoc($lichsu_list)): ?>
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="badge bg-info">
                                    <?php echo $ls['phan_tram_cu']; ?>% → <?php echo $ls['phan_tram_moi']; ?>%
                                </span>
                                <small class="text-muted">
                                    <?php echo timeAgo($ls['thoi_gian_cap_nhat']); ?>
                                </small>
                            </div>
                            <?php if(!empty($ls['ghi_chu'])): ?>
                            <p class="small mb-0 text-muted"><?php echo htmlspecialchars($ls['ghi_chu']); ?></p>
                            <?php endif; ?>
                        </div>
                        <?php endwhile; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="card-footer text-center">
                    <a href="history.php?hangmuc_id=<?php echo $id; ?>" class="text-decoration-none">
                        Xem tất cả lịch sử <i class="fas fa-arrow-right ms-2"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- Ghi chú thi công -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fas fa-sticky-note me-2 text-warning"></i>
                        Ghi chú thi công
                    </div>
                    <a href="../ghichu/add.php?hangmuc_id=<?php echo $id; ?>" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus-circle me-2"></i>Thêm ghi chú
                    </a>
                </div>
                <div class="card-body">
                    <?php if(mysqli_num_rows($ghichu_list) == 0): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-comment-slash fa-4x text-muted mb-3"></i>
                        <p class="text-muted">Chưa có ghi chú nào cho hạng mục này</p>
                        <a href="../ghichu/add.php?hangmuc_id=<?php echo $id; ?>" class="btn btn-primary">
                            <i class="fas fa-plus-circle me-2"></i>Thêm ghi chú đầu tiên
                        </a>
                    </div>
                    <?php else: ?>
                    <div class="timeline">
                        <?php while($gc = mysqli_fetch_assoc($ghichu_list)): ?>
                        <div class="timeline-item">
                            <div class="timeline-badge bg-info">
                                <i class="fas fa-user"></i>
                            </div>
                            <div class="timeline-content">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="mb-0">
                                        <i class="fas fa-user-circle me-2"></i>
                                        <?php echo htmlspecialchars($gc['hoten']); ?>
                                    </h6>
                                    <small class="text-muted">
                                        <i class="far fa-clock me-1"></i>
                                        <?php echo timeAgo($gc['ngay_ghi']); ?>
                                    </small>
                                </div>
                                <p class="mb-2"><?php echo nl2br(htmlspecialchars($gc['noi_dung'])); ?></p>
                                <?php if(!empty($gc['hinh_anh'])): ?>
                                <div class="mb-2">
                                    <img src="../../<?php echo $gc['hinh_anh']; ?>" class="img-thumbnail" style="max-height: 100px;">
                                </div>
                                <?php endif; ?>
                                <div class="text-end">
                                    <a href="../ghichu/detail.php?id=<?php echo $gc['id']; ?>" class="btn btn-sm btn-outline-info">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </div>
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
.table-borderless th {
    font-weight: 600;
    color: #475569;
}

.timeline {
    position: relative;
    padding: 20px 0;
}

.timeline-item {
    position: relative;
    padding-left: 60px;
    margin-bottom: 30px;
}

.timeline-badge {
    position: absolute;
    left: 0;
    top: 0;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #fbbf24;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
}

.timeline-content {
    background: #f8fafc;
    padding: 15px;
    border-radius: 8px;
    border-left: 3px solid #fbbf24;
}
</style>

<?php require_once '../includes/footer.php'; ?>