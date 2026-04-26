<script setup lang="ts">
import { computed, onMounted, ref, useTemplateRef } from 'vue';
import { useRouter } from 'vue-router';
import IconTile from '@/components/IconTile.vue';
import PullToRefreshIndicator from '@/components/PullToRefreshIndicator.vue';
import SurfaceCard from '@/components/SurfaceCard.vue';
import AppLayout from '@/spa/layouts/AppLayout.vue';
import { useTranslations } from '@/spa/composables/useTranslations';
import { usePullToRefresh } from '@/spa/composables/usePullToRefresh';
import { useToastsStore } from '@/spa/stores/toasts';
import { externalApi } from '@/spa/http/externalApi';
import bellIcon from '../../../../svg/doodle-icons/bell.svg';

interface Preferences {
    post_liked: boolean;
    post_commented: boolean;
    comment_liked: boolean;
    comment_replied: boolean;
    new_circle_post: boolean;
    circle_invitation_accepted: boolean;
    circle_ownership_transfer_requested: boolean;
    circle_ownership_transfer_accepted: boolean;
    circle_ownership_transfer_declined: boolean;
}

const { t } = useTranslations();
const router = useRouter();
const toasts = useToastsStore();

const preferences = ref<Preferences | null>(null);

function goBack(): void {
    router.push({ name: 'spa.settings' });
}

const layoutRef = useTemplateRef<InstanceType<typeof AppLayout>>('layout');
const containerRef = computed(() => layoutRef.value?.mainRef ?? null);

async function loadPreferences(): Promise<void> {
    try {
        const data = await externalApi.get<{ data: Preferences }>('/notification-preferences');
        preferences.value = data.data;
    } catch {
        preferences.value = null;
    }
}

const { pullDistance, isRefreshing } = usePullToRefresh({
    onRefresh: loadPreferences,
    containerRef,
});

onMounted(loadPreferences);

const labels = computed<Record<keyof Preferences, string>>(() => ({
    post_liked: t('Post liked'),
    post_commented: t('Post commented'),
    comment_liked: t('Comment liked'),
    comment_replied: t('Comment replied'),
    new_circle_post: t('New circle post'),
    circle_invitation_accepted: t('Circle invitation accepted'),
    circle_ownership_transfer_requested: t('Circle ownership transfer requested'),
    circle_ownership_transfer_accepted: t('Circle ownership transfer accepted'),
    circle_ownership_transfer_declined: t('Circle ownership transfer declined'),
}));

async function togglePreference(key: keyof Preferences): Promise<void> {
    if (!preferences.value) {
        return;
    }

    preferences.value[key] = !preferences.value[key];

    try {
        await externalApi.put('/notification-preferences', preferences.value);
    } catch {
        preferences.value[key] = !preferences.value[key];
        toasts.error(t('Failed to update preferences'));
    }
}
</script>

<template>
    <AppLayout ref="layout" :title="t('Push notifications')">
        <template #header-left>
            <button class="flex items-center text-teal dark:text-sand-300" @click="goBack">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                </svg>
            </button>
        </template>

        <div class="relative mt-10 min-h-full pb-[calc(theme(spacing.40)+env(safe-area-inset-bottom))]">
            <PullToRefreshIndicator :pull-distance="pullDistance" :is-refreshing="isRefreshing" />

            <div class="relative space-y-4 px-4 pt-4 pb-24">
                <SurfaceCard>
                    <h3 class="flex items-center gap-3 text-sm font-semibold text-sand-900 dark:text-sand-100">
                        <IconTile :icon="bellIcon" size="sm" tone="sage" />
                        {{ t('Push notifications') }}
                    </h3>

                    <ul v-if="!preferences" class="mt-3 divide-y divide-sand-100 dark:divide-sand-700/60">
                        <li v-for="(label, key) in labels" :key="key" class="flex items-center justify-between gap-3 py-3">
                            <span class="text-base text-sand-800 dark:text-sand-100">{{ label }}</span>
                            <span class="h-8 w-14 shrink-0 animate-pulse rounded-full bg-sand-200 dark:bg-sand-700/60" />
                        </li>
                    </ul>

                    <ul v-else class="mt-3 divide-y divide-sand-100 dark:divide-sand-700/60">
                        <li v-for="(label, key) in labels" :key="key">
                            <label class="flex cursor-pointer items-center justify-between gap-3 py-3">
                                <span class="text-base text-sand-800 dark:text-sand-100">{{ label }}</span>
                                <button
                                    type="button"
                                    role="switch"
                                    :aria-checked="preferences[key]"
                                    :class="preferences[key] ? 'bg-teal' : 'bg-sand-300 dark:bg-sand-600'"
                                    class="relative inline-flex h-8 w-14 shrink-0 cursor-pointer rounded-full transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-teal/40"
                                    @click="togglePreference(key)"
                                >
                                    <span
                                        :class="preferences[key] ? 'translate-x-7' : 'translate-x-1'"
                                        class="pointer-events-none mt-1 size-6 rounded-full bg-white shadow transition-transform"
                                    />
                                </button>
                            </label>
                        </li>
                    </ul>
                </SurfaceCard>
            </div>
        </div>
    </AppLayout>
</template>
