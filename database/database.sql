--KeepNote Database Schema
--Run this file in your MySQL server

CREATE DATABASE IF NOT EXISTS keepnote_db;
USE keepnote_db;

--Users Table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin', 'super_admin') DEFAULT 'user',
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

--Notes Table
CREATE TABLE notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) DEFAULT '',
    content TEXT DEFAULT '',
    color VARCHAR(20) DEFAULT '#ffffff',
    is_pinned TINYINT(1) DEFAULT 0,
    is_archived TINYINT(1) DEFAULT 0,
    is_deleted TINYINT(1) DEFAULT 0,
    deleted_at DATETIME NULL DEFAULT NULL,
    created_at DATETIME DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

--Todo Lists Table
CREATE TABLE todos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) DEFAULT '',
    color VARCHAR(20) DEFAULT '#ffffff',
    is_pinned TINYINT(1) DEFAULT 0,
    is_archived TINYINT(1) DEFAULT 0,
    is_deleted TINYINT(1) DEFAULT 0,
    deleted_at DATETIME NULL DEFAULT NULL,
    created_at DATETIME DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

--Todo Items Table
CREATE TABLE todo_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    todo_id INT NOT NULL,
    content VARCHAR(500) NOT NULL,
    is_checked TINYINT(1) DEFAULT 0,
    sort_order INT DEFAULT 0,
    created_at DATETIME DEFAULT NULL,
    FOREIGN KEY (todo_id) REFERENCES todos(id) ON DELETE CASCADE
);

--Audit Logs Table (for Admin/Super Admin)
CREATE TABLE audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    actor_id INT NOT NULL,
    target_user_id INT NULL,
    action VARCHAR(100) NOT NULL,
    details TEXT NULL,
    created_at DATETIME DEFAULT NULL,
    FOREIGN KEY (actor_id) REFERENCES users(id) ON DELETE CASCADE
);

--Default Super Admin Account
--Password: superadmin123 (change this immediately after setup!)
INSERT INTO users (username, email, password, role, created_at) VALUES (
    'superadmin',
    'superadmin@keepnote.com',
    'b3124a3814772f36445c6faa5b2ec4e71a90ef44',
    'super_admin',
    NOW()
);
