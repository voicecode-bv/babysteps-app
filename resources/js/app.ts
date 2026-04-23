import { createInertiaApp, router } from '@inertiajs/vue3';
import { flare } from '@flareapp/js';
import { flareVue } from '@flareapp/vue';
import { createApp, h } from 'vue';
import { FetchHttpClient } from '@/http/FetchHttpClient';

declare global {
    interface Window {
        router: typeof router;
    }
}

if (typeof window !== 'undefined') {
    window.router = router;
}

if (import.meta.env.PROD) {
    flare.light();
}

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    progress: {
        color: '#4B5563',
    },
    http: new FetchHttpClient(),
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(flareVue)
            .mount(el);
    },
});
