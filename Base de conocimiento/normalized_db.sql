-- Normalized Database Schema for Laravel SAAS Application
-- Updated to English naming conventions and modern SAAS architecture

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- --------------------------------------------------------
-- GEOGRAPHICAL TABLES
-- --------------------------------------------------------

--
-- Table structure for countries
--
CREATE TABLE `countries` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(191) NOT NULL,
  `code` varchar(3) DEFAULT NULL,
  `phone_code` varchar(10) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for states
--
CREATE TABLE `states` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `country_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(191) NOT NULL,
  `code` varchar(10) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for cities
--
CREATE TABLE `cities` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `state_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(191) NOT NULL,
  `code` varchar(10) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- COMPANY AND USER MANAGEMENT (SAAS)
-- --------------------------------------------------------

--
-- Table structure for companies (Multi-tenant)
--
CREATE TABLE `companies` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(191) NOT NULL,
  `slug` varchar(191) UNIQUE NOT NULL,
  `email` varchar(191) DEFAULT NULL,
  `phone` varchar(191) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city_id` bigint(20) UNSIGNED DEFAULT NULL,
  `state_id` bigint(20) UNSIGNED DEFAULT NULL,
  `country_id` bigint(20) UNSIGNED DEFAULT NULL,
  `tax_id` varchar(191) DEFAULT NULL,
  `logo` varchar(191) DEFAULT NULL,
  `website` varchar(191) DEFAULT NULL,
  `subscription_plan` enum('free', 'basic', 'premium', 'enterprise') DEFAULT 'free',
  `subscription_expires_at` timestamp NULL DEFAULT NULL,
  `max_users` int DEFAULT 1,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for roles
--
CREATE TABLE `roles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(191) NOT NULL,
  `guard_name` varchar(191) NOT NULL DEFAULT 'web',
  `is_system` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for permissions
--
CREATE TABLE `permissions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(191) NOT NULL,
  `guard_name` varchar(191) NOT NULL DEFAULT 'web',
  `group` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for role permissions
--
CREATE TABLE `role_has_permissions` (
  `permission_id` bigint(20) UNSIGNED NOT NULL,
  `role_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for users
--
CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(191) NOT NULL,
  `email` varchar(191) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(191) NOT NULL,
  `document_type` enum('CC', 'NIT', 'CE', 'passport') DEFAULT 'CC',
  `document_number` varchar(191) DEFAULT NULL,
  `phone` varchar(191) DEFAULT NULL,
  `mobile` varchar(191) DEFAULT NULL,
  `position` varchar(191) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city_id` bigint(20) UNSIGNED DEFAULT NULL,
  `state_id` bigint(20) UNSIGNED DEFAULT NULL,
  `country_id` bigint(20) UNSIGNED DEFAULT NULL,
  `avatar` varchar(191) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for user roles
--
CREATE TABLE `model_has_roles` (
  `role_id` bigint(20) UNSIGNED NOT NULL,
  `model_type` varchar(191) NOT NULL,
  `model_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for user permissions
