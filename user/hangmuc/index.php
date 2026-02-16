<?php
// hangmuc/index.php
require_once '../includes/header.php';

$user_id = $_SESSION['id'];
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Xử lý bộ lọc
$where = "WHERE ct.user_id = '$user_id'";
$params = [];

if(isset($_GET['congtrinh_id']) && !empty($_GET['congtrinh_id'])) {
    $congtrinh_id = (int)$_GET['congtrinh_id'];
    $where .= " AND hm.congtrinh_id = $congtrinh_id";
}

if(isset($_GET['trangthai']) && !empty($_GET['trangthai'])) {
    $trangthai = mysqli_real_escape_string($conn, $_GET['trangthai']);
    $where .= " AND hm.trang_thai = '$trangthai'";
}

if(isset($_GET['keyword']) && !empty($_GET['keyword'])) {
    $keyword = mysqli_real_escape_string($conn, $_GET['keyword']);
    $where .= " AND (hm.ten_hang_muc LIKE '%$keyword%' OR hm.ghi_chu LIKE '%$keyword%')";
}

if(isset($_GET['thoigian']) && !empty($_GET['thoigian'])) {
    $today = date('Y-m-d');
    switch($_GET['thoigian']) {
        case 'quahan':
            $where .= " AND hm.ngay_ket_thuc < '$today' AND hm.trang_thai != 'Hoàn thành'";
            break;
        case 'sapdenhan':
            $next_week = date('Y-m-d', strtotime('+7 days'));
            $where .= " AND hm.ngay_ket_thuc BETWEEN '$today' AND '$next_week' AND hm.trang_thai != 'Hoàn thành'";
            break;
        case 'hoanthanh':
            $where .= " AND hm.trang_thai = 'Hoàn thành'";
            break;
    }
}

// Đếm tổng số bản ghi
$sql_count = "SELECT COUNT(*) as total 
              FROM hangmucthicong hm
              LEFT JOIN congtrinh ct ON hm.congtrinh_id = ct.id
              $where";
$result_count = mysqli_query($conn, $sql_count);
$total_rows = mysqli_fetch_assoc($result_count)['total'];
$total_pages = ceil($total_rows / $limit);

// Lấy danh sách hạng mục
$sql = "SELECT hm.*, ct.ten_cong_trinh, ct.ma_cong_trinh,
        DATEDIFF(hm.ngay_ket_thuc, CURDATE()) as so_ngay_con,
        (SELECT COUNT(*) FROM ghichuthicong WHERE hangmuc_id = hm.id) as so_ghichu
        FROM hangmucthicong hm
        LEFT JOIN congtrinh ct ON hm.congtrinh_id = ct.id
        $where
        ORDER BY 
            CASE 
                WHEN hm.trang_thai != 'Hoàn thành' AND hm.ngay_ket_thuc < CURDATE() THEN 1
                WHEN hm.trang_thai = 'Đang thi công' AND hm.ngay_ket_thuc BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 2
                WHEN hm.trang_thai = 'Đang thi công' THEN 3
                WHEN hm.trang_thai = 'Chưa thi công' THEN 4
                ELSE 5
            END,
            hm.ngay_ket_thuc ASC
        LIMIT $offset, $limit";
$result = mysqli_query($conn, $sql);

// Lấy danh sách công trình cho filter
$sql_ct = "SELECT id, ten_cong_trinh FROM congtrinh WHERE user_id = '$user_id' ORDER BY ten_cong_trinh";
$congtrinh_list = mysqli_query($conn, $sql_ct);

// Thống kê nhanh
// Thống kê nhanh - THÊM tổng kinh phí
// Thống kê nhanh - ĐÃ SỬA ĐẦY ĐỦ
$sql_thongke = "SELECT 
                COUNT(*) as tong,
                SUM(CASE WHEN hm.trang_thai = 'Đang thi công' THEN 1 ELSE 0 END) as dangtc,
                SUM(CASE WHEN hm.trang_thai = 'Hoàn thành' THEN 1 ELSE 0 END) as hoanthanh,
                SUM(CASE WHEN hm.trang_thai = 'Chưa thi công' THEN 1 ELSE 0 END) as chuatc,
                SUM(CASE WHEN hm.ngay_ket_thuc < CURDATE() AND hm.trang_thai != 'Hoàn thành' THEN 1 ELSE 0 END) as quahan,
                SUM(hm.kinh_phi) as tong_kinhphi
                FROM hangmucthicong hm
                LEFT JOIN congtrinh ct ON hm.congtrinh_id = ct.id
                WHERE ct.user_id = '$user_id'";
$result_thongke = mysqli_query($conn, $sql_thongke);
$thongke = mysqli_fetch_assoc($result_thongke);
?>

