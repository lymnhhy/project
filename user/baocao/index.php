<?php
// baocao/index.php
$page_title = 'Báo cáo tổng hợp';
require_once '../includes/header.php';

$user_id = $_SESSION['id'];
$filter_type = $_GET['type'] ?? 'thang';
$filter_value = $_GET['value'] ?? date('m-Y');

// Xử lý filter
list($thang, $nam) = explode('-', $filter_value);

// Thống kê công trình theo trạng thái
$sql_congtrinh = "SELECT 
                    COUNT(*) as tong,
                    SUM(CASE WHEN trangthaiCT_id = 1 THEN 1 ELSE 0 END) as chuatc,
                    SUM(CASE WHEN trangthaiCT_id = 2 THEN 1 ELSE 0 END) as dangtc,
                    SUM(CASE WHEN trangthaiCT_id = 3 THEN 1 ELSE 0 END) as hoanthanh,
                    SUM(CASE WHEN ngay_ket_thuc < CURDATE() AND trangthaiCT_id != 3 THEN 1 ELSE 0 END) as quahan
                  FROM congtrinh 
                  WHERE user_id = '$user_id'";
$result_congtrinh = mysqli_query($conn, $sql_congtrinh);
$thongke_congtrinh = mysqli_fetch_assoc($result_congtrinh);

// Thống kê hạng mục theo trạng thái
$sql_hangmuc = "SELECT 
                  COUNT(*) as tong,
                  SUM(CASE WHEN trang_thai = 'Chưa thi công' THEN 1 ELSE 0 END) as chuatc,
                  SUM(CASE WHEN trang_thai = 'Đang thi công' THEN 1 ELSE 0 END) as dangtc,
                  SUM(CASE WHEN trang_thai = 'Hoàn thành' THEN 1 ELSE 0 END) as hoanthanh
                FROM hangmucthicong hm
                LEFT JOIN congtrinh ct ON hm.congtrinh_id = ct.id
                WHERE ct.user_id = '$user_id'";
$result_hangmuc = mysqli_query($conn, $sql_hangmuc);
$thongke_hangmuc = mysqli_fetch_assoc($result_hangmuc);

// Thống kê tiến độ theo tháng
$sql_tiendo_thang = "SELECT 
                      DAY(ngay_tao) as ngay,
                      COUNT(*) as so_luong
                    FROM congtrinh 
                    WHERE user_id = '$user_id' 
                      AND MONTH(ngay_tao) = $thang 
                      AND YEAR(ngay_tao) = $nam
                    GROUP BY DAY(ngay_tao)
                    ORDER BY ngay";
$result_tiendo_thang = mysqli_query($conn, $sql_tiendo_thang);

$labels = [];
$data = [];
for($i = 1; $i <= 31; $i++) {
    $labels[] = "Ngày $i";
    $data[$i] = 0;
}
while($row = mysqli_fetch_assoc($result_tiendo_thang)) {
    $data[$row['ngay']] = $row['so_luong'];
}

// Lấy danh sách tháng để lọc
$sql_thang = "SELECT DISTINCT 
                MONTH(ngay_tao) as thang, 
                YEAR(ngay_tao) as nam 
              FROM congtrinh 
              WHERE user_id = '$user_id'
              ORDER BY nam DESC, thang DESC";
$result_thang = mysqli_query($conn, $sql_thang);
?>

