<?php
// congtrinh/add.php
require_once '../includes/header.php';

$error = '';
$success = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Lấy dữ liệu từ form
    $ma_cong_trinh = mysqli_real_escape_string($conn, $_POST['ma_cong_trinh'] ?? 'CT-' . time());
    $ten_cong_trinh = mysqli_real_escape_string($conn, $_POST['ten_cong_trinh']);
    $dia_diem = mysqli_real_escape_string($conn, $_POST['dia_diem']);
    $ngay_bat_dau = $_POST['ngay_bat_dau'];
    $ngay_ket_thuc = $_POST['ngay_ket_thuc'];
    $loaiCT_id = (int)$_POST['loaiCT_id'];
    $trangthaiCT_id = (int)$_POST['trangthaiCT_id'];
    $phan_tram_tien_do = (int)$_POST['phan_tram_tien_do'];
    
    // XỬ LÝ KINH PHÍ - BỎ DẤU CHẤM TRƯỚC KHI LƯU
    $kinh_phi_raw = $_POST['kinh_phi'] ?? '';
    // Loại bỏ dấu chấm (chỉ giữ lại số)
    $kinh_phi = str_replace('.', '', $kinh_phi_raw);
    // Nếu không có số, gán 0
    $kinh_phi = $kinh_phi !== '' ? (float)$kinh_phi : 0;
    
    $mo_ta = mysqli_real_escape_string($conn, $_POST['mo_ta']);
    $user_id = $_SESSION['id'];
    
    // Validate
    if(empty($ten_cong_trinh)) {
        $error = 'Vui lòng nhập tên công trình';
    } elseif(empty($dia_diem)) {
        $error = 'Vui lòng nhập địa điểm';
    } elseif(empty($ngay_bat_dau)) {
        $error = 'Vui lòng chọn ngày bắt đầu';
    } elseif(empty($ngay_ket_thuc)) {
        $error = 'Vui lòng chọn ngày kết thúc';
    } elseif(strtotime($ngay_ket_thuc) < strtotime($ngay_bat_dau)) {
        $error = 'Ngày kết thúc phải sau ngày bắt đầu';
    } elseif($loaiCT_id == 0) {
        $error = 'Vui lòng chọn loại công trình';
    } else {
        // Upload hình ảnh
        $hinh_anh = '';
        if(isset($_FILES['hinh_anh']) && $_FILES['hinh_anh']['size'] > 0) {
            $target_dir = "../../uploads/congtrinh/";
            if(!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            $file_name = time() . '_' . basename($_FILES['hinh_anh']['name']);
            $target_file = $target_dir . $file_name;
            
            if(move_uploaded_file($_FILES['hinh_anh']['tmp_name'], $target_file)) {
                $hinh_anh = 'uploads/congtrinh/' . $file_name;
            }
        }
        
        // Insert database
        $sql = "INSERT INTO congtrinh (ma_cong_trinh, ten_cong_trinh, dia_diem, ngay_bat_dau, 
                ngay_ket_thuc, loaiCT_id, trangthaiCT_id, phan_tram_tien_do, kinh_phi, mo_ta, user_id, hinh_anh) 
                VALUES ('$ma_cong_trinh', '$ten_cong_trinh', '$dia_diem', '$ngay_bat_dau', 
                '$ngay_ket_thuc', $loaiCT_id, $trangthaiCT_id, $phan_tram_tien_do, '$kinh_phi', '$mo_ta', $user_id, '$hinh_anh')";
        
        if(mysqli_query($conn, $sql)) {
            $congtrinh_id = mysqli_insert_id($conn);
            
            // Ghi log
            if(function_exists('logActivity')) {
                logActivity($conn, $user_id, 'Thêm công trình', "Thêm công trình: $ten_cong_trinh");
            }
            
            $_SESSION['success'] = 'Thêm công trình thành công';
            echo '<script>window.location.href="detail.php?id=' . $congtrinh_id . '";</script>';
            exit();
        } else {
            $error = 'Lỗi: ' . mysqli_error($conn);
        }
    }
}

