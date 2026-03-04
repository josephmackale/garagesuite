/* public/service-worker.js
 * GarageSuite PWA Service Worker (safe + minimal + WAITING updates)
 *
 * - Network-first for navigations (avoid stale HTML/app shell)
 * - Cache-first for static assets
 * - Updates WAIT (do not auto-skipWaiting)
 * - UI triggers activation via postMessage("SKIP_WAITING")
 */

const VERSION = "gs-v1.0.2"; // bump to force SW update
const STATIC_CACHE = `${VERSION}-static`;
const RUNTIME_CACHE = `${VERSION}-runtime`;

const CACHEABLE_ASSET = /\.(?:css|js|mjs|png|jpg|jpeg|gif|svg|webp|ico|woff2?|ttf|otf)$/i;
const OFFLINE_URL = "/offline";

// Install: do NOT auto-activate.
// This allows the new SW to become "waiting" so the app can show "Update available".
self.addEventListener("install", () => {
    // intentionally empty
});

// Activate: clean old caches + take control
self.addEventListener("activate", (event) => {
    event.waitUntil(
        (async () => {
            const keys = await caches.keys();
            await Promise.all(
                keys
                    .filter((k) => k.startsWith("gs-") && ![STATIC_CACHE, RUNTIME_CACHE].includes(k))
                    .map((k) => caches.delete(k))
            );

            await self.clients.claim();
        })()
    );
});

// Allow the UI to apply the update
self.addEventListener("message", (event) => {
    if (event.data === "SKIP_WAITING") {
        self.skipWaiting();
    }
});

self.addEventListener("fetch", (event) => {
    const req = event.request;

    if (req.method !== "GET") return;

    const url = new URL(req.url);

    // Only same-origin
    if (url.origin !== self.location.origin) return;

    const accept = (req.headers.get("accept") || "").toLowerCase();

    // 1) Navigations (HTML): network-first
    if (req.mode === "navigate" || accept.includes("text/html")) {
        event.respondWith(
            (async () => {
                try {
                    const fresh = await fetch(req);

                    if (fresh && fresh.ok) {
                        const cache = await caches.open(RUNTIME_CACHE);
                        cache.put(req, fresh.clone());
                    }

                    return fresh;
                } catch (err) {
                    const cache = await caches.open(RUNTIME_CACHE);

                    const cached = await cache.match(req);
                    if (cached) return cached;

                    const offline = await cache.match(OFFLINE_URL);
                    if (offline) return offline;

                    return new Response(
                        "<h1>Offline</h1><p>You appear to be offline. Please reconnect and try again.</p>",
                        { headers: { "Content-Type": "text/html; charset=utf-8" }, status: 200 }
                    );
                }
            })()
        );
        return;
    }

    // 2) Static assets: cache-first
    if (CACHEABLE_ASSET.test(url.pathname)) {
        event.respondWith(
            (async () => {
                const cache = await caches.open(STATIC_CACHE);
                const cached = await cache.match(req);
                if (cached) return cached;

                const fresh = await fetch(req);
                if (fresh && fresh.ok && fresh.type === "basic") {
                    cache.put(req, fresh.clone());
                }
                return fresh;
            })()
        );
        return;
    }

    // 3) Other GETs: network-first with cache fallback
    event.respondWith(
        (async () => {
            const cache = await caches.open(RUNTIME_CACHE);

            try {
                const fresh = await fetch(req);
                if (fresh && fresh.ok && fresh.type === "basic") {
                    cache.put(req, fresh.clone());
                }
                return fresh;
            } catch (err) {
                const cached = await cache.match(req);
                if (cached) return cached;
                throw err;
            }
        })()
    );
});
