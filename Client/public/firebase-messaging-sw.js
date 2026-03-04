// Firebase Messaging Service Worker for background push notifications
// This file MUST be in the public/ directory at the root

importScripts("https://www.gstatic.com/firebasejs/11.6.0/firebase-app-compat.js");
importScripts("https://www.gstatic.com/firebasejs/11.6.0/firebase-messaging-compat.js");

firebase.initializeApp({
    apiKey: "AIzaSyCV15jYbi6cUclXLX1zvZfHNg0Y9sTE74g",
    authDomain: "selfshop-911.firebaseapp.com",
    projectId: "selfshop-911",
    storageBucket: "selfshop-911.firebasestorage.app",
    messagingSenderId: "634594589289",
    appId: "1:634594589289:web:d7285479ab5af40a6c40f8",
});

const messaging = firebase.messaging();

// Handle background messages
messaging.onBackgroundMessage((payload) => {
    console.log("[SW] Background message received:", payload);

    const title = payload.notification?.title || payload.data?.title || "SelfShop";
    const body = payload.notification?.body || payload.data?.body || "";
    const image = payload.notification?.image || payload.data?.image;
    const clickAction = payload.data?.click_action || "/";

    const options = {
        body: body,
        icon: "/favicon.ico",
        badge: "/favicon.ico",
        image: image || undefined,
        data: {
            url: clickAction,
        },
        vibrate: [200, 100, 200],
        tag: "selfshop-notification",
        renotify: true,
    };

    self.registration.showNotification(title, options);
});

// Handle notification click
self.addEventListener("notificationclick", (event) => {
    event.notification.close();

    const url = event.notification.data?.url || "/";

    event.waitUntil(
        clients.matchAll({ type: "window", includeUncontrolled: true }).then((clientList) => {
            // If a window is already open, focus it
            for (const client of clientList) {
                if (client.url === url && "focus" in client) {
                    return client.focus();
                }
            }
            // Otherwise open a new window
            if (clients.openWindow) {
                return clients.openWindow(url);
            }
        })
    );
});
