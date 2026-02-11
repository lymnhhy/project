<?php
// user/congtrinh/index.php
$page_title = 'Danh sách công trình';
require_once '../includes/header.php';

$user_id = $_SESSION['id'];

// Hàm tính tiến độ
function tinhTienDoCongTrinh($conn, $congtrinh_id) {
    $sql = "SELECT AVG(phan_tram_tien_do) as avg_progress 
            FROM hangmucthicong 
            WHERE congtrinh_id = '$congtrinh_id'";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
    return round($row['avg_progress'] ?? 0);
}

// Hàm lấy màu
function getProgressColor($percent) {
    if ($percent < 30) return 'danger';
    if ($percent < 70) return 'warning';
    if ($percent < 100) return 'info';
    return 'success';
}

// Xử lý lọc
$where = "WHERE user_id = '$user_id'";
if (isset($_GET['trangthai']) && $_GET['trangthai'] != '') {
    $trangthai = mysqli_real_escape_string($conn, $_GET['trangthai']);
    $where .= " AND trangthaiCT_id = '$trangthai'";
}

// Danh sách công trình
$sql = "SELECT ct.*, lct.ten_loai, ttct.ten_trang_thai 
        FROM congtrinh ct
        LEFT JOIN loaicongtrinh lct ON ct.loaiCT_id = lct.id
        LEFT JOIN trangthaicongtrinh ttct ON ct.trangthaiCT_id = ttct.id
        $where
        ORDER BY ct.ngay_tao DESC";
$result = mysqli_query($conn, $sql);

// Lấy danh sách trạng thái để lọc
$sql_trangthai = "SELECT * FROM trangthaicongtrinh";
$result_trangthai = mysqli_query($conn, $sql_trangthai);
?>

<div class="container-fluid">
    <!-- Page Title -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">Danh sách công trình</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active">Công trình</li>
            </ol>
        </nav>
    </div>

    <!-- Filter và Add Button -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <form method="GET" class="row g-3">
                        <div class="col-auto">
                            <select name="trangthai" class="form-select">
                                <option value="">Tất cả trạng thái</option>
                                <?php while ($tt = mysqli_fetch_assoc($result_trangthai)): ?>
                                <option value="<?php echo $tt['id']; ?>" 
                                    <?php echo (isset($_GET['trangthai']) && $_GET['trangthai'] == $tt['id']) ? 'selected' : ''; ?>>
                                    <?php echo $tt['ten_trang_thai']; ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter"></i> Lọc
                            </button>
                        </div>
                        <div class="col-auto">
                            <a href="index.php" class="btn btn-outline-secondary">
                                <i class="fas fa-undo"></i> Reset
                            </a>
                        </div>
                    </form>
                </div>
                <div class="col-md-4 text-end">
                    <a href="add.php" class="btn btn-primary">
                        <i class="fas fa-plus-circle"></i> Thêm công trình
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Danh sách công trình -->
    <div class="card">
        <div class="card-header">
            <i class="fas fa-table me-1"></i>
            Tất cả công trình
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="dataTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Tên công trình</th>
                            <th>Địa điểm</th>
                            <th>Loại</th>
                            <th>Thời gian</th>
                            <th>Tiến độ</th>
                            <th>Trạng thái</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($result) == 0): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <i class="fas fa-building fa-4x text-muted mb-3"></i>
                                <h5 class="text-muted">Chưa có công trình nào</h5>
                                <p class="text-muted">Hãy thêm công trình mới để bắt đầu quản lý</p>
                                <a href="add.php" class="btn btn-primary mt-2">
                                    <i class="fas fa-plus-circle"></i> Thêm công trình
                                </a>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php $stt = 1; ?>
                            <?php while ($ct = mysqli_fetch_assoc($result)): 
                                $tien_do = tinhTienDoCongTrinh($conn, $ct['id']);
                                $color = getProgressColor($tien_do);
                            ?>
                            <tr>
                                <td><?php echo $stt++; ?></td>
                                <td>
                                    <a href="detail.php?id=<?php echo $ct['id']; ?>" class="text-decoration-none fw-bold">
                                        <?php echo $ct['ten_cong_trinh']; ?>
                                    </a>
                                </td>
                                <td><?php echo $ct['dia_diem']; ?></td>
                                <td><?php echo $ct['ten_loai'] ?? 'Chưa phân loại'; ?></td>
                                <td>
                                    <small>
                                        <?php echo date('d/m/Y', strtotime($ct['ngay_bat_dau'])); ?><br>
                                        <i class="fas fa-arrow-down"></i><br>
                                        <?php echo date('d/m/Y', strtotime($ct['ngay_ket_thuc'])); ?>
                                    </small>
                                </td>
                                <td style="width: 200px;">
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
                                        <button onclick="confirmDelete('delete.php?id=<?php echo $ct['id']; ?>')"
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
</div>

<?php
require_once '../includes/footer.php';
?>