// Lấy danh sách loại công trình
$loai = mysqli_query($conn, "SELECT * FROM loaicongtrinh ORDER BY id");

// Tạo mã công trình tự động
$ma_ct = 'CT-' . date('Ymd') . '-' . rand(100, 999);
?>

<div class="content-wrapper">
    <div class="page-header">
        <h4 class="mb-0">
            <i class="fas fa-plus-circle me-2 text-warning"></i>
            Thêm công trình mới
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
            <form method="POST" enctype="multipart/form-data" id="addForm">
                <!-- Thông tin cơ bản -->
                <h5 class="mb-3">Thông tin cơ bản</h5>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Tên công trình <span class="text-danger">*</span></label>
                        <input type="text" name="ten_cong_trinh" class="form-control" 
                               value="<?php echo htmlspecialchars($_POST['ten_cong_trinh'] ?? ''); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Mã công trình</label>
                        <div class="input-group">
                            <span class="input-group-text">CT-</span>
                            <input type="text" name="ma_cong_trinh" class="form-control" 
                                   value="<?php echo htmlspecialchars($_POST['ma_cong_trinh'] ?? $ma_ct); ?>">
                        </div>
                        <small class="text-muted">Để trống để tự động tạo</small>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Địa điểm <span class="text-danger">*</span></label>
                        <input type="text" name="dia_diem" class="form-control" 
                               value="<?php echo htmlspecialchars($_POST['dia_diem'] ?? ''); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Loại công trình <span class="text-danger">*</span></label>
                        <select name="loaiCT_id" class="form-select" required>
                            <option value="">-- Chọn loại --</option>
                            <?php while($row = mysqli_fetch_assoc($loai)): ?>
                            <option value="<?php echo $row['id']; ?>" 
                                <?php echo ($_POST['loaiCT_id'] ?? '') == $row['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($row['ten_loai']); ?>
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
                               value="<?php echo $_POST['ngay_bat_dau'] ?? date('Y-m-d'); ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Ngày kết thúc <span class="text-danger">*</span></label>
                        <input type="date" name="ngay_ket_thuc" class="form-control" 
                               value="<?php echo $_POST['ngay_ket_thuc'] ?? date('Y-m-d', strtotime('+6 months')); ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Kinh phí dự kiến (VNĐ)</label>
                        <input type="text" name="kinh_phi" id="kinhPhi" class="form-control text-end" 
                               value="<?php echo isset($_POST['kinh_phi']) ? number_format((float)$_POST['kinh_phi'], 0, ',', '.') : ''; ?>" 
                               placeholder="0">
                        <small class="text-muted">Tự động thêm dấu chấm phân cách</small>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Trạng thái ban đầu</label>
                        <select name="trangthaiCT_id" class="form-select">
                            <option value="1" <?php echo ($_POST['trangthaiCT_id'] ?? '1') == '1' ? 'selected' : ''; ?>>Chưa thi công</option>
                            <option value="2" <?php echo ($_POST['trangthaiCT_id'] ?? '') == '2' ? 'selected' : ''; ?>>Đang thi công</option>
                            <option value="3" <?php echo ($_POST['trangthaiCT_id'] ?? '') == '3' ? 'selected' : ''; ?>>Hoàn thành</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Tiến độ ban đầu (%)</label>
                        <div class="d-flex align-items-center gap-3">
                            <input type="range" name="phan_tram_tien_do" class="form-range" 
                                   min="0" max="100" value="<?php echo $_POST['phan_tram_tien_do'] ?? 0; ?>" 
                                   onchange="updateRange(this.value)">
                            <span class="badge bg-primary" id="rangeValue" style="min-width: 50px;">
                                <?php echo $_POST['phan_tram_tien_do'] ?? 0; ?>%
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Mô tả -->
                <h5 class="mb-3 mt-4">Mô tả chi tiết</h5>
                <div class="mb-3">
                    <textarea name="mo_ta" class="form-control" rows="5" 
                              placeholder="Nhập mô tả chi tiết về công trình..."><?php echo htmlspecialchars($_POST['mo_ta'] ?? ''); ?></textarea>
                </div>

                <!-- Hình ảnh -->
                <h5 class="mb-3 mt-4">Hình ảnh công trình</h5>
                <div class="mb-3">
                    <input type="file" name="hinh_anh" class="form-control" accept="image/*" onchange="previewImage(this)">
                    <small class="text-muted">Chấp nhận: JPG, PNG, GIF. Tối đa 5MB</small>
                </div>
                <div id="imagePreview" class="mb-3"></div>

                <!-- Buttons -->
                <div class="text-center mt-4">
                    <button type="submit" name="submit" class="btn btn-primary px-5">
                        <i class="fas fa-save me-2"></i>Lưu công trình
                    </button>
                    <a href="index.php" class="btn btn-secondary px-5">
                        <i class="fas fa-times me-2"></i>Hủy
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Hàm format số thành dạng có dấu chấm (VD: 120000 -> 120.000)
function formatMoney(amount) {
    return amount.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}

// Hàm loại bỏ dấu chấm để lấy số nguyên
function unformatMoney(str) {
    return str.replace(/\./g, '');
}

// Xử lý input kinh phí
const kinhPhiInput = document.getElementById('kinhPhi');
if (kinhPhiInput) {
    // Khi người dùng nhập
    kinhPhiInput.addEventListener('input', function(e) {
        // Chỉ cho phép nhập số
        let value = this.value.replace(/[^0-9]/g, '');
        if (value) {
            // Format và hiển thị
            this.value = formatMoney(value);
        } else {
            this.value = '';
        }
    });

    // Khi rời khỏi ô input
    kinhPhiInput.addEventListener('blur', function(e) {
        let value = this.value.replace(/[^0-9]/g, '');
        if (value) {
            this.value = formatMoney(value);
        }
    });
}

// Xử lý khi submit form
document.getElementById('addForm').addEventListener('submit', function(e) {
    // Xử lý kinh phí - loại bỏ dấu chấm trước khi gửi đi
    const kinhPhi = document.getElementById('kinhPhi');
    if (kinhPhi) {
        let rawValue = unformatMoney(kinhPhi.value);
        kinhPhi.value = rawValue;
    }
    
    // Validate ngày tháng
    const startDate = new Date(document.querySelector('input[name="ngay_bat_dau"]').value);
    const endDate = new Date(document.querySelector('input[name="ngay_ket_thuc"]').value);
    
    if (endDate < startDate) {
        alert('Ngày kết thúc phải sau ngày bắt đầu!');
        e.preventDefault();
        return false;
    }
    
    return true;
});

// Preview image
function previewImage(input) {
    const preview = document.getElementById('imagePreview');
    preview.innerHTML = '';
    
    if(input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.innerHTML = `
                <div class="image-preview">
                    <img src="${e.target.result}" class="img-thumbnail" style="max-height: 200px;">
                </div>
            `;
        }
        reader.readAsDataURL(input.files[0]);
    }
}

// Update range value
function updateRange(value) {
    document.getElementById('rangeValue').textContent = value + '%';
    
    // Đổi màu badge
    const badge = document.getElementById('rangeValue');
    badge.className = 'badge ';
    if(value == 0) {
        badge.classList.add('bg-secondary');
    } else if(value == 100) {
        badge.classList.add('bg-success');
    } else {
        badge.classList.add('bg-warning');
    }
}

// Khởi tạo
window.onload = function() {
    const rangeValue = document.querySelector('input[name="phan_tram_tien_do"]').value;
    updateRange(rangeValue);
}
</script>

<style>
.image-preview {
    text-align: center;
    padding: 10px;
    border: 2px dashed #dee2e6;
    border-radius: 8px;
}
.image-preview img {
    max-width: 100%;
    border-radius: 8px;
}
.form-label {
    font-weight: 600;
    color: #2c3e50;
}
.input-group-text {
    background-color: #e9ecef;
}
.text-end {
    text-align: right;
}
</style>

<?php require_once '../includes/footer.php'; ?>