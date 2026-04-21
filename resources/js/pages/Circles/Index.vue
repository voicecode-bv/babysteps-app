<script setup lang="ts">
import Button from '@/components/Button.vue';
import IconTile from '@/components/IconTile.vue';
import PullToRefreshIndicator from '@/components/PullToRefreshIndicator.vue';
import SurfaceCard from '@/components/SurfaceCard.vue';
import { usePullToRefresh } from '@/composables/usePullToRefresh';
import { useTranslations } from '@/composables/useTranslations';
import AppLayout from '@/layouts/AppLayout.vue';
import { Deferred, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref, useTemplateRef } from 'vue';
import usersIcon from '../../../svg/doodle-icons/user.svg';

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

function goBack() {
    window.history.back();
}

const layoutRef = useTemplateRef<InstanceType<typeof AppLayout>>('layout');
const containerRef = computed(() => layoutRef.value?.mainRef ?? null);

const { pullDistance, isRefreshing } = usePullToRefresh({
    onRefresh: () =>
        new Promise<void>((resolve) => {
            router.reload({
                only: ['circles'],
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
            <button class="flex items-center text-teal dark:text-sand-300" @click="goBack">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                </svg>
            </button>
        </template>
        <template #header-right>
            <button
                class="flex size-9 items-center justify-center rounded-full bg-teal text-white shadow-sm transition hover:bg-teal-light"
                :aria-label="t('Create circle')"
                @click="showCreateForm = !showCreateForm"
            >
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-5">
                    <path v-if="!showCreateForm" stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    <path v-else stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                </svg>
            </button>
        </template>

        <div class="relative mt-10 min-h-full bg-warmwhite pb-[calc(theme(spacing.24)+env(safe-area-inset-bottom))] dark:bg-sand-900">
            <!-- Soft blobs -->
            <div aria-hidden="true" class="pointer-events-none absolute inset-x-0 top-0 h-72 overflow-hidden">
                <div class="absolute -left-16 top-0 size-64 rounded-full bg-sage-200/40 blur-3xl dark:bg-sage-700/20"></div>
                <div class="absolute -right-16 top-10 size-64 rounded-full bg-accent-soft/30 blur-3xl dark:bg-accent/10"></div>
            </div>

            <PullToRefreshIndicator :pull-distance="pullDistance" :is-refreshing="isRefreshing" />

            <div class="relative space-y-4 px-4 pt-4">
                <!-- Create Circle Form -->
                <Transition
                    enter-active-class="transition duration-200 ease-out"
                    enter-from-class="-translate-y-2 opacity-0"
                    enter-to-class="translate-y-0 opacity-100"
                    leave-active-class="transition duration-150 ease-in"
                    leave-from-class="translate-y-0 opacity-100"
                    leave-to-class="-translate-y-2 opacity-0"
                >
                    <SurfaceCard v-if="showCreateForm">
                        <form class="space-y-3" @submit.prevent="createCircle">
                            <label class="text-xs font-medium uppercase tracking-wider text-sand-500 dark:text-sand-400">
                                {{ t('New circle') }}
                            </label>
                            <input
                                v-model="form.name"
                                type="text"
                                :placeholder="t('Circle name...')"
                                class="field"
                                autofocus
                            />
                            <p v-if="form.errors.name" class="text-xs text-accent">{{ form.errors.name }}</p>
                            <div class="flex justify-end gap-2">
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="md"
                                    @click="showCreateForm = false; form.reset(); form.clearErrors();"
                                >
                                    {{ t('Cancel') }}
                                </Button>
                                <Button
                                    type="submit"
                                    size="md"
                                    :disabled="form.processing || !form.name.trim()"
                                >
                                    {{ t('Create') }}
                                </Button>
                            </div>
                        </form>
                    </SurfaceCard>
                </Transition>

                <!-- Circles List -->
                <Deferred data="circles">
                    <template #fallback>
                        <div />
                    </template>

                    <ul v-if="circles && circles.length > 0" class="space-y-3">
                        <li v-for="circle in circles" :key="circle.id">
                            <Link
                                :href="`/circles/${circle.id}`"
                                class="flex items-center gap-4 rounded-lg bg-white/70 p-4 shadow-sm backdrop-blur-sm transition hover:bg-white active:scale-[0.99] dark:border-sand-700/50 dark:bg-sand-800/60 dark:hover:bg-sand-800"
                            >
                                <img
                                    v-if="circle.photo"
                                    :src="circle.photo"
                                    :alt="circle.name"
                                    class="size-12 shrink-0 rounded-lg object-cover"
                                />
                                <IconTile v-else :icon="usersIcon" size="md" tone="sage" />
                                <div class="min-w-0 flex-1">
                                    <p class="truncate font-display text-base font-semibold text-teal dark:text-sand-100">{{ circle.name }}</p>
                                    <p class="text-sm text-sand-600 dark:text-sand-400">
                                        {{ circle.members_count === 1 ? t(':count member', { count: circle.members_count }) : t(':count members', { count: circle.members_count }) }}
                                    </p>
                                </div>
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-5 text-sand-400 dark:text-sand-500">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                                </svg>
                            </Link>
                        </li>
                    </ul>

                    <!-- Empty State -->
                    <SurfaceCard v-else-if="circles && circles.length === 0">
                        <div class="flex flex-col items-center px-2 py-4 text-center">
                            <IconTile :icon="usersIcon" size="lg" tone="sage" class="mb-4" />
                            <h3 class="font-display text-lg font-semibold text-teal dark:text-sand-100">{{ t('No circles yet') }}</h3>
                            <p class="mt-1 text-sm text-sand-600 dark:text-sand-400">
                                {{ t('Create a circle to share moments with specific people.') }}
                            </p>
                            <div class="mt-5">
                                <Button size="md" @click="showCreateForm = true">
                                    {{ t('Create your first circle') }}
                                </Button>
                            </div>
                        </div>
                    </SurfaceCard>
                </Deferred>
            </div>
        </div>
    </AppLayout>
</template>
