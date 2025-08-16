<?php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

// 检查是否已登录且为普通用户
if (!is_logged_in() || !is_user()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// 获取当前用户信息
$stmt = $conn->prepare("SELECT username, nickname FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_info = $stmt->get_result()->fetch_assoc();
$stmt->close();

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_nickname = $_POST['nickname'] ?? $user_info['nickname'];
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_new_password = $_POST['confirm_new_password'] ?? '';

    // 检查是否提供了当前密码
    if (empty($current_password)) {
        $error = "修改密码必须输入当前密码。";
    } else {
        // 验证当前密码是否正确
        $stmt_check_password = $conn->prepare("SELECT user_id FROM users WHERE user_id = ? AND password = ?");
        $stmt_check_password->bind_param("is", $user_id, $current_password);
        $stmt_check_password->execute();
        if ($stmt_check_password->get_result()->num_rows === 0) {
            $error = "当前密码不正确。";
        } else {
            // 如果提供了新密码，则验证并更新
            if (!empty($new_password)) {
                if ($new_password !== $confirm_new_password) {
                    $error = "新密码和确认密码不一致。";
                } else {
                    $stmt_update = $conn->prepare("UPDATE users SET password = ?, nickname = ? WHERE user_id = ?");
                    $stmt_update->bind_param("ssi", $new_password, $new_nickname, $user_id);
                    $stmt_update->execute();
                    $success = "密码和昵称更新成功！";
                }
            } else {
                // 只更新昵称
                $stmt_update = $conn->prepare("UPDATE users SET nickname = ? WHERE user_id = ?");
                $stmt_update->bind_param("si", $new_nickname, $user_id);
                $stmt_update->execute();
                $success = "昵称更新成功！";
            }
        }
        $stmt_check_password->close();
    }
    
    // 更新会话中的昵称
    if (empty($error)) {
        $_SESSION['nickname'] = $new_nickname;
        $user_info['nickname'] = $new_nickname; // 确保表单显示最新昵称
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>个人资料 - 金瑞科技员工报销系统</title>
    <link rel="stylesheet" href="../../public/user/css/style.css">
    <link rel="stylesheet" href="../../public/user/css/dashboard.css">
    <link rel="stylesheet" href="../../public/user/css/forms.css">
</head>
<body>

    <header class="main-header">
        <div class="header-content">
            <h1>员工报销系统</h1>
            <nav>
                <a href="dashboard.php">仪表盘</a>
                <a href="submit_expense.php">提交报销</a>
                <a href="my_expenses.php">我的报销</a>
            </nav>
            <div class="user-info">
                <span>你好, <?php echo htmlspecialchars($_SESSION['nickname']); ?></span>
                <a href="profile.php" class="active">个人资料</a> | 
                <a href="logout.php">退出</a>
            </div>
        </div>
    </header>

    <div class="main-container">
        <h2>我的个人资料</h2>

        <?php if ($success): ?>
            <p class="success-message"><?php echo htmlspecialchars($success); ?></p>
        <?php endif; ?>
        <?php if ($error): ?>
            <p class="error-message"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <form action="profile.php" method="POST" class="profile-form">
            <div class="form-section">
                <h3>账户信息</h3>
                <div class="form-group">
                    <label for="username">用户名:</label>
                    <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user_info['username']); ?>" disabled>
                </div>
                <div class="form-group">
                    <label for="nickname">昵称:</label>
                    <input type="text" id="nickname" name="nickname" value="<?php echo htmlspecialchars($user_info['nickname']); ?>">
                </div>
            </div>

            <div class="form-section">
                <h3>修改密码</h3>
                <p>如需修改密码，请填写以下字段。</p>
                <div class="form-group">
                    <label for="current_password">当前密码:</label>
                    <input type="password" id="current_password" name="current_password" required>
                </div>
                <div class="form-group">
                    <label for="new_password">新密码:</label>
                    <input type="password" id="new_password" name="new_password">
                </div>
                <div class="form-group">
                    <label for="confirm_new_password">确认新密码:</label>
                    <input type="password" id="confirm_new_password" name="confirm_new_password">
                </div>
            </div>
            
            <button type="submit" class="btn-submit-expense">保存更改</button>
        </form>
    </div>

</body>
</html>