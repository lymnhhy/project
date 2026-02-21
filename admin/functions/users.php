<?php
include dirname(__DIR__) . "/includes/header.php";
include $_SERVER['DOCUMENT_ROOT'] . "/project/admin/includes/functions.php";
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Xử lý khóa/mở khóa
if(isset($_GET['lock']) && $_GET['lock'] > 0) {
    $user_id = (int)$_GET['lock'];
    // Lấy trạng thái hiện tại
    $sql = "SELECT trangthai FROM users WHERE id = $user_id";
    $result = mysqli_query($conn, $sql);
    $user = mysqli_fetch_assoc($result);
    
    $new_status = $user['trangthai'] == 1 ? 0 : 1;
    $status_text = $new_status == 1 ? 'mở khóa' : 'khóa';
    
    $update = "UPDATE users SET trangthai = $new_status WHERE id = $user_id";
    if(mysqli_query($conn, $update)) {
        $_SESSION['success'] = "Đã $status_text tài khoản thành công!";
        logActivity($conn, $_SESSION['id'], "user_$status_text", "User ID: $user_id");
    }
    header("Location: users.php");
    exit();
}

// Xử lý xóa user
if(isset($_GET['delete']) && $_GET['delete'] > 0) {
    $user_id = (int)$_GET['delete'];
    
    // Kiểm tra không cho xóa admin
    $check = "SELECT role_id FROM users WHERE id = $user_id";
    $result = mysqli_query($conn, $check);
    $user = mysqli_fetch_assoc($result);
    
    if($user['role_id'] == 1) {
        $_SESSION['error'] = "Không thể xóa tài khoản Admin!";
    } else {
        $sql = "DELETE FROM users WHERE id = $user_id";
        if(mysqli_query($conn, $sql)) {
            $_SESSION['success'] = "Xóa tài khoản thành công!";
            logActivity($conn, $_SESSION['id'], "user_delete", "User ID: $user_id");
        }
    }
    header("Location: users.php");
    exit();
}

// Xử lý thêm/sửa user
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    if(isset($_POST['add_user'])) {
        // THÊM USER
        $username = mysqli_real_escape_string($conn, $_POST['username']);
        $password = md5($_POST['password']);
        $hoten = mysqli_real_escape_string($conn, $_POST['hoten']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $sdt = mysqli_real_escape_string($conn, $_POST['sdt']);
        $role_id = (int)$_POST['role_id'];
        $trangthai = isset($_POST['trangthai']) ? 1 : 0;
        
        // Kiểm tra username đã tồn tại?
        $check = "SELECT id FROM users WHERE username = '$username'";
        $check_result = mysqli_query($conn, $check);
        
        if(mysqli_num_rows($check_result) > 0) {
            $_SESSION['error'] = "Tên đăng nhập đã tồn tại!";
        } else {
            $sql = "INSERT INTO users (username, password, hoten, email, sdt, role_id, trangthai) 
                    VALUES ('$username', '$password', '$hoten', '$email', '$sdt', $role_id, $trangthai)";
            
            if(mysqli_query($conn, $sql)) {
                $_SESSION['success'] = "Thêm người dùng thành công!";
                logActivity($conn, $_SESSION['id'], "user_add", "Username: $username");
                header("Location: users.php");
                exit();
            } else {
                $_SESSION['error'] = "Lỗi: " . mysqli_error($conn);
            }
        }
    }
    
    if(isset($_POST['edit_user'])) {
        // SỬA USER
        $user_id = (int)$_POST['user_id'];
        $hoten = mysqli_real_escape_string($conn, $_POST['hoten']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $sdt = mysqli_real_escape_string($conn, $_POST['sdt']);
        $role_id = (int)$_POST['role_id'];
        $trangthai = isset($_POST['trangthai']) ? 1 : 0;
        
        $sql = "UPDATE users SET 
                hoten = '$hoten',
                email = '$email',
                sdt = '$sdt',
                role_id = $role_id,
                trangthai = $trangthai
                WHERE id = $user_id";
        
        // Nếu có đổi mật khẩu
        if(!empty($_POST['password'])) {
            $password = md5($_POST['password']);
            $sql = "UPDATE users SET 
                    hoten = '$hoten',
                    email = '$email',
                    sdt = '$sdt',
                    password = '$password',
                    role_id = $role_id,
                    trangthai = $trangthai
                    WHERE id = $user_id";
        }
        
        if(mysqli_query($conn, $sql)) {
            $_SESSION['success'] = "Cập nhật người dùng thành công!";
            logActivity($conn, $_SESSION['id'], "user_edit", "User ID: $user_id");
            header("Location: users.php");
            exit();
        } else {
            $_SESSION['error'] = "Lỗi: " . mysqli_error($conn);
        }
    }
}

// Lấy danh sách users
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Xử lý tìm kiếm
$where = "WHERE 1=1";
if(isset($_GET['keyword']) && !empty($_GET['keyword'])) {
    $keyword = mysqli_real_escape_string($conn, $_GET['keyword']);
    $where .= " AND (username LIKE '%$keyword%' OR hoten LIKE '%$keyword%' OR email LIKE '%$keyword%')";
}

if(isset($_GET['role']) && !empty($_GET['role'])) {
    $role = (int)$_GET['role'];
    $where .= " AND role_id = $role";
}

if(isset($_GET['status']) && $_GET['status'] !== '') {
    $status = (int)$_GET['status'];
    $where .= " AND trangthai = $status";
}

// Đếm tổng số
$count_sql = "SELECT COUNT(*) as total FROM users $where";
$count_result = mysqli_query($conn, $count_sql);
$total_rows = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_rows / $limit);