<div class="content-wrapper">
    <!-- Page Header -->
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h4 class="mb-1">
                    <i class="fas fa-tasks me-2 text-warning"></i>
                    Quản lý hạng mục thi công
                </h4>
                <p class="text-muted mb-0">Theo dõi và cập nhật tiến độ các hạng mục</p>
            </div>
            <div class="mt-2 mt-sm-0">
                <a href="add.php" class="btn btn-primary">
                    <i class="fas fa-plus-circle me-2"></i>Thêm hạng mục
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
            <div class="card stat-card bg-primary text-white" onclick="filterByStatus('all')" style="cursor: pointer;">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 mb-1">Tổng số</h6>
                            <h3 class="text-white mb-0"><?php echo $thongke['tong'] ?? 0; ?></h3>
                        </div>
                        <i class="fas fa-tasks fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-6">
            <div class="card stat-card bg-warning text-white" onclick="filterByStatus('Đang thi công')" style="cursor: pointer;">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 mb-1">Đang thi công</h6>
                            <h3 class="text-white mb-0"><?php echo $thongke['dangtc'] ?? 0; ?></h3>
                        </div>
                        <i class="fas fa-spinner fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-6">
            <div class="card stat-card bg-success text-white" onclick="filterByStatus('Hoàn thành')" style="cursor: pointer;">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 mb-1">Hoàn thành</h6>
                            <h3 class="text-white mb-0"><?php echo $thongke['hoanthanh'] ?? 0; ?></h3>
                        </div>
                        <i class="fas fa-check-circle fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-6">
            <div class="card stat-card bg-secondary text-white" onclick="filterByStatus('Chưa thi công')" style="cursor: pointer;">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 mb-1">Chưa thi công</h6>
                            <h3 class="text-white mb-0"><?php echo $thongke['chuatc'] ?? 0; ?></h3>
                        </div>
                        <i class="fas fa-clock fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-6">
            <div class="card stat-card bg-danger text-white" onclick="filterByStatus('quahan')" style="cursor: pointer;">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 mb-1">Quá hạn</h6>
                            <h3 class="text-white mb-0"><?php echo $thongke['quahan'] ?? 0; ?></h3>
                        </div>
                        <i class="fas fa-exclamation-triangle fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
<div class="col-md-2 col-6">
    <div class="card stat-card bg-info text-white">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-white-50 mb-1">Tổng kinh phí</h6>
                    <h6 class="text-white mb-0"><?php echo formatMoney($thongke['tong_kinhphi'] ?? 0); ?></h6>
                </div>
                <i class="fas fa-coins fa-2x opacity-50"></i>
            </div>
        </div>
    </div>
