<?php
// congtrinh/index.php
require_once '../includes/header.php';

$user_id = $_SESSION['id'];
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Xử lý bộ lọc
$where = "WHERE ct.user_id = '$user_id'";
$params = [];

if(isset($_GET['keyword']) && !empty($_GET['keyword'])) {
    $keyword = mysqli_real_escape_string($conn, $_GET['keyword']);
    $where .= " AND (ct.ten_cong_trinh LIKE '%$keyword%' OR ct.dia_diem LIKE '%$keyword%')";
}

if(isset($_GET['trangthai']) && !empty($_GET['trangthai'])) {
    $trangthai = (int)$_GET['trangthai'];
    $where .= " AND ct.trangthaiCT_id = $trangthai";
}

if(isset($_GET['loai']) && !empty($_GET['loai'])) {
    $loai = (int)$_GET['loai'];
    $where .= " AND ct.loaiCT_id = $loai";
}

// Xử lý lọc theo thời gian
if(isset($_GET['thoigian']) && !empty($_GET['thoigian'])) {
    $today = date('Y-m-d');
    switch($_GET['thoigian']) {
        case 'tuan':
            $next_week = date('Y-m-d', strtotime('+7 days'));
            $where .= " AND ct.ngay_ket_thuc BETWEEN '$today' AND '$next_week'";
            break;
        case 'thang':
            $next_month = date('Y-m-d', strtotime('+30 days'));
            $where .= " AND ct.ngay_ket_thuc BETWEEN '$today' AND '$next_month'";
            break;
        case 'quahan':
            $where .= " AND ct.ngay_ket_thuc < '$today' AND ct.trangthaiCT_id != 3";
            break;
        case 'sapdenhan':
            $next_week = date('Y-m-d', strtotime('+7 days'));
            $where .= " AND ct.ngay_ket_thuc BETWEEN '$today' AND '$next_week' AND ct.trangthaiCT_id != 3";
            break;
    }
}

// Đếm tổng số bản ghi
$sql_count = "SELECT COUNT(*) as total FROM congtrinh ct $where";
$result_count = mysqli_query($conn, $sql_count);
$total_rows = mysqli_fetch_assoc($result_count)['total'];
$total_pages = ceil($total_rows / $limit);

// Lấy danh sách công trình
$sql = "SELECT ct.*, lct.ten_loai, ttct.ten_trang_thai, u.hoten,
        (SELECT COUNT(*) FROM hangmucthicong WHERE congtrinh_id = ct.id) as tong_hm,
        (SELECT COUNT(*) FROM hangmucthicong WHERE congtrinh_id = ct.id AND trang_thai = 'Hoàn thành') as hm_ht,
        (SELECT COUNT(*) FROM hangmucthicong WHERE congtrinh_id = ct.id AND ngay_ket_thuc < CURDATE() AND trang_thai != 'Hoàn thành') as hm_quahan,
        (SELECT COUNT(*) FROM ghichuthicong gc LEFT JOIN hangmucthicong hm ON gc.hangmuc_id = hm.id WHERE hm.congtrinh_id = ct.id) as tong_ghichu
        FROM congtrinh ct
        LEFT JOIN loaicongtrinh lct ON ct.loaiCT_id = lct.id
        LEFT JOIN trangthaicongtrinh ttct ON ct.trangthaiCT_id = ttct.id
        LEFT JOIN users u ON ct.user_id = u.id
        $where
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
$result = mysqli_query($conn, $sql);

// Thống kê nhanh
$sql_thongke = "SELECT 
                COUNT(*) as tong,
                SUM(CASE WHEN trangthaiCT_id = 2 THEN 1 ELSE 0 END) as dangtc,
                SUM(CASE WHEN trangthaiCT_id = 3 THEN 1 ELSE 0 END) as hoanthanh,
                SUM(CASE WHEN trangthaiCT_id = 1 THEN 1 ELSE 0 END) as chuatc,
                SUM(CASE WHEN ngay_ket_thuc < CURDATE() AND trangthaiCT_id != 3 THEN 1 ELSE 0 END) as quahan
                FROM congtrinh 
                WHERE user_id = '$user_id'";
$result_thongke = mysqli_query($conn, $sql_thongke);
$thongke = mysqli_fetch_assoc($result_thongke);
?>

