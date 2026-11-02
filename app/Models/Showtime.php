<?php
namespace App\Models;

use App\Core\Model;

class Showtime extends Model
{
    private const BASE_SQL = 'SELECT st.*, m.title, m.poster, m.duration, m.status AS movie_status, h.hall_name, h.capacity,
            (SELECT COUNT(*) FROM booking_seats bs WHERE bs.showtime_id = st.showtime_id) AS booked_count
        FROM showtimes st
        JOIN movies m ON m.movie_id = st.movie_id
        JOIN halls h ON h.hall_id = st.hall_id';

    public static function find(int $id): ?array
    {
        return self::one(self::BASE_SQL . ' WHERE st.showtime_id = ?', [$id]);
    }

    /** Upcoming screenings of one movie (start time still in the future). */
    public static function upcomingForMovie(int $movieId): array
    {
        return self::all(self::BASE_SQL . ' WHERE st.movie_id = ? AND TIMESTAMP(st.show_date, st.start_time) > ?
            ORDER BY st.show_date, st.start_time', [$movieId, date('Y-m-d H:i:s')]);
    }

    public static function adminList(array $f = []): array
    {
        $where = [];
        $params = [];
        if (!empty($f['date'])) {
            $where[] = 'st.show_date = ?';
            $params[] = $f['date'];
        } elseif (empty($f['past'])) {
            $where[] = 'st.show_date >= CURDATE()';
        }
        if (!empty($f['movie'])) {
            $where[] = 'st.movie_id = ?';
            $params[] = (int) $f['movie'];
        }
        if (!empty($f['hall'])) {
            $where[] = 'st.hall_id = ?';
            $params[] = (int) $f['hall'];
        }
        $sql = self::BASE_SQL . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY st.show_date, st.start_time, h.hall_name';
        return self::all($sql, $params);
    }

    /** Another screening in the same hall that overlaps the given time range (FR15). */
    public static function findConflict(int $hallId, string $date, string $start, string $end, int $exceptId = 0): ?array
    {
        return self::one('SELECT st.*, m.title FROM showtimes st JOIN movies m ON m.movie_id = st.movie_id
            WHERE st.hall_id = ? AND st.show_date = ? AND st.showtime_id <> ?
              AND st.start_time < ? AND st.end_time > ?
            LIMIT 1', [$hallId, $date, $exceptId, $end, $start]);
    }

    public static function create(array $d): int
    {
        return self::insert('INSERT INTO showtimes (movie_id, hall_id, show_date, start_time, end_time, ticket_price) VALUES (?, ?, ?, ?, ?, ?)',
            [$d['movie_id'], $d['hall_id'], $d['show_date'], $d['start_time'], $d['end_time'], $d['ticket_price']]);
    }

    public static function update(int $id, array $d): void
    {
        self::run('UPDATE showtimes SET movie_id = ?, hall_id = ?, show_date = ?, start_time = ?, end_time = ?, ticket_price = ? WHERE showtime_id = ?',
            [$d['movie_id'], $d['hall_id'], $d['show_date'], $d['start_time'], $d['end_time'], $d['ticket_price'], $id]);
    }

    public static function hasBookings(int $id): bool
    {
        return (bool) self::value('SELECT COUNT(*) FROM bookings WHERE showtime_id = ?', [$id]);
    }

    public static function delete(int $id): void
    {
        self::run('DELETE FROM showtimes WHERE showtime_id = ?', [$id]);
    }

    public static function count(): int
    {
        return (int) self::value('SELECT COUNT(*) FROM showtimes');
    }

    public static function countUpcoming(): int
    {
        return (int) self::value('SELECT COUNT(*) FROM showtimes WHERE TIMESTAMP(show_date, start_time) > ?', [date('Y-m-d H:i:s')]);
    }

    public static function hasStarted(array $st): bool
    {
        return strtotime($st['show_date'] . ' ' . $st['start_time']) <= time();
    }
}
