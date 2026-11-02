<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Mailer;
use App\Core\Validator;
use App\Models\Booking;
use App\Models\Seat;
use App\Models\SeatTakenException;
use App\Models\Showtime;

class BookingController extends Controller
{
    /** Seat selection page (FR06). The seat map itself loads through seatMap() with fetch(). */
    public function seats(int $showtimeId): void
    {
        $show = $this->bookableShowtime($showtimeId);
        $this->view('booking/seats', [
            'pageTitle' => 'Choose seats · ' . $show['title'],
            'show'      => $show,
        ]);
    }

    /** JSON seat map used by assets/js/seats.js. */
    public function seatMap(int $showtimeId): void
    {
        Booking::releaseExpiredHolds();
        $show = Showtime::find($showtimeId);
        if (!$show) {
            $this->json(['error' => 'Screening not found.'], 404);
            return;
        }
        $seats = Seat::mapForShowtime($showtimeId, (int) $show['hall_id'], (float) $show['ticket_price']);
        $this->json([
            'showtime_id' => $showtimeId,
            'started'     => Showtime::hasStarted($show),
            'currency'    => config('app.currency'),
            'available'   => count(array_filter($seats, fn ($s) => !$s['booked'])),
            'seats'       => $seats,
        ]);
    }

    /** FR07: create a Pending booking for the chosen seats. */
    public function store(): void
    {
        $showtimeId = (int) ($_POST['showtime_id'] ?? 0);
        $seatIds = array_filter(array_map('intval', explode(',', (string) ($_POST['seat_ids'] ?? ''))));
        $show = $this->bookableShowtime($showtimeId);

        if (!$seatIds) {
            flash('error', 'Please choose at least one seat.');
            redirect("/showtimes/$showtimeId/seats");
        }
        if (count($seatIds) > 10) {
            flash('error', 'You can book up to 10 seats at a time.');
            redirect("/showtimes/$showtimeId/seats");
        }

        Booking::releaseExpiredHolds();
        try {
            $bookingId = Booking::create(Auth::id(), $show, $seatIds);
        } catch (SeatTakenException $e) {
            flash('error', $e->getMessage() . ' The seat map has been refreshed, please choose again.');
            redirect("/showtimes/$showtimeId/seats");
        } catch (\InvalidArgumentException $e) {
            flash('error', $e->getMessage());
            redirect("/showtimes/$showtimeId/seats");
        }
        redirect("/bookings/$bookingId/pay");
    }

    /** FR08: review the booking and pay (simulated). */
    public function payment(int $id): void
    {
        $booking = $this->ownBooking($id);
        if ($booking['payment_status'] === 'Paid') {
            redirect("/bookings/$id");
        }
        if ($booking['status'] === 'Cancelled') {
            flash('error', 'This booking was cancelled because it was not paid in time. Please choose your seats again.');
            redirect('/showtimes/' . $booking['showtime_id'] . '/seats');
        }
        $expiresAt = strtotime($booking['booking_date']) + 60 * (int) config('app.hold_minutes');
        $this->view('booking/payment', [
            'pageTitle' => 'Payment',
            'booking'   => $booking,
            'seats'     => Booking::seats($id),
            'secondsLeft' => max(0, $expiresAt - time()),
        ]);
    }

    public function pay(int $id): void
    {
        Booking::releaseExpiredHolds();
        $booking = $this->ownBooking($id);
        if ($booking['status'] !== 'Pending' || $booking['payment_status'] !== 'Unpaid') {
            flash('error', $booking['status'] === 'Cancelled'
                ? 'Your seat hold expired before payment. Please choose your seats again.'
                : 'This booking is already paid.');
            redirect($booking['status'] === 'Cancelled' ? '/showtimes/' . $booking['showtime_id'] . '/seats' : "/bookings/$id");
        }

        // Simulated card payment: check the form, nothing is charged and card data is not stored.
        $v = new Validator($_POST);
        $v->required('card_name', 'Name on card')->max('card_name', 100)
          ->required('card_number', 'Card number')
          ->required('expiry', 'Expiry date')
          ->required('cvv', 'CVV');
        $number = preg_replace('/\D/', '', $v->get('card_number'));
        if ($number !== '' && !self::luhn($number)) {
            $v->addError('card_number', 'This card number is not valid.');
        }
        if ($v->get('expiry') !== '') {
            if (!preg_match('/^(0[1-9]|1[0-2])\/(\d{2})$/', $v->get('expiry'), $m)) {
                $v->addError('expiry', 'Use the format MM/YY.');
            } elseif (mktime(0, 0, 0, (int) $m[1] + 1, 1, 2000 + (int) $m[2]) <= time()) {
                $v->addError('expiry', 'This card has expired.');
            }
        }
        if ($v->get('cvv') !== '' && !preg_match('/^\d{3,4}$/', $v->get('cvv'))) {
            $v->addError('cvv', 'CVV is 3 or 4 digits.');
        }
        if ($v->fails()) {
            $this->backWithErrors("/bookings/$id/pay", $v->errors(), $_POST);
        }

        Booking::markPaid($id);
        $booking = Booking::find($id);
        $this->sendConfirmationEmail($booking);
        flash('success', 'Payment successful! Your booking is confirmed.');
        redirect("/bookings/$id");
    }

