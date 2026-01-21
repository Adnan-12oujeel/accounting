-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               8.4.3 - MySQL Community Server - GPL
-- Server OS:                    Win64
-- HeidiSQL Version:             12.8.0.6908
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Dumping database structure for accounting
CREATE DATABASE IF NOT EXISTS `accounting` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `accounting`;

-- Dumping structure for table accounting.actions_log
CREATE TABLE IF NOT EXISTS `actions_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `branch_id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `action_type` enum('print','add','delete','edit') NOT NULL,
  `product_id` int DEFAULT NULL,
  `action_details` text,
  `date_and_time` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `branch_id` (`branch_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `actions_log_ibfk_1` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `actions_log_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table accounting.actions_log: ~0 rows (approximately)

-- Dumping structure for table accounting.branches
CREATE TABLE IF NOT EXISTS `branches` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `address` text,
  `mobile` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `date_of_start` date DEFAULT NULL,
  `plan` enum('annual','2_years','4_years') NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table accounting.branches: ~1 rows (approximately)
INSERT INTO `branches` (`id`, `name`, `address`, `mobile`, `email`, `is_active`, `date_of_start`, `plan`, `active`) VALUES
	(1, 'الإدارة العامة', 'المركز الرئيسي', NULL, NULL, 1, NULL, 'annual', 1);

-- Dumping structure for table accounting.categories
CREATE TABLE IF NOT EXISTS `categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `branch_id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `country_of_origin` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `branch_id` (`branch_id`),
  CONSTRAINT `categories_ibfk_1` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table accounting.categories: ~4 rows (approximately)
INSERT INTO `categories` (`id`, `branch_id`, `name`, `country_of_origin`, `is_active`) VALUES
	(1, 1, 'Test Category 902', NULL, 1),
	(2, 1, 'لباسل', NULL, 1),
	(3, 1, 'لباسلبلءاىبل', NULL, 1),
	(4, 1, 'ؤرلاؤ', NULL, 1);

-- Dumping structure for table accounting.customers
CREATE TABLE IF NOT EXISTS `customers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `customer_type` enum('Supplier','Importer','Cash') COLLATE utf8mb4_general_ci DEFAULT 'Cash',
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `company` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `phone` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_general_ci,
  `balance` decimal(10,2) DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table accounting.customers: ~2 rows (approximately)
INSERT INTO `customers` (`id`, `branch_id`, `customer_type`, `name`, `company`, `phone`, `address`, `balance`, `created_at`, `active`) VALUES
	(1, 1, 'Cash', 'عميل نقدي عام', NULL, '0000000000', NULL, 0.00, '2026-01-21 14:38:37', 1),
	(2, 1, 'Cash', 'رباي', 'لبءالبسيب', '23453254325', 'تابيس ', 0.00, '2026-01-21 15:10:15', 1);

-- Dumping structure for table accounting.installments
CREATE TABLE IF NOT EXISTS `installments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `invoice_id` int NOT NULL,
  `date` datetime DEFAULT CURRENT_TIMESTAMP,
  `paid_amount` decimal(15,2) NOT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `invoice_creator` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `invoice_id` (`invoice_id`),
  CONSTRAINT `installments_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table accounting.installments: ~0 rows (approximately)

-- Dumping structure for table accounting.invoices
CREATE TABLE IF NOT EXISTS `invoices` (
  `id` int NOT NULL AUTO_INCREMENT,
  `kind` enum('sales','purchase','expense') DEFAULT 'sales',
  `branch_id` int NOT NULL,
  `customer_name` varchar(100) DEFAULT 'عميل نقدي',
  `total_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `customer_id` int DEFAULT NULL,
  `supplier_id` int DEFAULT NULL,
  `invoice_type` enum('sales_invoice','bought_invoice','expense_invoice','sales_return_invoice','bought_return_invoice') DEFAULT 'sales_invoice',
  `payment_method_id` int DEFAULT NULL,
  `date` datetime DEFAULT CURRENT_TIMESTAMP,
  `payment_method` varchar(50) DEFAULT NULL,
  `invoice_status` enum('Paid','Unpaid','Installments','Pending','Overdue','Draft','Canceled') DEFAULT NULL,
  `total` decimal(15,2) DEFAULT '0.00',
  `discount` decimal(15,2) DEFAULT '0.00',
  `final_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `net_amount` decimal(15,2) DEFAULT '0.00',
  `notes` text,
  PRIMARY KEY (`id`),
  KEY `branch_id` (`branch_id`),
  KEY `customer_id` (`customer_id`),
  CONSTRAINT `invoices_ibfk_1` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `invoices_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table accounting.invoices: ~1 rows (approximately)
INSERT INTO `invoices` (`id`, `kind`, `branch_id`, `customer_name`, `total_amount`, `customer_id`, `origin_invoice_id`, `supplier_id`, `invoice_type`, `payment_method_id`, `date`, `payment_method`, `invoice_status`, `total`, `discount`, `final_amount`, `net_amount`, `paid_amount`, `notes`, `creator_id`) VALUES
	(1, 'sales', 1, 'عميل نقدي', 123.00, 1, NULL, NULL, 'sales_invoice', 1, '2026-01-21 15:02:09', NULL, 'Paid', 0.00, 0.00, 0.00, 112.00, 0.00, '', NULL);

-- Dumping structure for table accounting.invoice_items
CREATE TABLE IF NOT EXISTS `invoice_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `invoice_id` int NOT NULL,
  `product_id` int NOT NULL,
  `unit` varchar(50) DEFAULT NULL,
  `count` int NOT NULL,
  `unit_price` decimal(15,2) NOT NULL,
  `total` decimal(15,2) DEFAULT NULL,
  `discount` decimal(15,2) DEFAULT '0.00',
  `net_amount` decimal(15,2) DEFAULT NULL,
  `notes` text,
  PRIMARY KEY (`id`),
  KEY `invoice_id` (`invoice_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `invoice_items_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
  CONSTRAINT `invoice_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table accounting.invoice_items: ~0 rows (approximately)

-- Dumping structure for table accounting.invoice_return_items
CREATE TABLE IF NOT EXISTS `invoice_return_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `return_invoice_id` int NOT NULL,
  `product_id` int NOT NULL,
  `unit` varchar(50) DEFAULT NULL,
  `count` int NOT NULL,
  `unit_price` decimal(15,2) DEFAULT NULL,
  `total` decimal(15,2) DEFAULT NULL,
  `discount` decimal(15,2) DEFAULT NULL,
  `net_amount` decimal(15,2) DEFAULT NULL,
  `notes` text,
  PRIMARY KEY (`id`),
  KEY `return_invoice_id` (`return_invoice_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `invoice_return_items_ibfk_1` FOREIGN KEY (`return_invoice_id`) REFERENCES `invoice_sales_returns` (`id`) ON DELETE CASCADE,
  CONSTRAINT `invoice_return_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table accounting.invoice_return_items: ~0 rows (approximately)

-- Dumping structure for table accounting.invoice_sales_returns
CREATE TABLE IF NOT EXISTS `invoice_sales_returns` (
  `id` int NOT NULL AUTO_INCREMENT,
  `branch_id` int NOT NULL,
  `main_invoice_id` int NOT NULL,
  `main_invoice_date` datetime DEFAULT NULL,
  `condition_of_goods` text,
  `total_value_of_returns` decimal(15,2) DEFAULT NULL,
  `invoice_creator` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `branch_id` (`branch_id`),
  KEY `main_invoice_id` (`main_invoice_id`),
  CONSTRAINT `invoice_sales_returns_ibfk_1` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `invoice_sales_returns_ibfk_2` FOREIGN KEY (`main_invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table accounting.invoice_sales_returns: ~0 rows (approximately)

-- Dumping structure for table accounting.products
CREATE TABLE IF NOT EXISTS `products` (
  `id` int NOT NULL AUTO_INCREMENT,
  `branch_id` int NOT NULL,
  `category_id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text,
  `price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `cost` decimal(10,2) NOT NULL DEFAULT '0.00',
  `stock_quantity` int NOT NULL DEFAULT '0',
  `alert_quantity` int DEFAULT '5',
  `weight` decimal(10,2) DEFAULT NULL,
  `selling_price` decimal(15,2) DEFAULT NULL,
  `default_unit` varchar(50) DEFAULT NULL,
  `productive_capital` decimal(15,2) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `product_code` varchar(100) DEFAULT NULL,
  `container_code` varchar(100) DEFAULT NULL,
  `received_date` date DEFAULT NULL,
  `product_place` enum('depot','fair') DEFAULT NULL,
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `branch_id` (`branch_id`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `products_ibfk_1` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `products_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table accounting.products: ~1 rows (approximately)
INSERT INTO `products` (`id`, `branch_id`, `category_id`, `name`, `description`, `price`, `cost`, `stock_quantity`, `alert_quantity`, `weight`, `selling_price`, `default_unit`, `productive_capital`, `is_active`, `product_code`, `container_code`, `received_date`, `product_place`, `stock_qty`, `notes`, `created_at`, `active`) VALUES
	(1, 1, 1, 'dsgfws', 'fghasfsdagb', 123.00, 12.00, 1212, 5, NULL, NULL, NULL, NULL, 1, 'code-123', NULL, NULL, NULL, 0.00, NULL, '2026-01-21 14:21:27', 1);

-- Dumping structure for table accounting.system_settings
CREATE TABLE IF NOT EXISTS `system_settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `branch_id` int DEFAULT NULL,
  `setting_type` enum('unit','payment_method','permission','currency') NOT NULL,
  `setting_value` varchar(100) NOT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `branch_id` (`branch_id`),
  CONSTRAINT `system_settings_ibfk_1` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table accounting.system_settings: ~0 rows (approximately)

-- Dumping structure for table accounting.users
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `branch_id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `user_permissions` json DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `branch_id` (`branch_id`),
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table accounting.users: ~1 rows (approximately)
INSERT INTO `users` (`id`, `branch_id`, `name`, `email`, `password`, `user_permissions`, `is_active`, `last_login`, `created_at`, `active`) VALUES
	(2, 1, 'Super Admin', 'super@system.com', '$2y$10$U0ZRmOdWH2yO7qj2ppoDC.GXWBkWF4/GufozFtZrrjK8sEOLXo/SS', '{"role": "super_admin", "all_access": true, "can_manage_admins": true, "can_create_branches": true}', 1, NULL, '2026-01-21 13:39:09', 1),
	(5, 1, 'المدير العام', 'admin@system.com', '$2y$10$9ABJ98m/Nl7K8Y5bmD3wyeF84Z68.NqrVxJ7hwtntjQvNBZYdFEXG', '["all_access"]', 1, '2026-01-21 21:08:51', '2026-01-21 18:02:41', 1);

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
