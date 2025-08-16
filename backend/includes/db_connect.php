<?php
$servername = "localhost";
$username = "jrbx";
$password = "jr88888.";
$dbname = "jrbx_db";

// 创建连接
$conn = new mysqli($servername, $username, $password, $dbname);

// 检测连接
if ($conn->connect_error) {
    die("连接失败: " . $conn->connect_error);
}

// 设置字符集
$conn->set_charset("utf8mb4");
?>