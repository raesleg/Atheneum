CREATE DATABASE IF NOT EXISTS Atheneum;
USE Atheneum;

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
    reset_expiry DATETIME NULL,
    profile_pic VARCHAR(255) NULL DEFAULT "assets/images/default-avatar.jpg"
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
    userId INT UNSIGNED NOT NULL,
    productId INT UNSIGNED NOT NULL,
    quantity INT UNSIGNED NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_cart_user
        FOREIGN KEY (userId) REFERENCES Users(userId)
        ON DELETE CASCADE,
    CONSTRAINT fk_cart_product
        FOREIGN KEY (productId) REFERENCES Products(productId)
        ON DELETE CASCADE,
    CONSTRAINT uq_cart_user_product UNIQUE (userId, productId)
);