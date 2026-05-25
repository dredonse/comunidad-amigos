# Comunidad de Amigos 🏘️

Red social privada desarrollada con PHP 8.3, MariaDB y Docker.

## 🚀 Inicio rápido

```bash
# 1. Clonar / descomprimir el proyecto
# 2. Copiar y editar variables de entorno
cp .env .env.local   # ajusta credenciales si es necesario

# 3. Levantar contenedores
docker compose up -d --build

# 4. Abrir en el navegador
#    Aplicación:  http://localhost:8080
#    phpMyAdmin:  http://localhost:8081
```

## 🔑 Credenciales por defecto

| Rol   | Email                    | Contraseña  |
|-------|--------------------------|-------------|
| Admin | admin@comunidad.local    | Admin1234!  |

> Cámbiala en cuanto inicies sesión.

## 📁 Estructura

```
app/
├── public/         # Document root (index.php, .htaccess, assets)
├── controllers/    # Lógica de cada sección
├── models/         # Acceso a base de datos (PDO)
├── views/          # Plantillas PHP
├── helpers/        # Funciones globales
└── config/         # BD y configuración de app
docker/
├── php/Dockerfile
└── mysql/init.sql  # Schema inicial
```

## 🛡️ Seguridad implementada

- `password_hash()` / `password_verify()` con bcrypt cost 12
- PDO con prepared statements (anti SQL Injection)
- Escape HTML con `htmlspecialchars()` (anti XSS)
- Tokens CSRF en todos los formularios
- Validación de archivos subidos (tipo + tamaño + `getimagesize`)
- `session_regenerate_id()` al autenticarse
- Cabeceras de seguridad vía `.htaccess`
- Variables sensibles en `.env` (no en código)

## 📔 Diario de desarrollo

### Sesión 2 — Módulo Feed

**Fecha:** 25/05/2025  
**Estado:** ✅ Completado

**Objetivos:**  
Implementar el feed principal con publicaciones, likes y comentarios en tiempo real.

**Trabajo realizado:**

**PostModel**
- `getFeed` / `countFeed`: publicaciones propias + amigos con `status = accepted`, con subconsultas para `like_count`, `comment_count` y `user_liked`.
- `create`, `findById`, `delete`: CRUD básico de posts.
- `getComments`: JOIN con users, limit configurable.
- `addComment`: INSERT en comments.
- `toggleLike`: INSERT o DELETE según exista, devuelve `liked` + `count`.

**Controladores**
- `FeedController`: paginación de 10 posts por página, hasta 3 comentarios por post en la carga inicial. `store` valida contenido (1-2000 chars) e imagen opcional. `delete` comprueba que sea owner o admin y borra el archivo.
- `CommentController`: endpoint AJAX, responde JSON con username, avatar y comment.
- `LikeController`: endpoint AJAX, responde JSON con liked y count.

**Vistas**
- `feed.php`: layout 8+4, formulario de publicación, loop de posts, sidebar con perfil y paginación.
- `_post.php`: tarjeta reutilizable con avatar, contenido, imagen opcional, contadores, botones de like y comentar, sección de comentarios colapsable y modal para ampliar imagen.

**Incidencias resueltas**
- DocumentRoot de Apache apuntaba a `app/` en vez de `app/public/`, mostraba el árbol de carpetas. Corregido en el Dockerfile.
- Hash bcrypt del admin en `init.sql` era un placeholder incorrecto. Regenerado y aplicado con `docker compose down -v` + `up --build`.
- Error «Token CSRF inválido» por pérdida de cookie de sesión entre GET y POST. Corregido fijando `session.cookie_path = /` y `session.use_only_cookies = 1` en `config/app.php`.

**Archivos creados / modificados:**
```
app/models/PostModel.php
app/controllers/FeedController.php
app/controllers/CommentController.php
app/controllers/LikeController.php
app/views/posts/feed.php
app/views/posts/_post.php
docker/php/Dockerfile           (modificado: DocumentRoot)
docker/mysql/init.sql           (modificado: hash admin)
app/config/app.php              (modificado: configuración sesión)
```

### Sesión 3 — Módulo Amigos

**Fecha:** 25/05/2025  
**Estado:** ✅ Completado

**Objetivos:**  
Implementar el sistema de amigos: solicitudes, aceptación, rechazo y sugerencias. Sin amigos el feed solo mostraba publicaciones propias.

