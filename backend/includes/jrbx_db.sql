-- 创建数据库
CREATE DATABASE IF NOT EXISTS `jrbx_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `jrbx_db`;

--
-- 表的结构 `users`
-- 账号密码明文存储
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL COMMENT '用户名',
  `password` varchar(50) NOT NULL COMMENT '密码（明文）',
  `nickname` varchar(50) DEFAULT NULL COMMENT '昵称',
  `role` enum('admin','user') NOT NULL DEFAULT 'user' COMMENT '角色',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 转存表中的数据 `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `nickname`, `role`) VALUES
(1, 'admin', 'admin', '管理员', 'admin'),
(2, 'testuser', '123456', '测试用户', 'user');

--
-- 表的结构 `expense_categories`
--

DROP TABLE IF EXISTS `expense_categories`;
CREATE TABLE `expense_categories` (
  `category_id` int(11) NOT NULL AUTO_INCREMENT,
  `category_name` varchar(100) NOT NULL COMMENT '费用类别名称',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 转存表中的数据 `expense_categories`
--

INSERT INTO `expense_categories` (`category_id`, `category_name`) VALUES
(1, '交通费'),
(2, '餐饮费'),
(3, '住宿费'),
(4, '办公用品');

--
-- 表的结构 `expenses`
--

DROP TABLE IF EXISTS `expenses`;
CREATE TABLE `expenses` (
  `expense_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT '报销人ID',
  `project_name` varchar(255) NOT NULL COMMENT '报销项目名称',
  `total_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '总金额',
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending' COMMENT '状态',
  `rejected_reason` text DEFAULT NULL COMMENT '驳回理由',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `approved_at` timestamp NULL DEFAULT NULL COMMENT '审核通过时间',
  PRIMARY KEY (`expense_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `expenses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 表的结构 `expense_items`
--

DROP TABLE IF EXISTS `expense_items`;
CREATE TABLE `expense_items` (
  `item_id` int(11) NOT NULL AUTO_INCREMENT,
  `expense_id` int(11) NOT NULL COMMENT '报销单ID',
  `category_id` int(11) NOT NULL COMMENT '费用类别ID',
  `amount` decimal(10,2) NOT NULL COMMENT '金额',
  `description` varchar(255) DEFAULT NULL COMMENT '描述',
  PRIMARY KEY (`item_id`),
  KEY `expense_id` (`expense_id`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `expense_items_ibfk_1` FOREIGN KEY (`expense_id`) REFERENCES `expenses` (`expense_id`) ON DELETE CASCADE,
  CONSTRAINT `expense_items_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `expense_categories` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;