--
CREATE TABLE `model_has_permissions` (
  `permission_id` bigint(20) UNSIGNED NOT NULL,
  `model_type` varchar(191) NOT NULL,
  `model_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for company settings
--
CREATE TABLE `company_settings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `measurement_system` enum('metric', 'imperial') DEFAULT 'metric',
  `quote_number_start` int DEFAULT 1,
  `order_number_start` int DEFAULT 1,
  `print_order_number_start` int DEFAULT 1,
  `profit_margin_percentage` decimal(8,2) DEFAULT 20.00,
  `waste_percentage` decimal(8,2) DEFAULT 5.00,
  `default_design_price` decimal(10,2) DEFAULT 0.00,
  `default_transport_price` decimal(10,2) DEFAULT 0.00,
  `default_cutting_price` decimal(10,2) DEFAULT 0.00,
  `tax_rate` decimal(5,2) DEFAULT 0.00,
  `currency` varchar(3) DEFAULT 'USD',
  `timezone` varchar(191) DEFAULT 'UTC',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- CONTACT MANAGEMENT
-- --------------------------------------------------------

--
-- Table structure for contacts (suppliers, customers, etc.)
--
CREATE TABLE `contacts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `type` enum('customer', 'supplier', 'both') NOT NULL DEFAULT 'customer',
  `name` varchar(191) NOT NULL,
  `document_type` enum('CC', 'NIT', 'CE', 'passport') DEFAULT 'CC',
  `document_number` varchar(191) DEFAULT NULL,
  `email` varchar(191) DEFAULT NULL,
  `phone` varchar(191) DEFAULT NULL,
  `mobile` varchar(191) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city_id` bigint(20) UNSIGNED DEFAULT NULL,
  `state_id` bigint(20) UNSIGNED DEFAULT NULL,
  `country_id` bigint(20) UNSIGNED DEFAULT NULL,
  `tax_id` varchar(191) DEFAULT NULL,
  `payment_terms` int DEFAULT 30,
  `credit_limit` decimal(12,2) DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- PRINTING EQUIPMENT AND MATERIALS
-- --------------------------------------------------------

--
-- Table structure for measurement units
--
CREATE TABLE `measurement_units` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `type` enum('length', 'weight', 'area', 'volume', 'quantity') NOT NULL,
  `name` varchar(191) NOT NULL,
  `symbol` varchar(10) NOT NULL,
  `conversion_factor` decimal(12,6) DEFAULT 1.000000,
  `is_base_unit` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for CTP plates
--
CREATE TABLE `ctp_plates` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `supplier_id` bigint(20) UNSIGNED DEFAULT NULL,
  `code` varchar(191) DEFAULT NULL,
  `name` varchar(191) NOT NULL,
  `width` decimal(8,2) NOT NULL,
  `height` decimal(8,2) NOT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `is_own` tinyint(1) DEFAULT 1,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for printing machines
--
CREATE TABLE `printing_machines` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `supplier_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ctp_plate_id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(191) DEFAULT NULL,
  `name` varchar(191) NOT NULL,
  `min_width` decimal(8,2) DEFAULT 0.00,
  `min_height` decimal(8,2) DEFAULT 0.00,
  `max_width` decimal(8,2) NOT NULL,
  `max_height` decimal(8,2) NOT NULL,
  `hourly_rate` decimal(10,2) DEFAULT 0.00,
  `setup_cost` decimal(10,2) DEFAULT 0.00,
  `is_own` tinyint(1) DEFAULT 1,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for papers
--
CREATE TABLE `papers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `supplier_id` bigint(20) UNSIGNED DEFAULT NULL,
  `code` varchar(191) DEFAULT NULL,
  `name` varchar(191) NOT NULL,
  `width` decimal(8,2) NOT NULL,
  `height` decimal(8,2) NOT NULL,
  `weight` decimal(8,2) DEFAULT NULL,
  `price_per_sheet` decimal(10,4) DEFAULT 0.0000,
  `price_per_kg` decimal(10,4) DEFAULT 0.0000,
  `finish_type` enum('matte', 'glossy', 'satin', 'uncoated') DEFAULT 'uncoated',
  `is_own` tinyint(1) DEFAULT 1,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for finishing processes
--
CREATE TABLE `finishing_processes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `supplier_id` bigint(20) UNSIGNED DEFAULT NULL,
  `name` varchar(191) NOT NULL,
  `description` text DEFAULT NULL,
  `unit_id` bigint(20) UNSIGNED DEFAULT NULL,
  `price_per_unit` decimal(10,4) DEFAULT 0.0000,
  `setup_cost` decimal(10,2) DEFAULT 0.00,
  `is_own` tinyint(1) DEFAULT 1,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for general products/services
--
CREATE TABLE `products` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `supplier_id` bigint(20) UNSIGNED DEFAULT NULL,
  `name` varchar(191) NOT NULL,
  `description` text DEFAULT NULL,
  `sku` varchar(191) DEFAULT NULL,
  `category` varchar(191) DEFAULT NULL,
  `unit_id` bigint(20) UNSIGNED DEFAULT NULL,
  `cost_price` decimal(10,2) DEFAULT 0.00,
  `selling_price` decimal(10,2) DEFAULT 0.00,
  `is_own` tinyint(1) DEFAULT 1,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- DOCUMENT AND ORDER MANAGEMENT
-- --------------------------------------------------------

--
-- Table structure for document types
--
CREATE TABLE `document_types` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(191) NOT NULL,
  `code` varchar(10) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for documents (quotes, orders, invoices)
--
CREATE TABLE `documents` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `contact_id` bigint(20) UNSIGNED NOT NULL,
  `document_type_id` bigint(20) UNSIGNED NOT NULL,
  `document_number` varchar(191) NOT NULL,
  `reference` varchar(191) DEFAULT NULL,
  `date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `status` enum('draft', 'sent', 'approved', 'rejected', 'in_production', 'completed', 'cancelled') DEFAULT 'draft',
  `subtotal` decimal(12,2) DEFAULT 0.00,
  `discount_amount` decimal(12,2) DEFAULT 0.00,
  `discount_percentage` decimal(5,2) DEFAULT 0.00,
  `tax_amount` decimal(12,2) DEFAULT 0.00,
  `tax_percentage` decimal(5,2) DEFAULT 0.00,
  `total` decimal(12,2) DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `internal_notes` text DEFAULT NULL,
  `valid_until` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for document items
--
CREATE TABLE `document_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `document_id` bigint(20) UNSIGNED NOT NULL,
  `printing_machine_id` bigint(20) UNSIGNED DEFAULT NULL,
  `paper_id` bigint(20) UNSIGNED DEFAULT NULL,
  `description` text NOT NULL,
  `quantity` decimal(10,2) DEFAULT 1.00,
  `width` decimal(8,2) DEFAULT NULL,
  `height` decimal(8,2) DEFAULT NULL,
  `pages` int DEFAULT 1,
  `colors_front` int DEFAULT 0,
  `colors_back` int DEFAULT 0,
  `paper_cut_width` decimal(8,2) DEFAULT NULL,
  `paper_cut_height` decimal(8,2) DEFAULT NULL,
  `unit_copies` int DEFAULT 1,
  `design_cost` decimal(10,2) DEFAULT 0.00,
  `transport_cost` decimal(10,2) DEFAULT 0.00,
  `cutting_cost` decimal(10,2) DEFAULT 0.00,
  `unit_price` decimal(10,2) DEFAULT 0.00,
  `total_price` decimal(12,2) DEFAULT 0.00,
  `profit_margin` decimal(5,2) DEFAULT 0.00,
  `is_template` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for document item finishing processes
--
CREATE TABLE `document_item_finishings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `document_item_id` bigint(20) UNSIGNED NOT NULL,
  `finishing_process_id` bigint(20) UNSIGNED NOT NULL,
  `quantity` decimal(10,2) DEFAULT 1.00,
  `is_double_sided` tinyint(1) DEFAULT 0,
  `unit_price` decimal(10,4) DEFAULT 0.0000,
  `total_price` decimal(12,2) DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- COMMUNICATION AND POSTS
-- --------------------------------------------------------

--
-- Table structure for posts/publications
--
CREATE TABLE `posts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(191) DEFAULT NULL,
  `content` text NOT NULL,
  `excerpt` text DEFAULT NULL,
  `featured_image` varchar(191) DEFAULT NULL,
  `status` enum('draft', 'published', 'archived') DEFAULT 'draft',
  `published_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- LARAVEL SPECIFIC TABLES
-- --------------------------------------------------------

--
-- Table structure for password resets
--
CREATE TABLE `password_reset_tokens` (
  `email` varchar(191) NOT NULL,
  `token` varchar(191) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for failed jobs
--
CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(191) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for personal access tokens
--
CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tokenable_type` varchar(191) NOT NULL,
  `tokenable_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(191) NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for migrations
--
CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(191) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- INDEXES AND CONSTRAINTS
-- --------------------------------------------------------

--
-- Indexes for countries
--
ALTER TABLE `countries`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `countries_code_unique` (`code`),
  ADD KEY `countries_is_active_index` (`is_active`);

--
-- Indexes for states
--
ALTER TABLE `states`
  ADD PRIMARY KEY (`id`),
  ADD KEY `states_country_id_foreign` (`country_id`),
  ADD KEY `states_is_active_index` (`is_active`);

--
-- Indexes for cities
--
ALTER TABLE `cities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cities_state_id_foreign` (`state_id`),
  ADD KEY `cities_is_active_index` (`is_active`);

--
-- Indexes for companies
--
ALTER TABLE `companies`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `companies_slug_unique` (`slug`),
  ADD KEY `companies_city_id_foreign` (`city_id`),
  ADD KEY `companies_state_id_foreign` (`state_id`),
  ADD KEY `companies_country_id_foreign` (`country_id`),
  ADD KEY `companies_is_active_index` (`is_active`);

--
-- Indexes for roles
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`);

--
-- Indexes for permissions
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`);

--
-- Indexes for role_has_permissions
--
ALTER TABLE `role_has_permissions`
  ADD PRIMARY KEY (`permission_id`,`role_id`),
  ADD KEY `role_has_permissions_role_id_foreign` (`role_id`);

--
-- Indexes for users
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`),
  ADD KEY `users_company_id_foreign` (`company_id`),
  ADD KEY `users_city_id_foreign` (`city_id`),
  ADD KEY `users_state_id_foreign` (`state_id`),
  ADD KEY `users_country_id_foreign` (`country_id`),
  ADD KEY `users_is_active_index` (`is_active`);

--
-- Indexes for model_has_roles
--
ALTER TABLE `model_has_roles`
  ADD PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  ADD KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`);

