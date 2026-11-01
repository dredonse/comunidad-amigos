/* ============================================
   Comunidad de Amigos - JavaScript principal
   ============================================ */

'use strict';

// ── CSRF token para peticiones AJAX ──────────────────────────
const csrfToken =
    document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
    ?? document.querySelector('input[name="csrf_token"]')?.value
    ?? '';

async function postAjax(url, data = {}) {
    const body = new URLSearchParams({ csrf_token: csrfToken, ...data });
    const res  = await fetch(url, {
        method:  'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
        body
    });
    return res.json();
}

// ── Likes ─────────────────────────────────────────────────────
document.addEventListener('click', async function(e) {
    const btn = e.target.closest('.btn-like');
    if (!btn) return;

    const postId = btn.dataset.postId;
    try {
        const data = await postAjax('/ajax/like', { post_id: postId });
        if (data.success) {
            const icon  = btn.querySelector('i');
            const count = btn.querySelector('.like-count');
            btn.classList.toggle('liked', data.liked);
            icon.className = data.liked ? 'bi bi-heart-fill' : 'bi bi-heart';
            if (count) count.textContent = data.count;
        }
    } catch (err) { console.error('Error al procesar like:', err); }
});

document.addEventListener('submit', async function(e) {
    const groupForm = e.target.closest('.group-comment-form');
    if (groupForm) {
        e.preventDefault();
        const postId  = groupForm.dataset.groupPostId;
        const input   = groupForm.querySelector('input[name="comment"]');
        const comment = input?.value.trim();
        if (!comment) return;
        try {
            const data = await postAjax('/ajax/group/comment', {
                group_post_id: postId, comment
            });
            if (data.success) {
                const list = document.querySelector(`#group-comments-${postId}`);
                list?.insertAdjacentHTML('beforeend', `
                    <div class="comment-item d-flex gap-2 fade-in">
                        <div>
                            <strong>${escapeHtml(data.username)}</strong>
                            <span class="text-muted ms-1">${escapeHtml(data.comment)}</span>
                        </div>
                    </div>`);
                input.value = '';
            }
        } catch (err) { console.error('Error comentario grupo:', err); }
        return;
    }

    const postForm = e.target.closest('.comment-form');
    if (postForm) {
        e.preventDefault();
        const postId  = postForm.dataset.postId;
        const input   = postForm.querySelector('textarea, input[name="comment"]');
        const comment = input?.value.trim();
        if (!comment) return;
        try {
            const data = await postAjax('/ajax/comment', { post_id: postId, comment });
            if (data.success) {
                const list = document.querySelector(`#comments-${postId}`);
                list?.insertAdjacentHTML('beforeend', `
                    <div class="comment-item fade-in">
                        <strong>${escapeHtml(data.username)}</strong>
                        <span class="text-muted ms-1">${escapeHtml(data.comment)}</span>
                        <span class="text-muted ms-2" style="font-size:.75rem">ahora</span>
                    </div>`);
                input.value = '';
            }
        } catch (err) { console.error('Error comentario post:', err); }
        return;
    }

    const msgForm = e.target.closest('.message-form');
    if (msgForm) {
        // Los mensajes se envían siempre por HTTP; el WebSocket (chat.js) solo los recibe
        e.preventDefault();
        const input   = msgForm.querySelector('input[name="content"]');
        const content = input?.value.trim();
        if (!content) return;
        const receiverId = msgForm.dataset.receiverId
            || msgForm.querySelector('input[name="receiver_id"]')?.value;
        const area = document.querySelector('.messages-area');
        try {
            const data = await postAjax('/messages/send', { receiver_id: receiverId, content });
            if (data.success && data.message) {
                const wrap = document.createElement('div');
                wrap.className = 'd-flex justify-content-end';
                wrap.innerHTML = `
                    <div class="msg-bubble msg-mine fade-in">
                        <div>${escapeHtml(data.message.content)}</div>
                        <div class="msg-time">ahora</div>
                    </div>`;
                area?.appendChild(wrap);
                input.value = '';
                if (area) area.scrollTop = area.scrollHeight;
            }
        } catch (err) {
            console.error('Error mensaje:', err);
            HTMLFormElement.prototype.submit.call(msgForm);
        }
    }
});

// ── Auto-resize textarea ──────────────────────────────────────
document.querySelectorAll('textarea[data-autoresize]').forEach(el => {
    el.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 300) + 'px';
    });
});

// ── Confirmaciones de borrado ─────────────────────────────────
document.addEventListener('click', function(e) {
    const btn = e.target.closest('[data-confirm]');
    if (!btn) return;
    if (!confirm(btn.dataset.confirm)) e.preventDefault();
});

