<?php

/**
 * Database Configuration
 * MySQL Database Setup for Catfish Farming System
 */

// Include guard - prevent redeclaration
if (defined('DB_CONFIG_LOADED')) {
    return;
}
define('DB_CONFIG_LOADED', true);

// Database Configuration
if (!defined('DB_HOST')) {
    define('DB_HOST', 'localhost');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'catfish_farm');
}

define('APP_ROOT', dirname(__FILE__) . '/..');

/**
 * Get Database Connection
 *
 * @return PDO Database connection
 */
if (!function_exists('getDB')) {
    function getDB()
    {
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
            $db = new PDO($dsn, DB_USER, DB_PASS);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $db;
        } catch (PDOException $e) {
            die('Database Connection Error: ' . $e->getMessage());
        }
    }
}

/**
 * Initialize Database
 * Creates all necessary tables and admin user if they don't exist
 *
 * @return PDO Database connection
 */
if (!function_exists('initializeDatabase')) {
    function initializeDatabase()
    {
        try {
            // Connect to MySQL server (without database)
            $db = new PDO('mysql:host=' . DB_HOST . ';charset=utf8mb4', DB_USER, DB_PASS);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Create database if not exists
            $db->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

            // Select database
            $db = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Create Users Table
            $db->exec("CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(255) UNIQUE NOT NULL,
                email VARCHAR(255) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                full_name VARCHAR(255),
                phone VARCHAR(20),
                address TEXT,
                role VARCHAR(20) DEFAULT 'user',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            // Create Ponds Table
            $db->exec("CREATE TABLE IF NOT EXISTS ponds (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                pond_name VARCHAR(255) NOT NULL,
                location VARCHAR(255),
                size_area VARCHAR(50),
                capacity INT,
                water_source VARCHAR(100),
                notes TEXT,
                status VARCHAR(50) DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            // Create Fish Inventory Table
            $db->exec("CREATE TABLE IF NOT EXISTS fish_inventory (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                pond_id INT NOT NULL,
                species VARCHAR(100) NOT NULL,
                quantity INT NOT NULL,
                size VARCHAR(50),
                age_days INT,
                health_status VARCHAR(50) DEFAULT 'healthy',
                notes TEXT,
                entry_date DATE NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (pond_id) REFERENCES ponds(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            // Create Feed Management Table
            $db->exec("CREATE TABLE IF NOT EXISTS feed_management (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                pond_id INT NOT NULL,
                feed_type VARCHAR(100) NOT NULL,
                quantity_kg DECIMAL(10,2) NOT NULL,
                cost DECIMAL(10,2),
                feed_date DATE NOT NULL,
                time_fed TIME,
                supplier VARCHAR(100),
                notes TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (pond_id) REFERENCES ponds(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            // Create Health Monitoring Table
            $db->exec("CREATE TABLE IF NOT EXISTS health_monitoring (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                pond_id INT NOT NULL,
                fish_count INT NOT NULL,
                health_status ENUM('healthy', 'sick', 'dead') NOT NULL,
                symptoms TEXT,
                treatment_given TEXT,
                treatment_date DATE NOT NULL,
                ph_level DECIMAL(4,2),
                temperature DECIMAL(5,2),
                monitoring_date DATE NOT NULL,
                mortality_count INT DEFAULT 0,
                condition_status VARCHAR(50),
                notes TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (pond_id) REFERENCES ponds(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            // Create Sessions Table for authentication
            $db->exec("CREATE TABLE IF NOT EXISTS sessions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                session_id VARCHAR(255) UNIQUE NOT NULL,
                user_agent TEXT,
                ip_address VARCHAR(45),
                last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                INDEX idx_session_id (session_id),
                INDEX idx_user_id (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            // Check if admin user exists
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM users WHERE username = 'admin'");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result['count'] == 0) {
                // Create default admin user
                $hashed_password = password_hash('admin123', PASSWORD_BCRYPT);
                $stmt = $db->prepare("INSERT INTO users (username, email, password, full_name, role) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute(['admin', 'admin@catfish.local', $hashed_password, 'Administrator', 'admin']);
            }

            return $db;
        } catch (PDOException $e) {
            die('Database Error: ' . $e->getMessage());
        }
    }
}