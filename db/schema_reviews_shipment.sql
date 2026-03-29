USE db_atheneum;

-- reviews part
CREATE TABLE IF NOT EXISTS Reviews (
    reviewId      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    productId     INT UNSIGNED NOT NULL,
    username      VARCHAR(45)  NOT NULL,
    orderId       INT UNSIGNED NOT NULL,
    rating        TINYINT UNSIGNED NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment       VARCHAR(200) NULL,                -- setting for length of review
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_review_product FOREIGN KEY (productId) REFERENCES Products(productId) ON DELETE CASCADE,
    CONSTRAINT fk_review_user    FOREIGN KEY (username)  REFERENCES Users(username)      ON DELETE CASCADE,
    CONSTRAINT fk_review_order   FOREIGN KEY (orderId)   REFERENCES Orders(orderId)      ON DELETE CASCADE,

    CONSTRAINT uq_review_user_product UNIQUE (username, productId)
);

-- shipping part
CREATE TABLE IF NOT EXISTS OrderShipments (
    shipmentId      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    orderId         INT UNSIGNED NOT NULL UNIQUE,
    currentStatus   ENUM('order_placed','order_shipped','in_transit','out_for_delivery','delivered')
                    NOT NULL DEFAULT 'order_placed',
    order_placed_at    TIMESTAMP NULL,
    order_shipped_at   TIMESTAMP NULL,
    in_transit_at      TIMESTAMP NULL,
    out_for_delivery_at TIMESTAMP NULL,
    delivered_at       TIMESTAMP NULL,
    created_at         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_shipment_order FOREIGN KEY (orderId) REFERENCES Orders(orderId) ON DELETE CASCADE
);
