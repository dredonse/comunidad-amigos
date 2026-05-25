(function () {
    'use strict';

    if (typeof WebSocket === 'undefined' || typeof WS_URL === 'undefined') {
        return;
    }

    const form = document.querySelector('.message-form');
    const area = document.querySelector('.messages-area');
    if (!form || !area) {
        return;
    }

    let socket = null;
    let wsConnected = false;
    let reconnectTimer = null;
    const sendQueue = [];

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function formatTime(isoOrSql) {
        if (!isoOrSql) {
            return 'ahora';
        }
        const d = new Date(String(isoOrSql).replace(' ', 'T'));
        if (Number.isNaN(d.getTime())) {
            return 'ahora';
        }
        return d.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
    }

    function scrollToBottom() {
        area.scrollTop = area.scrollHeight;
    }

    function removeEmptyPlaceholder() {
        const empty = area.querySelector('.text-muted.text-center');
        if (empty) {
            empty.remove();
        }
    }

    function appendBubble(side, content, options) {
        const opts = options || {};
        removeEmptyPlaceholder();

        const wrap = document.createElement('div');
        wrap.className = 'd-flex ' + (side === 'mine' ? 'justify-content-end' : 'justify-content-start');

        const statusHtml = opts.pending
            ? '<span class="msg-status"><i class="bi bi-arrow-repeat msg-sending"></i></span>'
            : (opts.sent ? '<span class="msg-status"><i class="bi bi-check2"></i></span>' : '');

        wrap.innerHTML =
            '<div class="msg-bubble ' + (side === 'mine' ? 'msg-mine' : 'msg-other') + ' fade-in"' +
            (opts.tempId ? ' data-temp-id="' + opts.tempId + '"' : '') +
            (opts.messageId ? ' data-message-id="' + opts.messageId + '"' : '') + '>' +
            '<div>' + escapeHtml(content) + '</div>' +
            '<div class="msg-time">' + (opts.timeLabel || 'ahora') + ' ' + statusHtml + '</div>' +
            '</div>';

        area.appendChild(wrap);
        scrollToBottom();
    }

    function markSent(tempId, messageId, createdAt) {
        const bubble = area.querySelector('[data-temp-id="' + tempId + '"]');
        if (!bubble) {
            return;
        }
        bubble.dataset.messageId = String(messageId);
        bubble.removeAttribute('data-temp-id');
        const timeEl = bubble.querySelector('.msg-time');
        if (timeEl) {
            timeEl.innerHTML = formatTime(createdAt) + ' <span class="msg-status"><i class="bi bi-check2"></i></span>';
        }
    }

    function handleSocketMessage(event) {
        let data;
        try {
            data = JSON.parse(event.data);
        } catch (e) {
            return;
        }

        if (data.type === 'auth_ok') {
            setWsMode(true);
            return;
        }

        if (data.type === 'sent' && data.message_id) {
            const tempId = sendQueue.shift();
            if (tempId) {
                markSent(tempId, data.message_id, data.created_at);
            }
            return;
        }

        if (data.type === 'message' && data.sender_id) {
            if (parseInt(data.sender_id, 10) !== parseInt(RECEIVER_ID, 10)) {
                return;
            }
            appendBubble('other', data.content, {
                timeLabel: formatTime(data.created_at),
            });
        }
    }

    function connect() {
        if (socket && (socket.readyState === WebSocket.OPEN || socket.readyState === WebSocket.CONNECTING)) {
            return;
        }

        try {
            socket = new WebSocket(WS_URL);
        } catch (err) {
            console.warn('WebSocket no disponible:', err);
            setWsMode(false);
            scheduleReconnect();
            return;
        }

        socket.onopen = function () {
            socket.send(JSON.stringify({
                type: 'auth',
                user_id: USER_ID,
                username: USERNAME,
                avatar: AVATAR,
            }));
        };

        socket.onmessage = handleSocketMessage;

        socket.onclose = function () {
            setWsMode(false);
            scheduleReconnect();
        };

        socket.onerror = function () {
            setWsMode(false);
        };
    }

    function setWsMode(active) {
        wsConnected = active;
        form.dataset.wsConnected = active ? '1' : '0';
    }

    function scheduleReconnect() {
        clearTimeout(reconnectTimer);
        reconnectTimer = setTimeout(connect, 3000);
    }

    form.addEventListener('submit', function (e) {
        if (!wsConnected || !socket || socket.readyState !== WebSocket.OPEN) {
            return;
        }

        e.preventDefault();
        e.stopImmediatePropagation();

        const input = form.querySelector('input[name="content"]');
        const content = input?.value.trim();
        if (!content) {
            return;
        }

        const tempId = 't' + Date.now();
        sendQueue.push(tempId);
        appendBubble('mine', content, { pending: true, tempId: tempId });
        input.value = '';

        socket.send(JSON.stringify({
            type: 'message',
            receiver_id: RECEIVER_ID,
            content: content,
        }));
    }, true);

    connect();
})();
