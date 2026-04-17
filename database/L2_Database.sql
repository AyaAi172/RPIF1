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
  CHECK (sender_user_id <> receiver_user_id),
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
    ON UPDATE CASCADE,
  CHECK (user_id <> friend_user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- Sample available stations (user_id NULL)
INSERT INTO stations (serial_number, name, description, user_id) VALUES
('ST-4004-001','Station 1','Available station',NULL),
('ST-4004-002','Station 2','Available station',NULL),
('ST-4004-003','Station 3','Available station',NULL),
('1','Station 4','Available station',NULL);

USE pif_db;

INSERT INTO measurements (station_id, measured_at, temperature, humidity, pressure, light, gas) VALUES
(1, '2026-04-14 08:00:00', 18.40, 46.00, 1008.20, 180, 210),
(1, '2026-04-14 12:00:00', 19.10, 48.50, 1009.10, 260, 225),
(1, '2026-04-14 16:00:00', 19.80, 50.20, 1010.30, 340, 240),
(1, '2026-04-15 08:00:00', 18.90, 47.40, 1008.90, 200, 218),
(1, '2026-04-15 12:00:00', 20.20, 49.10, 1009.80, 290, 235),
(1, '2026-04-15 16:00:00', 21.00, 51.00, 1010.60, 360, 248),
(1, '2026-04-16 08:00:00', 19.30, 46.80, 1008.70, 210, 220),
(1, '2026-04-16 12:00:00', 20.60, 48.20, 1009.70, 300, 232),
(1, '2026-04-16 16:00:00', 21.40, 50.10, 1010.90, 380, 245),
(1, '2026-04-17 08:00:00', 19.70, 47.00, 1009.20, 220, 222),
(1, '2026-04-17 12:00:00', 21.10, 49.00, 1010.10, 310, 238),
(1, '2026-04-17 16:00:00', 22.00, 51.20, 1011.00, 390, 250),

(2, '2026-04-14 08:00:00', 17.80, 52.00, 1007.90, 150, 205),
(2, '2026-04-14 12:00:00', 18.60, 54.10, 1008.80, 230, 220),
(2, '2026-04-14 16:00:00', 19.50, 55.80, 1009.70, 320, 236),
(2, '2026-04-15 08:00:00', 18.10, 52.80, 1008.00, 165, 210),
(2, '2026-04-15 12:00:00', 19.00, 54.60, 1008.90, 245, 224),
(2, '2026-04-15 16:00:00', 19.90, 56.20, 1009.90, 330, 238),
(2, '2026-04-16 08:00:00', 18.30, 53.10, 1008.20, 170, 212),
(2, '2026-04-16 12:00:00', 19.20, 54.80, 1009.10, 250, 226),
(2, '2026-04-16 16:00:00', 20.10, 56.40, 1010.00, 335, 240),
(2, '2026-04-17 08:00:00', 18.50, 53.30, 1008.40, 175, 214),
(2, '2026-04-17 12:00:00', 19.40, 55.00, 1009.20, 255, 228),
(2, '2026-04-17 16:00:00', 20.30, 56.70, 1010.10, 340, 242),

(3, '2026-04-14 08:00:00', 16.90, 58.00, 1006.80, 120, 198),
(3, '2026-04-14 12:00:00', 17.70, 59.60, 1007.70, 200, 212),
(3, '2026-04-14 16:00:00', 18.60, 61.20, 1008.50, 285, 225),
(3, '2026-04-15 08:00:00', 17.20, 58.50, 1006.90, 130, 202),
(3, '2026-04-15 12:00:00', 18.10, 60.10, 1007.80, 210, 216),
(3, '2026-04-15 16:00:00', 18.90, 61.70, 1008.70, 295, 228),
(3, '2026-04-16 08:00:00', 17.40, 58.90, 1007.00, 135, 204),
(3, '2026-04-16 12:00:00', 18.20, 60.50, 1007.90, 220, 218),
(3, '2026-04-16 16:00:00', 19.10, 62.10, 1008.80, 300, 230),
(3, '2026-04-17 08:00:00', 17.60, 59.20, 1007.10, 140, 206),
(3, '2026-04-17 12:00:00', 18.40, 60.80, 1008.00, 225, 220),
(3, '2026-04-17 16:00:00', 19.30, 62.40, 1008.90, 305, 232),

(4, '2026-04-14 08:00:00', 20.10, 44.00, 1010.20, 210, 230),
(4, '2026-04-14 12:00:00', 21.00, 45.80, 1011.00, 300, 245),
(4, '2026-04-14 16:00:00', 21.80, 47.40, 1011.80, 390, 258),
(4, '2026-04-15 08:00:00', 20.30, 44.50, 1010.30, 220, 232),
(4, '2026-04-15 12:00:00', 21.20, 46.20, 1011.10, 310, 247),
(4, '2026-04-15 16:00:00', 22.00, 47.90, 1012.00, 400, 260),
(4, '2026-04-16 08:00:00', 20.50, 44.80, 1010.40, 225, 234),
(4, '2026-04-16 12:00:00', 21.40, 46.50, 1011.30, 320, 249),
(4, '2026-04-16 16:00:00', 22.20, 48.10, 1012.10, 410, 262),
(4, '2026-04-17 08:00:00', 20.70, 45.00, 1010.50, 230, 236),
(4, '2026-04-17 12:00:00', 21.60, 46.70, 1011.40, 325, 251),
(4, '2026-04-17 16:00:00', 22.40, 48.30, 1012.20, 415, 264);
