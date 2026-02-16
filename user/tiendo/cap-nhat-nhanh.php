<?php
// tiendo/cap-nhat-nhanh.php
require_once '../includes/header.php';

$user_id = $_SESSION['id'];

// Xử lý cập nhật hàng loạt
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['bulk_update'])) {
    
    if(!isset($_POST['chon']) || empty($_POST['chon'])) {
        $_SESSION['error'] = 'Vui lòng chọn ít nhất 1 hạng mục để cập nhật';
        header('Location: cap-nhat-nhanh.php');
        exit();
    }
    
    $selected_items = $_POST['chon'];
    $tien_do = $_POST['tien_do'] ?? [];
    $ghi_chu = $_POST['ghi_chu'] ?? [];
    $success_count = 0;
    $error_count = 0;
    
    foreach($selected_items as $hm_id) {
        $hm_id = (int)$hm_id;
        
        if(!isset($tien_do[$hm_id])) {
            continue;
        }
        
        $tien_do_moi = (int)$tien_do[$hm_id];
        $ghi_chu_text = mysqli_real_escape_string($conn, $ghi_chu[$hm_id] ?? '');
        
        // Lấy tiến độ cũ
        $sql_cu = "SELECT phan_tram_tien_do FROM hangmucthicong WHERE id = $hm_id";
        $result_cu = mysqli_query($conn, $sql_cu);
        
        if(mysqli_num_rows($result_cu) == 0) {
            $error_count++;
            continue;
        }
        
        $tien_do_cu = mysqli_fetch_assoc($result_cu)['phan_tram_tien_do'];
        
        // Xác định trạng thái mới
        if($tien_do_moi == 0) {
            $trang_thai = 'Chưa thi công';
        } elseif($tien_do_moi == 100) {
            $trang_thai = 'Hoàn thành';
        } else {
            $trang_thai = 'Đang thi công';
        }
        
        // Cập nhật
        $sql_update = "UPDATE hangmucthicong SET 
                       phan_tram_tien_do = $tien_do_moi,
                       trang_thai = '$trang_thai'
                       WHERE id = $hm_id";
        
        if(mysqli_query($conn, $sql_update)) {
            // Ghi lịch sử
            $sql_lichsu = "INSERT INTO lichsucapnhat (hangmuc_id, phan_tram_cu, phan_tram_moi, ghi_chu) 
                          VALUES ($hm_id, $tien_do_cu, $tien_do_moi, '$ghi_chu_text')";
            mysqli_query($conn, $sql_lichsu);
            $success_count++;
        } else {
            $error_count++;
        }
    }
    
    if($success_count > 0) {
        $_SESSION['success'] = "✅ Đã cập nhật $success_count hạng mục thành công!";
        if($error_count > 0) {
            $_SESSION['error'] = "❌ Có $error_count hạng mục cập nhật thất bại!";
        }
    } else {
        $_SESSION['error'] = "❌ Không có hạng mục nào được cập nhật!";
    }
    
    header('Location: cap-nhat-nhanh.php');
    exit();
}

// Lấy danh sách hạng mục
$sql = "SELECT hm.*, ct.ten_cong_trinh,
        DATEDIFF(hm.ngay_ket_thuc, CURDATE()) as so_ngay_con
        FROM hangmucthicong hm
        LEFT JOIN congtrinh ct ON hm.congtrinh_id = ct.id
        WHERE ct.user_id = '$user_id'
        AND hm.trang_thai != 'Hoàn thành'
        ORDER BY 
            CASE 
                WHEN hm.ngay_ket_thuc < CURDATE() THEN 1
                WHEN hm.ngay_ket_thuc BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 2
                ELSE 3
            END,
            hm.ngay_ket_thuc ASC";
$result = mysqli_query($conn, $sql);
?>

