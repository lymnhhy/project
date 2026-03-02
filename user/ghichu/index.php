<?php
// user/ghichu/index.php
$page_title = 'Danh sách ghi chú thi công';
require_once '../includes/header.php';

$user_id = $_SESSION['id'];
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Xử lý bộ lọc
$where = "WHERE gc.user_id = '$user_id'";
$params = [];

if(isset($_GET['congtrinh_id']) && !empty($_GET['congtrinh_id'])) {
    $congtrinh_id = (int)$_GET['congtrinh_id'];
    $where .= " AND ct.id = $congtrinh_id";
}

if(isset($_GET['hangmuc_id']) && !empty($_GET['hangmuc_id'])) {
    $hangmuc_id = (int)$_GET['hangmuc_id'];
    $where .= " AND gc.hangmuc_id = $hangmuc_id";
}

if(isset($_GET['tu_ngay']) && !empty($_GET['tu_ngay'])) {
    $tu_ngay = mysqli_real_escape_string($conn, $_GET['tu_ngay']);
    $where .= " AND DATE(gc.ngay_ghi) >= '$tu_ngay'";
}

if(isset($_GET['den_ngay']) && !empty($_GET['den_ngay'])) {
    $den_ngay = mysqli_real_escape_string($conn, $_GET['den_ngay']);
    $where .= " AND DATE(gc.ngay_ghi) <= '$den_ngay'";
}

if(isset($_GET['keyword']) && !empty($_GET['keyword'])) {
    $keyword = mysqli_real_escape_string($conn, $_GET['keyword']);
    $where .= " AND (gc.noi_dung LIKE '%$keyword%')";
}

// Đếm tổng số bản ghi
$sql_count = "SELECT COUNT(*) as total 
              FROM ghichuthicong gc
              LEFT JOIN hangmucthicong hm ON gc.hangmuc_id = hm.id
              LEFT JOIN congtrinh ct ON hm.congtrinh_id = ct.id
              $where";
$result_count = mysqli_query($conn, $sql_count);
$total_rows = mysqli_fetch_assoc($result_count)['total'];
$total_pages = ceil($total_rows / $limit);

// Lấy danh sách ghi chú
$sql = "SELECT gc.*, hm.ten_hang_muc, ct.ten_cong_trinh, u.hoten as nguoi_ghi,
        DATEDIFF(CURDATE(), gc.ngay_ghi) as so_ngay_truoc
        FROM ghichuthicong gc
        LEFT JOIN hangmucthicong hm ON gc.hangmuc_id = hm.id
        LEFT JOIN congtrinh ct ON hm.congtrinh_id = ct.id
        LEFT JOIN users u ON gc.user_id = u.id
        $where
        ORDER BY gc.ngay_ghi DESC, gc.created_at DESC
        LIMIT $offset, $limit";
$result = mysqli_query($conn, $sql);

// Lấy danh sách công trình cho filter
$sql_ct = "SELECT id, ten_cong_trinh FROM congtrinh WHERE user_id = '$user_id' ORDER BY ten_cong_trinh";
$congtrinh_list = mysqli_query($conn, $sql_ct);

// Lấy danh sách hạng mục cho filter (nếu có chọn công trình)
$hangmuc_list = null;
if(isset($_GET['congtrinh_id']) && !empty($_GET['congtrinh_id'])) {
    $ct_id = (int)$_GET['congtrinh_id'];
    $sql_hm = "SELECT id, ten_hang_muc FROM hangmucthicong WHERE congtrinh_id = $ct_id ORDER BY ten_hang_muc";
    $hangmuc_list = mysqli_query($conn, $sql_hm);
}
?>

