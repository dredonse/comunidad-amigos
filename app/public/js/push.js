(function () {
    'use strict';

    if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
        return;
    }

    const btn = document.getElementById('push-toggle-btn');
    if (!btn) {
        return;
    }

    let swRegistration = null;

    function urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
        const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
        const raw = atob(base64);
        const output = new Uint8Array(raw.length);
        for (let i = 0; i < raw.length; ++i) {
            output[i] = raw.charCodeAt(i);
        }
        return output;
    }

    function getCsrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    function updateButtonLabel() {
        if (Notification.permission === 'granted') {
            btn.textContent = 'Desactivar notificaciones push';
            btn.classList.remove('btn-outline-primary');
            btn.classList.add('btn-outline-secondary');
        } else {
            btn.textContent = 'Activar notificaciones push';
            btn.classList.remove('btn-outline-secondary');
            btn.classList.add('btn-outline-primary');
        }
    }

    function subscriptionPayload(subscription) {
        const json = subscription.toJSON();
        return {
            endpoint: json.endpoint,
            keys: {
                p256dh: json.keys.p256dh,
                auth: json.keys.auth,
            },
        };
    }

    async function postJson(url, body) {
        const res = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(body),
            credentials: 'same-origin',
        });
        return res.json();
    }

    window.subscribeToPush = async function () {
        if (!VAPID_PUBLIC || VAPID_PUBLIC.length < 10) {
            alert('Las claves VAPID no están configuradas en el servidor.');
            return;
        }

        const permission = await Notification.requestPermission();
        updateButtonLabel();

        if (permission !== 'granted') {
            return;
        }

        if (!swRegistration) {
            swRegistration = await navigator.serviceWorker.register('/sw.js');
        }

        let subscription = await swRegistration.pushManager.getSubscription();
        if (!subscription) {
            subscription = await swRegistration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(VAPID_PUBLIC),
            });
        }

        const data = subscriptionPayload(subscription);
        const result = await postJson(PUSH_SUBSCRIBE_URL, data);

        if (!result.success) {
            console.error('Error al suscribir push:', result);
        }
    };

    window.unsubscribeFromPush = async function () {
        if (!swRegistration) {
            swRegistration = await navigator.serviceWorker.getRegistration();
        }
        if (!swRegistration) {
            return;
        }

        const subscription = await swRegistration.pushManager.getSubscription();
        if (!subscription) {
            updateButtonLabel();
            return;
        }

        const keys = subscription.toJSON().keys || {};
        await postJson(PUSH_UNSUBSCRIBE_URL, { auth: keys.auth || '' });
        await subscription.unsubscribe();
        updateButtonLabel();
    };

    btn.addEventListener('click', async function (e) {
        e.preventDefault();
        try {
            if (Notification.permission === 'granted') {
                await window.unsubscribeFromPush();
            } else {
                await window.subscribeToPush();
            }
        } catch (err) {
            console.error(err);
            alert('No se pudieron gestionar las notificaciones push.');
        }
    });

    navigator.serviceWorker.register('/sw.js').then(function (reg) {
        swRegistration = reg;
        updateButtonLabel();
    }).catch(function (err) {
        console.error('Service Worker:', err);
    });

    updateButtonLabel();
})();
