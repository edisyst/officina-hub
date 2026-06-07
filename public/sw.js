"use strict";

const CACHE_VERSION  = "v1";
const STATIC_CACHE   = "officina-static-"  + CACHE_VERSION;
const DYNAMIC_CACHE  = "officina-dynamic-" + CACHE_VERSION;
const OFFLINE_URL    = "/offline.html";

// Assets to pre-cache on install
const PRECACHE_URLS = [
    OFFLINE_URL,
    "/vendor/adminlte/dist/css/adminlte.min.css",
    "/vendor/adminlte/plugins/fontawesome-free/css/all.min.css",
    "/vendor/adminlte/plugins/jquery/jquery.min.js",
    "/vendor/adminlte/plugins/bootstrap/js/bootstrap.bundle.min.js",
    "/images/icons/icon-192x192.png",
    "/images/icons/icon-512x512.png",
];

// URL prefixes served network-first (Livewire AJAX, API calls)
const NETWORK_FIRST_PATTERNS = [
    "/livewire/",
    "/api/",
];

// Stale-while-revalidate for the tablet board
const STALE_WHILE_REVALIDATE_PATTERNS = [
    "/officina/marcatempo",
    "/officina/checklist/",
];

self.addEventListener("install", (event) => {
    event.waitUntil(
        caches.open(STATIC_CACHE).then((cache) => cache.addAll(PRECACHE_URLS))
    );
    self.skipWaiting();
});

self.addEventListener("activate", (event) => {
    event.waitUntil(
        caches.keys().then((names) =>
            Promise.all(
                names.map((name) => {
                    if (name !== STATIC_CACHE && name !== DYNAMIC_CACHE) {
                        return caches.delete(name);
                    }
                })
            )
        )
    );
    self.clients.claim();
});

self.addEventListener("fetch", (event) => {
    const { request } = event;
    const url = new URL(request.url);

    // Only handle same-origin requests
    if (url.origin !== location.origin) return;

    const path = url.pathname;

    // Network-first: Livewire & API (always need fresh data)
    if (NETWORK_FIRST_PATTERNS.some((p) => path.startsWith(p))) {
        event.respondWith(networkFirst(request));
        return;
    }

    // Stale-while-revalidate: tablet pages
    if (STALE_WHILE_REVALIDATE_PATTERNS.some((p) => path.startsWith(p))) {
        event.respondWith(staleWhileRevalidate(request));
        return;
    }

    // Cache-first: static assets (CSS, JS, fonts, images)
    if (isStaticAsset(path)) {
        event.respondWith(cacheFirst(request));
        return;
    }

    // Network-only with offline fallback for navigation
    if (request.mode === "navigate") {
        event.respondWith(
            fetch(request).catch(() => caches.match(OFFLINE_URL))
        );
        return;
    }
});

function isStaticAsset(path) {
    return /\.(css|js|png|jpg|jpeg|gif|svg|woff2?|ttf|eot|ico)(\?.*)?$/.test(path);
}

async function cacheFirst(request) {
    const cached = await caches.match(request);
    if (cached) return cached;
    const response = await fetch(request);
    if (response.ok) {
        const cache = await caches.open(STATIC_CACHE);
        cache.put(request, response.clone());
    }
    return response;
}

async function networkFirst(request) {
    try {
        const response = await fetch(request);
        if (response.ok) {
            const cache = await caches.open(DYNAMIC_CACHE);
            cache.put(request, response.clone());
        }
        return response;
    } catch {
        const cached = await caches.match(request);
        return cached || new Response("", { status: 503 });
    }
}

async function staleWhileRevalidate(request) {
    const cache  = await caches.open(DYNAMIC_CACHE);
    const cached = await cache.match(request);

    const fetchPromise = fetch(request).then((response) => {
        if (response.ok) cache.put(request, response.clone());
        return response;
    });

    return cached || fetchPromise;
}
