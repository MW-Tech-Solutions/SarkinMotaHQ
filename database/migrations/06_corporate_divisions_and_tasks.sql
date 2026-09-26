-- Migration: 06_corporate_divisions_and_tasks.sql
-- Enterprise Corporate Divisions, Scalable Projects, Staff Assignments, Task Management & Reviews

-- 1. Corporate Divisions Table
CREATE TABLE IF NOT EXISTS `corporate_divisions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(150) NOT NULL UNIQUE,
    `slug` VARCHAR(150) NOT NULL UNIQUE,
    `code` VARCHAR(20) NOT NULL UNIQUE,
    `description` TEXT NULL,
    `icon` VARCHAR(100) DEFAULT 'bi-building',
    `image` VARCHAR(255) NULL,
    `status` ENUM('active', 'inactive') DEFAULT 'active',
    `created_by` INT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed Initial Divisions: Real Estate and Automobile / Car Sales
INSERT IGNORE INTO `corporate_divisions` (`id`, `name`, `slug`, `code`, `description`, `icon`, `status`) VALUES
(1, 'Real Estate', 'real-estate', 'RE', 'Corporate real estate, estate management, land acquisitions, and commercial property development.', 'bi-houses-fill', 'active'),
(2, 'Automobile / Car Sales', 'automobile', 'AUTO', 'Vehicle procurement, fleet management, automotive sales, and inspection services.', 'bi-car-front-fill', 'active'),
(3, 'Agriculture & Agronomy', 'agriculture', 'AGR', 'Commercial crop farming, soil valuation, agribusiness feasibility, and farm management.', 'bi-flower1', 'active'),
(4, 'Environmental Consulting', 'environmental', 'ENV', 'Environmental Impact Assessment (EIA), eco-auditing, and sustainable resource management.', 'bi-tree-fill', 'active'),
(5, 'Development Consulting', 'development', 'DEV', 'Urban planning, municipal infrastructure, traffic grid mapping, and civil advisory.', 'bi-tools', 'active');

-- 2. User Divisions (Many-to-Many Staff Division Assignment)
CREATE TABLE IF NOT EXISTS `user_divisions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `division_id` INT NOT NULL,
    `assigned_by` INT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_user_division` (`user_id`, `division_id`),
    CONSTRAINT `fk_user_div_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_user_div_div` FOREIGN KEY (`division_id`) REFERENCES `corporate_divisions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Corporate Projects Table
CREATE TABLE IF NOT EXISTS `corporate_projects` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `division_id` INT NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `reference_number` VARCHAR(50) NOT NULL UNIQUE,
    `description` LONGTEXT NULL,
    `status` ENUM('draft', 'active', 'on_hold', 'completed', 'archived') DEFAULT 'active',
    `priority` ENUM('Low', 'Normal', 'High', 'Urgent') DEFAULT 'Normal',
    `created_by` INT NULL,
    `assigned_manager_id` INT NULL,
    `start_date` DATE NULL,
    `due_date` DATE NULL,
    `completed_at` DATETIME NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_proj_division` FOREIGN KEY (`division_id`) REFERENCES `corporate_divisions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Real Estate Project Specific Details
