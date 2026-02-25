<?php
// admin/includes/footer.php
// Lấy lại thông tin cấu hình (nếu cần)
if(!isset($config) || !is_array($config)) {
    global $conn;
    $sql_config = "SELECT * FROM cauhinhweb WHERE id = 1";
    $result_config = mysqli_query($conn, $sql_config);
    
    // SỬA DÒNG 21 - Chuyển từ object sang mảng
    $config = mysqli_fetch_assoc($result_config);
    
    // Nếu chưa có cấu hình, tạo mảng mặc định
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
        </div> <!-- END CONTENT WRAPPER -->
        
        <!-- FOOTER -->
        <footer class="footer">
            <div class="container-fluid">
                <!-- Thông tin liên hệ -->
                <div class="row mb-3">
                    <div class="col-md-4">
                        <h6><i class="fas fa-info-circle me-2 text-primary"></i>Thông tin liên hệ</h6>
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
                        </ul>
                    </div>
                    
                    <div class="col-md-4">
                        <h6><i class="fas fa-share-alt me-2 text-primary"></i>Kết nối với chúng tôi</h6>
                        <div class="social-links">
                            <?php if(!empty($config['facebook'])): ?>
                            <a href="<?php echo $config['facebook']; ?>" target="_blank" class="btn btn-social btn-facebook me-2 mb-2">
                                <i class="fab fa-facebook-f"></i> Facebook
                            </a>
                            <?php endif; ?>
                            
                            <?php if(!empty($config['zalo'])): ?>
                            <a href="https://zalo.me/<?php echo $config['zalo']; ?>" target="_blank" class="btn btn-social btn-zalo me-2 mb-2">
                                <i class="fas fa-comment"></i> Zalo
                            </a>
                            <?php endif; ?>
                            
                            <?php if(!empty($config['website'])): ?>
                            <a href="<?php echo $config['website']; ?>" target="_blank" class="btn btn-social btn-website me-2 mb-2">
                                <i class="fas fa-globe"></i> Website
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <h6><i class="fas fa-clock me-2 text-primary"></i>Giờ làm việc</h6>
                        <ul class="list-unstyled">
                            <li class="mb-2"><i class="far fa-calendar-alt me-2 text-secondary"></i> Thứ 2 - Thứ 6: 8:00 - 17:30</li>
                            <li class="mb-2"><i class="far fa-calendar-alt me-2 text-secondary"></i> Thứ 7: 8:00 - 12:00</li>
                            <li class="mb-2"><i class="far fa-calendar-alt me-2 text-secondary"></i> Chủ nhật: Nghỉ</li>
                        </ul>
                    </div>
                </div>
                
                <!-- Đường kẻ phân cách -->
                <hr class="my-3">
                
                <!-- Bản quyền -->
                <div class="row">
                    <div class="col-md-6">
                        <p class="mb-0">
                            <i class="far fa-copyright me-1"></i> <?php echo date('Y'); ?> 
                            <strong><?php echo htmlspecialchars($config['ten_website'] ?? 'ProTrack'); ?></strong>. 
                            <?php echo htmlspecialchars($config['slogan'] ?? 'Hệ thống theo dõi dự án chuyên nghiệp'); ?>
                        </p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <p class="mb-0">
                            <i class="fas fa-code-branch me-1"></i> v1.0.0 | 
                            <i class="fas fa-user-shield me-1"></i> Admin Panel |
                            <i class="fas fa-database me-1"></i> ProTrack
                        </p>
                    </div>
                </div>
            </div>
        </footer>
    </div> <!-- END MAIN CONTENT -->

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    <!-- Admin Script -->
    <script src="/project/admin/js/admin-script.js"></script>
    
    <style>
        /* Footer Styles */
        .footer {
            background: #fff;
            padding: 25px;
            border-top: 1px solid #dee2e6;
            color: #495057;
            font-size: 13px;
            margin-top: auto;
        }
        
        .footer h6 {
            color: #2c3e50;
            font-weight: 600;
            margin-bottom: 15px;
            font-size: 14px;
        }
        
        .footer a {
            color: #3498db;
            transition: all 0.3s;
        }
        
        .footer a:hover {
            color: #2980b9;
            text-decoration: underline;
        }
        
        .btn-social {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 20px;
            padding: 5px 15px;
            font-size: 12px;
            color: #495057;
            transition: all 0.3s;
        }
        
        .btn-social:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 10px rgba(0,0,0,0.1);
        }
        
        .btn-facebook:hover {
            background: #1877f2;
            border-color: #1877f2;
            color: #fff;
        }
        
        .btn-zalo:hover {
            background: #0068ff;
            border-color: #0068ff;
            color: #fff;
        }
        
        .btn-website:hover {
            background: #2c3e50;
            border-color: #2c3e50;
            color: #fff;
        }
        
        .footer hr {
            opacity: 0.2;
            margin: 20px 0;
        }
        
        .list-unstyled li {
            font-size: 13px;
            line-height: 1.6;
        }
        
        .list-unstyled i {
            width: 20px;
            text-align: center;
        }
        
        @media (max-width: 768px) {
            .footer .col-md-4 {
                margin-bottom: 20px;
            }
            
            .footer .text-md-end {
                text-align: left !important;
                margin-top: 10px;
            }
        }
    </style>
    
    <script>
        // Toggle sidebar trên mobile
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('show');
        }
        
        // DataTable mặc định
        $(document).ready(function() {
            $('.datatable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/vi.json'
                },
                pageLength: 10,
                responsive: true
            });
            
            // Select2
            $('.select2').select2({
                theme: 'bootstrap-5',
                width: '100%'
            });
        });
        
        // Xác nhận xóa
        function confirmDelete(url, message = 'Bạn có chắc muốn xóa?') {
            Swal.fire({
                title: 'Xác nhận',
                text: message,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Xóa',
                cancelButtonText: 'Hủy'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = url;
                }
            });
            return false;
        }
        
        // Thông báo thành công
        function showSuccess(message) {
            Swal.fire({
                icon: 'success',
                title: 'Thành công!',
                text: message,
                timer: 2000,
                showConfirmButton: false
            });
        }
        
        // Thông báo lỗi
        function showError(message) {
            Swal.fire({
                icon: 'error',
                title: 'Lỗi!',
                text: message,
                confirmButtonText: 'Đóng'
            });
        }
    </script>
</body>
</html>