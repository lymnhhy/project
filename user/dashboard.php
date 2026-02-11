<?php
// user/dashboard.php
$page_title = 'Dashboard';
require_once 'includes/header.php';

$user_id = $_SESSION['id'];

// Hàm tính tiến độ công trình
function tinhTienDoCongTrinh($conn, $congtrinh_id) {
    $sql = "SELECT AVG(phan_tram_tien_do) as avg_progress 
            FROM hangmucthicong 
            WHERE congtrinh_id = '$congtrinh_id'";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
    return round($row['avg_progress'] ?? 0);
}

// Hàm lấy màu theo %
function getProgressColor($percent) {
    if ($percent < 30) return 'danger';
    if ($percent < 70) return 'warning';
    if ($percent < 100) return 'info';
    return 'success';
}

// Hàm format ngày
function formatDate($date) {
    if ($date == '0000-00-00' || $date == null) return 'Chưa cập nhật';
    return date('d/m/Y', strtotime($date));
}

// Thống kê tổng quan
$sql_thongke = "SELECT 
                    COUNT(*) as tong_cong_trinh,
                    SUM(CASE WHEN trangthaiCT_id = 2 THEN 1 ELSE 0 END) as dang_thi_cong,
                    SUM(CASE WHEN trangthaiCT_id = 3 THEN 1 ELSE 0 END) as hoan_thanh,
                    SUM(CASE WHEN trangthaiCT_id = 1 THEN 1 ELSE 0 END) as chua_thi_cong
                FROM congtrinh 
                WHERE user_id = '$user_id'";
$result_thongke = mysqli_query($conn, $sql_thongke);
$thongke = mysqli_fetch_assoc($result_thongke);

// Danh sách công trình
$sql_congtrinh = "SELECT ct.*, lct.ten_loai, ttct.ten_trang_thai 
                 FROM congtrinh ct
                 LEFT JOIN loaicongtrinh lct ON ct.loaiCT_id = lct.id
                 LEFT JOIN trangthaicongtrinh ttct ON ct.trangthaiCT_id = ttct.id
                 WHERE ct.user_id = '$user_id'
                 ORDER BY ct.ngay_tao DESC";
$result_congtrinh = mysqli_query($conn, $sql_congtrinh);

// Công trình sắp đến hạn
$sql_sap_den_han = "SELECT * FROM congtrinh 
                    WHERE user_id = '$user_id' 
                    AND trangthaiCT_id != 3 
                    AND ngay_ket_thuc BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
                    ORDER BY ngay_ket_thuc ASC";
$result_sap_den_han = mysqli_query($conn, $sql_sap_den_han);

// Lấy dữ liệu cho biểu đồ
$labels = [];
$progress_data = [];
$status_data = [0, 0, 0]; // Chưa thi công, Đang thi công, Hoàn thành

while ($ct = mysqli_fetch_assoc($result_congtrinh)) {
    $labels[] = $ct['ten_cong_trinh'];
    $progress_data[] = tinhTienDoCongTrinh($conn, $ct['id']);
    
    if ($ct['trangthaiCT_id'] == 1) $status_data[0]++;
    elseif ($ct['trangthaiCT_id'] == 2) $status_data[1]++;
    elseif ($ct['trangthaiCT_id'] == 3) $status_data[2]++;
}

// Reset result
mysqli_data_seek($result_congtrinh, 0);
?>

