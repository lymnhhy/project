<?php
// user/congtrinh/detail.php
$page_title = 'Chi tiết công trình';
require_once '../includes/header.php';

$user_id = $_SESSION['id'];
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Lấy thông tin công trình
$sql = "SELECT ct.*, lct.ten_loai, ttct.ten_trang_thai 
        FROM congtrinh ct
        LEFT JOIN loaicongtrinh lct ON ct.loaiCT_id = lct.id
        LEFT JOIN trangthaicongtrinh ttct ON ct.trangthaiCT_id = ttct.id
        WHERE ct.id = '$id' AND ct.user_id = '$user_id'";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) == 0) {
    echo "<script>
        alert('Không tìm thấy công trình!');
        window.location.href = 'index.php';
    </script>";
    exit();
}

$ct = mysqli_fetch_assoc($result);

// Tính tiến độ
$sql_tien_do = "SELECT AVG(phan_tram_tien_do) as avg_progress 
                FROM hangmucthicong 
                WHERE congtrinh_id = '$id'";
$result_tien_do = mysqli_query($conn, $sql_tien_do);
$tien_do = round(mysqli_fetch_assoc($result_tien_do)['avg_progress'] ?? 0);

// Lấy danh sách hạng mục
$sql_hangmuc = "SELECT * FROM hangmucthicong 
                WHERE congtrinh_id = '$id' 
                ORDER BY ngay_bat_dau ASC";
$result_hangmuc = mysqli_query($conn, $sql_hangmuc);

// Lấy ghi chú
$sql_ghichu = "SELECT gc.*, u.hoten 
               FROM ghichuthicong gc
               LEFT JOIN users u ON gc.user_id = u.id
               WHERE gc.congtrinh_id = '$id' 
               ORDER BY gc.ngay_ghi DESC LIMIT 5";
$result_ghichu = mysqli_query($conn, $sql_ghichu);

// Format ngày
function formatDate($date) {
    return date('d/m/Y', strtotime($date));
}

// Màu tiến độ
function getProgressColor($percent) {
    if ($percent < 30) return 'danger';
    if ($percent < 70) return 'warning';
    if ($percent < 100) return 'info';
    return 'success';
}
?>