<div class="content-wrapper">
    <!-- Page Header -->
    <div class="page-header d-flex justify-content-between align-items-center flex-wrap">
        <div>
            <h4 class="mb-1">
                <i class="fas fa-chart-bar me-2 text-warning"></i>
                Báo cáo tổng hợp
            </h4>
            <p class="text-muted mb-0">Thống kê tổng quan công trình và hạng mục</p>
        </div>
        <div class="d-flex gap-2 mt-2 mt-sm-0">
            <!-- <a href="export.php?type=tonghop" class="btn btn-success">
                <i class="fas fa-file-excel me-2"></i>Xuất Excel
            </a> -->
            <a href="history.php" class="btn btn-outline-primary">
                <i class="fas fa-history me-2"></i>Lịch sử báo cáo
            </a>
        </div>
    </div>

    <!-- Bộ lọc thời gian -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-center">
                <div class="col-auto">
                    <label class="form-label mb-0">Chọn tháng:</label>
                </div>
                <div class="col-auto">
                    <select name="value" class="form-select">
                        <?php while($row = mysqli_fetch_assoc($result_thang)): 
                            $value = $row['thang'] . '-' . $row['nam'];
                            $text = 'Tháng ' . $row['thang'] . ' năm ' . $row['nam'];
                        ?>
                        <option value="<?php echo $value; ?>" 
                            <?php echo $filter_value == $value ? 'selected' : ''; ?>>
                            <?php echo $text; ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter me-2"></i>Xem báo cáo
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Thống kê nhanh -->
    <div class="row g-4 mb-4">
        <!-- Công trình -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header">
                    <i class="fas fa-building me-2 text-warning"></i>
                    Thống kê công trình
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="stat-box bg-primary text-white p-3 rounded">
                                <h6 class="text-white-50 mb-1">Tổng số</h6>
                                <h3 class="text-white mb-0"><?php echo $thongke_congtrinh['tong'] ?? 0; ?></h3>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stat-box bg-success text-white p-3 rounded">
                                <h6 class="text-white-50 mb-1">Hoàn thành</h6>
                                <h3 class="text-white mb-0"><?php echo $thongke_congtrinh['hoanthanh'] ?? 0; ?></h3>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stat-box bg-warning text-white p-3 rounded">
                                <h6 class="text-white-50 mb-1">Đang thi công</h6>
                                <h3 class="text-white mb-0"><?php echo $thongke_congtrinh['dangtc'] ?? 0; ?></h3>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stat-box bg-secondary text-white p-3 rounded">
                                <h6 class="text-white-50 mb-1">Chưa thi công</h6>
                                <h3 class="text-white mb-0"><?php echo $thongke_congtrinh['chuatc'] ?? 0; ?></h3>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="stat-box bg-danger text-white p-3 rounded">
                                <h6 class="text-white-50 mb-1">Quá hạn</h6>
                                <h3 class="text-white mb-0"><?php echo $thongke_congtrinh['quahan'] ?? 0; ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Hạng mục -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header">
                    <i class="fas fa-tasks me-2 text-warning"></i>
                    Thống kê hạng mục
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="stat-box bg-primary text-white p-3 rounded">
                                <h6 class="text-white-50 mb-1">Tổng số</h6>
                                <h3 class="text-white mb-0"><?php echo $thongke_hangmuc['tong'] ?? 0; ?></h3>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stat-box bg-success text-white p-3 rounded">
                                <h6 class="text-white-50 mb-1">Hoàn thành</h6>
                                <h3 class="text-white mb-0"><?php echo $thongke_hangmuc['hoanthanh'] ?? 0; ?></h3>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stat-box bg-warning text-white p-3 rounded">
                                <h6 class="text-white-50 mb-1">Đang thi công</h6>
                                <h3 class="text-white mb-0"><?php echo $thongke_hangmuc['dangtc'] ?? 0; ?></h3>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stat-box bg-secondary text-white p-3 rounded">
                                <h6 class="text-white-50 mb-1">Chưa thi công</h6>
                                <h3 class="text-white mb-0"><?php echo $thongke_hangmuc['chuatc'] ?? 0; ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Biểu đồ tiến độ -->
    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-chart-line me-2 text-warning"></i>
                    Biểu đồ công trình theo ngày (Tháng <?php echo $thang . '/' . $nam; ?>)
                </div>
                <div class="card-body">
                    <canvas id="progressChart" style="height: 300px;"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Danh sách công trình gần đây -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <i class="fas fa-building me-2 text-warning"></i>
                Công trình gần đây
            </div>
            <a href="congtrinh.php" class="btn btn-sm btn-outline-primary">
                Xem chi tiết <i class="fas fa-arrow-right ms-2"></i>
            </a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Mã CT</th>
                            <th>Tên công trình</th>
                            <th>Địa điểm</th>
                            <th>Tiến độ</th>
                            <th>Trạng thái</th>
                            <th>Hạng mục</th>
                            <th>Ngày tạo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT ct.*, 
                                (SELECT COUNT(*) FROM hangmucthicong WHERE congtrinh_id = ct.id) as tong_hm,
                                (SELECT AVG(phan_tram_tien_do) FROM hangmucthicong WHERE congtrinh_id = ct.id) as tien_do
                                FROM congtrinh ct
                                WHERE ct.user_id = '$user_id'
                                ORDER BY ct.ngay_tao DESC
                                LIMIT 10";
                        $result = mysqli_query($conn, $sql);
                        
                        if(mysqli_num_rows($result) == 0): ?>
                        <tr>
                            <td colspan="7" class="text-center py-4">
                                <p class="text-muted mb-0">Chưa có công trình nào</p>
                            </td>
                        </tr>
                        <?php else: 
                            while($ct = mysqli_fetch_assoc($result)): 
                                $tien_do = round($ct['tien_do'] ?? 0);
                                $color = getProgressColor($tien_do);
                        ?>
                        <tr>
                            <td><span class="badge bg-secondary">CT-<?php echo str_pad($ct['id'], 4, '0', STR_PAD_LEFT); ?></span></td>
                            <td>
                                <a href="../congtrinh/detail.php?id=<?php echo $ct['id']; ?>" class="text-decoration-none">
                                    <?php echo htmlspecialchars($ct['ten_cong_trinh']); ?>
                                </a>
                            </td>
                            <td><?php echo htmlspecialchars($ct['dia_diem']); ?></td>
                            <td style="min-width: 120px;">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="progress flex-grow-1" style="height: 6px;">
                                        <div class="progress-bar bg-<?php echo $color; ?>" 
                                             style="width: <?php echo $tien_do; ?>%"></div>
                                    </div>
                                    <span class="badge bg-<?php echo $color; ?>"><?php echo $tien_do; ?>%</span>
                                </div>
                            </td>
                            <td>
                                <?php 
                                $status_class = $ct['trangthaiCT_id'] == 1 ? 'secondary' : 
                                               ($ct['trangthaiCT_id'] == 2 ? 'warning' : 'success');
                                ?>
                                <span class="badge bg-<?php echo $status_class; ?>">
                                    <?php
                                    switch($ct['trangthaiCT_id']) {
                                        case 1: echo 'Chưa thi công'; break;
                                        case 2: echo 'Đang thi công'; break;
                                        case 3: echo 'Hoàn thành'; break;
                                    }
                                    ?>
                                </span>
                            </td>
                            <td><span class="badge bg-info"><?php echo $ct['tong_hm']; ?> hạng mục</span></td>
                            <td><?php echo date('d/m/Y', strtotime($ct['ngay_tao'])); ?></td>
                        </tr>
                        <?php endwhile; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
// Biểu đồ tiến độ
const ctx = document.getElementById('progressChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: [<?php foreach($labels as $label) { echo '"' . $label . '",'; } ?>],
        datasets: [{
            label: 'Số công trình',
            data: [<?php echo implode(',', array_slice($data, 1)); ?>],
            backgroundColor: '#fbbf24',
            borderColor: '#f59e0b',
            borderWidth: 1,
            borderRadius: 5
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});
</script>

<style>
.stat-box {
    transition: all 0.3s;
    border: none;
}
.stat-box:hover {
    transform: translateY(-3px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}
.bg-primary { background: #3b82f6 !important; }
.bg-success { background: #10b981 !important; }
.bg-warning { background: #f59e0b !important; }
.bg-danger { background: #ef4444 !important; }
.bg-secondary { background: #6b7280 !important; }
</style>

<?php require_once '../includes/footer.php'; ?>