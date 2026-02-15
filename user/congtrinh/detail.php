<?php
// congtrinh/detail.php
require_once '../includes/header.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Lấy thông tin công trình
$sql = "SELECT ct.*, lct.ten_loai, ttct.ten_trang_thai, u.hoten, u.email, u.sdt
        FROM congtrinh ct
        LEFT JOIN loaicongtrinh lct ON ct.loaiCT_id = lct.id
        LEFT JOIN trangthaicongtrinh ttct ON ct.trangthaiCT_id = ttct.id
        LEFT JOIN users u ON ct.user_id = u.id
        WHERE ct.id = $id AND ct.user_id = '{$_SESSION['id']}'";
$result = mysqli_query($conn, $sql);

if(mysqli_num_rows($result) == 0) {
    $_SESSION['error'] = 'Không tìm thấy công trình';
    header('Location: index.php');
    exit();
}

$ct = mysqli_fetch_assoc($result);

// Thống kê hạng mục
$sql_hm = "SELECT 
            COUNT(*) as tong,
            SUM(CASE WHEN trang_thai = 'Hoàn thành' THEN 1 ELSE 0 END) as hoanthanh,
            SUM(CASE WHEN trang_thai = 'Đang thi công' THEN 1 ELSE 0 END) as dangtc,
            SUM(CASE WHEN trang_thai = 'Chưa thi công' THEN 1 ELSE 0 END) as chuatc,
            SUM(CASE WHEN ngay_ket_thuc < CURDATE() AND trang_thai != 'Hoàn thành' THEN 1 ELSE 0 END) as quahan,
            AVG(phan_tram_tien_do) as tien_do_tb
            FROM hangmucthicong 
            WHERE congtrinh_id = $id";
$result_hm = mysqli_query($conn, $sql_hm);
$hm_stats = mysqli_fetch_assoc($result_hm);

// Lấy danh sách hạng mục
$sql_hm_list = "SELECT hm.*, 
                DATEDIFF(ngay_ket_thuc, CURDATE()) as so_ngay_con
                FROM hangmucthicong hm
                WHERE hm.congtrinh_id = $id
                ORDER BY 
                    CASE 
                        WHEN trang_thai != 'Hoàn thành' AND ngay_ket_thuc < CURDATE() THEN 1
                        WHEN trang_thai = 'Đang thi công' THEN 2
                        WHEN trang_thai = 'Chưa thi công' THEN 3
                        ELSE 4
                    END,
                    ngay_ket_thuc ASC";
$hm_list = mysqli_query($conn, $sql_hm_list);

// Lấy ghi chú gần đây
$sql_ghichu = "SELECT gc.*, u.hoten
               FROM ghichuthicong gc
               LEFT JOIN hangmucthicong hm ON gc.hangmuc_id = hm.id
               LEFT JOIN users u ON gc.user_id = u.id
               WHERE hm.congtrinh_id = $id OR gc.hangmuc_id IS NULL
               ORDER BY gc.ngay_ghi DESC
               LIMIT 5";
$ghichu_list = mysqli_query($conn, $sql_ghichu);

// Tính số ngày còn lại
$ngay_con = round((strtotime($ct['ngay_ket_thuc']) - time()) / 86400);
$ngay_da_lam = round((time() - strtotime($ct['ngay_bat_dau'])) / 86400);
$tong_ngay = round((strtotime($ct['ngay_ket_thuc']) - strtotime($ct['ngay_bat_dau'])) / 86400);

// Ghi log
logActivity($conn, $_SESSION['id'], 'Xem chi tiết', "Xem chi tiết công trình: {$ct['ten_cong_trinh']}");
?>

