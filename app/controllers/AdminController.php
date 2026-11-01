<?php

declare(strict_types=1);

class AdminController
{
    private AdminModel $adminModel;
    private UserModel $userModel;

    public function __construct()
    {
        requireAdmin();
        $this->adminModel = new AdminModel();
        $this->userModel  = new UserModel();
    }

    public function index(): void
    {
        $stats       = $this->adminModel->getStats();
        $recentUsers = $this->adminModel->getRecentUsers(5);
        $recentPosts = $this->adminModel->getRecentPosts(5);

        require_once VIEWS_PATH . '/admin/index.php';
    }

    public function users(): void
    {
        $adminId = (int)currentUser()['id'];
        $page    = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 20;

        $total = $this->adminModel->countUsers();
        $pag   = paginate($total, $perPage, $page);

        $users = $this->adminModel->getAllUsers($pag['perPage'], $pag['offset']);

        $userForm = $_SESSION['admin_user_form'] ?? null;
        unset($_SESSION['admin_user_form']);

        require_once VIEWS_PATH . '/admin/users.php';
    }

    private function redirectCreateUserError(string $message, array $form): never
    {
        $_SESSION['admin_user_form'] = $form;
        flash('admin', $message, 'danger');
        redirect('/admin/users?new=1');
    }

    public function createUser(): void
    {
        verifyCsrf();

        $username = trim($_POST['username'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role     = $_POST['role'] ?? 'user';

        $form = ['username' => $username, 'email' => $email, 'role' => $role];

        if (strlen($username) < 3 || strlen($username) > 50) {
            $this->redirectCreateUserError(
                'El nombre de usuario debe tener entre 3 y 50 caracteres.',
                $form
            );
        }

        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            $this->redirectCreateUserError(
                'El usuario solo puede contener letras, números y guión bajo.',
                $form
            );
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->redirectCreateUserError('El email no es válido.', $form);
        }

        if (strlen($password) < 8) {
            $this->redirectCreateUserError(
                'La contraseña debe tener al menos 8 caracteres.',
                $form
            );
        }

        if (!in_array($role, ['user', 'admin'], true)) {
            $role = 'user';
        }

        if ($this->userModel->usernameExists($username)) {
            $this->redirectCreateUserError('Ese nombre de usuario ya está en uso.', $form);
        }

        if ($this->userModel->emailExists($email)) {
            $this->redirectCreateUserError('Ese email ya está registrado.', $form);
        }

        $this->userModel->create($username, $email, $password, $role);

        flash('admin', 'Usuario creado correctamente.');
        redirect('/admin/users');
    }

    public function posts(): void
    {
        $page    = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 20;
        $total   = $this->adminModel->countAllPosts();
        $pag     = paginate($total, $perPage, $page);
        $posts   = $this->adminModel->getAllPosts($pag['perPage'], $pag['offset']);

        require_once VIEWS_PATH . '/admin/posts.php';
    }

    public function comments(): void
    {
        $page     = max(1, (int)($_GET['page'] ?? 1));
        $perPage  = 20;
        $total    = $this->adminModel->countAllComments();
        $pag      = paginate($total, $perPage, $page);
        $comments = $this->adminModel->getAllComments($pag['perPage'], $pag['offset']);

        require_once VIEWS_PATH . '/admin/comments.php';
    }

    public function events(): void
    {
        $page    = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 20;
        $total   = $this->adminModel->countAllEvents();
        $pag     = paginate($total, $perPage, $page);
        $events  = $this->adminModel->getAllEvents($pag['perPage'], $pag['offset']);

        require_once VIEWS_PATH . '/admin/events.php';
    }

    public function groups(): void
    {
        $page    = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 20;
        $total   = $this->adminModel->countAllGroups();
        $pag     = paginate($total, $perPage, $page);
        $groups  = $this->adminModel->getAllGroups($pag['perPage'], $pag['offset']);

        require_once VIEWS_PATH . '/admin/groups.php';
    }

    public function toggleUser(): void
    {
        verifyCsrf();

        $adminId = (int)currentUser()['id'];
        $userId  = (int)($_POST['user_id'] ?? 0);

        if ($userId < 1) {
            flash('admin', 'Usuario no válido.', 'danger');
            redirect('/admin/users');
        }

        if ($userId === $adminId) {
            flash('admin', 'No puedes desactivar tu propia cuenta.', 'warning');
            redirect('/admin/users');
        }

        $user = $this->userModel->findById($userId);
        if (!$user) {
            flash('admin', 'Usuario no encontrado.', 'danger');
            redirect('/admin/users');
        }

        if ($user['role'] === 'admin') {
            flash('admin', 'No se puede desactivar a otro administrador.', 'warning');
            redirect('/admin/users');
        }

        $this->userModel->toggleActive($userId);

        $label = $user['active'] ? 'desactivado' : 'activado';
        flash('admin', 'Usuario ' . $user['username'] . ' ' . $label . ' correctamente.');
        redirect('/admin/users');
    }

    public function deletePost(): void
    {
        verifyCsrf();

        $postId = (int)($_POST['post_id'] ?? 0);

        if ($postId < 1) {
            flash('admin', 'Publicación no válida.', 'danger');
            redirect('/admin');
        }

        if (!$this->adminModel->deletePost($postId)) {
            flash('admin', 'No se pudo eliminar la publicación.', 'danger');
            redirect('/admin');
        }

        flash('admin', 'Publicación eliminada.');
        redirect('/admin/posts');
    }

    public function deleteComment(): void
    {
        verifyCsrf();

        $commentId = (int)($_POST['comment_id'] ?? 0);

        if ($commentId < 1) {
            flash('admin', 'Comentario no válido.', 'danger');
            redirect('/admin/comments');
        }

        if (!$this->adminModel->deleteComment($commentId)) {
            flash('admin', 'No se pudo eliminar el comentario.', 'danger');
            redirect('/admin/comments');
        }

        flash('admin', 'Comentario eliminado.');
        redirect('/admin/comments');
    }

    public function deleteEvent(): void
    {
        verifyCsrf();

        $eventId = (int)($_POST['event_id'] ?? 0);

        if ($eventId < 1) {
            flash('admin', 'Evento no válido.', 'danger');
            redirect('/admin/events');
        }

        if (!$this->adminModel->deleteEvent($eventId)) {
            flash('admin', 'No se pudo eliminar el evento.', 'danger');
            redirect('/admin/events');
        }

        flash('admin', 'Evento eliminado.');
        redirect('/admin/events');
    }

    public function deleteGroup(): void
    {
        verifyCsrf();

        $groupId = (int)($_POST['group_id'] ?? 0);

        if ($groupId < 1) {
            flash('admin', 'Grupo no válido.', 'danger');
            redirect('/admin/groups');
        }

        if (!$this->adminModel->deleteGroup($groupId)) {
            flash('admin', 'No se pudo eliminar el grupo.', 'danger');
            redirect('/admin/groups');
        }

        flash('admin', 'Grupo eliminado.');
        redirect('/admin/groups');
    }

    public function toggleGroupPrivacy(): void
    {
        verifyCsrf();

        $groupId = (int)($_POST['group_id'] ?? 0);

        if ($groupId < 1) {
            flash('admin', 'Grupo no válido.', 'danger');
            redirect('/admin/groups');
        }

        if (!$this->adminModel->toggleGroupPrivacy($groupId)) {
            flash('admin', 'No se pudo cambiar la privacidad del grupo.', 'danger');
            redirect('/admin/groups');
        }

        flash('admin', 'Privacidad del grupo actualizada.');
        redirect('/admin/groups');
    }

    public function deleteUser(): void
    {
        verifyCsrf();

        $adminId = (int)currentUser()['id'];
        $userId  = (int)($_POST['user_id'] ?? 0);

        if ($userId < 1) {
            flash('admin', 'Usuario no válido.', 'danger');
            redirect('/admin/users');
        }

        if ($userId === $adminId) {
            flash('admin', 'No puedes eliminar tu propia cuenta.', 'warning');
            redirect('/admin/users');
        }

        if (!$this->adminModel->deleteUser($userId, $adminId)) {
            flash('admin', 'No se pudo eliminar el usuario (los administradores no se pueden borrar).', 'danger');
            redirect('/admin/users');
        }

        flash('admin', 'Usuario eliminado.');
        redirect('/admin/users');
    }
}
