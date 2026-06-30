-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 02, 2026 at 06:39 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.1.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `e-commerce-mlm-v2`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_members`
--

CREATE TABLE `admin_members` (
  `id` int(11) NOT NULL,
  `admin_name` varchar(150) DEFAULT NULL,
  `admin_email` varchar(150) DEFAULT NULL,
  `admin_roll` int(11) DEFAULT 2,
  `created_date` datetime DEFAULT NULL,
  `update_date` datetime DEFAULT NULL,
  `permission_pages` text DEFAULT NULL,
  `admin_status` int(11) DEFAULT 1,
  `auth_status` int(11) DEFAULT 0,
  `auth_key` varchar(150) DEFAULT NULL,
  `admin_password` varchar(250) DEFAULT NULL,
  `get_status` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_members`
--

INSERT INTO `admin_members` (`id`, `admin_name`, `admin_email`, `admin_roll`, `created_date`, `update_date`, `permission_pages`, `admin_status`, `auth_status`, `auth_key`, `admin_password`, `get_status`) VALUES
(1, 'admin', 'admin@gmail.com', 1, '2025-02-19 18:19:05', '2025-02-19 18:19:05', '{\"site_settings\": true, \"payment_settings\": true,\"mail_settings\": true,\"advance_settings\":true,\"email_markettings\":true,\"newsletter_markettings\":true,\"social_link\":true,\"website_content_cms\":true,\"annoucement_cms\":true,\"slider_cms\":true,\"faq_cms\":true,\"wallet_management\":true,\"package_settings\":true,\"support_management\":true,\"member_management\":true,\"commission_settings\":true,\"rank_management\":true,\"transfer_settings\":true}', 1, 1, 'LA6K7B37L3A3A6LC', '$2y$10$HwAX3aTvVUAunU102tZaNuLtKV5sA0.MeTcxHlDP228UpfGBa95AK', 0);

-- --------------------------------------------------------

--
-- Table structure for table `announcement`
--

