<?php

declare(strict_types=1);

class AuthController
{
    private UserModel $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    // ── Registro ──────────────────────────────────────────────

    public function showRegister(): void
    {
        if (isLoggedIn()) redirect('/feed');
        require_once VIEWS_PATH . '/auth/register.php';
    }

    public function register(): void
    {
        if (isLoggedIn()) redirect('/feed');
        verifyCsrf();

        $username = trim($_POST['username'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['password_confirm'] ?? '';
        $errors   = [];

        // Validaciones
        if (strlen($username) < 3 || strlen($username) > 50) {
            $errors[] = 'El nombre de usuario debe tener entre 3 y 50 caracteres.';
        }
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            $errors[] = 'El nombre de usuario solo puede contener letras, números y guiones bajos.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'El correo electrónico no es válido.';
        }
        if (strlen($password) < 8) {
            $errors[] = 'La contraseña debe tener al menos 8 caracteres.';
        }
        if ($password !== $confirm) {
            $errors[] = 'Las contraseñas no coinciden.';
        }
        if ($this->userModel->emailExists($email)) {
            $errors[] = 'Este correo electrónico ya está registrado.';
        }
        if ($this->userModel->usernameExists($username)) {
            $errors[] = 'Este nombre de usuario ya está en uso.';
        }

        if (!empty($errors)) {
            $_SESSION['form_errors'] = $errors;
            $_SESSION['form_data']   = compact('username', 'email');
            redirect('/register');
        }

        $id = $this->userModel->create($username, $email, $password);
        $this->createSession($this->userModel->findById($id));

        flash('success', '¡Bienvenido a la comunidad, ' . e($username) . '!');
        redirect('/feed');
    }

    // ── Login ──────────────────────────────────────────────────

    public function showLogin(): void
    {
        if (isLoggedIn()) redirect('/feed');
        require_once VIEWS_PATH . '/auth/login.php';
    }

    public function login(): void
    {
        if (isLoggedIn()) redirect('/feed');
        verifyCsrf();

        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        $user = $this->userModel->findByEmail($email);

        if (!$user || !$this->userModel->verifyPassword($password, $user['password'])) {
            flash('auth', 'Credenciales incorrectas.', 'danger');
            redirect('/login');
        }

        if (!$user['active']) {
            flash('auth', 'Tu cuenta está desactivada. Contacta con el administrador.', 'warning');
            redirect('/login');
        }

        $this->createSession($user);

        flash('success', '¡Bienvenido de nuevo, ' . e($user['username']) . '!');
        redirect('/feed');
    }

    // ── Logout ─────────────────────────────────────────────────

    public function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }
        session_destroy();
        redirect('/login');
    }

    // ── Helpers privados ──────────────────────────────────────

    private function createSession(array $user): void
    {
        session_regenerate_id(true); // Prevenir session fixation
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['username']  = $user['username'];
        $_SESSION['avatar']    = $user['avatar'];
        $_SESSION['user_role'] = $user['role'];
    }
}
