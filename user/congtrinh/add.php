<?php
// user/congtrinh/add.php
$page_title = 'Thêm công trình';
require_once '../includes/header.php';

$user_id = $_SESSION['id'];
$success = $error = "";

// Lấy danh sách loại công trình
$sql_loai = "SELECT * FROM loaicongtrinh ORDER BY ten_loai";
$result_loai = mysqli_query($conn, $sql_loai);

// Xử lý thêm công trình
if (isset($_POST['them'])) {
    $ten_cong_trinh = mysqli_real_escape_string($conn, $_POST['ten_cong_trinh']);
    $dia_diem = mysqli_real_escape_string($conn, $_POST['dia_diem']);
    $ngay_bat_dau = $_POST['ngay_bat_dau'];
    $ngay_ket_thuc = $_POST['ngay_ket_thuc'];
    $loaiCT_id = $_POST['loaiCT_id'];
    $mo_ta = mysqli_real_escape_string($conn, $_POST['mo_ta']);
    $trangthaiCT_id = 1; // Mặc định: Chưa thi công
    
    // Validate
    if (empty($ten_cong_trinh) || empty($dia_diem) || empty($ngay_bat_dau) || empty($ngay_ket_thuc)) {
        $error = "Vui lòng nhập đầy đủ thông tin!";
    } elseif (strtotime($ngay_ket_thuc) < strtotime($ngay_bat_dau)) {
        $error = "Ngày kết thúc phải sau ngày bắt đầu!";
    } else {
        $sql = "INSERT INTO congtrinh (ten_cong_trinh, dia_diem, ngay_bat_dau, ngay_ket_thuc, 
                loaiCT_id, trangthaiCT_id, user_id, mo_ta) 
                VALUES ('$ten_cong_trinh', '$dia_diem', '$ngay_bat_dau', '$ngay_ket_thuc', 
                '$loaiCT_id', '$trangthaiCT_id', '$user_id', '$mo_ta')";
        
        if (mysqli_query($conn, $sql)) {
            $congtrinh_id = mysqli_insert_id($conn);
            $success = "Thêm công trình thành công!";
            echo "<script>
                setTimeout(function() {
                    window.location.href = 'detail.php?id=$congtrinh_id';
                }, 1500);
            </script>";
        } else {
            $error = "Lỗi: " . mysqli_error($conn);
        }
    }
}
?>

<div class="container-fluid">
    <!-- Page Title -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">Thêm công trình mới</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="index.php">Công trình</a></li>
                <li class="breadcrumb-item active">Thêm mới</li>
            </ol>
        </nav>
    </div>

    <!-- Form thêm công trình -->
    <div class="card">
        <div class="card-header">
            <i class="fas fa-plus-circle me-1"></i>
            Thông tin công trình
        </div>
        <div class="card-body">
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i> <?php echo $success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Tên công trình <span class="text-danger">*</span></label>
                        <input type="text" name="ten_cong_trinh" class="form-control" 
                               placeholder="Nhập tên công trình" required>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Địa điểm xây dựng <span class="text-danger">*</span></label>
                        <input type="text" name="dia_diem" class="form-control" 
                               placeholder="Nhập địa điểm" required>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Ngày bắt đầu <span class="text-danger">*</span></label>
                        <input type="date" name="ngay_bat_dau" class="form-control" 
                               value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Ngày kết thúc <span class="text-danger">*</span></label>
                        <input type="date" name="ngay_ket_thuc" class="form-control" 
                               value="<?php echo date('Y-m-d', strtotime('+30 days')); ?>" required>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Loại công trình</label>
                        <select name="loaiCT_id" class="form-select">
                            <option value="">-- Chọn loại --</option>
                            <?php while ($loai = mysqli_fetch_assoc($result_loai)): ?>
                            <option value="<?php echo $loai['id']; ?>">
                                <?php echo $loai['ten_loai']; ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="col-12 mb-3">
                        <label class="form-label">Mô tả</label>
                        <textarea name="mo_ta" class="form-control" rows="4" 
                                  placeholder="Nhập mô tả chi tiết về công trình..."></textarea>
                    </div>
                    
                    <div class="col-12">
                        <hr>
                        <button type="submit" name="them" class="btn btn-primary">
                            <i class="fas fa-save"></i> Lưu công trình
                        </button>
                        <a href="index.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Quay lại
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
require_once '../includes/footer.php';
?>