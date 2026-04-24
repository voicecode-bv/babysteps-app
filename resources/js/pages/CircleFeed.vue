<script setup lang="ts">
import { InfiniteScroll, Link, router } from '@inertiajs/vue3';
import { computed, useTemplateRef } from 'vue';
import PostCard from '@/components/PostCard.vue';
import type { PostData } from '@/components/PostCard.vue';
import PullToRefreshIndicator from '@/components/PullToRefreshIndicator.vue';
import { usePullToRefresh } from '@/composables/usePullToRefresh';
import { useTranslations } from '@/composables/useTranslations';
import AppLayout from '@/layouts/AppLayout.vue';
import cameraIcon from '../../svg/doodle-icons/camera.svg';
import starIcon from '../../svg/doodle-icons/star.svg';
import userIcon from '../../svg/doodle-icons/user.svg';

function iconMaskStyle(url: string) {
    return {
        maskImage: `url(${url})`,
        WebkitMaskImage: `url(${url})`,
        maskSize: 'contain',
        WebkitMaskSize: 'contain',
        maskRepeat: 'no-repeat',
        WebkitMaskRepeat: 'no-repeat',
        maskPosition: 'center',
        WebkitMaskPosition: 'center',
    };
}

interface Circle {
    id: number;
    name: string;
    photo: string | null;
}

const props = defineProps<{
    circle: Circle;
    posts: {
        data: PostData[];
    };
}>();

const { t } = useTranslations();

const layoutRef = useTemplateRef<InstanceType<typeof AppLayout>>('layout');
const containerRef = computed(() => layoutRef.value?.mainRef ?? null);

const { pullDistance, isRefreshing } = usePullToRefresh({
    onRefresh: () =>
        new Promise<void>((resolve) => {
            router.reload({
                only: ['circle', 'posts'],
                reset: ['posts'],
                data: { page: 1 },
                onFinish: () => resolve(),
            });
        }),
    containerRef,
});

function goBack() {
    if (window.history.length > 1) {
        window.history.back();
    } else {
        router.visit('/');
    }
}
</script>

<template>
    <AppLayout ref="layout" :title="circle?.name ?? ''">
        <template #header-left>
            <button class="flex items-center text-sand-700 dark:text-sand-300" @click="goBack">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                </svg>
            </button>
        </template>

        <template #header-right>
            <Link
                v-if="props.circle"
                :href="`/circles/${props.circle.id}`"
                class="flex size-9 items-center justify-center rounded-full bg-white/80 shadow-sm transition hover:bg-white dark:bg-sand-800/70"
                :aria-label="t('Circle details')"
            >
                <img
                    v-if="props.circle.photo"
                    :src="props.circle.photo"
                    :alt="props.circle.name"
                    class="size-8 rounded-full object-cover"
                />
                <span
                    v-else
                    aria-hidden="true"
                    class="inline-block size-5 bg-sand-600 dark:bg-sand-300"
                    :style="iconMaskStyle(userIcon)"
                ></span>
            </Link>
        </template>

        <div class="mt-10 pb-24">
            <PullToRefreshIndicator :pull-distance="pullDistance" :is-refreshing="isRefreshing" />

            <template v-if="!posts">
                <div v-for="n in 3" :key="n" class="animate-pulse">
                    <div class="flex items-center gap-3 px-4 py-3">
                        <div class="size-10 rounded-full bg-sand-200 dark:bg-sand-700" />
                        <div class="h-3 w-32 rounded bg-sand-200 dark:bg-sand-700" />
                    </div>
                    <div class="aspect-square w-full bg-sand-200 dark:bg-sand-700" />
                    <div class="space-y-2 px-4 py-3">
                        <div class="h-3 w-24 rounded bg-sand-200 dark:bg-sand-700" />
                        <div class="h-3 w-48 rounded bg-sand-200 dark:bg-sand-700" />
                    </div>
                </div>
            </template>

            <InfiniteScroll v-else data="posts" only-next :buffer="0" preserve-url class="no-scrollbar">
                <template #default>
                    <PostCard v-for="post in posts.data" :key="post.id" :post="post" />
                </template>
                <template #loading>
                    <div class="flex items-center justify-center gap-2 py-6 text-sm text-sand-500 dark:text-sand-400">
                        <span class="flex items-center gap-1">
                            <span class="dot dot-1 size-1.5 rounded-full bg-teal"></span>
                            <span class="dot dot-2 size-1.5 rounded-full bg-accent"></span>
                            <span class="dot dot-3 size-1.5 rounded-full bg-sage-500"></span>
                        </span>
                        {{ t('Loading more...') }}
                    </div>
                </template>
            </InfiniteScroll>

            <Link v-if="posts && posts.data.length === 0" href="/posts/create" class="relative flex min-h-[calc(100dvh-6rem-var(--inset-top))] flex-col items-center justify-center overflow-hidden px-8 py-20">
                <div aria-hidden="true" class="pointer-events-none absolute inset-0">
                    <div class="absolute -left-16 top-4 size-56 rounded-full bg-sage-200/50 blur-3xl dark:bg-sage-700/20"></div>
                    <div class="absolute -right-16 bottom-0 size-64 rounded-full bg-accent-soft/30 blur-3xl dark:bg-accent/10"></div>
                </div>

                <div class="relative mb-5 flex size-24 rotate-[-6deg] items-center justify-center rounded-3xl bg-white shadow-lg shadow-sand-900/5 dark:bg-sand-800">
                    <span aria-hidden="true" class="inline-block size-12 bg-teal" :style="iconMaskStyle(cameraIcon)"></span>
                    <span aria-hidden="true" class="absolute -right-2 -top-2 flex size-8 rotate-12 items-center justify-center rounded-full bg-accent shadow-md">
                        <span class="inline-block size-4 bg-white" :style="iconMaskStyle(starIcon)"></span>
                    </span>
                </div>

                <h3 class="relative font-display text-xl font-semibold text-teal">{{ t('No moments in this circle yet') }}</h3>
                <p class="relative mt-2 text-center text-sm text-sand-600 dark:text-sand-400">
                    {{ t('Share a photo to get things started.') }}
                </p>
            </Link>
        </div>
    </AppLayout>
</template>

<style scoped>
.dot-1 { animation-delay: 0s; }
.dot-2 { animation-delay: 0.15s; }
.dot-3 { animation-delay: 0.3s; }
</style>