**Trabajo realizado:**

**FriendModel**
- `getFriends`: amigos aceptados en cualquier dirección (sender o receiver).
- `getPendingReceived` / `getPendingSent`: solicitudes pendientes entrantes y salientes.
- `getStatus`: estado de la relación entre dos usuarios.
- `sendRequest`: INSERT comprobando que no existe relación previa.
- `accept` / `reject`: UPDATE de status con validación de dirección.
- `unfriend`: DELETE en cualquier dirección (listo para perfil).
- `getSuggestions`: usuarios sin relación previa, ORDER BY RAND().
- `countPending`: contador para el badge de la navbar.

**FriendController**
- `index`: carga amigos, pendientes, enviadas y sugerencias.
- `sendRequest` / `accept` / `reject`: con verifyCsrf y flash.

**Vistas**
- `friends/index.php`: layout 8+4 con solicitudes recibidas, listado de amigos, solicitudes enviadas y sugerencias.
- `layout/header.php`: badge rojo en el enlace de Amigos con el número de solicitudes pendientes recibidas.

**Flujo verificado**
- Registro de segundo usuario.
- Envío de solicitud desde Sugerencias.
- Aceptación desde la cuenta del segundo usuario.
- Feed del admin muestra publicaciones del segundo usuario tras aceptar la amistad.

**Archivos creados / modificados:**
```
app/models/FriendModel.php
app/controllers/FriendController.php
app/views/friends/index.php
app/views/layout/header.php    (modificado: badge solicitudes)
```

### Sesión 4 — Módulo Perfiles

**Fecha:** 25/05/2025  
**Estado:** ✅ Completado

**Objetivos:**  
Implementar perfiles de usuario propios y ajenos con edición de bio, avatar y contraseña, y acciones de amistad contextuales.

**Trabajo realizado:**

**ProfileController**
- `show()`: perfil propio si `id=0` o `id=userId`, ajeno en caso contrario. Carga posts paginados, estado de amistad y contador de amigos.
- `update()`: edición de bio (máx 300 chars), avatar con borrado del anterior si no es `default.png` y actualización de sesión, cambio de contraseña opcional con verificación de la actual.

**Vistas**
- `profile/show.php`: layout 4+8. Tarjeta izquierda con avatar, bio, contadores y fecha de alta. Formulario colapsable de edición en perfil propio con preview de avatar en tiempo real. Botones de amistad contextuales en perfil ajeno (añadir, solicitud enviada, aceptar/rechazar, eliminar amigo). Publicaciones paginadas con `_post.php`. Formulario de nueva publicación solo en perfil propio.

**Otros cambios**
- `PostModel`: añadido `countByUser()`.
- `UserModel`: añadido `getPasswordHash()` para validar contraseña actual antes de cambiarla.
- `FriendController`: añadido `unfriend()`.
- `index.php`: añadida ruta `POST /friends/unfriend`.
- `app.css`: añadida clase `.cursor-pointer`.

**Flujo verificado**
- Edición de bio y avatar desde `/profile`.
- Cambio visible en `/profile/2` desde el segundo usuario.
- Acciones de amistad contextuales según el estado.

**Archivos creados / modificados:**
```
app/controllers/ProfileController.php
app/views/profile/show.php
app/models/PostModel.php              (modificado: countByUser)
app/models/UserModel.php              (modificado: getPasswordHash)
app/controllers/FriendController.php  (modificado: unfriend)
app/public/index.php                  (modificado: ruta unfriend)
app/public/css/app.css                (modificado: cursor-pointer)
```

### Sesión 5 — Módulo Eventos

**Fecha:** 25/05/2025  
**Estado:** ✅ Completado

**Objetivos:**  
Implementar el módulo de eventos y quedadas con creación, detalle, asistencia y eliminación.

**Trabajo realizado:**

**EventModel**
- `getAll`: eventos ordenados por fecha ascendente con datos del creador, total de asistentes `going` y estado del usuario.
- `findById`: evento con contadores por status.
- `create`: INSERT con alta automática del creador como `going`.
- `getAttendees`: filtrable por status, JOIN con users.
- `attend`: INSERT o UPDATE con ON DUPLICATE KEY.
- `cancelAttendance`: DELETE de `event_attendees`.
- `isCreator` / `delete`: control de permisos.

