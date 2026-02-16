<?php
// profile/activity.php
require_once '../includes/header.php';

$user_id = $_SESSION['id'];
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Xử lý bộ lọc
$where = "WHERE user_id = '$user_id'";

if(isset($_GET['action']) && !empty($_GET['action'])) {
    $action = mysqli_real_escape_string($conn, $_GET['action']);
    $where .= " AND hanh_dong LIKE '%$action%'";
}

if(isset($_GET['date']) && !empty($_GET['date'])) {
    $date = mysqli_real_escape_string($conn, $_GET['date']);
    $where .= " AND DATE(thoi_gian) = '$date'";
}

// Đếm tổng số
$sql_count = "SELECT COUNT(*) as total FROM lichsuhoatdong $where";
$result_count = mysqli_query($conn, $sql_count);
$total_rows = mysqli_fetch_assoc($result_count)['total'];
$total_pages = ceil($total_rows / $limit);

// Lấy danh sách hoạt động
$sql = "SELECT * FROM lichsuhoatdong 
        $where 
        ORDER BY thoi_gian DESC 
        LIMIT $offset, $limit";
$activity_list = mysqli_query($conn, $sql);

// Thống kê theo ngày
$sql_stats = "SELECT 
              DATE(thoi_gian) as ngay,
              COUNT(*) as so_luong
              FROM lichsuhoatdong
              WHERE user_id = '$user_id'
              GROUP BY DATE(thoi_gian)
              ORDER BY ngay DESC
              LIMIT 7";
$stats = mysqli_query($conn, $sql_stats);
?>

<div class="content-wrapper">
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h4 class="mb-1">
                    <i class="fas fa-history me-2 text-warning"></i>
                    Lịch sử hoạt động
                </h4>
                <p class="text-muted mb-0">Theo dõi tất cả hoạt động của bạn trong hệ thống</p>
            </div>
            <div class="mt-2 mt-sm-0">
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Quay lại
                </a>
                <button onclick="exportActivity()" class="btn btn-success">
                    <i class="fas fa-download me-2"></i>Xuất báo cáo
                </button>
            </div>
        </div>
    </div>

    <!-- Thống kê nhanh -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h6 class="text-white-50">Tổng hoạt động</h6>
                    <h3><?php echo $total_rows; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h6 class="text-white-50">Hôm nay</h6>
                    <h3>
                        <?php 
                        $today = date('Y-m-d');
                        $sql_today = "SELECT COUNT(*) as total FROM lichsuhoatdong 
                                     WHERE user_id = '$user_id' AND DATE(thoi_gian) = '$today'";
                        $result_today = mysqli_query($conn, $sql_today);
                        echo mysqli_fetch_assoc($result_today)['total'];
                        ?>
                    </h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6 class="text-white-50">Hoạt động cuối</h6>
                    <h6>
                        <?php 
                        $last = mysqli_fetch_assoc(mysqli_query($conn, 
                            "SELECT thoi_gian FROM lichsuhoatdong 
                             WHERE user_id = '$user_id' 
                             ORDER BY thoi_gian DESC LIMIT 1"
                        ));
                        echo $last ? timeAgo($last['thoi_gian']) : 'Chưa có';
                        ?>
                    </h6>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h6 class="text-white-50">IP gần nhất</h6>
                    <h6>
                        <?php 
                        $last_ip = mysqli_fetch_assoc(mysqli_query($conn, 
                            "SELECT ip_address FROM lichsuhoatdong 
                             WHERE user_id = '$user_id' 
                             ORDER BY thoi_gian DESC LIMIT 1"
                        ));
                        echo $last_ip ? $last_ip['ip_address'] : 'Chưa có';
                        ?>
                    </h6>
                </div>
            </div>
        </div>
    </div>

    <!-- Bộ lọc -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <input type="text" name="action" class="form-control" 
                           placeholder="Tìm theo hành động..." 
                           value="<?php echo htmlspecialchars($_GET['action'] ?? ''); ?>">
                </div>
                <div class="col-md-3">
                    <input type="date" name="date" class="form-control" 
                           value="<?php echo $_GET['date'] ?? ''; ?>">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter me-2"></i>Lọc
                    </button>
                    <a href="activity.php" class="btn btn-secondary">
                        <i class="fas fa-undo me-2"></i>Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Hoạt động theo ngày -->
    <div class="row">
        <div class="col-lg-9">
            <!-- Danh sách hoạt động -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-list me-2 text-warning"></i>
                    Chi tiết hoạt động
                </div>
                <div class="card-body p-0">
                    <?php if(mysqli_num_rows($activity_list) == 0): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-history fa-4x text-muted mb-3"></i>
                        <h5 class="text-muted">Không có hoạt động nào</h5>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Thời gian</th>
                                    <th>Hành động</th>
                                    <th>Chi tiết</th>
                                    <th>IP Address</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $stt = $offset + 1; ?>
                                <?php while($activity = mysqli_fetch_assoc($activity_list)): ?>
                                <tr>
                                    <td><?php echo $stt++; ?></td>
                                    <td>
                                        <i class="far fa-clock text-muted me-2"></i>
                                        <?php echo date('d/m/Y H:i:s', strtotime($activity['thoi_gian'])); ?>
                                        <br>
                                        <small class="text-muted"><?php echo timeAgo($activity['thoi_gian']); ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-info"><?php echo htmlspecialchars($activity['hanh_dong']); ?></span>
                                    </td>
                                    <td><?php echo htmlspecialchars($activity['chi_tiet']); ?></td>
                                    <td><code><?php echo $activity['ip_address']; ?></code></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
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

        <div class="col-lg-3">
            <!-- Thống kê 7 ngày -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-chart-bar me-2 text-warning"></i>
                    Thống kê 7 ngày
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <?php while($stat = mysqli_fetch_assoc($stats)): ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <span>
                                <i class="far fa-calendar-alt me-2 text-muted"></i>
                                <?php echo date('d/m/Y', strtotime($stat['ngay'])); ?>
                            </span>
                            <span class="badge bg-primary rounded-pill"><?php echo $stat['so_luong']; ?></span>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>

            <!-- Các hành động thường xuyên -->
            <?php
            $sql_actions = "SELECT hanh_dong, COUNT(*) as so_lan
                           FROM lichsuhoatdong
                           WHERE user_id = '$user_id'
                           GROUP BY hanh_dong
                           ORDER BY so_lan DESC
                           LIMIT 5";
            $actions = mysqli_query($conn, $sql_actions);
            ?>
            <?php if(mysqli_num_rows($actions) > 0): ?>
            <div class="card mt-3">
                <div class="card-header">
                    <i class="fas fa-chart-pie me-2 text-warning"></i>
                    Hành động thường xuyên
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <?php while($action = mysqli_fetch_assoc($actions)): ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <span><?php echo htmlspecialchars($action['hanh_dong']); ?></span>
                            <span class="badge bg-info rounded-pill"><?php echo $action['so_lan']; ?> lần</span>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function exportActivity() {
    const params = new URLSearchParams(window.location.search).toString();
    window.location.href = 'export-activity.php?' + params;
}
</script>

<?php require_once '../includes/footer.php'; ?>