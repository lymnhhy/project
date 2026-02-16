<?php
// tiendo/theo-doi.php
require_once '../includes/header.php';

$user_id = $_SESSION['id'];

// Lấy tất cả công trình và hạng mục
$sql = "SELECT 
            ct.id as ct_id,
            ct.ten_cong_trinh,
            ct.ngay_bat_dau,
            ct.ngay_ket_thuc,
            ct.trangthaiCT_id,
            hm.id as hm_id,
            hm.ten_hang_muc,
            hm.phan_tram_tien_do,
            hm.trang_thai,
            hm.ngay_bat_dau as hm_bat_dau,
            hm.ngay_ket_thuc as hm_ket_thuc,
            DATEDIFF(hm.ngay_ket_thuc, CURDATE()) as so_ngay_con
        FROM congtrinh ct
        LEFT JOIN hangmucthicong hm ON ct.id = hm.congtrinh_id
        WHERE ct.user_id = '$user_id'
        ORDER BY 
            CASE 
                WHEN ct.trangthaiCT_id != 3 AND ct.ngay_ket_thuc < CURDATE() THEN 1
                WHEN ct.trangthaiCT_id = 2 THEN 2
                ELSE 3
            END,
            ct.ngay_ket_thuc ASC,
            hm.ngay_ket_thuc ASC";
$result = mysqli_query($conn, $sql);

// Nhóm dữ liệu theo công trình
$congtrinh_list = [];
while($row = mysqli_fetch_assoc($result)) {
    $ct_id = $row['ct_id'];
    if(!isset($congtrinh_list[$ct_id])) {
        $congtrinh_list[$ct_id] = [
            'ten_cong_trinh' => $row['ten_cong_trinh'],
            'ngay_bat_dau' => $row['ngay_bat_dau'],
            'ngay_ket_thuc' => $row['ngay_ket_thuc'],
            'trangthaiCT_id' => $row['trangthaiCT_id'],
            'hangmuc' => []
        ];
    }
    if($row['hm_id']) {
        $congtrinh_list[$ct_id]['hangmuc'][] = [
            'id' => $row['hm_id'],
            'ten' => $row['ten_hang_muc'],
            'tien_do' => $row['phan_tram_tien_do'],
            'trang_thai' => $row['trang_thai'],
            'ngay_ket_thuc' => $row['hm_ket_thuc'],
            'so_ngay_con' => $row['so_ngay_con']
        ];
    }
}
?>

