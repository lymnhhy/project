<?php
// hangmuc/add.php
require_once '../includes/header.php';

$congtrinh_id = isset($_GET['congtrinh_id']) ? (int)$_GET['congtrinh_id'] : 0;
$error = '';
$success = '';

// Lấy danh sách công trình của user
$sql_ct = "SELECT id, ten_cong_trinh FROM congtrinh WHERE user_id = '{$_SESSION['id']}' ORDER BY ten_cong_trinh";
$congtrinh_list = mysqli_query($conn, $sql_ct);

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Lấy dữ liệu từ form
    $congtrinh_id = (int)$_POST['congtrinh_id'];
    $ma_hang_muc = mysqli_real_escape_string($conn, $_POST['ma_hang_muc'] ?? 'HM-' . time());
    $ten_hang_muc = mysqli_real_escape_string($conn, $_POST['ten_hang_muc']);
    
    // XỬ LÝ KINH PHÍ - CHỈ LOẠI BỎ DẤU CHẤM, GIỮ NGUYÊN SỐ
    $kinh_phi_raw = $_POST['kinh_phi'] ?? '0';
    // Loại bỏ dấu chấm (phân cách nghìn)
    $kinh_phi = str_replace('.', '', $kinh_phi_raw);
    // Nếu có dấu phẩy (do number_format), chuyển thành .
    $kinh_phi = str_replace(',', '', $kinh_phi);
    // Chuyển thành số
    $kinh_phi = $kinh_phi !== '' ? (float)$kinh_phi : 0;
    
    $ngay_bat_dau = $_POST['ngay_bat_dau'];
    $ngay_ket_thuc = $_POST['ngay_ket_thuc'];
    $phan_tram_tien_do = (int)$_POST['phan_tram_tien_do'];
    $ghi_chu = mysqli_real_escape_string($conn, $_POST['ghi_chu']);
    
    // Xác định trạng thái dựa vào tiến độ
    if($phan_tram_tien_do == 0) {
        $trang_thai = 'Chưa thi công';
    } elseif($phan_tram_tien_do == 100) {
        $trang_thai = 'Hoàn thành';
    } else {
        $trang_thai = 'Đang thi công';
    }
    
    // Validate
    if(empty($ten_hang_muc)) {
        $error = 'Vui lòng nhập tên hạng mục';
    } elseif(empty($congtrinh_id)) {
        $error = 'Vui lòng chọn công trình';
    } elseif(empty($ngay_bat_dau)) {
        $error = 'Vui lòng chọn ngày bắt đầu';
    } elseif(empty($ngay_ket_thuc)) {
        $error = 'Vui lòng chọn ngày kết thúc';
    } elseif(strtotime($ngay_ket_thuc) < strtotime($ngay_bat_dau)) {
        $error = 'Ngày kết thúc phải sau ngày bắt đầu';
    } else {
        // Insert database
        $sql = "INSERT INTO hangmucthicong (ma_hang_muc, ten_hang_muc, congtrinh_id, kinh_phi, 
                ngay_bat_dau, ngay_ket_thuc, phan_tram_tien_do, trang_thai, ghi_chu) 
                VALUES ('$ma_hang_muc', '$ten_hang_muc', $congtrinh_id, '$kinh_phi', 
                '$ngay_bat_dau', '$ngay_ket_thuc', $phan_tram_tien_do, '$trang_thai', '$ghi_chu')";
        
        if(mysqli_query($conn, $sql)) {
            $hangmuc_id = mysqli_insert_id($conn);
            
            // Ghi log
            if(function_exists('logActivity')) {
                logActivity($conn, $_SESSION['id'], 'Thêm hạng mục', "Thêm hạng mục: $ten_hang_muc");
            }
            
            // Ghi lịch sử cập nhật
            $sql_lichsu = "INSERT INTO lichsucapnhat (hangmuc_id, phan_tram_cu, phan_tram_moi, ghi_chu) 
                          VALUES ($hangmuc_id, 0, $phan_tram_tien_do, 'Khởi tạo')";
            mysqli_query($conn, $sql_lichsu);
            
            $_SESSION['success'] = 'Thêm hạng mục thành công';
            // SỬA ĐƯỜNG DẪN - chuyển về index.php
            echo '<script>window.location.href="index.php";</script>';
            exit();
        } else {
            $error = 'Lỗi: ' . mysqli_error($conn);
        }
    }
}

// Lấy thông tin công trình nếu có ID
$ten_cong_trinh = '';
if($congtrinh_id > 0) {
    $sql = "SELECT ten_cong_trinh FROM congtrinh WHERE id = $congtrinh_id AND user_id = '{$_SESSION['id']}'";
    $result = mysqli_query($conn, $sql);
    if(mysqli_num_rows($result) > 0) {
        $ct = mysqli_fetch_assoc($result);
        $ten_cong_trinh = $ct['ten_cong_trinh'];
    } else {
        $congtrinh_id = 0;
    }
}

// Tạo mã hạng mục tự động
$ma_hm = 'HM-' . date('Ymd') . '-' . rand(100, 999);
?>

