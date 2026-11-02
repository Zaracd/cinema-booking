<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Validator;
use App\Models\Hall;
use App\Models\Movie;
use App\Models\Showtime;

/** FR15: screenings, with no two screenings overlapping in the same hall. */
class ShowtimeAdminController extends Controller
{
    public function index(): void
    {
        $filters = [
            'date'  => preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['date'] ?? '') ? $_GET['date'] : '',
            'movie' => (int) ($_GET['movie'] ?? 0) ?: '',
            'hall'  => (int) ($_GET['hall'] ?? 0) ?: '',
            'past'  => !empty($_GET['past']),
        ];
        $this->view('admin/showtimes/index', [
            'pageTitle' => 'Screenings',
            'showtimes' => Showtime::adminList($filters),
            'movies'    => Movie::search([]),
            'halls'     => Hall::allHalls(),
            'filters'   => $filters,
        ], 'admin');
    }

    public function create(): void
    {
        $this->form(null);
    }

    public function store(): void
    {
        $data = $this->validated(0, '/admin/showtimes/create');
        Showtime::create($data);
        flash('success', 'Screening added.');
        redirect('/admin/showtimes?date=' . $data['show_date']);
    }

    public function edit(int $id): void
    {
        $this->form(Showtime::find($id) ?? abort(404));
    }

    public function update(int $id): void
    {
        Showtime::find($id) ?? abort(404);
        $data = $this->validated($id, "/admin/showtimes/$id/edit");
        if (Showtime::hasBookings($id)) {
            $old = Showtime::find($id);
            if ((int) $old['hall_id'] !== (int) $data['hall_id']) {
                $this->backWithErrors("/admin/showtimes/$id/edit", ['hall_id' => 'This screening already has bookings, so its hall cannot change.'], $_POST);
            }
        }
        Showtime::update($id, $data);
        flash('success', 'Screening updated.');
        redirect('/admin/showtimes?date=' . $data['show_date']);
    }

    public function destroy(int $id): void
    {
        Showtime::find($id) ?? abort(404);
        if (Showtime::hasBookings($id)) {
            flash('error', 'This screening has bookings (including cancelled ones) in its history, so it cannot be deleted.');
        } else {
            Showtime::delete($id);
            flash('success', 'Screening deleted.');
        }
        redirect('/admin/showtimes');
    }

    // ---------------------------------------------------------------

    private function form(?array $show): void
    {
        $this->view('admin/showtimes/form', [
            'pageTitle' => $show ? 'Edit screening' : 'Add screening',
            'show'      => $show,
            'movies'    => Movie::options(),
            'halls'     => Hall::allHalls(),
        ], 'admin');
    }

    private function validated(int $id, string $back): array
    {
        $v = new Validator($_POST);
        $v->required('movie_id', 'Movie')->integer('movie_id', 1)
          ->required('hall_id', 'Hall')->integer('hall_id', 1)
          ->required('show_date', 'Date')->date('show_date')
          ->required('start_time', 'Start time')->time('start_time')
          ->required('end_time', 'End time')->time('end_time')
          ->required('ticket_price', 'Ticket price')->number('ticket_price', 0, 10000);

        $movieId = (int) $v->get('movie_id');
        $hallId = (int) $v->get('hall_id');
        if ($movieId && !Movie::find($movieId)) {
            $v->addError('movie_id', 'Choose a movie.');
        }
        if ($hallId && !Hall::find($hallId)) {
            $v->addError('hall_id', 'Choose a hall.');
        }

        $date = $v->get('show_date');
        $start = substr($v->get('start_time'), 0, 5);
        $end = substr($v->get('end_time'), 0, 5);
        if (!$v->fails()) {
            if ($end <= $start) {
                $v->addError('end_time', 'End time must be after the start time (on the same day).');
            } elseif ($id === 0 && strtotime("$date $start") < time()) {
                $v->addError('start_time', 'A new screening cannot start in the past.');
            } elseif ($c = Showtime::findConflict($hallId, $date, $start, $end, $id)) {
                $v->addError('start_time', 'This hall is busy then: "' . $c['title'] . '" runs '
                    . fmt_time($c['start_time']) . ' – ' . fmt_time($c['end_time']) . '.');
            }
        }
        if ($v->fails()) {
            $this->backWithErrors($back, $v->errors(), $_POST);
        }
        return [
            'movie_id'     => $movieId,
            'hall_id'      => $hallId,
            'show_date'    => $date,
            'start_time'   => $start,
            'end_time'     => $end,
            'ticket_price' => round((float) $v->get('ticket_price'), 2),
        ];
    }
}
