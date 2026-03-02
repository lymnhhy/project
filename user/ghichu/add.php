<?php
// user/ghichu/add.php
$page_title = 'Thêm ghi chú thi công';
require_once '../includes/header.php';

$user_id = $_SESSION['id'];
$hangmuc_id = isset($_GET['hangmuc_id']) ? (int)$_GET['hangmuc_id'] : 0;
$error = '';
$success = '';

// Debug: Kiểm tra kết nối database
if (!$conn) {
    die("Lỗi kết nối database");
}

// TẠO THƯ MỤC UPLOADS NẾU CHƯA CÓ
$upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/project/uploads/ghichu/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
    chmod($upload_dir, 0777);
}

// Lấy danh sách công trình của user
$sql_ct = "SELECT id, ten_cong_trinh FROM congtrinh WHERE user_id = '$user_id' ORDER BY ten_cong_trinh";
$congtrinh_list = mysqli_query($conn, $sql_ct);

if (!$congtrinh_list) {
    die("Lỗi truy vấn công trình: " . mysqli_error($conn));
}

// XỬ LÝ SUBMIT FORM
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Debug
    error_log("POST data: " . print_r($_POST, true));
    error_log("FILES data: " . print_r($_FILES, true));
    
    // Lấy dữ liệu từ form
    $hangmuc_id = isset($_POST['hangmuc_id']) ? (int)$_POST['hangmuc_id'] : 0;
    $noi_dung = isset($_POST['noi_dung']) ? trim($_POST['noi_dung']) : '';
    $ngay_ghi = isset($_POST['ngay_ghi']) ? $_POST['ngay_ghi'] : date('Y-m-d');
    
    // Validate dữ liệu
    $errors = [];
    
    if ($hangmuc_id <= 0) {
        $errors[] = 'Vui lòng chọn hạng mục';
    }
    
    if (empty($noi_dung)) {
        $errors[] = 'Vui lòng nhập nội dung ghi chú';
    }
    
    // Kiểm tra hạng mục có tồn tại và thuộc quyền user không
    if ($hangmuc_id > 0) {
        $check_sql = "SELECT hm.*, ct.user_id 
                      FROM hangmucthicong hm 
                      LEFT JOIN congtrinh ct ON hm.congtrinh_id = ct.id 
                      WHERE hm.id = $hangmuc_id";
        $check_result = mysqli_query($conn, $check_sql);
        
        if (mysqli_num_rows($check_result) == 0) {
            $errors[] = 'Hạng mục không tồn tại';
        } else {
            $hm_data = mysqli_fetch_assoc($check_result);
            if ($hm_data['user_id'] != $user_id) {
                $errors[] = 'Bạn không có quyền ghi chú cho hạng mục này';
            }
        }
    }
    
    // Xử lý upload hình ảnh
    $hinh_anh = '';
    
    if (isset($_FILES['hinh_anh']) && $_FILES['hinh_anh']['error'] == UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['hinh_anh']['tmp_name'];
        $file_name = $_FILES['hinh_anh']['name'];
        $file_size = $_FILES['hinh_anh']['size'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        // Kiểm tra định dạng
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array($file_ext, $allowed_types)) {
            $errors[] = 'Chỉ chấp nhận file JPG, PNG, GIF';
        }
        
        // Kiểm tra kích thước (5MB)
        if ($file_size > 5 * 1024 * 1024) {
            $errors[] = 'File không được vượt quá 5MB';
        }
        
        if (empty($errors)) {
            $new_file_name = time() . '_' . uniqid() . '.' . $file_ext;
            $target_path = $upload_dir . $new_file_name;
            
            if (move_uploaded_file($file_tmp, $target_path)) {
                $hinh_anh = 'uploads/ghichu/' . $new_file_name;
                error_log("Upload thành công: " . $target_path);
            } else {
                $errors[] = 'Không thể upload file. Lỗi: ' . error_get_last()['message'];
                error_log("Upload failed: " . print_r(error_get_last(), true));
            }
        }
    } elseif (isset($_FILES['hinh_anh']) && $_FILES['hinh_anh']['error'] != UPLOAD_ERR_NO_FILE) {
        $errors[] = 'Lỗi upload file. Mã lỗi: ' . $_FILES['hinh_anh']['error'];
    }
    
    // Nếu không có lỗi, insert vào database
    if (empty($errors)) {
        $noi_dung_safe = mysqli_real_escape_string($conn, $noi_dung);
        
        $sql = "INSERT INTO ghichuthicong (hangmuc_id, noi_dung, ngay_ghi, user_id, hinh_anh, created_at) 
                VALUES ($hangmuc_id, '$noi_dung_safe', '$ngay_ghi', $user_id, '$hinh_anh', NOW())";
        
        error_log("SQL: " . $sql);
        
        if (mysqli_query($conn, $sql)) {
            $ghichu_id = mysqli_insert_id($conn);
            error_log("Insert thành công, ID: " . $ghichu_id);
            
            // Ghi log
            $ip = $_SERVER['REMOTE_ADDR'];
            $log_sql = "INSERT INTO lichsuhoatdong (user_id, hanh_dong, chi_tiet, ip_address, thoi_gian) 
                        VALUES ($user_id, 'Thêm ghi chú', 'Thêm ghi chú ID: $ghichu_id', '$ip', NOW())";
            mysqli_query($conn, $log_sql);
            
            $_SESSION['success'] = 'Thêm ghi chú thành công!';
            echo '<script>window.location.href="detail.php?id=' . $ghichu_id . '";</script>';
            exit();
        } else {
            $errors[] = 'Lỗi database: ' . mysqli_error($conn);
            error_log("Lỗi SQL: " . mysqli_error($conn));
        }
    }
    
    if (!empty($errors)) {
        $error = implode('<br>', $errors);
    }
}

