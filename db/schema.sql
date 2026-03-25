CREATE DATABASE IF NOT EXISTS db_atheneum;
USE db_atheneum;

CREATE TABLE Users (
    userId INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(45) NOT NULL UNIQUE,
    email VARCHAR(45) NOT NULL UNIQUE,
    fname VARCHAR(45) NULL,
    lname VARCHAR(45) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('customer', 'admin') NOT NULL DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reset_token VARCHAR(255) NULL,
    reset_expiry DATETIME NULL
);

CREATE TABLE Products (
    productId INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    author VARCHAR(100) NOT NULL,
    genre VARCHAR(50) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    quantity INT UNSIGNED NOT NULL DEFAULT 0,
    description TEXT,
    cover_image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE Cart (
    cartId INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(45) NOT NULL,
    productId INT UNSIGNED NOT NULL,
    quantity INT UNSIGNED NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_cart_user
        FOREIGN KEY (username) REFERENCES Users(username)
        ON DELETE CASCADE,
    CONSTRAINT fk_cart_product
        FOREIGN KEY (productId) REFERENCES Products(productId)
        ON DELETE CASCADE,
    CONSTRAINT uq_cart_user_product UNIQUE (username, productId)
);

CREATE TABLE Orders (
    orderId INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(45) NOT NULL,
    totalPrice DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    orderStatus ENUM('pending', 'paid', 'shipped', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
    paymentStatus ENUM('unpaid', 'pending', 'paid', 'failed', 'refunded') NOT NULL DEFAULT 'unpaid',
    stripeSessionId VARCHAR(100),
    paymentId VARCHAR(100),
    receiptUrl VARCHAR(255),
    paid_at TIMESTAMP NULL,
    CONSTRAINT fk_order_user
        FOREIGN KEY (username) REFERENCES Users(username)
        ON DELETE CASCADE
);

CREATE TABLE OrderItems (
    orderItemId INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    orderId INT UNSIGNED NOT NULL,
    productId INT UNSIGNED NOT NULL,
    quantity INT UNSIGNED NOT NULL DEFAULT 1,
    price_at_purchase DECIMAL(10,2) NOT NULL,
    CONSTRAINT fk_orderitem_order
        FOREIGN KEY (orderId) REFERENCES Orders(orderId)
        ON DELETE CASCADE,
    CONSTRAINT fk_orderitem_product
        FOREIGN KEY (productId) REFERENCES Products(productId)
        ON DELETE RESTRICT
);