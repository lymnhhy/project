<?php
date_default_timezone_set('Asia/Ho_Chi_Minh');
// user/dashboard.php - PHIÊN BẢN HOÀN CHỈNH NHẤT
$page_title = 'Tổng quan hệ thống';
require_once 'includes/header.php';

$user_id = $_SESSION['id'];

// ============================================
// THỐNG KÊ TỔNG QUAN
// ============================================
$sql_thongke = "SELECT 
                    COUNT(*) as tong_cong_trinh,
                    SUM(CASE WHEN trangthaiCT_id = 2 THEN 1 ELSE 0 END) as dang_thi_cong,
                    SUM(CASE WHEN trangthaiCT_id = 3 THEN 1 ELSE 0 END) as hoan_thanh,
                    SUM(CASE WHEN trangthaiCT_id = 1 THEN 1 ELSE 0 END) as chua_thi_cong,
                    SUM(CASE WHEN ngay_ket_thuc < CURDATE() AND trangthaiCT_id != 3 THEN 1 ELSE 0 END) as qua_han,
                    SUM(kinh_phi) as tong_kinh_phi
                FROM congtrinh 
                WHERE user_id = '$user_id'";
$result_thongke = mysqli_query($conn, $sql_thongke);
$thongke = mysqli_fetch_assoc($result_thongke);

// Thống kê hạng mục (ĐÃ SỬA LỖI)
$sql_hangmuc_stats = "SELECT 
                        COUNT(*) as tong_hang_muc,
                        SUM(CASE WHEN hm.trang_thai = 'Đang thi công' THEN 1 ELSE 0 END) as hm_dang_thi_cong,
                        SUM(CASE WHEN hm.trang_thai = 'Hoàn thành' THEN 1 ELSE 0 END) as hm_hoan_thanh,
                        SUM(CASE WHEN hm.trang_thai = 'Chưa thi công' THEN 1 ELSE 0 END) as hm_chua_thi_cong,
                        SUM(CASE WHEN hm.ngay_ket_thuc < CURDATE() AND hm.trang_thai != 'Hoàn thành' THEN 1 ELSE 0 END) as hm_qua_han,
                        AVG(hm.phan_tram_tien_do) as tien_do_trung_binh
                    FROM hangmucthicong hm
                    LEFT JOIN congtrinh ct ON hm.congtrinh_id = ct.id
                    WHERE ct.user_id = '$user_id'";  // ĐÃ SỬA: thêm ct.
$result_hangmuc_stats = mysqli_query($conn, $sql_hangmuc_stats);
$hangmuc_stats = mysqli_fetch_assoc($result_hangmuc_stats);

// Thống kê ghi chú (ĐÃ SỬA LỖI)
$sql_ghichu_stats = "SELECT COUNT(*) as tong_ghi_chu,
                     COUNT(DISTINCT DATE(gc.ngay_ghi)) as so_ngay_ghi,
                     COUNT(DISTINCT gc.user_id) as nguoi_ghi
                     FROM ghichuthicong gc
                     LEFT JOIN hangmucthicong hm ON gc.hangmuc_id = hm.id
                     LEFT JOIN congtrinh ct ON hm.congtrinh_id = ct.id
                     WHERE ct.user_id = '$user_id' OR gc.user_id = '$user_id'";
$result_ghichu_stats = mysqli_query($conn, $sql_ghichu_stats);
$ghichu_stats = mysqli_fetch_assoc($result_ghichu_stats);

// ============================================
// DANH SÁCH CÔNG TRÌNH (có phân trang)
// ============================================
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 5;
$offset = ($page - 1) * $limit;

$sql_congtrinh = "SELECT ct.*, lct.ten_loai, ttct.ten_trang_thai,
                  (SELECT COUNT(*) FROM hangmucthicong WHERE congtrinh_id = ct.id) as tong_hm,
                  (SELECT COUNT(*) FROM hangmucthicong WHERE congtrinh_id = ct.id AND trang_thai = 'Hoàn thành') as hm_ht,
                  (SELECT COUNT(*) FROM hangmucthicong WHERE congtrinh_id = ct.id AND ngay_ket_thuc < CURDATE() AND trang_thai != 'Hoàn thành') as hm_quahan,
                  (SELECT COUNT(*) FROM ghichuthicong gc 
                   LEFT JOIN hangmucthicong hm ON gc.hangmuc_id = hm.id 
                   WHERE hm.congtrinh_id = ct.id) as tong_ghichu
                  FROM congtrinh ct
                  LEFT JOIN loaicongtrinh lct ON ct.loaiCT_id = lct.id
                  LEFT JOIN trangthaicongtrinh ttct ON ct.trangthaiCT_id = ttct.id
                  WHERE ct.user_id = '$user_id'
                  ORDER BY 
                    CASE 
                        WHEN ct.trangthaiCT_id != 3 AND ct.ngay_ket_thuc < CURDATE() THEN 1
                        WHEN ct.trangthaiCT_id = 2 AND ct.ngay_ket_thuc BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 2
                        WHEN ct.trangthaiCT_id = 2 THEN 3
                        WHEN ct.trangthaiCT_id = 1 THEN 4
                        ELSE 5
                    END,
                    ct.ngay_ket_thuc ASC
                  LIMIT $offset, $limit";
$result_congtrinh = mysqli_query($conn, $sql_congtrinh);

// Đếm tổng số công trình để phân trang
$sql_total = "SELECT COUNT(*) as total FROM congtrinh WHERE user_id = '$user_id'";
$result_total = mysqli_query($conn, $sql_total);
$total_rows = mysqli_fetch_assoc($result_total)['total'];
$total_pages = ceil($total_rows / $limit);

// ============================================
// CÁC CẢNH BÁO
// ============================================

// Công trình sắp đến hạn
$sql_sap_den_han = "SELECT ct.*, ttct.ten_trang_thai,
                    DATEDIFF(ngay_ket_thuc, CURDATE()) as so_ngay_con_lai,
                    (SELECT COUNT(*) FROM hangmucthicong WHERE congtrinh_id = ct.id AND trang_thai != 'Hoàn thành') as hm_con_lai
                    FROM congtrinh ct
                    LEFT JOIN trangthaicongtrinh ttct ON ct.trangthaiCT_id = ttct.id
                    WHERE ct.user_id = '$user_id' 
                    AND ct.trangthaiCT_id != 3 
                    AND ct.ngay_ket_thuc BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
                    ORDER BY ct.ngay_ket_thuc ASC";
