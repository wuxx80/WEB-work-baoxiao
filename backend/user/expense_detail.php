<?php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

if (!is_logged_in() || !is_user()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$nickname = $_SESSION['nickname'] ?? '用户';
$expense = null;
$expense_items = [];

// 获取报销单ID
$expense_id = $_GET['id'] ?? 0;

if ($expense_id > 0) {
    // 查询报销单主体信息
    $stmt_expense = $conn->prepare("SELECT * FROM expenses WHERE expense_id = ? AND user_id = ?");
    $stmt_expense->bind_param("ii", $expense_id, $user_id);
    $stmt_expense->execute();
    $result_expense = $stmt_expense->get_result();

    if ($result_expense->num_rows > 0) {
        $expense = $result_expense->fetch_assoc();

        // 查询报销单明细
        $stmt_items = $conn->prepare("SELECT ei.*, ec.category_name FROM expense_items ei
                                    JOIN expense_categories ec ON ei.category_id = ec.category_id
                                    WHERE ei.expense_id = ?");
        $stmt_items->bind_param("i", $expense_id);
        $stmt_items->execute();
        $result_items = $stmt_items->get_result();
        while ($item = $result_items->fetch_assoc()) {
            $expense_items[] = $item;
        }
        $stmt_items->close();
    }
    $stmt_expense->close();
}

// 如果找不到报销单，则重定向回我的报销页面
if ($expense === null) {
    redirect('my_expenses.php');
}

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
    <title>报销单详情 - 金瑞科技员工报销系统</title>
    <link rel="stylesheet" href="../../public/user/css/style.css">
    <link rel="stylesheet" href="../../public/user/css/dashboard.css">
    <link rel="stylesheet" href="../../public/user/css/my_expenses.css">
</head>
<body>

    <header class="main-header">
        <div class="header-content">
            <h1>员工报销系统</h1>
            <nav>
                <a href="dashboard.php">仪表盘</a>
                <a href="submit_expense.php">提交报销</a>
                <a href="my_expenses.php" class="active">我的报销</a>
            </nav>
            <div class="user-info">
                <span>你好, <?php echo htmlspecialchars($nickname); ?></span>
                <a href="profile.php">个人资料</a> | 
                <a href="logout.php">退出</a>
            </div>
        </div>
    </header>

    <div class="main-container">
        <h2>报销单详情</h2>
        
        <div class="expense-detail-container">
            <div class="detail-section">
                <h3>基本信息</h3>
                <div class="detail-row">
                    <span class="detail-label">单号:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($expense['expense_id']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">项目/报销人:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($expense['project_name']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">总金额:</span>
                    <span class="detail-value">¥<?php echo number_format($expense['total_amount'], 2); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">状态:</span>
                    <span class="detail-value status-badge <?php echo getStatusClass($expense['status']); ?>">
                        <?php echo get_status_text($expense['status']); ?>
                    </span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">提交日期:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($expense['created_at']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">审批人:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($expense['approver_name'] ?? '待审批'); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">审批意见:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($expense['comment'] ?? '无'); ?></span>
                </div>
            </div>

            <div class="detail-section">
                <h3>报销明细</h3>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>类别</th>
                            <th>金额</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($expense_items)): ?>
                            <?php foreach ($expense_items as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['category_name']); ?></td>
                                    <td>¥<?php echo number_format($item['amount'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="2">没有找到明细。</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</body>
</html>