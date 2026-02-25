<?php
$page_title = 'Dashboard';
include 'includes/header.php';
include dirname(__DIR__) . "/admin/includes/functions.php";

?>

<?php
// Thống kê công trình
$sql_congtrinh = "SELECT 
                    COUNT(*) as tong,
                    SUM(CASE WHEN trangthaiCT_id = 3 THEN 1 ELSE 0 END) as hoan_thanh,
                    SUM(CASE WHEN trangthaiCT_id = 2 THEN 1 ELSE 0 END) as dang_thi_cong,
                    SUM(CASE WHEN trangthaiCT_id = 1 THEN 1 ELSE 0 END) as chua_thi_cong
                  FROM congtrinh";
$result_ct = mysqli_query($conn, $sql_congtrinh);
$thongke = mysqli_fetch_assoc($result_ct);

// Thống kê người dùng đang hoạt động
$sql_user = "SELECT COUNT(*) as active FROM users WHERE trangthai = 1";
$result_user = mysqli_query($conn, $sql_user);
$user_active = mysqli_fetch_assoc($result_user);
?>

<!-- NỘI DUNG DASHBOARD -->
<div class="stats-grid">
    <div class="stat-box">
        <div class="stat-icon">🏗️</div>
        <div class="stat-content">
            <h3>Tổng công trình</h3>
            <p class="stat-number"><?php echo $thongke['tong'] ?? 0; ?></p>
        </div>
    </div>
    
    <div class="stat-box success">
        <div class="stat-icon">✅</div>
        <div class="stat-content">
            <h3>Hoàn thành</h3>
            <p class="stat-number"><?php echo $thongke['hoan_thanh'] ?? 0; ?></p>
        </div>
    </div>
    
    <div class="stat-box warning">
        <div class="stat-icon">🚧</div>
        <div class="stat-content">
            <h3>Đang thi công</h3>
            <p class="stat-number"><?php echo $thongke['dang_thi_cong'] ?? 0; ?></p>
        </div>
    </div>
    
    <div class="stat-box info">
        <div class="stat-icon">⏳</div>
        <div class="stat-content">
            <h3>Chưa thi công</h3>
            <p class="stat-number"><?php echo $thongke['chua_thi_cong'] ?? 0; ?></p>
        </div>
    </div>
    
    <div class="stat-box primary">
        <div class="stat-icon">👥</div>
        <div class="stat-content">
            <h3>Người dùng hoạt động</h3>
            <p class="stat-number"><?php echo $user_active['active'] ?? 0; ?></p>
        </div>
    </div>
</div>

<div class="recent-section">
    <h3>Công trình gần đây</h3>
    <table class="table">
        <thead>
            <tr>
                <th>Mã CT</th>
                <th>Tên công trình</th>
                <th>Địa điểm</th>
                <th>Tiến độ</th>
                <th>Trạng thái</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $sql_recent = "SELECT c.*, t.ten_trang_thai 
                          FROM congtrinh c
                          LEFT JOIN trangthaicongtrinh t ON c.trangthaiCT_id = t.id
                          ORDER BY c.ngay_tao DESC LIMIT 5";
            $recent = mysqli_query($conn, $sql_recent);
            while($row = mysqli_fetch_assoc($recent)):
                // TÍNH TIẾN ĐỘ BẰNG HÀM (THÊM DÒNG NÀY)
                $tien_do = tinhTienDoCongTrinh($conn, $row['id']);
            ?>
            <tr>
                <td><?php echo $row['ma_cong_trinh']; ?></td>
                <td><?php echo $row['ten_cong_trinh']; ?></td>
                <td><?php echo $row['dia_diem']; ?></td>
                <td>
                    <div class="progress" style="height: 24px; background: #e9ecef; border-radius: 12px; width: 180px;">
                        <div class="progress-bar" 
                             style="width: <?php echo $tien_do; ?>%; 
                                    background: linear-gradient(90deg, #4facfe 0%, #00f2fe 100%);
                                    height: 100%; 
                                    border-radius: 12px; 
                                    display: flex; 
                                    align-items: center; 
                                    justify-content: center; 
                                    color: white; 
                                    font-weight: bold; 
                                    font-size: 11px;">
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
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php include 'includes/footer.php'; ?>