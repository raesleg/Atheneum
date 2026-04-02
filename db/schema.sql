CREATE DATABASE IF NOT EXISTS Atheneum
    DEFAULT CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
USE Atheneum;

CREATE TABLE IF NOT EXISTS Users (
    userId       INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
    username     VARCHAR(45)   NOT NULL UNIQUE,
    email        VARCHAR(45)   NOT NULL UNIQUE,
    fname        VARCHAR(45)   NULL,
    lname        VARCHAR(45)   NOT NULL,
    password     VARCHAR(255)  NOT NULL,
    role         ENUM('customer','admin') NOT NULL DEFAULT 'customer',
    created_at   TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    reset_token  VARCHAR(255)  NULL,
    reset_expiry DATETIME      NULL,
    profile_pic  VARCHAR(255)  NULL DEFAULT "assets/images/default-avatar.jpg"
);

CREATE TABLE IF NOT EXISTS Products (
    productId   INT UNSIGNED   AUTO_INCREMENT PRIMARY KEY,
    title       VARCHAR(150)   NOT NULL,
    author      VARCHAR(100)   NOT NULL,
    genre       VARCHAR(50)    NOT NULL,
    price       DECIMAL(10,2)  NOT NULL,
    quantity    INT UNSIGNED   NOT NULL DEFAULT 0,
    description TEXT           NULL,
    cover_image VARCHAR(255)   NULL,
    created_at  TIMESTAMP      DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_genre   (genre),
    INDEX idx_created (created_at)
);

CREATE TABLE IF NOT EXISTS Cart (
    cartId      INT UNSIGNED   AUTO_INCREMENT PRIMARY KEY,
    userId      INT UNSIGNED   NOT NULL,
    productId   INT UNSIGNED   NOT NULL,
    quantity    INT UNSIGNED   NOT NULL DEFAULT 1,
    created_at  TIMESTAMP      DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_cart_user
        FOREIGN KEY (userId)    REFERENCES Users(userId)       ON DELETE CASCADE,
    CONSTRAINT fk_cart_product
        FOREIGN KEY (productId) REFERENCES Products(productId) ON DELETE CASCADE,
    CONSTRAINT uq_cart_user_product UNIQUE (userId, productId)
);

CREATE TABLE IF NOT EXISTS Addresses (
    addressId INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    userId INT UNSIGNED NOT NULL,
    label VARCHAR(50) NOT NULL,
    address_line1 VARCHAR(255) NOT NULL,
    address_line2 VARCHAR(255),
    city VARCHAR(100) NOT NULL,
    state VARCHAR(100),
    postal_code VARCHAR(20) NOT NULL,
    country VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (userId) REFERENCES Users(userId) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS Orders (
    orderId         INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
    userId          INT UNSIGNED   NOT NULL,
    totalPrice      DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    created_at      TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    orderStatus     ENUM('pending','paid','shipped','completed','cancelled') NOT NULL DEFAULT 'pending',
    paymentStatus   ENUM('unpaid','pending','paid','failed','refunded')      NOT NULL DEFAULT 'pending',
    stripeSessionId VARCHAR(255)  NULL,
    paymentId       VARCHAR(100)  NULL,
    receiptUrl      VARCHAR(255)  NULL,
    paid_at         TIMESTAMP     NULL,
    CONSTRAINT fk_order_user
        FOREIGN KEY (userId) REFERENCES Users(userId) ON DELETE CASCADE,
    INDEX idx_order_user (userId)
);

CREATE TABLE IF NOT EXISTS OrderItems (
    orderItemId       INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
    orderId           INT UNSIGNED  NOT NULL,
    productId         INT UNSIGNED  NOT NULL,
    quantity          INT UNSIGNED  NOT NULL DEFAULT 1,
    price_at_purchase DECIMAL(10,2) NOT NULL,
    CONSTRAINT fk_orderitem_order
        FOREIGN KEY (orderId)   REFERENCES Orders(orderId)     ON DELETE CASCADE,
    CONSTRAINT fk_orderitem_product
        FOREIGN KEY (productId) REFERENCES Products(productId) ON DELETE RESTRICT
);

CREATE TABLE IF NOT EXISTS Reviews (
    reviewId   INT UNSIGNED     AUTO_INCREMENT PRIMARY KEY,
    productId  INT UNSIGNED     NOT NULL,
    userId     INT UNSIGNED     NOT NULL,
    orderId    INT UNSIGNED     NOT NULL,
    rating     TINYINT UNSIGNED NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment    VARCHAR(200)     NULL,
    created_at TIMESTAMP        DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_review_product FOREIGN KEY (productId) REFERENCES Products(productId) ON DELETE CASCADE,
    CONSTRAINT fk_review_user    FOREIGN KEY (userId)  REFERENCES Users(userId)     ON DELETE CASCADE,
    CONSTRAINT fk_review_order   FOREIGN KEY (orderId)   REFERENCES Orders(orderId)     ON DELETE CASCADE,
    CONSTRAINT uq_review_user_product UNIQUE (userId, productId),
    INDEX idx_review_product (productId)
);

CREATE TABLE IF NOT EXISTS OrderShipments (
    shipmentId          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    orderId             INT UNSIGNED NOT NULL UNIQUE,
    currentStatus       ENUM('order_placed','order_shipped','in_transit','out_for_delivery','delivered')
                        NOT NULL DEFAULT 'order_placed',
    order_placed_at     TIMESTAMP NULL,
    order_shipped_at    TIMESTAMP NULL,
    in_transit_at       TIMESTAMP NULL,
    out_for_delivery_at TIMESTAMP NULL,
    delivered_at        TIMESTAMP NULL,
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_shipment_order FOREIGN KEY (orderId) REFERENCES Orders(orderId) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS FAQ (
    faqId         INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
    question      VARCHAR(255)  NOT NULL,
    answer        TEXT          NOT NULL,
    category      VARCHAR(50)   NULL DEFAULT 'General',
    display_order INT UNSIGNED  NOT NULL DEFAULT 0,
    is_active     TINYINT(1)    NOT NULL DEFAULT 1,
    created_at    TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_faq_category (category),
    INDEX idx_faq_order    (display_order)
);

CREATE TABLE IF NOT EXISTS Refund (
    refundId    INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
    orderId     INT UNSIGNED  NULL,
    userId      INT UNSIGNED  NULL,
    type        ENUM('Refund', 'General Enquiry', 'Feedback') NOT NULL DEFAULT 'Feedback',
    name        VARCHAR(100)  NULL,
    email       VARCHAR(100)  NULL,
    subject     VARCHAR(150)  NULL,
    paymentId   VARCHAR(100)  NULL,
    reason      TEXT          NOT NULL,
    status      ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    admin_note  TEXT          NULL,
    resolved_by INT UNSIGNED  NULL,
    resolved_at DATETIME      NULL,
    created_at  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_refund_order    FOREIGN KEY (orderId)     REFERENCES Orders(orderId)  ON DELETE SET NULL,
    CONSTRAINT fk_refund_user     FOREIGN KEY (userId)      REFERENCES Users(userId)    ON DELETE CASCADE,
    CONSTRAINT fk_refund_resolver FOREIGN KEY (resolved_by) REFERENCES Users(userId)    ON DELETE SET NULL,

    UNIQUE KEY uq_refund_order (orderId)
);
