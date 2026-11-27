/// <reference lib="webworker" />
declare const self: ServiceWorkerGlobalScope & {
  __WB_MANIFEST: Array<{ url: string; revision: string | null }>;
};

// Referensi manifest Workbox agar vite-plugin-pwa injectManifest valid
const precacheManifest = self.__WB_MANIFEST;
void precacheManifest;

self.addEventListener('install', () => {
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(self.clients.claim());
});

self.addEventListener('push', (event) => {
  if (!event.data) return;

  let data: {
    title?: string;
    body?: string;
    url?: string;
    icon?: string;
    badge?: string;
    tag?: string;
  } = {};

  try {
    data = event.data.json();
  } catch {
    data = {
      title: 'Notifikasi SIM-BIM',
      body: event.data.text(),
    };
  }

  const title = data.title || 'Notifikasi SIM-BIM';
  const options: NotificationOptions = {
    body: data.body || '',
    icon: data.icon || '/logo.png',
    badge: data.badge || '/logo.png',
    tag: data.tag || 'simbim-notification',
    data: {
      url: data.url || '/presensi',
    },
  };

  event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  const targetUrl = (event.notification.data?.url as string) || '/presensi';

  event.waitUntil(
    self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
      for (const client of clientList) {
        if ('focus' in client) {
          client.focus();
          if ('navigate' in client) {
            return (client as WindowClient).navigate(targetUrl);
          }
          return client;
        }
      }
      return self.clients.openWindow(targetUrl);
    })
  );
});