**EventController**
- `index`: grid paginado de 9 eventos.
- `show`: detalle con asistentes `going` y `maybe`.
- `create` / `store`: formulario con validaciones (título, fecha futura, imagen opcional en `uploads/events/`).
- `attend` / `cancel`: cambio de estado de asistencia.
- `delete`: solo creador o admin, borra imagen del disco.

**Vistas**
- `events/index.php`: grid 3 columnas con imagen, fecha, título, ubicación, contador `going` y badge de estado.
- `events/show.php`: layout 8+4, imagen completa, descripción, sidebar con botones Voy/Quizás/No voy y lista de asistentes.
- `events/create.php`: formulario con `datetime-local`, validación HTML5.

**Otros cambios**
- `index.php`: rutas `POST /events/attend`, `/events/cancel`, `/events/delete`. Ruta `GET /events/create` antes de `/events/(\d+)` para evitar conflicto. Conversión de parámetros numéricos de ruta a `int` en el router.

**Flujo verificado**
- Crear evento con imagen y fecha futura.
- Cambio de estado Voy/Quizás/No voy.
- Badge actualizado en el listado.
- Segundo usuario puede apuntarse al mismo evento.

**Archivos creados / modificados:**
```
app/models/EventModel.php
app/controllers/EventController.php
app/views/events/index.php
app/views/events/show.php
app/views/events/create.php
app/public/index.php    (modificado: nuevas rutas)
```

### Sesión 6 — Módulo Notificaciones

**Fecha:** 25/05/2025  
**Estado:** ✅ Completado

**Objetivos:**  
Implementar el sistema de notificaciones integrado con amigos, comentarios, likes y mensajes.

**Trabajo realizado:**

**NotificationModel**
- CRUD completo, listado paginado y contadores.
- `markRead`: marcar una o todas como leídas.

**NotificationController**
- `index`: listado con paginación.
- `markRead`: una notificación o todas a la vez.

**Vistas**
- `notifications/index.php`: listado con botón marcar leída y botón marcar todas.

**Integraciones**
- `FriendController`: notifica solicitud enviada y aceptación.
- `CommentController`: notifica al autor del post comentado.
- `LikeController`: notifica al autor del post al dar like.
- `helpers/functions.php`: añadidas `notificationUrl()` y `notificationIcon()`.
- `layout/header.php`: badge en la campana con no leídas.

**Flujo verificado**
- Solicitud de amistad genera notificación al receptor.
- Aceptación genera notificación al emisor.
- Comentario y like en post ajeno generan notificación al autor.

**Archivos creados / modificados:**
```
app/models/NotificationModel.php
app/controllers/NotificationController.php
app/views/notifications/index.php
app/helpers/functions.php              (modificado: helpers de notificación)
app/views/layout/header.php            (modificado: badge campana)
app/controllers/FriendController.php   (modificado)
app/controllers/CommentController.php  (modificado)
app/controllers/LikeController.php     (modificado)
```

### Sesión 7 — Módulo Mensajería

**Fecha:** 25/05/2025  
**Estado:** ✅ Completado

**Objetivos:**  
Implementar el chat privado entre usuarios con envío en tiempo real por AJAX y notificaciones integradas.

**Trabajo realizado:**

**MessageModel**
- `getConversations`: lista de conversaciones con último mensaje, fecha y no leídos.
- `getMessages`: mensajes paginados entre dos usuarios, marca como leídos los recibidos al cargar.
- `send`: INSERT en `messages`.
- `countUnread`: total no leídos para badge en navbar.

**MessageController**
- `index`: lista de conversaciones.
- `conversation`: chat paginado (50 mensajes), marca leídos al abrir.
- `send`: AJAX responde JSON, fallback a redirect si no es AJAX. Crea notificación al receptor.

**Vistas**
- `messages/index.php`: lista de conversaciones con badge de no leídos.
- `messages/conversation.php`: layout 4+8, burbujas `msg-mine`/`msg-other`, scroll automático al último mensaje.
- `messages/_conversations.php`: partial reutilizable en el sidebar.

**Otros cambios**
- `layout/header.php`: badge en Mensajes con `countUnread()`.
- `app.js`: envío AJAX con `postAjax`, añade burbuja sin recargar.
- `app.css`: estilos del área de chat.

