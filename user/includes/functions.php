<?php
// includes/functions.php

// Format tiền tệ
function formatMoney($amount) {
    if(!$amount) return '0 VNĐ';
    return number_format($amount, 0, ',', '.') . ' VNĐ';
}

// Format ngày tháng
function formatDate($date) {
    return date('d/m/Y', strtotime($date));
}

function formatDateTime($datetime) {
    return date('d/m/Y H:i', strtotime($datetime));
}

// Time ago
function timeAgo($time) {
    $time = strtotime($time);
    $now = time();
    $diff = $now - $time;
    
    if($diff < 60) return 'Vài giây trước';
    if($diff < 3600) return floor($diff/60) . ' phút trước';
    if($diff < 86400) return floor($diff/3600) . ' giờ trước';
    if($diff < 2592000) return floor($diff/86400) . ' ngày trước';
    return date('d/m/Y', $time);
}

// Màu sắc cho tiến độ
function getProgressColor($progress) {
    if($progress >= 100) return 'success';
    if($progress >= 70) return 'primary';
    if($progress >= 30) return 'warning';
    return 'danger';
}

// Màu sắc cho trạng thái
function getStatusBadge($status) {
    switch($status) {
        case 'Chưa thi công': return 'secondary';
        case 'Đang thi công': return 'warning';
        case 'Hoàn thành': return 'success';
        default: return 'secondary';
    }
}

// Tính tiến độ công trình (trung bình các hạng mục)
function tinhTienDoCongTrinh($conn, $congtrinh_id) {
    $sql = "SELECT AVG(phan_tram_tien_do) as tien_do 
            FROM hangmucthicong 
            WHERE congtrinh_id = $congtrinh_id";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
    return round($row['tien_do'] ?? 0);
}

// Ghi log hoạt động
function logActivity($conn, $user_id, $action, $detail) {
    $ip = $_SERVER['REMOTE_ADDR'];
    $sql = "INSERT INTO lichsuhoatdong (user_id, hanh_dong, chi_tiet, ip_address) 
            VALUES ($user_id, '$action', '$detail', '$ip')";
    mysqli_query($conn, $sql);
}

// Tạo thông báo
function setNotification($conn, $user_id, $type, $message, $link = '') {
    $sql = "INSERT INTO notifications (user_id, type, message, link) 
            VALUES ($user_id, '$type', '$message', '$link')";
    mysqli_query($conn, $sql);
}
?>