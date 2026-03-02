<?php
ob_start();
include dirname(__DIR__) . "/includes/header.php";
include $_SERVER['DOCUMENT_ROOT'] . "/project/admin/includes/functions.php";

// Xử lý thêm loại hạng mục
if(isset($_POST['add_loaihangmuc'])) {
    $ma_loai = mysqli_real_escape_string($conn, $_POST['ma_loai'] ?? '');
    $ten_loai = mysqli_real_escape_string($conn, $_POST['ten_loai']);
    $mo_ta = mysqli_real_escape_string($conn, $_POST['mo_ta']);
    $don_vi_tinh = mysqli_real_escape_string($conn, $_POST['don_vi_tinh']);
    
    // Tự động tạo mã nếu để trống
    if(empty($ma_loai)) {
        // Lấy số thứ tự lớn nhất
        $result = mysqli_query($conn, "SELECT MAX(CAST(SUBSTRING(ma_loai, 5) AS UNSIGNED)) as max_id FROM loaihangmuc WHERE ma_loai LIKE 'LHM-%'");
        $row = mysqli_fetch_assoc($result);
        $next_id = ($row['max_id'] ?? 0) + 1;
        $ma_loai = 'LHM-' . str_pad($next_id, 3, '0', STR_PAD_LEFT);
    }
    
    $sql = "INSERT INTO loaihangmuc (ma_loai, ten_loai, mo_ta, don_vi_tinh, trang_thai) 
            VALUES ('$ma_loai', '$ten_loai', '$mo_ta', '$don_vi_tinh', 1)";
    
    if(mysqli_query($conn, $sql)) {
        $_SESSION['success'] = "Thêm loại hạng mục thành công!";
        logActivity($conn, $_SESSION['id'], "add_loaihangmuc", "Tên: $ten_loai, Mã: $ma_loai");
    } else {
        $_SESSION['error'] = "Lỗi: " . mysqli_error($conn);
    }
    header("Location: hangmuc.php");
    exit();
}

// Xử lý sửa loại hạng mục
if(isset($_POST['edit_loaihangmuc'])) {
    $id = (int)$_POST['id'];
    $ma_loai = mysqli_real_escape_string($conn, $_POST['ma_loai']);
    $ten_loai = mysqli_real_escape_string($conn, $_POST['ten_loai']);
    $mo_ta = mysqli_real_escape_string($conn, $_POST['mo_ta']);
    $don_vi_tinh = mysqli_real_escape_string($conn, $_POST['don_vi_tinh']);
    $trang_thai = (int)$_POST['trang_thai'];
    
    $sql = "UPDATE loaihangmuc SET 
            ma_loai = '$ma_loai',
            ten_loai = '$ten_loai',
            mo_ta = '$mo_ta',
            don_vi_tinh = '$don_vi_tinh',
            trang_thai = $trang_thai
            WHERE id = $id";
    
    if(mysqli_query($conn, $sql)) {
        $_SESSION['success'] = "Cập nhật loại hạng mục thành công!";
        logActivity($conn, $_SESSION['id'], "edit_loaihangmuc", "ID: $id, Tên: $ten_loai");
    } else {
        $_SESSION['error'] = "Lỗi: " . mysqli_error($conn);
    }
    header("Location: hangmuc.php");
    exit();
}

// Xử lý xóa loại hạng mục
if(isset($_GET['delete_loaihangmuc'])) {
    $id = (int)$_GET['delete_loaihangmuc'];
    
    // Kiểm tra xem loại hạng mục có đang được sử dụng trong bảng hangmucthicong không
    $check = mysqli_query($conn, "SELECT COUNT(*) as total FROM hangmucthicong WHERE loaihangmuc_id = $id");
    $row = mysqli_fetch_assoc($check);
    
    if($row['total'] > 0) {
        $_SESSION['error'] = "Không thể xóa vì loại hạng mục đang được sử dụng trong " . $row['total'] . " hạng mục thi công!";
    } else {
        $sql = "DELETE FROM loaihangmuc WHERE id = $id";
        if(mysqli_query($conn, $sql)) {
            $_SESSION['success'] = "Xóa loại hạng mục thành công!";
            logActivity($conn, $_SESSION['id'], "delete_loaihangmuc", "ID: $id");
        } else {
            $_SESSION['error'] = "Lỗi: " . mysqli_error($conn);
        }
    }
    header("Location: hangmuc.php");
    exit();
}

// Lấy danh sách loại hạng mục
$result = mysqli_query($conn, "SELECT * FROM loaihangmuc ORDER BY id DESC");
?>

