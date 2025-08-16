<?php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

if (!is_logged_in() || !is_user()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$nickname = $_SESSION['nickname'] ?? '用户';
$sql_where = "WHERE user_id = ?";
$params = [$user_id];
$param_types = 'i';

// 获取并处理筛选条件
$filter_status = $_GET['status'] ?? 'all';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? date('Y-m-d'); // 默认设置为今天

// 根据状态筛选
if ($filter_status !== 'all') {
    $sql_where .= " AND status = ?";
    $params[] = $filter_status;
    $param_types .= 's';
}

// 根据日期范围筛选
if (!empty($start_date)) {
    $sql_where .= " AND DATE(created_at) >= ?";
    $params[] = $start_date;
    $param_types .= 's';
}
if (!empty($end_date)) {
    $sql_where .= " AND DATE(created_at) <= ?";
    $params[] = $end_date;
    $param_types .= 's';
}

// 查询报销单列表
$sql = "SELECT * FROM expenses " . $sql_where . " ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param($param_types, ...$params);
$stmt->execute();
$expenses_result = $stmt->get_result();

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
    <title>我的报销 - 金瑞科技员工报销系统</title>
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
        <h2>我的报销单</h2>

        <div class="search-panel">
            <form action="my_expenses.php" method="GET" class="search-form">
                <div class="form-group">
                    <label for="start_date">开始日期</label>
                    <input type="date" id="start_date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
                </div>
                <div class="form-group">
                    <label for="end_date">结束日期</label>
                    <input type="date" id="end_date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
                </div>
                <button type="submit" class="btn btn-primary">查询</button>
            </form>
        </div>

        <div class="table-panel">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>单号</th>
                        <th>报销项目</th>
                        <th>金额</th>
                        <th>
                            <label for="status_select">状态</label>
                            <select id="status_select" name="status" onchange="this.form.submit()">
                                <option value="all" <?php echo $filter_status == 'all' ? 'selected' : ''; ?>>所有</option>
                                <option value="pending" <?php echo $filter_status == 'pending' ? 'selected' : ''; ?>>待处理</option>
                                <option value="approved" <?php echo $filter_status == 'approved' ? 'selected' : ''; ?>>已通过</option>
                                <option value="rejected" <?php echo $filter_status == 'rejected' ? 'selected' : ''; ?>>已驳回</option>
                            </select>
                        </th>
                        <th>提交日期</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($expenses_result->num_rows > 0): ?>
                        <?php while ($expense = $expenses_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($expense['expense_id']); ?></td>
                                <td><?php echo htmlspecialchars($expense['project_name']); ?></td>
                                <td>¥<?php echo number_format($expense['total_amount'], 2); ?></td>
                                <td>
                                    <span class="status-badge <?php echo getStatusClass($expense['status']); ?>">
                                        <?php echo get_status_text($expense['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($expense['created_at']); ?></td>
                                <td>
                                    <a href="expense_detail.php?id=<?php echo htmlspecialchars($expense['expense_id']); ?>" class="btn-link">查看详情</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="no-data">没有找到符合条件的报销单。</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>

</body>
</html>