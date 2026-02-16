<?php
// congtrinh/progress.php
require_once '../includes/header.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Lấy thông tin công trình
$sql = "SELECT ct.*, lct.ten_loai 
        FROM congtrinh ct
        LEFT JOIN loaicongtrinh lct ON ct.loaiCT_id = lct.id
        WHERE ct.id = $id AND ct.user_id = '{$_SESSION['id']}'";
$result = mysqli_query($conn, $sql);

if(mysqli_num_rows($result) == 0) {
    $_SESSION['error'] = 'Không tìm thấy công trình';
    echo '<script>window.location.href="index.php";</script>';
    exit();
}

$ct = mysqli_fetch_assoc($result);

// Lấy dữ liệu tiến độ theo thời gian
$sql_tiendo = "SELECT 
                DATE(lscn.thoi_gian_cap_nhat) as ngay,
                AVG(lscn.phan_tram_moi) as tien_do
                FROM lichsucapnhat lscn
                LEFT JOIN hangmucthicong hm ON lscn.hangmuc_id = hm.id
                WHERE hm.congtrinh_id = $id
                GROUP BY DATE(lscn.thoi_gian_cap_nhat)
                ORDER BY ngay ASC
                LIMIT 30";
$tiendo_data = mysqli_query($conn, $sql_tiendo);

$dates = [];
$progress = [];
while($row = mysqli_fetch_assoc($tiendo_data)) {
    $dates[] = date('d/m', strtotime($row['ngay']));
    $progress[] = round($row['tien_do']);
}

// Lấy danh sách hạng mục với tiến độ
$sql_hm = "SELECT hm.*, 
           DATEDIFF(ngay_ket_thuc, CURDATE()) as so_ngay_con,
           (SELECT phan_tram_moi FROM lichsucapnhat WHERE hangmuc_id = hm.id ORDER BY thoi_gian_cap_nhat DESC LIMIT 1) as tien_do_moi_nhat
           FROM hangmucthicong hm
           WHERE hm.congtrinh_id = $id
           ORDER BY 
                CASE 
                    WHEN trang_thai != 'Hoàn thành' AND ngay_ket_thuc < CURDATE() THEN 1
                    WHEN trang_thai = 'Đang thi công' THEN 2
                    ELSE 3
                END,
                ngay_ket_thuc ASC";
$hm_list = mysqli_query($conn, $sql_hm);

// Tính tổng quan tiến độ
$tong_hm = mysqli_num_rows($hm_list);
$hm_hoanthanh = 0;
$hm_danglam = 0;
$hm_chualam = 0;
$hm_quahan = 0;

// Reset result pointer
mysqli_data_seek($hm_list, 0);
while($hm = mysqli_fetch_assoc($hm_list)) {
    if($hm['trang_thai'] == 'Hoàn thành') $hm_hoanthanh++;
    elseif($hm['trang_thai'] == 'Đang thi công') $hm_danglam++;
    else $hm_chualam++;
    
    if($hm['trang_thai'] != 'Hoàn thành' && strtotime($hm['ngay_ket_thuc']) < time()) {
        $hm_quahan++;
    }
}
// Reset lại result pointer để dùng cho vòng lặp sau
mysqli_data_seek($hm_list, 0);
?>

