<?php
// hangmuc/edit.php
require_once '../includes/header.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Lấy thông tin hạng mục
$sql = "SELECT hm.*, ct.ten_cong_trinh 
        FROM hangmucthicong hm
        LEFT JOIN congtrinh ct ON hm.congtrinh_id = ct.id
        WHERE hm.id = $id AND ct.user_id = '{$_SESSION['id']}'";
$result = mysqli_query($conn, $sql);

if(mysqli_num_rows($result) == 0) {
    $_SESSION['error'] = 'Không tìm thấy hạng mục';
    echo '<script>window.location.href="index.php";</script>';
    exit();
}

$hm = mysqli_fetch_assoc($result);
$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $ten_hang_muc = mysqli_real_escape_string($conn, $_POST['ten_hang_muc']);
    $kinh_phi = str_replace(',', '', $_POST['kinh_phi']);
    $ngay_bat_dau = $_POST['ngay_bat_dau'];
    $ngay_ket_thuc = $_POST['ngay_ket_thuc'];
    $ghi_chu = mysqli_real_escape_string($conn, $_POST['ghi_chu']);
    
    // Validate
    if(empty($ten_hang_muc)) {
        $error = 'Vui lòng nhập tên hạng mục';
    } elseif(strtotime($ngay_ket_thuc) < strtotime($ngay_bat_dau)) {
        $error = 'Ngày kết thúc phải sau ngày bắt đầu';
    } else {
        $sql_update = "UPDATE hangmucthicong SET 
                       ten_hang_muc = '$ten_hang_muc',
                       kinh_phi = '$kinh_phi',
                       ngay_bat_dau = '$ngay_bat_dau',
                       ngay_ket_thuc = '$ngay_ket_thuc',
                       ghi_chu = '$ghi_chu'
                       WHERE id = $id";
        
        if(mysqli_query($conn, $sql_update)) {
            logActivity($conn, $_SESSION['id'], 'Sửa hạng mục', "Sửa hạng mục: $ten_hang_muc");
            $_SESSION['success'] = 'Cập nhật hạng mục thành công';
            header('Location: detail.php?id=' . $id);
            exit();
        } else {
            $error = 'Lỗi: ' . mysqli_error($conn);
        }
    }
}
?>

<div class="content-wrapper">
    <div class="page-header">
        <h4 class="mb-0">
            <i class="fas fa-edit me-2 text-warning"></i>
            Sửa hạng mục: <?php echo htmlspecialchars($hm['ten_hang_muc']); ?>
        </h4>
        <p class="text-muted">Công trình: <?php echo htmlspecialchars($hm['ten_cong_trinh']); ?></p>
    </div>

    <?php if($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="POST" onsubmit="return validateForm()">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Tên hạng mục <span class="text-danger">*</span></label>
                        <input type="text" name="ten_hang_muc" class="form-control" 
                               value="<?php echo $hm['ten_hang_muc']; ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Kinh phí (VNĐ)</label>
                        <input type="text" name="kinh_phi" class="form-control money-format" 
                               value="<?php echo number_format($hm['kinh_phi'], 0, ',', '.'); ?>">
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Ngày bắt đầu <span class="text-danger">*</span></label>
                        <input type="date" name="ngay_bat_dau" class="form-control" 
                               value="<?php echo $hm['ngay_bat_dau']; ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Ngày kết thúc <span class="text-danger">*</span></label>
                        <input type="date" name="ngay_ket_thuc" class="form-control" 
                               value="<?php echo $hm['ngay_ket_thuc']; ?>" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Ghi chú</label>
                    <textarea name="ghi_chu" class="form-control" rows="4"><?php echo htmlspecialchars($hm['ghi_chu']); ?></textarea>
                </div>

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
// Format money
document.querySelector('.money-format')?.addEventListener('input', function(e) {
    let value = this.value.replace(/\D/g, '');
    if(value) {
        this.value = new Intl.NumberFormat('vi-VN').format(value);
    }
});

function validateForm() {
    const startDate = new Date(document.querySelector('input[name="ngay_bat_dau"]').value);
    const endDate = new Date(document.querySelector('input[name="ngay_ket_thuc"]').value);
    
    if(endDate < startDate) {
        alert('Ngày kết thúc phải sau ngày bắt đầu!');
        return false;
    }
    return true;
}
</script>

<?php require_once '../includes/footer.php'; ?>