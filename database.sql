-- CDRRMO Inventory System Database Schema
-- Run this SQL script to create the database and tables

CREATE DATABASE IF NOT EXISTS cdrrmo_inventory;
USE cdrrmo_inventory;

-- Users table for authentication
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'staff') DEFAULT 'staff',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Categories table
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(50) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Inventory items table
CREATE TABLE IF NOT EXISTS inventory_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    item_description TEXT NOT NULL,
    unit VARCHAR(50),
    items_received INT DEFAULT 0,
    items_distributed INT DEFAULT 0,
    items_on_hand INT DEFAULT 0,
    expiration_date DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
);

-- Transaction history table
CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    transaction_type ENUM('received', 'distributed', 'adjustment') NOT NULL,
    quantity INT NOT NULL,
    transaction_date DATE NOT NULL,
    notes TEXT,
    user_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES inventory_items(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Insert default categories
INSERT INTO categories (category_name, description) VALUES
('OFFICE', 'Office Supplies and Equipment'),
('MEDICAL', 'Medical Supplies and Equipment'),
('SRR', 'Search, Rescue and Relief Equipment'),
('FOOD', 'Food and Beverage Supplies');

-- Insert default admin user (password: admin123)
INSERT INTO users (username, password, full_name, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin');

-- Sample inventory items for OFFICE category
INSERT INTO inventory_items (category_id, item_description, unit, items_received, items_distributed, items_on_hand) VALUES
(1, 'Book paper 70 GSM 8.5 inch x 13 inch, sub', 'reams', 50, 30, 20),
(1, 'Book paper 70 GSM A4 8.3 inches x 11.7 inch sub 20', 'reams', 50, 30, 20),
(1, 'Book paper 70 GSM A4 8.5 inches x 11 inch sub 20', 'reams', 45, 30, 15),
(1, 'Printer Ink Bottle Refill 003 "Black" 65ml', 'bottles', 15, 15, 0),
(1, 'Printer Ink Bottle Refill 003 "Cyan" 65ml', 'bottles', 6, 6, 0),
(1, 'Printer Ink Bottle Refill 003 "Yellow" 65ml', 'bottles', 6, 6, 0);

-- Sample inventory items for MEDICAL category
INSERT INTO inventory_items (category_id, item_description, unit, items_received, items_distributed, items_on_hand) VALUES
(2, 'Arm Sling (Large)', 'pcs', 20, 15, 5),
(2, 'Arm Sling (Small)', 'pcs', 20, 15, 5),
(2, 'Blood Glucose Tester Strips', 'pcs', 2, 2, 0),
(2, 'Extra Large Garbage Bag', 'pack', 10, 3, 7),
(2, '70% Alcohol', 'bot', 25, 15, 10),
(2, 'Non-Rebreather Mask (Adult)', 'pcs', 100, 75, 25),
(2, 'Nasal Cannula (Adult)', 'pcs', 100, 75, 25);

-- Sample inventory items for SRR category
INSERT INTO inventory_items (category_id, item_description, unit, items_received, items_distributed, items_on_hand) VALUES
(3, 'Rechargeable outdoor LED flood light', 'pcs', 13, 1, 13),
(3, 'Collapsible telescope floodlight tripod', 'pcs', 4, 0, 4),
(3, 'Industrial waterproof rechargeable emergency flashlight', 'pcs', 10, 3, 7),
(3, 'Rechargeable searchlight', 'pcs', 14, 1, 13),
(3, 'Storage box 155L storage capacity', 'pcs', 2, 0, 2),
(3, 'Folding Table 6ft. Stands 74cm high', 'pcs', 2, 0, 2);

-- Sample inventory items for FOOD category
INSERT INTO inventory_items (category_id, item_description, unit, items_received, items_distributed, items_on_hand, expiration_date) VALUES
(4, 'Bearbrand 33g', 'boxes', 39, 12, 27, NULL),
(4, 'HOMI BEEF', 'boxes', 30, 23, 9, '2026-03-01'),
(4, 'HOMI CHICKEN', 'boxes', 5, 5, 0, NULL),
(4, 'LUCKY ME BEEF', 'boxes', 5, 4, 1, NULL),
(4, 'Nescafe 3n1 Original', 'boxes', 6, 1, 5, NULL),
(4, 'Nescafe 3n1 Creamy White', 'boxes', 3, 2, 1, NULL),
(4, 'Nescafe 3n1 Creamy Latte', 'boxes', 6, 2, 4, NULL);