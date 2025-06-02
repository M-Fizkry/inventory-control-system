-- Create database
CREATE DATABASE IF NOT EXISTS inventory_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE inventory_system;

-- Users table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL,
    last_login DATETIME,
    status TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- User access rights
CREATE TABLE user_access (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    menu_id VARCHAR(50) NOT NULL,
    can_view TINYINT(1) DEFAULT 0,
    can_edit TINYINT(1) DEFAULT 0,
    can_delete TINYINT(1) DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Items table
CREATE TABLE items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    code VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    type ENUM('raw', 'wip', 'finished') NOT NULL,
    min_stock DECIMAL(10,2) NOT NULL DEFAULT 0,
    max_stock DECIMAL(10,2) NOT NULL DEFAULT 0,
    current_stock DECIMAL(10,2) NOT NULL DEFAULT 0,
    unit VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Bill of Materials (BOM)
CREATE TABLE bom (
    id INT PRIMARY KEY AUTO_INCREMENT,
    finished_item_id INT NOT NULL,
    component_item_id INT NOT NULL,
    quantity DECIMAL(10,2) NOT NULL,
    unit VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (finished_item_id) REFERENCES items(id) ON DELETE CASCADE,
    FOREIGN KEY (component_item_id) REFERENCES items(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Production Plans
CREATE TABLE production_plans (
    id INT PRIMARY KEY AUTO_INCREMENT,
    plan_type ENUM('1', '2', '3') NOT NULL,
    plan_code VARCHAR(10) NOT NULL,
    description TEXT,
    status ENUM('draft', 'active', 'completed', 'cancelled') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Production Items
CREATE TABLE production_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    plan_id INT NOT NULL,
    item_id INT NOT NULL,
    quantity DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (plan_id) REFERENCES production_plans(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Settings
CREATE TABLE settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(50) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Language strings
CREATE TABLE language_strings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    language_code VARCHAR(5) NOT NULL,
    string_key VARCHAR(100) NOT NULL,
    string_value TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY (language_code, string_key)
) ENGINE=InnoDB;

-- Insert default admin user (password: admin123)
INSERT INTO users (username, password, role) VALUES 
('admin', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN.jf9G.5/MYnGHGRHEei', 'admin');

-- Insert default settings
INSERT INTO settings (setting_key, setting_value, setting_type) VALUES
('system_name', 'Inventory Control System', 'string'),
('system_logo', 'logo.png', 'file'),
('default_language', 'en', 'string');

-- Create indexes
CREATE INDEX idx_items_type ON items(type);
CREATE INDEX idx_bom_finished_item ON bom(finished_item_id);
CREATE INDEX idx_production_plan_type ON production_plans(plan_type);
CREATE INDEX idx_production_items_status ON production_items(status);
