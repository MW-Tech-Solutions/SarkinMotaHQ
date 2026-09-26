-- Sarkin Mota HQ Enterprise Database Migration 04 - Corporate Team Members

CREATE TABLE IF NOT EXISTS `team_members` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(120) NOT NULL,
  `role` VARCHAR(150) NOT NULL,
  `bio` TEXT NULL,
  `image_url` VARCHAR(255) NULL,
  `sort_order` INT(11) NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Initial default seeds
INSERT INTO `team_members` (`id`, `name`, `role`, `bio`, `image_url`, `sort_order`, `is_active`) VALUES
(1, 'Dr. Ken Davies', 'Managing Consultant & Agribusiness Director', 'Lead advisor for agricultural investments and credit feasibility audits, with over 15 years experience.', 'https://images.unsplash.com/photo-1560250097-0b93528c311a?auto=format&fit=crop&w=300&q=80', 1, 1),
(2, 'Arc. Aminu Yola', 'Director of Estate & Property Valuations', 'NIESV registered architect and property valuer supervising structural audits and asset portfolios.', 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?auto=format&fit=crop&w=300&q=80', 2, 1),
(3, 'Engr. Fatima Bello', 'Head of Environmental Auditing', 'COREN certified environmental engineer managing EIA reports, waste audits, and post-impact mitigation programs.', 'https://images.unsplash.com/photo-1519085360753-af0119f7cbe7?auto=format&fit=crop&w=300&q=80', 3, 1)
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`), `role`=VALUES(`role`);
