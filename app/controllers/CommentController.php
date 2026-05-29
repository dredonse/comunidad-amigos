<?php

declare(strict_types=1);

class CommentController
{
    private PostModel $postModel;
    private NotificationModel $notificationModel;

    public function __construct()
    {
        requireLogin();
        $this->postModel           = new PostModel();
        $this->notificationModel   = new NotificationModel();
    }

    public function store(): void
    {
        verifyCsrf();

        $user    = currentUser();
        $postId  = (int)($_POST['post_id'] ?? 0);
        $comment = trim($_POST['comment'] ?? '');

        if ($postId < 1) {
            $this->json(['success' => false, 'error' => 'Publicación no válida.'], 400);
        }

        if (strlen($comment) < 1 || strlen($comment) > 1000) {
            $this->json(['success' => false, 'error' => 'El comentario debe tener entre 1 y 1000 caracteres.'], 400);
        }

        $post = $this->postModel->findById($postId);
        if (!$post) {
            $this->json(['success' => false, 'error' => 'La publicación no existe.'], 404);
        }

        if ((int)$post['user_id'] === (int)$user['id']) {
            $this->json(['success' => false, 'error' => 'No puedes comentar tu propia publicación.'], 403);
        }

        $this->postModel->addComment($postId, (int)$user['id'], $comment);

        $ownerId = (int)$post['user_id'];
        if ($ownerId !== (int)$user['id']) {
            $this->notificationModel->create(
                $ownerId,
                'comment',
                $user['username'] . ' comentó tu publicación',
                $postId
            );

            PushHelper::sendNotification(
                [$ownerId],
                'Nuevo comentario',
                '@' . $user['username'] . ' comentó tu publicación',
                '/feed'
            );
        }

        $this->json([
            'success'  => true,
            'username' => $user['username'],
            'avatar'   => $user['avatar'],
            'comment'  => $comment,
        ]);
    }

    private function json(array $data, int $code = 200): never
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