CREATE TABLE `announcement` (
  `id` int(11) NOT NULL,
  `title` varchar(250) DEFAULT NULL,
  `title_status` int(11) DEFAULT 1,
  `created_date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcement`
--

INSERT INTO `announcement` (`id`, `title`, `title_status`, `created_date`) VALUES
(10, 'Welcome to Fenizo MLM Software & \r\nPack your excitement', 1, '2025-05-07 10:52:35'),
(11, ' —It’s almost time to travel with our upcoming tour announcement!', 1, '2025-05-07 10:52:41');

-- --------------------------------------------------------

--
-- Table structure for table `binary_carry`
--

CREATE TABLE `binary_carry` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `left_carry` decimal(14,4) NOT NULL DEFAULT 0.0000,
  `right_carry` decimal(14,4) NOT NULL DEFAULT 0.0000,
  `last_flush_at` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `binary_carry`
--

INSERT INTO `binary_carry` (`user_id`, `left_carry`, `right_carry`, `last_flush_at`) VALUES
(1, 0.7500, 0.0000, '2025-08-29'),
(247, 0.2000, 0.4000, '2025-08-29'),
(248, 0.0000, 0.0000, '2025-08-29'),
(249, 0.0000, 0.0000, '2025-08-29'),
(250, 0.0000, 0.0000, '2025-08-29');

-- --------------------------------------------------------

--
-- Table structure for table `binary_placement`
--

CREATE TABLE `binary_placement` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `sponsor_id` int(11) NOT NULL,
  `parent_id` int(11) NOT NULL,
  `position` enum('left','right') NOT NULL,
  `placement_type` enum('direct','auto') NOT NULL,
  `auto_from_user` int(11) DEFAULT NULL,
  `placed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `direct_placement` int(11) DEFAULT NULL,
  `type` enum('direct','auto') NOT NULL DEFAULT 'direct'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `binary_placement`
--

INSERT INTO `binary_placement` (`id`, `user_id`, `sponsor_id`, `parent_id`, `position`, `placement_type`, `auto_from_user`, `placed_at`, `direct_placement`, `type`) VALUES
(244, 247, 1, 1, 'left', 'direct', NULL, '2025-05-26 09:17:21', 1, 'direct'),
(245, 248, 247, 247, 'left', 'direct', NULL, '2025-05-27 08:50:08', 1, 'direct'),
(246, 249, 247, 247, 'right', 'direct', NULL, '2025-05-27 08:50:56', 1, 'direct'),
(247, 250, 1, 1, 'right', 'direct', NULL, '2025-05-30 12:04:25', 1, 'direct'),
(248, 251, 1, 248, 'left', 'auto', NULL, '2025-10-13 03:39:36', 0, 'direct'),
(249, 252, 250, 250, 'left', 'direct', NULL, '2026-02-01 02:43:54', 1, 'direct'),
(250, 253, 250, 250, 'right', 'direct', NULL, '2026-02-01 02:45:21', 1, 'direct');

-- --------------------------------------------------------

--
-- Table structure for table `binary_volume_ledger`
--

CREATE TABLE `binary_volume_ledger` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
  `invest_id` bigint(20) UNSIGNED NOT NULL,
  `pv` decimal(14,4) NOT NULL DEFAULT 0.0000,
  `bv` decimal(14,4) NOT NULL DEFAULT 0.0000,
  `source_amount` decimal(14,4) NOT NULL DEFAULT 0.0000,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `binary_volume_ledger`
--

INSERT INTO `binary_volume_ledger` (`id`, `user_id`, `invest_id`, `pv`, `bv`, `source_amount`, `created_at`) VALUES
(1, 247, 0, 20.0000, 20.0000, 150.0000, '2025-08-28 08:47:00'),
(2, 247, 0, 20.0000, 20.0000, 150.0000, '2025-08-28 09:00:00'),
(3, 247, 0, 20.0000, 20.0000, 150.0000, '2025-08-28 09:00:00'),
(4, 247, 0, 20.0000, 20.0000, 100.0000, '2025-08-28 09:09:00'),
(5, 247, 0, 20.0000, 20.0000, 150.0000, '2025-08-28 09:15:00'),
(6, 247, 0, 0.0000, 20.0000, 150.0000, '2025-08-27 09:22:00'),
(7, 247, 0, 0.0000, 20.0000, 150.0000, '2025-08-27 09:37:00'),
(8, 248, 0, 0.0000, 20.0000, 110.0000, '2025-08-28 09:49:00'),
(9, 247, 1, 0.0000, 20.0000, 150.0000, '2025-08-27 09:57:00'),
(10, 248, 2, 0.0000, 20.0000, 110.0000, '2025-08-28 10:00:00'),
(11, 249, 3, 0.0000, 20.0000, 130.0000, '2025-08-28 10:01:00'),
(12, 250, 4, 0.0000, 20.0000, 120.0000, '2025-08-28 10:03:00'),
(13, 247, 1, 0.0000, 20.0000, 150.0000, '2025-08-26 10:28:00'),
(14, 248, 2, 0.0000, 20.0000, 110.0000, '2025-08-27 10:28:00'),
(15, 249, 3, 0.0000, 20.0000, 111.0000, '2025-08-28 10:29:00'),
(16, 250, 4, 0.0000, 20.0000, 100.0000, '2025-08-28 10:04:00'),
(18, 250, 6, 0.0000, 20.0000, 100.0000, '2025-08-29 10:45:00'),
(19, 250, 7, 0.0000, 20.0000, 111.0000, '2025-08-29 10:54:00'),
(20, 250, 8, 0.0000, 20.0000, 111.0000, '2025-08-29 11:00:00'),
(21, 250, 9, 0.0000, 20.0000, 111.0000, '2025-08-27 11:01:00'),
(22, 250, 10, 0.0000, 20.0000, 111.0000, '2025-08-28 11:02:00'),
(23, 250, 11, 0.0000, 20.0000, 111.0000, '2025-08-28 11:03:00'),
(24, 247, 1, 0.0000, 20.0000, 100.0000, '2025-08-29 11:22:00'),
(25, 248, 2, 0.0000, 20.0000, 111.0000, '2025-08-29 11:22:00'),
(26, 249, 3, 0.0000, 20.0000, 111.0000, '2025-08-29 11:22:00'),
(27, 250, 4, 0.0000, 20.0000, 111.0000, '2025-08-29 11:22:00'),
(28, 250, 5, 0.0000, 20.0000, 150.0000, '2026-02-01 08:34:52');

-- --------------------------------------------------------

--
-- Table structure for table `blocked_ips`
--

CREATE TABLE `blocked_ips` (
  `id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `blocked_ips`
--

INSERT INTO `blocked_ips` (`id`, `ip_address`, `reason`, `created_at`) VALUES
(2, '223.185.26.62', 'tester', '2025-03-01 02:29:41');

-- --------------------------------------------------------

--
-- Table structure for table `blogs`
--

CREATE TABLE `blogs` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1,
  `category` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `blogs`
--

INSERT INTO `blogs` (`id`, `title`, `slug`, `content`, `image`, `status`, `category`, `created_at`) VALUES
(5, 'Why Our Herbal Supplements Are a Must-Have in Every Home', 'why-our-herbal-supplements-are-a-must-have-in-every-home', '<p>In the fast-paced world we live in, maintaining good health has become a priority for many. Our herbal supplements, made from 100% natural ingredients, offer a safe and effective solution for boosting immunity, improving digestion, and enhancing overall wellness. Unlike synthetic products, these herbal formulas work with your body&rsquo;s natural rhythm, making them ideal for long-term use.<br />\r\n<br />\r\nWhat sets our supplements apart is the rigorous quality testing and sourcing process. We partner directly with farmers and certified manufacturers to ensure every capsule, powder, or tonic meets the highest standards. Customers love the visible results &mdash; better energy levels, sound sleep, and fewer seasonal illnesses. It&rsquo;s not just a product &mdash; it&rsquo;s a lifestyle upgrade.<br />\r\n<br />\r\nFor our MLM partners, promoting these supplements isn&rsquo;t just about selling &mdash; it&rsquo;s about sharing real transformations. The high customer retention, repeat orders, and attractive commissions make it a top-performing product line in our network. If you&#39;re looking for a wellness product that truly delivers &mdash; and earns &mdash; this is where you start.</p>\r\n', 'uploads/category/1752135249_blog_1.jpg', 1, '3', '2025-07-10 00:00:00'),
(6, 'Glow Naturally The Secret Behind Our Aloe-Based Skincare Range', 'glow-naturally-the-secret-behind-our-aloe-based-skincare-range', '<blockquote data-end=\"1913\" data-start=\"1383\">\r\n<p data-end=\"1560\" data-start=\"1385\">Many people underestimate the power of aloe vera. Our aloe-based facial creams, gels, and masks have become customer favorites due to their hydrating and healing properties.</p>\r\n\r\n<p data-end=\"1731\" data-start=\"1567\">These products work wonders on sensitive and acne-prone skin, repairing without clogging pores. Best of all, they are free from parabens and synthetic fragrances.</p>\r\n\r\n<p data-end=\"1913\" data-start=\"1738\">Our MLM partners love this category for its visual impact &mdash; before/after photos drive sales, and the affordable price points make this an easy upsell for every skincare order.</p>\r\n</blockquote>\r\n', 'uploads/category/1752135483_3d_image_for_this_blog_thumline_glow.jpeg', 1, '4', '2025-07-10 00:00:00'),
(7, 'Why Our Whey Protein Is the Perfect Choice for Everyday Fitness', 'why-our-whey-protein-is-the-perfect-choice-for-everyday-fitness', '<p>Our premium whey protein blend delivers 25g of protein per scoop and is low in carbs, perfect for building lean muscle and aiding recovery. It&rsquo;s gluten-free, gut-friendly, and mixes easily &mdash; no clumps, no chalky taste.<br />\r\n<br />\r\nIt&#39;s available in chocolate, vanilla, and coffee flavors, all naturally sweetened. Whether you&#39;re an athlete or just starting your fitness journey, this blend works for all body types.<br />\r\n<br />\r\nWith competitive pricing and strong margins, our MLM agents can position this product to gyms, fitness influencers, and lifestyle customers alike.</p>\r\n', 'uploads/category/1752135581_3d_image_for_this_blog_thumline_why.jpeg', 1, '4', '2025-07-10 00:00:00'),
(8, 'Top 3 Home Aromatherapy Kits to Reduce Stress and Improve Sleep', 'top-3-home-aromatherapy-kits-to-reduce-stress-and-improve-sleep', '<p>Aromatherapy has gone mainstream. Our essential oil diffusers and oil sets &mdash; lavender, eucalyptus, and lemon &mdash; help improve mood and sleep quality while enhancing home ambiance.<br />\r\n<br />\r\nDesigned to fit any room d&eacute;cor, the diffusers operate silently with smart mist control and LED lighting. Perfect for gifting, relaxation, or simply winding down after work.<br />\r\n<br />\r\nCustomers love the combo offers and subscription refills, giving MLM sellers recurring sales and great word-of-mouth marketing.</p>\r\n', 'uploads/category/1752135659_3d_image_for_this_blog_thumline_(1).jpeg', 1, '6', '2025-07-10 00:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `blog_categories`
--

CREATE TABLE `blog_categories` (
  `name` varchar(150) DEFAULT NULL,
  `status` int(11) NOT NULL DEFAULT 0,
  `description` text DEFAULT NULL,
  `created_date` datetime DEFAULT NULL,
  `id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `blog_categories`
--

INSERT INTO `blog_categories` (`name`, `status`, `description`, `created_date`, `id`) VALUES
('Health & Wellness Products', 1, '<p>Stay updated on the latest trends, tips, and reviews for top-selling health and wellness products offered in our MLM store. Discover how these products improve lives and boost your earning potential.</p>', '2025-07-10 13:43:31', 3),
('Beauty & Personal Care', 1, '<p>Learn how natural skincare and beauty products are transforming routines across the MLM world.</p>', '2025-07-10 13:45:07', 4),
('Fitness & Nutrition', 1, '<p>Stay fit, energized, and lean with our best-in-class nutritional supplements and protein products.</p>', '2025-07-10 13:45:22', 5),
('Home & Lifestyle', 1, '<p>Discover smart living with products that simplify, beautify, and enhance daily routines.</p>', '2025-07-10 13:45:35', 6),
('Electronics & Accessories', 1, '<p>Stay updated with trending gadgets and tools built for smart consumers and tech-savvy sellers.</p>', '2025-07-10 13:45:48', 7),
('Kids & Baby Care', 1, '<p>Safe, fun, and tested products every parent needs in their routine.</p>', '2025-07-10 13:46:02', 8);

-- --------------------------------------------------------

--
-- Table structure for table `brands`
--

CREATE TABLE `brands` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `brand_img` varchar(250) DEFAULT NULL,
  `description` varchar(250) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `brands`
--

INSERT INTO `brands` (`id`, `name`, `slug`, `status`, `sort_order`, `brand_img`, `description`) VALUES
(1, 'Nike', 'nike', 1, 0, 'uploads/category/1752049842_nike_brand_logo_3d_cortton.jpeg', '<p>Nike inspires greatness with innovative sportswear, footwear, and gear.<br>Designed for performance, style, and pushing your limits every day.</p>'),
(2, 'Adidas', 'adidas', 1, 0, 'uploads/category/1752049781_adidas_name_brand_logo_3d_cortton.jpeg', '<p>Adidas combines performance and style with innovative sportswear and footwear.<br>Empowering athletes and creators with gear built for comfort and excellence.</p>'),
(3, 'Puma', 'puma', 1, 0, 'uploads/category/1752049742_puma_name_bran_logo_3d_cortton.jpeg', '<p>Puma blends sport and style with high-performance footwear, apparel, and accessories.<br>Designed for athletes and trendsetters who move the world forward.</p>'),
(4, 'Samsung', 'samsung', 1, 0, 'uploads/category/1752049705_samsung_brand_name_logo_3d_cortton.jpeg', '<p>Samsung leads the way in smart technology with innovative phones, TVs, and appliances.<br>Experience cutting-edge design, powerful performance, and trusted quality.</p>'),
(5, 'Apple', 'apple', 1, 0, 'uploads/category/1752049654_apple_brand_name_logo_3d_cortton.jpeg', '<p>Apple redefines innovation with sleek, powerful devices and a seamless ecosystem.<br>From iPhones to MacBooks, experience premium design, performance, and reliability.</p>'),
(6, 'Sony', 'sony', 1, 0, 'uploads/category/1752049609_sony_brand_logo_3d_cortton.jpeg', '<p>Sony delivers cutting-edge technology in entertainment, electronics, and imaging.<br>Experience premium quality with powerful audio, visual, and gaming products.</p>'),
(7, 'Prestige', 'prestige', 1, 0, 'uploads/category/1752049567_prestige_name_logo_genreate_3d_cortton.jpeg', '<p>Prestige is a trusted name in kitchen appliances, known for quality and innovation.<br>From pressure cookers to modern cookware, simplify cooking with style and safety.</p>'),
(8, 'Philips', 'philips', 1, 0, 'uploads/category/1752049520_philips_name_logo_genraet_3d_cortton.jpeg', '<p>Philips offers innovative and user-friendly solutions in personal care, lighting, and home appliances.<br>Trusted worldwide for quality, performance, and cutting-edge technology.</p>'),
(9, 'Bosch', 'bosch', 1, 0, 'uploads/category/1752049467_images_bosh.png', '<p>Bosch delivers innovative technology and reliable performance across home and automotive solutions.<br>Trusted worldwide for quality tools, appliances, and engineering excellence.</p>'),
(10, 'Castrol', 'castrol', 1, 0, 'uploads/category/1752049345_castrol_brand_3d_cortton_image.jpeg', '<p>Castrol is a global leader in high-performance engine oils and lubricants.<br>Trusted by professionals to protect and power engines with advanced technology.</p>');

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart`
--

INSERT INTO `cart` (`id`, `user_id`, `product_id`, `quantity`, `created_at`) VALUES
(76, 251, 9, 1, '2025-10-13 12:45:20'),
(77, 250, 14, 1, '2026-01-30 11:58:41');

-- --------------------------------------------------------

--
-- Table structure for table `commission_config`
--

CREATE TABLE `commission_config` (
  `id` int(11) NOT NULL,
  `direct_commission_status` int(11) DEFAULT 0,
  `level_commission_status` int(11) DEFAULT 0,
  `update_date` datetime DEFAULT NULL,
  `binary_pair_type` enum('percent','amount') NOT NULL DEFAULT 'percent',
  `binary_pair_ratio` varchar(10) NOT NULL DEFAULT '1:1',
  `repurchase_commission_status` tinyint(1) NOT NULL DEFAULT 0,
  `leadership_bonus_status` tinyint(1) NOT NULL DEFAULT 0,
  `pool_bonus_status` tinyint(1) NOT NULL DEFAULT 0,
  `binary_commission_status` int(11) NOT NULL DEFAULT 0,
  `matching_bonus_status` tinyint(1) NOT NULL DEFAULT 0,
  `direct_commission_type` varchar(250) DEFAULT '0',
  `own_commission_status` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `commission_config`
--

INSERT INTO `commission_config` (`id`, `direct_commission_status`, `level_commission_status`, `update_date`, `binary_pair_type`, `binary_pair_ratio`, `repurchase_commission_status`, `leadership_bonus_status`, `pool_bonus_status`, `binary_commission_status`, `matching_bonus_status`, `direct_commission_type`, `own_commission_status`) VALUES
(1, 1, 1, '2025-08-29 11:38:47', 'percent', '1:1', 1, 1, 1, 1, 1, 'percent', 1);

-- --------------------------------------------------------

--
-- Table structure for table `countries`
--

CREATE TABLE `countries` (
  `id` int(11) NOT NULL,
  `code` varchar(10) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `dial_code` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `countries`
--

INSERT INTO `countries` (`id`, `code`, `name`, `dial_code`) VALUES
(1, 'AF', 'Afghanistan', '+93'),
(2, 'AL', 'Albania', '+355'),
(3, 'DZ', 'Algeria', '+213'),
(4, 'AS', 'American Samoa', '+1-684'),
(5, 'AD', 'Andorra', '+376'),
(6, 'AO', 'Angola', '+244'),
(7, 'AI', 'Anguilla', '+1-264'),
(8, 'AG', 'Antigua and Barbuda', '+1-268'),
(9, 'AR', 'Argentina', '+54'),
(10, 'AM', 'Armenia', '+374'),
(11, 'AW', 'Aruba', '+297'),
(12, 'AU', 'Australia', '+61'),
(13, 'AT', 'Austria', '+43'),
(14, 'AZ', 'Azerbaijan', '+994'),
(15, 'BS', 'Bahamas', '+1-242'),
(16, 'BH', 'Bahrain', '+973'),
(17, 'BD', 'Bangladesh', '+880'),
(18, 'BB', 'Barbados', '+1-246'),
(19, 'BY', 'Belarus', '+375'),
(20, 'BE', 'Belgium', '+32'),
(21, 'BZ', 'Belize', '+501'),
(22, 'BJ', 'Benin', '+229'),
(23, 'BM', 'Bermuda', '+1-441'),
(24, 'BT', 'Bhutan', '+975'),
(25, 'BO', 'Bolivia', '+591'),
(26, 'BA', 'Bosnia and Herzegovina', '+387'),
(27, 'BW', 'Botswana', '+267'),
(28, 'BR', 'Brazil', '+55'),
(29, 'IO', 'British Indian Ocean Territory', '+246'),
(30, 'VG', 'British Virgin Islands', '+1-284'),
(31, 'BN', 'Brunei', '+673'),
(32, 'BG', 'Bulgaria', '+359'),
(33, 'BF', 'Burkina Faso', '+226'),
(34, 'BI', 'Burundi', '+257'),
(35, 'KH', 'Cambodia', '+855'),
(36, 'CM', 'Cameroon', '+237'),
(37, 'CA', 'Canada', '+1'),
(38, 'CV', 'Cape Verde', '+238'),
(39, 'KY', 'Cayman Islands', '+1-345'),
(40, 'CF', 'Central African Republic', '+236'),
(41, 'TD', 'Chad', '+235'),
(42, 'CL', 'Chile', '+56'),
(43, 'CN', 'China', '+86'),
(44, 'CX', 'Christmas Island', '+61'),
(45, 'CC', 'Cocos Islands', '+61'),
(46, 'CO', 'Colombia', '+57'),
(47, 'KM', 'Comoros', '+269'),
(48, 'CK', 'Cook Islands', '+682'),
(49, 'CR', 'Costa Rica', '+506'),
(50, 'HR', 'Croatia', '+385'),
(51, 'CU', 'Cuba', '+53'),
(52, 'CY', 'Cyprus', '+357'),
(53, 'CZ', 'Czech Republic', '+420'),
(54, 'CD', 'Democratic Republic of the Congo', '+243'),
(55, 'DK', 'Denmark', '+45'),
(56, 'DJ', 'Djibouti', '+253'),
(57, 'DM', 'Dominica', '+1-767'),
(58, 'DO', 'Dominican Republic', '+1-809'),
(59, 'DO', 'Dominican Republic', '+1-829'),
(60, 'TL', 'East Timor', '+670'),
(61, 'EC', 'Ecuador', '+593'),
(62, 'EG', 'Egypt', '+20'),
(63, 'SV', 'El Salvador', '+503'),
(64, 'GQ', 'Equatorial Guinea', '+240'),
(65, 'ER', 'Eritrea', '+291'),
(66, 'EE', 'Estonia', '+372'),
(67, 'ET', 'Ethiopia', '+251'),
(68, 'FK', 'Falkland Islands', '+500'),
(69, 'FO', 'Faroe Islands', '+298'),
(70, 'FJ', 'Fiji', '+679'),
(71, 'FI', 'Finland', '+358'),
(72, 'FR', 'France', '+33'),
(73, 'PF', 'French Polynesia', '+689'),
(74, 'GA', 'Gabon', '+241'),
(75, 'GM', 'Gambia', '+220'),
(76, 'GE', 'Georgia', '+995'),
(77, 'DE', 'Germany', '+49'),
(78, 'GH', 'Ghana', '+233'),
(79, 'GI', 'Gibraltar', '+350'),
(80, 'GR', 'Greece', '+30'),
(81, 'GL', 'Greenland', '+299'),
(82, 'GD', 'Grenada', '+1-473'),
(83, 'GU', 'Guam', '+1-671'),
(84, 'GT', 'Guatemala', '+502'),
(85, 'GN', 'Guinea', '+224'),
(86, 'GW', 'Guinea-Bissau', '+245'),
(87, 'GY', 'Guyana', '+592'),
(88, 'HT', 'Haiti', '+509'),
(89, 'HN', 'Honduras', '+504'),
(90, 'HK', 'Hong Kong', '+852'),
(91, 'HU', 'Hungary', '+36'),
(92, 'IS', 'Iceland', '+354'),
(93, 'IN', 'India', '+91'),
(94, 'ID', 'Indonesia', '+62'),
(95, 'IR', 'Iran', '+98'),
(96, 'IQ', 'Iraq', '+964'),
(97, 'IE', 'Ireland', '+353'),
(98, 'IM', 'Isle of Man', '+44-1624'),
(99, 'IL', 'Israel', '+972'),
(100, 'IT', 'Italy', '+39'),
(101, 'CI', 'Ivory Coast', '+225'),
(102, 'JM', 'Jamaica', '+1-876'),
(103, 'JP', 'Japan', '+81'),
(104, 'JE', 'Jersey', '+44-1534'),
(105, 'JO', 'Jordan', '+962'),
(106, 'KZ', 'Kazakhstan', '+7'),
(107, 'KE', 'Kenya', '+254'),
(108, 'KI', 'Kiribati', '+686'),
(109, 'KW', 'Kuwait', '+965'),
(110, 'KG', 'Kyrgyzstan', '+996'),
(111, 'LA', 'Laos', '+856'),
(112, 'LV', 'Latvia', '+371'),
(113, 'LB', 'Lebanon', '+961'),
(114, 'LS', 'Lesotho', '+266'),
(115, 'LR', 'Liberia', '+231'),
(116, 'LY', 'Libya', '+218'),
(117, 'LI', 'Liechtenstein', '+423'),
(118, 'LT', 'Lithuania', '+370'),
(119, 'LU', 'Luxembourg', '+352'),
(120, 'MO', 'Macao', '+853'),
(121, 'MK', 'Macedonia', '+389'),
(122, 'MG', 'Madagascar', '+261'),
(123, 'MW', 'Malawi', '+265'),
(124, 'MY', 'Malaysia', '+60'),
(125, 'MV', 'Maldives', '+960'),
(126, 'ML', 'Mali', '+223'),
(127, 'MT', 'Malta', '+356'),
(128, 'MH', 'Marshall Islands', '+692'),
(129, 'MQ', 'Martinique', '+596'),
(130, 'MR', 'Mauritania', '+222'),
(131, 'MU', 'Mauritius', '+230'),
(132, 'YT', 'Mayotte', '+262'),
(133, 'MX', 'Mexico', '+52'),
(134, 'FM', 'Micronesia', '+691'),
(135, 'MD', 'Moldova', '+373'),
(136, 'MC', 'Monaco', '+377'),
(137, 'MN', 'Mongolia', '+976'),
(138, 'ME', 'Montenegro', '+382'),
(139, 'MS', 'Montserrat', '+1-664'),
(140, 'MA', 'Morocco', '+212'),
(141, 'MZ', 'Mozambique', '+258'),
(142, 'MM', 'Myanmar', '+95'),
(143, 'NA', 'Namibia', '+264'),
(144, 'NR', 'Nauru', '+674'),
(145, 'NP', 'Nepal', '+977'),
(146, 'NL', 'Netherlands', '+31'),
(147, 'AN', 'Netherlands Antilles', '+599'),
(148, 'NC', 'New Caledonia', '+687'),
(149, 'NZ', 'New Zealand', '+64'),
(150, 'NI', 'Nicaragua', '+505'),
(151, 'NE', 'Niger', '+227'),
(152, 'NG', 'Nigeria', '+234'),
(153, 'NU', 'Niue', '+683'),
(154, 'KP', 'North Korea', '+850'),
(155, 'MP', 'Northern Mariana Islands', '+1-670'),
(156, 'NO', 'Norway', '+47'),
(157, 'OM', 'Oman', '+968'),
(158, 'PK', 'Pakistan', '+92'),
(159, 'PW', 'Palau', '+680'),
(160, 'PA', 'Panama', '+507'),
(161, 'PG', 'Papua New Guinea', '+675'),
(162, 'PY', 'Paraguay', '+595'),
(163, 'PE', 'Peru', '+51'),
(164, 'PH', 'Philippines', '+63'),
(165, 'PN', 'Pitcairn', '+870'),
(166, 'PL', 'Poland', '+48'),
(167, 'PT', 'Portugal', '+351'),
(168, 'PR', 'Puerto Rico', '+1-787'),
(169, 'PR', 'Puerto Rico', '+1-939'),
(170, 'QA', 'Qatar', '+974'),
(171, 'CG', 'Republic of the Congo', '+242'),
(172, 'RO', 'Romania', '+40'),
(173, 'RU', 'Russia', '+7'),
(174, 'RW', 'Rwanda', '+250'),
(175, 'BL', 'Saint Barthelemy', '+590'),
(176, 'SH', 'Saint Helena', '+290'),
(177, 'KN', 'Saint Kitts and Nevis', '+1-869'),
(178, 'LC', 'Saint Lucia', '+1-758'),
(179, 'MF', 'Saint Martin', '+590'),
(180, 'PM', 'Saint Pierre and Miquelon', '+508'),
(181, 'VC', 'Saint Vincent and the Grenadines', '+1-784'),
(182, 'WS', 'Samoa', '+685'),
(183, 'SM', 'San Marino', '+378'),
(184, 'ST', 'Sao Tome and Principe', '+239'),
(185, 'SA', 'Saudi Arabia', '+966'),
(186, 'SN', 'Senegal', '+221'),
(187, 'RS', 'Serbia', '+381'),
(188, 'SC', 'Seychelles', '+248'),
(189, 'SL', 'Sierra Leone', '+232'),
(190, 'SG', 'Singapore', '+65'),
(191, 'SK', 'Slovakia', '+421'),
(192, 'SI', 'Slovenia', '+386'),
(193, 'SB', 'Solomon Islands', '+677'),
(194, 'SO', 'Somalia', '+252'),
(195, 'ZA', 'South Africa', '+27'),
(196, 'KR', 'South Korea', '+82'),
(197, 'ES', 'Spain', '+34'),
(198, 'LK', 'Sri Lanka', '+94'),
(199, 'SD', 'Sudan', '+249'),
(200, 'SR', 'Suriname', '+597'),
(201, 'SJ', 'Svalbard and Jan Mayen', '+47'),
(202, 'SZ', 'Swaziland', '+268'),
(203, 'SE', 'Sweden', '+46'),
(204, 'CH', 'Switzerland', '+41'),
(205, 'SY', 'Syria', '+963'),
(206, 'TW', 'Taiwan', '+886'),
(207, 'TJ', 'Tajikistan', '+992'),
(208, 'TZ', 'Tanzania', '+255'),
(209, 'TH', 'Thailand', '+66'),
(210, 'TG', 'Togo', '+228'),
(211, 'TK', 'Tokelau', '+690'),
(212, 'TO', 'Tonga', '+676'),
(213, 'TT', 'Trinidad and Tobago', '+1-868'),
(214, 'TN', 'Tunisia', '+216'),
(215, 'TR', 'Turkey', '+90'),
(216, 'TM', 'Turkmenistan', '+993'),
(217, 'TC', 'Turks and Caicos Islands', '+1-649'),
(218, 'TV', 'Tuvalu', '+688'),
(219, 'VI', 'US Virgin Islands', '+1-340'),
(220, 'UG', 'Uganda', '+256'),
(221, 'UA', 'Ukraine', '+380'),
(222, 'AE', 'United Arab Emirates', '+971'),
(223, 'GB', 'United Kingdom', '+44'),
(224, 'US', 'United States', '+1'),
(225, 'UY', 'Uruguay', '+598'),
(226, 'UZ', 'Uzbekistan', '+998'),
(227, 'VU', 'Vanuatu', '+678'),
(228, 'VA', 'Vatican City', '+379'),
(229, 'VE', 'Venezuela', '+58'),
(230, 'VN', 'Vietnam', '+84'),
(231, 'WF', 'Wallis and Futuna', '+681'),
(232, 'EH', 'Western Sahara', '+212'),
(233, 'YE', 'Yemen', '+967'),
(234, 'ZM', 'Zambia', '+260'),
(235, 'ZW', 'Zimbabwe', '+263');

-- --------------------------------------------------------

--
-- Table structure for table `coupons`
--

CREATE TABLE `coupons` (
  `id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `discount_type` enum('percentage','fixed') NOT NULL,
  `discount_value` decimal(10,2) NOT NULL,
  `usage_limit` int(11) DEFAULT NULL,
  `usage_per_user` int(11) DEFAULT NULL,
  `min_order_amount` decimal(10,2) DEFAULT NULL,
  `max_discount` decimal(10,2) DEFAULT NULL,
  `valid_from` date NOT NULL,
  `valid_to` date NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `coupons`
--

INSERT INTO `coupons` (`id`, `code`, `discount_type`, `discount_value`, `usage_limit`, `usage_per_user`, `min_order_amount`, `max_discount`, `valid_from`, `valid_to`, `status`, `created_at`, `updated_at`) VALUES
(3, 'SAVE20', 'percentage', 20.00, 500, 2, 50.00, 25.00, '2025-07-10', '2025-07-31', 'active', '2025-07-09 15:17:52', '2025-07-09 15:17:52'),
(4, 'SAVE30', 'percentage', 30.00, 500, 2, 60.00, 30.00, '2025-07-10', '2025-07-31', 'active', '2025-07-09 15:18:31', '2025-07-09 15:18:31');

-- --------------------------------------------------------

--
-- Table structure for table `coupon_usage`
--

CREATE TABLE `coupon_usage` (
  `id` int(11) NOT NULL,
  `coupon_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `applied_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `currency_config`
--

CREATE TABLE `currency_config` (
  `id` int(11) NOT NULL,
  `coin_name` varchar(250) NOT NULL,
  `currency_status` int(11) NOT NULL,
  `api_call` varchar(250) DEFAULT NULL,
  `decimal` int(11) DEFAULT 2,
  `currency_value` varchar(250) NOT NULL DEFAULT '2',
  `staking_toke_symbol` varchar(250) DEFAULT NULL,
  `staking_toke_name` varchar(240) DEFAULT NULL,
  `currency_symbol` blob DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `currency_config`
--

INSERT INTO `currency_config` (`id`, `coin_name`, `currency_status`, `api_call`, `decimal`, `currency_value`, `staking_toke_symbol`, `staking_toke_name`, `currency_symbol`) VALUES
(1, 'USD', 1, '1b6ed52ef6a6416c1acc3095b52ac90f83e26dd35edd72f95c225795dcc38a67', 3, '1', '', 'CSQ', 0x24),
(6, 'USDT', 0, '1b6ed52ef6a6416c1acc3095b52ac90f83e26dd35edd72f95c225795dcc38a67', 2, '1', '', 'AUSD', 0xe282ae);

-- --------------------------------------------------------

--
-- Table structure for table `deposits`
--

CREATE TABLE `deposits` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `status` enum('pending','completed','failed') DEFAULT 'pending',
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `package_id` varchar(250) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `deposits`
--

INSERT INTO `deposits` (`id`, `user_id`, `amount`, `payment_method`, `status`, `created_at`, `updated_at`, `package_id`) VALUES
(1, 247, 2.00, 'paypal', 'pending', '2025-07-14 18:43:21', NULL, NULL),
(2, 247, 1.00, 'paypal', 'pending', '2025-07-14 18:49:03', NULL, NULL),
(3, 247, 1.00, 'paypal', 'pending', '2025-07-14 18:49:08', NULL, NULL),
(4, 247, 1.00, 'paypal', 'pending', '2025-07-14 18:49:23', NULL, NULL),
(5, 247, 1.00, 'paypal', 'pending', '2025-07-14 18:50:20', NULL, NULL),
(6, 247, 1.00, 'paypal', 'pending', '2025-07-14 18:50:27', NULL, NULL),
(7, 247, 1.00, 'paypal', 'pending', '2025-07-14 18:53:29', NULL, NULL),
(8, 247, 1.00, 'paypal', 'pending', '2025-07-14 18:53:36', NULL, NULL),
(9, 247, 1.00, 'paypal', 'pending', '2025-07-14 18:53:55', NULL, NULL),
(10, 247, 1.00, 'paypal', 'pending', '2025-07-14 18:55:37', NULL, NULL),
(11, 247, 1.00, 'paypal', 'pending', '2025-07-14 18:55:44', NULL, NULL),
(12, 247, 1.00, 'paypal', 'pending', '2025-07-14 18:55:51', NULL, NULL),
(13, 247, 1.00, 'paypal', 'pending', '2025-07-14 18:55:52', NULL, NULL),
(14, 247, 1.00, 'paypal', 'pending', '2025-07-14 18:55:53', NULL, NULL),
(15, 247, 1.00, 'paypal', 'pending', '2025-07-14 18:55:53', NULL, NULL),
(16, 247, 1.00, 'stripe', 'completed', '2025-07-14 18:56:01', NULL, NULL),
(17, 247, 1.00, 'paypal', 'pending', '2025-07-14 18:59:19', NULL, NULL),
(18, 247, 1.00, 'paypal', 'pending', '2025-07-14 18:59:27', NULL, NULL),
(19, 247, 1.00, 'paypal', 'pending', '2025-07-14 18:59:33', NULL, NULL),
(20, 247, 1.00, 'paypal', 'pending', '2025-07-14 18:59:40', NULL, NULL),
(21, 247, 1.00, 'paypal', 'pending', '2025-07-14 18:59:42', NULL, NULL),
(22, 247, 1.00, 'stripe', 'pending', '2025-07-14 18:59:53', NULL, NULL),
(23, 247, 1.00, 'stripe', 'pending', '2025-07-14 19:01:03', NULL, NULL),
(24, 247, 1.00, 'stripe', 'completed', '2025-07-14 19:27:30', NULL, NULL),
(25, 247, 1.00, 'stripe', 'completed', '2025-07-14 19:45:08', NULL, '8'),
(26, 247, 1.00, 'stripe', 'completed', '2025-07-14 19:47:41', NULL, '8'),
(27, 247, 1.00, 'stripe', 'pending', '2025-07-15 11:37:27', NULL, '8'),
(28, 247, 1.00, 'stripe', 'failed', '2025-07-15 15:17:50', NULL, '8'),
(29, 247, 1.00, 'stripe', 'failed', '2025-07-15 15:40:19', NULL, '8'),
(30, 247, 12.00, 'stripe', 'completed', '2025-07-15 15:40:58', NULL, '8'),
(31, 247, 1.00, 'stripe', 'completed', '2025-07-15 15:43:04', NULL, '8'),
(32, 247, 1.00, 'stripe', 'completed', '2025-07-15 15:59:16', NULL, '8'),
(33, 247, 100.00, 'wallet', 'pending', '2025-08-30 09:49:00', NULL, '4'),
(34, 247, 100.00, 'stripe', 'pending', '2025-08-30 09:49:04', NULL, '4'),
(35, 250, 1000.00, 'wallet', 'pending', '2026-02-01 08:24:48', NULL, '9'),
(36, 250, 1000.00, 'wallet', 'pending', '2026-02-01 08:25:03', NULL, '9'),
(37, 250, 100.00, 'paypal', 'pending', '2026-02-01 08:30:10', NULL, '4'),
(38, 250, 100.00, 'stripe', 'pending', '2026-02-01 08:30:21', NULL, '4'),
(39, 250, 100.00, 'paypal', 'pending', '2026-02-01 08:31:20', NULL, '9'),
(40, 250, 100.00, 'stripe', 'pending', '2026-02-01 08:31:34', NULL, '9'),
(41, 250, 1500.00, 'stripe', 'pending', '2026-02-01 08:32:32', NULL, '9'),
(42, 250, 150.00, 'stripe', 'completed', '2026-02-01 08:34:29', NULL, '4');

-- --------------------------------------------------------

--
-- Table structure for table `earning_ads`
--

CREATE TABLE `earning_ads` (
  `id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `ad_url` text NOT NULL,
  `thumb_url` text DEFAULT NULL,
  `duration_seconds` int(11) NOT NULL DEFAULT 30,
  `reward_usd` decimal(12,2) NOT NULL DEFAULT 0.00,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `earning_ads`
--

INSERT INTO `earning_ads` (`id`, `title`, `description`, `ad_url`, `thumb_url`, `duration_seconds`, `reward_usd`, `is_active`, `sort_order`, `created_at`) VALUES
(1, 'Ad 1 - Short', 'Watch full 30s ad to earn', 'https://www.youtube.com/watch?v=ysz5S6PUM-U', NULL, 30, 0.50, 1, 1, '2026-02-01 12:32:35'),
(2, 'Ad 2 - Promo', 'Quick promo ad', 'https://www.youtube.com/watch?v=jNQXAC9IVRw', NULL, 20, 0.30, 1, 2, '2026-02-01 12:32:35'),
(3, 'Ad 3 - Brand', 'Brand awareness', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', NULL, 30, 0.50, 1, 3, '2026-02-01 12:32:35');

-- --------------------------------------------------------

--
-- Table structure for table `earning_methods`
--

CREATE TABLE `earning_methods` (
  `id` int(11) NOT NULL,
  `code` varchar(30) NOT NULL,
  `title` varchar(100) NOT NULL,
  `subtitle` varchar(255) DEFAULT NULL,
  `icon` varchar(60) DEFAULT NULL,
  `badge_text` varchar(30) DEFAULT NULL,
  `badge_bg` varchar(30) DEFAULT NULL,
  `badge_color` varchar(30) DEFAULT NULL,
  `progress_color` varchar(30) DEFAULT NULL,
  `btn_text` varchar(50) DEFAULT NULL,
  `btn_gradient` varchar(120) DEFAULT NULL,
  `daily_target` int(11) NOT NULL DEFAULT 0,
  `reward_usd` decimal(12,2) NOT NULL DEFAULT 0.00,
  `est_time_label` varchar(50) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `earning_methods`
--

INSERT INTO `earning_methods` (`id`, `code`, `title`, `subtitle`, `icon`, `badge_text`, `badge_bg`, `badge_color`, `progress_color`, `btn_text`, `btn_gradient`, `daily_target`, `reward_usd`, `est_time_label`, `is_active`, `sort_order`) VALUES
(1, 'ads', 'Watch Ads', 'Instant credits for short 30s ads.', 'ph-television', 'ACTIVE', '#f0fdf4', '#16a34a', NULL, 'Start Watching Now', 'linear-gradient(135deg, #6E56CF 0%, #4D39A3 100%)', 20, 5.00, '30 Sec', 1, 1),
(2, 'videos', 'Watch Videos', 'High reward premium content.', 'ph-youtube-logo', 'HOT', '#fff7ed', '#ea580c', '#d63384', 'Browse Videos', 'linear-gradient(135deg, #d63384 0%, #a01a5d 100%)', 10, 15.00, '3 Mins', 1, 2);

-- --------------------------------------------------------

--
-- Table structure for table `earning_videos`
--

CREATE TABLE `earning_videos` (
  `id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `video_url` text NOT NULL,
  `thumb_url` text DEFAULT NULL,
  `duration_seconds` int(11) NOT NULL DEFAULT 30,
  `reward_usd` decimal(12,2) NOT NULL DEFAULT 0.00,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `earning_videos`
--

INSERT INTO `earning_videos` (`id`, `title`, `description`, `video_url`, `thumb_url`, `duration_seconds`, `reward_usd`, `is_active`, `sort_order`, `created_at`) VALUES
(1, 'Welcome Video 1', 'Watch full video to earn', 'https://www.youtube.com/shorts/D0M8apaXB1Q', NULL, 30, 1.50, 1, 1, '2026-02-01 12:20:48'),
(2, 'Promo Video 2', 'Short promo', 'https://www.youtube.com/shorts/D0M8apaXB1Q', NULL, 45, 2.00, 1, 2, '2026-02-01 12:20:48'),
(3, 'Training Video 3', 'Learn & earn', 'https://www.youtube.com/shorts/D0M8apaXB1Q', NULL, 60, 3.00, 1, 3, '2026-02-01 12:20:48');

-- --------------------------------------------------------

--
-- Table structure for table `email_config`
--

CREATE TABLE `email_config` (
  `id` int(11) NOT NULL,
  `host` varchar(250) DEFAULT NULL,
  `smtp_auth` varchar(50) DEFAULT NULL,
  `username` varchar(250) DEFAULT NULL,
  `password` varchar(250) DEFAULT NULL,
  `smtpsecure` varchar(150) DEFAULT NULL,
  `port` int(11) DEFAULT NULL,
  `from_name` varchar(150) DEFAULT NULL,
  `from_mail` varchar(150) DEFAULT NULL,
  `php_mail` varchar(150) DEFAULT NULL,
  `smtp_status` enum('0','1') DEFAULT NULL,
  `updated_name` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `email_config`
--

INSERT INTO `email_config` (`id`, `host`, `smtp_auth`, `username`, `password`, `smtpsecure`, `port`, `from_name`, `from_mail`, `php_mail`, `smtp_status`, `updated_name`) VALUES
(1, 'test', 'true', 'test', 'v@}3Sn?@em,x', 'ssl', 465, 'support@fenizotechnologies.com', 'support@fenizotechnologies.com', 'support@adrox.ai', '0', '2025-05-07 10:50:45');

-- --------------------------------------------------------

--
-- Table structure for table `email_log`
--

CREATE TABLE `email_log` (
  `id` int(11) NOT NULL,
  `otp` varchar(250) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `type` varchar(150) DEFAULT NULL,
  `created_date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `email_log`
--

INSERT INTO `email_log` (`id`, `otp`, `email`, `type`, `created_date`) VALUES
(1, '384297', 'ashokece68@gmail.com', 'email_verify', '2025-04-11 21:00:25'),
(2, '384297', 'ashokece68@gmail.com', 'email_verify', '2025-04-11 21:10:08'),
(3, '384297', 'ashokece68@gmail.com', 'email_verify', '2025-04-11 21:12:54'),
(4, '384297', 'ashokece68@gmail.com', 'email_verify', '2025-04-11 21:14:01'),
(5, '384297', 'ashokece68@gmail.com', 'email_verify', '2025-04-11 21:19:05'),
(6, '384297', 'ashokece68@gmail.com', 'email_verify', '2025-04-11 21:23:08'),
(7, '384297', 'ashokece68@gmail.com', 'email_verify', '2025-04-11 21:24:50'),
(8, '384297', 'ashokece68@gmail.com', 'email_verify', '2025-04-11 21:29:16'),
(9, '384297', 'ashokece68@gmail.com', 'email_verify', '2025-04-11 21:38:16'),
(10, '384297', 'ashokece68@gmail.com', 'email_verify', '2025-04-11 21:41:12'),
(11, '924671', 'adroxorganization@gmail.com', 'email_verify', '2025-04-11 23:31:44'),
(12, '924671', 'adroxorganization@gmail.com', 'email_verify', '2025-04-11 23:33:09'),
(13, '924671', 'adroxorganization@gmail.com', 'email_verify', '2025-04-11 23:33:25'),
(14, '924671', 'adroxorganization@gmail.com', 'email_verify', '2025-04-11 23:34:27'),
(15, '924671', 'adroxorganization@gmail.com', 'email_verify', '2025-04-11 23:38:02'),
(16, '924671', 'adroxorganization@gmail.com', 'email_verify', '2025-04-11 23:41:13'),
(17, '924671', 'adroxorganization@gmail.com', 'email_verify', '2025-04-11 23:42:33'),
(18, '924671', 'adroxorganization@gmail.com', 'email_verify', '2025-04-11 23:43:58'),
(19, '924671', 'adroxorganization@gmail.com', 'email_verify', '2025-04-11 23:51:03'),
(20, '924671', 'adroxorganization@gmail.com', 'email_verify', '2025-04-11 23:53:40'),
(21, '924671', 'adroxorganization@gmail.com', 'email_verify', '2025-04-11 23:57:00'),
(22, '924671', 'adroxorganization@gmail.com', 'email_verify', '2025-04-11 23:57:34'),
(23, '924671', 'adroxorganization@gmail.com', 'email_verify', '2025-04-12 11:17:01'),
(24, '924671', 'adroxorganization@gmail.com', 'email_verify', '2025-04-12 23:09:22'),
(25, '924671', 'adroxorganization@gmail.com', 'email_verify', '2025-04-12 23:18:26'),
(26, '924671', 'adroxorganization@gmail.com', 'email_verify', '2025-04-12 23:20:13'),
(27, '924671', 'adroxorganization@gmail.com', 'email_verify', '2025-04-13 07:25:00'),
(28, '924671', 'adroxorganization@gmail.com', 'email_verify', '2025-04-13 14:16:34'),
(29, '924671', 'adroxorganization@gmail.com', 'email_verify', '2025-04-13 14:40:57'),
(30, '924671', 'adroxorganization@gmail.com', 'email_verify', '2025-04-14 14:56:55'),
(31, '924671', 'adroxorganization@gmail.com', 'email_verify', '2025-04-14 15:52:54'),
(32, '924671', 'adroxorganization@gmail.com', 'email_verify', '2025-04-14 15:54:56'),
(33, '924671', 'adroxorganization@gmail.com', 'email_verify', '2025-04-15 08:28:14'),
(34, '924671', 'adroxorganization@gmail.com', 'email_verify', '2025-04-15 16:21:43'),
(35, '924671', 'adroxorganization@gmail.com', 'email_verify', '2025-04-16 00:03:52'),
(36, '924671', 'adroxorganization@gmail.com', 'email_verify', '2025-04-16 20:27:58'),
(37, '924671', 'adroxorganization@gmail.com', 'email_verify', '2025-04-16 20:47:51'),
(38, '924671', 'adroxorganization@gmail.com', 'email_verify', '2025-04-16 20:49:32'),
(39, '924671', 'adroxorganization@gmail.com', 'email_verify', '2025-04-16 21:30:44'),
(40, '924671', 'adroxorganization@gmail.com', 'email_verify', '2025-04-17 14:27:45'),
(41, '924671', 'adroxorganization@gmail.com', 'email_verify', '2025-04-17 22:16:31'),
(42, '924671', 'adroxorganization@gmail.com', 'email_verify', '2025-04-18 00:19:41'),
(43, '924671', 'adroxorganization@gmail.com', 'email_verify', '2025-04-18 08:16:18'),
(44, '924671', 'adroxorganization@gmail.com', 'email_verify', '2025-04-19 11:42:01'),
(45, '924671', 'adroxorganization@gmail.com', 'email_verify', '2025-04-19 11:43:26'),
(46, '924671', 'adroxorganization@gmail.com', 'email_verify', '2025-04-19 13:14:47'),
(47, '924671', 'adroxorganization@gmail.com', 'email_verify', '2025-04-19 13:46:49'),
(48, '924671', 'adroxorganization@gmail.com', 'email_verify', '2025-04-19 16:53:43'),
(49, '924671', 'adroxorganization@gmail.com', 'email_verify', '2025-04-19 16:55:44'),
(50, '924671', 'adroxorganization@gmail.com', 'email_verify', '2025-04-19 21:42:32'),
(51, '924671', 'adroxorganization@gmail.com', 'email_verify', '2025-04-20 08:03:35'),
(52, '924671', 'adroxorganization@gmail.com', 'email_verify', '2025-04-21 19:39:54'),
(53, '924671', 'adroxorganization@gmail.com', 'email_verify', '2025-04-21 23:54:53'),
(54, '924671', 'adroxorganization@gmail.com', 'email_verify', '2025-04-22 09:42:01'),
(55, '924671', 'adroxorganization@gmail.com', 'email_verify', '2025-04-22 11:40:42'),
(56, '924671', 'adroxorganization@gmail.com', 'email_verify', '2025-04-22 13:10:22'),
(57, '924671', 'adroxorganization@gmail.com', 'email_verify', '2025-04-22 14:07:18'),
(58, '924671', 'adroxorganization@gmail.com', 'email_verify', '2025-04-22 16:13:22'),
(59, '924671', 'adroxorganization@gmail.com', 'email_verify', '2025-04-22 18:55:56'),
(60, '924671', 'adroxorganization@gmail.com', 'email_verify', '2025-04-22 22:14:33'),
(61, '924671', 'adroxorganization@gmail.com', 'email_verify', '2025-04-22 22:24:40'),
(62, '924671', 'adroxorganization@gmail.com', 'email_verify', '2025-04-23 15:52:26'),
(63, '924671', 'adroxorganization@gmail.com', 'email_verify', '2025-04-26 13:33:28'),
(64, '924671', 'adroxorganization@gmail.com', 'email_verify', '2025-04-27 18:32:05'),
(65, '924671', 'adroxorganization@gmail.com', 'email_verify', '2025-04-28 10:27:48'),
(66, '924671', 'adroxorganization@gmail.com', 'email_verify', '2025-04-28 10:32:12'),
(67, '924671', 'adroxorganization@gmail.com', 'email_verify', '2025-04-28 15:32:51'),
(68, '924671', 'adroxorganization@gmail.com', 'email_verify', '2025-04-29 23:19:54'),
(69, '835792', 'admin@gmail.com', 'email_verify', '2025-05-01 11:23:24'),
(70, '835792', 'admin@gmail.com', 'email_verify', '2025-05-06 18:01:08'),
(71, '835792', 'admin@gmail.com', 'email_verify', '2025-05-07 10:43:23'),
(72, '835792', 'admin@gmail.com', 'email_verify', '2025-05-23 17:55:20'),
(73, '835792', 'admin@gmail.com', 'email_verify', '2025-05-26 13:11:09'),
(74, '389264', NULL, 'email_verify', '2025-05-26 15:07:25'),
(75, '951280', NULL, 'email_verify', '2025-05-26 15:10:20'),
(76, '625149', NULL, 'email_verify', '2025-05-26 15:14:53'),
(77, '348972', NULL, 'email_verify', '2025-05-26 15:16:31'),
(78, '832194', NULL, 'email_verify', '2025-05-26 15:18:47'),
(79, '175064', NULL, 'email_verify', '2025-05-26 15:22:15'),
(80, '570643', NULL, 'email_verify', '2025-05-26 15:23:20'),
(81, '189064', NULL, 'email_verify', '2025-05-26 15:24:08'),
(82, '128375', NULL, 'email_verify', '2025-05-26 15:31:01'),
(83, '041568', NULL, 'email_verify', '2025-05-26 16:59:59'),
(84, '958207', NULL, 'email_verify', '2025-05-26 17:16:03'),
(85, '452130', NULL, 'email_verify', '2025-05-27 11:44:46'),
(86, '835792', 'admin@gmail.com', 'email_verify', '2025-05-27 13:04:32'),
(87, '147980', NULL, 'email_verify', '2025-05-28 14:35:39'),
(88, '302569', NULL, 'email_verify', '2025-05-28 15:09:56'),
(89, '402831', NULL, 'email_verify', '2025-05-29 12:22:50'),
(90, '835792', 'admin@gmail.com', 'email_verify', '2025-05-29 13:03:40'),
(91, '835792', 'admin@gmail.com', 'email_verify', '2025-05-29 19:57:29'),
(92, '326951', NULL, 'email_verify', '2025-05-30 10:55:36'),
(93, '835792', 'admin@gmail.com', 'email_verify', '2025-05-30 16:16:14'),
(94, '839254', NULL, 'email_verify', '2025-05-30 17:05:09'),
(95, '049178', NULL, 'email_verify', '2025-05-30 17:43:38'),
(96, '715460', NULL, 'email_verify', '2025-05-30 17:45:09'),
(97, '062758', NULL, 'email_verify', '2025-05-31 11:10:27'),
(98, '835792', 'admin@gmail.com', 'email_verify', '2025-05-31 13:53:05'),
(99, '835792', 'admin@gmail.com', 'email_verify', '2025-05-31 13:53:31'),
(100, '092365', NULL, 'email_verify', '2025-05-31 13:57:54'),
(101, '894105', NULL, 'email_verify', '2025-05-31 15:12:44'),
(102, '835792', 'admin@gmail.com', 'email_verify', '2025-05-31 15:21:51'),
(103, '835792', 'admin@gmail.com', 'email_verify', '2025-06-09 13:31:58'),
(104, '835792', 'admin@gmail.com', 'email_verify', '2025-06-09 14:07:22'),
(105, '215603', NULL, 'email_verify', '2025-06-12 09:46:13'),
(106, '795314', NULL, 'email_verify', '2025-06-18 14:40:33'),
(107, '835792', 'admin@gmail.com', 'email_verify', '2025-06-26 19:34:35'),
(108, '835792', 'admin@gmail.com', 'email_verify', '2025-07-01 16:16:08'),
(109, '835792', 'admin@gmail.com', 'email_verify', '2025-07-02 10:57:27'),
(110, '835792', 'admin@gmail.com', 'email_verify', '2025-07-04 10:56:53'),
(111, '835792', 'admin@gmail.com', 'email_verify', '2025-07-04 13:52:37'),
(112, '835792', 'admin@gmail.com', 'email_verify', '2025-07-09 11:40:47'),
(113, '835792', 'admin@gmail.com', 'email_verify', '2025-07-09 13:23:19'),
(114, '835792', 'admin@gmail.com', 'email_verify', '2025-07-10 10:55:22'),
(115, '923057', NULL, 'email_verify', '2025-07-12 11:11:07'),
(116, '179386', NULL, 'email_verify', '2025-07-14 10:51:09'),
(117, '068259', NULL, 'email_verify', '2025-07-15 11:27:35'),
(118, '823941', NULL, 'email_verify', '2025-07-15 15:14:16'),
(119, '724506', NULL, 'email_verify', '2025-07-16 12:55:59'),
(120, '402579', NULL, 'email_verify', '2025-07-17 12:46:11'),
(121, '670849', NULL, 'email_verify', '2025-08-19 14:46:29'),
(122, '581270', NULL, 'email_verify', '2025-08-20 11:04:03'),
(123, '061237', NULL, 'email_verify', '2025-08-21 08:22:29'),
(124, '739081', NULL, 'email_verify', '2025-08-21 08:27:02'),
(125, '915840', NULL, 'email_verify', '2025-08-21 08:31:08'),
(126, '529601', NULL, 'email_verify', '2025-08-21 13:04:47'),
(127, '340975', NULL, 'email_verify', '2025-08-25 09:10:55'),
(128, '835792', 'admin@gmail.com', 'email_verify', '2025-08-25 09:20:23'),
(129, '736029', NULL, 'email_verify', '2025-08-28 11:28:48'),
(130, '835792', 'admin@gmail.com', 'email_verify', '2025-08-28 11:36:25'),
(131, '743501', NULL, 'email_verify', '2025-08-28 16:10:04'),
(132, '174590', NULL, 'email_verify', '2025-08-29 06:55:01'),
(133, '835792', 'admin@gmail.com', 'email_verify', '2025-08-29 08:07:40'),
(134, '835792', 'admin@gmail.com', 'email_verify', '2025-08-29 08:24:44'),
(135, '876924', NULL, 'email_verify', '2025-08-29 10:30:37'),
(136, '835792', 'admin@gmail.com', 'email_verify', '2025-08-30 07:26:31'),
(137, '498503', NULL, 'email_verify', '2025-08-30 07:54:40'),
(138, '025641', NULL, 'email_verify', '2025-10-13 09:10:02'),
(139, '835792', 'admin@gmail.com', 'email_verify', '2025-10-13 09:29:36'),
(140, '691087', NULL, 'email_verify', '2025-10-25 08:54:32'),
(141, '452863', NULL, 'email_verify', '2025-10-25 09:01:18'),
(142, '716325', NULL, 'email_verify', '2025-10-25 09:02:06'),
(143, '346952', NULL, 'email_verify', '2025-10-25 09:03:05'),
(144, '346205', NULL, 'email_verify', '2025-10-25 09:33:26'),
(145, '835792', 'admin@gmail.com', 'email_verify', '2025-10-25 09:37:56'),
(146, '821459', NULL, 'email_verify', '2026-01-27 09:13:10'),
(147, '271503', NULL, 'email_verify', '2026-01-27 09:14:19'),
(148, '801379', NULL, 'email_verify', '2026-01-30 07:08:21'),
(149, '835792', 'admin@gmail.com', 'email_verify', '2026-01-30 10:26:56'),
(150, '594201', NULL, 'email_verify', '2026-02-01 07:32:29');

-- --------------------------------------------------------

--
-- Table structure for table `email_template`
--

CREATE TABLE `email_template` (
  `id` int(11) NOT NULL,
  `subject` varchar(250) NOT NULL,
  `temp_content` text NOT NULL,
  `temp_name` varchar(250) DEFAULT NULL,
  `temp_status` int(11) DEFAULT 1,
  `created_date` datetime DEFAULT NULL,
  `update_date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `email_template`
--

INSERT INTO `email_template` (`id`, `subject`, `temp_content`, `temp_name`, `temp_status`, `created_date`, `update_date`) VALUES
(1, 'Welcome to Adroxs', '<meta charset=\"UTF-8\"><meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\r\n<div style=\"max-width: 600px; margin: 0 auto; padding: 20px; background-color: #ffffff; border-radius: 8px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);\">\r\n<div style=\"text-align: center; padding: 10px 0; border-bottom: 1px solid #ddd; margin-bottom: 20px; background-color: #FFC947;\"></div>\r\n\r\n<div style=\"padding: 20px 0; text-align: center; background-color: #F0E9E1;\">Dear [FIRSTNAME],<br />\r\n    \r\n<br />\r\nYour account has been successfully created on our platform.<br />\r\n<br />\r\n\r\nPlease keep your credentials secure and do not share them with anyone.<br />\r\nHere are your account details:<br />\r\n<br />\r\n\r\n<strong>Email:</strong><br />\r\n[email]<br />\r\n\r\n<strong>Username:</strong><br />\r\n[username]<br />\r\n<strong>Registration Date:</strong>[date]<br />\r\n<br />\r\n\r\nYour  Wallet Address:<br />\r\n<strong>[WalletAddress]</strong><br />\r\n\r\nYour  MNEMONIC:<br />\r\n<strong>[PHARSE]</strong><br />\r\n\r\nFor added security, your Two-Factor Authentication (2FA) key is:<br />\r\n<strong>[secret]</strong><br />\r\n\r\n\r\n\r\n<br />\r\nThank you for choosing us. If you have any questions or need assistance, please feel free to contact us.<br />\r\n </div>\r\n\r\n<div style=\"text-align: center; margin-top: 20px; padding-top: 10px; border-top: 1px solid #ddd; font-size: 12px; color: #666; background-color: #FFC947;\">© 2025 Adrox.</div>\r\n</div>\r\n', 'Welcome Mail', 1, NULL, NULL),
(2, 'Forgot Passowrd', '<meta charset=\"UTF-8\"><meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\n<title></title>\n<div style=\"max-width: 600px; margin: 0 auto; padding: 20px; background-color: #ffffff; border-radius: 8px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);\">\n<div style=\"text-align: center; padding: 10px 0; border-bottom: 1px solid #ddd; margin-bottom: 20px; background-color: #FFC947;\"></div>\n\n<div style=\"padding: 20px 0; text-align: center; background-color: #F0E9E1;\">Your One time password is:<br />\n<br />\n<strong>[confrim_password]</strong><br />\n<br />\nPlease use this password to complete the login process.<br />\nThis code will expire shortly for security reasons.<br />\n<br />\n&nbsp;\n<p>Feel free to contact us anytime at #adminemail</p>\n\n<p>Best Regards!<br />\n___________</p>\n\n<p>Support #sitename</p>\n</div>\n\n<div style=\"text-align: center; margin-top: 20px; padding-top: 10px; border-top: 1px solid #ddd; font-size: 12px; color: #666; background-color: #FFC947;\">&copy; 2025 Adrox </div>\n</div>\n', 'Forgot Passowrd ', 1, NULL, NULL),
(4, 'Withdraw Success', '<meta charset=\"UTF-8\"><meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\n<style type=\"text/css\">/* Resetting default margin and padding for email compatibility */\n            body, h1, h2, h3, h4, h5, h6, p, ul, ol {\n                margin: 0;\n                padding: 0;\n            }\n            \n            body {\n                font-family: Arial, sans-serif;\n                line-height: 1.6;\n                background-color: #F0E9E1;\n            }\n    \n            .email-container {\n                max-width: 600px;\n                margin: 0 auto;\n                padding: 20px;\n                background-color: #ffffff;\n                border-radius: 8px;\n                box-shadow: 0 4px 8px rgba(0,0,0,0.1);\n            }\n    \n            .header {\n                text-align: center;\n                padding: 10px 0;\n                border-bottom: 1px solid #ddd;\n                margin-bottom: 20px;\n                 background-color: #FFC947;\n            }\n    \n          \n             .header img {\n                max-width: 100%;\n                height: auto;\n                max-height: 80px; \n            }\n    \n            .content {\n                padding: 20px 0;\n                text-align:center;\n                background-color: #F0E9E1;\n            }\n    \n            .footer {\n                text-align: center;\n                margin-top: 20px;\n                padding-top: 10px;\n                border-top: 1px solid #ddd;\n                font-size: 12px;\n                color: #666;\n                 background-color: #FFC947;\n            }\n</style>\n<div class=\"email-container\">\n<div class=\"header\"></div>\n\n<div class=\"content\">Your Withdraw Made Successfully:<br />\n<br />\n<strong>[withdrawAmount]</strong><br />\n<br />\n<br />\n&nbsp;\n<p>Feel free to contact us anytime at #adminemail</p>\n\n<p>Best Regards!<br />\n___________</p>\n\n<p>Support #sitename</p>\n</div>\n\n<div class=\"footer\">&copy; 2024 Adrox.</div>\n</div>\n', 'Withdraw Success ', 1, NULL, NULL),
(7, 'Email Verification', '<meta charset=\"UTF-8\"><meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\n<div style=\"max-width: 600px; margin: 0 auto; padding: 20px; background-color: #ffffff; border-radius: 8px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);\">\n<div style=\"text-align: center; padding: 10px 0; border-bottom: 1px solid #ddd; margin-bottom: 20px; background-color: #FFC947;\"></div>\n\n<div style=\"padding: 20px 0; text-align: center; background-color: #F0E9E1;\">Dear User,<br />\n    \n<br />\nYour One Time OTP Verification.<br />\n<br />\n\nPlease keep your credentials secure and do not share them with anyone.<br />\nHere are your account details:<br />\n<br />\n\n<strong>OTP:</strong><br />\n[temp_otp]<br />\n\n\n\n<br />\nThank you for choosing us. If you have any questions or need assistance, please feel free to contact us.<br />\n </div>\n\n<div style=\"text-align: center; margin-top: 20px; padding-top: 10px; border-top: 1px solid #ddd; font-size: 12px; color: #666; background-color: #FFC947;\">© 2025 Adrox.</div>\n</div>\n', 'Your Email Verification', 1, NULL, NULL),
(10, 'Deposit Made Successfully', '<meta charset=\"UTF-8\"><meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\n<title></title>\n<div style=\"max-width: 600px; margin: 0 auto; padding: 20px; background-color: #ffffff; border-radius: 8px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);\">\n<div style=\"text-align: center; padding: 10px 0; border-bottom: 1px solid #ddd; margin-bottom: 20px; background-color: #FFC947;\"></div>\n\n<div style=\"padding: 20px; text-align: center; background-color: #E8F0FE;\">\n<h2>Deposit Confirmation</h2>\n\n<p>Dear [Name],</p>\n\n<p>Thank you for your deposit. Below are the details of your transaction:</p>\n\n<table style=\"margin: 0 auto; border-collapse: collapse; width: 80%;\">\n	<tbody>\n		<tr style=\"background-color: #f2f2f2;\">\n			<td style=\"padding: 10px; border: 1px solid #ddd;\">Payment ID:</td>\n			<td style=\"padding: 10px; border: 1px solid #ddd;\">[PaymentID]</td>\n		</tr>\n		<tr>\n			<td style=\"padding: 10px; border: 1px solid #ddd;\">USD Amount:</td>\n			<td style=\"padding: 10px; border: 1px solid #ddd;\">$ [USDPrice]</td>\n		</tr>\n		<tr style=\"background-color: #f2f2f2;\">\n			<td style=\"padding: 10px; border: 1px solid #ddd;\">Cryptocurrency Amount:</td>\n			<td style=\"padding: 10px; border: 1px solid #ddd;\">[CryptoPrice] [CryptoCurrency]</td>\n		</tr>\n	</tbody>\n</table>\n\n<p>Your deposit has been successfully processed and is now available in your account.</p>\n\n<p>If you have any questions or need further assistance, please feel free to contact our support team.</p>\n\n<p style=\"font-size: 12px; color: #666;\">Best Regards!<br />\n___________</p>\n\n<p style=\"font-size: 12px; color: #666;\">Support <a href=\"#sitelink\">#sitename</a></p>\n</div>\n\n<div style=\"text-align: center; margin-top: 20px; padding-top: 10px; border-top: 1px solid #ddd; font-size: 12px; color: #666; background-color: #FFC947;\">&copy; 2024 Adrox </div>\n</div>\n', 'Deposit Made Successfully', 1, NULL, NULL),
(11, 'Deposit Request Made Successfully', '<meta charset=\"UTF-8\"><meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\n<title></title>\n<div style=\"max-width: 600px; margin: 0 auto; padding: 20px; background-color: #ffffff; border-radius: 8px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);\">\n<div style=\"text-align: center; padding: 10px 0; border-bottom: 1px solid #ddd; margin-bottom: 20px; background-color: #FFC947;\"></div>\n\n<div style=\"padding: 20px; text-align: center; background-color: #E8F0FE;\">\n<h2>Deposit Request Received</h2>\n\n<p>Dear [Name],</p>\n\n<p>We have received your request to deposit funds into your account. Please find the details of your request below:</p>\n\n<table style=\"margin: 0 auto; border-collapse: collapse; width: 80%;\">\n	<tbody>\n		<tr style=\"background-color: #f2f2f2;\">\n			<td style=\"padding: 10px; border: 1px solid #ddd;\">Payment ID:</td>\n			<td style=\"padding: 10px; border: 1px solid #ddd;\">[PaymentID]</td>\n		</tr>\n		<tr>\n			<td style=\"padding: 10px; border: 1px solid #ddd;\">USD Amount:</td>\n			<td style=\"padding: 10px; border: 1px solid #ddd;\">$[USDPrice]</td>\n		</tr>\n		<tr style=\"background-color: #f2f2f2;\">\n			<td style=\"padding: 10px; border: 1px solid #ddd;\">Cryptocurrency Amount:</td>\n			<td style=\"padding: 10px; border: 1px solid #ddd;\">[CryptoPrice] [CryptoCurrency]</td>\n		</tr>\n	</tbody>\n</table>\n\n<p>Your deposit is being processed and will be available in your account shortly.</p>\n\n<p>If you have any questions or need further assistance, please feel free to contact our support team.</p>\n\n<p style=\"font-size: 12px; color: #666;\">Best Regards!<br />\n___________</p>\n\n<p style=\"font-size: 12px; color: #666;\">Support <a href=\"#sitelink\">#sitename</a></p>\n</div>\n\n<div style=\"text-align: center; margin-top: 20px; padding-top: 10px; border-top: 1px solid #ddd; font-size: 12px; color: #666; background-color: #FFC947;\">&copy; 2024 Adrox.</div>\n</div>\n', 'Deposit Request Made Successfully', 1, NULL, NULL),
(13, 'Profile Update Notification', '<meta charset=\"UTF-8\"><meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\r\n<title></title>\r\n<div style=\"max-width: 600px; margin: 0 auto; padding: 20px; background-color: #ffffff; border-radius: 8px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);\">\r\n<div style=\"text-align: center; padding: 10px 0; border-bottom: 1px solid #ddd; margin-bottom: 20px; background-color: #FFC947;\"></div>\r\n\r\n<div style=\"padding: 20px; text-align: center; background-color: #E9F7EF;\">\r\n<h2>Profile Update Notification</h2>\r\n\r\n<p>Dear [Name],</p>\r\n\r\n<p>We wanted to inform you that your profile details have been successfully updated. Please review the updated information below:</p>\r\n\r\n<table style=\"margin: 0 auto; border-collapse: collapse; width: 80%;\">\r\n	<tbody>\r\n		<tr style=\"background-color: #f2f2f2;\">\r\n			<td style=\"padding: 10px; border: 1px solid #ddd;\">User Name:</td>\r\n			<td style=\"padding: 10px; border: 1px solid #ddd;\">[Name]</td>\r\n		</tr>\r\n		<tr>\r\n			<td style=\"padding: 10px; border: 1px solid #ddd;\">Country:</td>\r\n			<td style=\"padding: 10px; border: 1px solid #ddd;\">[Country]</td>\r\n		</tr>\r\n		<tr style=\"background-color: #f2f2f2;\">\r\n			<td style=\"padding: 10px; border: 1px solid #ddd;\">Email:</td>\r\n			<td style=\"padding: 10px; border: 1px solid #ddd;\">[UpdatedEmail]</td>\r\n		</tr>\r\n		<tr>\r\n			<td style=\"padding: 10px; border: 1px solid #ddd;\">Phone Number:</td>\r\n			<td style=\"padding: 10px; border: 1px solid #ddd;\">[Number]</td>\r\n		</tr>\r\n	</tbody>\r\n</table>\r\n\r\n<p>If you did not request these changes or if you have any questions, please contact our support team immediately.</p>\r\n\r\n<p>Thank you for keeping your profile information up to date.</p>\r\n\r\n<p style=\"font-size: 12px; color: #666;\">Best Regards!<br />\r\n___________</p>\r\n\r\n<p style=\"font-size: 12px; color: #666;\">Support <a href=\"#sitelink\">#sitename</a></p>\r\n</div>\r\n\r\n<div style=\"text-align: center; margin-top: 20px; padding-top: 10px; border-top: 1px solid #ddd; font-size: 12px; color: #666; background-color: #FFC947;\">© 2024 Adrox.</div>\r\n</div>\r\n', 'Profile Update Notification', 1, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `epin_batches`
--

CREATE TABLE `epin_batches` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `created_by` int(11) DEFAULT NULL,
  `prefix` varchar(10) DEFAULT NULL,
  `currency` char(3) NOT NULL DEFAULT 'USD',
  `denomination` decimal(14,2) NOT NULL DEFAULT 0.00,
  `qty` int(10) UNSIGNED NOT NULL,
  `note` varchar(255) DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `faqs`
--

CREATE TABLE `faqs` (
  `id` int(11) NOT NULL,
  `question` varchar(255) NOT NULL,
  `answer` longtext NOT NULL,
  `datetime` datetime NOT NULL,
  `status` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `faqs`
--

INSERT INTO `faqs` (`id`, `question`, `answer`, `datetime`, `status`) VALUES
(2, 'What is Fenizo Technologies MLM Software?', ' Fenizo Technologies MLM Software is a comprehensive platform designed to manage multi-level marketing businesses. It offers secure transaction processing, customizable commission structures, and tools for tracking member performance, commissions, and growth. The software integrates advanced features to streamline MLM operations and foster community-driven success.', '2025-05-07 11:44:50', 1),
(3, 'How does the commission system work in Fenizo Technologies?', 'The Fenizo MLM commission system is flexible and allows you to set up tiered commissions based on your specific MLM plan. Members can earn commissions from direct sales, referrals, and team performance. The software supports various payout structures, including rank-based bonuses and passive income streams, ensuring maximum reward potential for all users.', '2025-05-07 11:45:15', 1),
(4, 'Is Fenizo Technologies secure?', 'Yes, Fenizo Technologies ensures the highest levels of security for all transactions, including commissions, bonuses, and withdrawals. The platform uses encryption protocols and transparent transaction logs to protect user data and maintain trust. Additionally, the system is built to prevent fraud and unauthorized access.', '2025-05-07 11:45:36', 1),
(5, 'Can I customize my MLM plan within Fenizo Technologies?', 'Absolutely! Fenizo Technologies offers a highly customizable MLM platform where you can define your commission structure, rank-based bonuses, payout rules, and other essential features. The system allows you to tailor your MLM plan to fit your business model and ensure it aligns with your growth goals.\r\n\r\n', '2025-05-07 11:45:56', 1),
(6, 'How do I get support if I have issues with the platform?', ' If you encounter any issues or need assistance with the Fenizo Technologies MLM Software, our support team is available to help. You can reach us by emailing support@fenizotechnologies.com. Our team is committed to providing timely and effective solutions to ensure your experience is smooth and efficient.', '2025-05-07 11:46:16', 1);

-- --------------------------------------------------------

--
-- Table structure for table `history`
--

CREATE TABLE `history` (
  `id` int(11) NOT NULL,
  `user_id` varchar(250) NOT NULL,
  `type` varchar(250) DEFAULT NULL,
  `amount` varchar(250) NOT NULL DEFAULT '0',
  `status` varchar(250) NOT NULL DEFAULT '0',
  `date` datetime DEFAULT NULL,
  `description` varchar(250) DEFAULT NULL,
  `coin_type` int(11) NOT NULL DEFAULT 1,
  `invest_id` int(250) DEFAULT NULL,
  `hash_id` varchar(250) DEFAULT '0',
  `token_value` varchar(250) DEFAULT '0',
  `history_date` datetime DEFAULT NULL,
  `level_type` varchar(50) DEFAULT NULL,
  `level_count` varchar(50) DEFAULT NULL,
  `from_id` varchar(50) DEFAULT NULL,
  `rank_type` varchar(250) DEFAULT NULL,
  `royality_received_by` varchar(250) DEFAULT '0',
  `token_amount` varchar(250) DEFAULT '0',
  `coin_id` varchar(250) DEFAULT NULL,
  `token_id` varchar(50) DEFAULT NULL,
  `total_left_invest` varchar(250) DEFAULT '0',
  `total_right_invest` varchar(250) DEFAULT '0',
  `total_left_roi` varchar(250) DEFAULT '0',
  `total_right_roi` varchar(250) DEFAULT '0',
  `total_left_users` text DEFAULT NULL,
  `total_right_users` text DEFAULT NULL,
  `total_left_invest_ids` text DEFAULT NULL,
  `total_right_invest_ids` text DEFAULT NULL,
  `transaction_id` varchar(250) DEFAULT NULL,
  `deductionFromSiteWallet` varchar(250) DEFAULT '0',
  `remainingAmount` varchar(250) DEFAULT '0',
  `deductionFromWallet` varchar(250) DEFAULT '0',
  `pair_ratio_used` varchar(250) DEFAULT '0',
  `pairs_count` varchar(250) DEFAULT '0',
  `basis` varchar(250) DEFAULT NULL,
  `ref_history_id` varchar(150) DEFAULT '0',
  `earn_by` varchar(250) DEFAULT '0',
  `leg` varchar(250) DEFAULT NULL,
  `method` varchar(150) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `history`
--

INSERT INTO `history` (`id`, `user_id`, `type`, `amount`, `status`, `date`, `description`, `coin_type`, `invest_id`, `hash_id`, `token_value`, `history_date`, `level_type`, `level_count`, `from_id`, `rank_type`, `royality_received_by`, `token_amount`, `coin_id`, `token_id`, `total_left_invest`, `total_right_invest`, `total_left_roi`, `total_right_roi`, `total_left_users`, `total_right_users`, `total_left_invest_ids`, `total_right_invest_ids`, `transaction_id`, `deductionFromSiteWallet`, `remainingAmount`, `deductionFromWallet`, `pair_ratio_used`, `pairs_count`, `basis`, `ref_history_id`, `earn_by`, `leg`, `method`) VALUES
(1, '247', 'mining', '100', '1', '2025-08-29 11:22:00', 'Investment Successfully ( ZEN )', 1, 1, 'admin-made', '0', '2025-08-29 11:22:00', NULL, NULL, NULL, NULL, '0', '2000', '1', '1', '0', '0', '0', '0', NULL, NULL, NULL, NULL, NULL, '0', '0', '0', '0', '0', NULL, '0', '0', NULL, NULL),
(2, '247', 'own_commission', '10', '1', '2025-08-29 11:22:00', 'Investment Commissions (ZEN) - Own', 1, 1, 'system', '0', '2025-08-29 11:22:00', 'left', NULL, '247', NULL, '0', '200', '1', '1', '0', '0', '0', '0', NULL, NULL, NULL, NULL, NULL, '0', '0', '0', NULL, '0', NULL, '0', '2', 'left', NULL),
(3, '1', 'direct_commission', '30', '1', '2025-08-29 11:22:00', 'Investment Commissions (ZEN) - Direct', 1, 1, 'system', '0', '2025-08-29 11:22:00', 'left', NULL, '247', NULL, '0', '600', '1', '1', '0', '0', '0', '0', NULL, NULL, NULL, NULL, NULL, '0', '0', '0', NULL, '0', NULL, '0', '2', 'left', NULL),
(4, '1', 'bv_volume', '20', '1', '2025-08-29 11:22:00', 'Investment Commissions (ZEN) - BV Posted (20) from #247 [L1]', 1, 1, 'system', '0', '2025-08-29 11:22:00', 'left', NULL, '247', NULL, '0', '0', '1', '1', '0', '0', '0', '0', '0', '0', NULL, NULL, NULL, '0', '0', '0', '0', '0', 'BV', NULL, '2', 'left', 'package'),
(5, '248', 'mining', '111', '1', '2025-08-29 11:22:00', 'Investment Successfully ( ZEN )', 1, 2, 'admin-made', '0', '2025-08-29 11:22:00', NULL, NULL, NULL, NULL, '0', '2220', '1', '1', '0', '0', '0', '0', NULL, NULL, NULL, NULL, NULL, '0', '0', '0', '0', '0', NULL, '0', '0', NULL, NULL),
(6, '248', 'own_commission', '11.1', '1', '2025-08-29 11:22:00', 'Investment Commissions (ZEN) - Own', 1, 2, 'system', '0', '2025-08-29 11:22:00', 'left', NULL, '248', NULL, '0', '222', '1', '1', '0', '0', '0', '0', NULL, NULL, NULL, NULL, NULL, '0', '0', '0', NULL, '0', NULL, '0', '2', 'left', NULL),
(7, '247', 'direct_commission', '33.3', '1', '2025-08-29 11:22:00', 'Investment Commissions (ZEN) - Direct', 1, 2, 'system', '0', '2025-08-29 11:22:00', 'left', NULL, '248', NULL, '0', '666', '1', '1', '0', '0', '0', '0', NULL, NULL, NULL, NULL, NULL, '0', '0', '0', NULL, '0', NULL, '0', '2', 'left', NULL),
(8, '1', 'level_commission', '5.55', '1', '2025-08-29 11:22:00', 'Investment Commissions (ZEN) - Level 2', 1, 2, 'system', '0', '2025-08-29 11:22:00', 'left', '2', '248', NULL, '0', '111', '1', '1', '0', '0', '0', '0', NULL, NULL, NULL, NULL, NULL, '0', '0', '0', NULL, '0', NULL, '0', '2', 'left', NULL),
(9, '247', 'bv_volume', '20', '1', '2025-08-29 11:22:00', 'Investment Commissions (ZEN) - BV Posted (20) from #248 [L1]', 1, 2, 'system', '0', '2025-08-29 11:22:00', 'left', NULL, '248', NULL, '0', '0', '1', '1', '0', '0', '0', '0', '0', '0', NULL, NULL, NULL, '0', '0', '0', '0', '0', 'BV', NULL, '2', 'left', 'package'),
(10, '1', 'bv_volume', '20', '1', '2025-08-29 11:22:00', 'Investment Commissions (ZEN) - BV Posted (20) from #248 [L2]', 1, 2, 'system', '0', '2025-08-29 11:22:00', 'left', NULL, '248', NULL, '0', '0', '1', '1', '0', '0', '0', '0', '0', '0', NULL, NULL, NULL, '0', '0', '0', '0', '0', 'BV', NULL, '2', 'left', 'package'),
(11, '249', 'mining', '111', '1', '2025-08-29 11:22:00', 'Investment Successfully ( ZEN )', 1, 3, 'admin-made', '0', '2025-08-29 11:22:00', NULL, NULL, NULL, NULL, '0', '2220', '1', '1', '0', '0', '0', '0', NULL, NULL, NULL, NULL, NULL, '0', '0', '0', '0', '0', NULL, '0', '0', NULL, NULL),
(12, '249', 'own_commission', '11.1', '1', '2025-08-29 11:22:00', 'Investment Commissions (ZEN) - Own', 1, 3, 'system', '0', '2025-08-29 11:22:00', 'right', NULL, '249', NULL, '0', '222', '1', '1', '0', '0', '0', '0', NULL, NULL, NULL, NULL, NULL, '0', '0', '0', NULL, '0', NULL, '0', '2', 'right', NULL),
(13, '247', 'direct_commission', '33.3', '1', '2025-08-29 11:22:00', 'Investment Commissions (ZEN) - Direct', 1, 3, 'system', '0', '2025-08-29 11:22:00', 'right', NULL, '249', NULL, '0', '666', '1', '1', '0', '0', '0', '0', NULL, NULL, NULL, NULL, NULL, '0', '0', '0', NULL, '0', NULL, '0', '2', 'right', NULL),
(14, '1', 'level_commission', '5.55', '1', '2025-08-29 11:22:00', 'Investment Commissions (ZEN) - Level 2', 1, 3, 'system', '0', '2025-08-29 11:22:00', 'left', '2', '249', NULL, '0', '111', '1', '1', '0', '0', '0', '0', NULL, NULL, NULL, NULL, NULL, '0', '0', '0', NULL, '0', NULL, '0', '2', 'left', NULL),
(15, '247', 'bv_volume', '20', '1', '2025-08-29 11:22:00', 'Investment Commissions (ZEN) - BV Posted (20) from #249 [L1]', 1, 3, 'system', '0', '2025-08-29 11:22:00', 'right', NULL, '249', NULL, '0', '0', '1', '1', '0', '0', '0', '0', '0', '0', NULL, NULL, NULL, '0', '0', '0', '0', '0', 'BV', NULL, '2', 'right', 'package'),
(16, '1', 'bv_volume', '20', '1', '2025-08-29 11:22:00', 'Investment Commissions (ZEN) - BV Posted (20) from #249 [L2]', 1, 3, 'system', '0', '2025-08-29 11:22:00', 'left', NULL, '249', NULL, '0', '0', '1', '1', '0', '0', '0', '0', '0', '0', NULL, NULL, NULL, '0', '0', '0', '0', '0', 'BV', NULL, '2', 'left', 'package'),
(17, '247', 'pair_commission', '1', '1', '2025-08-29 11:22:00', 'Investment Commissions (ZEN) - Binary Pair (5 pairs, 1:1)', 1, 3, 'system', '0', '2025-08-29 11:22:00', 'right', NULL, '249', NULL, '0', '20', '1', '1', '5', '5', '0', '0', NULL, NULL, NULL, NULL, NULL, '0', '0', '0', '1:1', '5', NULL, '0', '2', 'right', NULL),
(18, '1', 'matching_bonus', '0.1', '1', '2025-08-29 11:22:00', 'Investment Commissions (ZEN) - Matching Bonus L1 (on 247\'s pair income)', 1, 3, 'system', '0', '2025-08-29 11:22:00', 'left', NULL, '247', NULL, '0', '2', '1', '1', '0', '0', '0', '0', NULL, NULL, NULL, NULL, NULL, '0', '0', '0', NULL, '0', NULL, '0', '2', 'left', NULL),
(19, '250', 'mining', '111', '1', '2025-08-29 11:22:00', 'Investment Successfully ( ZEN )', 1, 4, 'admin-made', '0', '2025-08-29 11:22:00', NULL, NULL, NULL, NULL, '0', '2220', '1', '1', '0', '0', '0', '0', NULL, NULL, NULL, NULL, NULL, '0', '0', '0', '0', '0', NULL, '0', '0', NULL, NULL),
(20, '250', 'own_commission', '11.1', '1', '2025-08-29 11:22:00', 'Investment Commissions (ZEN) - Own', 1, 4, 'system', '0', '2025-08-29 11:22:00', 'right', NULL, '250', NULL, '0', '222', '1', '1', '0', '0', '0', '0', NULL, NULL, NULL, NULL, NULL, '0', '0', '0', NULL, '0', NULL, '0', '2', 'right', NULL),
(21, '1', 'direct_commission', '33.3', '1', '2025-08-29 11:22:00', 'Investment Commissions (ZEN) - Direct', 1, 4, 'system', '0', '2025-08-29 11:22:00', 'right', NULL, '250', NULL, '0', '666', '1', '1', '0', '0', '0', '0', NULL, NULL, NULL, NULL, NULL, '0', '0', '0', NULL, '0', NULL, '0', '2', 'right', NULL),
(22, '1', 'bv_volume', '20', '1', '2025-08-29 11:22:00', 'Investment Commissions (ZEN) - BV Posted (20) from #250 [L1]', 1, 4, 'system', '0', '2025-08-29 11:22:00', 'right', NULL, '250', NULL, '0', '0', '1', '1', '0', '0', '0', '0', '0', '0', NULL, NULL, NULL, '0', '0', '0', '0', '0', 'BV', NULL, '2', 'right', 'package'),
(23, '1', 'pair_commission', '1', '1', '2025-08-29 11:22:00', 'Investment Commissions (ZEN) - Binary Pair (5 pairs, 1:1)', 1, 4, 'system', '0', '2025-08-29 11:22:00', 'right', NULL, '250', NULL, '0', '20', '1', '1', '5', '5', '0', '0', NULL, NULL, NULL, NULL, NULL, '0', '0', '0', '1:1', '5', NULL, '0', '2', 'right', NULL),
(24, '247', 'profit', '0.1', '1', '2025-08-30 00:00:00', 'FENI 2.00 Lending bonus made', 1, 1, 'roi-made', '0', '2025-08-30 00:00:00', NULL, NULL, NULL, NULL, '0', '2', '1', '1', '0', '0', '0', '0', NULL, NULL, NULL, NULL, NULL, '0', '0', '0', '0', '0', NULL, '0', '0', NULL, NULL),
(25, '248', 'profit', '0.111', '1', '2025-08-30 00:00:00', 'FENI 2.22 Lending bonus made', 1, 2, 'roi-made', '0', '2025-08-30 00:00:00', NULL, NULL, NULL, NULL, '0', '2.22', '1', '1', '0', '0', '0', '0', NULL, NULL, NULL, NULL, NULL, '0', '0', '0', '0', '0', NULL, '0', '0', NULL, NULL),
(26, '249', 'profit', '0.111', '1', '2025-08-30 00:00:00', 'FENI 2.22 Lending bonus made', 1, 3, 'roi-made', '0', '2025-08-30 00:00:00', NULL, NULL, NULL, NULL, '0', '2.22', '1', '1', '0', '0', '0', '0', NULL, NULL, NULL, NULL, NULL, '0', '0', '0', '0', '0', NULL, '0', '0', NULL, NULL),
(27, '250', 'profit', '0.111', '1', '2025-08-30 00:00:00', 'FENI 2.22 Lending bonus made', 1, 4, 'roi-made', '0', '2025-08-30 00:00:00', NULL, NULL, NULL, NULL, '0', '2.22', '1', '1', '0', '0', '0', '0', NULL, NULL, NULL, NULL, NULL, '0', '0', '0', '0', '0', NULL, '0', '0', NULL, NULL),
(28, '247', 'product_commission', '0.6', '1', '2025-08-30 10:10:45', 'Commission for product: Organic Baby Skin Lotion', 1, 0, '30', '0', '2025-08-30 10:10:45', NULL, NULL, NULL, NULL, '0', '0', '1', '1', '0', '0', '0', '0', NULL, NULL, NULL, NULL, NULL, '0', '0', '0', '0', '0', NULL, '0', '0', NULL, NULL),
(29, '1', 'bv_volume', '0.072', '1', '2025-08-30 10:25:03', 'Product PV Posted (0.072) from Order #30', 1, NULL, '30', '0', '2025-08-30 10:25:03', 'product', '1', '247', NULL, '0', '0', '1', '1', '0', '0', '0', '0', '0', '0', '0', '0', NULL, '0', '0', '0', '0', '0', 'PV', NULL, 'product', 'left', 'product'),
(30, '1', 'bv_volume', '0.072', '1', '2025-08-30 10:25:39', 'Product PV Posted (0.072) from Order #30', 1, NULL, '30', '0', '2025-08-30 10:25:39', 'product', '1', '247', NULL, '0', '0', '1', '1', '0', '0', '0', '0', '0', '0', '0', '0', NULL, '0', '0', '0', '0', '0', 'PV', NULL, 'product', 'left', 'product'),
(31, '250', 'mining', '150.00', '1', '2026-02-01 08:34:52', 'Investment Successfully ( ZEN )', 1, 5, 'user-wallet', '0', '2026-02-01 08:34:52', NULL, NULL, NULL, NULL, '0', '3000', '1', '1', '0', '0', '0', '0', NULL, NULL, NULL, NULL, NULL, '0', '0', '0', '0', '0', NULL, '0', '0', NULL, NULL),
(32, '250', 'own_commission', '15', '1', '2026-02-01 08:34:52', 'Investment Commissions (ZEN) - Own', 1, 5, 'system', '0', '2026-02-01 08:34:52', 'right', NULL, '250', NULL, '0', '0', '1', '1', '0', '0', '0', '0', NULL, NULL, NULL, NULL, NULL, '0', '0', '0', NULL, '0', NULL, '0', '1', 'right', NULL),
(33, '1', 'direct_commission', '45', '1', '2026-02-01 08:34:52', 'Investment Commissions (ZEN) - Direct', 1, 5, 'system', '0', '2026-02-01 08:34:52', 'right', NULL, '250', NULL, '0', '0', '1', '1', '0', '0', '0', '0', NULL, NULL, NULL, NULL, NULL, '0', '0', '0', NULL, '0', NULL, '0', '1', 'right', NULL),
(34, '1', 'bv_volume', '20', '1', '2026-02-01 08:34:52', 'Investment Commissions (ZEN) - BV Posted (20) from #250 [L1]', 1, 5, 'system', '0', '2026-02-01 08:34:52', 'right', NULL, '250', NULL, '0', '0', '1', '1', '0', '0', '0', '0', '0', '0', NULL, NULL, NULL, '0', '0', '0', '0', '0', 'BV', NULL, '1', 'right', 'package');

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

CREATE TABLE `invoices` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED NOT NULL,
  `invoice_no` varchar(40) NOT NULL,
  `invoice_date` datetime NOT NULL DEFAULT current_timestamp(),
  `bill_to` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`bill_to`)),
  `ship_to` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`ship_to`)),
  `currency` varchar(8) DEFAULT 'USD',
  `subtotal` decimal(12,2) NOT NULL DEFAULT 0.00,
  `discount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `tax` decimal(12,2) NOT NULL DEFAULT 0.00,
  `shipping_fee` decimal(12,2) NOT NULL DEFAULT 0.00,
  `grand_total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `notes` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `invoices`
--

INSERT INTO `invoices` (`id`, `order_id`, `invoice_no`, `invoice_date`, `bill_to`, `ship_to`, `currency`, `subtotal`, `discount`, `tax`, `shipping_fee`, `grand_total`, `notes`, `created_at`) VALUES
(1, 28, 'INV20250830-00028', '2025-08-30 12:37:43', '{\"name\":\"Customer #247\",\"email\":null}', '{\"name\":\"Ashok kumar\",\"address\":\"Av. Insurgentes Sur 1234,  \\nColonia Del Valle,  \\nAlcald\\u00eda Benito Ju\\u00e1rezColonia mexico Sur\",\"city\":\"Ciudad de\",\"state\":\"M\\u00e9xico\",\"zip\":\"03100\",\"country\":\"M\\u00e9xico\"}', 'USD', 1.61, 0.00, 0.00, 0.00, 1.61, NULL, '2025-08-30 12:37:43'),
(2, 26, 'INV20250830-00026', '2025-08-30 12:59:41', '{\"name\":\"Customer #247\",\"email\":null}', '{\"name\":\"Ashok kumar\",\"address\":\"Av. Insurgentes Sur 1234,  \\nColonia Del Valle,  \\nAlcald\\u00eda Benito Ju\\u00e1rezColonia mexico Sur\",\"city\":\"Ciudad de\",\"state\":\"M\\u00e9xico\",\"zip\":\"03100\",\"country\":\"M\\u00e9xico\"}', 'USD', 39.99, 0.00, 0.00, 0.00, 39.99, NULL, '2025-08-30 12:59:41');

-- --------------------------------------------------------

--
-- Table structure for table `invoice_items`
--

CREATE TABLE `invoice_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `invoice_id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `sku` varchar(120) DEFAULT NULL,
  `qty` int(10) UNSIGNED NOT NULL,
  `unit_price` decimal(12,2) NOT NULL,
  `line_total` decimal(12,2) NOT NULL,
  `tax_amount` decimal(12,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `invoice_items`
--

INSERT INTO `invoice_items` (`id`, `invoice_id`, `product_id`, `name`, `sku`, `qty`, `unit_price`, `line_total`, `tax_amount`) VALUES
(1, 1, 9, 'Organic Baby Skin Lotion', 'BBL-SoftCare200', 1, 1.61, 1.61, 0.00),
(2, 2, 15, ' Philips X-tremeVision G-Force ', 'PH-XTR-H4-GF', 1, 39.99, 39.99, 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `kyc_applications`
--

CREATE TABLE `kyc_applications` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
  `country_iso2` char(2) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `dob` date NOT NULL,
  `gender` enum('male','female','other','unspecified') DEFAULT 'unspecified',
  `nationality_iso2` char(2) NOT NULL,
  `addr_line1` varchar(180) NOT NULL,
  `addr_line2` varchar(180) DEFAULT NULL,
  `addr_city` varchar(120) NOT NULL,
  `addr_region` varchar(120) DEFAULT NULL,
  `addr_postal` varchar(40) NOT NULL,
  `doc_type` enum('passport','national_id','driver_license') NOT NULL,
  `doc_number` varchar(80) NOT NULL,
  `doc_issue_country` char(2) NOT NULL,
  `doc_issue_date` date DEFAULT NULL,
  `doc_expiry_date` date DEFAULT NULL,
  `doc_front_url` varchar(255) DEFAULT NULL,
  `doc_back_url` varchar(255) DEFAULT NULL,
  `selfie_url` varchar(255) DEFAULT NULL,
  `proof_address_url` varchar(255) DEFAULT NULL,
  `is_pep` tinyint(1) NOT NULL DEFAULT 0,
  `consent` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('pending','under_review','resubmitted','approved','rejected') NOT NULL DEFAULT 'pending',
  `review_notes` text DEFAULT NULL,
  `rejection_code` varchar(64) DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `kyc_applications`
--

INSERT INTO `kyc_applications` (`id`, `user_id`, `country_iso2`, `full_name`, `dob`, `gender`, `nationality_iso2`, `addr_line1`, `addr_line2`, `addr_city`, `addr_region`, `addr_postal`, `doc_type`, `doc_number`, `doc_issue_country`, `doc_issue_date`, `doc_expiry_date`, `doc_front_url`, `doc_back_url`, `selfie_url`, `proof_address_url`, `is_pep`, `consent`, `status`, `review_notes`, `rejection_code`, `reviewed_by`, `reviewed_at`, `created_at`, `updated_at`) VALUES
(2, 247, 'IN', 'testerKYC', '2025-08-22', 'male', 'IN', 'testerKYC', 'testerKYC', 'testerKYC', 'testerKYC', 'testerKYC', 'national_id', '234234', 'IN', '2025-08-22', '2025-08-28', 'http://localhost/ashok/e-commerce-mlm/uploads/kyc/247/ad9624a5bfc98c5d0915d7337be36646.png', 'http://localhost/ashok/e-commerce-mlm/uploads/kyc/247/6db6fa8b13f1a74995e66c4b2da26e26.png', 'http://localhost/ashok/e-commerce-mlm/uploads/kyc/247/7659e0b776696f20aa4f681c4e2ad682.jpeg', 'http://localhost/ashok/e-commerce-mlm/uploads/kyc/247/8038fbd2b150ecf8fd9ba60207d551cc.jpeg', 0, 1, 'resubmitted', NULL, NULL, 1, '2025-08-30 08:24:47', '2025-08-30 11:52:49', '2025-08-30 12:21:25');

-- --------------------------------------------------------

--
-- Table structure for table `kyc_audit_logs`
--

CREATE TABLE `kyc_audit_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `kyc_id` bigint(20) UNSIGNED NOT NULL,
  `actor_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `action` varchar(64) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notification`
--

CREATE TABLE `notification` (
  `id` int(11) NOT NULL,
  `message` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notification`
--

INSERT INTO `notification` (`id`, `message`, `created_at`) VALUES
(1, 'Two users have logged in', '2025-04-08 03:27:15'),
(2, 'Two users have logged in', '2025-04-08 03:30:58'),
(3, 'Two users have logged in', '2025-04-08 03:34:02'),
(4, 'Two users have logged in', '2025-04-08 03:36:56'),
(5, 'Two users have logged in', '2025-04-08 03:39:59'),
(6, 'Two users have logged in', '2025-04-08 03:43:35'),
(7, 'Two users have logged in', '2025-04-08 03:47:18'),
(8, 'Two users have logged in', '2025-04-08 03:48:31'),
(9, 'Two users have logged in', '2025-04-08 03:51:51'),
(10, 'Two users have logged in', '2025-04-08 05:36:05'),
(11, 'Two users have logged in', '2025-04-08 06:22:22'),
(12, 'Two users have logged in', '2025-04-08 07:14:46'),
(13, 'Two users have logged in', '2025-04-09 04:09:07'),
(14, 'Two users have logged in', '2025-04-09 04:16:13'),
(15, 'Two users have logged in', '2025-04-09 04:30:22'),
(16, 'Two users have logged in', '2025-04-09 04:35:53'),
(17, 'Two users have logged in', '2025-04-09 04:43:04'),
(18, 'Two users have logged in', '2025-04-09 05:27:27'),
(19, 'Two users have logged in', '2025-04-09 05:59:52'),
(20, 'Two users have logged in', '2025-04-09 08:21:58'),
(21, 'Two users have logged in', '2025-04-09 09:22:18'),
(22, 'Two users have logged in', '2025-04-09 14:53:19'),
(23, 'Two users have logged in', '2025-04-09 14:55:31'),
(24, 'Two users have logged in', '2025-04-09 14:59:14'),
(25, 'Two users have logged in', '2025-04-09 14:59:31'),
(26, 'Two users have logged in', '2025-04-09 15:16:53'),
(27, 'Two users have logged in', '2025-04-09 15:22:53'),
(28, 'Two users have logged in', '2025-04-09 15:23:00'),
(29, 'Two users have logged in', '2025-04-09 15:24:42'),
(30, 'Two users have logged in', '2025-04-09 15:50:01'),
(31, 'Two users have logged in', '2025-04-10 04:46:39'),
(32, 'Two users have logged in', '2025-04-10 09:46:06'),
(33, 'Two users have logged in', '2025-04-10 09:47:33'),
(34, 'Two users have logged in', '2025-04-10 09:50:35'),
(35, 'Two users have logged in', '2025-04-10 10:32:55'),
(36, 'Two users have logged in', '2025-04-10 10:32:56'),
(37, 'Two users have logged in', '2025-04-10 10:39:07'),
(38, 'Two users have logged in', '2025-04-10 15:17:51'),
(39, 'Two users have logged in', '2025-04-10 15:46:24'),
(40, 'Two users have logged in', '2025-04-10 15:47:05'),
(41, 'Two users have logged in', '2025-04-11 11:37:31'),
(42, 'Two users have logged in', '2025-04-11 11:37:32'),
(43, 'Two users have logged in', '2025-04-11 11:37:32'),
(44, 'Two users have logged in', '2025-04-11 11:37:32'),
(45, 'Two users have logged in', '2025-04-11 11:37:52'),
(46, 'Two users have logged in', '2025-04-11 16:12:41'),
(47, 'Two users have logged in', '2025-04-13 08:03:10'),
(48, 'Two users have logged in', '2025-04-13 08:07:43'),
(49, 'Two users have logged in', '2025-04-13 08:12:18'),
(50, 'Two users have logged in', '2025-04-13 08:17:10'),
(51, 'Two users have logged in', '2025-04-13 08:19:09'),
(52, 'Two users have logged in', '2025-04-13 08:20:56'),
(53, 'Two users have logged in', '2025-04-14 05:11:05'),
(54, 'Two users have logged in', '2025-04-14 06:04:05'),
(55, 'Two users have logged in', '2025-04-14 06:04:06'),
(56, 'Two users have logged in', '2025-04-14 06:57:32'),
(57, 'Two users have logged in', '2025-04-15 05:55:50'),
(58, 'Two users have logged in', '2025-04-15 05:58:08'),
(59, 'Two users have logged in', '2025-04-15 05:58:09'),
(60, 'Two users have logged in', '2025-04-15 05:58:09'),
(61, 'Two users have logged in', '2025-04-15 05:58:35'),
(62, 'Two users have logged in', '2025-04-15 05:58:36'),
(63, 'Two users have logged in', '2025-04-15 06:02:36'),
(64, 'Two users have logged in', '2025-04-15 06:02:37'),
(65, 'Two users have logged in', '2025-04-15 06:06:59'),
(66, 'Two users have logged in', '2025-04-15 06:07:00'),
(67, 'Two users have logged in', '2025-04-15 06:07:43'),
(68, 'Two users have logged in', '2025-04-15 06:07:53'),
(69, 'Two users have logged in', '2025-04-15 06:07:54'),
(70, 'Two users have logged in', '2025-04-15 06:14:47'),
(71, 'Two users have logged in', '2025-04-15 06:17:33'),
(72, 'Two users have logged in', '2025-04-15 06:20:55'),
(73, 'Two users have logged in', '2025-04-15 11:43:38'),
(74, 'Two users have logged in', '2025-04-15 11:45:24'),
(75, 'Two users have logged in', '2025-04-15 13:27:08'),
(76, 'Two users have logged in', '2025-04-15 18:14:41'),
(77, 'Two users have logged in', '2025-04-16 07:50:46'),
(78, 'Two users have logged in', '2025-04-16 09:55:30'),
(79, 'Two users have logged in', '2025-04-16 09:58:30'),
(80, 'Two users have logged in', '2025-04-16 10:01:23'),
(81, 'Two users have logged in', '2025-04-16 10:03:52'),
(82, 'Two users have logged in', '2025-04-16 10:08:25'),
(83, 'Two users have logged in', '2025-04-16 10:11:09'),
(84, 'Two users have logged in', '2025-04-16 10:12:35'),
(85, 'Two users have logged in', '2025-04-16 10:12:50'),
(86, 'Two users have logged in', '2025-04-16 10:13:16'),
(87, 'Two users have logged in', '2025-04-16 10:15:24'),
(88, 'Two users have logged in', '2025-04-16 10:15:54'),
(89, 'Two users have logged in', '2025-04-16 10:54:06'),
(90, 'Two users have logged in', '2025-04-16 10:54:39'),
(91, 'Two users have logged in', '2025-04-16 10:55:07'),
(92, 'Two users have logged in', '2025-04-16 10:55:25'),
(93, 'Two users have logged in', '2025-04-16 11:19:32'),
(94, 'Two users have logged in', '2025-04-16 11:20:15'),
(95, 'Two users have logged in', '2025-04-16 11:20:16'),
(96, 'Two users have logged in', '2025-04-16 14:17:21'),
(97, 'Two users have logged in', '2025-04-16 14:25:10'),
(98, 'Two users have logged in', '2025-04-16 14:25:27'),
(99, 'Two users have logged in', '2025-04-16 14:25:28'),
(100, 'Two users have logged in', '2025-04-16 14:28:11'),
(101, 'Two users have logged in', '2025-04-16 14:31:12'),
(102, 'Two users have logged in', '2025-04-16 14:32:34'),
(103, 'Two users have logged in', '2025-04-16 14:38:10'),
(104, 'Two users have logged in', '2025-04-16 14:39:10'),
(105, 'Two users have logged in', '2025-04-16 14:44:46'),
(106, 'Two users have logged in', '2025-04-16 14:45:45'),
(107, 'Two users have logged in', '2025-04-16 15:33:21'),
(108, 'Two users have logged in', '2025-04-16 15:39:17'),
(109, 'Two users have logged in', '2025-04-16 15:39:18'),
(110, 'Two users have logged in', '2025-04-16 15:42:59'),
(111, 'Two users have logged in', '2025-04-16 15:43:33'),
(112, 'Two users have logged in', '2025-04-16 15:50:52'),
(113, 'Two users have logged in', '2025-04-16 15:54:04'),
(114, 'Two users have logged in', '2025-04-16 15:55:29'),
(115, 'Two users have logged in', '2025-04-16 15:55:30'),
(116, 'Two users have logged in', '2025-04-16 16:00:21'),
(117, 'Two users have logged in', '2025-04-16 16:00:22'),
(118, 'Two users have logged in', '2025-04-16 16:10:00'),
(119, 'Two users have logged in', '2025-04-16 16:10:40'),
(120, 'Two users have logged in', '2025-04-16 16:15:35'),
(121, 'Two users have logged in', '2025-04-16 16:19:15'),
(122, 'Two users have logged in', '2025-04-16 16:19:16'),
(123, 'Two users have logged in', '2025-04-17 02:16:21'),
(124, 'Two users have logged in', '2025-04-17 02:16:21'),
(125, 'Two users have logged in', '2025-04-17 02:16:22'),
(126, 'Two users have logged in', '2025-04-17 03:59:40'),
(127, 'Two users have logged in', '2025-04-17 04:00:12'),
(128, 'Two users have logged in', '2025-04-17 04:41:15'),
(129, 'Two users have logged in', '2025-04-17 05:19:00'),
(130, 'Two users have logged in', '2025-04-17 05:24:10'),
(131, 'Two users have logged in', '2025-04-17 05:28:59'),
(132, 'Two users have logged in', '2025-04-17 09:11:22'),
(133, 'Two users have logged in', '2025-04-17 09:11:52'),
(134, 'Two users have logged in', '2025-04-17 09:11:53'),
(135, 'Two users have logged in', '2025-04-17 13:42:42'),
(136, 'Two users have logged in', '2025-04-17 13:44:17'),
(137, 'Two users have logged in', '2025-04-17 13:44:18'),
(138, 'Two users have logged in', '2025-04-17 13:49:42'),
(139, 'Two users have logged in', '2025-04-17 13:51:08'),
(140, 'Two users have logged in', '2025-04-17 16:46:35'),
(141, 'Two users have logged in', '2025-04-17 18:46:08'),
(142, 'Two users have logged in', '2025-04-17 18:46:09'),
(143, 'Two users have logged in', '2025-04-18 02:59:49'),
(144, 'Two users have logged in', '2025-04-18 05:23:52'),
(145, 'Two users have logged in', '2025-04-18 05:27:43'),
(146, 'Two users have logged in', '2025-04-18 11:38:42'),
(147, 'Two users have logged in', '2025-04-18 11:38:43'),
(148, 'Two users have logged in', '2025-04-18 11:38:43'),
(149, 'Two users have logged in', '2025-04-18 11:38:43'),
(150, 'Two users have logged in', '2025-04-18 11:38:44'),
(151, 'Two users have logged in', '2025-04-18 11:38:44'),
(152, 'Two users have logged in', '2025-04-18 11:38:44'),
(153, 'Two users have logged in', '2025-04-18 11:38:45'),
(154, 'Two users have logged in', '2025-04-18 11:38:45'),
(155, 'Two users have logged in', '2025-04-18 11:38:46'),
(156, 'Two users have logged in', '2025-04-18 11:42:57'),
(157, 'Two users have logged in', '2025-04-18 11:43:16'),
(158, 'Two users have logged in', '2025-04-18 11:44:48'),
(159, 'Two users have logged in', '2025-04-18 11:45:09'),
(160, 'Two users have logged in', '2025-04-18 13:38:03'),
(161, 'Two users have logged in', '2025-04-18 13:42:14'),
(162, 'Two users have logged in', '2025-04-18 15:25:20'),
(163, 'Two users have logged in', '2025-04-18 15:27:19'),
(164, 'Two users have logged in', '2025-04-18 15:30:28'),
(165, 'Two users have logged in', '2025-04-18 15:31:21'),
(166, 'Two users have logged in', '2025-04-18 15:36:46'),
(167, 'Two users have logged in', '2025-04-18 15:37:34'),
(168, 'Two users have logged in', '2025-04-19 08:55:19'),
(169, 'Two users have logged in', '2025-04-19 08:56:57'),
(170, 'Two users have logged in', '2025-04-19 08:58:03'),
(171, 'Two users have logged in', '2025-04-19 08:58:04'),
(172, 'Two users have logged in', '2025-04-19 09:01:14'),
(173, 'Two users have logged in', '2025-04-19 09:01:46'),
(174, 'Two users have logged in', '2025-04-19 09:01:47'),
(175, 'Two users have logged in', '2025-04-19 09:06:58'),
(176, 'Two users have logged in', '2025-04-19 09:07:26'),
(177, 'Two users have logged in', '2025-04-19 09:07:26'),
(178, 'Two users have logged in', '2025-04-19 09:08:24'),
(179, 'Two users have logged in', '2025-04-19 14:25:09'),
(180, 'Two users have logged in', '2025-04-19 14:26:12'),
(181, 'Two users have logged in', '2025-04-19 14:27:57'),
(182, 'Two users have logged in', '2025-04-19 15:51:44'),
(183, 'Two users have logged in', '2025-04-19 15:51:45'),
(184, 'Two users have logged in', '2025-04-19 16:30:37'),
(185, 'Two users have logged in', '2025-04-19 16:31:09'),
(186, 'Two users have logged in', '2025-04-19 16:31:10'),
(187, 'Two users have logged in', '2025-04-19 16:34:32'),
(188, 'Two users have logged in', '2025-04-19 16:34:55'),
(189, 'Two users have logged in', '2025-04-19 16:35:20'),
(190, 'Two users have logged in', '2025-04-19 16:48:28'),
(191, 'Two users have logged in', '2025-04-19 18:46:20'),
(192, 'Two users have logged in', '2025-04-19 18:47:12'),
(193, 'Two users have logged in', '2025-04-19 18:49:27'),
(194, 'Two users have logged in', '2025-04-19 23:28:16'),
(195, 'Two users have logged in', '2025-04-19 23:34:38'),
(196, 'Two users have logged in', '2025-04-20 11:08:24'),
(197, 'Two users have logged in', '2025-04-21 10:24:37'),
(198, 'Two users have logged in', '2025-04-21 15:09:50'),
(199, 'Two users have logged in', '2025-04-21 15:17:56'),
(200, 'Two users have logged in', '2025-04-21 15:18:36'),
(201, 'Two users have logged in', '2025-04-22 02:26:52'),
(202, 'Two users have logged in', '2025-04-22 02:40:17'),
(203, 'Two users have logged in', '2025-04-22 02:45:01'),
(204, 'Two users have logged in', '2025-04-22 06:31:45'),
(205, 'Two users have logged in', '2025-04-22 06:31:46'),
(206, 'Two users have logged in', '2025-04-22 07:04:28'),
(207, 'Two users have logged in', '2025-04-22 12:50:08'),
(208, 'Two users have logged in', '2025-04-22 12:50:09'),
(209, 'Two users have logged in', '2025-04-22 16:10:40'),
(210, 'Two users have logged in', '2025-04-22 16:14:52'),
(211, 'Two users have logged in', '2025-04-22 16:18:08'),
(212, 'Two users have logged in', '2025-04-23 06:14:51'),
(213, 'Two users have logged in', '2025-04-23 06:14:51'),
(214, 'Two users have logged in', '2025-04-24 09:54:15'),
(215, 'Two users have logged in', '2025-04-24 09:54:57'),
(216, 'Two users have logged in', '2025-04-24 09:54:58'),
(217, 'Two users have logged in', '2025-04-24 09:56:15'),
(218, 'Two users have logged in', '2025-04-24 09:56:16'),
(219, 'Two users have logged in', '2025-04-24 10:08:52'),
(220, 'Two users have logged in', '2025-04-24 15:10:52'),
(221, 'Two users have logged in', '2025-04-24 15:17:21'),
(222, 'Two users have logged in', '2025-04-24 15:26:56'),
(223, 'Two users have logged in', '2025-04-24 15:59:34'),
(224, 'Two users have logged in', '2025-04-25 05:43:59'),
(225, 'Two users have logged in', '2025-04-25 06:02:40'),
(226, 'Two users have logged in', '2025-04-25 06:07:37'),
(227, 'Two users have logged in', '2025-04-25 06:07:38'),
(228, 'Two users have logged in', '2025-04-25 07:30:03'),
(229, 'Two users have logged in', '2025-04-25 07:30:04'),
(230, 'Two users have logged in', '2025-04-25 07:30:04'),
(231, 'Two users have logged in', '2025-04-25 07:31:55'),
(232, 'Two users have logged in', '2025-04-25 07:32:49'),
(233, 'Two users have logged in', '2025-04-26 08:00:08'),
(234, 'Two users have logged in', '2025-04-26 08:00:55'),
(235, 'Two users have logged in', '2025-04-26 08:00:56'),
(236, 'Two users have logged in', '2025-04-26 08:10:27'),
(237, 'Two users have logged in', '2025-04-26 08:14:49'),
(238, 'Two users have logged in', '2025-04-26 08:15:21'),
(239, 'Two users have logged in', '2025-04-26 08:16:03'),
(240, 'Two users have logged in', '2025-04-26 08:16:05'),
(241, 'Two users have logged in', '2025-04-26 08:22:01'),
(242, 'Two users have logged in', '2025-04-26 16:18:49'),
(243, 'Two users have logged in', '2025-04-26 17:38:45'),
(244, 'Two users have logged in', '2025-04-26 17:41:05'),
(245, 'Two users have logged in', '2025-04-26 17:43:23'),
(246, 'Two users have logged in', '2025-04-26 17:51:13'),
(247, 'Two users have logged in', '2025-04-27 03:56:38'),
(248, 'Two users have logged in', '2025-04-27 03:57:55'),
(249, 'Two users have logged in', '2025-04-27 03:57:55'),
(250, 'Two users have logged in', '2025-04-27 04:00:46'),
(251, 'Two users have logged in', '2025-04-27 04:00:46'),
(252, 'Two users have logged in', '2025-04-27 04:01:26'),
(253, 'Two users have logged in', '2025-04-27 04:01:27'),
(254, 'Two users have logged in', '2025-04-27 04:01:27'),
(255, 'Two users have logged in', '2025-04-27 04:01:28'),
(256, 'Two users have logged in', '2025-04-27 04:01:28'),
(257, 'Two users have logged in', '2025-04-27 04:01:28'),
(258, 'Two users have logged in', '2025-04-27 04:01:32'),
(259, 'Two users have logged in', '2025-04-28 11:41:26'),
(260, 'Two users have logged in', '2025-04-28 11:48:36'),
(261, 'Two users have logged in', '2025-04-28 12:38:50'),
(262, 'Two users have logged in', '2025-04-28 17:56:15'),
(263, 'Two users have logged in', '2025-04-29 03:31:35'),
(264, 'Two users have logged in', '2025-04-29 03:36:58'),
(265, 'Two users have logged in', '2025-04-29 03:43:27'),
(266, 'Two users have logged in', '2025-04-29 03:47:40'),
(267, 'Two users have logged in', '2025-04-29 03:47:41'),
(268, 'Two users have logged in', '2025-04-29 03:48:08'),
(269, 'Two users have logged in', '2025-04-29 06:53:52'),
(270, 'Two users have logged in', '2025-04-29 06:55:27'),
(271, 'Two users have logged in', '2025-04-29 06:59:33'),
(272, 'Two users have logged in', '2025-04-29 08:18:51'),
(273, 'Two users have logged in', '2025-04-29 10:17:19'),
(274, 'Two users have logged in', '2025-04-29 10:17:19'),
(275, 'Two users have logged in', '2025-04-29 10:19:12'),
(276, 'Two users have logged in', '2025-04-30 10:27:47'),
(277, 'Two users have logged in', '2025-04-30 10:27:49'),
(278, 'Two users have logged in', '2025-04-30 10:27:52'),
(279, 'Two users have logged in', '2025-04-30 10:28:12'),
(280, 'Two users have logged in', '2025-04-30 10:28:14'),
(281, 'Two users have logged in', '2025-04-30 10:43:24'),
(282, 'Two users have logged in', '2025-04-30 10:43:39'),
(283, 'Two users have logged in', '2025-04-30 10:53:31'),
(284, 'Two users have logged in', '2025-04-30 11:43:07'),
(285, 'Two users have logged in', '2025-04-30 11:44:19'),
(286, 'Two users have logged in', '2025-04-30 14:27:45'),
(287, 'Two users have logged in', '2025-04-30 14:28:42'),
(288, 'Two users have logged in', '2025-04-30 14:28:43'),
(289, 'Two users have logged in', '2025-04-30 14:29:27'),
(290, 'Two users have logged in', '2025-04-30 14:29:28'),
(291, 'Two users have logged in', '2025-04-30 14:32:47'),
(292, 'Two users have logged in', '2025-05-01 03:25:47'),
(293, 'Two users have logged in', '2025-05-01 03:27:06');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `shipping_id` int(11) DEFAULT NULL,
  `total_amount` decimal(10,2) DEFAULT NULL,
  `payment_status` enum('pending','paid','failed') DEFAULT 'pending',
  `payment_method` varchar(50) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `commission_given` tinyint(1) NOT NULL DEFAULT 0,
  `commission_amount` varchar(250) DEFAULT '0',
  `order_code` varchar(250) DEFAULT '000'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `shipping_id`, `total_amount`, `payment_status`, `payment_method`, `created_at`, `commission_given`, `commission_amount`, `order_code`) VALUES
(1, 247, 2, 3.86, 'pending', 'stripe', '2025-08-30 10:07:14', 0, '0', 'ORDVVYY'),
(26, 247, 2, 47.99, 'paid', 'stripe', '2025-07-16 14:56:50', 1, '0.33', 'ORD52RJ'),
(27, 247, 2, 47.99, 'paid', 'stripe', '2025-07-17 13:35:15', 1, '0.33', 'ORD65M7'),
(28, 247, 2, 1.93, 'failed', 'stripe', '2025-08-19 15:01:25', 0, '0', 'ORD3XS1'),
(29, 247, 2, 3.86, 'pending', 'stripe', '2025-08-30 10:10:01', 0, '0', 'ORDI78L'),
(30, 247, 2, 3.86, 'paid', 'stripe', '2025-08-30 10:10:23', 1, '0.6', 'ORDFR0N');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price`) VALUES
(52, 26, 15, 1, 39.99),
(53, 27, 15, 1, 39.99),
(54, 28, 9, 1, 1.61),
(55, 0, 9, 2, 1.61),
(56, 29, 9, 2, 1.61),
(57, 30, 9, 2, 1.61);

-- --------------------------------------------------------

--
-- Table structure for table `order_shipments`
--

CREATE TABLE `order_shipments` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED NOT NULL,
  `courier_name` varchar(120) DEFAULT NULL,
  `tracking_number` varchar(120) DEFAULT NULL,
  `status` enum('placed','paid','packed','shipped','out_for_delivery','delivered','cancelled','refunded','failed') NOT NULL DEFAULT 'placed',
  `shipped_at` datetime DEFAULT NULL,
  `expected_delivery` date DEFAULT NULL,
  `delivered_at` datetime DEFAULT NULL,
  `remarks` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_shipments`
--

INSERT INTO `order_shipments` (`id`, `order_id`, `courier_name`, `tracking_number`, `status`, `shipped_at`, `expected_delivery`, `delivered_at`, `remarks`, `created_at`, `updated_at`) VALUES
(1, 28, 'DHL', 'ABCD123', 'packed', NULL, '2025-08-31', NULL, 'will appere to now', '2025-08-30 12:38:14', '2025-08-30 12:56:42'),
(2, 27, 'DHL', 'ABC1234', 'packed', NULL, '2025-08-28', NULL, 'keep to update', '2025-08-30 13:09:20', '2025-08-30 13:09:20'),
(3, 30, 'DHL', 'ABC123', 'placed', NULL, '2025-09-06', NULL, 'test', '2025-08-30 14:27:43', '2025-08-30 14:27:43');

-- --------------------------------------------------------

--
-- Table structure for table `order_status_history`
--

CREATE TABLE `order_status_history` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED NOT NULL,
  `status` enum('placed','paid','packed','shipped','out_for_delivery','delivered','cancelled','refunded','failed') NOT NULL,
  `note` varchar(255) DEFAULT NULL,
  `changed_by_admin_id` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_status_history`
--

INSERT INTO `order_status_history` (`id`, `order_id`, `status`, `note`, `changed_by_admin_id`, `created_at`) VALUES
(1, 28, 'paid', 'asdf', 1, '2025-08-30 12:38:14'),
(2, 28, 'packed', 'will appere to now', 1, '2025-08-30 12:56:42'),
(3, 27, 'packed', 'keep to update', 1, '2025-08-30 13:09:20'),
(4, 30, 'placed', 'test', 1, '2025-08-30 14:27:43');

-- --------------------------------------------------------

--
-- Table structure for table `package_config`
--

CREATE TABLE `package_config` (
  `package_name` varchar(250) DEFAULT NULL,
  `id` int(11) NOT NULL,
  `minimum` varchar(250) DEFAULT NULL,
  `maximum` varchar(250) DEFAULT NULL,
  `bv` decimal(14,4) NOT NULL DEFAULT 0.0000,
  `binary_commission` decimal(14,4) NOT NULL DEFAULT 0.0000,
  `binary_commission_type` enum('amount','percent') NOT NULL DEFAULT 'percent',
  `own_commission` decimal(14,4) NOT NULL DEFAULT 0.0000,
  `direct_commission` decimal(14,4) NOT NULL DEFAULT 0.0000,
  `pair_commission_status` tinyint(1) NOT NULL DEFAULT 1,
  `pair_commission` decimal(14,4) NOT NULL DEFAULT 0.0000,
  `pair_commission_type` enum('amount','percent') NOT NULL DEFAULT 'percent',
  `daily_max_pairs` int(11) NOT NULL DEFAULT 0,
  `matching_bonus_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`matching_bonus_json`)),
  `level_pv_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`level_pv_json`)),
  `product_level_comm_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`product_level_comm_json`)),
  `subscription_type` enum('monthly','yearly') NOT NULL DEFAULT 'yearly',
  `subscription_grace_days` int(11) NOT NULL DEFAULT 0,
  `period` varchar(150) DEFAULT NULL,
  `roi` varchar(150) DEFAULT NULL,
  `duration` varchar(150) DEFAULT NULL,
  `status` int(11) NOT NULL DEFAULT 0,
  `created_date` datetime DEFAULT NULL,
  `update_date` datetime DEFAULT NULL,
  `retrun_principle` int(11) DEFAULT 1,
  `days_duration` varchar(250) DEFAULT NULL,
  `roi_made_by` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `package_config`
--

INSERT INTO `package_config` (`package_name`, `id`, `minimum`, `maximum`, `bv`, `binary_commission`, `binary_commission_type`, `own_commission`, `direct_commission`, `pair_commission_status`, `pair_commission`, `pair_commission_type`, `daily_max_pairs`, `matching_bonus_json`, `level_pv_json`, `product_level_comm_json`, `subscription_type`, `subscription_grace_days`, `period`, `roi`, `duration`, `status`, `created_date`, `update_date`, `retrun_principle`, `days_duration`, `roi_made_by`) VALUES
('ZEN', 4, '100', '200', 20.0000, 10.0000, 'percent', 10.0000, 30.0000, 1, 10.0000, 'percent', 5, '[10,2,4,6]', '[10,5,4]', '[12,6,7]', '', 0, 'daily', '0.1', '30', 1, '2025-03-07 13:17:33', '2025-08-29 08:39:53', 1, '30', 'token'),
('CORE', 9, '1000', '2000', 20.0000, 20.0000, 'percent', 20.0000, 30.0000, 1, 20.0000, 'percent', 5, '[10,2,4,6]', '[10,5,4]', '[12,6,7]', 'yearly', 0, 'daily', '0.1', '30', 1, '2025-03-07 13:17:33', '2025-08-29 08:39:53', 1, '30', 'token');

-- --------------------------------------------------------

--
-- Table structure for table `page_content`
--

CREATE TABLE `page_content` (
  `id` int(11) NOT NULL,
  `page_name` varchar(255) NOT NULL,
  `content` longtext NOT NULL,
  `datetime` datetime NOT NULL,
  `updated_datetime` datetime NOT NULL,
  `temp_status` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `page_content`
--

INSERT INTO `page_content` (`id`, `page_name`, `content`, `datetime`, `updated_datetime`, `temp_status`) VALUES
(12, 'hero_sectoin', '<h3 class=\"title\">Secure Reliable</h3>\r\n\r\n<h1 class=\"title pt-3\">Fenizo MLM Software</h1>\r\n', '0000-00-00 00:00:00', '2023-11-19 09:25:04', 1),
(13, 'token_structure', 'Token Structure', '2025-03-31 10:38:33', '2025-03-31 10:38:33', 1),
(14, 'token_structure_dec', '<p>Our token structure ensures transparency, fairness, and value.</p>\r\n\r\n<p>Join us in revolutionizing the digital economy.</p>\r\n', '2025-03-31 10:38:48', '2025-03-31 10:38:48', 1),
(15, 'pre_sale_box_title', '<p>PRE-SALE                 SOFT CAF               IDO</p>\r\n', '2025-03-31 10:41:02', '2025-03-31 10:41:02', 1),
(16, 'pre_sale_box_value', '<p> ||  1 FENI = 0.05 USDT  </p>\r\n\r\n<p> ||  1 USDT = 20 FENI</p>\r\n', '2025-03-31 10:41:44', '2025-03-31 10:41:44', 1),
(17, 'vission_mission_title_content', '<h2 class=\"xb-item--title\">Vision & Mission</h2>\r\n\r\n<p class=\"xb-item--content\">At <strong data-end=\"206\" data-start=\"183\">Fenizo Technologies</strong>, our vision is to empower businesses and individuals through innovative MLM software solutions that simplify network management and accelerate growth. We aim to be a trusted leader in the MLM industry by driving digital transformation, ensuring transparency, and delivering scalable, user-friendly tools that support success at every level.</p>\r\n', '2025-03-31 10:42:55', '2025-03-31 10:42:55', 1),
(18, 'vission_mission_list', '<div class=\"xb-item--list\"><span><svg fill=\"none\" height=\"18\" viewbox=\"0 0 18 18\" width=\"18\" xmlns=\"http://www.w3.org/2000/svg\"> <path d=\"M18 9C18 9.768 17.0565 10.401 16.8675 11.109C16.6725 11.841 17.166 12.861 16.7955 13.5015C16.419 14.1525 15.2865 14.2305 14.7585 14.7585C14.2305 15.2865 14.1525 16.419 13.5015 16.7955C12.861 17.166 11.841 16.6725 11.109 16.8675C10.401 17.0565 9.768 18 9 18C8.232 18 7.599 17.0565 6.891 16.8675C6.159 16.6725 5.139 17.166 4.4985 16.7955C3.8475 16.419 3.7695 15.2865 3.2415 14.7585C2.7135 14.2305 1.581 14.1525 1.2045 13.5015C0.834 12.861 1.3275 11.841 1.1325 11.109C0.9435 10.401 0 9.768 0 9C0 8.232 0.9435 7.599 1.1325 6.891C1.3275 6.159 0.834 5.139 1.2045 4.4985C1.581 3.8475 2.7135 3.7695 3.2415 3.2415C3.7695 2.7135 3.8475 1.581 4.4985 1.2045C5.139 0.834 6.159 1.3275 6.891 1.1325C7.599 0.9435 8.232 0 9 0C9.768 0 10.401 0.9435 11.109 1.1325C11.841 1.3275 12.861 0.834 13.5015 1.2045C14.1525 1.581 14.2305 2.7135 14.7585 3.2415C15.2865 3.7695 16.419 3.8475 16.7955 4.4985C17.166 5.139 16.6725 6.159 16.8675 6.891C17.0565 7.599 18 8.232 18 9Z\" fill=\"white\"></path> <path d=\"M11.6674 6.85539L8.54986 9.88334L6.93376 8.31501C6.58297 7.9743 6.01379 7.9743 5.663 8.31501C5.3122 8.65572 5.3122 9.20854 5.663 9.54926L7.93018 11.7513C8.27141 12.0827 8.82558 12.0827 9.16682 11.7513L12.9368 8.08963C13.2876 7.74892 13.2876 7.1961 12.9368 6.85539C12.586 6.51468 12.0182 6.51468 11.6674 6.85539Z\" fill=\"#080B18\"></path> </svg> Simplify and elevate digital finance</span> <span><svg fill=\"none\" height=\"18\" viewbox=\"0 0 18 18\" width=\"18\" xmlns=\"http://www.w3.org/2000/svg\"> <path d=\"M18 9C18 9.768 17.0565 10.401 16.8675 11.109C16.6725 11.841 17.166 12.861 16.7955 13.5015C16.419 14.1525 15.2865 14.2305 14.7585 14.7585C14.2305 15.2865 14.1525 16.419 13.5015 16.7955C12.861 17.166 11.841 16.6725 11.109 16.8675C10.401 17.0565 9.768 18 9 18C8.232 18 7.599 17.0565 6.891 16.8675C6.159 16.6725 5.139 17.166 4.4985 16.7955C3.8475 16.419 3.7695 15.2865 3.2415 14.7585C2.7135 14.2305 1.581 14.1525 1.2045 13.5015C0.834 12.861 1.3275 11.841 1.1325 11.109C0.9435 10.401 0 9.768 0 9C0 8.232 0.9435 7.599 1.1325 6.891C1.3275 6.159 0.834 5.139 1.2045 4.4985C1.581 3.8475 2.7135 3.7695 3.2415 3.2415C3.7695 2.7135 3.8475 1.581 4.4985 1.2045C5.139 0.834 6.159 1.3275 6.891 1.1325C7.599 0.9435 8.232 0 9 0C9.768 0 10.401 0.9435 11.109 1.1325C11.841 1.3275 12.861 0.834 13.5015 1.2045C14.1525 1.581 14.2305 2.7135 14.7585 3.2415C15.2865 3.7695 16.419 3.8475 16.7955 4.4985C17.166 5.139 16.6725 6.159 16.8675 6.891C17.0565 7.599 18 8.232 18 9Z\" fill=\"white\"></path> <path d=\"M11.6674 6.85539L8.54986 9.88334L6.93376 8.31501C6.58297 7.9743 6.01379 7.9743 5.663 8.31501C5.3122 8.65572 5.3122 9.20854 5.663 9.54926L7.93018 11.7513C8.27141 12.0827 8.82558 12.0827 9.16682 11.7513L12.9368 8.08963C13.2876 7.74892 13.2876 7.1961 12.9368 6.85539C12.586 6.51468 12.0182 6.51468 11.6674 6.85539Z\" fill=\"#080B18\"></path> </svg> Disruptive tech (AI, blockchain, NFTs)</span> <span><svg fill=\"none\" height=\"18\" viewbox=\"0 0 18 18\" width=\"18\" xmlns=\"http://www.w3.org/2000/svg\"> <path d=\"M18 9C18 9.768 17.0565 10.401 16.8675 11.109C16.6725 11.841 17.166 12.861 16.7955 13.5015C16.419 14.1525 15.2865 14.2305 14.7585 14.7585C14.2305 15.2865 14.1525 16.419 13.5015 16.7955C12.861 17.166 11.841 16.6725 11.109 16.8675C10.401 17.0565 9.768 18 9 18C8.232 18 7.599 17.0565 6.891 16.8675C6.159 16.6725 5.139 17.166 4.4985 16.7955C3.8475 16.419 3.7695 15.2865 3.2415 14.7585C2.7135 14.2305 1.581 14.1525 1.2045 13.5015C0.834 12.861 1.3275 11.841 1.1325 11.109C0.9435 10.401 0 9.768 0 9C0 8.232 0.9435 7.599 1.1325 6.891C1.3275 6.159 0.834 5.139 1.2045 4.4985C1.581 3.8475 2.7135 3.7695 3.2415 3.2415C3.7695 2.7135 3.8475 1.581 4.4985 1.2045C5.139 0.834 6.159 1.3275 6.891 1.1325C7.599 0.9435 8.232 0 9 0C9.768 0 10.401 0.9435 11.109 1.1325C11.841 1.3275 12.861 0.834 13.5015 1.2045C14.1525 1.581 14.2305 2.7135 14.7585 3.2415C15.2865 3.7695 16.419 3.8475 16.7955 4.4985C17.166 5.139 16.6725 6.159 16.8675 6.891C17.0565 7.599 18 8.232 18 9Z\" fill=\"white\"></path> <path d=\"M11.6674 6.85539L8.54986 9.88334L6.93376 8.31501C6.58297 7.9743 6.01379 7.9743 5.663 8.31501C5.3122 8.65572 5.3122 9.20854 5.663 9.54926L7.93018 11.7513C8.27141 12.0827 8.82558 12.0827 9.16682 11.7513L12.9368 8.08963C13.2876 7.74892 13.2876 7.1961 12.9368 6.85539C12.586 6.51468 12.0182 6.51468 11.6674 6.85539Z\" fill=\"#080B18\"></path> </svg> Secure transactions, staking, digital asset ownership.</span> <span><svg fill=\"none\" height=\"18\" viewbox=\"0 0 18 18\" width=\"18\" xmlns=\"http://www.w3.org/2000/svg\"> <path d=\"M18 9C18 9.768 17.0565 10.401 16.8675 11.109C16.6725 11.841 17.166 12.861 16.7955 13.5015C16.419 14.1525 15.2865 14.2305 14.7585 14.7585C14.2305 15.2865 14.1525 16.419 13.5015 16.7955C12.861 17.166 11.841 16.6725 11.109 16.8675C10.401 17.0565 9.768 18 9 18C8.232 18 7.599 17.0565 6.891 16.8675C6.159 16.6725 5.139 17.166 4.4985 16.7955C3.8475 16.419 3.7695 15.2865 3.2415 14.7585C2.7135 14.2305 1.581 14.1525 1.2045 13.5015C0.834 12.861 1.3275 11.841 1.1325 11.109C0.9435 10.401 0 9.768 0 9C0 8.232 0.9435 7.599 1.1325 6.891C1.3275 6.159 0.834 5.139 1.2045 4.4985C1.581 3.8475 2.7135 3.7695 3.2415 3.2415C3.7695 2.7135 3.8475 1.581 4.4985 1.2045C5.139 0.834 6.159 1.3275 6.891 1.1325C7.599 0.9435 8.232 0 9 0C9.768 0 10.401 0.9435 11.109 1.1325C11.841 1.3275 12.861 0.834 13.5015 1.2045C14.1525 1.581 14.2305 2.7135 14.7585 3.2415C15.2865 3.7695 16.419 3.8475 16.7955 4.4985C17.166 5.139 16.6725 6.159 16.8675 6.891C17.0565 7.599 18 8.232 18 9Z\" fill=\"white\"></path> <path d=\"M11.6674 6.85539L8.54986 9.88334L6.93376 8.31501C6.58297 7.9743 6.01379 7.9743 5.663 8.31501C5.3122 8.65572 5.3122 9.20854 5.663 9.54926L7.93018 11.7513C8.27141 12.0827 8.82558 12.0827 9.16682 11.7513L12.9368 8.08963C13.2876 7.74892 13.2876 7.1961 12.9368 6.85539C12.586 6.51468 12.0182 6.51468 11.6674 6.85539Z\" fill=\"#080B18\"></path> </svg> Supportive community, transparency, member voice.</span></div>\r\n', '2025-03-31 10:43:48', '2025-03-31 10:43:48', 1),
(19, 'register_title_content', ' <h2 class=\"xb-item--title\">register account</h2>\r\n                                <p class=\"xb-item--content\">To register, download our app, fill out your email, and password.</p>', '2025-03-31 10:45:55', '2025-03-31 10:45:55', 1),
(20, 'deposit_title_content', ' <h2 class=\"xb-item--title\">deposit amount</h2>\r\n                                <p class=\"xb-item--content\">To deposit funds, log in to your account and navigate to the lending section. </p>', '2025-03-31 10:46:26', '2025-03-31 10:46:26', 1),
(21, 'lend_title_content', '<h2 class=\"xb-item--title\">lend Fenizo MLM Software!</h2>\r\n\r\n<p class=\"xb-item--content\">To earn through our MLM system, simply log in to your account and navigate to the <strong data-end=\"208\" data-start=\"194\">\"Earnings\"</strong> section to access commissions, bonuses, and team performance insights.</p>\r\n', '2025-03-31 10:46:56', '2025-03-31 10:46:56', 1),
(22, 'director_title', '  <h1 class=\"title\">Director\'s Live Project </h1>', '2025-03-31 10:47:23', '2025-03-31 10:47:23', 1),
(23, 'director_list', '<div class=\"col-md-4\">\r\n<div class=\"prj-set\">\r\n<img src=\"https://adrox.ai/assets/user/img/p1.jpg\" class=\"prj-img\">\r\n<div class=\"prj-cnt\">VictorScott Properties</div>\r\n</div>\r\n</div>\r\n<div class=\"col-md-4\">\r\n<div class=\"prj-set\">\r\n<img src=\"https://adrox.ai/assets/user/img/p2.jpg\" class=\"prj-img\">\r\n<div class=\"prj-cnt\">VictorScott Fragrances</div>\r\n</div>\r\n</div>\r\n<div class=\"col-md-4\">\r\n<div class=\"prj-set\">\r\n<img src=\"https://adrox.ai/assets/user/img/p3.jpg\" class=\"prj-img\">\r\n<div class=\"prj-cnt\">TauTona Gold Mine</div>\r\n</div>\r\n</div>\r\n<div class=\"col-md-3\">\r\n<div class=\"prj-set\">\r\n<img src=\"https://adrox.ai/assets/user/img/p4.jpg\" class=\"prj-img\">\r\n<div class=\"prj-cnt\">MedOrange Pharmacies</div>\r\n</div>\r\n</div>\r\n<div class=\"col-md-3\">\r\n<div class=\"prj-set\">\r\n<img src=\"https://adrox.ai/assets/user/img/p5.jpg\" class=\"prj-img\">\r\n<div class=\"prj-cnt\">Moollas Sunrise Farm</div>\r\n</div>\r\n</div>\r\n<div class=\"col-md-3\">\r\n<div class=\"prj-set\">\r\n<img src=\"https://adrox.ai/assets/user/img/p6.jpg\" class=\"prj-img\">\r\n<div class=\"prj-cnt\">Shades Beauty Studio</div>\r\n</div>\r\n</div>\r\n<div class=\"col-md-3\">\r\n<div class=\"prj-set\">\r\n<img src=\"https://adrox.ai/assets/user/img/p7.jpg\" class=\"prj-img\">\r\n<div class=\"prj-cnt\">UCS Solutions</div>\r\n</div>\r\n</div>', '2025-03-31 10:48:41', '2025-03-31 10:48:41', 1),
(24, 'road_map_tilte', ' <h1 class=\"title\">Our Road map</h1>', '2025-03-31 10:49:16', '2025-03-31 10:49:16', 1),
(25, 'road_map_list_2027', '<div class=\"roadmap--item roadmap--first_item\">\r\n  <h2 class=\"roadmap--head\">Fenizo Member Wallet</h2>\r\n  <p class=\"roadmap--info\">\r\n    A secure, user-friendly wallet system designed for MLM payouts, bonuses, and earnings tracking. Supports multi-tier commissions, real-time balances, and secure transactions within the Fenizo ecosystem.\r\n  </p>\r\n  <div class=\"roadmap--year\">\r\n    <div class=\"roadmap--circle\"> </div>\r\n    <span>April of 2025</span>\r\n  </div>\r\n</div>\r\n\r\n<div class=\"roadmap--item\">\r\n  <h2 class=\"roadmap--head\">Integration with Payment Gateways</h2>\r\n  <p class=\"roadmap--info\">\r\n    Seamless integration with both local and global payment platforms to enhance withdrawal options, commission distribution, and member accessibility—boosting trust and growth across the MLM network.\r\n  </p>\r\n  <div class=\"roadmap--year\">\r\n    <div class=\"roadmap--circle\"> </div>\r\n    <span>End of 2026</span>\r\n  </div>\r\n</div>\r\n\r\n<div class=\"roadmap--item\">\r\n  <h2 class=\"roadmap--head\">Fenizo Lending & Rewards System</h2>\r\n  <p class=\"roadmap--info\">\r\n    An internal lending and bonus system that allows members to earn rewards, offer peer-to-peer support, and receive rank-based incentives—fostering transparency, financial empowerment, and community growth.\r\n  </p>\r\n  <div class=\"roadmap--year\">\r\n    <div class=\"roadmap--circle\"> </div>\r\n    <span>April of 2025</span>\r\n  </div>\r\n</div>\r\n', '2025-03-31 10:50:23', '2025-03-31 10:50:23', 1),
(26, 'road_map_list_2025_28', '<div class=\"roadmap--item bottom-item\">\r\n  <div class=\"roadmap--year\">\r\n    <span>Mid of 2026</span>\r\n    <div class=\"roadmap--circle\"> </div>\r\n  </div>\r\n  <h2 class=\"roadmap--head\" style=\"min-width: 20rem;\">Multi-Payment Commission Hub</h2>\r\n  <p class=\"roadmap--info\">\r\n    A major advancement to offer centralized commission distribution across multiple payment channels, improving accessibility, speed, and trust for global members.\r\n  </p>\r\n</div>\r\n\r\n<div class=\"roadmap--item bottom-item\">\r\n  <div class=\"roadmap--year\">\r\n    <span>Mid of 2026</span>\r\n    <div class=\"roadmap--circle\"> </div>\r\n  </div>\r\n  <h2 class=\"roadmap--head\">Smart Rule Engine</h2>\r\n  <p class=\"roadmap--info\">\r\n    A powerful, scalable backend system enabling flexible commission logic, custom MLM plan structures, and automated workflows tailored to business growth and compliance.\r\n  </p>\r\n</div>\r\n\r\n<div class=\"roadmap--item bottom-item\">\r\n  <div class=\"roadmap--year\">\r\n    <span>Mid of 2027</span>\r\n    <div class=\"roadmap--circle\"> </div>\r\n  </div>\r\n  <h2 class=\"roadmap--head\">Fenizo Blockchain Framework</h2>\r\n  <p class=\"roadmap--info\">\r\n    A proprietary blockchain framework for MLM applications—offering unmatched transparency, smart contract support, secure transactions, and decentralized reward systems.\r\n  </p>\r\n</div>\r\n\r\n<div class=\"roadmap--item bottom-item\">\r\n  <div class=\"roadmap--year\">\r\n    <span>Mid of 2027</span>\r\n    <div class=\"roadmap--circle\"> </div>\r\n  </div>\r\n  <h2 class=\"roadmap--head\">Fenizo Digital Market Hub</h2>\r\n  <p class=\"roadmap--info\">\r\n    A secure, blockchain-backed marketplace for digital products and services where members can earn, sell, and exchange offerings—boosting member value and passive income streams.\r\n  </p>\r\n</div>\r\n', '2025-03-31 10:51:04', '2025-03-31 10:51:04', 1),
(27, 'features_title', '<h1 class=\"title\">our great features</h1>', '2025-03-31 10:51:41', '2025-03-31 10:51:41', 1),
(28, 'mobile_content', '<h2 class=\"xb-item--title\">Simplify Your MLM Business</h2>\r\n\r\n<p class=\"xb-item--content\">\r\nManage your downlines, track commissions, and monitor growth—all in one powerful MLM platform designed for effortless success.\r\n</p>\r\n', '2025-03-31 10:52:06', '2025-03-31 10:52:06', 1),
(29, 'security_content', '<h2 class=\"xb-item--title\">Secure MLM Transactions & Full Control</h2>\r\n\r\n<p class=\"xb-item--content\">\r\nOur MLM platform ensures top-level security for all transactions while giving you complete control over commissions, payouts, and team activity.\r\n</p>\r\n', '2025-03-31 10:53:07', '2025-03-31 10:53:07', 1),
(30, 'transaction_content', '<h2 class=\"xb-item--title\">Lifetime Free Internal Transfers</h2>\r\n\r\n<p class=\"xb-item--content\">\r\nEnjoy unlimited, lifetime free internal transactions between downlines and uplines — boosting your MLM network growth without extra costs.\r\n</p>\r\n', '2025-03-31 10:53:47', '2025-03-31 10:53:47', 1),
(31, 'protect_indentity', '<h2 class=\"xb-item--title\">Protect Member Identity</h2>\r\n\r\n<p class=\"xb-item--content\">\r\nSafeguarding the identity of every member in your MLM network is our top priority. Our platform uses advanced encryption to ensure personal and financial data stays secure at all times.\r\n</p>\r\n', '2025-03-31 10:54:21', '2025-03-31 10:54:21', 1),
(32, 'apk_verision', 'Mobile App 1.0 <span class=\"new-btn\">new</span>', '2025-03-31 10:55:20', '2025-03-31 10:55:20', 1),
(33, 'apk_info', '<h2 class=\"xb-item--title\">Fenizo MLM Software App</h2>\r\n\r\n<p class=\"xb-item--content\">Everything you need in your smartphone: crypto transaction, lending, send and receive crypto. Our goal-replace your wallet app</p>\r\n', '2025-03-31 10:55:48', '2025-03-31 10:55:48', 1),
(34, 'apk_info_list', ' <li><svg width=\"18\" height=\"18\" viewbox=\"0 0 18 18\" fill=\"none\" xmlns=\"http://www.w3.org/2000/svg\">\n                                        <path d=\"M18 9C18 9.768 17.0565 10.401 16.8675 11.109C16.6725 11.841 17.166 12.861 16.7955 13.5015C16.419 14.1525 15.2865 14.2305 14.7585 14.7585C14.2305 15.2865 14.1525 16.419 13.5015 16.7955C12.861 17.166 11.841 16.6725 11.109 16.8675C10.401 17.0565 9.768 18 9 18C8.232 18 7.599 17.0565 6.891 16.8675C6.159 16.6725 5.139 17.166 4.4985 16.7955C3.8475 16.419 3.7695 15.2865 3.2415 14.7585C2.7135 14.2305 1.581 14.1525 1.2045 13.5015C0.834 12.861 1.3275 11.841 1.1325 11.109C0.9435 10.401 0 9.768 0 9C0 8.232 0.9435 7.599 1.1325 6.891C1.3275 6.159 0.834 5.139 1.2045 4.4985C1.581 3.8475 2.7135 3.7695 3.2415 3.2415C3.7695 2.7135 3.8475 1.581 4.4985 1.2045C5.139 0.834 6.159 1.3275 6.891 1.1325C7.599 0.9435 8.232 0 9 0C9.768 0 10.401 0.9435 11.109 1.1325C11.841 1.3275 12.861 0.834 13.5015 1.2045C14.1525 1.581 14.2305 2.7135 14.7585 3.2415C15.2865 3.7695 16.419 3.8475 16.7955 4.4985C17.166 5.139 16.6725 6.159 16.8675 6.891C17.0565 7.599 18 8.232 18 9Z\" fill=\"white\"></path>\n                                        <path d=\"M11.6674 6.85539L8.54986 9.88334L6.93376 8.31501C6.58297 7.9743 6.01379 7.9743 5.663 8.31501C5.3122 8.65572 5.3122 9.20854 5.663 9.54926L7.93018 11.7513C8.27141 12.0827 8.82558 12.0827 9.16682 11.7513L12.9368 8.08963C13.2876 7.74892 13.2876 7.1961 12.9368 6.85539C12.586 6.51468 12.0182 6.51468 11.6674 6.85539Z\" fill=\"#080B18\"></path>\n                                        </svg> Secure & Reliable Transactions.</li>\n                                    <li><svg width=\"18\" height=\"18\" viewbox=\"0 0 18 18\" fill=\"none\" xmlns=\"http://www.w3.org/2000/svg\">\n                                        <path d=\"M18 9C18 9.768 17.0565 10.401 16.8675 11.109C16.6725 11.841 17.166 12.861 16.7955 13.5015C16.419 14.1525 15.2865 14.2305 14.7585 14.7585C14.2305 15.2865 14.1525 16.419 13.5015 16.7955C12.861 17.166 11.841 16.6725 11.109 16.8675C10.401 17.0565 9.768 18 9 18C8.232 18 7.599 17.0565 6.891 16.8675C6.159 16.6725 5.139 17.166 4.4985 16.7955C3.8475 16.419 3.7695 15.2865 3.2415 14.7585C2.7135 14.2305 1.581 14.1525 1.2045 13.5015C0.834 12.861 1.3275 11.841 1.1325 11.109C0.9435 10.401 0 9.768 0 9C0 8.232 0.9435 7.599 1.1325 6.891C1.3275 6.159 0.834 5.139 1.2045 4.4985C1.581 3.8475 2.7135 3.7695 3.2415 3.2415C3.7695 2.7135 3.8475 1.581 4.4985 1.2045C5.139 0.834 6.159 1.3275 6.891 1.1325C7.599 0.9435 8.232 0 9 0C9.768 0 10.401 0.9435 11.109 1.1325C11.841 1.3275 12.861 0.834 13.5015 1.2045C14.1525 1.581 14.2305 2.7135 14.7585 3.2415C15.2865 3.7695 16.419 3.8475 16.7955 4.4985C17.166 5.139 16.6725 6.159 16.8675 6.891C17.0565 7.599 18 8.232 18 9Z\" fill=\"white\"></path>\n                                        <path d=\"M11.6674 6.85539L8.54986 9.88334L6.93376 8.31501C6.58297 7.9743 6.01379 7.9743 5.663 8.31501C5.3122 8.65572 5.3122 9.20854 5.663 9.54926L7.93018 11.7513C8.27141 12.0827 8.82558 12.0827 9.16682 11.7513L12.9368 8.08963C13.2876 7.74892 13.2876 7.1961 12.9368 6.85539C12.586 6.51468 12.0182 6.51468 11.6674 6.85539Z\" fill=\"#080B18\"></path>\n                                        </svg> Multi-Currency Commission Support</li>\n                                    <li><svg width=\"18\" height=\"18\" viewbox=\"0 0 18 18\" fill=\"none\" xmlns=\"http://www.w3.org/2000/svg\">\n                                        <path d=\"M18 9C18 9.768 17.0565 10.401 16.8675 11.109C16.6725 11.841 17.166 12.861 16.7955 13.5015C16.419 14.1525 15.2865 14.2305 14.7585 14.7585C14.2305 15.2865 14.1525 16.419 13.5015 16.7955C12.861 17.166 11.841 16.6725 11.109 16.8675C10.401 17.0565 9.768 18 9 18C8.232 18 7.599 17.0565 6.891 16.8675C6.159 16.6725 5.139 17.166 4.4985 16.7955C3.8475 16.419 3.7695 15.2865 3.2415 14.7585C2.7135 14.2305 1.581 14.1525 1.2045 13.5015C0.834 12.861 1.3275 11.841 1.1325 11.109C0.9435 10.401 0 9.768 0 9C0 8.232 0.9435 7.599 1.1325 6.891C1.3275 6.159 0.834 5.139 1.2045 4.4985C1.581 3.8475 2.7135 3.7695 3.2415 3.2415C3.7695 2.7135 3.8475 1.581 4.4985 1.2045C5.139 0.834 6.159 1.3275 6.891 1.1325C7.599 0.9435 8.232 0 9 0C9.768 0 10.401 0.9435 11.109 1.1325C11.841 1.3275 12.861 0.834 13.5015 1.2045C14.1525 1.581 14.2305 2.7135 14.7585 3.2415C15.2865 3.7695 16.419 3.8475 16.7955 4.4985C17.166 5.139 16.6725 6.159 16.8675 6.891C17.0565 7.599 18 8.232 18 9Z\" fill=\"white\"></path>\n                                        <path d=\"M11.6674 6.85539L8.54986 9.88334L6.93376 8.31501C6.58297 7.9743 6.01379 7.9743 5.663 8.31501C5.3122 8.65572 5.3122 9.20854 5.663 9.54926L7.93018 11.7513C8.27141 12.0827 8.82558 12.0827 9.16682 11.7513L12.9368 8.08963C13.2876 7.74892 13.2876 7.1961 12.9368 6.85539C12.586 6.51468 12.0182 6.51468 11.6674 6.85539Z\" fill=\"#080B18\"></path>\n                                        </svg> Fenizo Lending & Bonus Program</li>\n                                    <li><svg width=\"18\" height=\"18\" viewbox=\"0 0 18 18\" fill=\"none\" xmlns=\"http://www.w3.org/2000/svg\">\n                                        <path d=\"M18 9C18 9.768 17.0565 10.401 16.8675 11.109C16.6725 11.841 17.166 12.861 16.7955 13.5015C16.419 14.1525 15.2865 14.2305 14.7585 14.7585C14.2305 15.2865 14.1525 16.419 13.5015 16.7955C12.861 17.166 11.841 16.6725 11.109 16.8675C10.401 17.0565 9.768 18 9 18C8.232 18 7.599 17.0565 6.891 16.8675C6.159 16.6725 5.139 17.166 4.4985 16.7955C3.8475 16.419 3.7695 15.2865 3.2415 14.7585C2.7135 14.2305 1.581 14.1525 1.2045 13.5015C0.834 12.861 1.3275 11.841 1.1325 11.109C0.9435 10.401 0 9.768 0 9C0 8.232 0.9435 7.599 1.1325 6.891C1.3275 6.159 0.834 5.139 1.2045 4.4985C1.581 3.8475 2.7135 3.7695 3.2415 3.2415C3.7695 2.7135 3.8475 1.581 4.4985 1.2045C5.139 0.834 6.159 1.3275 6.891 1.1325C7.599 0.9435 8.232 0 9 0C9.768 0 10.401 0.9435 11.109 1.1325C11.841 1.3275 12.861 0.834 13.5015 1.2045C14.1525 1.581 14.2305 2.7135 14.7585 3.2415C15.2865 3.7695 16.419 3.8475 16.7955 4.4985C17.166 5.139 16.6725 6.159 16.8675 6.891C17.0565 7.599 18 8.232 18 9Z\" fill=\"white\"></path>\n                                        <path d=\"M11.6674 6.85539L8.54986 9.88334L6.93376 8.31501C6.58297 7.9743 6.01379 7.9743 5.663 8.31501C5.3122 8.65572 5.3122 9.20854 5.663 9.54926L7.93018 11.7513C8.27141 12.0827 8.82558 12.0827 9.16682 11.7513L12.9368 8.08963C13.2876 7.74892 13.2876 7.1961 12.9368 6.85539C12.586 6.51468 12.0182 6.51468 11.6674 6.85539Z\" fill=\"#080B18\"></path>\n                                        </svg> User-Friendly MLM Dashboard </li>', '2025-03-31 10:56:27', '2025-03-31 10:56:27', 1),
(35, 'our_team_info', '<div class=\"container\">\r\n<div class=\"section-title pb-35\">\r\n<h1 class=\"title\">Meet our team</h1>\r\n</div>\r\n\r\n<div class=\"row\">\r\n<div class=\"col-lg-4\">\r\n<div class=\"xb-team xb-team1 text-center\">\r\n<div class=\"xb-item--img pos-rel\"><img alt=\"\" src=\"https://adrox.ai/assets/user/img/t5.jpg\" /></div>\r\n\r\n<div class=\"xb-item--holder\">\r\n<h2 class=\"xb-item--title\">Victor</h2>\r\n<span class=\"xb-item--sub-title\">DIRECTOR</span></div>\r\n</div>\r\n</div>\r\n\r\n<div class=\"col-lg-8 \">\r\n<div class=\"row xb-team-right\">\r\n<div class=\"col-lg-4 col-md-6 col-6\">\r\n<div class=\"xb-team text-center\">\r\n<div class=\"xb-item--img pos-rel\"><img alt=\"\" src=\"https://adrox.ai/assets/user/img/t1.jpg\" /></div>\r\n\r\n<div class=\"xb-item--holder\">\r\n<h2 class=\"xb-item--title\">Nathan</h2>\r\n<span class=\"xb-item--sub-title\">DIRECTOR</span></div>\r\n</div>\r\n</div>\r\n\r\n<div class=\"col-lg-4 col-md-6  col-6\">\r\n<div class=\"xb-team text-center\">\r\n<div class=\"xb-item--img pos-rel\"><img alt=\"\" src=\"https://adrox.ai/assets/user/img/t2.jpg\" /></div>\r\n\r\n<div class=\"xb-item--holder\">\r\n<h2 class=\"xb-item--title\">Joanne</h2>\r\n<span class=\"xb-item--sub-title\">DIRECTOR</span></div>\r\n</div>\r\n</div>\r\n\r\n<div class=\"col-lg-4 col-md-6  col-6\">\r\n<div class=\"xb-team text-center\">\r\n<div class=\"xb-item--img pos-rel\"><img alt=\"\" src=\"https://adrox.ai/assets/user/img/t3.jpg\" /></div>\r\n\r\n<div class=\"xb-item--holder\">\r\n<h2 class=\"xb-item--title\">Amine</h2>\r\n<span class=\"xb-item--sub-title\">DIRECTOR</span></div>\r\n</div>\r\n</div>\r\n\r\n<div class=\"col-lg-4 col-md-6  col-6\">\r\n<div class=\"xb-team text-center\">\r\n<div class=\"xb-item--img pos-rel\"><img alt=\"\" src=\"https://adrox.ai/assets/user/img/t4.jpg\" /></div>\r\n\r\n<div class=\"xb-item--holder\">\r\n<h2 class=\"xb-item--title\">Shedrah</h2>\r\n<span class=\"xb-item--sub-title\">DIRECTOR</span></div>\r\n</div>\r\n</div>\r\n\r\n<div class=\"col-lg-4 col-md-6  col-6\">\r\n<div class=\"xb-team text-center\">\r\n<div class=\"xb-item--img pos-rel\"><img alt=\"\" src=\"https://adrox.ai/assets/user/img/t7.jpg\" /></div>\r\n\r\n<div class=\"xb-item--holder\">\r\n<h2 class=\"xb-item--title\">Aaron</h2>\r\nDIRECTOR</div>\r\n</div>\r\n</div>\r\n\r\n<div class=\"col-lg-4 col-md-6  col-6\">\r\n<div class=\"xb-team text-center\">\r\n<div class=\"xb-item--img pos-rel\"><img alt=\"\" src=\"https://adrox.ai/assets/user/img/t6.jpg\" /></div>\r\n\r\n<div class=\"xb-item--holder\">\r\n<h2 class=\"xb-item--title\">Millar</h2>\r\n<span class=\"xb-item--sub-title\">DIRECTOR</span></div>\r\n</div>\r\n</div>\r\n</div>\r\n</div>\r\n</div>\r\n</div>\r\n', '2025-03-31 10:57:47', '2025-03-31 10:57:47', 0),
(36, 'terms_info', '<p>By accessing and using <strong data-end=\"123\" data-start=\"100\">Fenizo Technologies</strong>, you agree to our <strong data-end=\"166\" data-start=\"142\">Terms and Conditions</strong>, which govern your use of our services, including the <strong data-end=\"245\" data-start=\"221\">Fenizo Member Wallet</strong>, <strong data-end=\"272\" data-start=\"247\">MLM Commission System</strong>, <strong data-end=\"301\" data-start=\"274\">Lending & Bonus Program</strong>, and other MLM tools powered by secure technology. Fenizo Technologies is dedicated to promoting transparency, fairness, and innovation within the MLM industry, ensuring secure transactions, customizable commission structures, and community-driven growth. All intellectual property on this site is owned by <strong data-end=\"632\" data-start=\"609\">Fenizo Technologies</strong>. Users must be 18+ and agree not to misuse the platform or engage in prohibited activities. The tools and systems provided are not investment products and carry inherent risks; users assume full responsibility for their actions. We are not liable for system errors, commission losses, or third-party interactions. Terms are subject to change, and continued use signifies acceptance. For questions or support, please contact us at <strong data-end=\"1097\" data-start=\"1063\"><a data-end=\"1095\" data-start=\"1065\" rel=\"noopener\">support@fenizotechnologies.com</a></strong>.</p>\r\n', '2025-03-31 10:57:47', '2025-03-31 10:57:47', 1);

-- --------------------------------------------------------

--
-- Table structure for table `page_link_config`
--

CREATE TABLE `page_link_config` (
  `id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `page_status` int(11) NOT NULL DEFAULT 0,
  `page_image` varchar(255) DEFAULT NULL,
  `page_document` varchar(255) DEFAULT NULL,
  `page_title` varchar(255) DEFAULT NULL,
  `page_content` varchar(255) DEFAULT NULL,
  `created_date` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `page_link_config`
--

INSERT INTO `page_link_config` (`id`, `title`, `page_status`, `page_image`, `page_document`, `page_title`, `page_content`, `created_date`) VALUES
(1, 'White Paper', 1, 'assets/images/1744294935_chart-graph.png', 'assets/documents/1745311471_DOC-20250421-WA0001..pdf', 'Unlocking the Vision – Our Whitepaper', 'Dive into the blueprint of our mission, technology, and roadmap. Our whitepaper reveals how we’re shaping the future — one innovation at a time.', '0000-00-00 00:00:00'),
(2, 'Project', 0, 'assets/images/1744294936_chart-graph.png', 'assets/documents/1744266391_backup1.txt', 'Project Genesis – The Build Begins', 'Every revolution starts with a bold idea. Explore the concept, strategy, and foundation of our upcoming project that’s set to change the game.', '0000-00-00 00:00:00'),
(3, 'Roadmap', 0, 'assets/images/1744294936_chart-graph.png', 'assets/documents/1744266391_controller.txt', 'The Road Ahead – Our Strategic Path', 'Witness the journey from concept to completion. Our roadmap outlines key milestones and the timeline that will guide us to success.', '0000-00-00 00:00:00'),
(4, 'ai robotics', 0, 'assets/images/1744294936_chart-graph.png', 'assets/documents/1744266391_backup1.txt', 'Intelligent Machines – The AI Robotics Vision', 'Step into the world of smart automation. Our robotics roadmap is geared towards creating solutions that think, adapt, and perform.', '0000-00-00 00:00:00'),
(5, 'E-commerce', 0, 'assets/images/1744294936_chart-graph.png', 'assets/documents/1744273499_controller.txt', 'Redefining Digital Shopping – Our E-commerce Plan', 'Get ready for a seamless, next-gen shopping experience. We’re building an ecosystem that’s fast, smart, and customer-first.', '0000-00-00 00:00:00'),
(6, 'Games', 0, 'assets/images/1744294937_chart-graph.png', 'assets/documents/1744266391_database.txt', 'Play the Future – Gaming Reimagined', 'Power-packed, immersive, and built for tomorrow. Discover the concepts behind our game development universe that blends fun and tech.', '0000-00-00 00:00:00'),
(7, 'education', 0, 'assets/images/1744294937_chart-graph.png', 'assets/documents/1744295212_dummy (1).pdf', 'Learn to Lead – The Future of Education', 'Transforming how knowledge is delivered. We’re on a mission to make learning more interactive, accessible, and impactful.', '0000-00-00 00:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `payment_controls`
--

CREATE TABLE `payment_controls` (
  `id` int(11) NOT NULL,
  `wallet_name` varchar(250) DEFAULT NULL,
  `wallet_adderss` varchar(250) DEFAULT NULL,
  `privat_key` varchar(250) DEFAULT NULL,
  `secret_key` varchar(250) DEFAULT NULL,
  `payment_mode` smallint(6) NOT NULL DEFAULT 0,
  `payment_image` varchar(150) DEFAULT NULL,
  `payment_status` int(11) DEFAULT 1,
  `address_last` varchar(150) DEFAULT NULL,
  `key_last` varchar(150) DEFAULT NULL,
  `private_last` varchar(150) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `payment_controls`
--

INSERT INTO `payment_controls` (`id`, `wallet_name`, `wallet_adderss`, `privat_key`, `secret_key`, `payment_mode`, `payment_image`, `payment_status`, `address_last`, `key_last`, `private_last`) VALUES
(1, 'mexc - Payment', '0b19d291fb32002a986ef79bd238a1ca089f5855c22134cb6c9e35b405d8a61262512da632024ac6a46e3972149ca0c8', '5fb6127475c1c3ce14fd8f1cb113d113650d68e5dc1db7f7cf01926e75c79246', 'a630820540fd92980a1506693943eb3d367f843bff168416fc65737098c7a8a9a989d8ce13728af71081cec3e05f5b04', 1, '', 1, '12c1', 'KyWF', 'd561');

-- --------------------------------------------------------

--
-- Table structure for table `payment_settings`
--

CREATE TABLE `payment_settings` (
  `id` int(11) NOT NULL,
  `gateway` varchar(50) NOT NULL,
  `mode` varchar(10) DEFAULT 'sandbox',
  `client_id` text DEFAULT NULL,
  `client_secret` text DEFAULT NULL,
  `publishable_key` text DEFAULT NULL,
  `secret_key` text DEFAULT NULL,
  `status` tinyint(4) DEFAULT 1,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment_settings`
--

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `sku` varchar(100) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `offer_price` decimal(10,2) DEFAULT NULL,
  `offer_status` tinyint(1) DEFAULT NULL,
  `product_type` varchar(50) DEFAULT NULL,
  `brand_id` int(11) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `stock` int(11) DEFAULT NULL,
  `commission` decimal(10,2) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `product_image` varchar(255) DEFAULT NULL,
  `product_warranty` varchar(100) DEFAULT NULL,
  `status` tinyint(1) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `product_size` varchar(250) DEFAULT NULL,
  `offer_percentage` varchar(150) DEFAULT NULL,
  `is_physical` varchar(150) DEFAULT '0',
  `weight` varchar(150) NOT NULL DEFAULT '0',
  `length` varchar(250) NOT NULL DEFAULT '0',
  `width` varchar(150) NOT NULL DEFAULT '0',
  `height` varchar(150) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `sku`, `price`, `offer_price`, `offer_status`, `product_type`, `brand_id`, `category_id`, `stock`, `commission`, `description`, `product_image`, `product_warranty`, `status`, `created_at`, `product_size`, `offer_percentage`, `is_physical`, `weight`, `length`, `width`, `height`) VALUES
(7, 'Wireless Bluetooth Headphones ( WH-XB700BT )', 'BT-H1001', 49.99, 34.99, 1, 'physical', 6, 2, 100, 0.20, '<p>Experience powerful wireless sound with WH-XB700BT.&nbsp;<br>Features include 20-hour battery life, deep bass, hands-free calling,&nbsp;<br>fast charging, and a comfortable fit.&nbsp;<br>Ideal for music, calls, and entertainment.<br>&nbsp;</p>', '1752050435_wireless_bluetooth_headphones_3d_cortton_product_thumbnail.jpeg', '1 Year', 1, '2025-07-09 14:10:35', '', '30', '1', '0.6', '18', '15', '8'),
(8, 'Men’s Classic Cotton T-Shirt ( TS-MC100 )', 'TS-MC100-BLK', 19.99, 14.99, 1, 'physical', 3, 1, 150, 0.10, '<p><strong>Stay cool and stylish with the Men’s Classic Cotton T-Shirt – your everyday essential.</strong></p><p>100% Pure Cotton – Soft, breathable, and comfortable all day long.</p><p>&nbsp;Easy to Wash – Machine washable and wrinkle-resistant fabric.</p><p>Available in Popular Colors – Match your style with Black, White, or Navy Blue.</p><p>Perfect Fit – Regular fit with round neck and half sleeves for a casual look.</p><p>Versatile – Pairs well with jeans, shorts, or joggers.</p><p>Whether for work-from-home, weekend chill, or a casual outing — this tee has you covered.</p>', '1752050802_men_s_classic_cotton_t_shirt_ts_mc100.jpeg', '', 1, '2025-07-09 14:16:55', 'S,M,L,XL', '25', '1', '0.25', '30', '25', '3'),
(9, 'Organic Baby Skin Lotion', 'BBL-SoftCare200', 1.79, 1.61, 1, 'physical', 1, 4, 400, 0.30, '<p>Keep your baby’s skin soft, moisturized, and protected with this gentle, dermatologically-tested lotion. Made with organic ingredients, it’s perfect for daily use on delicate skin.</p><p>Organic &amp; Hypoallergenic – Free from parabens, sulfates, and synthetic fragrances.</p><p>Pediatrician Approved – Safe for newborns and infants.</p><p>Non-Greasy Formula – Absorbs quickly, leaving skin smooth and hydrated.</p><p>Mild Natural Fragrance – Keeps baby smelling fresh all day.</p><p>Convenient Packaging – 200ml bottle with easy pump.</p><p>Perfect for daily moisturizing after bath time or before bedtime.</p>', '1752051121_3d_image_organic_baby_skin_lotion_this.jpeg', '', 1, '2025-07-09 14:22:01', '', '10', '1', '0.3', '16', '6', '6'),
(10, 'Herbal Face Wash with Neem & Aloe Vera (HFW-NA150)', 'HFW-NA150', 2.49, 2.49, 0, 'physical', 2, 4, 300, 0.01, '<p>Cleanse and refresh your skin naturally with our Herbal Face Wash enriched with Neem and Aloe Vera.<br>Perfect for oily to combination skin types, it helps remove excess oil, prevent acne, and keep your skin feeling cool and clear.<br>Natural Neem Extract – Helps prevent pimples and breakouts<br>Aloe Vera – Soothes and hydrates skin<br>Gentle Formula – Free from harsh chemicals, parabens, and SLS<br>Everyday Use – Mild and refreshing, suitable for daily use</p>', '1752051384_herbal_face_wash_with_neem_aloe.jpeg', '', 1, '2025-07-09 14:26:24', '', '0', NULL, '', '', '', ''),
(11, 'Apple iPhone 14 Pro (128GB, Deep Purple)', 'IP14P-128-DP', 999.00, 899.10, 1, 'physical', 5, 11, 20, 10.00, '<p>Experience the ultimate in power and performance with the Apple iPhone 14 Pro. Designed with a beautiful ceramic shield front, aerospace-grade aluminum, and an innovative <strong>Dynamic Island</strong> display, this device redefines what a smartphone can do.</p><p><strong>48MP Triple Camera System</strong> – Pro-grade low light photography<br><strong>A16 Bionic Chip</strong> – Super-fast performance and efficiency<br><strong>All-Day Battery</strong> – Up to 23 hours of video playback<br><strong>5G Enabled</strong> – Lightning-fast internet speeds<br><strong>Face ID &amp; iOS 17</strong> – Privacy-first with seamless user experience<br>Color Options – Deep Purple, Space Black, Silver</p>', '1752051640_apple_iphone_14_pro_product_thumbnail.jpeg', '1 Year', 1, '2025-07-09 14:30:40', '', '10', NULL, '', '', '', ''),
(12, 'Samsung Galaxy S23 Ultra (256GB, Phantom Black)', 'SGS23U-256-PB', 1199.00, 1139.05, 1, 'physical', 4, 11, 80, 5.00, '<p>Redefine power and precision with the Samsung Galaxy S23 Ultra — featuring a built-in <strong>S Pen</strong>, a <strong>200MP main camera</strong>, and a sleek, durable design. Built for professionals, creators, and tech enthusiasts alike.</p><p><strong>200MP Quad Camera</strong> – Pro-grade shots in any lighting</p><p><strong>Built-in S Pen</strong> – Precision input for note-taking and creativity</p><p><strong>Snapdragon 8 Gen 2</strong> – Ultra-fast flagship performance</p><p><strong>5000mAh Battery</strong> – All-day usage with 45W super fast charging</p><p><strong>6.8\" QHD+ AMOLED</strong> – 120Hz display with Gorilla Glass Victus 2</p><p>Color Options – Phantom Black, Green, Cream, Lavender</p>', '1752052379_samsung_galaxy_s23_ultra_product_thumbnail.jpeg', '1 Year', 1, '2025-07-09 14:42:59', '', '5', '1', '0.23', '16.3', '7.8', '0.9'),
(13, 'Adidas Lite Racer 3.0 Running Shoes', 'AD-LR3-BLK-M', 74.99, 59.99, 1, 'physical', 2, 18, 30, 4.00, '<p>Step into all-day comfort and lightweight speed with the <strong>Adidas Lite Racer 3.0</strong> — inspired by classic runners but built for modern street style.</p><p><strong>Cloudfoam Midsole</strong> for ultra-light cushioning<br><strong>Mesh Upper</strong> allows breathability and flexibility<br><strong>Rubber Outsole</strong> provides grip on various surfaces<br>Ideal for running, gym, walking &amp; casual use<br>Color: Core Black / Grey Three / White<br>Lace closure with iconic 3-stripe Adidas design</p>', '1752052801_adidas_lite_racer_3_0_running_shoes.jpeg', '', 1, '2025-07-09 14:50:01', '', '20', '1', '0.9', '33', '19', '12'),
(14, 'Sony EOS 1500D DSLR Camera (18–55mm Lens)', 'CN-1500D-KIT', 499.00, 449.10, 1, 'physical', 6, 13, 40, 0.30, '<p>Capture crisp, high-quality photos and Full HD videos with the <strong>Sony EOS 1500D</strong>, the perfect entry-level DSLR for budding photographers and content creators.</p><p>???? <strong>24.1MP APS-C CMOS Sensor</strong> for high-resolution photography</p><p>???? <strong>Full HD 1080p Video Recording</strong> at 30fps</p><p>???? <strong>DIGIC 4+ Image Processor</strong> for sharp images</p><p>???? Built-in Wi-Fi &amp; NFC for quick sharing</p><p>???? <strong>9-point AF system</strong> with optical viewfinder</p><p>???? Includes 18–55mm f/3.5–5.6 IS II lens</p>', '1752053568_sony_eos_1500d_dslr_camera_18_55mm_lens.jpeg', '2 Years', 1, '2025-07-09 15:02:48', '', '10', '1', '1.45 kg', '22', '18', '15'),
(15, ' Philips X-tremeVision G-Force ', 'PH-XTR-H4-GF', 39.99, 37.59, 0, 'physical', 8, 9, 48, 0.33, '<p>Upgrade your vehicle\'s night visibility with <strong>Philips X-tremeVision G-Force bulbs</strong>. Designed for performance and durability, these bulbs offer up to <strong>130% more brightness</strong> than standard halogen bulbs and a strong filament for tough driving conditions.</p><p><strong>Up to 130% Brighter Light</strong> – for safer night driving</p><p><strong>H4 Type – 60/55W, 12V</strong> – widely compatible</p><p><strong>Vibration Resistance</strong> – suitable for rough roads</p><p><strong>Plug-and-Play Installation</strong> – no modification needed</p><p>Road-legal and ECE certified</p>', '1752053927_philips_x_tremevision_g_force_h4_bulbs_for_car.jpeg', '1 Year', 1, '2025-07-09 15:08:47', '', '6', '1', '0.15', '12', '6', '6'),
(16, 'Prestige Baby Food Steamer & Blender', 'PR-BFSB-2IN1-BL', 79.99, 75.19, 0, 'physical', 7, 6, 40, 0.22, '<p>Make healthy and homemade baby food in minutes with the <strong>Prestige 2-in-1 Baby Food Steamer &amp; Blender</strong>. Designed for modern parents who want <strong>safe</strong>, <strong>nutritious</strong>, and <strong>convenient feeding</strong> solutions for their babies.</p><p><strong>Steam &amp; Blend in One Jar</strong> – retain nutrients while preparing</p><p><strong>BPA-Free Materials</strong> – completely safe for infants</p><p><strong>One-Touch Operation</strong> – easy and quick to use</p><p><strong>Low Noise</strong> – ideal for babies’ sensitive ears</p><p><strong>Detachable Parts</strong> – easy to clean and dishwasher safe</p><p>Voltage: 220-240V, 50Hz (Universal Plug Adapter included)</p>', '1752054185_prestige_baby_food_steamer_blender_3d.jpeg', '1 Year', 1, '2025-10-13 09:33:29', '', '6', '1', '1.5', '25', '22', '18');

-- --------------------------------------------------------

--
-- Table structure for table `product_categories`
--

CREATE TABLE `product_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `slug` varchar(150) DEFAULT NULL,
  `status` tinyint(4) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `image` varchar(150) DEFAULT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_categories`
--

INSERT INTO `product_categories` (`id`, `name`, `parent_id`, `slug`, `status`, `sort_order`, `image`, `description`) VALUES
(1, 'Fashion', NULL, 'fashion', 1, 0, 'uploads/category/1752049235_fashion_cortoon_3d_image.jpeg', '<p>Stay stylish with the latest trends in men’s, women’s, and kids’ fashion.<br>From casual wear to statement pieces, find your perfect look for every occasion.</p>'),
(2, 'Electronics', NULL, 'electronics', 1, 0, 'uploads/category/1752049184_electronics_3d_cortoon_image.jpeg', '<p>Discover the latest in electronics with cutting-edge technology and performance.<br>Shop gadgets, devices, and appliances that make life easier and smarter.</p>'),
(3, 'Home & Kitchen', NULL, 'home-kitchen', 1, 0, 'uploads/category/1752049129_home_kitchen_3d_cortton_style.jpeg', '<p>Upgrade your living space with smart home and kitchen essentials.<br>From cookware to decor, find everything to style and simplify your home.</p>'),
(4, 'Beauty & Personal Care', NULL, 'beauty-personal-care', 1, 0, 'uploads/category/1752049059_3d_cortoon_beauty_personal_care_image.jpeg', '<p>Discover top beauty and grooming products for your daily routine.<br>From skincare to haircare, glow up with trusted personal care essentials.</p>'),
(5, 'Health & Wellness', NULL, 'health-wellness', 1, 0, 'uploads/category/1752049015_health_wellness_3d_cortoon_image.jpeg', '<p>Prioritize your well-being with trusted health and wellness essentials.<br>From supplements to self-care, find everything for a healthier lifestyle.</p>'),
(6, 'Baby & Kids', NULL, 'baby-kids', 1, 0, 'uploads/category/1752048964_baby_kids_category_images_genrate_3d.jpeg', '<p>Everything your little ones need — from newborn essentials to playful toys.<br>Safe, comfortable, and fun products for babies and growing kids.</p>'),
(7, 'Sports & Outdoors', NULL, 'sports-outdoors', 1, 0, 'uploads/category/1752048860_sports_outdoors_3d_cortoon_image.jpeg', '<p>Fuel your active lifestyle with top-notch sports gear and outdoor essentials.<br>From fitness equipment to camping tools, get ready for every adventure.</p>'),
(8, 'Books & Stationery', NULL, 'books-stationery', 1, 0, 'uploads/category/1752048808_books_stationery_3d_cortoon_image.jpeg', '<p>Dive into a world of knowledge with books for every reader and age.<br>Plus, shop quality stationery for school, office, and creative needs.</p>'),
(9, 'Automotive', NULL, 'automotive', 1, 0, 'uploads/category/1752048754_3d_cortoon_image_for_automotive_category.jpeg', '<p>Gear up with top-quality automotive parts, tools, and accessories.<br>Everything you need to maintain, upgrade, and style your ride.</p>'),
(10, 'Grocery & Essentials', NULL, 'grocery-essentials', 1, 0, 'uploads/category/1752048692_grocery_essentials_3d_cortoon.jpeg', '<p>Stock up on daily essentials and fresh groceries at great prices. From pantry staples to personal care, everything you need in one place.</p>'),
(11, 'Mobiles', 2, 'mobiles', 1, 0, 'uploads/category/1752048515_mobile_phone_3d_image_for_3d_cortoon.jpeg', '<p>Stay connected with the latest smartphones packed with smart features.<br>Shop top brands offering powerful performance, sleek design, and great value.</p>'),
(12, 'Laptops', 2, 'laptops', 1, 0, 'uploads/category/1752048448_laptops_3d_cortoon.jpeg', '<p>Explore powerful and portable laptops for work, gaming, and everyday use.<br>Choose from top brands with the latest features and performance.</p>'),
(13, 'Cameras', 2, 'cameras', 1, 0, 'uploads/category/1752048403_electronic_cameras_image_cortoon_3d.jpeg', '<p>Capture life’s moments in stunning detail with our range of cameras.<br>From DSLRs to action cams, find the perfect gear for every shot.</p>'),
(14, 'Accessories', 2, 'accessories', 1, 0, 'uploads/category/1752048335_electronic_accessories_category_image_3d_cortoon.jpeg', '<p>Enhance your tech experience with the latest electronic accessories.<br>Shop chargers, cables, earbuds, and more — built for performance and style.</p>'),
(15, 'Men', 1, 'men', 1, 0, 'uploads/category/1752048274_man_professonal_dress_category_cortoon_image_3d.jpeg', '<p>Discover adorable and comfortable dresses for every little adventure.<br>Perfect styles for playtime, parties, and everything in between.</p>'),
(16, 'Women', 1, 'women', 1, 0, 'uploads/category/1752048135_20_year_woman_dress_category_image_3d.jpeg', '<p>Discover adorable and comfortable dresses for every little adventure.<br>Perfect styles for playtime, parties, and everything in between.</p>'),
(17, 'Kids', 1, 'kids', 1, 0, 'uploads/category/1752048080_kids_dress_category_image_3d.jpeg', '<p>Discover adorable and comfortable dresses for every little adventure.<br>Perfect styles for playtime, parties, and everything in between.</p>'),
(18, 'Footwear', 1, 'footwear', 1, 0, 'uploads/category/1752048018_footware_category_3d_image_for_my_mlm.jpeg', '<p>Step into comfort and style with our latest footwear collection.<br>From casual shoes to formal wear, find the perfect fit for every occasion.</p>');

-- --------------------------------------------------------

--
-- Table structure for table `product_images`
--

CREATE TABLE `product_images` (
  `id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_images`
--

INSERT INTO `product_images` (`id`, `product_id`, `image`) VALUES
(4, 3, '1751380080_crypto_landing.png'),
(5, 3, '1751380080_gym_landing1.png'),
(6, 3, '1751380080_mlm_landing.png'),
(7, 3, '1751380449_agency_landing.png'),
(8, 4, '1751613489_before-update.png'),
(9, 4, '1751613489_Screenshot_2025-05-31_110128.png'),
(10, 4, '1751613489_Screenshot_2025-05-31_110215.png'),
(11, 5, '1751617298_Screenshot_2025-05-31_1101281.png'),
(12, 5, '1751617298_Screenshot_2025-05-31_110215.png'),
(13, 6, '1751627668_mlm-software-free-trial.png'),
(14, 6, '1751627668_mlm-demo.png'),
(15, 6, '1751627668_fenizo-mlm-software-price1.png'),
(16, 6, '1751627668_best-mlm-software-demo.png'),
(17, 7, '1752050435_wireless_bluetooth_headphone_product_image_left_and_(2).jpeg'),
(18, 7, '1752050435_wireless_bluetooth_headphone_product_image_left_and_(1).jpeg'),
(19, 7, '1752050435_wireless_bluetooth_headphone_product_image_left_and.jpeg'),
(20, 8, '1752050802_men_s_classic_cotton_t_shirt_ts_mc100_(2).jpeg'),
(21, 8, '1752050802_men_s_classic_cotton_t_shirt_ts_mc100_(1).jpeg'),
(22, 8, '1752050802_men_s_classic_cotton_t_shirt_ts_mc1001.jpeg'),
(23, 9, '1752051121_3d_image_organic_baby_skin_lotion_this_(2).jpeg'),
(24, 9, '1752051121_3d_image_organic_baby_skin_lotion_this_(1).jpeg'),
(25, 9, '1752051121_3d_image_organic_baby_skin_lotion_this1.jpeg'),
(26, 10, '1752051384_herbal_face_wash_with_neem_aloe_(2).jpeg'),
(27, 10, '1752051384_herbal_face_wash_with_neem_aloe_(1).jpeg'),
(28, 10, '1752051384_herbal_face_wash_with_neem_aloe1.jpeg'),
(29, 11, '1752051640_apple_iphone_14_pro_product_thumbnail_(3).jpeg'),
(30, 11, '1752051640_apple_iphone_14_pro_product_thumbnail_(2).jpeg'),
(31, 11, '1752051640_apple_iphone_14_pro_product_thumbnail_(1).jpeg'),
(32, 11, '1752051640_apple_iphone_14_pro_product_thumbnail1.jpeg'),
(33, 12, '1752052246_samsung_galaxy_s23_ultra_product_thumbnail_(3)1.jpeg'),
(34, 12, '1752052246_samsung_galaxy_s23_ultra_product_thumbnail_(2).jpeg'),
(35, 12, '1752052246_samsung_galaxy_s23_ultra_product_thumbnail_(1).jpeg'),
(36, 12, '1752052246_samsung_galaxy_s23_ultra_product_thumbnail.jpeg'),
(37, 13, '1752052801_adidas_lite_racer_3_0_running_shoes_(2).jpeg'),
(38, 13, '1752052801_adidas_lite_racer_3_0_running_shoes_(1).jpeg'),
(39, 13, '1752052801_adidas_lite_racer_3_0_running_shoes1.jpeg'),
(40, 14, '1752053568_sony_eos_1500d_dslr_camera_18_55mm_lens_(2).jpeg'),
(41, 14, '1752053568_sony_eos_1500d_dslr_camera_18_55mm_lens_(1).jpeg'),
(42, 14, '1752053568_sony_eos_1500d_dslr_camera_18_55mm_lens1.jpeg'),
(43, 15, '1752053927_philips_x_tremevision_g_force_h4_bulbs_for_car_(3).jpeg'),
(44, 15, '1752053927_philips_x_tremevision_g_force_h4_bulbs_for_car_(2).jpeg'),
(45, 15, '1752053927_philips_x_tremevision_g_force_h4_bulbs_for_car_(1).jpeg'),
(46, 15, '1752053927_philips_x_tremevision_g_force_h4_bulbs_for_car1.jpeg'),
(47, 16, '1752054185_prestige_baby_food_steamer_blender_3d_(3).jpeg'),
(48, 16, '1752054185_prestige_baby_food_steamer_blender_3d_(2).jpeg'),
(49, 16, '1752054185_prestige_baby_food_steamer_blender_3d_(1).jpeg');

-- --------------------------------------------------------

--
-- Table structure for table `product_meta`
--

CREATE TABLE `product_meta` (
  `id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `meta_key` varchar(100) DEFAULT NULL,
  `meta_value` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_meta`
--

INSERT INTO `product_meta` (`id`, `product_id`, `meta_key`, `meta_value`) VALUES
(1, 1, 'size', 'S'),
(2, 1, 'asdf', 'asdf'),
(3, 1, 'asdf', 'asdf'),
(4, 2, 'size', 'S'),
(5, 2, 'asdf', 'asdf'),
(6, 1, 'size', 'M'),
(7, 1, 'size', 'L'),
(8, 1, 'size', 'XL'),
(9, 1, 'asdf', 'asdf'),
(10, 1, 'asdf', 'asdfasdf'),
(11, 1, 'asdf', 'asdfasdf'),
(42, 3, 'asdf', 'asdf'),
(43, 3, 'asdfasdf', 'asdfasdfasdf'),
(68, 4, 'asdf', 'asdf'),
(69, 4, 'asdf', 'asdf'),
(70, 4, 'asdf', 'asdf'),
(71, 5, '23', 'asdf'),
(72, 6, 'asd', 'fasdf'),
(73, 6, 'asdf', 'asdf'),
(74, 6, 'asdfasdf', 'asdfas'),
(75, 6, 'dfasdfasdf', 'asdasdf'),
(86, 7, 'Bluetooth Version', '5.3'),
(87, 7, 'Battery Life', 'Up to 20 hours'),
(88, 7, 'Charging Time', '1.5 hours'),
(89, 7, 'Noise Cancellation', 'Yes'),
(90, 7, 'Driver Size', '40mm'),
(91, 7, 'Warranty', '1 Year'),
(92, 7, 'Weight', '0.6 kg'),
(93, 7, 'Compatible Devices', 'Android, iOS, Laptop, PC'),
(94, 7, 'Included in Box', 'Headphones, Charging Cable, Manual'),
(95, 7, 'Model', 'WH-XB700BT-BLK (with color variation)'),
(110, 8, 'Fabric', '100% Cotton'),
(111, 8, 'Fit Type', 'Regular Fit'),
(112, 8, 'Sleeve Type', 'Half Sleeve'),
(113, 8, 'Pattern', 'Solid'),
(114, 8, 'Ideal For', 'Men'),
(115, 8, 'Occasion', 'Casual, Daily Wear'),
(116, 8, 'Country of Origin', 'India'),
(123, 9, 'Suitable Age', '0+ months'),
(124, 9, 'Skin Type', 'All (sensitive-safe)'),
(125, 9, 'Usage', 'Face & Body'),
(126, 9, 'Shelf Life', '24 months'),
(127, 9, 'Dermatologist Tested', 'Yes'),
(128, 9, 'Organic Certified', 'Yes'),
(133, 10, 'Suitable For', 'Men & Women'),
(134, 10, 'Skin Type', 'Oily to Combination'),
(135, 10, 'Key Ingredients', 'Neem, Aloe Vera'),
(136, 10, 'Shelf Life', '24 months'),
(143, 11, 'Display', '6.1\" Super Retina XDR'),
(144, 11, 'Processor', 'A16 Bionic'),
(145, 11, 'Operating System', 'iOS 17'),
(146, 11, 'Camera', '48MP + 12MP + 12MP'),
(147, 11, 'Water Resistance', 'IP68 Certified'),
(148, 11, 'Warranty', '1 Year Manufacturer'),
(170, 12, 'Display', '6.8\" Edge QHD+ AMOLED'),
(171, 12, 'Processor', 'Snapdragon 8 Gen 2 (4nm)'),
(172, 12, 'Operating System', 'Android 13 (One UI 5.1)'),
(173, 12, 'Main Camera', '200MP + 12MP + 10MP + 10MP'),
(174, 12, 'Water Resistance', 'IP68 Certified'),
(175, 12, 'Country of Origin', 'South Korea / Vietnam'),
(176, 12, 'Warranty', '1 Year Manufacturer'),
(184, 13, 'Type', 'Physical Product'),
(185, 13, 'Weight', '0.9'),
(186, 13, 'Dimensions', '33 × 19 × 12 cm'),
(187, 13, 'Material', 'Mesh Upper + Rubber Sole'),
(188, 13, 'Closure', 'Lace-Up'),
(189, 13, 'Suitable For', 'Daily Wear, Training, Travel'),
(190, 13, 'Brand Origin', 'Germany'),
(196, 14, 'Screen', '3.0\" TFT LCD (Live View)'),
(197, 14, 'Connectivity', 'Wi-Fi, NFC, micro USB'),
(198, 14, 'Battery Life', '500 shots per charge'),
(199, 14, 'Format Support', 'JPEG, RAW, MP4'),
(200, 14, 'Storage', 'SD / SDHC / SDXC'),
(207, 15, 'Voltage / Wattage', '12V / 60W (High) / 55W (Low)'),
(208, 15, 'Base Type', 'P43t (H4)'),
(209, 15, 'Color Temperature', '3700K Warm White'),
(210, 15, 'Certification', 'ECE R37 / DOT Approved'),
(211, 15, 'Country of Origin', 'Germany / Poland'),
(212, 15, 'Warranty', '1 Year Manufacturer Warranty'),
(218, 16, 'Age Group', '6+ Months'),
(219, 16, 'Material', 'BPA-Free Plastic'),
(220, 16, 'Safety Certifications', 'CE Certified, ISO Compliant'),
(221, 16, 'Warranty', '1 Year Prestige Warranty'),
(222, 16, 'Return Policy', '7-Day Replacement');

-- --------------------------------------------------------

--
-- Table structure for table `product_reviews`
--

CREATE TABLE `product_reviews` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `rating` int(11) DEFAULT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `review` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `quick_tasks`
--

CREATE TABLE `quick_tasks` (
  `id` int(11) NOT NULL,
  `code` varchar(30) NOT NULL,
  `title` varchar(100) NOT NULL,
  `subtitle` varchar(255) DEFAULT NULL,
  `icon` varchar(60) DEFAULT NULL,
  `icon_bg` varchar(30) DEFAULT NULL,
  `icon_color` varchar(30) DEFAULT NULL,
  `reward_usd` decimal(12,2) NOT NULL DEFAULT 0.00,
  `action_type` enum('claim','verify') NOT NULL DEFAULT 'claim',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `quick_tasks`
--

INSERT INTO `quick_tasks` (`id`, `code`, `title`, `subtitle`, `icon`, `icon_bg`, `icon_color`, `reward_usd`, `action_type`, `is_active`, `sort_order`) VALUES
(1, 'checkin', 'Daily Check-in', 'Claim your streak bonus for today', 'ph-calendar-check', NULL, NULL, 10.00, 'claim', 1, 1),
(2, 'share', 'Share on Social Media', 'Post your referral link on Twitter/X', 'ph-megaphone', '#e0f2fe', '#0369a1', 25.00, 'verify', 1, 2);

-- --------------------------------------------------------

--
-- Table structure for table `rank_config`
--

CREATE TABLE `rank_config` (
  `id` int(11) NOT NULL,
  `rank_name` varchar(250) DEFAULT NULL,
  `rank_status` int(11) NOT NULL DEFAULT 1,
  `left_leg_investment` varchar(250) DEFAULT NULL,
  `right_leg_investment` varchar(250) DEFAULT NULL,
  `pairs_needed` int(11) NOT NULL DEFAULT 0,
  `directs_needed` int(11) NOT NULL DEFAULT 0,
  `pair_value` decimal(12,2) NOT NULL DEFAULT 1.00,
  `cycle_type` enum('WEEK','MONTH') NOT NULL DEFAULT 'WEEK',
  `team_volume_need` decimal(12,2) NOT NULL DEFAULT 0.00,
  `create_date` datetime DEFAULT NULL,
  `rank_bonus` varchar(250) DEFAULT NULL,
  `rank_bonus_type` int(11) DEFAULT 1,
  `rank_eligibel_amt` varchar(250) DEFAULT '0',
  `rank_order` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rank_config`
--

INSERT INTO `rank_config` (`id`, `rank_name`, `rank_status`, `left_leg_investment`, `right_leg_investment`, `pairs_needed`, `directs_needed`, `pair_value`, `cycle_type`, `team_volume_need`, `create_date`, `rank_bonus`, `rank_bonus_type`, `rank_eligibel_amt`, `rank_order`) VALUES
(1, 'Executive', 1, '100', '100.1', 0, 0, 1.00, 'WEEK', 0.00, '2025-03-27 13:47:49', '500', 0, '5000', 1),
(3, 'Elite', 1, '200', '200', 0, 0, 1.00, 'WEEK', 0.00, '2025-03-27 13:48:19', '800.0000', 0, '0', 2);

-- --------------------------------------------------------

--
-- Table structure for table `shipping_zones`
--

CREATE TABLE `shipping_zones` (
  `id` int(11) NOT NULL,
  `pincode` varchar(10) NOT NULL,
  `zone_name` varchar(100) DEFAULT NULL,
  `shipping_charge` decimal(10,2) NOT NULL DEFAULT 0.00,
  `cod_available` tinyint(1) NOT NULL DEFAULT 0,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `shipping_zones`
--

INSERT INTO `shipping_zones` (`id`, `pincode`, `zone_name`, `shipping_charge`, `cod_available`, `status`, `created_at`, `updated_at`) VALUES
(3, '560001', 'Bangalore Central', 2.50, 1, 1, '2025-07-09 15:15:04', '2025-07-09 15:15:04'),
(4, '01000', 'Mexico City – Álvaro Obregón', 4.99, 1, 1, '2025-07-09 15:15:38', '2025-08-29 12:32:17');

-- --------------------------------------------------------

--
-- Table structure for table `site_settings`
--

CREATE TABLE `site_settings` (
  `id` int(11) NOT NULL,
  `settings_type` varchar(250) DEFAULT NULL,
  `settings_name` varchar(250) DEFAULT NULL,
  `settings_value` varchar(250) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `site_settings`
--

INSERT INTO `site_settings` (`id`, `settings_type`, `settings_name`, `settings_value`) VALUES
(1, 'captcha', 'status', '0'),
(2, 'captcha', 'sitekey', 'test'),
(3, 'captcha', 'secretkey', 'test'),
(4, 'image', 'logo', 'logo-whites.png'),
(5, 'image', 'favicon', 'logo-whites.png'),
(6, 'image', 'mobile_logo', 'custom-1.png'),
(7, 'image', 'footer_logo', 'logo-whites.png'),
(8, 'image', 'dark_logo', 'logo-whites.png'),
(9, 'image', 'dark_mobile_logo', 'custom-1.png'),
(10, 'image', 'dark_footer_logo', 'logo-whites.png'),
(11, 'image', 'og-img', 'og-img.jpg'),
(12, 'meta-settings', 'site-title', 'Fenizo MLM Software'),
(13, 'meta-settings', 'site-url', 'https://fenizotechnologies.com'),
(14, 'meta-settings', 'site-keyword', 'Fenizo MLM Software – Best Multi-Level Marketing Solution for Your Business'),
(15, 'meta-settings', 'site-description', 'Fenizo MLM Software offers a powerful, user-friendly platform for network marketing businesses. Manage members, track commissions, grow your downline, and scale your business with ease. Start your MLM journey with Fenizo today!'),
(16, 'meta-settings', 'site-name', 'Fenizo MLM Software'),
(17, 'company', 'email', 'mlm@fenizotechnologies.com'),
(18, 'company', 'contact_number', '+91 944 321 8385'),
(19, 'company', 'address', 'plot no:77, Nehru St, Sathyamoorthy Nagar, Madurai, Tamil Nadu 625016'),
(20, 'site_settings', 'landing_status', '1'),
(21, 'site_settings', 'kyc_status', '0'),
(22, 'site_settings', 'email_verify', '1'),
(23, 'site_settings', 'two_fa_status', '0'),
(24, 'site_settings', 'register_status', '1'),
(25, 'site_settings', 'allow_login', '1'),
(26, 'site_settings', 'unique_ip', '0'),
(27, 'site_settings', 'unique_mobile', '1'),
(28, 'site_settings', 'unique_email', '1'),
(29, 'site_settings', 'allow_referral_only', '0'),
(30, 'withdraw_settings', 'min_withdraw', '20'),
(31, 'withdraw_settings', 'max_withdraw', '1000'),
(34, 'withdraw_settings', 'withdraw_fee', '0'),
(35, 'withdraw_settings', 'withdraw_amount_type', '1'),
(36, 'withdraw_settings', 'withdraw_monthly_limit', '10000.00'),
(37, 'withdraw_settings', 'withdraw_status', '0'),
(38, 'withdraw_settings', 'withdraw_daily_limit', '500.00'),
(39, 'withdraw_settings', 'auto_withdraw', '1'),
(40, 'withdraw_settings', 'withdraw_notification_user', '1'),
(41, 'withdraw_settings', 'withdraw_notification_admin', '1'),
(42, 'user_settings', 'twofa_login', '0'),
(43, 'user_settings', 'twofa_editprofile', '1'),
(44, 'user_settings', 'twofa_withdraw', '1'),
(45, 'user_settings', 'min_password_length', '4'),
(46, 'user_settings', 'max_password_length', '8'),
(47, 'token_withdraw_settings', 'min_withdraw', '1'),
(48, 'token_withdraw_settings', 'max_withdraw', '20000'),
(49, 'token_withdraw_settings', 'withdraw_fee', '5'),
(50, 'token_withdraw_settings', 'withdraw_amount_type', '1'),
(51, 'token_withdraw_settings', 'withdraw_monthly_limit', '0.00'),
(52, 'token_withdraw_settings', 'withdraw_status', '1'),
(53, 'token_withdraw_settings', 'withdraw_daily_limit', '0.00'),
(54, 'token_withdraw_settings', 'auto_withdraw', '1'),
(55, 'token_withdraw_settings', 'withdraw_notification_user', '1'),
(56, 'token_withdraw_settings', 'withdraw_notification_admin', '1'),
(57, 'user_settings', 'twofa_internel_transfer', '1'),
(58, 'swap_settings', 'min_swap', '1'),
(59, 'swap_settings', 'max_swap', '10000.0000'),
(60, 'swap_settings', 'swap_fee', '5'),
(61, 'swap_settings', 'swap_amount_type', '1'),
(62, 'swap_settings', 'swap_status', '1'),
(63, 'swap_settings', 'swap_daily_limit', '0.0000'),
(64, 'swap_settings', 'swap_notification_user', '0'),
(65, 'swap_settings', 'swap_notification_admin', '0'),
(66, 'transfer_settings', 'min_transfer', '400'),
(67, 'transfer_settings', 'max_transfer', '10000.00'),
(68, 'transfer_settings', 'transfer_fee', '0'),
(69, 'transfer_settings', 'transfer_amount_type', '1'),
(70, 'transfer_settings', 'transfer_status', '1'),
(71, 'transfer_settings', 'transfer_daily_limit', '0.00'),
(72, 'transfer_settings', 'transfer_notification_user', '0'),
(73, 'transfer_settings', 'transfer_notification_admin', '0');

-- --------------------------------------------------------

--
-- Table structure for table `sliders_img`
--

CREATE TABLE `sliders_img` (
  `id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `image` varchar(255) NOT NULL,
  `status` int(11) NOT NULL,
  `created_date` datetime DEFAULT NULL,
  `type` varchar(50) DEFAULT NULL,
  `label_text` varchar(100) DEFAULT NULL,
  `heading` text DEFAULT NULL,
  `sub_heading` text DEFAULT NULL,
  `button_text` varchar(100) DEFAULT NULL,
  `button_url` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `sliders_img`
--

INSERT INTO `sliders_img` (`id`, `title`, `description`, `image`, `status`, `created_date`, `type`, `label_text`, `heading`, `sub_heading`, `button_text`, `button_url`) VALUES
(11, 'Fashion sale for women', NULL, 'assets/images/sliders/1752061523_banner_1.jpg', 1, '2025-07-09 17:18:10', 'image', '50% Off', 'Fashion sale<br>for women\'s', 'Elevate your every day. Style that speaks volumes.', 'Shop Now', 'category/womens-fashion'),
(12, 'Cosmetics sale Men', NULL, 'assets/images/sliders/1752061841_banner_2.jpg', 1, '2025-07-09 17:23:22', 'image', '35% Off', 'Cosmetics sale <br>for Men\'s', 'Wear the change. Fashion that feels good', 'Shop Now', ''),
(13, 'Fashion sale', NULL, 'assets/images/sliders/1752061940_banner_3.jpg', 1, '2025-07-09 17:22:20', 'image', '44% off', 'Fashion sale <br>for Children\'s', 'Wear the change. Fashion that feels good.', 'Shop Now', '');

-- --------------------------------------------------------

--
-- Table structure for table `sociallinks`
--

CREATE TABLE `sociallinks` (
  `id` int(11) NOT NULL,
  `social_name` varchar(255) NOT NULL,
  `link` varchar(255) NOT NULL,
  `dashboard_status` int(11) DEFAULT 0,
  `social_label` varchar(250) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `sociallinks`
--

INSERT INTO `sociallinks` (`id`, `social_name`, `link`, `dashboard_status`, `social_label`) VALUES
(8, 'Facebook', 'https://www.facebook.com/profile.php?id=61558413025006', 1, 'Facebook'),
(9, 'twitter', 'https://x.com/fenizomlm', 1, 'Twitter (x)'),
(10, 'Instagram', 'https://www.instagram.com/fenizotechnologies/', 1, 'Instagram'),
(12, 'telegram', 'http://telegram.me/+919443218385', 1, 'Telegram'),
(13, 'youtube', 'https://www.youtube.com/@FenizoTechnologies', 1, 'youtube'),
(14, 'whatsapp', 'https://wa.me/919443218385', 1, 'whatsapp');

-- --------------------------------------------------------

--
-- Table structure for table `support`
--

CREATE TABLE `support` (
  `id` int(11) NOT NULL,
  `ticket_id` varchar(250) NOT NULL,
  `user_id` int(11) NOT NULL,
  `date` datetime DEFAULT current_timestamp(),
  `status` int(11) NOT NULL DEFAULT 0,
  `discription` varchar(250) DEFAULT NULL,
  `ticket_status` varchar(250) DEFAULT NULL,
  `subject` varchar(250) DEFAULT NULL,
  `email` varchar(50) DEFAULT NULL,
  `files` varchar(250) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `support`
--

INSERT INTO `support` (`id`, `ticket_id`, `user_id`, `date`, `status`, `discription`, `ticket_status`, `subject`, `email`, `files`) VALUES
(1, 'TICKET_ykMOH', 3, '2023-10-05 00:00:00', 1, '    testning', 'urgent', 'Forgot Passowrd', 'testers@mail.com', NULL),
(4, 'TIC-53-NN', 4, '2025-04-08 08:24:01', 2, 'my router is not working', NULL, 'i have a issue', 'adroxtest1@gmail.com', NULL),
(5, 'TIC-27-ZN', 4, '2025-04-08 09:19:59', 0, 'my router is not working', NULL, 'i have a issue', 'adroxtest1@gmail.com', NULL),
(6, 'TIC-15-JQ', 4, '2025-04-08 09:23:17', 0, 'my router is not working', NULL, 'i have a issue', 'adroxtest1@gmail.com', NULL),
(7, 'TIC-47-KZ', 4, '2025-04-08 09:32:43', 0, 'my router is not working', NULL, 'i have a issue', 'adroxtest1@gmail.com', ''),
(8, 'TIC-15-MF', 4, '2025-04-08 09:34:01', 0, 'my router is not working', NULL, 'i have a issue', 'adroxtest1@gmail.com', ''),
(9, 'TIC-23-XQ', 4, '2025-04-08 09:38:51', 0, 'my router is not working', NULL, 'i have a issue', 'adroxtest1@gmail.com', 'https://adrox.ai/./assets/images/support/image_1744105131_67f4eeaba9adb.jpeg'),
(10, 'TIC-63-XX', 4, '2025-04-08 09:41:11', 0, 'my router is not working', NULL, 'i have a issue', 'adroxtest1@gmail.com', ''),
(11, 'TIC-98-CW', 4, '2025-04-08 09:41:18', 0, 'my router is not working', NULL, 'i have a issue', 'adroxtest1@gmail.com', 'https://adrox.ai/./assets/images/support/image_1744105278_67f4ef3eea9f6.png'),
(12, 'TIC-50-BV', 4, '2025-04-08 11:28:27', 0, 'my router is not working', NULL, 'i have a issue', 'adroxtest1@gmail.com', 'https://adrox.ai/./assets/images/support/image_1744111707_67f5085b2281a.png'),
(13, 'TIC-21-UH', 4, '2025-04-08 11:28:37', 0, 'my router is not working', NULL, 'i have a issue', 'adroxtest1@gmail.com', 'https://adrox.ai/./assets/images/support/image_1744111717_67f5086517260.png'),
(14, 'TIC-60-PF', 4, '2025-04-08 11:29:10', 0, 'my router is not  ', NULL, 'i have a issue', 'adroxtest1@gmail.com', 'https://adrox.ai/./assets/images/support/image_1744111750_67f5088620778.png'),
(15, 'TIC-10-EX', 4, '2025-04-08 11:35:58', 0, 'hii test', NULL, 'Hello', 'adroxtest1@gmail.com', ''),
(16, 'TIC-99-PA', 4, '2025-04-08 11:38:04', 0, 'my router is not working', NULL, 'i have a issue', 'adroxtest1@gmail.com', 'https://adrox.ai/./assets/images/support/image_1744112284_67f50a9c7e1c6.jpeg'),
(17, 'TIC-15-VU', 4, '2025-04-08 11:38:34', 0, 'tedt2', NULL, 'hi', 'adroxtest1@gmail.com', ''),
(18, 'TIC-90-BU', 4, '2025-04-08 11:40:18', 0, 'tedt2', NULL, 'hi', 'adroxtest1@gmail.com', ''),
(19, 'TIC-72-VK', 4, '2025-04-08 11:42:13', 0, 'test3', NULL, 'Hi', 'adroxtest1@gmail.com', ''),
(20, 'TIC-84-ZU', 4, '2025-04-08 11:48:33', 0, 'test5', NULL, 'hi', 'adroxtest1@gmail.com', ''),
(21, 'TIC-98-ZC', 4, '2025-04-08 11:50:30', 0, 'test6', NULL, 'hi', 'adroxtest1@gmail.com', ''),
(22, 'TIC-26-RD', 4, '2025-04-08 11:50:52', 0, 'test7', NULL, 'hi', 'adroxtest1@gmail.com', 'https://adrox.ai/./assets/images/support/image_1744113052_67f50d9ca4ccf.jpg'),
(23, 'TIC-66-SC', 4, '2025-04-08 11:52:21', 0, 'test image', NULL, 'image', 'adroxtest1@gmail.com', 'https://adrox.ai/./assets/images/support/image_1744113141_67f50df5d3c9e.jpg'),
(24, 'TIC-76-LG', 4, '2025-04-08 11:55:00', 0, 'Image test 2', NULL, 'Image', 'adroxtest1@gmail.com', ''),
(25, 'TIC-19-MO', 4, '2025-04-08 11:58:32', 0, 'testing image', NULL, 'image 3', 'adroxtest1@gmail.com', ''),
(26, 'TIC-73-AQ', 4, '2025-04-08 11:59:21', 0, 'image', NULL, 'noo', 'adroxtest1@gmail.com', 'https://adrox.ai/./assets/images/support/image_1744113561_67f50f998f905.jpg'),
(27, 'TIC-82-FS', 4, '2025-04-08 13:28:30', 0, 'image uplodeung', NULL, 'testing pro', 'adroxtest1@gmail.com', 'https://adrox.ai/./assets/images/support/image_1744118910_67f5247e0bfde.jpg'),
(28, 'TIC-72-AR', 78, '2025-04-17 03:58:14', 0, 'inwant', NULL, 'abcd', 'ts463637@gmail.com', ''),
(29, 'TIC-34-JQ', 24, '2025-04-30 09:58:54', 0, 'yhh', NULL, 'hh', 'criptocoindeo@gmail.com', ''),
(30, 'TICKET_PwgbN', 247, '2025-05-28 14:58:31', 0, 'tester', 'Pending', 'tester', 'tester@gmail.com', NULL),
(31, 'TICKET_Qtm8j', 247, '2025-05-28 14:58:41', 0, 'tester', 'Pending', 'tester', 'tester@gmail.com', NULL),
(32, 'TICKET_Tq8ct', 247, '2025-05-28 15:05:06', 0, 'tester', 'Pending', 'tester', 'tester@gmail.com', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `support_message`
--

CREATE TABLE `support_message` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `ticket_id` varchar(250) DEFAULT NULL,
  `message` varchar(250) DEFAULT NULL,
  `created_date` datetime NOT NULL,
  `admin` int(11) DEFAULT NULL,
  `files` varchar(250) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `support_message`
--

INSERT INTO `support_message` (`id`, `user_id`, `ticket_id`, `message`, `created_date`, `admin`, `files`) VALUES
(1, 3, 'TICKET_ykMOH', 'pls update my password', '2023-10-05 00:47:25', 0, NULL),
(2, 0, 'TICKET_ykMOH', 'ok we will check', '2023-10-05 00:47:25', 1, NULL),
(3, 4, 'TIC-53-NN', 'there is a issue', '2025-04-08 10:00:01', 0, 'https://adrox.ai/./assets/images/support/image_1744106403_67f4f3a3e1fa9.jpeg'),
(4, 0, 'TIC-53-NN', 'we will check issue', '2025-04-08 10:00:01', 1, 'https://adrox.ai/./assets/images/support/image_1744106403_67f4f3a3e1fa9.jpeg'),
(5, 4, 'TIC-76-LG', 'there is a issue', '2025-04-08 11:56:46', 0, 'https://adrox.ai/./assets/images/support/image_1744113407_67f50eff9281c.jpeg'),
(6, 4, 'TIC-76-LG', 'there is a issue', '2025-04-08 11:57:21', 0, 'https://adrox.ai/./assets/images/support/image_1744113442_67f50f22718e7.jpeg'),
(7, 4, 'TIC-53-NN', 'hii', '2025-04-08 12:54:13', 0, NULL),
(8, 4, 'TIC-53-NN', 'hello', '2025-04-08 12:54:32', 0, NULL),
(9, 4, 'TIC-53-NN', 'hii', '2025-04-08 12:55:47', 0, NULL),
(10, 4, 'TIC-27-ZN', 'holoo', '2025-04-08 12:55:55', 0, NULL),
(11, 4, 'TIC-27-ZN', 'holoo', '2025-04-08 12:56:54', 0, NULL),
(12, 4, 'TIC-73-AQ', 'heyy broo', '2025-04-08 13:00:03', 0, NULL),
(13, 4, 'TIC-73-AQ', 'heloo test', '2025-04-08 13:04:36', 0, 'https://adrox.ai/./assets/images/support/image_1744117477_67f51ee5ccd8e.jpg'),
(14, 4, 'TIC-66-SC', 'hi', '2025-04-08 13:05:58', 0, 'https://adrox.ai/./assets/images/support/image_1744117558_67f51f36cfb9d.jpg'),
(15, 4, 'TIC-27-ZN', 'my image', '2025-04-08 13:07:36', 0, 'https://adrox.ai/./assets/images/support/image_1744117657_67f51f9943693.jpg'),
(16, 4, 'TIC-27-ZN', 'hiii', '2025-04-08 13:18:31', 0, 'https://adrox.ai/./assets/images/support/image_1744118312_67f52228e535f.jpg'),
(17, 4, 'TIC-15-JQ', 'hoooo', '2025-04-08 13:27:29', 0, 'https://adrox.ai/./assets/images/support/image_1744118851_67f524436e658.jpg'),
(18, 4, 'TIC-15-MF', 'holoo', '2025-04-08 13:27:59', 0, 'https://adrox.ai/./assets/images/support/image_1744118880_67f5246094611.jpg'),
(19, 4, 'TIC-82-FS', 'adorx', '2025-04-08 13:28:57', 0, 'https://adrox.ai/./assets/images/support/image_1744118937_67f52499dd857.jpg'),
(20, 78, 'TIC-72-AR', 'hii', '2025-04-17 06:01:07', 0, NULL),
(21, 0, 'TIC-53-NN', 'test', '2025-04-22 18:57:07', 1, NULL),
(22, 0, 'TIC-53-NN', 'Hello Adrox test, Your support ticket (#TIC-53-NN) has been closed. Thank you, Adrox Support Team', '2025-04-22 18:57:17', 1, NULL),
(23, 78, 'TIC-72-AR', 'test', '2025-04-30 03:45:52', 0, NULL),
(24, 24, 'TIC-34-JQ', 'hi', '2025-04-30 09:59:21', 0, NULL),
(25, 0, 'TIC-27-ZN', NULL, '2025-05-27 15:55:56', 1, 'http://localhost/ashok/mlm_demo/./assets/images/fenizo_technologies_software_company_logo.jpg'),
(26, 247, 'TICKET_Tq8ct', 'tester', '2025-05-28 15:05:06', 0, NULL),
(27, 0, 'TICKET_Tq8ct', 'test', '2025-05-28 15:05:42', 1, NULL),
(28, 0, 'TICKET_PwgbN', 'test', '2025-05-28 15:38:24', 1, NULL),
(29, 0, 'TICKET_Qtm8j', 'test', '2025-05-28 15:39:22', 1, NULL),
(30, 0, 'TICKET_Qtm8j', 'check\r\n', '2025-05-28 15:39:42', 1, NULL),
(31, 0, 'TICKET_PwgbN', 'test', '2025-05-28 15:40:23', 1, NULL),
(32, 0, 'TICKET_Qtm8j', 'test', '2025-05-28 15:41:23', 1, NULL),
(33, 0, 'TICKET_Qtm8j', 'test', '2025-05-28 15:52:55', 1, NULL),
(34, 0, 'TICKET_Tq8ct', 'new test', '2025-05-28 15:53:23', 1, NULL),
(35, 0, 'TICKET_Tq8ct', 'tester', '2025-05-28 15:54:30', 1, NULL),
(36, 0, 'TICKET_Tq8ct', 'new tester', '2025-05-28 15:55:01', 1, NULL),
(37, 0, 'TICKET_Tq8ct', 'asdf', '2025-05-28 15:55:20', 1, NULL),
(38, 0, 'TICKET_Tq8ct', NULL, '2025-05-28 15:55:27', 1, 'http://localhost/ashok/mlm_demo/./assets/images/19.png'),
(39, 0, 'TICKET_PwgbN', 'test', '2025-05-30 16:56:50', 1, NULL),
(40, 0, 'TICKET_PwgbN', NULL, '2025-05-30 16:56:57', 1, 'http://localhost/ashok/mlm_demo/./assets/images/1732687564058.jpg'),
(41, 0, 'TICKET_PwgbN', 'test', '2025-05-31 13:47:24', 1, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `token_config`
--

CREATE TABLE `token_config` (
  `id` int(11) NOT NULL,
  `coin_name` varchar(250) NOT NULL,
  `currency_status` int(11) NOT NULL,
  `api_call` varchar(250) DEFAULT NULL,
  `decimal` int(11) DEFAULT 2,
  `currency_value` varchar(250) NOT NULL DEFAULT '2',
  `staking_toke_symbol` varchar(250) DEFAULT NULL,
  `staking_toke_name` varchar(240) DEFAULT NULL,
  `currency_symbol` blob DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `token_config`
--

INSERT INTO `token_config` (`id`, `coin_name`, `currency_status`, `api_call`, `decimal`, `currency_value`, `staking_toke_symbol`, `staking_toke_name`, `currency_symbol`) VALUES
(1, 'Feni', 1, '1b6ed52ef6a6416c1acc3095b52ac90f83e26dd35edd72f95c225795dcc38a67', 2, '20', '', 'CSQ', 0x46454e49);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `username` varchar(255) DEFAULT NULL,
  `contact` varchar(255) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `gender` varchar(255) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `role_id` int(11) DEFAULT NULL,
  `kyc_status` enum('none','pending','under_review','resubmitted','approved','rejected') NOT NULL DEFAULT 'none',
  `kyc_last_submitted_at` datetime DEFAULT NULL,
  `kyc_verified_at` datetime DEFAULT NULL,
  `kyc_reviewer_id` int(10) UNSIGNED DEFAULT NULL,
  `zipcode` varchar(255) DEFAULT NULL,
  `dob` varchar(255) DEFAULT NULL,
  `status` int(11) DEFAULT NULL,
  `register_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `sponser` varchar(250) DEFAULT NULL,
  `get_status` int(20) DEFAULT 0,
  `referral_id` varchar(250) DEFAULT NULL,
  `rank_id` varchar(50) DEFAULT '0',
  `wallet_id` int(50) DEFAULT 0,
  `password_update` int(11) DEFAULT 0,
  `withdraw_status` int(11) DEFAULT 0,
  `updated_date` datetime DEFAULT NULL,
  `withdraw_action` varchar(250) DEFAULT '0',
  `twofa_key` varchar(250) DEFAULT NULL,
  `twofa_status` int(11) DEFAULT 0,
  `left_id` int(11) DEFAULT 0,
  `right_id` int(11) DEFAULT 0,
  `position` enum('left','right') DEFAULT NULL,
  `twofactorsecret` varchar(250) DEFAULT NULL,
  `twofacode_path` varchar(250) DEFAULT NULL,
  `country` varchar(250) DEFAULT NULL,
  `first_name` varchar(250) DEFAULT NULL,
  `last_name` varchar(150) DEFAULT NULL,
  `language_set` varchar(150) DEFAULT 'AS',
  `communication_set` varchar(150) DEFAULT 'en',
  `time_zone` varchar(150) DEFAULT 'Arizona',
  `profile_img` varchar(250) DEFAULT NULL,
  `success_payments` tinyint(1) NOT NULL DEFAULT 0,
  `payouts` tinyint(1) NOT NULL DEFAULT 0,
  `product_commission` tinyint(1) NOT NULL DEFAULT 0,
  `refund_alerts` tinyint(1) NOT NULL DEFAULT 0,
  `invoice_payments` tinyint(1) NOT NULL DEFAULT 0,
  `prefs_updated_at` datetime DEFAULT NULL,
  `package_id` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `username`, `contact`, `address`, `gender`, `image`, `role_id`, `kyc_status`, `kyc_last_submitted_at`, `kyc_verified_at`, `kyc_reviewer_id`, `zipcode`, `dob`, `status`, `register_date`, `sponser`, `get_status`, `referral_id`, `rank_id`, `wallet_id`, `password_update`, `withdraw_status`, `updated_date`, `withdraw_action`, `twofa_key`, `twofa_status`, `left_id`, `right_id`, `position`, `twofactorsecret`, `twofacode_path`, `country`, `first_name`, `last_name`, `language_set`, `communication_set`, `time_zone`, `profile_img`, `success_payments`, `payouts`, `product_commission`, `refund_alerts`, `invoice_payments`, `prefs_updated_at`, `package_id`) VALUES
(1, 'test', 'admin@gmail.com', '$2y$10$8bcTo/6HZzhmYLOSDBxIReMNSXAxIZuRdptLa8StDskhlr9Ic9uhq', 'yadu', '9009009000', 'Admin Nagar', 'Male', 'YADU_Logo.JPG', 1, 'none', NULL, NULL, NULL, '23232', '1999-08-03', 1, '2024-01-04 16:16:38', '0', 0, 'FENI001', '0', 0, 0, 0, NULL, '0', NULL, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, 'AS', '1', 'Arizona', NULL, 0, 0, 0, 0, 0, NULL, 4),
(247, NULL, 'testerer@gmail.com', '$2y$10$DOxTMWX2KV4NN1HvwRo9ROliqvweCgfKZoONrgCWvp/eOlGCwfgZ2', 'tester', '79040 05315', NULL, NULL, NULL, NULL, 'pending', '2025-08-30 08:51:25', NULL, NULL, NULL, NULL, 1, '2025-05-26 09:17:21', '1', 0, 'FENI597975', '0', 0, 0, 0, '2025-08-21 08:30:39', '0', NULL, 1, 0, 0, NULL, 'EDZKDXIDE7NBNVN5', 'http://localhost/ashok/mlm_demo/assets/images/qr_image/PMJJGKUVRP2K5HYQqr_code.png', 'IN', 'testers', 'new', 'en', '1,2', 'International Date Line West', 'profile_68a5d42faacf1.png', 1, 0, 1, 1, 1, '2025-08-21 09:45:08', 4),
(248, NULL, 'testera@gmail.com', '$2y$10$0WK2zaOGkwrWvEj7vhsNqOe/N1poSk9T0yPRs3QNWdZUU.Cr.IF3O', 'testera', NULL, NULL, NULL, NULL, NULL, 'none', NULL, NULL, NULL, NULL, NULL, 1, '2025-05-27 08:50:08', '247', 0, 'FENIZO317651', '0', 0, 0, 0, NULL, '0', NULL, 0, 0, 0, NULL, 'SENQQDIYL3233R6K', 'http://localhost/ashok/mlm_demo/assets/images/qr_image/SENQQDIYL3233R6Kqr_code.png', NULL, NULL, NULL, 'AS', '1', 'Arizona', NULL, 0, 0, 0, 0, 0, NULL, 4),
(249, NULL, 'testerb@gmail.com', '$2y$10$B2jcJEBfNXT/CO9LRwq1VOI03wZ8PBVr6zrlb59vEROk1NA5EHPmC', 'testerb', NULL, NULL, NULL, NULL, NULL, 'none', NULL, NULL, NULL, NULL, NULL, 1, '2025-05-27 08:50:56', '247', 0, 'FENIZO116416', '0', 0, 0, 0, NULL, '0', NULL, 0, 0, 0, NULL, 'AJN7C52CX7N72HNO', 'http://localhost/ashok/mlm_demo/assets/images/qr_image/AJN7C52CX7N72HNOqr_code.png', NULL, NULL, NULL, 'AS', '1', 'Arizona', NULL, 0, 0, 0, 0, 0, NULL, 4),
(250, NULL, 'tester@gmail.com', '$2y$10$pE768BihbgbtQSkoxWawV.D3Lsh.Kd1kSBWCuwjexMWrDfhjpPtD2', 'testersdf', '9999999999', NULL, NULL, NULL, NULL, 'none', NULL, NULL, NULL, NULL, NULL, 1, '2025-05-30 12:04:25', '1', 0, 'FENIZO165289', '0', 0, 0, 0, '2026-01-30 15:59:19', '0', NULL, 0, 0, 0, NULL, 'PMJJGKUVRP2K5HYQ', 'http://localhost/ashok/mlm_demo/assets/images/qr_image/EDZKDXIDE7NBNVN5qr_code.png', 'India', 'test', 'test', 'AS', '1', 'Arizona', 'profile_697cc747eac6a.png', 1, 1, 1, 0, 0, '2026-01-30 16:00:12', 4),
(251, NULL, 'ashok@gmail.com', '$2y$10$QF4P5ZjvHi7GQFYkGwIGiueYS8a4KbmrGY0W7Zo22LKPXYCiF63C2', 'ashok', NULL, NULL, NULL, NULL, NULL, 'none', NULL, NULL, NULL, NULL, NULL, 1, '2025-10-13 07:09:36', '1', 0, 'FENIZO456546', '0', 0, 0, 0, NULL, '0', NULL, 0, 0, 0, NULL, 'ZWJPTN3PUBLRGK2S', 'http://localhost/ashok/encrypt-code/e-commerce/assets/images/qr_image/ZWJPTN3PUBLRGK2Sqr_code.png', NULL, NULL, NULL, 'AS', 'en', 'Arizona', NULL, 0, 0, 0, 0, 0, NULL, 0),
(252, NULL, 'oneu@mail.com', '$2y$10$pdmZoBp94RyNZHfPA1wEFOYFZjjJ8ifRLePVNnzQjJ1u0.3R.EHIK', 'oneu', NULL, NULL, NULL, NULL, NULL, 'none', NULL, NULL, NULL, NULL, NULL, 1, '2026-02-01 07:13:54', '250', 0, 'FENIZO381508', '0', 0, 0, 0, NULL, '0', NULL, 0, 0, 0, NULL, '2WDHHORRAD36I2R3', 'http://localhost/ashok/e-commerce-mlm-v2/assets/images/qr_image/2WDHHORRAD36I2R3qr_code.png', NULL, NULL, NULL, 'AS', 'en', 'Arizona', NULL, 0, 0, 0, 0, 0, NULL, 0),
(253, NULL, 'ruser@mail.com', '$2y$10$Y67r2gQZf8Sg.KW2qmixa.ohUtiWRf0jccEKdm92j8zi4zdcbd7Ma', 'ruser', NULL, NULL, NULL, NULL, NULL, 'none', NULL, NULL, NULL, NULL, NULL, 1, '2026-02-01 07:15:21', '250', 0, 'FENIZO295935', '0', 0, 0, 0, NULL, '0', NULL, 0, 0, 0, NULL, 'B753FRKHGNADY4XK', 'http://localhost/ashok/e-commerce-mlm-v2/assets/images/qr_image/B753FRKHGNADY4XKqr_code.png', NULL, NULL, NULL, 'AS', 'en', 'Arizona', NULL, 0, 0, 0, 0, 0, NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `user_activity_logs`
--

CREATE TABLE `user_activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `status` enum('logged in','logged out') DEFAULT NULL,
  `ticket_status` varchar(255) DEFAULT NULL,
  `timestamp` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_addresses`
--

CREATE TABLE `user_addresses` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `address_type` enum('home','work','apartment') NOT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_addresses`
--

INSERT INTO `user_addresses` (`id`, `user_id`, `address_type`, `first_name`, `last_name`, `address`, `city`, `state`, `postal_code`, `country`, `is_default`, `created_at`) VALUES
(2, 247, 'work', 'Ashok', 'kumar', 'Av. Insurgentes Sur 1234,  \nColonia Del Valle,  \nAlcaldía Benito JuárezColonia mexico Sur', 'Ciudad de', 'México', '03100', 'México', 0, '2025-07-14 12:02:54');

-- --------------------------------------------------------

--
-- Table structure for table `user_ad_rewards`
--

CREATE TABLE `user_ad_rewards` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `ad_id` int(11) NOT NULL,
  `reward_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_ad_sessions`
--

CREATE TABLE `user_ad_sessions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `ad_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `status` enum('started','completed','expired') NOT NULL DEFAULT 'started',
  `start_at` datetime NOT NULL,
  `expected_end_at` datetime NOT NULL,
  `completed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_ad_sessions`
--

INSERT INTO `user_ad_sessions` (`id`, `user_id`, `ad_id`, `token`, `status`, `start_at`, `expected_end_at`, `completed_at`) VALUES
(1, 250, 1, '78e9be482337ff303dbf9ae560283b63', 'started', '2026-02-01 08:04:29', '2026-02-01 08:04:59', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_bank`
--

CREATE TABLE `user_bank` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `holder_name` varchar(255) DEFAULT NULL,
  `bank_name` varchar(255) DEFAULT NULL,
  `account_number` varchar(80) DEFAULT NULL,
  `ifsc` varchar(30) DEFAULT NULL,
  `upi_id` varchar(120) DEFAULT NULL,
  `status` enum('not_added','pending','approved','rejected') NOT NULL DEFAULT 'not_added',
  `note` varchar(255) DEFAULT NULL,
  `submitted_at` datetime DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `reviewer_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_bank`
--

INSERT INTO `user_bank` (`id`, `user_id`, `holder_name`, `bank_name`, `account_number`, `ifsc`, `upi_id`, `status`, `note`, `submitted_at`, `reviewed_at`, `reviewer_id`, `created_at`, `updated_at`) VALUES
(1, 250, 'asdfasdf', 'asdfasdf', '123123123123123', 'ASDF123123', '12321', 'pending', NULL, '2026-01-30 16:11:27', NULL, NULL, '2026-01-30 16:11:27', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_email_otp`
--

CREATE TABLE `user_email_otp` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `email` varchar(191) NOT NULL,
  `otp` int(11) NOT NULL,
  `ref` varchar(32) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime NOT NULL,
  `verified` varchar(150) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_investment`
--

CREATE TABLE `user_investment` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `invest_amount` varchar(200) NOT NULL,
  `invest_network` varchar(50) DEFAULT NULL,
  `status` int(11) NOT NULL DEFAULT 0,
  `created_date` datetime DEFAULT NULL,
  `run_date` datetime DEFAULT NULL,
  `mature_date` datetime DEFAULT NULL,
  `days_count` int(11) DEFAULT NULL,
  `profit` varchar(250) DEFAULT NULL,
  `type` varchar(250) DEFAULT NULL,
  `bot` int(11) DEFAULT 1,
  `hash_id` varchar(250) DEFAULT NULL,
  `ending_date` datetime DEFAULT NULL,
  `starting_date` datetime DEFAULT NULL,
  `reinvest_status` int(11) DEFAULT 1,
  `reinvest_id` int(11) DEFAULT 0,
  `reinvest_date` datetime DEFAULT NULL,
  `req_method` varchar(250) DEFAULT NULL,
  `recived_status` int(11) DEFAULT 0,
  `approve_status` int(11) DEFAULT 0,
  `stake_interest` varchar(250) DEFAULT NULL,
  `csq_price` varchar(250) DEFAULT '0',
  `csq_deposit` varchar(250) DEFAULT '0',
  `package_id` int(11) DEFAULT NULL,
  `token_id` int(11) DEFAULT 0,
  `currency_id` int(11) DEFAULT 0,
  `earn_by` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `user_investment`
--

INSERT INTO `user_investment` (`id`, `user_id`, `invest_amount`, `invest_network`, `status`, `created_date`, `run_date`, `mature_date`, `days_count`, `profit`, `type`, `bot`, `hash_id`, `ending_date`, `starting_date`, `reinvest_status`, `reinvest_id`, `reinvest_date`, `req_method`, `recived_status`, `approve_status`, `stake_interest`, `csq_price`, `csq_deposit`, `package_id`, `token_id`, `currency_id`, `earn_by`) VALUES
(1, 247, '100', 'BSC', 1, '2025-08-29 11:22:00', '2025-08-31 00:00:00', '2025-09-28 11:22:00', 29, '0.1', 'mining', 1, 'admin-made', '2025-09-28 11:22:00', '2025-08-29 11:22:00', 1, 0, NULL, 'admin', 0, 1, NULL, '20', '2000', 4, 1, 1, 'token'),
(2, 248, '111', 'BSC', 1, '2025-08-29 11:22:00', '2025-08-31 00:00:00', '2025-09-28 11:22:00', 29, '0.111', 'mining', 1, 'admin-made', '2025-09-28 11:22:00', '2025-08-29 11:22:00', 1, 0, NULL, 'admin', 0, 1, NULL, '20', '2220', 4, 1, 1, 'token'),
(3, 249, '111', 'BSC', 1, '2025-08-29 11:22:00', '2025-08-31 00:00:00', '2025-09-28 11:22:00', 29, '0.111', 'mining', 1, 'admin-made', '2025-09-28 11:22:00', '2025-08-29 11:22:00', 1, 0, NULL, 'admin', 0, 1, NULL, '20', '2220', 4, 1, 1, 'token'),
(4, 250, '111', 'BSC', 1, '2025-08-29 11:22:00', '2025-08-31 00:00:00', '2025-09-28 11:22:00', 29, '0.111', 'mining', 1, 'admin-made', '2025-09-28 11:22:00', '2025-08-29 11:22:00', 1, 0, NULL, 'admin', 0, 1, NULL, '20', '2220', 4, 1, 1, 'token'),
(5, 250, '150.00', 'stripe', 1, '2026-02-01 08:34:52', '2026-02-02 00:00:00', '2026-03-03 08:34:52', 30, '0.1', 'mining', 1, 'user-wallet', '2026-03-03 08:34:52', '2026-02-01 08:34:52', 1, 0, NULL, 'user-wallet', 0, 1, NULL, '20', '3000', 4, 1, 1, 'token');

-- --------------------------------------------------------

--
-- Table structure for table `user_kyc`
--

CREATE TABLE `user_kyc` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `full_name_pan` varchar(255) DEFAULT NULL,
  `pan_number` varchar(20) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `aadhaar_last4` varchar(4) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(120) DEFAULT NULL,
  `state` varchar(120) DEFAULT NULL,
  `pincode` varchar(15) DEFAULT NULL,
  `pan_doc` varchar(255) DEFAULT NULL,
  `aadhaar_doc` varchar(255) DEFAULT NULL,
  `status` enum('none','pending','under_review','approved','rejected','resubmission_required') NOT NULL DEFAULT 'none',
  `reviewer_note` varchar(255) DEFAULT NULL,
  `submitted_at` datetime DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `reviewer_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_kyc`
--

INSERT INTO `user_kyc` (`id`, `user_id`, `full_name_pan`, `pan_number`, `dob`, `aadhaar_last4`, `address`, `city`, `state`, `pincode`, `pan_doc`, `aadhaar_doc`, `status`, `reviewer_note`, `submitted_at`, `reviewed_at`, `reviewer_id`, `created_at`, `updated_at`) VALUES
(1, 250, 'Ashokkumar Sakthivel', '4123213ASDF', '0000-00-00', '1231', 'RAJENDRA 3RD STREET Madurai South\r\nArasaradi', 'Madurai', 'TAmil NAdu', '625016', NULL, NULL, 'pending', 'Admin will review documents within 24–48 hrs.', '2026-01-30 16:10:52', NULL, NULL, '2026-01-30 16:10:52', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_method_progress`
--

CREATE TABLE `user_method_progress` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `method_id` int(11) NOT NULL,
  `progress_date` date NOT NULL,
  `completed_count` int(11) NOT NULL DEFAULT 0,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_method_progress`
--

INSERT INTO `user_method_progress` (`id`, `user_id`, `method_id`, `progress_date`, `completed_count`, `updated_at`) VALUES
(1, 1, 1, '2026-02-01', 12, '2026-02-01 12:10:03'),
(2, 1, 2, '2026-02-01', 2, '2026-02-01 12:10:03'),
(4, 250, 1, '2026-02-01', 10, '2026-02-01 07:53:27'),
(5, 250, 2, '2026-02-01', 5, '2026-02-01 07:48:20');

-- --------------------------------------------------------

--
-- Table structure for table `user_streaks`
--

CREATE TABLE `user_streaks` (
  `user_id` int(11) NOT NULL,
  `streak_days` int(11) NOT NULL DEFAULT 0,
  `streak_bonus_percent` int(11) NOT NULL DEFAULT 0,
  `last_checkin_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_streaks`
--

INSERT INTO `user_streaks` (`user_id`, `streak_days`, `streak_bonus_percent`, `last_checkin_date`) VALUES
(1, 6, 15, '2026-01-31'),
(250, 1, 5, '2026-02-01');

-- --------------------------------------------------------

--
-- Table structure for table `user_task_claims`
--

CREATE TABLE `user_task_claims` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `claim_date` date NOT NULL,
  `status` enum('claimed','pending','approved','rejected') NOT NULL DEFAULT 'claimed',
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_task_claims`
--

INSERT INTO `user_task_claims` (`id`, `user_id`, `task_id`, `claim_date`, `status`, `created_at`) VALUES
(1, 250, 1, '2026-02-01', 'claimed', '2026-02-01 07:48:03'),
(2, 250, 2, '2026-02-01', 'pending', '2026-02-01 07:48:05');

-- --------------------------------------------------------

--
-- Table structure for table `user_video_rewards`
--

CREATE TABLE `user_video_rewards` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `video_id` int(11) NOT NULL,
  `reward_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_video_sessions`
--

CREATE TABLE `user_video_sessions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `video_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `status` enum('started','completed','expired') NOT NULL DEFAULT 'started',
  `start_at` datetime NOT NULL,
  `expected_end_at` datetime NOT NULL,
  `completed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_video_sessions`
--

INSERT INTO `user_video_sessions` (`id`, `user_id`, `video_id`, `token`, `status`, `start_at`, `expected_end_at`, `completed_at`) VALUES
(1, 250, 1, '44c960b3a3a51b4806d118c5a4c18cd8', 'started', '2026-02-01 08:01:05', '2026-02-01 08:01:35', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_wallet`
--

CREATE TABLE `user_wallet` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `wallet_address` varchar(250) NOT NULL,
  `mnemonic` varchar(250) NOT NULL,
  `wallet_qrimage` varchar(255) NOT NULL,
  `private_key` varchar(250) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_wallet`
--

INSERT INTO `user_wallet` (`id`, `user_id`, `wallet_address`, `mnemonic`, `wallet_qrimage`, `private_key`) VALUES
(1, 1, '0x08615Eca9DC0208c8A4bbb777f68DA2159b9B14E', 'c8c4341016312c70e2c4513bd265f18986de9514ed43003970c76a2f8e19b6cb0d0d46a24d988867a26c0359477ddc0b53fb492daaba4db3a38f3d1341b650916a229905cd845720eeb22cb1d034eaae', 'https://adrox.ai/assets/images/qr_image/0x08615Eca9DC0208c8A4bbb777f68DA2159b9B14Eqr_code.png', 'ac6ed93d62a9fb7651418be18dd959807f6eb7c74ad873ce709831a0a9acc8139d090dbad5751a6645b1a251136d510394cc846628d5037a5c12791942574b832a39a4303a6963d3ea851dac894ced95'),
(246, 246, '0x40a60e56809eaff53d8a1b60fe6446b90a29975d', '889f5b664b05e0d9cf37a4beb9b81da72d1d421df5355265c2bf8e2c84b131d1109e1a5da3f007ebb2674cd8ffd221be9b091c7d7cdf3561819fee07c480b13b32eed369d478e837f6e6c0d3b5e26f60', 'http://localhost/ashok/mlm_demo/assets/images/qr_image/0x40a60e56809eaff53d8a1b60fe6446b90a29975dqr_code.png', '2ad716a502ca3b4192cd21c5fe47f8edc6f200a4e41a3506bbc9f74bdafc0a526f7cf956e0caae45fb4f6d7ce04422258acb2732871ed50504fb4e61789e0d28d409e2221b82751d702c854e3122053b'),
(247, 247, '0xb41ce484a045db1381e8e911af6b187bf61ada2c', '71bae421bf751af69c2a6cee393c9962431481cc4f82ac624e09223c6a5ce9dfe4b64868a705a2bb13337c9abc3f89a1b84b1c39f5ff077f0bbae17d5d8707c5acc28c3ab4b608cee278663092315ea3', 'http://localhost/ashok/mlm_demo/assets/images/qr_image/0xb41ce484a045db1381e8e911af6b187bf61ada2cqr_code.png', '8b9bebe2af28c698576d7ccfaf9777dd1caaf38e32bf471dc1e39b6189bf0606fc0c2f47efbe18ad6380a3cdb250b38c1e6c9086fda21c0e9e3dc84a369df8f728b93e57e67465005f026e27fce9523a'),
(248, 248, '0x71df8e0070e7368ec2ab3b791205f4fb4229287b', '1de1eccbfc3da3e30dda70ea99cb79b99af5292267ceb4af349d50fc1a7f07d0589d8200cb433b8d6a152b76d0e167227f1d353df53e8dca3dd50e8696aceb180e8d17ce92ef69525532f6b1276c26be', 'http://localhost/ashok/mlm_demo/assets/images/qr_image/0x71df8e0070e7368ec2ab3b791205f4fb4229287bqr_code.png', 'dfd1c36f7e009165dc244fc12947f9c0bbbeba4e33d1207ac9b528ac083675b0b6fef8f4d86c0a848b31921248b79e15018dd5e377243b7e64fabdb69ba0025f0cc081dfa5b7b1872d734954bae3b13a'),
(249, 249, '0x8993fd877b0d470c2bad0387c2666a60d3787430', 'bcdf9667f02321999af20594b553ceb25c32925304b20a22c8b0ac95eb41c35bed392c1e385e18afbf817b781ca71ea5997abf98142bd65b4590e74f01f9bf9598526fbbb6fda35339f79d67ec1916c7', 'http://localhost/ashok/mlm_demo/assets/images/qr_image/0x8993fd877b0d470c2bad0387c2666a60d3787430qr_code.png', 'db4978a7ece76633aad883b024b1b83d76df419d473efcb25991cbf28bfd4800f43a31783f4f5f0bba15f1b3dd51f59bee1d82589acf60f6ca3e46447f48ea0e6c826d6bc908e23c8f72cb4ff764fc2e'),
(250, 250, '0xe0a9fd07da1866c726f866c19471277cffa3fa5e', '35220d9d2dd10c033e155420106cd69d887a4c5e97144df760681e3cf167ebe3be6714ccddf9a540ef625fe209465585e8a87df4f533045a95bc803ad5edb4893aff9f03101c49517623402454b90ee4', 'http://localhost/ashok/mlm_demo/assets/images/qr_image/0xe0a9fd07da1866c726f866c19471277cffa3fa5eqr_code.png', '951f64c80d5456650f2872394ee66c3c30cdf17de612fac3f8dec606975bc4ca59d1a02499da4d822fa92b679772f8fddd5eceee10c2df9beabda67c79c42d99fe46c80592502c31aee1be3c5a0c4b45'),
(251, 251, '0x5377ce69012d27c8096976018a0f00a3ec029903', 'f5631178fa7cb4ac22994a0e4814f9d66ce0a5fea731535f15a4c9f75c6850e52b85db8f22ecacc7fc408ea24e6d7f346b7fab62d280779584ac0e4bf6efe6ea2133b0a48479fcade6ad5e561180bb2b0792a91d993a9887a27ed36c91b2c230', 'http://localhost/ashok/encrypt-code/e-commerce/assets/images/qr_image/0x5377ce69012d27c8096976018a0f00a3ec029903qr_code.png', '7859b620ae33aec302681de4320d2860432c2dc06bba6ea157c73558a3c8aada627820fbeabeb2676c6e808abf23398c91279cff3626d7b504da2399fc13171642bb7e30cb485e50129863e647e8e6bc'),
(252, 252, '0xb757835c9faa652649802b5d317f514c5112c5c6', 'c8381c93092774e81ba1a6a7ad056f4a4b3bff6ccbc3637f226d554c9a5ff2b7a3b4936937d541ff71f999b080b0073ada1431c08f2ca4d771fb0ecf94bdcefcfee642dbe5f4687be8c0cfbc425258f0', 'http://localhost/ashok/e-commerce-mlm-v2/assets/images/qr_image/0xb757835c9faa652649802b5d317f514c5112c5c6qr_code.png', '9eeb18770628ae468a2fcea62694c5d6c02098565cda1e514cd326c7258ee8e727bde8bee98686be1d05ee2d0086c61bcb0035c09de093715b688f9adcd9bb3045c50f1f2fe3680bcd1200cd8dc6bc09'),
(253, 253, '0x4f709d50adc697aab9c8e6adbd9c8ddf8fa32fd3', '8d57d4c5910abfb05338b21ad53da3574d406b9a0fa282eedee915cab0d60651580c251bd24bb00af24963756dcae0eb60b9c40329efaa896b4a9152137e44cf8c56a7d6c22aaefb0cc713c8dd552983', 'http://localhost/ashok/e-commerce-mlm-v2/assets/images/qr_image/0x4f709d50adc697aab9c8e6adbd9c8ddf8fa32fd3qr_code.png', '70ccc97580420e8e87aa6670a638fc27843e391dfcc19e7d7d70d1dd0c50b8cb7acf7716de1638d3e7648aabb92ded7d8df52c8ce607f83d96fe2e0fc837ddb75e10f293b53efbcee40795b1c33a963c');

-- --------------------------------------------------------

--
-- Table structure for table `user_wallets`
--

CREATE TABLE `user_wallets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `usd_balance` decimal(12,2) NOT NULL DEFAULT 0.00,
  `usd_pending` decimal(12,2) NOT NULL DEFAULT 0.00,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_wallets`
--

INSERT INTO `user_wallets` (`id`, `user_id`, `usd_balance`, `usd_pending`, `updated_at`) VALUES
(1, 1, 12450.00, 80.00, '2026-02-01 12:10:03'),
(2, 250, 20.00, 0.00, '2026-02-01 07:53:27');

-- --------------------------------------------------------

--
-- Table structure for table `wallet_transactions`
--

CREATE TABLE `wallet_transactions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `tx_type` enum('earn','bonus','withdraw') NOT NULL DEFAULT 'earn',
  `source` varchar(50) DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `status` enum('pending','completed','rejected') NOT NULL DEFAULT 'completed',
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wallet_transactions`
--

INSERT INTO `wallet_transactions` (`id`, `user_id`, `tx_type`, `source`, `amount`, `status`, `created_at`) VALUES
(1, 1, 'earn', 'ads', 40.00, 'completed', '2026-02-01 12:10:03'),
(2, 1, 'earn', 'videos', 60.00, 'completed', '2026-02-01 12:10:03'),
(3, 1, 'bonus', 'checkin', 20.00, 'completed', '2026-02-01 12:10:03'),
(4, 250, 'earn', 'ads', 0.25, 'completed', '2026-02-01 07:47:10'),
(5, 250, 'earn', 'ads', 0.25, 'completed', '2026-02-01 07:47:19'),
(6, 250, 'earn', 'ads', 0.25, 'completed', '2026-02-01 07:47:23'),
(7, 250, 'earn', 'ads', 0.25, 'completed', '2026-02-01 07:47:57'),
(8, 250, 'earn', 'videos', 1.50, 'completed', '2026-02-01 07:48:00'),
(9, 250, 'bonus', 'checkin', 10.00, 'completed', '2026-02-01 07:48:03'),
(10, 250, 'earn', 'ads', 0.25, 'completed', '2026-02-01 07:48:14'),
(11, 250, 'earn', 'ads', 0.25, 'completed', '2026-02-01 07:48:15'),
(12, 250, 'earn', 'videos', 1.50, 'completed', '2026-02-01 07:48:16'),
(13, 250, 'earn', 'videos', 1.50, 'completed', '2026-02-01 07:48:18'),
(14, 250, 'earn', 'ads', 0.25, 'completed', '2026-02-01 07:48:19'),
(15, 250, 'earn', 'videos', 1.50, 'completed', '2026-02-01 07:48:19'),
(16, 250, 'earn', 'videos', 1.50, 'completed', '2026-02-01 07:48:20'),
(17, 250, 'earn', 'ads', 0.25, 'completed', '2026-02-01 07:48:21'),
(18, 250, 'earn', 'ads', 0.25, 'completed', '2026-02-01 07:53:26'),
(19, 250, 'earn', 'ads', 0.25, 'completed', '2026-02-01 07:53:27');

-- --------------------------------------------------------

--
-- Table structure for table `wishlist`
--

CREATE TABLE `wishlist` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wishlist`
--

INSERT INTO `wishlist` (`id`, `user_id`, `product_id`, `created_at`) VALUES
(28, 247, 13, '2025-07-14 22:53:51'),
(31, 251, 9, '2025-10-13 12:45:19');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_members`
--
ALTER TABLE `admin_members`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `announcement`
--
ALTER TABLE `announcement`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `binary_carry`
--
ALTER TABLE `binary_carry`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `binary_placement`
--
ALTER TABLE `binary_placement`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `sponsor_id` (`sponsor_id`),
  ADD KEY `parent_id` (`parent_id`),
  ADD KEY `auto_from_user` (`auto_from_user`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_parent` (`parent_id`);

--
-- Indexes for table `binary_volume_ledger`
--
ALTER TABLE `binary_volume_ledger`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_invest` (`user_id`,`invest_id`);

--
-- Indexes for table `blocked_ips`
--
ALTER TABLE `blocked_ips`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `blogs`
--
ALTER TABLE `blogs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `blog_categories`
--
ALTER TABLE `blog_categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `brands`
--
ALTER TABLE `brands`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_cart` (`user_id`,`product_id`);

--
-- Indexes for table `commission_config`
--
ALTER TABLE `commission_config`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `countries`
--
ALTER TABLE `countries`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `coupons`
--
ALTER TABLE `coupons`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `coupon_usage`
--
ALTER TABLE `coupon_usage`
  ADD PRIMARY KEY (`id`),
  ADD KEY `coupon_id` (`coupon_id`);

--
-- Indexes for table `currency_config`
--
ALTER TABLE `currency_config`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `deposits`
--
ALTER TABLE `deposits`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `earning_ads`
--
ALTER TABLE `earning_ads`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `earning_methods`
--
ALTER TABLE `earning_methods`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_method_code` (`code`);

--
-- Indexes for table `earning_videos`
--
ALTER TABLE `earning_videos`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `email_config`
--
ALTER TABLE `email_config`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `email_log`
--
ALTER TABLE `email_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `email_template`
--
ALTER TABLE `email_template`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `epin_batches`
--
ALTER TABLE `epin_batches`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_creator` (`created_by`,`created_at`);

--
-- Indexes for table `faqs`
--
ALTER TABLE `faqs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `history`
--
ALTER TABLE `history`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoice_no` (`invoice_no`),
  ADD KEY `idx_invoices_order` (`order_id`),
  ADD KEY `idx_invoices_no` (`invoice_no`);

--
-- Indexes for table `invoice_items`
--
ALTER TABLE `invoice_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_invoice_items_invoice` (`invoice_id`);

--
-- Indexes for table `kyc_applications`
--
ALTER TABLE `kyc_applications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`);

--
-- Indexes for table `kyc_audit_logs`
--
ALTER TABLE `kyc_audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_kyc` (`kyc_id`);

--
-- Indexes for table `notification`
--
ALTER TABLE `notification`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `order_shipments`
--
ALTER TABLE `order_shipments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order_shipments_order` (`order_id`),
  ADD KEY `idx_order_shipments_track` (`tracking_number`),
  ADD KEY `idx_order_shipments_status` (`status`);

--
-- Indexes for table `order_status_history`
--
ALTER TABLE `order_status_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order_history_order` (`order_id`),
  ADD KEY `idx_order_history_status` (`status`);

--
-- Indexes for table `package_config`
--
ALTER TABLE `package_config`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `page_content`
--
ALTER TABLE `page_content`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `page_link_config`
--
ALTER TABLE `page_link_config`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payment_controls`
--
ALTER TABLE `payment_controls`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payment_settings`
--
ALTER TABLE `payment_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `product_categories`
--
ALTER TABLE `product_categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `product_images`
--
ALTER TABLE `product_images`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `product_meta`
--
ALTER TABLE `product_meta`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `product_reviews`
--
ALTER TABLE `product_reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `quick_tasks`
--
ALTER TABLE `quick_tasks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_task_code` (`code`);

--
-- Indexes for table `rank_config`
--
ALTER TABLE `rank_config`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `shipping_zones`
--
ALTER TABLE `shipping_zones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `pincode` (`pincode`);

--
-- Indexes for table `site_settings`
--
ALTER TABLE `site_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sliders_img`
--
ALTER TABLE `sliders_img`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sociallinks`
--
ALTER TABLE `sociallinks`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `support`
--
ALTER TABLE `support`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `support_message`
--
ALTER TABLE `support_message`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `token_config`
--
ALTER TABLE `token_config`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_users_kyc_status` (`kyc_status`);

--
-- Indexes for table `user_activity_logs`
--
ALTER TABLE `user_activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `user_addresses`
--
ALTER TABLE `user_addresses`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user_ad_rewards`
--
ALTER TABLE `user_ad_rewards`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_user_ad_once` (`user_id`,`ad_id`);

--
-- Indexes for table `user_ad_sessions`
--
ALTER TABLE `user_ad_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_ad_token` (`token`),
  ADD KEY `idx_user_ad` (`user_id`,`ad_id`);

--
-- Indexes for table `user_bank`
--
ALTER TABLE `user_bank`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_user_bank_user` (`user_id`),
  ADD KEY `idx_user_bank_status` (`status`);

--
-- Indexes for table `user_email_otp`
--
ALTER TABLE `user_email_otp`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `ref` (`ref`);

--
-- Indexes for table `user_investment`
--
ALTER TABLE `user_investment`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user_kyc`
--
ALTER TABLE `user_kyc`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_user_kyc_user` (`user_id`),
  ADD KEY `idx_user_kyc_status` (`status`);

--
-- Indexes for table `user_method_progress`
--
ALTER TABLE `user_method_progress`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_user_method_day` (`user_id`,`method_id`,`progress_date`),
  ADD KEY `idx_method` (`method_id`);

--
-- Indexes for table `user_streaks`
--
ALTER TABLE `user_streaks`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `user_task_claims`
--
ALTER TABLE `user_task_claims`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_user_task_day` (`user_id`,`task_id`,`claim_date`),
  ADD KEY `idx_user_day` (`user_id`,`claim_date`);

--
-- Indexes for table `user_video_rewards`
--
ALTER TABLE `user_video_rewards`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_user_video_once` (`user_id`,`video_id`);

--
-- Indexes for table `user_video_sessions`
--
ALTER TABLE `user_video_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_token` (`token`),
  ADD KEY `idx_user_video` (`user_id`,`video_id`);

--
-- Indexes for table `user_wallet`
--
ALTER TABLE `user_wallet`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user_wallets`
--
ALTER TABLE `user_wallets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_user_wallet_user` (`user_id`);

--
-- Indexes for table `wallet_transactions`
--
ALTER TABLE `wallet_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_date` (`user_id`,`created_at`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_product` (`user_id`,`product_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_members`
--
ALTER TABLE `admin_members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `announcement`
--
ALTER TABLE `announcement`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `binary_placement`
--
ALTER TABLE `binary_placement`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=251;

--
-- AUTO_INCREMENT for table `binary_volume_ledger`
--
ALTER TABLE `binary_volume_ledger`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `blocked_ips`
--
ALTER TABLE `blocked_ips`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `blogs`
--
ALTER TABLE `blogs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `blog_categories`
--
ALTER TABLE `blog_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `brands`
--
ALTER TABLE `brands`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=78;

--
-- AUTO_INCREMENT for table `commission_config`
--
ALTER TABLE `commission_config`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `countries`
--
ALTER TABLE `countries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=236;

--
-- AUTO_INCREMENT for table `coupons`
--
ALTER TABLE `coupons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `coupon_usage`
--
ALTER TABLE `coupon_usage`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `currency_config`
--
ALTER TABLE `currency_config`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `deposits`
--
ALTER TABLE `deposits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `earning_ads`
--
ALTER TABLE `earning_ads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `earning_methods`
--
ALTER TABLE `earning_methods`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `earning_videos`
--
ALTER TABLE `earning_videos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `email_config`
--
ALTER TABLE `email_config`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `email_log`
--
ALTER TABLE `email_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=151;

--
-- AUTO_INCREMENT for table `email_template`
--
ALTER TABLE `email_template`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `epin_batches`
--
ALTER TABLE `epin_batches`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `faqs`
--
ALTER TABLE `faqs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `history`
--
ALTER TABLE `history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `invoice_items`
--
ALTER TABLE `invoice_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `kyc_applications`
--
ALTER TABLE `kyc_applications`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `kyc_audit_logs`
--
ALTER TABLE `kyc_audit_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notification`
--
ALTER TABLE `notification`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=294;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT for table `order_shipments`
--
ALTER TABLE `order_shipments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `order_status_history`
--
ALTER TABLE `order_status_history`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `package_config`
--
ALTER TABLE `package_config`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `page_content`
--
ALTER TABLE `page_content`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `page_link_config`
--
ALTER TABLE `page_link_config`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `payment_controls`
--
ALTER TABLE `payment_controls`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `payment_settings`
--
ALTER TABLE `payment_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `product_categories`
--
ALTER TABLE `product_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `product_images`
--
ALTER TABLE `product_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT for table `product_meta`
--
ALTER TABLE `product_meta`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=223;

--
-- AUTO_INCREMENT for table `product_reviews`
--
ALTER TABLE `product_reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `quick_tasks`
--
ALTER TABLE `quick_tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `rank_config`
--
ALTER TABLE `rank_config`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `shipping_zones`
--
ALTER TABLE `shipping_zones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `site_settings`
--
ALTER TABLE `site_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=74;

--
-- AUTO_INCREMENT for table `sliders_img`
--
ALTER TABLE `sliders_img`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `sociallinks`
--
ALTER TABLE `sociallinks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `support`
--
ALTER TABLE `support`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `support_message`
--
ALTER TABLE `support_message`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT for table `token_config`
--
ALTER TABLE `token_config`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=254;

--
-- AUTO_INCREMENT for table `user_activity_logs`
--
ALTER TABLE `user_activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_addresses`
--
ALTER TABLE `user_addresses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `user_ad_rewards`
--
ALTER TABLE `user_ad_rewards`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_ad_sessions`
--
ALTER TABLE `user_ad_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `user_bank`
--
ALTER TABLE `user_bank`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `user_email_otp`
--
ALTER TABLE `user_email_otp`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `user_investment`
--
ALTER TABLE `user_investment`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `user_kyc`
--
ALTER TABLE `user_kyc`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `user_method_progress`
--
ALTER TABLE `user_method_progress`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `user_task_claims`
--
ALTER TABLE `user_task_claims`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `user_video_rewards`
--
ALTER TABLE `user_video_rewards`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_video_sessions`
--
ALTER TABLE `user_video_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `user_wallet`
--
ALTER TABLE `user_wallet`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=254;

--
-- AUTO_INCREMENT for table `user_wallets`
--
ALTER TABLE `user_wallets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `wallet_transactions`
--
ALTER TABLE `wallet_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `wishlist`
--
ALTER TABLE `wishlist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `binary_placement`
--
ALTER TABLE `binary_placement`
  ADD CONSTRAINT `binary_placement_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `binary_placement_ibfk_2` FOREIGN KEY (`sponsor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `binary_placement_ibfk_3` FOREIGN KEY (`parent_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `binary_placement_ibfk_4` FOREIGN KEY (`auto_from_user`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `coupon_usage`
--
ALTER TABLE `coupon_usage`
  ADD CONSTRAINT `coupon_usage_ibfk_1` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `epin_batches`
--
ALTER TABLE `epin_batches`
  ADD CONSTRAINT `fk_batch_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `invoices`
--
ALTER TABLE `invoices`
  ADD CONSTRAINT `fk_invoices_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `invoice_items`
--
ALTER TABLE `invoice_items`
  ADD CONSTRAINT `fk_invoice_items_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `kyc_applications`
--
ALTER TABLE `kyc_applications`
  ADD CONSTRAINT `fk_kyc_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_shipments`
--
ALTER TABLE `order_shipments`
  ADD CONSTRAINT `fk_order_shipments_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_status_history`
--
ALTER TABLE `order_status_history`
  ADD CONSTRAINT `fk_order_history_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_reviews`
--
ALTER TABLE `product_reviews`
  ADD CONSTRAINT `product_reviews_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
