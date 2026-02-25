<?php
// baocao/congtrinh.php
$page_title = 'Báo cáo công trình';
require_once '../includes/header.php';

$user_id = $_SESSION['id'];
$congtrinh_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Lấy danh sách công trình
$sql_list = "SELECT id, ten_cong_trinh FROM congtrinh WHERE user_id = '$user_id' ORDER BY ten_cong_trinh";
$result_list = mysqli_query($conn, $sql_list);

// Nếu có ID, lấy thông tin chi tiết
$ct = null;
$thongke = null;
$hangmuc_list = null;
$lichsu_list = null;

if($congtrinh_id > 0) {
    // Kiểm tra quyền
    $check = mysqli_query($conn, "SELECT id FROM congtrinh WHERE id = $congtrinh_id AND user_id = '$user_id'");
    if(mysqli_num_rows($check) == 0) {
        $congtrinh_id = 0;
    }
}

if($congtrinh_id > 0) {
    // Thông tin công trình
    $sql_ct = "SELECT ct.*, lct.ten_loai, ttct.ten_trang_thai 
               FROM congtrinh ct
               LEFT JOIN loaicongtrinh lct ON ct.loaiCT_id = lct.id
               LEFT JOIN trangthaicongtrinh ttct ON ct.trangthaiCT_id = ttct.id
               WHERE ct.id = $congtrinh_id";
    $result_ct = mysqli_query($conn, $sql_ct);
    $ct = mysqli_fetch_assoc($result_ct);
    
    // Thống kê hạng mục
    $sql_thongke = "SELECT 
                    COUNT(*) as tong,
                    SUM(CASE WHEN trang_thai = 'Hoàn thành' THEN 1 ELSE 0 END) as hoanthanh,
                    SUM(CASE WHEN trang_thai = 'Đang thi công' THEN 1 ELSE 0 END) as dangtc,
                    SUM(CASE WHEN trang_thai = 'Chưa thi công' THEN 1 ELSE 0 END) as chuatc,
                    SUM(CASE WHEN ngay_ket_thuc < CURDATE() AND trang_thai != 'Hoàn thành' THEN 1 ELSE 0 END) as quahan,
                    SUM(kinh_phi) as tong_kinhphi,
                    AVG(phan_tram_tien_do) as tb_tiendo
                    FROM hangmucthicong 
                    WHERE congtrinh_id = $congtrinh_id";
    $result_thongke = mysqli_query($conn, $sql_thongke);
    $thongke = mysqli_fetch_assoc($result_thongke);
    
    // Danh sách hạng mục
    $sql_hm = "SELECT *,
               DATEDIFF(ngay_ket_thuc, CURDATE()) as so_ngay_con
               FROM hangmucthicong 
               WHERE congtrinh_id = $congtrinh_id
               ORDER BY 
                 CASE 
                   WHEN trang_thai != 'Hoàn thành' AND ngay_ket_thuc < CURDATE() THEN 1
                   WHEN trang_thai = 'Đang thi công' AND ngay_ket_thuc BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 2
                   ELSE 3
                 END,
                 ngay_ket_thuc ASC";
    $hangmuc_list = mysqli_query($conn, $sql_hm);
    
    // Lịch sử cập nhật
    $sql_ls = "SELECT ls.*, hm.ten_hang_muc 
               FROM lichsucapnhat ls
               LEFT JOIN hangmucthicong hm ON ls.hangmuc_id = hm.id
               WHERE hm.congtrinh_id = $congtrinh_id
               ORDER BY ls.thoi_gian_cap_nhat DESC
               LIMIT 20";
    $lichsu_list = mysqli_query($conn, $sql_ls);
}
?>

