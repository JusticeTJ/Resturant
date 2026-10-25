<?php
/**
 * Database Connection File for Hungry Man's Restaurant
 *
 * CONFIGURATION FOR INFINITYFREE:
 * 1. Log in to your InfinityFree control panel
 * 2. Go to "MySQL Databases" section
 * 3. Find your database details and update the values below
 *
 * Format examples:
 * - Host: typically "sqlXXX.infinityfree.com" (check your control panel)
 * - Database Name: usually "if0_XXXXXXX_hungrymans" or similar
 * - Username: usually same as database name or provided separately
 * - Password: the password you set when creating the database
 */

// ============================================================
// INFINITYFREE DATABASE CONFIGURATION - UPDATE THESE VALUES
// ============================================================

$host = 'localhost';        // InfinityFree MySQL hostname (e.g., sql123.infinityfree.com)
$dbUser = 'root';           // Database username (check InfinityFree control panel)
$dbPass = '';               // Database password (set in InfinityFree control panel)
$dbName = 'hungrymans';     // Database name (check InfinityFree control panel)

// ============================================================
// END OF CONFIGURATION
// ============================================================

// Connect to MySQL server first (without specifying database)
$server = new mysqli($host, $dbUser, $dbPass);
if ($server->connect_error) {
    die('MySQL connection failed: ' . $server->connect_error . '<br><br>' .
        'Please check your connection.php settings.');
}

// Create database if it doesn't exist
$server->query("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
$server->close();

// Now connect to the specific database
$conn = new mysqli($host, $dbUser, $dbPass, $dbName);

// Check connection
if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error . '<br><br>' .
        'Please check your connection.php settings and ensure your database is created in InfinityFree.');
}

// Set charset to utf8mb4 for proper character encoding
$conn->set_charset('utf8mb4');

