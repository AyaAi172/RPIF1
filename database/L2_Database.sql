-- =========================================================
-- Portable Indoor Feedback - Database Schema
-- Save this as pif_database.sql and run in MariaDB/MySQL
-- =========================================================

-- 1) Create database and select it
CREATE DATABASE IF NOT EXISTS pif_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE pif_db;

-- 2) Drop tables if they already exist (for clean rebuild)
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS collection_measurements;
DROP TABLE IF EXISTS collection_shares;
DROP TABLE IF EXISTS friendships;
DROP TABLE IF EXISTS measurements;
DROP TABLE IF EXISTS collections;
DROP TABLE IF EXISTS stations;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

-- 3) Tables

-- 3.1 Users
CREATE TABLE users (
  user_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  full_name VARCHAR(150) NOT NULL,
  email VARCHAR(255) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('user','admin') NOT NULL DEFAULT 'user'
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

-- 3.2 Stations
CREATE TABLE stations (
  station_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  serial_number VARCHAR(50) NOT NULL UNIQUE,
  name VARCHAR(150) NOT NULL,
  description TEXT,
  user_id INT UNSIGNED NOT NULL,      -- owner of the station
  CONSTRAINT fk_station_user
    FOREIGN KEY (user_id) REFERENCES users(user_id)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

-- 3.3 Measurements
CREATE TABLE measurements (
  measurement_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  station_id INT UNSIGNED NOT NULL,
  measured_at DATETIME NOT NULL,      -- timestamp of measurement
  temperature DECIMAL(5,2),           -- °C
  humidity DECIMAL(5,2),              -- %
  pressure DECIMAL(7,2),              -- hPa
  light INT,                          -- lux
  gas INT,                            -- ppm
  CONSTRAINT fk_measurement_station
    FOREIGN KEY (station_id) REFERENCES stations(station_id)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  INDEX idx_measurements_station_time (station_id, measured_at)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

-- 3.4 Collections
CREATE TABLE collections (
  collection_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  description TEXT,
  user_id INT UNSIGNED NOT NULL,      -- creator / owner
  CONSTRAINT fk_collection_user
    FOREIGN KEY (user_id) REFERENCES users(user_id)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

-- 3.5 CollectionMeasurement (link: Collection <-> Measurement, M:N)
CREATE TABLE collection_measurements (
  collection_id INT UNSIGNED NOT NULL,
  measurement_id BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (collection_id, measurement_id),
  CONSTRAINT fk_cm_collection
    FOREIGN KEY (collection_id) REFERENCES collections(collection_id)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT fk_cm_measurement
    FOREIGN KEY (measurement_id) REFERENCES measurements(measurement_id)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

-- 3.6 CollectionShare (link: User <-> Collection, M:N)
CREATE TABLE collection_shares (
  collection_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (collection_id, user_id),
  CONSTRAINT fk_cs_collection
    FOREIGN KEY (collection_id) REFERENCES collections(collection_id)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT fk_cs_user
    FOREIGN KEY (user_id) REFERENCES users(user_id)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

-- 3.7 Friendships (link: User <-> User, M:N)
CREATE TABLE friendships (
  user_id INT UNSIGNED NOT NULL,
  friend_user_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (user_id, friend_user_id),
  CONSTRAINT fk_friend_user
    FOREIGN KEY (user_id) REFERENCES users(user_id)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT fk_friend_friend
    FOREIGN KEY (friend_user_id) REFERENCES users(user_id)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CHECK (user_id <> friend_user_id)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;
  
