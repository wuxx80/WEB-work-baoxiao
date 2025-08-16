<?php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

if (!is_logged_in() || !is_admin()) {
    redirect('login.php');
}

$nickname = $_SESSION['nickname'] ?? '管理员';

// 查询不同状态的报销单数量
$sql_counts = "SELECT status, COUNT(*) as count FROM expenses GROUP BY status";
$counts_result = $conn->query($sql_counts);
$expense_counts = ['pending' => 0, 'approved' => 0, 'rejected' => 0];
while ($row = $counts_result->fetch_assoc()) {
    $expense_counts[$row['status']] = $row['count'];
}

$total_expenses = array_sum($expense_counts);

// 获取最新报销单列表
$sql_recent_expenses = "SELECT e.*, u.nickname FROM expenses e
                        JOIN users u ON e.user_id = u.user_id
                        ORDER BY e.created_at DESC LIMIT 5";
$recent_expenses_result = $conn->query($sql_recent_expenses);

function getStatusClass($status) {
    switch ($status) {
        case 'pending':
            return 'status-pending';
        case 'approved':
            return 'status-approved';
        case 'rejected':
            return 'status-rejected';
        default:
            return '';
    }
}
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
                <a href="logout.php">退出</a>
            </div>
        </div>
    </header>

    <div class="main-container">
        <h2>欢迎来到管理员仪表盘</h2>

        <div class="dashboard-cards">
            <a href="expense_list.php?status=pending" class="card pending">
                <h3>待处理报销单</h3>
                <p class="count"><?php echo $expense_counts['pending']; ?></p>
            </a>
            <a href="expense_list.php?status=approved" class="card approved">
                <h3>已通过报销单</h3>
                <p class="count"><?php echo $expense_counts['approved']; ?></p>
            </a>
            <a href="expense_list.php?status=rejected" class="card rejected">
                <h3>被驳回报销单</h3>
                <p class="count"><?php echo $expense_counts['rejected']; ?></p>
            </a>
            <a href="expense_list.php?status=total" class="card total">
                <h3>报销单总数</h3>
                <p class="count"><?php echo $total_expenses; ?></p>
            </a>
        </div>

        <div class="recent-expenses">
            <h3>最新报销单</h3>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>单号</th>
                        <th>提交人</th>
                        <th>金额</th>
                        <th>状态</th>
                        <th>提交日期</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($recent_expenses_result->num_rows > 0): ?>
                        <?php while ($expense = $recent_expenses_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($expense['expense_id']); ?></td>
                                <td><?php echo htmlspecialchars($expense['nickname']); ?></td>
                                <td>¥<?php echo number_format($expense['total_amount'], 2); ?></td>
                                <td>
                                    <span class="status-badge <?php echo getStatusClass($expense['status']); ?>">
                                        <?php echo get_status_text($expense['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($expense['created_at']); ?>
                                </td>
                                <td>
                                    <a href="expense_detail.php?id=<?php echo htmlspecialchars($expense['expense_id']); ?>" class="btn-link">查看详情</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6">目前没有新的报销单。</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</body>
</html>