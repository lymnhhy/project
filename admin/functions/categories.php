<?php
include dirname(__DIR__) . "/includes/header.php";
include $_SERVER['DOCUMENT_ROOT'] . "/project/admin/includes/functions.php";

// Xử lý thêm loại công trình
if(isset($_POST['add_loai'])) {
    $ten_loai = mysqli_real_escape_string($conn, $_POST['ten_loai']);
    
    $sql = "INSERT INTO loaicongtrinh (ten_loai) VALUES ('$ten_loai')";
    if(mysqli_query($conn, $sql)) {
        $_SESSION['success'] = "Thêm loại công trình thành công!";
        logActivity($conn, $_SESSION['id'], "add_loaicongtrinh", "Tên loại: $ten_loai");
    } else {
        $_SESSION['error'] = "Lỗi: " . mysqli_error($conn);
    }
    header("Location: categories.php");
    exit();
}

// Xử lý sửa loại công trình
if(isset($_POST['edit_loai'])) {
    $id = (int)$_POST['id'];
    $ten_loai = mysqli_real_escape_string($conn, $_POST['ten_loai']);
    
    $sql = "UPDATE loaicongtrinh SET ten_loai = '$ten_loai' WHERE id = $id";
    if(mysqli_query($conn, $sql)) {
        $_SESSION['success'] = "Cập nhật loại công trình thành công!";
        logActivity($conn, $_SESSION['id'], "edit_loaicongtrinh", "ID: $id, Tên: $ten_loai");
    } else {
        $_SESSION['error'] = "Lỗi: " . mysqli_error($conn);
    }
    header("Location: categories.php");
    exit();
}

// Xử lý xóa loại công trình
if(isset($_GET['delete_loai'])) {
    $id = (int)$_GET['delete_loai'];
    
    // Kiểm tra xem có công trình nào đang dùng loại này không
    $check = "SELECT COUNT(*) as total FROM congtrinh WHERE loaiCT_id = $id";
    $result = mysqli_query($conn, $check);
    $row = mysqli_fetch_assoc($result);
    
    if($row['total'] > 0) {
        $_SESSION['error'] = "Không thể xóa vì có công trình đang sử dụng loại này!";
    } else {
        $sql = "DELETE FROM loaicongtrinh WHERE id = $id";
        if(mysqli_query($conn, $sql)) {
            $_SESSION['success'] = "Xóa loại công trình thành công!";
            logActivity($conn, $_SESSION['id'], "delete_loaicongtrinh", "ID: $id");
        } else {
            $_SESSION['error'] = "Lỗi: " . mysqli_error($conn);
        }
    }
    header("Location: categories.php");
    exit();
}

// Xử lý thêm trạng thái
if(isset($_POST['add_trangthai'])) {
    $ten_trang_thai = mysqli_real_escape_string($conn, $_POST['ten_trang_thai']);
    
    $sql = "INSERT INTO trangthaicongtrinh (ten_trang_thai) VALUES ('$ten_trang_thai')";
    if(mysqli_query($conn, $sql)) {
        $_SESSION['success'] = "Thêm trạng thái thành công!";
        logActivity($conn, $_SESSION['id'], "add_trangthai", "Tên: $ten_trang_thai");
    } else {
        $_SESSION['error'] = "Lỗi: " . mysqli_error($conn);
    }
    header("Location: categories.php");
    exit();
}

// Xử lý sửa trạng thái
if(isset($_POST['edit_trangthai'])) {
    $id = (int)$_POST['id'];
    $ten_trang_thai = mysqli_real_escape_string($conn, $_POST['ten_trang_thai']);
    
    $sql = "UPDATE trangthaicongtrinh SET ten_trang_thai = '$ten_trang_thai' WHERE id = $id";
    if(mysqli_query($conn, $sql)) {
        $_SESSION['success'] = "Cập nhật trạng thái thành công!";
        logActivity($conn, $_SESSION['id'], "edit_trangthai", "ID: $id, Tên: $ten_trang_thai");
    } else {
        $_SESSION['error'] = "Lỗi: " . mysqli_error($conn);
    }
    header("Location: categories.php");
    exit();
}

// Xử lý xóa trạng thái
if(isset($_GET['delete_trangthai'])) {
    $id = (int)$_GET['delete_trangthai'];
    
    // Kiểm tra xem có công trình nào đang dùng trạng thái này không
    $check = "SELECT COUNT(*) as total FROM congtrinh WHERE trangthaiCT_id = $id";
    $result = mysqli_query($conn, $check);
    $row = mysqli_fetch_assoc($result);
    
    if($row['total'] > 0) {
        $_SESSION['error'] = "Không thể xóa vì có công trình đang sử dụng trạng thái này!";
    } else {
        $sql = "DELETE FROM trangthaicongtrinh WHERE id = $id";
        if(mysqli_query($conn, $sql)) {
            $_SESSION['success'] = "Xóa trạng thái thành công!";
            logActivity($conn, $_SESSION['id'], "delete_trangthai", "ID: $id");
        } else {
            $_SESSION['error'] = "Lỗi: " . mysqli_error($conn);
        }
    }
    header("Location: categories.php");
    exit();
}

// Lấy danh sách loại công trình
$loai_result = mysqli_query($conn, "SELECT * FROM loaicongtrinh ORDER BY id DESC");

// Lấy danh sách trạng thái
$trangthai_result = mysqli_query($conn, "SELECT * FROM trangthaicongtrinh ORDER BY id");
?>

