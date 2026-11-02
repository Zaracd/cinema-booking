<?php
namespace App\Models;

use App\Core\Model;

class Genre extends Model
{
    public static function allGenres(): array
    {
        return self::all('SELECT * FROM genres ORDER BY genre_name');
    }

    public static function forMovie(int $movieId): array
    {
        return self::all('SELECT g.* FROM genres g JOIN movie_genres mg ON mg.genre_id = g.genre_id
                          WHERE mg.movie_id = ? ORDER BY g.genre_name', [$movieId]);
    }

    public static function create(string $name): void
    {
        self::run('INSERT IGNORE INTO genres (genre_name) VALUES (?)', [$name]);
    }
}