<div class="content-wrapper">
    <!-- Page Header -->
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h4 class="mb-1">
                    <i class="fas fa-eye me-2 text-warning"></i>
                    Theo dõi tiến độ tổng thể
                </h4>
                <p class="text-muted mb-0">Xem tiến độ tất cả công trình và hạng mục</p>
            </div>
            <div class="mt-2 mt-sm-0">
                <a href="cap-nhat-nhanh.php" class="btn btn-primary">
                    <i class="fas fa-bolt me-2"></i>Cập nhật nhanh
                </a>
                <button onclick="exportProgress()" class="btn btn-success">
                    <i class="fas fa-file-excel me-2"></i>Xuất báo cáo
                </button>
            </div>
        </div>
    </div>

    <!-- Bộ lọc nhanh -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <select id="filterTrangThai" class="form-select">
                        <option value="">Tất cả trạng thái</option>
                        <option value="dangtc">Đang thi công</option>
                        <option value="hoanthanh">Hoàn thành</option>
                        <option value="chuatc">Chưa thi công</option>
                        <option value="quahan">Quá hạn</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select id="filterCongTrinh" class="form-select">
                        <option value="">Tất cả công trình</option>
                        <?php foreach($congtrinh_list as $id => $ct): ?>
                        <option value="<?php echo $id; ?>"><?php echo $ct['ten_cong_trinh']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <input type="text" id="searchHangMuc" class="form-control" placeholder="Tìm hạng mục...">
                </div>
                <div class="col-md-3">
                    <button onclick="applyFilters()" class="btn btn-primary w-100">
                        <i class="fas fa-filter me-2"></i>Áp dụng
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Danh sách công trình và hạng mục -->
    <div class="accordion" id="accordionCongTrinh">
        <?php foreach($congtrinh_list as $ct_id => $ct): 
            $tien_do_ct = tinhTienDoCongTrinh($conn, $ct_id);
            $is_overdue_ct = ($ct['trangthaiCT_id'] != 3 && strtotime($ct['ngay_ket_thuc']) < time());
        ?>
        <div class="accordion-item mb-3 congtrinh-item" data-id="<?php echo $ct_id; ?>">
            <h2 class="accordion-header" id="heading<?php echo $ct_id; ?>">
                <button class="accordion-button <?php echo $is_overdue_ct ? 'bg-danger text-white' : ''; ?>" 
                        type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $ct_id; ?>">
                    <div class="d-flex justify-content-between align-items-center w-100 me-3">
                        <div>
                            <i class="fas fa-building me-2"></i>
                            <strong><?php echo htmlspecialchars($ct['ten_cong_trinh']); ?></strong>
                            <span class="badge bg-<?php echo getStatusBadgeByID($ct['trangthaiCT_id']); ?> ms-2">
                                <?php echo getTrangThaiByID($ct['trangthaiCT_id']); ?>
                            </span>
                        </div>
                        <div class="d-flex gap-3">
                            <span class="badge bg-info">Tiến độ: <?php echo $tien_do_ct; ?>%</span>
                            <span class="badge bg-secondary">HM: <?php echo count($ct['hangmuc']); ?></span>
                            <?php if($is_overdue_ct): ?>
                            <span class="badge bg-warning text-dark">Quá hạn</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </button>
            </h2>
            <div id="collapse<?php echo $ct_id; ?>" class="accordion-collapse collapse show">
                <div class="accordion-body p-0">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Hạng mục</th>
                                <th width="200">Tiến độ</th>
                                <th width="150">Trạng thái</th>
                                <th width="200">Kế hoạch</th>
                                <th width="100">Còn lại</th>
                                <th width="100">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($ct['hangmuc'] as $hm): 
                                $is_overdue_hm = ($hm['trang_thai'] != 'Hoàn thành' && $hm['so_ngay_con'] < 0);
                            ?>
                            <tr class="hangmuc-item" 
                                data-trangthai="<?php echo $hm['trang_thai']; ?>"
                                data-quahan="<?php echo $is_overdue_hm ? '1' : '0'; ?>">
                                <td>
                                    <a href="../hangmuc/detail.php?id=<?php echo $hm['id']; ?>" class="text-decoration-none">
                                        <?php echo htmlspecialchars($hm['ten']); ?>
                                    </a>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress flex-grow-1" style="height: 8px;">
                                            <div class="progress-bar bg-<?php echo getProgressColor($hm['tien_do']); ?>" 
                                                 style="width: <?php echo $hm['tien_do']; ?>%"></div>
                                        </div>
                                        <span class="badge bg-<?php echo getProgressColor($hm['tien_do']); ?>">
                                            <?php echo $hm['tien_do']; ?>%
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $hm['trang_thai'] == 'Hoàn thành' ? 'success' : 
                                            ($hm['trang_thai'] == 'Đang thi công' ? 'warning' : 'secondary'); 
                                    ?>">
                                        <?php echo $hm['trang_thai']; ?>
                                    </span>
                                    <?php if($is_overdue_hm): ?>
                                        <span class="badge bg-danger mt-1">Quá hạn</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small><?php echo date('d/m/Y', strtotime($hm['ngay_ket_thuc'])); ?></small>
                                </td>
                                <td>
                                    <?php if(!$is_overdue_hm && $hm['so_ngay_con'] > 0): ?>
                                        <span class="badge bg-info"><?php echo $hm['so_ngay_con']; ?> ngày</span>
                                    <?php elseif($is_overdue_hm): ?>
                                        <span class="badge bg-danger">-<?php echo abs($hm['so_ngay_con']); ?> ngày</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Hết hạn</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="../hangmuc/update.php?id=<?php echo $hm['id']; ?>" 
                                       class="btn btn-sm btn-primary">
                                        <i class="fas fa-pen"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
function applyFilters() {
    const filterTrangThai = document.getElementById('filterTrangThai').value;
    const filterCongTrinh = document.getElementById('filterCongTrinh').value;
    const searchText = document.getElementById('searchHangMuc').value.toLowerCase();
    
    document.querySelectorAll('.congtrinh-item').forEach(ct => {
        if(filterCongTrinh && ct.dataset.id !== filterCongTrinh) {
            ct.style.display = 'none';
            return;
        } else {
            ct.style.display = '';
        }
        
        let hasVisibleItems = false;
        ct.querySelectorAll('.hangmuc-item').forEach(hm => {
            let show = true;
            
            // Lọc theo trạng thái
            if(filterTrangThai) {
                if(filterTrangThai === 'quahan') {
                    show = hm.dataset.quahan === '1';
                } else if(filterTrangThai === 'dangtc') {
                    show = hm.dataset.trangthai === 'Đang thi công';
                } else if(filterTrangThai === 'hoanthanh') {
                    show = hm.dataset.trangthai === 'Hoàn thành';
                } else if(filterTrangThai === 'chuatc') {
                    show = hm.dataset.trangthai === 'Chưa thi công';
                }
            }
            
            // Lọc theo tìm kiếm
            if(searchText && show) {
                const tenHM = hm.querySelector('td:first-child').textContent.toLowerCase();
                show = tenHM.includes(searchText);
            }
            
            hm.style.display = show ? '' : 'none';
            if(show) hasVisibleItems = true;
        });
        
        ct.style.display = hasVisibleItems ? '' : 'none';
    });
}

function exportProgress() {
    window.location.href = 'export.php';
}

// Event listeners
document.getElementById('filterTrangThai').addEventListener('change', applyFilters);
document.getElementById('filterCongTrinh').addEventListener('change', applyFilters);
document.getElementById('searchHangMuc').addEventListener('keyup', applyFilters);
</script>

<?php require_once '../includes/footer.php'; ?>