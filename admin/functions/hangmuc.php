<?php
include dirname(__DIR__) . "/includes/header.php";
include $_SERVER['DOCUMENT_ROOT'] . "/project/admin/includes/functions.php";

// Xử lý thêm hạng mục
if(isset($_POST['add_hangmuc'])) {
    $ten_hang_muc = mysqli_real_escape_string($conn, $_POST['ten_hang_muc']);
    
    // Chỉ insert ten_hang_muc, các cột khác để null hoặc giá trị mặc định
    $sql = "INSERT INTO hangmucthicong (ten_hang_muc) VALUES ('$ten_hang_muc')";
    
    if(mysqli_query($conn, $sql)) {
        $_SESSION['success'] = "Thêm hạng mục thi công thành công!";
        logActivity($conn, $_SESSION['id'], "add_hangmuc", "Tên: $ten_hang_muc");
    } else {
        $_SESSION['error'] = "Lỗi: " . mysqli_error($conn);
    }
    header("Location: hangmuc.php");
    exit();
}

// Xử lý sửa hạng mục
if(isset($_POST['edit_hangmuc'])) {
    $id = (int)$_POST['id'];
    $ten_hang_muc = mysqli_real_escape_string($conn, $_POST['ten_hang_muc']);
    
    $sql = "UPDATE hangmucthicong SET ten_hang_muc = '$ten_hang_muc' WHERE id = $id";
    
    if(mysqli_query($conn, $sql)) {
        $_SESSION['success'] = "Cập nhật hạng mục thành công!";
        logActivity($conn, $_SESSION['id'], "edit_hangmuc", "ID: $id, Tên: $ten_hang_muc");
    } else {
        $_SESSION['error'] = "Lỗi: " . mysqli_error($conn);
    }
    header("Location: hangmuc.php");
    exit();
}

// Xử lý xóa hạng mục
if(isset($_GET['delete_hangmuc'])) {
    $id = (int)$_GET['delete_hangmuc'];
    
    // Kiểm tra xem hạng mục có đang được dùng trong công trình không
    $check = "SELECT COUNT(*) as total FROM hangmucthicong WHERE id = $id";
    $result = mysqli_query($conn, $check);
    $row = mysqli_fetch_assoc($result);
    
    $sql = "DELETE FROM hangmucthicong WHERE id = $id";
    if(mysqli_query($conn, $sql)) {
        $_SESSION['success'] = "Xóa hạng mục thành công!";
        logActivity($conn, $_SESSION['id'], "delete_hangmuc", "ID: $id");
    } else {
        $_SESSION['error'] = "Không thể xóa vì hạng mục đang được sử dụng!";
    }
    header("Location: hangmuc.php");
    exit();
}

// Lấy danh sách hạng mục
$result = mysqli_query($conn, "SELECT * FROM hangmucthicong ORDER BY id DESC");
?>

<div class="content-wrapper">
    <!-- Page Header -->
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center">
            <h4 class="mb-0">
                <i class="fas fa-tools me-2 text-primary"></i>
                Quản lý hạng mục thi công
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
        <!-- Form thêm hạng mục -->
        <div class="col-md-5">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-plus-circle me-2 text-success"></i>
                        Thêm hạng mục mới
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Tên hạng mục <span class="text-danger">*</span></label>
                            <input type="text" name="ten_hang_muc" class="form-control" 
                                   placeholder="VD: Móng, Kết cấu, Hoàn thiện..." required>
                            <small class="text-muted">Đây là danh mục hạng mục thi công</small>
                        </div>
                        <button type="submit" name="add_hangmuc" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Thêm mới
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Danh sách hạng mục -->
        <div class="col-md-7">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-list me-2 text-warning"></i>
                        Danh sách hạng mục
                    </h5>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th width="60">ID</th>
                                <th>Tên hạng mục</th>
                                <th width="120">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(mysqli_num_rows($result) == 0): ?>
                            <tr>
                                <td colspan="3" class="text-center py-5">
                                    <i class="fas fa-tools fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">Chưa có hạng mục nào</p>
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php while($row = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td>#<?php echo $row['id']; ?></td>
                                    <td>
                                        <span class="badge bg-primary" style="font-size: 14px;">
                                            <?php echo htmlspecialchars($row['ten_hang_muc']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary" 
                                                onclick="editHangMuc(<?php echo $row['id']; ?>, '<?php echo addslashes($row['ten_hang_muc']); ?>')">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <a href="?delete_hangmuc=<?php echo $row['id']; ?>" 
                                           class="btn btn-sm btn-outline-danger"
                                           onclick="return confirm('Xóa hạng mục <?php echo addslashes($row['ten_hang_muc']); ?>?')">
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

<!-- Modal sửa hạng mục -->
<div class="modal fade" id="editHangMucModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Sửa hạng mục thi công</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="mb-3">
                        <label class="form-label">Tên hạng mục <span class="text-danger">*</span></label>
                        <input type="text" name="ten_hang_muc" id="edit_ten" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" name="edit_hangmuc" class="btn btn-primary">Cập nhật</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editHangMuc(id, ten) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_ten').value = ten;
    new bootstrap.Modal(document.getElementById('editHangMucModal')).show();
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
    padding: 8px 15px;
    font-weight: 500;
    font-size: 13px;
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