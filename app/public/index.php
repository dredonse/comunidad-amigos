<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

// Router simple basado en la URL
$uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// Normalizar URI
$uri = rtrim($uri, '/') ?: '/';

// Tabla de rutas: [método, patrón, controlador, acción]
$routes = [
    // Auth
    ['GET',  '/login',    'AuthController', 'showLogin'],
    ['POST', '/login',    'AuthController', 'login'],
    ['GET',  '/register', 'AuthController', 'showRegister'],
    ['POST', '/register', 'AuthController', 'register'],
    ['GET',  '/logout',   'AuthController', 'logout'],

    // Feed principal
    ['GET',  '/feed',     'FeedController', 'index'],
    ['GET',  '/',         'FeedController', 'index'],
    ['POST', '/post',     'FeedController', 'store'],
    ['POST', '/post/delete', 'FeedController', 'delete'],

    // Perfil
    ['GET',  '/profile',        'ProfileController', 'show'],
    ['GET',  '/profile/(\d+)',  'ProfileController', 'show'],
    ['POST', '/profile/update', 'ProfileController', 'update'],

    // Amigos
    ['GET',  '/friends',         'FriendController', 'index'],
    ['POST', '/friends/request', 'FriendController', 'sendRequest'],
    ['POST', '/friends/accept',  'FriendController', 'accept'],
    ['POST', '/friends/reject',  'FriendController', 'reject'],
    ['POST', '/friends/unfriend', 'FriendController', 'unfriend'],

    // Eventos
    ['GET',  '/events',         'EventController', 'index'],
    ['GET',  '/events/create',  'EventController', 'create'],
    ['POST', '/events/store',   'EventController', 'store'],
    ['POST', '/events/attend',  'EventController', 'attend'],
    ['POST', '/events/cancel',  'EventController', 'cancel'],
    ['POST', '/events/delete',  'EventController', 'delete'],
    ['GET',  '/events/(\d+)',   'EventController', 'show'],

    // Grupos
    ['GET',  '/groups',              'GroupController', 'index'],
    ['GET',  '/groups/create',       'GroupController', 'create'],
    ['POST', '/groups/store',        'GroupController', 'store'],
    ['POST', '/groups/join',         'GroupController', 'join'],
    ['POST', '/groups/leave',        'GroupController', 'leave'],
    ['POST', '/groups/post',         'GroupController', 'addPost'],
    ['POST', '/groups/post/delete',  'GroupController', 'deletePost'],
    ['GET',  '/groups/(\d+)',        'GroupController', 'show'],

    // Mensajes
    ['GET',  '/messages',          'MessageController', 'index'],
    ['GET',  '/messages/(\d+)',    'MessageController', 'conversation'],
    ['POST', '/messages/send',     'MessageController', 'send'],

    // Notificaciones
    ['GET',  '/notifications',      'NotificationController', 'index'],
    ['POST', '/notifications/read', 'NotificationController', 'markRead'],

    // Admin
    ['GET',  '/admin',               'AdminController', 'index'],
    ['GET',  '/admin/users',         'AdminController', 'users'],
    ['POST', '/admin/user/toggle',   'AdminController', 'toggleUser'],
    ['POST', '/admin/user/delete',   'AdminController', 'deleteUser'],
    ['POST', '/admin/post/delete',   'AdminController', 'deletePost'],

    // AJAX - Comentarios y likes
    ['POST', '/ajax/comment',       'CommentController', 'store'],
    ['POST', '/ajax/group/comment', 'GroupController', 'addComment'],
    ['POST', '/ajax/like',            'LikeController',    'toggle'],
];

$matched = false;

foreach ($routes as [$routeMethod, $pattern, $controller, $action]) {
    // Escapar el patrón para regex
    $regex = '@^' . $pattern . '$@';

    if ($method === $routeMethod && preg_match($regex, $uri, $matches)) {
        $matched = true;
        array_shift($matches); // quitar coincidencia completa

        // Cargar controlador
        $file = BASE_PATH . '/controllers/' . $controller . '.php';
        if (!file_exists($file)) {
            http_response_code(500);
            die("Controlador {$controller} no encontrado.");
        }
        require_once $file;

        $ctrl = new $controller();
        $params = array_map(
            static fn (string $param): int|string => ctype_digit($param) ? (int) $param : $param,
            $matches
        );
        $ctrl->$action(...$params);
        break;
    }
}

if (!$matched) {
    http_response_code(404);
    require_once VIEWS_PATH . '/404.php';
}