    /** FR09: booking confirmation / ticket. */
    public function show(int $id): void
    {
        $booking = $this->ownBooking($id);
        $this->view('booking/confirmation', [
            'pageTitle' => 'Booking #' . $id,
            'booking'   => $booking,
            'seats'     => Booking::seats($id),
            'canCancel' => Booking::canCancel($booking),
        ]);
    }

    /** FR11: cancel up to 2 hours before the screening; seats become free again. */
    public function cancel(int $id): void
    {
        $booking = $this->ownBooking($id);
        if (!Booking::canCancel($booking)) {
            flash('error', 'This booking can no longer be cancelled. Cancellation is allowed up to '
                . config('app.cancel_hours') . ' hours before the screening.');
            redirect("/bookings/$id");
        }
        Booking::cancel($id);
        $booking = self::afterCancel($booking);
        self::sendCancellationEmail($booking);
        flash('success', 'Booking #' . $id . ' was cancelled' . ($booking['payment_status'] === 'Refunded' ? ' and your payment will be refunded.' : '.'));
        redirect('/account/bookings');
    }

    // ---------------------------------------------------------------

    private function bookableShowtime(int $id): array
    {
        $show = Showtime::find($id) ?? abort(404);
        if (Showtime::hasStarted($show)) {
            flash('error', 'This screening has already started. Please choose another time.');
            redirect('/movies/' . $show['movie_id']);
        }
        return $show;
    }

    /** A booking that belongs to the logged-in user (admins may see any). */
    private function ownBooking(int $id): array
    {
        $booking = Booking::find($id) ?? abort(404);
        if ((int) $booking['user_id'] !== Auth::id() && !Auth::isAdmin()) {
            abort(403);
        }
        return $booking;
    }

    private static function luhn(string $digits): bool
    {
        if (strlen($digits) < 13 || strlen($digits) > 19) {
            return false;
        }
        $sum = 0;
        $alt = false;
        for ($i = strlen($digits) - 1; $i >= 0; $i--) {
            $n = (int) $digits[$i];
            if ($alt) {
                $n *= 2;
                if ($n > 9) {
                    $n -= 9;
                }
            }
            $sum += $n;
            $alt = !$alt;
        }
        return $sum % 10 === 0;
    }

    private function sendConfirmationEmail(array $b): void
    {
        $html = self::emailLayout('Your booking is confirmed', $b, '<p>Thank you for booking with ' . e(config('app.name'))
            . '. Please show this booking number at the entrance.</p>');
        Mailer::send($b['email'], $b['full_name'], 'Booking confirmed #' . $b['booking_id'] . ' - ' . $b['title'], $html);
    }

    /** Fresh booking row after cancelling, keeping the seat list so the email can still name the seats. */
    public static function afterCancel(array $before): array
    {
        $after = Booking::find((int) $before['booking_id']);
        $after['seat_list'] = $before['seat_list'];
        $after['ticket_count'] = $before['ticket_count'];
        return $after;
    }

    public static function sendCancellationEmail(array $b): void
    {
        $refund = $b['payment_status'] === 'Refunded' ? '<p>Your payment of ' . e(money($b['total_amount'])) . ' will be refunded.</p>' : '';
        $html = self::emailLayout('Your booking was cancelled', $b, '<p>The seats have been released.</p>' . $refund);
        Mailer::send($b['email'], $b['full_name'], 'Booking cancelled #' . $b['booking_id'] . ' - ' . $b['title'], $html);
    }

    private static function emailLayout(string $heading, array $b, string $extra): string
    {
        $rows = [
            'Booking number' => '#' . $b['booking_id'],
            'Movie'          => $b['title'],
            'Date'           => fmt_date($b['show_date']),
            'Time'           => fmt_time($b['start_time']),
            'Hall'           => $b['hall_name'],
            'Seats'          => $b['seat_list'] ?: '-',
            'Tickets'        => (string) $b['ticket_count'],
            'Total'          => money($b['total_amount']),
        ];
        $table = '';
        foreach ($rows as $k => $v) {
            $table .= '<tr><td style="padding:4px 12px 4px 0;color:#666">' . e($k) . '</td><td style="padding:4px 0"><b>' . e($v) . '</b></td></tr>';
        }
        return '<div style="font-family:Arial,sans-serif;max-width:520px">'
            . '<h2 style="color:#c0392b">' . e(config('app.name')) . '</h2>'
            . '<p>Hello ' . e($b['full_name']) . ',</p><h3>' . e($heading) . '</h3>'
            . '<table>' . $table . '</table>' . $extra . '</div>';
    }
}