<div class="content-wrapper">
    <div class="page-header">
        <h4 class="mb-0">
            <i class="fas fa-plus-circle me-2 text-warning"></i>
            Thêm hạng mục mới
        </h4>
    </div>

    <?php if($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?php if(isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i><?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="POST" id="addForm">
                <!-- Thông tin cơ bản -->
                <h5 class="mb-3">Thông tin hạng mục</h5>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Công trình <span class="text-danger">*</span></label>
                        <?php if($congtrinh_id > 0): ?>
                            <input type="hidden" name="congtrinh_id" value="<?php echo $congtrinh_id; ?>">
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($ten_cong_trinh); ?>" readonly disabled>
                        <?php else: ?>
                            <select name="congtrinh_id" class="form-select" required>
                                <option value="">-- Chọn công trình --</option>
                                <?php while($ct = mysqli_fetch_assoc($congtrinh_list)): ?>
                                <option value="<?php echo $ct['id']; ?>" 
                                    <?php echo ($_POST['congtrinh_id'] ?? '') == $ct['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($ct['ten_cong_trinh']); ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Mã hạng mục</label>
                        <div class="input-group">
                            <span class="input-group-text">HM-</span>
                            <input type="text" name="ma_hang_muc" class="form-control" 
                                   value="<?php echo $_POST['ma_hang_muc'] ?? $ma_hm; ?>">
                        </div>
                        <small class="text-muted">Để trống để tự động tạo</small>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Tên hạng mục <span class="text-danger">*</span></label>
                    <input type="text" name="ten_hang_muc" class="form-control" 
                           value="<?php echo $_POST['ten_hang_muc'] ?? ''; ?>" 
                           placeholder="VD: Thi công phần móng, Lắp đặt điện nước,..." required>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Kinh phí (VNĐ)</label>
                        <input type="text" name="kinh_phi" id="kinhPhi" class="form-control text-end" 
                               value="<?php echo isset($_POST['kinh_phi']) ? $_POST['kinh_phi'] : ''; ?>" 
                               placeholder="1.000.000.000">
                        <small class="text-muted">Tự động thêm dấu chấm phân cách</small>
                    </div>
                </div>

                <!-- Thời gian -->
                <h5 class="mb-3 mt-4">Thời gian thi công</h5>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Ngày bắt đầu <span class="text-danger">*</span></label>
                        <input type="date" name="ngay_bat_dau" class="form-control" 
                               value="<?php echo $_POST['ngay_bat_dau'] ?? date('Y-m-d'); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Ngày kết thúc <span class="text-danger">*</span></label>
                        <input type="date" name="ngay_ket_thuc" class="form-control" 
                               value="<?php echo $_POST['ngay_ket_thuc'] ?? date('Y-m-d', strtotime('+3 months')); ?>" required>
                    </div>
                </div>

                <!-- Tiến độ -->
                <h5 class="mb-3 mt-4">Tiến độ</h5>
                <div class="mb-3">
                    <label class="form-label">Phần trăm hoàn thành (%)</label>
                    <div class="d-flex align-items-center gap-3">
                        <input type="range" name="phan_tram_tien_do" class="form-range" 
                               min="0" max="100" value="<?php echo $_POST['phan_tram_tien_do'] ?? 0; ?>" 
                               onchange="updateRange(this.value)">
                        <span class="badge bg-primary" id="rangeValue" style="min-width: 50px; font-size: 14px;">
                            <?php echo $_POST['phan_tram_tien_do'] ?? 0; ?>%
                        </span>
                    </div>
                    <small class="text-muted">
                        <span class="badge bg-secondary mt-2">0% = Chưa thi công</span>
                        <span class="badge bg-warning mt-2">1-99% = Đang thi công</span>
                        <span class="badge bg-success mt-2">100% = Hoàn thành</span>
                    </small>
                </div>

                <!-- Ghi chú -->
                <h5 class="mb-3 mt-4">Ghi chú</h5>
                <div class="mb-3">
                    <textarea name="ghi_chu" class="form-control" rows="4" 
                              placeholder="Nhập ghi chú về tình trạng thi công, khó khăn, vướng mắc..."><?php echo $_POST['ghi_chu'] ?? ''; ?></textarea>
                </div>

                <!-- Buttons -->
                <div class="text-center mt-4">
                    <button type="submit" name="submit" class="btn btn-primary px-5">
                        <i class="fas fa-save me-2"></i>Lưu hạng mục
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
// Hàm format số thành dạng có dấu chấm (VD: 12000000 -> 12.000.000)
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
    kinhPhiInput.addEventListener('input', function(e) {
        // Chỉ cho phép nhập số
        let value = this.value.replace(/[^0-9]/g, '');
        if (value) {
            // Format và hiển thị (thêm dấu chấm)
            this.value = formatMoney(value);
        } else {
            this.value = '';
        }
    });
}

// Xử lý khi submit form
document.getElementById('addForm')?.addEventListener('submit', function(e) {
    // Xử lý kinh phí - loại bỏ dấu chấm trước khi gửi
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

function updateRange(value) {
    document.getElementById('rangeValue').textContent = value + '%';
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
    const rangeValue = document.querySelector('input[name="phan_tram_tien_do"]')?.value;
    if (rangeValue !== undefined) updateRange(rangeValue);
    
    // Format lại kinh phí nếu có giá trị
    if (kinhPhiInput && kinhPhiInput.value) {
        let numValue = kinhPhiInput.value.replace(/[^0-9]/g, '');
        if (numValue) kinhPhiInput.value = formatMoney(numValue);
    }
}
</script>

<style>
.form-label {
    font-weight: 600;
    color: #334155;
}
.input-group-text {
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    color: #475569;
}
.money-format, .text-end {
    text-align: right;
}
</style>

<?php require_once '../includes/footer.php'; ?>