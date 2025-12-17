-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 14, 2025 at 11:31 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `dwellscape_capstone`
--

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `booking_id` varchar(50) NOT NULL,
  `user_id` int(11) NOT NULL,
  `room_id` varchar(50) NOT NULL,
  `room_name` varchar(255) NOT NULL,
  `checkin_date` date NOT NULL,
  `checkout_date` date NOT NULL,
  `nights` int(11) NOT NULL,
  `price_per_night` decimal(10,2) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `payment_status` varchar(20) DEFAULT 'pending',
  `payment_link_id` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `booking_id`, `user_id`, `room_id`, `room_name`, `checkin_date`, `checkout_date`, `nights`, `price_per_night`, `total_price`, `payment_status`, `payment_link_id`, `created_at`, `updated_at`) VALUES
(2, 'BK_693f216d595e8_1765745005', 3, '1', '1 Bedroom Suite', '2025-12-15', '2025-12-16', 1, 2500.00, 2500.00, 'paid', 'link_NcKe4FBVqzFBdADWQCUcxYQq', '2025-12-14 20:43:25', '2025-12-14 20:43:25'),
(3, 'BK_693f22ce5a098_1765745358', 3, '3', 'Family Loft', '2025-12-22', '2025-12-23', 1, 5500.00, 5500.00, 'paid', 'link_F5EP4NA5AM29hNGuo3K2eZSx', '2025-12-14 20:49:18', '2025-12-14 20:49:18'),
(4, 'BK_693f257043730_1765746032', 3, '3', 'Family Loft', '2025-12-17', '2025-12-18', 1, 5500.00, 5500.00, 'paid', 'link_6XT2o4xsi6DoJe6yPyyK9psP', '2025-12-14 21:00:32', '2025-12-14 21:00:32'),
(5, 'BK_693f2613cd527_1765746195', 3, '1', '1 Bedroom Suite', '2026-01-10', '2026-01-11', 1, 2500.00, 2500.00, 'paid', 'link_mcDmhgKpDxXAKcukTZQFnaUe', '2025-12-14 21:03:15', '2025-12-14 21:03:15'),
(6, 'BK_693f26bddb3d0_1765746365', 3, '1', '1 Bedroom Suite', '2025-12-19', '2025-12-20', 1, 2500.00, 2500.00, 'paid', 'link_Yo3v2BBoAACc3vmLzBe8sNNp', '2025-12-14 21:06:05', '2025-12-14 21:06:05'),
(7, 'BK_693f2a27155ef_1765747239', 3, '3', 'Family Loft', '2025-12-31', '2026-01-01', 1, 5500.00, 5500.00, 'paid', 'link_w1y4pYY8jzwKHzCcZcjJsK6Z', '2025-12-14 21:20:39', '2025-12-14 21:20:39'),
(8, 'BK_693f2e978a464_1765748375', 3, '2', '2 Bedroom Premium', '2025-12-25', '2025-12-26', 1, 4500.00, 4500.00, 'paid', 'link_4NWVU6esoQVv6k6t5gTPidxa', '2025-12-14 21:39:35', '2025-12-14 21:39:35'),
(9, 'BK_693f302a7ab1f_1765748778', 3, '2', '2 Bedroom Premium', '2026-01-02', '2026-01-03', 1, 4500.00, 4500.00, 'paid', 'link_SjA7owGaFR4QpBvvACfsYoE8', '2025-12-14 21:46:18', '2025-12-14 21:46:18'),
(10, 'BK_693f399b82f1e_1765751195', 3, '1', '1 Bedroom Suite', '2026-01-08', '2026-01-09', 1, 2500.00, 2500.00, 'paid', 'link_QJyKK8GVSp3o8ZvsJCtQCd5M', '2025-12-14 22:26:35', '2025-12-14 22:26:35');

-- --------------------------------------------------------

--
-- Table structure for table `gallery_images`
--

