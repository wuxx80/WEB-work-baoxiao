<?php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

if (!is_logged_in() || !is_admin()) {
    redirect('login.php');
}

$nickname = $_SESSION['nickname'] ?? '管理员';
$expense = null;
$expense_items = [];
$error = '';
$success = '';

// 获取报销单ID
$expense_id = $_GET['id'] ?? 0;

if ($expense_id > 0) {
    // 处理审批操作
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        $comment = $_POST['comment'] ?? '';
        $approver_id = $_SESSION['user_id'];
        $approver_name = $_SESSION['nickname'];
        $status_to_update = '';

        if ($action === 'approve') {
            $status_to_update = 'approved';
        } elseif ($action === 'reject') {
            $status_to_update = 'rejected';
        }

        if (!empty($status_to_update)) {
            // 确保使用 $_SESSION['nickname'] 作为审批人姓名
            $stmt = $conn->prepare("UPDATE expenses SET status = ?, approver_id = ?, approver_name = ?, comment = ? WHERE expense_id = ?");
            $stmt->bind_param("sissi", $status_to_update, $approver_id, $approver_name, $comment, $expense_id);
            if ($stmt->execute()) {
                $success = "报销单已成功处理。";
            } else {
                $error = "处理失败，请稍后再试。";
            }
            $stmt->close();
        }
    }

    // 重新查询报销单主体信息
    $stmt_expense = $conn->prepare("SELECT e.*, u.nickname as user_nickname FROM expenses e JOIN users u ON e.user_id = u.user_id WHERE e.expense_id = ?");
    $stmt_expense->bind_param("i", $expense_id);
    $stmt_expense->execute();
    $result_expense = $stmt_expense->get_result();

    if ($result_expense->num_rows > 0) {
        $expense = $result_expense->fetch_assoc();

        // 查询报销单明细
        $stmt_items = $conn->prepare("SELECT ei.*, ec.category_name FROM expense_items ei JOIN expense_categories ec ON ei.category_id = ec.category_id WHERE ei.expense_id = ?");
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

// 如果找不到报销单，则重定向回列表
if ($expense === null) {
    redirect('expense_list.php');
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
    <link rel="stylesheet" href="../../public/admin/css/style.css">
    <link rel="stylesheet" href="../../public/admin/css/admin_forms.css">
</head>
<body>

    <header class="main-header">
        <div class="header-content">
            <h1>员工报销系统</h1>
            <nav>
                <a href="dashboard.php">仪表盘</a>
                <a href="expense_list.php" class="active">报销管理</a>
                <a href="categories.php">类别管理</a>
                <a href="users.php">用户管理</a>
            </nav>
            <div class="user-info">
                <span>你好, <?php echo htmlspecialchars($nickname); ?></span>
                <a href="logout.php">退出</a>
            </div>
        </div>
    </header>

    <div class="main-container">
        <h2>报销单详情</h2>

        <?php if ($success): ?>
            <p class="success-message"><?php echo htmlspecialchars($success); ?></p>
        <?php endif; ?>
        <?php if ($error): ?>
            <p class="error-message"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <div class="expense-detail-container">
            <div class="detail-section">
                <h3>基本信息</h3>
                <div class="detail-row">
                    <span class="detail-label">单号:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($expense['expense_id']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">提交人:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($expense['user_nickname']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">项目:</span>
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
                <?php if ($expense['status'] !== 'pending'): ?>
                    <div class="detail-row">
                        <span class="detail-label">审批人:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($expense['approver_name'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">审批意见:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($expense['comment'] ?? '无'); ?></span>
                    </div>
                <?php endif; ?>
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

            <?php if ($expense['status'] === 'pending'): ?>
            <div class="approval-form-container">
                <h3>审批操作</h3>
                <form action="expense_detail.php?id=<?php echo htmlspecialchars($expense['expense_id']); ?>" method="POST" class="approval-form">
                    <div class="form-group">
                        <label for="comment">审批意见 (可选):</label>
                        <textarea id="comment" name="comment" rows="4"></textarea>
                    </div>
                    <div class="button-group">
                        <button type="submit" name="action" value="approve" class="btn-approve">通过</button>
                        <button type="submit" name="action" value="reject" class="btn-reject">驳回</button>
                    </div>
                </form>
            </div>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>
