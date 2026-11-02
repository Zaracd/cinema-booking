// Builds database/cinema_db.sql (schema + demo data) and the demo poster images.
// Run with:  node database/generate-sql.js
// You only need this if you change the demo data; cinema_db.sql is already generated.
const fs = require('fs');
const path = require('path');

const ADMIN_HASH = '$2y$10$6I7xMwedU.W/Y4NEnDL7j.6wme593QMecMGGNwlVT4wTcGhkzV80C';    // Admin@123
const CUSTOMER_HASH = '$2y$10$CHsmkLzpfWmZ7uknUOfxAOQOIG01j3CpLpyjhonIAX4pF6ZDHmhZm'; // Customer@123

const q = (s) => "'" + String(s).replace(/\\/g, '\\\\').replace(/'/g, "''") + "'";

const genres = ['Action', 'Adventure', 'Animation', 'Comedy', 'Drama', 'Family', 'Horror', 'Mystery', 'Romance', 'Sci-Fi', 'Thriller'];

// Fictional demo movies. colors = poster gradient.
const movies = [
  { title: 'The Last Lighthouse', g: ['Drama', 'Mystery'], dur: 128, rel: -40, dir: 'Laila Haddad', rating: 8.1, status: 'Now Showing', colors: ['#0f2a4a', '#e8a23b'],
    desc: 'A retired keeper returns to a remote island lighthouse after a ship vanishes in a storm, and starts to suspect the light itself is guiding vessels to their end.' },
  { title: 'Orbit of Ashes', g: ['Sci-Fi', 'Action'], dur: 142, rel: -25, dir: 'Marcus Reyne', rating: 7.6, status: 'Now Showing', colors: ['#1b0b2e', '#ff5a3c'],
    desc: 'When a mining station loses contact with Earth, its last pilot must cross a field of burning debris to bring forty survivors home.' },
  { title: 'Desert Wind', g: ['Adventure', 'Drama'], dur: 117, rel: -18, dir: 'Omar Benali', rating: 7.9, status: 'Now Showing', colors: ['#5a2e0c', '#f4c26b'],
    desc: 'Two estranged brothers retrace their grandfather\'s caravan route across the Sahara to return a family heirloom to an oasis town.' },
  { title: 'Laugh Track', g: ['Comedy'], dur: 96, rel: -12, dir: 'Jenna Cole', rating: 6.8, status: 'Now Showing', colors: ['#ff6fa1', '#ffd23f'],
    desc: 'A failing sitcom writer discovers that whatever he types into his script comes true the next morning, with hilarious side effects.' },
  { title: 'Whispers in the Hall', g: ['Horror', 'Thriller'], dur: 104, rel: -9, dir: 'Viktor Lang', rating: 7.1, status: 'Now Showing', colors: ['#050505', '#7a1020'],
    desc: 'A night guard at an abandoned school hears students\' voices in the corridors every night at exactly 3:07 AM.' },
  { title: 'Paper Dragons', g: ['Animation', 'Family', 'Adventure'], dur: 92, rel: -5, dir: 'Mei Tanaka', rating: 8.3, status: 'Now Showing', colors: ['#0b6e4f', '#9be564'],
    desc: 'A shy girl folds a paper dragon that comes to life, and together they fly across the city to save her grandmother\'s bookshop.' },
  { title: 'Silent Code', g: ['Thriller', 'Sci-Fi'], dur: 135, rel: 20, dir: 'Aaron Pike', rating: 0, status: 'Coming Soon', colors: ['#00121f', '#00d1b2'],
    desc: 'A young programmer finds a hidden message in a banking system that predicts a crime one hour before it happens.' },
  { title: 'Harbor Lights', g: ['Romance', 'Drama'], dur: 110, rel: 34, dir: 'Sofia Marin', rating: 0, status: 'Coming Soon', colors: ['#2b1055', '#d53369'],
    desc: 'A ferry captain and a travelling painter meet every summer for ten years in the same small harbor town.' },
  { title: 'Iron Valley', g: ['Action'], dur: 125, rel: -120, dir: 'Daniel Brooks', rating: 7.0, status: 'Ended', colors: ['#2d3436', '#b2bec3'],
    desc: 'A former soldier defends a mountain village from a private army after the valley\'s iron mine is sold overnight.' },
];

// Halls: rows x seatsPerRow, vipRows = row letters that are VIP.
const halls = [
  { name: 'Hall 1', rows: 8, per: 12, vip: ['G', 'H'], price: 25 },
  { name: 'Hall 2', rows: 6, per: 10, vip: ['F'], price: 20 },
  { name: 'VIP Lounge', rows: 4, per: 8, vip: ['A', 'B', 'C', 'D'], price: 40 },
];

const slots = ['12:00', '15:00', '18:00', '21:00'];
const days = [-1, 0, 1, 2, 3, 4, 5, 6];

const out = [];
const w = (s) => out.push(s);

w(`-- Cinema Management and Online Booking System
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
`);

w('-- ---------- Demo data ----------');
w(`INSERT INTO users (full_name, email, phone, password, role) VALUES
  ('Cinema Admin', 'admin@cinema.test', '+218910000000', ${q(ADMIN_HASH)}, 'Admin'),
  ('Demo Customer', 'customer@cinema.test', '+218920000000', ${q(CUSTOMER_HASH)}, 'Customer'),
  ('Sara Ali', 'sara@cinema.test', '+218930000000', ${q(CUSTOMER_HASH)}, 'Customer');`);

w('INSERT INTO genres (genre_name) VALUES ' + genres.map((g) => `(${q(g)})`).join(', ') + ';');

const slug = (t) => t.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
w('INSERT INTO movies (title, description, duration, release_date, director, rating, poster, status) VALUES\n' +
  movies.map((m) => `  (${q(m.title)}, ${q(m.desc)}, ${m.dur}, CURDATE() + INTERVAL ${m.rel} DAY, ${q(m.dir)}, ${m.rating}, ${q(slug(m.title) + '.svg')}, ${q(m.status)})`).join(',\n') + ';');

const mg = [];
movies.forEach((m, i) => m.g.forEach((g) => mg.push(`(${i + 1}, ${genres.indexOf(g) + 1})`)));
w('INSERT INTO movie_genres (movie_id, genre_id) VALUES ' + mg.join(', ') + ';');

w('INSERT INTO halls (hall_name, capacity) VALUES ' + halls.map((h) => `(${q(h.name)}, ${h.rows * h.per})`).join(', ') + ';');

// Seats, keeping track of ids for the demo bookings.
const seatId = {}; // `${hall}-${row}${num}` -> id
let sid = 0;
const seatRows = [];
halls.forEach((h, hi) => {
  for (let r = 0; r < h.rows; r++) {
    const row = String.fromCharCode(65 + r);
    for (let n = 1; n <= h.per; n++) {
      sid++;
      seatId[`${hi + 1}-${row}${n}`] = sid;
      seatRows.push(`(${hi + 1}, '${row}', ${n}, '${h.vip.includes(row) ? 'VIP' : 'Standard'}')`);
    }
  }
});
w('INSERT INTO seats (hall_id, row_label, seat_number, seat_type) VALUES\n  ' + seatRows.join(',\n  ') + ';');

// Showtimes: every hall, every day, 4 slots, rotating the "Now Showing" movies.
const showing = movies.map((m, i) => ({ ...m, id: i + 1 })).filter((m) => m.status === 'Now Showing');
const addMin = (hhmm, min) => {
  const [h, m] = hhmm.split(':').map(Number);
  const t = h * 60 + m + min;
  return `${String(Math.floor(t / 60)).padStart(2, '0')}:${String(t % 60).padStart(2, '0')}`;
};
const showRows = [];
const showId = {}; // `${day}-${hall}-${slot}` -> id
let stid = 0;
days.forEach((d, di) => {
  halls.forEach((h, hi) => {
    slots.forEach((s, si) => {
      const m = showing[(di + hi * 2 + si) % showing.length];
      const end = addMin(s, Math.ceil(m.dur / 5) * 5);
      const price = h.price + (si >= 2 ? 5 : 0);
      stid++;
      showId[`${d}-${hi + 1}-${s}`] = stid;
      showRows.push(`(${m.id}, ${hi + 1}, CURDATE() + INTERVAL ${d} DAY, '${s}', '${end}', ${price.toFixed(2)})`);
    });
  });
});
w('INSERT INTO showtimes (movie_id, hall_id, show_date, start_time, end_time, ticket_price) VALUES\n  ' + showRows.join(',\n  ') + ';');

// Demo bookings: one past and one upcoming for the demo customer, plus some taken seats.
const bookings = [
  { user: 2, show: showId['-1-1-18:00'], hall: 1, seats: ['D6', 'D7'], status: 'Confirmed', pay: 'Paid', ago: 2 },
  { user: 2, show: showId['1-1-21:00'], hall: 1, seats: ['E5', 'E6', 'E7'], status: 'Confirmed', pay: 'Paid', ago: 1 },
  { user: 3, show: showId['1-1-21:00'], hall: 1, seats: ['G5', 'G6', 'G7', 'G8'], status: 'Confirmed', pay: 'Paid', ago: 1 },
  { user: 3, show: showId['2-2-18:00'], hall: 2, seats: ['C4', 'C5'], status: 'Confirmed', pay: 'Paid', ago: 0 },
  { user: 3, show: showId['3-3-21:00'], hall: 3, seats: ['B3', 'B4'], status: 'Cancelled', pay: 'Refunded', ago: 0 },
];
const priceOf = (b, seat) => {
  const h = halls[b.hall - 1];
  const showIdx = b.show - 1;
  const base = Number(showRows[showIdx].match(/(\d+\.\d\d)\)$/)[1]);
  return h.vip.includes(seat[0]) ? base * 1.5 : base;
};
bookings.forEach((b, i) => {
  const total = b.seats.reduce((s, seat) => s + priceOf(b, seat), 0);
  w(`INSERT INTO bookings (user_id, showtime_id, booking_date, total_amount, status, payment_status) VALUES (${b.user}, ${b.show}, NOW() - INTERVAL ${b.ago} DAY, ${total.toFixed(2)}, '${b.status}', '${b.pay}');`);
  if (b.status !== 'Cancelled') {
    w('INSERT INTO booking_seats (booking_id, showtime_id, seat_id, price) VALUES ' +
      b.seats.map((s) => `(${i + 1}, ${b.show}, ${seatId[`${b.hall}-${s}`]}, ${priceOf(b, s).toFixed(2)})`).join(', ') + ';');
  }
});

