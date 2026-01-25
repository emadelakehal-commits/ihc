-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Dec 17, 2025 at 12:01 AM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ihc_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` text NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `domain`
--

CREATE TABLE `domain` (
  `code` varchar(50) NOT NULL,
  `domain` varchar(255) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `domain`
--

INSERT INTO `domain` (`code`, `domain`, `name`) VALUES
('LT', 'lt.example.com', 'Lithuania Domain');

-- --------------------------------------------------------

--
-- Table structure for table `lkp_attribute`
--

CREATE TABLE `lkp_attribute` (
  `name` varchar(100) NOT NULL,
  `unit` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `lkp_attribute`
--

INSERT INTO `lkp_attribute` (`name`, `unit`) VALUES
('cable', NULL),
('colour', NULL),
('length', 'mm'),
('other', NULL),
('plug', NULL),
('power', 'W'),
('thickness', 'mm'),
('warranty', NULL),
('weight', 'g'),
('width', 'mm');

-- --------------------------------------------------------

--
-- Table structure for table `lkp_category`
--

CREATE TABLE `lkp_category` (
  `category_code` varchar(255) NOT NULL,
  `parent_code` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `lkp_category`
--

INSERT INTO `lkp_category` (`category_code`, `parent_code`, `created_at`, `updated_at`) VALUES
('books', NULL, '2025-12-16 11:09:18', '2025-12-16 11:09:18'),
('clothing', NULL, '2025-12-16 11:09:18', '2025-12-16 11:09:18'),
('electronics', NULL, '2025-12-16 11:09:18', '2025-12-16 11:09:18'),
('laptops', 'electronics', '2025-12-16 11:09:18', '2025-12-16 11:09:18'),
('shoes', 'clothing', '2025-12-16 11:09:18', '2025-12-16 11:09:18'),
('smartphones', 'electronics', '2025-12-16 11:09:18', '2025-12-16 11:09:18');

-- --------------------------------------------------------

--
-- Table structure for table `lkp_category_translation`
--

CREATE TABLE `lkp_category_translation` (
  `category_code` varchar(255) NOT NULL,
  `language` varchar(10) NOT NULL,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lkp_currency`
--

