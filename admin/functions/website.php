<?php
// admin/functions/website.php
$page_title = 'Cấu hình website';
include dirname(__DIR__) . "/includes/header.php";

// Xử lý cập nhật cấu hình
if(isset($_POST['update_config'])) {
    // Lấy dữ liệu từ form
    $ten_website = mysqli_real_escape_string($conn, $_POST['ten_website']);
    $slogan = mysqli_real_escape_string($conn, $_POST['slogan']);
    $mo_ta = mysqli_real_escape_string($conn, $_POST['mo_ta']);
    $tu_khoa = mysqli_real_escape_string($conn, $_POST['tu_khoa']);
    $so_dien_thoai = mysqli_real_escape_string($conn, $_POST['so_dien_thoai']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $dia_chi = mysqli_real_escape_string($conn, $_POST['dia_chi']);
    $website = mysqli_real_escape_string($conn, $_POST['website']);
    $facebook = mysqli_real_escape_string($conn, $_POST['facebook']);
    $zalo = mysqli_real_escape_string($conn, $_POST['zalo']);
    
    // Xử lý upload logo
    $logo = '';
    if(isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
        $target_dir = $_SERVER['DOCUMENT_ROOT'] . "/project/uploads/logo/";
        if(!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $file_name = time() . '_' . basename($_FILES['logo']['name']);
        $target_file = $target_dir . $file_name;
        $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        
        // Kiểm tra file ảnh
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'svg'];
        if(in_array($file_type, $allowed_types)) {
            if(move_uploaded_file($_FILES['logo']['tmp_name'], $target_file)) {
                $logo = "/project/uploads/logo/" . $file_name;
            }
        }
    }
    
    // Xử lý upload favicon
    $favicon = '';
    if(isset($_FILES['favicon']) && $_FILES['favicon']['error'] == 0) {
        $target_dir = $_SERVER['DOCUMENT_ROOT'] . "/project/uploads/favicon/";
        if(!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $file_name = time() . '_' . basename($_FILES['favicon']['name']);
        $target_file = $target_dir . $file_name;
        $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        
        // Favicon thường là .ico, .png
        $allowed_types = ['ico', 'png', 'jpg', 'jpeg'];
        if(in_array($file_type, $allowed_types)) {
            if(move_uploaded_file($_FILES['favicon']['tmp_name'], $target_file)) {
                $favicon = "/project/uploads/favicon/" . $file_name;
            }
        }
    }
    
    // Kiểm tra xem đã có cấu hình chưa
    $check = mysqli_query($conn, "SELECT COUNT(*) as total FROM cauhinhweb");
    $row = mysqli_fetch_assoc($check);
    
    if($row['total'] > 0) {
        // UPDATE - Xây dựng câu SQL động
        $sql = "UPDATE cauhinhweb SET 
                ten_website = '$ten_website',
                slogan = '$slogan',
                mo_ta = '$mo_ta',
                tu_khoa = '$tu_khoa',
                so_dien_thoai = '$so_dien_thoai',
                email = '$email',
                dia_chi = '$dia_chi',
                website = '$website',
                facebook = '$facebook',
                zalo = '$zalo'";
        
        // Thêm logo nếu có upload
        if($logo) {
            $sql .= ", logo = '$logo'";
        }
        
        // Thêm favicon nếu có upload
        if($favicon) {
            $sql .= ", favicon = '$favicon'";
        }
        
        $sql .= " WHERE id = 1";
    } else {
        // INSERT
        $sql = "INSERT INTO cauhinhweb (
                ten_website, slogan, mo_ta, tu_khoa, 
                so_dien_thoai, email, dia_chi, website,
                facebook, zalo, logo, favicon
                ) VALUES (
                '$ten_website', '$slogan', '$mo_ta', '$tu_khoa',
                '$so_dien_thoai', '$email', '$dia_chi', '$website',
                '$facebook', '$zalo', '$logo', '$favicon'
                )";
    }
    
    if(mysqli_query($conn, $sql)) {
        $_SESSION['success'] = "Cập nhật cấu hình website thành công!";
        
        // Ghi log hoạt động
        $ip = $_SERVER['REMOTE_ADDR'];
        $thoi_gian = date('Y-m-d H:i:s');
        $log_sql = "INSERT INTO lichsuhoatdong (user_id, hanh_dong, chi_tiet, ip_address, thoi_gian) 
                    VALUES ('{$_SESSION['id']}', 'Cập nhật cấu hình', 'Cập nhật thông tin website', '$ip', '$thoi_gian')";
        mysqli_query($conn, $log_sql);
    } else {
        $_SESSION['error'] = "Lỗi: " . mysqli_error($conn);
    }
    header("Location: website.php");
    exit();
}

// Lấy thông tin cấu hình
$config = mysqli_query($conn, "SELECT * FROM cauhinhweb WHERE id = 1");
$row = mysqli_fetch_assoc($config);
?>

<div class="content-wrapper">
    <!-- Page Header -->
    <div class="page-header d-flex justify-content-between align-items-center flex-wrap">
        <div>
            <h4 class="mb-1">
                <i class="fas fa-globe me-2 text-primary"></i>
                Cấu hình website
            </h4>
            <p class="text-muted mb-0">Quản lý thông tin chung của website</p>
        </div>
        <div class="d-flex gap-2 mt-2 mt-sm-0">
            <a href="../dashboard.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Quay lại Dashboard
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

    <?php if(isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i><?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header bg-white">
            <ul class="nav nav-tabs card-header-tabs" id="configTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="basic-tab" data-bs-toggle="tab" data-bs-target="#basic" type="button" role="tab">
                        <i class="fas fa-info-circle me-2"></i>Thông tin cơ bản
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="contact-tab" data-bs-toggle="tab" data-bs-target="#contact" type="button" role="tab">
                        <i class="fas fa-address-card me-2"></i>Liên hệ
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="social-tab" data-bs-toggle="tab" data-bs-target="#social" type="button" role="tab">
                        <i class="fas fa-share-alt me-2"></i>Mạng xã hội
                    </button>
                </li>
            </ul>
        </div>
        
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
                <div class="tab-content" id="configTabsContent">
                    <!-- TAB 1: THÔNG TIN CƠ BẢN -->
                    <div class="tab-pane fade show active" id="basic" role="tabpanel">
                        <h5 class="mb-3 text-primary">Thông tin cơ bản</h5>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">
                                    Tên website <span class="text-danger">*</span>
                                </label>
                                <input type="text" name="ten_website" class="form-control" 
                                       value="<?php echo htmlspecialchars($row['ten_website'] ?? ''); ?>" 
                                       placeholder="VD: ProTrack" required>
                                <small class="text-muted">Tên hiển thị của website</small>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Slogan</label>
                                <input type="text" name="slogan" class="form-control" 
                                       value="<?php echo htmlspecialchars($row['slogan'] ?? ''); ?>" 
                                       placeholder="VD: Giải pháp theo dõi dự án chuyên nghiệp">
                                <small class="text-muted">Câu khẩu hiệu ngắn gọn</small>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Mô tả website</label>
                                <textarea name="mo_ta" class="form-control" rows="3" 
                                          placeholder="Mô tả ngắn về website (phục vụ SEO)"><?php echo htmlspecialchars($row['mo_ta'] ?? ''); ?></textarea>
                                <small class="text-muted">Hiển thị trong kết quả tìm kiếm</small>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Từ khóa (SEO)</label>
                                <textarea name="tu_khoa" class="form-control" rows="3" 
                                          placeholder="từ khóa 1, từ khóa 2, từ khóa 3"><?php echo htmlspecialchars($row['tu_khoa'] ?? ''); ?></textarea>
                                <small class="text-muted">Ngăn cách bằng dấu phẩy</small>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Logo website</label>
                                <input type="file" name="logo" class="form-control" accept="image/*">
                                <?php if(!empty($row['logo'])): ?>
                                <div class="mt-2 d-flex align-items-center">
                                    <img src="<?php echo $row['logo']; ?>" alt="Logo" style="max-height: 60px;" class="border rounded p-1">
                                    <span class="ms-2 text-muted small">Logo hiện tại</span>
                                </div>
                                <?php endif; ?>
                                <small class="text-muted">Định dạng: JPG, PNG, GIF, SVG. Tối đa 2MB</small>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Favicon (icon tab)</label>
                                <input type="file" name="favicon" class="form-control" accept=".ico,.png,.jpg,.jpeg">
                                <?php if(!empty($row['favicon'])): ?>
                                <div class="mt-2 d-flex align-items-center">
                                    <img src="<?php echo $row['favicon']; ?>" alt="Favicon" style="max-height: 32px;" class="border rounded p-1">
                                    <span class="ms-2 text-muted small">Favicon hiện tại</span>
                                </div>
                                <?php endif; ?>
                                <small class="text-muted">Định dạng: ICO, PNG. Kích thước 32x32px</small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- TAB 2: THÔNG TIN LIÊN HỆ -->
                    <div class="tab-pane fade" id="contact" role="tabpanel">
                        <h5 class="mb-3 text-primary">Thông tin liên hệ</h5>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Số điện thoại</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                    <input type="text" name="so_dien_thoai" class="form-control" 
                                           value="<?php echo htmlspecialchars($row['so_dien_thoai'] ?? ''); ?>" 
                                           placeholder="VD: 1900 1234">
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Email</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    <input type="email" name="email" class="form-control" 
                                           value="<?php echo htmlspecialchars($row['email'] ?? ''); ?>" 
                                           placeholder="VD: info@protrack.com">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Địa chỉ</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span>
                                    <input type="text" name="dia_chi" class="form-control" 
                                           value="<?php echo htmlspecialchars($row['dia_chi'] ?? ''); ?>" 
                                           placeholder="VD: 123 Đường ABC, Quận XYZ, TP.HCM">
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Website</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-globe"></i></span>
                                    <input type="url" name="website" class="form-control" 
                                           value="<?php echo htmlspecialchars($row['website'] ?? ''); ?>" 
                                           placeholder="VD: https://protrack.com">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- TAB 3: MẠNG XÃ HỘI -->
                    <div class="tab-pane fade" id="social" role="tabpanel">
                        <h5 class="mb-3 text-primary">Mạng xã hội</h5>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Facebook</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-primary text-white"><i class="fab fa-facebook-f"></i></span>
                                    <input type="url" name="facebook" class="form-control" 
                                           value="<?php echo htmlspecialchars($row['facebook'] ?? ''); ?>" 
                                           placeholder="https://facebook.com/protrack">
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Zalo</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-info text-white"><i class="fas fa-comment"></i></span>
                                    <input type="text" name="zalo" class="form-control" 
                                           value="<?php echo htmlspecialchars($row['zalo'] ?? ''); ?>" 
                                           placeholder="Số điện thoại Zalo">
                                </div>
                                <small class="text-muted">Nhập số điện thoại Zalo</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="text-center mt-4 pt-3 border-top">
                    <button type="submit" name="update_config" class="btn btn-primary px-5">
                        <i class="fas fa-save me-2"></i>Lưu cấu hình
                    </button>
                    <button type="reset" class="btn btn-outline-secondary px-5 ms-2">
                        <i class="fas fa-undo me-2"></i>Nhập lại
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Xem trước cấu hình -->
    <?php if(!empty($row)): ?>
    <div class="card mt-4">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="fas fa-eye me-2 text-primary"></i>Xem trước thông tin website</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-bordered">
                        <tr>
                            <th width="150">Tên website:</th>
                            <td><?php echo htmlspecialchars($row['ten_website'] ?? ''); ?></td>
                        </tr>
                        <tr>
                            <th>Slogan:</th>
                            <td><?php echo htmlspecialchars($row['slogan'] ?? ''); ?></td>
                        </tr>
                        <tr>
                            <th>Số điện thoại:</th>
                            <td><?php echo htmlspecialchars($row['so_dien_thoai'] ?? ''); ?></td>
                        </tr>
                        <tr>
                            <th>Email:</th>
                            <td><?php echo htmlspecialchars($row['email'] ?? ''); ?></td>
                        </tr>
                        <tr>
                            <th>Địa chỉ:</th>
                            <td><?php echo htmlspecialchars($row['dia_chi'] ?? ''); ?></td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-bordered">
                        <tr>
                            <th width="150">Website:</th>
                            <td>
                                <?php if(!empty($row['website'])): ?>
                                <a href="<?php echo $row['website']; ?>" target="_blank">
                                    <?php echo htmlspecialchars($row['website']); ?>
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Facebook:</th>
                            <td>
                                <?php if(!empty($row['facebook'])): ?>
                                <a href="<?php echo $row['facebook']; ?>" target="_blank">
                                    <?php echo htmlspecialchars($row['facebook']); ?>
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Zalo:</th>
                            <td><?php echo htmlspecialchars($row['zalo'] ?? ''); ?></td>
                        </tr>
                        <tr>
                            <th>Logo:</th>
                            <td>
                                <?php if(!empty($row['logo'])): ?>
                                <img src="<?php echo $row['logo']; ?>" alt="Logo" style="max-height: 50px;">
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Favicon:</th>
                            <td>
                                <?php if(!empty($row['favicon'])): ?>
                                <img src="<?php echo $row['favicon']; ?>" alt="Favicon" style="max-height: 20px;">
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
.nav-tabs .nav-link {
    color: #495057;
    font-weight: 500;
    padding: 0.75rem 1.25rem;
    border: none;
    border-bottom: 2px solid transparent;
}

.nav-tabs .nav-link:hover {
    border-color: transparent;
    color: #0d6efd;
}

.nav-tabs .nav-link.active {
    color: #0d6efd;
    background: transparent;
    border-bottom: 2px solid #0d6efd;
}

.input-group-text {
    min-width: 40px;
    justify-content: center;
}

.form-label {
    font-weight: 600;
    color: #34495e;
    margin-bottom: 0.5rem;
}

.card-header {
    background: transparent;
    border-bottom: 1px solid #dee2e6;
    padding: 1rem 1.25rem 0;
}

.table th {
    background: #f8f9fa;
    font-weight: 600;
}
</style>

<script>
// Preview image before upload
document.querySelector('input[name="logo"]')?.addEventListener('change', function(e) {
    const file = e.target.files[0];
    if(file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            // Tạo preview
            const preview = document.createElement('div');
            preview.className = 'mt-2';
            preview.innerHTML = `
                <div class="d-flex align-items-center">
                    <img src="${e.target.result}" style="max-height: 60px;" class="border rounded p-1">
                    <span class="ms-2 text-success small">Logo mới</span>
                </div>
            `;
            
            // Xóa preview cũ nếu có
            const oldPreview = e.target.parentNode.querySelector('.mt-2:not(.text-muted)');
            if(oldPreview) oldPreview.remove();
            
            e.target.parentNode.appendChild(preview);
        }
        reader.readAsDataURL(file);
    }
});

document.querySelector('input[name="favicon"]')?.addEventListener('change', function(e) {
    const file = e.target.files[0];
    if(file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.createElement('div');
            preview.className = 'mt-2';
            preview.innerHTML = `
                <div class="d-flex align-items-center">
                    <img src="${e.target.result}" style="max-height: 32px;" class="border rounded p-1">
                    <span class="ms-2 text-success small">Favicon mới</span>
                </div>
            `;
            
            const oldPreview = e.target.parentNode.querySelector('.mt-2:not(.text-muted)');
            if(oldPreview) oldPreview.remove();
            
            e.target.parentNode.appendChild(preview);
        }
        reader.readAsDataURL(file);
    }
});
</script>

<?php include dirname(__DIR__) . "/includes/footer.php"; ?>