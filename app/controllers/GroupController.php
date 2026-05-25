<?php

declare(strict_types=1);

class GroupController
{
    private GroupModel $groupModel;
    private NotificationModel $notificationModel;

    public function __construct()
    {
        requireLogin();
        $this->groupModel          = new GroupModel();
        $this->notificationModel   = new NotificationModel();
    }

    public function index(): void
    {
        $userId = (int)currentUser()['id'];
        $page   = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 9;

        $total = $this->groupModel->countAll();
        $pag   = paginate($total, $perPage, $page);

        $groups = $this->groupModel->getAll(
            $userId,
            $pag['perPage'],
            $pag['offset']
        );

        require_once VIEWS_PATH . '/groups/index.php';
    }

    public function create(): void
    {
        require_once VIEWS_PATH . '/groups/create.php';
    }

    public function store(): void
    {
        verifyCsrf();

        $user = currentUser();

        $name        = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $privacy     = $_POST['privacy'] ?? 'public';

        if (strlen($name) < 3 || strlen($name) > 100) {
            flash('groups', 'El nombre debe tener entre 3 y 100 caracteres.', 'danger');
            redirect('/groups/create');
        }

        if (strlen($description) > 1000) {
            flash('groups', 'La descripción no puede superar 1000 caracteres.', 'danger');
            redirect('/groups/create');
        }

        if (!in_array($privacy, ['public', 'private'], true)) {
            $privacy = 'public';
        }

        $avatar = null;
        if (!empty($_FILES['avatar']['name'])) {
            $avatar = uploadImage($_FILES['avatar'], 'groups');
            if ($avatar === null) {
                flash('groups', 'El avatar no es válido.', 'danger');
                redirect('/groups/create');
            }
        }

        $groupId = $this->groupModel->create(
            (int)$user['id'],
            $name,
            $description !== '' ? $description : null,
            $privacy,
            $avatar
        );

        flash('groups', 'Grupo creado correctamente.');
        redirect('/groups/' . $groupId);
    }

    public function show(int $id): void
    {
        $user  = currentUser();
        $userId = (int)$user['id'];

        $group = $this->groupModel->findById($id);
        if (!$group) {
            http_response_code(404);
            require_once VIEWS_PATH . '/404.php';
            return;
        }

        $isMember = $this->groupModel->isMember($id, $userId);
        $isAdmin  = $this->groupModel->isAdmin($id, $userId);
        $canViewPosts = $isMember || $group['privacy'] === 'public';

        $members = $this->groupModel->getMembers($id, 10);

        $posts = [];
        $pag   = paginate(0, 10, 1);

        if ($canViewPosts) {
            $total = $this->groupModel->countPosts($id);
            $page  = max(1, (int)($_GET['page'] ?? 1));
            $pag   = paginate($total, 10, $page);

            $posts = $this->groupModel->getPosts($id, $pag['perPage'], $pag['offset']);
            foreach ($posts as &$post) {
                $post['comments'] = $this->groupModel->getPostComments((int)$post['id'], 10);
            }
            unset($post);
        }

        $canLeave = $isMember && !($isAdmin && $this->groupModel->countAdmins($id) <= 1);

        require_once VIEWS_PATH . '/groups/show.php';
    }

    public function join(): void
    {
        verifyCsrf();

        $user    = currentUser();
        $userId  = (int)$user['id'];
        $groupId = (int)($_POST['group_id'] ?? 0);

        $group = $this->groupModel->findById($groupId);
        if (!$group) {
            flash('groups', 'Grupo no encontrado.', 'danger');
            redirect('/groups');
        }

        if ($this->groupModel->isMember($groupId, $userId)) {
            flash('groups', 'Ya eres miembro de este grupo.', 'warning');
            redirect('/groups/' . $groupId);
        }

        if (!$this->groupModel->join($groupId, $userId)) {
            flash('groups', 'No se pudo unir al grupo.', 'danger');
            redirect('/groups/' . $groupId);
        }

        if ((int)$group['creator_id'] !== $userId) {
            $this->notificationModel->create(
                (int)$group['creator_id'],
                'group',
                $user['username'] . ' se unió al grupo «' . $group['name'] . '»',
                $groupId
            );

            PushHelper::sendNotification(
                [(int)$group['creator_id']],
                'Nuevo miembro en el grupo',
                '@' . $user['username'] . ' se unió al grupo «' . $group['name'] . '»',
                '/groups/' . $groupId
            );
        }

        flash('groups', 'Te has unido al grupo.');
        redirect('/groups/' . $groupId);
    }

    public function leave(): void
    {
        verifyCsrf();

        $userId  = (int)currentUser()['id'];
        $groupId = (int)($_POST['group_id'] ?? 0);

        if (!$this->groupModel->leave($groupId, $userId)) {
            flash('groups', 'No puedes salir: eres el único administrador del grupo.', 'warning');
            redirect('/groups/' . $groupId);
        }

        flash('groups', 'Has abandonado el grupo.');
        redirect('/groups');
    }

    public function addPost(): void
    {
        verifyCsrf();

        $user    = currentUser();
        $userId  = (int)$user['id'];
        $groupId = (int)($_POST['group_id'] ?? 0);
        $content = trim($_POST['content'] ?? '');

        if (!$this->groupModel->isMember($groupId, $userId)) {
            flash('groups', 'Debes ser miembro para publicar.', 'danger');
            redirect('/groups/' . $groupId);
        }

        if (strlen($content) < 1 || strlen($content) > 2000) {
            flash('groups', 'El contenido debe tener entre 1 y 2000 caracteres.', 'danger');
            redirect('/groups/' . $groupId);
        }

        $image = null;
        if (!empty($_FILES['image']['name'])) {
            $image = uploadImage($_FILES['image'], 'groups');
            if ($image === null) {
                flash('groups', 'La imagen no es válida.', 'danger');
                redirect('/groups/' . $groupId);
            }
        }

        $this->groupModel->addPost($groupId, $userId, $content, $image);

        flash('groups', 'Publicación añadida al grupo.');
        redirect('/groups/' . $groupId);
    }

    public function deletePost(): void
    {
        verifyCsrf();

        $user    = currentUser();
        $userId  = (int)$user['id'];
        $groupId = (int)($_POST['group_id'] ?? 0);
        $postId  = (int)($_POST['post_id'] ?? 0);

        if (!$this->groupModel->deletePost($postId, $userId, $groupId)) {
            flash('groups', 'No se pudo eliminar la publicación.', 'danger');
            redirect('/groups/' . $groupId);
        }

        flash('groups', 'Publicación eliminada.');
        redirect('/groups/' . $groupId);
    }

    public function addComment(): void
    {
        verifyCsrf();

        $user    = currentUser();
        $userId  = (int)$user['id'];
        $postId  = (int)($_POST['group_post_id'] ?? 0);
        $comment = trim($_POST['comment'] ?? '');

        if ($postId < 1) {
            $this->json(['success' => false, 'error' => 'Publicación no válida.'], 400);
        }

        if (strlen($comment) < 1 || strlen($comment) > 1000) {
            $this->json(['success' => false, 'error' => 'El comentario debe tener entre 1 y 1000 caracteres.'], 400);
        }

        $post = $this->groupModel->findPostById($postId);
        if (!$post) {
            $this->json(['success' => false, 'error' => 'La publicación no existe.'], 404);
        }

        $groupId = (int)$post['group_id'];
        if (!$this->groupModel->isMember($groupId, $userId)) {
            $this->json(['success' => false, 'error' => 'Debes ser miembro para comentar.'], 403);
        }

        $this->groupModel->addComment($postId, $userId, $comment);

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
