<?php
include "../config/db.php";

if (isset($_POST['dangky'])) {
    $username = $_POST['username'];
    $password = md5($_POST['password']);
    $ho_ten   = $_POST['ho_ten'];
    $email    = $_POST['email'];
    $sdt      = $_POST['so_dien_thoai'];
    $role_id  = 2; // TỰ ĐỘNG LÀ USER

    $sql = "INSERT INTO users 
    (username, password, hoten, email, sdt, role_id) 
    VALUES 
    ('$username', '$password', '$ho_ten', '$email', '$sdt', '$role_id')";

    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('Đăng ký thành công!');</script>";
    } else {
        echo "Lỗi: " . mysqli_error($conn);
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Đăng ký tài khoản</title>
        <style>
        body { font-family: Arial; }
        .box { width: 400px; margin: 50px auto; }
        input, select { width: 100%; padding: 8px; margin: 6px 0; }
        button { padding: 10px; width: 100%; }
    </style>
</head>
<body>

<div class="box">
    <h2>Đăng ký tài khoản</h2> 

<form method="POST" autocomplete="off">
    
    <!-- input mồi để lừa trình duyệt -->
    <input type="text" name="fakeuser" style="display:none">
    <input type="password" name="fakepass" style="display:none">

    <input type="text" name="username" placeholder="Tên đăng nhập" autocomplete="new-username">
    <input type="password" name="password" placeholder="Mật khẩu" autocomplete="new-password">
    <input type="text" name="ho_ten" placeholder="Họ tên" autocomplete="off">
    <input type="email" name="email" placeholder="Email" autocomplete="off">
    <input type="text" name="so_dien_thoai" placeholder="Số điện thoại" autocomplete="off">

    <button type="submit" name="dangky">Đăng ký</button>
</form>

</div>

</body>
</html>
