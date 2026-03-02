<?php
// user/ghichu/edit.php
ob_start();
$page_title = 'Sửa ghi chú';
require_once '../includes/header.php';

$user_id = $_SESSION['id'];
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error = '';

if ($id <= 0) {
    $_SESSION['error'] = 'ID không hợp lệ';
    header('Location: index.php');
    exit();
}

// ============================================
// XỬ LÝ CẬP NHẬT - ĐẶT LÊN ĐẦU TIÊN
// ============================================
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Lấy dữ liệu từ form
    $noi_dung = isset($_POST['noi_dung']) ? trim($_POST['noi_dung']) : '';
    $ngay_ghi = isset($_POST['ngay_ghi']) ? $_POST['ngay_ghi'] : date('Y-m-d');
    
    // Validate
    if (empty($noi_dung)) {
        $error = 'Vui lòng nhập nội dung ghi chú';
    } else {
        // Cập nhật database - CHỈ CẬP NHẬT NỘI DUNG VÀ NGÀY
        $noi_dung_safe = mysqli_real_escape_string($conn, $noi_dung);
        
        $sql = "UPDATE ghichuthicong SET 
                noi_dung = '$noi_dung_safe',
                ngay_ghi = '$ngay_ghi'
                WHERE id = $id AND user_id = '$user_id'";
        
        if (mysqli_query($conn, $sql)) {
            // Ghi log
            $ip = $_SERVER['REMOTE_ADDR'];
            $log_sql = "INSERT INTO lichsuhoatdong (user_id, hanh_dong, chi_tiet, ip_address, thoi_gian) 
                        VALUES ($user_id, 'Sửa ghi chú', 'Sửa ghi chú ID: $id', '$ip', NOW())";
            mysqli_query($conn, $log_sql);
            
            $_SESSION['success'] = 'Cập nhật ghi chú thành công!';
            header('Location: detail.php?id=' . $id);
            exit();
        } else {
            $error = 'Lỗi database: ' . mysqli_error($conn);
        }
    }
}

// ============================================
// LẤY THÔNG TIN GHI CHÚ (SAU KHI XỬ LÝ POST)
// ============================================
$sql = "SELECT gc.*, 
               hm.ten_hang_muc, hm.id as hangmuc_id,
               ct.id as congtrinh_id, ct.ten_cong_trinh
        FROM ghichuthicong gc
        LEFT JOIN hangmucthicong hm ON gc.hangmuc_id = hm.id
        LEFT JOIN congtrinh ct ON hm.congtrinh_id = ct.id
        WHERE gc.id = $id AND gc.user_id = '$user_id'";
$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Lỗi truy vấn: " . mysqli_error($conn));
}

if (mysqli_num_rows($result) == 0) {
    $_SESSION['error'] = 'Không tìm thấy ghi chú hoặc không có quyền sửa';
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
                <i class="fas fa-edit me-2 text-warning"></i>
                Sửa ghi chú
            </h4>
            <p class="text-muted mb-0">Cập nhật nội dung ghi chú thi công</p>
        </div>
        <div>
            <a href="detail.php?id=<?php echo $id; ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Quay lại
            </a>
        </div>
    </div>

    <!-- Hiển thị thông báo lỗi -->
    <?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Hiển thị thông báo thành công từ session -->
    <?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i><?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <!-- Thông tin liên quan -->
            <div class="mb-4 p-3 bg-light rounded">
                <h6 class="mb-2 text-primary">Thông tin liên quan:</h6>
                <p class="mb-1">
                    <strong>Công trình:</strong> 
                    <a href="../congtrinh/detail.php?id=<?php echo $gc['congtrinh_id']; ?>">
                        <?php echo htmlspecialchars($gc['ten_cong_trinh']); ?>
                    </a>
                </p>
                <p class="mb-0">
                    <strong>Hạng mục:</strong> 
                    <a href="../hangmuc/detail.php?id=<?php echo $gc['hangmuc_id']; ?>">
                        <?php echo htmlspecialchars($gc['ten_hang_muc']); ?>
                    </a>
                </p>
            </div>

            <form method="POST">
                <!-- Ngày ghi chú -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Ngày ghi chú</label>
                        <input type="date" name="ngay_ghi" class="form-control" 
                               value="<?php echo htmlspecialchars($gc['ngay_ghi']); ?>">
                    </div>
                </div>
                
                <!-- Nội dung -->
                <div class="mb-3">
                    <label class="form-label fw-bold">Nội dung ghi chú <span class="text-danger">*</span></label>
                    <textarea name="noi_dung" class="form-control" rows="6"><?php echo htmlspecialchars($gc['noi_dung']); ?></textarea>
                </div>
                
                <!-- Hình ảnh hiện tại (chỉ hiển thị) -->
                <?php if (!empty($gc['hinh_anh'])): ?>
                <div class="mb-3">
                    <label class="form-label fw-bold">Hình ảnh đính kèm</label>
                    <div>
                        <img src="<?php echo BASE_URL . '/' . $gc['hinh_anh']; ?>" 
                             class="img-thumbnail" style="max-height: 150px;">
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Buttons -->
                <div class="text-center mt-4">
                    <button type="submit" class="btn btn-primary px-5">
                        <i class="fas fa-save me-2"></i>Cập nhật
                    </button>
                    <a href="detail.php?id=<?php echo $id; ?>" class="btn btn-outline-secondary px-5">
                        <i class="fas fa-times me-2"></i>Hủy
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>