<div class="container-fluid">
    <!-- Page Title -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">Dashboard</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="dashboard.php">Trang chủ</a></li>
                <li class="breadcrumb-item active">Dashboard</li>
            </ol>
        </nav>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card h-100 py-2">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs text-primary text-uppercase mb-1">
                                <i class="fas fa-building me-1"></i> Tổng công trình
                            </div>
                            <div class="h2 mb-0 font-weight-bold">
                                <?php echo $thongke['tong_cong_trinh'] ?? 0; ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clipboard-list fa-3x text-primary opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card h-100 py-2">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs text-warning text-uppercase mb-1">
                                <i class="fas fa-spinner me-1"></i> Đang thi công
                            </div>
                            <div class="h2 mb-0 font-weight-bold">
                                <?php echo $thongke['dang_thi_cong'] ?? 0; ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-hard-hat fa-3x text-warning opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card h-100 py-2">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs text-success text-uppercase mb-1">
                                <i class="fas fa-check-circle me-1"></i> Hoàn thành
                            </div>
                            <div class="h2 mb-0 font-weight-bold">
                                <?php echo $thongke['hoan_thanh'] ?? 0; ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-double fa-3x text-success opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card h-100 py-2">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs text-secondary text-uppercase mb-1">
                                <i class="fas fa-clock me-1"></i> Chưa thi công
                            </div>
                            <div class="h2 mb-0 font-weight-bold">
                                <?php echo $thongke['chua_thi_cong'] ?? 0; ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-hourglass-start fa-3x text-secondary opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts -->
    <div class="row mb-4">
        <div class="col-xl-8 col-lg-7">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-chart-line me-1"></i>
                    Tiến độ các công trình
                </div>
                <div class="card-body">
                    <canvas id="progressChart" style="height: 300px;"></canvas>
                </div>
            </div>
        </div>
        
        <div class="col-xl-4 col-lg-5">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-chart-pie me-1"></i>
                    Phân bố trạng thái
                </div>
                <div class="card-body">
                    <canvas id="statusChart" style="height: 300px;"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Danh sách công trình -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <i class="fas fa-table me-1"></i>
                Danh sách công trình
            </div>
            <a href="congtrinh/add.php" class="btn btn-primary btn-sm">
                <i class="fas fa-plus-circle"></i> Thêm công trình
            </a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="dataTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Tên công trình</th>
                            <th>Địa điểm</th>
                            <th>Thời gian</th>
                            <th>Tiến độ</th>
                            <th>Trạng thái</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($result_congtrinh) == 0): ?>
                        <tr>
                            <td colspan="7" class="text-center py-4">
                                <i class="fas fa-building fa-3x text-muted mb-3"></i>
                                <p class="text-muted">Chưa có công trình nào. Hãy thêm công trình mới!</p>
                                <a href="congtrinh/add.php" class="btn btn-primary btn-sm">
                                    <i class="fas fa-plus-circle"></i> Thêm công trình
                                </a>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php $stt = 1; ?>
                            <?php while ($ct = mysqli_fetch_assoc($result_congtrinh)): 
                                $tien_do = tinhTienDoCongTrinh($conn, $ct['id']);
                                $color = getProgressColor($tien_do);
                            ?>
                            <tr>
                                <td><?php echo $stt++; ?></td>
                                <td>
                                    <a href="congtrinh/detail.php?id=<?php echo $ct['id']; ?>" class="text-decoration-none fw-bold">
                                        <?php echo $ct['ten_cong_trinh']; ?>
                                    </a>
                                </td>
                                <td><?php echo $ct['dia_diem']; ?></td>
                                <td>
                                    <?php echo formatDate($ct['ngay_bat_dau']); ?> - 
                                    <?php echo formatDate($ct['ngay_ket_thuc']); ?>
                                </td>
                                <td style="width: 180px;">
                                    <div class="d-flex align-items-center">
                                        <div class="progress flex-grow-1 me-2" style="height: 8px;">
                                            <div class="progress-bar bg-<?php echo $color; ?>" 
                                                 style="width: <?php echo $tien_do; ?>%"></div>
                                        </div>
                                        <span class="badge bg-<?php echo $color; ?>">
                                            <?php echo $tien_do; ?>%
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $ct['trangthaiCT_id'] == 1 ? 'secondary' : 
                                            ($ct['trangthaiCT_id'] == 2 ? 'warning' : 'success'); 
                                    ?>">
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
                                        <button onclick="confirmDelete('congtrinh/delete.php?id=<?php echo $ct['id']; ?>')"
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
    </div>

    <!-- 2 Cột -->
    <div class="row">
        <!-- Công trình sắp đến hạn -->
        <div class="col-xl-6">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-clock me-1"></i>
                    Công trình sắp đến hạn (7 ngày tới)
                </div>
                <div class="card-body">
                    <div class="list-group">
                        <?php if (mysqli_num_rows($result_sap_den_han) == 0): ?>
                            <p class="text-muted text-center py-3 mb-0">
                                <i class="fas fa-check-circle text-success"></i> 
                                Không có công trình sắp đến hạn
                            </p>
                        <?php else: ?>
                            <?php while ($row = mysqli_fetch_assoc($result_sap_den_han)): 
                                $so_ngay = (strtotime($row['ngay_ket_thuc']) - time()) / (60*60*24);
                                $so_ngay = round($so_ngay);
                            ?>
                            <a href="congtrinh/detail.php?id=<?php echo $row['id']; ?>" 
                               class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1"><?php echo $row['ten_cong_trinh']; ?></h6>
                                    <small class="text-muted">
                                        <i class="fas fa-map-marker-alt me-1"></i> 
                                        <?php echo $row['dia_diem']; ?>
                                    </small>
                                </div>
                                <span class="badge bg-<?php echo $so_ngay <= 3 ? 'danger' : 'warning'; ?> rounded-pill">
                                    Còn <?php echo $so_ngay; ?> ngày
                                </span>
                            </a>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Thông tin nhanh -->
        <div class="col-xl-6">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-info-circle me-1"></i>
                    Thông tin nhanh
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6 mb-3">
                            <div class="border rounded p-3 text-center">
                                <h5 class="text-primary mb-2">
                                    <?php echo $thongke['tong_cong_trinh'] ?? 0; ?>
                                </h5>
                                <small class="text-muted">Tổng công trình</small>
                            </div>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="border rounded p-3 text-center">
                                <h5 class="text-warning mb-2">
                                    <?php echo $thongke['dang_thi_cong'] ?? 0; ?>
                                </h5>
                                <small class="text-muted">Đang thi công</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="border rounded p-3 text-center">
                                <h5 class="text-success mb-2">
                                    <?php echo $thongke['hoan_thanh'] ?? 0; ?>
                                </h5>
                                <small class="text-muted">Hoàn thành</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="border rounded p-3 text-center">
                                <h5 class="text-secondary mb-2">
                                    <?php echo $thongke['chua_thi_cong'] ?? 0; ?>
                                </h5>
                                <small class="text-muted">Chưa thi công</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Progress Chart
    var ctx1 = document.getElementById('progressChart').getContext('2d');
    var progressChart = new Chart(ctx1, {
        type: 'bar',
        data: {
            labels: [<?php foreach($labels as $label) { echo '"' . addslashes($label) . '",'; } ?>],
            datasets: [{
                label: 'Tiến độ (%)',
                data: [<?php echo implode(',', $progress_data); ?>],
                backgroundColor: [
                    <?php foreach($progress_data as $p) { 
                        if($p < 30) echo "'#dc3545',";
                        elseif($p < 70) echo "'#ffc107',";
                        elseif($p < 100) echo "'#17a2b8',";
                        else echo "'#28a745',";
                    } ?>
                ],
                borderWidth: 0,
                borderRadius: 5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    grid: {
                        display: true,
                        color: 'rgba(0,0,0,0.05)'
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
    
    // Status Chart
    var ctx2 = document.getElementById('statusChart').getContext('2d');
    var statusChart = new Chart(ctx2, {
        type: 'doughnut',
        data: {
            labels: ['Chưa thi công', 'Đang thi công', 'Hoàn thành'],
            datasets: [{
                data: [<?php echo $status_data[0]; ?>, <?php echo $status_data[1]; ?>, <?php echo $status_data[2]; ?>],
                backgroundColor: ['#6c757d', '#ffc107', '#28a745'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
</script>

<?php
require_once 'includes/footer.php';
?>