$result_sap_den_han = mysqli_query($conn, $sql_sap_den_han);

// Công trình quá hạn
$sql_qua_han = "SELECT ct.*, ttct.ten_trang_thai,
                DATEDIFF(CURDATE(), ngay_ket_thuc) as so_ngay_qua_han,
                (SELECT COUNT(*) FROM hangmucthicong WHERE congtrinh_id = ct.id AND trang_thai != 'Hoàn thành') as hm_dang_lam
                FROM congtrinh ct
                LEFT JOIN trangthaicongtrinh ttct ON ct.trangthaiCT_id = ttct.id
                WHERE ct.user_id = '$user_id' 
                AND ct.trangthaiCT_id != 3 
                AND ct.ngay_ket_thuc < CURDATE()
                ORDER BY ct.ngay_ket_thuc ASC";
$result_qua_han = mysqli_query($conn, $sql_qua_han);

// Hạng mục cần cập nhật gấp
$sql_hm_can_capnhat = "SELECT hm.*, ct.ten_cong_trinh,
                       DATEDIFF(hm.ngay_ket_thuc, CURDATE()) as so_ngay_con_lai
                       FROM hangmucthicong hm
                       LEFT JOIN congtrinh ct ON hm.congtrinh_id = ct.id
                       WHERE ct.user_id = '$user_id' 
                       AND hm.phan_tram_tien_do < 100
                       AND hm.ngay_ket_thuc BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 3 DAY)
                       ORDER BY hm.ngay_ket_thuc ASC
                       LIMIT 5";
$result_hm_can_capnhat = mysqli_query($conn, $sql_hm_can_capnhat);

// Hạng mục chậm tiến độ
$sql_hm_cham = "SELECT hm.*, ct.ten_cong_trinh,
                DATEDIFF(CURDATE(), hm.ngay_ket_thuc) as so_ngay_cham
                FROM hangmucthicong hm
                LEFT JOIN congtrinh ct ON hm.congtrinh_id = ct.id
                WHERE ct.user_id = '$user_id' 
                AND hm.phan_tram_tien_do < 100
                AND hm.ngay_ket_thuc < CURDATE()
                ORDER BY hm.ngay_ket_thuc ASC
                LIMIT 5";
$result_hm_cham = mysqli_query($conn, $sql_hm_cham);

// ============================================
// HOẠT ĐỘNG GẦN ĐÂY
// ============================================
$sql_hoatdong = "SELECT * FROM lichsuhoatdong 
                 WHERE user_id = '$user_id' 
                 ORDER BY thoi_gian DESC 
                 LIMIT 10";
$result_hoatdong = mysqli_query($conn, $sql_hoatdong);

// ============================================
// GHI CHÚ MỚI NHẤT
// ============================================
$sql_ghichu_moi = "SELECT gc.*, u.hoten, ct.ten_cong_trinh, hm.ten_hang_muc
                   FROM ghichuthicong gc
                   LEFT JOIN users u ON gc.user_id = u.id
                   LEFT JOIN hangmucthicong hm ON gc.hangmuc_id = hm.id
                   LEFT JOIN congtrinh ct ON hm.congtrinh_id = ct.id
                   WHERE ct.user_id = '$user_id' OR gc.user_id = '$user_id'
                   ORDER BY gc.ngay_ghi DESC
                   LIMIT 5";
$result_ghichu_moi = mysqli_query($conn, $sql_ghichu_moi);

// ============================================
// DỮ LIỆU CHO BIỂU ĐỒ
// ============================================
$sql_bieudo = "SELECT ct.ten_cong_trinh, ct.trangthaiCT_id, ct.ngay_ket_thuc,
              (SELECT AVG(phan_tram_tien_do) FROM hangmucthicong WHERE congtrinh_id = ct.id) as tien_do
              FROM congtrinh ct
              WHERE ct.user_id = '$user_id'
              ORDER BY 
                CASE 
                    WHEN ct.ngay_ket_thuc < CURDATE() AND ct.trangthaiCT_id != 3 THEN 1
                    ELSE 2
                END,
                ct.ngay_ket_thuc ASC
              LIMIT 10";
$result_bieudo = mysqli_query($conn, $sql_bieudo);

$labels = [];
$progress_data = [];
$status_data = [0, 0, 0];
$overdue_data = [];

while ($ct = mysqli_fetch_assoc($result_bieudo)) {
    $labels[] = $ct['ten_cong_trinh'];
    $tien_do = round($ct['tien_do'] ?? 0);
    $progress_data[] = $tien_do;
    $overdue_data[] = ($ct['trangthaiCT_id'] != 3 && strtotime($ct['ngay_ket_thuc']) < time()) ? 1 : 0;
    
    if ($ct['trangthaiCT_id'] == 1) $status_data[0]++;
    elseif ($ct['trangthaiCT_id'] == 2) $status_data[1]++;
    elseif ($ct['trangthaiCT_id'] == 3) $status_data[2]++;
}
?>

<!-- ============================================ -->
<!-- BẮT ĐẦU NỘI DUNG DASHBOARD -->
<!-- ============================================ -->

<!-- Page Header -->
<div class="welcome-section mb-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap">
        <div>
            <h4 class="mb-1">
                <i class="fas fa-hand-wave text-warning me-2"></i>
                Xin chào, <span class="text-primary"><?php echo htmlspecialchars($current_user['hoten']); ?></span>!
            </h4>
            <p class="text-muted mb-0">
                <i class="far fa-calendar-alt me-2"></i><?php echo date('l, d/m/Y'); ?> | 
                <i class="far fa-clock me-2 ms-2"></i><?php echo date('H:i'); ?>
            </p>
        </div>
        <div class="d-flex gap-2 mt-2 mt-sm-0">
            <a href="congtrinh/add.php" class="btn btn-primary">
                <i class="fas fa-plus-circle me-2"></i>Thêm công trình
            </a>
            <a href="hangmuc/add.php" class="btn btn-success">
                <i class="fas fa-plus-circle me-2"></i>Thêm hạng mục
            </a>
            <a href="baocao/export.php" class="btn btn-outline-primary">
                <i class="fas fa-download me-2"></i>Xuất báo cáo
            </a>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- 1. THỐNG KÊ TỔNG QUAN (6 CARD) -->
