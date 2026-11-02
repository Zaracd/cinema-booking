<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Movie;
use App\Models\Genre;
use App\Models\Showtime;

class MovieController extends Controller
{
    /** Movie list with search and filters (FR03, FR18). */
    public function index(): void
    {
        $filters = [
            'q'      => trim((string) ($_GET['q'] ?? '')),
            'genre'  => (int) ($_GET['genre'] ?? 0) ?: '',
            'status' => in_array($_GET['status'] ?? '', Movie::STATUSES, true) ? $_GET['status'] : '',
            'date'   => preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['date'] ?? '') ? $_GET['date'] : '',
        ];
        $this->view('movies/index', [
            'pageTitle' => 'Movies',
            'movies'    => Movie::search($filters),
            'genres'    => Genre::allGenres(),
            'filters'   => $filters,
        ]);
    }

    /** Movie details and its upcoming screenings (FR04, FR05). */
    public function show(int $id): void
    {
        $movie = Movie::find($id) ?? abort(404);
        $showtimes = Showtime::upcomingForMovie($id);
        $byDate = [];
        foreach ($showtimes as $s) {
            $byDate[$s['show_date']][] = $s;
        }
        $this->view('movies/show', [
            'pageTitle' => $movie['title'],
            'movie'     => $movie,
            'byDate'    => $byDate,
        ]);
    }
}