--
-- Indexes for model_has_permissions
--
ALTER TABLE `model_has_permissions`
  ADD PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  ADD KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`);

--
-- Indexes for company_settings
--
ALTER TABLE `company_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `company_settings_company_id_unique` (`company_id`);

--
-- Indexes for contacts
--
ALTER TABLE `contacts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `contacts_company_id_foreign` (`company_id`),
  ADD KEY `contacts_city_id_foreign` (`city_id`),
  ADD KEY `contacts_state_id_foreign` (`state_id`),
  ADD KEY `contacts_country_id_foreign` (`country_id`),
  ADD KEY `contacts_type_index` (`type`),
  ADD KEY `contacts_is_active_index` (`is_active`);

--
-- Indexes for measurement_units
--
ALTER TABLE `measurement_units`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `measurement_units_symbol_unique` (`symbol`),
  ADD KEY `measurement_units_type_index` (`type`);

--
-- Indexes for ctp_plates
--
ALTER TABLE `ctp_plates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ctp_plates_company_id_foreign` (`company_id`),
  ADD KEY `ctp_plates_supplier_id_foreign` (`supplier_id`),
  ADD KEY `ctp_plates_is_active_index` (`is_active`);

--
-- Indexes for printing_machines
--
ALTER TABLE `printing_machines`
  ADD PRIMARY KEY (`id`),
  ADD KEY `printing_machines_company_id_foreign` (`company_id`),
  ADD KEY `printing_machines_supplier_id_foreign` (`supplier_id`),
  ADD KEY `printing_machines_ctp_plate_id_foreign` (`ctp_plate_id`),
  ADD KEY `printing_machines_is_active_index` (`is_active`);

--
-- Indexes for papers
--
ALTER TABLE `papers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `papers_company_id_foreign` (`company_id`),
  ADD KEY `papers_supplier_id_foreign` (`supplier_id`),
  ADD KEY `papers_is_active_index` (`is_active`);

