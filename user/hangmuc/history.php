<?php
// hangmuc/history.php
require_once '../includes/header.php';

$hangmuc_id = isset($_GET['hangmuc_id']) ? (int)$_GET['hangmuc_id'] : 0;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Lấy thông tin hạng mục
$hm_info = null;
if($hangmuc_id > 0) {
    $sql = "SELECT hm.*, ct.ten_cong_trinh 
            FROM hangmucthicong hm
            LEFT JOIN congtrinh ct ON hm.congtrinh_id = ct.id
            WHERE hm.id = $hangmuc_id AND ct.user_id = '{$_SESSION['id']}'";
    $result = mysqli_query($conn, $sql);
    if(mysqli_num_rows($result) > 0) {
        $hm_info = mysqli_fetch_assoc($result);
    }
}

// Đếm tổng số bản ghi
$sql_count = "SELECT COUNT(*) as total FROM lichsucapnhat lscn
              LEFT JOIN hangmucthicong hm ON lscn.hangmuc_id = hm.id
              LEFT JOIN congtrinh ct ON hm.congtrinh_id = ct.id
              WHERE ct.user_id = '{$_SESSION['id']}'";
if($hangmuc_id > 0) {
    $sql_count .= " AND lscn.hangmuc_id = $hangmuc_id";
}
$result_count = mysqli_query($conn, $sql_count);
$total_rows = mysqli_fetch_assoc($result_count)['total'];
$total_pages = ceil($total_rows / $limit);

// Lấy lịch sử cập nhật
$sql = "SELECT lscn.*, hm.ten_hang_muc, hm.congtrinh_id, ct.ten_cong_trinh,
        DATEDIFF(lscn.thoi_gian_cap_nhat, lscn.thoi_gian_cap_nhat) as test
        FROM lichsucapnhat lscn
        LEFT JOIN hangmucthicong hm ON lscn.hangmuc_id = hm.id
        LEFT JOIN congtrinh ct ON hm.congtrinh_id = ct.id
        WHERE ct.user_id = '{$_SESSION['id']}'";
if($hangmuc_id > 0) {
    $sql .= " AND lscn.hangmuc_id = $hangmuc_id";
}
$sql .= " ORDER BY lscn.thoi_gian_cap_nhat DESC
          LIMIT $offset, $limit";
$result = mysqli_query($conn, $sql);

// Debug: Kiểm tra số dòng
// echo "<!-- Số dòng: " . mysqli_num_rows($result) . " -->";
?>

<div class="content-wrapper">
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h4 class="mb-1">
                    <i class="fas fa-history me-2 text-warning"></i>
                    Lịch sử cập nhật tiến độ
                </h4>
                <?php if($hm_info): ?>
                <p class="text-muted mb-0">
                    Hạng mục: 
                    <a href="detail.php?id=<?php echo $hangmuc_id; ?>" class="text-decoration-none">
                        <?php echo htmlspecialchars($hm_info['ten_hang_muc']); ?>
                    </a>
                    (<?php echo htmlspecialchars($hm_info['ten_cong_trinh']); ?>)
                </p>
                <?php endif; ?>
            </div>
            <div class="mt-2 mt-sm-0">
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Quay lại
                </a>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Thời gian</th>
                            <th>Công trình</th>
                            <th>Hạng mục</th>
                            <th>Tiến độ cũ</th>
                            <th>Tiến độ mới</th>
                            <th>Thay đổi</th>
                            <th>Ghi chú</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(mysqli_num_rows($result) == 0): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <i class="fas fa-history fa-3x text-muted mb-3"></i>
                                <h6 class="text-muted">Chưa có lịch sử cập nhật</h6>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php $stt = $offset + 1; ?>
                            <?php while($row = mysqli_fetch_assoc($result)): 
                                $thay_doi = $row['phan_tram_moi'] - $row['phan_tram_cu'];
                                $thay_doi_class = $thay_doi > 0 ? 'text-success' : ($thay_doi < 0 ? 'text-danger' : 'text-muted');
                            ?>
                            <tr>
                                <td><?php echo $stt++; ?></td>
                                <td>
                                    <i class="far fa-clock text-muted me-2"></i>
                                    <?php echo date('d/m/Y H:i', strtotime($row['thoi_gian_cap_nhat'])); ?>
                                </td>
                                <td>
                                    <?php if(!empty($row['congtrinh_id'])): ?>
                                    <a href="../congtrinh/detail.php?id=<?php echo $row['congtrinh_id']; ?>" 
                                       class="text-decoration-none">
                                        <?php echo htmlspecialchars($row['ten_cong_trinh'] ?? 'N/A'); ?>
                                    </a>
                                    <?php else: ?>
                                        <span class="text-muted">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if(!empty($row['hangmuc_id'])): ?>
                                    <a href="detail.php?id=<?php echo $row['hangmuc_id']; ?>" 
                                       class="text-decoration-none">
                                        <?php echo htmlspecialchars($row['ten_hang_muc'] ?? 'N/A'); ?>
                                    </a>
                                    <?php else: ?>
                                        <span class="text-muted">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-secondary"><?php echo $row['phan_tram_cu']; ?>%</span>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo getProgressColor($row['phan_tram_moi']); ?>">
                                        <?php echo $row['phan_tram_moi']; ?>%
                                    </span>
                                </td>
                                <td>
                                    <span class="<?php echo $thay_doi_class; ?> fw-bold">
                                        <?php echo $thay_doi > 0 ? '+' : ''; ?><?php echo $thay_doi; ?>%
                                    </span>
                                </td>
                                <td>
                                    <?php if(!empty($row['ghi_chu'])): ?>
                                        <i class="fas fa-info-circle text-info" 
                                           title="<?php echo htmlspecialchars($row['ghi_chu']); ?>"></i>
                                        <span class="d-none d-md-inline"><?php echo htmlspecialchars($row['ghi_chu']); ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
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
                        <a class="page-link" href="?page=<?php echo $page-1; ?><?php echo $hangmuc_id ? '&hangmuc_id='.$hangmuc_id : ''; ?>">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    </li>
                    <?php for($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?><?php echo $hangmuc_id ? '&hangmuc_id='.$hangmuc_id : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                    <?php endfor; ?>
                    <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page+1; ?><?php echo $hangmuc_id ? '&hangmuc_id='.$hangmuc_id : ''; ?>">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
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

.badge {
    font-weight: 500;
    padding: 0.4em 0.8em;
    border-radius: 999px;
}

.text-success {
    color: #10b981 !important;
}

.text-danger {
    color: #ef4444 !important;
}

.fa-info-circle {
    cursor: help;
}
</style>

<?php require_once '../includes/footer.php'; ?>