<?php

declare(strict_types=1);

class LikeController
{
    private PostModel $postModel;
    private NotificationModel $notificationModel;

    public function __construct()
    {
        requireLogin();
        $this->postModel         = new PostModel();
        $this->notificationModel = new NotificationModel();
    }

    public function toggle(): void
    {
        verifyCsrf();

        $user   = currentUser();
        $postId = (int)($_POST['post_id'] ?? 0);

        if ($postId < 1) {
            $this->json(['success' => false, 'error' => 'Publicación no válida.'], 400);
        }

        $post = $this->postModel->findById($postId);
        if (!$post) {
            $this->json(['success' => false, 'error' => 'La publicación no existe.'], 404);
        }

        $result = $this->postModel->toggleLike($postId, (int)$user['id']);

        if ($result['liked']) {
            $ownerId = (int)$post['user_id'];
            if ($ownerId !== (int)$user['id']) {
                $this->notificationModel->create(
                    $ownerId,
                    'like',
                    $user['username'] . ' le gustó tu publicación',
                    $postId
                );
            }
        }

        $this->json([
            'success' => true,
            'liked'   => $result['liked'],
            'count'   => $result['count'],
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
