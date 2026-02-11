<?php
session_start();
include "config/db.php"; // sửa đường dẫn đúng theo cấu trúc của bạn

$error = "";
$success = "";

/* ================== ĐĂNG KÝ ================== */
if (isset($_POST['dangky'])) {

    $username = $_POST['username'];
    $password = md5($_POST['password']);
    $hoten    = $_POST['fullname'];
    $email    = $_POST['email'];
    $sdt      = $_POST['phone'];
    $role_id  = 2; // mặc định user

    // Check trùng username
    $check = mysqli_query($conn, "SELECT id FROM users WHERE username='$username'");
    if (mysqli_num_rows($check) > 0) {
        $error = "Tên đăng nhập đã tồn tại!";
    } else {
        $sql = "INSERT INTO users (username,password,hoten,email,sdt,role_id,trangthai)
                VALUES ('$username','$password','$hoten','$email','$sdt','$role_id',1)";
        
        if (mysqli_query($conn, $sql)) {
            $success = "Đăng ký thành công! Vui lòng đăng nhập.";
        } else {
            $error = "Lỗi đăng ký!";
        }
    }
}

/* ================== ĐĂNG NHẬP ================== */
if (isset($_POST['dangnhap'])) {

    $username = $_POST['username'];
    $password = md5($_POST['password']);

    $sql = "SELECT * FROM users 
            WHERE username='$username' 
            AND password='$password' 
            AND trangthai=1";

    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);

        // LƯU SESSION
        $_SESSION['user'] = $row['username'];
        $_SESSION['role'] = $row['role_id'];
        $_SESSION['id']   = $row['id'];

        // PHÂN QUYỀN
        if ($row['role_id'] == 1) {
            header("Location: admin/dashboard.php");
        } else {
            header("Location: user/dashboard.php");
        }
        exit();
    } else {
        $error = "Sai tài khoản hoặc mật khẩu!";
    }
}
?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="description" content="">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <!-- The above 4 meta tags *must* come first in the head; any other head content must come *after* these tags -->

    <!-- Title -->
    <title>ProTrack - Project Progress Tracking System</title>

    <!-- Favicon -->
    <link rel="icon" href="img/core-img/favicon1.ico">

    <!-- Style CSS -->
    <link rel="stylesheet" href="style.css">

</head>

