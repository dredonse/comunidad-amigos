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

        $friendModel = new FriendModel();
        $friends     = $friendModel->getFriends($userId);

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

        $wsUrl   = env('WS_PUBLIC_URL', 'ws://localhost:8090');
        $wsToken = wsToken($userId);

        require_once VIEWS_PATH . '/messages/conversation.php';
    }

    /**
     * Único punto de entrada para enviar mensajes: sesión, CSRF, validación,
     * notificación, push y, por último, aviso al servidor WebSocket para la entrega en vivo.
     */
    public function send(): void
    {
        verifyCsrf();

        $user       = currentUser();
        $senderId   = (int)$user['id'];
        $receiverId = (int)($_POST['receiver_id'] ?? 0);
        $content    = trim((string)($_POST['content'] ?? ''));

        $receiver = $receiverId > 0 ? $this->userModel->findById($receiverId) : null;
        if (!$receiver || !$receiver['active']) {
            $this->fail('Usuario no válido.', '/messages');
        }

        if ($receiverId === $senderId) {
            $this->fail('No puedes enviarte mensajes a ti mismo.', '/messages');
        }

        if (mb_strlen($content) < 1 || mb_strlen($content) > 1000) {
            $this->fail('El mensaje debe tener entre 1 y 1000 caracteres.', '/messages/' . $receiverId);
        }

        $messageId = $this->messageModel->send($senderId, $receiverId, $content);
        $row       = $this->messageModel->findById($messageId);
        $createdAt = $row['created_at'] ?? date('Y-m-d H:i:s');

        $this->notificationModel->create(
            $receiverId,
            'message',
            $user['username'] . ' te ha enviado un mensaje',
            $senderId
        );

        RealtimeNotifier::messageCreated($messageId, $senderId, $receiverId, $content, $createdAt);

        PushHelper::sendNotification(
            [$receiverId],
            'Nuevo mensaje',
            '@' . $user['username'] . ' te ha escrito',
            '/messages/' . $senderId
        );

        if (isAjax()) {
            jsonResponse([
                'success' => true,
                'message' => [
                    'id'         => $messageId,
                    'content'    => $content,
                    'created_at' => $createdAt,
                    'sender_id'  => $senderId,
                ],
            ]);
        }

        redirect('/messages/' . $receiverId);
    }

    /** El chat abierto avisa de que ha mostrado mensajes recibidos en vivo. */
    public function markRead(): void
    {
        verifyCsrf();

        $otherUserId = (int)($_POST['other_user_id'] ?? 0);
        if ($otherUserId > 0) {
            $this->messageModel->markConversationRead((int)currentUser()['id'], $otherUserId);
        }

        jsonResponse(['success' => true]);
    }

    private function fail(string $error, string $redirectTo): never
    {
        if (isAjax()) {
            jsonResponse(['success' => false, 'error' => $error], 400);
        }
        flash('messages', $error, 'danger');
        redirect($redirectTo);
    }
}
