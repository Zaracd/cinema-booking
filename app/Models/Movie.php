<?php
namespace App\Models;

use App\Core\Model;

class Movie extends Model
{
    public const STATUSES = ['Now Showing', 'Coming Soon', 'Ended'];

    private const LIST_SQL = "SELECT m.*, GROUP_CONCAT(g.genre_name ORDER BY g.genre_name SEPARATOR ', ') AS genres
        FROM movies m
        LEFT JOIN movie_genres mg ON mg.movie_id = m.movie_id
        LEFT JOIN genres g ON g.genre_id = mg.genre_id";

    /** Search by title and filter by genre, status and screening date (FR18). */
    public static function search(array $f = []): array
    {
        $where = [];
        $params = [];
        if (!empty($f['q'])) {
            $where[] = '(m.title LIKE ? OR m.original_title LIKE ? OR m.director LIKE ? OR m.starring LIKE ?)';
            array_push($params, ...array_fill(0, 4, '%' . $f['q'] . '%'));
        }
        if (!empty($f['status'])) {
            $where[] = 'm.status = ?';
            $params[] = $f['status'];
        }
        if (!empty($f['genre'])) {
            $where[] = 'm.movie_id IN (SELECT movie_id FROM movie_genres WHERE genre_id = ?)';
            $params[] = (int) $f['genre'];
        }
        if (!empty($f['date'])) {
            $where[] = 'm.movie_id IN (SELECT movie_id FROM showtimes WHERE show_date = ?)';
            $params[] = $f['date'];
        }
        $sql = self::LIST_SQL . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
            . " GROUP BY m.movie_id ORDER BY FIELD(m.status, 'Now Showing', 'Coming Soon', 'Ended'), m.release_date DESC";
        return self::all($sql, $params);
    }

    public static function byStatus(string $status, int $limit = 12): array
    {
        return self::all(self::LIST_SQL . ' WHERE m.status = ? GROUP BY m.movie_id ORDER BY m.release_date DESC LIMIT ' . (int) $limit, [$status]);
    }

    public static function find(int $id): ?array
    {
        return self::one(self::LIST_SQL . ' WHERE m.movie_id = ? GROUP BY m.movie_id', [$id]);
    }

    public static function genreIds(int $id): array
    {
        return array_map('intval', array_column(self::all('SELECT genre_id FROM movie_genres WHERE movie_id = ?', [$id]), 'genre_id'));
    }

    public static function options(): array
    {
        return self::all("SELECT movie_id, title, duration, status FROM movies WHERE status <> 'Ended' ORDER BY title");
    }

    public static function create(array $d, array $genreIds): int
    {
        $id = self::insert('INSERT INTO movies (title, original_title, language, description, duration, release_date, director, starring, rating, poster, status)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$d['title'], $d['original_title'], $d['language'], $d['description'], $d['duration'], $d['release_date'], $d['director'], $d['starring'], $d['rating'], $d['poster'], $d['status']]);
        self::syncGenres($id, $genreIds);
        return $id;
    }

    public static function update(int $id, array $d, array $genreIds): void
    {
        self::run('UPDATE movies SET title = ?, original_title = ?, language = ?, description = ?, duration = ?, release_date = ?, director = ?, starring = ?, rating = ?, poster = ?, status = ?
                   WHERE movie_id = ?',
            [$d['title'], $d['original_title'], $d['language'], $d['description'], $d['duration'], $d['release_date'], $d['director'], $d['starring'], $d['rating'], $d['poster'], $d['status'], $id]);
        self::syncGenres($id, $genreIds);
    }

    public static function setStatus(int $id, string $status): void
    {
        self::run('UPDATE movies SET status = ? WHERE movie_id = ?', [$status, $id]);
    }

    public static function hasShowtimes(int $id): bool
    {
        return (bool) self::value('SELECT COUNT(*) FROM showtimes WHERE movie_id = ?', [$id]);
    }

    public static function delete(int $id): void
    {
        self::run('DELETE FROM movies WHERE movie_id = ?', [$id]);
    }

    public static function count(): int
    {
        return (int) self::value('SELECT COUNT(*) FROM movies');
    }

    private static function syncGenres(int $id, array $genreIds): void
    {
        self::run('DELETE FROM movie_genres WHERE movie_id = ?', [$id]);
        foreach (array_unique(array_map('intval', $genreIds)) as $gid) {
            self::run('INSERT IGNORE INTO movie_genres (movie_id, genre_id)
                       SELECT ?, genre_id FROM genres WHERE genre_id = ?', [$id, $gid]);
        }
    }
}
