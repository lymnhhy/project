<?php
// congtrinh/edit.php
require_once '../includes/header.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error = '';

// XỬ LÝ POST - ĐẶT LÊN ĐẦU
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
        // Lấy thông tin công trình hiện tại
        $sql_ct = "SELECT hinh_anh FROM congtrinh WHERE id = $id AND user_id = '{$_SESSION['id']}'";
        $result_ct = mysqli_query($conn, $sql_ct);
        $current_ct = mysqli_fetch_assoc($result_ct);
        
        // Xử lý upload hình ảnh mới
        $hinh_anh = $current_ct['hinh_anh'] ?? '';
        
        if(isset($_FILES['hinh_anh']) && $_FILES['hinh_anh']['size'] > 0) {
            $target_dir = "../../uploads/congtrinh/";
            if(!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            // Xóa hình cũ nếu có
            if(!empty($current_ct['hinh_anh']) && file_exists("../../" . $current_ct['hinh_anh'])) {
                unlink("../../" . $current_ct['hinh_anh']);
            }
            
            $file_name = time() . '_' . basename($_FILES['hinh_anh']['name']);
            $target_file = $target_dir . $file_name;
            $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
            
            // Kiểm tra file hình ảnh
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
            if(in_array($file_type, $allowed_types)) {
                if(move_uploaded_file($_FILES['hinh_anh']['tmp_name'], $target_file)) {
                    $hinh_anh = 'uploads/congtrinh/' . $file_name;
                }
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
                WHERE id = $id AND user_id = '{$_SESSION['id']}'";
        
        if(mysqli_query($conn, $sql)) {
            $_SESSION['success'] = 'Cập nhật công trình thành công';
            // Dùng JavaScript thay vì header
            echo '<script>window.location.href = "detail.php?id=' . $id . '";</script>';
            exit();
        } else {
            $error = 'Lỗi: ' . mysqli_error($conn);
        }
    }
}

// Lấy thông tin công trình
$sql = "SELECT ct.*, lct.ten_loai 
        FROM congtrinh ct
        LEFT JOIN loaicongtrinh lct ON ct.loaiCT_id = lct.id
        WHERE ct.id = $id AND ct.user_id = '{$_SESSION['id']}'";
$result = mysqli_query($conn, $sql);

if(mysqli_num_rows($result) == 0) {
    $_SESSION['error'] = 'Không tìm thấy công trình';
    echo '<script>window.location.href = "index.php";</script>';
    exit();
}

$ct = mysqli_fetch_assoc($result);

// Lấy danh sách loại công trình
$loai = mysqli_query($conn, "SELECT * FROM loaicongtrinh ORDER BY ten_loai");
?>

<div class="content-wrapper">
    <!-- Page Header -->
    <div class="page-header d-flex justify-content-between align-items-center flex-wrap">
        <div>
            <h4 class="mb-0">
                <i class="fas fa-edit me-2 text-warning"></i>
                Sửa công trình: <?php echo htmlspecialchars($ct['ten_cong_trinh']); ?>
            </h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="index.php">Công trình</a></li>
                    <li class="breadcrumb-item active">Sửa</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2">
            <a href="detail.php?id=<?php echo $id; ?>" class="btn btn-outline-info">
                <i class="fas fa-eye me-2"></i>Xem chi tiết
            </a>
            <a href="index.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Quay lại
            </a>
        </div>
    </div>

    <!-- Hiển thị thông báo lỗi -->
    <?php if(!empty($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i>
        <?php echo $error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Hiển thị thông báo thành công từ session -->
    <?php if(isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Form sửa công trình -->
    <div class="card">
        <div class="card-header">
            <i class="fas fa-edit me-2 text-warning"></i>
            Thông tin công trình
        </div>
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data" onsubmit="return validateForm()">
                <!-- Thông tin cơ bản -->
                <h5 class="mb-3 text-primary">1. Thông tin cơ bản</h5>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">
                            Tên công trình <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="ten_cong_trinh" class="form-control" 
                               value="<?php echo htmlspecialchars($ct['ten_cong_trinh']); ?>" 
                               placeholder="Nhập tên công trình" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Mã công trình</label>
                        <input type="text" class="form-control" 
                               value="<?php echo $ct['ma_cong_trinh'] ?? 'CT-'.str_pad($id, 4, '0', STR_PAD_LEFT); ?>" 
                               readonly disabled>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">
                            Địa điểm <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="dia_diem" class="form-control" 
                               value="<?php echo htmlspecialchars($ct['dia_diem']); ?>" 
                               placeholder="Nhập địa điểm xây dựng" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">
                            Loại công trình <span class="text-danger">*</span>
                        </label>
                        <select name="loaiCT_id" class="form-select" required>
                            <option value="">-- Chọn loại công trình --</option>
                            <?php while($row = mysqli_fetch_assoc($loai)): ?>
                            <option value="<?php echo $row['id']; ?>" 
                                <?php echo $ct['loaiCT_id'] == $row['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($row['ten_loai']); ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>

                <!-- Thời gian và kinh phí -->
                <h5 class="mb-3 mt-4 text-primary">2. Thời gian và kinh phí</h5>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">
                            Ngày bắt đầu <span class="text-danger">*</span>
                        </label>
                        <input type="date" name="ngay_bat_dau" class="form-control" 
                               value="<?php echo $ct['ngay_bat_dau']; ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">
                            Ngày kết thúc <span class="text-danger">*</span>
                        </label>
                        <input type="date" name="ngay_ket_thuc" class="form-control" 
                               value="<?php echo $ct['ngay_ket_thuc']; ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Kinh phí dự kiến (VNĐ)</label>
                        <input type="text" name="kinh_phi" class="form-control money-format" 
                               value="<?php echo $ct['kinh_phi'] ? number_format($ct['kinh_phi'], 0, ',', '.') : ''; ?>"
                               placeholder="0">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Trạng thái thi công</label>
                    <select name="trangthaiCT_id" class="form-select">
                        <option value="1" <?php echo $ct['trangthaiCT_id'] == 1 ? 'selected' : ''; ?>>Chưa thi công</option>
                        <option value="2" <?php echo $ct['trangthaiCT_id'] == 2 ? 'selected' : ''; ?>>Đang thi công</option>
                        <option value="3" <?php echo $ct['trangthaiCT_id'] == 3 ? 'selected' : ''; ?>>Hoàn thành</option>
                    </select>
                </div>

                <!-- Mô tả chi tiết -->
                <h5 class="mb-3 mt-4 text-primary">3. Mô tả chi tiết</h5>
                <div class="mb-3">
                    <textarea name="mo_ta" class="form-control" rows="5" 
                              placeholder="Nhập mô tả chi tiết về công trình..."><?php echo htmlspecialchars($ct['mo_ta'] ?? ''); ?></textarea>
                </div>

                <!-- Hình ảnh -->
                <h5 class="mb-3 mt-4 text-primary">4. Hình ảnh công trình</h5>
                <div class="mb-3">
                    <label class="form-label">Hình ảnh hiện tại</label>
                    <div class="mb-2">
                        <?php if(!empty($ct['hinh_anh'])): ?>
                            <img src="../../<?php echo $ct['hinh_anh']; ?>" 
                                 class="img-thumbnail" style="max-height: 150px;">
                            <p class="text-muted small mt-1">Hình ảnh hiện tại</p>
                        <?php else: ?>
                            <p class="text-muted">Chưa có hình ảnh</p>
                        <?php endif; ?>
                    </div>
                    <label class="form-label">Chọn hình ảnh mới</label>
                    <input type="file" name="hinh_anh" class="form-control" accept="image/*" onchange="previewImage(this)">
                    <small class="text-muted">Chấp nhận: JPG, PNG, GIF. Tối đa 5MB</small>
                    <div id="imagePreview" class="mt-2"></div>
                </div>

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
        // Kiểm tra dung lượng
        if(input.files[0].size > 5 * 1024 * 1024) {
            preview.innerHTML = '<div class="alert alert-warning">File quá lớn! Tối đa 5MB</div>';
            input.value = '';
            return;
        }
        
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.innerHTML = `
                <div class="image-preview">
                    <p class="mb-2">Hình ảnh mới:</p>
                    <img src="${e.target.result}" class="img-thumbnail" style="max-height: 200px;">
                </div>
            `;
        }
        reader.readAsDataURL(input.files[0]);
    }
}

// Format tiền tệ
document.querySelector('.money-format')?.addEventListener('input', function(e) {
    let value = this.value.replace(/\D/g, '');
    if(value) {
        this.value = new Intl.NumberFormat('vi-VN').format(value);
    }
});

// Validate form
function validateForm() {
    const ten = document.querySelector('input[name="ten_cong_trinh"]').value.trim();
    const diaDiem = document.querySelector('input[name="dia_diem"]').value.trim();
    const startDate = new Date(document.querySelector('input[name="ngay_bat_dau"]').value);
    const endDate = new Date(document.querySelector('input[name="ngay_ket_thuc"]').value);
    
    if(ten === '') {
        alert('Vui lòng nhập tên công trình');
        return false;
    }
    
    if(diaDiem === '') {
        alert('Vui lòng nhập địa điểm');
        return false;
    }
    
    if(endDate < startDate) {
        alert('Ngày kết thúc phải sau ngày bắt đầu!');
        return false;
    }
    
    return true;
}
</script>

<style>
.form-label {
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 0.5rem;
}
.text-primary {
    color: #3498db !important;
}
.card {
    border: none;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}
.card-header {
    background: white;
    border-bottom: 1px solid #ecf0f1;
    padding: 1rem 1.5rem;
    font-weight: 600;
}
.btn-primary {
    background: #3498db;
    border-color: #3498db;
}
.btn-primary:hover {
    background: #2980b9;
    border-color: #2980b9;
}
</style>

<?php require_once '../includes/footer.php'; ?>