</div>
    </div>

    <!-- Bộ lọc -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <select name="congtrinh_id" class="form-select">
                        <option value="">Tất cả công trình</option>
                        <?php while($ct = mysqli_fetch_assoc($congtrinh_list)): ?>
                        <option value="<?php echo $ct['id']; ?>" 
                            <?php echo ($_GET['congtrinh_id'] ?? '') == $ct['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($ct['ten_cong_trinh']); ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="trangthai" class="form-select">
                        <option value="">Tất cả trạng thái</option>
                        <option value="Chưa thi công" <?php echo ($_GET['trangthai'] ?? '') == 'Chưa thi công' ? 'selected' : ''; ?>>Chưa thi công</option>
                        <option value="Đang thi công" <?php echo ($_GET['trangthai'] ?? '') == 'Đang thi công' ? 'selected' : ''; ?>>Đang thi công</option>
                        <option value="Hoàn thành" <?php echo ($_GET['trangthai'] ?? '') == 'Hoàn thành' ? 'selected' : ''; ?>>Hoàn thành</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="thoigian" class="form-select">
                        <option value="">Thời gian</option>
                        <option value="quahan" <?php echo ($_GET['thoigian'] ?? '') == 'quahan' ? 'selected' : ''; ?>>Quá hạn</option>
                        <option value="sapdenhan" <?php echo ($_GET['thoigian'] ?? '') == 'sapdenhan' ? 'selected' : ''; ?>>Sắp đến hạn</option>
                        <option value="hoanthanh" <?php echo ($_GET['thoigian'] ?? '') == 'hoanthanh' ? 'selected' : ''; ?>>Đã hoàn thành</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <input type="text" name="keyword" class="form-control" 
                           placeholder="Tìm hạng mục..." value="<?php echo $_GET['keyword'] ?? ''; ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter me-2"></i>Lọc
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Danh sách hạng mục -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th width="50">STT</th>
                            <th>Tên hạng mục</th>
                            <th>Công trình</th>
                            <th>Tiến độ</th>
                            <th>Trạng thái</th>
                            <th>Thời gian</th>
                            <th>Kinh phí</th>
                            <th>Ghi chú</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(mysqli_num_rows($result) == 0): ?>
                        <tr>
                            <td colspan="9" class="text-center py-5">
                                <i class="fas fa-tasks fa-3x text-muted mb-3"></i>
                                <h6 class="text-muted">Chưa có hạng mục nào</h6>
                                <a href="add.php" class="btn btn-primary btn-sm mt-2">
                                    <i class="fas fa-plus-circle me-2"></i>Thêm hạng mục
                                </a>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php $stt = $offset + 1; ?>
                            <?php while($hm = mysqli_fetch_assoc($result)): 
                                $is_overdue = ($hm['trang_thai'] != 'Hoàn thành' && $hm['so_ngay_con'] < 0);
                                $progress_color = getProgressColor($hm['phan_tram_tien_do']);
                            ?>
                            <tr class="<?php echo $is_overdue ? 'table-danger' : ''; ?>">
                                <td><?php echo $stt++; ?></td>
                                <td>
                                    <a href="detail.php?id=<?php echo $hm['id']; ?>" class="text-decoration-none fw-bold">
                                        <?php echo htmlspecialchars($hm['ten_hang_muc']); ?>
                                    </a>
                                    <br>
                                    <small class="text-muted">Mã: <?php echo $hm['ma_hang_muc'] ?? 'HM-'.str_pad($hm['id'], 4, '0', STR_PAD_LEFT); ?></small>
                                </td>
                                <td>
                                    <a href="../congtrinh/detail.php?id=<?php echo $hm['congtrinh_id']; ?>" class="text-decoration-none">
                                        <?php echo htmlspecialchars($hm['ten_cong_trinh']); ?>
                                    </a>
                                </td>
                                <td style="min-width: 150px;">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress flex-grow-1" style="height: 8px;">
                                            <div class="progress-bar bg-<?php echo $progress_color; ?>" 
                                                 style="width: <?php echo $hm['phan_tram_tien_do']; ?>%"></div>
                                        </div>
                                        <span class="badge bg-<?php echo $progress_color; ?>">
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
                                        <span class="badge bg-danger mt-1">Quá hạn</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small>
                                        <i class="far fa-calendar-alt"></i> 
                                        <?php echo date('d/m/Y', strtotime($hm['ngay_ket_thuc'])); ?>
                                        <br>
                                        <?php if(!$is_overdue && $hm['so_ngay_con'] > 0): ?>
                                            <span class="text-success">Còn <?php echo $hm['so_ngay_con']; ?> ngày</span>
                                        <?php elseif($is_overdue): ?>
                                            <span class="text-danger">Chậm <?php echo abs($hm['so_ngay_con']); ?> ngày</span>
                                        <?php endif; ?>
                                    </small>
                                </td>
                                <td>
                                    <small class="text-muted"><?php echo formatMoney($hm['kinh_phi']); ?></small>
                                </td>
                                <td>
                                    <?php if($hm['so_ghichu'] > 0): ?>
                                        <a href="../ghichu/index.php?hangmuc_id=<?php echo $hm['id']; ?>" 
                                           class="badge bg-info text-decoration-none">
                                            <i class="fas fa-sticky-note me-1"></i><?php echo $hm['so_ghichu']; ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">0</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="update.php?id=<?php echo $hm['id']; ?>" 
                                           class="btn btn-sm btn-primary" title="Cập nhật tiến độ">
                                            <i class="fas fa-pen"></i>
                                        </a>
                                        <a href="detail.php?id=<?php echo $hm['id']; ?>" 
                                           class="btn btn-sm btn-info" title="Chi tiết">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="edit.php?id=<?php echo $hm['id']; ?>" 
                                           class="btn btn-sm btn-warning" title="Sửa">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="../ghichu/add.php?hangmuc_id=<?php echo $hm['id']; ?>" 
                                           class="btn btn-sm btn-secondary" title="Thêm ghi chú">
                                            <i class="fas fa-sticky-note"></i>
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
function filterByStatus(status) {
    let url = new URL(window.location.href);
    if(status === 'all') {
        url.searchParams.delete('trangthai');
        url.searchParams.delete('thoigian');
    } else if(status === 'quahan') {
        url.searchParams.delete('trangthai');
        url.searchParams.set('thoigian', 'quahan');
    } else {
        url.searchParams.delete('thoigian');
        url.searchParams.set('trangthai', status);
    }
    url.searchParams.set('page', '1');
    window.location.href = url.toString();
}

function exportExcel() {
    const params = new URLSearchParams(window.location.search).toString();
    window.location.href = 'export.php?' + params;
}
</script>

<style>
.stat-card {
    border: none;
    border-radius: 12px;
    transition: all 0.3s;
    cursor: pointer;
    overflow: hidden;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
}

.stat-card .opacity-50 {
    opacity: 0.3;
}

.table td {
    vertical-align: middle;
    padding: 1rem 0.75rem;
}

.table thead th {
    background: #f8fafc;
    font-weight: 600;
    color: #475569;
    border-bottom-width: 2px;
    white-space: nowrap;
}

.table tbody tr:hover {
    background: #f8fafc;
}

.table-danger {
    background-color: rgba(239, 68, 68, 0.05);
}

.table-danger:hover {
    background-color: rgba(239, 68, 68, 0.1) !important;
}

.progress {
    background: #f1f5f9;
    border-radius: 999px;
    overflow: hidden;
}

.progress-bar {
    transition: width 0.6s ease;
    border-radius: 999px;
}

.badge {
    font-weight: 500;
    padding: 0.4em 0.8em;
    border-radius: 999px;
}

.btn-group .btn {
    padding: 0.25rem 0.5rem;
    border-radius: 6px !important;
    margin: 0 2px;
}

.btn-group .btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}
</style>

<?php require_once '../includes/footer.php'; ?>