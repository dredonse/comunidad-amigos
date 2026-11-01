/* ============================================
   Chat en tiempo real (solo recepción)
   --------------------------------------------
   Los mensajes se ENVÍAN siempre por HTTP (app.js → /messages/send), que es donde
   están la sesión, el CSRF, las notificaciones y el push. Este script solo abre un
   WebSocket para RECIBIR en vivo los mensajes de la conversación abierta.
   ============================================ */
(function () {
    'use strict';

    if (typeof WebSocket === 'undefined' || typeof WS_URL === 'undefined' || !WS_TOKEN) {
        return;
    }

    const area = document.querySelector('.messages-area');
    if (!area) {
        return;
    }

    const MAX_DELAY = 30000;
    let socket = null;
    let delay = 2000;
    let reconnectTimer = null;
    let stopped = false;

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function formatTime(sqlDate) {
        const d = new Date(String(sqlDate || '').replace(' ', 'T'));
        return Number.isNaN(d.getTime())
            ? 'ahora'
            : d.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
    }

    function appendIncoming(content, createdAt) {
        const empty = area.querySelector('.text-muted.text-center');
        if (empty) {
            empty.remove();
        }
        const wrap = document.createElement('div');
        wrap.className = 'd-flex justify-content-start';
        wrap.innerHTML =
            '<div class="msg-bubble msg-other fade-in">' +
            '<div>' + escapeHtml(content) + '</div>' +
            '<div class="msg-time">' + escapeHtml(formatTime(createdAt)) + '</div>' +
            '</div>';
        area.appendChild(wrap);
        area.scrollTop = area.scrollHeight;
    }

    function markRead() {
        if (typeof postAjax === 'function') {
            postAjax('/messages/read', { other_user_id: RECEIVER_ID }).catch(function () {});
        }
    }

    function scheduleReconnect() {
        if (stopped) {
            return;
        }
        clearTimeout(reconnectTimer);
        reconnectTimer = setTimeout(connect, delay);
        delay = Math.min(delay * 2, MAX_DELAY); // backoff exponencial
    }

    function connect() {
        try {
            socket = new WebSocket(WS_URL);
        } catch (err) {
            scheduleReconnect();
            return;
        }

        socket.onopen = function () {
            socket.send(JSON.stringify({ type: 'auth', token: WS_TOKEN }));
        };

        socket.onmessage = function (event) {
            let data;
            try {
                data = JSON.parse(event.data);
            } catch (e) {
                return;
            }

            if (data.type === 'auth_ok') {
                delay = 2000;
                return;
            }
            if (data.type === 'auth_error') {
                // Token caducado o inválido: recargar la página genera uno nuevo
                stopped = true;
                return;
            }
            if (data.type === 'message' && parseInt(data.sender_id, 10) === RECEIVER_ID) {
                appendIncoming(data.content, data.created_at);
                markRead();
            }
        };

        socket.onclose = scheduleReconnect;
    }

    connect();
})();
