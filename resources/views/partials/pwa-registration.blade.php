<script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/sw.js').then(async () => {
                if (! /^\/(?:ar|en|fr)\/auth\/quick-attendance\/?$/.test(window.location.pathname)) {
                    return;
                }

                const registration = await navigator.serviceWorker.ready;
                const assetPattern = /\.(?:css|js|mjs|woff2?|ttf|otf|eot|png|svg|ico|jpe?g|webp|gif|avif)(?:\?|$)/i;

                const assetUrls = performance.getEntriesByType('resource')
                    .map((entry) => entry.name)
                    .filter((url) => {
                        try {
                            return new URL(url).origin === window.location.origin && assetPattern.test(url);
                        } catch {
                            return false;
                        }
                    });

                (registration.active ?? navigator.serviceWorker.controller)?.postMessage({
                    type: 'CACHE_QUICK_ATTENDANCE',
                    pageUrl: window.location.href,
                    assetUrls,
                });
            }).catch((error) => {
                console.error('Sajel service worker registration failed.', error);
            });
        });
    }
</script>