// ── Escape HTML helper ────────────────────────────────────────
function escapeHtml(str) {
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function avatarSrc(avatar) {
    return !avatar || avatar === 'default.png'
        ? '/img/default.svg'
        : '/uploads/avatars/' + encodeURIComponent(avatar);
}

// ── Cerrar alertas automáticamente (3 s) ─────────────────────
document.querySelectorAll('.alert.alert-success').forEach(el => {
    setTimeout(() => {
        el.classList.add('fade');
        setTimeout(() => el.remove(), 500);
    }, 3000);
});

// ── Polling del feed ──────────────────────────────────────────
(function() {
    const feedContainer = document.querySelector('#feed-container');
    if (!feedContainer) return;

    // El id más alto de los que ya se ven (no el primero: varias publicaciones
    // pueden tener la misma fecha y el orden no coincidir con el id)
    let lastPostId = Math.max(0, ...Array.from(
        feedContainer.querySelectorAll('.post-card[data-post-id]'),
        card => parseInt(card.dataset.postId, 10) || 0
    ));

    async function checkNewPosts() {
        try {
            const res = await fetch('/ajax/feed/new?last_id=' + lastPostId);
            const data = await res.json();

            if (data.posts && data.posts.length > 0) {
                showNewPostsButton(data.posts.length, data.posts);
                lastPostId = data.posts[data.posts.length - 1].id;
            }
        } catch (err) {
            console.error('Error polling feed:', err);
        }
    }

    function showNewPostsButton(count, posts) {
        document.querySelector('#new-posts-btn')?.remove();

        const btn = document.createElement('div');
        btn.id = 'new-posts-btn';
        btn.className = 'alert alert-primary text-center cursor-pointer fade-in mb-3';
        btn.style.cursor = 'pointer';
        btn.innerHTML = `
            <i class="bi bi-arrow-up-circle me-1"></i>
            Ver ${count} ${count > 1 ? 'publicaciones nuevas' : 'publicación nueva'}
        `;
        btn.addEventListener('click', () => {
            insertNewPosts(posts);
            btn.remove();
        });

        feedContainer.insertBefore(btn, feedContainer.firstChild);
    }

    function insertNewPosts(posts) {
        posts.forEach(post => {
            const div = document.createElement('div');
            div.className = 'fade-in';
            div.innerHTML = renderPost(post);
            feedContainer.insertBefore(div, feedContainer.firstChild);
        });
    }

    function renderPost(post) {
        return `
            <div class="card post-card mb-3" data-post-id="${Number(post.id)}">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2">
                        <img src="${escapeHtml(avatarSrc(post.avatar))}"
                             class="avatar me-2" width="42" height="42"
                             onerror="this.onerror=null;this.src='/img/default.svg'">
                        <div>
                            <strong>${escapeHtml(post.username)}</strong>
                            <div class="text-muted small">ahora</div>
                        </div>
                    </div>
                    <p style="white-space:pre-wrap">${escapeHtml(post.content)}</p>
                    ${post.image ? `
                        <img src="/uploads/posts/${encodeURIComponent(post.image)}"
                             class="post-image rounded mb-2">
                    ` : ''}
                    <hr class="my-2">
                    <div class="post-actions d-flex gap-2">
                        <button class="btn btn-sm btn-like"
                                data-post-id="${Number(post.id)}">
                            <i class="bi bi-heart"></i>
                            <span class="like-count">0</span>
                            Me gusta
                        </button>
                        <button class="btn btn-sm btn-toggle-comments"
                                data-post-id="${Number(post.id)}">
                            <i class="bi bi-chat"></i> Comentar
                        </button>
                    </div>
                </div>
            </div>`;
    }

    // Iniciar polling cada 30 segundos
    setInterval(checkNewPosts, 30000);
})();

// ── Cerrar sesión ─────────────────────────────────────────────
// Antes de salir: se borra la suscripción push de este navegador (si no, la siguiente
// persona que use el equipo recibiría tus notificaciones) y las cachés del Service Worker.
document.getElementById('logout-form')?.addEventListener('submit', async function (e) {
    e.preventDefault();
    const form = this;
    try {
        if (typeof window.unsubscribeFromPush === 'function') {
            await window.unsubscribeFromPush();
        }
        if ('caches' in window) {
            const keys = await caches.keys();
            await Promise.all(keys.map(key => caches.delete(key)));
        }
    } catch (err) {
        console.warn('Limpieza al cerrar sesión incompleta:', err);
    }
    HTMLFormElement.prototype.submit.call(form);
});
