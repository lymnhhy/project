<?php
include dirname(__DIR__) . "/includes/header.php";
include $_SERVER['DOCUMENT_ROOT'] . "/project/admin/includes/functions.php";

// Xử lý cập nhật cấu hình
if(isset($_POST['update_config'])) {
    $ten_website = mysqli_real_escape_string($conn, $_POST['ten_website']);
    $so_dien_thoai = mysqli_real_escape_string($conn, $_POST['so_dien_thoai']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $dia_chi = mysqli_real_escape_string($conn, $_POST['dia_chi']);
    
    // Xử lý upload logo
    $logo = '';
    if(isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
        $target_dir = $_SERVER['DOCUMENT_ROOT'] . "/project/uploads/logo/";
        if(!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $file_name = time() . '_' . basename($_FILES['logo']['name']);
        $target_file = $target_dir . $file_name;
        
        if(move_uploaded_file($_FILES['logo']['tmp_name'], $target_file)) {
            $logo = "/project/uploads/logo/" . $file_name;
        }
    }
    
    // Kiểm tra xem đã có cấu hình chưa
    $check = mysqli_query($conn, "SELECT COUNT(*) as total FROM cauhinhweb");
    $row = mysqli_fetch_assoc($check);
    
    if($row['total'] > 0) {
        // Update
        if($logo) {
            $sql = "UPDATE cauhinhweb SET 
                    ten_website = '$ten_website',
                    logo = '$logo',
                    so_dien_thoai = '$so_dien_thoai',
                    email = '$email',
                    dia_chi = '$dia_chi' 
                    WHERE id = 1";
        } else {
            $sql = "UPDATE cauhinhweb SET 
                    ten_website = '$ten_website',
                    so_dien_thoai = '$so_dien_thoai',
                    email = '$email',
                    dia_chi = '$dia_chi' 
                    WHERE id = 1";
        }
    } else {
        // Insert
        $sql = "INSERT INTO cauhinhweb (ten_website, logo, so_dien_thoai, email, dia_chi) 
                VALUES ('$ten_website', '$logo', '$so_dien_thoai', '$email', '$dia_chi')";
    }
    
    if(mysqli_query($conn, $sql)) {
        $_SESSION['success'] = "Cập nhật cấu hình website thành công!";
        logActivity($conn, $_SESSION['id'], "update_website", "Cập nhật cấu hình");
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
    <div class="page-header">
        <h4 class="mb-0">
            <i class="fas fa-globe me-2 text-primary"></i>
            Cấu hình website
        </h4>
    </div>

    <?php if(isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle me-2"></i><?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?php if(isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="fas fa-exclamation-circle me-2"></i><?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Tên website</label>
                        <input type="text" name="ten_website" class="form-control" 
                               value="<?php echo $row['ten_website'] ?? ''; ?>" required>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Logo</label>
                        <input type="file" name="logo" class="form-control" accept="image/*">
                        <?php if(!empty($row['logo'])): ?>
                        <div class="mt-2">
                            <img src="<?php echo $row['logo']; ?>" alt="Logo" style="max-height: 50px;">
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Số điện thoại</label>
                        <input type="text" name="so_dien_thoai" class="form-control" 
                               value="<?php echo $row['so_dien_thoai'] ?? ''; ?>">
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" 
                               value="<?php echo $row['email'] ?? ''; ?>">
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Địa chỉ</label>
                        <input type="text" name="dia_chi" class="form-control" 
                               value="<?php echo $row['dia_chi'] ?? ''; ?>">
                    </div>
                </div>
                
                <button type="submit" name="update_config" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Lưu cấu hình
                </button>
            </form>
        </div>
    </div>
</div>

<?php include dirname(__DIR__) . "/includes/footer.php"; ?>