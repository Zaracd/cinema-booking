// Builds database/cinema_db.sql (schema + demo data) and the demo poster images.
// Run with:  node database/generate-sql.js
// You only need this if you change the demo data; cinema_db.sql is already generated.
const fs = require('fs');
const path = require('path');

const ADMIN_HASH = '$2y$10$6I7xMwedU.W/Y4NEnDL7j.6wme593QMecMGGNwlVT4wTcGhkzV80C';    // Admin@123
const CUSTOMER_HASH = '$2y$10$CHsmkLzpfWmZ7uknUOfxAOQOIG01j3CpLpyjhonIAX4pF6ZDHmhZm'; // Customer@123

const q = (s) => "'" + String(s).replace(/\\/g, '\\\\').replace(/'/g, "''") + "'";

const genres = ['Action', 'Adventure', 'Animation', 'Biography', 'Comedy', 'Crime', 'Drama', 'Family', 'Fantasy', 'History',
  'Horror', 'Mystery', 'Romance', 'Sci-Fi', 'Thriller', 'War'];

// Top-rated IMDb films, English and Arabic: see movies-data.js.
const movies = require('./movies-data');

// Halls: rows x seatsPerRow, vipRows = row letters that are VIP.
const halls = [
  { name: 'Hall 1', rows: 8, per: 12, vip: ['G', 'H'], price: 25 },
  { name: 'Hall 2', rows: 6, per: 10, vip: ['F'], price: 20 },
  { name: 'VIP Lounge', rows: 4, per: 8, vip: ['A', 'B', 'C', 'D'], price: 40 },
];

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
`);

w('-- ---------- Demo data ----------');
w(`INSERT INTO users (full_name, email, phone, password, role) VALUES
  ('Cinema Admin', 'admin@cinema.test', '+218910000000', ${q(ADMIN_HASH)}, 'Admin'),
  ('Demo Customer', 'customer@cinema.test', '+218920000000', ${q(CUSTOMER_HASH)}, 'Customer'),
  ('Sara Ali', 'sara@cinema.test', '+218930000000', ${q(CUSTOMER_HASH)}, 'Customer');`);

w('INSERT INTO genres (genre_name) VALUES ' + genres.map((g) => `(${q(g)})`).join(', ') + ';');

const slug = (t) => t.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
w('INSERT INTO movies (title, original_title, language, description, duration, release_date, director, starring, rating, poster, status) VALUES\n' +
  movies.map((m) => `  (${q(m.title)}, ${q(m.ar)}, ${q(m.lang)}, ${q(m.desc)}, ${m.dur}, ${q(m.rel)}, ${q(m.dir)}, ${q(m.cast)}, ${m.rating}, ${q(slug(m.title) + '.svg')}, ${q(m.status)})`).join(',\n') + ';');

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

// Showtimes: each hall runs films back to back from 11:00, with 20 minutes between
// screenings and the last one ending before midnight, rotating the "Now Showing" movies.
const showing = movies.map((m, i) => ({ ...m, id: i + 1 })).filter((m) => m.status === 'Now Showing');
const hhmm = (t) => `${String(Math.floor(t / 60)).padStart(2, '0')}:${String(t % 60).padStart(2, '0')}`;
const showRows = [];
const showId = {}; // `${day}-${hall}-${index of the screening that day}` -> id
let stid = 0;
let next = 0;
days.forEach((d) => {
  halls.forEach((h, hi) => {
    let start = 11 * 60;
    for (let k = 0; ; k++) {
      const m = showing[next % showing.length];
      const end = start + Math.ceil(m.dur / 5) * 5;
      if (end > 23 * 60 + 55) break;
      next++;
      const price = h.price + (start >= 17 * 60 ? 5 : 0);
      stid++;
      showId[`${d}-${hi + 1}-${k}`] = stid;
      showRows.push(`(${m.id}, ${hi + 1}, CURDATE() + INTERVAL ${d} DAY, '${hhmm(start)}', '${hhmm(end)}', ${price.toFixed(2)})`);
      start = Math.ceil((end + 20) / 15) * 15;
    }
  });
});
w('INSERT INTO showtimes (movie_id, hall_id, show_date, start_time, end_time, ticket_price) VALUES\n  ' + showRows.join(',\n  ') + ';');

// Demo bookings: one past and one upcoming for the demo customer, plus some taken seats.
const bookings = [
  { user: 2, show: showId['-1-1-2'], hall: 1, seats: ['D6', 'D7'], status: 'Confirmed', pay: 'Paid', ago: 2 },
  { user: 2, show: showId['1-1-2'], hall: 1, seats: ['E5', 'E6', 'E7'], status: 'Confirmed', pay: 'Paid', ago: 1 },
  { user: 3, show: showId['1-1-2'], hall: 1, seats: ['G5', 'G6', 'G7', 'G8'], status: 'Confirmed', pay: 'Paid', ago: 1 },
  { user: 3, show: showId['2-2-1'], hall: 2, seats: ['C4', 'C5'], status: 'Confirmed', pay: 'Paid', ago: 0 },
  { user: 3, show: showId['3-3-2'], hall: 3, seats: ['B3', 'B4'], status: 'Cancelled', pay: 'Refunded', ago: 0 },
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

// Poster images: Arabic films show the Arabic title large with the English title under it;
// English films show the English title with the Arabic title under it.
const esc = (s) => s.replace(/&/g, '&amp;').replace(/</g, '&lt;');
const wrap = (t, max) => {
  const words = t.split(' ');
  const lines = [];
  let cur = '';
  words.forEach((wd) => {
    if ((cur + ' ' + wd).trim().length > max) { if (cur) lines.push(cur); cur = wd; } else cur = (cur + ' ' + wd).trim();
  });
  if (cur) lines.push(cur);
  return lines;
};
const posterDir = path.join(__dirname, '..', 'public', 'uploads', 'posters');
fs.mkdirSync(posterDir, { recursive: true });
movies.forEach((m, i) => {
  const [c1, c2] = m.colors;
  const arabicFirst = m.lang.startsWith('Arabic');
  const main = arabicFirst
    ? { lines: wrap(m.ar, 14), font: 'Tahoma, Arial, sans-serif', dir: ' direction="rtl"' }
    : { lines: wrap(m.title.toUpperCase().replace(':', ''), 12), font: 'Georgia, serif', dir: '' };
  // Shrink the title so the longest line fits inside the 400px poster.
  const longest = Math.max(...main.lines.map((l) => l.length));
  const fit = arabicFirst ? 340 / (longest * 0.55) : (340 / longest - 2) / 0.74;
  const size = Math.floor(Math.min(main.lines.length > 3 ? 34 : main.lines.length > 2 ? 40 : 48, fit));
  const gap = size + 10;
  const startY = 300 - ((main.lines.length - 1) * gap) / 2;
  const subY = startY + (main.lines.length - 1) * gap + 52;
  const sub = arabicFirst ? m.title.toUpperCase() : m.ar;
  const subFont = arabicFirst ? 'Arial, sans-serif' : 'Tahoma, Arial, sans-serif';
  const by = 'A FILM BY ' + m.dir.toUpperCase();
  const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="400" height="600" viewBox="0 0 400 600">
<defs><linearGradient id="g" x1="0" y1="0" x2="0.4" y2="1"><stop offset="0" stop-color="${c1}"/><stop offset="1" stop-color="${c2}"/></linearGradient>
<radialGradient id="r" cx="0.7" cy="0.25" r="0.6"><stop offset="0" stop-color="#fff" stop-opacity="0.3"/><stop offset="1" stop-color="#fff" stop-opacity="0"/></radialGradient></defs>
<rect width="400" height="600" fill="url(#g)"/><rect width="400" height="600" fill="url(#r)"/>
<circle cx="${120 + (i * 37) % 180}" cy="${150 + (i * 53) % 90}" r="${60 + (i * 13) % 50}" fill="#fff" opacity="0.07"/>
<text x="200" y="70" text-anchor="middle" font-family="Arial, sans-serif" font-size="15" fill="#fff" opacity="0.8" letter-spacing="6">${m.rel.slice(0, 4)}</text>
<rect x="0" y="470" width="400" height="130" fill="#000" opacity="0.4"/>
${main.lines.map((l, k) => `<text x="200" y="${startY + k * gap}" text-anchor="middle"${main.dir} font-family="${main.font}" font-size="${size}" font-weight="700" fill="#fff" letter-spacing="${arabicFirst ? 0 : 2}">${esc(l)}</text>`).join('\n')}
<text x="200" y="${subY}" text-anchor="middle" font-family="${subFont}" font-size="${sub.length > 24 ? 15 : 19}" fill="#fff" opacity="0.85" letter-spacing="${arabicFirst ? 3 : 0}">${esc(sub)}</text>
<text x="200" y="515" text-anchor="middle" font-family="Arial, sans-serif" font-size="${by.length > 30 ? 11 : 14}" fill="#fff" opacity="0.85" letter-spacing="${by.length > 30 ? 1.5 : 3}">${esc(by)}</text>
<text x="200" y="545" text-anchor="middle" font-family="Arial, sans-serif" font-size="12" fill="#fff" opacity="0.7" letter-spacing="3">${esc(m.g.join(' · ').toUpperCase())}</text>
<text x="200" y="575" text-anchor="middle" font-family="Arial, sans-serif" font-size="13" font-weight="700" fill="#f5c518">IMDb ★ ${m.rating.toFixed(1)}</text>
</svg>`;
  fs.writeFileSync(path.join(posterDir, slug(m.title) + '.svg'), svg);
});
console.log('Wrote cinema_db.sql with', seatRows.length, 'seats and', showRows.length, 'showtimes, plus', movies.length, 'posters.');
