<?php
// congtrinh/edit.php
require_once '../includes/header.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Lấy thông tin công trình
$sql = "SELECT * FROM congtrinh WHERE id = $id AND user_id = '{$_SESSION['id']}'";
$result = mysqli_query($conn, $sql);

if(mysqli_num_rows($result) == 0) {
    $_SESSION['error'] = 'Không tìm thấy công trình';
    header('Location: index.php');
    exit();
}

$ct = mysqli_fetch_assoc($result);
$error = '';
$success = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Lấy dữ liệu từ form
    $ten_cong_trinh = mysqli_real_escape_string($conn, $_POST['ten_cong_trinh']);
    $dia_diem = mysqli_real_escape_string($conn, $_POST['dia_diem']);
    $ngay_bat_dau = $_POST['ngay_bat_dau'];
    $ngay_ket_thuc = $_POST['ngay_ket_thuc'];
    $loaiCT_id = (int)$_POST['loaiCT_id'];
    $trangthaiCT_id = (int)$_POST['trangthaiCT_id'];
    $kinh_phi = str_replace(',', '', $_POST['kinh_phi']);
    $mo_ta = mysqli_real_escape_string($conn, $_POST['mo_ta']);
    
    // Validate
    if(empty($ten_cong_trinh)) {
        $error = 'Vui lòng nhập tên công trình';
    } elseif(empty($dia_diem)) {
        $error = 'Vui lòng nhập địa điểm';
    } elseif(strtotime($ngay_ket_thuc) < strtotime($ngay_bat_dau)) {
        $error = 'Ngày kết thúc phải sau ngày bắt đầu';
    } else {
        // Xử lý upload hình ảnh mới
        $hinh_anh = $ct['hinh_anh'];
        if(isset($_FILES['hinh_anh']) && $_FILES['hinh_anh']['size'] > 0) {
            $target_dir = "../../uploads/congtrinh/";
            if(!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            // Xóa hình cũ
            if(!empty($ct['hinh_anh']) && file_exists("../../" . $ct['hinh_anh'])) {
                unlink("../../" . $ct['hinh_anh']);
            }
            
            $file_name = time() . '_' . basename($_FILES['hinh_anh']['name']);
            $target_file = $target_dir . $file_name;
            
            if(move_uploaded_file($_FILES['hinh_anh']['tmp_name'], $target_file)) {
                $hinh_anh = 'uploads/congtrinh/' . $file_name;
            }
        }
        
        // Update database
        $sql = "UPDATE congtrinh SET 
                ten_cong_trinh = '$ten_cong_trinh',
                dia_diem = '$dia_diem',
                ngay_bat_dau = '$ngay_bat_dau',
                ngay_ket_thuc = '$ngay_ket_thuc',
                loaiCT_id = $loaiCT_id,
                trangthaiCT_id = $trangthaiCT_id,
                kinh_phi = '$kinh_phi',
                mo_ta = '$mo_ta',
                hinh_anh = '$hinh_anh'
                WHERE id = $id";
        
        if(mysqli_query($conn, $sql)) {
            logActivity($conn, $_SESSION['id'], 'Cập nhật công trình', "Cập nhật: $ten_cong_trinh");
            $_SESSION['success'] = 'Cập nhật công trình thành công';
            header('Location: detail.php?id=' . $id);
            exit();
        } else {
            $error = 'Lỗi: ' . mysqli_error($conn);
        }
    }
}

// Lấy danh sách loại công trình
$loai = mysqli_query($conn, "SELECT * FROM loaicongtrinh ORDER BY id");
?>

