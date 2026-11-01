# Seguridad

Este documento recoge las medidas de seguridad de la aplicación y la revisión que hice en noviembre de 2026, antes de publicar el repositorio: qué encontré, cómo lo comprobé y cómo lo arreglé.

## Medidas

### Sesión y autenticación
- Contraseñas con `password_hash` (bcrypt, coste 12) y `password_verify`.
- Mensaje genérico ante credenciales incorrectas y tiempo de respuesta igual exista o no el email (se verifica contra un hash ficticio).
- Cookie de sesión `HttpOnly`, `SameSite=Lax`, solo por cookie (`use_only_cookies`, sin id en la URL), `use_strict_mode` y `Secure` cuando `APP_URL` es HTTPS.
- `session_regenerate_id(true)` al iniciar sesión, contra la fijación de sesión.
- `requireLogin()` contrasta la sesión con la base de datos en cada petición: una cuenta desactivada o borrada sale en su siguiente clic y los cambios de rol se aplican al momento.
- Cierre de sesión por POST con token CSRF: borra datos, cookie y sesión, y envía `Clear-Site-Data: "cache"`.
- No hay usuarios por defecto: el administrador se crea con `bin/create-admin.php`.

### CSRF
- Token de 256 bits (`random_bytes(32)`) por sesión, en un campo oculto de cada formulario y en `<meta name="csrf-token">` para las peticiones AJAX (cabecera `X-CSRF-TOKEN`).
- `verifyCsrf()` al principio de **las 32 acciones POST**, con `hash_equals` (comparación en tiempo constante).

### XSS
- Toda salida en las vistas pasa por `e()` (`htmlspecialchars` con `ENT_QUOTES | ENT_HTML5`, UTF-8).
- Datos hacia JavaScript con `json_encode` y `JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP`.
- En el navegador, el texto recibido por AJAX o WebSocket se escapa antes de insertarlo; los popups de los mapas se construyen con `textContent`.
- Librerías de CDN con *Subresource Integrity* (Bootstrap, Bootstrap Icons, Leaflet).

### Inyección SQL
- PDO con consultas preparadas en todas las consultas con datos y `PDO::ATTR_EMULATE_PREPARES = false` (la preparación la hace MariaDB; datos y SQL viajan por separado).

### Subida de ficheros
`uploadImage()` en `app/helpers/functions.php`:
1. Tipo real detectado por el contenido con `finfo`; `$_FILES['type']` (que envía el navegador) no se usa.
2. Límite de 5 MB y de 6000 px por lado.
3. La imagen se **re-codifica** con GD: desaparece cualquier contenido incrustado y los metadatos EXIF (que pueden incluir la ubicación GPS de la foto). Antes se aplica la orientación EXIF.
4. Nombre aleatorio (`random_bytes(16)`) y extensión decidida por el servidor según el tipo real.
5. En `public/uploads/` PHP está desactivado dos veces: en el *virtual host* de Apache (`php_admin_flag engine off` y denegación de `*.php`) y en `uploads/.htaccess`.

### WebSocket
- Autenticación con un token `id.caducidad.firma` firmado por la web con HMAC-SHA256 y `APP_SECRET`; el servidor verifica firma y caducidad y usa el id del token.
- Comprobación de la cabecera `Origin` (los navegadores no aplican la política del mismo origen a los WebSockets).
- El servidor solo entrega mensajes; no acepta mensajes del navegador.
- Canal interno web → WebSocket en un puerto no publicado y con secreto compartido.

### Autorización
- Borrar publicaciones, eventos y publicaciones de grupo: solo el autor o un administrador (del sitio o del grupo). Los `DELETE` del autor incluyen además `AND user_id = ?`.
- Las publicaciones de un usuario solo las ven él, sus amigos y los administradores; comentar y dar like, igual.
- Grupos privados: unirse crea una solicitud que aprueba un administrador del grupo; hasta entonces no se ven ni se pueden crear publicaciones.
- Un administrador no puede desactivar ni borrar a otro administrador ni a sí mismo.
- Notificaciones: solo se pueden marcar las propias.
- Suscripciones push: solo endpoints HTTPS de servicios push conocidos (evita que el servidor haga peticiones a direcciones arbitrarias).

### Configuración e infraestructura
- Solo `app/public/` es raíz web. `.htaccess` deniega ficheros ocultos, `.env`, `.sql`, `.log`, `.md`, etc.
- Cabeceras: `X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`, `Referrer-Policy`, `Permissions-Policy`. `mod_headers` está activado en la imagen.
- `display_errors` desactivado; `APP_DEBUG=false` por defecto; `expose_php` y la firma de Apache desactivados.
- Páginas con sesión servidas con `Cache-Control: no-store`; el Service Worker solo guarda recursos estáticos.
- MariaDB sin puerto publicado; phpMyAdmin opcional, solo en `127.0.0.1` y sin inicio de sesión automático.
- Secretos en `.env` (fuera de git); `.env.example` sin valores; `APP_SECRET` obligatorio para arrancar.

