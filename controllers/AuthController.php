<?php
class AuthController extends Controller
{
    public function index(): void
    {
        $this->login();
    }

    public function login(): void
    {
        if (Auth::check()) {
            redirect('dashboard');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->requireCsrf();
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';

            if ($email === '' || $password === '') {
                Flash::set('error', 'Please enter your email and password.');
                $_SESSION['_old']['email'] = $email;
                redirect('auth/login');
            }

            if (Auth::attempt($email, $password)) {
                unset($_SESSION['_old']);
                ActivityLog::record('login', 'User signed in');
                redirect('dashboard');
            }

            Flash::set('error', 'Invalid credentials or inactive account.');
            $_SESSION['_old']['email'] = $email;
            redirect('auth/login');
        }

        $this->view('auth/login', ['pageTitle' => 'Sign in'], 'blank');
    }

    public function logout(): void
    {
        ActivityLog::record('logout', 'User signed out');
        Auth::logout();
        Flash::set('success', 'You have been signed out.');
        redirect('auth/login');
    }
}
