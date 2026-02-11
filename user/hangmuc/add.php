<?php
// user/hangmuc/add.php
$page_title = 'Thêm hạng mục';
require_once '../includes/header.php';

$user_id = $_SESSION['id'];
$congtrinh_id = isset($_GET['congtrinh_id']) ? (int)$_GET['congtrinh_id'] : 0;

// Kiểm tra quyền
if ($congtrinh_id > 0) {
    $check = mysqli_query($conn, "SELECT id FROM congtrinh WHERE id = '$congtrinh_id' AND user_id = '$user_id'");
    if (mysqli_num_rows($check) == 0) {
        echo "<script>alert('Không có quyền thực hiện!'); window.location.href='index.php';</script>";
        exit();
    }
}

// Lấy danh sách công trình
$sql_congtrinh = "SELECT id, ten_cong_trinh FROM congtrinh WHERE user_id = '$user_id' ORDER BY ten_cong_trinh";
$result_congtrinh = mysqli_query($conn, $sql_congtrinh);

if (isset($_POST['them'])) {
    $congtrinh_id = $_POST['congtrinh_id'];
    $ten_hang_muc = mysqli_real_escape_string($conn, $_POST['ten_hang_muc']);
    $ngay_bat_dau = $_POST['ngay_bat_dau'];
    $ngay_ket_thuc = $_POST['ngay_ket_thuc'];
    $ghi_chu = mysqli_real_escape_string($conn, $_POST['ghi_chu']);
    $phan_tram_tien_do = 0;
    $trang_thai = 'Chưa thi công';
    
    if (empty($ten_hang_muc)) {
        $error = "Vui lòng nhập tên hạng mục!";
    } elseif (strtotime($ngay_ket_thuc) < strtotime($ngay_bat_dau)) {
        $error = "Ngày kết thúc phải sau ngày bắt đầu!";
    } else {
        $sql = "INSERT INTO hangmucthicong (congtrinh_id, ten_hang_muc, ngay_bat_dau, ngay_ket_thuc, 
                phan_tram_tien_do, trang_thai, ghi_chu) 
                VALUES ('$congtrinh_id', '$ten_hang_muc', '$ngay_bat_dau', '$ngay_ket_thuc', 
                '$phan_tram_tien_do', '$trang_thai', '$ghi_chu')";
        
        if (mysqli_query($conn, $sql)) {
            $success = "Thêm hạng mục thành công!";
            echo "<script>
                setTimeout(function() {
                    window.location.href = '../congtrinh/detail.php?id=$congtrinh_id';
                }, 1500);
            </script>";
        } else {
            $error = "Lỗi: " . mysqli_error($conn);
        }
    }
}
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">Thêm hạng mục mới</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="index.php">Hạng mục</a></li>
                <li class="breadcrumb-item active">Thêm mới</li>
            </ol>
        </nav>
    </div>

    <div class="card">
        <div class="card-header">
            <i class="fas fa-plus-circle me-1"></i>
            Thông tin hạng mục
        </div>
        <div class="card-body">
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Công trình <span class="text-danger">*</span></label>
                        <select name="congtrinh_id" class="form-select" required>
                            <option value="">-- Chọn công trình --</option>
                            <?php while ($ct = mysqli_fetch_assoc($result_congtrinh)): ?>
                            <option value="<?php echo $ct['id']; ?>" 
                                <?php echo ($congtrinh_id == $ct['id']) ? 'selected' : ''; ?>>
                                <?php echo $ct['ten_cong_trinh']; ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Tên hạng mục <span class="text-danger">*</span></label>
                        <input type="text" name="ten_hang_muc" class="form-control" required>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Ngày bắt đầu <span class="text-danger">*</span></label>
                        <input type="date" name="ngay_bat_dau" class="form-control" 
                               value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Ngày kết thúc <span class="text-danger">*</span></label>
                        <input type="date" name="ngay_ket_thuc" class="form-control" 
                               value="<?php echo date('Y-m-d', strtotime('+7 days')); ?>" required>
                    </div>
                    
                    <div class="col-12 mb-3">
                        <label class="form-label">Ghi chú</label>
                        <textarea name="ghi_chu" class="form-control" rows="3"></textarea>
                    </div>
                    
                    <div class="col-12">
                        <hr>
                        <button type="submit" name="them" class="btn btn-primary">
                            <i class="fas fa-save"></i> Lưu hạng mục
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