/**
 * Browser Push API integration for match invitations, bet results and
 * tournament start alerts. Exposed as `window.enablePushNotifications()`
 * for a simple "Enable notifications" button anywhere in the UI.
 */

function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const rawData = window.atob(base64);
    return Uint8Array.from([...rawData].map((char) => char.charCodeAt(0)));
}

window.enablePushNotifications = async function enablePushNotifications() {
    if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
        alert('Push notifications are not supported in this browser.');
        return false;
    }

    const permission = await Notification.requestPermission();
    if (permission !== 'granted') {
        return false;
    }

    const registration = await navigator.serviceWorker.ready;
    const vapidPublicKey = document.querySelector('meta[name="vapid-public-key"]')?.content;

    if (!vapidPublicKey) {
        return false;
    }

    const subscription = await registration.pushManager.subscribe({
        userVisibleOnly: true,
        applicationServerKey: urlBase64ToUint8Array(vapidPublicKey),
    });

    await fetch('/push-subscriptions', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify(subscription.toJSON()),
    });

    return true;
};