<div class="content-wrapper">
    <!-- Page Header -->
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center">
            <h4 class="mb-0">
                <i class="fas fa-building me-2 text-warning"></i>
                Quản lý công trình
            </h4>
            <div>
                <a href="add.php" class="btn btn-primary">
                    <i class="fas fa-plus-circle me-2"></i>Thêm công trình
                </a>
                <button onclick="exportExcel()" class="btn btn-success">
                    <i class="fas fa-file-excel me-2"></i>Xuất Excel
                </button>
            </div>
        </div>
    </div>

    <!-- Thống kê nhanh -->
    <div class="row g-3 mb-4">
        <div class="col-md-2 col-6">
            <div class="stat-card bg-primary text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1">Tổng số</h6>
                        <h3 class="mb-0"><?php echo $thongke['tong'] ?? 0; ?></h3>
                    </div>
                    <i class="fas fa-building fa-2x opacity-50"></i>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-6">
            <div class="stat-card bg-warning text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1">Đang thi công</h6>
                        <h3 class="mb-0"><?php echo $thongke['dangtc'] ?? 0; ?></h3>
                    </div>
                    <i class="fas fa-spinner fa-2x opacity-50"></i>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-6">
            <div class="stat-card bg-success text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1">Hoàn thành</h6>
                        <h3 class="mb-0"><?php echo $thongke['hoanthanh'] ?? 0; ?></h3>
                    </div>
                    <i class="fas fa-check-circle fa-2x opacity-50"></i>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-6">
            <div class="stat-card bg-info text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1">Chưa thi công</h6>
                        <h3 class="mb-0"><?php echo $thongke['chuatc'] ?? 0; ?></h3>
                    </div>
                    <i class="fas fa-clock fa-2x opacity-50"></i>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-6">
            <div class="stat-card bg-danger text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1">Quá hạn</h6>
                        <h3 class="mb-0"><?php echo $thongke['quahan'] ?? 0; ?></h3>
                    </div>
                    <i class="fas fa-exclamation-triangle fa-2x opacity-50"></i>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-6">
            <div class="stat-card bg-secondary text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1">Tổng kinh phí</h6>
                        <h6 class="mb-0"><?php echo formatMoney($tong_kinhphi ?? 0); ?></h6>
                    </div>
                    <i class="fas fa-coins fa-2x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Bộ lọc -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <input type="text" name="keyword" class="form-control" 
                           placeholder="Tìm kiếm..." value="<?php echo $_GET['keyword'] ?? ''; ?>">
                </div>
                <div class="col-md-2">
                    <select name="trangthai" class="form-select">
                        <option value="">Tất cả trạng thái</option>
                        <option value="1" <?php echo ($_GET['trangthai'] ?? '') == '1' ? 'selected' : ''; ?>>Chưa thi công</option>
                        <option value="2" <?php echo ($_GET['trangthai'] ?? '') == '2' ? 'selected' : ''; ?>>Đang thi công</option>
                        <option value="3" <?php echo ($_GET['trangthai'] ?? '') == '3' ? 'selected' : ''; ?>>Hoàn thành</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="loai" class="form-select">
                        <option value="">Tất cả loại</option>
                        <?php 
                        $loai = mysqli_query($conn, "SELECT * FROM loaicongtrinh");
                        while($row = mysqli_fetch_assoc($loai)): 
                        ?>
                        <option value="<?php echo $row['id']; ?>" 
                            <?php echo ($_GET['loai'] ?? '') == $row['id'] ? 'selected' : ''; ?>>
                            <?php echo $row['ten_loai']; ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="thoigian" class="form-select">
                        <option value="">Thời gian</option>
                        <option value="tuan" <?php echo ($_GET['thoigian'] ?? '') == 'tuan' ? 'selected' : ''; ?>>Trong tuần</option>
                        <option value="thang" <?php echo ($_GET['thoigian'] ?? '') == 'thang' ? 'selected' : ''; ?>>Trong tháng</option>
                        <option value="quahan" <?php echo ($_GET['thoigian'] ?? '') == 'quahan' ? 'selected' : ''; ?>>Quá hạn</option>
                        <option value="sapdenhan" <?php echo ($_GET['thoigian'] ?? '') == 'sapdenhan' ? 'selected' : ''; ?>>Sắp đến hạn</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter me-2"></i>Lọc
                    </button>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-undo me-2"></i>Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Danh sách công trình -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th width="40">
                                <input type="checkbox" id="selectAll">
                            </th>
                            <th>Mã CT</th>
                            <th>Tên công trình</th>
                            <th>Địa điểm</th>
                            <th>Loại</th>
                            <th>Tiến độ</th>
                            <th>Hạng mục</th>
                            <th>Thời gian</th>
                            <th>Trạng thái</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(mysqli_num_rows($result) == 0): ?>
                        <tr>
                            <td colspan="10" class="text-center py-5">
                                <i class="fas fa-building fa-3x text-muted mb-3"></i>
                                <h6 class="text-muted">Chưa có công trình nào</h6>
                                <a href="add.php" class="btn btn-primary btn-sm mt-2">
                                    <i class="fas fa-plus-circle me-2"></i>Thêm công trình
                                </a>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php $stt = $offset + 1; ?>
                            <?php while($ct = mysqli_fetch_assoc($result)): 
                                $tien_do = tinhTienDoCongTrinh($conn, $ct['id']);
                                $is_overdue = ($ct['trangthaiCT_id'] != 3 && strtotime($ct['ngay_ket_thuc']) < time());
                                $row_class = $is_overdue ? 'table-danger' : '';
                            ?>
                            <tr class="<?php echo $row_class; ?>">
                                <td>
                                    <input type="checkbox" name="ids[]" value="<?php echo $ct['id']; ?>">
                                </td>
                                <td>
                                    <span class="badge bg-secondary">
                                        CT-<?php echo str_pad($ct['id'], 4, '0', STR_PAD_LEFT); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="detail.php?id=<?php echo $ct['id']; ?>" 
                                       class="text-decoration-none fw-bold">
                                        <?php echo htmlspecialchars($ct['ten_cong_trinh']); ?>
                                    </a>
                                </td>
                                <td>
                                    <i class="fas fa-map-marker-alt text-muted me-1"></i>
                                    <?php echo htmlspecialchars($ct['dia_diem']); ?>
                                </td>
                                <td>
                                    <span class="badge bg-info">
                                        <?php echo $ct['ten_loai']; ?>
                                    </span>
                                </td>
                                <td style="min-width: 120px;">
                                    <div class="d-flex align-items-center">
                                        <div class="progress flex-grow-1 me-2" style="height: 6px;">
                                            <div class="progress-bar bg-<?php echo getProgressColor($tien_do); ?>" 
                                                 style="width: <?php echo $tien_do; ?>%"></div>
                                        </div>
                                        <span class="badge bg-<?php echo getProgressColor($tien_do); ?>">
                                            <?php echo $tien_do; ?>%
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-info"><?php echo $ct['tong_hm']; ?> HM</span>
                                    <?php if($ct['hm_quahan'] > 0): ?>
                                        <span class="badge bg-danger"><?php echo $ct['hm_quahan']; ?> chậm</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php 
                                    $start = date('d/m', strtotime($ct['ngay_bat_dau']));
                                    $end = date('d/m/Y', strtotime($ct['ngay_ket_thuc']));
                                    ?>
                                    <small class="text-muted d-block">
                                        <i class="far fa-calendar-alt"></i> <?php echo $start; ?>
                                    </small>
                                    <small class="text-muted">
                                        <i class="far fa-calendar-check"></i> <?php echo $end; ?>
                                    </small>
                                </td>
                                <td>
                                    <?php 
                                    $status_class = '';
                                    $status_text = $ct['ten_trang_thai'];
                                    
                                    if($is_overdue) {
                                        $status_class = 'danger';
                                        $status_text = 'Quá hạn';
                                    } else {
                                        switch($ct['trangthaiCT_id']) {
                                            case 1: $status_class = 'secondary'; break;
                                            case 2: $status_class = 'warning'; break;
                                            case 3: $status_class = 'success'; break;
                                        }
                                    }
                                    ?>
                                    <span class="badge bg-<?php echo $status_class; ?>">
                                        <?php echo $status_text; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="detail.php?id=<?php echo $ct['id']; ?>" 
                                           class="btn btn-sm btn-outline-info" title="Chi tiết">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="edit.php?id=<?php echo $ct['id']; ?>" 
                                           class="btn btn-sm btn-outline-primary" title="Sửa">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="progress.php?id=<?php echo $ct['id']; ?>" 
                                           class="btn btn-sm btn-outline-success" title="Tiến độ">
                                            <i class="fas fa-chart-line"></i>
                                        </a>
                                        <button onclick="confirmDelete(<?php echo $ct['id']; ?>)" 
                                                class="btn btn-sm btn-outline-danger" title="Xóa">
                                            <i class="fas fa-trash"></i>
                                        </button>
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
        <?php if($total_pages > 1): ?>
        <div class="card-footer">
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center mb-0">
                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page-1; ?><?php echo $query_string; ?>">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    </li>
                    <?php for($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?><?php echo $query_string; ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                    <?php endfor; ?>
                    <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page+1; ?><?php echo $query_string; ?>">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Select all checkbox
document.getElementById('selectAll').addEventListener('change', function(e) {
    document.querySelectorAll('input[name="ids[]"]').forEach(cb => cb.checked = e.target.checked);
});

// Confirm delete
function confirmDelete(id) {
    if(confirm('Bạn có chắc muốn xóa công trình này? Tất cả hạng mục và ghi chú liên quan sẽ bị xóa!')) {
        window.location.href = 'delete.php?id=' + id;
    }
}

// Export Excel
function exportExcel() {
    const params = new URLSearchParams(window.location.search).toString();
    window.location.href = 'export.php?' + params;
}
</script>

<?php require_once '../includes/footer.php'; ?>