## Revisión de noviembre de 2026

Revisé el código completo (controladores, modelos, vistas, JavaScript, servidor WebSocket, Docker y SQL) y comprobé cada hallazgo con una instancia local (Apache + PHP 8.3 + MariaDB + Ratchet). Después de los arreglos, una batería de 47 pruebas HTTP y WebSocket y 10 en navegador (Chromium) pasan sin errores.

### Graves

| Hallazgo | Cómo lo comprobé | Arreglo |
|---|---|---|
| **Ejecución remota de código por la subida de imágenes.** El tipo MIME lo enviaba el navegador, `getimagesize()` solo mira la cabecera del fichero y la extensión se copiaba del nombre original. Un fichero que empieza por `GIF89a` y contiene PHP se guardaba como `<aleatorio>.php` dentro de la raíz web, y Apache lo ejecutaba | Subí `shell.php` con `Content-Type: image/gif` como imagen de una publicación y pedí su URL: el servidor ejecutó el código | `finfo` + re-codificación con GD + extensión del servidor + PHP desactivado en `uploads/` |
| **Fotos publicadas con su ubicación GPS** (metadatos EXIF sin tocar) | — | La re-codificación elimina el EXIF |
| **WebSocket sin autenticación real.** El servidor aceptaba como identidad el `user_id` que enviaba el navegador, y su puerto estaba publicado: cualquiera podía escribir en nombre de otro y recibir en directo sus mensajes | Conexión con un cliente WebSocket, sin sesión, enviando `{"type":"auth","user_id":1}` | Token firmado con HMAC, `Origin`, servidor de solo entrega |
| **phpMyAdmin con inicio de sesión automático y MariaDB publicados** (`8081` y `3306`) | — | Perfil opcional, solo `127.0.0.1`, sin credenciales; BD sin puerto publicado |
| **Administrador por defecto con contraseña documentada** | — | Eliminado; `bin/create-admin.php` |

### Medios

| Hallazgo | Arreglo |
|---|---|
| Grupos "privados" en los que cualquiera entraba con un clic | Solicitudes con aprobación |
| Un usuario desactivado seguía dentro hasta que caducaba su sesión; el rol se leía solo de la sesión | `requireLogin()` consulta la BD en cada petición |
| El Service Worker guardaba todas las páginas (feed, mensajes, administración), que seguían en el navegador tras cerrar sesión | Solo estáticos, `Cache-Control: no-store`, limpieza al cerrar sesión |
| La suscripción push seguía activa tras cerrar sesión (la siguiente persona del equipo recibía tus notificaciones) | Se borra al cerrar sesión |
| XSS almacenado en el popup del mapa del detalle de evento (título sin escapar) | `textContent` |
| `GET /logout`: cualquier web podía cerrar la sesión | POST con CSRF |
| Comentar, dar like y ver las publicaciones de cualquiera aunque no fuerais amigos | Mismas reglas que el feed |
| Las redirecciones usaban la cabecera `Host` enviada por el cliente | `APP_URL` fija |
| `mod_headers` no estaba activado: las cabeceras de seguridad del `.htaccess` no se enviaban | Activado en la imagen |
| Diferencia de tiempo en el login según existiera o no el email | Verificación contra un hash ficticio |
| Suscripciones push con cualquier URL como endpoint | Solo servicios push conocidos |

### Errores de funcionamiento corregidos

- **`app.js` no se ejecutaba**: una variable declarada dos veces en la misma función provocaba un error de sintaxis, así que likes, comentarios, confirmaciones de borrado y el envío de mensajes por AJAX no funcionaban.
- Los mensajes enviados por WebSocket no creaban notificación ni push.
- Una solicitud de amistad rechazada impedía para siempre volver a enviarla.
- "Solicitud aceptada" aunque no existiera la solicitud.
- El botón de notificaciones push no permitía volver a activarlas tras desactivarlas.
- Las dependencias de Composer de la imagen quedaban ocultas por el volumen, y el push dejaba de funcionar en un clon limpio.
- La conexión del servidor WebSocket a MariaDB no se recuperaba tras horas de inactividad.
- Dos clics simultáneos en "me gusta" podían provocar un error 500.
- Al borrar usuarios, publicaciones o eventos quedaban imágenes huérfanas en disco.
- Longitudes medidas en bytes en lugar de caracteres (`strlen` → `mb_strlen`).

## Pendiente

- Límite de intentos de inicio de sesión (fuerza bruta).
- Content-Security-Policy (requiere sacar los `<script>` en línea de las vistas).
- Tests automáticos de los helpers de seguridad e integración continua.
- Registro por invitación o con aprobación, si "privada" debe significar "solo invitados".
- Despliegue detrás de un proxy con HTTPS (`wss://`, HSTS).
