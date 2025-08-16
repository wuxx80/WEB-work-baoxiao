<?php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

// 如果已登录且角色为管理员，则重定向到后台首页
if (is_logged_in() && is_admin()) {
    redirect('dashboard.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (!empty($username) && !empty($password)) {
        $stmt = $conn->prepare("SELECT user_id, nickname FROM users WHERE username = ? AND password = ? AND role = 'admin'");
        $stmt->bind_param("ss", $username, $password);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['nickname'] = $user['nickname'];
            $_SESSION['role'] = 'admin';
            redirect('dashboard.php');
        } else {
            $error = "用户名或密码错误。";
        }
    } else {
        $error = "请填写用户名和密码。";
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>管理员登录 - 金瑞科技员工报销系统</title>
    <link rel="stylesheet" href="../../public/admin/css/style.css">
    <link rel="stylesheet" href="../../public/admin/css/dashboard.css">
</head>
<body>
    <div class="login-container">
			<h2 class="form-title">
                <a href="../user/login.php" class="login-option">员工登录</a>|<a href="login.php" class="login-option active">管理员登录</a>
            </h2>
        <form action="login.php" method="POST">
            <?php if ($error): ?>
                <p class="error-message"><?php echo htmlspecialchars($error); ?></p>
            <?php endif; ?>
            <div class="form-group">
                <label for="username">用户名:</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">密码:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit">登录</button>
        </form>
    </div>
</body>
</html>