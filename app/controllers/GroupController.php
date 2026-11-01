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
        $user     = currentUser();
        $userId   = (int)$user['id'];
        $isSiteAdmin = ($user['role'] ?? '') === 'admin';
        $page     = max(1, (int)($_GET['page'] ?? 1));
        $perPage  = 9;

        $total = $this->groupModel->countAll($userId, $isSiteAdmin);
        $pag   = paginate($total, $perPage, $page);

        $groups = $this->groupModel->getAll(
            $userId,
            $pag['perPage'],
            $pag['offset'],
            $isSiteAdmin
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

        if (mb_strlen($name) < 3 || mb_strlen($name) > 100) {
            flash('groups', 'El nombre debe tener entre 3 y 100 caracteres.', 'danger');
            redirect('/groups/create');
        }

        if (mb_strlen($description) > 1000) {
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

        $isMember    = $this->groupModel->isMember($id, $userId);
        $isInvited   = !$isMember && $this->groupModel->isInvited($id, $userId);
        $isAdmin     = $this->groupModel->isAdmin($id, $userId);
        $isSiteAdmin = ($user['role'] ?? '') === 'admin';

        // Los grupos privados son invisibles salvo para miembros, invitados y administradores del sitio
        if ($group['privacy'] === 'private' && !$isMember && !$isInvited && !$isSiteAdmin) {
            flash('groups', 'Este grupo es privado.', 'warning');
            redirect('/groups');
        }

        $canViewPosts = $isMember || $group['privacy'] === 'public' || $isSiteAdmin;

        $invitableFriends = [];
        $invitedUsers     = [];
        if ($isAdmin && $group['privacy'] === 'private') {
            $invitableFriends = $this->groupModel->getInvitableFriends($id, $userId);
            $invitedUsers     = $this->groupModel->getInvited($id);
        }

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

    /** Solo grupos públicos. A los privados se entra por invitación. */
    public function join(): void
    {
        verifyCsrf();

        $user    = currentUser();
        $userId  = (int)$user['id'];
        $groupId = (int)($_POST['group_id'] ?? 0);

        $group = $this->groupModel->findById($groupId);
        // Un grupo privado responde igual que uno inexistente: no se revela que existe
        if (!$group || $group['privacy'] === 'private') {
            flash('groups', 'Grupo no encontrado.', 'danger');
            redirect('/groups');
        }

        if (!$this->groupModel->join($groupId, $userId)) {
            flash('groups', 'Ya eres miembro de este grupo.', 'warning');
            redirect('/groups/' . $groupId);
        }

        $adminIds = array_values(array_diff($this->groupModel->getAdminIds($groupId), [$userId]));
        $text = $user['username'] . ' se unió al grupo «' . $group['name'] . '»';
        foreach ($adminIds as $adminId) {
            $this->notificationModel->create($adminId, 'group', $text, $groupId);
        }
        PushHelper::sendNotification($adminIds, 'Nuevo miembro en el grupo', '@' . $text, '/groups/' . $groupId);

        flash('groups', 'Te has unido al grupo.');
        redirect('/groups/' . $groupId);
    }

    /** Un administrador de un grupo privado invita a uno de sus amigos. */
    public function invite(): void
    {
        verifyCsrf();

        $user     = currentUser();
        $userId   = (int)$user['id'];
        $groupId  = (int)($_POST['group_id'] ?? 0);
        $friendId = (int)($_POST['user_id'] ?? 0);

        $group = $this->groupModel->findById($groupId);
        if (!$group || !$this->groupModel->isAdmin($groupId, $userId)) {
            flash('groups', 'Solo los administradores del grupo pueden invitar.', 'danger');
            redirect('/groups');
        }
        if ($group['privacy'] !== 'private') {
            flash('groups', 'Los grupos públicos no necesitan invitación.', 'warning');
            redirect('/groups/' . $groupId);
        }

        // Solo se puede invitar a amigos propios que aún no estén en el grupo
        $invitable = array_map('intval', array_column(
            $this->groupModel->getInvitableFriends($groupId, $userId), 'id'
        ));
        if (!in_array($friendId, $invitable, true) || !$this->groupModel->invite($groupId, $friendId)) {
            flash('groups', 'No se puede invitar a ese usuario.', 'danger');
            redirect('/groups/' . $groupId);
        }

        $text = $user['username'] . ' te ha invitado al grupo «' . $group['name'] . '»';
        $this->notificationModel->create($friendId, 'group', $text, $groupId);
        PushHelper::sendNotification([$friendId], 'Invitación a un grupo', '@' . $text, '/groups/' . $groupId);

        flash('groups', 'Invitación enviada.');
        redirect('/groups/' . $groupId);
    }

    public function acceptInvite(): void
    {
        verifyCsrf();

        $userId  = (int)currentUser()['id'];
        $groupId = (int)($_POST['group_id'] ?? 0);

        if ($this->groupModel->acceptInvite($groupId, $userId)) {
            flash('groups', '¡Bienvenido al grupo!');
            redirect('/groups/' . $groupId);
        }

        flash('groups', 'La invitación ya no existe.', 'warning');
        redirect('/groups');
    }

    /** La persona invitada rechaza la invitación, o un admin del grupo la cancela. */
    public function removeInvite(): void
    {
        verifyCsrf();

        $userId   = (int)currentUser()['id'];
        $groupId  = (int)($_POST['group_id'] ?? 0);
        $targetId = (int)($_POST['user_id'] ?? $userId);

        $isSelf = $targetId === $userId;
        if (!$isSelf && !$this->groupModel->isAdmin($groupId, $userId)) {
            flash('groups', 'No tienes permiso para cancelar esa invitación.', 'danger');
            redirect('/groups');
        }

        $this->groupModel->removeInvite($groupId, $targetId);

        if ($isSelf) {
            flash('groups', 'Invitación rechazada.');
            redirect('/groups');
        }
        flash('groups', 'Invitación cancelada.');
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

        if (mb_strlen($content) < 1 || mb_strlen($content) > 2000) {
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
            jsonResponse(['success' => false, 'error' => 'Publicación no válida.'], 400);
        }

        if (mb_strlen($comment) < 1 || mb_strlen($comment) > 1000) {
            jsonResponse(['success' => false, 'error' => 'El comentario debe tener entre 1 y 1000 caracteres.'], 400);
        }

        $post = $this->groupModel->findPostById($postId);
        if (!$post) {
            jsonResponse(['success' => false, 'error' => 'La publicación no existe.'], 404);
        }

        $groupId = (int)$post['group_id'];
        if (!$this->groupModel->isMember($groupId, $userId)) {
            jsonResponse(['success' => false, 'error' => 'Debes ser miembro para comentar.'], 403);
        }

        $this->groupModel->addComment($postId, $userId, $comment);

        jsonResponse([
            'success'  => true,
            'username' => $user['username'],
            'avatar'   => $user['avatar'],
            'comment'  => $comment,
        ]);
    }
}
