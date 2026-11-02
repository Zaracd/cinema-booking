<?php
namespace App\Models;

use App\Core\Model;

class Hall extends Model
{
    public static function allHalls(): array
    {
        return self::all('SELECT h.*,
                (SELECT COUNT(*) FROM seats s WHERE s.hall_id = h.hall_id) AS seat_count,
                (SELECT COUNT(*) FROM seats s WHERE s.hall_id = h.hall_id AND s.seat_type = \'VIP\') AS vip_count,
                (SELECT COUNT(*) FROM showtimes st WHERE st.hall_id = h.hall_id AND st.show_date >= CURDATE()) AS upcoming_shows
            FROM halls h ORDER BY h.hall_name');
    }

    public static function find(int $id): ?array
    {
        return self::one('SELECT * FROM halls WHERE hall_id = ?', [$id]);
    }

    public static function nameExists(string $name, int $exceptId = 0): bool
    {
        return (bool) self::value('SELECT COUNT(*) FROM halls WHERE hall_name = ? AND hall_id <> ?', [$name, $exceptId]);
    }

    /** Creates a hall and its seats in a grid. Rows listed in $vipRows get VIP seats. */
    public static function createWithSeats(string $name, int $rows, int $perRow, array $vipRows): int
    {
        $db = self::db();
        $db->beginTransaction();
        try {
            $id = self::insert('INSERT INTO halls (hall_name, capacity) VALUES (?, 0)', [$name]);
            $stmt = $db->prepare('INSERT INTO seats (hall_id, row_label, seat_number, seat_type) VALUES (?, ?, ?, ?)');
            for ($r = 0; $r < $rows; $r++) {
                $label = chr(65 + $r);
                for ($n = 1; $n <= $perRow; $n++) {
                    $stmt->execute([$id, $label, $n, in_array($label, $vipRows, true) ? 'VIP' : 'Standard']);
                }
            }
            self::refreshCapacity($id);
            $db->commit();
            return $id;
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    public static function rename(int $id, string $name): void
    {
        self::run('UPDATE halls SET hall_name = ? WHERE hall_id = ?', [$name, $id]);
    }

    public static function hasShowtimes(int $id): bool
    {
        return (bool) self::value('SELECT COUNT(*) FROM showtimes WHERE hall_id = ?', [$id]);
    }

    public static function delete(int $id): void
    {
        self::run('DELETE FROM halls WHERE hall_id = ?', [$id]);
    }

    /** Capacity always equals the number of seats in the hall. */
    public static function refreshCapacity(int $id): void
    {
        self::run('UPDATE halls SET capacity = (SELECT COUNT(*) FROM seats WHERE hall_id = ?) WHERE hall_id = ?', [$id, $id]);
    }

    public static function count(): int
    {
        return (int) self::value('SELECT COUNT(*) FROM halls');
    }
}
