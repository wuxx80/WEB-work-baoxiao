<?php
// 必须在任何输出之前调用 session_start()
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/**
 * 检查用户是否已登录
 * @return bool
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * 检查用户是否是管理员
 * @return bool
 */
function is_admin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * 检查用户是否是普通用户
 * @return bool
 */
function is_user() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'user';
}

/**
 * 重定向函数
 * @param string $url
 */
function redirect($url) {
    header("Location: " . $url);
    exit();
}

/**
 * 获取报销单状态的中文文本
 * @param string $status
 * @return string
 */
function get_status_text($status) {
    switch ($status) {
        case 'pending': return '待处理';
        case 'approved': return '已通过';
        case 'rejected': return '已驳回';
        default: return '未知';
    }
}
?>