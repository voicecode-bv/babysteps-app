import { Network } from '@nativephp/mobile';
import { onMounted, onUnmounted, ref } from 'vue';
import { useTranslations } from '@/spa/composables/useTranslations';
import { useToastsStore } from '@/spa/stores/toasts';

const POLL_INTERVAL_MS = 15_000;

/**
 * Volgt de netwerkstatus via de NativePhp Network plugin (met fallback naar
 * `navigator.onLine` voor web). Bij een transitie online → offline laat de
 * SPA een toast zien; bij offline → online verdwijnt die automatisch.
 */
export function useNetworkStatus() {
    const { t } = useTranslations();
    const toasts = useToastsStore();
    const isOnline = ref(true);

    let intervalId: number | undefined;
    let offlineToastId: number | null = null;

    async function fetchConnected(): Promise<boolean> {
        try {
            const status = await Network.status();
            return !!status?.connected;
        } catch {
            return typeof navigator !== 'undefined' ? navigator.onLine : true;
        }
    }

    function showOfflineToast(): void {
        if (offlineToastId !== null) return;
        offlineToastId = toasts.error(t('No internet connection'), 0);
    }

    function clearOfflineToast(): void {
        if (offlineToastId !== null) {
            toasts.dismiss(offlineToastId);
            offlineToastId = null;
        }
    }

    async function check(): Promise<void> {
        const connected = await fetchConnected();

        if (!connected && isOnline.value) {
            isOnline.value = false;
            showOfflineToast();
            return;
        }

        if (connected && !isOnline.value) {
            isOnline.value = true;
            clearOfflineToast();
        }
    }

    function handleBrowserOnline(): void {
        isOnline.value = true;
        clearOfflineToast();
    }

    function handleBrowserOffline(): void {
        void check();
    }

    function handleVisibilityChange(): void {
        if (document.visibilityState === 'visible') {
            void check();
        }
    }

    onMounted(() => {
        void check();
        window.addEventListener('online', handleBrowserOnline);
        window.addEventListener('offline', handleBrowserOffline);
        document.addEventListener('visibilitychange', handleVisibilityChange);
        intervalId = window.setInterval(check, POLL_INTERVAL_MS);
    });

    onUnmounted(() => {
        if (intervalId !== undefined) {
            window.clearInterval(intervalId);
        }
        window.removeEventListener('online', handleBrowserOnline);
        window.removeEventListener('offline', handleBrowserOffline);
        document.removeEventListener('visibilitychange', handleVisibilityChange);
        clearOfflineToast();
    });

    return { isOnline };
}
