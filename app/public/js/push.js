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

    // El estado real es si existe una suscripción, no el permiso: tras darse de baja,
    // el permiso sigue en "granted" y antes el botón ya no permitía volver a activarlas.
    async function isSubscribed() {
        if (!swRegistration) {
            return false;
        }
        return (await swRegistration.pushManager.getSubscription()) !== null;
    }

    async function updateButtonLabel() {
        const subscribed = Notification.permission === 'granted' && await isSubscribed();
        btn.innerHTML = subscribed
            ? '<i class="bi bi-bell-slash me-1"></i> Desactivar notificaciones push'
            : '<i class="bi bi-bell me-1"></i> Activar notificaciones push';
        btn.dataset.subscribed = subscribed ? '1' : '0';
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
        await updateButtonLabel();
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
            await updateButtonLabel();
            return;
        }

        const keys = subscription.toJSON().keys || {};
        await postJson(PUSH_UNSUBSCRIBE_URL, { auth: keys.auth || '' });
        await subscription.unsubscribe();
        await updateButtonLabel();
    };

    btn.addEventListener('click', async function (e) {
        e.preventDefault();
        try {
            if (btn.dataset.subscribed === '1') {
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
        return updateButtonLabel();
    }).catch(function (err) {
        console.error('Service Worker:', err);
    });
})();