CREATE TABLE `gallery_images` (
  `id` int(11) NOT NULL,
  `image_url` varchar(500) NOT NULL,
  `title` varchar(255) NOT NULL,
  `category` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `gallery_images`
--

INSERT INTO `gallery_images` (`id`, `image_url`, `title`, `category`, `description`, `display_order`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'pictures/living1.png', 'Living Room', 'living-room', 'Spacious & Comfortable', 1, 1, '2025-12-14 19:47:07', '2025-12-14 21:12:30'),
(2, 'pictures/living2.png', 'Living Room', 'living-room', 'Modern Design', 2, 1, '2025-12-14 19:47:07', '2025-12-14 21:12:30'),
(3, 'pictures/living3.png', 'Living Room', 'living-room', 'Elegant & Cozy', 3, 1, '2025-12-14 19:47:07', '2025-12-14 21:12:30'),
(4, 'pictures/living4.png', 'Living Room', 'living-room', 'Stylish Interior', 4, 1, '2025-12-14 19:47:07', '2025-12-14 21:12:30'),
(5, 'pictures/kitchen1.png', 'Full Kitchen', 'kitchen', 'Fully Equipped', 5, 1, '2025-12-14 19:47:07', '2025-12-14 21:12:30'),
(6, 'pictures/kitchen2.png', 'Full Kitchen', 'kitchen', 'Modern & Spacious', 6, 1, '2025-12-14 19:47:07', '2025-12-14 21:12:30'),
(7, 'https://images.unsplash.com/photo-1556911220-e15b29be8c8f?q=80&w=800&auto=format&fit=crop', 'Dining Area', 'dining', 'Elegant Dining Space', 7, 1, '2025-12-14 19:47:07', '2025-12-14 19:47:07'),
(8, 'https://images.unsplash.com/photo-1631049307264-da0ec9d70304?q=80&w=800&auto=format&fit=crop', 'Bedroom 1', 'bedroom1', 'Master Bedroom', 8, 1, '2025-12-14 19:47:07', '2025-12-14 19:47:07'),
(9, 'https://images.unsplash.com/photo-1522771739844-6a9f6d5f14af?q=80&w=800&auto=format&fit=crop', 'Bedroom 2', 'bedroom2', 'Cozy Guest Room', 9, 1, '2025-12-14 19:47:07', '2025-12-14 19:47:07'),
(10, 'https://images.unsplash.com/photo-1620626011761-996317b8d101?q=80&w=800&auto=format&fit=crop', 'Full Bathroom', 'bathroom', 'Modern & Clean', 10, 1, '2025-12-14 19:47:07', '2025-12-14 19:47:07'),
(11, 'https://images.unsplash.com/photo-1497366216548-37526070297c?q=80&w=800&auto=format&fit=crop', 'Workplace', 'workplace', 'Productive Workspace', 11, 1, '2025-12-14 19:47:07', '2025-12-14 19:47:07'),
(12, 'https://images.unsplash.com/photo-1571896349842-33c89424de2d?q=80&w=800&auto=format&fit=crop', 'Pool', 'pool', 'Resort-Style Pool', 12, 1, '2025-12-14 19:47:07', '2025-12-14 19:47:07'),
(13, 'https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?q=80&w=800&auto=format&fit=crop', 'Activity Area', 'activity', 'Recreation Space', 13, 1, '2025-12-14 19:47:07', '2025-12-14 19:47:07');

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE `rooms` (
  `id` int(11) NOT NULL,
  `room_id` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price_per_night` decimal(10,2) NOT NULL,
  `max_guests` int(11) DEFAULT 2,
  `bedrooms` int(11) DEFAULT 1,
  `bathrooms` int(11) DEFAULT 1,
  `area_sqm` decimal(10,2) DEFAULT NULL,
  `amenities` text DEFAULT NULL,
  `images` text DEFAULT NULL,
  `is_available` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `rooms`
--

INSERT INTO `rooms` (`id`, `room_id`, `name`, `description`, `price_per_night`, `max_guests`, `bedrooms`, `bathrooms`, `area_sqm`, `amenities`, `images`, `is_available`, `created_at`, `updated_at`) VALUES
(1, '1', '1 Bedroom Suite', 'Cozy and comfortable suite perfect for small families or couples', 2500.00, 5, 1, 1, 45.00, 'Wi-Fi,Kitchen,TV,AC', 'https://images.unsplash.com/photo-1631049307264-da0ec9d70304?q=80&w=800&auto=format&fit=crop', 1, '2025-12-14 19:53:08', '2025-12-14 19:53:08'),
(2, '2', '2 Bedroom Premium', 'Spacious premium suite with modern amenities and balcony', 4500.00, 10, 2, 2, 85.00, 'Wi-Fi,Full Kitchen,Smart TV,AC,Balcony', 'https://images.unsplash.com/photo-1522771739844-6a9f6d5f14af?q=80&w=800&auto=format&fit=crop', 1, '2025-12-14 19:53:08', '2025-12-14 19:53:08'),
(3, '3', 'Family Loft', 'Large family-friendly loft with pool access and multiple bedrooms', 5500.00, 8, 2, 3, 95.00, 'Wi-Fi,Full Kitchen,Smart TV,AC,Pool Access', 'https://images.unsplash.com/photo-1586023492125-27b2c045efd7?q=80&w=800&auto=format&fit=crop', 1, '2025-12-14 19:53:08', '2025-12-14 19:53:08');

-- --------------------------------------------------------

--
-- Table structure for table `suggestions`
--

CREATE TABLE `suggestions` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `status` varchar(20) DEFAULT 'unread',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `transaction_id` varchar(50) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `payment_link_id` varchar(255) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(3) DEFAULT 'PHP',
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_status` varchar(20) DEFAULT 'pending',
  `paymongo_response` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`id`, `transaction_id`, `booking_id`, `user_id`, `payment_link_id`, `amount`, `currency`, `payment_method`, `payment_status`, `paymongo_response`, `created_at`, `updated_at`) VALUES
(2, 'TXN_693f216d595f0_1765745005', 2, 3, 'link_NcKe4FBVqzFBdADWQCUcxYQq', 2500.00, 'PHP', NULL, 'paid', '{\"id\":\"link_NcKe4FBVqzFBdADWQCUcxYQq\",\"type\":\"link\",\"attributes\":{\"amount\":250000,\"archived\":false,\"currency\":\"PHP\",\"description\":\"Booking for 1 Bedroom Suite - 2025-12-15 to 2025-12-16\",\"livemode\":false,\"fee\":6250,\"remarks\":\"Dwellscape Staycation Booking\",\"status\":\"paid\",\"tax_amount\":null,\"taxes\":[],\"checkout_url\":\"https:\\/\\/pm.link\\/org-oEaek5F9E8MJERN7vVRyAxx7\\/test\\/uKzrVnx\",\"reference_number\":\"uKzrVnx\",\"created_at\":1765744966,\"updated_at\":1765744966,\"payments\":[{\"data\":{\"id\":\"pay_6FWxEsK3Unu4NyxV2ALF1EJ7\",\"type\":\"payment\",\"attributes\":{\"access_url\":null,\"amount\":250000,\"balance_transaction_id\":\"bal_txn_dJsi8XDgoUFZKRxJGxUSy4tE\",\"billing\":{\"address\":{\"city\":\"Taguig\",\"country\":\"PH\",\"line1\":\"12th floor The Trade and Financial Tower u1206\",\"line2\":\"32nd street and 7th Avenue\",\"postal_code\":\"1630\",\"state\":\"Bonifacio Global City\"},\"email\":\"cieloivanlouis@gmail.com\",\"name\":\"Ivan Louis Cielo\",\"phone\":\"09366274094\"},\"currency\":\"PHP\",\"description\":\"Booking for 1 Bedroom Suite - 2025-12-15 to 2025-12-16\",\"digital_withholding_vat_amount\":0,\"disputed\":false,\"external_reference_number\":\"uKzrVnx\",\"fee\":6250,\"instant_settlement\":null,\"livemode\":false,\"net_amount\":243750,\"origin\":\"links\",\"payment_intent_id\":null,\"payout\":null,\"source\":{\"id\":\"src_FBmNv86hoBr9xpGi25CUXoTk\",\"type\":\"gcash\",\"provider\":{\"id\":null},\"provider_id\":null},\"statement_descriptor\":\"RODEL MARASIGAN ANDAYA\",\"status\":\"paid\",\"tax_amount\":null,\"metadata\":{\"pm_reference_number\":\"uKzrVnx\"},\"promotion\":null,\"refunds\":[],\"taxes\":[],\"available_at\":1765962000,\"created_at\":1765745004,\"credited_at\":1766019600,\"paid_at\":1765745004,\"updated_at\":1765745004}}}]}}', '2025-12-14 20:43:25', '2025-12-14 20:43:25'),
(3, 'TXN_693f22ce5a0a1_1765745358', 3, 3, 'link_F5EP4NA5AM29hNGuo3K2eZSx', 5500.00, 'PHP', NULL, 'paid', '{\"id\":\"link_F5EP4NA5AM29hNGuo3K2eZSx\",\"type\":\"link\",\"attributes\":{\"amount\":550000,\"archived\":false,\"currency\":\"PHP\",\"description\":\"Booking for Family Loft - 2025-12-22 to 2025-12-23\",\"livemode\":false,\"fee\":11000,\"remarks\":\"Dwellscape Staycation Booking\",\"status\":\"paid\",\"tax_amount\":null,\"taxes\":[],\"checkout_url\":\"https:\\/\\/pm.link\\/org-oEaek5F9E8MJERN7vVRyAxx7\\/test\\/mjJsJ9n\",\"reference_number\":\"mjJsJ9n\",\"created_at\":1765745321,\"updated_at\":1765745321,\"payments\":[{\"data\":{\"id\":\"pay_HwwRJGtgtFA3tcQnVwZSKvjN\",\"type\":\"payment\",\"attributes\":{\"access_url\":null,\"amount\":550000,\"balance_transaction_id\":\"bal_txn_umfEdUfmgdW9oTWMqqg1deWz\",\"billing\":{\"address\":{\"city\":null,\"country\":null,\"line1\":null,\"line2\":null,\"postal_code\":null,\"state\":null},\"email\":\"cieloivanlouis@gmail.com\",\"name\":\"Ivan Louis Cielo\",\"phone\":\"\"},\"currency\":\"PHP\",\"description\":\"Booking for Family Loft - 2025-12-22 to 2025-12-23\",\"digital_withholding_vat_amount\":0,\"disputed\":false,\"external_reference_number\":\"mjJsJ9n\",\"fee\":11000,\"instant_settlement\":null,\"livemode\":false,\"net_amount\":539000,\"origin\":\"links\",\"payment_intent_id\":\"pi_n9DVteKWADEyBSsWYLhP6Phc\",\"payout\":null,\"source\":{\"id\":\"paymaya_e4i8unNJSuiphwe2rx56xUcm\",\"type\":\"paymaya\",\"provider\":{\"id\":null},\"provider_id\":null},\"statement_descriptor\":\"RODEL MARASIGAN ANDAYA\",\"status\":\"paid\",\"tax_amount\":null,\"metadata\":{\"pm_reference_number\":\"mjJsJ9n\"},\"promotion\":null,\"refunds\":[],\"taxes\":[],\"available_at\":1765962000,\"created_at\":1765745353,\"credited_at\":1766019600,\"paid_at\":1765745353,\"updated_at\":1765745353}}}]}}', '2025-12-14 20:49:18', '2025-12-14 20:49:18'),
(4, 'TXN_693f257043736_1765746032', 4, 3, 'link_6XT2o4xsi6DoJe6yPyyK9psP', 5500.00, 'PHP', NULL, 'paid', '{\"id\":\"link_6XT2o4xsi6DoJe6yPyyK9psP\",\"type\":\"link\",\"attributes\":{\"amount\":550000,\"archived\":false,\"currency\":\"PHP\",\"description\":\"Booking for Family Loft - 2025-12-17 to 2025-12-18\",\"livemode\":false,\"fee\":13750,\"remarks\":\"Dwellscape Staycation Booking\",\"status\":\"paid\",\"tax_amount\":null,\"taxes\":[],\"checkout_url\":\"https:\\/\\/pm.link\\/org-oEaek5F9E8MJERN7vVRyAxx7\\/test\\/AYXhxnx\",\"reference_number\":\"AYXhxnx\",\"created_at\":1765745999,\"updated_at\":1765745999,\"payments\":[{\"data\":{\"id\":\"pay_UVc1odWNMwfZp1PwyBbXkdvG\",\"type\":\"payment\",\"attributes\":{\"access_url\":null,\"amount\":550000,\"balance_transaction_id\":\"bal_txn_Sqr9PTtKXsEYuoiHYRPR3gQY\",\"billing\":{\"address\":{\"city\":\"Taguig\",\"country\":\"PH\",\"line1\":\"12th floor The Trade and Financial Tower u1206\",\"line2\":\"32nd street and 7th Avenue\",\"postal_code\":\"1630\",\"state\":\"Bonifacio Global City\"},\"email\":\"cieloivanlouis@gmail.com\",\"name\":\"Ivan Louis Cielo\",\"phone\":\"09366274094\"},\"currency\":\"PHP\",\"description\":\"Booking for Family Loft - 2025-12-17 to 2025-12-18\",\"digital_withholding_vat_amount\":0,\"disputed\":false,\"external_reference_number\":\"AYXhxnx\",\"fee\":13750,\"instant_settlement\":null,\"livemode\":false,\"net_amount\":536250,\"origin\":\"links\",\"payment_intent_id\":null,\"payout\":null,\"source\":{\"id\":\"src_XmYinqefaDT2yGs1iAwQiver\",\"type\":\"gcash\",\"provider\":{\"id\":null},\"provider_id\":null},\"statement_descriptor\":\"RODEL MARASIGAN ANDAYA\",\"status\":\"paid\",\"tax_amount\":null,\"metadata\":{\"pm_reference_number\":\"AYXhxnx\"},\"promotion\":null,\"refunds\":[],\"taxes\":[],\"available_at\":1765962000,\"created_at\":1765746031,\"credited_at\":1766019600,\"paid_at\":1765746031,\"updated_at\":1765746031}}}]}}', '2025-12-14 21:00:32', '2025-12-14 21:00:32'),
(5, 'TXN_693f2613cd536_1765746195', 5, 3, 'link_mcDmhgKpDxXAKcukTZQFnaUe', 2500.00, 'PHP', NULL, 'paid', '{\"id\":\"link_mcDmhgKpDxXAKcukTZQFnaUe\",\"type\":\"link\",\"attributes\":{\"amount\":250000,\"archived\":false,\"currency\":\"PHP\",\"description\":\"Booking for 1 Bedroom Suite - 2026-01-10 to 2026-01-11\",\"livemode\":false,\"fee\":6250,\"remarks\":\"Dwellscape Staycation Booking\",\"status\":\"paid\",\"tax_amount\":null,\"taxes\":[],\"checkout_url\":\"https:\\/\\/pm.link\\/org-oEaek5F9E8MJERN7vVRyAxx7\\/test\\/KaQsssk\",\"reference_number\":\"KaQsssk\",\"created_at\":1765746171,\"updated_at\":1765746171,\"payments\":[{\"data\":{\"id\":\"pay_WTH3eQiKVUj2wPBymmNrU88J\",\"type\":\"payment\",\"attributes\":{\"access_url\":null,\"amount\":250000,\"balance_transaction_id\":\"bal_txn_GLQ5ocwnMKM2vDhJtWjrZP1A\",\"billing\":{\"address\":{\"city\":\"Taguig\",\"country\":\"PH\",\"line1\":\"12th floor The Trade and Financial Tower u1206\",\"line2\":\"32nd street and 7th Avenue\",\"postal_code\":\"1630\",\"state\":\"Bonifacio Global City\"},\"email\":\"sdadasda@gmail.com\",\"name\":\"Ivan\",\"phone\":\"\"},\"currency\":\"PHP\",\"description\":\"Booking for 1 Bedroom Suite - 2026-01-10 to 2026-01-11\",\"digital_withholding_vat_amount\":0,\"disputed\":false,\"external_reference_number\":\"KaQsssk\",\"fee\":6250,\"instant_settlement\":null,\"livemode\":false,\"net_amount\":243750,\"origin\":\"links\",\"payment_intent_id\":null,\"payout\":null,\"source\":{\"id\":\"src_jZQZcZT4LX1rUENYKPCbZ9ft\",\"type\":\"gcash\",\"provider\":{\"id\":null},\"provider_id\":null},\"statement_descriptor\":\"RODEL MARASIGAN ANDAYA\",\"status\":\"paid\",\"tax_amount\":null,\"metadata\":{\"pm_reference_number\":\"KaQsssk\"},\"promotion\":null,\"refunds\":[],\"taxes\":[],\"available_at\":1765962000,\"created_at\":1765746195,\"credited_at\":1766019600,\"paid_at\":1765746195,\"updated_at\":1765746195}}}]}}', '2025-12-14 21:03:15', '2025-12-14 21:03:15'),
(6, 'TXN_693f26bddb3d6_1765746365', 6, 3, 'link_Yo3v2BBoAACc3vmLzBe8sNNp', 2500.00, 'PHP', NULL, 'paid', '{\"id\":\"link_Yo3v2BBoAACc3vmLzBe8sNNp\",\"type\":\"link\",\"attributes\":{\"amount\":250000,\"archived\":false,\"currency\":\"PHP\",\"description\":\"Booking for 1 Bedroom Suite - 2025-12-19 to 2025-12-20\",\"livemode\":false,\"fee\":6250,\"remarks\":\"Dwellscape Staycation Booking\",\"status\":\"paid\",\"tax_amount\":null,\"taxes\":[],\"checkout_url\":\"https:\\/\\/pm.link\\/org-oEaek5F9E8MJERN7vVRyAxx7\\/test\\/QhgT1nY\",\"reference_number\":\"QhgT1nY\",\"created_at\":1765746335,\"updated_at\":1765746335,\"payments\":[{\"data\":{\"id\":\"pay_XerL7xGzp6ap3cahQuiz6VwV\",\"type\":\"payment\",\"attributes\":{\"access_url\":null,\"amount\":250000,\"balance_transaction_id\":\"bal_txn_g46T8LxYvAY9wxG71RB1b86h\",\"billing\":{\"address\":{\"city\":\"Taguig\",\"country\":\"PH\",\"line1\":\"12th floor The Trade and Financial Tower u1206\",\"line2\":\"32nd street and 7th Avenue\",\"postal_code\":\"1630\",\"state\":\"Bonifacio Global City\"},\"email\":\"fklmp@gmail.com\",\"name\":\"adabsdsdf\",\"phone\":\"\"},\"currency\":\"PHP\",\"description\":\"Booking for 1 Bedroom Suite - 2025-12-19 to 2025-12-20\",\"digital_withholding_vat_amount\":0,\"disputed\":false,\"external_reference_number\":\"QhgT1nY\",\"fee\":6250,\"instant_settlement\":null,\"livemode\":false,\"net_amount\":243750,\"origin\":\"links\",\"payment_intent_id\":null,\"payout\":null,\"source\":{\"id\":\"src_ZXQMagYs97XHaZoNjmvJBAT6\",\"type\":\"gcash\",\"provider\":{\"id\":null},\"provider_id\":null},\"statement_descriptor\":\"RODEL MARASIGAN ANDAYA\",\"status\":\"paid\",\"tax_amount\":null,\"metadata\":{\"pm_reference_number\":\"QhgT1nY\"},\"promotion\":null,\"refunds\":[],\"taxes\":[],\"available_at\":1765962000,\"created_at\":1765746363,\"credited_at\":1766019600,\"paid_at\":1765746363,\"updated_at\":1765746363}}}]}}', '2025-12-14 21:06:05', '2025-12-14 21:06:05'),
(7, 'TXN_693f2a27155f4_1765747239', 7, 3, 'link_w1y4pYY8jzwKHzCcZcjJsK6Z', 5500.00, 'PHP', NULL, 'paid', '{\"id\":\"link_w1y4pYY8jzwKHzCcZcjJsK6Z\",\"type\":\"link\",\"attributes\":{\"amount\":550000,\"archived\":false,\"currency\":\"PHP\",\"description\":\"Booking for Family Loft - 2025-12-31 to 2026-01-01\",\"livemode\":false,\"fee\":13750,\"remarks\":\"Dwellscape Staycation Booking\",\"status\":\"paid\",\"tax_amount\":null,\"taxes\":[],\"checkout_url\":\"https:\\/\\/pm.link\\/org-oEaek5F9E8MJERN7vVRyAxx7\\/test\\/moEUJve\",\"reference_number\":\"moEUJve\",\"created_at\":1765747211,\"updated_at\":1765747211,\"payments\":[{\"data\":{\"id\":\"pay_ABbSP5zfX3ysYLpWfw2XYq3A\",\"type\":\"payment\",\"attributes\":{\"access_url\":null,\"amount\":550000,\"balance_transaction_id\":\"bal_txn_uWhDg1KnXpL1f6jAW5VC9h6G\",\"billing\":{\"address\":{\"city\":\"Taguig\",\"country\":\"PH\",\"line1\":\"12th floor The Trade and Financial Tower u1206\",\"line2\":\"32nd street and 7th Avenue\",\"postal_code\":\"1630\",\"state\":\"Bonifacio Global City\"},\"email\":\"Sa@gmail.com\",\"name\":\"adasmdabs\",\"phone\":\"09366274094\"},\"currency\":\"PHP\",\"description\":\"Booking for Family Loft - 2025-12-31 to 2026-01-01\",\"digital_withholding_vat_amount\":0,\"disputed\":false,\"external_reference_number\":\"moEUJve\",\"fee\":13750,\"instant_settlement\":null,\"livemode\":false,\"net_amount\":536250,\"origin\":\"links\",\"payment_intent_id\":null,\"payout\":null,\"source\":{\"id\":\"src_M4a4sydkjeCkXUmmtfbwDbEt\",\"type\":\"gcash\",\"provider\":{\"id\":null},\"provider_id\":null},\"statement_descriptor\":\"RODEL MARASIGAN ANDAYA\",\"status\":\"paid\",\"tax_amount\":null,\"metadata\":{\"pm_reference_number\":\"moEUJve\"},\"promotion\":null,\"refunds\":[],\"taxes\":[],\"available_at\":1765962000,\"created_at\":1765747238,\"credited_at\":1766019600,\"paid_at\":1765747238,\"updated_at\":1765747238}}}]}}', '2025-12-14 21:20:39', '2025-12-14 21:20:39'),
(8, 'TXN_693f2e978a46d_1765748375', 8, 3, 'link_4NWVU6esoQVv6k6t5gTPidxa', 4500.00, 'PHP', NULL, 'paid', '{\"id\":\"link_4NWVU6esoQVv6k6t5gTPidxa\",\"type\":\"link\",\"attributes\":{\"amount\":450000,\"archived\":false,\"currency\":\"PHP\",\"description\":\"Booking for 2 Bedroom Premium - 2025-12-25 to 2025-12-26\",\"livemode\":false,\"fee\":9900,\"remarks\":\"Dwellscape Staycation Booking\",\"status\":\"paid\",\"tax_amount\":null,\"taxes\":[],\"checkout_url\":\"https:\\/\\/pm.link\\/org-oEaek5F9E8MJERN7vVRyAxx7\\/test\\/G9JLf3X\",\"reference_number\":\"G9JLf3X\",\"created_at\":1765748345,\"updated_at\":1765748345,\"payments\":[{\"data\":{\"id\":\"pay_yZF5v8kuKAUJo8RFMMeYhRFi\",\"type\":\"payment\",\"attributes\":{\"access_url\":null,\"amount\":450000,\"balance_transaction_id\":\"bal_txn_TodKMNeJ9kAqJ8puyrJmpYfd\",\"billing\":{\"address\":{\"city\":\"Taguig\",\"country\":\"PH\",\"line1\":\"12th floor The Trade and Financial Tower u1206\",\"line2\":\"32nd street and 7th Avenue\",\"postal_code\":\"1630\",\"state\":\"Bonifacio Global City\"},\"email\":\"cieloivanlouis@gmail.com\",\"name\":\"Ivan Louis\",\"phone\":\"\"},\"currency\":\"PHP\",\"description\":\"Booking for 2 Bedroom Premium - 2025-12-25 to 2025-12-26\",\"digital_withholding_vat_amount\":0,\"disputed\":false,\"external_reference_number\":\"G9JLf3X\",\"fee\":9900,\"instant_settlement\":null,\"livemode\":false,\"net_amount\":440100,\"origin\":\"links\",\"payment_intent_id\":null,\"payout\":null,\"source\":{\"id\":\"src_JrTQA2TUa8i5fBVj6LniwNjf\",\"type\":\"grab_pay\",\"provider\":{\"id\":null},\"provider_id\":null},\"statement_descriptor\":\"RODEL MARASIGAN ANDAYA\",\"status\":\"paid\",\"tax_amount\":null,\"metadata\":{\"pm_reference_number\":\"G9JLf3X\"},\"promotion\":null,\"refunds\":[],\"taxes\":[],\"available_at\":1765962000,\"created_at\":1765748375,\"credited_at\":1766019600,\"paid_at\":1765748375,\"updated_at\":1765748375}}}]}}', '2025-12-14 21:39:35', '2025-12-14 21:39:35'),
(9, 'TXN_693f302a7ab24_1765748778', 9, 3, 'link_SjA7owGaFR4QpBvvACfsYoE8', 4500.00, 'PHP', NULL, 'paid', '{\"id\":\"link_SjA7owGaFR4QpBvvACfsYoE8\",\"type\":\"link\",\"attributes\":{\"amount\":450000,\"archived\":false,\"currency\":\"PHP\",\"description\":\"Booking for 2 Bedroom Premium - 2026-01-02 to 2026-01-03\",\"livemode\":false,\"fee\":11250,\"remarks\":\"Dwellscape Staycation Booking\",\"status\":\"paid\",\"tax_amount\":null,\"taxes\":[],\"checkout_url\":\"https:\\/\\/pm.link\\/org-oEaek5F9E8MJERN7vVRyAxx7\\/test\\/jRZwrPn\",\"reference_number\":\"jRZwrPn\",\"created_at\":1765748748,\"updated_at\":1765748748,\"payments\":[{\"data\":{\"id\":\"pay_CFmZhzoy5w7jHsw9bWjkuzKu\",\"type\":\"payment\",\"attributes\":{\"access_url\":null,\"amount\":450000,\"balance_transaction_id\":\"bal_txn_7F2eiYSfkZip2HBiTaGHDM9J\",\"billing\":{\"address\":{\"city\":\"Taguig\",\"country\":\"PH\",\"line1\":\"12th floor The Trade and Financial Tower u1206\",\"line2\":\"32nd street and 7th Avenue\",\"postal_code\":\"1630\",\"state\":\"Bonifacio Global City\"},\"email\":\"cieloivanlouis@gmail.com\",\"name\":\"Ivanad\",\"phone\":\"\"},\"currency\":\"PHP\",\"description\":\"Booking for 2 Bedroom Premium - 2026-01-02 to 2026-01-03\",\"digital_withholding_vat_amount\":0,\"disputed\":false,\"external_reference_number\":\"jRZwrPn\",\"fee\":11250,\"instant_settlement\":null,\"livemode\":false,\"net_amount\":438750,\"origin\":\"links\",\"payment_intent_id\":null,\"payout\":null,\"source\":{\"id\":\"src_LkssK5eA4cL2fCh9EX4PAB3E\",\"type\":\"gcash\",\"provider\":{\"id\":null},\"provider_id\":null},\"statement_descriptor\":\"RODEL MARASIGAN ANDAYA\",\"status\":\"paid\",\"tax_amount\":null,\"metadata\":{\"pm_reference_number\":\"jRZwrPn\"},\"promotion\":null,\"refunds\":[],\"taxes\":[],\"available_at\":1765962000,\"created_at\":1765748777,\"credited_at\":1766019600,\"paid_at\":1765748777,\"updated_at\":1765748777}}}]}}', '2025-12-14 21:46:18', '2025-12-14 21:46:18'),
(10, 'TXN_693f399b82f23_1765751195', 10, 3, 'link_QJyKK8GVSp3o8ZvsJCtQCd5M', 2500.00, 'PHP', NULL, 'paid', '{\"id\":\"link_QJyKK8GVSp3o8ZvsJCtQCd5M\",\"type\":\"link\",\"attributes\":{\"amount\":250000,\"archived\":false,\"currency\":\"PHP\",\"description\":\"Booking for 1 Bedroom Suite - 2026-01-08 to 2026-01-09\",\"livemode\":false,\"fee\":5500,\"remarks\":\"Dwellscape Staycation Booking\",\"status\":\"paid\",\"tax_amount\":null,\"taxes\":[],\"checkout_url\":\"https:\\/\\/pm.link\\/org-oEaek5F9E8MJERN7vVRyAxx7\\/test\\/VZyoaS5\",\"reference_number\":\"VZyoaS5\",\"created_at\":1765751165,\"updated_at\":1765751165,\"payments\":[{\"data\":{\"id\":\"pay_JgwE6x56JENCZoAJdBZujje8\",\"type\":\"payment\",\"attributes\":{\"access_url\":null,\"amount\":250000,\"balance_transaction_id\":\"bal_txn_MyF183JDT1YVnb6xbYRd3QB1\",\"billing\":{\"address\":{\"city\":\"Taguig\",\"country\":\"PH\",\"line1\":\"12th floor The Trade and Financial Tower u1206\",\"line2\":\"32nd street and 7th Avenue\",\"postal_code\":\"1630\",\"state\":\"Bonifacio Global City\"},\"email\":\"cieloivanlouis@gmail.com\",\"name\":\"Ivan\",\"phone\":\"\"},\"currency\":\"PHP\",\"description\":\"Booking for 1 Bedroom Suite - 2026-01-08 to 2026-01-09\",\"digital_withholding_vat_amount\":0,\"disputed\":false,\"external_reference_number\":\"VZyoaS5\",\"fee\":5500,\"instant_settlement\":null,\"livemode\":false,\"net_amount\":244500,\"origin\":\"links\",\"payment_intent_id\":null,\"payout\":null,\"source\":{\"id\":\"src_1LxNaJbu22zzreKKArZqfjQd\",\"type\":\"grab_pay\",\"provider\":{\"id\":null},\"provider_id\":null},\"statement_descriptor\":\"RODEL MARASIGAN ANDAYA\",\"status\":\"paid\",\"tax_amount\":null,\"metadata\":{\"pm_reference_number\":\"VZyoaS5\"},\"promotion\":null,\"refunds\":[],\"taxes\":[],\"available_at\":1765962000,\"created_at\":1765751194,\"credited_at\":1766019600,\"paid_at\":1765751194,\"updated_at\":1765751194}}}]}}', '2025-12-14 22:26:35', '2025-12-14 22:26:35');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `birthday` date DEFAULT NULL,
  `gender` varchar(10) DEFAULT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_token_expires` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `profile_picture` varchar(255) DEFAULT NULL,
  `is_admin` tinyint(1) DEFAULT 0,
  `role` varchar(20) DEFAULT 'user',
  `first_name` varchar(100) DEFAULT NULL,
  `middle_initial` varchar(5) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `birthday`, `gender`, `reset_token`, `reset_token_expires`, `created_at`, `updated_at`, `profile_picture`, `is_admin`, `role`, `first_name`, `middle_initial`, `last_name`) VALUES
(2, 'admin', 'admin@dwellscape.com', '$2y$10$2a2cJasXdla3sLeu94v5vOMzNeDrOTMe5DEP8RtWBf3fuzecR9eC2', NULL, NULL, NULL, NULL, '2025-12-14 19:50:46', '2025-12-14 19:50:46', NULL, 1, 'admin', NULL, NULL, NULL),
(3, 'ivan', 'cieloivanlouis@gmail.com', '$2y$10$zlTXWdEv3SAp8gYJ/3xRbOluKcVnvM5k8njbLlDnyayfmwpzTJWYK', '2002-12-11', 'male', NULL, NULL, '2025-12-14 20:23:26', '2025-12-14 21:46:50', 'uploads/profile_pictures/profile_3_1765748810.jpg', 0, 'user', 'Ivan Louis', 'D', 'Cielo');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `booking_id` (`booking_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_checkin_date` (`checkin_date`),
  ADD KEY `idx_checkout_date` (`checkout_date`),
  ADD KEY `idx_booking_id` (`booking_id`);

--
-- Indexes for table `gallery_images`
--
ALTER TABLE `gallery_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_display_order` (`display_order`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `room_id` (`room_id`),
  ADD KEY `idx_room_id` (`room_id`),
  ADD KEY `idx_is_available` (`is_available`);

--
-- Indexes for table `suggestions`
--
ALTER TABLE `suggestions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `transaction_id` (`transaction_id`),
  ADD KEY `idx_booking_id` (`booking_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_payment_status` (`payment_status`),
  ADD KEY `idx_transaction_id` (`transaction_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `gallery_images`
--
ALTER TABLE `gallery_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `suggestions`
--
ALTER TABLE `suggestions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `transactions_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
