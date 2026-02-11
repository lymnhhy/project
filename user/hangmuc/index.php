<?php
// user/hangmuc/index.php
$page_title = 'Danh sách hạng mục';
require_once '../includes/header.php';

$user_id = $_SESSION['id'];

// Lọc theo công trình
$congtrinh_id = isset($_GET['congtrinh_id']) ? (int)$_GET['congtrinh_id'] : 0;

$where = "WHERE hm.id IS NOT NULL";
if ($congtrinh_id > 0) {
    $where .= " AND hm.congtrinh_id = '$congtrinh_id'";
}

// Lấy danh sách hạng mục
$sql = "SELECT hm.*, ct.ten_cong_trinh 
        FROM hangmucthicong hm
        LEFT JOIN congtrinh ct ON hm.congtrinh_id = ct.id
        $where AND ct.user_id = '$user_id'
        ORDER BY hm.ngay_bat_dau DESC";
$result = mysqli_query($conn, $sql);

// Lấy danh sách công trình để lọc
$sql_congtrinh = "SELECT id, ten_cong_trinh FROM congtrinh WHERE user_id = '$user_id' ORDER BY ten_cong_trinh";
$result_congtrinh = mysqli_query($conn, $sql_congtrinh);

function getProgressColor($percent) {
    if ($percent < 30) return 'danger';
    if ($percent < 70) return 'warning';
    if ($percent < 100) return 'info';
    return 'success';
}
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">Danh sách hạng mục</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active">Hạng mục</li>
            </ol>
        </nav>
    </div>

    <!-- Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-center">
                <div class="col-auto">
                    <select name="congtrinh_id" class="form-select">
                        <option value="0">Tất cả công trình</option>
                        <?php while ($ct = mysqli_fetch_assoc($result_congtrinh)): ?>
                        <option value="<?php echo $ct['id']; ?>" 
                            <?php echo ($congtrinh_id == $ct['id']) ? 'selected' : ''; ?>>
                            <?php echo $ct['ten_cong_trinh']; ?>
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
    </div>

    <!-- Danh sách hạng mục -->
    <div class="card">
        <div class="card-header">
            <i class="fas fa-tasks me-1"></i>
            Tất cả hạng mục
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="dataTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Tên hạng mục</th>
                            <th>Công trình</th>
                            <th>Thời gian</th>
                            <th>Tiến độ</th>
                            <th>Trạng thái</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($result) == 0): ?>
                        <tr>
                            <td colspan="7" class="text-center py-4">
                                <p class="text-muted mb-0">Không có hạng mục nào</p>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php $stt = 1; ?>
                            <?php while ($hm = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td><?php echo $stt++; ?></td>
                                <td><?php echo $hm['ten_hang_muc']; ?></td>
                                <td>
                                    <a href="../congtrinh/detail.php?id=<?php echo $hm['congtrinh_id']; ?>">
                                        <?php echo $hm['ten_cong_trinh']; ?>
                                    </a>
                                </td>
                                <td>
                                    <small>
                                        <?php echo date('d/m/Y', strtotime($hm['ngay_bat_dau'])); ?> - 
                                        <?php echo date('d/m/Y', strtotime($hm['ngay_ket_thuc'])); ?>
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
                                        <a href="update.php?id=<?php echo $hm['id']; ?>" 
                                           class="btn btn-sm btn-outline-success" title="Cập nhật tiến độ">
                                            <i class="fas fa-percent"></i>
                                        </a>
                                        <a href="edit.php?id=<?php echo $hm['id']; ?>" 
                                           class="btn btn-sm btn-outline-primary" title="Sửa">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button onclick="confirmDelete('delete.php?id=<?php echo $hm['id']; ?>')"
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