<div class="content-wrapper">
    <!-- Page Header -->
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h4 class="mb-1">
                    <i class="fas fa-chart-line me-2 text-warning"></i>
                    Theo dõi tiến độ: <?php echo htmlspecialchars($ct['ten_cong_trinh']); ?>
                </h4>
                <div class="d-flex gap-2 mt-2">
                    <span class="badge bg-info">Mã: <?php echo $ct['ma_cong_trinh']; ?></span>
                    <span class="badge bg-secondary">Loại: <?php echo $ct['ten_loai']; ?></span>
                </div>
            </div>
            <div class="mt-2 mt-sm-0">
                <a href="detail.php?id=<?php echo $id; ?>" class="btn btn-secondary me-2">
                    <i class="fas fa-arrow-left me-2"></i>Quay lại
                </a>
                <div class="btn-group">
                    <a href="../hangmuc/add.php?congtrinh_id=<?php echo $id; ?>" class="btn btn-primary">
                        <i class="fas fa-plus-circle me-2"></i>Thêm hạng mục
                    </a>
                    <button type="button" class="btn btn-primary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown">
                        <span class="visually-hidden">Toggle Dropdown</span>
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="../baocao/congtrinh.php?id=<?php echo $id; ?>">
                            <i class="fas fa-file-pdf me-2"></i>Xuất báo cáo PDF
                        </a></li>
                        <li><a class="dropdown-item" href="#" onclick="exportProgress()">
                            <i class="fas fa-file-excel me-2"></i>Xuất báo cáo Excel
                        </a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Thống kê nhanh -->
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-sm-6">
            <div class="card stat-card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 mb-1">Tổng hạng mục</h6>
                            <h3 class="text-white mb-0"><?php echo $tong_hm; ?></h3>
                        </div>
                        <i class="fas fa-tasks fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card stat-card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 mb-1">Hoàn thành</h6>
                            <h3 class="text-white mb-0"><?php echo $hm_hoanthanh; ?></h3>
                        </div>
                        <i class="fas fa-check-circle fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card stat-card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 mb-1">Đang thi công</h6>
                            <h3 class="text-white mb-0"><?php echo $hm_danglam; ?></h3>
                        </div>
                        <i class="fas fa-spinner fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card stat-card bg-danger text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 mb-1">Quá hạn</h6>
                            <h3 class="text-white mb-0"><?php echo $hm_quahan; ?></h3>
                        </div>
                        <i class="fas fa-exclamation-triangle fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Biểu đồ tiến độ -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <i class="fas fa-chart-line me-2 text-warning"></i>
                Biểu đồ tiến độ 30 ngày gần nhất
            </div>
            <div class="btn-group btn-group-sm">
                <button type="button" class="btn btn-outline-secondary active" onclick="changeChartType('line')">
                    <i class="fas fa-chart-line"></i> Line
                </button>
                <button type="button" class="btn btn-outline-secondary" onclick="changeChartType('bar')">
                    <i class="fas fa-chart-bar"></i> Bar
                </button>
            </div>
        </div>
        <div class="card-body">
            <?php if(empty($dates)): ?>
            <div class="text-center py-5">
                <i class="fas fa-chart-line fa-4x text-muted mb-3"></i>
                <p class="text-muted">Chưa có dữ liệu cập nhật tiến độ</p>
                <a href="../hangmuc/update.php?congtrinh_id=<?php echo $id; ?>" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus-circle me-2"></i>Cập nhật tiến độ
                </a>
            </div>
            <?php else: ?>
            <canvas id="progressChart" style="height: 350px;"></canvas>
            <?php endif; ?>
        </div>
    </div>

    <!-- Danh sách hạng mục -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <i class="fas fa-tasks me-2 text-warning"></i>
                Chi tiết tiến độ hạng mục
                <span class="badge bg-secondary ms-2"><?php echo $tong_hm; ?></span>
            </div>
            <div>
                <input type="text" id="searchHangMuc" class="form-control form-control-sm" placeholder="Tìm hạng mục..." style="width: 250px;">
            </div>
        </div>
        <div class="card-body p-0">
            <?php if($tong_hm == 0): ?>
            <div class="text-center py-5">
                <i class="fas fa-tasks fa-4x text-muted mb-3"></i>
                <p class="text-muted">Chưa có hạng mục nào</p>
                <a href="../hangmuc/add.php?congtrinh_id=<?php echo $id; ?>" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus-circle me-2"></i>Thêm hạng mục
                </a>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="hangMucTable">
                    <thead>
                        <tr>
                            <th width="50">STT</th>
                            <th>Tên hạng mục</th>
                            <th width="200">Tiến độ</th>
                            <th width="150">Trạng thái</th>
                            <th width="200">Kế hoạch</th>
                            <th width="150">Cập nhật cuối</th>
                            <th width="120">Kinh phí</th>
                            <th width="100">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $stt = 1;
                        while($hm = mysqli_fetch_assoc($hm_list)): 
                            $is_overdue = ($hm['trang_thai'] != 'Hoàn thành' && strtotime($hm['ngay_ket_thuc']) < time());
                            $tien_do = $hm['tien_do_moi_nhat'] ?? $hm['phan_tram_tien_do'];
                            $progress_color = getProgressColor($tien_do);
                            
                            // Lấy thời gian cập nhật cuối
                            $sql_last = "SELECT thoi_gian_cap_nhat FROM lichsucapnhat 
                                         WHERE hangmuc_id = {$hm['id']} 
                                         ORDER BY thoi_gian_cap_nhat DESC LIMIT 1";
                            $result_last = mysqli_query($conn, $sql_last);
                            $last_update = mysqli_fetch_assoc($result_last);
                        ?>
                        <tr class="<?php echo $is_overdue ? 'table-danger' : ''; ?>">
                            <td><?php echo $stt++; ?></td>
                            <td>
                                <a href="../hangmuc/detail.php?id=<?php echo $hm['id']; ?>" class="text-decoration-none fw-bold">
                                    <?php echo htmlspecialchars($hm['ten_hang_muc']); ?>
                                </a>
                                <?php if($hm['ghi_chu']): ?>
                                <i class="fas fa-info-circle text-muted ms-1" title="<?php echo htmlspecialchars($hm['ghi_chu']); ?>"></i>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="progress flex-grow-1" style="height: 8px;">
                                        <div class="progress-bar bg-<?php echo $progress_color; ?>" 
                                             style="width: <?php echo $tien_do; ?>%"
                                             role="progressbar"
                                             aria-valuenow="<?php echo $tien_do; ?>"
                                             aria-valuemin="0"
                                             aria-valuemax="100">
                                        </div>
                                    </div>
                                    <span class="badge bg-<?php echo $progress_color; ?>" style="min-width: 45px;">
                                        <?php echo $tien_do; ?>%
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
                                    <i class="far fa-calendar-alt text-muted me-1"></i>
                                    BĐ: <?php echo date('d/m/Y', strtotime($hm['ngay_bat_dau'])); ?>
                                    <br>
                                    <i class="far fa-calendar-check text-muted me-1"></i>
                                    KT: <?php echo date('d/m/Y', strtotime($hm['ngay_ket_thuc'])); ?>
                                    <?php if(!$is_overdue && $hm['so_ngay_con'] > 0): ?>
                                        <br>
                                        <span class="badge bg-info">Còn <?php echo $hm['so_ngay_con']; ?> ngày</span>
                                    <?php elseif($is_overdue): ?>
                                        <br>
                                        <span class="badge bg-danger">Chậm <?php echo abs($hm['so_ngay_con']); ?> ngày</span>
                                    <?php endif; ?>
                                </small>
                            </td>
                            <td>
                                <small class="text-muted">
                                    <i class="far fa-clock me-1"></i>
