<?php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

if (!is_logged_in() || !is_admin()) {
    redirect('login.php');
}

$nickname = $_SESSION['nickname'] ?? '管理员';
$error = '';
$success = '';

// 处理表单提交（添加、编辑、删除）
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'add':
            $category_name = $_POST['category_name'] ?? '';
            if (!empty($category_name)) {
                $stmt = $conn->prepare("INSERT INTO expense_categories (category_name) VALUES (?)");
                $stmt->bind_param("s", $category_name);
                if ($stmt->execute()) {
                    $success = "费用类别添加成功！";
                } else {
                    $error = "添加失败：" . $stmt->error;
                }
                $stmt->close();
            } else {
                $error = "请填写类别名称。";
            }
            break;
        case 'edit':
            $category_id = $_POST['category_id'] ?? 0;
            $category_name = $_POST['category_name'] ?? '';
            if ($category_id > 0 && !empty($category_name)) {
                $stmt = $conn->prepare("UPDATE expense_categories SET category_name = ? WHERE category_id = ?");
                $stmt->bind_param("si", $category_name, $category_id);
                if ($stmt->execute()) {
                    $success = "类别名称更新成功！";
                } else {
                    $error = "更新失败：" . $stmt->error;
                }
                $stmt->close();
            } else {
                $error = "更新失败，请检查输入。";
            }
            break;
        case 'delete':
            $category_id = $_POST['category_id'] ?? 0;
            if ($category_id > 0) {
                $stmt = $conn->prepare("DELETE FROM expense_categories WHERE category_id = ?");
                $stmt->bind_param("i", $category_id);
                if ($stmt->execute()) {
                    $success = "类别删除成功！";
                } else {
                    $error = "删除失败：" . $stmt->error;
                }
                $stmt->close();
            } else {
                $error = "删除失败，类别ID无效。";
            }
            break;
    }
}

// 查询所有费用类别
$sql = "SELECT * FROM expense_categories ORDER BY created_at DESC";
$categories_result = $conn->query($sql);

?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>费用类别管理 - 金瑞科技员工报销系统</title>
    <link rel="stylesheet" href="../../public/admin/css/style.css">
    <link rel="stylesheet" href="../../public/admin/css/dashboard.css">
</head>
<body>

    <header class="main-header">
        <div class="header-content">
            <h1>金瑞科技员工报销系统</h1>
            <nav>
                <a href="dashboard.php">首页</a>
                <a href="expense_list.php">报销列表</a>
                <a href="user_management.php">用户管理</a>
                <a href="data_management.php">数据管理</a>
                <a href="category_management.php" class="active">费用类别</a>
            </nav>
            <div class="user-info">
                <span>你好, <?php echo htmlspecialchars($nickname); ?></span>
                <a href="profile.php">个人资料</a> | 
                <a href="logout.php">退出</a>
            </div>
        </div>
    </header>

    <div class="main-container">
        <h2>费用类别管理</h2>
        
        <?php if ($success): ?>
            <p class="success-message"><?php echo htmlspecialchars($success); ?></p>
        <?php endif; ?>
        <?php if ($error): ?>
            <p class="error-message"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <div class="add-form-container">
            <h3>添加新类别</h3>
            <form action="category_management.php" method="POST">
                <input type="hidden" name="action" value="add">
                <div class="form-row">
                    <input type="text" name="category_name" placeholder="类别名称" required>
                </div>
                <button type="submit" class="btn-add">添加</button>
            </form>
        </div>

        <h3>现有费用类别列表</h3>
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>类别名称</th>
                    <th>创建时间</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
            <?php
            if ($categories_result->num_rows > 0) {
                while ($category = $categories_result->fetch_assoc()) {
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($category['category_id']); ?></td>
                        <td><?php echo htmlspecialchars($category['category_name']); ?></td>
                        <td><?php echo htmlspecialchars($category['created_at']); ?></td>
                        <td>
                            <button class="btn-edit" onclick="editCategory(<?php echo htmlspecialchars(json_encode($category)); ?>)">编辑</button>
                            <form action="category_management.php" method="POST" style="display: inline-block;" onsubmit="return confirm('确定要删除该类别吗？');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="category_id" value="<?php echo htmlspecialchars($category['category_id']); ?>">
                                <button type="submit" class="btn-delete">删除</button>
                            </form>
                        </td>
                    </tr>
                    <?php
                }
            } else {
                echo '<tr><td colspan="4">没有找到类别记录。</td></tr>';
            }
            ?>
            </tbody>
        </table>
    </div>

    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal()">&times;</span>
            <h3>编辑类别信息</h3>
            <form id="editForm" action="category_management.php" method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="category_id" id="edit-category-id">
                <div class="form-group">
                    <label for="edit-category-name">类别名称:</label>
                    <input type="text" id="edit-category-name" name="category_name" required>
                </div>
                <button type="submit" class="btn-save">保存</button>
            </form>
        </div>
    </div>

    <script>
    function editCategory(category) {
        document.getElementById('edit-category-id').value = category.category_id;
        document.getElementById('edit-category-name').value = category.category_name;
        document.getElementById('editModal').style.display = 'block';
    }

    function closeModal() {
        document.getElementById('editModal').style.display = 'none';
    }

    window.onclick = function(event) {
        if (event.target == document.getElementById('editModal')) {
            closeModal();
        }
    }
    </script>
</body>
</html>