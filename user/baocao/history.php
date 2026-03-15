<?php
// baocao/history.php

require_once '../includes/header.php';

$user_id = $_SESSION['id'];

// Xóa báo cáo
if(isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $sql = "SELECT file_path FROM baocao WHERE id = $id AND user_id = '$user_id'";
    $result = mysqli_query($conn, $sql);
    if(mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        if(file_exists('../../' . $row['file_path'])) {
            unlink('../../' . $row['file_path']);
        }
        mysqli_query($conn, "DELETE FROM baocao WHERE id = $id");
        $_SESSION['success'] = 'Đã xóa báo cáo';
    }
    header('Location: history.php');
    exit();
}

// Lấy danh sách báo cáo
$sql = "SELECT * FROM baocao 
        WHERE user_id = '$user_id' 
        ORDER BY thoi_gian_tao DESC";
$result = mysqli_query($conn, $sql);
?>

<div class="content-wrapper">
    <!-- Page Header -->
    <div class="page-header d-flex justify-content-between align-items-center flex-wrap">
        <div>
            <h4 class="mb-1">
                <i class="fas fa-history me-2 text-warning"></i>
                Lịch sử báo cáo đã xuất
            </h4>
            <p class="text-muted mb-0">Các file báo cáo Excel đã xuất gần đây</p>
        </div>
        <div>
            <a href="index.php" class="btn btn-outline-primary">
                <i class="fas fa-chart-bar me-2"></i>Báo cáo mới
            </a>
        </div>
    </div>

    <!-- Hiển thị thông báo -->
    <?php if(isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i><?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Danh sách báo cáo -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>STT</th>
                            <th>Tên báo cáo</th>
                            <th>Loại</th>
                            <th>Thời gian tạo</th>
                            <th>Dung lượng</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(mysqli_num_rows($result) == 0): ?>
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <i class="fas fa-file-excel fa-3x text-muted mb-3"></i>
                                <h6 class="text-muted">Chưa có báo cáo nào</h6>
                                <a href="index.php" class="btn btn-primary btn-sm mt-2">
                                    <i class="fas fa-plus-circle me-2"></i>Tạo báo cáo mới
                                </a>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php $stt = 1; ?>
                            <?php while($row = mysqli_fetch_assoc($result)): 
                                $file_path = '../../' . $row['file_path'];
                                $file_size = file_exists($file_path) ? filesize($file_path) : 0;
                                $size_text = $file_size > 1024 * 1024 ? round($file_size / (1024*1024), 2) . ' MB' : 
                                            ($file_size > 1024 ? round($file_size / 1024, 2) . ' KB' : $file_size . ' B');
                            ?>
                            <tr>
                                <td><?php echo $stt++; ?></td>
                                <td>
                                    <i class="fas fa-file-excel text-success me-2"></i>
                                    <?php echo htmlspecialchars($row['ten_bao_cao']); ?>
                                </td>
                                <td>
                                    <?php
                                    $loai_text = '';
                                    switch($row['loai_file']) {
                                        case 'tonghop': $loai_text = 'Báo cáo tổng hợp'; break;
                                        case 'congtrinh': $loai_text = 'Báo cáo công trình'; break;
                                        default: $loai_text = $row['loai_file'];
                                    }
                                    ?>
                                    <span class="badge bg-info"><?php echo $loai_text; ?></span>
                                </td>
                                <td><?php echo date('d/m/Y H:i:s', strtotime($row['thoi_gian_tao'])); ?></td>
                                <td><?php echo $size_text; ?></td>
                                <td>
                                    <div class="btn-group">
                                        <?php if(file_exists($file_path)): ?>
                                        <a href="../../<?php echo $row['file_path']; ?>" 
                                           class="btn btn-sm btn-success" 
                                           title="Tải xuống"
                                           download>
                                            <i class="fas fa-download"></i>
                                        </a>
                                        <?php endif; ?>
                                        <a href="?delete=<?php echo $row['id']; ?>" 
                                           class="btn btn-sm btn-danger" 
                                           title="Xóa"
                                           onclick="return confirm('Xóa báo cáo này?')">
                                            <i class="fas fa-trash"></i>
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

<?php require_once '../includes/footer.php'; ?>