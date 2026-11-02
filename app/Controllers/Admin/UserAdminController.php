<?php
namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\User;

/** FR17: list users and manage their roles. */
class UserAdminController extends Controller
{
    public function index(): void
    {
        $q = trim((string) ($_GET['q'] ?? ''));
        $role = in_array($_GET['role'] ?? '', ['Customer', 'Admin'], true) ? $_GET['role'] : '';
        $this->view('admin/users/index', [
            'pageTitle' => 'Users',
            'users'     => User::search($q, $role),
            'filters'   => ['q' => $q, 'role' => $role],
        ], 'admin');
    }

    public function role(int $id): void
    {
        $user = User::find($id) ?? abort(404);
        $role = (string) ($_POST['role'] ?? '');
        if (!in_array($role, ['Customer', 'Admin'], true)) {
            redirect('/admin/users');
        }
        if ($id === Auth::id()) {
            flash('error', 'You cannot change your own role.');
        } else {
            User::setRole($id, $role);
            flash('success', $user['full_name'] . ' is now ' . ($role === 'Admin' ? 'an Admin.' : 'a Customer.'));
        }
        redirect('/admin/users');
    }

    public function destroy(int $id): void
    {
        $user = User::find($id) ?? abort(404);
        if ($id === Auth::id()) {
            flash('error', 'You cannot delete your own account.');
        } elseif ($user['role'] === 'Admin' && User::countAdmins() <= 1) {
            flash('error', 'You cannot delete the last admin.');
        } else {
            User::delete($id);
            flash('success', 'User ' . $user['full_name'] . ' was deleted.');
        }
        redirect('/admin/users');
    }
}