<body>
    <!-- Preloader -->
    <div id="preloader">
        <div class="preload-content">
            <div id="original-load"></div>
        </div>
    </div>

    <div class="coming-soon-area bg-img background-overlay" style="background-image: url(img/img_jpg/project.jpg);">
        <!-- ##### Header Area Start ##### -->
        <header class="header-area">

            <!-- Top Header Area -->
            <div class="top-header">
                <div class="container h-100">
                    <div class="row h-100 align-items-center">
                        <!-- Breaking News Area -->
                        <div class="col-12 col-sm-8">
                            <div class="breaking-news-area">
                                <div id="breakingNewsTicker" class="ticker">
                                    <ul>
                                        <li><a href="#">Hello World!</a></li>
                                        <li><a href="#">Hello Universe!</a></li>
                                        <li><a href="#">Hello Original!</a></li>
                                        <li><a href="#">Hello Earth!</a></li>
                                        <li><a href="#">Hello Colorlib!</a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <!-- Top Social Area -->
                        <div class="col-12 col-sm-4">
                            <div class="top-social-area">
                                <a href="#" data-toggle="tooltip" data-placement="bottom" title="Pinterest"><i class="fa fa-pinterest" aria-hidden="true"></i></a>
                                <a href="#" data-toggle="tooltip" data-placement="bottom" title="Facebook"><i class="fa fa-facebook" aria-hidden="true"></i></a>
                                <a href="#" data-toggle="tooltip" data-placement="bottom" title="Twitter"><i class="fa fa-twitter" aria-hidden="true"></i></a>
                                <a href="#" data-toggle="tooltip" data-placement="bottom" title="Dribbble"><i class="fa fa-dribbble" aria-hidden="true"></i></a>
                                <a href="#" data-toggle="tooltip" data-placement="bottom" title="Behance"><i class="fa fa-behance" aria-hidden="true"></i></a>
                                <a href="#" data-toggle="tooltip" data-placement="bottom" title="Linkedin"><i class="fa fa-linkedin" aria-hidden="true"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Logo Area -->
            <div class="logo-area text-center">
                <div class="container h-100">
                    <div class="row h-100 align-items-center">
                        <div class="col-12">
                            <a href="index.html" class="original-logo"><img src="img/core-img/1.png" alt=""></a>
                        </div>
                    </div>
                </div>
            </div>
        </header>
        <!-- ##### Header Area End ##### -->

        <!-- ##### Coming Soon Area Start ##### -->
        <div class="coming-soon-timer text-center">
            <div class="container">
                <div class="row">
                    <div class="col-12">
                        <div class="coming-soon-content">
                            <div class="sonar-wrapper">
                                <div class="sonar-emitter">
                                    <div class="sonar-wave">
                                    </div>
                                </div>
                            </div>
                            <p>our website is coming soon</p>
                        </div>
                        <div id="clock" class="d-flex align-items-center justify-content-between"></div>
                    </div>
                </div>
            </div>
        </div>
        <!-- ##### Coming Soon Area End ##### -->

        <!-- ##### Contact Area Start ##### -->
        <div class="contact-area section-padding-100">
            <div class="container">
                <div class="row justify-content-center">
                    <!-- Contact Form Area -->
                    <div class="col-12 col-md-10 col-lg-9">
                        <div class="contact-form">
                            <h5>Create an Account</h5>
                            <!-- Contact Form -->
                            <form action="#" method="post">
                                <div class="row">
                                    <div class="col-12 col-md-6">
                                        <div class="group">
                                        <input type="text" name="username" id="username" autocomplete="off" required>                                            
                                            <span class="highlight"></span>
                                            <span class="bar"></span>
                                            <label>Username</label>
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <div class="group">
                                        <input type="password" name="password" id="password" autocomplete="new-password" required>                                            
                                            <span class="highlight"></span>
                                            <span class="bar"></span>
                                            <label>Password</label>
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <div class="group">
                                            <input type="text" name="fullname" id="fullname" required>
                                            <span class="highlight"></span>
                                            <span class="bar"></span>
                                            <label>Fullname</label>
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <div class="group">
                                            <input type="email" name="email" id="email" required>
                                            <span class="highlight"></span>
                                            <span class="bar"></span>
                                            <label>Email</label>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="group">
                                            <input type="number" name="phone" id="phone" required>
                                            <span class="highlight"></span>
                                            <span class="bar"></span>
                                            <label>Phone</label>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <button type="submit" class="btn original-btn" name="dangky">Sign up</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="col-12 col-md-10 col-lg-3">
                        <div class="post-sidebar-area">
                            <!-- Widget Area -->
                            <div class="sidebar-widget-area">
                                <h5 class="title subscribe-title">Welcome Back / Sign In to Your Account</h5>
                                <div class="widget-content">
                                    <form action="#" method="post" class="newsletterForm">
                                        <input type="text" name="username" placeholder="Username" autocomplete="new-username">
                                        <input type="password" name="password" placeholder="Password" autocomplete="new-password">
                                        <button type="submit" class="btn original-btn" name="dangnhap">Sign in</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- ##### Contact Area End ##### -->

    <!-- ##### Instagram Feed Area Start ##### -->
    <div class="instagram-feed-area">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="insta-title">
                        <h5>Follow us @ Instagram</h5>
                    </div>
                </div>
            </div>
        </div>
        <!-- Instagram Slides -->
        <div class="instagram-slides owl-carousel">
            <!-- Single Insta Feed -->
            <div class="single-insta-feed">
                <img src="img/instagram-img/1.png" alt="">
                <!-- Hover Effects -->
                <div class="hover-effects">
                    <a href="#" class="d-flex align-items-center justify-content-center"><i class="fa fa-instagram"></i></a>
                </div>
            </div>
            <!-- Single Insta Feed -->
            <div class="single-insta-feed">
                <img src="img/instagram-img/2.png" alt="">
                <!-- Hover Effects -->
                <div class="hover-effects">
                    <a href="#" class="d-flex align-items-center justify-content-center"><i class="fa fa-instagram"></i></a>
                </div>
            </div>
            <!-- Single Insta Feed -->
            <div class="single-insta-feed">
                <img src="img/instagram-img/3.png" alt="">
                <!-- Hover Effects -->
                <div class="hover-effects">
                    <a href="#" class="d-flex align-items-center justify-content-center"><i class="fa fa-instagram"></i></a>
                </div>
            </div>
            <!-- Single Insta Feed -->
            <div class="single-insta-feed">
                <img src="img/instagram-img/4.png" alt="">
                <!-- Hover Effects -->
                <div class="hover-effects">
                    <a href="#" class="d-flex align-items-center justify-content-center"><i class="fa fa-instagram"></i></a>
                </div>
            </div>
            <!-- Single Insta Feed -->
            <div class="single-insta-feed">
                <img src="img/instagram-img/5.png" alt="">
                <!-- Hover Effects -->
                <div class="hover-effects">
                    <a href="#" class="d-flex align-items-center justify-content-center"><i class="fa fa-instagram"></i></a>
                </div>
            </div>
            <!-- Single Insta Feed -->
            <div class="single-insta-feed">
                <img src="img/instagram-img/6.png" alt="">
                <!-- Hover Effects -->
                <div class="hover-effects">
                    <a href="#" class="d-flex align-items-center justify-content-center"><i class="fa fa-instagram"></i></a>
                </div>
            </div>
            <!-- Single Insta Feed -->
            <div class="single-insta-feed">
                <img src="img/instagram-img/7.png" alt="">
                <!-- Hover Effects -->
                <div class="hover-effects">
                    <a href="#" class="d-flex align-items-center justify-content-center"><i class="fa fa-instagram"></i></a>
                </div>
            </div>
        </div>
    </div>
    <!-- ##### Instagram Feed Area End ##### -->

    <!-- ##### Footer Area Start ##### -->
    <footer class="footer-area text-center">
        <div class="container">
            <div class="row">
                <div class="col-12">
                   
                    <!-- Footer Nav Area -->
                    <div class="classy-nav-container breakpoint-off">
                        <!-- Classy Menu -->
                        <nav class="classy-navbar justify-content-center" id="footerNav">

                            <!-- Navbar Toggler -->
                            <div class="classy-navbar-toggler">
                                <span class="navbarToggler"><span></span><span></span><span></span></span>
                            </div>

                            <!-- Menu -->
                            <div class="classy-menu">

                                <!-- close btn -->
                                <div class="classycloseIcon">
                                    <div class="cross-wrap"><span class="top"></span><span class="bottom"></span></div>
                                </div>

                                <!-- Nav Start -->
                                <div class="classynav">
                                    <ul>
                                        <li><a href="#">Home</a></li>
                                        <li><a href="#">About Us</a></li>
                                        <li><a href="#">Lifestyle</a></li>
                                        <li><a href="#">travel</a></li>
                                        <li><a href="#">Music</a></li>
                                        <li><a href="#">Contact</a></li>
                                    </ul>
                                </div>
                                <!-- Nav End -->
                            </div>
                        </nav>
                    </div>
                    
                    <!-- Footer Social Area -->
                    <div class="footer-social-area mt-30">
                        <a href="#" data-toggle="tooltip" data-placement="top" title="Pinterest"><i class="fa fa-pinterest" aria-hidden="true"></i></a>
                        <a href="#" data-toggle="tooltip" data-placement="top" title="Facebook"><i class="fa fa-facebook" aria-hidden="true"></i></a>
                        <a href="#" data-toggle="tooltip" data-placement="top" title="Twitter"><i class="fa fa-twitter" aria-hidden="true"></i></a>
                        <a href="#" data-toggle="tooltip" data-placement="top" title="Dribbble"><i class="fa fa-dribbble" aria-hidden="true"></i></a>
                        <a href="#" data-toggle="tooltip" data-placement="top" title="Behance"><i class="fa fa-behance" aria-hidden="true"></i></a>
                        <a href="#" data-toggle="tooltip" data-placement="top" title="Linkedin"><i class="fa fa-linkedin" aria-hidden="true"></i></a>
                    </div>
                </div>
            </div>
        </div>

<!-- Link back to Colorlib can't be removed. Template is licensed under CC BY 3.0. -->
Copyright &copy;<script>document.write(new Date().getFullYear());</script> All rights reserved | This template is made with <i class="fa fa-heart-o" aria-hidden="true"></i> by <a href="https://colorlib.com" target="_blank">Colorlib</a>
<!-- Link back to Colorlib can't be removed. Template is licensed under CC BY 3.0. -->

    </footer>
    <!-- ##### Footer Area End ##### -->

    <!-- jQuery (Necessary for All JavaScript Plugins) -->
    <script src="js/jquery/jquery-2.2.4.min.js"></script>
    <!-- Popper js -->
    <script src="js/popper.min.js"></script>
    <!-- Bootstrap js -->
    <script src="js/bootstrap.min.js"></script>
    <!-- Plugins js -->
    <script src="js/plugins.js"></script>
    <!-- Active js -->
    <script src="js/active.js"></script>

</body>

</html>