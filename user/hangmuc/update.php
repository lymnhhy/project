<?php
// hangmuc/update.php
require_once '../includes/header.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error = '';
$success = '';

// Lấy thông tin hạng mục
$sql = "SELECT hm.*, ct.ten_cong_trinh, ct.user_id 
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

// Lấy lịch sử cập nhật gần nhất
$sql_lichsu = "SELECT * FROM lichsucapnhat WHERE hangmuc_id = $id ORDER BY thoi_gian_cap_nhat DESC LIMIT 5";
$lichsu_list = mysqli_query($conn, $sql_lichsu);

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $phan_tram_moi = (int)$_POST['phan_tram_tien_do'];
    $ghi_chu_capnhat = mysqli_real_escape_string($conn, $_POST['ghi_chu_capnhat']);
    $ghi_chu = mysqli_real_escape_string($conn, $_POST['ghi_chu']);
    
    // Xác định trạng thái mới
    if($phan_tram_moi == 0) {
        $trang_thai_moi = 'Chưa thi công';
    } elseif($phan_tram_moi == 100) {
        $trang_thai_moi = 'Hoàn thành';
    } else {
        $trang_thai_moi = 'Đang thi công';
    }
    
    // Cập nhật hạng mục
    $sql_update = "UPDATE hangmucthicong SET 
                   phan_tram_tien_do = $phan_tram_moi,
                   trang_thai = '$trang_thai_moi',
                   ghi_chu = '$ghi_chu',
                   updated_at = NOW()
                   WHERE id = $id";
    
    if(mysqli_query($conn, $sql_update)) {
        // Ghi lịch sử cập nhật
        $sql_lichsu_insert = "INSERT INTO lichsucapnhat (hangmuc_id, phan_tram_cu, phan_tram_moi, ghi_chu) 
                             VALUES ($id, {$hm['phan_tram_tien_do']}, $phan_tram_moi, '$ghi_chu_capnhat')";
        mysqli_query($conn, $sql_lichsu_insert);
        
        // Ghi log
        logActivity($conn, $_SESSION['id'], 'Cập nhật tiến độ', 
                   "Cập nhật hạng mục {$hm['ten_hang_muc']}: {$hm['phan_tram_tien_do']}% → {$phan_tram_moi}%");
        
        $_SESSION['success'] = 'Cập nhật tiến độ thành công';
        
        // Cập nhật lại thông tin
        $hm['phan_tram_tien_do'] = $phan_tram_moi;
        $hm['trang_thai'] = $trang_thai_moi;
        $hm['ghi_chu'] = $ghi_chu;
        
        // Refresh lịch sử
        $lichsu_list = mysqli_query($conn, $sql_lichsu);
    } else {
        $error = 'Lỗi: ' . mysqli_error($conn);
    }
}
?>

<div class="content-wrapper">
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h4 class="mb-1">
                    <i class="fas fa-pen me-2 text-warning"></i>
                    Cập nhật tiến độ
                </h4>
                <p class="text-muted mb-0">
                    <a href="../congtrinh/detail.php?id=<?php echo $hm['congtrinh_id']; ?>" class="text-decoration-none">
                        <?php echo htmlspecialchars($hm['ten_cong_trinh']); ?>
                    </a>
                    / <?php echo htmlspecialchars($hm['ten_hang_muc']); ?>
                </p>
            </div>
            <div class="mt-2 mt-sm-0">
                <a href="detail.php?id=<?php echo $id; ?>" class="btn btn-info">
                    <i class="fas fa-eye me-2"></i>Chi tiết
                </a>
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
        <!-- Form cập nhật -->
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-edit me-2 text-warning"></i>
                    Cập nhật tiến độ
                </div>
                <div class="card-body">
                    <form method="POST">
                        <!-- Tiến độ hiện tại -->
                        <div class="mb-4">
                            <label class="form-label">Tiến độ hiện tại</label>
                            <div class="progress" style="height: 30px;">
                                <div class="progress-bar bg-<?php echo getProgressColor($hm['phan_tram_tien_do']); ?>" 
                                     style="width: <?php echo $hm['phan_tram_tien_do']; ?>%; font-size: 14px; font-weight: bold;">
                                    <?php echo $hm['phan_tram_tien_do']; ?>%
                                </div>
                            </div>
                        </div>

                        <!-- Tiến độ mới -->
                        <div class="mb-4">
                            <label class="form-label">Tiến độ mới (%)</label>
                            <div class="d-flex align-items-center gap-3">
                                <input type="range" name="phan_tram_tien_do" class="form-range" 
                                       min="0" max="100" value="<?php echo $hm['phan_tram_tien_do']; ?>" 
                                       onchange="updateRange(this.value)">
                                <span class="badge bg-primary" id="rangeValue" style="min-width: 50px; font-size: 16px;">
                                    <?php echo $hm['phan_tram_tien_do']; ?>%
                                </span>
                            </div>
                            <div class="mt-2">
                                <span class="badge bg-secondary">0% = Chưa thi công</span>
                                <span class="badge bg-warning">1-99% = Đang thi công</span>
                                <span class="badge bg-success">100% = Hoàn thành</span>
                            </div>
                        </div>

                        <!-- Ghi chú cập nhật -->
                        <div class="mb-4">
                            <label class="form-label">Ghi chú cập nhật</label>
                            <textarea name="ghi_chu_capnhat" class="form-control" rows="3" 
                                      placeholder="Nhập lý do cập nhật, khó khăn, vướng mắc..."></textarea>
                        </div>

                        <!-- Ghi chú chung -->
                        <div class="mb-4">
                            <label class="form-label">Ghi chú hạng mục</label>
                            <textarea name="ghi_chu" class="form-control" rows="3"><?php echo htmlspecialchars($hm['ghi_chu']); ?></textarea>
                        </div>

                        <!-- Buttons -->
                        <div class="text-center">
                            <button type="submit" class="btn btn-primary px-5">
                                <i class="fas fa-save me-2"></i>Cập nhật
                            </button>
                            <button type="reset" class="btn btn-secondary px-5">
                                <i class="fas fa-undo me-2"></i>Reset
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Lịch sử cập nhật -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-history me-2 text-warning"></i>
                    Lịch sử cập nhật
                </div>
                <div class="card-body p-0">
                    <?php if(mysqli_num_rows($lichsu_list) == 0): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-history fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Chưa có lịch sử cập nhật</p>
                    </div>
                    <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php while($ls = mysqli_fetch_assoc($lichsu_list)): ?>
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="badge bg-info">
                                    <?php echo $ls['phan_tram_cu']; ?>% → <?php echo $ls['phan_tram_moi']; ?>%
                                </span>
                                <small class="text-muted">
                                    <?php echo timeAgo($ls['thoi_gian_cap_nhat']); ?>
                                </small>
                            </div>
                            <?php if(!empty($ls['ghi_chu'])): ?>
                            <p class="small mb-0"><?php echo htmlspecialchars($ls['ghi_chu']); ?></p>
                            <?php endif; ?>
                        </div>
                        <?php endwhile; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="card-footer text-center">
                    <a href="history.php?hangmuc_id=<?php echo $id; ?>" class="text-decoration-none">
                        Xem tất cả lịch sử <i class="fas fa-arrow-right ms-2"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function updateRange(value) {
    document.getElementById('rangeValue').textContent = value + '%';
    
    // Đổi màu badge
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
</script>

<?php require_once '../includes/footer.php'; ?>