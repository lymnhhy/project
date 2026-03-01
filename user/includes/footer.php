<?php
// user/includes/footer.php
// Lấy lại thông tin cấu hình nếu chưa có
if(!isset($config)) {
    global $conn;
    $sql_config = "SELECT * FROM cauhinhweb WHERE id = 1";
    $result_config = mysqli_query($conn, $sql_config);
    $config = mysqli_fetch_assoc($result_config);
    
    // Mặc định nếu chưa có
    if(!$config) {
        $config = [
            'ten_website' => 'ProTrack',
            'slogan' => 'Hệ thống theo dõi dự án chuyên nghiệp',
            'so_dien_thoai' => '',
            'email' => '',
            'dia_chi' => '',
            'website' => '',
            'facebook' => '',
            'zalo' => ''
        ];
    }
}
?>
        </div> <!-- End content-wrapper -->
        
        <!-- Footer -->
        <footer class="footer">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-4">
                        <h6 class="text-primary"><i class="fas fa-info-circle me-2"></i>Thông tin liên hệ</h6>
                        <ul class="list-unstyled">
                            <?php if(!empty($config['so_dien_thoai'])): ?>
                            <li class="mb-2">
                                <i class="fas fa-phone-alt me-2 text-success"></i>
                                <strong>Điện thoại:</strong> <?php echo htmlspecialchars($config['so_dien_thoai']); ?>
                            </li>
                            <?php endif; ?>
                            
                            <?php if(!empty($config['email'])): ?>
                            <li class="mb-2">
                                <i class="fas fa-envelope me-2 text-info"></i>
                                <strong>Email:</strong> 
                                <a href="mailto:<?php echo $config['email']; ?>" class="text-decoration-none">
                                    <?php echo htmlspecialchars($config['email']); ?>
                                </a>
                            </li>
                            <?php endif; ?>
                            
                            <?php if(!empty($config['dia_chi'])): ?>
                            <li class="mb-2">
                                <i class="fas fa-map-marker-alt me-2 text-danger"></i>
                                <strong>Địa chỉ:</strong> <?php echo htmlspecialchars($config['dia_chi']); ?>
                            </li>
                            <?php endif; ?>
                            
                            <?php if(!empty($config['website'])): ?>
                            <li class="mb-2">
                                <i class="fas fa-globe me-2 text-primary"></i>
                                <strong>Website:</strong> 
                                <a href="<?php echo $config['website']; ?>" target="_blank">
                                    <?php echo htmlspecialchars($config['website']); ?>
                                </a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    
                    <div class="col-md-4">
                        <h6 class="text-primary"><i class="fas fa-share-alt me-2"></i>Kết nối với chúng tôi</h6>
                        <div class="social-links">
                            <?php if(!empty($config['facebook'])): ?>
                            <a href="<?php echo $config['facebook']; ?>" target="_blank" class="btn btn-sm btn-outline-primary mb-2 me-2">
                                <i class="fab fa-facebook-f me-1"></i> Facebook
                            </a>
                            <?php endif; ?>
                            
                            <?php if(!empty($config['zalo'])): ?>
                            <a href="https://zalo.me/<?php echo $config['zalo']; ?>" target="_blank" class="btn btn-sm btn-outline-info mb-2 me-2">
                                <i class="fas fa-comment me-1"></i> Zalo
                            </a>
                            <?php endif; ?>
                        </div>
                        
                        <h6 class="text-primary mt-3"><i class="fas fa-clock me-2"></i>Giờ làm việc</h6>
                        <ul class="list-unstyled">
                            <li><i class="far fa-calendar-alt me-2 text-secondary"></i> Thứ 2 - Thứ 6: 8:00 - 17:30</li>
                            <li><i class="far fa-calendar-alt me-2 text-secondary"></i> Thứ 7: 8:00 - 12:00</li>
                            <li><i class="far fa-calendar-alt me-2 text-secondary"></i> Chủ nhật: Nghỉ</li>
                        </ul>
                    </div>
                    
                    <div class="col-md-4">
                        <h6 class="text-primary"><i class="fas fa-building me-2"></i>Giới thiệu</h6>
                        <p class="text-muted small">
                            <?php echo htmlspecialchars($config['mo_ta'] ?? 'ProTrack - Hệ thống theo dõi tiến độ dự án chuyên nghiệp, giúp quản lý công trình hiệu quả.'); ?>
                        </p>
                        
                        <hr>
                        
                        <div class="d-flex justify-content-between align-items-center">
                            <p class="mb-0">
                                <i class="far fa-copyright me-1"></i> <?php echo date('Y'); ?> 
                                <strong><?php echo htmlspecialchars($config['ten_website'] ?? 'ProTrack'); ?></strong>
                            </p>
                            <p class="mb-0">
                                <i class="fas fa-code-branch me-1"></i> v1.0.0
                            </p>
                        </div>
                        <p class="text-muted small mb-0 mt-2">
                            <?php echo htmlspecialchars($config['slogan'] ?? 'Hệ thống theo dõi dự án chuyên nghiệp'); ?>
                        </p>
                    </div>
                </div>
            </div>
        </footer>
    </div> <!-- End main-content -->

    <!-- JavaScript Libraries (giữ nguyên của bạn) -->
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.4.1/js/responsive.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/vn.js"></script>
    
    <script>
        // Cấu hình chung
        const BASE_URL = '<?php echo BASE_URL; ?>';
        
        // Toggle sidebar
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('show');
        }
        
        // Đóng sidebar khi click ra ngoài (mobile)
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const toggler = document.querySelector('.navbar-toggler');
            
            if (window.innerWidth < 992) {
                if (!sidebar.contains(event.target) && !toggler.contains(event.target)) {
                    sidebar.classList.remove('show');
                }
            }
        });
        
        // Global search
        $('#globalSearch').on('keyup', function() {
            const value = $(this).val().toLowerCase();
            
            // Tìm trong tất cả các bảng
            $('.table tbody tr').each(function() {
                const text = $(this).text().toLowerCase();
                $(this).toggle(text.indexOf(value) > -1);
            });
            
            // Hiển thị thông báo nếu không có kết quả
            if ($('.table tbody tr:visible').length === 0) {
                if ($('.table tbody .no-search-result').length === 0) {
                    const colCount = $('.table thead th').length;
                    $('.table tbody').append(`
                        <tr class="no-search-result">
                            <td colspan="${colCount}" class="text-center py-4">
                                <i class="fas fa-search fa-2x text-muted mb-2"></i>
                                <p class="text-muted mb-0">Không tìm thấy kết quả phù hợp</p>
                            </td>
                        </tr>
                    `);
                }
            } else {
                $('.no-search-result').remove();
            }
        });
        
        // Auto close alert after 5 seconds
        $(document).ready(function() {
            setTimeout(function() {
                $('.alert').fadeOut('slow', function() {
                    $(this).remove();
                });
            }, 5000);
        });
        
        // DataTable configuration
        $.extend(true, $.fn.dataTable.defaults, {
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/vi.json'
            },
            pageLength: 10,
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "Tất cả"]],
            responsive: true,
            autoWidth: false,
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip',
            initComplete: function() {
                this.api().search('');
                $('.dataTables_filter').hide();
            }
        });
        
        // Initialize DataTable if exists
        $(document).ready(function() {
            if ($('#dataTable').length > 0) {
                $('#dataTable').DataTable();
            }
        });
        
        // Initialize Select2
        $(document).ready(function() {
            $('.select2').select2({
                theme: 'bootstrap-5',
                language: 'vi',
                width: '100%',
                placeholder: '-- Chọn --',
                allowClear: true
            });
        });
        
        // Initialize Flatpickr
        $(document).ready(function() {
            $('.datepicker').flatpickr({
                locale: 'vn',
                dateFormat: 'd/m/Y',
                altInput: true,
                altFormat: 'd/m/Y'
            });
            
            $('.datetimepicker').flatpickr({
                locale: 'vn',
                enableTime: true,
                dateFormat: 'd/m/Y H:i',
                altInput: true,
                altFormat: 'd/m/Y H:i'
            });
        });
        
        // Confirm delete
