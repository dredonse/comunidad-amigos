<?php

declare(strict_types=1);

class LikeController
{
    private PostModel $postModel;
    private NotificationModel $notificationModel;
    private FriendModel $friendModel;

    public function __construct()
    {
        requireLogin();
        $this->postModel         = new PostModel();
        $this->friendModel       = new FriendModel();
        $this->notificationModel = new NotificationModel();
    }

    public function toggle(): void
    {
        verifyCsrf();

        $user   = currentUser();
        $postId = (int)($_POST['post_id'] ?? 0);

        if ($postId < 1) {
            jsonResponse(['success' => false, 'error' => 'Publicación no válida.'], 400);
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

        $result = $this->postModel->toggleLike($postId, (int)$user['id']);

        if ($result['liked']) {
            if ($ownerId !== (int)$user['id']) {
                $this->notificationModel->create(
                    $ownerId,
                    'like',
                    $user['username'] . ' le gustó tu publicación',
                    $postId
                );

                PushHelper::sendNotification(
                    [$ownerId],
                    'Nuevo like',
                    '@' . $user['username'] . ' dio like a tu publicación',
                    '/feed'
                );
            }
        }

        jsonResponse([
            'success' => true,
            'liked'   => $result['liked'],
            'count'   => $result['count'],
        ]);
    }
}
