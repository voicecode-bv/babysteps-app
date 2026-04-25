<script setup lang="ts">
import { InfiniteScroll, Link, router } from '@inertiajs/vue3';
import { computed, reactive, useTemplateRef } from 'vue';
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
    posts?: {
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

const loadedMedia = reactive<Record<number, boolean>>({});
function markLoaded(postId: number) {
    loadedMedia[postId] = true;
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

            <!-- Skeleton grid while loading -->
            <div v-if="!posts" class="grid grid-cols-3 gap-0.5 bg-sand-100 dark:bg-sand-800">
                <div v-for="n in 30" :key="n" class="aspect-square animate-pulse bg-sand-200 dark:bg-sand-700" />
            </div>

            <!-- Posts Grid -->
            <InfiniteScroll v-else data="posts" only-next :buffer="0" preserve-url items-element="#circle-posts-grid" class="no-scrollbar">
                <div id="circle-posts-grid" class="grid grid-cols-3 gap-0.5 bg-sand-100 dark:bg-sand-800">
                    <Link
                        v-for="post in posts.data"
                        :key="post.id"
                        :href="`/posts/${post.id}`"
                        class="relative block aspect-square overflow-hidden bg-sand-100 dark:bg-sand-800"
                    >
                        <div v-if="!loadedMedia[post.id] && post.media_type !== 'unknown'" class="absolute inset-0 animate-pulse bg-sand-200 dark:bg-sand-700" />
                        <img
                            v-if="post.media_type === 'image'"
                            :src="post.media_url"
                            :alt="post.caption ?? t('Photo')"
                            class="relative size-full object-cover transition-opacity duration-300"
                            :class="loadedMedia[post.id] ? 'opacity-100' : 'opacity-0'"
                            loading="lazy"
                            @load="markLoaded(post.id)"
                        />
                        <img
                            v-else-if="post.media_type === 'video' && post.thumbnail_url"
                            :src="post.thumbnail_url"
                            :alt="post.caption ?? t('Moment')"
                            class="relative size-full object-cover transition-opacity duration-300"
                            :class="loadedMedia[post.id] ? 'opacity-100' : 'opacity-0'"
                            loading="lazy"
                            @load="markLoaded(post.id)"
                        />
                        <div v-else class="flex size-full items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-8 text-sand-300 dark:text-sand-600">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.91 11.672a.375.375 0 0 1 0 .656l-5.603 3.113a.375.375 0 0 1-.557-.328V8.887c0-.286.307-.466.557-.327l5.603 3.112Z" />
                            </svg>
                        </div>
                        <div v-if="post.media_type === 'video'" class="absolute right-1.5 top-1.5 z-10">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-4 text-white drop-shadow">
                                <path d="M4.5 5.653c0-1.426 1.529-2.33 2.779-1.643l11.54 6.348c1.295.712 1.295 2.573 0 3.285L7.28 19.991c-1.25.687-2.779-.217-2.779-1.643V5.653Z" />
                            </svg>
                        </div>
                    </Link>
                </div>
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

