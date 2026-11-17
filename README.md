# CineMax – Cinema Management and Online Booking System

Web Application Programming project (Fall 2026) – plain **PHP + MySQL**, MVC structure, no framework.

## What it does

**Customers** can sign up / log in (with "Remember me"), browse and search movies, filter by genre, status and
screening date, see movie details and screening times, pick seats on a live seat map, pay (simulated), get a
confirmation email, see upcoming and past bookings, and cancel up to 2 hours before the screening.

**Admins** get a dashboard (counts, revenue, today's screenings) and can manage movies (with poster upload and
genres), halls and seats, screenings (no overlaps in the same hall), bookings and users.

## Run it with XAMPP

1. Install [XAMPP](https://www.apachefriends.org/) and start **Apache** and **MySQL** in the XAMPP Control Panel.
2. Copy this whole `CinemaBooking` folder into `C:\xampp\htdocs\`.
3. Open <http://localhost/phpmyadmin>, choose **Import**, pick `database/cinema_db.sql`, and click **Import**.
  This creates the `cinema_db` database with 22 top-rated IMDb films (13 English, 9 Arabic), halls, seats and screenings for the coming week. The film list is in `database/movies-data.js`; after changing it run `node database/generate-sql.js` and import again. Official posters (from Wikipedia) are in `public/uploads/posters/films/`; a film without one there gets a generated poster.
4. Open <http://localhost/CinemaBooking/public/>

If your MySQL user or password is not `root` with an empty password, change the `db` section in `config/config.php`.

### Demo accounts

| Role     | Email                  | Password       |
|----------|------------------------|----------------|
| Admin    | admin@cinema.test      | `Admin@123`    |
| Customer | customer@cinema.test   | `Customer@123` |

Test card for the payment page: `4242 4242 4242 4242`, any future expiry (e.g. `12/30`), any 3‑digit CVV.

### Run from VS Code instead of Apache

With XAMPP's MySQL running, press **Ctrl+Shift+P → Tasks: Run Task → Start PHP server**, then open
<http://localhost:8000>. (The task uses `C:\xampp\php\php.exe`.)

## Email

Emails (booking confirmed / cancelled) use PHPMailer (in `lib/PHPMailer`). While `mail.enabled` is `false` in
`config/config.php`, each email is written to `storage/logs/mail.log` so you can read it without an SMTP account.
To really send: set `enabled` to `true` and fill in your SMTP details (for Gmail, create an *App password*).

## Folder structure (MVC)

```
app/
  Core/          Router, Controller/Model base classes, Database (PDO), Auth, Csrf, Validator, Mailer, helpers
  Models/        User, Movie, Genre, Hall, Seat, Showtime, Booking
  Controllers/   Home, Movie, Auth, Booking, Account  +  Admin/ (Dashboard, Movies, Halls, Showtimes, Bookings, Users)
  Views/         layouts, customer pages, admin pages
  routes.php     every URL of the site
config/          config.php  (database, email, app settings)
database/        cinema_db.sql  (tables + demo data), generate-sql.js (rebuilds it)
lib/PHPMailer/   PHPMailer library
public/          index.php (entry point), assets (CSS, JS, images), uploads/posters
storage/logs/    mail.log
```

## How the requirements are covered

| Requirement | Where |
|---|---|
| FR01 Register + strong password | `AuthController::register`, `Validator::strongPassword`, live checklist in `app.js` |
| FR02 Login / logout, roles, Remember me | `Auth` (session + `remember_tokens` table; cookie holds a random token, never the password) |
| FR03–FR05 Movies, details, screenings | `MovieController`, `movies/index`, `movies/show` |
| FR06 Seat selection with Fetch API | `public/assets/js/seats.js` + `GET /api/showtimes/{id}/seats` (refreshes every 15 s) |
| FR07 Booking | `Booking::create` – one transaction; unpaid bookings hold seats for 10 minutes |
| FR08 Payment (simulated) | `booking/payment`, card checked with the Luhn rule, nothing stored |
| FR09 Confirmation + email | `booking/confirmation`, `Mailer` |
| FR10 My bookings | `AccountController::bookings` (upcoming / past) |
| FR11 Cancellation (≥ 2 h before) | `Booking::canCancel`, `Booking::cancel` deletes seat rows so seats are free again |
| FR12–FR17 Admin management | `app/Controllers/Admin/*` |
| FR15 No overlapping screenings | `Showtime::findConflict` |
| FR18 Search & filters | `Movie::search` |
| FR19 Dashboard | `DashboardController` |
| No double booking | `UNIQUE (showtime_id, seat_id)` on `booking_seats` |
| SQL injection | PDO prepared statements everywhere (`app/Core/Model.php`) |
| Passwords | `password_hash` / `password_verify` |
| XSS | every output goes through `e()` = `htmlspecialchars` |
| CSRF | token in every form, checked on every POST (`Csrf`, `Router`) |
| Admin pages | `admin` middleware in `routes.php` → `Auth::requireAdmin` |