<div class="content-wrapper">
    <!-- Page Header -->
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center">
            <h4 class="mb-0">
                <i class="fas fa-tools me-2 text-primary"></i>
                Quản lý loại hạng mục thi công
            </h4>
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

    <div class="row">
        <!-- Form thêm loại hạng mục -->
        <div class="col-md-5">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-plus-circle me-2 text-success"></i>
                        Thêm loại hạng mục mới
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Mã loại</label>
                            <input type="text" name="ma_loai" class="form-control" 
                                   readonly>
                            <small class="text-muted">Để trống để tự động tạo mã</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Tên loại hạng mục <span class="text-danger">*</span></label>
                            <input type="text" name="ten_loai" class="form-control" 
                                   placeholder="VD: Phần móng, Phần thân, Hoàn thiện..." required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Đơn vị tính</label>
                            <input type="text" name="don_vi_tinh" class="form-control" 
                                   placeholder="VD: m2, m3, bộ, hệ thống...">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Mô tả</label>
                            <textarea name="mo_ta" class="form-control" rows="3" 
                                      placeholder="Mô tả chi tiết về loại hạng mục này..."></textarea>
                        </div>
                        
                        <button type="submit" name="add_loaihangmuc" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Thêm mới
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Danh sách loại hạng mục -->
        <div class="col-md-7">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-list me-2 text-warning"></i>
                        Danh sách loại hạng mục
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th width="60">ID</th>
                                    <th>Mã loại</th>
                                    <th>Tên loại hạng mục</th>
                                    <th>Đơn vị</th>
                                    <th>Trạng thái</th>
                                    <th width="120">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(mysqli_num_rows($result) == 0): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5">
                                        <i class="fas fa-tools fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">Chưa có loại hạng mục nào</p>
                                    </td>
                                </tr>
                                <?php else: ?>
                                    <?php while($row = mysqli_fetch_assoc($result)): ?>
                                    <tr>
                                        <td>#<?php echo $row['id']; ?></td>
                                        <td>
                                            <span class="badge bg-secondary"><?php echo htmlspecialchars($row['ma_loai']); ?></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary" style="font-size: 14px;">
                                                <?php echo htmlspecialchars($row['ten_loai']); ?>
                                            </span>
                                            <?php if(!empty($row['mo_ta'])): ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($row['mo_ta']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if(!empty($row['don_vi_tinh'])): ?>
                                            <span class="badge bg-info"><?php echo htmlspecialchars($row['don_vi_tinh']); ?></span>
                                            <?php else: ?>
                                            <span class="text-muted">---</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if($row['trang_thai'] == 1): ?>
                                            <span class="badge bg-success">Hoạt động</span>
                                            <?php else: ?>
                                            <span class="badge bg-secondary">Tạm dừng</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary" 
                                                    onclick="editLoaiHangMuc(<?php echo $row['id']; ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <a href="?delete_loaihangmuc=<?php echo $row['id']; ?>" 
                                               class="btn btn-sm btn-outline-danger"
                                               onclick="return confirm('Xóa loại hạng mục <?php echo addslashes($row['ten_loai']); ?>?\nCác hạng mục liên quan sẽ mất phân loại!')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal sửa loại hạng mục -->
<div class="modal fade" id="editLoaiHangMucModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Sửa loại hạng mục</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="mb-3">
                        <label class="form-label">Mã loại <span class="text-danger">*</span></label>
                        <input type="text" name="ma_loai" id="edit_ma_loai" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tên loại hạng mục <span class="text-danger">*</span></label>
                        <input type="text" name="ten_loai" id="edit_ten_loai" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Đơn vị tính</label>
                        <input type="text" name="don_vi_tinh" id="edit_don_vi_tinh" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mô tả</label>
                        <textarea name="mo_ta" id="edit_mo_ta" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Trạng thái</label>
                        <select name="trang_thai" id="edit_trang_thai" class="form-select">
                            <option value="1">Hoạt động</option>
                            <option value="0">Tạm dừng</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" name="edit_loaihangmuc" class="btn btn-primary">Cập nhật</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Hàm lấy thông tin loại hạng mục để sửa
function editLoaiHangMuc(id) {
    // Gọi AJAX để lấy thông tin chi tiết
    fetch('get_loaihangmuc.php?id=' + id)
        .then(response => response.json())
        .then(data => {
            document.getElementById('edit_id').value = data.id;
            document.getElementById('edit_ma_loai').value = data.ma_loai;
            document.getElementById('edit_ten_loai').value = data.ten_loai;
            document.getElementById('edit_don_vi_tinh').value = data.don_vi_tinh || '';
            document.getElementById('edit_mo_ta').value = data.mo_ta || '';
            document.getElementById('edit_trang_thai').value = data.trang_thai;
            
            new bootstrap.Modal(document.getElementById('editLoaiHangMucModal')).show();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Không thể tải thông tin!');
        });
}
</script>

<style>
.card {
    border: none;
    border-radius: 12px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    margin-bottom: 20px;
}

.card-header {
    background: white;
    border-bottom: 1px solid #eee;
    padding: 15px 20px;
    border-radius: 12px 12px 0 0 !important;
}

.card-header h5 {
    color: #333;
    font-weight: 600;
    margin: 0;
}

.table th {
    background: #f8f9fa;
    color: #495057;
    font-weight: 600;
    font-size: 13px;
    border-bottom: 2px solid #dee2e6;
    padding: 12px;
}

.table td {
    vertical-align: middle;
    padding: 12px;
}

.badge {
    padding: 6px 12px;
    font-weight: 500;
    font-size: 12px;
}

.btn-sm {
    padding: 5px 10px;
    margin: 0 2px;
}

.form-label {
    font-weight: 500;
    color: #495057;
    margin-bottom: 5px;
}

.form-control {
    border-radius: 8px;
    border: 1px solid #e0e0e0;
    padding: 10px 15px;
}

.form-control:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102,126,234,0.1);
}
</style>

<?php include dirname(__DIR__) . "/includes/footer.php"; ?>