<?php 
if($last_update) {
    $time = strtotime($last_update['thoi_gian_cap_nhat']);
    $now = time();
    $diff = $now - $time;
    
    if($diff < 60) echo 'Vài giây trước';
    elseif($diff < 3600) echo floor($diff/60) . ' phút trước';
    elseif($diff < 86400) echo floor($diff/3600) . ' giờ trước';
    elseif($diff < 2592000) echo floor($diff/86400) . ' ngày trước';
    else echo date('d/m/Y', $time);
} else {
    echo 'Chưa cập nhật';
}
?>                                </small>
                            </td>
                            <td>
                                <small class="text-muted"><?php echo formatMoney($hm['kinh_phi']); ?></small>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="../hangmuc/update.php?id=<?php echo $hm['id']; ?>" 
                                       class="btn btn-sm btn-primary" title="Cập nhật tiến độ">
                                        <i class="fas fa-pen"></i>
                                    </a>
                                    <a href="../hangmuc/detail.php?id=<?php echo $hm['id']; ?>" 
                                       class="btn btn-sm btn-info" title="Chi tiết">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="../hangmuc/history.php?hangmuc_id=<?php echo $hm['id']; ?>" 
                                       class="btn btn-sm btn-secondary" title="Lịch sử">
                                        <i class="fas fa-history"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let progressChart;

<?php if(!empty($dates)): ?>
// Khởi tạo biểu đồ
function initChart(type) {
    const ctx = document.getElementById('progressChart').getContext('2d');
    
    if(progressChart) {
        progressChart.destroy();
    }
    
    progressChart = new Chart(ctx, {
        type: type,
        data: {
            labels: [<?php foreach($dates as $d) echo "'$d',"; ?>],
            datasets: [{
                label: 'Tiến độ trung bình (%)',
                data: [<?php echo implode(',', $progress); ?>],
                borderColor: '#fbbf24',
                backgroundColor: type === 'line' ? 'rgba(251, 191, 36, 0.1)' : '#fbbf24',
                tension: 0.3,
                fill: type === 'line',
                pointBackgroundColor: '#fbbf24',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6,
                barPercentage: 0.6,
                categoryPercentage: 0.8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return 'Tiến độ: ' + context.raw + '%';
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
                    },
                    title: {
                        display: true,
                        text: 'Tiến độ (%)',
                        color: '#64748b'
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    title: {
                        display: true,
                        text: 'Ngày cập nhật',
                        color: '#64748b'
                    }
                }
            }
        }
    });
}

// Thay đổi loại biểu đồ
function changeChartType(type) {
    initChart(type);
    document.querySelectorAll('.btn-group .btn').forEach(btn => {
        btn.classList.remove('active');
    });
    event.target.classList.add('active');
}

// Khởi tạo biểu đồ line mặc định
initChart('line');
<?php endif; ?>

// Xuất báo cáo
function exportProgress() {
    window.location.href = 'export-progress.php?id=<?php echo $id; ?>';
}

// Tìm kiếm hạng mục
document.getElementById('searchHangMuc')?.addEventListener('keyup', function() {
    const searchText = this.value.toLowerCase();
    const table = document.getElementById('hangMucTable');
    if(!table) return;
    
    const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
    
    for(let row of rows) {
        const tenHangMuc = row.cells[1].textContent.toLowerCase();
        if(tenHangMuc.includes(searchText)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    }
});

// Auto refresh data every 30 seconds (optional)
// setInterval(function() {
//     location.reload();
// }, 30000);
</script>

<!-- Custom CSS cho trang này -->
<style>
.stat-card {
    border: none;
    border-radius: 12px;
    transition: all 0.3s;
    cursor: default;
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

#searchHangMuc {
    border-radius: 20px;
    padding: 0.375rem 1rem;
    border: 1px solid #e2e8f0;
    transition: all 0.3s;
}

#searchHangMuc:focus {
    border-color: #fbbf24;
    box-shadow: 0 0 0 3px rgba(251, 191, 36, 0.1);
    outline: none;
}

@media (max-width: 768px) {
    .btn-group {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }
    
    .btn-group .btn {
        margin: 0;
    }
    
    .table {
        font-size: 0.9rem;
    }
}
</style>

<?php require_once '../includes/footer.php'; ?>