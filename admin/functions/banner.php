<?php
include dirname(__DIR__) . "/includes/header.php";
include $_SERVER['DOCUMENT_ROOT'] . "/project/admin/includes/functions.php";

// Xử lý thêm banner
if(isset($_POST['add_banner'])) {
    $tieu_de = mysqli_real_escape_string($conn, $_POST['tieu_de']);
    $trang_thai = isset($_POST['trang_thai']) ? 1 : 0;
    
    // Xử lý upload hình ảnh
    $hinh_anh = '';
    if(isset($_FILES['hinh_anh']) && $_FILES['hinh_anh']['error'] == 0) {
        $target_dir = $_SERVER['DOCUMENT_ROOT'] . "/project/uploads/banner/";
        if(!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $file_name = time() . '_' . basename($_FILES['hinh_anh']['name']);
        $target_file = $target_dir . $file_name;
        
        if(move_uploaded_file($_FILES['hinh_anh']['tmp_name'], $target_file)) {
            $hinh_anh = "/project/uploads/banner/" . $file_name;
        }
    }
    
    $sql = "INSERT INTO anh (tieu_de, hinh_anh, trang_thai) VALUES ('$tieu_de', '$hinh_anh', $trang_thai)";
    
    if(mysqli_query($conn, $sql)) {
        $_SESSION['success'] = "Thêm banner thành công!";
        logActivity($conn, $_SESSION['id'], "add_banner", "Tiêu đề: $tieu_de");
    } else {
        $_SESSION['error'] = "Lỗi: " . mysqli_error($conn);
    }
    header("Location: banner.php");
    exit();
}

// Xử lý sửa banner
if(isset($_POST['edit_banner'])) {
    $id = (int)$_POST['id'];
    $tieu_de = mysqli_real_escape_string($conn, $_POST['tieu_de']);
    $trang_thai = isset($_POST['trang_thai']) ? 1 : 0;
    
    // Xử lý upload hình ảnh mới
    if(isset($_FILES['hinh_anh']) && $_FILES['hinh_anh']['error'] == 0) {
        $target_dir = $_SERVER['DOCUMENT_ROOT'] . "/project/uploads/banner/";
        $file_name = time() . '_' . basename($_FILES['hinh_anh']['name']);
        $target_file = $target_dir . $file_name;
        
        if(move_uploaded_file($_FILES['hinh_anh']['tmp_name'], $target_file)) {
            $hinh_anh = "/project/uploads/banner/" . $file_name;
            $sql = "UPDATE anh SET tieu_de = '$tieu_de', hinh_anh = '$hinh_anh', trang_thai = $trang_thai WHERE id = $id";
        }
    } else {
        $sql = "UPDATE anh SET tieu_de = '$tieu_de', trang_thai = $trang_thai WHERE id = $id";
    }
    
    if(mysqli_query($conn, $sql)) {
        $_SESSION['success'] = "Cập nhật banner thành công!";
        logActivity($conn, $_SESSION['id'], "edit_banner", "ID: $id");
    } else {
        $_SESSION['error'] = "Lỗi: " . mysqli_error($conn);
    }
    header("Location: banner.php");
    exit();
}

// Xử lý xóa banner
if(isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    // Lấy đường dẫn ảnh để xóa file
    $img = mysqli_query($conn, "SELECT hinh_anh FROM anh WHERE id = $id");
    $row = mysqli_fetch_assoc($img);
    
    if($row['hinh_anh']) {
        $file_path = $_SERVER['DOCUMENT_ROOT'] . $row['hinh_anh'];
        if(file_exists($file_path)) {
            unlink($file_path);
        }
    }
    
    $sql = "DELETE FROM anh WHERE id = $id";
    if(mysqli_query($conn, $sql)) {
        $_SESSION['success'] = "Xóa banner thành công!";
        logActivity($conn, $_SESSION['id'], "delete_banner", "ID: $id");
    } else {
        $_SESSION['error'] = "Lỗi: " . mysqli_error($conn);
    }
    header("Location: banner.php");
    exit();
}

// Xử lý thay đổi trạng thái
if(isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $sql = "UPDATE anh SET trang_thai = 1 - trang_thai WHERE id = $id";
    mysqli_query($conn, $sql);
    header("Location: banner.php");
    exit();
}

// Lấy danh sách banner
$result = mysqli_query($conn, "SELECT * FROM anh ORDER BY id DESC");
?>

<div class="content-wrapper">
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center">
            <h4 class="mb-0">
                <i class="fas fa-images me-2 text-primary"></i>
                Quản lý banner
            </h4>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                <i class="fas fa-plus-circle me-2"></i>Thêm banner
            </button>
        </div>
    </div>

    <?php if(isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle me-2"></i><?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th width="50">ID</th>
                        <th width="200">Hình ảnh</th>
                        <th>Tiêu đề</th>
                        <th width="100">Trạng thái</th>
                        <th width="150">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(mysqli_num_rows($result) == 0): ?>
                    <tr>
                        <td colspan="5" class="text-center py-5">
                            <i class="fas fa-images fa-3x text-muted mb-3"></i>
                            <p class="text-muted">Chưa có banner nào</p>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php while($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td>#<?php echo $row['id']; ?></td>
                            <td>
                                <?php if($row['hinh_anh']): ?>
                                <img src="<?php echo $row['hinh_anh']; ?>" style="max-height: 60px; border-radius: 5px;">
                                <?php else: ?>
                                <span class="text-muted">Chưa có ảnh</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $row['tieu_de']; ?></td>
                            <td>
                                <a href="?toggle=<?php echo $row['id']; ?>" class="text-decoration-none">
                                    <?php if($row['trang_thai']): ?>
                                    <span class="badge bg-success">Hiển thị</span>
                                    <?php else: ?>
                                    <span class="badge bg-secondary">Ẩn</span>
                                    <?php endif; ?>
                                </a>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary" 
                                        onclick="editBanner(<?php echo $row['id']; ?>, '<?php echo addslashes($row['tieu_de']); ?>', '<?php echo $row['hinh_anh']; ?>')">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <a href="?delete=<?php echo $row['id']; ?>" 
                                   class="btn btn-sm btn-outline-danger"
                                   onclick="return confirm('Xóa banner này?')">
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

<!-- Modal thêm banner -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title">Thêm banner mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Tiêu đề</label>
                        <input type="text" name="tieu_de" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Hình ảnh</label>
                        <input type="file" name="hinh_anh" class="form-control" accept="image/*" required>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" name="trang_thai" class="form-check-input" id="add_status" checked>
                            <label class="form-check-label" for="add_status">Hiển thị</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" name="add_banner" class="btn btn-primary">Thêm</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal sửa banner -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title">Sửa banner</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="mb-3">
                        <label class="form-label">Tiêu đề</label>
                        <input type="text" name="tieu_de" id="edit_tieu_de" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Hình ảnh mới (để trống nếu giữ nguyên)</label>
                        <input type="file" name="hinh_anh" class="form-control" accept="image/*">
                    </div>
                    <div class="mb-3" id="preview_image"></div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" name="trang_thai" class="form-check-input" id="edit_status">
                            <label class="form-check-label" for="edit_status">Hiển thị</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" name="edit_banner" class="btn btn-primary">Cập nhật</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editBanner(id, tieu_de, hinh_anh) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_tieu_de').value = tieu_de;
    document.getElementById('edit_status').checked = <?php echo $row['trang_thai'] ?? 1 ?>;
    
    if(hinh_anh) {
        document.getElementById('preview_image').innerHTML = 
            '<img src="' + hinh_anh + '" style="max-height: 100px; border-radius: 5px;">';
    }
    
    new bootstrap.Modal(document.getElementById('editModal')).show();
}
</script>

<?php include dirname(__DIR__) . "/includes/footer.php"; ?>