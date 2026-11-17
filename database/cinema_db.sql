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
  original_title VARCHAR(150) NULL,
  language     VARCHAR(50) NOT NULL DEFAULT 'English',
  description  TEXT NOT NULL,
  duration     INT NOT NULL,
  release_date DATE NOT NULL,
  director     VARCHAR(100) NOT NULL,
  starring     VARCHAR(255) NULL,
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

INSERT INTO genres (genre_name) VALUES ('Action'), ('Adventure'), ('Animation'), ('Biography'), ('Comedy'), ('Crime'), ('Drama'), ('Family'), ('Fantasy'), ('History'), ('Horror'), ('Mystery'), ('Romance'), ('Sci-Fi'), ('Thriller'), ('War');

INSERT INTO movies (title, original_title, language, description, duration, release_date, director, starring, rating, poster, status) VALUES
  ('The Shawshank Redemption', 'الخلاص من شاوشانك', 'English', 'Banker Andy Dufresne is sentenced to life in Shawshank prison for a double murder he says he did not commit. Over two decades he forms an unlikely friendship with fellow inmate Red, keeps his hope alive, and quietly works toward a way out.', 142, '1994-09-23', 'Frank Darabont', 'Tim Robbins, Morgan Freeman, Bob Gunton, William Sadler', 9.3, 'the-shawshank-redemption.svg', 'Now Showing'),
  ('The Godfather', 'العراب', 'English', 'The aging head of the Corleone crime family tries to keep his empire safe as rival families move against him. His youngest son Michael, who wanted no part of the family business, is slowly drawn into the centre of it.', 175, '1972-03-24', 'Francis Ford Coppola', 'Marlon Brando, Al Pacino, James Caan, Diane Keaton', 9.2, 'the-godfather.svg', 'Now Showing'),
  ('The Dark Knight', 'فارس الظلام', 'English', 'Batman, Lieutenant Gordon and District Attorney Harvey Dent join forces to break Gotham''s organised crime, until a chaotic criminal called the Joker pushes the city, and its heroes, to their limits.', 152, '2008-07-18', 'Christopher Nolan', 'Christian Bale, Heath Ledger, Aaron Eckhart, Gary Oldman', 9, 'the-dark-knight.svg', 'Now Showing'),
  ('The Lord of the Rings: The Return of the King', 'سيد الخواتم: عودة الملك', 'English', 'While Gandalf and Aragorn lead the free peoples of Middle-earth against Sauron''s army, Frodo and Sam make the last and hardest part of their journey to Mount Doom to destroy the One Ring.', 201, '2003-12-17', 'Peter Jackson', 'Elijah Wood, Viggo Mortensen, Ian McKellen, Sean Astin', 9, 'the-lord-of-the-rings-the-return-of-the-king.svg', 'Now Showing'),
  ('Pulp Fiction', 'خيال رخيص', 'English', 'Two hitmen, a boxer, a gangster''s wife and a pair of diner robbers cross paths in Los Angeles in a set of interlinked stories told out of order.', 154, '1994-10-14', 'Quentin Tarantino', 'John Travolta, Uma Thurman, Samuel L. Jackson, Bruce Willis', 8.9, 'pulp-fiction.svg', 'Coming Soon'),
  ('Forrest Gump', 'فورست غامب', 'English', 'A kind-hearted man from Alabama finds himself present at many key moments of American history, while all he really wants is to be with his childhood friend Jenny.', 142, '1994-07-06', 'Robert Zemeckis', 'Tom Hanks, Robin Wright, Gary Sinise, Sally Field', 8.8, 'forrest-gump.svg', 'Now Showing'),
  ('Inception', 'استهلال', 'English', 'Dom Cobb steals secrets from people''s dreams. Offered a chance to clear his past, he must do the reverse: plant an idea deep inside a target''s mind.', 148, '2010-07-16', 'Christopher Nolan', 'Leonardo DiCaprio, Joseph Gordon-Levitt, Elliot Page, Tom Hardy', 8.8, 'inception.svg', 'Now Showing'),
  ('Interstellar', 'بين النجوم', 'English', 'With crops failing and Earth becoming unliveable, a former pilot leaves his children to lead a team through a wormhole in search of a new home for humanity.', 169, '2014-11-07', 'Christopher Nolan', 'Matthew McConaughey, Anne Hathaway, Jessica Chastain, Michael Caine', 8.7, 'interstellar.svg', 'Now Showing'),
  ('The Matrix', 'المصفوفة', 'English', 'Computer hacker Neo learns that the world he lives in is a simulation built by machines, and joins a group of rebels fighting to free humanity.', 136, '1999-03-31', 'Lana Wachowski, Lilly Wachowski', 'Keanu Reeves, Laurence Fishburne, Carrie-Anne Moss, Hugo Weaving', 8.7, 'the-matrix.svg', 'Now Showing'),
  ('Gladiator', 'المصارع', 'English', 'Betrayed by the new emperor and with his family murdered, Roman general Maximus is sold into slavery and fights his way through the arenas to Rome to seek justice.', 155, '2000-05-05', 'Ridley Scott', 'Russell Crowe, Joaquin Phoenix, Connie Nielsen, Oliver Reed', 8.5, 'gladiator.svg', 'Now Showing'),
  ('The Lion King', 'الأسد الملك', 'English', 'Young lion prince Simba runs away after his father''s death, which his uncle Scar arranged. Years later he must return to the Pride Lands to take his place as king.', 88, '1994-06-24', 'Roger Allers, Rob Minkoff', 'Matthew Broderick, Jeremy Irons, James Earl Jones, Nathan Lane', 8.5, 'the-lion-king.svg', 'Now Showing'),
  ('Oppenheimer', 'أوبنهايمر', 'English', 'The story of physicist J. Robert Oppenheimer, who led the project that built the first atomic bomb, and of the political fight that followed him afterwards.', 180, '2023-07-21', 'Christopher Nolan', 'Cillian Murphy, Emily Blunt, Matt Damon, Robert Downey Jr.', 8.3, 'oppenheimer.svg', 'Now Showing'),
  ('Dune: Part Two', 'كثيب: الجزء الثاني', 'English', 'Paul Atreides joins the Fremen of the desert planet Arrakis and seeks revenge on those who destroyed his family, while facing a future only he can foresee.', 166, '2024-03-01', 'Denis Villeneuve', 'Timothée Chalamet, Zendaya, Rebecca Ferguson, Austin Butler', 8.5, 'dune-part-two.svg', 'Coming Soon'),
  ('The Message', 'الرسالة', 'Arabic', 'The story of the early days of Islam in Mecca and Medina, told through the people around the Prophet Muhammad, from the first revelation to the return to Mecca.
قصة بداية الدعوة الإسلامية في مكة والمدينة، من نزول الوحي حتى فتح مكة، كما عاشها من كانوا حول النبي.', 177, '1976-03-09', 'Moustapha Akkad', 'Abdullah Gaith, Muna Wassef, Hamdi Ghaith, Ali Ahmed Salem', 8.1, 'the-message.svg', 'Now Showing'),
  ('Lion of the Desert', 'عمر المختار', 'Arabic / English', 'The true story of Omar Mukhtar, the Libyan teacher who led the Bedouin resistance against the Italian occupation for nearly twenty years.
القصة الحقيقية لعمر المختار، شيخ الشهداء الذي قاد المقاومة الليبية ضد الاحتلال الإيطالي قرابة عشرين عامًا.', 173, '1981-05-15', 'Moustapha Akkad', 'Anthony Quinn, Oliver Reed, Irene Papas, Rod Steiger', 8.3, 'lion-of-the-desert.svg', 'Now Showing'),
  ('Capernaum', 'كفرناحوم', 'Arabic', 'In the slums of Beirut, twelve-year-old Zain takes his parents to court for bringing him into a life of neglect, and tells the story of how he ended up there.
في أحياء بيروت الفقيرة، يرفع الطفل زين ذو الاثني عشر عامًا دعوى على والديه لأنهما أنجباه، ويروي كيف وصل إلى هذه اللحظة.', 126, '2018-09-20', 'Nadine Labaki', 'Zain Al Rafeea, Yordanos Shiferaw, Boluwatife Treasure Bankole', 8.4, 'capernaum.svg', 'Now Showing'),
  ('The Blue Elephant', 'الفيل الأزرق', 'Arabic', 'A psychiatrist returns to work in a hospital ward for criminals and finds his old friend there, accused of murder. To uncover the truth he takes a strange pill that blurs the line between reality and hallucination.
يعود الطبيب النفسي يحيى إلى العمل في قسم الحالات الخطرة ليجد صديقه القديم متهمًا بالقتل، فيدخل رحلة غامضة بين الحقيقة والهلوسة.', 170, '2014-07-28', 'Marwan Hamed', 'Karim Abdel Aziz, Khaled El Nabawy, Nelly Karim', 8, 'the-blue-elephant.svg', 'Now Showing'),
  ('Cairo Station', 'باب الحديد', 'Arabic', 'Qinawi, a lonely newspaper seller at Cairo''s main railway station, becomes obsessed with Hanuma, a lively drinks seller who is engaged to another man.
قناوي بائع الجرائد الوحيد في محطة القاهرة يتعلق بهنومة بائعة المشروبات المخطوبة لغيره، فيتحول حبه إلى هوس خطير.', 77, '1958-01-20', 'Youssef Chahine', 'Farid Shawqi, Hind Rostom, Youssef Chahine', 7.6, 'cairo-station.svg', 'Now Showing'),
  ('The Yacoubian Building', 'عمارة يعقوبيان', 'Arabic', 'The lives of the residents of an old apartment building in downtown Cairo, from the aging aristocrat to the families on the roof, paint a picture of Egyptian society.
حكايات سكان عمارة قديمة في وسط القاهرة، من الأرستقراطي العجوز إلى سكان السطح، ترسم صورة للمجتمع المصري.', 172, '2006-06-21', 'Marwan Hamed', 'Adel Emam, Nour El-Sherif, Yousra, Hend Sabry', 7.5, 'the-yacoubian-building.svg', 'Now Showing'),
  ('Wadjda', 'وجدة', 'Arabic', 'A spirited ten-year-old girl in Riyadh wants a green bicycle so she can race her friend Abdullah, and enters her school''s Quran recitation contest to win the prize money.
فتاة في العاشرة من عمرها في الرياض تحلم بدراجة خضراء لتسابق صديقها عبدالله، فتشارك في مسابقة لتلاوة القرآن لتفوز بالجائزة.', 98, '2012-08-31', 'Haifaa al-Mansour', 'Waad Mohammed, Reem Abdullah, Abdullrahman Al Gohani', 7.5, 'wadjda.svg', 'Now Showing'),
  ('Theeb', 'ذيب', 'Arabic', 'In the Arabian desert in 1916, a young Bedouin boy follows his older brother on a dangerous journey to guide a British officer, and must learn to survive alone.
في صحراء الحجاز عام 1916، يرافق الطفل البدوي ذيب أخاه في رحلة خطرة لإرشاد ضابط بريطاني، فيجد نفسه مضطرًا للنجاة وحده.', 100, '2014-09-04', 'Naji Abu Nowar', 'Jacir Eid Al-Hwietat, Hassan Mutlag Al-Maraiyeh, Hussein Salameh Al-Sweilhiyeen', 7.2, 'theeb.svg', 'Now Showing'),
  ('The Night of Counting the Years', 'المومياء', 'Arabic', 'In 1881 a young man from an Upper Egyptian tribe discovers that his family has secretly lived for generations by selling treasures from a hidden royal tomb, and must decide what to do.
في عام 1881 يكتشف شاب من قبيلة في صعيد مصر أن عائلته تعيش منذ أجيال على بيع كنوز مقبرة فرعونية مخفية، ويقف أمام قرار صعب.', 102, '1969-10-01', 'Shadi Abdel Salam', 'Ahmed Marei, Nadia Lutfi, Ahmad Hegazi, Zouzou Hamdy El-Hakim', 7.5, 'the-night-of-counting-the-years.svg', 'Coming Soon');

INSERT INTO movie_genres (movie_id, genre_id) VALUES (1, 7), (2, 6), (2, 7), (3, 1), (3, 6), (3, 7), (4, 2), (4, 7), (4, 9), (5, 6), (5, 7), (6, 7), (6, 13), (7, 1), (7, 14), (7, 15), (8, 2), (8, 7), (8, 14), (9, 1), (9, 14), (10, 1), (10, 2), (10, 7), (11, 3), (11, 2), (11, 8), (12, 4), (12, 7), (12, 10), (13, 1), (13, 2), (13, 14), (14, 4), (14, 7), (14, 10), (15, 4), (15, 7), (15, 10), (15, 16), (16, 7), (17, 12), (17, 15), (17, 11), (18, 6), (18, 7), (19, 7), (20, 7), (20, 8), (21, 2), (21, 7), (22, 7), (22, 10), (22, 12);

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
  (1, 1, CURDATE() + INTERVAL -1 DAY, '11:00', '13:25', 25.00),
  (2, 1, CURDATE() + INTERVAL -1 DAY, '13:45', '16:40', 25.00),
  (3, 1, CURDATE() + INTERVAL -1 DAY, '17:00', '19:35', 30.00),
  (4, 1, CURDATE() + INTERVAL -1 DAY, '20:00', '23:25', 30.00),
  (6, 2, CURDATE() + INTERVAL -1 DAY, '11:00', '13:25', 20.00),
  (7, 2, CURDATE() + INTERVAL -1 DAY, '13:45', '16:15', 20.00),
  (8, 2, CURDATE() + INTERVAL -1 DAY, '16:45', '19:35', 20.00),
  (9, 2, CURDATE() + INTERVAL -1 DAY, '20:00', '22:20', 25.00),
  (10, 3, CURDATE() + INTERVAL -1 DAY, '11:00', '13:35', 40.00),
  (11, 3, CURDATE() + INTERVAL -1 DAY, '14:00', '15:30', 40.00),
  (12, 3, CURDATE() + INTERVAL -1 DAY, '16:00', '19:00', 40.00),
  (14, 3, CURDATE() + INTERVAL -1 DAY, '19:30', '22:30', 45.00),
  (15, 1, CURDATE() + INTERVAL 0 DAY, '11:00', '13:55', 25.00),
  (16, 1, CURDATE() + INTERVAL 0 DAY, '14:15', '16:25', 25.00),
  (17, 1, CURDATE() + INTERVAL 0 DAY, '16:45', '19:35', 25.00),
  (18, 1, CURDATE() + INTERVAL 0 DAY, '20:00', '21:20', 30.00),
  (19, 2, CURDATE() + INTERVAL 0 DAY, '11:00', '13:55', 20.00),
  (20, 2, CURDATE() + INTERVAL 0 DAY, '14:15', '15:55', 20.00),
  (21, 2, CURDATE() + INTERVAL 0 DAY, '16:15', '17:55', 20.00),
  (1, 2, CURDATE() + INTERVAL 0 DAY, '18:15', '20:40', 25.00),
  (2, 2, CURDATE() + INTERVAL 0 DAY, '21:00', '23:55', 25.00),
  (3, 3, CURDATE() + INTERVAL 0 DAY, '11:00', '13:35', 40.00),
  (4, 3, CURDATE() + INTERVAL 0 DAY, '14:00', '17:25', 40.00),
  (6, 3, CURDATE() + INTERVAL 0 DAY, '17:45', '20:10', 45.00),
  (7, 3, CURDATE() + INTERVAL 0 DAY, '20:30', '23:00', 45.00),
  (8, 1, CURDATE() + INTERVAL 1 DAY, '11:00', '13:50', 25.00),
  (9, 1, CURDATE() + INTERVAL 1 DAY, '14:15', '16:35', 25.00),
  (10, 1, CURDATE() + INTERVAL 1 DAY, '17:00', '19:35', 30.00),
  (11, 1, CURDATE() + INTERVAL 1 DAY, '20:00', '21:30', 30.00),
  (12, 2, CURDATE() + INTERVAL 1 DAY, '11:00', '14:00', 20.00),
  (14, 2, CURDATE() + INTERVAL 1 DAY, '14:30', '17:30', 20.00),
  (15, 2, CURDATE() + INTERVAL 1 DAY, '18:00', '20:55', 25.00),
  (16, 2, CURDATE() + INTERVAL 1 DAY, '21:15', '23:25', 25.00),
  (17, 3, CURDATE() + INTERVAL 1 DAY, '11:00', '13:50', 40.00),
  (18, 3, CURDATE() + INTERVAL 1 DAY, '14:15', '15:35', 40.00),
  (19, 3, CURDATE() + INTERVAL 1 DAY, '16:00', '18:55', 40.00),
  (20, 3, CURDATE() + INTERVAL 1 DAY, '19:15', '20:55', 45.00),
  (21, 3, CURDATE() + INTERVAL 1 DAY, '21:15', '22:55', 45.00),
  (1, 1, CURDATE() + INTERVAL 2 DAY, '11:00', '13:25', 25.00),
  (2, 1, CURDATE() + INTERVAL 2 DAY, '13:45', '16:40', 25.00),
  (3, 1, CURDATE() + INTERVAL 2 DAY, '17:00', '19:35', 30.00),
  (4, 1, CURDATE() + INTERVAL 2 DAY, '20:00', '23:25', 30.00),
  (6, 2, CURDATE() + INTERVAL 2 DAY, '11:00', '13:25', 20.00),
  (7, 2, CURDATE() + INTERVAL 2 DAY, '13:45', '16:15', 20.00),
  (8, 2, CURDATE() + INTERVAL 2 DAY, '16:45', '19:35', 20.00),
  (9, 2, CURDATE() + INTERVAL 2 DAY, '20:00', '22:20', 25.00),
  (10, 3, CURDATE() + INTERVAL 2 DAY, '11:00', '13:35', 40.00),
  (11, 3, CURDATE() + INTERVAL 2 DAY, '14:00', '15:30', 40.00),
  (12, 3, CURDATE() + INTERVAL 2 DAY, '16:00', '19:00', 40.00),
  (14, 3, CURDATE() + INTERVAL 2 DAY, '19:30', '22:30', 45.00),
  (15, 1, CURDATE() + INTERVAL 3 DAY, '11:00', '13:55', 25.00),
  (16, 1, CURDATE() + INTERVAL 3 DAY, '14:15', '16:25', 25.00),
  (17, 1, CURDATE() + INTERVAL 3 DAY, '16:45', '19:35', 25.00),
  (18, 1, CURDATE() + INTERVAL 3 DAY, '20:00', '21:20', 30.00),
  (19, 2, CURDATE() + INTERVAL 3 DAY, '11:00', '13:55', 20.00),
  (20, 2, CURDATE() + INTERVAL 3 DAY, '14:15', '15:55', 20.00),
  (21, 2, CURDATE() + INTERVAL 3 DAY, '16:15', '17:55', 20.00),
  (1, 2, CURDATE() + INTERVAL 3 DAY, '18:15', '20:40', 25.00),
  (2, 2, CURDATE() + INTERVAL 3 DAY, '21:00', '23:55', 25.00),
  (3, 3, CURDATE() + INTERVAL 3 DAY, '11:00', '13:35', 40.00),
  (4, 3, CURDATE() + INTERVAL 3 DAY, '14:00', '17:25', 40.00),
  (6, 3, CURDATE() + INTERVAL 3 DAY, '17:45', '20:10', 45.00),
  (7, 3, CURDATE() + INTERVAL 3 DAY, '20:30', '23:00', 45.00),
  (8, 1, CURDATE() + INTERVAL 4 DAY, '11:00', '13:50', 25.00),
  (9, 1, CURDATE() + INTERVAL 4 DAY, '14:15', '16:35', 25.00),
  (10, 1, CURDATE() + INTERVAL 4 DAY, '17:00', '19:35', 30.00),
  (11, 1, CURDATE() + INTERVAL 4 DAY, '20:00', '21:30', 30.00),
  (12, 2, CURDATE() + INTERVAL 4 DAY, '11:00', '14:00', 20.00),
  (14, 2, CURDATE() + INTERVAL 4 DAY, '14:30', '17:30', 20.00),
  (15, 2, CURDATE() + INTERVAL 4 DAY, '18:00', '20:55', 25.00),
  (16, 2, CURDATE() + INTERVAL 4 DAY, '21:15', '23:25', 25.00),
  (17, 3, CURDATE() + INTERVAL 4 DAY, '11:00', '13:50', 40.00),
  (18, 3, CURDATE() + INTERVAL 4 DAY, '14:15', '15:35', 40.00),
  (19, 3, CURDATE() + INTERVAL 4 DAY, '16:00', '18:55', 40.00),
  (20, 3, CURDATE() + INTERVAL 4 DAY, '19:15', '20:55', 45.00),
  (21, 3, CURDATE() + INTERVAL 4 DAY, '21:15', '22:55', 45.00),
  (1, 1, CURDATE() + INTERVAL 5 DAY, '11:00', '13:25', 25.00),
  (2, 1, CURDATE() + INTERVAL 5 DAY, '13:45', '16:40', 25.00),
  (3, 1, CURDATE() + INTERVAL 5 DAY, '17:00', '19:35', 30.00),
  (4, 1, CURDATE() + INTERVAL 5 DAY, '20:00', '23:25', 30.00),
  (6, 2, CURDATE() + INTERVAL 5 DAY, '11:00', '13:25', 20.00),
  (7, 2, CURDATE() + INTERVAL 5 DAY, '13:45', '16:15', 20.00),
  (8, 2, CURDATE() + INTERVAL 5 DAY, '16:45', '19:35', 20.00),
  (9, 2, CURDATE() + INTERVAL 5 DAY, '20:00', '22:20', 25.00),
  (10, 3, CURDATE() + INTERVAL 5 DAY, '11:00', '13:35', 40.00),
  (11, 3, CURDATE() + INTERVAL 5 DAY, '14:00', '15:30', 40.00),
  (12, 3, CURDATE() + INTERVAL 5 DAY, '16:00', '19:00', 40.00),
  (14, 3, CURDATE() + INTERVAL 5 DAY, '19:30', '22:30', 45.00),
  (15, 1, CURDATE() + INTERVAL 6 DAY, '11:00', '13:55', 25.00),
  (16, 1, CURDATE() + INTERVAL 6 DAY, '14:15', '16:25', 25.00),
  (17, 1, CURDATE() + INTERVAL 6 DAY, '16:45', '19:35', 25.00),
  (18, 1, CURDATE() + INTERVAL 6 DAY, '20:00', '21:20', 30.00),
  (19, 2, CURDATE() + INTERVAL 6 DAY, '11:00', '13:55', 20.00),
  (20, 2, CURDATE() + INTERVAL 6 DAY, '14:15', '15:55', 20.00),
  (21, 2, CURDATE() + INTERVAL 6 DAY, '16:15', '17:55', 20.00),
  (1, 2, CURDATE() + INTERVAL 6 DAY, '18:15', '20:40', 25.00),
  (2, 2, CURDATE() + INTERVAL 6 DAY, '21:00', '23:55', 25.00),
  (3, 3, CURDATE() + INTERVAL 6 DAY, '11:00', '13:35', 40.00),
  (4, 3, CURDATE() + INTERVAL 6 DAY, '14:00', '17:25', 40.00),
  (6, 3, CURDATE() + INTERVAL 6 DAY, '17:45', '20:10', 45.00),
  (7, 3, CURDATE() + INTERVAL 6 DAY, '20:30', '23:00', 45.00);

INSERT INTO bookings (user_id, showtime_id, booking_date, total_amount, status, payment_status) VALUES (2, 3, NOW() - INTERVAL 2 DAY, 60.00, 'Confirmed', 'Paid');

INSERT INTO booking_seats (booking_id, showtime_id, seat_id, price) VALUES (1, 3, 42, 30.00), (1, 3, 43, 30.00);

INSERT INTO bookings (user_id, showtime_id, booking_date, total_amount, status, payment_status) VALUES (2, 28, NOW() - INTERVAL 1 DAY, 90.00, 'Confirmed', 'Paid');

INSERT INTO booking_seats (booking_id, showtime_id, seat_id, price) VALUES (2, 28, 53, 30.00), (2, 28, 54, 30.00), (2, 28, 55, 30.00);

INSERT INTO bookings (user_id, showtime_id, booking_date, total_amount, status, payment_status) VALUES (3, 28, NOW() - INTERVAL 1 DAY, 180.00, 'Confirmed', 'Paid');

INSERT INTO booking_seats (booking_id, showtime_id, seat_id, price) VALUES (3, 28, 77, 45.00), (3, 28, 78, 45.00), (3, 28, 79, 45.00), (3, 28, 80, 45.00);

INSERT INTO bookings (user_id, showtime_id, booking_date, total_amount, status, payment_status) VALUES (3, 44, NOW() - INTERVAL 0 DAY, 40.00, 'Confirmed', 'Paid');

INSERT INTO booking_seats (booking_id, showtime_id, seat_id, price) VALUES (4, 44, 120, 20.00), (4, 44, 121, 20.00);

INSERT INTO bookings (user_id, showtime_id, booking_date, total_amount, status, payment_status) VALUES (3, 62, NOW() - INTERVAL 0 DAY, 135.00, 'Cancelled', 'Refunded');
