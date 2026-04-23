import { Dialog, Network } from '@nativephp/mobile';
import { onMounted, onUnmounted, ref } from 'vue';
import { useTranslations } from '@/composables/useTranslations';

const POLL_INTERVAL_MS = 15_000;

export function useNetworkStatus() {
    const { t } = useTranslations();
    const isOnline = ref(true);

    let intervalId: number | undefined;
    let alertVisible = false;

    async function fetchConnected(): Promise<boolean> {
        try {
            const status = await Network.status();
            return !!status?.connected;
        } catch {
            return typeof navigator !== 'undefined' ? navigator.onLine : true;
        }
    }

    async function showOfflineAlert() {
        if (alertVisible) {
            return;
        }
        alertVisible = true;
        try {
            await Dialog.alert(
                t('No internet connection'),
                t("It looks like you're offline. Check your connection and try again."),
            );
        } finally {
            alertVisible = false;
        }
    }

    async function check() {
        const connected = await fetchConnected();

        if (!connected && isOnline.value) {
            isOnline.value = false;
            void showOfflineAlert();
            return;
        }

        if (connected && !isOnline.value) {
            isOnline.value = true;
        }
    }

    function handleBrowserOnline() {
        isOnline.value = true;
    }

    function handleBrowserOffline() {
        void check();
    }

    function handleVisibilityChange() {
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
    });

    return { isOnline };
}
