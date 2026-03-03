<?php
$page_title = 'Dashboard Admin';
include 'includes/header.php';
include dirname(__DIR__) . "/admin/includes/functions.php";

// ============================================
// THỐNG KÊ TỔNG QUAN
// ============================================

// Thống kê công trình
$sql_congtrinh = "SELECT 
                    COUNT(*) as tong,
                    SUM(CASE WHEN trangthaiCT_id = 3 THEN 1 ELSE 0 END) as hoan_thanh,
                    SUM(CASE WHEN trangthaiCT_id = 2 THEN 1 ELSE 0 END) as dang_thi_cong,
                    SUM(CASE WHEN trangthaiCT_id = 1 THEN 1 ELSE 0 END) as chua_thi_cong
                  FROM congtrinh";
$result_ct = mysqli_query($conn, $sql_congtrinh);
$thongke = mysqli_fetch_assoc($result_ct);

// Thống kê hạng mục
$sql_hangmuc = "SELECT 
                  COUNT(*) as tong,
                  SUM(CASE WHEN trang_thai = 'Hoàn thành' THEN 1 ELSE 0 END) as hoan_thanh,
                  SUM(CASE WHEN trang_thai = 'Đang thi công' THEN 1 ELSE 0 END) as dang_thi_cong,
                  SUM(CASE WHEN trang_thai = 'Chưa thi công' THEN 1 ELSE 0 END) as chua_thi_cong
                FROM hangmucthicong";
$result_hm = mysqli_query($conn, $sql_hangmuc);
$thongke_hm = mysqli_fetch_assoc($result_hm);

// Thống kê người dùng
$sql_user = "SELECT 
               COUNT(*) as tong,
               SUM(CASE WHEN role_id = 1 THEN 1 ELSE 0 END) as admin,
               SUM(CASE WHEN role_id = 2 AND trangthai = 1 THEN 1 ELSE 0 END) as user_active,
               SUM(CASE WHEN role_id = 2 AND trangthai = 0 THEN 1 ELSE 0 END) as user_blocked
             FROM users";
$result_user = mysqli_query($conn, $sql_user);
$thongke_user = mysqli_fetch_assoc($result_user);

// ============================================
// DỮ LIỆU CHO BIỂU ĐỒ 1: TIẾN ĐỘ CÔNG TRÌNH
// ============================================
$sql_chart1 = "SELECT 
                ct.id, 
                ct.ten_cong_trinh,
                COUNT(hm.id) as so_hang_muc,
                AVG(hm.phan_tram_tien_do) as tien_do_tb
              FROM congtrinh ct
              LEFT JOIN hangmucthicong hm ON ct.id = hm.congtrinh_id
              GROUP BY ct.id
              ORDER BY ct.ngay_tao DESC
              LIMIT 10";
$chart1_data = mysqli_query($conn, $sql_chart1);

$ct_labels = [];
$ct_tiendo = [];
$ct_hm_count = [];

if ($chart1_data && mysqli_num_rows($chart1_data) > 0) {
    while($row = mysqli_fetch_assoc($chart1_data)) {
        $ct_labels[] = $row['ten_cong_trinh'] ?: 'Không tên';
        $ct_tiendo[] = round($row['tien_do_tb'] ?? 0);
        $ct_hm_count[] = $row['so_hang_muc'] ?? 0;
    }
} else {
    $ct_labels = ['Chưa có dữ liệu'];
    $ct_tiendo = [0];
    $ct_hm_count = [0];
}

// ============================================
// DỮ LIỆU CHO BIỂU ĐỒ 2: HẠNG MỤC THEO CÔNG TRÌNH
// ============================================
$sql_chart2 = "SELECT 
                    ct.id,
                    ct.ten_cong_trinh,
                    COUNT(hm.id) as tong_hm,
                    SUM(CASE WHEN hm.trang_thai = 'Hoàn thành' THEN 1 ELSE 0 END) as hm_hoanthanh,
                    SUM(CASE WHEN hm.trang_thai = 'Đang thi công' THEN 1 ELSE 0 END) as hm_dangtc,
                    SUM(CASE WHEN hm.trang_thai = 'Chưa thi công' THEN 1 ELSE 0 END) as hm_chuatc,
                    AVG(hm.phan_tram_tien_do) as tien_do_tb
                FROM congtrinh ct
                LEFT JOIN hangmucthicong hm ON ct.id = hm.congtrinh_id
                GROUP BY ct.id
                HAVING tong_hm > 0
                ORDER BY ct.ngay_tao DESC
                LIMIT 10";
