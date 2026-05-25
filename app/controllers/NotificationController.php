<?php

declare(strict_types=1);

class NotificationController
{
    private NotificationModel $notificationModel;

    public function __construct()
    {
        requireLogin();
        $this->notificationModel = new NotificationModel();
    }

    public function index(): void
    {
        $userId  = (int)currentUser()['id'];
        $page    = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 20;

        $total = $this->notificationModel->countAll($userId);
        $pag   = paginate($total, $perPage, $page);

        $notifications = $this->notificationModel->getForUser(
            $userId,
            $pag['perPage'],
            $pag['offset']
        );

        $unreadCount = $this->notificationModel->countUnread($userId);

        require_once VIEWS_PATH . '/notifications/index.php';
    }

    public function markRead(): void
    {
        verifyCsrf();

        $userId = (int)currentUser()['id'];

        if (!empty($_POST['mark_all'])) {
            $this->notificationModel->markAllRead($userId);
            flash('notifications', 'Todas las notificaciones marcadas como leídas.');
        } else {
            $notificationId = (int)($_POST['notification_id'] ?? 0);
            if ($notificationId < 1) {
                flash('notifications', 'Notificación no válida.', 'danger');
                redirect('/notifications');
            }

            if (!$this->notificationModel->markRead($notificationId, $userId)) {
                flash('notifications', 'No se pudo marcar la notificación.', 'danger');
                redirect('/notifications');
            }

            flash('notifications', 'Notificación marcada como leída.');
        }

        redirect('/notifications');
    }
}
