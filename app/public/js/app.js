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
        if (msgForm.dataset.wsConnected === '1') return;
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
        .replace(/"/g, '&quot;');
}

// ── Cerrar alertas automáticamente (3 s) ─────────────────────
document.querySelectorAll('.alert.alert-success').forEach(el => {
    setTimeout(() => {
        el.classList.add('fade');
        setTimeout(() => el.remove(), 500);
    }, 3000);
});
