-- =========================================================
-- PIF Database (teacher schema + our simplifications)
-- Key changes:
-- 1) stations.user_id can be NULL (available station)
-- 2) FK ON DELETE SET NULL (do not destroy stations when user deleted)
-- =========================================================

CREATE DATABASE IF NOT EXISTS pif_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE pif_db;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS collection_measurements;
DROP TABLE IF EXISTS collection_shares;
DROP TABLE IF EXISTS friend_requests;
DROP TABLE IF EXISTS friendships;
DROP TABLE IF EXISTS measurements;
DROP TABLE IF EXISTS collections;
DROP TABLE IF EXISTS stations;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

-- USERS
CREATE TABLE users (
  user_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  full_name VARCHAR(150) NOT NULL,
  email VARCHAR(255) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('user','admin') NOT NULL DEFAULT 'user',
  theme ENUM('light','dark') NOT NULL DEFAULT 'light'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- STATIONS (CHANGED: user_id NULL allowed)
CREATE TABLE stations (
  station_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  serial_number VARCHAR(50) NOT NULL UNIQUE,
  name VARCHAR(150) NOT NULL,
  description TEXT,
  user_id INT UNSIGNED NULL, -- NULL means available
  CONSTRAINT fk_station_user
    FOREIGN KEY (user_id) REFERENCES users(user_id)
    ON DELETE SET NULL
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- MEASUREMENTS
CREATE TABLE measurements (
  measurement_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  station_id INT UNSIGNED NOT NULL,
  measured_at DATETIME NOT NULL,
  temperature DECIMAL(5,2),
  humidity DECIMAL(5,2),
  pressure DECIMAL(7,2),
  light INT,
  gas INT,
  CONSTRAINT fk_measurement_station
    FOREIGN KEY (station_id) REFERENCES stations(station_id)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  INDEX idx_measurements_station_time (station_id, measured_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- COLLECTIONS
CREATE TABLE collections (
  collection_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  description TEXT,
  user_id INT UNSIGNED NOT NULL,
  station_id INT UNSIGNED NOT NULL,
  start_at DATETIME NOT NULL,
  end_at DATETIME NOT NULL,
  CONSTRAINT fk_collection_user
    FOREIGN KEY (user_id) REFERENCES users(user_id)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT fk_collection_station
    FOREIGN KEY (station_id) REFERENCES stations(station_id)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE friend_requests (
  request_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  sender_user_id INT UNSIGNED NOT NULL,
  receiver_user_id INT UNSIGNED NOT NULL,
  status ENUM('pending','accepted','rejected') NOT NULL DEFAULT 'pending',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  responded_at DATETIME NULL,
  CONSTRAINT fk_request_sender
    FOREIGN KEY (sender_user_id) REFERENCES users(user_id)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT fk_request_receiver
    FOREIGN KEY (receiver_user_id) REFERENCES users(user_id)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  UNIQUE KEY uq_pending_pair (sender_user_id, receiver_user_id, status),
  INDEX idx_requests_receiver_status (receiver_user_id, status),
  INDEX idx_requests_sender_status (sender_user_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- Sample available stations (user_id NULL)
INSERT INTO stations (serial_number, name, description, user_id) VALUES
('ST-4004-001','Station 1','Available station',NULL),
('ST-4004-002','Station 2','Available station',NULL),
('ST-4004-003','Station 3','Available station',NULL),
('1','Station 4','Available station',NULL);


