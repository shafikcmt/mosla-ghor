// MoslaMart service worker — minimal, no offline caching (kept simple & safe).
// Its only job is to make the site installable (a fetch handler is required for
// the install prompt in Chromium browsers). Network passthrough only.
self.addEventListener('install', function () { self.skipWaiting(); });
self.addEventListener('activate', function (event) { event.waitUntil(self.clients.claim()); });
self.addEventListener('fetch', function () { /* network passthrough; no caching */ });
