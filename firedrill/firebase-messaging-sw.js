importScripts(
  "https://www.gstatic.com/firebasejs/10.7.1/firebase-app-compat.js",
);
importScripts(
  "https://www.gstatic.com/firebasejs/10.7.1/firebase-messaging-compat.js",
);

firebase.initializeApp({
  apiKey: "AIzaSyD1FlnMJN9ThCuIXqqg5fyuiia62W81BSI",
  authDomain: "firedrill-8bb54.firebaseapp.com",
  projectId: "firedrill-8bb54",
  storageBucket: "firedrill-8bb54.firebasestorage.app",
  messagingSenderId: "7847919788",
  appId: "1:7847919788:web:03c5563d04bbe9007edf09",
});

const messaging = firebase.messaging();

messaging.onBackgroundMessage((payload) => {
  self.registration.showNotification("🚨 FIRE ALERT", {
    body: "Fire reported! Evacuate immediately!",
    icon: "icon-192.png",
    badge: "icon-192.png",
    vibrate: [1000, 500, 1000, 500, 1000],
    requireInteraction: true,
  });
});