**Flujo verificado**
- Mensajes aparecen sin recargar en tiempo real.
- Badge en navbar del receptor al recibir mensaje.
- Notificación generada al enviar mensaje.
- Conversación visible en `/messages` para ambos usuarios.

**Archivos creados / modificados:**
```
app/models/MessageModel.php
app/controllers/MessageController.php
app/views/messages/index.php
app/views/messages/conversation.php
app/views/messages/_conversations.php
app/views/layout/header.php   (modificado: badge mensajes)
app/public/js/app.js          (modificado: AJAX mensajería)
app/public/css/app.css        (modificado: estilos chat)
```

### Sesión 8 — Panel de Administración

**Fecha:** 25/05/2025  
**Estado:** ✅ Completado

**Objetivos:**  
Implementar el panel de administración con estadísticas globales, gestión de usuarios y moderación de contenido.

**Trabajo realizado:**

**AdminModel**
- `getStats()`: 8 métricas globales (usuarios, posts, comentarios, eventos, mensajes, nuevos esta semana).
- `getRecentUsers` / `getRecentPosts`: últimos 5 de cada uno.
- `getAllUsers`: listado paginado completo.
- `deletePost`: DELETE con borrado de imagen en disco.
- `deleteUser`: DELETE con cascada por FK en BD.

**AdminController**
- Llama `requireAdmin()` en constructor: cualquier método queda protegido automáticamente.
- `index`: dashboard con stats y tablas recientes.
- `users`: gestión paginada de usuarios.
- `toggleUser`: activar/desactivar, no permitido sobre la propia cuenta.
- `deletePost` / `deleteUser`: con validaciones de seguridad.

**Vistas**
- `admin/index.php`: 7 tarjetas de estadísticas con iconos, tabla de últimos usuarios y últimos posts con opción de eliminar.
- `admin/users.php`: tabla completa con rol, estado, acciones de activar/desactivar y eliminar. Paginación.

**Seguridad verificada**
- Enlace al panel solo visible para admin en la navbar.
- Acceso directo por URL a `/admin` deniega con 403 a usuarios no admin.
- No es posible desactivar ni eliminar la propia cuenta.
- Borrado en cascada por claves foráneas en BD.

**Archivos creados / modificados:**
```
app/models/AdminModel.php
app/controllers/AdminController.php
app/views/admin/index.php
app/views/admin/users.php
app/public/index.php    (modificado: nuevas rutas admin)
```

### Sesión 9 — Módulo Grupos

**Fecha:** 25/05/2025  
**Estado:** ✅ Completado

**Objetivos:**  
Implementar el sistema de grupos privados con publicaciones, comentarios y gestión de miembros.

**Trabajo realizado:**

**Base de datos**
- 4 tablas nuevas: `groups`, `group_members`, `group_posts`, `group_post_comments` con claves foráneas y CASCADE.
- Script `docker/mysql/groups.sql` para aplicar en contenedores existentes sin recrear el volumen.

**GroupModel**
- `getAll` / `countAll`: listado paginado con contador de miembros y estado de membresía del usuario.
- `findById`: grupo con datos del creador.
- `create`: INSERT con alta automática del creador como admin.
- `getMembers` / `isMember` / `isAdmin`: consultas de membresía.
- `join` / `leave`: con protección para no dejar grupo sin admin.
- `getPosts` / `countPosts` / `addPost` / `deletePost`: CRUD de publicaciones del grupo.
- `getPostComments` / `addComment`: comentarios en posts de grupo.
- `getUserGroups`: grupos del usuario para sidebar.

**GroupController**
- `index` / `create` / `store` / `show`: CRUD completo.
- `join` / `leave`: con notificación al admin al unirse.
- `addPost` / `deletePost`: validando membresía.
- `addComment`: endpoint AJAX, responde JSON.

**Vistas**
- `groups/index.php`: grid 3 columnas con avatar, nombre, descripción, miembros y badge público/privado.
- `groups/create.php`: formulario con preview de avatar.
- `groups/show.php`: layout 8+4, posts con comentarios AJAX, sidebar con miembros y botones unirse/salir. Grupos privados: solo miembros ven publicaciones.

**Otros cambios**
- `index.php`: nuevas rutas de grupos y `/ajax/group/comment`.
- `helpers/functions.php`: añadida `groupAvatarUrl()`.
- `app.js`: soporte AJAX para `.group-comment-form`.
- `layout/header.php`: enlace Grupos en la navbar.