// Auto-create tables if they don't exist (for development)
$tables = [
    "CREATE TABLE IF NOT EXISTS admin_users (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        first_name VARCHAR(100) NOT NULL,
        last_name VARCHAR(100) NOT NULL,
        email VARCHAR(255) NOT NULL,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uq_admin_users_email (email)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",

    "CREATE TABLE IF NOT EXISTS users (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        first_name VARCHAR(100) NOT NULL,
        last_name VARCHAR(100) NOT NULL,
        email VARCHAR(255) NOT NULL,
        password VARCHAR(255) NOT NULL,
        status ENUM('active', 'blocked') NOT NULL DEFAULT 'active',
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uq_users_email (email)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",

    "CREATE TABLE IF NOT EXISTS menu_items (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        name VARCHAR(160) NOT NULL,
        description TEXT NOT NULL,
        category VARCHAR(80) NOT NULL DEFAULT 'Main',
        price DECIMAL(12,2) NOT NULL,
        image_url VARCHAR(500) NOT NULL DEFAULT '',
        is_available TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_menu_available (is_available, id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",

    "CREATE TABLE IF NOT EXISTS orders (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        order_number VARCHAR(20) NOT NULL,
        customer_id INT UNSIGNED NULL,
        customer_name VARCHAR(200) NOT NULL,
        item_name VARCHAR(255) NOT NULL,
        total_amount DECIMAL(12,2) NOT NULL,
        status ENUM('Pending','Confirmed','Preparing','Ready','Delivering','Delivered','Cancelled') NOT NULL DEFAULT 'Pending',
        payment_status ENUM('not_sent','submitted','confirmed','rejected') NOT NULL DEFAULT 'not_sent',
        payment_submitted_at DATETIME NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uq_orders_number (order_number),
        KEY idx_orders_customer (customer_id, created_at),
        KEY idx_orders_status (status, payment_status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",

    "CREATE TABLE IF NOT EXISTS order_items (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        order_id INT UNSIGNED NOT NULL,
        menu_item_id INT UNSIGNED NOT NULL,
        item_name VARCHAR(160) NOT NULL,
        unit_price DECIMAL(12,2) NOT NULL,
        quantity SMALLINT UNSIGNED NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_order_items_order (order_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",

    "CREATE TABLE IF NOT EXISTS reservations (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id INT UNSIGNED NOT NULL,
        customer_name VARCHAR(200) NOT NULL,
        email VARCHAR(255) NOT NULL,
        phone VARCHAR(40) NOT NULL,
        reservation_date DATE NOT NULL,
        reservation_time VARCHAR(30) NOT NULL,
        guests TINYINT UNSIGNED NOT NULL,
        occasion VARCHAR(100) NOT NULL,
        notes TEXT NULL,
        status ENUM('Pending','Confirmed','Cancelled','Completed') NOT NULL DEFAULT 'Pending',
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_res_customer (user_id),
        KEY idx_res_date (reservation_date, reservation_time)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",

    "CREATE TABLE IF NOT EXISTS contact_messages (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        name VARCHAR(200) NOT NULL,
        email VARCHAR(255) NOT NULL,
        subject VARCHAR(255) NOT NULL DEFAULT 'Website message',
        message TEXT NOT NULL,
        status ENUM('New','Read') NOT NULL DEFAULT 'New',
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",

    "CREATE TABLE IF NOT EXISTS restaurant_settings (
        setting_key VARCHAR(80) NOT NULL,
        setting_value TEXT NOT NULL,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (setting_key)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",

    "CREATE TABLE IF NOT EXISTS admin_notifications (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        type VARCHAR(60) NOT NULL,
        message VARCHAR(255) NOT NULL,
        is_read TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",

    "CREATE TABLE IF NOT EXISTS chatbot_conversations (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        session_id VARCHAR(100) NOT NULL,
        user_id INT UNSIGNED NULL,
        message TEXT NOT NULL,
        sender ENUM('user', 'bot') NOT NULL,
        intent VARCHAR(50) NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_chat_session (session_id, created_at),
        KEY idx_chat_user (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
];

foreach ($tables as $sql) {
    $conn->query($sql);
}

// Insert sample menu items if menu_items is empty
$menuCheck = $conn->query("SELECT COUNT(*) as count FROM menu_items");
$menuCount = $menuCheck ? $menuCheck->fetch_assoc()['count'] : 0;

if ($menuCount == 0) {
    $menuItems = [
        "INSERT INTO menu_items (name, description, category, price, image_url, is_available) VALUES ('Prime Ribeye', 'Perfectly seared with herb butter, roasted vegetables and rich pan jus.', 'Main', 42000.00, 'https://images.unsplash.com/photo-1544025162-d76694265947?auto=format&fit=crop&w=900&q=80', 1)",
        "INSERT INTO menu_items (name, description, category, price, image_url, is_available) VALUES ('Grilled Salmon', 'Fresh Atlantic salmon with lemon butter sauce and asparagus tips.', 'Main', 31000.00, 'https://images.unsplash.com/photo-1559847844-5315695dadae?auto=format&fit=crop&w=900&q=80', 1)",
        "INSERT INTO menu_items (name, description, category, price, image_url, is_available) VALUES ('Lobster Pasta', 'Rich cream sauce, tender lobster chunks, and garlic butter linguine.', 'Main', 35000.00, 'https://images.unsplash.com/photo-1551183053-bf91a1d81141?auto=format&fit=crop&w=900&q=80', 1)",
        "INSERT INTO menu_items (name, description, category, price, image_url, is_available) VALUES ('Chicken Shawarma', 'Chargrilled chicken, warm flatbread, pickles, and house garlic sauce.', 'Main', 22000.00, 'https://images.unsplash.com/photo-1547592180-85f173990554?auto=format&fit=crop&w=900&q=80', 1)",
        "INSERT INTO menu_items (name, description, category, price, image_url, is_available) VALUES ('Truffle Fries', 'Crispy fries tossed in truffle oil, parmesan, and herbs.', 'Side', 12000.00, 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?auto=format&fit=crop&w=900&q=80', 1)",
        "INSERT INTO menu_items (name, description, category, price, image_url, is_available) VALUES ('Chocolate Tart', 'Dark chocolate ganache, berry compote, and shaved cocoa nibs.', 'Dessert', 14000.00, 'https://images.unsplash.com/photo-1551024601-bec78aea704b?auto=format&fit=crop&w=900&q=80', 1)"
    ];
    foreach ($menuItems as $item) {
        $conn->query($item);
    }
}

// Insert sample restaurant settings if empty
$settingsCheck = $conn->query("SELECT COUNT(*) as count FROM restaurant_settings");
$settingsCount = $settingsCheck ? $settingsCheck->fetch_assoc()['count'] : 0;

if ($settingsCount == 0) {
    $settings = [
        "INSERT INTO restaurant_settings (setting_key, setting_value) VALUES ('restaurant_name', \"Hungry Man's\")",
        "INSERT INTO restaurant_settings (setting_key, setting_value) VALUES ('address', '123 Gourmet Street, Downtown District, Restaurant Quarter')",
        "INSERT INTO restaurant_settings (setting_key, setting_value) VALUES ('opening_hours', 'Mon-Thu: 11 AM-10 PM; Fri-Sat: 11 AM-11 PM; Sun: 10 AM-9 PM')",
        "INSERT INTO restaurant_settings (setting_key, setting_value) VALUES ('contact_phone', '+234 000 000 0000')",
        "INSERT INTO restaurant_settings (setting_key, setting_value) VALUES ('contact_email', 'hello@hungrymans.com')"
    ];
    foreach ($settings as $setting) {
        $conn->query($setting);
    }
}

// Return the connection object for use in other files
return $conn;
