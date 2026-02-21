<?php
include dirname(__DIR__) . "/includes/header.php";
include $_SERVER['DOCUMENT_ROOT'] . "/project/admin/includes/functions.php";

// Tạo bảng nếu chưa có
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS noidung (
    id INT PRIMARY KEY AUTO_INCREMENT,
    loai VARCHAR(50) UNIQUE,
    tieu_de VARCHAR(255),
    noi_dung TEXT,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

// Xử lý cập nhật nội dung
if(isset($_POST['update_content'])) {
    $loai = mysqli_real_escape_string($conn, $_POST['loai']);
    $tieu_de = mysqli_real_escape_string($conn, $_POST['tieu_de']);
    $noi_dung = mysqli_real_escape_string($conn, $_POST['noi_dung']);
    
    // Kiểm tra đã có chưa
    $check = mysqli_query($conn, "SELECT COUNT(*) as total FROM noidung WHERE loai = '$loai'");
    $row = mysqli_fetch_assoc($check);
    
    if($row['total'] > 0) {
        $sql = "UPDATE noidung SET tieu_de = '$tieu_de', noi_dung = '$noi_dung' WHERE loai = '$loai'";
    } else {
        $sql = "INSERT INTO noidung (loai, tieu_de, noi_dung) VALUES ('$loai', '$tieu_de', '$noi_dung')";
    }
    
    if(mysqli_query($conn, $sql)) {
        $_SESSION['success'] = "Cập nhật nội dung thành công!";
        logActivity($conn, $_SESSION['id'], "update_content", "Loại: $loai");
    } else {
        $_SESSION['error'] = "Lỗi: " . mysqli_error($conn);
    }
    header("Location: content.php?type=" . $loai);
    exit();
}

$type = $_GET['type'] ?? 'gioi_thieu';

// Lấy nội dung
$content = mysqli_query($conn, "SELECT * FROM noidung WHERE loai = '$type'");
$row = mysqli_fetch_assoc($content);
?>

<div class="content-wrapper">
    <div class="page-header">
        <h4 class="mb-0">
            <i class="fas fa-file-alt me-2 text-primary"></i>
            Quản lý nội dung
        </h4>
    </div>

    <?php if(isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle me-2"></i><?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <ul class="nav nav-tabs card-header-tabs">
                <li class="nav-item">
                    <a class="nav-link <?php echo $type == 'gioi_thieu' ? 'active' : ''; ?>" 
                       href="?type=gioi_thieu">Giới thiệu</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $type == 'huong_dan' ? 'active' : ''; ?>" 
                       href="?type=huong_dan">Hướng dẫn sử dụng</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $type == 'lien_he' ? 'active' : ''; ?>" 
                       href="?type=lien_he">Liên hệ</a>
                </li>
            </ul>
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="loai" value="<?php echo $type; ?>">
                
                <div class="mb-3">
                    <label class="form-label">Tiêu đề</label>
                    <input type="text" name="tieu_de" class="form-control" 
                           value="<?php echo $row['tieu_de'] ?? ''; ?>" required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Nội dung</label>
                    <textarea name="noi_dung" class="form-control" rows="15" required><?php echo $row['noi_dung'] ?? ''; ?></textarea>
                </div>
                
                <button type="submit" name="update_content" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Lưu nội dung
                </button>
            </form>
        </div>
    </div>
</div>

<?php include dirname(__DIR__) . "/includes/footer.php"; ?>