<div class="content-wrapper">
    <!-- Header -->
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h4 class="mb-1">
                    <i class="fas fa-building me-2 text-warning"></i>
                    <?php echo htmlspecialchars($ct['ten_cong_trinh']); ?>
                </h4>
                <div class="d-flex gap-2">
                    <span class="badge bg-<?php echo getStatusBadge($ct['ten_trang_thai']); ?>">
                        <?php echo $ct['ten_trang_thai']; ?>
                    </span>
                    <span class="text-muted">
                        <i class="fas fa-code me-1"></i>Mã: <?php echo $ct['ma_cong_trinh']; ?>
                    </span>
                </div>
            </div>
            <div class="mt-2 mt-sm-0">
                <a href="edit.php?id=<?php echo $id; ?>" class="btn btn-primary">
                    <i class="fas fa-edit me-2"></i>Sửa
                </a>
                <a href="progress.php?id=<?php echo $id; ?>" class="btn btn-info">
                    <i class="fas fa-chart-line me-2"></i>Tiến độ
                </a>
                <a href="../baocao/congtrinh.php?id=<?php echo $id; ?>" class="btn btn-success">
                    <i class="fas fa-file-pdf me-2"></i>Báo cáo
                </a>
                <div class="btn-group">
                    <button type="button" class="btn btn-secondary dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="fas fa-tasks me-2"></i>Thêm
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="../hangmuc/add.php?congtrinh_id=<?php echo $id; ?>">
                            <i class="fas fa-plus-circle me-2"></i>Hạng mục
                        </a></li>
                        <li><a class="dropdown-item" href="../ghichu/add.php?congtrinh_id=<?php echo $id; ?>">
                            <i class="fas fa-sticky-note me-2"></i>Ghi chú
                        </a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Thống kê nhanh -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card stat-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-muted mb-1">Tổng hạng mục</h6>
                            <h3 class="mb-0"><?php echo $hm_stats['tong'] ?? 0; ?></h3>
                        </div>
                        <div class="stat-icon bg-primary">
                            <i class="fas fa-tasks text-white"></i>
                        </div>
                    </div>
                    <div class="mt-2 small">
                        <span class="text-success">HT: <?php echo $hm_stats['hoanthanh'] ?? 0; ?></span>
                        <span class="text-warning ms-2">ĐG: <?php echo $hm_stats['dangtc'] ?? 0; ?></span>
                        <span class="text-secondary ms-2">CĐ: <?php echo $hm_stats['chuatc'] ?? 0; ?></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-muted mb-1">Tiến độ TB</h6>
                            <h3 class="mb-0"><?php echo round($hm_stats['tien_do_tb'] ?? 0); ?>%</h3>
                        </div>
                        <div class="stat-icon bg-success">
                            <i class="fas fa-chart-line text-white"></i>
                        </div>
                    </div>
                    <div class="progress mt-2" style="height: 6px;">
                        <div class="progress-bar bg-success" style="width: <?php echo round($hm_stats['tien_do_tb'] ?? 0); ?>%"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-muted mb-1">Kinh phí</h6>
                            <h6 class="mb-0"><?php echo formatMoney($ct['kinh_phi']); ?></h6>
                        </div>
                        <div class="stat-icon bg-warning">
                            <i class="fas fa-coins text-white"></i>
                        </div>
                    </div>
                    <small class="text-muted">Dự kiến</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-muted mb-1">Thời gian</h6>
                            <h3 class="mb-0 <?php echo $ngay_con < 0 ? 'text-danger' : ''; ?>">
                                <?php echo $ngay_con < 0 ? 'Quá '.abs($ngay_con).' ngày' : $ngay_con.' ngày'; ?>
                            </h3>
                        </div>
                        <div class="stat-icon bg-<?php echo $ngay_con < 0 ? 'danger' : 'info'; ?>">
                            <i class="fas fa-clock text-white"></i>
                        </div>
                    </div>
                    <div class="progress mt-2" style="height: 6px;">
                        <?php 
                        $percent_time = $tong_ngay > 0 ? round(($ngay_da_lam / $tong_ngay) * 100) : 0;
                        $percent_time = min(100, $percent_time);
                        ?>
                        <div class="progress-bar bg-<?php echo $ngay_con < 0 ? 'danger' : 'info'; ?>" 
                             style="width: <?php echo $percent_time; ?>%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Cột trái: Thông tin chi tiết -->
        <div class="col-lg-4">
            <!-- Thông tin chung -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-info-circle me-2 text-warning"></i>
                    Thông tin chung
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <th width="120">Địa điểm:</th>
                            <td>
                                <i class="fas fa-map-marker-alt text-muted me-2"></i>
                                <?php echo htmlspecialchars($ct['dia_diem']); ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Loại CT:</th>
                            <td>
                                <span class="badge bg-info"><?php echo $ct['ten_loai']; ?></span>
                            </td>
                        </tr>
                        <tr>
                            <th>Ngày bắt đầu:</th>
                            <td>
                                <i class="far fa-calendar-alt text-muted me-2"></i>
                                <?php echo date('d/m/Y', strtotime($ct['ngay_bat_dau'])); ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Ngày kết thúc:</th>
                            <td>
                                <i class="far fa-calendar-check text-muted me-2"></i>
                                <?php echo date('d/m/Y', strtotime($ct['ngay_ket_thuc'])); ?>
                                <?php if($ngay_con < 0): ?>
                                    <span class="badge bg-danger ms-2">Quá hạn</span>
                                <?php elseif($ngay_con <= 7): ?>
                                    <span class="badge bg-warning ms-2">Sắp hết hạn</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Kinh phí:</th>
                            <td>
                                <i class="fas fa-coins text-muted me-2"></i>
                                <?php echo formatMoney($ct['kinh_phi']); ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Người phụ trách:</th>
                            <td>
                                <i class="fas fa-user text-muted me-2"></i>
                                <?php echo htmlspecialchars($ct['hoten']); ?>
                                <br>
                                <small class="text-muted ms-4">
                                    <i class="fas fa-phone"></i> <?php echo $ct['sdt']; ?><br>
                                    <i class="fas fa-envelope"></i> <?php echo $ct['email']; ?>
                                </small>
                            </td>
                        </tr>
                        <tr>
                            <th>Ngày tạo:</th>
                            <td>
                                <i class="far fa-clock text-muted me-2"></i>
                                <?php echo date('d/m/Y H:i', strtotime($ct['ngay_tao'])); ?>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Mô tả -->
            <?php if(!empty($ct['mo_ta'])): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-file-alt me-2 text-warning"></i>
                    Mô tả
                </div>
                <div class="card-body">
                    <?php echo nl2br(htmlspecialchars($ct['mo_ta'])); ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Hình ảnh -->
            <?php if(!empty($ct['hinh_anh'])): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-image me-2 text-warning"></i>
                    Hình ảnh
                </div>
                <div class="card-body text-center">
                    <img src="../../<?php echo $ct['hinh_anh']; ?>" class="img-fluid rounded" alt="Công trình">
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Cột phải: Danh sách hạng mục và ghi chú -->
        <div class="col-lg-8">
            <!-- Danh sách hạng mục -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fas fa-tasks me-2 text-warning"></i>
                        Danh sách hạng mục
                        <span class="badge bg-secondary ms-2"><?php echo $hm_stats['tong'] ?? 0; ?></span>
                    </div>
                    <a href="../hangmuc/add.php?congtrinh_id=<?php echo $id; ?>" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus-circle me-2"></i>Thêm hạng mục
                    </a>
                </div>
                <div class="card-body p-0">
                    <?php if(mysqli_num_rows($hm_list) == 0): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-tasks fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Chưa có hạng mục nào</p>
                        <a href="../hangmuc/add.php?congtrinh_id=<?php echo $id; ?>" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus-circle me-2"></i>Thêm hạng mục
                        </a>
                    </div>
                    <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php while($hm = mysqli_fetch_assoc($hm_list)): 
                            $is_overdue = ($hm['trang_thai'] != 'Hoàn thành' && strtotime($hm['ngay_ket_thuc']) < time());
                        ?>
                        <div class="list-group-item list-group-item-action">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between">
                                        <h6 class="mb-1">
                                            <a href="../hangmuc/detail.php?id=<?php echo $hm['id']; ?>" class="text-decoration-none">
                                                <?php echo htmlspecialchars($hm['ten_hang_muc']); ?>
                                            </a>
                                            <?php if($is_overdue): ?>
                                                <span class="badge bg-danger ms-2">Quá hạn</span>
                                            <?php endif; ?>
                                        </h6>
                                        <span class="badge bg-<?php echo getProgressColor($hm['phan_tram_tien_do']); ?>">
                                            <?php echo $hm['phan_tram_tien_do']; ?>%
                                        </span>
                                    </div>
                                    <div class="d-flex gap-3 mb-2 small text-muted">
                                        <span><i class="fas fa-dollar-sign"></i> <?php echo formatMoney($hm['kinh_phi']); ?></span>
                                        <span><i class="far fa-calendar-alt"></i> <?php echo date('d/m/Y', strtotime($hm['ngay_ket_thuc'])); ?></span>
                                    </div>
                                    <div class="progress" style="height: 6px;">
                                        <div class="progress-bar bg-<?php echo getProgressColor($hm['phan_tram_tien_do']); ?>" 
                                             style="width: <?php echo $hm['phan_tram_tien_do']; ?>%"></div>
                                    </div>
                                </div>
                                <div class="ms-3">
                                    <a href="../hangmuc/update.php?id=<?php echo $hm['id']; ?>" class="btn btn-sm btn-outline-primary" title="Cập nhật">
                                        <i class="fas fa-pen"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php if(mysqli_num_rows($hm_list) > 0): ?>
                <div class="card-footer text-center">
                    <a href="../hangmuc/index.php?congtrinh_id=<?php echo $id; ?>" class="text-decoration-none">
                        Xem tất cả hạng mục <i class="fas fa-arrow-right ms-2"></i>
                    </a>
                </div>
                <?php endif; ?>
            </div>

            <!-- Ghi chú gần đây -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fas fa-sticky-note me-2 text-warning"></i>
                        Ghi chú gần đây
                    </div>
                    <a href="../ghichu/add.php?congtrinh_id=<?php echo $id; ?>" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus-circle me-2"></i>Thêm ghi chú
                    </a>
                </div>
                <div class="card-body p-0">
                    <?php if(mysqli_num_rows($ghichu_list) == 0): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-comment-slash fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Chưa có ghi chú nào</p>
                    </div>
                    <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php while($gc = mysqli_fetch_assoc($ghichu_list)): ?>
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between">
                                <small class="text-muted">
                                    <i class="fas fa-user me-1"></i> <?php echo htmlspecialchars($gc['hoten']); ?>
                                </small>
                                <small class="text-muted">
                                    <i class="far fa-clock me-1"></i> <?php echo timeAgo($gc['ngay_ghi']); ?>
                                </small>
                            </div>
                            <p class="mb-2 mt-2"><?php echo htmlspecialchars($gc['noi_dung']); ?></p>
                            <div class="text-end">
                                <a href="../ghichu/detail.php?id=<?php echo $gc['id']; ?>" class="btn btn-sm btn-outline-info">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php if(mysqli_num_rows($ghichu_list) > 0): ?>
                <div class="card-footer text-center">
                    <a href="../ghichu/index.php?congtrinh_id=<?php echo $id; ?>" class="text-decoration-none">
                        Xem tất cả ghi chú <i class="fas fa-arrow-right ms-2"></i>
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.stat-card {
    border: none;
    border-radius: 12px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.02);
    transition: all 0.3s;
}
.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.05);
}
.stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.table-borderless th {
    font-weight: 600;
    color: #64748b;
}
.list-group-item {
    transition: all 0.3s;
}
.list-group-item:hover {
    background: #f8fafc;
}
</style>

<?php require_once '../includes/footer.php'; ?>