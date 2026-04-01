-- spingo_db Schema

CREATE DATABASE IF NOT EXISTS `spingo_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `spingo_db`;

-- 1. Users Table
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `full_name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('user', 'admin') DEFAULT 'user',
  `is_verified` TINYINT(1) DEFAULT 0,
  `otp_code` VARCHAR(255) NULL,
  `otp_expiry` DATETIME NULL,
  `deleted_at` DATETIME NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Vehicles Table
CREATE TABLE IF NOT EXISTS `vehicles` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `type` ENUM('car', 'bike') NOT NULL,
  `price` DECIMAL(10, 2) NOT NULL,
  `fuel` VARCHAR(100) NOT NULL,
  `seats` INT NOT NULL,
  `city` VARCHAR(255) NULL,
  `image` VARCHAR(255) NULL,
  `availability` TINYINT(1) DEFAULT 1,
  `deleted_at` DATETIME NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Bookings Table
CREATE TABLE IF NOT EXISTS `bookings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `vehicle_id` INT NOT NULL,
  `pickup_date` DATE NOT NULL,
  `dropoff_date` DATE NOT NULL,
  `total_price` DECIMAL(10, 2) NOT NULL,
  `license_file` VARCHAR(255) NULL,
  `status` ENUM('pending', 'confirmed', 'cancelled', 'completed') DEFAULT 'pending',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Login Attempts Table (Rate Limiting)
CREATE TABLE IF NOT EXISTS `login_attempts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `email` VARCHAR(255) NOT NULL,
  `ip_address` VARCHAR(45) NOT NULL,
  `attempted_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Initial Admin User (password: Admin@123)
INSERT INTO `users` (`full_name`, `email`, `password_hash`, `role`, `is_verified`) 
VALUES ('Super Admin', 'admin@spingo.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1) 
ON DUPLICATE KEY UPDATE `id`=`id`;