// Trong footer.php, tìm hàm confirmDelete và sửa thành:
function confirmDelete(id, message = 'Bạn có chắc muốn xóa?') {
    Swal.fire({
        title: 'Xác nhận',
        text: message,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Xóa',
        cancelButtonText: 'Hủy',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            // SỬA Ở ĐÂY: thêm tham số id vào URL
            window.location.href = BASE_URL + '/user/congtrinh/delete.php?id=' + id;
        }
    });
    return false;
}
        
        // Show success message
        function showSuccess(message) {
            Swal.fire({
                icon: 'success',
                title: 'Thành công!',
                text: message,
                timer: 2000,
                showConfirmButton: false,
                position: 'top-end',
                toast: true
            });
        }
        
        // Show error message
        function showError(message) {
            Swal.fire({
                icon: 'error',
                title: 'Lỗi!',
                text: message,
                confirmButtonText: 'Đóng',
                confirmButtonColor: '#ef4444'
            });
        }
        
        // Show loading
        function showLoading() {
            Swal.fire({
                title: 'Đang xử lý...',
                allowOutsideClick: false,
                showConfirmButton: false,
                willOpen: () => {
                    Swal.showLoading();
                }
            });
        }
        
        // Format currency
        function formatCurrency(amount) {
            return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(amount);
        }
        
        // Format date
        function formatDate(dateStr) {
            if (!dateStr) return 'Chưa cập nhật';
            const date = new Date(dateStr);
            return date.toLocaleDateString('vi-VN');
        }
        
        // Update breadcrumb
        function updateBreadcrumb() {
            const path = window.location.pathname;
            const parts = path.split('/').filter(p => p);
            const breadcrumb = $('#breadcrumb');
            
            breadcrumb.empty();
            
            // Home
            breadcrumb.append('<li class="breadcrumb-item"><a href="' + BASE_URL + '/user/dashboard.php">Trang chủ</a></li>');
            
            // Process parts
            let currentPath = '';
            parts.forEach((part, index) => {
                if (part === 'project' || part === 'user') return;
                
                currentPath += '/' + part;
                
                if (index === parts.length - 1) {
                    const title = part.replace('.php', '').replace(/-/g, ' ');
                    breadcrumb.append('<li class="breadcrumb-item active">' + title.charAt(0).toUpperCase() + title.slice(1) + '</li>');
                } else {
                    breadcrumb.append('<li class="breadcrumb-item"><a href="#">' + part + '</a></li>');
                }
            });
        }
        
        // Auto update breadcrumb
        $(document).ready(function() {
            updateBreadcrumb();
        });
        
        // Handle ajax errors
        $(document).ajaxError(function(event, jqxhr, settings, error) {
            showError('Có lỗi xảy ra: ' + error);
        });
        
        // Tooltip initialization
        $(document).ready(function() {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
        
        // Popover initialization
        $(document).ready(function() {
            var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
            popoverTriggerList.map(function(popoverTriggerEl) {
                return new bootstrap.Popover(popoverTriggerEl);
            });
        });
        
        // Prevent double submit
        $(document).on('submit', 'form', function() {
            const btn = $(this).find('button[type="submit"]');
            if (btn.data('submitted')) {
                return false;
            }
            btn.data('submitted', true);
            btn.html('<span class="spinner-border spinner-border-sm me-2"></span>Đang xử lý...');
            btn.prop('disabled', true);
        });
        
        // Image preview
        function previewImage(input, previewId) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    $('#' + previewId).attr('src', e.target.result).show();
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        // Export table to Excel
        function exportToExcel(tableId, filename = 'data.xlsx') {
            const table = document.getElementById(tableId);
            const wb = XLSX.utils.table_to_book(table, { sheet: "Sheet1" });
            XLSX.writeFile(wb, filename);
        }
        
        // Print table
        function printTable(tableId, title = '') {
            const table = document.getElementById(tableId);
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <html>
                    <head>
                        <title>${title || 'In báo cáo'}</title>
                        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
                        <style>
                            body { padding: 20px; }
                            @media print {
                                .no-print { display: none; }
                            }
                        </style>
                    </head>
                    <body>
                        <div class="container">
                            <h3 class="mb-4">${title}</h3>
                            ${table.outerHTML}
                            <div class="no-print mt-4">
                                <button class="btn btn-primary" onclick="window.print()">In</button>
                                <button class="btn btn-secondary" onclick="window.close()">Đóng</button>
                            </div>
                        </div>
                    </body>
                </html>
            `);
            printWindow.document.close();
        }
    </script>
    
    <!-- Custom JavaScript -->
    <script>
        $(document).ready(function() {
            console.log('ProTrack User Panel loaded');
        });
    </script>
</body>
</html>