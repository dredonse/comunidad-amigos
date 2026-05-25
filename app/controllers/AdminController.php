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

        require_once VIEWS_PATH . '/admin/users.php';
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

        $this->userModel->toggleActive($userId);

        $label = $user['active'] ? 'desactivado' : 'activado';
        flash('admin', 'Usuario ' . e($user['username']) . ' ' . $label . ' correctamente.');
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
        redirect('/admin');
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
            flash('admin', 'No se pudo eliminar el usuario.', 'danger');
            redirect('/admin/users');
        }

        flash('admin', 'Usuario eliminado.');
        redirect('/admin/users');
    }
}
