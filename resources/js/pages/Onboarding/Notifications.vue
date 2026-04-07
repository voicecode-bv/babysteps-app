<script setup lang="ts">
import { useTranslations } from '@/composables/useTranslations';
import { router } from '@inertiajs/vue3';
import { PushNotifications, On, Events } from '@nativephp/mobile';

const { t } = useTranslations();

const sendToken = (token: string) => {
    router.post('/device-token', { token }, { preserveState: true, preserveScroll: true });
};

On(Events.PushNotification.TokenGenerated, ({ token }: { token: string }) => {
    sendToken(token);
});

async function enableNotifications() {
    await PushNotifications.enroll();

    const result = await PushNotifications.getToken();
    if (result?.token) {
        sendToken(result.token);
    }

    router.visit('/');
}

function skip() {
    router.visit('/');
}
</script>

<template>
    <div class="flex min-h-dvh flex-col bg-sand-50 px-8 text-sand-900 dark:bg-sand-900 dark:text-sand-100">
        <div class="flex flex-1 flex-col items-center justify-center">
            <div class="mb-6 flex size-20 items-center justify-center rounded-full bg-sand-100 dark:bg-sand-800">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-10 text-sand-500 dark:text-sand-400">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                </svg>
            </div>

            <h1 class="font-display text-2xl font-bold tracking-tight text-sand-800 dark:text-sand-100">
                {{ t('Stay in the loop') }}
            </h1>

            <p class="mt-3 max-w-xs text-center text-sm leading-relaxed text-sand-500 dark:text-sand-400">
                {{ t('Enable notifications so you never miss a moment. We\'ll let you know when:') }}
            </p>

            <ul class="mt-6 w-full max-w-xs space-y-4">
                <li class="flex items-start gap-3">
                    <div class="mt-0.5 flex size-8 flex-shrink-0 items-center justify-center rounded-full bg-sand-100 dark:bg-sand-800">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4 text-sand-500 dark:text-sand-400">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0 0 22.5 18.75V5.25A2.25 2.25 0 0 0 20.25 3H3.75A2.25 2.25 0 0 0 1.5 5.25v13.5A2.25 2.25 0 0 0 3.75 21Z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-sand-700 dark:text-sand-200">{{ t('New photos') }}</p>
                        <p class="text-xs text-sand-400 dark:text-sand-500">{{ t('Someone in your circle shared a new moment') }}</p>
                    </div>
                </li>

                <li class="flex items-start gap-3">
                    <div class="mt-0.5 flex size-8 flex-shrink-0 items-center justify-center rounded-full bg-sand-100 dark:bg-sand-800">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4 text-sand-500 dark:text-sand-400">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-sand-700 dark:text-sand-200">{{ t('Likes') }}</p>
                        <p class="text-xs text-sand-400 dark:text-sand-500">{{ t('Someone liked your photo') }}</p>
                    </div>
                </li>

                <li class="flex items-start gap-3">
                    <div class="mt-0.5 flex size-8 flex-shrink-0 items-center justify-center rounded-full bg-sand-100 dark:bg-sand-800">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4 text-sand-500 dark:text-sand-400">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 20.25c4.97 0 9-3.694 9-8.25s-4.03-8.25-9-8.25S3 7.444 3 12c0 2.104.859 4.023 2.273 5.48.432.447.74 1.04.586 1.641a4.483 4.483 0 0 1-.923 1.785A5.969 5.969 0 0 0 6 21c1.282 0 2.47-.402 3.445-1.087.81.22 1.668.337 2.555.337Z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-sand-700 dark:text-sand-200">{{ t('Comments') }}</p>
                        <p class="text-xs text-sand-400 dark:text-sand-500">{{ t('Someone commented on your photo') }}</p>
                    </div>
                </li>
            </ul>
        </div>

        <div class="pb-12">
            <button
                class="w-full rounded-xl bg-sand-500 py-3.5 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-sand-600 dark:bg-sand-400 dark:text-sand-900 dark:hover:bg-sand-300"
                @click="enableNotifications"
            >
                {{ t('Enable notifications') }}
            </button>

            <button
                class="mt-3 w-full py-2 text-sm text-sand-400 dark:text-sand-500"
                @click="skip"
            >
                {{ t('Maybe later') }}
            </button>
        </div>
    </div>
</template>
