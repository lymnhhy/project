<?php
// profile/edit.php
require_once '../includes/header.php';

$user_id = $_SESSION['id'];
$error = '';
$success = '';

// Lấy thông tin user hiện tại
$sql = "SELECT * FROM users WHERE id = '$user_id'";
$result = mysqli_query($conn, $sql);
$user = mysqli_fetch_assoc($result);

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $hoten = mysqli_real_escape_string($conn, $_POST['hoten']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $sdt = mysqli_real_escape_string($conn, $_POST['sdt']);
    $dia_chi = mysqli_real_escape_string($conn, $_POST['dia_chi']);
    $gioi_tinh = mysqli_real_escape_string($conn, $_POST['gioi_tinh']);
    $ngay_sinh = $_POST['ngay_sinh'];
    
    // Validate
    if(empty($hoten)) {
        $error = 'Vui lòng nhập họ tên';
    } elseif(!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email không hợp lệ';
    } else {
        // Xử lý upload avatar
        $anh_dai_dien = $user['anh_dai_dien'];
        
        if(isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['avatar']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if(in_array($ext, $allowed)) {
                if($_FILES['avatar']['size'] <= 5 * 1024 * 1024) { // 5MB
                    $upload_dir = '../../uploads/avatar/';
                    
                    // Tạo thư mục nếu chưa có
                    if(!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    // Xóa avatar cũ nếu có
                    if(!empty($user['anh_dai_dien']) && file_exists($upload_dir . $user['anh_dai_dien'])) {
                        unlink($upload_dir . $user['anh_dai_dien']);
                    }
                    
                    $new_filename = 'user_' . $user_id . '_' . time() . '.' . $ext;
                    $upload_path = $upload_dir . $new_filename;
                    
                    if(move_uploaded_file($_FILES['avatar']['tmp_name'], $upload_path)) {
                        $anh_dai_dien = $new_filename;
                    }
                } else {
                    $error = 'Kích thước file không được vượt quá 5MB';
                }
            } else {
                $error = 'Chỉ chấp nhận file JPG, JPEG, PNG, GIF';
            }
        }
        
        if(empty($error)) {
            $sql_update = "UPDATE users SET 
                          hoten = '$hoten',
                          email = '$email',
                          sdt = '$sdt',
                          dia_chi = '$dia_chi',
                          gioi_tinh = '$gioi_tinh',
                          ngay_sinh = " . (!empty($ngay_sinh) ? "'$ngay_sinh'" : "NULL") . ",
                          anh_dai_dien = '$anh_dai_dien'
                          WHERE id = '$user_id'";
            
            if(mysqli_query($conn, $sql_update)) {
                logActivity($conn, $user_id, 'Cập nhật thông tin', 'Cập nhật thông tin cá nhân');
                $_SESSION['success'] = 'Cập nhật thông tin thành công!';
                header('Location: index.php');
                exit();
            } else {
                $error = 'Lỗi: ' . mysqli_error($conn);
            }
        }
    }
}
?>

<div class="content-wrapper">
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h4 class="mb-1">
                    <i class="fas fa-edit me-2 text-warning"></i>
                    Cập nhật thông tin
                </h4>
                <p class="text-muted mb-0">Chỉnh sửa thông tin cá nhân của bạn</p>
            </div>
            <div class="mt-2 mt-sm-0">
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Quay lại
                </a>
            </div>
        </div>
    </div>

    <?php if($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-user-edit me-2 text-warning"></i>
                    Form cập nhật thông tin
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Username</label>
                                <input type="text" class="form-control" 
                                       value="<?php echo htmlspecialchars($user['username']); ?>" 
                                       readonly disabled>
                                <small class="text-muted">Không thể thay đổi username</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Họ và tên <span class="text-danger">*</span></label>
                                <input type="text" name="hoten" class="form-control" 
                                       value="<?php echo htmlspecialchars($user['hoten'] ?? ''); ?>" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    <input type="email" name="email" class="form-control" 
                                           value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>"
                                           placeholder="example@email.com">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Số điện thoại</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                    <input type="tel" name="sdt" class="form-control" 
                                           value="<?php echo htmlspecialchars($user['sdt'] ?? ''); ?>"
                                           placeholder="0123456789">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Địa chỉ</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span>
                                <input type="text" name="dia_chi" class="form-control" 
                                       value="<?php echo htmlspecialchars($user['dia_chi'] ?? ''); ?>"
                                       placeholder="Số nhà, đường, phường, quận, thành phố">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Giới tính</label>
                                <select name="gioi_tinh" class="form-select">
                                    <option value="">-- Chọn giới tính --</option>
                                    <option value="nam" <?php echo ($user['gioi_tinh'] ?? '') == 'nam' ? 'selected' : ''; ?>>Nam</option>
                                    <option value="nu" <?php echo ($user['gioi_tinh'] ?? '') == 'nu' ? 'selected' : ''; ?>>Nữ</option>
                                    <option value="khac" <?php echo ($user['gioi_tinh'] ?? '') == 'khac' ? 'selected' : ''; ?>>Khác</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Ngày sinh</label>
                                <input type="date" name="ngay_sinh" class="form-control" 
                                       value="<?php echo $user['ngay_sinh'] ?? ''; ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Ảnh đại diện</label>
                            <input type="file" name="avatar" class="form-control" accept="image/*" onchange="previewAvatar(this)">
                            <small class="text-muted">Chấp nhận: JPG, PNG, GIF. Tối đa 5MB</small>
                            <div id="avatarPreview" class="mt-2"></div>
                        </div>

                        <div class="text-center mt-4">
                            <button type="submit" class="btn btn-primary px-5">
                                <i class="fas fa-save me-2"></i>Lưu thay đổi
                            </button>
                            <a href="index.php" class="btn btn-secondary px-5">
                                <i class="fas fa-times me-2"></i>Hủy
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <!-- Avatar hiện tại -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-image me-2 text-warning"></i>
                    Ảnh đại diện hiện tại
                </div>
                <div class="card-body text-center">
                    <?php 
                    $avatar = !empty($user['anh_dai_dien']) ? $user['anh_dai_dien'] : 'default-avatar.png';
                    $avatar_path = BASE_URL . '/uploads/avatar/' . $avatar;
                    ?>
                    <img src="<?php echo $avatar_path; ?>" 
                         class="img-thumbnail rounded-circle mb-3" 
                         alt="Current Avatar"
                         style="width: 150px; height: 150px; object-fit: cover;"
                         onerror="this.src='<?php echo BASE_URL; ?>/assets/img/default-avatar.png'">
                    <p class="text-muted small">Ảnh đại diện hiện tại của bạn</p>
                </div>
            </div>

            <!-- Hướng dẫn -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-info-circle me-2 text-warning"></i>
                    Lưu ý
                </div>
                <div class="card-body">
                    <ul class="mb-0">
                        <li class="mb-2">Username không thể thay đổi</li>
                        <li class="mb-2">Email phải đúng định dạng</li>
                        <li class="mb-2">Số điện thoại nên nhập đúng 10 số</li>
                        <li class="mb-2">Ảnh đại diện không quá 5MB</li>
                        <li>Định dạng ảnh: JPG, PNG, GIF</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function previewAvatar(input) {
    const preview = document.getElementById('avatarPreview');
    preview.innerHTML = '';
    
    if(input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.innerHTML = `
                <div class="text-center">
                    <p class="mb-2">Ảnh xem trước:</p>
                    <img src="${e.target.result}" class="img-thumbnail rounded-circle" 
                         style="width: 100px; height: 100px; object-fit: cover;">
                </div>
            `;
        }
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php require_once '../includes/footer.php'; ?>