<div class="content-wrapper">
    <!-- Page Header -->
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center">
            <h4 class="mb-0">
                <i class="fas fa-tags me-2 text-primary"></i>
                Quản lý danh mục hệ thống
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
        <!-- DANH MỤC LOẠI CÔNG TRÌNH -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-building me-2 text-warning"></i>
                        Loại công trình
                    </h5>
                </div>
                <div class="card-body">
                    <!-- Form thêm loại công trình -->
                    <form method="POST" class="mb-4">
                        <div class="input-group">
                            <input type="text" name="ten_loai" class="form-control" 
                                   placeholder="Nhập tên loại công trình..." required>
                            <button type="submit" name="add_loai" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Thêm
                            </button>
                        </div>
                    </form>

                    <!-- Danh sách loại công trình -->
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th width="50">ID</th>
                                <th>Tên loại</th>
                                <th width="150">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(mysqli_num_rows($loai_result) == 0): ?>
                            <tr>
                                <td colspan="3" class="text-center py-4">
                                    <i class="fas fa-building fa-2x text-muted mb-2"></i>
                                    <p class="text-muted">Chưa có loại công trình nào</p>
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php while($loai = mysqli_fetch_assoc($loai_result)): ?>
                                <tr>
                                    <td>#<?php echo $loai['id']; ?></td>
                                    <td>
                                        <span class="badge bg-info"><?php echo $loai['ten_loai']; ?></span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary" 
                                                onclick="editLoai(<?php echo $loai['id']; ?>, '<?php echo $loai['ten_loai']; ?>')">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <a href="?delete_loai=<?php echo $loai['id']; ?>" 
                                           class="btn btn-sm btn-outline-danger"
                                           onclick="return confirm('Xóa loại công trình này?')">
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

        <!-- DANH MỤC TRẠNG THÁI -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-circle me-2 text-success"></i>
                        Trạng thái công trình
                    </h5>
                </div>
                <div class="card-body">
                    <!-- Form thêm trạng thái -->
                    <form method="POST" class="mb-4">
                        <div class="input-group">
                            <input type="text" name="ten_trang_thai" class="form-control" 
                                   placeholder="Nhập tên trạng thái..." required>
                            <button type="submit" name="add_trangthai" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Thêm
                            </button>
                        </div>
                    </form>

                    <!-- Danh sách trạng thái -->
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th width="50">ID</th>
                                <th>Tên trạng thái</th>
                                <th width="150">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(mysqli_num_rows($trangthai_result) == 0): ?>
                            <tr>
                                <td colspan="3" class="text-center py-4">
                                    <i class="fas fa-circle fa-2x text-muted mb-2"></i>
                                    <p class="text-muted">Chưa có trạng thái nào</p>
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php while($tt = mysqli_fetch_assoc($trangthai_result)): 
                                    $badge_color = 'secondary';
                                    if($tt['ten_trang_thai'] == 'Đang thi công') $badge_color = 'warning';
                                    if($tt['ten_trang_thai'] == 'Hoàn thành') $badge_color = 'success';
                                    if($tt['ten_trang_thai'] == 'Chưa thi công') $badge_color = 'secondary';
                                ?>
                                <tr>
                                    <td>#<?php echo $tt['id']; ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $badge_color; ?>">
                                            <?php echo $tt['ten_trang_thai']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary" 
                                                onclick="editTrangThai(<?php echo $tt['id']; ?>, '<?php echo $tt['ten_trang_thai']; ?>')">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <a href="?delete_trangthai=<?php echo $tt['id']; ?>" 
                                           class="btn btn-sm btn-outline-danger"
                                           onclick="return confirm('Xóa trạng thái này?')">
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

<!-- Modal sửa loại công trình -->
<div class="modal fade" id="editLoaiModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Sửa loại công trình</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="edit_loai_id">
                    <div class="mb-3">
                        <label class="form-label">Tên loại</label>
                        <input type="text" name="ten_loai" id="edit_loai_ten" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" name="edit_loai" class="btn btn-primary">Cập nhật</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal sửa trạng thái -->
<div class="modal fade" id="editTrangThaiModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Sửa trạng thái</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="edit_trangthai_id">
                    <div class="mb-3">
                        <label class="form-label">Tên trạng thái</label>
                        <input type="text" name="ten_trang_thai" id="edit_trangthai_ten" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" name="edit_trangthai" class="btn btn-primary">Cập nhật</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editLoai(id, ten) {
    document.getElementById('edit_loai_id').value = id;
    document.getElementById('edit_loai_ten').value = ten;
    new bootstrap.Modal(document.getElementById('editLoaiModal')).show();
}

function editTrangThai(id, ten) {
    document.getElementById('edit_trangthai_id').value = id;
    document.getElementById('edit_trangthai_ten').value = ten;
    new bootstrap.Modal(document.getElementById('editTrangThaiModal')).show();
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
}

.table {
    margin-bottom: 0;
}

.table th {
    background: #f8f9fa;
    color: #495057;
    font-weight: 600;
    font-size: 13px;
    border-bottom: 2px solid #dee2e6;
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
    padding: 4px 8px;
    margin: 0 2px;
}

.input-group .form-control {
    border-radius: 8px 0 0 8px;
    border: 1px solid #e0e0e0;
}

.input-group .btn {
    border-radius: 0 8px 8px 0;
    padding: 10px 20px;
}

.modal-content {
    border-radius: 12px;
    border: none;
}

.modal-header {
    background: #f8f9fa;
    border-radius: 12px 12px 0 0;
}

.modal-footer {
    border-top: 1px solid #eee;
}
</style>

<?php include dirname(__DIR__) . "/includes/footer.php"; ?>