CREATE TABLE IF NOT EXISTS `real_estate_project_details` (
    `project_id` INT PRIMARY KEY,
    `property_type` VARCHAR(50) DEFAULT 'residential',
    `transaction_type` VARCHAR(50) DEFAULT 'sale',
    `location` VARCHAR(255) NULL,
    `state` VARCHAR(100) NULL,
    `city` VARCHAR(100) NULL,
    `address` TEXT NULL,
    `price` DECIMAL(15, 2) DEFAULT 0.00,
    `currency` VARCHAR(10) DEFAULT 'NGN',
    `property_size` INT DEFAULT 0,
    `bedrooms` INT DEFAULT 0,
    `bathrooms` INT DEFAULT 0,
    `parking` INT DEFAULT 0,
    `land_size` INT DEFAULT 0,
    `availability` VARCHAR(50) DEFAULT 'available',
    `is_featured` TINYINT(1) DEFAULT 0,
    `cover_image` VARCHAR(255) NULL,
    `gallery` LONGTEXT NULL,
    CONSTRAINT `fk_re_details_project` FOREIGN KEY (`project_id`) REFERENCES `corporate_projects`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Automobile Project Specific Details
CREATE TABLE IF NOT EXISTS `automobile_project_details` (
    `project_id` INT PRIMARY KEY,
    `make` VARCHAR(100) NOT NULL,
    `model` VARCHAR(100) NOT NULL,
    `year` INT NOT NULL,
    `vehicle_type` VARCHAR(50) DEFAULT 'SUV',
    `transmission` VARCHAR(50) DEFAULT 'Automatic',
    `fuel_type` VARCHAR(50) DEFAULT 'Petrol',
    `mileage` VARCHAR(50) DEFAULT '0 km',
    `color` VARCHAR(50) NULL,
    `vehicle_condition` VARCHAR(50) DEFAULT 'Brand New',
    `engine` VARCHAR(100) NULL,
    `vin` VARCHAR(100) NULL,
    `price` DECIMAL(15, 2) DEFAULT 0.00,
    `currency` VARCHAR(10) DEFAULT 'NGN',
    `location` VARCHAR(255) NULL,
    `availability` VARCHAR(50) DEFAULT 'available',
    `is_featured` TINYINT(1) DEFAULT 0,
    `cover_image` VARCHAR(255) NULL,
    `gallery` LONGTEXT NULL,
    CONSTRAINT `fk_auto_details_project` FOREIGN KEY (`project_id`) REFERENCES `corporate_projects`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Project Staff Assignment
CREATE TABLE IF NOT EXISTS `project_staff` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `project_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `assigned_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_proj_staff` (`project_id`, `user_id`),
    CONSTRAINT `fk_pstaff_proj` FOREIGN KEY (`project_id`) REFERENCES `corporate_projects`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_pstaff_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Staff Tasks Table
CREATE TABLE IF NOT EXISTS `staff_tasks` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `task_reference` VARCHAR(50) NOT NULL UNIQUE,
    `division_id` INT NOT NULL,
    `project_id` INT NULL,
    `title` VARCHAR(255) NOT NULL,
    `description` LONGTEXT NULL,
    `instructions` LONGTEXT NULL,
    `priority` ENUM('Low', 'Normal', 'High', 'Urgent') DEFAULT 'Normal',
    `status` ENUM('Assigned', 'Acknowledged', 'In Progress', 'Submitted', 'Under Review', 'Accepted', 'Rejected', 'Revision Required', 'Completed', 'Cancelled', 'Overdue') DEFAULT 'Assigned',
    `start_date` DATE NULL,
    `due_date` DATE NULL,
    `created_by` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_task_division` FOREIGN KEY (`division_id`) REFERENCES `corporate_divisions`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_task_project` FOREIGN KEY (`project_id`) REFERENCES `corporate_projects`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. Task Assignees (Individual Accountability for Single/Multiple Staff)
CREATE TABLE IF NOT EXISTS `task_assignees` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `task_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `status` ENUM('Assigned', 'Acknowledged', 'In Progress', 'Submitted', 'Under Review', 'Accepted', 'Rejected', 'Revision Required', 'Completed') DEFAULT 'Assigned',
    `acknowledged_at` DATETIME NULL,
    `started_at` DATETIME NULL,
    `submitted_at` DATETIME NULL,
    `reviewed_at` DATETIME NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_task_user` (`task_id`, `user_id`),
    CONSTRAINT `fk_tassign_task` FOREIGN KEY (`task_id`) REFERENCES `staff_tasks`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_tassign_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9. Task Submissions History
CREATE TABLE IF NOT EXISTS `task_submissions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `task_id` INT NOT NULL,
    `staff_id` INT NOT NULL,
    `submission_number` INT DEFAULT 1,
    `summary` VARCHAR(255) NOT NULL,
    `report` LONGTEXT NOT NULL,
    `challenges` TEXT NULL,
    `comments` TEXT NULL,
    `status` ENUM('Submitted', 'Under Review', 'Accepted', 'Rejected', 'Revision Required') DEFAULT 'Submitted',
    `submitted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_tsub_task` FOREIGN KEY (`task_id`) REFERENCES `staff_tasks`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_tsub_staff` FOREIGN KEY (`staff_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 10. Task Reviews History
CREATE TABLE IF NOT EXISTS `task_reviews` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `task_submission_id` INT NOT NULL,
    `task_id` INT NOT NULL,
    `reviewed_by` INT NOT NULL,
    `decision` ENUM('accepted', 'rejected', 'revision_required') NOT NULL,
    `reason` LONGTEXT NOT NULL,
    `comments` TEXT NULL,
    `reviewed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_trev_sub` FOREIGN KEY (`task_submission_id`) REFERENCES `task_submissions`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_trev_task` FOREIGN KEY (`task_id`) REFERENCES `staff_tasks`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 11. Task Attachments Table
CREATE TABLE IF NOT EXISTS `task_attachments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `task_id` INT NOT NULL,
    `task_submission_id` INT NULL,
    `uploaded_by` INT NOT NULL,
    `original_name` VARCHAR(255) NOT NULL,
    `stored_name` VARCHAR(255) NOT NULL,
    `mime_type` VARCHAR(100) NOT NULL,
    `size` INT NOT NULL,
    `path` VARCHAR(255) NOT NULL,
    `attachment_type` ENUM('assignment', 'submission', 'review') DEFAULT 'submission',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_tatt_task` FOREIGN KEY (`task_id`) REFERENCES `staff_tasks`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_tatt_sub` FOREIGN KEY (`task_submission_id`) REFERENCES `task_submissions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 12. In-App Notifications Table
CREATE TABLE IF NOT EXISTS `notifications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `message` TEXT NOT NULL,
    `link` VARCHAR(255) NULL,
    `type` VARCHAR(50) DEFAULT 'info',
    `is_read` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed Task Permissions into RBAC Matrix
INSERT IGNORE INTO `permissions` (`name`, `label`, `category`) VALUES
('manage_divisions', 'Manage Corporate Divisions', 'divisions'),
('view_all_divisions', 'View All Divisions', 'divisions'),
('manage_all_projects', 'Manage All Corporate Projects', 'projects'),
('view_division_projects', 'View Division Projects', 'projects'),
('create_project', 'Create Division Project', 'projects'),
('edit_project', 'Edit Division Project', 'projects'),
('delete_project', 'Delete Division Project', 'projects'),
('manage_staff', 'Manage Staff & Division Assignments', 'staff'),
('assign_staff_division', 'Assign Staff to Divisions', 'staff'),
('assign_tasks', 'Assign Tasks to Staff', 'tasks'),
('view_all_tasks', 'View All System Tasks', 'tasks'),
('view_assigned_tasks', 'View My Assigned Tasks', 'tasks'),
('submit_task_report', 'Submit Task Work Report', 'tasks'),
('review_task_report', 'Review Task Submissions (Accept/Reject)', 'tasks'),
('approve_task_report', 'Approve Task Submissions', 'tasks'),
('reject_task_report', 'Reject Task Submissions', 'tasks');

-- Assign Super Admin & Admin all new permissions
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id FROM `roles` r JOIN `permissions` p WHERE r.name IN ('super_admin', 'admin');

-- Assign Staff task viewing and submission permissions
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id FROM `roles` r JOIN `permissions` p 
WHERE r.name IN ('staff', 'hr_manager', 'hr_officer') 
AND p.name IN ('view_assigned_tasks', 'submit_task_report', 'view_division_projects');
