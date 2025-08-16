<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

// 如果已登录则重定向
if (is_logged_in()) {
    if (is_user()) {
        redirect('dashboard.php');
    } elseif (is_admin()) {
        redirect('../admin/dashboard.php');
    }
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $nickname = $_POST['nickname'] ?? '';

    // 简单验证
    if (empty($username) || empty($password) || empty($confirm_password) || empty($nickname)) {
        $error = "所有字段都必须填写。";
    } elseif ($password !== $confirm_password) {
        $error = "两次输入的密码不一致。";
    } else {
        // 检查用户名是否已存在
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            $error = "用户名已存在，请选择其他用户名。";
        } else {
            // 插入新用户
            $stmt = $conn->prepare("INSERT INTO users (username, password, nickname, role) VALUES (?, ?, ?, 'user')");
            $stmt->bind_param("sss", $username, $password, $nickname);
            
            if ($stmt->execute()) {
                // 注册成功后自动重定向到登录页面
                redirect('login.php');
            } else {
                $error = "注册失败，请稍后再试。";
            }
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>员工注册 - 金瑞科技员工报销系统</title>
    <link rel="stylesheet" href="../../public/user/css/style.css">
    <link rel="stylesheet" href="../../public/user/css/forms.css">
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <h2 class="form-title">员工注册</h2>
            <?php if ($success): ?>
                <p class="success-message"><?php echo htmlspecialchars($success); ?></p>
            <?php endif; ?>
            <?php if ($error): ?>
                <p class="error-message"><?php echo htmlspecialchars($error); ?></p>
            <?php endif; ?>
            <form action="register.php" method="POST">
                <div class="form-group">
                    <label for="username">用户名:</label>
                    <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
                </div>
                <div class="form-group">
                    <label for="nickname">昵称:</label>
                    <input type="text" id="nickname" name="nickname" value="<?php echo htmlspecialchars($nickname); ?>" required>
                </div>
                <div class="form-group">
                    <label for="password">密码:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="form-group">
                    <label for="confirm_password">确认密码:</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                <button type="submit" class="btn-submit">注册</button>
            </form>
            <div class="link-container">
                <a href="login.php" class="link-secondary">已有账号？立即登录</a>
            </div>
        </div>
    </div>
</body>
</html>