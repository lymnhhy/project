// admin-script.js

// Xác nhận trước khi xóa
function confirmDelete(message, url) {
    if(confirm(message || 'Bạn có chắc muốn xóa?')) {
        window.location.href = url;
    }
    return false;
}

// Xác nhận trước khi khóa/mở khóa
function confirmLock(message, url) {
    if(confirm(message || 'Bạn có chắc muốn thay đổi trạng thái?')) {
        window.location.href = url;
    }
    return false;
}

// Tự động ẩn thông báo sau 3 giây
document.addEventListener('DOMContentLoaded', function() {
    // Ẩn alert sau 3 giây
    setTimeout(function() {
        let alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(function() {
                alert.style.display = 'none';
            }, 500);
        });
    }, 3000);
    
    // Tooltip
    let tooltips = document.querySelectorAll('[data-toggle="tooltip"]');
    if(tooltips.length && typeof bootstrap !== 'undefined') {
        tooltips.forEach(function(tooltip) {
            new bootstrap.Tooltip(tooltip);
        });
    }
});

// Xử lý check all checkbox
function checkAll(source) {
    let checkboxes = document.querySelectorAll('input[name="ids[]"]');
    checkboxes.forEach(function(cb) {
        cb.checked = source.checked;
    });
}

// Preview ảnh trước khi upload
function previewImage(input, previewId) {
    if (input.files && input.files[0]) {
        let reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById(previewId).src = e.target.result;
            document.getElementById(previewId).style.display = 'block';
        }
        reader.readAsDataURL(input.files[0]);
    }
}

// Format số tiền
function formatMoney(amount) {
    return new Intl.NumberFormat('vi-VN', { 
        style: 'currency', 
        currency: 'VND' 
    }).format(amount);
}

// Xác nhận trước khi submit form
function confirmSubmit(formId, message) {
    document.getElementById(formId).addEventListener('submit', function(e) {
        if(!confirm(message || 'Bạn có chắc muốn thực hiện?')) {
            e.preventDefault();
        }
    });
}

// Load dữ liệu bằng Ajax
function loadContent(url, targetId) {
    fetch(url)
        .then(response => response.text())
        .then(data => {
            document.getElementById(targetId).innerHTML = data;
        })
        .catch(error => console.error('Error:', error));
}

// Xuất Excel
function exportToExcel(url) {
    window.location.href = url;
}

// In ấn
function printContent(elementId) {
    let content = document.getElementById(elementId).innerHTML;
    let originalContent = document.body.innerHTML;
    
    document.body.innerHTML = content;
    window.print();
    document.body.innerHTML = originalContent;
    location.reload();
}

// Tìm kiếm nâng cao
let searchTimeout;
function searchProducts(keyword, url) {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(function() {
        if(keyword.length >= 2) {
            window.location.href = url + '?keyword=' + encodeURIComponent(keyword);
        }
    }, 500);
}