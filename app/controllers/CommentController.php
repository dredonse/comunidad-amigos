<?php

declare(strict_types=1);

class CommentController
{
    private PostModel $postModel;
    private NotificationModel $notificationModel;
    private FriendModel $friendModel;

    public function __construct()
    {
        requireLogin();
        $this->postModel           = new PostModel();
        $this->friendModel       = new FriendModel();
        $this->notificationModel   = new NotificationModel();
    }

    public function store(): void
    {
        verifyCsrf();

        $user    = currentUser();
        $postId  = (int)($_POST['post_id'] ?? 0);
        $comment = trim($_POST['comment'] ?? '');

        if ($postId < 1) {
            jsonResponse(['success' => false, 'error' => 'Publicación no válida.'], 400);
        }

        if (mb_strlen($comment) < 1 || mb_strlen($comment) > 1000) {
            jsonResponse(['success' => false, 'error' => 'El comentario debe tener entre 1 y 1000 caracteres.'], 400);
        }

        $post = $this->postModel->findById($postId);
        if (!$post) {
            jsonResponse(['success' => false, 'error' => 'La publicación no existe.'], 404);
        }

        // Igual que en el feed: solo el autor, sus amigos o un administrador
        $ownerId = (int)$post['user_id'];
        $canInteract = $ownerId === (int)$user['id']
            || ($user['role'] ?? '') === 'admin'
            || $this->friendModel->areFriends((int)$user['id'], $ownerId);
        if (!$canInteract) {
            jsonResponse(['success' => false, 'error' => 'Solo los amigos del autor pueden interactuar con esta publicación.'], 403);
        }

        if ((int)$post['user_id'] === (int)$user['id']) {
            jsonResponse(['success' => false, 'error' => 'No puedes comentar tu propia publicación.'], 403);
        }

        $this->postModel->addComment($postId, (int)$user['id'], $comment);

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

        jsonResponse([
            'success'  => true,
            'username' => $user['username'],
            'avatar'   => $user['avatar'],
            'comment'  => $comment,
        ]);
    }
}