--
-- Indexes for finishing_processes
--
ALTER TABLE `finishing_processes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `finishing_processes_company_id_foreign` (`company_id`),
  ADD KEY `finishing_processes_supplier_id_foreign` (`supplier_id`),
  ADD KEY `finishing_processes_unit_id_foreign` (`unit_id`),
  ADD KEY `finishing_processes_is_active_index` (`is_active`);

--
-- Indexes for products
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `products_company_id_foreign` (`company_id`),
  ADD KEY `products_supplier_id_foreign` (`supplier_id`),
  ADD KEY `products_unit_id_foreign` (`unit_id`),
  ADD KEY `products_is_active_index` (`is_active`),
  ADD KEY `products_sku_index` (`sku`);

--
-- Indexes for document_types
--
ALTER TABLE `document_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `document_types_code_unique` (`code`);

--
-- Indexes for documents
--
ALTER TABLE `documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `documents_company_id_foreign` (`company_id`),
  ADD KEY `documents_user_id_foreign` (`user_id`),
  ADD KEY `documents_contact_id_foreign` (`contact_id`),
  ADD KEY `documents_document_type_id_foreign` (`document_type_id`),
  ADD KEY `documents_status_index` (`status`),
  ADD KEY `documents_date_index` (`date`);

--
-- Indexes for document_items
--
ALTER TABLE `document_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `document_items_document_id_foreign` (`document_id`),
  ADD KEY `document_items_printing_machine_id_foreign` (`printing_machine_id`),
  ADD KEY `document_items_paper_id_foreign` (`paper_id`);