<div class="content-wrapper">
    <div class="page-header">
        <h4 class="mb-0">
            <i class="fas fa-edit me-2 text-warning"></i>
            Sửa công trình: <?php echo htmlspecialchars($ct['ten_cong_trinh']); ?>
        </h4>
    </div>

    <?php if($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data" onsubmit="return validateForm()">
                <!-- Thông tin cơ bản -->
                <h5 class="mb-3">Thông tin cơ bản</h5>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Tên công trình <span class="text-danger">*</span></label>
                        <input type="text" name="ten_cong_trinh" class="form-control" 
                               value="<?php echo $ct['ten_cong_trinh']; ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Mã công trình</label>
                        <input type="text" class="form-control" value="<?php echo $ct['ma_cong_trinh']; ?>" readonly disabled>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Địa điểm <span class="text-danger">*</span></label>
                        <input type="text" name="dia_diem" class="form-control" 
                               value="<?php echo $ct['dia_diem']; ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Loại công trình <span class="text-danger">*</span></label>
                        <select name="loaiCT_id" class="form-select" required>
                            <option value="">-- Chọn loại --</option>
                            <?php while($row = mysqli_fetch_assoc($loai)): ?>
                            <option value="<?php echo $row['id']; ?>" 
                                <?php echo $ct['loaiCT_id'] == $row['id'] ? 'selected' : ''; ?>>
                                <?php echo $row['ten_loai']; ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>

                <!-- Thời gian và kinh phí -->
                <h5 class="mb-3 mt-4">Thời gian và kinh phí</h5>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Ngày bắt đầu <span class="text-danger">*</span></label>
                        <input type="date" name="ngay_bat_dau" class="form-control" 
                               value="<?php echo $ct['ngay_bat_dau']; ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Ngày kết thúc <span class="text-danger">*</span></label>
                        <input type="date" name="ngay_ket_thuc" class="form-control" 
                               value="<?php echo $ct['ngay_ket_thuc']; ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Kinh phí dự kiến (VNĐ)</label>
                        <input type="text" name="kinh_phi" class="form-control money-format" 
                               value="<?php echo number_format($ct['kinh_phi'], 0, ',', '.'); ?>">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Trạng thái</label>
                    <select name="trangthaiCT_id" class="form-select">
                        <option value="1" <?php echo $ct['trangthaiCT_id'] == 1 ? 'selected' : ''; ?>>Chưa thi công</option>
                        <option value="2" <?php echo $ct['trangthaiCT_id'] == 2 ? 'selected' : ''; ?>>Đang thi công</option>
                        <option value="3" <?php echo $ct['trangthaiCT_id'] == 3 ? 'selected' : ''; ?>>Hoàn thành</option>
                    </select>
                </div>

                <!-- Mô tả -->
                <h5 class="mb-3 mt-4">Mô tả chi tiết</h5>
                <div class="mb-3">
                    <textarea name="mo_ta" class="form-control" rows="5"><?php echo $ct['mo_ta']; ?></textarea>
                </div>

                <!-- Hình ảnh -->
                <h5 class="mb-3 mt-4">Hình ảnh công trình</h5>
                <div class="mb-3">
                    <?php if(!empty($ct['hinh_anh'])): ?>
                    <div class="mb-2">
                        <img src="../../<?php echo $ct['hinh_anh']; ?>" class="img-thumbnail" style="max-height: 150px;">
                        <p class="text-muted small mt-1">Hình ảnh hiện tại</p>
                    </div>
                    <?php endif; ?>
                    <input type="file" name="hinh_anh" class="form-control" accept="image/*" onchange="previewImage(this)">
                    <small class="text-muted">Chọn hình mới để thay thế</small>
                </div>
                <div id="imagePreview" class="mb-3"></div>

                <!-- Buttons -->
                <div class="text-center mt-4">
                    <button type="submit" class="btn btn-primary px-5">
                        <i class="fas fa-save me-2"></i>Cập nhật
                    </button>
                    <a href="detail.php?id=<?php echo $id; ?>" class="btn btn-secondary px-5">
                        <i class="fas fa-times me-2"></i>Hủy
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function previewImage(input) {
    const preview = document.getElementById('imagePreview');
    preview.innerHTML = '';
    
    if(input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.innerHTML = `
                <div class="image-preview">
                    <img src="${e.target.result}" class="img-thumbnail" style="max-height: 200px;">
                    <p class="text-muted small mt-1">Hình ảnh mới</p>
                </div>
            `;
        }
        reader.readAsDataURL(input.files[0]);
    }
}

// Format money
document.querySelector('.money-format')?.addEventListener('input', function(e) {
    let value = this.value.replace(/\D/g, '');
    if(value) {
        this.value = new Intl.NumberFormat('vi-VN').format(value);
    }
});

// Validate form
function validateForm() {
    const startDate = new Date(document.querySelector('input[name="ngay_bat_dau"]').value);
    const endDate = new Date(document.querySelector('input[name="ngay_ket_thuc"]').value);
    
    if(endDate < startDate) {
        alert('Ngày kết thúc phải sau ngày bắt đầu!');
        return false;
    }
    return true;
}
</script>

<?php require_once '../includes/footer.php'; ?>