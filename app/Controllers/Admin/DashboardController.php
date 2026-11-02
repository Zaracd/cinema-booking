<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\Booking;
use App\Models\Hall;
use App\Models\Movie;
use App\Models\Showtime;
use App\Models\User;

/** FR19: counts of users, movies, halls, screenings and bookings. */
class DashboardController extends Controller
{
    public function index(): void
    {
        Booking::releaseExpiredHolds();
        $this->view('admin/dashboard', [
            'pageTitle' => 'Dashboard',
            'counts' => [
                'users'      => User::count(),
                'movies'     => Movie::count(),
                'halls'      => Hall::count(),
                'showtimes'  => Showtime::count(),
                'upcoming'   => Showtime::countUpcoming(),
            ],
            'stats'   => Booking::stats(),
            'revenue' => Booking::revenueLastDays(7),
            'recent'  => Booking::recent(8),
            'today'   => Showtime::adminList(['date' => date('Y-m-d')]),
        ], 'admin');
    }
}
