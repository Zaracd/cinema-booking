<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Validator;
use App\Models\Hall;
use App\Models\Seat;

/** FR13 + FR14: halls and their seats. */
class HallAdminController extends Controller
{
    public function index(): void
    {
        $this->view('admin/halls/index', ['pageTitle' => 'Halls & seats', 'halls' => Hall::allHalls()], 'admin');
    }

    /** New hall with a grid of seats (rows x seats per row). Capacity = number of seats. */
    public function store(): void
    {
        $v = new Validator($_POST);
        $v->required('hall_name', 'Hall name')->max('hall_name', 50)
          ->required('rows', 'Rows')->integer('rows', 1, 26)
          ->required('per_row', 'Seats per row')->integer('per_row', 1, 40);
        if ($v->get('hall_name') !== '' && Hall::nameExists($v->get('hall_name'))) {
            $v->addError('hall_name', 'A hall with this name already exists.');
        }
        if ($v->fails()) {
            $this->backWithErrors('/admin/halls', $v->errors(), $_POST);
        }
        $vipRows = array_filter(array_map(fn ($r) => strtoupper(trim($r)), explode(',', $v->get('vip_rows'))));
        $id = Hall::createWithSeats($v->get('hall_name'), (int) $v->get('rows'), (int) $v->get('per_row'), $vipRows);
        flash('success', 'Hall "' . $v->get('hall_name') . '" was created with ' . ((int) $v->get('rows') * (int) $v->get('per_row')) . ' seats.');
        redirect("/admin/halls/$id");
    }

    public function show(int $id): void
    {
        $hall = Hall::find($id) ?? abort(404);
        $rows = [];
        foreach (Seat::forHall($id) as $s) {
            $rows[$s['row_label']][] = $s;
        }
        $this->view('admin/halls/show', ['pageTitle' => $hall['hall_name'], 'hall' => $hall, 'rows' => $rows], 'admin');
    }

    public function update(int $id): void
    {
        Hall::find($id) ?? abort(404);
        $v = new Validator($_POST);
        $v->required('hall_name', 'Hall name')->max('hall_name', 50);
        if (Hall::nameExists($v->get('hall_name'), $id)) {
            $v->addError('hall_name', 'A hall with this name already exists.');
        }
        if ($v->fails()) {
            $this->backWithErrors("/admin/halls/$id", $v->errors(), $_POST);
        }
        Hall::rename($id, $v->get('hall_name'));
        flash('success', 'Hall renamed.');
        redirect("/admin/halls/$id");
    }

    public function destroy(int $id): void
    {
        $hall = Hall::find($id) ?? abort(404);
        if (Hall::hasShowtimes($id)) {
            flash('error', '"' . $hall['hall_name'] . '" has screenings, so it cannot be deleted. Delete its screenings first.');
            redirect('/admin/halls');
        }
        Hall::delete($id);
        flash('success', 'Hall "' . $hall['hall_name'] . '" was deleted.');
        redirect('/admin/halls');
    }

    public function addSeat(int $hallId): void
    {
        Hall::find($hallId) ?? abort(404);
        $v = new Validator($_POST);
        $v->required('row_label', 'Row')->max('row_label', 3)
          ->required('seat_number', 'Seat number')->integer('seat_number', 1, 99)
          ->in('seat_type', Seat::TYPES);
        $row = strtoupper($v->get('row_label'));
        if ($row !== '' && !preg_match('/^[A-Z]{1,3}$/', $row)) {
            $v->addError('row_label', 'Row must be letters, e.g. A or AA.');
        }
        if (!$v->fails() && Seat::exists($hallId, $row, (int) $v->get('seat_number'))) {
            $v->addError('seat_number', "Seat $row" . $v->get('seat_number') . ' already exists.');
        }
        if ($v->fails()) {
            $this->backWithErrors("/admin/halls/$hallId", $v->errors(), $_POST);
        }
        Seat::create($hallId, $row, (int) $v->get('seat_number'), $v->get('seat_type'));
        flash('success', "Seat $row" . $v->get('seat_number') . ' added.');
        redirect("/admin/halls/$hallId");
    }

    public function seatType(int $seatId): void
    {
        $seat = Seat::find($seatId) ?? abort(404);
        $type = (string) ($_POST['seat_type'] ?? '');
        if (in_array($type, Seat::TYPES, true)) {
            Seat::setType($seatId, $type);
            flash('success', 'Seat ' . $seat['row_label'] . $seat['seat_number'] . ' is now ' . $type . '.');
        }
        redirect('/admin/halls/' . $seat['hall_id']);
    }

    public function deleteSeat(int $seatId): void
    {
        $seat = Seat::find($seatId) ?? abort(404);
        if (Seat::everBooked($seatId)) {
            flash('error', 'Seat ' . $seat['row_label'] . $seat['seat_number'] . ' has bookings and cannot be deleted.');
        } else {
            Seat::delete($seatId, (int) $seat['hall_id']);
            flash('success', 'Seat ' . $seat['row_label'] . $seat['seat_number'] . ' deleted.');
        }
        redirect('/admin/halls/' . $seat['hall_id']);
    }
}