**Flujo verificado**
- Crear grupo y publicar desde el creador.
- Segundo usuario se une y puede publicar.
- Comentarios aparecen por AJAX sin recargar.
- Grupos privados ocultan contenido a no miembros.

**Archivos creados / modificados:**
```
app/models/GroupModel.php
app/controllers/GroupController.php
app/views/groups/index.php
app/views/groups/create.php
app/views/groups/show.php
docker/mysql/groups.sql
docker/mysql/init.sql          (modificado: tablas de grupos)
app/public/index.php           (modificado: nuevas rutas)
app/helpers/functions.php      (modificado: groupAvatarUrl)
app/public/js/app.js           (modificado: AJAX grupos)
app/views/layout/header.php    (modificado: enlace Grupos)
```

### Sesión 10 — Mapas en eventos (Leaflet.js)

**Fecha:** 25/05/2025  
**Estado:** ✅ Completado

**Objetivos:**
Integrar mapas interactivos en el módulo de eventos usando
Leaflet.js y la API gratuita de OpenStreetMap/Nominatim.

**Trabajo realizado:**

**Base de datos**
- Añadidas columnas latitude y longitude a la tabla events.
- docker/mysql/maps.sql para migrar contenedores existentes.
- docker/mysql/init.sql actualizado para nuevas instalaciones.

**EventModel**
- create / findById / getAll: incluyen latitude y longitude.
- getAllWithCoordinates: eventos con coordenadas para el 
  mapa del listado.

**EventController**
- store(): valida y recoge latitude y longitude del POST.
- index(): pasa $mapEvents a la vista.

**Vistas**
- events/create.php: mapa interactivo 350px centrado en 
  España. Clic coloca marcador, rellena campos ocultos y 
  geocodifica con Nominatim (debounce 500ms).
- events/show.php: mapa de solo lectura 300px si el evento 
  tiene coordenadas, con popup del título.
- events/index.php: mapa superior con todos los eventos 
  geolocalizados y fitBounds. Oculto si no hay ninguno.

**Otros cambios**
- app.css: estilos para .event-map y .leaflet-container.
- Leaflet.js cargado solo en las vistas que lo usan (CDN 1.9.4).

**Flujo verificado**
- Crear evento con clic en mapa rellena dirección y coordenadas.
- Mapa visible en detalle del evento.
- Mapa del listado muestra todos los eventos geolocalizados.
- Eventos sin coordenadas muestran solo texto de location.

**Archivos creados / modificados:**
```
docker/mysql/maps.sql
docker/mysql/init.sql          (modificado: columnas lat/lon)
app/models/EventModel.php      (modificado)
app/controllers/EventController.php  (modificado)
app/views/events/create.php    (modificado: mapa interactivo)
app/views/events/show.php      (modificado: mapa solo lectura)
app/views/events/index.php     (modificado: mapa listado)
app/public/css/app.css         (modificado: estilos mapa)
```

### Sesión 11 — Notificaciones Push PWA

**Fecha:** 25/05/2025  
**Estado:** ✅ Completado

**Objetivos:**
Implementar notificaciones push nativas del navegador usando
la Push API, Service Workers y la librería web-push de PHP.

**Trabajo realizado:**

**Base de datos**
- Tabla push_subscriptions para almacenar endpoints y keys.
- docker/mysql/push.sql para migrar contenedores existentes.

**PushModel**
- save / delete / getByUser / getAllSubscriptions /
  getSubscriptionsByUserIds.

**PushController**
- subscribe / unsubscribe: reciben JSON, gestionan 
  suscripciones por usuario.

**PushHelper**
- sendNotification(userIds, title, body, url): envía push
  con minishlink/web-push, limpia suscripciones caducadas.
- ob_start/ob_end_clean para evitar conflictos de headers
  con output de librerías externas.

**Integración**
- Notificaciones push en: solicitudes de amistad, 
  aceptación, comentarios, likes, mensajes y unión a grupos.

**PWA**
- manifest.json con nombre, iconos y theme_color.
- sw.js: install (caché), fetch (network-first), push 
  (showNotification), notificationclick (openWindow).
- iconos icon-192.png / icon-512.png (SVG fondo azul con C).
- push.js: registro SW, suscripción VAPID, botón en navbar.

