CREATE DATABASE IF NOT EXISTS email_marketing_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE email_marketing_system;

CREATE TABLE IF NOT EXISTS audience (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    phone VARCHAR(50) DEFAULT NULL,
    birthday DATE DEFAULT NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    tags VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS campaigns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    body MEDIUMTEXT NOT NULL,
    shortcodes VARCHAR(255) DEFAULT '{name},{email},{phone}',
    status ENUM('draft','sent') NOT NULL DEFAULT 'draft',
    sent_count INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS campaign_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT NULL,
    audience_id INT NULL,
    email VARCHAR(190) NOT NULL,
    status ENUM('success','failed') NOT NULL,
    error_message TEXT NULL,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_logs_campaign FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE SET NULL,
    CONSTRAINT fk_logs_audience FOREIGN KEY (audience_id) REFERENCES audience(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
