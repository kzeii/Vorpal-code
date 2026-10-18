-- Import this file in phpMyAdmin (Import tab).
CREATE DATABASE IF NOT EXISTS sun_son_solar CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sun_son_solar;

CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    middle_name VARCHAR(100) NULL,
    last_name VARCHAR(100) NOT NULL,
    birthdate DATE NOT NULL,
    gender ENUM('Female','Male','Prefer not to say') NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    phone VARCHAR(40) NULL,
    address VARCHAR(255) NOT NULL,
    username VARCHAR(60) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('customer','employee','admin') NOT NULL DEFAULT 'customer',
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    must_change_password TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_users_role_status (role, status)
) ENGINE=InnoDB;

ALTER TABLE users MODIFY role ENUM('customer','employee','admin') NOT NULL DEFAULT 'customer';
ALTER TABLE users ADD COLUMN IF NOT EXISTS must_change_password TINYINT(1) NOT NULL DEFAULT 0 AFTER status;

CREATE TABLE IF NOT EXISTS service_requests (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    subject VARCHAR(160) NOT NULL,
    category ENUM('Installation','Maintenance','Billing','Other') NOT NULL DEFAULT 'Other',
    details TEXT NOT NULL,
    status ENUM('Open','In Progress','Resolved','Closed') NOT NULL DEFAULT 'Open',
    admin_note TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_service_requests_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_service_requests_status (status),
    INDEX idx_service_requests_user_created (user_id, created_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS solar_packages (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(80) NOT NULL UNIQUE,
    name VARCHAR(120) NOT NULL,
    capacity_kw DECIMAL(5,2) NOT NULL,
    price_php DECIMAL(12,2) NOT NULL,
    monthly_savings_php DECIMAL(12,2) NOT NULL,
    description VARCHAR(255) NOT NULL,
    warranty_years TINYINT UNSIGNED NOT NULL DEFAULT 10,
    features TEXT NOT NULL,
    badge VARCHAR(60) NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    display_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT IGNORE INTO solar_packages (slug, name, capacity_kw, price_php, monthly_savings_php, description, warranty_years, features, badge, display_order) VALUES
('sunrise-5kw', 'Sunrise', 5.00, 285000, 3200, 'A smart starting point for smaller homes and lighter daytime use.', 10, '5 kW hybrid-ready system|High-efficiency solar panels|Smart monitoring|Professional site assessment', 'Great for getting started', 1),
('daylight-8kw', 'Daylight', 8.00, 425000, 5600, 'Balanced power for busy family homes that want more control over bills.', 12, '8 kW hybrid-ready system|Premium high-efficiency panels|App-based monitoring|Installation and commissioning', 'Most popular', 2),
('horizon-12kw', 'Horizon', 12.00, 625000, 8400, 'More capacity for larger homes and higher energy demand.', 15, '12 kW hybrid-ready system|Premium panel and inverter package|Priority installation planning|Extended workmanship coverage', 'Maximum home coverage', 3);

CREATE TABLE IF NOT EXISTS quote_requests (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    package_id BIGINT UNSIGNED NULL,
    monthly_bill DECIMAL(12,2) NOT NULL,
    roof_type ENUM('Metal','Concrete','Tile','Other') NOT NULL,
    message VARCHAR(2000) NULL,
    status ENUM('New','Contacted','Site Survey','Proposal Sent','Won','Lost') NOT NULL DEFAULT 'New',
    admin_note VARCHAR(2000) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_quote_requests_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_quote_requests_package FOREIGN KEY (package_id) REFERENCES solar_packages(id) ON DELETE SET NULL,
    INDEX idx_quote_requests_status_created (status, created_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS installations (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    package_id BIGINT UNSIGNED NOT NULL,
    site_address VARCHAR(255) NOT NULL,
    status ENUM('Survey Scheduled','Design','Permitting','Installation','Inspection','Completed','Cancelled') NOT NULL DEFAULT 'Survey Scheduled',
    progress TINYINT UNSIGNED NOT NULL DEFAULT 0,
    scheduled_date DATE NULL,
    admin_note VARCHAR(2000) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_installations_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_installations_package FOREIGN KEY (package_id) REFERENCES solar_packages(id),
    INDEX idx_installations_status (status)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS activity_log (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    actor_user_id BIGINT UNSIGNED NULL,
    customer_user_id BIGINT UNSIGNED NULL,
    entity_type VARCHAR(40) NOT NULL,
    entity_id BIGINT UNSIGNED NULL,
    event_type VARCHAR(60) NOT NULL,
    description VARCHAR(500) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_activity_actor FOREIGN KEY (actor_user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_activity_customer FOREIGN KEY (customer_user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_activity_created (created_at),
    INDEX idx_activity_customer_created (customer_user_id, created_at)
) ENGINE=InnoDB;

-- Create the first administrator using the application's password_hash() output.
-- Do not store a plain text password in this database.
