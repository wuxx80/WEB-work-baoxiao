<?php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

if (!is_logged_in() || !is_admin()) {
    redirect('login.php');
}

$nickname = $_SESSION['nickname'] ?? '管理员';
$status = $_GET['status'] ?? 'pending';

$page_title = '报销列表';
$sql_where = '';

switch ($status) {
    case 'pending':
        $page_title = '待处理报销列表';
        $sql_where = "WHERE e.status = 'pending'";
        break;
    case 'approved':
        $page_title = '已处理报销列表';
        $sql_where = "WHERE e.status = 'approved' OR e.status = 'rejected'";
        break;
    case 'rejected':
        $page_title = '已驳回报销列表';
        $sql_where = "WHERE e.status = 'rejected'";
        break;
    case 'total':
        $page_title = '所有报销列表';
        $sql_where = "";
        break;
    default:
        $page_title = '待处理报销列表';
        $sql_where = "WHERE e.status = 'pending'";
}

$sql = "SELECT e.*, u.nickname FROM expenses e JOIN users u ON e.user_id = u.user_id {$sql_where} ORDER BY e.created_at DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($page_title); ?> - 金瑞科技员工报销系统</title>
    <link rel="stylesheet" href="../../public/admin/css/style.css">
    <link rel="stylesheet" href="../../public/admin/css/dashboard.css">
</head>
<body>

    <header class="main-header">
        <div class="header-content">
            <h1>金瑞科技员工报销系统</h1>
            <nav>
                <a href="dashboard.php">首页</a>
                <a href="expense_list.php" class="active">报销列表</a>
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
        <h2><?php echo htmlspecialchars($page_title); ?></h2>
        
        <div class="list-tabs">
            <a href="expense_list.php?status=pending" class="<?php echo $status == 'pending' ? 'active' : ''; ?>">待处理</a>
            <a href="expense_list.php?status=approved" class="<?php echo $status == 'approved' ? 'active' : ''; ?>">已处理</a>
            <a href="expense_list.php?status=rejected" class="<?php echo $status == 'rejected' ? 'active' : ''; ?>">已驳回</a>
            <a href="expense_list.php?status=total" class="<?php echo $status == 'total' ? 'active' : ''; ?>">报销总数</a>
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th>报销单号</th>
                    <th>报销人</th>
                    <th>项目名称</th>
                    <th>总金额</th>
                    <th>提交时间</th>
                    <th>状态</th>
                    <?php if ($status == 'rejected'): ?>
                    <th>驳回理由</th>
                    <?php endif; ?>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
            <?php
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['expense_id']); ?></td>
                        <td><?php echo htmlspecialchars($row['nickname']); ?></td>
                        <td><?php echo htmlspecialchars($row['project_name']); ?></td>
                        <td>¥<?php echo number_format($row['total_amount'], 2); ?></td>
                        <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                        <td>
                            <?php 
                            switch($row['status']) {
                                case 'pending': echo '待处理'; break;
                                case 'approved': echo '已通过'; break;
                                case 'rejected': echo '已驳回'; break;
                            }
                            ?>
                        </td>
                        <?php if ($status == 'rejected'): ?>
                        <td><?php echo htmlspecialchars($row['rejected_reason']); ?></td>
                        <?php endif; ?>
                        <td>
                            <?php if ($status == 'pending'): ?>
                                <a href="expense_detail.php?id=<?php echo $row['expense_id']; ?>">审核</a>
                            <?php else: ?>
                                <a href="expense_detail.php?id=<?php echo $row['expense_id']; ?>">详情</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php
                }
            } else {
                echo '<tr><td colspan="' . ($status == 'rejected' ? '8' : '7') . '">没有找到报销记录。</td></tr>';
            }
            ?>
            </tbody>
        </table>
    </div>

</body>
</html>