fs.writeFileSync(path.join(__dirname, 'cinema_db.sql'), out.join('\n\n') + '\n');

// Simple poster images for the demo movies.
const esc = (s) => s.replace(/&/g, '&amp;').replace(/</g, '&lt;');
const wrap = (t) => {
  const words = t.toUpperCase().split(' ');
  const lines = [];
  let cur = '';
  words.forEach((wd) => {
    if ((cur + ' ' + wd).trim().length > 11) { if (cur) lines.push(cur); cur = wd; } else cur = (cur + ' ' + wd).trim();
  });
  if (cur) lines.push(cur);
  return lines;
};
const posterDir = path.join(__dirname, '..', 'public', 'uploads', 'posters');
fs.mkdirSync(posterDir, { recursive: true });
movies.forEach((m, i) => {
  const [c1, c2] = m.colors;
  const lines = wrap(m.title);
  const startY = 330 - (lines.length - 1) * 28;
  const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="400" height="600" viewBox="0 0 400 600">
<defs><linearGradient id="g" x1="0" y1="0" x2="0.4" y2="1"><stop offset="0" stop-color="${c1}"/><stop offset="1" stop-color="${c2}"/></linearGradient>
<radialGradient id="r" cx="0.7" cy="0.25" r="0.6"><stop offset="0" stop-color="#fff" stop-opacity="0.35"/><stop offset="1" stop-color="#fff" stop-opacity="0"/></radialGradient></defs>
<rect width="400" height="600" fill="url(#g)"/><rect width="400" height="600" fill="url(#r)"/>
<circle cx="${120 + (i * 37) % 180}" cy="${150 + (i * 53) % 90}" r="${60 + (i * 13) % 50}" fill="#fff" opacity="0.08"/>
<rect x="0" y="470" width="400" height="130" fill="#000" opacity="0.35"/>
${lines.map((l, k) => `<text x="200" y="${startY + k * 56}" text-anchor="middle" font-family="Georgia, serif" font-size="46" font-weight="700" fill="#fff" letter-spacing="2">${esc(l)}</text>`).join('\n')}
<text x="200" y="520" text-anchor="middle" font-family="Arial, sans-serif" font-size="16" fill="#fff" opacity="0.85" letter-spacing="4">A FILM BY ${esc(m.dir.toUpperCase())}</text>
<text x="200" y="560" text-anchor="middle" font-family="Arial, sans-serif" font-size="13" fill="#fff" opacity="0.7" letter-spacing="3">${esc(m.g.join(' · ').toUpperCase())}</text>
</svg>`;
  fs.writeFileSync(path.join(posterDir, slug(m.title) + '.svg'), svg);
});
console.log('Wrote cinema_db.sql with', seatRows.length, 'seats and', showRows.length, 'showtimes, plus', movies.length, 'posters.');
