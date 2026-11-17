<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Validator;
use App\Models\Genre;
use App\Models\Movie;

/** FR12: add, edit, delete, view and change the status of movies. */
class MovieAdminController extends Controller
{
    private const MAX_UPLOAD = 2 * 1024 * 1024; // 2 MB

    public function index(): void
    {
        $filters = [
            'q'      => trim((string) ($_GET['q'] ?? '')),
            'status' => in_array($_GET['status'] ?? '', Movie::STATUSES, true) ? $_GET['status'] : '',
            'genre'  => (int) ($_GET['genre'] ?? 0) ?: '',
        ];
        $this->view('admin/movies/index', [
            'pageTitle' => 'Movies',
            'movies'    => Movie::search($filters),
            'genres'    => Genre::allGenres(),
            'filters'   => $filters,
        ], 'admin');
    }

    public function create(): void
    {
        $this->view('admin/movies/form', [
            'pageTitle' => 'Add movie',
            'movie'     => null,
            'genres'    => Genre::allGenres(),
            'selected'  => array_map('intval', old('genres', [])),
        ], 'admin');
    }

    public function store(): void
    {
        [$data, $genreIds] = $this->validated(null, '/admin/movies/create');
        $id = Movie::create($data, $genreIds);
        flash('success', 'Movie "' . $data['title'] . '" was added.');
        redirect('/admin/movies');
    }

    public function edit(int $id): void
    {
        $movie = Movie::find($id) ?? abort(404);
        $this->view('admin/movies/form', [
            'pageTitle' => 'Edit movie',
            'movie'     => $movie,
            'genres'    => Genre::allGenres(),
            'selected'  => array_map('intval', old('genres', Movie::genreIds($id))),
        ], 'admin');
    }

    public function update(int $id): void
    {
        $movie = Movie::find($id) ?? abort(404);
        [$data, $genreIds] = $this->validated($movie, "/admin/movies/$id/edit");
        Movie::update($id, $data, $genreIds);
        flash('success', 'Movie "' . $data['title'] . '" was updated.');
        redirect('/admin/movies');
    }

    public function status(int $id): void
    {
        Movie::find($id) ?? abort(404);
        $status = (string) ($_POST['status'] ?? '');
        if (in_array($status, Movie::STATUSES, true)) {
            Movie::setStatus($id, $status);
            flash('success', 'Status changed to ' . $status . '.');
        }
        redirect('/admin/movies');
    }

    public function destroy(int $id): void
    {
        $movie = Movie::find($id) ?? abort(404);
        if (Movie::hasShowtimes($id)) {
            flash('error', '"' . $movie['title'] . '" has screenings, so it cannot be deleted. Set its status to Ended instead.');
            redirect('/admin/movies');
        }
        Movie::delete($id);
        $this->deletePosterFile($movie['poster']);
        flash('success', 'Movie "' . $movie['title'] . '" was deleted.');
        redirect('/admin/movies');
    }

    public function addGenre(): void
    {
        $name = trim((string) ($_POST['genre_name'] ?? ''));
        if ($name === '' || mb_strlen($name) > 50) {
            flash('error', 'Genre name is required (max 50 characters).');
        } else {
            Genre::create($name);
            flash('success', 'Genre "' . $name . '" added.');
        }
        redirect((string) ($_POST['back'] ?? '') === 'create' ? '/admin/movies/create' : '/admin/movies');
    }

    // ---------------------------------------------------------------

    private function validated(?array $movie, string $back): array
    {
        $v = new Validator($_POST);
        $v->required('title', 'Title')->max('title', 150)
          ->required('description', 'Description')
          ->required('duration', 'Duration')->integer('duration', 30, 400)
          ->required('release_date', 'Release date')->date('release_date')
          ->max('original_title', 150)
          ->required('language', 'Language')->max('language', 50)
          ->required('director', 'Director')->max('director', 100)
          ->max('starring', 255)
          ->required('rating', 'Rating')->number('rating', 0, 10)
          ->in('status', Movie::STATUSES);
        $genreIds = array_map('intval', (array) ($_POST['genres'] ?? []));
        if (!$genreIds) {
            $v->addError('genres', 'Choose at least one genre.');
        }

        $poster = $movie['poster'] ?? null;
        $posterUrl = $v->get('poster_url');
        if (!empty($_FILES['poster']['name'])) {
            $result = $this->storeUpload($_FILES['poster']);
            if (isset($result['error'])) {
                $v->addError('poster', $result['error']);
            } else {
                $this->deletePosterFile($poster);
                $poster = $result['file'];
            }
        } elseif ($posterUrl !== '') {
            if (!filter_var($posterUrl, FILTER_VALIDATE_URL) || !preg_match('#^https?://#', $posterUrl)) {
                $v->addError('poster_url', 'Enter a full image link starting with http:// or https://');
            } else {
                $poster = $posterUrl;
            }
        }

        if ($v->fails()) {
            $this->backWithErrors($back, $v->errors(), $_POST);
        }

        return [[
            'title'        => $v->get('title'),
            'original_title' => $v->get('original_title') !== '' ? $v->get('original_title') : null,
            'language'     => $v->get('language'),
            'starring'     => $v->get('starring') !== '' ? $v->get('starring') : null,
            'description'  => $v->get('description'),
            'duration'     => (int) $v->get('duration'),
            'release_date' => $v->get('release_date'),
            'director'     => $v->get('director'),
            'rating'       => round((float) $v->get('rating'), 1),
            'poster'       => $poster,
            'status'       => $v->get('status'),
        ], $genreIds];
    }

    /** Saves an uploaded poster after checking it is a real JPG/PNG/WebP image. */
    private function storeUpload(array $file): array
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['error' => 'The poster upload failed. Try a smaller image (max 2 MB).'];
        }
        if ($file['size'] > self::MAX_UPLOAD) {
            return ['error' => 'The poster must be 2 MB or smaller.'];
        }
        $types = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        if (!isset($types[$mime]) || @getimagesize($file['tmp_name']) === false) {
            return ['error' => 'The poster must be a JPG, PNG or WebP image.'];
        }
        $name = bin2hex(random_bytes(8)) . '.' . $types[$mime];
        if (!move_uploaded_file($file['tmp_name'], ROOT . '/public/uploads/posters/' . $name)) {
            return ['error' => 'Could not save the poster. Check that public/uploads/posters is writable.'];
        }
        return ['file' => $name];
    }

    private function deletePosterFile(?string $poster): void
    {
        if ($poster && !preg_match('#^https?://#', $poster) && preg_match('/^[a-f0-9]{16}\.(jpg|png|webp)$/', $poster)) {
            @unlink(ROOT . '/public/uploads/posters/' . $poster);
        }
    }
}
