<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Validator;
use App\Models\User;

class AuthController extends Controller
{
    public function showRegister(): void
    {
        $this->view('auth/register', ['pageTitle' => 'Create account']);
    }

    /** FR01: register with name, email, phone and a strong password. */
    public function register(): void
    {
        $v = new Validator($_POST);
        $v->required('full_name', 'Full name')->max('full_name', 100)
          ->required('email', 'Email')->email('email')->max('email', 150)
          ->required('phone', 'Phone')->phone('phone')
          ->strongPassword('password')
          ->same('password_confirm', 'password', 'The two passwords do not match.');
        if ($v->get('email') !== '' && User::emailExists($v->get('email'))) {
            $v->addError('email', 'An account with this email already exists.');
        }
        if ($v->fails()) {
            $this->backWithErrors('/register', $v->errors(), $_POST);
        }

        $id = User::create($v->get('full_name'), strtolower($v->get('email')), $v->get('phone'), $_POST['password']);
        Auth::login(User::find($id));
        flash('success', 'Welcome, ' . $v->get('full_name') . '! Your account is ready.');
        redirect('/movies');
    }

    public function showLogin(): void
    {
        $this->view('auth/login', ['pageTitle' => 'Log in']);
    }

    /** FR02: session login with optional "Remember me". */
    public function login(): void
    {
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');
        $user = $email !== '' ? User::findByEmail($email) : null;

        if (!$user || !password_verify($password, $user['password'])) {
            $this->backWithErrors('/login', ['email' => 'Wrong email or password.'], $_POST);
        }

        Auth::login($user, !empty($_POST['remember']));
        flash('success', 'Welcome back, ' . $user['full_name'] . '!');

        $intended = $_SESSION['intended'] ?? null;
        unset($_SESSION['intended']);
        if ($intended && str_starts_with($intended, '/') && !str_starts_with($intended, '//')) {
            redirect($intended);
        }
        redirect($user['role'] === 'Admin' ? '/admin' : '/');
    }

    public function logout(): void
    {
        Auth::logout();
        flash('success', 'You have logged out.');
        redirect('/');
    }
}
