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
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';
            $nickname_new = $_POST['nickname'] ?? '';
            $role = $_POST['role'] ?? 'user';

            if (!empty($username) && !empty($password) && !empty($nickname_new)) {
                $stmt = $conn->prepare("INSERT INTO users (username, password, nickname, role) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssss", $username, $password, $nickname_new, $role);
                if ($stmt->execute()) {
                    $success = "用户添加成功！";
                } else {
                    $error = "添加失败：" . $stmt->error;
                }
                $stmt->close();
            } else {
                $error = "请填写所有必填项。";
            }
            break;
        case 'edit':
            $user_id = $_POST['user_id'] ?? 0;
            $username = $_POST['username'] ?? '';
            $nickname_new = $_POST['nickname'] ?? '';
            $role = $_POST['role'] ?? 'user';

            if ($user_id > 0 && !empty($username) && !empty($nickname_new)) {
                $stmt = $conn->prepare("UPDATE users SET username = ?, nickname = ?, role = ? WHERE user_id = ?");
                $stmt->bind_param("sssi", $username, $nickname_new, $role, $user_id);
                if ($stmt->execute()) {
                    $success = "用户信息更新成功！";
                } else {
                    $error = "更新失败：" . $stmt->error;
                }
                $stmt->close();
            } else {
                $error = "更新失败，请检查输入。";
            }
            break;
        case 'delete':
            $user_id = $_POST['user_id'] ?? 0;
            if ($user_id > 0) {
                $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
                $stmt->bind_param("i", $user_id);
                if ($stmt->execute()) {
                    $success = "用户删除成功！";
                } else {
                    $error = "删除失败：" . $stmt->error;
                }
                $stmt->close();
            } else {
                $error = "删除失败，用户ID无效。";
            }
            break;
    }
}

// 查询所有用户
$sql = "SELECT * FROM users ORDER BY created_at DESC";
$users_result = $conn->query($sql);

?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>用户管理 - 金瑞科技员工报销系统</title>
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
                <a href="user_management.php" class="active">用户管理</a>
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
        <h2>用户管理</h2>
        
        <?php if ($success): ?>
            <p class="success-message"><?php echo htmlspecialchars($success); ?></p>
        <?php endif; ?>
        <?php if ($error): ?>
            <p class="error-message"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <div class="add-form-container">
            <h3>添加新用户</h3>
            <form action="user_management.php" method="POST">
                <input type="hidden" name="action" value="add">
                <div class="form-row">
                    <input type="text" name="username" placeholder="用户名" required>
                    <input type="password" name="password" placeholder="密码" required>
                    <input type="text" name="nickname" placeholder="昵称" required>
                    <select name="role">
                        <option value="user">普通用户</option>
                        <option value="admin">管理员</option>
                    </select>
                </div>
                <button type="submit" class="btn-add">添加</button>
            </form>
        </div>

        <h3>现有用户列表</h3>
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>用户名</th>
                    <th>昵称</th>
                    <th>角色</th>
                    <th>创建时间</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
            <?php
            if ($users_result->num_rows > 0) {
                while ($user = $users_result->fetch_assoc()) {
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['user_id']); ?></td>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td><?php echo htmlspecialchars($user['nickname']); ?></td>
                        <td><?php echo $user['role'] === 'admin' ? '管理员' : '普通用户'; ?></td>
                        <td><?php echo htmlspecialchars($user['created_at']); ?></td>
                        <td>
                            <button class="btn-edit" onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)">编辑</button>
                            <form action="user_management.php" method="POST" style="display: inline-block;" onsubmit="return confirm('确定要删除该用户吗？');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user['user_id']); ?>">
                                <button type="submit" class="btn-delete">删除</button>
                            </form>
                        </td>
                    </tr>
                    <?php
                }
            } else {
                echo '<tr><td colspan="6">没有找到用户记录。</td></tr>';
            }
            ?>
            </tbody>
        </table>
    </div>

    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal()">&times;</span>
            <h3>编辑用户信息</h3>
            <form id="editForm" action="user_management.php" method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="user_id" id="edit-user-id">
                <div class="form-group">
                    <label for="edit-username">用户名:</label>
                    <input type="text" id="edit-username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="edit-nickname">昵称:</label>
                    <input type="text" id="edit-nickname" name="nickname" required>
                </div>
                <div class="form-group">
                    <label for="edit-role">角色:</label>
                    <select id="edit-role" name="role">
                        <option value="user">普通用户</option>
                        <option value="admin">管理员</option>
                    </select>
                </div>
                <button type="submit" class="btn-save">保存</button>
            </form>
        </div>
    </div>

    <script>
    function editUser(user) {
        document.getElementById('edit-user-id').value = user.user_id;
        document.getElementById('edit-username').value = user.username;
        document.getElementById('edit-nickname').value = user.nickname;
        document.getElementById('edit-role').value = user.role;
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