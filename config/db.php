<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "project";

// Kết nối
$conn = mysqli_connect("localhost", "root", "", "project");

if (!$conn) {
    die("Không thể kết nối CSDL!");
}
mysqli_set_charset($conn, "utf8");
?>
