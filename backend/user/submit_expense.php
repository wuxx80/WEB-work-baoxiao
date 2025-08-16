<?php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

if (!is_logged_in() || !is_user()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$nickname = $_SESSION['nickname'] ?? '用户';
$error = '';
$success = '';

// 获取所有报销类别
$categories_result = $conn->query("SELECT * FROM expense_categories ORDER BY category_name ASC");
$categories = [];
while ($row = $categories_result->fetch_assoc()) {
    $categories[] = $row;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $project_name = trim($_POST['project_name']);
    $expense_items_data = json_decode($_POST['expense_items'], true);
    
    if (empty($project_name)) {
        $error = "项目名称不能为空。";
    } else if (empty($expense_items_data) || count(array_filter($expense_items_data, function($item) { return !empty($item['amount']) && $item['amount'] > 0; })) == 0) {
        $error = "请至少为一项报销明细输入金额。";
    } else {
        $conn->begin_transaction();
        
        try {
            // 计算总金额
            $total_amount = 0;
            $submitted_items = [];
            foreach ($expense_items_data as $item) {
                if (!empty($item['amount']) && $item['amount'] > 0) {
                    $total_amount += $item['amount'];
                    $submitted_items[] = $item;
                }
            }
            
            // 插入报销单主表
            $stmt = $conn->prepare("INSERT INTO expenses (user_id, project_name, total_amount, status) VALUES (?, ?, ?, 'pending')");
            $stmt->bind_param("isd", $user_id, $project_name, $total_amount);
            $stmt->execute();
            $expense_id = $stmt->insert_id;
            $stmt->close();

            // 插入报销明细表
            $stmt_item = $conn->prepare("INSERT INTO expense_items (expense_id, category_id, amount, description) VALUES (?, ?, ?, ?)");
            foreach ($submitted_items as $item) {
                // Description is now empty
                $description = ''; 
                $stmt_item->bind_param("iids", $expense_id, $item['category_id'], $item['amount'], $description);
                $stmt_item->execute();
            }
            $stmt_item->close();

            $conn->commit();
            $success = "报销单提交成功！单号: " . $expense_id;
            // 清空表单
            unset($_POST);
        } catch (mysqli_sql_exception $e) {
            $conn->rollback();
            $error = "提交失败，请重试。错误信息: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>提交报销 - 金瑞科技员工报销系统</title>
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
                <a href="submit_expense.php" class="active">提交报销</a>
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
        <h2>提交新的报销单</h2>

        <?php if ($success): ?>
            <p class="success-message"><?php echo htmlspecialchars($success); ?></p>
        <?php endif; ?>
        <?php if ($error): ?>
            <p class="error-message"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <form id="expense-form" action="submit_expense.php" method="POST">
            <div class="form-section">
                <h3>报销单信息</h3>
                <!-- 项目名称和报销人并排显示，并居中 -->
                <div class="form-row-flex">
                    <div class="form-group flex-item">
                        <label for="project_name">项目名称:</label>
                        <input type="text" id="project_name" name="project_name" required>
                    </div>
                    <div class="form-group flex-item">
                        <label for="submitter">报销人:</label>
                        <input type="text" id="submitter" name="submitter" value="<?php echo htmlspecialchars($nickname); ?>" disabled>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3>报销明细</h3>
                <p>请直接在下方输入各费用类目的金额：</p>
                <div id="expense-items-container" class="item-grid">
                    <?php foreach ($categories as $category): ?>
                        <div class="expense-item form-section">
                            <div class="form-group">
                                <label><?php echo htmlspecialchars($category['category_name']); ?>:</label>
                                <input type="number" 
                                       class="amount-input" 
                                       data-category-id="<?php echo htmlspecialchars($category['category_id']); ?>" 
                                       min="0" 
                                       step="0.01">
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <input type="hidden" name="expense_items" id="expense-items-input">

            <!-- Combine total amount and submit button -->
            <div class="form-actions-flex">
                <div class="total-amount">
                    <p>总金额: <span id="total-amount-display">¥0.00</span></p>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">提交报销单</button>
                </div>
            </div>
        </form>
    </div>

    <script>
        const expenseForm = document.getElementById('expense-form');
        const itemsContainer = document.getElementById('expense-items-container');
        const expenseItemsInput = document.getElementById('expense-items-input');
        const totalAmountDisplay = document.getElementById('total-amount-display');

        function updateTotals() {
            let total = 0;
            const expenseItems = [];
            document.querySelectorAll('.amount-input').forEach(input => {
                const amount = parseFloat(input.value) || 0;
                const categoryId = input.dataset.categoryId;
                
                if (amount > 0) {
                    total += amount;
                    expenseItems.push({
                        category_id: categoryId,
                        amount: amount,
                        description: '' // Description is now empty
                    });
                }
            });
            totalAmountDisplay.textContent = '¥' + total.toFixed(2);
            expenseItemsInput.value = JSON.stringify(expenseItems);
        }

        itemsContainer.addEventListener('input', (e) => {
            if (e.target.classList.contains('amount-input')) {
                updateTotals();
            }
        });

        expenseForm.addEventListener('submit', (e) => {
            updateTotals();
            const submittedItems = JSON.parse(expenseItemsInput.value);
            if (submittedItems.length === 0) {
                // Use a modal or a div to display the message instead of alert()
                alert('请至少为一项报销明细输入金额。'); 
                e.preventDefault();
            }
        });

        // Initialize total on page load
        window.addEventListener('load', () => {
            updateTotals();
        });
    </script>
</body>
</html>