// Lấy danh sách
$sql = "SELECT u.*, r.vaitro 
        FROM users u
        LEFT JOIN roles r ON u.role_id = r.id
        $where
        ORDER BY u.id DESC
        LIMIT $offset, $limit";
$result = mysqli_query($conn, $sql);
?>

<div class="content-wrapper">
    <!-- Page Header -->
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center">
            <h4 class="mb-0">
                <i class="fas fa-users me-2 text-primary"></i>
                Quản lý người dùng
            </h4>
            <a href="?action=add" class="btn btn-primary">
                <i class="fas fa-plus-circle me-2"></i>Thêm người dùng
            </a>
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

    <?php if($action == 'add' || $action == 'edit'): 
        // Lấy thông tin user nếu edit
        $user = [];
        if($action == 'edit' && $id > 0) {
            $sql = "SELECT * FROM users WHERE id = $id";
            $user_result = mysqli_query($conn, $sql);
            $user = mysqli_fetch_assoc($user_result);
        }
    ?>
    <!-- FORM THÊM/SỬA USER -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><?php echo $action == 'add' ? 'Thêm người dùng mới' : 'Sửa thông tin người dùng'; ?></h5>
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <?php if($action == 'edit'): ?>
                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Tên đăng nhập <span class="text-danger">*</span></label>
                        <input type="text" name="username" class="form-control" 
                               value="<?php echo $user['username'] ?? ''; ?>" 
                               <?php echo $action == 'edit' ? 'readonly' : 'required'; ?>>
                        <?php if($action == 'edit'): ?>
                        <small class="text-muted">Không thể thay đổi tên đăng nhập</small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Mật khẩu <?php echo $action == 'add' ? '<span class="text-danger">*</span>' : '(Để trống nếu không đổi)'; ?></label>
                        <input type="password" name="password" class="form-control" 
                               <?php echo $action == 'add' ? 'required' : ''; ?>>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Họ tên <span class="text-danger">*</span></label>
                        <input type="text" name="hoten" class="form-control" 
                               value="<?php echo $user['hoten'] ?? ''; ?>" required>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" 
                               value="<?php echo $user['email'] ?? ''; ?>">
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Số điện thoại</label>
                        <input type="text" name="sdt" class="form-control" 
                               value="<?php echo $user['sdt'] ?? ''; ?>">
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Vai trò</label>
                        <select name="role_id" class="form-select">
                            <option value="2" <?php echo ($user['role_id'] ?? 2) == 2 ? 'selected' : ''; ?>>User</option>
                            <option value="1" <?php echo ($user['role_id'] ?? '') == 1 ? 'selected' : ''; ?>>Admin</option>
                        </select>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <div class="form-check mt-4">
                            <input type="checkbox" name="trangthai" class="form-check-input" id="trangthai" 
                                   <?php echo ($user['trangthai'] ?? 1) == 1 ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="trangthai">Kích hoạt tài khoản</label>
                        </div>
                    </div>
                </div>
                
                <div class="text-end">
                    <a href="users.php" class="btn btn-secondary me-2">
                        <i class="fas fa-undo me-2"></i>Hủy
                    </a>
                    <button type="submit" name="<?php echo $action == 'add' ? 'add_user' : 'edit_user'; ?>" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i><?php echo $action == 'add' ? 'Thêm mới' : 'Cập nhật'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php else: ?>
    <!-- BỘ LỌC -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <input type="text" name="keyword" class="form-control" 
                           placeholder="Tìm kiếm..." value="<?php echo $_GET['keyword'] ?? ''; ?>">
                </div>
                <div class="col-md-2">
                    <select name="role" class="form-select">
                        <option value="">Tất cả vai trò</option>
                        <option value="1" <?php echo ($_GET['role'] ?? '') == '1' ? 'selected' : ''; ?>>Admin</option>
                        <option value="2" <?php echo ($_GET['role'] ?? '') == '2' ? 'selected' : ''; ?>>User</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="">Tất cả trạng thái</option>
                        <option value="1" <?php echo ($_GET['status'] ?? '') === '1' ? 'selected' : ''; ?>>Đang hoạt động</option>
                        <option value="0" <?php echo ($_GET['status'] ?? '') === '0' ? 'selected' : ''; ?>>Đã khóa</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter me-2"></i>Lọc
                    </button>
                    <a href="users.php" class="btn btn-secondary">
                        <i class="fas fa-undo me-2"></i>Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- DANH SÁCH USERS -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th width="50">ID</th>
                            <th>Tên đăng nhập</th>
                            <th>Họ tên</th>
                            <th>Email/SĐT</th>
                            <th>Vai trò</th>
                            <th>Trạng thái</th>
                            <th>Ngày tạo</th>
                            <th width="150">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(mysqli_num_rows($result) == 0): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                <h6 class="text-muted">Không có người dùng nào</h6>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php while($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td>#<?php echo $row['id']; ?></td>
                                <td>
                                    <strong><?php echo $row['username']; ?></strong>
                                </td>
                                <td><?php echo $row['hoten']; ?></td>
                                <td>
                                    <small class="d-block">📧 <?php echo $row['email']; ?></small>
                                    <small class="text-muted">📞 <?php echo $row['sdt']; ?></small>
                                </td>
                                <td>
                                    <?php if($row['role_id'] == 1): ?>
                                    <span class="badge bg-danger">Admin</span>
                                    <?php else: ?>
                                    <span class="badge bg-info">User</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if($row['trangthai'] == 1): ?>
                                    <span class="badge bg-success">Đang hoạt động</span>
                                    <?php else: ?>
                                    <span class="badge bg-secondary">Đã khóa</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small><?php echo date('d/m/Y', strtotime($row['ngaytao'])); ?></small>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="?action=edit&id=<?php echo $row['id']; ?>" 
                                           class="btn btn-sm btn-outline-primary" title="Sửa">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        
                                        <a href="?lock=<?php echo $row['id']; ?>" 
                                           class="btn btn-sm btn-outline-<?php echo $row['trangthai'] == 1 ? 'warning' : 'success'; ?>"
                                           title="<?php echo $row['trangthai'] == 1 ? 'Khóa' : 'Mở khóa'; ?>"
                                           onclick="return confirm('Bạn có chắc muốn <?php echo $row['trangthai'] == 1 ? 'khóa' : 'mở khóa'; ?> tài khoản này?');">
                                            <i class="fas fa-<?php echo $row['trangthai'] == 1 ? 'lock' : 'unlock'; ?>"></i>
                                        </a>
                                        
                                        <?php if($row['role_id'] != 1): // Không cho xóa Admin ?>
                                        <a href="?delete=<?php echo $row['id']; ?>" 
                                           class="btn btn-sm btn-outline-danger" title="Xóa"
                                           onclick="return confirm('Bạn có chắc muốn xóa tài khoản <?php echo $row['username']; ?>?');">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Phân trang -->
        <?php if($total_pages > 1): ?>
        <div class="card-footer">
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center mb-0">
                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page-1; ?>&<?php echo http_build_query($_GET); ?>">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    </li>
                    <?php for($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>&<?php echo http_build_query($_GET); ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                    <?php endfor; ?>
                    <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page+1; ?>&<?php echo http_build_query($_GET); ?>">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<style>
/* CSS riêng cho trang users */
.page-header {
    background: white;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 20px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
}

.page-header h4 {
    color: #333;
    font-weight: 600;
}

.page-header h4 i {
    font-size: 24px;
}

.card {
    border: none;
    border-radius: 10px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
}

.card-header {
    background: white;
    border-bottom: 1px solid #eee;
    padding: 15px 20px;
    font-weight: 600;
}

.table th {
    background: #f8f9fa;
    color: #495057;
    font-weight: 600;
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    border-bottom: 2px solid #dee2e6;
}

.table td {
    vertical-align: middle;
    color: #212529;
}

.btn-group .btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}

.badge {
    padding: 6px 10px;
    font-weight: 500;
}

.form-label {
    font-weight: 500;
    color: #495057;
    margin-bottom: 5px;
}

.form-control, .form-select {
    border-radius: 8px;
    border: 1px solid #e0e0e0;
    padding: 10px 15px;
}

.form-control:focus, .form-select:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102,126,234,0.1);
}

.alert {
    border: none;
    border-radius: 10px;
    padding: 15px 20px;
}

.alert-success {
    background: #d4edda;
    color: #155724;
}

.alert-danger {
    background: #f8d7da;
    color: #721c24;
}
</style>

<?php include "../includes/footer.php"; ?>