<div class="container-fluid">
    <!-- Page Title -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">Chi tiết công trình</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="index.php">Công trình</a></li>
                <li class="breadcrumb-item active">Chi tiết</li>
            </ol>
        </nav>
    </div>

    <!-- Thông tin công trình -->
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fas fa-info-circle me-1"></i>
                        Thông tin công trình
                    </div>
                    <div>
                        <a href="edit.php?id=<?php echo $id; ?>" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-edit"></i> Sửa
                        </a>
                        <a href="../hangmuc/add.php?congtrinh_id=<?php echo $id; ?>" class="btn btn-sm btn-outline-success">
                            <i class="fas fa-plus-circle"></i> Thêm hạng mục
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <h6 class="text-muted mb-1">Tên công trình</h6>
                            <h5><?php echo $ct['ten_cong_trinh']; ?></h5>
                        </div>
                        <div class="col-md-6 mb-3">
                            <h6 class="text-muted mb-1">Địa điểm</h6>
                            <p class="mb-0"><i class="fas fa-map-marker-alt me-2"></i><?php echo $ct['dia_diem']; ?></p>
                        </div>
                        <div class="col-md-3 mb-3">
                            <h6 class="text-muted mb-1">Ngày bắt đầu</h6>
                            <p class="mb-0"><i class="fas fa-calendar me-2"></i><?php echo formatDate($ct['ngay_bat_dau']); ?></p>
                        </div>
                        <div class="col-md-3 mb-3">
                            <h6 class="text-muted mb-1">Ngày kết thúc</h6>
                            <p class="mb-0"><i class="fas fa-calendar-check me-2"></i><?php echo formatDate($ct['ngay_ket_thuc']); ?></p>
                        </div>
                        <div class="col-md-3 mb-3">
                            <h6 class="text-muted mb-1">Loại công trình</h6>
                            <p class="mb-0"><?php echo $ct['ten_loai'] ?? 'Chưa phân loại'; ?></p>
                        </div>
                        <div class="col-md-3 mb-3">
                            <h6 class="text-muted mb-1">Trạng thái</h6>
                            <span class="badge bg-<?php 
                                echo $ct['trangthaiCT_id'] == 1 ? 'secondary' : 
                                    ($ct['trangthaiCT_id'] == 2 ? 'warning' : 'success'); 
                            ?>">
                                <?php echo $ct['ten_trang_thai']; ?>
                            </span>
                        </div>
                        <div class="col-12 mb-3">
                            <h6 class="text-muted mb-1">Tiến độ tổng thể</h6>
                            <div class="d-flex align-items-center">
                                <div class="progress flex-grow-1 me-3" style="height: 12px;">
                                    <div class="progress-bar bg-<?php echo getProgressColor($tien_do); ?>" 
                                         style="width: <?php echo $tien_do; ?>%"></div>
                                </div>
                                <span class="h5 mb-0"><?php echo $tien_do; ?>%</span>
                            </div>
                        </div>
                        <?php if ($ct['mo_ta']): ?>
                        <div class="col-12">
                            <h6 class="text-muted mb-1">Mô tả</h6>
                            <p class="mb-0"><?php echo nl2br($ct['mo_ta']); ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Danh sách hạng mục -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fas fa-tasks me-1"></i>
                        Hạng mục thi công
                    </div>
                    <a href="../hangmuc/add.php?congtrinh_id=<?php echo $id; ?>" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus-circle"></i> Thêm hạng mục
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Tên hạng mục</th>
                                    <th>Thời gian</th>
                                    <th>Tiến độ</th>
                                    <th>Trạng thái</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($result_hangmuc) == 0): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4">
                                        <p class="text-muted mb-0">Chưa có hạng mục nào</p>
                                    </td>
                                </tr>
                                <?php else: ?>
                                    <?php while ($hm = mysqli_fetch_assoc($result_hangmuc)): ?>
                                    <tr>
                                        <td><?php echo $hm['ten_hang_muc']; ?></td>
                                        <td>
                                            <small>
                                                <?php echo formatDate($hm['ngay_bat_dau']); ?> - 
                                                <?php echo formatDate($hm['ngay_ket_thuc']); ?>
                                            </small>
                                        </td>
                                        <td style="width: 150px;">
                                            <div class="d-flex align-items-center">
                                                <div class="progress flex-grow-1 me-2" style="height: 6px;">
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
                                                echo $hm['trang_thai'] == 'Chưa thi công' ? 'secondary' : 
                                                    ($hm['trang_thai'] == 'Đang thi công' ? 'warning' : 'success'); 
                                            ?>">
                                                <?php echo $hm['trang_thai']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="../hangmuc/update.php?id=<?php echo $hm['id']; ?>" 
                                                   class="btn btn-sm btn-outline-success" title="Cập nhật tiến độ">
                                                    <i class="fas fa-percent"></i>
                                                </a>
                                                <a href="../hangmuc/edit.php?id=<?php echo $hm['id']; ?>" 
                                                   class="btn btn-sm btn-outline-primary" title="Sửa">
                                                    <i class="fas fa-edit"></i>
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
            </div>
        </div>
        
        <!-- Sidebar -->
        <div class="col-md-4">
            <!-- Thông tin nhanh -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-chart-simple me-1"></i>
                    Thông tin nhanh
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Tổng hạng mục:</span>
                            <strong><?php echo mysqli_num_rows($result_hangmuc); ?></strong>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Ngày tạo:</span>
                            <strong><?php echo date('d/m/Y', strtotime($ct['ngay_tao'])); ?></strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Còn lại:</span>
                            <strong class="text-<?php 
                                $con_lai = (strtotime($ct['ngay_ket_thuc']) - time()) / (60*60*24);
                                echo $con_lai <= 7 ? 'danger' : 'success';
                            ?>">
                                <?php echo round($con_lai); ?> ngày
                            </strong>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Ghi chú gần đây -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fas fa-sticky-note me-1"></i>
                        Ghi chú gần đây
                    </div>
                    <a href="../ghichu/add.php?congtrinh_id=<?php echo $id; ?>" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-plus"></i>
                    </a>
                </div>
                <div class="card-body">
                    <?php if (mysqli_num_rows($result_ghichu) == 0): ?>
                        <p class="text-muted text-center py-3 mb-0">
                            <i class="fas fa-comment-slash fa-2x mb-2"></i><br>
                            Chưa có ghi chú
                        </p>
                    <?php else: ?>
                        <?php while ($gc = mysqli_fetch_assoc($result_ghichu)): ?>
                        <div class="mb-3 pb-3 border-bottom">
                            <div class="d-flex justify-content-between mb-1">
                                <small class="text-primary fw-bold"><?php echo $gc['hoten']; ?></small>
                                <small class="text-muted"><?php echo date('d/m/Y', strtotime($gc['ngay_ghi'])); ?></small>
                            </div>
                            <p class="mb-1"><?php echo $gc['noi_dung']; ?></p>
                            <?php if ($gc['hinh_anh']): ?>
                            <img src="../../uploads/ghichu/<?php echo $gc['hinh_anh']; ?>" 
                                 class="img-fluid rounded" style="max-height: 100px;">
                            <?php endif; ?>
                        </div>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once '../includes/footer.php';
?>