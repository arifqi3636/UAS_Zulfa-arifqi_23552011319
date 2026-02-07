-- Database schema for Catfish Farming Information System
-- Database name: lele_farming

CREATE DATABASE IF NOT EXISTS lele_farming;
USE lele_farming;

-- Users table for authentication
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default admin user (password: admin123)
INSERT INTO users (username, password) VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi') ON DUPLICATE KEY UPDATE id=id;

-- Ponds table
CREATE TABLE IF NOT EXISTS ponds (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    capacity INT NOT NULL COMMENT 'Capacity in thousand fish',
    location VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Feeds table
CREATE TABLE IF NOT EXISTS feeds (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pond_id INT NOT NULL,
    feed_type VARCHAR(100) NOT NULL,
    quantity DECIMAL(10,2) NOT NULL COMMENT 'Quantity in kg',
    date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pond_id) REFERENCES ponds(id) ON DELETE CASCADE
);

-- Harvests table
CREATE TABLE IF NOT EXISTS harvests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pond_id INT NOT NULL,
    weight DECIMAL(10,2) NOT NULL COMMENT 'Weight in kg',
    date DATE NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pond_id) REFERENCES ponds(id) ON DELETE CASCADE
);

-- Insert sample data
INSERT INTO ponds (name, capacity, location) VALUES
('Kolam A', 50, 'Area Utara'),
('Kolam B', 75, 'Area Selatan'),
('Kolam C', 60, 'Area Timur')
ON DUPLICATE KEY UPDATE id=id;

INSERT INTO feeds (pond_id, feed_type, quantity, date) VALUES
(1, 'Pelet Lele', 25.5, CURDATE()),
(2, 'Pelet Lele Premium', 30.0, CURDATE()),
(3, 'Pelet Organik', 20.0, CURDATE()-1)
ON DUPLICATE KEY UPDATE id=id;

INSERT INTO harvests (pond_id, weight, date, notes) VALUES
(1, 150.5, CURDATE()-7, 'Panen pertama musim ini'),
(2, 200.0, CURDATE()-5, 'Kualitas baik'),
(3, 180.0, CURDATE()-3, 'Siap untuk pasar')
ON DUPLICATE KEY UPDATE id=id;