$chart2_data = mysqli_query($conn, $sql_chart2);

$hm_labels = [];
$hm_tiendo = [];
$hm_hoanthanh = [];
$hm_dangtc = [];
$hm_chuatc = [];

if ($chart2_data && mysqli_num_rows($chart2_data) > 0) {
    while($row = mysqli_fetch_assoc($chart2_data)) {
        $hm_labels[] = $row['ten_cong_trinh'] ?: 'Không tên';
        $hm_tiendo[] = round($row['tien_do_tb'] ?? 0);
        $hm_hoanthanh[] = $row['hm_hoanthanh'] ?? 0;
        $hm_dangtc[] = $row['hm_dangtc'] ?? 0;
        $hm_chuatc[] = $row['hm_chuatc'] ?? 0;
    }
}

// ============================================
// DỮ LIỆU CHO BIỂU ĐỒ TRÒN
// ============================================
$status_data = [$thongke['chua_thi_cong'] ?? 0, $thongke['dang_thi_cong'] ?? 0, $thongke['hoan_thanh'] ?? 0];
$hm_status_data = [$thongke_hm['chua_thi_cong'] ?? 0, $thongke_hm['dang_thi_cong'] ?? 0, $thongke_hm['hoan_thanh'] ?? 0];
?>

<!-- CSS CHO DASHBOARD -->
<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    background: #f4f6f9;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.content-wrapper {
    padding: 25px;
}

.dashboard-header {
    margin-bottom: 30px;
}

.dashboard-title {
    font-size: 24px;
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 10px;
}

.dashboard-title i {
    color: #3498db;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    display: flex;
    align-items: center;
    transition: all 0.3s;
    border-left: 5px solid;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    margin-right: 15px;
    background: rgba(0,0,0,0.03);
}

.stat-content {
    flex: 1;
}

.stat-content h3 {
    font-size: 14px;
    color: #7f8c8d;
    margin-bottom: 5px;
    font-weight: 500;
}

.stat-number {
    font-size: 28px;
    font-weight: 700;
    margin: 0;
    line-height: 1.2;
}

.stat-label {
    font-size: 12px;
    color: #95a5a6;
    margin-top: 5px;
}