<div class="content-wrapper">
    <!-- Page Header -->
    <div class="page-header d-flex justify-content-between align-items-center flex-wrap">
        <div>
            <h4 class="mb-1">
                <i class="fas fa-building me-2 text-warning"></i>
                Báo cáo chi tiết công trình
            </h4>
            <p class="text-muted mb-0">Xem báo cáo tiến độ theo từng công trình</p>
        </div>
        <div>
            <a href="index.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Quay lại
            </a>
        </div>
    </div>

    <!-- Chọn công trình -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-center">
                <div class="col-auto">
                    <label class="form-label mb-0">Chọn công trình:</label>
                </div>
                <div class="col-md-4">
                    <select name="id" class="form-select" onchange="this.form.submit()">
                        <option value="">-- Chọn công trình --</option>
                        <?php while($row = mysqli_fetch_assoc($result_list)): ?>
                        <option value="<?php echo $row['id']; ?>" 
                            <?php echo $congtrinh_id == $row['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($row['ten_cong_trinh']); ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <?php if($congtrinh_id > 0 && $ct): ?>
                <div class="col-auto">
                    <a href="export.php?type=congtrinh&id=<?php echo $congtrinh_id; ?>" class="btn btn-success">
                        <i class="fas fa-file-excel me-2"></i>Xuất báo cáo
                    </a>
                </div>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <?php if($congtrinh_id > 0 && $ct): ?>
    <!-- Thông tin công trình -->
    <div class="row g-4 mb-4">
        <div class="col-md-8">
            <div class="card h-100">
                <div class="card-header">
                    <i class="fas fa-info-circle me-2 text-warning"></i>
                    Thông tin công trình
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tr>
                            <th style="width: 200px;">Tên công trình</th>
                            <td><?php echo htmlspecialchars($ct['ten_cong_trinh']); ?></td>
                        </tr>
                        <tr>
                            <th>Mã công trình</th>
                            <td><?php echo $ct['ma_cong_trinh'] ?? 'CT-'.str_pad($ct['id'], 4, '0', STR_PAD_LEFT); ?></td>
                        </tr>
                        <tr>
                            <th>Địa điểm</th>
                            <td><?php echo htmlspecialchars($ct['dia_diem']); ?></td>
                        </tr>
                        <tr>
                            <th>Loại công trình</th>
                            <td><?php echo $ct['ten_loai']; ?></td>
                        </tr>
                        <tr>
                            <th>Thời gian</th>
                            <td>
                                <?php echo date('d/m/Y', strtotime($ct['ngay_bat_dau'])); ?> - 
                                <?php echo date('d/m/Y', strtotime($ct['ngay_ket_thuc'])); ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Kinh phí</th>
                            <td><?php echo formatMoney($ct['kinh_phi']); ?></td>
                        </tr>
                        <tr>
                            <th>Trạng thái</th>
                            <td>
                                <span class="badge bg-<?php 
                                    echo $ct['trangthaiCT_id'] == 1 ? 'secondary' : 
                                        ($ct['trangthaiCT_id'] == 2 ? 'warning' : 'success'); 
                                ?>">
                                    <?php echo $ct['ten_trang_thai']; ?>
                                </span>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header">
                    <i class="fas fa-chart-pie me-2 text-warning"></i>
                    Thống kê nhanh
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <h2 class="mb-0"><?php echo round($thongke['tb_tiendo'] ?? 0); ?>%</h2>
                        <p class="text-muted">Tiến độ trung bình</p>
                        <div class="progress" style="height: 10px;">
                            <div class="progress-bar bg-<?php echo getProgressColor($thongke['tb_tiendo'] ?? 0); ?>" 
                                 style="width: <?php echo round($thongke['tb_tiendo'] ?? 0); ?>%"></div>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="p-3 bg-light rounded text-center">
                                <h4 class="mb-1"><?php echo $thongke['tong'] ?? 0; ?></h4>
                                <small class="text-muted">Tổng HM</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 bg-success bg-opacity-10 rounded text-center">
                                <h4 class="mb-1"><?php echo $thongke['hoanthanh'] ?? 0; ?></h4>
                                <small class="text-success">Hoàn thành</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 bg-warning bg-opacity-10 rounded text-center">
                                <h4 class="mb-1"><?php echo $thongke['dangtc'] ?? 0; ?></h4>
                                <small class="text-warning">Đang TC</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 bg-secondary bg-opacity-10 rounded text-center">
                                <h4 class="mb-1"><?php echo $thongke['chuatc'] ?? 0; ?></h4>
                                <small class="text-secondary">Chưa TC</small>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="p-3 bg-danger bg-opacity-10 rounded text-center">
                                <h4 class="mb-1"><?php echo $thongke['quahan'] ?? 0; ?></h4>
                                <small class="text-danger">Quá hạn</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Danh sách hạng mục -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-tasks me-2 text-warning"></i>
            Chi tiết hạng mục thi công
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Tên hạng mục</th>
                            <th>Tiến độ</th>
                            <th>Trạng thái</th>
                            <th>Thời gian</th>
                            <th>Kinh phí</th>
                            <th>Cập nhật cuối</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(mysqli_num_rows($hangmuc_list) == 0): ?>
                        <tr>
                            <td colspan="6" class="text-center py-4">
                                <p class="text-muted mb-0">Chưa có hạng mục nào</p>
                            </td>
                        </tr>
                        <?php else: 
                            while($hm = mysqli_fetch_assoc($hangmuc_list)): 
                                $is_overdue = ($hm['trang_thai'] != 'Hoàn thành' && $hm['so_ngay_con'] < 0);
                        ?>
                        <tr class="<?php echo $is_overdue ? 'table-danger' : ''; ?>">
                            <td><?php echo htmlspecialchars($hm['ten_hang_muc']); ?></td>
                            <td style="min-width: 120px;">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="progress flex-grow-1" style="height: 6px;">
                                        <div class="progress-bar bg-<?php echo getProgressColor($hm['phan_tram_tien_do']); ?>" 
                                             style="width: <?php echo $hm['phan_tram_tien_do']; ?>%"></div>
                                    </div>
                                    <span class="badge bg-<?php echo getProgressColor($hm['phan_tram_tien_do']); ?>">
                                        <?php echo $hm['phan_tram_tien_do']; ?>%
                                    </span>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo $hm['trang_thai'] == 'Hoàn thành' ? 'success' : 
                                        ($hm['trang_thai'] == 'Đang thi công' ? 'warning' : 'secondary'); 
                                ?>">
                                    <?php echo $hm['trang_thai']; ?>
                                </span>
                                <?php if($is_overdue): ?>
                                    <br><small class="text-danger">Quá hạn <?php echo abs($hm['so_ngay_con']); ?> ngày</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <small>
                                    <?php echo date('d/m/Y', strtotime($hm['ngay_ket_thuc'])); ?>
                                </small>
                            </td>
                            <td><?php echo formatMoney($hm['kinh_phi']); ?></td>
                            <td>
                                <small class="text-muted">
                                    <?php echo date('d/m/Y H:i', strtotime($hm['updated_at'] ?? $hm['ngay_tao'] ?? 'now')); ?>
                                </small>
                            </td>
                        </tr>
                        <?php endwhile; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Lịch sử cập nhật -->
    <div class="card">
        <div class="card-header">
            <i class="fas fa-history me-2 text-warning"></i>
            Lịch sử cập nhật tiến độ
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Thời gian</th>
                            <th>Hạng mục</th>
                            <th>Thay đổi</th>
                            <th>Ghi chú</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(mysqli_num_rows($lichsu_list) == 0): ?>
                        <tr>
                            <td colspan="4" class="text-center py-4">
                                <p class="text-muted mb-0">Chưa có lịch sử cập nhật</p>
                            </td>
                        </tr>
                        <?php else: 
                            while($ls = mysqli_fetch_assoc($lichsu_list)): 
                        ?>
                        <tr>
                            <td><?php echo date('d/m/Y H:i', strtotime($ls['thoi_gian_cap_nhat'])); ?></td>
                            <td><?php echo htmlspecialchars($ls['ten_hang_muc']); ?></td>
                            <td>
                                <span class="badge bg-secondary"><?php echo $ls['phan_tram_cu']; ?>%</span>
                                <i class="fas fa-arrow-right mx-2"></i>
                                <span class="badge bg-<?php echo getProgressColor($ls['phan_tram_moi']); ?>">
                                    <?php echo $ls['phan_tram_moi']; ?>%
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($ls['ghi_chu'] ?? ''); ?></td>
                        </tr>
                        <?php endwhile; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>