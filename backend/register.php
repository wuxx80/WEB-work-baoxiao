<?php
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

// 如果已登录，根据角色重定向
if (is_logged_in()) {
    if (is_admin()) {
        redirect('admin/dashboard.php');
    } else {
        redirect('user/dashboard.php');
    }
}

$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $nickname = $_POST['nickname'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (!empty($username) && !empty($password) && !empty($nickname) && !empty($confirm_password)) {
        if ($password !== $confirm_password) {
            $error = "两次输入的密码不一致。";
        } else {
            // 检查用户名是否已存在
            $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $error = "用户名已存在，请更换。";
            } else {
                // 注册新用户
                $stmt = $conn->prepare("INSERT INTO users (username, password, nickname, role) VALUES (?, ?, ?, 'user')");
                $stmt->bind_param("sss", $username, $password, $nickname);
                if ($stmt->execute()) {
                    $success = "注册成功！请返回登录页面。";
                } else {
                    $error = "注册失败，请重试。";
                }
            }
        }
    } else {
        $error = "请填写所有必填项。";
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>注册 - 金瑞科技员工报销系统</title>
    <link rel="stylesheet" href="../public/user/css/style.css">
</head>
<body>
    <div class="login-container">
        <h2>员工注册</h2>
        <form action="register.php" method="POST">
            <?php if ($error): ?>
                <p class="error-message"><?php echo htmlspecialchars($error); ?></p>
            <?php endif; ?>
            <?php if ($success): ?>
                <p class="success-message"><?php echo htmlspecialchars($success); ?></p>
            <?php endif; ?>
            <div class="form-group">
                <label for="username">用户名:</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="nickname">昵称:</label>
                <input type="text" id="nickname" name="nickname" required>
            </div>
            <div class="form-group">
                <label for="password">密码:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="confirm_password">确认密码:</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            <button type="submit">注册</button>
        </form>
        <p class="register-link">已有账号？<a href="login.php">立即登录</a></p>
    </div>
</body>
</html>