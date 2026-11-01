<?php

declare(strict_types=1);

class FeedController
{
    private PostModel $postModel;
    private UserModel $userModel;

    public function __construct()
    {
        requireLogin();
        $this->postModel = new PostModel();
        $this->userModel = new UserModel();
    }

    public function index(): void
    {
        $user    = currentUser();
        $userId  = (int)$user['id'];
        $page    = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 10;

        $total      = $this->postModel->countFeed($userId);
        $pagination = paginate($total, $perPage, $page);

        $posts = $this->postModel->getFeed(
            $userId,
            $pagination['perPage'],
            $pagination['offset']
        );

        foreach ($posts as &$post) {
            $post['comments'] = $this->postModel->getComments((int)$post['id'], 3);
        }
        unset($post);

        $profile = $this->userModel->findById($userId);

        require_once VIEWS_PATH . '/posts/feed.php';
    }

    public function store(): void
    {
        verifyCsrf();

        $user    = currentUser();
        $content = trim($_POST['content'] ?? '');

        if (mb_strlen($content) < 1 || mb_strlen($content) > 2000) {
            flash('feed', 'El contenido debe tener entre 1 y 2000 caracteres.', 'danger');
            redirect('/feed');
        }

        $image = null;
        if (!empty($_FILES['image']['name'])) {
            $image = uploadImage($_FILES['image'], 'posts');
            if ($image === null) {
                flash('feed', 'La imagen no es válida. Usa JPG, PNG, WebP o GIF (máx. 5 MB).', 'danger');
                redirect('/feed');
            }
        }

        $this->postModel->create((int)$user['id'], $content, $image);

        flash('feed', 'Publicación creada correctamente.');
        redirect('/feed');
    }

    public function delete(): void
    {
        verifyCsrf();

        $user   = currentUser();
        $postId = (int)($_POST['post_id'] ?? 0);

        if ($postId < 1) {
            flash('feed', 'Publicación no válida.', 'danger');
            redirect('/feed');
        }

        $post = $this->postModel->findById($postId);
        if (!$post) {
            flash('feed', 'La publicación no existe.', 'danger');
            redirect('/feed');
        }

        $isOwner = (int)$post['user_id'] === (int)$user['id'];
        $isAdmin = ($user['role'] ?? '') === 'admin';

        if (!$isOwner && !$isAdmin) {
            flash('feed', 'No tienes permiso para eliminar esta publicación.', 'danger');
            redirect('/feed');
        }

        if ($isOwner) {
            $this->postModel->delete($postId, (int)$user['id']);
        } else {
            $this->postModel->deleteById($postId);
        }
        deleteUpload('posts', $post['image'] ?? null);

        flash('feed', 'Publicación eliminada.');
        redirect('/feed');
    }
}
