-- Sarkin Mota HQ Enterprise Database Migration v2.0

-- 1. Users Table Enhancements
ALTER TABLE `users` 
  ADD COLUMN IF NOT EXISTS `session_token` VARCHAR(64) NULL AFTER `role`,
  ADD COLUMN IF NOT EXISTS `status` ENUM('active','suspended','disabled') NOT NULL DEFAULT 'active' AFTER `session_token`,
  ADD COLUMN IF NOT EXISTS `phone` VARCHAR(30) NULL AFTER `status`,
  ADD COLUMN IF NOT EXISTS `theme_preference` VARCHAR(20) DEFAULT 'system' AFTER `phone`,
  ADD COLUMN IF NOT EXISTS `password_reset_token` VARCHAR(64) NULL AFTER `theme_preference`,
  ADD COLUMN IF NOT EXISTS `password_reset_expires` DATETIME NULL AFTER `password_reset_token`;

-- 2. RBAC Tables
CREATE TABLE IF NOT EXISTS `roles` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(50) NOT NULL UNIQUE,
  `label` VARCHAR(100) NOT NULL,
  `description` VARCHAR(255) NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `permissions` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL UNIQUE,
  `label` VARCHAR(150) NOT NULL,
  `category` VARCHAR(50) DEFAULT 'general',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `role_permissions` (
  `role_id` INT(11) NOT NULL,
  `permission_id` INT(11) NOT NULL,
  PRIMARY KEY (`role_id`, `permission_id`),
  CONSTRAINT `fk_rp_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rp_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `user_roles` (
  `user_id` INT(11) NOT NULL,
  `role_id` INT(11) NOT NULL,
  PRIMARY KEY (`user_id`, `role_id`),
  CONSTRAINT `fk_ur_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ur_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Domain Entities (Landlords & Tenants)
CREATE TABLE IF NOT EXISTS `landlords` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL UNIQUE,
  `company_name` VARCHAR(150) NULL,
  `tax_id` VARCHAR(50) NULL,
  `phone` VARCHAR(30) NULL,
  `address` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_landlord_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tenants` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL UNIQUE,
  `emergency_contact` VARCHAR(150) NULL,
  `employer` VARCHAR(150) NULL,
  `identity_no` VARCHAR(50) NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_tenant_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Properties Table Enhancements
ALTER TABLE `properties`
  ADD COLUMN IF NOT EXISTS `landlord_id` INT(11) NULL AFTER `id`,
  ADD COLUMN IF NOT EXISTS `latitude` DECIMAL(10,8) DEFAULT 9.07650000 AFTER `location`,
  ADD COLUMN IF NOT EXISTS `longitude` DECIMAL(11,8) DEFAULT 7.39860000 AFTER `latitude`,
  ADD COLUMN IF NOT EXISTS `listing_status` ENUM('draft','pending_review','active','rented','sold','inactive','archived') NOT NULL DEFAULT 'active' AFTER `status`;

-- 5. Leases Table
CREATE TABLE IF NOT EXISTS `leases` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `property_id` INT(11) NOT NULL,
  `landlord_id` INT(11) NOT NULL,
  `tenant_id` INT(11) NOT NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `rent_amount` DECIMAL(15,2) NOT NULL,
  `deposit_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `status` ENUM('active','expired','terminated','pending') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_lease_property` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_lease_landlord` FOREIGN KEY (`landlord_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_lease_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Payment Transactions Architecture
CREATE TABLE IF NOT EXISTS `payment_transactions` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `transaction_ref` VARCHAR(100) NOT NULL UNIQUE,
  `provider_ref` VARCHAR(100) NULL,
  `user_id` INT(11) NOT NULL,
  `tenant_id` INT(11) NULL,
  `lease_id` INT(11) NULL,
  `property_id` INT(11) NULL,
  `expected_amount` DECIMAL(15,2) NOT NULL,
  `received_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `currency` VARCHAR(10) NOT NULL DEFAULT 'NGN',
  `provider` ENUM('Paystack','Flutterwave','Bank Transfer','USSD') NOT NULL,
  `status` ENUM('pending','processing','paid','failed','cancelled','refunded') NOT NULL DEFAULT 'pending',
  `initiated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `verified_at` DATETIME NULL,
  `webhook_data` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tx_ref` (`transaction_ref`),
  KEY `idx_user_tx` (`user_id`),
  CONSTRAINT `fk_tx_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Inspection Requests Table
CREATE TABLE IF NOT EXISTS `inspection_requests` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `property_id` INT(11) NOT NULL,
  `user_id` INT(11) NULL,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `phone` VARCHAR(30) NOT NULL,
  `preferred_date` DATE NOT NULL,
  `preferred_time` TIME NOT NULL,
  `inspection_type` ENUM('in_person','virtual') NOT NULL DEFAULT 'in_person',
  `status` ENUM('requested','under_review','scheduled','confirmed','completed','cancelled') NOT NULL DEFAULT 'requested',
  `notes` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_insp_property` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Consultation Requests Table
CREATE TABLE IF NOT EXISTS `consultation_requests` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NULL,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `phone` VARCHAR(30) NOT NULL,
  `division` ENUM('agriculture','estate','environmental','development') NOT NULL,
  `preferred_date` DATE NOT NULL,
  `message` TEXT NOT NULL,
  `status` ENUM('unread','contacted','in_progress','completed') NOT NULL DEFAULT 'unread',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Support Tickets Table
CREATE TABLE IF NOT EXISTS `support_tickets` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `subject` VARCHAR(200) NOT NULL,
  `category` VARCHAR(100) DEFAULT 'General',
  `priority` ENUM('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
  `status` ENUM('open','in_progress','resolved','closed') NOT NULL DEFAULT 'open',
  `message` TEXT NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_ticket_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

