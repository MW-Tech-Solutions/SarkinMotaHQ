-- Migration 02: System Settings and Global Branding Table
-- Enterprise Edition - Sarkin Mota HQ

CREATE TABLE IF NOT EXISTS system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT NULL,
    setting_type ENUM('text', 'image', 'color', 'boolean', 'enum') NOT NULL DEFAULT 'text',
    setting_group ENUM('general', 'branding', 'colors', 'theme') NOT NULL DEFAULT 'general',
    is_public TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by VARCHAR(255) NULL,
    INDEX idx_setting_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default system settings if missing
INSERT IGNORE INTO system_settings (setting_key, setting_value, setting_type, setting_group, is_public) VALUES
('company_name', 'Sarkin Mota HQ', 'text', 'general', 1),
('company_short_name', 'Sarkin Mota HQ', 'text', 'general', 1),
('company_tagline', 'Enterprise Real Estate & Strategic Consulting', 'text', 'general', 1),
('company_logo', 'assets/images/logo.png', 'image', 'branding', 1),
('company_logo_dark', 'assets/images/logo.png', 'image', 'branding', 1),
('favicon', 'assets/images/favicon.ico', 'image', 'branding', 1),
('company_email', 'contact@sarkinmotahq.com', 'text', 'general', 1),
('company_phone', '+234 800 5363 284', 'text', 'general', 1),
('company_address', 'Victoria Island, Lagos, Nigeria', 'text', 'general', 1),
('website_url', 'http://localhost/SarkinMota', 'text', 'general', 1),

('primary_color', '#f59e0b', 'color', 'colors', 1),
('primary_hover_color', '#d97706', 'color', 'colors', 1),
('secondary_color', '#0f172a', 'color', 'colors', 1),
('accent_color', '#eab308', 'color', 'colors', 1),
('header_bg', '#ffffff', 'color', 'colors', 1),
('header_text', '#0f172a', 'color', 'colors', 1),
('sidebar_bg', '#0f172a', 'color', 'colors', 1),
('sidebar_text', '#f8fafc', 'color', 'colors', 1),
('button_color', '#f59e0b', 'color', 'colors', 1),
('button_text', '#0f172a', 'color', 'colors', 1),
('link_color', '#d97706', 'color', 'colors', 1),
('footer_bg', '#020617', 'color', 'colors', 1),
('footer_text', '#94a3b8', 'color', 'colors', 1),

('default_theme', 'system', 'enum', 'theme', 1),
('allow_theme_switching', '1', 'boolean', 'theme', 1),
('supported_theme_modes', 'light_dark', 'enum', 'theme', 1);

-- Map permission 'settings.branding.manage' to super_admin role
INSERT IGNORE INTO permissions (name, label, category) VALUES
('settings.branding.manage', 'Manage Global Branding & Theme Settings', 'settings');

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r, permissions p
WHERE r.name = 'super_admin' AND p.name = 'settings.branding.manage';

