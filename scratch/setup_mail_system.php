<?php
require_once __DIR__ . '/../config/db.php';

// 1. Add notification_email column to careers table if not exists
$dbname = $pdo->query("SELECT DATABASE()")->fetchColumn();
$stmt_col = $pdo->prepare("
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'careers' AND COLUMN_NAME = 'notification_email'
");
$stmt_col->execute([$dbname]);
if ((int)$stmt_col->fetchColumn() === 0) {
    $pdo->exec("ALTER TABLE `careers` ADD COLUMN `notification_email` VARCHAR(255) DEFAULT NULL AFTER `hiring_manager_id`");
    echo "[OK] Added notification_email column to careers table.\n";
} else {
    echo "[OK] notification_email column already exists in careers table.\n";
}

// 2. Create mail_logs table
$sql_mail_logs = "
CREATE TABLE IF NOT EXISTS `mail_logs` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `sender_user_id` INT(11) DEFAULT NULL,
  `recipient_email` VARCHAR(255) NOT NULL,
  `recipient_name` VARCHAR(150) DEFAULT NULL,
  `subject` VARCHAR(255) NOT NULL,
  `body_content` TEXT NOT NULL,
  `mail_type` VARCHAR(80) NOT NULL DEFAULT 'custom',
  `vacancy_id` INT(11) DEFAULT NULL,
  `status` ENUM('sent', 'queued', 'failed') DEFAULT 'sent',
  `sent_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_recipient` (`recipient_email`),
  KEY `idx_mail_type` (`mail_type`),
  KEY `idx_sent_at` (`sent_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";
$pdo->exec($sql_mail_logs);
echo "[OK] mail_logs table created successfully.\n";