<div class="content-wrapper">
    <!-- Page Header -->
    <div class="page-header d-flex justify-content-between align-items-center flex-wrap">
        <div>
            <h4 class="mb-1">
                <i class="fas fa-sticky-note me-2 text-warning"></i>
                Ghi chú thi công
            </h4>
            <p class="text-muted mb-0">Quản lý nhật ký, biên bản, ghi chú công trình</p>
        </div>
        <div class="mt-2 mt-sm-0">
            <a href="add.php" class="btn btn-primary">
                <i class="fas fa-plus-circle me-2"></i>Thêm ghi chú
            </a>
        </div>
    </div>

    <!-- Bộ lọc -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-2">
                    <select name="congtrinh_id" class="form-select" id="congtrinh_filter">
                        <option value="">Tất cả công trình</option>
                        <?php while($ct = mysqli_fetch_assoc($congtrinh_list)): ?>
                        <option value="<?php echo $ct['id']; ?>" 
                            <?php echo ($_GET['congtrinh_id'] ?? '') == $ct['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($ct['ten_cong_trinh']); ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <select name="hangmuc_id" class="form-select" id="hangmuc_filter">
                        <option value="">Tất cả hạng mục</option>
                        <?php if($hangmuc_list): ?>
                            <?php while($hm = mysqli_fetch_assoc($hangmuc_list)): ?>
                            <option value="<?php echo $hm['id']; ?>" 
                                <?php echo ($_GET['hangmuc_id'] ?? '') == $hm['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($hm['ten_hang_muc']); ?>
                            </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <input type="date" name="tu_ngay" class="form-control" 
                           value="<?php echo $_GET['tu_ngay'] ?? ''; ?>" 
                           placeholder="Từ ngày">
                </div>
                
                <div class="col-md-2">
                    <input type="date" name="den_ngay" class="form-control" 
                           value="<?php echo $_GET['den_ngay'] ?? ''; ?>" 
                           placeholder="Đến ngày">
                </div>
                
                <div class="col-md-2">
                    <input type="text" name="keyword" class="form-control" 
                           value="<?php echo $_GET['keyword'] ?? ''; ?>" 
                           placeholder="Tìm nội dung...">
                </div>
                
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter me-2"></i>Lọc
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Danh sách ghi chú -->
    <div class="card">
        <div class="card-body p-0">
            <?php if(mysqli_num_rows($result) == 0): ?>
            <div class="text-center py-5">
                <i class="fas fa-sticky-note fa-4x text-muted mb-3"></i>
                <h6 class="text-muted">Chưa có ghi chú nào</h6>
                <p class="text-muted mb-3">Bắt đầu thêm ghi chú thi công đầu tiên</p>
                <a href="add.php" class="btn btn-primary">
                    <i class="fas fa-plus-circle me-2"></i>Thêm ghi chú
                </a>
            </div>
            <?php else: ?>
                <?php while($gc = mysqli_fetch_assoc($result)): 
                    $ngay_class = '';
                    $ngay_text = '';
                    
                    if($gc['so_ngay_truoc'] == 0) {
                        $ngay_class = 'badge bg-success';
                        $ngay_text = 'Hôm nay';
                    } elseif($gc['so_ngay_truoc'] == 1) {
                        $ngay_class = 'badge bg-info';
                        $ngay_text = 'Hôm qua';
                    } elseif($gc['so_ngay_truoc'] <= 7) {
                        $ngay_class = 'badge bg-warning';
                        $ngay_text = $gc['so_ngay_truoc'] . ' ngày trước';
                    } else {
                        $ngay_class = 'badge bg-secondary';
                        $ngay_text = date('d/m/Y', strtotime($gc['ngay_ghi']));
                    }
                ?>
                <div class="note-item p-3 border-bottom">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <h5 class="mb-1">
                                <a href="detail.php?id=<?php echo $gc['id']; ?>" class="text-decoration-none">
                                    <?php echo htmlspecialchars($gc['ten_cong_trinh']); ?>
                                </a>
                                <small class="text-muted">/ <?php echo htmlspecialchars($gc['ten_hang_muc']); ?></small>
                            </h5>
                            <div class="mb-2">
                                <span class="<?php echo $ngay_class; ?> me-2">
                                    <i class="far fa-calendar-alt me-1"></i><?php echo $ngay_text; ?>
                                </span>
                                <span class="badge bg-secondary">
                                    <i class="far fa-clock me-1"></i><?php echo date('H:i', strtotime($gc['created_at'])); ?>
                                </span>
                                <?php if(!empty($gc['hinh_anh'])): ?>
                                <span class="badge bg-info">
                                    <i class="fas fa-image me-1"></i>Có ảnh
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div>
                            <span class="text-muted small">
                                <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($gc['nguoi_ghi']); ?>
                            </span>
                        </div>
                    </div>
                    
                    <p class="mb-2"><?php echo nl2br(htmlspecialchars(substr($gc['noi_dung'], 0, 200))); ?>
                        <?php if(strlen($gc['noi_dung']) > 200): ?>
                        ... <a href="detail.php?id=<?php echo $gc['id']; ?>" class="text-primary">Xem thêm</a>
                        <?php endif; ?>
                    </p>
                    
                    <?php if(!empty($gc['hinh_anh'])): ?>
                    <div class="mb-2">
                        <img src="<?php echo BASE_URL . '/' . $gc['hinh_anh']; ?>" 
                             alt="note image" 
                             style="max-height: 100px; max-width: 150px;" 
                             class="img-thumbnail">
                    </div>
                    <?php endif; ?>
                    
                    <div class="d-flex justify-content-end gap-2">
                        <a href="detail.php?id=<?php echo $gc['id']; ?>" class="btn btn-sm btn-outline-info">
                            <i class="fas fa-eye"></i> Chi tiết
                        </a>
                        <a href="edit.php?id=<?php echo $gc['id']; ?>" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-edit"></i> Sửa
                        </a>
                        <button onclick="confirmDelete1(<?php echo $gc['id']; ?>)" class="btn btn-sm btn-outline-danger">
                            <i class="fas fa-trash"></i> Xóa
                        </button>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php endif; ?>
        </div>
        
        <!-- Phân trang -->
        <?php if($total_pages > 1): ?>
        <div class="card-footer">
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center mb-0">
                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page-1; ?><?php echo $query_string; ?>">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    </li>
                    <?php for($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?><?php echo $query_string; ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                    <?php endfor; ?>
                    <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page+1; ?><?php echo $query_string; ?>">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Filter hạng mục theo công trình
document.getElementById('congtrinh_filter').addEventListener('change', function() {
    var ct_id = this.value;
    var hm_select = document.getElementById('hangmuc_filter');
    
    if(ct_id) {
        // Gọi AJAX để lấy danh sách hạng mục
        fetch('<?php echo BASE_URL; ?>/user/api/get_hangmuc.php?congtrinh_id=' + ct_id)
            .then(response => response.json())
            .then(data => {
                hm_select.innerHTML = '<option value="">Tất cả hạng mục</option>';
                data.forEach(function(item) {
                    hm_select.innerHTML += '<option value="' + item.id + '">' + item.ten_hang_muc + '</option>';
                });
            });
    } else {
        hm_select.innerHTML = '<option value="">Tất cả hạng mục</option>';
    }
});

function confirmDelete1(id) {
    Swal.fire({
        title: 'Xác nhận xóa',
        text: 'Bạn có chắc muốn xóa ghi chú này?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Xóa',
        cancelButtonText: 'Hủy'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'delete.php?id=' + id;
        }
    });
}
</script>

<style>
.note-item {
    transition: background-color 0.3s;
}
.note-item:hover {
    background-color: #f8f9fa;
}
</style>

<?php require_once '../includes/footer.php'; ?>