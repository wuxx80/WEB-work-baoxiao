<?php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

if (!is_logged_in() || !is_admin()) {
    redirect('login.php');
}

$nickname = $_SESSION['nickname'] ?? '管理员';

// 查询待处理的报销单数量
$sql_pending_count = "SELECT COUNT(*) AS pending_count FROM expenses WHERE status = 'pending'";
$pending_count = $conn->query($sql_pending_count)->fetch_assoc()['pending_count'];

// 查询已处理的报销单数量
$sql_approved_count = "SELECT COUNT(*) AS approved_count FROM expenses WHERE status = 'approved'";
$approved_count = $conn->query($sql_approved_count)->fetch_assoc()['approved_count'];

// 查询所有报销单数量
$sql_total_count = "SELECT COUNT(*) AS total_count FROM expenses";
$total_count = $conn->query($sql_total_count)->fetch_assoc()['total_count'];

// 查询最近的5条报销单
$sql_recent_expenses = "SELECT e.*, u.nickname FROM expenses e JOIN users u ON e.user_id = u.user_id ORDER BY e.created_at DESC LIMIT 5";
$recent_expenses = $conn->query($sql_recent_expenses);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>后台管理中心 - 金瑞科技员工报销系统</title>
    <link rel="stylesheet" href="../../public/admin/css/style.css">
    <link rel="stylesheet" href="../../public/admin/css/dashboard.css">
</head>
<body>

    <header class="main-header">
        <div class="header-content">
            <h1>金瑞科技员工报销系统</h1>
            <nav>
                <a href="dashboard.php" class="active">首页</a>
                <a href="expense_list.php">报销列表</a>
                <a href="user_management.php">用户管理</a>
                <a href="data_management.php">数据管理</a>
                <a href="category_management.php">费用类别</a>
            </nav>
            <div class="user-info">
                <span>你好, <?php echo htmlspecialchars($nickname); ?></span>
                <a href="profile.php">个人资料</a> | 
                <a href="logout.php">退出</a>
            </div>
        </div>
    </header>

    <div class="main-container">
        <h2>欢迎来到管理员后台</h2>
        
        <div class="dashboard-cards">
            <div class="card">
                <h3>待处理报销单</h3>
                <p><?php echo htmlspecialchars($pending_count); ?></p>
                <a href="expense_list.php?status=pending">查看详情</a>
            </div>
            <div class="card">
                <h3>已处理报销单</h3>
                <p><?php echo htmlspecialchars($approved_count); ?></p>
                <a href="expense_list.php?status=approved">查看详情</a>
            </div>
            <div class="card">
                <h3>报销单总数</h3>
                <p><?php echo htmlspecialchars($total_count); ?></p>
                <a href="expense_list.php?status=total">查看详情</a>
            </div>
        </div>

        <div class="recent-table card">
            <h3>最新报销单</h3>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>报销单号</th>
                        <th>项目名称</th>
                        <th>总金额</th>
                        <th>状态</th>
                        <th>提交时间</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($recent_expenses->num_rows > 0): ?>
                        <?php while($row = $recent_expenses->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['expense_id']); ?></td>
                            <td><?php echo htmlspecialchars($row['project_name']); ?></td>
                            <td>¥<?php echo number_format($row['total_amount'], 2); ?></td>
                            <td>
                                <?php 
                                    switch($row['status']) {
                                        case 'pending': echo '待处理'; break;
                                        case 'approved': echo '已通过'; break;
                                        case 'rejected': echo '已驳回'; break;
                                    }
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                            <td><a href="expense_detail.php?id=<?php echo $row['expense_id']; ?>">审核/详情</a></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6">没有找到报销记录。</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>