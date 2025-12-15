-- Dwellscape Capstone Database Dump
-- Import this file into phpMyAdmin or via MySQL command line
-- Database: dwellscape_capstone

-- Create database
CREATE DATABASE IF NOT EXISTS `dwellscape_capstone` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `dwellscape_capstone`;

-- Create users table
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) UNIQUE NOT NULL,
    `email` VARCHAR(100) UNIQUE NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `first_name` VARCHAR(100) NULL,
    `middle_initial` VARCHAR(5) NULL,
    `last_name` VARCHAR(100) NULL,
    `birthday` DATE NULL,
    `gender` VARCHAR(10) NULL,
    `profile_picture` VARCHAR(255) NULL,
    `is_admin` BOOLEAN DEFAULT FALSE,
    `role` VARCHAR(20) DEFAULT 'user',
    `reset_token` VARCHAR(255) NULL,
    `reset_token_expires` DATETIME NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create rooms table
CREATE TABLE IF NOT EXISTS `rooms` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `room_id` VARCHAR(50) UNIQUE NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `price_per_night` DECIMAL(10, 2) NOT NULL,
    `max_guests` INT DEFAULT 2,
    `bedrooms` INT DEFAULT 1,
    `bathrooms` INT DEFAULT 1,
    `area_sqm` DECIMAL(10, 2) NULL,
    `amenities` TEXT NULL,
    `images` TEXT NULL,
    `is_available` BOOLEAN DEFAULT TRUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_room_id` (`room_id`),
    INDEX `idx_is_available` (`is_available`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create bookings table
CREATE TABLE IF NOT EXISTS `bookings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `booking_id` VARCHAR(50) UNIQUE NOT NULL,
    `user_id` INT NOT NULL,
    `room_id` VARCHAR(50) NOT NULL,
    `room_name` VARCHAR(255) NOT NULL,
    `checkin_date` DATE NOT NULL,
    `checkout_date` DATE NOT NULL,
    `nights` INT NOT NULL,
    `price_per_night` DECIMAL(10, 2) NOT NULL,
    `total_price` DECIMAL(10, 2) NOT NULL,
    `payment_status` VARCHAR(20) DEFAULT 'pending',
    `payment_link_id` VARCHAR(255) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_checkin_date` (`checkin_date`),
    INDEX `idx_checkout_date` (`checkout_date`),
    INDEX `idx_booking_id` (`booking_id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create transactions table
CREATE TABLE IF NOT EXISTS `transactions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `transaction_id` VARCHAR(50) UNIQUE NOT NULL,
    `booking_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `payment_link_id` VARCHAR(255) NULL,
    `amount` DECIMAL(10, 2) NOT NULL,
    `currency` VARCHAR(3) DEFAULT 'PHP',
    `payment_method` VARCHAR(50) NULL,
    `payment_status` VARCHAR(20) DEFAULT 'pending',
    `paymongo_response` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_booking_id` (`booking_id`),
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_payment_status` (`payment_status`),
    INDEX `idx_transaction_id` (`transaction_id`),
    FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create gallery_images table
CREATE TABLE IF NOT EXISTS `gallery_images` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `image_url` VARCHAR(500) NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `category` VARCHAR(100) NOT NULL,
    `description` VARCHAR(255) NULL,
    `display_order` INT DEFAULT 0,
    `is_active` BOOLEAN DEFAULT TRUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_category` (`category`),
    INDEX `idx_display_order` (`display_order`),
    INDEX `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create suggestions table
CREATE TABLE IF NOT EXISTS `suggestions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `message` TEXT NOT NULL,
    `status` VARCHAR(20) DEFAULT 'unread',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_status` (`status`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

