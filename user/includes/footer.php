<?php
// user/includes/footer.php
?>
        </div> <!-- End Page Content -->
        
        <!-- Footer -->
        <footer class="footer mt-5">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-6">
                        <p class="text-muted mb-0">&copy; 2026 ProTrack - Hệ thống theo dõi tiến độ dự án</p>
                    </div>
                    <div class="col-md-6 text-end">
                        <p class="text-muted mb-0">Xin chào, <?php echo $current_user['hoten']; ?></p>
                    </div>
                </div>
            </div>
        </footer>
    </div> <!-- End Main Content -->
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        // Toggle sidebar
        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('active');
        }
        
        // DataTable
        $(document).ready(function() {
            $('#dataTable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/vi.json'
                },
                pageLength: 10,
                responsive: true
            });
        });
        
        // Global search
        $('#globalSearch').on('keyup', function() {
            var value = $(this).val().toLowerCase();
            $('.table tbody tr').filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
            });
        });
        
        // Auto close alert
        window.setTimeout(function() {
            $(".alert").fadeTo(500, 0).slideUp(500, function() {
                $(this).remove();
            });
        }, 3000);
        
        // Confirm delete
        function confirmDelete(url) {
            Swal.fire({
                title: 'Bạn có chắc không?',
                text: "Bạn sẽ không thể khôi phục lại dữ liệu này!",
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
        
        // Success message
        function showSuccess(message) {
            Swal.fire({
                icon: 'success',
                title: 'Thành công!',
                text: message,
                timer: 2000,
                showConfirmButton: false
            });
        }
        
        // Error message
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