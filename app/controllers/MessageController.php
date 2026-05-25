<?php

declare(strict_types=1);

class MessageController
{
    private MessageModel $messageModel;
    private UserModel $userModel;
    private NotificationModel $notificationModel;

    public function __construct()
    {
        requireLogin();
        $this->messageModel       = new MessageModel();
        $this->userModel          = new UserModel();
        $this->notificationModel  = new NotificationModel();
    }

    public function index(): void
    {
        $userId = (int)currentUser()['id'];

        $conversations = $this->messageModel->getConversations($userId);
        $activeOtherId = null;

        require_once VIEWS_PATH . '/messages/index.php';
    }

    public function conversation(int $otherUserId): void
    {
        $user    = currentUser();
        $userId  = (int)$user['id'];

        if ($otherUserId === $userId) {
            redirect('/messages');
        }

        $otherUser = $this->userModel->findById($otherUserId);
        if (!$otherUser || !$otherUser['active']) {
            flash('messages', 'Usuario no encontrado.', 'danger');
            redirect('/messages');
        }

        $conversations = $this->messageModel->getConversations($userId);
        $activeOtherId = $otherUserId;

        $perPage = 50;
        $total   = $this->messageModel->countMessages($userId, $otherUserId);
        $pag     = paginate($total, $perPage, max(1, (int)($_GET['page'] ?? 1)));

        if ($pag['totalPages'] > 0 && empty($_GET['page'])) {
            $pag['page']   = $pag['totalPages'];
            $pag['offset'] = ($pag['page'] - 1) * $perPage;
        }

        $messages = $this->messageModel->getMessages(
            $userId,
            $otherUserId,
            $pag['perPage'],
            $pag['offset']
        );

        require_once VIEWS_PATH . '/messages/conversation.php';
    }

    public function send(): void
    {
        verifyCsrf();

        $user       = currentUser();
        $senderId   = (int)$user['id'];
        $receiverId = (int)($_POST['receiver_id'] ?? 0);
        $content    = trim($_POST['content'] ?? '');

        if ($receiverId < 1 || !$this->userModel->findById($receiverId)) {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'error' => 'Usuario no válido.'], 400);
            }
            flash('messages', 'Usuario no válido.', 'danger');
            redirect('/messages');
        }

        if ($receiverId === $senderId) {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'error' => 'No puedes enviarte mensajes a ti mismo.'], 400);
            }
            flash('messages', 'No puedes enviarte mensajes a ti mismo.', 'warning');
            redirect('/messages');
        }

        if (strlen($content) < 1 || strlen($content) > 1000) {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'error' => 'El mensaje debe tener entre 1 y 1000 caracteres.'], 400);
            }
            flash('messages', 'El mensaje debe tener entre 1 y 1000 caracteres.', 'danger');
            redirect('/messages/' . $receiverId);
        }

        $messageId = $this->messageModel->send($senderId, $receiverId, $content);

        $this->notificationModel->create(
            $receiverId,
            'message',
            $user['username'] . ' te ha enviado un mensaje',
            $senderId
        );

        PushHelper::sendNotification(
            [$receiverId],
            'Nuevo mensaje',
            '@' . $user['username'] . ' te ha escrito',
            '/messages/' . $senderId
        );

        if ($this->isAjax()) {
            $row = $this->messageModel->findById($messageId);
            $this->json([
                'success' => true,
                'message' => [
                    'id'         => $messageId,
                    'content'    => $content,
                    'created_at' => $row['created_at'] ?? date('Y-m-d H:i:s'),
                    'sender_id'  => $senderId,
                ],
            ]);
        }

        redirect('/messages/' . $receiverId);
    }

    private function isAjax(): bool
    {
        return strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
    }

    private function json(array $data, int $code = 200): never
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