<!-- ============================================ -->
<div class="row g-4 mb-4">
    <!-- Card 1: Tổng công trình -->
    <div class="col-xl-2 col-md-4 col-sm-6">
        <div class="card stat-card h-100" onclick="window.location.href='congtrinh/index.php'" style="cursor: pointer;">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="stat-icon bg-primary bg-opacity-10">
                        <i class="fas fa-building text-primary"></i>
                    </div>
                    <div class="ms-3">
                        <h6 class="text-muted mb-1">Công trình</h6>
                        <h3 class="mb-0 fw-bold"><?php echo $thongke['tong_cong_trinh'] ?? 0; ?></h3>
                    </div>
                </div>
                <div class="small d-flex justify-content-between">
                    <span><i class="fas fa-check-circle text-success me-1"></i><?php echo $thongke['hoan_thanh'] ?? 0; ?> HT</span>
                    <span><i class="fas fa-spinner text-warning me-1"></i><?php echo $thongke['dang_thi_cong'] ?? 0; ?> ĐG</span>
                    <span><i class="fas fa-clock text-secondary me-1"></i><?php echo $thongke['chua_thi_cong'] ?? 0; ?> CĐ</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 2: Tổng hạng mục -->
    <div class="col-xl-2 col-md-4 col-sm-6">
        <div class="card stat-card h-100" onclick="window.location.href='hangmuc/index.php'" style="cursor: pointer;">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="stat-icon bg-success bg-opacity-10">
                        <i class="fas fa-tasks text-success"></i>
                    </div>
                    <div class="ms-3">
                        <h6 class="text-muted mb-1">Hạng mục</h6>
                        <h3 class="mb-0 fw-bold"><?php echo $hangmuc_stats['tong_hang_muc'] ?? 0; ?></h3>
                    </div>
                </div>
                <div class="small d-flex justify-content-between">
                    <span><i class="fas fa-check text-success me-1"></i><?php echo $hangmuc_stats['hm_hoan_thanh'] ?? 0; ?> HT</span>
                    <span><i class="fas fa-spinner text-warning me-1"></i><?php echo $hangmuc_stats['hm_dang_thi_cong'] ?? 0; ?> ĐG</span>
                    <span><i class="fas fa-clock text-secondary me-1"></i><?php echo $hangmuc_stats['hm_chua_thi_cong'] ?? 0; ?> CĐ</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 3: Tiến độ trung bình -->
    <div class="col-xl-2 col-md-4 col-sm-6">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="stat-icon bg-info bg-opacity-10">
                        <i class="fas fa-chart-line text-info"></i>
                    </div>
                    <div class="ms-3">
                        <h6 class="text-muted mb-1">Tiến độ TB</h6>
                        <h3 class="mb-0 fw-bold"><?php echo round($hangmuc_stats['tien_do_trung_binh'] ?? 0); ?>%</h3>
                    </div>
                </div>
                <div class="progress mt-2" style="height: 6px;">
                    <div class="progress-bar bg-info" style="width: <?php echo round($hangmuc_stats['tien_do_trung_binh'] ?? 0); ?>%"></div>
                </div>
                <div class="small text-muted mt-2 text-center">
                    <i class="fas fa-calendar-alt me-1"></i>Cập nhật: <?php echo date('d/m'); ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 4: Quá hạn -->
    <div class="col-xl-2 col-md-4 col-sm-6">
        <div class="card stat-card h-100 border-danger" onclick="window.location.href='congtrinh/index.php?filter=quahan'" style="cursor: pointer;">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="stat-icon bg-danger bg-opacity-10">
                        <i class="fas fa-exclamation-triangle text-danger"></i>
                    </div>
                    <div class="ms-3">
                        <h6 class="text-muted mb-1">Quá hạn</h6>
                        <h3 class="mb-0 fw-bold"><?php echo ($thongke['qua_han'] ?? 0) + ($hangmuc_stats['hm_qua_han'] ?? 0); ?></h3>
                    </div>
                </div>
                <div class="small d-flex justify-content-between">
                    <span><i class="fas fa-building me-1"></i>CT: <?php echo $thongke['qua_han'] ?? 0; ?></span>
                    <span><i class="fas fa-tasks me-1"></i>HM: <?php echo $hangmuc_stats['hm_qua_han'] ?? 0; ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 5: Ghi chú -->
    <div class="col-xl-2 col-md-4 col-sm-6">
        <div class="card stat-card h-100" onclick="window.location.href='ghichu/index.php'" style="cursor: pointer;">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="stat-icon bg-warning bg-opacity-10">
                        <i class="fas fa-sticky-note text-warning"></i>
                    </div>
                    <div class="ms-3">
                        <h6 class="text-muted mb-1">Ghi chú</h6>
                        <h3 class="mb-0 fw-bold"><?php echo $ghichu_stats['tong_ghi_chu'] ?? 0; ?></h3>
                    </div>
                </div>
                <div class="small d-flex justify-content-between">
                    <span><i class="fas fa-users me-1"></i><?php echo $ghichu_stats['nguoi_ghi'] ?? 0; ?> người</span>
                    <span><i class="fas fa-calendar me-1"></i><?php echo $ghichu_stats['so_ngay_ghi'] ?? 0; ?> ngày</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 6: Tổng kinh phí -->
    <div class="col-xl-2 col-md-4 col-sm-6">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="stat-icon bg-purple bg-opacity-10">
                        <i class="fas fa-coins text-purple"></i>
                    </div>
                    <div class="ms-3">
                        <h6 class="text-muted mb-1">Tổng kinh phí</h6>
                        <h6 class="mb-0 fw-bold"><?php echo formatMoney($thongke['tong_kinh_phi'] ?? 0); ?></h6>
                    </div>
                </div>
                <div class="small text-muted text-center">
                    <i class="fas fa-chart-pie me-1"></i>Đầu tư dự án
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- 2. CẢNH BÁO KHẨN CẤP -->
<!-- ============================================ -->
<?php 
$tong_canh_bao = mysqli_num_rows($result_qua_han) + mysqli_num_rows($result_sap_den_han) + 
                 mysqli_num_rows($result_hm_cham) + mysqli_num_rows($result_hm_can_capnhat);