CREATE TABLE `lkp_currency` (
  `code` varchar(3) NOT NULL,
  `name` varchar(100) NOT NULL,
  `symbol` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `lkp_currency`
--

INSERT INTO `lkp_currency` (`code`, `name`, `symbol`) VALUES
('AUD', 'Australian Dollar', 'A$'),
('BRL', 'Brazilian Real', 'R$'),
('CAD', 'Canadian Dollar', 'C$'),
('CHF', 'Swiss Franc', 'CHF'),
('CNY', 'Chinese Yuan', '¥'),
('EUR', 'Euro', '€'),
('GBP', 'British Pound', '£'),
('HKD', 'Hong Kong Dollar', 'HK$'),
('INR', 'Indian Rupee', '₹'),
('JPY', 'Japanese Yen', '¥'),
('KRW', 'South Korean Won', '₩'),
('MXN', 'Mexican Peso', '$'),
('NOK', 'Norwegian Krone', 'kr'),
('NZD', 'New Zealand Dollar', 'NZ$'),
('RUB', 'Russian Ruble', '₽'),
('SEK', 'Swedish Krona', 'kr'),
('SGD', 'Singapore Dollar', 'S$'),
('TRY', 'Turkish Lira', '₺'),
('USD', 'US Dollar', '$'),
('ZAR', 'South African Rand', 'R');

-- --------------------------------------------------------

--
-- Table structure for table `lkp_language`
--

CREATE TABLE `lkp_language` (
  `code` varchar(10) NOT NULL,
  `name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `lkp_language`
--

INSERT INTO `lkp_language` (`code`, `name`) VALUES
('af', 'Afrikaans'),
('am', 'Amharic'),
('ar', 'Arabic'),
('as', 'Assamese'),
('az', 'Azerbaijani'),
('bg', 'Bulgarian'),
('bn', 'Bengali'),
('bo', 'Tibetan'),
('bs', 'Bosnian'),
('ca', 'Catalan'),
('cs', 'Czech'),
('cy', 'Welsh'),
('da', 'Danish'),
('de', 'German'),
('el', 'Greek'),
('en', 'English'),
('eo', 'Esperanto'),
('es', 'Spanish'),
('et', 'Estonian'),
('eu', 'Basque'),
('fa', 'Persian'),
('fi', 'Finnish'),
('fj', 'Fijian'),
('fr', 'French'),
('fy', 'Frisian'),
('ga', 'Irish'),
('gu', 'Gujarati'),
('haw', 'Hawaiian'),
('he', 'Hebrew'),
('hi', 'Hindi'),
('hr', 'Croatian'),
('hu', 'Hungarian'),
('hy', 'Armenian'),
('id', 'Indonesian'),
('is', 'Icelandic'),
('it', 'Italian'),
('ja', 'Japanese'),
('ka', 'Georgian'),
('kk', 'Kazakh'),
('km', 'Khmer'),
('kn', 'Kannada'),
('ko', 'Korean'),
('ky', 'Kyrgyz'),
('la', 'Latin'),
('lb', 'Luxembourgish'),
('lo', 'Lao'),
('lt', 'Lithuanian'),
('lv', 'Latvian'),
('mg', 'Malagasy'),
('mi', 'Maori'),
('mk', 'Macedonian'),
('ml', 'Malayalam'),
('mn', 'Mongolian'),
('mr', 'Marathi'),
('ms', 'Malay'),
('mt', 'Maltese'),
('my', 'Burmese'),
('ne', 'Nepali'),
('nl', 'Dutch'),
('no', 'Norwegian'),
('or', 'Oriya'),
('pa', 'Punjabi'),
('pl', 'Polish'),
('pt', 'Portuguese'),
('ro', 'Romanian'),
('ru', 'Russian'),
('sa', 'Sanskrit'),
('si', 'Sinhala'),
('sk', 'Slovak'),
('sl', 'Slovenian'),
('sm', 'Samoan'),
('sq', 'Albanian'),
('sr', 'Serbian'),
('sv', 'Swedish'),
('sw', 'Swahili'),
('ta', 'Tamil'),
('te', 'Telugu'),
('tg', 'Tajik'),
('th', 'Thai'),
('tl', 'Tagalog'),
('to', 'Tongan'),
('tr', 'Turkish'),
('ug', 'Uyghur'),
('ur', 'Urdu'),
('uz', 'Uzbek'),
('vi', 'Vietnamese'),
('zh', 'Chinese');

-- --------------------------------------------------------

--
-- Table structure for table `main_product`
--

CREATE TABLE `main_product` (
  `main_product_code` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `main_product_translation`
--

CREATE TABLE `main_product_translation` (
  `main_product_code` varchar(100) NOT NULL,
  `language` varchar(10) NOT NULL,
  `title` varchar(255) NOT NULL,
  `slogan` varchar(255) DEFAULT NULL,
  `summary` text DEFAULT NULL,
  `description` longtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(4, '2025_12_16_105638_create_main_products_table', 1),
(5, '2025_12_16_110024_rename_product_translation_to_main_product_translation_table', 1),
(6, '2025_12_16_110459_alter_main_product_translation_foreign_key', 2),
(7, '2025_12_16_130548_alter_lkp_category_table_change_primary_key', 3),
(8, '2025_12_16_152352_fix_main_product_translation_foreign_key', 4);

-- --------------------------------------------------------

--
-- Table structure for table `product`
--

CREATE TABLE `product` (
  `product_code` varchar(100) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `original_price` decimal(10,2) DEFAULT NULL,
  `original_price_currency` varchar(3) DEFAULT NULL,
  `rrp` decimal(10,2) DEFAULT NULL,
  `rrp_currency` varchar(3) DEFAULT NULL,
  `main_product_code` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_attribute_value`
--

CREATE TABLE `product_attribute_value` (
  `product_code` varchar(100) NOT NULL,
  `attribute_name` varchar(100) NOT NULL,
  `value` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_category`
--

CREATE TABLE `product_category` (
  `product_code` varchar(255) NOT NULL,
  `category_code` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_delivery`
--

CREATE TABLE `product_delivery` (
  `product_code` varchar(100) NOT NULL,
  `domain_id` varchar(50) NOT NULL,
  `delivery_min` int(11) NOT NULL,
  `delivery_max` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_document`
--

CREATE TABLE `product_document` (
  `id` bigint(20) NOT NULL,
  `product_code` varchar(100) NOT NULL,
  `doc_type` enum('manual','technical','warranty') NOT NULL,
  `file_url` varchar(500) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `domain`
--
ALTER TABLE `domain`
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `lkp_attribute`
--
ALTER TABLE `lkp_attribute`
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `lkp_category`
--
ALTER TABLE `lkp_category`
  ADD PRIMARY KEY (`category_code`),
  ADD KEY `lkp_category_parent_code_foreign` (`parent_code`);

--
-- Indexes for table `lkp_category_translation`
--
ALTER TABLE `lkp_category_translation`
  ADD PRIMARY KEY (`category_code`,`language`),
  ADD KEY `lkp_category_translation_language_foreign` (`language`);

--
-- Indexes for table `lkp_currency`
--
ALTER TABLE `lkp_currency`
  ADD PRIMARY KEY (`code`);

--
-- Indexes for table `lkp_language`
--
ALTER TABLE `lkp_language`
  ADD PRIMARY KEY (`code`);

--
-- Indexes for table `main_product`
--
ALTER TABLE `main_product`
  ADD PRIMARY KEY (`main_product_code`);

--
-- Indexes for table `main_product_translation`
--
ALTER TABLE `main_product_translation`
  ADD PRIMARY KEY (`main_product_code`,`language`),
  ADD KEY `main_product_translation_language_foreign` (`language`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `product`
--
ALTER TABLE `product`
  ADD PRIMARY KEY (`product_code`),
  ADD KEY `idx_product_active` (`is_active`);

--
-- Indexes for table `product_attribute_value`
--
ALTER TABLE `product_attribute_value`
  ADD PRIMARY KEY (`product_code`,`attribute_name`),
  ADD KEY `idx_attribute_value` (`attribute_name`,`value`);

--
-- Indexes for table `product_category`
--
ALTER TABLE `product_category`
  ADD PRIMARY KEY (`product_code`,`category_code`),
  ADD KEY `product_category_category_code_foreign` (`category_code`);

--
-- Indexes for table `product_delivery`
--
ALTER TABLE `product_delivery`
  ADD PRIMARY KEY (`product_code`,`domain_id`),
  ADD KEY `idx_delivery_domain` (`domain_id`);

--
-- Indexes for table `product_document`
--
ALTER TABLE `product_document`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_code` (`product_code`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `product_document`
--
ALTER TABLE `product_document`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `lkp_category`
--
ALTER TABLE `lkp_category`
  ADD CONSTRAINT `lkp_category_parent_code_foreign` FOREIGN KEY (`parent_code`) REFERENCES `lkp_category` (`category_code`) ON DELETE CASCADE;

--
-- Constraints for table `lkp_category_translation`
--
ALTER TABLE `lkp_category_translation`
  ADD CONSTRAINT `lkp_category_translation_category_code_foreign` FOREIGN KEY (`category_code`) REFERENCES `lkp_category` (`category_code`) ON DELETE CASCADE,
  ADD CONSTRAINT `lkp_category_translation_language_foreign` FOREIGN KEY (`language`) REFERENCES `lkp_language` (`code`);

--
-- Constraints for table `main_product_translation`
--
ALTER TABLE `main_product_translation`
  ADD CONSTRAINT `main_product_translation_ibfk_2` FOREIGN KEY (`language`) REFERENCES `lkp_language` (`code`),
  ADD CONSTRAINT `main_product_translation_language_foreign` FOREIGN KEY (`language`) REFERENCES `lkp_language` (`code`),
  ADD CONSTRAINT `main_product_translation_main_product_code_foreign` FOREIGN KEY (`main_product_code`) REFERENCES `main_product` (`main_product_code`) ON DELETE CASCADE;

--
-- Constraints for table `product_attribute_value`
--
ALTER TABLE `product_attribute_value`
  ADD CONSTRAINT `FK_product_attribute_value_attribute` FOREIGN KEY (`attribute_name`) REFERENCES `lkp_attribute` (`name`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_attribute_value_ibfk_1` FOREIGN KEY (`product_code`) REFERENCES `product` (`product_code`) ON DELETE CASCADE;

--
-- Constraints for table `product_category`
--
ALTER TABLE `product_category`
  ADD CONSTRAINT `product_category_category_code_foreign` FOREIGN KEY (`category_code`) REFERENCES `lkp_category` (`category_code`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_category_product_code_foreign` FOREIGN KEY (`product_code`) REFERENCES `product` (`product_code`) ON DELETE CASCADE;

--
-- Constraints for table `product_delivery`
--
ALTER TABLE `product_delivery`
  ADD CONSTRAINT `FK_product_delivery_domain` FOREIGN KEY (`domain_id`) REFERENCES `domain` (`code`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_delivery_ibfk_1` FOREIGN KEY (`product_code`) REFERENCES `product` (`product_code`) ON DELETE CASCADE;

--
-- Constraints for table `product_document`
--
ALTER TABLE `product_document`
  ADD CONSTRAINT `product_document_ibfk_1` FOREIGN KEY (`product_code`) REFERENCES `product` (`product_code`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
