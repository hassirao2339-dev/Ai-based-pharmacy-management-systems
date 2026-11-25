-- =====================================================================
-- AI-Based Pharmacy Management System
-- Database schema (Phase 1)
--
-- Creates the database, 11 tables and 1 view.
-- Works on MySQL 8 and on MariaDB 10.4+ (the database inside XAMPP).
--
-- WARNING: running this file DELETES the existing tables and all their data.
-- That is useful while developing; never run it on a database you need.
-- =====================================================================

CREATE DATABASE IF NOT EXISTS pharmacy_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE pharmacy_db;

-- Remove old copies first so this file can be run again.
-- Child tables are removed before parent tables because of foreign keys.
DROP VIEW IF EXISTS order_trending_summary;
DROP TABLE IF EXISTS order_item_batches;
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS cart;
DROP TABLE IF EXISTS prescriptions;
DROP TABLE IF EXISTS stock_alerts;
DROP TABLE IF EXISTS reports;
DROP TABLE IF EXISTS audit_log;
DROP TABLE IF EXISTS inventory_batches;
DROP TABLE IF EXISTS medicines;
DROP TABLE IF EXISTS users;


-- ---------------------------------------------------------------------
-- 1. users
-- Customers and administrators in one table, told apart by "role".
-- SRS: FR-01 to FR-08, FR-58, FR-61, FR-62
-- ---------------------------------------------------------------------
CREATE TABLE users (
    user_id               INT           NOT NULL AUTO_INCREMENT,
    name                  VARCHAR(100)  NOT NULL,
    email                 VARCHAR(150)  NOT NULL,
    password_hash         VARCHAR(255)  NOT NULL,
    phone                 VARCHAR(20)   NOT NULL,
    role                  ENUM('customer', 'admin') NOT NULL DEFAULT 'customer',
    is_active             TINYINT(1)    NOT NULL DEFAULT 1,
    address               TEXT          NULL,
    failed_login_attempts INT           NOT NULL DEFAULT 0,      -- FR-07 lockout counter
    locked_until          DATETIME      NULL,                    -- FR-07 locked until this time
    reset_token_hash      CHAR(64)      NULL,                    -- FR-08 hashed reset token
    reset_expires_at      DATETIME      NULL,                    -- FR-08 reset link expiry
    created_at            DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at            DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (user_id),
    UNIQUE KEY uq_users_email (email),                            -- FR-02 unique email
    KEY idx_users_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------
-- 2. medicines
-- The catalogue. stock_qty and expiry_date are summaries that PHP
-- recalculates from inventory_batches after every stock change.
-- SRS: FR-18 to FR-22, FR-34, FR-39
-- ---------------------------------------------------------------------
CREATE TABLE medicines (
    medicine_id       INT           NOT NULL AUTO_INCREMENT,
    name              VARCHAR(150)  NOT NULL,
    sku               VARCHAR(50)   NOT NULL,
    category          VARCHAR(100)  NOT NULL,
    drap_category     VARCHAR(100)  NOT NULL,
    manufacturer      VARCHAR(150)  NOT NULL,
    description       TEXT          NOT NULL,
    price             DECIMAL(10,2) NOT NULL,
    stock_qty         INT           NOT NULL DEFAULT 0,          -- total of usable batches
    expiry_date       DATE          NULL,                        -- earliest expiry of usable batches
    reorder_threshold INT           NOT NULL DEFAULT 10,         -- low-stock level (FR-41)
    is_active         TINYINT(1)    NOT NULL DEFAULT 1,          -- 0 = soft-deleted (FR-39)
    created_at        DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (medicine_id),
    UNIQUE KEY uq_medicines_sku (sku),
    KEY idx_medicines_name (name),                               -- fast search (FR-19)
    KEY idx_medicines_category (category),                       -- fast filter (FR-20)
    KEY idx_medicines_manufacturer (manufacturer),
    CONSTRAINT chk_medicines_price CHECK (price >= 0),
    CONSTRAINT chk_medicines_stock CHECK (stock_qty >= 0),
    CONSTRAINT chk_medicines_threshold CHECK (reorder_threshold >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------
-- 3. inventory_batches
-- One row per delivery of a medicine. FIFO sells from the lowest
-- fifo_order first. "quantity" is the number of units still left.
-- SRS: FR-35 to FR-38
-- ---------------------------------------------------------------------
CREATE TABLE inventory_batches (
    batch_id      INT           NOT NULL AUTO_INCREMENT,
    medicine_id   INT           NOT NULL,
    quantity      INT           NOT NULL,
        quantity_received INT       NOT NULL DEFAULT 0,             -- how many arrived; never changes
    supplier_name VARCHAR(150)  NOT NULL,
    cost_price    DECIMAL(10,2) NOT NULL,
    received_date DATE          NOT NULL,
    expiry_date   DATE          NOT NULL,
    fifo_order    INT           NOT NULL,
    is_depleted   TINYINT(1)    NOT NULL DEFAULT 0,              -- 1 when quantity reaches 0
    created_at    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (batch_id),
    UNIQUE KEY uq_batches_medicine_fifo (medicine_id, fifo_order),  -- no two batches share a FIFO position
    KEY idx_batches_expiry (expiry_date),
    CONSTRAINT fk_batches_medicine
        FOREIGN KEY (medicine_id) REFERENCES medicines (medicine_id)
        ON DELETE RESTRICT,
    CONSTRAINT chk_batches_quantity CHECK (quantity >= 0),
        CONSTRAINT chk_batches_received CHECK (quantity_received >= 0),
    CONSTRAINT chk_batches_cost CHECK (cost_price >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------
-- 4. cart
-- A persistent cart: items stay saved after logout.
-- One row per customer per medicine.
-- SRS: FR-13, FR-15, FR-23, FR-24
-- ---------------------------------------------------------------------
CREATE TABLE cart (
    cart_id     INT      NOT NULL AUTO_INCREMENT,
    customer_id INT      NOT NULL,
    medicine_id INT      NOT NULL,
    quantity    INT      NOT NULL DEFAULT 1,
    added_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (cart_id),
    UNIQUE KEY uq_cart_customer_medicine (customer_id, medicine_id),
    KEY idx_cart_medicine (medicine_id),
    CONSTRAINT fk_cart_customer
        FOREIGN KEY (customer_id) REFERENCES users (user_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_cart_medicine
        FOREIGN KEY (medicine_id) REFERENCES medicines (medicine_id)
        ON DELETE CASCADE,
    CONSTRAINT chk_cart_quantity CHECK (quantity > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------
-- 5. orders
-- The order header. Status colours are applied in the views (FR-32).
-- SRS: FR-25 to FR-33, FR-59
-- ---------------------------------------------------------------------
CREATE TABLE orders (
    order_id         INT           NOT NULL AUTO_INCREMENT,
    customer_id      INT           NOT NULL,
    order_date       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    total_amount     DECIMAL(10,2) NOT NULL,
    status           ENUM('pending', 'confirmed', 'dispatched', 'delivered', 'cancelled')
                                   NOT NULL DEFAULT 'pending',
    delivery_address TEXT          NOT NULL,
    payment_method   VARCHAR(20)   NOT NULL DEFAULT 'COD',
    cancel_reason    TEXT          NULL,                         -- required when admin cancels (FR-59)
    notified_admin   TINYINT(1)    NOT NULL DEFAULT 0,           -- 0 = new-order badge still showing (FR-29)
    created_at       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (order_id),
    KEY idx_orders_customer (customer_id),
    KEY idx_orders_status (status),
    KEY idx_orders_date (order_date),
    CONSTRAINT fk_orders_customer
        FOREIGN KEY (customer_id) REFERENCES users (user_id)
        ON DELETE RESTRICT,
    CONSTRAINT chk_orders_total CHECK (total_amount >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------
-- 6. order_items
-- The lines of an order. unit_price is copied at order time, so later
-- price changes never change old orders.
-- SRS: FR-27, FR-30, FR-31
-- ---------------------------------------------------------------------
CREATE TABLE order_items (
    item_id     INT           NOT NULL AUTO_INCREMENT,
    order_id    INT           NOT NULL,
    medicine_id INT           NOT NULL,
    quantity    INT           NOT NULL,
    unit_price  DECIMAL(10,2) NOT NULL,
    subtotal    DECIMAL(10,2) NOT NULL,
    created_at  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (item_id),
    UNIQUE KEY uq_items_order_medicine (order_id, medicine_id),  -- a medicine appears once per order
    KEY idx_items_medicine (medicine_id),
    CONSTRAINT fk_items_order
        FOREIGN KEY (order_id) REFERENCES orders (order_id)
        ON DELETE RESTRICT,
    CONSTRAINT fk_items_medicine
        FOREIGN KEY (medicine_id) REFERENCES medicines (medicine_id)
        ON DELETE RESTRICT,
    CONSTRAINT chk_items_quantity CHECK (quantity > 0),
    CONSTRAINT chk_items_price CHECK (unit_price >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------
-- 7. order_item_batches  (added to the ERD, decision D3)
-- Records exactly which batches FIFO took stock from for each order
-- line, so editing or cancelling a pending order can return the stock
-- to the same batches.
-- SRS: FR-27, FR-31, FR-33
-- ---------------------------------------------------------------------
CREATE TABLE order_item_batches (
    allocation_id INT NOT NULL AUTO_INCREMENT,
    item_id       INT NOT NULL,
    batch_id      INT NOT NULL,
    quantity      INT NOT NULL,

    PRIMARY KEY (allocation_id),
    UNIQUE KEY uq_allocation_item_batch (item_id, batch_id),
    KEY idx_allocation_batch (batch_id),
    CONSTRAINT fk_allocation_item
        FOREIGN KEY (item_id) REFERENCES order_items (item_id)
        ON DELETE RESTRICT,
    CONSTRAINT fk_allocation_batch
        FOREIGN KEY (batch_id) REFERENCES inventory_batches (batch_id)
        ON DELETE RESTRICT,
    CONSTRAINT chk_allocation_quantity CHECK (quantity > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------
-- 8. prescriptions
-- image_path stores only the random file name. The file itself lives in
-- the private storage folder outside htdocs.
-- SRS: FR-11 to FR-17, FR-63
-- ---------------------------------------------------------------------
CREATE TABLE prescriptions (
    prescription_id     INT          NOT NULL AUTO_INCREMENT,
    customer_id         INT          NOT NULL,
    image_path          VARCHAR(255) NOT NULL,
    ocr_result          JSON         NULL,                       -- text returned by Python
    medicines_found     JSON         NULL,                       -- names added to cart
    medicines_not_found JSON         NULL,                       -- names with no match
    uploaded_at         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    status              ENUM('processing', 'completed', 'failed') NOT NULL DEFAULT 'processing',

    PRIMARY KEY (prescription_id),
    UNIQUE KEY uq_prescriptions_image (image_path),
    KEY idx_prescriptions_customer (customer_id),
    CONSTRAINT fk_prescriptions_customer
        FOREIGN KEY (customer_id) REFERENCES users (user_id)
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------
-- 9. stock_alerts
-- Low-stock alerts that stay visible until an admin acknowledges them.
-- SRS: FR-41 to FR-43
-- ---------------------------------------------------------------------
CREATE TABLE stock_alerts (
    alert_id        INT        NOT NULL AUTO_INCREMENT,
    medicine_id     INT        NOT NULL,
    threshold       INT        NOT NULL,                         -- reorder threshold at that moment
    current_stock   INT        NOT NULL,                         -- stock level at that moment
    is_acknowledged TINYINT(1) NOT NULL DEFAULT 0,
    acknowledged_by INT        NULL,                             -- admin who resolved it
    triggered_at    DATETIME   NOT NULL DEFAULT CURRENT_TIMESTAMP,
    acknowledged_at DATETIME   NULL,
    created_at      DATETIME   NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (alert_id),
    KEY idx_alerts_medicine (medicine_id),
    KEY idx_alerts_acknowledged (is_acknowledged),
    KEY idx_alerts_acknowledged_by (acknowledged_by),
    CONSTRAINT fk_alerts_medicine
        FOREIGN KEY (medicine_id) REFERENCES medicines (medicine_id)
        ON DELETE RESTRICT,
    CONSTRAINT fk_alerts_acknowledged_by
        FOREIGN KEY (acknowledged_by) REFERENCES users (user_id)
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------
-- 10. reports
-- A record of every generated PDF report.
-- SRS: FR-51 to FR-56, FR-60
-- ---------------------------------------------------------------------
CREATE TABLE reports (
    report_id    INT          NOT NULL AUTO_INCREMENT,
    generated_by INT          NOT NULL,
    report_type  VARCHAR(50)  NOT NULL,                          -- sales, inventory, orders, low_stock
    date_from    DATE         NULL,
    date_to      DATE         NULL,
    filters      JSON         NULL,                              -- e.g. status or customer name
       file_path    VARCHAR(255) NULL,                              -- PDF file name, when the PDF is saved
    generated_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (report_id),
    KEY idx_reports_generated_by (generated_by),
    CONSTRAINT fk_reports_generated_by
        FOREIGN KEY (generated_by) REFERENCES users (user_id)
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------
-- 11. audit_log
-- A permanent record of critical admin actions.
-- SRS: FR-60
-- ---------------------------------------------------------------------
CREATE TABLE audit_log (
    log_id         INT         NOT NULL AUTO_INCREMENT,
    user_id        INT         NOT NULL,
    action_type    VARCHAR(50) NOT NULL,                         -- e.g. order_status_update
    description    TEXT        NOT NULL,
    affected_table VARCHAR(50) NULL,
    affected_id    INT         NULL,
    ip_address     VARCHAR(45) NULL,                             -- 45 characters fits IPv6
    logged_at      DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (log_id),
    KEY idx_audit_user (user_id),
    KEY idx_audit_logged_at (logged_at),
    CONSTRAINT fk_audit_user
        FOREIGN KEY (user_id) REFERENCES users (user_id)
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------
-- VIEW: order_trending_summary  (decision D6)
-- A saved query, not a real table. Every time it is read, MySQL adds up
-- the quantities from counted orders, so it is always up to date (FR-50).
-- Counted statuses: confirmed, dispatched, delivered.
-- SRS: FR-45 to FR-50
-- ---------------------------------------------------------------------
CREATE VIEW order_trending_summary AS
SELECT
    order_items.medicine_id,
    YEAR(orders.order_date)  AS order_year,
    MONTH(orders.order_date) AS order_month,
    SUM(order_items.quantity) AS total_ordered
FROM order_items
INNER JOIN orders ON orders.order_id = order_items.order_id
WHERE orders.status IN ('confirmed', 'dispatched', 'delivered')
GROUP BY
    order_items.medicine_id,
    YEAR(orders.order_date),
    MONTH(orders.order_date);