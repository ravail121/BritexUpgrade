-- phpMyAdmin SQL Dump
-- version 4.8.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 09, 2018 at 12:59 PM
-- Server version: 10.1.34-MariaDB
-- PHP Version: 7.2.7

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `britex`
--

-- --------------------------------------------------------

--
-- Table structure for table `addon`
--

CREATE TABLE `addon` (
  `id` int(10) UNSIGNED NOT NULL,
  `company_id` int(10) UNSIGNED NOT NULL,
  `name` char(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `notes` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `image` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount_recurring` double NOT NULL,
  `taxable` tinyint(4) NOT NULL,
  `show` tinyint(4) NOT NULL,
  `sku` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `soc_code` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `bot_code` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `addon`
--

INSERT INTO `addon` (`id`, `company_id`, `name`, `description`, `notes`, `image`, `amount_recurring`, `taxable`, `show`, `sku`, `soc_code`, `bot_code`, `created_at`, `updated_at`) VALUES
(1, 1, 'suraj', 'it works', 'sgggsgs', 'sgsgsgsg', 2000.3, 1, 1, 'hdhhdhd', 'sdghsj', 'xbxbx', NULL, NULL),
(2, 1, 'suraj', 'it works', 'sgggsgs', 'sgsgsgsg', 2000.3, 1, 1, 'hdhhdhd', 'sdghsj', 'xbxbx', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `bans`
--

CREATE TABLE `bans` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `number` int(11) NOT NULL,
  `billing_day` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bans_notes`
--

CREATE TABLE `bans_notes` (
  `id` int(10) UNSIGNED NOT NULL,
  `staff_id` int(10) UNSIGNED NOT NULL,
  `date` date NOT NULL,
  `text` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ban_groups`
--

CREATE TABLE `ban_groups` (
  `id` int(10) UNSIGNED NOT NULL,
  `ban_id` int(10) UNSIGNED NOT NULL,
  `group_id` char(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `business_verification`
--

CREATE TABLE `business_verification` (
  `id` int(10) UNSIGNED NOT NULL,
  `approved` tinyint(4) NOT NULL,
  `hash` char(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `business_name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `tax_id` int(11) NOT NULL,
  `fname` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `lname` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `address_line1` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `address_line2` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `city` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `state` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `zip` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `business_verification`
--

INSERT INTO `business_verification` (`id`, `approved`, `hash`, `business_name`, `tax_id`, `fname`, `lname`, `email`, `address_line1`, `address_line2`, `city`, `state`, `zip`, `created_at`, `updated_at`) VALUES
(1, 122, 'hardare', 'xyz', 1, 'suraj', 'bhattarai', 'suraj.bh46@gmail.com', 'block g', 'millan tol', 'lamahi', 'five', 'egegege', NULL, NULL),
(2, 122, 'hardare', 'xyz', 1, 'suraj', 'bhattarai', 'suraj.bh46@gmail.com', 'block g', 'millan tol', 'lamahi', 'five', 'egegege', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `business_verification_docs`
--

CREATE TABLE `business_verification_docs` (
  `id` int(10) UNSIGNED NOT NULL,
  `bus_ver_id` int(10) UNSIGNED NOT NULL,
  `src` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `carriers`
--

CREATE TABLE `carriers` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `carrier_blocks`
--

CREATE TABLE `carrier_blocks` (
  `id` int(10) UNSIGNED NOT NULL,
  `carrier_id` int(10) UNSIGNED NOT NULL,
  `type` int(11) NOT NULL,
  `display_name` int(11) NOT NULL,
  `bot_code` int(11) NOT NULL,
  `soc_code` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `companies`
--

CREATE TABLE `companies` (
  `id` int(10) UNSIGNED NOT NULL,
  `api_key` char(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` char(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `url` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `selling_devices` tinyint(4) NOT NULL DEFAULT '0',
  `selling_plans` tinyint(4) NOT NULL,
  `selling_addons` tinyint(4) NOT NULL,
  `selling_sim_standalone` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `business_verification` tinyint(4) NOT NULL DEFAULT '0',
  `regulatory_label` char(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Regulatory',
  `default_reg_fee` double NOT NULL,
  `sprint_api_key` char(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `companies`
--

INSERT INTO `companies` (`id`, `api_key`, `name`, `url`, `selling_devices`, `selling_plans`, `selling_addons`, `selling_sim_standalone`, `business_verification`, `regulatory_label`, `default_reg_fee`, `sprint_api_key`, `created_at`, `updated_at`) VALUES
(1, 'xyz', 'britex', 'www.britex.com', 122, 123, 123, 'testing ', 123, 'Regulatory', 123.09, 'fsfsgsg', NULL, NULL),
(2, 'xyz', 'britex', 'www.britex.com', 122, 123, 123, 'testing ', 123, 'Regulatory', 123.09, 'fsfsgsg', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `company_to_carriers`
--

CREATE TABLE `company_to_carriers` (
  `id` int(10) UNSIGNED NOT NULL,
  `company_id` int(10) UNSIGNED NOT NULL,
  `carrier_id` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `coupons`
--

CREATE TABLE `coupons` (
  `id` int(10) UNSIGNED NOT NULL,
  `company_id` int(10) UNSIGNED NOT NULL,
  `active` tinyint(4) NOT NULL,
  `class` tinyint(4) NOT NULL,
  `fixed_or_perc` tinyint(4) NOT NULL,
  `amount` double NOT NULL,
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `num_cycles` int(11) NOT NULL,
  `max_uses` int(11) NOT NULL,
  `num_uses` int(11) NOT NULL,
  `stackable` tinyint(4) NOT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `multiline_min` int(11) NOT NULL,
  `multiline_max` int(11) NOT NULL,
  `multiline_restrict_plans` tinyint(4) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `coupon_multiline_plan_types`
--

CREATE TABLE `coupon_multiline_plan_types` (
  `id` int(10) UNSIGNED NOT NULL,
  `coupon_id` int(10) UNSIGNED NOT NULL,
  `plan_types` tinyint(4) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `coupon_product_types`
--

CREATE TABLE `coupon_product_types` (
  `id` int(10) UNSIGNED NOT NULL,
  `coupon_id` int(10) UNSIGNED NOT NULL,
  `amount` double NOT NULL,
  `type` tinyint(4) NOT NULL,
  `sub_type` tinyint(4) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(10) UNSIGNED NOT NULL,
  `hash` char(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `company_id` int(10) UNSIGNED NOT NULL,
  `business_verification_id` int(10) UNSIGNED NOT NULL,
  `subscription_start_date` date NOT NULL,
  `billing_start` date NOT NULL,
  `billing_end` date NOT NULL,
  `primary_payment_method` int(11) NOT NULL,
  `primary_payment_card` int(11) NOT NULL,
  `account_suspended` tinyint(4) NOT NULL,
  `billing_address1` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `billing_address2` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `billing_city` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `billing_state_id` char(2) COLLATE utf8mb4_unicode_ci NOT NULL,
  `shipping_address1` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `shipping_address2` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `shipping_city` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `shipping_state_id` char(2) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `hash`, `company_id`, `business_verification_id`, `subscription_start_date`, `billing_start`, `billing_end`, `primary_payment_method`, `primary_payment_card`, `account_suspended`, `billing_address1`, `billing_address2`, `billing_city`, `billing_state_id`, `shipping_address1`, `shipping_address2`, `shipping_city`, `shipping_state_id`, `created_at`, `updated_at`) VALUES
(1, 'ssd', 1, 1, '0000-00-00', '0000-00-00', '0000-00-00', 1, 1, 1, 'lamahi', 'dang', 'ghorahi', '', 'narayanpur', 'tulsipur', 'butwal', '1', NULL, NULL),
(2, 'ssd', 1, 1, '0000-00-00', '0000-00-00', '0000-00-00', 1, 1, 1, 'lamahi', 'dang', 'ghorahi', '', 'narayanpur', 'tulsipur', 'butwal', '1', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `customer_notes`
--

CREATE TABLE `customer_notes` (
  `id` int(10) UNSIGNED NOT NULL,
  `customer_id` int(10) UNSIGNED NOT NULL,
  `staff_id` int(10) UNSIGNED NOT NULL,
  `date` date NOT NULL,
  `text` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `default_imeis`
--

CREATE TABLE `default_imeis` (
  `id` int(10) UNSIGNED NOT NULL,
  `type` int(11) NOT NULL,
  `os` char(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `device`
--

CREATE TABLE `device` (
  `id` int(10) UNSIGNED NOT NULL,
  `company_id` int(10) UNSIGNED NOT NULL,
  `carrier_id` int(10) UNSIGNED NOT NULL,
  `type` int(11) NOT NULL,
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `tag_id` int(10) UNSIGNED NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `primary_image` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` double NOT NULL,
  `amount_w_plan` double NOT NULL,
  `taxable` tinyint(4) NOT NULL,
  `associate_with_plan` tinyint(4) NOT NULL,
  `show` tinyint(4) NOT NULL,
  `sku` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `device_images`
--

CREATE TABLE `device_images` (
  `id` int(10) UNSIGNED NOT NULL,
  `device_id` int(10) UNSIGNED NOT NULL,
  `source` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `device_to_carrier`
--

CREATE TABLE `device_to_carrier` (
  `id` int(10) UNSIGNED NOT NULL,
  `device_id` int(10) UNSIGNED NOT NULL,
  `carrier_id` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `device_to_plan`
--

CREATE TABLE `device_to_plan` (
  `id` int(10) UNSIGNED NOT NULL,
  `device_id` int(10) UNSIGNED NOT NULL,
  `plan_id` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `device_to_sim`
--

CREATE TABLE `device_to_sim` (
  `id` int(10) UNSIGNED NOT NULL,
  `device_id` int(10) UNSIGNED NOT NULL,
  `sim_id` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `device__groups`
--

CREATE TABLE `device__groups` (
  `id` int(10) UNSIGNED NOT NULL,
  `company_id` int(10) UNSIGNED NOT NULL,
  `name` char(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `email_template`
--

CREATE TABLE `email_template` (
  `id` int(10) UNSIGNED NOT NULL,
  `company_id` int(10) UNSIGNED NOT NULL,
  `code` char(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `from` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `to` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `body` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `reply_to` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `cc` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `bcc` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

CREATE TABLE `invoices` (
  `id` int(10) UNSIGNED NOT NULL,
  `customer_id` int(10) UNSIGNED NOT NULL,
  `type` tinyint(4) NOT NULL,
  `status` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `due_date` date NOT NULL,
  `subtotal` double NOT NULL,
  `total_due` double NOT NULL,
  `prev_balance` double NOT NULL,
  `payment_method` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `business_name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `billing_fname` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `billing_lname` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `billing_address_line_1` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `billing_address_line_2` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `billing_city` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `billing_state` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `billing_zip` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `shipping_fname` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `shipping_lname` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `shipping_address_line_1` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `shipping_address_line_2` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `shipping_city` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `shipping_state` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `shipping_zip` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `invoices`
--

INSERT INTO `invoices` (`id`, `customer_id`, `type`, `status`, `start_date`, `end_date`, `due_date`, `subtotal`, `total_due`, `prev_balance`, `payment_method`, `business_name`, `billing_fname`, `billing_lname`, `billing_address_line_1`, `billing_address_line_2`, `billing_city`, `billing_state`, `billing_zip`, `shipping_fname`, `shipping_lname`, `shipping_address_line_1`, `shipping_address_line_2`, `shipping_city`, `shipping_state`, `shipping_zip`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, '0000-00-00', '0000-00-00', '0000-00-00', 1221323.89, 2323442.34, 224344, 'cheque', 'xyz', 'suraj', 'bhatttari', 'dang', 'ghorahi', 'pmb', 'five', 'wregeg', 'adc', 'ch', 'shshhs', 'bfs', 'xhs', 'six', 'fegehehee', NULL, NULL),
(2, 1, 1, 1, '0000-00-00', '0000-00-00', '0000-00-00', 1221323.89, 2323442.34, 224344, 'cheque', 'xyz', 'suraj', 'bhatttari', 'dang', 'ghorahi', 'pmb', 'five', 'wregeg', 'adc', 'ch', 'shshhs', 'bfs', 'xhs', 'six', 'fegehehee', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `invoice_item`
--

CREATE TABLE `invoice_item` (
  `id` int(10) UNSIGNED NOT NULL,
  `invoie_id` int(10) UNSIGNED NOT NULL,
  `subscription_id` int(10) UNSIGNED NOT NULL,
  `product_type` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `product_id` int(11) NOT NULL,
  `type` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` double NOT NULL,
  `taxable` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '2014_10_12_000000_create_users_table', 1),
(2, '2014_10_12_100000_create_password_resets_table', 1),
(3, '2018_07_31_062413_create_order_table', 1);

-- --------------------------------------------------------

--
-- Table structure for table `order`
--

CREATE TABLE `order` (
  `id` int(10) UNSIGNED NOT NULL,
  `active_group_id` int(11) NOT NULL,
  `Active_subscription_id` int(11) NOT NULL,
  `order_num` int(11) NOT NULL,
  `status` tinyint(4) NOT NULL,
  `invoice_id` int(10) UNSIGNED NOT NULL,
  `hash` char(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `company_id` int(10) UNSIGNED NOT NULL,
  `customer_id` int(10) UNSIGNED NOT NULL,
  `date_processed` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order`
--

INSERT INTO `order` (`id`, `active_group_id`, `Active_subscription_id`, `order_num`, `status`, `invoice_id`, `hash`, `company_id`, `customer_id`, `date_processed`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, 1, 1, 'software', 1, 1, '0000-00-00', NULL, NULL),
(3, 1, 1, 1, 1, 1, 'software', 1, 1, '0000-00-00', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `order_coupons`
--

CREATE TABLE `order_coupons` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED NOT NULL,
  `coupon_id` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_group`
--

CREATE TABLE `order_group` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED NOT NULL,
  `closed` tinyint(4) NOT NULL,
  `device_id` int(10) UNSIGNED NOT NULL,
  `plan_id` int(10) UNSIGNED NOT NULL,
  `sim_id` int(10) UNSIGNED NOT NULL,
  `sim_num` int(11) NOT NULL,
  `sim_type` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `require_plan` tinyint(4) NOT NULL,
  `require_device` tinyint(4) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_group_addon`
--

CREATE TABLE `order_group_addon` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_group_id` int(10) UNSIGNED NOT NULL,
  `addon_id` int(10) UNSIGNED NOT NULL,
  `subscription_id` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `email` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pending_charges`
--

CREATE TABLE `pending_charges` (
  `id` int(10) UNSIGNED NOT NULL,
  `customer_id` int(10) UNSIGNED NOT NULL,
  `invoice_id` int(10) UNSIGNED NOT NULL,
  `type` tinyint(4) NOT NULL,
  `amount` double NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `plan`
--

CREATE TABLE `plan` (
  `id` int(10) UNSIGNED NOT NULL,
  `device_id` int(10) UNSIGNED NOT NULL,
  `carrier_id` int(10) UNSIGNED NOT NULL,
  `type` int(11) NOT NULL,
  `tag_id` int(10) UNSIGNED NOT NULL,
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `image` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `primary_image` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount_recurring` double NOT NULL,
  `amount_onetime` double NOT NULL,
  `regulatory_fee_type` tinyint(4) NOT NULL,
  `regulatory_fee_amount` double NOT NULL,
  `taxable` tinyint(4) NOT NULL,
  `show` tinyint(4) NOT NULL,
  `sku` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `data_limit` int(11) NOT NULL,
  `rate_plan_soc` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `rate_plan_bot_code` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `data_soc` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `signup_porting` tinyint(4) NOT NULL,
  `subsequent_porting` tinyint(4) NOT NULL,
  `associate_with_device` tinyint(4) NOT NULL,
  `affilate_credit` tinyint(4) NOT NULL DEFAULT '1',
  `sim_required` tinyint(4) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `plan_blocks`
--

CREATE TABLE `plan_blocks` (
  `id` int(10) UNSIGNED NOT NULL,
  `plan_id` int(10) UNSIGNED NOT NULL,
  `carrier_block_id` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `plan_custom_types`
--

CREATE TABLE `plan_custom_types` (
  `id` int(10) UNSIGNED NOT NULL,
  `company_id` int(10) UNSIGNED NOT NULL,
  `plan_id` int(10) UNSIGNED NOT NULL,
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `plan_data_soc_bot_codes`
--

CREATE TABLE `plan_data_soc_bot_codes` (
  `id` int(10) UNSIGNED NOT NULL,
  `plan_id` int(10) UNSIGNED NOT NULL,
  `data_soc_bot_code` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `plan_to_addon`
--

CREATE TABLE `plan_to_addon` (
  `id` int(10) UNSIGNED NOT NULL,
  `plan_id` int(10) UNSIGNED NOT NULL,
  `addon_id` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ports`
--

CREATE TABLE `ports` (
  `id` int(10) UNSIGNED NOT NULL,
  `subscription_id` int(10) UNSIGNED NOT NULL,
  `status` tinyint(4) NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `number_to_port` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `company_porting_from` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `account_number_porting_from` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `authorized_name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `address_line1` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `address_line2` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `city` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `state` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `zip` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `ssn_taxid` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `port_note`
--

CREATE TABLE `port_note` (
  `id` int(10) UNSIGNED NOT NULL,
  `port_id` int(10) UNSIGNED NOT NULL,
  `staff_id` int(10) UNSIGNED NOT NULL,
  `date` date NOT NULL,
  `text` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sim`
--

CREATE TABLE `sim` (
  `id` int(10) UNSIGNED NOT NULL,
  `company_id` int(10) UNSIGNED NOT NULL,
  `carrier_id` int(10) UNSIGNED NOT NULL,
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `image` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount_alone` double NOT NULL,
  `amount_w_plan` double NOT NULL,
  `taxable` tinyint(4) NOT NULL,
  `show` tinyint(4) NOT NULL,
  `sku` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `id` int(10) UNSIGNED NOT NULL,
  `company_id` int(10) UNSIGNED NOT NULL,
  `level` int(11) NOT NULL,
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` char(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` char(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reset_hash` char(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` char(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subscriptions`
--

CREATE TABLE `subscriptions` (
  `id` int(10) UNSIGNED NOT NULL,
  `customer_id` int(10) UNSIGNED NOT NULL,
  `plan_id` int(10) UNSIGNED NOT NULL,
  `phone_number` char(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` char(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `suspend_restore_status` char(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `upgrade_downgrade_status` char(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `porting_status` char(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sim_card_product_id` int(11) NOT NULL,
  `sim_card_num` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `old_plan_id` int(11) NOT NULL,
  `new_plan_id` int(11) NOT NULL,
  `downgrade_date` date NOT NULL,
  `imei` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `subsequent_porting` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `ban_id` int(10) UNSIGNED NOT NULL,
  `ban_group_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subscription_addon`
--

CREATE TABLE `subscription_addon` (
  `id` int(10) UNSIGNED NOT NULL,
  `subscription_id` int(10) UNSIGNED NOT NULL,
  `addon_id` int(10) UNSIGNED NOT NULL,
  `status` char(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `removal_date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subscription_coupons`
--

CREATE TABLE `subscription_coupons` (
  `id` int(10) UNSIGNED NOT NULL,
  `subscription_id` int(10) UNSIGNED NOT NULL,
  `coupon_id` int(10) UNSIGNED NOT NULL,
  `cycles_remaining` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subscription_log`
--

CREATE TABLE `subscription_log` (
  `id` int(10) UNSIGNED NOT NULL,
  `company_id` int(10) UNSIGNED NOT NULL,
  `customer_id` int(10) UNSIGNED NOT NULL,
  `subscription_id` int(10) UNSIGNED NOT NULL,
  `date` date NOT NULL,
  `category` char(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `product_id` int(11) NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `old_product` int(11) NOT NULL,
  `new_product` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `system_email_templates`
--

CREATE TABLE `system_email_templates` (
  `id` int(10) UNSIGNED NOT NULL,
  `code` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `system_global_setting`
--

CREATE TABLE `system_global_setting` (
  `id` int(10) UNSIGNED NOT NULL,
  `site_url` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `upload_path` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tags`
--

CREATE TABLE `tags` (
  `id` int(10) UNSIGNED NOT NULL,
  `company_id` int(10) UNSIGNED NOT NULL,
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `taxes`
--

CREATE TABLE `taxes` (
  `id` int(10) UNSIGNED NOT NULL,
  `company_id` int(10) UNSIGNED NOT NULL,
  `state` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `rate` double NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `taxes`
--

INSERT INTO `taxes` (`id`, `company_id`, `state`, `rate`, `created_at`, `updated_at`) VALUES
(3, 1, 'five', 1200.9, NULL, NULL),
(4, 1, 'five', 1200.9, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `addon`
--
ALTER TABLE `addon`
  ADD PRIMARY KEY (`id`),
  ADD KEY `addon_company_id_foreign` (`company_id`);

--
-- Indexes for table `bans`
--
ALTER TABLE `bans`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bans_notes`
--
ALTER TABLE `bans_notes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `bans_notes_staff_id_foreign` (`staff_id`);

--
-- Indexes for table `ban_groups`
--
ALTER TABLE `ban_groups`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ban_groups_ban_id_foreign` (`ban_id`);

--
-- Indexes for table `business_verification`
--
ALTER TABLE `business_verification`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `business_verification_docs`
--
ALTER TABLE `business_verification_docs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `business_verification_docs_bus_ver_id_foreign` (`bus_ver_id`);

--
-- Indexes for table `carriers`
--
ALTER TABLE `carriers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `carrier_blocks`
--
ALTER TABLE `carrier_blocks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `carrier_blocks_carrier_id_foreign` (`carrier_id`);

--
-- Indexes for table `companies`
--
ALTER TABLE `companies`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `company_to_carriers`
--
ALTER TABLE `company_to_carriers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `company_to_carriers_company_id_foreign` (`company_id`),
  ADD KEY `company_to_carriers_carrier_id_foreign` (`carrier_id`);

--
-- Indexes for table `coupons`
--
ALTER TABLE `coupons`
  ADD PRIMARY KEY (`id`),
  ADD KEY `coupons_company_id_foreign` (`company_id`);

--
-- Indexes for table `coupon_multiline_plan_types`
--
ALTER TABLE `coupon_multiline_plan_types`
  ADD PRIMARY KEY (`id`),
  ADD KEY `coupon_multiline_plan_types_coupon_id_foreign` (`coupon_id`);

--
-- Indexes for table `coupon_product_types`
--
ALTER TABLE `coupon_product_types`
  ADD PRIMARY KEY (`id`),
  ADD KEY `coupon_product_types_coupon_id_foreign` (`coupon_id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customers_company_id_foreign` (`company_id`),
  ADD KEY `customers_business_verification_id_foreign` (`business_verification_id`);

--
-- Indexes for table `customer_notes`
--
ALTER TABLE `customer_notes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_notes_customer_id_foreign` (`customer_id`),
  ADD KEY `customer_notes_staff_id_foreign` (`staff_id`);

--
-- Indexes for table `default_imeis`
--
ALTER TABLE `default_imeis`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `device`
--
ALTER TABLE `device`
  ADD PRIMARY KEY (`id`),
  ADD KEY `device_company_id_foreign` (`company_id`),
  ADD KEY `device_carrier_id_foreign` (`carrier_id`),
  ADD KEY `device_tag_id_foreign` (`tag_id`);

--
-- Indexes for table `device_images`
--
ALTER TABLE `device_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `device_images_device_id_foreign` (`device_id`);

--
-- Indexes for table `device_to_carrier`
--
ALTER TABLE `device_to_carrier`
  ADD PRIMARY KEY (`id`),
  ADD KEY `device_to_carrier_device_id_foreign` (`device_id`),
  ADD KEY `device_to_carrier_carrier_id_foreign` (`carrier_id`);

--
-- Indexes for table `device_to_plan`
--
ALTER TABLE `device_to_plan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `device_to_plan_device_id_foreign` (`device_id`),
  ADD KEY `device_to_plan_plan_id_foreign` (`plan_id`);

--
-- Indexes for table `device_to_sim`
--
ALTER TABLE `device_to_sim`
  ADD PRIMARY KEY (`id`),
  ADD KEY `device_to_sim_device_id_foreign` (`device_id`),
  ADD KEY `device_to_sim_sim_id_foreign` (`sim_id`);

--
-- Indexes for table `device__groups`
--
ALTER TABLE `device__groups`
  ADD PRIMARY KEY (`id`),
  ADD KEY `device__groups_company_id_foreign` (`company_id`);

--
-- Indexes for table `email_template`
--
ALTER TABLE `email_template`
  ADD PRIMARY KEY (`id`),
  ADD KEY `email_template_company_id_foreign` (`company_id`);

--
-- Indexes for table `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`id`),
  ADD KEY `invoices_customer_id_foreign` (`customer_id`);

--
-- Indexes for table `invoice_item`
--
ALTER TABLE `invoice_item`
  ADD PRIMARY KEY (`id`),
  ADD KEY `invoice_item_invoie_id_foreign` (`invoie_id`),
  ADD KEY `invoice_item_subscription_id_foreign` (`subscription_id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `order`
--
ALTER TABLE `order`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_invoice_id_foreign` (`invoice_id`),
  ADD KEY `order_company_id_foreign` (`company_id`),
  ADD KEY `order_customer_id_foreign` (`customer_id`);

--
-- Indexes for table `order_coupons`
--
ALTER TABLE `order_coupons`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_coupons_order_id_foreign` (`order_id`),
  ADD KEY `order_coupons_coupon_id_foreign` (`coupon_id`);

--
-- Indexes for table `order_group`
--
ALTER TABLE `order_group`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_group_order_id_foreign` (`order_id`),
  ADD KEY `order_group_device_id_foreign` (`device_id`),
  ADD KEY `order_group_plan_id_foreign` (`plan_id`),
  ADD KEY `order_group_sim_id_foreign` (`sim_id`);

--
-- Indexes for table `order_group_addon`
--
ALTER TABLE `order_group_addon`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_group_addon_order_group_id_foreign` (`order_group_id`),
  ADD KEY `order_group_addon_addon_id_foreign` (`addon_id`),
  ADD KEY `order_group_addon_subscription_id_foreign` (`subscription_id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD KEY `password_resets_email_index` (`email`);

--
-- Indexes for table `pending_charges`
--
ALTER TABLE `pending_charges`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pending_charges_customer_id_foreign` (`customer_id`),
  ADD KEY `pending_charges_invoice_id_foreign` (`invoice_id`);

--
-- Indexes for table `plan`
--
ALTER TABLE `plan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `plan_device_id_foreign` (`device_id`),
  ADD KEY `plan_carrier_id_foreign` (`carrier_id`),
  ADD KEY `plan_tag_id_foreign` (`tag_id`);

--
-- Indexes for table `plan_blocks`
--
ALTER TABLE `plan_blocks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `plan_blocks_plan_id_foreign` (`plan_id`),
  ADD KEY `plan_blocks_carrier_block_id_foreign` (`carrier_block_id`);

--
-- Indexes for table `plan_custom_types`
--
ALTER TABLE `plan_custom_types`
  ADD PRIMARY KEY (`id`),
  ADD KEY `plan_custom_types_company_id_foreign` (`company_id`),
  ADD KEY `plan_custom_types_plan_id_foreign` (`plan_id`);

--
-- Indexes for table `plan_data_soc_bot_codes`
--
ALTER TABLE `plan_data_soc_bot_codes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `plan_data_soc_bot_codes_plan_id_foreign` (`plan_id`);

--
-- Indexes for table `plan_to_addon`
--
ALTER TABLE `plan_to_addon`
  ADD PRIMARY KEY (`id`),
  ADD KEY `plan_to_addon_plan_id_foreign` (`plan_id`),
  ADD KEY `plan_to_addon_addon_id_foreign` (`addon_id`);

--
-- Indexes for table `ports`
--
ALTER TABLE `ports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ports_subscription_id_foreign` (`subscription_id`);

--
-- Indexes for table `port_note`
--
ALTER TABLE `port_note`
  ADD PRIMARY KEY (`id`),
  ADD KEY `port_note_port_id_foreign` (`port_id`),
  ADD KEY `port_note_staff_id_foreign` (`staff_id`);

--
-- Indexes for table `sim`
--
ALTER TABLE `sim`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sim_company_id_foreign` (`company_id`),
  ADD KEY `sim_carrier_id_foreign` (`carrier_id`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`id`),
  ADD KEY `staff_company_id_foreign` (`company_id`);

--
-- Indexes for table `subscriptions`
--
ALTER TABLE `subscriptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `subscriptions_customer_id_foreign` (`customer_id`),
  ADD KEY `subscriptions_plan_id_foreign` (`plan_id`),
  ADD KEY `subscriptions_ban_id_foreign` (`ban_id`),
  ADD KEY `subscriptions_ban_group_id_foreign` (`ban_group_id`);

--
-- Indexes for table `subscription_addon`
--
ALTER TABLE `subscription_addon`
  ADD PRIMARY KEY (`id`),
  ADD KEY `subscription_addon_subscription_id_foreign` (`subscription_id`),
  ADD KEY `subscription_addon_addon_id_foreign` (`addon_id`);

--
-- Indexes for table `subscription_coupons`
--
ALTER TABLE `subscription_coupons`
  ADD PRIMARY KEY (`id`),
  ADD KEY `subscription_coupons_subscription_id_foreign` (`subscription_id`),
  ADD KEY `subscription_coupons_coupon_id_foreign` (`coupon_id`);

--
-- Indexes for table `subscription_log`
--
ALTER TABLE `subscription_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `subscription_log_company_id_foreign` (`company_id`),
  ADD KEY `subscription_log_customer_id_foreign` (`customer_id`),
  ADD KEY `subscription_log_subscription_id_foreign` (`subscription_id`);

--
-- Indexes for table `system_email_templates`
--
ALTER TABLE `system_email_templates`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `system_global_setting`
--
ALTER TABLE `system_global_setting`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tags`
--
ALTER TABLE `tags`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tags_company_id_foreign` (`company_id`);

--
-- Indexes for table `taxes`
--
ALTER TABLE `taxes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `taxes_company_id_foreign` (`company_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `addon`
--
ALTER TABLE `addon`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `bans`
--
ALTER TABLE `bans`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bans_notes`
--
ALTER TABLE `bans_notes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ban_groups`
--
ALTER TABLE `ban_groups`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `business_verification`
--
ALTER TABLE `business_verification`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `business_verification_docs`
--
ALTER TABLE `business_verification_docs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `carriers`
--
ALTER TABLE `carriers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `carrier_blocks`
--
ALTER TABLE `carrier_blocks`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `companies`
--
ALTER TABLE `companies`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `company_to_carriers`
--
ALTER TABLE `company_to_carriers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `coupons`
--
ALTER TABLE `coupons`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `coupon_multiline_plan_types`
--
ALTER TABLE `coupon_multiline_plan_types`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `coupon_product_types`
--
ALTER TABLE `coupon_product_types`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `customer_notes`
--
ALTER TABLE `customer_notes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `default_imeis`
--
ALTER TABLE `default_imeis`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `device`
--
ALTER TABLE `device`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `device_images`
--
ALTER TABLE `device_images`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `device_to_carrier`
--
ALTER TABLE `device_to_carrier`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `device_to_plan`
--
ALTER TABLE `device_to_plan`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `device_to_sim`
--
ALTER TABLE `device_to_sim`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `device__groups`
--
ALTER TABLE `device__groups`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `email_template`
--
ALTER TABLE `email_template`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `invoice_item`
--
ALTER TABLE `invoice_item`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `order`
--
ALTER TABLE `order`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `order_coupons`
--
ALTER TABLE `order_coupons`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_group`
--
ALTER TABLE `order_group`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_group_addon`
--
ALTER TABLE `order_group_addon`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pending_charges`
--
ALTER TABLE `pending_charges`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `plan`
--
ALTER TABLE `plan`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `plan_blocks`
--
ALTER TABLE `plan_blocks`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `plan_custom_types`
--
ALTER TABLE `plan_custom_types`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `plan_data_soc_bot_codes`
--
ALTER TABLE `plan_data_soc_bot_codes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `plan_to_addon`
--
ALTER TABLE `plan_to_addon`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ports`
--
ALTER TABLE `ports`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `port_note`
--
ALTER TABLE `port_note`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sim`
--
ALTER TABLE `sim`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `subscriptions`
--
ALTER TABLE `subscriptions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `subscription_addon`
--
ALTER TABLE `subscription_addon`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `subscription_coupons`
--
ALTER TABLE `subscription_coupons`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `subscription_log`
--
ALTER TABLE `subscription_log`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `system_email_templates`
--
ALTER TABLE `system_email_templates`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `system_global_setting`
--
ALTER TABLE `system_global_setting`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tags`
--
ALTER TABLE `tags`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `taxes`
--
ALTER TABLE `taxes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `addon`
--
ALTER TABLE `addon`
  ADD CONSTRAINT `addon_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`);

--
-- Constraints for table `bans_notes`
--
ALTER TABLE `bans_notes`
  ADD CONSTRAINT `bans_notes_staff_id_foreign` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`);

--
-- Constraints for table `ban_groups`
--
ALTER TABLE `ban_groups`
  ADD CONSTRAINT `ban_groups_ban_id_foreign` FOREIGN KEY (`ban_id`) REFERENCES `bans` (`id`);

--
-- Constraints for table `business_verification_docs`
--
ALTER TABLE `business_verification_docs`
  ADD CONSTRAINT `business_verification_docs_bus_ver_id_foreign` FOREIGN KEY (`bus_ver_id`) REFERENCES `business_verification` (`id`);

--
-- Constraints for table `carrier_blocks`
--
ALTER TABLE `carrier_blocks`
  ADD CONSTRAINT `carrier_blocks_carrier_id_foreign` FOREIGN KEY (`carrier_id`) REFERENCES `carriers` (`id`);

--
-- Constraints for table `company_to_carriers`
--
ALTER TABLE `company_to_carriers`
  ADD CONSTRAINT `company_to_carriers_carrier_id_foreign` FOREIGN KEY (`carrier_id`) REFERENCES `carriers` (`id`),
  ADD CONSTRAINT `company_to_carriers_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`);

--
-- Constraints for table `coupons`
--
ALTER TABLE `coupons`
  ADD CONSTRAINT `coupons_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`);

--
-- Constraints for table `coupon_multiline_plan_types`
--
ALTER TABLE `coupon_multiline_plan_types`
  ADD CONSTRAINT `coupon_multiline_plan_types_coupon_id_foreign` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`);

--
-- Constraints for table `coupon_product_types`
--
ALTER TABLE `coupon_product_types`
  ADD CONSTRAINT `coupon_product_types_coupon_id_foreign` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`);

--
-- Constraints for table `customers`
--
ALTER TABLE `customers`
  ADD CONSTRAINT `customers_business_verification_id_foreign` FOREIGN KEY (`business_verification_id`) REFERENCES `business_verification` (`id`),
  ADD CONSTRAINT `customers_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`);

--
-- Constraints for table `customer_notes`
--
ALTER TABLE `customer_notes`
  ADD CONSTRAINT `customer_notes_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  ADD CONSTRAINT `customer_notes_staff_id_foreign` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`);

--
-- Constraints for table `device`
--
ALTER TABLE `device`
  ADD CONSTRAINT `device_carrier_id_foreign` FOREIGN KEY (`carrier_id`) REFERENCES `carriers` (`id`),
  ADD CONSTRAINT `device_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  ADD CONSTRAINT `device_tag_id_foreign` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`);

--
-- Constraints for table `device_images`
--
ALTER TABLE `device_images`
  ADD CONSTRAINT `device_images_device_id_foreign` FOREIGN KEY (`device_id`) REFERENCES `device` (`id`);

--
-- Constraints for table `device_to_carrier`
--
ALTER TABLE `device_to_carrier`
  ADD CONSTRAINT `device_to_carrier_carrier_id_foreign` FOREIGN KEY (`carrier_id`) REFERENCES `carriers` (`id`),
  ADD CONSTRAINT `device_to_carrier_device_id_foreign` FOREIGN KEY (`device_id`) REFERENCES `device` (`id`);

--
-- Constraints for table `device_to_plan`
--
ALTER TABLE `device_to_plan`
  ADD CONSTRAINT `device_to_plan_device_id_foreign` FOREIGN KEY (`device_id`) REFERENCES `device` (`id`),
  ADD CONSTRAINT `device_to_plan_plan_id_foreign` FOREIGN KEY (`plan_id`) REFERENCES `plan` (`id`);

--
-- Constraints for table `device_to_sim`
--
ALTER TABLE `device_to_sim`
  ADD CONSTRAINT `device_to_sim_device_id_foreign` FOREIGN KEY (`device_id`) REFERENCES `device` (`id`),
  ADD CONSTRAINT `device_to_sim_sim_id_foreign` FOREIGN KEY (`sim_id`) REFERENCES `sim` (`id`);

--
-- Constraints for table `device__groups`
--
ALTER TABLE `device__groups`
  ADD CONSTRAINT `device__groups_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`);

--
-- Constraints for table `email_template`
--
ALTER TABLE `email_template`
  ADD CONSTRAINT `email_template_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`);

--
-- Constraints for table `invoices`
--
ALTER TABLE `invoices`
  ADD CONSTRAINT `invoices_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`);

--
-- Constraints for table `invoice_item`
--
ALTER TABLE `invoice_item`
  ADD CONSTRAINT `invoice_item_invoie_id_foreign` FOREIGN KEY (`invoie_id`) REFERENCES `invoices` (`id`),
  ADD CONSTRAINT `invoice_item_subscription_id_foreign` FOREIGN KEY (`subscription_id`) REFERENCES `subscriptions` (`id`);

--
-- Constraints for table `order`
--
ALTER TABLE `order`
  ADD CONSTRAINT `order_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  ADD CONSTRAINT `order_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  ADD CONSTRAINT `order_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`);

--
-- Constraints for table `order_coupons`
--
ALTER TABLE `order_coupons`
  ADD CONSTRAINT `order_coupons_coupon_id_foreign` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`),
  ADD CONSTRAINT `order_coupons_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `order` (`id`);

--
-- Constraints for table `order_group`
--
ALTER TABLE `order_group`
  ADD CONSTRAINT `order_group_device_id_foreign` FOREIGN KEY (`device_id`) REFERENCES `device` (`id`),
  ADD CONSTRAINT `order_group_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `order` (`id`),
  ADD CONSTRAINT `order_group_plan_id_foreign` FOREIGN KEY (`plan_id`) REFERENCES `plan` (`id`),
  ADD CONSTRAINT `order_group_sim_id_foreign` FOREIGN KEY (`sim_id`) REFERENCES `sim` (`id`);

--
-- Constraints for table `order_group_addon`
--
ALTER TABLE `order_group_addon`
  ADD CONSTRAINT `order_group_addon_addon_id_foreign` FOREIGN KEY (`addon_id`) REFERENCES `addon` (`id`),
  ADD CONSTRAINT `order_group_addon_order_group_id_foreign` FOREIGN KEY (`order_group_id`) REFERENCES `order_group` (`id`),
  ADD CONSTRAINT `order_group_addon_subscription_id_foreign` FOREIGN KEY (`subscription_id`) REFERENCES `subscriptions` (`id`);

--
-- Constraints for table `pending_charges`
--
ALTER TABLE `pending_charges`
  ADD CONSTRAINT `pending_charges_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  ADD CONSTRAINT `pending_charges_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`);

--
-- Constraints for table `plan`
--
ALTER TABLE `plan`
  ADD CONSTRAINT `plan_carrier_id_foreign` FOREIGN KEY (`carrier_id`) REFERENCES `carriers` (`id`),
  ADD CONSTRAINT `plan_device_id_foreign` FOREIGN KEY (`device_id`) REFERENCES `device` (`id`),
  ADD CONSTRAINT `plan_tag_id_foreign` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`);

--
-- Constraints for table `plan_blocks`
--
ALTER TABLE `plan_blocks`
  ADD CONSTRAINT `plan_blocks_carrier_block_id_foreign` FOREIGN KEY (`carrier_block_id`) REFERENCES `carrier_blocks` (`id`),
  ADD CONSTRAINT `plan_blocks_plan_id_foreign` FOREIGN KEY (`plan_id`) REFERENCES `plan` (`id`);

--
-- Constraints for table `plan_custom_types`
--
ALTER TABLE `plan_custom_types`
  ADD CONSTRAINT `plan_custom_types_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  ADD CONSTRAINT `plan_custom_types_plan_id_foreign` FOREIGN KEY (`plan_id`) REFERENCES `plan` (`id`);

--
-- Constraints for table `plan_data_soc_bot_codes`
--
ALTER TABLE `plan_data_soc_bot_codes`
  ADD CONSTRAINT `plan_data_soc_bot_codes_plan_id_foreign` FOREIGN KEY (`plan_id`) REFERENCES `plan` (`id`);

--
-- Constraints for table `plan_to_addon`
--
ALTER TABLE `plan_to_addon`
  ADD CONSTRAINT `plan_to_addon_addon_id_foreign` FOREIGN KEY (`addon_id`) REFERENCES `addon` (`id`),
  ADD CONSTRAINT `plan_to_addon_plan_id_foreign` FOREIGN KEY (`plan_id`) REFERENCES `plan` (`id`);

--
-- Constraints for table `ports`
--
ALTER TABLE `ports`
  ADD CONSTRAINT `ports_subscription_id_foreign` FOREIGN KEY (`subscription_id`) REFERENCES `subscriptions` (`id`);

--
-- Constraints for table `port_note`
--
ALTER TABLE `port_note`
  ADD CONSTRAINT `port_note_port_id_foreign` FOREIGN KEY (`port_id`) REFERENCES `ports` (`id`),
  ADD CONSTRAINT `port_note_staff_id_foreign` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`);

--
-- Constraints for table `sim`
--
ALTER TABLE `sim`
  ADD CONSTRAINT `sim_carrier_id_foreign` FOREIGN KEY (`carrier_id`) REFERENCES `carriers` (`id`),
  ADD CONSTRAINT `sim_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`);

--
-- Constraints for table `staff`
--
ALTER TABLE `staff`
  ADD CONSTRAINT `staff_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`);

--
-- Constraints for table `subscriptions`
--
ALTER TABLE `subscriptions`
  ADD CONSTRAINT `subscriptions_ban_group_id_foreign` FOREIGN KEY (`ban_group_id`) REFERENCES `ban_groups` (`id`),
  ADD CONSTRAINT `subscriptions_ban_id_foreign` FOREIGN KEY (`ban_id`) REFERENCES `bans` (`id`),
  ADD CONSTRAINT `subscriptions_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  ADD CONSTRAINT `subscriptions_plan_id_foreign` FOREIGN KEY (`plan_id`) REFERENCES `plan` (`id`);

--
-- Constraints for table `subscription_addon`
--
ALTER TABLE `subscription_addon`
  ADD CONSTRAINT `subscription_addon_addon_id_foreign` FOREIGN KEY (`addon_id`) REFERENCES `addon` (`id`),
  ADD CONSTRAINT `subscription_addon_subscription_id_foreign` FOREIGN KEY (`subscription_id`) REFERENCES `subscriptions` (`id`);

--
-- Constraints for table `subscription_coupons`
--
ALTER TABLE `subscription_coupons`
  ADD CONSTRAINT `subscription_coupons_coupon_id_foreign` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`),
  ADD CONSTRAINT `subscription_coupons_subscription_id_foreign` FOREIGN KEY (`subscription_id`) REFERENCES `subscriptions` (`id`);

--
-- Constraints for table `subscription_log`
--
ALTER TABLE `subscription_log`
  ADD CONSTRAINT `subscription_log_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  ADD CONSTRAINT `subscription_log_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  ADD CONSTRAINT `subscription_log_subscription_id_foreign` FOREIGN KEY (`subscription_id`) REFERENCES `subscriptions` (`id`);

--
-- Constraints for table `tags`
--
ALTER TABLE `tags`
  ADD CONSTRAINT `tags_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`);

--
-- Constraints for table `taxes`
--
ALTER TABLE `taxes`
  ADD CONSTRAINT `taxes_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
