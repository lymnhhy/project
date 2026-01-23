<?php
session_start();
include "../config/db.php";

if (!$conn) {
    die("Không thể kết nối CSDL!");
}

$error = "";

if (isset($_POST['dangnhap'])) {
    $username = $_POST['username'];
    $password = md5($_POST['password']);

    $sql = "SELECT * FROM users 
            WHERE username = '$username' 
            AND password = '$password' 
            AND trangthai = 1";

    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);

        $_SESSION['user'] = $row['username'];
        $_SESSION['role'] = $row['role_id'];   // LƯU ROLE

        header("Location: ../trangchu.php");
        exit();
    } else {
        $error = "Sai tài khoản hoặc mật khẩu!";
    }
}
?>
    
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Đăng nhập</title>
    <style>
        body { font-family: Arial; }
        .box { width: 350px; margin: 100px auto; }
        input { width: 100%; padding: 8px; margin: 6px 0; }
        button { width: 100%; padding: 10px; }
        .error { color: red; }
    </style>
</head>
<body>

<div class="box">
    <h2>Đăng nhập</h2>

    <?php if ($error != "") { ?>
        <p class="error"><?= $error ?></p>
    <?php } ?>

    <form method="POST" autocomplete="off">
    <input type="text" name="fakeuser" style="display:none">
    <input type="password" name="fakepass" style="display:none">

    <input type="text" name="username" placeholder="Tên đăng nhập" autocomplete="new-username">
    <input type="password" name="password" placeholder="Mật khẩu" autocomplete="new-password">
        <button type="submit" name="dangnhap">Đăng nhập</button>
    </form>
</div>

</body>
</html>
