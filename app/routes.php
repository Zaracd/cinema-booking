<?php
/* All site addresses. Middleware: 'guest', 'auth' (logged in), 'admin'. */

use App\Controllers\HomeController;
use App\Controllers\MovieController;
use App\Controllers\AuthController;
use App\Controllers\BookingController;
use App\Controllers\AccountController;
use App\Controllers\Admin\DashboardController;
use App\Controllers\Admin\MovieAdminController;
use App\Controllers\Admin\HallAdminController;
use App\Controllers\Admin\ShowtimeAdminController;
use App\Controllers\Admin\BookingAdminController;
use App\Controllers\Admin\UserAdminController;

/** @var App\Core\Router $router */

// Public pages
$router->get('/', [HomeController::class, 'index']);
$router->get('/movies', [MovieController::class, 'index']);
$router->get('/movies/{id}', [MovieController::class, 'show']);

// Accounts
$router->get('/register', [AuthController::class, 'showRegister'], ['guest']);
$router->post('/register', [AuthController::class, 'register'], ['guest']);
$router->get('/login', [AuthController::class, 'showLogin'], ['guest']);
$router->post('/login', [AuthController::class, 'login'], ['guest']);
$router->post('/logout', [AuthController::class, 'logout']);

// Booking flow
$router->get('/showtimes/{id}/seats', [BookingController::class, 'seats'], ['auth']);
$router->get('/api/showtimes/{id}/seats', [BookingController::class, 'seatMap']);   // JSON for the Fetch API
$router->post('/bookings', [BookingController::class, 'store'], ['auth']);
$router->get('/bookings/{id}/pay', [BookingController::class, 'payment'], ['auth']);
$router->post('/bookings/{id}/pay', [BookingController::class, 'pay'], ['auth']);
$router->get('/bookings/{id}', [BookingController::class, 'show'], ['auth']);
$router->post('/bookings/{id}/cancel', [BookingController::class, 'cancel'], ['auth']);

// My account
$router->get('/account/bookings', [AccountController::class, 'bookings'], ['auth']);
$router->get('/account/profile', [AccountController::class, 'profile'], ['auth']);
$router->post('/account/profile', [AccountController::class, 'updateProfile'], ['auth']);
$router->post('/account/password', [AccountController::class, 'changePassword'], ['auth']);

// Admin dashboard
$router->get('/admin', [DashboardController::class, 'index'], ['admin']);

$router->get('/admin/movies', [MovieAdminController::class, 'index'], ['admin']);
$router->get('/admin/movies/create', [MovieAdminController::class, 'create'], ['admin']);
$router->post('/admin/movies', [MovieAdminController::class, 'store'], ['admin']);
$router->get('/admin/movies/{id}/edit', [MovieAdminController::class, 'edit'], ['admin']);
$router->post('/admin/movies/{id}', [MovieAdminController::class, 'update'], ['admin']);
$router->post('/admin/movies/{id}/status', [MovieAdminController::class, 'status'], ['admin']);
$router->post('/admin/movies/{id}/delete', [MovieAdminController::class, 'destroy'], ['admin']);
$router->post('/admin/genres', [MovieAdminController::class, 'addGenre'], ['admin']);

$router->get('/admin/halls', [HallAdminController::class, 'index'], ['admin']);
$router->post('/admin/halls', [HallAdminController::class, 'store'], ['admin']);
$router->get('/admin/halls/{id}', [HallAdminController::class, 'show'], ['admin']);
$router->post('/admin/halls/{id}', [HallAdminController::class, 'update'], ['admin']);
$router->post('/admin/halls/{id}/delete', [HallAdminController::class, 'destroy'], ['admin']);
$router->post('/admin/halls/{id}/seats', [HallAdminController::class, 'addSeat'], ['admin']);
$router->post('/admin/seats/{id}/type', [HallAdminController::class, 'seatType'], ['admin']);
$router->post('/admin/seats/{id}/delete', [HallAdminController::class, 'deleteSeat'], ['admin']);

$router->get('/admin/showtimes', [ShowtimeAdminController::class, 'index'], ['admin']);
$router->get('/admin/showtimes/create', [ShowtimeAdminController::class, 'create'], ['admin']);
$router->post('/admin/showtimes', [ShowtimeAdminController::class, 'store'], ['admin']);
$router->get('/admin/showtimes/{id}/edit', [ShowtimeAdminController::class, 'edit'], ['admin']);
$router->post('/admin/showtimes/{id}', [ShowtimeAdminController::class, 'update'], ['admin']);
$router->post('/admin/showtimes/{id}/delete', [ShowtimeAdminController::class, 'destroy'], ['admin']);

$router->get('/admin/bookings', [BookingAdminController::class, 'index'], ['admin']);
$router->get('/admin/bookings/{id}', [BookingAdminController::class, 'show'], ['admin']);
$router->post('/admin/bookings/{id}/cancel', [BookingAdminController::class, 'cancel'], ['admin']);

$router->get('/admin/users', [UserAdminController::class, 'index'], ['admin']);
$router->post('/admin/users/{id}/role', [UserAdminController::class, 'role'], ['admin']);
$router->post('/admin/users/{id}/delete', [UserAdminController::class, 'destroy'], ['admin']);
