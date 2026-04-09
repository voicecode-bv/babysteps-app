<script setup lang="ts">
import Button from '@/components/Button.vue';
import PullToRefreshIndicator from '@/components/PullToRefreshIndicator.vue';
import { usePullToRefresh } from '@/composables/usePullToRefresh';
import { useTranslations } from '@/composables/useTranslations';
import AppLayout from '@/layouts/AppLayout.vue';
import { Deferred, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref, useTemplateRef } from 'vue';

interface Circle {
    id: number;
    name: string;
    photo: string | null;
    members_count: number;
    created_at: string;
}

defineProps<{
    circles?: Circle[];
}>();

const { t } = useTranslations();

const layoutRef = useTemplateRef<InstanceType<typeof AppLayout>>('layout');
const containerRef = computed(() => layoutRef.value?.mainRef ?? null);

const { pullDistance, isRefreshing } = usePullToRefresh({
    onRefresh: () =>
        new Promise<void>((resolve) => {
            router.reload({
                onFinish: () => resolve(),
            });
        }),
    containerRef,
});

const showCreateForm = ref(false);
const form = useForm({ name: '' });

function createCircle() {
    form.post('/circles', {
        onSuccess: () => {
            form.reset();
            showCreateForm.value = false;
        },
    });
}
</script>

<template>
    <AppLayout ref="layout" :title="t('Circles')">
        <template #header-left>
            <Link href="/" class="text-sand-700 dark:text-sand-300">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                </svg>
            </Link>
        </template>
        <template #header-right>
            <button class="text-sand-600 dark:text-sand-400" @click="showCreateForm = !showCreateForm">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
            </button>
        </template>

        <PullToRefreshIndicator :pull-distance="pullDistance" :is-refreshing="isRefreshing" />

        <!-- Create Circle Form -->
        <div v-if="showCreateForm" class="border-b border-sand-200 bg-white px-4 py-3 dark:border-sand-800 dark:bg-sand-900">
            <form class="flex items-center gap-2" @submit.prevent="createCircle">
                <input
                    v-model="form.name"
                    type="text"
                    :placeholder="t('Circle name...')"
                    class="field flex-1"
                />
                <Button
                    type="submit"
                    size="sm"
                    :disabled="form.processing || !form.name.trim()"
                >
                    {{ t('Create') }}
                </Button>
            </form>
            <p v-if="form.errors.name" class="mt-1 text-xs text-blush-500">{{ form.errors.name }}</p>
        </div>

        <!-- Circles List -->
        <Deferred data="circles">
            <template #fallback>
                <div class="divide-y divide-sand-100 dark:divide-sand-800">
                    <div v-for="n in 4" :key="n" class="flex items-center gap-3 bg-white px-4 py-3 dark:bg-sand-900">
                        <div class="size-12 animate-pulse rounded-full bg-sand-200 dark:bg-sand-700" />
                        <div class="flex-1 space-y-2">
                            <div class="h-3.5 w-28 animate-pulse rounded bg-sand-200 dark:bg-sand-700" />
                            <div class="h-3 w-20 animate-pulse rounded bg-sand-200 dark:bg-sand-700" />
                        </div>
                    </div>
                </div>
            </template>

            <div class="divide-y divide-sand-100 dark:divide-sand-800">
                <Link
                    v-for="circle in circles"
                    :key="circle.id"
                    :href="`/circles/${circle.id}`"
                    class="flex items-center gap-3 bg-white px-4 py-3 active:bg-sand-50 dark:bg-sand-900 dark:active:bg-sand-800"
                >
                    <img
                        v-if="circle.photo"
                        :src="circle.photo"
                        :alt="circle.name"
                        class="size-12 rounded-full object-cover"
                    />
                    <div v-else class="flex size-12 items-center justify-center rounded-full bg-sand-200 dark:bg-sand-800">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.3" stroke="currentColor" class="size-6 text-sand-600 dark:text-sand-300">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-semibold text-sand-800 dark:text-sand-100">{{ circle.name }}</p>
                        <p class="text-xs text-sand-500 dark:text-sand-400">
                            {{ circle.members_count === 1 ? t(':count member', { count: circle.members_count }) : t(':count members', { count: circle.members_count }) }}
                        </p>
                    </div>
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4 text-sand-400 dark:text-sand-500">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                    </svg>
                </Link>
            </div>

            <!-- Empty State -->
            <div v-if="circles && circles.length === 0" class="flex flex-col items-center justify-center px-8 py-20">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" class="mb-4 size-16 text-sand-300 dark:text-sand-600">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                </svg>
                <h3 class="font-display text-lg font-semibold text-sand-800 dark:text-sand-200">{{ t('No circles yet') }}</h3>
                <p class="mt-1 text-center text-sm text-sand-500 dark:text-sand-400">
                    {{ t('Create a circle to share moments with specific people.') }}
                </p>
            </div>
        </Deferred>
    </AppLayout>
</template>
