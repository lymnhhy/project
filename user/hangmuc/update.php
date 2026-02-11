<?php
// user/hangmuc/update.php
$page_title = 'Cập nhật tiến độ';
require_once '../includes/header.php';

$user_id = $_SESSION['id'];
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Kiểm tra quyền
$sql = "SELECT hm.*, ct.user_id, ct.ten_cong_trinh 
        FROM hangmucthicong hm
        LEFT JOIN congtrinh ct ON hm.congtrinh_id = ct.id
        WHERE hm.id = '$id'";
$result = mysqli_query($conn, $sql);
$hm = mysqli_fetch_assoc($result);

if (!$hm || $hm['user_id'] != $user_id) {
    echo "<script>
        alert('Không tìm thấy hạng mục!');
        window.location.href = 'index.php';
    </script>";
    exit();
}

if (isset($_POST['capnhat'])) {
    $phan_tram_moi = (int)$_POST['phan_tram_tien_do'];
    $phan_tram_cu = $hm['phan_tram_tien_do'];
    $ghi_chu = mysqli_real_escape_string($conn, $_POST['ghi_chu']);
    
    // Xác định trạng thái
    if ($phan_tram_moi == 0) {
        $trang_thai = 'Chưa thi công';
    } elseif ($phan_tram_moi == 100) {
        $trang_thai = 'Hoàn thành';
    } else {
        $trang_thai = 'Đang thi công';
    }
    
    // Cập nhật hạng mục
    $sql_update = "UPDATE hangmucthicong SET 
                    phan_tram_tien_do = '$phan_tram_moi',
                    trang_thai = '$trang_thai',
                    ghi_chu = '$ghi_chu'
                    WHERE id = '$id'";
    
    if (mysqli_query($conn, $sql_update)) {
        // Lưu lịch sử
        $sql_history = "INSERT INTO lichsucapnhat (hangmuc_id, phan_tram_cu, phan_tram_moi, ghi_chu) 
                        VALUES ('$id', '$phan_tram_cu', '$phan_tram_moi', '$ghi_chu')";
        mysqli_query($conn, $sql_history);
        
        $success = "Cập nhật tiến độ thành công!";
    } else {
        $error = "Lỗi: " . mysqli_error($conn);
    }
}

// Lấy lịch sử cập nhật
$sql_history = "SELECT * FROM lichsucapnhat 
                WHERE hangmuc_id = '$id' 
                ORDER BY thoi_gian_cap_nhat DESC";
$result_history = mysqli_query($conn, $sql_history);
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">Cập nhật tiến độ</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="index.php">Hạng mục</a></li>
                <li class="breadcrumb-item active">Cập nhật</li>
            </ol>
        </nav>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-percent me-1"></i>
                    Cập nhật tiến độ
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <h6 class="text-muted mb-1">Công trình</h6>
                        <h5><?php echo $hm['ten_cong_trinh']; ?></h5>
                        <h6 class="text-muted mt-3 mb-1">Hạng mục</h6>
                        <h5><?php echo $hm['ten_hang_muc']; ?></h5>
                    </div>
                    
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    <?php if (isset($success)): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label class="form-label">Tiến độ hiện tại</label>
                            <div class="d-flex align-items-center">
                                <div class="progress flex-grow-1 me-3" style="height: 10px;">
                                    <div class="progress-bar bg-<?php echo getProgressColor($hm['phan_tram_tien_do']); ?>" 
                                         style="width: <?php echo $hm['phan_tram_tien_do']; ?>%"></div>
                                </div>
                                <span class="h5 mb-0"><?php echo $hm['phan_tram_tien_do']; ?>%</span>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Cập nhật tiến độ mới (%) <span class="text-danger">*</span></label>
                            <input type="range" name="phan_tram_tien_do" class="form-range" 
                                   min="0" max="100" value="<?php echo $hm['phan_tram_tien_do']; ?>" 
                                   oninput="this.nextElementSibling.value = this.value">
                            <output class="badge bg-primary mt-2 p-2"><?php echo $hm['phan_tram_tien_do']; ?>%</output>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Ghi chú cập nhật</label>
                            <textarea name="ghi_chu" class="form-control" rows="3" 
                                      placeholder="Nhập ghi chú cho lần cập nhật này..."></textarea>
                        </div>
                        
                        <button type="submit" name="capnhat" class="btn btn-primary">
                            <i class="fas fa-save"></i> Cập nhật
                        </button>
                        <a href="../congtrinh/detail.php?id=<?php echo $hm['congtrinh_id']; ?>" 
                           class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Quay lại
                        </a>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-history me-1"></i>
                    Lịch sử cập nhật
                </div>
                <div class="card-body" style="max-height: 500px; overflow-y: auto;">
                    <?php if (mysqli_num_rows($result_history) == 0): ?>
                        <p class="text-muted text-center py-4">
                            <i class="fas fa-clock fa-2x mb-2"></i><br>
                            Chưa có lịch sử cập nhật
                        </p>
                    <?php else: ?>
                        <?php while ($history = mysqli_fetch_assoc($result_history)): ?>
                        <div class="mb-3 pb-3 border-bottom">
                            <div class="d-flex justify-content-between">
                                <small class="text-primary fw-bold">
                                    <i class="fas fa-percent"></i> 
                                    <?php echo $history['phan_tram_cu']; ?>% → 
                                    <span class="text-success"><?php echo $history['phan_tram_moi']; ?>%</span>
                                </small>
                                <small class="text-muted">
                                    <?php echo date('d/m/Y H:i', strtotime($history['thoi_gian_cap_nhat'])); ?>
                                </small>
                            </div>
                            <?php if ($history['ghi_chu']): ?>
                                <p class="mb-0 mt-1 small"><?php echo $history['ghi_chu']; ?></p>
                            <?php endif; ?>
                        </div>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once '../includes/footer.php';
?>