<?php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

if (!is_logged_in() || !is_admin()) {
    redirect('login.php');
}

$nickname = $_SESSION['nickname'] ?? '管理员';
$success = '';
$error = '';

// 处理表单提交（清空数据）
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'clear_all_data') {
        try {
            $conn->begin_transaction();
            
            // 删除所有报销明细
            $conn->query("DELETE FROM expense_items");
            
            // 删除所有报销单
            $conn->query("DELETE FROM expenses");

            $success = "所有报销单和明细数据已成功清空。";
            $conn->commit();
        } catch (mysqli_sql_exception $e) {
            $conn->rollback();
            $error = "清空数据失败：" . $e->getMessage();
        }
    } elseif ($action === 'delete_records') {
        $record_ids = $_POST['record_ids'] ?? [];
        if (!empty($record_ids) && is_array($record_ids)) {
            $placeholders = implode(',', array_fill(0, count($record_ids), '?'));
            $stmt = $conn->prepare("DELETE FROM expenses WHERE expense_id IN ($placeholders)");
            $types = str_repeat('i', count($record_ids));
            $stmt->bind_param($types, ...$record_ids);
            
            if ($stmt->execute()) {
                $success = "已成功删除选定的报销记录。";
            } else {
                $error = "删除失败: " . $stmt->error;
            }
        } else {
            $error = "请选择要删除的记录。";
        }
    }
}

// 查询历史审核记录
$user_id = $_GET['user_id'] ?? '';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

$sql = "SELECT e.*, u.nickname FROM expenses e JOIN users u ON e.user_id = u.user_id WHERE 1=1";
$params = [];
$types = '';

if (!empty($user_id)) {
    $sql .= " AND e.user_id = ?";
    $params[] = $user_id;
    $types .= 'i';
}
if (!empty($start_date)) {
    $sql .= " AND e.created_at >= ?";
    $params[] = $start_date . ' 00:00:00';
    $types .= 's';
}
if (!empty($end_date)) {
    $sql .= " AND e.created_at <= ?";
    $params[] = $end_date . ' 23:59:59';
    $types .= 's';
}

$sql .= " ORDER BY e.created_at DESC";
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$history_records = $stmt->get_result();

// 查询所有用户以供筛选
$users_result = $conn->query("SELECT user_id, nickname FROM users ORDER BY nickname");

?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>数据管理 - 金瑞科技员工报销系统</title>
    <link rel="stylesheet" href="../../public/admin/css/style.css">
    <link rel="stylesheet" href="../../public/admin/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>

    <header class="main-header">
        <div class="header-content">
            <h1>金瑞科技员工报销系统</h1>
            <nav>
                <a href="dashboard.php">首页</a>
                <a href="expense_list.php">报销列表</a>
                <a href="user_management.php">用户管理</a>
                <a href="data_management.php" class="active">数据管理</a>
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
        <h2>数据管理中心</h2>
        
        <?php if ($success): ?>
            <p class="success-message"><?php echo htmlspecialchars($success); ?></p>
        <?php endif; ?>
        <?php if ($error): ?>
            <p class="error-message"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <div class="card">
            <h3>报销记录查询与管理</h3>
            <form action="data_management.php" method="GET" class="filter-form">
                <select name="user_id">
                    <option value="">所有用户</option>
                    <?php while($user = $users_result->fetch_assoc()): ?>
                    <option value="<?php echo htmlspecialchars($user['user_id']); ?>" <?php echo $user_id == $user['user_id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($user['nickname']); ?>
                    </option>
                    <?php endwhile; ?>
                </select>
                <input type="date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
                <input type="date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
                <button type="submit" class="btn-primary">查询</button>
                <button type="button" class="btn-secondary" onclick="exportToCSV()">导出CSV</button>
            </form>

            <form id="history-form" action="data_management.php" method="POST" onsubmit="return confirm('确定要删除选定的记录吗？');">
                <input type="hidden" name="action" value="delete_records">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="selectAll"></th>
                            <th>报销单号</th>
                            <th>报销人</th>
                            <th>项目名称</th>
                            <th>总金额</th>
                            <th>状态</th>
                            <th>提交时间</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($history_records->num_rows > 0): ?>
                            <?php while ($row = $history_records->fetch_assoc()): ?>
                            <tr>
                                <td><input type="checkbox" name="record_ids[]" value="<?php echo htmlspecialchars($row['expense_id']); ?>"></td>
                                <td><?php echo htmlspecialchars($row['expense_id']); ?></td>
                                <td><?php echo htmlspecialchars($row['nickname']); ?></td>
                                <td>¥<?php echo number_format($row['total_amount'], 2); ?></td>
                                <td><?php echo get_status_text($row['status']); ?></td>
                                <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="7">没有找到符合条件的报销记录。</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                <div class="table-actions">
                    <button type="submit" class="btn-danger">批量删除</button>
                </div>
            </form>
        </div>

        <div class="card">
            <h3>系统维护</h3>
            <div class="maintenance-options">
                <div class="maintenance-item">
                    <h4>清空所有报销数据</h4>
                    <p>此操作将永久删除系统中的所有报销单及其关联的明细数据。此操作不可逆，请谨慎操作！</p>
                    <form action="data_management.php" method="POST" onsubmit="return confirm('您确定要清空所有报销数据吗？此操作不可逆！');">
                        <input type="hidden" name="action" value="clear_all_data">
                        <button type="submit" class="btn-clear">清空数据</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
    document.getElementById('selectAll').addEventListener('change', function(e) {
        let checkboxes = document.getElementsByName('record_ids[]');
        for (let i = 0; i < checkboxes.length; i++) {
            checkboxes[i].checked = e.target.checked;
        }
    });

    function exportToCSV() {
        const table = document.querySelector('.data-table');
        const rows = table.querySelectorAll('tr');
        let csv = [];

        // Get headers (exclude checkbox column)
        const headerCells = rows[0].querySelectorAll('th');
        let header = [];
        for (let i = 1; i < headerCells.length; i++) {
            header.push(headerCells[i].innerText.trim());
        }
        csv.push(header.join(','));

        // Get data rows (exclude checkbox column)
        for (let i = 1; i < rows.length; i++) {
            let row = [];
            const cells = rows[i].querySelectorAll('td');
            for (let j = 1; j < cells.length; j++) {
                let cellText = cells[j].innerText.trim();
                row.push(`"${cellText.replace(/"/g, '""')}"`);
            }
            csv.push(row.join(','));
        }

        const csvContent = "data:text/csv;charset=utf-8,\uFEFF" + csv.join("\n");
        const encodedUri = encodeURI(csvContent);
        const link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", "报销历史记录.csv");
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
    </script>
</body>
</html>