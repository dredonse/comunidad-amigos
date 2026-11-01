<?php

declare(strict_types=1);

class ProfileController
{
    private UserModel $userModel;
    private PostModel $postModel;
    private FriendModel $friendModel;

    public function __construct()
    {
        requireLogin();
        $this->userModel   = new UserModel();
        $this->postModel   = new PostModel();
        $this->friendModel = new FriendModel();
    }

    public function show(int $id = 0): void
    {
        $currentUser = currentUser();
        $currentId   = (int)$currentUser['id'];
        $profileId   = $id > 0 ? $id : $currentId;

        $profileUser = $this->userModel->findById($profileId);
        if (!$profileUser || !$profileUser['active']) {
            http_response_code(404);
            require_once VIEWS_PATH . '/404.php';
            return;
        }

        $isOwner = $profileId === $currentId;

        $friendStatus = $isOwner ? null : $this->friendModel->getStatus($currentId, $profileId);

        // Coherente con el feed: las publicaciones solo las ven el dueño, sus amigos y los admins
        $canViewPosts = $isOwner
            || ($currentUser['role'] ?? '') === 'admin'
            || ($friendStatus !== null && $friendStatus['status'] === 'accepted');

        $page    = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 10;
        $total   = $this->postModel->countByUser($profileId);
        $pag     = paginate($canViewPosts ? $total : 0, $perPage, $page);
        $posts   = [];

        if ($canViewPosts) {
            $posts = $this->postModel->getByUser(
                $profileId,
                $currentId,
                $pag['perPage'],
                $pag['offset']
            );

            foreach ($posts as &$post) {
                $post['comments'] = $this->postModel->getComments((int)$post['id'], 3);
            }
            unset($post);
        }

        $friendCount  = count($this->friendModel->getFriends($profileId));
        $postCount    = $total;

        require_once VIEWS_PATH . '/profile/show.php';
    }

    public function update(): void
    {
        verifyCsrf();

        $currentUser = currentUser();
        $userId      = (int)$currentUser['id'];

        $user = $this->userModel->findById($userId);
        if (!$user) {
            flash('profile', 'Usuario no encontrado.', 'danger');
            redirect('/profile');
        }

        $bio = trim($_POST['bio'] ?? '');
        if (mb_strlen($bio) > 300) {
            flash('profile', 'La biografía no puede superar 300 caracteres.', 'danger');
            redirect('/profile');
        }

        $this->userModel->updateProfile($userId, $bio);

        if (!empty($_FILES['avatar']['name'])) {
            $newAvatar = uploadImage($_FILES['avatar'], 'avatars');
            if ($newAvatar === null) {
                flash('profile', 'El avatar no es válido. Usa JPG, PNG, WebP o GIF (máx. 5 MB).', 'danger');
                redirect('/profile');
            }

            deleteUpload('avatars', $user['avatar'] ?? null);

            $this->userModel->updateAvatar($userId, $newAvatar);
            $_SESSION['avatar'] = $newAvatar;
        }

        $newPassword     = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $currentPassword = $_POST['current_password'] ?? '';

        if ($newPassword !== '') {
            if (strlen($newPassword) < 8) {
                flash('profile', 'La nueva contraseña debe tener al menos 8 caracteres.', 'danger');
                redirect('/profile');
            }

            if ($newPassword !== $confirmPassword) {
                flash('profile', 'Las contraseñas nuevas no coinciden.', 'danger');
                redirect('/profile');
            }

            $hash = $this->userModel->getPasswordHash($userId);
            if (!$hash || !$this->userModel->verifyPassword($currentPassword, $hash)) {
                flash('profile', 'La contraseña actual no es correcta.', 'danger');
                redirect('/profile');
            }

            $this->userModel->updatePassword($userId, $newPassword);
        }

        flash('profile', 'Perfil actualizado correctamente.');
        redirect('/profile');
    }
}