/* Màu sắc cho stat card */
.stat-card.primary { border-left-color: #3498db; }
.stat-card.primary .stat-icon { color: #3498db; }
.stat-card.primary .stat-number { color: #3498db; }

.stat-card.success { border-left-color: #2ecc71; }
.stat-card.success .stat-icon { color: #2ecc71; }
.stat-card.success .stat-number { color: #2ecc71; }

.stat-card.warning { border-left-color: #f39c12; }
.stat-card.warning .stat-icon { color: #f39c12; }
.stat-card.warning .stat-number { color: #f39c12; }

.stat-card.danger { border-left-color: #e74c3c; }
.stat-card.danger .stat-icon { color: #e74c3c; }
.stat-card.danger .stat-number { color: #e74c3c; }

.stat-card.info { border-left-color: #1abc9c; }
.stat-card.info .stat-icon { color: #1abc9c; }
.stat-card.info .stat-number { color: #1abc9c; }

.stat-card.secondary { border-left-color: #95a5a6; }
.stat-card.secondary .stat-icon { color: #95a5a6; }
.stat-card.secondary .stat-number { color: #95a5a6; }

/* Chart grid */
.chart-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
}

.chart-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    min-height: 400px;
    position: relative;
}

.chart-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid #ecf0f1;
}

.chart-header h3 {
    font-size: 18px;
    font-weight: 600;
    color: #2c3e50;
    margin: 0;
}

.chart-header i {
    color: #bdc3c7;
    font-size: 20px;
}

canvas {
    max-width: 100%;
    height: auto !important;
    display: block;
    max-height: 300px;
    width: 100% !important;
}

/* Table styles */
.table-section {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    margin-bottom: 30px;
}

.table-section h3 {
    font-size: 18px;
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid #ecf0f1;
}

.table {
    width: 100%;
    border-collapse: collapse;
}

.table th {
    text-align: left;
    padding: 12px;
    background: #f8f9fa;
    color: #2c3e50;
    font-weight: 600;
    font-size: 13px;
    border-bottom: 2px solid #dee2e6;
}

.table td {
    padding: 12px;
    border-bottom: 1px solid #e9ecef;
    vertical-align: middle;
}

.table tbody tr:hover {
    background: #f8f9fa;
}

.progress-wrapper {
    width: 200px;
    height: 24px;
    background: #e9ecef;
    border-radius: 12px;
    overflow: hidden;
    position: relative;
}

.progress-bar {
    height: 100%;
    background: linear-gradient(90deg, #4facfe 0%, #00f2fe 100%);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    font-size: 11px;
    transition: width 0.3s ease;
}

.badge {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 500;
    color: white;
    display: inline-block;
    margin: 2px;
}

.badge-success { background: #2ecc71; }
.badge-warning { background: #f39c12; color: #2c3e50; }
.badge-secondary { background: #95a5a6; }
.badge-info { background: #3498db; }

.quick-actions {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
    margin-top: 20px;
}

.action-btn {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 10px;
    padding: 15px;
    text-align: center;
    transition: all 0.3s;
    cursor: pointer;
    text-decoration: none;
    color: #2c3e50;
    display: block;
}

.action-btn:hover {
    background: #f8f9fa;
    transform: translateY(-3px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    border-color: transparent;
    text-decoration: none;
    color: #2c3e50;
}

.action-btn i {
    font-size: 24px;
    margin-bottom: 10px;
    display: block;
}

.action-btn.primary i { color: #3498db; }
.action-btn.success i { color: #2ecc71; }
.action-btn.warning i { color: #f39c12; }
.action-btn.info i { color: #1abc9c; }

@media (max-width: 768px) {
    .chart-grid {
        grid-template-columns: 1fr;
    }
    
    .stats-grid {
        grid-template-columns: 1fr 1fr;
    }
}
</style>

<div class="content-wrapper">
    <!-- Dashboard Header -->
    <div class="dashboard-header">
        <h2 class="dashboard-title">
            <i class="fas fa-chart-pie me-2 text-primary"></i>
            Tổng quan hệ thống
        </h2>
        <p class="text-muted">Xin chào, <?php echo htmlspecialchars($_SESSION['user']); ?> | <?php echo date('d/m/Y H:i'); ?></p>
    </div>

    <!-- Stats Grid -->
    <div class="stats-grid">
        <div class="stat-card primary">
            <div class="stat-icon">🏗️</div>
            <div class="stat-content">
                <h3>Tổng công trình</h3>
                <p class="stat-number"><?php echo $thongke['tong'] ?? 0; ?></p>
                <div class="stat-label">Đã hoàn thành: <?php echo $thongke['hoan_thanh'] ?? 0; ?></div>
            </div>
        </div>

        <div class="stat-card warning">
            <div class="stat-icon">🚧</div>
            <div class="stat-content">
                <h3>Đang thi công</h3>
                <p class="stat-number"><?php echo $thongke['dang_thi_cong'] ?? 0; ?></p>
                <div class="stat-label">Công trình</div>
            </div>
        </div>

        <div class="stat-card info">
            <div class="stat-icon">📋</div>
            <div class="stat-content">
                <h3>Tổng hạng mục</h3>
                <p class="stat-number"><?php echo $thongke_hm['tong'] ?? 0; ?></p>
                <div class="stat-label">Hoàn thành: <?php echo $thongke_hm['hoan_thanh'] ?? 0; ?></div>
            </div>
        </div>

        <div class="stat-card success">
            <div class="stat-icon">👥</div>
            <div class="stat-content">
                <h3>Người dùng</h3>
                <p class="stat-number"><?php echo $thongke_user['tong'] ?? 0; ?></p>
                <div class="stat-label">Hoạt động: <?php echo $thongke_user['user_active'] ?? 0; ?></div>
            </div>
        </div>

        <div class="stat-card secondary">
            <div class="stat-icon">⏳</div>
            <div class="stat-content">
                <h3>Chưa thi công</h3>
                <p class="stat-number"><?php echo $thongke['chua_thi_cong'] ?? 0; ?></p>
                <div class="stat-label">Công trình</div>
            </div>
        </div>

        <div class="stat-card danger">
            <div class="stat-icon">🔨</div>
            <div class="stat-content">
                <h3>Hạng mục đang làm</h3>
                <p class="stat-number"><?php echo $thongke_hm['dang_thi_cong'] ?? 0; ?></p>
                <div class="stat-label">Đang thi công</div>
            </div>
        </div>
    </div>

    <!-- HÀNG 1: 2 BIỂU ĐỒ CHÍNH -->
    <div class="chart-grid">
        <!-- BIỂU ĐỒ 1: Tiến độ công trình -->
        <div class="chart-card">
            <div class="chart-header">
                <h3><i class="fas fa-chart-line me-2 text-primary"></i>Tiến độ công trình</h3>
                <i class="fas fa-building"></i>
            </div>
            <div style="height: 300px; position: relative;">
                <canvas id="progressChart"></canvas>
            </div>
            <?php if(empty($ct_labels) || $ct_labels[0] == 'Chưa có dữ liệu'): ?>
            <p class="text-muted text-center mt-3">Chưa có công trình nào để hiển thị</p>
            <?php endif; ?>
        </div>

        <!-- BIỂU ĐỒ 2: Hạng mục theo công trình -->
        <div class="chart-card">
            <div class="chart-header">
                <h3><i class="fas fa-chart-bar me-2 text-success"></i>Hạng mục theo công trình</h3>
                <i class="fas fa-tasks"></i>
            </div>
            <div style="height: 300px; position: relative;">
                <canvas id="hmProgressChart"></canvas>
            </div>
            <?php if(empty($hm_labels)): ?>
            <p class="text-muted text-center mt-3">Chưa có hạng mục nào để hiển thị</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- HÀNG 2: 2 BIỂU ĐỒ TRÒN -->
    <div class="chart-grid" style="margin-top: 20px;">
        <!-- Biểu đồ tròn trạng thái công trình -->
        <div class="chart-card">
            <div class="chart-header">
                <h3><i class="fas fa-chart-pie me-2 text-warning"></i>Trạng thái công trình</h3>
                <i class="fas fa-chart-simple"></i>
            </div>
            <div style="height: 300px; display: flex; flex-direction: column; align-items: center;">
                <canvas id="statusChart" style="height: 200px; width: 200px;"></canvas>
                <div style="text-align: center; margin-top: 20px;">
                    <span class="badge badge-secondary">Chưa TC: <?php echo $status_data[0]; ?></span>
                    <span class="badge badge-warning">Đang TC: <?php echo $status_data[1]; ?></span>
                    <span class="badge badge-success">Hoàn thành: <?php echo $status_data[2]; ?></span>
                </div>
            </div>
        </div>

        <!-- Biểu đồ tròn trạng thái hạng mục -->
        <div class="chart-card">
            <div class="chart-header">
                <h3><i class="fas fa-chart-pie me-2 text-danger"></i>Trạng thái hạng mục</h3>
                <i class="fas fa-tasks"></i>
            </div>
            <div style="height: 300px; display: flex; flex-direction: column; align-items: center;">
                <canvas id="hmStatusChart" style="height: 200px; width: 200px;"></canvas>
                <div style="text-align: center; margin-top: 20px;">
                    <span class="badge badge-secondary">Chưa TC: <?php echo $hm_status_data[0]; ?></span>
                    <span class="badge badge-warning">Đang TC: <?php echo $hm_status_data[1]; ?></span>
                    <span class="badge badge-success">Hoàn thành: <?php echo $hm_status_data[2]; ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Bảng công trình gần đây -->
    <div class="table-section">
        <h3><i class="fas fa-clock me-2 text-warning"></i>Công trình gần đây</h3>
        <table class="table">
            <thead>
                <tr>
                    <th>Mã CT</th>
                    <th>Tên công trình</th>
                    <th>Địa điểm</th>
                    <th>Số hạng mục</th>
                    <th>Tiến độ</th>
                    <th>Trạng thái</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $sql_recent = "SELECT c.*, t.ten_trang_thai,
                              COUNT(hm.id) as so_hm
                              FROM congtrinh c
                              LEFT JOIN trangthaicongtrinh t ON c.trangthaiCT_id = t.id
                              LEFT JOIN hangmucthicong hm ON c.id = hm.congtrinh_id
                              GROUP BY c.id
                              ORDER BY c.ngay_tao DESC LIMIT 5";
                $recent = mysqli_query($conn, $sql_recent);
                
                if ($recent && mysqli_num_rows($recent) > 0):
                    while($row = mysqli_fetch_assoc($recent)):
                        $tien_do = tinhTienDoCongTrinh($conn, $row['id']);
                ?>
                <tr>
                    <td><strong><?php echo $row['ma_cong_trinh'] ?? 'CT-'.str_pad($row['id'], 4, '0', STR_PAD_LEFT); ?></strong></td>
                    <td><?php echo htmlspecialchars($row['ten_cong_trinh']); ?></td>
                    <td><?php echo htmlspecialchars($row['dia_diem']); ?></td>
                    <td><span class="badge badge-info"><?php echo $row['so_hm']; ?> hạng mục</span></td>
                    <td>
                        <div class="progress-wrapper">
                            <div class="progress-bar" style="width: <?php echo $tien_do; ?>%;">
                                <?php echo $tien_do; ?>%
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="badge badge-<?php 
                            echo $row['trangthaiCT_id'] == 3 ? 'success' : 
                                ($row['trangthaiCT_id'] == 2 ? 'warning' : 'secondary'); 
                        ?>">
                            <?php echo $row['ten_trang_thai']; ?>
                        </span>
                    </td>
                    <td>
                        <a href="functions/congtrinh.php?edit=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-edit"></i>
                        </a>
                        <a href="functions/congtrinh.php?view=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-info">
                            <i class="fas fa-eye"></i>
                        </a>
                    </td>
                </tr>
                <?php 
                    endwhile;
                else:
                ?>
                <tr>
                    <td colspan="7" class="text-center py-4">
                        <i class="fas fa-building fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Chưa có công trình nào</p>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions">
        <a href="functions/congtrinh.php" class="action-btn primary">
            <i class="fas fa-building"></i>
            <span>Quản lý loại công trình</span>
        </a>
        <a href="functions/users.php" class="action-btn success">
            <i class="fas fa-users"></i>
            <span>Quản lý người dùng</span>
        </a>
        <a href="functions/website.php" class="action-btn info">
            <i class="fas fa-globe"></i>
            <span>Cấu hình web</span>
        </a>
        <a href="functions/hangmuc.php" class="action-btn warning">
            <i class="fas fa-tasks"></i>
            <span>Loại hạng mục</span>
        </a>
    </div>
</div>

<!-- Thêm Chart.js nếu chưa có -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
// Hàm kiểm tra và hủy biểu đồ cũ
function destroyChart(chartInstance) {
    if (chartInstance && typeof chartInstance.destroy === 'function') {
        chartInstance.destroy();
    }
}

// Hủy các biểu đồ cũ nếu tồn tại - SỬ DỤNG HÀM KIỂM TRA
destroyChart(window.progressChart);
destroyChart(window.hmProgressChart);
destroyChart(window.statusChart);
destroyChart(window.hmStatusChart);

// ============================================
// BIỂU ĐỒ 1: Tiến độ công trình
// ============================================
const ctx1 = document.getElementById('progressChart')?.getContext('2d');
if (ctx1) {
    window.progressChart = new Chart(ctx1, {
        type: 'bar',
        data: {
            labels: [<?php foreach($ct_labels as $label) { echo '"' . addslashes($label) . '",'; } ?>],
            datasets: [
                {
                    label: 'Tiến độ (%)',
                    data: [<?php echo implode(',', $ct_tiendo); ?>],
                    backgroundColor: 'rgba(52, 152, 219, 0.7)',
                    borderColor: '#2980b9',
                    borderWidth: 1,
                    borderRadius: 5,
                    yAxisID: 'y'
                },
                {
                    label: 'Số hạng mục',
                    data: [<?php echo implode(',', $ct_hm_count); ?>],
                    backgroundColor: 'rgba(46, 204, 113, 0.7)',
                    borderColor: '#27ae60',
                    borderWidth: 1,
                    borderRadius: 5,
                    yAxisID: 'y1'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'top',
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    title: {
                        display: true,
                        text: 'Tiến độ (%)'
                    }
                },
                y1: {
                    position: 'right',
                    beginAtZero: true,
                    grid: {
                        drawOnChartArea: false
                    },
                    title: {
                        display: true,
                        text: 'Số hạng mục'
                    }
                }
            }
        }
    });
}

// ============================================
// BIỂU ĐỒ 2: Hạng mục theo công trình
// ============================================
const hmCtx = document.getElementById('hmProgressChart')?.getContext('2d');
if (hmCtx) {
    window.hmProgressChart = new Chart(hmCtx, {
        type: 'bar',
        data: {
            labels: [<?php foreach($hm_labels as $label) { echo '"' . addslashes($label) . '",'; } ?>],
            datasets: [
                {
                    label: 'Hoàn thành',
                    data: [<?php echo implode(',', $hm_hoanthanh); ?>],
                    backgroundColor: '#2ecc71',
                    borderRadius: 5,
                    stack: 'stack0'
                },
                {
                    label: 'Đang thi công',
                    data: [<?php echo implode(',', $hm_dangtc); ?>],
                    backgroundColor: '#f39c12',
                    borderRadius: 5,
                    stack: 'stack0'
                },
                {
                    label: 'Chưa thi công',
                    data: [<?php echo implode(',', $hm_chuatc); ?>],
                    backgroundColor: '#95a5a6',
                    borderRadius: 5,
                    stack: 'stack0'
                },
                {
                    label: 'Tiến độ %',
                    data: [<?php echo implode(',', $hm_tiendo); ?>],
                    type: 'line',
                    borderColor: '#3498db',
                    backgroundColor: 'transparent',
                    borderWidth: 3,
                    tension: 0.1,
                    pointBackgroundColor: '#2980b9',
                    pointBorderColor: 'white',
                    pointRadius: 5,
                    pointHoverRadius: 7,
                    yAxisID: 'y1'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'top',
                },
                tooltip: {
                    mode: 'index',
                    intersect: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Số lượng hạng mục'
                    },
                    grid: {
                        color: 'rgba(0,0,0,0.05)'
                    }
                },
                y1: {
                    position: 'right',
                    beginAtZero: true,
                    max: 100,
                    title: {
                        display: true,
                        text: 'Tiến độ %'
                    },
                    grid: {
                        drawOnChartArea: false
                    }
                }
            }
        }
    });
}

// ============================================
// BIỂU ĐỒ 3: Trạng thái công trình
// ============================================
const ctx2 = document.getElementById('statusChart')?.getContext('2d');
if (ctx2) {
    window.statusChart = new Chart(ctx2, {
        type: 'doughnut',
        data: {
            labels: ['Chưa thi công', 'Đang thi công', 'Hoàn thành'],
            datasets: [{
                data: [<?php echo implode(',', $status_data); ?>],
                backgroundColor: ['#95a5a6', '#f39c12', '#2ecc71'],
                borderWidth: 0,
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            cutout: '65%',
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
}

// ============================================
// BIỂU ĐỒ 4: Trạng thái hạng mục
// ============================================
const ctx3 = document.getElementById('hmStatusChart')?.getContext('2d');
if (ctx3) {
    window.hmStatusChart = new Chart(ctx3, {
        type: 'doughnut',
        data: {
            labels: ['Chưa thi công', 'Đang thi công', 'Hoàn thành'],
            datasets: [{
                data: [<?php echo implode(',', $hm_status_data); ?>],
                backgroundColor: ['#95a5a6', '#f39c12', '#2ecc71'],
                borderWidth: 0,
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            cutout: '65%',
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
}
</script>

<?php include 'includes/footer.php'; ?>