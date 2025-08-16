<?php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

// 检查是否已登录且角色为普通用户
if (!is_logged_in() || !is_user()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$nickname = $_SESSION['nickname'] ?? '用户';

// 查询该用户待处理的报销单数量
$sql_pending_count = "SELECT COUNT(*) AS pending_count FROM expenses WHERE user_id = ? AND status = 'pending'";
$stmt_pending = $conn->prepare($sql_pending_count);
$stmt_pending->bind_param("i", $user_id);
$stmt_pending->execute();
$pending_count = $stmt_pending->get_result()->fetch_assoc()['pending_count'];
$stmt_pending->close();

// 查询该用户已通过的报销单数量
$sql_approved_count = "SELECT COUNT(*) AS approved_count FROM expenses WHERE user_id = ? AND status = 'approved'";
$stmt_approved = $conn->prepare($sql_approved_count);
$stmt_approved->bind_param("i", $user_id);
$stmt_approved->execute();
$approved_count = $stmt_approved->get_result()->fetch_assoc()['approved_count'];
$stmt_approved->close();

// 查询该用户所有报销单总金额
$sql_total_amount = "SELECT SUM(total_amount) AS total_amount FROM expenses WHERE user_id = ?";
$stmt_total_amount = $conn->prepare($sql_total_amount);
$stmt_total_amount->bind_param("i", $user_id);
$stmt_total_amount->execute();
$total_amount_result = $stmt_total_amount->get_result()->fetch_assoc();
$total_amount = $total_amount_result['total_amount'] ?? 0;
$stmt_total_amount->close();

?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>员工仪表盘 - 金瑞科技员工报销系统</title>
    <link rel="stylesheet" href="../../public/user/css/style.css">
    <link rel="stylesheet" href="../../public/user/css/dashboard.css">
</head>
<body>

    <header class="main-header">
        <div class="header-content">
            <h1>员工报销系统</h1>
            <nav>
                <a href="dashboard.php" class="active">仪表盘</a>
                <a href="submit_expense.php">创建报销</a>
                <a href="my_expenses.php">我的报销</a>
            </nav>
            <div class="user-info">
                <span>你好, <?php echo htmlspecialchars($nickname); ?></span>
                <a href="profile.php">个人资料</a> | 
                <a href="logout.php">退出</a>
            </div>
        </div>
    </header>

    <div class="main-container">
        <h2>欢迎来到员工报销平台</h2>
        
        <div class="dashboard-cards">
            <div class="card">
                <h3>待处理报销单</h3>
                <p><?php echo htmlspecialchars($pending_count); ?></p>
                <a href="my_expenses.php?status=pending">查看详情</a>
            </div>
            <div class="card">
                <h3>已通过报销单</h3>
                <p><?php echo htmlspecialchars($approved_count); ?></p>
                <a href="my_expenses.php?status=approved">查看详情</a>
            </div>
            <div class="card">
                <h3>累计报销总额</h3>
                <p>¥<?php echo number_format($total_amount, 2); ?></p>
                <a href="my_expenses.php">查看所有报销</a>
            </div>
        </div>
        
    </div>

</body>
</html>