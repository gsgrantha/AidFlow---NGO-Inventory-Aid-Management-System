-- ============================================================
--  AidFlow — NGO Management System
--  DBMS Project: MySQL Schema ONLY — no sample/demo rows.
--  Every table starts EMPTY. Data only appears once you use
--  the app (sign up, submit a donation, etc.)
--  Import this file in phpMyAdmin (XAMPP) to create the database.
-- ============================================================

CREATE DATABASE IF NOT EXISTS aidflow_db;
USE aidflow_db;

-- ------------------------------------------------------------
-- TABLE: users
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    email       VARCHAR(100) NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,
    role        ENUM('admin','donor','volunteer','shelter') NOT NULL DEFAULT 'donor',
    avatar_data MEDIUMTEXT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- TABLE: donations
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS donations (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    donor_name    VARCHAR(100) NOT NULL,
    item_name     VARCHAR(100) NOT NULL,
    category      ENUM('Food','Medicine','Clothes','Relief') NOT NULL,
    quantity      INT NOT NULL,
    donation_date DATE NOT NULL,
    status        ENUM('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
    user_id       INT NULL,
    CONSTRAINT fk_donation_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- TABLE: inventory
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS inventory (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    item_name   VARCHAR(100) NOT NULL,
    category    ENUM('Food','Medicine','Clothes','Relief') NOT NULL,
    quantity    INT NOT NULL DEFAULT 0,
    expiry_date DATE NULL,
    status      ENUM('OK','Low','Critical') NOT NULL DEFAULT 'OK'
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- TABLE: requests
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS requests (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    shelter_name  VARCHAR(100) NOT NULL,
    item_required VARCHAR(100) NOT NULL,
    quantity      INT NOT NULL,
    priority      ENUM('Critical','High','Medium','Low') NOT NULL DEFAULT 'Medium',
    request_date  DATE NOT NULL,
    status        ENUM('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
    user_id       INT NULL,
    CONSTRAINT fk_request_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- TABLE: distribution
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS distribution (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    receiver_name  VARCHAR(100) NOT NULL,
    item_name      VARCHAR(100) NOT NULL,
    category       ENUM('Food','Medicine','Clothes','Relief') NOT NULL,
    quantity       INT NOT NULL,
    volunteer_name VARCHAR(100) NOT NULL,
    dist_date      DATE NOT NULL,
    status         ENUM('Pending','In Transit','Delivered') NOT NULL DEFAULT 'Pending',
    user_id        INT NULL,
    CONSTRAINT fk_dist_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- No INSERT statements — every table is empty on purpose.
-- Sign up in the app to create your first user, then use the
-- forms in each section to populate donations/inventory/etc.