if ($tong_canh_bao > 0): 
?>
<div class="alert alert-warning alert-dismissible fade show mb-4" role="alert">
    <div class="d-flex align-items-center">
        <i class="fas fa-exclamation-circle fs-4 me-3"></i>
        <div>
            <strong>Có <?php echo $tong_canh_bao; ?> vấn đề cần xử lý ngay!</strong>
            <div class="mt-2">
                <?php if (mysqli_num_rows($result_qua_han) > 0): ?>
                    <a href="congtrinh/index.php?filter=quahan" class="badge bg-danger text-decoration-none me-2 p-2">
                        <i class="fas fa-building me-1"></i><?php echo mysqli_num_rows($result_qua_han); ?> công trình quá hạn
                    </a>
                <?php endif; ?>
                <?php if (mysqli_num_rows($result_sap_den_han) > 0): ?>
                    <a href="congtrinh/index.php?filter=sapdenhan" class="badge bg-warning text-dark text-decoration-none me-2 p-2">
                        <i class="fas fa-clock me-1"></i><?php echo mysqli_num_rows($result_sap_den_han); ?> công trình sắp hết hạn
                    </a>
                <?php endif; ?>
                <?php if (mysqli_num_rows($result_hm_cham) > 0): ?>
                    <a href="hangmuc/index.php?filter=cham" class="badge bg-danger text-decoration-none me-2 p-2">
                        <i class="fas fa-tasks me-1"></i><?php echo mysqli_num_rows($result_hm_cham); ?> hạng mục chậm
                    </a>
                <?php endif; ?>
                <?php if (mysqli_num_rows($result_hm_can_capnhat) > 0): ?>
                    <a href="hangmuc/index.php?filter=can_cap_nhat" class="badge bg-info text-decoration-none me-2 p-2">
                        <i class="fas fa-pen me-1"></i><?php echo mysqli_num_rows($result_hm_can_capnhat); ?> hạng mục cần cập nhật
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- ============================================ -->
<!-- 3. BIỂU ĐỒ VÀ THỐNG KÊ NHANH -->
<!-- ============================================ -->
<div class="row g-4 mb-4">
    <!-- Biểu đồ cột -->
    <div class="col-xl-8">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <i class="fas fa-chart-bar me-2 text-warning"></i>
                    <span>Tiến độ công trình</span>
                </div>
                <div class="btn-group btn-group-sm">
                    <button type="button" class="btn btn-outline-secondary active" onclick="changeChartType('bar')">
                        <i class="fas fa-chart-bar"></i>
                    </button>
                    <button type="button" class="btn btn-outline-secondary" onclick="changeChartType('line')">
                        <i class="fas fa-chart-line"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($labels)): ?>
                    <div class="text-center py-5">
                        <img src="<?php echo BASE_URL; ?>/assets/img/no-data.svg" alt="No data" style="max-width: 200px;" class="mb-3">
                        <p class="text-muted">Chưa có dữ liệu để hiển thị biểu đồ</p>
                        <a href="congtrinh/add.php" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus-circle me-2"></i>Thêm công trình
                        </a>
                    </div>
                <?php else: ?>
                    <canvas id="progressChart" style="height: 280px;"></canvas>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Biểu đồ tròn và menu nhanh -->
    <div class="col-xl-4">
        <div class="row g-4">
            <!-- Biểu đồ tròn -->
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-chart-pie me-2 text-warning"></i>
                        <span>Phân bố trạng thái</span>
                    </div>
                    <div class="card-body">
                        <?php if (array_sum($status_data) == 0): ?>
                            <div class="text-center py-3">
                                <p class="text-muted mb-0">Chưa có dữ liệu</p>
                            </div>
                        <?php else: ?>
                            <canvas id="statusChart" style="height: 180px;"></canvas>
                            <div class="row mt-3 g-2">
                                <div class="col-4 text-center">
                                    <div class="p-2 rounded bg-secondary bg-opacity-10">
                                        <span class="badge bg-secondary d-block p-2"><?php echo $status_data[0]; ?></span>
                                        <small class="text-muted">Chưa TC</small>
                                    </div>
                                </div>
                                <div class="col-4 text-center">
                                    <div class="p-2 rounded bg-warning bg-opacity-10">
                                        <span class="badge bg-warning d-block p-2"><?php echo $status_data[1]; ?></span>
                                        <small class="text-muted">Đang TC</small>
                                    </div>
                                </div>
                                <div class="col-4 text-center">
                                    <div class="p-2 rounded bg-success bg-opacity-10">
                                        <span class="badge bg-success d-block p-2"><?php echo $status_data[2]; ?></span>
                                        <small class="text-muted">Hoàn thành</small>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Menu truy cập nhanh -->
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-bolt me-2 text-warning"></i>
                        <span>Truy cập nhanh</span>
                    </div>
                    <div class="card-body">
                        <div class="row g-2">
                            <div class="col-6">
                                <a href="congtrinh/index.php" class="btn btn-outline-primary w-100">
                                    <i class="fas fa-building me-2"></i>Công trình
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="hangmuc/index.php" class="btn btn-outline-success w-100">
                                    <i class="fas fa-tasks me-2"></i>Hạng mục
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="ghichu/index.php" class="btn btn-outline-info w-100">
                                    <i class="fas fa-sticky-note me-2"></i>Ghi chú
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="baocao/index.php" class="btn btn-outline-warning w-100">
                                    <i class="fas fa-chart-bar me-2"></i>Báo cáo
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="hangmuc/history.php" class="btn btn-outline-secondary w-100">
                                    <i class="fas fa-history me-2"></i>Lịch sử
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="profile/activity.php" class="btn btn-outline-secondary w-100">
                                    <i class="fas fa-user-clock me-2"></i>Hoạt động
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- 4. CẢNH BÁO CHI TIẾT (4 CỘT) -->
<!-- ============================================ -->
<div class="row g-4 mb-4">
    <!-- Công trình quá hạn -->
    <div class="col-xl-3 col-md-6">
        <div class="card h-100 border-danger">
            <div class="card-header bg-danger text-white py-2">
                <div class="d-flex align-items-center">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <h6 class="mb-0">Công trình quá hạn</h6>
                    <span class="badge bg-light text-danger ms-auto"><?php echo mysqli_num_rows($result_qua_han); ?></span>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if (mysqli_num_rows($result_qua_han) == 0): ?>
                    <p class="text-muted text-center py-3 mb-0">
                        <i class="fas fa-check-circle text-success me-2"></i>Không có
                    </p>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php while ($row = mysqli_fetch_assoc($result_qua_han)): ?>
                        <a href="congtrinh/detail.php?id=<?php echo $row['id']; ?>" 
                           class="list-group-item list-group-item-action">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <small class="fw-bold"><?php echo htmlspecialchars($row['ten_cong_trinh']); ?></small>
                                    <br>
                                    <small class="text-muted">Quá <?php echo $row['so_ngay_qua_han']; ?> ngày</small>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-danger"><?php echo $row['hm_dang_lam']; ?> HM</span>
                                    <br>
                                    <a href="hangmuc/add.php?congtrinh_id=<?php echo $row['id']; ?>" class="text-muted small">
                                        <i class="fas fa-plus-circle"></i>
                                    </a>
                                </div>
                            </div>
                        </a>
                        <?php endwhile; ?>
                    </div>
                <?php endif; ?>
            </div>
            <?php if (mysqli_num_rows($result_qua_han) > 0): ?>
            <div class="card-footer bg-transparent p-2 text-center">
                <a href="congtrinh/index.php?filter=quahan" class="small">Xem tất cả <i class="fas fa-arrow-right ms-1"></i></a>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Công trình sắp đến hạn -->
    <div class="col-xl-3 col-md-6">
        <div class="card h-100 border-warning">
            <div class="card-header bg-warning text-dark py-2">
                <div class="d-flex align-items-center">
                    <i class="fas fa-clock me-2"></i>
                    <h6 class="mb-0">Sắp đến hạn</h6>
                    <span class="badge bg-light text-warning ms-auto"><?php echo mysqli_num_rows($result_sap_den_han); ?></span>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if (mysqli_num_rows($result_sap_den_han) == 0): ?>
                    <p class="text-muted text-center py-3 mb-0">
                        <i class="fas fa-check-circle text-success me-2"></i>Không có
                    </p>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php while ($row = mysqli_fetch_assoc($result_sap_den_han)): ?>
                        <a href="congtrinh/detail.php?id=<?php echo $row['id']; ?>" 
                           class="list-group-item list-group-item-action">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <small class="fw-bold"><?php echo htmlspecialchars($row['ten_cong_trinh']); ?></small>
                                    <br>
                                    <small class="text-muted">Còn <?php echo $row['so_ngay_con_lai']; ?> ngày</small>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-warning"><?php echo $row['hm_con_lai']; ?> HM</span>
                                    <br>
                                    <a href="hangmuc/add.php?congtrinh_id=<?php echo $row['id']; ?>" class="text-muted small">
                                        <i class="fas fa-plus-circle"></i>
                                    </a>
                                </div>
                            </div>
                        </a>
                        <?php endwhile; ?>
                    </div>
                <?php endif; ?>
            </div>
            <?php if (mysqli_num_rows($result_sap_den_han) > 0): ?>
            <div class="card-footer bg-transparent p-2 text-center">
                <a href="congtrinh/index.php?filter=sapdenhan" class="small">Xem tất cả <i class="fas fa-arrow-right ms-1"></i></a>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Hạng mục chậm -->
    <div class="col-xl-3 col-md-6">
        <div class="card h-100 border-danger">
            <div class="card-header bg-danger text-white py-2">
                <div class="d-flex align-items-center">
                    <i class="fas fa-tasks me-2"></i>
                    <h6 class="mb-0">Hạng mục chậm</h6>
                    <span class="badge bg-light text-danger ms-auto"><?php echo mysqli_num_rows($result_hm_cham); ?></span>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if (mysqli_num_rows($result_hm_cham) == 0): ?>
                    <p class="text-muted text-center py-3 mb-0">
                        <i class="fas fa-check-circle text-success me-2"></i>Không có
                    </p>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php while ($hm = mysqli_fetch_assoc($result_hm_cham)): ?>
                        <a href="hangmuc/update.php?id=<?php echo $hm['id']; ?>" 
                           class="list-group-item list-group-item-action">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <small class="fw-bold"><?php echo htmlspecialchars($hm['ten_hang_muc']); ?></small>
                                    <br>
                                    <small class="text-muted"><?php echo htmlspecialchars($hm['ten_cong_trinh']); ?></small>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-<?php echo getProgressColor($hm['phan_tram_tien_do']); ?>">
                                        <?php echo $hm['phan_tram_tien_do']; ?>%
                                    </span>
                                    <br>
                                    <small class="text-danger">Chậm <?php echo $hm['so_ngay_cham']; ?> ngày</small>
                                </div>
                            </div>
                        </a>
                        <?php endwhile; ?>
                    </div>
                <?php endif; ?>
            </div>
            <?php if (mysqli_num_rows($result_hm_cham) > 0): ?>
            <div class="card-footer bg-transparent p-2 text-center">
                <a href="hangmuc/index.php?filter=cham" class="small">Xem tất cả <i class="fas fa-arrow-right ms-1"></i></a>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Hạng mục cần cập nhật -->
    <div class="col-xl-3 col-md-6">
        <div class="card h-100 border-info">
            <div class="card-header bg-info text-white py-2">
                <div class="d-flex align-items-center">
                    <i class="fas fa-pen me-2"></i>
                    <h6 class="mb-0">Cần cập nhật</h6>
                    <span class="badge bg-light text-info ms-auto"><?php echo mysqli_num_rows($result_hm_can_capnhat); ?></span>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if (mysqli_num_rows($result_hm_can_capnhat) == 0): ?>
                    <p class="text-muted text-center py-3 mb-0">
                        <i class="fas fa-check-circle text-success me-2"></i>Không có
                    </p>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php while ($hm = mysqli_fetch_assoc($result_hm_can_capnhat)): ?>
                        <a href="hangmuc/update.php?id=<?php echo $hm['id']; ?>" 
                           class="list-group-item list-group-item-action">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <small class="fw-bold"><?php echo htmlspecialchars($hm['ten_hang_muc']); ?></small>
                                    <br>
                                    <small class="text-muted"><?php echo htmlspecialchars($hm['ten_cong_trinh']); ?></small>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-<?php echo getProgressColor($hm['phan_tram_tien_do']); ?>">
                                        <?php echo $hm['phan_tram_tien_do']; ?>%
                                    </span>
                                    <br>
                                    <small class="text-info">Còn <?php echo $hm['so_ngay_con_lai']; ?> ngày</small>
                                </div>
                            </div>
                        </a>
                        <?php endwhile; ?>
                    </div>
                <?php endif; ?>
            </div>
            <?php if (mysqli_num_rows($result_hm_can_capnhat) > 0): ?>
            <div class="card-footer bg-transparent p-2 text-center">
                <a href="hangmuc/index.php?filter=can_cap_nhat" class="small">Xem tất cả <i class="fas fa-arrow-right ms-1"></i></a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- 5. DANH SÁCH CÔNG TRÌNH -->