**Infraestructura**
- Dockerfile: extensión GMP + Composer + web-push v9.
- docker-compose.yml: variables VAPID_*.
- .env.example: placeholders VAPID actualizados.
- app/config/app.php: carga vendor/autoload.php.

**Incidencias resueltas**
- Parse error en Windows al generar claves VAPID por 
  comillas en PowerShell. Resuelto creando script PHP 
  en app/ y ejecutándolo con docker exec.
- Notice de GMP imprimía output antes del redirect 
  rompiendo headers. Resuelto con ob_start/ob_end_clean
  en PushHelper y rebuild con GMP activo.

**Flujo verificado**
- Activar notificaciones push desde el menú de usuario.
- Mensaje del segundo usuario genera notificación nativa 
  en el navegador del admin.
- Push funciona con la pestaña en segundo plano.
- Actualización del contenido en tiempo real pendiente 
  de WebSockets (siguiente módulo).

**Archivos creados / modificados:**
```
app/models/PushModel.php
app/controllers/PushController.php
app/helpers/PushHelper.php
app/public/sw.js
app/public/manifest.json
app/public/img/icon-192.png
app/public/img/icon-512.png
app/public/js/push.js
docker/mysql/push.sql
docker/mysql/init.sql          (modificado)
docker/php/Dockerfile          (modificado: GMP + Composer)
docker-compose.yml             (modificado: VAPID vars)
app/config/app.php             (modificado: autoload + error_reporting)
app/helpers/PushHelper.php     (modificado: ob_start)
app/views/layout/header.php    (modificado: manifest + push.js)
app/views/layout/footer.php    (modificado: VAPID + push.js)
app/public/index.php           (modificado: rutas push)
app/controllers/FriendController.php   (modificado)
app/controllers/CommentController.php (modificado)
app/controllers/LikeController.php   (modificado)
app/controllers/MessageController.php (modificado)
app/controllers/GroupController.php  (modificado)
app/composer.json
.gitignore                     (modificado: app/vendor/)
```

## 🗺️ Roadmap

| Módulo | Estado |
|--------|--------|
| Docker + MariaDB + phpMyAdmin | ✅ Completado |
| Autenticación (registro / login / logout) | ✅ Completado |
| Feed de publicaciones con imágenes | ✅ Completado |
| Comentarios y likes (AJAX) | ✅ Completado |
| Sistema de amistad | ✅ Completado |
| Perfiles de usuario | ✅ Completado |
| Eventos y quedadas | ✅ Completado |
| Mensajería privada | ✅ Completado |
| Notificaciones | ✅ Completado |
| Panel de administración | ✅ Completado |
| Sistema de grupos | ✅ Completado |

> El roadmap principal del proyecto (Fase 1) está **completamente terminado**: todos los módulos en ✅ Completado.

## 🔮 Mejoras futuras

Tras cerrar la Fase 1 (MVC + Docker + módulos core), el desarrollo continuaría en dos fases con objetivos distintos.

### Fase 2 — Próximas mejoras

Mejoras pensadas para integrarse en el producto con alcance definido y uso en entorno de desarrollo o despliegue controlado.

| Orden | Módulo | Estado | Descripción |
|-------|--------|--------|-------------|
| 1 | **Grupos** | ✅ Hecho | Comunidades con feed, miembros y publicaciones (Sesión 9) |
| 2 | **Mapas** | ✅ Completado | Integración en eventos con [Leaflet.js](https://leafletjs.com/) y Nominatim (Sesión 10) |
| 3 | **Notificaciones push** | ✅ Completado | PWA con Push API, Service Worker y web-push (Sesión 11) |
| 4 | **WebSockets** | ← **Siguiente** | Mensajería en tiempo real con [Ratchet](https://github.com/ratchetphp/Ratchet) (PHP) |

### Fase 3 — Exploración técnica en local

> **Nota:** Las líneas de esta fase son **implementaciones de exploración técnica**: funcionales **solo en local** (o entorno de laboratorio) y con **alcance limitado**. No forman parte del núcleo del producto ni se asumen listas para producción sin trabajo adicional de infraestructura, seguridad y escalado.

| Área | Descripción |
|------|-------------|
| **Videochat** | WebRTC básico entre dos usuarios |
| **Streaming** | HLS básico, solo local |
| **App móvil** | PWA instalable (complementa notificaciones push de Fase 2) |
| **APIs externas** | Integraciones según necesidad (mapas, OAuth, etc.) |