--
-- Indexes for document_item_finishings
--
ALTER TABLE `document_item_finishings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `document_item_finishings_document_item_id_foreign` (`document_item_id`),
  ADD KEY `document_item_finishings_finishing_process_id_foreign` (`finishing_process_id`);

--
-- Indexes for posts
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `posts_company_id_foreign` (`company_id`),
  ADD KEY `posts_user_id_foreign` (`user_id`),
  ADD KEY `posts_status_index` (`status`);

--
-- Indexes for password_reset_tokens
--
ALTER TABLE `password_reset_tokens`
  ADD KEY `password_reset_tokens_email_index` (`email`);

--
-- Indexes for failed_jobs
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for personal_access_tokens
--
ALTER TABLE `personal_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  ADD KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`);

--
-- Indexes for migrations
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

-- --------------------------------------------------------
-- AUTO INCREMENT
-- --------------------------------------------------------

ALTER TABLE `countries` MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `states` MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `cities` MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `companies` MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `roles` MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `permissions` MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `users` MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `company_settings` MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `contacts` MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `measurement_units` MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `ctp_plates` MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `printing_machines` MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `papers` MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `finishing_processes` MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `products` MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `document_types` MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `documents` MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `document_items` MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `document_item_finishings` MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `posts` MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `failed_jobs` MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `personal_access_tokens` MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `migrations` MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

-- --------------------------------------------------------
-- FOREIGN KEY CONSTRAINTS
-- --------------------------------------------------------

--
-- Constraints for states
--
ALTER TABLE `states`
  ADD CONSTRAINT `states_country_id_foreign` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`) ON DELETE CASCADE;

--
-- Constraints for cities
--
ALTER TABLE `cities`
  ADD CONSTRAINT `cities_state_id_foreign` FOREIGN KEY (`state_id`) REFERENCES `states` (`id`) ON DELETE CASCADE;

--
-- Constraints for companies
--
ALTER TABLE `companies`
  ADD CONSTRAINT `companies_city_id_foreign` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `companies_state_id_foreign` FOREIGN KEY (`state_id`) REFERENCES `states` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `companies_country_id_foreign` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`) ON DELETE SET NULL;

--
-- Constraints for role_has_permissions
--
ALTER TABLE `role_has_permissions`
  ADD CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for users
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `users_city_id_foreign` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `users_state_id_foreign` FOREIGN KEY (`state_id`) REFERENCES `states` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `users_country_id_foreign` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`) ON DELETE SET NULL;

--
-- Constraints for model_has_roles
--
ALTER TABLE `model_has_roles`
  ADD CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for model_has_permissions
--
ALTER TABLE `model_has_permissions`
  ADD CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE;

--
-- Constraints for company_settings
--
ALTER TABLE `company_settings`
  ADD CONSTRAINT `company_settings_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

--
-- Constraints for contacts
--
ALTER TABLE `contacts`
  ADD CONSTRAINT `contacts_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `contacts_city_id_foreign` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `contacts_state_id_foreign` FOREIGN KEY (`state_id`) REFERENCES `states` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `contacts_country_id_foreign` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`) ON DELETE SET NULL;

--
-- Constraints for ctp_plates
--
ALTER TABLE `ctp_plates`
  ADD CONSTRAINT `ctp_plates_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ctp_plates_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `contacts` (`id`) ON DELETE SET NULL;

