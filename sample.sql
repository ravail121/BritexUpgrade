-- phpMyAdmin SQL Dump
-- version 4.8.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 15, 2018 at 01:34 PM
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
  `company_id` int(10) UNSIGNED DEFAULT NULL,
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
(1, 1, 'test', '', '', '', 0, 0, 0, '', '', '', NULL, NULL),
(2, 2, 'test company2 addon', '', '', '', 0, 0, 0, '', '', '', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `ban`
--

CREATE TABLE `ban` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `number` int(11) NOT NULL,
  `billing_day` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ban_group`
--

CREATE TABLE `ban_group` (
  `id` int(10) UNSIGNED NOT NULL,
  `ban_id` int(10) UNSIGNED DEFAULT NULL,
  `group_id` char(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ban_note`
--

CREATE TABLE `ban_note` (
  `id` int(10) UNSIGNED NOT NULL,
  `staff_id` int(10) UNSIGNED DEFAULT NULL,
  `date` date NOT NULL,
  `text` text COLLATE utf8mb4_unicode_ci NOT NULL,
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
  `tax_id` char(191) COLLATE utf8mb4_unicode_ci NOT NULL,
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
(1, 1, '', 'Test Co', '12-3456789', '', '', '', '', '', '', '', '', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `business_verification_doc`
--

CREATE TABLE `business_verification_doc` (
  `id` int(10) UNSIGNED NOT NULL,
  `bus_ver_id` int(10) UNSIGNED DEFAULT NULL,
  `src` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `carrier`
--

CREATE TABLE `carrier` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `carrier`
--

INSERT INTO `carrier` (`id`, `name`, `created_at`, `updated_at`) VALUES
(1, 'T-Mobile', NULL, NULL),
(2, 'At&T', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `carrier_block`
--

CREATE TABLE `carrier_block` (
  `id` int(10) UNSIGNED NOT NULL,
  `carrier_id` int(10) UNSIGNED DEFAULT NULL,
  `type` int(11) NOT NULL,
  `display_name` int(11) NOT NULL,
  `bot_code` int(11) NOT NULL,
  `soc_code` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `carrier_block`
--

INSERT INTO `carrier_block` (`id`, `carrier_id`, `type`, `display_name`, `bot_code`, `soc_code`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 0, 0, 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `company`
--

CREATE TABLE `company` (
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
-- Dumping data for table `company`
--

INSERT INTO `company` (`id`, `api_key`, `name`, `url`, `selling_devices`, `selling_plans`, `selling_addons`, `selling_sim_standalone`, `business_verification`, `regulatory_label`, `default_reg_fee`, `sprint_api_key`, `created_at`, `updated_at`) VALUES
(1, '', 'Test Communications', '', 0, 0, 0, '', 1, 'Regulatory', 3.5, '', NULL, NULL),
(2, '', 'Teltik', '', 0, 0, 0, '', 1, '', 0, '', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `company_to_carrier`
--

CREATE TABLE `company_to_carrier` (
  `id` int(10) UNSIGNED NOT NULL,
  `company_id` int(10) UNSIGNED DEFAULT NULL,
  `carrier_id` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `company_to_carrier`
--

INSERT INTO `company_to_carrier` (`id`, `company_id`, `carrier_id`, `created_at`, `updated_at`) VALUES
(1, 1, 1, NULL, NULL),
(2, 2, 1, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `coupon`
--

CREATE TABLE `coupon` (
  `id` int(10) UNSIGNED NOT NULL,
  `company_id` int(10) UNSIGNED DEFAULT NULL,
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
-- Table structure for table `coupon_multiline_plan_type`
--

CREATE TABLE `coupon_multiline_plan_type` (
  `id` int(10) UNSIGNED NOT NULL,
  `coupon_id` int(10) UNSIGNED DEFAULT NULL,
  `plan_type` tinyint(4) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `coupon_product_type`
--

CREATE TABLE `coupon_product_type` (
  `id` int(10) UNSIGNED NOT NULL,
  `coupon_id` int(10) UNSIGNED DEFAULT NULL,
  `amount` double NOT NULL,
  `type` tinyint(4) NOT NULL,
  `sub_type` tinyint(4) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customer`
--

CREATE TABLE `customer` (
  `id` int(10) UNSIGNED NOT NULL,
  `hash` char(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `company_id` int(10) UNSIGNED DEFAULT NULL,
  `business_verification_id` int(10) UNSIGNED DEFAULT NULL,
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
-- Dumping data for table `customer`
--

INSERT INTO `customer` (`id`, `hash`, `company_id`, `business_verification_id`, `subscription_start_date`, `billing_start`, `billing_end`, `primary_payment_method`, `primary_payment_card`, `account_suspended`, `billing_address1`, `billing_address2`, `billing_city`, `billing_state_id`, `shipping_address1`, `shipping_address2`, `shipping_city`, `shipping_state_id`, `created_at`, `updated_at`) VALUES
(1, '', 1, 1, '2018-03-01', '2018-03-01', '2018-03-31', 0, 0, 0, '731 Route 18 South', '', 'East Brunswick', 'NJ', '731 Route 18 South', '', 'East Brunswick', 'NJ', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `customer_note`
--

CREATE TABLE `customer_note` (
  `id` int(10) UNSIGNED NOT NULL,
  `customer_id` int(10) UNSIGNED DEFAULT NULL,
  `staff_id` int(10) UNSIGNED DEFAULT NULL,
  `date` date NOT NULL,
  `text` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `default_imei`
--

CREATE TABLE `default_imei` (
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
  `company_id` int(10) UNSIGNED DEFAULT NULL,
  `carrier_id` int(10) UNSIGNED DEFAULT NULL,
  `type` int(11) NOT NULL,
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `tag_id` int(10) UNSIGNED DEFAULT NULL,
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

--
-- Dumping data for table `device`
--

INSERT INTO `device` (`id`, `company_id`, `carrier_id`, `type`, `name`, `description`, `tag_id`, `notes`, `primary_image`, `amount`, `amount_w_plan`, `taxable`, `associate_with_plan`, `show`, `sku`, `created_at`, `updated_at`) VALUES
(1, 1, 2, 1, 'Samsung Galaxy', '<ul><li>Samsung Galaxy S9</li></ul>', NULL, '\r\n', '', 800, 720, 1, 0, 1, '', NULL, NULL),
(2, 1, 1, 2, 'Ipad pro', '<ul><li>iPad Pro</li><li>12.9-inch display</li></ul>', NULL, '', '', 850, 800, 1, 0, 1, '', NULL, NULL),
(3, 1, 1, 1, 'iphone X', '', NULL, '', '', 850, 800, 1, 0, 1, '', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `device_group`
--

CREATE TABLE `device_group` (
  `id` int(10) UNSIGNED NOT NULL,
  `company_id` int(10) UNSIGNED DEFAULT NULL,
  `name` char(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `device_group`
--

INSERT INTO `device_group` (`id`, `company_id`, `name`, `created_at`, `updated_at`) VALUES
(1, 1, 'Android', NULL, NULL),
(2, 1, 'Apple', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `device_image`
--

CREATE TABLE `device_image` (
  `id` int(10) UNSIGNED NOT NULL,
  `device_id` int(10) UNSIGNED DEFAULT NULL,
  `source` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `device_image`
--

INSERT INTO `device_image` (`id`, `device_id`, `source`, `created_at`, `updated_at`) VALUES
(1, 1, '', NULL, NULL),
(2, 1, '', NULL, NULL),
(3, 2, '', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `device_to_carrier`
--

CREATE TABLE `device_to_carrier` (
  `id` int(10) UNSIGNED NOT NULL,
  `device_id` int(10) UNSIGNED DEFAULT NULL,
  `carrier_id` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `device_to_group`
--

CREATE TABLE `device_to_group` (
  `id` int(10) UNSIGNED NOT NULL,
  `device_id` int(10) UNSIGNED DEFAULT NULL,
  `device_group_id` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `device_to_group`
--

INSERT INTO `device_to_group` (`id`, `device_id`, `device_group_id`, `created_at`, `updated_at`) VALUES
(1, 1, 1, NULL, NULL),
(2, 2, 2, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `device_to_plan`
--

CREATE TABLE `device_to_plan` (
  `id` int(10) UNSIGNED NOT NULL,
  `device_id` int(10) UNSIGNED DEFAULT NULL,
  `plan_id` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `device_to_plan`
--

INSERT INTO `device_to_plan` (`id`, `device_id`, `plan_id`, `created_at`, `updated_at`) VALUES
(1, 1, 1, NULL, NULL),
(2, 1, 2, NULL, NULL),
(3, 2, 3, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `device_to_sim`
--

CREATE TABLE `device_to_sim` (
  `id` int(10) UNSIGNED NOT NULL,
  `device_id` int(10) UNSIGNED DEFAULT NULL,
  `sim_id` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `device_to_sim`
--

INSERT INTO `device_to_sim` (`id`, `device_id`, `sim_id`, `created_at`, `updated_at`) VALUES
(1, 3, 1, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `email_template`
--

CREATE TABLE `email_template` (
  `id` int(10) UNSIGNED NOT NULL,
  `company_id` int(10) UNSIGNED DEFAULT NULL,
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
-- Table structure for table `invoice`
--

CREATE TABLE `invoice` (
  `id` int(10) UNSIGNED NOT NULL,
  `customer_id` int(10) UNSIGNED DEFAULT NULL,
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

-- --------------------------------------------------------

--
-- Table structure for table `invoice_item`
--

CREATE TABLE `invoice_item` (
  `id` int(10) UNSIGNED NOT NULL,
  `invoice_id` int(10) UNSIGNED DEFAULT NULL,
  `subscription_id` int(10) UNSIGNED DEFAULT NULL,
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
  `active_group_id` int(11) DEFAULT NULL,
  `active_subscription_id` int(11) DEFAULT NULL,
  `order_num` int(11) NOT NULL,
  `status` tinyint(4) NOT NULL,
  `invoice_id` int(10) UNSIGNED DEFAULT NULL,
  `hash` char(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `company_id` int(10) UNSIGNED DEFAULT NULL,
  `customer_id` int(10) UNSIGNED DEFAULT NULL,
  `date_processed` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order`
--

INSERT INTO `order` (`id`, `active_group_id`, `active_subscription_id`, `order_num`, `status`, `invoice_id`, `hash`, `company_id`, `customer_id`, `date_processed`, `created_at`, `updated_at`) VALUES
(1, NULL, NULL, 1, 1, NULL, '', 1, 1, '0000-00-00', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `order_coupon`
--

CREATE TABLE `order_coupon` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED DEFAULT NULL,
  `coupon_id` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_group`
--

CREATE TABLE `order_group` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED DEFAULT NULL,
  `closed` tinyint(4) NOT NULL,
  `device_id` int(10) UNSIGNED DEFAULT NULL,
  `plan_id` int(10) UNSIGNED DEFAULT NULL,
  `sim_id` int(10) UNSIGNED DEFAULT NULL,
  `sim_num` int(11) NOT NULL,
  `sim_type` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `porting_number` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `area_code` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `require_plan` tinyint(4) NOT NULL,
  `require_device` tinyint(4) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_group`
--

INSERT INTO `order_group` (`id`, `order_id`, `closed`, `device_id`, `plan_id`, `sim_id`, `sim_num`, `sim_type`, `porting_number`, `area_code`, `require_plan`, `require_device`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 3, 1, 1, 0, '', '123-123-1234', '', 0, 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `order_group_addon`
--

CREATE TABLE `order_group_addon` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_group_id` int(10) UNSIGNED DEFAULT NULL,
  `addon_id` int(10) UNSIGNED DEFAULT NULL,
  `subscription_id` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_group_addon`
--

INSERT INTO `order_group_addon` (`id`, `order_group_id`, `addon_id`, `subscription_id`, `created_at`, `updated_at`) VALUES
(2, 1, 1, NULL, NULL, NULL),
(3, 1, 2, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `order_plan`
--

CREATE TABLE `order_plan` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED DEFAULT NULL,
  `plan_id` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_plan`
--

INSERT INTO `order_plan` (`id`, `order_id`, `plan_id`, `created_at`, `updated_at`) VALUES
(1, 1, 1, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `order_sim`
--

CREATE TABLE `order_sim` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED DEFAULT NULL,
  `sim_id` int(10) UNSIGNED DEFAULT NULL,
  `order_plan_id` tinyint(4) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_sim`
--

INSERT INTO `order_sim` (`id`, `order_id`, `sim_id`, `order_plan_id`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, NULL, NULL);

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
-- Table structure for table `pending_charge`
--

CREATE TABLE `pending_charge` (
  `id` int(10) UNSIGNED NOT NULL,
  `customer_id` int(10) UNSIGNED DEFAULT NULL,
  `invoice_id` int(10) UNSIGNED DEFAULT NULL,
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
  `device_id` int(10) UNSIGNED DEFAULT NULL,
  `carrier_id` int(10) UNSIGNED DEFAULT NULL,
  `type` int(11) NOT NULL,
  `tag_id` int(10) UNSIGNED DEFAULT NULL,
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `image` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `primary_image` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount_recurring` double NOT NULL,
  `amount_onetime` double NOT NULL,
  `regulatory_fee_type` tinyint(4) NOT NULL,
  `regulatory_fee_amount` double NOT NULL,
  `sim_required` tinyint(4) NOT NULL,
  `taxable` tinyint(4) NOT NULL,
  `show` tinyint(4) NOT NULL,
  `sku` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `data_limit` int(11) NOT NULL,
  `rate_plan_soc` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `rate_plan_bot_code` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `data_soc` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `signup_porting` tinyint(4) NOT NULL,
  `subsequent_porting` tinyint(4) NOT NULL,
  `area_code` tinyint(4) NOT NULL,
  `imei_required` tinyint(4) NOT NULL,
  `associate_with_device` tinyint(4) NOT NULL,
  `affilate_credit` tinyint(4) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `plan`
--

INSERT INTO `plan` (`id`, `device_id`, `carrier_id`, `type`, `tag_id`, `name`, `image`, `description`, `notes`, `primary_image`, `amount_recurring`, `amount_onetime`, `regulatory_fee_type`, `regulatory_fee_amount`, `sim_required`, `taxable`, `show`, `sku`, `data_limit`, `rate_plan_soc`, `rate_plan_bot_code`, `data_soc`, `signup_porting`, `subsequent_porting`, `area_code`, `imei_required`, `associate_with_device`, `affilate_credit`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, NULL, '2GB Voice T-mobile', '', '<ul><li><em>Unlimited</em></li><li>Talk &amp; Text</li><li><em>2GB High-speed</em></li><li>4G LTE Data</li><li>+ Mobile HotSpot</li></ul>', '', '', 20, 0, 1, 3.5, 1, 1, 1, 'V-UNL-2GB', 0, 'ZNATTU12', 'BIZ SC N.AmericaTT+D 12Line', 'Z2GBXDATA', 0, 0, 0, 0, 0, 1, NULL, NULL),
(2, 1, 1, 1, NULL, '6GB Voice T-mobile', '', '<ul><li><em>Unlimited</em></li><li>Talk &amp; Text</li><li><em>6GB High-speed</em></li><li>4G LTE Data</li><li>+ Mobile HotSpot</li><li>Unlimited Video Streaming</li><li>Rollover Data</li></ul>', '', '', 30, 0, 1, 3.5, 1, 1, 1, 'V-UNL-2GB', 0, '6FMBZ4L', 'BIZ FM UTT+6GB for 4 lines', '6BFMX6G', 0, 0, 0, 0, 0, 1, NULL, NULL),
(3, 1, 1, 2, NULL, 'TabletOne Plus T-mobile', '', '<ul><li><em>Tablet Solution</em></li><li><em>Unlimited</li></em><li>High-speed 4G LTE Data</li><li><em>+ 10GB Mobile HotSpot</em></li></ul>', '', '', 40, 0, 1, 1.5, 1, 1, 1, 'D-UNL-10GBHS', 0, 'ZTMIUN13', 'T-Mobile ONE @Work Tabl TE', 'ZTM1PLMI2', 0, 0, 0, 0, 0, 1, NULL, NULL),
(4, 1, 2, 1, NULL, '2GB Voice AT&T', '', '', '', '', 0, 0, 0, 0, 0, 0, 0, '', 0, '', '', '', 0, 0, 0, 0, 0, 1, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `plan_block`
--

CREATE TABLE `plan_block` (
  `id` int(10) UNSIGNED NOT NULL,
  `plan_id` int(10) UNSIGNED DEFAULT NULL,
  `carrier_block_id` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `plan_custom_type`
--

CREATE TABLE `plan_custom_type` (
  `id` int(10) UNSIGNED NOT NULL,
  `company_id` int(10) UNSIGNED DEFAULT NULL,
  `plan_id` int(10) UNSIGNED DEFAULT NULL,
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `plan_data_soc_bot_code`
--

CREATE TABLE `plan_data_soc_bot_code` (
  `id` int(10) UNSIGNED NOT NULL,
  `plan_id` int(10) UNSIGNED DEFAULT NULL,
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
  `plan_id` int(10) UNSIGNED DEFAULT NULL,
  `addon_id` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `port`
--

CREATE TABLE `port` (
  `id` int(10) UNSIGNED NOT NULL,
  `subscription_id` int(10) UNSIGNED DEFAULT NULL,
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
  `port_id` int(10) UNSIGNED DEFAULT NULL,
  `staff_id` int(10) UNSIGNED DEFAULT NULL,
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
  `company_id` int(10) UNSIGNED DEFAULT NULL,
  `carrier_id` int(10) UNSIGNED DEFAULT NULL,
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

--
-- Dumping data for table `sim`
--

INSERT INTO `sim` (`id`, `company_id`, `carrier_id`, `name`, `description`, `notes`, `image`, `amount_alone`, `amount_w_plan`, `taxable`, `show`, `sku`, `code`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'T-Mobile Nano', '', '', '', 25, 10, 1, 0, '', '', NULL, NULL),
(2, 1, 2, 'AT&T Nano', '', '', '', 20, 5, 1, 0, '', '', NULL, NULL),
(3, 2, 1, 'T-mobile Micro', '', '', '', 10, 10, 1, 0, '', '', NULL, NULL),
(4, 2, 2, 'T-mobile Micro', '', '', '', 10, 10, 1, 0, '', '', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `id` int(10) UNSIGNED NOT NULL,
  `company_id` int(10) UNSIGNED DEFAULT NULL,
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
-- Table structure for table `subscription`
--

CREATE TABLE `subscription` (
  `id` int(10) UNSIGNED NOT NULL,
  `customer_id` int(10) UNSIGNED DEFAULT NULL,
  `plan_id` int(10) UNSIGNED DEFAULT NULL,
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
  `ban_id` int(10) UNSIGNED DEFAULT NULL,
  `ban_group_id` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subscription_addon`
--

CREATE TABLE `subscription_addon` (
  `id` int(10) UNSIGNED NOT NULL,
  `subscription_id` int(10) UNSIGNED DEFAULT NULL,
  `addon_id` int(10) UNSIGNED DEFAULT NULL,
  `status` char(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `removal_date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subscription_coupon`
--

CREATE TABLE `subscription_coupon` (
  `id` int(10) UNSIGNED NOT NULL,
  `subscription_id` int(10) UNSIGNED DEFAULT NULL,
  `coupon_id` int(10) UNSIGNED DEFAULT NULL,
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
  `company_id` int(10) UNSIGNED DEFAULT NULL,
  `customer_id` int(10) UNSIGNED DEFAULT NULL,
  `subscription_id` int(10) UNSIGNED DEFAULT NULL,
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
-- Table structure for table `system_email_template`
--

CREATE TABLE `system_email_template` (
  `id` int(10) UNSIGNED NOT NULL,
  `code` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `system_email_template_dynamic_field`
--

CREATE TABLE `system_email_template_dynamic_field` (
  `id` int(10) UNSIGNED NOT NULL,
  `code` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL
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
-- Table structure for table `tag`
--

CREATE TABLE `tag` (
  `id` int(10) UNSIGNED NOT NULL,
  `company_id` int(10) UNSIGNED DEFAULT NULL,
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tax`
--

CREATE TABLE `tax` (
  `id` int(10) UNSIGNED NOT NULL,
  `company_id` int(10) UNSIGNED DEFAULT NULL,
  `state` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `rate` double NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
-- Indexes for table `ban`
--
ALTER TABLE `ban`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ban_group`
--
ALTER TABLE `ban_group`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ban_group_ban_id_foreign` (`ban_id`);

--
-- Indexes for table `ban_note`
--
ALTER TABLE `ban_note`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ban_note_staff_id_foreign` (`staff_id`);

--
-- Indexes for table `business_verification`
--
ALTER TABLE `business_verification`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `business_verification_doc`
--
ALTER TABLE `business_verification_doc`
  ADD PRIMARY KEY (`id`),
  ADD KEY `business_verification_doc_bus_ver_id_foreign` (`bus_ver_id`);

--
-- Indexes for table `carrier`
--
ALTER TABLE `carrier`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `carrier_block`
--
ALTER TABLE `carrier_block`
  ADD PRIMARY KEY (`id`),
  ADD KEY `carrier_block_carrier_id_foreign` (`carrier_id`);

--
-- Indexes for table `company`
--
ALTER TABLE `company`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `company_to_carrier`
--
ALTER TABLE `company_to_carrier`
  ADD PRIMARY KEY (`id`),
  ADD KEY `company_to_carrier_company_id_foreign` (`company_id`),
  ADD KEY `company_to_carrier_carrier_id_foreign` (`carrier_id`);

--
-- Indexes for table `coupon`
--
ALTER TABLE `coupon`
  ADD PRIMARY KEY (`id`),
  ADD KEY `coupon_company_id_foreign` (`company_id`);

--
-- Indexes for table `coupon_multiline_plan_type`
--
ALTER TABLE `coupon_multiline_plan_type`
  ADD PRIMARY KEY (`id`),
  ADD KEY `coupon_multiline_plan_type_coupon_id_foreign` (`coupon_id`);

--
-- Indexes for table `coupon_product_type`
--
ALTER TABLE `coupon_product_type`
  ADD PRIMARY KEY (`id`),
  ADD KEY `coupon_product_type_coupon_id_foreign` (`coupon_id`);

--
-- Indexes for table `customer`
--
ALTER TABLE `customer`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_company_id_foreign` (`company_id`),
  ADD KEY `customer_business_verification_id_foreign` (`business_verification_id`);

--
-- Indexes for table `customer_note`
--
ALTER TABLE `customer_note`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_note_customer_id_foreign` (`customer_id`),
  ADD KEY `customer_note_staff_id_foreign` (`staff_id`);

--
-- Indexes for table `default_imei`
--
ALTER TABLE `default_imei`
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
-- Indexes for table `device_group`
--
ALTER TABLE `device_group`
  ADD PRIMARY KEY (`id`),
  ADD KEY `device_group_company_id_foreign` (`company_id`);

--
-- Indexes for table `device_image`
--
ALTER TABLE `device_image`
  ADD PRIMARY KEY (`id`),
  ADD KEY `device_image_device_id_foreign` (`device_id`);

--
-- Indexes for table `device_to_carrier`
--
ALTER TABLE `device_to_carrier`
  ADD PRIMARY KEY (`id`),
  ADD KEY `device_to_carrier_device_id_foreign` (`device_id`),
  ADD KEY `device_to_carrier_carrier_id_foreign` (`carrier_id`);

--
-- Indexes for table `device_to_group`
--
ALTER TABLE `device_to_group`
  ADD PRIMARY KEY (`id`),
  ADD KEY `device_to_group_device_group_id_foreign` (`device_group_id`),
  ADD KEY `device_to_group_device_id_foreign` (`device_id`);

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
-- Indexes for table `email_template`
--
ALTER TABLE `email_template`
  ADD PRIMARY KEY (`id`),
  ADD KEY `email_template_company_id_foreign` (`company_id`);

--
-- Indexes for table `invoice`
--
ALTER TABLE `invoice`
  ADD PRIMARY KEY (`id`),
  ADD KEY `invoice_customer_id_foreign` (`customer_id`);

--
-- Indexes for table `invoice_item`
--
ALTER TABLE `invoice_item`
  ADD PRIMARY KEY (`id`),
  ADD KEY `invoice_item_invoice_id_foreign` (`invoice_id`),
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
-- Indexes for table `order_coupon`
--
ALTER TABLE `order_coupon`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_coupon_order_id_foreign` (`order_id`),
  ADD KEY `order_coupon_coupon_id_foreign` (`coupon_id`);

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
-- Indexes for table `order_plan`
--
ALTER TABLE `order_plan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_plan_order_id_foreign` (`order_id`),
  ADD KEY `order_plan_plan_id_foreign` (`plan_id`);

--
-- Indexes for table `order_sim`
--
ALTER TABLE `order_sim`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_sim_order_id_foreign` (`order_id`),
  ADD KEY `order_sim_sim_id_foreign` (`sim_id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD KEY `password_resets_email_index` (`email`);

--
-- Indexes for table `pending_charge`
--
ALTER TABLE `pending_charge`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pending_charge_customer_id_foreign` (`customer_id`),
  ADD KEY `pending_charge_invoice_id_foreign` (`invoice_id`);

--
-- Indexes for table `plan`
--
ALTER TABLE `plan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `plan_device_id_foreign` (`device_id`),
  ADD KEY `plan_carrier_id_foreign` (`carrier_id`),
  ADD KEY `plan_tag_id_foreign` (`tag_id`);

--
-- Indexes for table `plan_block`
--
ALTER TABLE `plan_block`
  ADD PRIMARY KEY (`id`),
  ADD KEY `plan_block_plan_id_foreign` (`plan_id`),
  ADD KEY `plan_block_carrier_block_id_foreign` (`carrier_block_id`);

--
-- Indexes for table `plan_custom_type`
--
ALTER TABLE `plan_custom_type`
  ADD PRIMARY KEY (`id`),
  ADD KEY `plan_custom_type_company_id_foreign` (`company_id`),
  ADD KEY `plan_custom_type_plan_id_foreign` (`plan_id`);

--
-- Indexes for table `plan_data_soc_bot_code`
--
ALTER TABLE `plan_data_soc_bot_code`
  ADD PRIMARY KEY (`id`),
  ADD KEY `plan_data_soc_bot_code_plan_id_foreign` (`plan_id`);

--
-- Indexes for table `plan_to_addon`
--
ALTER TABLE `plan_to_addon`
  ADD PRIMARY KEY (`id`),
  ADD KEY `plan_to_addon_plan_id_foreign` (`plan_id`),
  ADD KEY `plan_to_addon_addon_id_foreign` (`addon_id`);

--
-- Indexes for table `port`
--
ALTER TABLE `port`
  ADD PRIMARY KEY (`id`),
  ADD KEY `port_subscription_id_foreign` (`subscription_id`);

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
-- Indexes for table `subscription`
--
ALTER TABLE `subscription`
  ADD PRIMARY KEY (`id`),
  ADD KEY `subscription_customer_id_foreign` (`customer_id`),
  ADD KEY `subscription_plan_id_foreign` (`plan_id`),
  ADD KEY `subscription_ban_id_foreign` (`ban_id`),
  ADD KEY `subscription_ban_group_id_foreign` (`ban_group_id`);

--
-- Indexes for table `subscription_addon`
--
ALTER TABLE `subscription_addon`
  ADD PRIMARY KEY (`id`),
  ADD KEY `subscription_addon_subscription_id_foreign` (`subscription_id`),
  ADD KEY `subscription_addon_addon_id_foreign` (`addon_id`);

--
-- Indexes for table `subscription_coupon`
--
ALTER TABLE `subscription_coupon`
  ADD PRIMARY KEY (`id`),
  ADD KEY `subscription_coupon_subscription_id_foreign` (`subscription_id`),
  ADD KEY `subscription_coupon_coupon_id_foreign` (`coupon_id`);

--
-- Indexes for table `subscription_log`
--
ALTER TABLE `subscription_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `subscription_log_company_id_foreign` (`company_id`),
  ADD KEY `subscription_log_customer_id_foreign` (`customer_id`),
  ADD KEY `subscription_log_subscription_id_foreign` (`subscription_id`);

--
-- Indexes for table `system_email_template`
--
ALTER TABLE `system_email_template`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `system_email_template_dynamic_field`
--
ALTER TABLE `system_email_template_dynamic_field`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `system_global_setting`
--
ALTER TABLE `system_global_setting`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tag`
--
ALTER TABLE `tag`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tag_company_id_foreign` (`company_id`);

--
-- Indexes for table `tax`
--
ALTER TABLE `tax`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tax_company_id_foreign` (`company_id`);

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
-- AUTO_INCREMENT for table `ban`
--
ALTER TABLE `ban`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ban_group`
--
ALTER TABLE `ban_group`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ban_note`
--
ALTER TABLE `ban_note`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `business_verification`
--
ALTER TABLE `business_verification`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `business_verification_doc`
--
ALTER TABLE `business_verification_doc`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `carrier`
--
ALTER TABLE `carrier`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `carrier_block`
--
ALTER TABLE `carrier_block`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `company`
--
ALTER TABLE `company`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `company_to_carrier`
--
ALTER TABLE `company_to_carrier`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `coupon`
--
ALTER TABLE `coupon`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `coupon_multiline_plan_type`
--
ALTER TABLE `coupon_multiline_plan_type`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `coupon_product_type`
--
ALTER TABLE `coupon_product_type`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `customer`
--
ALTER TABLE `customer`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `customer_note`
--
ALTER TABLE `customer_note`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `default_imei`
--
ALTER TABLE `default_imei`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `device`
--
ALTER TABLE `device`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `device_group`
--
ALTER TABLE `device_group`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `device_image`
--
ALTER TABLE `device_image`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `device_to_carrier`
--
ALTER TABLE `device_to_carrier`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `device_to_group`
--
ALTER TABLE `device_to_group`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `device_to_plan`
--
ALTER TABLE `device_to_plan`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `device_to_sim`
--
ALTER TABLE `device_to_sim`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `email_template`
--
ALTER TABLE `email_template`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `invoice`
--
ALTER TABLE `invoice`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

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
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `order_coupon`
--
ALTER TABLE `order_coupon`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_group`
--
ALTER TABLE `order_group`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `order_group_addon`
--
ALTER TABLE `order_group_addon`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `order_plan`
--
ALTER TABLE `order_plan`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `order_sim`
--
ALTER TABLE `order_sim`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `pending_charge`
--
ALTER TABLE `pending_charge`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `plan`
--
ALTER TABLE `plan`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `plan_block`
--
ALTER TABLE `plan_block`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `plan_custom_type`
--
ALTER TABLE `plan_custom_type`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `plan_data_soc_bot_code`
--
ALTER TABLE `plan_data_soc_bot_code`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `plan_to_addon`
--
ALTER TABLE `plan_to_addon`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `port`
--
ALTER TABLE `port`
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
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `subscription`
--
ALTER TABLE `subscription`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `subscription_addon`
--
ALTER TABLE `subscription_addon`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `subscription_coupon`
--
ALTER TABLE `subscription_coupon`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `subscription_log`
--
ALTER TABLE `subscription_log`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `system_email_template`
--
ALTER TABLE `system_email_template`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `system_email_template_dynamic_field`
--
ALTER TABLE `system_email_template_dynamic_field`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `system_global_setting`
--
ALTER TABLE `system_global_setting`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tag`
--
ALTER TABLE `tag`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tax`
--
ALTER TABLE `tax`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

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
  ADD CONSTRAINT `addon_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `company` (`id`);

--
-- Constraints for table `ban_group`
--
ALTER TABLE `ban_group`
  ADD CONSTRAINT `ban_group_ban_id_foreign` FOREIGN KEY (`ban_id`) REFERENCES `ban` (`id`);

--
-- Constraints for table `ban_note`
--
ALTER TABLE `ban_note`
  ADD CONSTRAINT `ban_note_staff_id_foreign` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`);

--
-- Constraints for table `business_verification_doc`
--
ALTER TABLE `business_verification_doc`
  ADD CONSTRAINT `business_verification_doc_bus_ver_id_foreign` FOREIGN KEY (`bus_ver_id`) REFERENCES `business_verification` (`id`);

--
-- Constraints for table `carrier_block`
--
ALTER TABLE `carrier_block`
  ADD CONSTRAINT `carrier_block_carrier_id_foreign` FOREIGN KEY (`carrier_id`) REFERENCES `carrier` (`id`);

--
-- Constraints for table `company_to_carrier`
--
ALTER TABLE `company_to_carrier`
  ADD CONSTRAINT `company_to_carrier_carrier_id_foreign` FOREIGN KEY (`carrier_id`) REFERENCES `carrier` (`id`),
  ADD CONSTRAINT `company_to_carrier_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `company` (`id`);

--
-- Constraints for table `coupon`
--
ALTER TABLE `coupon`
  ADD CONSTRAINT `coupon_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `company` (`id`);

--
-- Constraints for table `coupon_multiline_plan_type`
--
ALTER TABLE `coupon_multiline_plan_type`
  ADD CONSTRAINT `coupon_multiline_plan_type_coupon_id_foreign` FOREIGN KEY (`coupon_id`) REFERENCES `coupon` (`id`);

--
-- Constraints for table `coupon_product_type`
--
ALTER TABLE `coupon_product_type`
  ADD CONSTRAINT `coupon_product_type_coupon_id_foreign` FOREIGN KEY (`coupon_id`) REFERENCES `coupon` (`id`);

--
-- Constraints for table `customer`
--
ALTER TABLE `customer`
  ADD CONSTRAINT `customer_business_verification_id_foreign` FOREIGN KEY (`business_verification_id`) REFERENCES `business_verification` (`id`),
  ADD CONSTRAINT `customer_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `company` (`id`);

--
-- Constraints for table `customer_note`
--
ALTER TABLE `customer_note`
  ADD CONSTRAINT `customer_note_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`id`),
  ADD CONSTRAINT `customer_note_staff_id_foreign` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`);

--
-- Constraints for table `device`
--
ALTER TABLE `device`
  ADD CONSTRAINT `device_carrier_id_foreign` FOREIGN KEY (`carrier_id`) REFERENCES `carrier` (`id`),
  ADD CONSTRAINT `device_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `company` (`id`),
  ADD CONSTRAINT `device_tag_id_foreign` FOREIGN KEY (`tag_id`) REFERENCES `tag` (`id`);

--
-- Constraints for table `device_group`
--
ALTER TABLE `device_group`
  ADD CONSTRAINT `device_group_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `company` (`id`);

--
-- Constraints for table `device_image`
--
ALTER TABLE `device_image`
  ADD CONSTRAINT `device_image_device_id_foreign` FOREIGN KEY (`device_id`) REFERENCES `device` (`id`);

--
-- Constraints for table `device_to_carrier`
--
ALTER TABLE `device_to_carrier`
  ADD CONSTRAINT `device_to_carrier_carrier_id_foreign` FOREIGN KEY (`carrier_id`) REFERENCES `carrier` (`id`),
  ADD CONSTRAINT `device_to_carrier_device_id_foreign` FOREIGN KEY (`device_id`) REFERENCES `device` (`id`);

--
-- Constraints for table `device_to_group`
--
ALTER TABLE `device_to_group`
  ADD CONSTRAINT `device_to_group_device_group_id_foreign` FOREIGN KEY (`device_group_id`) REFERENCES `device_group` (`id`),
  ADD CONSTRAINT `device_to_group_device_id_foreign` FOREIGN KEY (`device_id`) REFERENCES `device` (`id`);

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
-- Constraints for table `email_template`
--
ALTER TABLE `email_template`
  ADD CONSTRAINT `email_template_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `company` (`id`);

--
-- Constraints for table `invoice`
--
ALTER TABLE `invoice`
  ADD CONSTRAINT `invoice_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`id`);

--
-- Constraints for table `invoice_item`
--
ALTER TABLE `invoice_item`
  ADD CONSTRAINT `invoice_item_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoice` (`id`),
  ADD CONSTRAINT `invoice_item_subscription_id_foreign` FOREIGN KEY (`subscription_id`) REFERENCES `subscription` (`id`);

--
-- Constraints for table `order`
--
ALTER TABLE `order`
  ADD CONSTRAINT `order_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `company` (`id`),
  ADD CONSTRAINT `order_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`id`),
  ADD CONSTRAINT `order_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoice` (`id`);

--
-- Constraints for table `order_coupon`
--
ALTER TABLE `order_coupon`
  ADD CONSTRAINT `order_coupon_coupon_id_foreign` FOREIGN KEY (`coupon_id`) REFERENCES `coupon` (`id`),
  ADD CONSTRAINT `order_coupon_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `order` (`id`);

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
  ADD CONSTRAINT `order_group_addon_subscription_id_foreign` FOREIGN KEY (`subscription_id`) REFERENCES `subscription` (`id`);

--
-- Constraints for table `order_plan`
--
ALTER TABLE `order_plan`
  ADD CONSTRAINT `order_plan_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `order` (`id`),
  ADD CONSTRAINT `order_plan_plan_id_foreign` FOREIGN KEY (`plan_id`) REFERENCES `plan` (`id`);

--
-- Constraints for table `order_sim`
--
ALTER TABLE `order_sim`
  ADD CONSTRAINT `order_sim_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `order` (`id`),
  ADD CONSTRAINT `order_sim_sim_id_foreign` FOREIGN KEY (`sim_id`) REFERENCES `sim` (`id`);

--
-- Constraints for table `pending_charge`
--
ALTER TABLE `pending_charge`
  ADD CONSTRAINT `pending_charge_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`id`),
  ADD CONSTRAINT `pending_charge_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoice` (`id`);

--
-- Constraints for table `plan`
--
ALTER TABLE `plan`
  ADD CONSTRAINT `plan_carrier_id_foreign` FOREIGN KEY (`carrier_id`) REFERENCES `carrier` (`id`),
  ADD CONSTRAINT `plan_device_id_foreign` FOREIGN KEY (`device_id`) REFERENCES `device` (`id`),
  ADD CONSTRAINT `plan_tag_id_foreign` FOREIGN KEY (`tag_id`) REFERENCES `tag` (`id`);

--
-- Constraints for table `plan_block`
--
ALTER TABLE `plan_block`
  ADD CONSTRAINT `plan_block_carrier_block_id_foreign` FOREIGN KEY (`carrier_block_id`) REFERENCES `carrier_block` (`id`),
  ADD CONSTRAINT `plan_block_plan_id_foreign` FOREIGN KEY (`plan_id`) REFERENCES `plan` (`id`);

--
-- Constraints for table `plan_custom_type`
--
ALTER TABLE `plan_custom_type`
  ADD CONSTRAINT `plan_custom_type_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `company` (`id`),
  ADD CONSTRAINT `plan_custom_type_plan_id_foreign` FOREIGN KEY (`plan_id`) REFERENCES `plan` (`id`);

--
-- Constraints for table `plan_data_soc_bot_code`
--
ALTER TABLE `plan_data_soc_bot_code`
  ADD CONSTRAINT `plan_data_soc_bot_code_plan_id_foreign` FOREIGN KEY (`plan_id`) REFERENCES `plan` (`id`);

--
-- Constraints for table `plan_to_addon`
--
ALTER TABLE `plan_to_addon`
  ADD CONSTRAINT `plan_to_addon_addon_id_foreign` FOREIGN KEY (`addon_id`) REFERENCES `addon` (`id`),
  ADD CONSTRAINT `plan_to_addon_plan_id_foreign` FOREIGN KEY (`plan_id`) REFERENCES `plan` (`id`);

--
-- Constraints for table `port`
--
ALTER TABLE `port`
  ADD CONSTRAINT `port_subscription_id_foreign` FOREIGN KEY (`subscription_id`) REFERENCES `subscription` (`id`);

--
-- Constraints for table `port_note`
--
ALTER TABLE `port_note`
  ADD CONSTRAINT `port_note_port_id_foreign` FOREIGN KEY (`port_id`) REFERENCES `port` (`id`),
  ADD CONSTRAINT `port_note_staff_id_foreign` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`);

--
-- Constraints for table `sim`
--
ALTER TABLE `sim`
  ADD CONSTRAINT `sim_carrier_id_foreign` FOREIGN KEY (`carrier_id`) REFERENCES `carrier` (`id`),
  ADD CONSTRAINT `sim_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `company` (`id`);

--
-- Constraints for table `staff`
--
ALTER TABLE `staff`
  ADD CONSTRAINT `staff_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `company` (`id`);

--
-- Constraints for table `subscription`
--
ALTER TABLE `subscription`
  ADD CONSTRAINT `subscription_ban_group_id_foreign` FOREIGN KEY (`ban_group_id`) REFERENCES `ban_group` (`id`),
  ADD CONSTRAINT `subscription_ban_id_foreign` FOREIGN KEY (`ban_id`) REFERENCES `ban` (`id`),
  ADD CONSTRAINT `subscription_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`id`),
  ADD CONSTRAINT `subscription_plan_id_foreign` FOREIGN KEY (`plan_id`) REFERENCES `plan` (`id`);

--
-- Constraints for table `subscription_addon`
--
ALTER TABLE `subscription_addon`
  ADD CONSTRAINT `subscription_addon_addon_id_foreign` FOREIGN KEY (`addon_id`) REFERENCES `addon` (`id`),
  ADD CONSTRAINT `subscription_addon_subscription_id_foreign` FOREIGN KEY (`subscription_id`) REFERENCES `subscription` (`id`);

--
-- Constraints for table `subscription_coupon`
--
ALTER TABLE `subscription_coupon`
  ADD CONSTRAINT `subscription_coupon_coupon_id_foreign` FOREIGN KEY (`coupon_id`) REFERENCES `coupon` (`id`),
  ADD CONSTRAINT `subscription_coupon_subscription_id_foreign` FOREIGN KEY (`subscription_id`) REFERENCES `subscription` (`id`);

--
-- Constraints for table `subscription_log`
--
ALTER TABLE `subscription_log`
  ADD CONSTRAINT `subscription_log_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `company` (`id`),
  ADD CONSTRAINT `subscription_log_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`id`),
  ADD CONSTRAINT `subscription_log_subscription_id_foreign` FOREIGN KEY (`subscription_id`) REFERENCES `subscription` (`id`);

--
-- Constraints for table `tag`
--
ALTER TABLE `tag`
  ADD CONSTRAINT `tag_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `company` (`id`);

--
-- Constraints for table `tax`
--
ALTER TABLE `tax`
  ADD CONSTRAINT `tax_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `company` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
