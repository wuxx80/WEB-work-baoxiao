<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

// 检查是否已登录，如果已登录则重定向到仪表盘
if (is_logged_in()) {
    if (is_user()) {
        redirect('dashboard.php');
    } elseif (is_admin()) {
        redirect('../admin/dashboard.php');
    }
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // 验证用户
    $stmt = $conn->prepare("SELECT user_id, username, password, nickname, role FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if ($password === $user['password']) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['nickname'] = $user['nickname'];
            $_SESSION['role'] = $user['role'];
            if ($user['role'] === 'user') {
                redirect('dashboard.php');
            } elseif ($user['role'] === 'admin') {
                redirect('../admin/dashboard.php');
            }
        } else {
            $error = "用户名或密码不正确。";
        }
    } else {
        $error = "用户名或密码不正确。";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>员工登录 - 金瑞科技员工报销系统</title>
    <link rel="stylesheet" href="../../public/user/css/style.css">
    <link rel="stylesheet" href="../../public/user/css/forms.css">
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <h2 class="form-title">员工登录</h2>
            <?php if ($error): ?>
                <p class="error-message"><?php echo htmlspecialchars($error); ?></p>
            <?php endif; ?>
            <form action="login.php" method="POST">
                <div class="form-group">
                    <label for="username">用户名:</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="password">密码:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" class="btn-submit">登录</button>
            </form>
            <div class="link-container">
                <a href="register.php" class="link-secondary">还没有账号？立即注册</a>
            </div>
        </div>
    </div>
</body>
</html>