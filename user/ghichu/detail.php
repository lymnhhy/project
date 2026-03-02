<?php
// user/ghichu/detail.php
$page_title = 'Chi tiết ghi chú';
require_once '../includes/header.php';

$user_id = $_SESSION['id'];
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if($id <= 0) {
    $_SESSION['error'] = 'ID không hợp lệ';
    header('Location: index.php');
    exit();
}

// Lấy thông tin ghi chú
$sql = "SELECT gc.*, hm.ten_hang_muc, ct.ten_cong_trinh, ct.id as congtrinh_id,
               u.hoten as nguoi_ghi, u.email as email_nguoi_ghi
        FROM ghichuthicong gc
        LEFT JOIN hangmucthicong hm ON gc.hangmuc_id = hm.id
        LEFT JOIN congtrinh ct ON hm.congtrinh_id = ct.id
        LEFT JOIN users u ON gc.user_id = u.id
        WHERE gc.id = $id AND (gc.user_id = '$user_id' OR ct.user_id = '$user_id')";
$result = mysqli_query($conn, $sql);

if(mysqli_num_rows($result) == 0) {
    $_SESSION['error'] = 'Không tìm thấy ghi chú hoặc không có quyền xem';
    header('Location: index.php');
    exit();
}

$gc = mysqli_fetch_assoc($result);
?>

<div class="content-wrapper">
    <!-- Page Header -->
    <div class="page-header d-flex justify-content-between align-items-center flex-wrap">
        <div>
            <h4 class="mb-1">
                <i class="fas fa-sticky-note me-2 text-warning"></i>
                Chi tiết ghi chú
            </h4>
            <p class="text-muted mb-0">Thông tin chi tiết ghi chú thi công</p>
        </div>
        <div class="d-flex gap-2 mt-2 mt-sm-0">
            <a href="edit.php?id=<?php echo $id; ?>" class="btn btn-primary">
                <i class="fas fa-edit me-2"></i>Sửa
            </a>
            <a href="index.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Quay lại
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <!-- Nội dung ghi chú -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-align-left me-2 text-warning"></i>
                    Nội dung ghi chú
                </div>
                <div class="card-body">
                    <p class="lead"><?php echo nl2br(htmlspecialchars($gc['noi_dung'])); ?></p>
                </div>
            </div>
            
            <!-- Hình ảnh đính kèm -->
            <?php if(!empty($gc['hinh_anh'])): ?>
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-image me-2 text-warning"></i>
                    Hình ảnh đính kèm
                </div>
                <div class="card-body text-center">
                    <img src="<?php echo BASE_URL . '/' . $gc['hinh_anh']; ?>" 
                         alt="Note image" class="img-fluid rounded" style="max-height: 500px;">
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="col-md-4">
            <!-- Thông tin chung -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-info-circle me-2 text-warning"></i>
                    Thông tin chung
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <th style="width: 100px;">Công trình:</th>
                            <td>
                                <a href="../congtrinh/detail.php?id=<?php echo $gc['congtrinh_id']; ?>">
                                    <?php echo htmlspecialchars($gc['ten_cong_trinh']); ?>
                                </a>
                            </td>
                        </tr>
                        <tr>
                            <th>Hạng mục:</th>
                            <td>
                                <a href="../hangmuc/detail.php?id=<?php echo $gc['hangmuc_id']; ?>">
                                    <?php echo htmlspecialchars($gc['ten_hang_muc']); ?>
                                </a>
                            </td>
                        </tr>
                        <tr>
                            <th>Ngày ghi:</th>
                            <td>
                                <span class="badge bg-info">
                                    <i class="far fa-calendar-alt me-1"></i>
                                    <?php echo date('d/m/Y', strtotime($gc['ngay_ghi'])); ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>Người ghi:</th>
                            <td>
                                <i class="fas fa-user me-1"></i>
                                <?php echo htmlspecialchars($gc['nguoi_ghi']); ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Email:</th>
                            <td>
                                <i class="fas fa-envelope me-1"></i>
                                <?php echo htmlspecialchars($gc['email_nguoi_ghi']); ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Tạo lúc:</th>
                            <td>
                                <small><?php echo date('d/m/Y H:i', strtotime($gc['created_at'])); ?></small>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
            
            <!-- Thao tác nhanh -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-tools me-2 text-warning"></i>
                    Thao tác
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="../hangmuc/detail.php?id=<?php echo $gc['hangmuc_id']; ?>" class="btn btn-outline-info">
                            <i class="fas fa-tasks me-2"></i>Xem hạng mục
                        </a>
                        <a href="../congtrinh/detail.php?id=<?php echo $gc['congtrinh_id']; ?>" class="btn btn-outline-primary">
                            <i class="fas fa-building me-2"></i>Xem công trình
                        </a>
                        <a href="edit.php?id=<?php echo $id; ?>" class="btn btn-warning">
                            <i class="fas fa-edit me-2"></i>Sửa ghi chú
                        </a>
                        <button onclick="confirmDelete2(<?php echo $id; ?>)" class="btn btn-danger">
                            <i class="fas fa-trash me-2"></i>Xóa ghi chú
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete2(id) {
    Swal.fire({
        title: 'Xác nhận xóa',
        text: 'Bạn có chắc muốn xóa ghi chú này?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Xóa',
        cancelButtonText: 'Hủy'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'delete.php?id=' + id;
        }
    });
}
</script>

<?php require_once '../includes/footer.php'; ?>