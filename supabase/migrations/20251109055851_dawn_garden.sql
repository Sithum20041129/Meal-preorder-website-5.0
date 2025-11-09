-- Create database
CREATE DATABASE IF NOT EXISTS meal_preorder;
USE meal_preorder;

-- Users table (customers, merchants, admins)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(255) NOT NULL,
    role ENUM('customer', 'merchant', 'admin') NOT NULL,
    approved BOOLEAN DEFAULT FALSE,
    shop_name VARCHAR(255) NULL,
    location VARCHAR(255) NULL,
    phone VARCHAR(20) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Shops table
CREATE TABLE shops (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    location VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    is_open BOOLEAN DEFAULT FALSE,
    accepting_orders BOOLEAN DEFAULT TRUE,
    closing_time TIME NULL,
    order_limit INT DEFAULT 20,
    orders_received INT DEFAULT 0,
    business_hours JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Meal types table
CREATE TABLE meal_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    shop_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    description TEXT NULL,
    available BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE
);

-- Curries table
CREATE TABLE curries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    shop_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    available BOOLEAN DEFAULT TRUE,
    price DECIMAL(10,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE
);

-- Customizations table
CREATE TABLE customizations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    shop_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    type ENUM('protein', 'curry', 'extra') NOT NULL,
    available BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE
);

-- Orders table
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(20) UNIQUE NOT NULL,
    customer_id INT NOT NULL,
    customer_name VARCHAR(255) NOT NULL,
    merchant_id INT NOT NULL,
    merchant_name VARCHAR(255) NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'preparing', 'ready', 'completed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (merchant_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Order items table
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    meal_type_id INT NOT NULL,
    meal_type_name VARCHAR(255) NOT NULL,
    meal_type_price DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (meal_type_id) REFERENCES meal_types(id)
);

-- Order item curries (junction table)
CREATE TABLE order_item_curries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_item_id INT NOT NULL,
    curry_id INT NOT NULL,
    curry_name VARCHAR(255) NOT NULL,
    FOREIGN KEY (order_item_id) REFERENCES order_items(id) ON DELETE CASCADE,
    FOREIGN KEY (curry_id) REFERENCES curries(id)
);

-- Order item customizations (junction table)
CREATE TABLE order_item_customizations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_item_id INT NOT NULL,
    customization_id INT NOT NULL,
    customization_name VARCHAR(255) NOT NULL,
    customization_price DECIMAL(10,2) NOT NULL,
    customization_type VARCHAR(50) NOT NULL,
    quantity INT DEFAULT 1,
    FOREIGN KEY (order_item_id) REFERENCES order_items(id) ON DELETE CASCADE,
    FOREIGN KEY (customization_id) REFERENCES customizations(id)
);

-- Create indexes for better performance
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_role ON users(role);
CREATE INDEX idx_shops_user_id ON shops(user_id);
CREATE INDEX idx_orders_customer_id ON orders(customer_id);
CREATE INDEX idx_orders_merchant_id ON orders(merchant_id);
CREATE INDEX idx_orders_status ON orders(status);
CREATE INDEX idx_orders_created_at ON orders(created_at);

-- Insert demo data
INSERT INTO users (email, password, name, role, approved, shop_name, location, phone) VALUES
('admin@mealorder.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Platform Admin', 'admin', TRUE, NULL, NULL, NULL),
('customer@demo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Demo Customer', 'customer', TRUE, NULL, NULL, NULL),
('merchant@demo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Demo Restaurant Owner', 'merchant', TRUE, 'Spice Garden Restaurant', 'Main Street, Downtown', '+1 234-567-8900');

-- Insert demo shop
INSERT INTO shops (user_id, name, location, phone, is_open, accepting_orders, closing_time, order_limit, orders_received) VALUES
(3, 'Spice Garden Restaurant', 'Main Street, Downtown', '+1 234-567-8900', TRUE, TRUE, '21:00:00', 25, 5);

-- Insert demo meal types
INSERT INTO meal_types (shop_id, name, price, description, available) VALUES
(1, 'Vegetarian Rice', 250.00, 'Fresh vegetables with aromatic basmati rice', TRUE),
(1, 'Chicken Rice', 350.00, 'Tender chicken pieces with spiced rice', TRUE),
(1, 'Fish Rice', 400.00, 'Fresh fish curry with fragrant rice', TRUE),
(1, 'Egg Rice', 300.00, 'Scrambled eggs with seasoned rice', TRUE);

-- Insert demo curries
INSERT INTO curries (shop_id, name, available) VALUES
(1, 'Dhal Curry', TRUE),
(1, 'Vegetable Curry', TRUE),
(1, 'Potato Curry', TRUE),
(1, 'Chicken Curry', TRUE),
(1, 'Fish Curry', TRUE),
(1, 'Coconut Curry', FALSE);

-- Insert demo customizations
INSERT INTO customizations (shop_id, name, price, type, available) VALUES
(1, 'Extra Chicken Piece', 100.00, 'protein', TRUE),
(1, 'Extra Fish Piece', 150.00, 'protein', TRUE),
(1, 'Extra Curry Portion', 50.00, 'curry', TRUE),
(1, 'Extra Rice', 30.00, 'extra', TRUE),
(1, 'Spicy Level +1', 0.00, 'extra', TRUE);

-- Insert demo order
INSERT INTO orders (order_number, customer_id, customer_name, merchant_id, merchant_name, total, status, created_at) VALUES
('ORD001234', 2, 'Demo Customer', 3, 'Spice Garden Restaurant', 450.00, 'preparing', DATE_SUB(NOW(), INTERVAL 30 MINUTE));

-- Insert demo order item
INSERT INTO order_items (order_id, meal_type_id, meal_type_name, meal_type_price, subtotal) VALUES
(1, 2, 'Chicken Rice', 350.00, 450.00);

-- Insert demo order item curries
INSERT INTO order_item_curries (order_item_id, curry_id, curry_name) VALUES
(1, 1, 'Dhal Curry'),
(1, 4, 'Chicken Curry'),
(1, 2, 'Vegetable Curry');

-- Insert demo order item customizations
INSERT INTO order_item_customizations (order_item_id, customization_id, customization_name, customization_price, customization_type, quantity) VALUES
(1, 1, 'Extra Chicken Piece', 100.00, 'protein', 1);