<?php

declare(strict_types=1);

class FriendController
{
    private FriendModel $friendModel;
    private UserModel $userModel;
    private NotificationModel $notificationModel;

    public function __construct()
    {
        requireLogin();
        $this->friendModel       = new FriendModel();
        $this->userModel         = new UserModel();
        $this->notificationModel = new NotificationModel();
    }

    public function index(): void
    {
        $userId = (int)currentUser()['id'];

        $friends        = $this->friendModel->getFriends($userId);
        $pendingReceived = $this->friendModel->getPendingReceived($userId);
        $pendingSent    = $this->friendModel->getPendingSent($userId);
        $suggestions    = $this->friendModel->getSuggestions($userId);

        require_once VIEWS_PATH . '/friends/index.php';
    }

    public function sendRequest(): void
    {
        verifyCsrf();

        $user       = currentUser();
        $receiverId = (int)($_POST['receiver_id'] ?? 0);

        if ($receiverId < 1) {
            flash('friends', 'Usuario no válido.', 'danger');
            redirect('/friends');
        }

        if ($receiverId === (int)$user['id']) {
            flash('friends', 'No puedes enviarte una solicitud a ti mismo.', 'warning');
            redirect('/friends');
        }

        if (!$this->userModel->findById($receiverId)) {
            flash('friends', 'El usuario no existe.', 'danger');
            redirect('/friends');
        }

        if (!$this->friendModel->sendRequest((int)$user['id'], $receiverId)) {
            flash('friends', 'Ya existe una solicitud o amistad con ese usuario.', 'warning');
            redirect('/friends');
        }

        $this->notificationModel->create(
            $receiverId,
            'friend_request',
            $user['username'] . ' te envió una solicitud de amistad',
            (int)$user['id']
        );

        PushHelper::sendNotification(
            [$receiverId],
            'Nueva solicitud de amistad',
            '@' . $user['username'] . ' quiere ser tu amigo',
            '/friends'
        );

        flash('friends', 'Solicitud de amistad enviada.');
        redirect('/friends');
    }

    public function accept(): void
    {
        verifyCsrf();

        $user     = currentUser();
        $senderId = (int)($_POST['sender_id'] ?? 0);

        if ($senderId < 1) {
            flash('friends', 'Solicitud no válida.', 'danger');
            redirect('/friends');
        }

        if (!$this->friendModel->accept((int)$user['id'], $senderId)) {
            flash('friends', 'No se pudo aceptar la solicitud.', 'danger');
            redirect('/friends');
        }

        $this->notificationModel->create(
            $senderId,
            'friend_request',
            $user['username'] . ' aceptó tu solicitud de amistad',
            (int)$user['id']
        );

        PushHelper::sendNotification(
            [$senderId],
            'Solicitud aceptada',
            '@' . $user['username'] . ' aceptó tu solicitud de amistad',
            '/friends'
        );

        flash('friends', '¡Amistad aceptada!');
        redirect('/friends');
    }

    public function reject(): void
    {
        verifyCsrf();

        $user     = currentUser();
        $senderId = (int)($_POST['sender_id'] ?? 0);

        if ($senderId < 1) {
            flash('friends', 'Solicitud no válida.', 'danger');
            redirect('/friends');
        }

        if (!$this->friendModel->reject((int)$user['id'], $senderId)) {
            flash('friends', 'No se pudo rechazar la solicitud.', 'danger');
            redirect('/friends');
        }

        flash('friends', 'Solicitud rechazada.');
        redirect('/friends');
    }

    public function unfriend(): void
    {
        verifyCsrf();

        $user        = currentUser();
        $otherUserId = (int)($_POST['other_user_id'] ?? 0);

        if ($otherUserId < 1) {
            flash('friends', 'Usuario no válido.', 'danger');
            redirect('/friends');
        }

        if ($otherUserId === (int)$user['id']) {
            flash('friends', 'Operación no válida.', 'warning');
            redirect('/profile');
        }

        if (!$this->friendModel->unfriend((int)$user['id'], $otherUserId)) {
            flash('friends', 'No se pudo eliminar la amistad.', 'danger');
            redirect('/profile/' . $otherUserId);
        }

        flash('friends', 'Amistad eliminada.');
        redirect('/profile/' . $otherUserId);
    }
}