<div class="content-wrapper">
    <!-- Page Header -->
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h4 class="mb-1">
                    <i class="fas fa-bolt me-2 text-warning"></i>
                    Cập nhật tiến độ nhanh
                </h4>
                <p class="text-muted mb-0">Cập nhật tiến độ nhiều hạng mục cùng lúc</p>
            </div>
            <div class="mt-2 mt-sm-0">
                <a href="theo-doi.php" class="btn btn-info">
                    <i class="fas fa-eye me-2"></i>Theo dõi tiến độ
                </a>
            </div>
        </div>
    </div>

    <!-- Hiển thị thông báo -->
    <?php if(isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        <?php 
        echo $_SESSION['success'];
        unset($_SESSION['success']);
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?php if(isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i>
        <?php 
        echo $_SESSION['error'];
        unset($_SESSION['error']);
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?php if(mysqli_num_rows($result) == 0): ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i>
        Không có hạng mục nào cần cập nhật. Tất cả đã hoàn thành!
    </div>
    <?php else: ?>

    <!-- Form cập nhật nhanh -->
    <form method="POST" id="bulkUpdateForm">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>
                    <i class="fas fa-list me-2 text-warning"></i>
                    Danh sách hạng mục cần cập nhật
                </span>
                <div>
                    <button type="button" onclick="selectAll()" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-check-double me-1"></i>Chọn tất cả
                    </button>
                    <button type="button" onclick="setProgress(100)" class="btn btn-sm btn-outline-success">
                        <i class="fas fa-check-circle me-1"></i>Hoàn thành
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th width="40">
                                    <input type="checkbox" id="selectAll" onclick="toggleAll(this)">
                                </th>
                                <th>Công trình</th>
                                <th>Hạng mục</th>
                                <th width="200">Tiến độ hiện tại</th>
                                <th width="250">Tiến độ mới</th>
                                <th width="200">Hạn chót</th>
                                <th width="250">Ghi chú</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($hm = mysqli_fetch_assoc($result)): 
                                $is_overdue = $hm['so_ngay_con'] < 0;
                                $row_class = $is_overdue ? 'table-danger' : '';
                            ?>
                            <tr class="<?php echo $row_class; ?>">
                                <td>
                                    <input type="checkbox" name="chon[]" value="<?php echo $hm['id']; ?>" 
                                           class="row-checkbox" onchange="updateSelectedCount()">
                                </td>
                                <td>
                                    <a href="../congtrinh/detail.php?id=<?php echo $hm['congtrinh_id']; ?>" 
                                       class="text-decoration-none">
                                        <?php echo htmlspecialchars($hm['ten_cong_trinh']); ?>
                                    </a>
                                </td>
                                <td>
                                    <a href="../hangmuc/detail.php?id=<?php echo $hm['id']; ?>" 
                                       class="text-decoration-none fw-bold">
                                        <?php echo htmlspecialchars($hm['ten_hang_muc']); ?>
                                    </a>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress flex-grow-1" style="height: 8px;">
                                            <div class="progress-bar bg-<?php echo getProgressColor($hm['phan_tram_tien_do']); ?>" 
                                                 style="width: <?php echo $hm['phan_tram_tien_do']; ?>%"></div>
                                        </div>
                                        <span class="badge bg-<?php echo getProgressColor($hm['phan_tram_tien_do']); ?>">
                                            <?php echo $hm['phan_tram_tien_do']; ?>%
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <input type="range" name="tien_do[<?php echo $hm['id']; ?>]" 
                                               class="form-range progress-range" 
                                               min="0" max="100" value="<?php echo $hm['phan_tram_tien_do']; ?>"
                                               onchange="updateProgressValue(this, <?php echo $hm['id']; ?>)">
                                        <span class="badge bg-primary" id="value_<?php echo $hm['id']; ?>" 
                                              style="min-width: 45px;">
                                            <?php echo $hm['phan_tram_tien_do']; ?>%
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <small>
                                        <?php echo date('d/m/Y', strtotime($hm['ngay_ket_thuc'])); ?>
                                        <?php if($is_overdue): ?>
                                            <span class="badge bg-danger d-block mt-1">
                                                Quá <?php echo abs($hm['so_ngay_con']); ?> ngày
                                            </span>
                                        <?php elseif($hm['so_ngay_con'] <= 7): ?>
                                            <span class="badge bg-warning d-block mt-1">
                                                Còn <?php echo $hm['so_ngay_con']; ?> ngày
                                            </span>
                                        <?php endif; ?>
                                    </small>
                                </td>
                                <td>
                                    <input type="text" name="ghi_chu[<?php echo $hm['id']; ?>]" 
                                           class="form-control form-control-sm" 
                                           placeholder="Ghi chú cập nhật...">
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span id="selectedCount" class="badge bg-primary">0</span> hạng mục được chọn
                    </div>
                    <div>
                        <button type="submit" name="bulk_update" class="btn btn-primary px-5">
                            <i class="fas fa-save me-2"></i>Cập nhật các mục đã chọn
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
    <?php endif; ?>
</div>

<script>
function updateProgressValue(range, id) {
    document.getElementById('value_' + id).textContent = range.value + '%';
    
    const badge = document.getElementById('value_' + id);
    badge.className = 'badge ';
    if(range.value == 0) {
        badge.classList.add('bg-secondary');
    } else if(range.value == 100) {
        badge.classList.add('bg-success');
    } else {
        badge.classList.add('bg-warning');
    }
}

function toggleAll(checkbox) {
    const checkboxes = document.querySelectorAll('.row-checkbox');
    checkboxes.forEach(cb => {
        cb.checked = checkbox.checked;
    });
    updateSelectedCount();
}

function selectAll() {
    document.querySelectorAll('.row-checkbox').forEach(cb => {
        cb.checked = true;
    });
    document.getElementById('selectAll').checked = true;
    updateSelectedCount();
}

function updateSelectedCount() {
    const checkboxes = document.querySelectorAll('.row-checkbox:checked');
    const count = checkboxes.length;
    document.getElementById('selectedCount').textContent = count;
    
    // Cập nhật trạng thái checkbox "Chọn tất cả"
    const totalCheckboxes = document.querySelectorAll('.row-checkbox').length;
    const selectAllCheckbox = document.getElementById('selectAll');
    
    if(selectAllCheckbox) {
        if(count === 0) {
            selectAllCheckbox.checked = false;
            selectAllCheckbox.indeterminate = false;
        } else if(count === totalCheckboxes) {
            selectAllCheckbox.checked = true;
            selectAllCheckbox.indeterminate = false;
        } else {
            selectAllCheckbox.checked = false;
            selectAllCheckbox.indeterminate = true;
        }
    }
}

function setProgress(value) {
    document.querySelectorAll('.row-checkbox:checked').forEach(cb => {
        const row = cb.closest('tr');
        const range = row.querySelector('.progress-range');
        if(range) {
            range.value = value;
            const id = range.name.match(/\d+/)[0];
            updateProgressValue(range, id);
        }
    });
}

// Kiểm tra trước khi submit
document.getElementById('bulkUpdateForm').addEventListener('submit', function(e) {
    const checkboxes = document.querySelectorAll('.row-checkbox:checked');
    
    if(checkboxes.length === 0) {
        e.preventDefault();
        alert('Vui lòng chọn ít nhất 1 hạng mục để cập nhật!');
        return false;
    }
    
    if(!confirm('Bạn có chắc muốn cập nhật ' + checkboxes.length + ' hạng mục đã chọn?')) {
        e.preventDefault();
        return false;
    }
    
    return true;
});

// Auto update khi load
document.addEventListener('DOMContentLoaded', function() {
    updateSelectedCount();
    
    // Thêm sự kiện cho tất cả checkbox
    document.querySelectorAll('.row-checkbox').forEach(cb => {
        cb.addEventListener('change', updateSelectedCount);
    });
});
</script>

<style>
/* Thêm style cho indeterminate state */
#selectAll:indeterminate {
    background-color: #fbbf24;
    border-color: #fbbf24;
}

.progress-range {
    width: 150px;
    cursor: pointer;
}

.table td {
    vertical-align: middle;
    padding: 1rem 0.75rem;
}

.table-danger {
    background-color: rgba(239, 68, 68, 0.05);
}

.table-danger:hover {
    background-color: rgba(239, 68, 68, 0.1) !important;
}

.badge {
    font-weight: 500;
    padding: 0.4em 0.8em;
    border-radius: 999px;
}

.btn-group .btn {
    padding: 0.25rem 0.5rem;
    border-radius: 6px !important;
    margin: 0 2px;
}

.card-header .btn {
    margin-left: 5px;
}

/* Animation cho badge */
.badge {
    transition: all 0.3s ease;
}

/* Style cho progress bar */
.progress {
    background: #f1f5f9;
    border-radius: 999px;
    overflow: hidden;
}

.progress-bar {
    transition: width 0.6s ease;
    border-radius: 999px;
}
</style>

<?php require_once '../includes/footer.php'; ?>