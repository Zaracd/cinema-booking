-- Cinema Management and Online Booking System
-- Import this file in phpMyAdmin (Import tab) or run:  mysql -u root < cinema_db.sql
-- Demo logins:  admin@cinema.test / Admin@123   and   customer@cinema.test / Customer@123
-- Show dates are relative to the day you import, so the demo always has upcoming screenings.

DROP DATABASE IF EXISTS cinema_db;
CREATE DATABASE cinema_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE cinema_db;

CREATE TABLE users (
  user_id     INT AUTO_INCREMENT PRIMARY KEY,
  full_name   VARCHAR(100) NOT NULL,
  email       VARCHAR(150) NOT NULL UNIQUE,
  phone       VARCHAR(20)  NOT NULL,
  password    VARCHAR(255) NOT NULL,
  role        ENUM('Customer','Admin') NOT NULL DEFAULT 'Customer',
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- "Remember me" tokens. Only a hash of the cookie secret is stored, never the password.
CREATE TABLE remember_tokens (
  selector    CHAR(18) PRIMARY KEY,
  user_id     INT NOT NULL,
  token_hash  CHAR(64) NOT NULL,
  expires_at  DATETIME NOT NULL,
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE movies (
  movie_id     INT AUTO_INCREMENT PRIMARY KEY,
  title        VARCHAR(150) NOT NULL,
  description  TEXT NOT NULL,
  duration     INT NOT NULL,
  release_date DATE NOT NULL,
  director     VARCHAR(100) NOT NULL,
  rating       DECIMAL(3,1) NOT NULL DEFAULT 0,
  poster       VARCHAR(255) NULL,
  status       ENUM('Now Showing','Coming Soon','Ended') NOT NULL DEFAULT 'Coming Soon',
  INDEX (status), INDEX (title)
) ENGINE=InnoDB;

CREATE TABLE genres (
  genre_id    INT AUTO_INCREMENT PRIMARY KEY,
  genre_name  VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE movie_genres (
  movie_id  INT NOT NULL,
  genre_id  INT NOT NULL,
  PRIMARY KEY (movie_id, genre_id),
  FOREIGN KEY (movie_id) REFERENCES movies(movie_id) ON DELETE CASCADE,
  FOREIGN KEY (genre_id) REFERENCES genres(genre_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE halls (
  hall_id    INT AUTO_INCREMENT PRIMARY KEY,
  hall_name  VARCHAR(50) NOT NULL UNIQUE,
  capacity   INT NOT NULL
) ENGINE=InnoDB;

CREATE TABLE seats (
  seat_id      INT AUTO_INCREMENT PRIMARY KEY,
  hall_id      INT NOT NULL,
  row_label    VARCHAR(3) NOT NULL,
  seat_number  INT NOT NULL,
  seat_type    VARCHAR(20) NOT NULL DEFAULT 'Standard',
  UNIQUE KEY uq_seat_position (hall_id, row_label, seat_number),
  FOREIGN KEY (hall_id) REFERENCES halls(hall_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE showtimes (
  showtime_id   INT AUTO_INCREMENT PRIMARY KEY,
  movie_id      INT NOT NULL,
  hall_id       INT NOT NULL,
  show_date     DATE NOT NULL,
  start_time    TIME NOT NULL,
  end_time      TIME NOT NULL,
  ticket_price  DECIMAL(8,2) NOT NULL,
  INDEX (show_date),
  FOREIGN KEY (movie_id) REFERENCES movies(movie_id) ON DELETE RESTRICT,
  FOREIGN KEY (hall_id)  REFERENCES halls(hall_id)  ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE bookings (
  booking_id      INT AUTO_INCREMENT PRIMARY KEY,
  user_id         INT NOT NULL,
  showtime_id     INT NOT NULL,
  booking_date    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  total_amount    DECIMAL(10,2) NOT NULL,
  status          ENUM('Pending','Confirmed','Cancelled') NOT NULL DEFAULT 'Pending',
  payment_status  ENUM('Unpaid','Paid','Refunded') NOT NULL DEFAULT 'Unpaid',
  FOREIGN KEY (user_id)     REFERENCES users(user_id)         ON DELETE CASCADE,
  FOREIGN KEY (showtime_id) REFERENCES showtimes(showtime_id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- UNIQUE (showtime_id, seat_id): the database itself refuses to book a seat twice
-- for the same screening, even if two people press "Book" at the same moment.
CREATE TABLE booking_seats (
  booking_seat_id  INT AUTO_INCREMENT PRIMARY KEY,
  booking_id       INT NOT NULL,
  showtime_id      INT NOT NULL,
  seat_id          INT NOT NULL,
  price            DECIMAL(8,2) NOT NULL,
  UNIQUE KEY uq_seat_per_showtime (showtime_id, seat_id),
  FOREIGN KEY (booking_id)  REFERENCES bookings(booking_id)   ON DELETE CASCADE,
  FOREIGN KEY (showtime_id) REFERENCES showtimes(showtime_id) ON DELETE CASCADE,
  FOREIGN KEY (seat_id)     REFERENCES seats(seat_id)         ON DELETE RESTRICT
) ENGINE=InnoDB;


-- ---------- Demo data ----------

INSERT INTO users (full_name, email, phone, password, role) VALUES
  ('Cinema Admin', 'admin@cinema.test', '+218910000000', '$2y$10$6I7xMwedU.W/Y4NEnDL7j.6wme593QMecMGGNwlVT4wTcGhkzV80C', 'Admin'),
  ('Demo Customer', 'customer@cinema.test', '+218920000000', '$2y$10$CHsmkLzpfWmZ7uknUOfxAOQOIG01j3CpLpyjhonIAX4pF6ZDHmhZm', 'Customer'),
  ('Sara Ali', 'sara@cinema.test', '+218930000000', '$2y$10$CHsmkLzpfWmZ7uknUOfxAOQOIG01j3CpLpyjhonIAX4pF6ZDHmhZm', 'Customer');

INSERT INTO genres (genre_name) VALUES ('Action'), ('Adventure'), ('Animation'), ('Comedy'), ('Drama'), ('Family'), ('Horror'), ('Mystery'), ('Romance'), ('Sci-Fi'), ('Thriller');

INSERT INTO movies (title, description, duration, release_date, director, rating, poster, status) VALUES
  ('The Last Lighthouse', 'A retired keeper returns to a remote island lighthouse after a ship vanishes in a storm, and starts to suspect the light itself is guiding vessels to their end.', 128, CURDATE() + INTERVAL -40 DAY, 'Laila Haddad', 8.1, 'the-last-lighthouse.svg', 'Now Showing'),
  ('Orbit of Ashes', 'When a mining station loses contact with Earth, its last pilot must cross a field of burning debris to bring forty survivors home.', 142, CURDATE() + INTERVAL -25 DAY, 'Marcus Reyne', 7.6, 'orbit-of-ashes.svg', 'Now Showing'),
  ('Desert Wind', 'Two estranged brothers retrace their grandfather''s caravan route across the Sahara to return a family heirloom to an oasis town.', 117, CURDATE() + INTERVAL -18 DAY, 'Omar Benali', 7.9, 'desert-wind.svg', 'Now Showing'),
  ('Laugh Track', 'A failing sitcom writer discovers that whatever he types into his script comes true the next morning, with hilarious side effects.', 96, CURDATE() + INTERVAL -12 DAY, 'Jenna Cole', 6.8, 'laugh-track.svg', 'Now Showing'),
  ('Whispers in the Hall', 'A night guard at an abandoned school hears students'' voices in the corridors every night at exactly 3:07 AM.', 104, CURDATE() + INTERVAL -9 DAY, 'Viktor Lang', 7.1, 'whispers-in-the-hall.svg', 'Now Showing'),
  ('Paper Dragons', 'A shy girl folds a paper dragon that comes to life, and together they fly across the city to save her grandmother''s bookshop.', 92, CURDATE() + INTERVAL -5 DAY, 'Mei Tanaka', 8.3, 'paper-dragons.svg', 'Now Showing'),
  ('Silent Code', 'A young programmer finds a hidden message in a banking system that predicts a crime one hour before it happens.', 135, CURDATE() + INTERVAL 20 DAY, 'Aaron Pike', 0, 'silent-code.svg', 'Coming Soon'),
  ('Harbor Lights', 'A ferry captain and a travelling painter meet every summer for ten years in the same small harbor town.', 110, CURDATE() + INTERVAL 34 DAY, 'Sofia Marin', 0, 'harbor-lights.svg', 'Coming Soon'),
  ('Iron Valley', 'A former soldier defends a mountain village from a private army after the valley''s iron mine is sold overnight.', 125, CURDATE() + INTERVAL -120 DAY, 'Daniel Brooks', 7, 'iron-valley.svg', 'Ended');

INSERT INTO movie_genres (movie_id, genre_id) VALUES (1, 5), (1, 8), (2, 10), (2, 1), (3, 2), (3, 5), (4, 4), (5, 7), (5, 11), (6, 3), (6, 6), (6, 2), (7, 11), (7, 10), (8, 9), (8, 5), (9, 1);

INSERT INTO halls (hall_name, capacity) VALUES ('Hall 1', 96), ('Hall 2', 60), ('VIP Lounge', 32);

INSERT INTO seats (hall_id, row_label, seat_number, seat_type) VALUES
  (1, 'A', 1, 'Standard'),
  (1, 'A', 2, 'Standard'),
  (1, 'A', 3, 'Standard'),
  (1, 'A', 4, 'Standard'),
  (1, 'A', 5, 'Standard'),
  (1, 'A', 6, 'Standard'),
  (1, 'A', 7, 'Standard'),
  (1, 'A', 8, 'Standard'),
  (1, 'A', 9, 'Standard'),
  (1, 'A', 10, 'Standard'),
  (1, 'A', 11, 'Standard'),
  (1, 'A', 12, 'Standard'),
  (1, 'B', 1, 'Standard'),
  (1, 'B', 2, 'Standard'),
  (1, 'B', 3, 'Standard'),
  (1, 'B', 4, 'Standard'),
  (1, 'B', 5, 'Standard'),
  (1, 'B', 6, 'Standard'),
  (1, 'B', 7, 'Standard'),
  (1, 'B', 8, 'Standard'),
  (1, 'B', 9, 'Standard'),
  (1, 'B', 10, 'Standard'),
  (1, 'B', 11, 'Standard'),
  (1, 'B', 12, 'Standard'),
  (1, 'C', 1, 'Standard'),
  (1, 'C', 2, 'Standard'),
  (1, 'C', 3, 'Standard'),
  (1, 'C', 4, 'Standard'),
  (1, 'C', 5, 'Standard'),
  (1, 'C', 6, 'Standard'),
  (1, 'C', 7, 'Standard'),
  (1, 'C', 8, 'Standard'),
  (1, 'C', 9, 'Standard'),
  (1, 'C', 10, 'Standard'),
  (1, 'C', 11, 'Standard'),
  (1, 'C', 12, 'Standard'),
  (1, 'D', 1, 'Standard'),
  (1, 'D', 2, 'Standard'),
  (1, 'D', 3, 'Standard'),
  (1, 'D', 4, 'Standard'),
  (1, 'D', 5, 'Standard'),
  (1, 'D', 6, 'Standard'),
  (1, 'D', 7, 'Standard'),
  (1, 'D', 8, 'Standard'),
  (1, 'D', 9, 'Standard'),
  (1, 'D', 10, 'Standard'),
  (1, 'D', 11, 'Standard'),
  (1, 'D', 12, 'Standard'),
  (1, 'E', 1, 'Standard'),
  (1, 'E', 2, 'Standard'),
  (1, 'E', 3, 'Standard'),
  (1, 'E', 4, 'Standard'),
  (1, 'E', 5, 'Standard'),
  (1, 'E', 6, 'Standard'),
  (1, 'E', 7, 'Standard'),
  (1, 'E', 8, 'Standard'),
  (1, 'E', 9, 'Standard'),
  (1, 'E', 10, 'Standard'),
  (1, 'E', 11, 'Standard'),
  (1, 'E', 12, 'Standard'),
  (1, 'F', 1, 'Standard'),
  (1, 'F', 2, 'Standard'),
  (1, 'F', 3, 'Standard'),
  (1, 'F', 4, 'Standard'),
  (1, 'F', 5, 'Standard'),
  (1, 'F', 6, 'Standard'),
  (1, 'F', 7, 'Standard'),
  (1, 'F', 8, 'Standard'),
  (1, 'F', 9, 'Standard'),
  (1, 'F', 10, 'Standard'),
  (1, 'F', 11, 'Standard'),
  (1, 'F', 12, 'Standard'),
  (1, 'G', 1, 'VIP'),
  (1, 'G', 2, 'VIP'),
  (1, 'G', 3, 'VIP'),
  (1, 'G', 4, 'VIP'),
  (1, 'G', 5, 'VIP'),
  (1, 'G', 6, 'VIP'),
  (1, 'G', 7, 'VIP'),
  (1, 'G', 8, 'VIP'),
  (1, 'G', 9, 'VIP'),
  (1, 'G', 10, 'VIP'),
  (1, 'G', 11, 'VIP'),
  (1, 'G', 12, 'VIP'),
  (1, 'H', 1, 'VIP'),
  (1, 'H', 2, 'VIP'),
  (1, 'H', 3, 'VIP'),
  (1, 'H', 4, 'VIP'),
  (1, 'H', 5, 'VIP'),
  (1, 'H', 6, 'VIP'),
  (1, 'H', 7, 'VIP'),
  (1, 'H', 8, 'VIP'),
  (1, 'H', 9, 'VIP'),
  (1, 'H', 10, 'VIP'),
  (1, 'H', 11, 'VIP'),
  (1, 'H', 12, 'VIP'),
  (2, 'A', 1, 'Standard'),
  (2, 'A', 2, 'Standard'),
  (2, 'A', 3, 'Standard'),
  (2, 'A', 4, 'Standard'),
  (2, 'A', 5, 'Standard'),
  (2, 'A', 6, 'Standard'),
  (2, 'A', 7, 'Standard'),
  (2, 'A', 8, 'Standard'),
  (2, 'A', 9, 'Standard'),
  (2, 'A', 10, 'Standard'),
  (2, 'B', 1, 'Standard'),
  (2, 'B', 2, 'Standard'),
  (2, 'B', 3, 'Standard'),
  (2, 'B', 4, 'Standard'),
  (2, 'B', 5, 'Standard'),
  (2, 'B', 6, 'Standard'),
  (2, 'B', 7, 'Standard'),
  (2, 'B', 8, 'Standard'),
  (2, 'B', 9, 'Standard'),
  (2, 'B', 10, 'Standard'),
  (2, 'C', 1, 'Standard'),
  (2, 'C', 2, 'Standard'),
  (2, 'C', 3, 'Standard'),
  (2, 'C', 4, 'Standard'),
  (2, 'C', 5, 'Standard'),
  (2, 'C', 6, 'Standard'),
  (2, 'C', 7, 'Standard'),
  (2, 'C', 8, 'Standard'),
  (2, 'C', 9, 'Standard'),
  (2, 'C', 10, 'Standard'),
  (2, 'D', 1, 'Standard'),
  (2, 'D', 2, 'Standard'),
  (2, 'D', 3, 'Standard'),
  (2, 'D', 4, 'Standard'),
  (2, 'D', 5, 'Standard'),
  (2, 'D', 6, 'Standard'),
  (2, 'D', 7, 'Standard'),
  (2, 'D', 8, 'Standard'),
  (2, 'D', 9, 'Standard'),
  (2, 'D', 10, 'Standard'),
  (2, 'E', 1, 'Standard'),
  (2, 'E', 2, 'Standard'),
  (2, 'E', 3, 'Standard'),
  (2, 'E', 4, 'Standard'),
  (2, 'E', 5, 'Standard'),
  (2, 'E', 6, 'Standard'),
  (2, 'E', 7, 'Standard'),
  (2, 'E', 8, 'Standard'),
  (2, 'E', 9, 'Standard'),
  (2, 'E', 10, 'Standard'),
  (2, 'F', 1, 'VIP'),
  (2, 'F', 2, 'VIP'),
  (2, 'F', 3, 'VIP'),
  (2, 'F', 4, 'VIP'),
  (2, 'F', 5, 'VIP'),
  (2, 'F', 6, 'VIP'),
  (2, 'F', 7, 'VIP'),
  (2, 'F', 8, 'VIP'),
  (2, 'F', 9, 'VIP'),
  (2, 'F', 10, 'VIP'),
  (3, 'A', 1, 'VIP'),
  (3, 'A', 2, 'VIP'),
  (3, 'A', 3, 'VIP'),
  (3, 'A', 4, 'VIP'),
  (3, 'A', 5, 'VIP'),
  (3, 'A', 6, 'VIP'),
  (3, 'A', 7, 'VIP'),
  (3, 'A', 8, 'VIP'),
  (3, 'B', 1, 'VIP'),
  (3, 'B', 2, 'VIP'),
  (3, 'B', 3, 'VIP'),
  (3, 'B', 4, 'VIP'),
  (3, 'B', 5, 'VIP'),
  (3, 'B', 6, 'VIP'),
  (3, 'B', 7, 'VIP'),
  (3, 'B', 8, 'VIP'),
  (3, 'C', 1, 'VIP'),
  (3, 'C', 2, 'VIP'),
  (3, 'C', 3, 'VIP'),
  (3, 'C', 4, 'VIP'),
  (3, 'C', 5, 'VIP'),
  (3, 'C', 6, 'VIP'),
  (3, 'C', 7, 'VIP'),
  (3, 'C', 8, 'VIP'),
  (3, 'D', 1, 'VIP'),
  (3, 'D', 2, 'VIP'),
  (3, 'D', 3, 'VIP'),
  (3, 'D', 4, 'VIP'),
  (3, 'D', 5, 'VIP'),
  (3, 'D', 6, 'VIP'),
  (3, 'D', 7, 'VIP'),
  (3, 'D', 8, 'VIP');

INSERT INTO showtimes (movie_id, hall_id, show_date, start_time, end_time, ticket_price) VALUES
  (1, 1, CURDATE() + INTERVAL -1 DAY, '12:00', '14:10', 25.00),
  (2, 1, CURDATE() + INTERVAL -1 DAY, '15:00', '17:25', 25.00),
  (3, 1, CURDATE() + INTERVAL -1 DAY, '18:00', '20:00', 30.00),
  (4, 1, CURDATE() + INTERVAL -1 DAY, '21:00', '22:40', 30.00),
  (3, 2, CURDATE() + INTERVAL -1 DAY, '12:00', '14:00', 20.00),
  (4, 2, CURDATE() + INTERVAL -1 DAY, '15:00', '16:40', 20.00),
  (5, 2, CURDATE() + INTERVAL -1 DAY, '18:00', '19:45', 25.00),
  (6, 2, CURDATE() + INTERVAL -1 DAY, '21:00', '22:35', 25.00),
  (5, 3, CURDATE() + INTERVAL -1 DAY, '12:00', '13:45', 40.00),
  (6, 3, CURDATE() + INTERVAL -1 DAY, '15:00', '16:35', 40.00),
  (1, 3, CURDATE() + INTERVAL -1 DAY, '18:00', '20:10', 45.00),
  (2, 3, CURDATE() + INTERVAL -1 DAY, '21:00', '23:25', 45.00),
  (2, 1, CURDATE() + INTERVAL 0 DAY, '12:00', '14:25', 25.00),
  (3, 1, CURDATE() + INTERVAL 0 DAY, '15:00', '17:00', 25.00),
  (4, 1, CURDATE() + INTERVAL 0 DAY, '18:00', '19:40', 30.00),
  (5, 1, CURDATE() + INTERVAL 0 DAY, '21:00', '22:45', 30.00),
  (4, 2, CURDATE() + INTERVAL 0 DAY, '12:00', '13:40', 20.00),
  (5, 2, CURDATE() + INTERVAL 0 DAY, '15:00', '16:45', 20.00),
  (6, 2, CURDATE() + INTERVAL 0 DAY, '18:00', '19:35', 25.00),
  (1, 2, CURDATE() + INTERVAL 0 DAY, '21:00', '23:10', 25.00),
  (6, 3, CURDATE() + INTERVAL 0 DAY, '12:00', '13:35', 40.00),
  (1, 3, CURDATE() + INTERVAL 0 DAY, '15:00', '17:10', 40.00),
  (2, 3, CURDATE() + INTERVAL 0 DAY, '18:00', '20:25', 45.00),
  (3, 3, CURDATE() + INTERVAL 0 DAY, '21:00', '23:00', 45.00),
  (3, 1, CURDATE() + INTERVAL 1 DAY, '12:00', '14:00', 25.00),
  (4, 1, CURDATE() + INTERVAL 1 DAY, '15:00', '16:40', 25.00),
  (5, 1, CURDATE() + INTERVAL 1 DAY, '18:00', '19:45', 30.00),
  (6, 1, CURDATE() + INTERVAL 1 DAY, '21:00', '22:35', 30.00),
  (5, 2, CURDATE() + INTERVAL 1 DAY, '12:00', '13:45', 20.00),
  (6, 2, CURDATE() + INTERVAL 1 DAY, '15:00', '16:35', 20.00),
  (1, 2, CURDATE() + INTERVAL 1 DAY, '18:00', '20:10', 25.00),
  (2, 2, CURDATE() + INTERVAL 1 DAY, '21:00', '23:25', 25.00),
  (1, 3, CURDATE() + INTERVAL 1 DAY, '12:00', '14:10', 40.00),
  (2, 3, CURDATE() + INTERVAL 1 DAY, '15:00', '17:25', 40.00),
  (3, 3, CURDATE() + INTERVAL 1 DAY, '18:00', '20:00', 45.00),
  (4, 3, CURDATE() + INTERVAL 1 DAY, '21:00', '22:40', 45.00),
  (4, 1, CURDATE() + INTERVAL 2 DAY, '12:00', '13:40', 25.00),
  (5, 1, CURDATE() + INTERVAL 2 DAY, '15:00', '16:45', 25.00),
  (6, 1, CURDATE() + INTERVAL 2 DAY, '18:00', '19:35', 30.00),
  (1, 1, CURDATE() + INTERVAL 2 DAY, '21:00', '23:10', 30.00),
  (6, 2, CURDATE() + INTERVAL 2 DAY, '12:00', '13:35', 20.00),
  (1, 2, CURDATE() + INTERVAL 2 DAY, '15:00', '17:10', 20.00),
  (2, 2, CURDATE() + INTERVAL 2 DAY, '18:00', '20:25', 25.00),
  (3, 2, CURDATE() + INTERVAL 2 DAY, '21:00', '23:00', 25.00),
  (2, 3, CURDATE() + INTERVAL 2 DAY, '12:00', '14:25', 40.00),
  (3, 3, CURDATE() + INTERVAL 2 DAY, '15:00', '17:00', 40.00),
  (4, 3, CURDATE() + INTERVAL 2 DAY, '18:00', '19:40', 45.00),
  (5, 3, CURDATE() + INTERVAL 2 DAY, '21:00', '22:45', 45.00),
  (5, 1, CURDATE() + INTERVAL 3 DAY, '12:00', '13:45', 25.00),
  (6, 1, CURDATE() + INTERVAL 3 DAY, '15:00', '16:35', 25.00),
  (1, 1, CURDATE() + INTERVAL 3 DAY, '18:00', '20:10', 30.00),
  (2, 1, CURDATE() + INTERVAL 3 DAY, '21:00', '23:25', 30.00),
  (1, 2, CURDATE() + INTERVAL 3 DAY, '12:00', '14:10', 20.00),
  (2, 2, CURDATE() + INTERVAL 3 DAY, '15:00', '17:25', 20.00),
  (3, 2, CURDATE() + INTERVAL 3 DAY, '18:00', '20:00', 25.00),
  (4, 2, CURDATE() + INTERVAL 3 DAY, '21:00', '22:40', 25.00),
  (3, 3, CURDATE() + INTERVAL 3 DAY, '12:00', '14:00', 40.00),
  (4, 3, CURDATE() + INTERVAL 3 DAY, '15:00', '16:40', 40.00),
  (5, 3, CURDATE() + INTERVAL 3 DAY, '18:00', '19:45', 45.00),
  (6, 3, CURDATE() + INTERVAL 3 DAY, '21:00', '22:35', 45.00),
  (6, 1, CURDATE() + INTERVAL 4 DAY, '12:00', '13:35', 25.00),
  (1, 1, CURDATE() + INTERVAL 4 DAY, '15:00', '17:10', 25.00),
  (2, 1, CURDATE() + INTERVAL 4 DAY, '18:00', '20:25', 30.00),
  (3, 1, CURDATE() + INTERVAL 4 DAY, '21:00', '23:00', 30.00),
  (2, 2, CURDATE() + INTERVAL 4 DAY, '12:00', '14:25', 20.00),
  (3, 2, CURDATE() + INTERVAL 4 DAY, '15:00', '17:00', 20.00),
  (4, 2, CURDATE() + INTERVAL 4 DAY, '18:00', '19:40', 25.00),
  (5, 2, CURDATE() + INTERVAL 4 DAY, '21:00', '22:45', 25.00),
  (4, 3, CURDATE() + INTERVAL 4 DAY, '12:00', '13:40', 40.00),
  (5, 3, CURDATE() + INTERVAL 4 DAY, '15:00', '16:45', 40.00),
  (6, 3, CURDATE() + INTERVAL 4 DAY, '18:00', '19:35', 45.00),
  (1, 3, CURDATE() + INTERVAL 4 DAY, '21:00', '23:10', 45.00),
  (1, 1, CURDATE() + INTERVAL 5 DAY, '12:00', '14:10', 25.00),
  (2, 1, CURDATE() + INTERVAL 5 DAY, '15:00', '17:25', 25.00),
  (3, 1, CURDATE() + INTERVAL 5 DAY, '18:00', '20:00', 30.00),
  (4, 1, CURDATE() + INTERVAL 5 DAY, '21:00', '22:40', 30.00),
  (3, 2, CURDATE() + INTERVAL 5 DAY, '12:00', '14:00', 20.00),
  (4, 2, CURDATE() + INTERVAL 5 DAY, '15:00', '16:40', 20.00),
  (5, 2, CURDATE() + INTERVAL 5 DAY, '18:00', '19:45', 25.00),
  (6, 2, CURDATE() + INTERVAL 5 DAY, '21:00', '22:35', 25.00),
  (5, 3, CURDATE() + INTERVAL 5 DAY, '12:00', '13:45', 40.00),
  (6, 3, CURDATE() + INTERVAL 5 DAY, '15:00', '16:35', 40.00),
  (1, 3, CURDATE() + INTERVAL 5 DAY, '18:00', '20:10', 45.00),
  (2, 3, CURDATE() + INTERVAL 5 DAY, '21:00', '23:25', 45.00),
  (2, 1, CURDATE() + INTERVAL 6 DAY, '12:00', '14:25', 25.00),
  (3, 1, CURDATE() + INTERVAL 6 DAY, '15:00', '17:00', 25.00),
  (4, 1, CURDATE() + INTERVAL 6 DAY, '18:00', '19:40', 30.00),
  (5, 1, CURDATE() + INTERVAL 6 DAY, '21:00', '22:45', 30.00),
  (4, 2, CURDATE() + INTERVAL 6 DAY, '12:00', '13:40', 20.00),
  (5, 2, CURDATE() + INTERVAL 6 DAY, '15:00', '16:45', 20.00),
  (6, 2, CURDATE() + INTERVAL 6 DAY, '18:00', '19:35', 25.00),
  (1, 2, CURDATE() + INTERVAL 6 DAY, '21:00', '23:10', 25.00),
  (6, 3, CURDATE() + INTERVAL 6 DAY, '12:00', '13:35', 40.00),
  (1, 3, CURDATE() + INTERVAL 6 DAY, '15:00', '17:10', 40.00),
  (2, 3, CURDATE() + INTERVAL 6 DAY, '18:00', '20:25', 45.00),
  (3, 3, CURDATE() + INTERVAL 6 DAY, '21:00', '23:00', 45.00);

INSERT INTO bookings (user_id, showtime_id, booking_date, total_amount, status, payment_status) VALUES (2, 3, NOW() - INTERVAL 2 DAY, 60.00, 'Confirmed', 'Paid');

INSERT INTO booking_seats (booking_id, showtime_id, seat_id, price) VALUES (1, 3, 42, 30.00), (1, 3, 43, 30.00);

INSERT INTO bookings (user_id, showtime_id, booking_date, total_amount, status, payment_status) VALUES (2, 28, NOW() - INTERVAL 1 DAY, 90.00, 'Confirmed', 'Paid');

INSERT INTO booking_seats (booking_id, showtime_id, seat_id, price) VALUES (2, 28, 53, 30.00), (2, 28, 54, 30.00), (2, 28, 55, 30.00);

INSERT INTO bookings (user_id, showtime_id, booking_date, total_amount, status, payment_status) VALUES (3, 28, NOW() - INTERVAL 1 DAY, 180.00, 'Confirmed', 'Paid');

INSERT INTO booking_seats (booking_id, showtime_id, seat_id, price) VALUES (3, 28, 77, 45.00), (3, 28, 78, 45.00), (3, 28, 79, 45.00), (3, 28, 80, 45.00);

INSERT INTO bookings (user_id, showtime_id, booking_date, total_amount, status, payment_status) VALUES (3, 43, NOW() - INTERVAL 0 DAY, 50.00, 'Confirmed', 'Paid');

INSERT INTO booking_seats (booking_id, showtime_id, seat_id, price) VALUES (4, 43, 120, 25.00), (4, 43, 121, 25.00);

INSERT INTO bookings (user_id, showtime_id, booking_date, total_amount, status, payment_status) VALUES (3, 60, NOW() - INTERVAL 0 DAY, 135.00, 'Cancelled', 'Refunded');
