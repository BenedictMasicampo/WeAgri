CREATE DATABASE IF NOT EXISTS weagri CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE weagri;

CREATE TABLE IF NOT EXISTS farmers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(120) NOT NULL,
    location VARCHAR(160) NOT NULL,
    primary_crop VARCHAR(80) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS experts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(120) NOT NULL,
    specialty VARCHAR(120) NOT NULL,
    status ENUM('online', 'busy', 'offline') NOT NULL DEFAULT 'online',
    response_minutes INT NOT NULL DEFAULT 10,
    bio VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(120) NOT NULL,
    email VARCHAR(160) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'farmer', 'consultant') NOT NULL,
    linked_farmer_id INT NULL,
    linked_expert_id INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_users_farmer FOREIGN KEY (linked_farmer_id) REFERENCES farmers(id) ON DELETE SET NULL,
    CONSTRAINT fk_users_expert FOREIGN KEY (linked_expert_id) REFERENCES experts(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS consultations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    farmer_id INT NOT NULL,
    title VARCHAR(180) NOT NULL,
    crop VARCHAR(80) NOT NULL,
    category VARCHAR(80) NOT NULL,
    urgency ENUM('low', 'medium', 'high', 'critical') NOT NULL DEFAULT 'medium',
    status ENUM('ai_triage', 'expert_assigned', 'monitoring', 'resolved') NOT NULL DEFAULT 'ai_triage',
    location VARCHAR(160) NOT NULL,
    assigned_expert_id INT NULL,
    summary TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_consultations_farmer FOREIGN KEY (farmer_id) REFERENCES farmers(id),
    CONSTRAINT fk_consultations_expert FOREIGN KEY (assigned_expert_id) REFERENCES experts(id)
);

CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    consultation_id INT NOT NULL,
    sender_type ENUM('farmer', 'ai', 'expert') NOT NULL,
    sender_name VARCHAR(120) NOT NULL,
    message TEXT NOT NULL,
    `references` TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_messages_consultation FOREIGN KEY (consultation_id) REFERENCES consultations(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(180) NOT NULL,
    body TEXT NOT NULL,
    type ENUM('advisory', 'consultation', 'system', 'weather') NOT NULL DEFAULT 'consultation',
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    consultation_id INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notifications_consultation FOREIGN KEY (consultation_id) REFERENCES consultations(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS knowledge_base (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(180) NOT NULL,
    topic VARCHAR(120) NOT NULL,
    content TEXT NOT NULL,
    recommendations TEXT NOT NULL,
    tags TEXT NOT NULL,
    source VARCHAR(180) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

