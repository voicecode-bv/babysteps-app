import { createInertiaApp, router } from '@inertiajs/vue3';
import { FetchHttpClient } from '@/http/FetchHttpClient';

declare global {
    interface Window {
        router: typeof router;
    }
}

if (typeof window !== 'undefined') {
    window.router = router;
}

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    progress: {
        color: '#4B5563',
    },
    http: new FetchHttpClient(),
});