// Nếu có hangmuc_id từ URL, lấy thông tin
$selected_ct = 0;
$hm_info = null;

if ($hangmuc_id > 0) {
    $sql_hm = "SELECT hm.*, ct.ten_cong_trinh, ct.id as congtrinh_id 
               FROM hangmucthicong hm
               LEFT JOIN congtrinh ct ON hm.congtrinh_id = ct.id
               WHERE hm.id = $hangmuc_id AND ct.user_id = '$user_id'";
    $result_hm = mysqli_query($conn, $sql_hm);
    if (mysqli_num_rows($result_hm) > 0) {
        $hm_info = mysqli_fetch_assoc($result_hm);
        $selected_ct = $hm_info['congtrinh_id'];
    }
}
?>

<div class="content-wrapper">
    <!-- Page Header -->
    <div class="page-header d-flex justify-content-between align-items-center flex-wrap">
        <div>
            <h4 class="mb-1">
                <i class="fas fa-plus-circle me-2 text-warning"></i>
                Thêm ghi chú thi công
            </h4>
            <p class="text-muted mb-0">Ghi lại nhật ký, biên bản, sự cố công trình</p>
        </div>
        <div>
            <a href="index.php" class="btn btn-outline-secondary">
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

    <div class="card">
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
                <!-- Chọn công trình và hạng mục -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Công trình <span class="text-danger">*</span></label>
                        <select class="form-select" id="congtrinh_select" required>
                            <option value="">-- Chọn công trình --</option>
                            <?php 
                            mysqli_data_seek($congtrinh_list, 0);
                            while($ct = mysqli_fetch_assoc($congtrinh_list)): 
                            ?>
                            <option value="<?php echo $ct['id']; ?>" 
                                <?php echo ($selected_ct == $ct['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($ct['ten_cong_trinh']); ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Hạng mục <span class="text-danger">*</span></label>
                        <select name="hangmuc_id" class="form-select" id="hangmuc_select" required>
                            <option value="">-- Chọn công trình trước --</option>
                            <?php if($hm_info): ?>
                            <option value="<?php echo $hm_info['id']; ?>" selected>
                                <?php echo htmlspecialchars($hm_info['ten_hang_muc']); ?>
                            </option>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>
                
                <!-- Ngày ghi chú -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Ngày ghi chú</label>
                        <input type="date" name="ngay_ghi" class="form-control" 
                               value="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>
                
                <!-- Nội dung -->
                <div class="mb-3">
                    <label class="form-label fw-bold">Nội dung ghi chú <span class="text-danger">*</span></label>
                    <textarea name="noi_dung" class="form-control" rows="6" 
                              placeholder="Nhập nội dung ghi chú..."><?php echo htmlspecialchars($_POST['noi_dung'] ?? ''); ?></textarea>
                </div>
                
                <!-- Hình ảnh -->
                <div class="mb-3">
                    <label class="form-label fw-bold">Hình ảnh đính kèm</label>
                    <input type="file" name="hinh_anh" class="form-control" accept="image/*" 
                           onchange="previewImage(this)">
                    <small class="text-muted">Chấp nhận: JPG, PNG, GIF. Tối đa 5MB</small>
                    <div id="imagePreview" class="mt-2"></div>
                </div>
                
                <!-- Buttons -->
                <div class="text-center mt-4">
                    <button type="submit" name="submit" class="btn btn-primary px-5">
                        <i class="fas fa-save me-2"></i>Lưu ghi chú
                    </button>
                    <a href="index.php" class="btn btn-outline-secondary px-5">
                        <i class="fas fa-times me-2"></i>Hủy
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Load hạng mục theo công trình
document.getElementById('congtrinh_select').addEventListener('change', function() {
    var ct_id = this.value;
    var hm_select = document.getElementById('hangmuc_select');
    
    hm_select.innerHTML = '<option value="">Đang tải...</option>';
    hm_select.disabled = true;
    
    if (!ct_id) {
        hm_select.innerHTML = '<option value="">-- Chọn công trình trước --</option>';
        hm_select.disabled = false;
        return;
    }
    
    fetch('<?php echo BASE_URL; ?>/user/api/get_hangmuc.php?congtrinh_id=' + ct_id)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            hm_select.innerHTML = '<option value="">-- Chọn hạng mục --</option>';
            data.forEach(function(item) {
                hm_select.innerHTML += '<option value="' + item.id + '">' + item.ten_hang_muc + '</option>';
            });
            hm_select.disabled = false;
        })
        .catch(error => {
            console.error('Error:', error);
            hm_select.innerHTML = '<option value="">-- Lỗi tải dữ liệu --</option>';
            hm_select.disabled = false;
        });
});

// Preview image
function previewImage(input) {
    const preview = document.getElementById('imagePreview');
    preview.innerHTML = '';
    
    if (input.files && input.files[0]) {
        if (input.files[0].size > 5 * 1024 * 1024) {
            preview.innerHTML = '<div class="alert alert-warning">File quá lớn! Tối đa 5MB</div>';
            input.value = '';
            return;
        }
        
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.innerHTML = '<img src="' + e.target.result + '" class="img-thumbnail" style="max-height: 200px;">';
        }
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php require_once '../includes/footer.php'; ?>