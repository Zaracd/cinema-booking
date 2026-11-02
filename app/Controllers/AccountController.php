<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Validator;
use App\Models\Booking;
use App\Models\User;

class AccountController extends Controller
{
    /** FR10: upcoming and past bookings with their status. */
    public function bookings(): void
    {
        Booking::releaseExpiredHolds();
        $upcoming = [];
        $past = [];
        foreach (Booking::forUser(Auth::id()) as $b) {
            $b['can_cancel'] = Booking::canCancel($b);
            if (strtotime($b['show_date'] . ' ' . $b['start_time']) > time()) {
                $upcoming[] = $b;
            } else {
                $past[] = $b;
            }
        }
        // Soonest screening first for upcoming ones.
        $upcoming = array_reverse($upcoming);
        $this->view('account/bookings', ['pageTitle' => 'My bookings', 'upcoming' => $upcoming, 'past' => $past]);
    }

    public function profile(): void
    {
        $this->view('account/profile', ['pageTitle' => 'My profile', 'user' => Auth::user()]);
    }

    public function updateProfile(): void
    {
        $v = new Validator($_POST);
        $v->required('full_name', 'Full name')->max('full_name', 100)->required('phone', 'Phone')->phone('phone');
        if ($v->fails()) {
            $this->backWithErrors('/account/profile', $v->errors(), $_POST);
        }
        User::updateProfile(Auth::id(), $v->get('full_name'), $v->get('phone'));
        flash('success', 'Your profile was updated.');
        redirect('/account/profile');
    }

    public function changePassword(): void
    {
        $user = User::findByEmail(Auth::user()['email']);
        $v = new Validator($_POST);
        if (!password_verify((string) ($_POST['current_password'] ?? ''), $user['password'])) {
            $v->addError('current_password', 'Your current password is not correct.');
        }
        $v->strongPassword('password')->same('password_confirm', 'password', 'The two passwords do not match.');
        if ($v->fails()) {
            $this->backWithErrors('/account/profile', $v->errors());
        }
        User::updatePassword(Auth::id(), $_POST['password']);
        flash('success', 'Your password was changed.');
        redirect('/account/profile');
    }
}