--
-- Constraints for printing_machines
--
ALTER TABLE `printing_machines`
  ADD CONSTRAINT `printing_machines_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `printing_machines_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `contacts` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `printing_machines_ctp_plate_id_foreign` FOREIGN KEY (`ctp_plate_id`) REFERENCES `ctp_plates` (`id`) ON DELETE RESTRICT;

--
-- Constraints for papers
--
ALTER TABLE `papers`
  ADD CONSTRAINT `papers_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `papers_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `contacts` (`id`) ON DELETE SET NULL;

--
-- Constraints for finishing_processes
--
ALTER TABLE `finishing_processes`
  ADD CONSTRAINT `finishing_processes_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `finishing_processes_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `contacts` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `finishing_processes_unit_id_foreign` FOREIGN KEY (`unit_id`) REFERENCES `measurement_units` (`id`) ON DELETE SET NULL;

--
-- Constraints for products
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `products_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `contacts` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `products_unit_id_foreign` FOREIGN KEY (`unit_id`) REFERENCES `measurement_units` (`id`) ON DELETE SET NULL;

--
-- Constraints for documents
--
ALTER TABLE `documents`
  ADD CONSTRAINT `documents_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `documents_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `documents_contact_id_foreign` FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `documents_document_type_id_foreign` FOREIGN KEY (`document_type_id`) REFERENCES `document_types` (`id`) ON DELETE RESTRICT;

--
-- Constraints for document_items
--
ALTER TABLE `document_items`
  ADD CONSTRAINT `document_items_document_id_foreign` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `document_items_printing_machine_id_foreign` FOREIGN KEY (`printing_machine_id`) REFERENCES `printing_machines` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `document_items_paper_id_foreign` FOREIGN KEY (`paper_id`) REFERENCES `papers` (`id`) ON DELETE SET NULL;

--
-- Constraints for document_item_finishings
--
ALTER TABLE `document_item_finishings`
  ADD CONSTRAINT `document_item_finishings_document_item_id_foreign` FOREIGN KEY (`document_item_id`) REFERENCES `document_items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `document_item_finishings_finishing_process_id_foreign` FOREIGN KEY (`finishing_process_id`) REFERENCES `finishing_processes` (`id`) ON DELETE RESTRICT;

--
-- Constraints for posts
--
ALTER TABLE `posts`
  ADD CONSTRAINT `posts_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `posts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

-- --------------------------------------------------------
-- TRIGGERS
-- --------------------------------------------------------

--
-- Trigger to create company settings after company creation
--
DELIMITER $
CREATE TRIGGER `create_company_settings` AFTER INSERT ON `companies` FOR EACH ROW 
BEGIN
    INSERT INTO `company_settings` (
        `company_id`, 
        `measurement_system`, 
        `quote_number_start`, 
        `order_number_start`, 
        `print_order_number_start`, 
        `profit_margin_percentage`, 
        `waste_percentage`, 
        `default_design_price`, 
        `default_transport_price`, 
        `default_cutting_price`, 
        `tax_rate`, 
        `currency`, 
        `timezone`,
        `created_at`, 
        `updated_at`
    ) VALUES (
        NEW.id, 
        'metric', 
        1, 
        1, 
        1, 
        20.00, 
        5.00, 
        0.00, 
        0.00, 
        0.00, 
        0.00, 
        'USD', 
        'UTC',
        NOW(), 
        NOW()
    );
END$
DELIMITER ;

-- --------------------------------------------------------
-- INITIAL DATA
-- --------------------------------------------------------

--
-- Insert default roles
--
INSERT INTO `roles` (`name`, `guard_name`, `is_system`, `created_at`, `updated_at`) VALUES
('Super Admin', 'web', 1, NOW(), NOW()),
('Company Admin', 'web', 1, NOW(), NOW()),
('Manager', 'web', 1, NOW(), NOW()),
('Employee', 'web', 1, NOW(), NOW()),
('Client', 'web', 1, NOW(), NOW());