<!-- ============================================ -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <i class="fas fa-building me-2 text-warning"></i>
            <span>Danh sách công trình</span>
            <span class="badge bg-secondary ms-2"><?php echo $total_rows; ?></span>
        </div>
        <div class="d-flex gap-2">
            <a href="congtrinh/add.php" class="btn btn-sm btn-primary">
                <i class="fas fa-plus-circle me-1"></i>Thêm
            </a>
            <a href="congtrinh/index.php" class="btn btn-sm btn-outline-primary">
                Xem tất cả <i class="fas fa-arrow-right ms-1"></i>
            </a>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Tên công trình</th>
                        <th>Địa điểm</th>
                        <th>Tiến độ</th>
                        <th>Hạng mục</th>
                        <th>Ghi chú</th>
                        <th>Thời hạn</th>
                        <th>Trạng thái</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($result_congtrinh) == 0): ?>
                    <tr>
                        <td colspan="9" class="text-center py-5">
                            <i class="fas fa-building fa-3x text-muted mb-3"></i>
                            <h6 class="text-muted">Chưa có công trình nào</h6>
                            <a href="congtrinh/add.php" class="btn btn-primary btn-sm mt-2">
                                <i class="fas fa-plus-circle me-2"></i>Thêm công trình
                            </a>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php $stt = $offset + 1; ?>
                        <?php while ($ct = mysqli_fetch_assoc($result_congtrinh)): 
                            $tien_do = tinhTienDoCongTrinh($conn, $ct['id']);
                            $color = getProgressColor($tien_do);
                            $is_overdue = ($ct['trangthaiCT_id'] != 3 && strtotime($ct['ngay_ket_thuc']) < time());
                            $row_class = $is_overdue ? 'table-danger' : '';
                        ?>
                        <tr class="<?php echo $row_class; ?>">
                            <td><?php echo $stt++; ?></td>
                            <td>
                                <a href="congtrinh/detail.php?id=<?php echo $ct['id']; ?>" class="text-decoration-none fw-bold">
                                    <?php echo htmlspecialchars($ct['ten_cong_trinh']); ?>
                                </a>
                                <?php if ($ct['kinh_phi']): ?>
                                    <br><small class="text-muted"><?php echo formatMoney($ct['kinh_phi']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td><i class="fas fa-map-marker-alt me-1 text-muted"></i><?php echo htmlspecialchars($ct['dia_diem']); ?></td>
                            <td style="min-width: 120px;">
                                <div class="d-flex align-items-center">
                                    <div class="progress flex-grow-1 me-2" style="height: 6px;">
                                        <div class="progress-bar bg-<?php echo $color; ?>" 
                                             style="width: <?php echo $tien_do; ?>%"></div>
                                    </div>
                                    <span class="badge bg-<?php echo $color; ?>"><?php echo $tien_do; ?>%</span>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-info"><?php echo $ct['tong_hm']; ?></span>
                                <?php if ($ct['hm_quahan'] > 0): ?>
                                    <span class="badge bg-danger"><?php echo $ct['hm_quahan']; ?> chậm</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($ct['tong_ghichu'] > 0): ?>
                                    <a href="ghichu/index.php?congtrinh_id=<?php echo $ct['id']; ?>" class="text-decoration-none">
                                        <span class="badge bg-warning"><?php echo $ct['tong_ghichu']; ?></span>
                                    </a>
                                <?php else: ?>
                                    <span class="badge bg-secondary">0</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                $ngay_con = (strtotime($ct['ngay_ket_thuc']) - time()) / 86400;
                                $ngay_con = round($ngay_con);
                                if ($is_overdue): ?>
                                    <span class="badge bg-danger">Quá hạn</span>
                                <?php elseif ($ngay_con <= 7 && $ngay_con > 0): ?>
                                    <span class="badge bg-warning">Còn <?php echo $ngay_con; ?> ngày</span>
                                <?php else: ?>
                                    <small><?php echo formatDate($ct['ngay_ket_thuc']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo getStatusBadge($ct['ten_trang_thai']); ?>">
                                    <?php echo $ct['ten_trang_thai']; ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="congtrinh/detail.php?id=<?php echo $ct['id']; ?>" 
                                       class="btn btn-sm btn-outline-info" title="Chi tiết">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="congtrinh/edit.php?id=<?php echo $ct['id']; ?>" 
                                       class="btn btn-sm btn-outline-primary" title="Sửa">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="hangmuc/add.php?congtrinh_id=<?php echo $ct['id']; ?>" 
                                       class="btn btn-sm btn-outline-success" title="Thêm hạng mục">
                                        <i class="fas fa-plus-circle"></i>
                                    </a>
                                    <a href="ghichu/add.php?congtrinh_id=<?php echo $ct['id']; ?>" 
                                       class="btn btn-sm btn-outline-warning" title="Thêm ghi chú">
                                        <i class="fas fa-sticky-note"></i>
                                    </a>
                                    <a href="baocao/congtrinh.php?id=<?php echo $ct['id']; ?>" 
                                       class="btn btn-sm btn-outline-secondary" title="Báo cáo">
                                        <i class="fas fa-chart-bar"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Phân trang -->
    <?php if ($total_pages > 1): ?>
    <div class="card-footer bg-transparent">
        <nav aria-label="Page navigation">
            <ul class="pagination justify-content-center mb-0">
                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $page-1; ?>" tabindex="-1">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                </li>
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $page+1; ?>">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>

<!-- ============================================ -->
<!-- 6. HOẠT ĐỘNG VÀ GHI CHÚ -->
<!-- ============================================ -->
<div class="row g-4">
    <!-- Hoạt động gần đây -->
    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <i class="fas fa-history me-2 text-warning"></i>
                    <span>Hoạt động gần đây</span>
                </div>
                <a href="profile/activity.php" class="btn btn-sm btn-outline-primary">
                    Xem tất cả <i class="fas fa-arrow-right ms-2"></i>
                </a>
            </div>
            <div class="card-body p-0">
                <?php if (mysqli_num_rows($result_hoatdong) == 0): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-clock fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Chưa có hoạt động nào</p>
                    </div>
                <?php else: ?>
                    <div class="timeline px-4 py-3">
                        <?php while ($hd = mysqli_fetch_assoc($result_hoatdong)): ?>
                        <div class="timeline-item pb-3">
                            <div class="d-flex">
                                <div class="flex-shrink-0">
                                    <div class="timeline-icon">
                                        <i class="fas fa-circle"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <p class="mb-1 fw-bold"><?php echo htmlspecialchars($hd['hanh_dong']); ?></p>
                                    <p class="mb-1 small text-muted"><?php echo htmlspecialchars($hd['chi_tiet']); ?></p>
                                    <small class="text-muted">
                                        <i class="far fa-clock me-1"></i>
                                        <?php echo formatDateTime($hd['thoi_gian']); ?>
                                        <?php if ($hd['ip_address']): ?>
                                            <span class="ms-2">IP: <?php echo $hd['ip_address']; ?></span>
                                        <?php endif; ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Ghi chú gần đây -->
    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <i class="fas fa-sticky-note me-2 text-warning"></i>
                    <span>Ghi chú gần đây</span>
                </div>
                <div class="d-flex gap-2">
                    <a href="ghichu/add.php" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus-circle"></i>
                    </a>
                    <a href="ghichu/index.php" class="btn btn-sm btn-outline-primary">
                        Xem tất cả <i class="fas fa-arrow-right ms-2"></i>
                    </a>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if (mysqli_num_rows($result_ghichu_moi) == 0): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-comment-slash fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Chưa có ghi chú nào</p>
                        <a href="ghichu/add.php" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus-circle me-2"></i>Thêm ghi chú
                        </a>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php while ($gc = mysqli_fetch_assoc($result_ghichu_moi)): ?>
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between mb-2">
                                <div>
                                    <span class="badge bg-info"><?php echo htmlspecialchars($gc['ten_cong_trinh']); ?></span>
                                    <?php if ($gc['ten_hang_muc']): ?>
                                        <a href="hangmuc/detail.php?id=<?php echo $gc['hangmuc_id']; ?>" class="badge bg-secondary text-decoration-none">
                                            <?php echo htmlspecialchars($gc['ten_hang_muc']); ?>
                                        </a>
                                    <?php endif; ?>
                                </div>
                                <small class="text-muted"><?php echo formatDateTime($gc['ngay_ghi']); ?></small>
                            </div>
                            <p class="mb-2"><?php echo htmlspecialchars($gc['noi_dung']); ?></p>
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">
                                    <i class="fas fa-user me-1"></i> <?php echo htmlspecialchars($gc['hoten']); ?>
                                </small>
                                <div class="btn-group btn-group-sm">
                                    <a href="ghichu/detail.php?id=<?php echo $gc['id']; ?>" class="btn btn-outline-info" title="Chi tiết">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="ghichu/edit.php?id=<?php echo $gc['id']; ?>" class="btn btn-outline-primary" title="Sửa">
                                        <i class="fas fa-edit"></i>
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

<!-- ============================================ -->
<!-- STYLE CHO DASHBOARD -->
<!-- ============================================ -->
<style>
.stat-card {
    transition: all 0.3s ease;
    border: none;
    box-shadow: 0 2px 4px rgba(0,0,0,0.02);
    border-radius: 12px;
    cursor: pointer;
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
    font-size: 20px;
}

.bg-purple {
    background-color: #8b5cf6 !important;
}
.bg-purple.bg-opacity-10 {
    background-color: rgba(139, 92, 246, 0.1) !important;
}
.text-purple {
    color: #8b5cf6 !important;
}

.timeline {
    position: relative;
    max-height: 300px;
    overflow-y: auto;
}

.timeline-item {
    position: relative;
    border-left: 2px solid #f1f5f9;
    padding-left: 1.5rem;
    margin-left: 0.5rem;
}

.timeline-item:last-child {
    border-left-color: transparent;
}

.timeline-icon {
    position: absolute;
    left: -0.5rem;
    top: 0;
    width: 1rem;
    height: 1rem;
    border-radius: 50%;
    background: #fbbf24;
    display: flex;
    align-items: center;
    justify-content: center;
}

.timeline-icon i {
    font-size: 0.5rem;
    color: white;
}

.welcome-section {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 1.5rem;
    border-radius: 16px;
    color: white;
    margin-bottom: 2rem;
}

.welcome-section .btn-primary {
    background: white;
    border-color: white;
    color: #764ba2;
}

.welcome-section .btn-primary:hover {
    background: rgba(255,255,255,0.9);
    border-color: rgba(255,255,255,0.9);
}

.welcome-section .btn-outline-primary {
    border-color: white;
    color: white;
}

.welcome-section .btn-outline-primary:hover {
    background: rgba(255,255,255,0.1);
    color: white;
}

.card {
    border: none;
    border-radius: 12px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.02);
}

.card-header {
    background: transparent;
    border-bottom: 1px solid #f1f5f9;
    font-weight: 600;
    padding: 1rem 1.25rem;
}

.table thead th {
    background: #f8fafc;
    font-weight: 600;
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    color: #64748b;
    border-bottom: 2px solid #e2e8f0;
    padding: 0.75rem;
}

.table tbody td {
    padding: 1rem 0.75rem;
    vertical-align: middle;
}

.badge {
    font-weight: 500;
    padding: 0.4em 0.8em;
}

.btn-group .btn {
    padding: 0.25rem 0.5rem;
}

.progress {
    background: #f1f5f9;
    border-radius: 999px;
}

.pagination .page-link {
    border: none;
    color: #64748b;
    padding: 0.5rem 1rem;
    margin: 0 2px;
    border-radius: 8px;
}

.pagination .page-item.active .page-link {
    background: #fbbf24;
    color: #000;
}

.pagination .page-item.disabled .page-link {
    color: #cbd5e1;
    pointer-events: none;
}

.list-group-item {
    border-left: none;
    border-right: none;
    padding: 1rem 1.25rem;
    transition: all 0.3s;
}

.list-group-item:hover {
    background: #f8fafc;
}

.list-group-item:first-child {
    border-top: none;
}

.list-group-item:last-child {
    border-bottom: none;
}
</style>

<!-- ============================================ -->
<!-- SCRIPT CHO BIỂU ĐỒ -->
<!-- ============================================ -->
<!-- ============================================ -->
<!-- SCRIPT CHO BIỂU ĐỒ - ĐÃ SỬA LỖI -->
<!-- ============================================ -->
<?php if (!empty($labels)): ?>
<script>
let progressChart;

function initChart(type) {
    const ctx = document.getElementById('progressChart').getContext('2d');
    
    // Set kích thước cố định cho canvas
    const canvas = document.getElementById('progressChart');
    canvas.style.height = '280px';
    canvas.style.width = '100%';
    
    if (progressChart) {
        progressChart.destroy();
    }
    
    const chartData = {
        labels: [<?php foreach($labels as $label) { echo '"' . addslashes($label) . '",'; } ?>],
        datasets: [{
            label: 'Tiến độ (%)',
            data: [<?php echo implode(',', $progress_data); ?>],
            backgroundColor: [
                <?php foreach($progress_data as $index => $p) { 
                    if ($overdue_data[$index] ?? 0) {
                        echo "'#ef4444',";
                    } elseif($p < 30) echo "'#ef4444',";
                    elseif($p < 70) echo "'#fbbf24',";
                    elseif($p < 100) echo "'#3b82f6',";
                    else echo "'#10b981',";
                } ?>
            ],
            borderColor: '#3b82f6',
            borderWidth: type === 'line' ? 2 : 0,
            tension: 0.3,
            fill: type === 'line' ? false : true,
            borderRadius: 8,
            barPercentage: 0.6,
            categoryPercentage: 0.8
        }]
    };
    
    if (type === 'line') {
        chartData.datasets.push({
            label: 'Mục tiêu',
            data: Array(<?php echo count($progress_data); ?>).fill(100),
            borderColor: '#94a3b8',
            borderWidth: 1,
            borderDash: [5, 5],
            fill: false,
            pointRadius: 0,
            tension: 0
        });
    }
    
    progressChart = new Chart(ctx, {
        type: type,
        data: chartData,
        options: {
            responsive: true,
            maintainAspectRatio: true, // Đổi thành true
            aspectRatio: 2, // Thêm tỷ lệ khung hình
            plugins: {
                legend: {
                    display: type === 'line',
                    position: 'top',
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + context.raw + '%';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    grid: {
                        color: '#f1f5f9',
                        drawBorder: false
                    },
                    ticks: {
                        callback: function(value) {
                            return value + '%';
                        },
                        stepSize: 20
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        maxRotation: 45,
                        minRotation: 45
                    }
                }
            }
        }
    });
}

function changeChartType(type) {
    initChart(type);
    
    // Thay thế jQuery bằng JavaScript thuần
    const buttons = document.querySelectorAll('.btn-group .btn');
    buttons.forEach(btn => btn.classList.remove('active'));
    
    // Tìm button có nội dung phù hợp
    buttons.forEach(btn => {
        if (btn.textContent.includes(type === 'bar' ? 'bar' : 'line')) {
            btn.classList.add('active');
        }
    });
}

// Khởi tạo khi trang load xong
document.addEventListener('DOMContentLoaded', function() {
    initChart('bar');
});

<?php if (array_sum($status_data) > 0): ?>
// Biểu đồ tròn
const ctx2 = document.getElementById('statusChart').getContext('2d');
const statusCanvas = document.getElementById('statusChart');
statusCanvas.style.height = '180px';
statusCanvas.style.width = '100%';

new Chart(ctx2, {
    type: 'doughnut',
    data: {
        labels: ['Chưa thi công', 'Đang thi công', 'Hoàn thành'],
        datasets: [{
            data: [<?php echo $status_data[0]; ?>, <?php echo $status_data[1]; ?>, <?php echo $status_data[2]; ?>],
            backgroundColor: ['#94a3b8', '#fbbf24', '#10b981'],
            borderWidth: 0,
            hoverOffset: 4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        aspectRatio: 1.5,
        cutout: '65%',
        plugins: {
            legend: {
                display: false
            }
        }
    }
});
<?php endif; ?>
</script>
<?php endif; ?>

<!-- ============================================ -->
<!-- GHI LOG HOẠT ĐỘNG -->
<!-- ============================================ -->
<?php
if (function_exists('logActivity')) {
    logActivity($conn, $user_id, 'Xem dashboard', 'Truy cập trang tổng quan');
}

require_once 'includes/footer.php';
?>