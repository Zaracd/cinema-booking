<?php
namespace App\Controllers\Admin;

use App\Controllers\BookingController;
use App\Core\Controller;
use App\Models\Booking;

/** FR16: view and monitor bookings and payment status. */
class BookingAdminController extends Controller
{
    public function index(): void
    {
        Booking::releaseExpiredHolds();
        $filters = [
            'q'       => trim((string) ($_GET['q'] ?? '')),
            'status'  => in_array($_GET['status'] ?? '', ['Pending', 'Confirmed', 'Cancelled'], true) ? $_GET['status'] : '',
            'payment' => in_array($_GET['payment'] ?? '', ['Unpaid', 'Paid', 'Refunded'], true) ? $_GET['payment'] : '',
            'date'    => preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['date'] ?? '') ? $_GET['date'] : '',
        ];
        $this->view('admin/bookings/index', [
            'pageTitle' => 'Bookings',
            'bookings'  => Booking::adminList($filters),
            'stats'     => Booking::stats(),
            'filters'   => $filters,
        ], 'admin');
    }

    public function show(int $id): void
    {
        $booking = Booking::find($id) ?? abort(404);
        $this->view('admin/bookings/show', [
            'pageTitle' => 'Booking #' . $id,
            'booking'   => $booking,
            'seats'     => Booking::seats($id),
        ], 'admin');
    }

    /** Admins can cancel any booking that is not cancelled yet (no 2-hour limit). */
    public function cancel(int $id): void
    {
        $booking = Booking::find($id) ?? abort(404);
        if ($booking['status'] === 'Cancelled') {
            flash('error', 'This booking is already cancelled.');
        } else {
            Booking::cancel($id);
            BookingController::sendCancellationEmail(BookingController::afterCancel($booking));
            flash('success', "Booking #$id was cancelled and its seats released.");
        }
        redirect("/admin/bookings/$id");
    }
}