--
-- Insert default permissions
--
INSERT INTO `permissions` (`name`, `guard_name`, `group`, `created_at`, `updated_at`) VALUES
-- Company Management
('view-companies', 'web', 'Companies', NOW(), NOW()),
('create-companies', 'web', 'Companies', NOW(), NOW()),
('edit-companies', 'web', 'Companies', NOW(), NOW()),
('delete-companies', 'web', 'Companies', NOW(), NOW()),
-- User Management
('view-users', 'web', 'Users', NOW(), NOW()),
('create-users', 'web', 'Users', NOW(), NOW()),
('edit-users', 'web', 'Users', NOW(), NOW()),
('delete-users', 'web', 'Users', NOW(), NOW()),
-- Contact Management
('view-contacts', 'web', 'Contacts', NOW(), NOW()),
('create-contacts', 'web', 'Contacts', NOW(), NOW()),
('edit-contacts', 'web', 'Contacts', NOW(), NOW()),
('delete-contacts', 'web', 'Contacts', NOW(), NOW()),
-- Document Management
('view-documents', 'web', 'Documents', NOW(), NOW()),
('create-documents', 'web', 'Documents', NOW(), NOW()),
('edit-documents', 'web', 'Documents', NOW(), NOW()),
('delete-documents', 'web', 'Documents', NOW(), NOW()),
('approve-documents', 'web', 'Documents', NOW(), NOW()),
-- Equipment Management
('view-equipment', 'web', 'Equipment', NOW(), NOW()),
('create-equipment', 'web', 'Equipment', NOW(), NOW()),
('edit-equipment', 'web', 'Equipment', NOW(), NOW()),
('delete-equipment', 'web', 'Equipment', NOW(), NOW()),
-- Product Management
('view-products', 'web', 'Products', NOW(), NOW()),
('create-products', 'web', 'Products', NOW(), NOW()),
('edit-products', 'web', 'Products', NOW(), NOW()),
('delete-products', 'web', 'Products', NOW(), NOW()),
-- Settings
('view-settings', 'web', 'Settings', NOW(), NOW()),
('edit-settings', 'web', 'Settings', NOW(), NOW());

--
-- Insert default document types
--
INSERT INTO `document_types` (`name`, `code`, `description`, `is_active`) VALUES
('Quote', 'QUO', 'Customer quotations and estimates', 1),
('Purchase Order', 'PO', 'Purchase orders to suppliers', 1),
('Work Order', 'WO', 'Internal work orders for production', 1),
('Invoice', 'INV', 'Customer invoices', 1),
('Credit Note', 'CN', 'Credit notes for returns/adjustments', 1);

--
-- Insert default measurement units
--
INSERT INTO `measurement_units` (`type`, `name`, `symbol`, `conversion_factor`, `is_base_unit`, `created_at`, `updated_at`) VALUES
-- Length units
('length', 'Millimeter', 'mm', 1.000000, 1, NOW(), NOW()),
('length', 'Centimeter', 'cm', 10.000000, 0, NOW(), NOW()),
('length', 'Meter', 'm', 1000.000000, 0, NOW(), NOW()),
('length', 'Inch', 'in', 25.400000, 0, NOW(), NOW()),
-- Weight units
('weight', 'Gram', 'g', 1.000000, 1, NOW(), NOW()),
('weight', 'Kilogram', 'kg', 1000.000000, 0, NOW(), NOW()),
('weight', 'Pound', 'lb', 453.592000, 0, NOW(), NOW()),
-- Area units
('area', 'Square Millimeter', 'mm²', 1.000000, 1, NOW(), NOW()),
('area', 'Square Centimeter', 'cm²', 100.000000, 0, NOW(), NOW()),
('area', 'Square Meter', 'm²', 1000000.000000, 0, NOW(), NOW()),
-- Quantity units
('quantity', 'Unit', 'pcs', 1.000000, 1, NOW(), NOW()),
('quantity', 'Dozen', 'dz', 12.000000, 0, NOW(), NOW()),
('quantity', 'Hundred', '100', 100.000000, 0, NOW(), NOW()),
('quantity', 'Thousand', '1000', 1000.000000, 0, NOW(), NOW());

--
-- Sample country data (Colombia)
--
INSERT INTO `countries` (`name`, `code`, `phone_code`, `is_active`, `created_at`, `updated_at`) VALUES
('Colombia', 'CO', '+57', 1, NOW(), NOW()),
('United States', 'US', '+1', 1, NOW(), NOW()),
('Mexico', 'MX', '+52', 1, NOW(), NOW());

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;