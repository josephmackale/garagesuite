// resources/js/pwa.js
// GarageSuite PWA: Install button + Update available (persistent, garage-user friendly)
//
// Exposes:
//   window.GS_PWA = {
//     canInstall(): boolean,
//     promptInstall(): Promise<void>,
//     hasUpdate(): boolean,
//     applyUpdate(): void,
//     onUpdate(cb: () => void): void   // optional hook (if you still want it)
//   }
//
// Fires window events for Alpine/UI:
//   - "gs-pwa-install-availability"  (when install becomes available/unavailable)
//   - "gs-pwa-update-available"      (when an update is waiting)

let deferredPrompt = null;
let updateCallbacks = [];

// Track update state
let swReg = null;
let updateAvailable = false;

// Detect standalone (installed) mode
function isStandalone() {
    return (
        window.matchMedia("(display-mode: standalone)").matches ||
        window.navigator.standalone === true
    );
}

// --- Install availability event ---
let installAvailable = false;
function setInstallAvailable(v) {
    installAvailable = !!v;
    window.dispatchEvent(
        new CustomEvent("gs-pwa-install-availability", {
            detail: { available: installAvailable },
        })
    );
}

// --- Update availability event ---
function setUpdateAvailable(v) {
    updateAvailable = !!v;
    if (updateAvailable) {
        // Notify listeners + UI
        updateCallbacks.forEach((cb) => {
            try { cb(); } catch (_) { }
        });
        window.dispatchEvent(new Event("gs-pwa-update-available"));
    }
}

// Capture install prompt
window.addEventListener("beforeinstallprompt", (e) => {
    // Stop Chrome from showing its own mini-infobar
    e.preventDefault();
    deferredPrompt = e;

    // Install makes sense only if not already installed
    setInstallAvailable(!isStandalone());
});

window.addEventListener("appinstalled", () => {
    deferredPrompt = null;
    setInstallAvailable(false);
});

// Public API
window.GS_PWA = {
    canInstall() {
        return !!deferredPrompt && !isStandalone();
    },

    async promptInstall() {
        if (!deferredPrompt) return;

        deferredPrompt.prompt();
        try {
            // accepted/dismissed
            await deferredPrompt.userChoice;
        } finally {
            deferredPrompt = null;
            setInstallAvailable(false);
        }
    },

    hasUpdate() {
        return !!updateAvailable && !!swReg && !!swReg.waiting;
    },

    // Activate the waiting SW and reload when it takes control
    applyUpdate() {
        try {
            if (swReg && swReg.waiting) {
                swReg.waiting.postMessage("SKIP_WAITING");
            } else {
                // fallback: reload (might pick up update if it was just installed)
                window.location.reload();
            }
        } catch (err) {
            window.location.reload();
        }
    },

    // Optional hook (if any UI wants a callback)
    onUpdate(cb) {
        if (typeof cb === "function") updateCallbacks.push(cb);
    },
};

// --- Service Worker: register + detect updates ---
async function registerSW() {
    if (!("serviceWorker" in navigator)) return;

    try {
        // IMPORTANT: if your SW file is /sw.js, change this path.
        swReg = await navigator.serviceWorker.register("/service-worker.js");

        // If there's already a waiting SW, update is available now
        if (swReg.waiting && navigator.serviceWorker.controller) {
            setUpdateAvailable(true);
        }

        // When a new SW is found
        swReg.addEventListener("updatefound", () => {
            const newWorker = swReg.installing;
            if (!newWorker) return;

            newWorker.addEventListener("statechange", () => {
                // When installed AND there is an existing controller,
                // it means an update is ready (waiting) but not controlling yet.
                if (newWorker.state === "installed" && navigator.serviceWorker.controller) {
                    setUpdateAvailable(true);
                }
            });
        });

        // When the new SW takes control, reload to serve fresh assets
        navigator.serviceWorker.addEventListener("controllerchange", () => {
            window.location.reload();
        });

        // Keep install availability correct on first load
        setInstallAvailable(!!deferredPrompt && !isStandalone());
    } catch (err) {
        // silent fail (don’t break app)
        console.warn("SW register failed:", err);
    }
}

// Run after load (safer + avoids slowing first paint)
window.addEventListener("load", registerSW);

// Backwards compatible helper (if you already wired this in the UI)
window.GS_PWA_APPLY_UPDATE = function () {
    if (window.GS_PWA && window.GS_PWA.applyUpdate) window.GS_PWA.applyUpdate();
    else window.location.reload();
};
