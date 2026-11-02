<?php
namespace App\Models;

use App\Core\Model;

class Seat extends Model
{
    public const TYPES = ['Standard', 'VIP', 'Accessible'];

    public static function forHall(int $hallId): array
    {
        return self::all('SELECT * FROM seats WHERE hall_id = ? ORDER BY LENGTH(row_label), row_label, seat_number', [$hallId]);
    }

    public static function find(int $id): ?array
    {
        return self::one('SELECT * FROM seats WHERE seat_id = ?', [$id]);
    }

    public static function exists(int $hallId, string $row, int $number): bool
    {
        return (bool) self::value('SELECT COUNT(*) FROM seats WHERE hall_id = ? AND row_label = ? AND seat_number = ?', [$hallId, $row, $number]);
    }

    public static function create(int $hallId, string $row, int $number, string $type): void
    {
        self::run('INSERT INTO seats (hall_id, row_label, seat_number, seat_type) VALUES (?, ?, ?, ?)', [$hallId, $row, $number, $type]);
        Hall::refreshCapacity($hallId);
    }

    public static function setType(int $id, string $type): void
    {
        self::run('UPDATE seats SET seat_type = ? WHERE seat_id = ?', [$type, $id]);
    }

    public static function everBooked(int $id): bool
    {
        return (bool) self::value('SELECT COUNT(*) FROM booking_seats WHERE seat_id = ?', [$id]);
    }

    public static function delete(int $id, int $hallId): void
    {
        self::run('DELETE FROM seats WHERE seat_id = ?', [$id]);
        Hall::refreshCapacity($hallId);
    }

    /**
     * Seat map for one screening. Status is worked out from booking_seats
     * for this screening, because the same seat can be free in one show and taken in another.
     */
    public static function mapForShowtime(int $showtimeId, int $hallId, float $basePrice): array
    {
        $rows = self::all('SELECT s.seat_id, s.row_label, s.seat_number, s.seat_type,
                                  (bs.booking_seat_id IS NOT NULL) AS booked
                           FROM seats s
                           LEFT JOIN booking_seats bs ON bs.seat_id = s.seat_id AND bs.showtime_id = ?
                           WHERE s.hall_id = ?
                           ORDER BY LENGTH(s.row_label), s.row_label, s.seat_number', [$showtimeId, $hallId]);
        foreach ($rows as &$r) {
            $r['seat_id'] = (int) $r['seat_id'];
            $r['seat_number'] = (int) $r['seat_number'];
            $r['booked'] = (bool) $r['booked'];
            $r['price'] = self::price($basePrice, $r['seat_type']);
        }
        return $rows;
    }

    public static function price(float $basePrice, string $type): float
    {
        return round($type === 'VIP' ? $basePrice * (float) config('app.vip_multiplier') : $basePrice, 2);
    }
}
