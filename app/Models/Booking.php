<?php
namespace App\Models;

use App\Core\Model;
use PDOException;

class Booking extends Model
{
    private const DETAIL_SQL = "SELECT b.*, u.full_name, u.email, u.phone,
            st.show_date, st.start_time, st.end_time, st.ticket_price, st.hall_id,
            m.movie_id, m.title, m.poster, m.duration, h.hall_name,
            (SELECT GROUP_CONCAT(CONCAT(s.row_label, s.seat_number) ORDER BY s.row_label, s.seat_number SEPARATOR ', ')
               FROM booking_seats bs JOIN seats s ON s.seat_id = bs.seat_id WHERE bs.booking_id = b.booking_id) AS seat_list,
            (SELECT COUNT(*) FROM booking_seats bs WHERE bs.booking_id = b.booking_id) AS ticket_count
        FROM bookings b
        JOIN users u ON u.user_id = b.user_id
        JOIN showtimes st ON st.showtime_id = b.showtime_id
        JOIN movies m ON m.movie_id = st.movie_id
        JOIN halls h ON h.hall_id = st.hall_id";

    /**
     * Unpaid bookings hold their seats for a few minutes only. After that they are
     * cancelled and the seats are freed for other customers.
     */
    public static function releaseExpiredHolds(): void
    {
        $limit = date('Y-m-d H:i:s', time() - 60 * (int) config('app.hold_minutes'));
        $ids = array_column(self::all("SELECT booking_id FROM bookings
            WHERE status = 'Pending' AND payment_status = 'Unpaid' AND booking_date < ?", [$limit]), 'booking_id');
        foreach ($ids as $id) {
            self::run('DELETE FROM booking_seats WHERE booking_id = ?', [$id]);
            self::run("UPDATE bookings SET status = 'Cancelled' WHERE booking_id = ?", [$id]);
        }
    }

    /**
     * Creates a Pending booking and reserves the seats in one transaction.
     * Returns the booking id, or throws SeatTakenException if any seat was just taken.
     */
    public static function create(int $userId, array $showtime, array $seatIds): int
    {
        $db = self::db();
        $seatIds = array_values(array_unique(array_map('intval', $seatIds)));
        $place = implode(',', array_fill(0, count($seatIds), '?'));
        $seats = self::all("SELECT seat_id, row_label, seat_number, seat_type FROM seats WHERE hall_id = ? AND seat_id IN ($place)",
            array_merge([(int) $showtime['hall_id']], $seatIds));
        if (count($seats) !== count($seatIds)) {
            throw new \InvalidArgumentException('One or more seats do not belong to this hall.');
        }

        $base = (float) $showtime['ticket_price'];
        $total = 0;
        foreach ($seats as &$s) {
            $s['price'] = Seat::price($base, $s['seat_type']);
            $total += $s['price'];
        }
        unset($s);

        $db->beginTransaction();
        try {
            $bookingId = self::insert("INSERT INTO bookings (user_id, showtime_id, booking_date, total_amount, status, payment_status)
                VALUES (?, ?, ?, ?, 'Pending', 'Unpaid')", [$userId, $showtime['showtime_id'], date('Y-m-d H:i:s'), round($total, 2)]);
            $stmt = $db->prepare('INSERT INTO booking_seats (booking_id, showtime_id, seat_id, price) VALUES (?, ?, ?, ?)');
            foreach ($seats as $s) {
                $stmt->execute([$bookingId, $showtime['showtime_id'], $s['seat_id'], $s['price']]);
            }
            $db->commit();
            return $bookingId;
        } catch (PDOException $e) {
            $db->rollBack();
            // 1062 = duplicate key: the UNIQUE (showtime_id, seat_id) rule rejected a seat.
            if (($e->errorInfo[1] ?? 0) == 1062) {
                throw new SeatTakenException('Sorry, one or more of those seats were just booked by someone else.');
            }
            throw $e;
        }
    }

    public static function find(int $id): ?array
    {
        return self::one(self::DETAIL_SQL . ' WHERE b.booking_id = ?', [$id]);
    }

    public static function seats(int $id): array
    {
        return self::all('SELECT s.row_label, s.seat_number, s.seat_type, bs.price FROM booking_seats bs
            JOIN seats s ON s.seat_id = bs.seat_id WHERE bs.booking_id = ? ORDER BY s.row_label, s.seat_number', [$id]);
    }

    public static function forUser(int $userId): array
    {
        return self::all(self::DETAIL_SQL . ' WHERE b.user_id = ? ORDER BY st.show_date DESC, st.start_time DESC', [$userId]);
    }

    public static function adminList(array $f = []): array
    {
        $where = [];
        $params = [];
        foreach (['status' => 'b.status', 'payment' => 'b.payment_status'] as $key => $col) {
            if (!empty($f[$key])) {
                $where[] = "$col = ?";
                $params[] = $f[$key];
            }
        }
        if (!empty($f['date'])) {
            $where[] = 'st.show_date = ?';
            $params[] = $f['date'];
        }
        if (!empty($f['q'])) {
            $where[] = '(u.full_name LIKE ? OR u.email LIKE ? OR m.title LIKE ? OR b.booking_id = ?)';
            array_push($params, "%{$f['q']}%", "%{$f['q']}%", "%{$f['q']}%", (int) ltrim($f['q'], '#'));
        }
        $sql = self::DETAIL_SQL . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY b.booking_date DESC LIMIT 300';
        return self::all($sql, $params);
    }

    public static function recent(int $limit = 6): array
    {
        return self::all(self::DETAIL_SQL . ' ORDER BY b.booking_date DESC LIMIT ' . (int) $limit);
    }

    /** Simulated payment succeeded: confirm the booking. */
    public static function markPaid(int $id): bool
    {
        return self::run("UPDATE bookings SET status = 'Confirmed', payment_status = 'Paid'
            WHERE booking_id = ? AND status = 'Pending' AND payment_status = 'Unpaid'", [$id])->rowCount() === 1;
    }

    /**
     * Cancels a booking. Its seat rows are deleted so the seats are free again,
     * while the booking itself stays with status Cancelled.
     */
    public static function cancel(int $id): void
    {
        $db = self::db();
        $db->beginTransaction();
        try {
            self::run('DELETE FROM booking_seats WHERE booking_id = ?', [$id]);
            self::run("UPDATE bookings SET status = 'Cancelled',
                payment_status = IF(payment_status = 'Paid', 'Refunded', payment_status) WHERE booking_id = ?", [$id]);
            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /** True when the booking may still be cancelled by the customer (FR11). */
    public static function canCancel(array $b): bool
    {
        if ($b['status'] === 'Cancelled') {
            return false;
        }
        $start = strtotime($b['show_date'] . ' ' . $b['start_time']);
        return $start - time() >= 3600 * (int) config('app.cancel_hours');
    }

    public static function stats(): array
    {
        return self::one("SELECT COUNT(*) AS total,
                SUM(status = 'Confirmed') AS confirmed,
                SUM(status = 'Pending') AS pending,
                SUM(status = 'Cancelled') AS cancelled,
                COALESCE(SUM(CASE WHEN payment_status = 'Paid' THEN total_amount END), 0) AS revenue,
                (SELECT COUNT(*) FROM booking_seats) AS tickets
            FROM bookings");
    }

    /** Paid revenue per day for the last 7 days. */
    public static function revenueLastDays(int $days = 7): array
    {
        $rows = self::all("SELECT DATE(booking_date) AS d, SUM(total_amount) AS amount FROM bookings
            WHERE payment_status = 'Paid' AND booking_date >= ? GROUP BY DATE(booking_date)",
            [date('Y-m-d', strtotime('-' . ($days - 1) . ' days'))]);
        $byDay = array_column($rows, 'amount', 'd');
        $out = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-$i days"));
            $out[] = ['date' => $d, 'amount' => (float) ($byDay[$d] ?? 0)];
        }
        return $out;
    }
}
