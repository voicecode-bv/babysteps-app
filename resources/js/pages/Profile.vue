<script setup lang="ts">
import { type PostData } from '@/components/PostCard.vue';
import PullToRefreshIndicator from '@/components/PullToRefreshIndicator.vue';
import { usePullToRefresh } from '@/composables/usePullToRefresh';
import { useTranslations } from '@/composables/useTranslations';
import AppLayout from '@/layouts/AppLayout.vue';
import { InfiniteScroll, Link, router } from '@inertiajs/vue3';
import { computed, reactive, useTemplateRef } from 'vue';

interface Profile {
    id: number;
    name: string;
    username: string;
    avatar: string | null;
    bio: string | null;
    created_at: string;
    posts_count: number;
}

const props = defineProps<{
    profile: Profile;
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
                only: ['profile', 'posts'],
                reset: ['posts'],
                onFinish: () => resolve(),
            });
        }),
    containerRef,
});

function goBack() {
    window.history.length > 1 ? window.history.back() : router.visit('/');
}

const loadedMedia = reactive<Record<number, boolean>>({});
function markLoaded(postId: number) {
    loadedMedia[postId] = true;
}
</script>

<template>
    <AppLayout ref="layout" :title="profile.name">
        <template #header-left>
            <button class="flex items-center text-sand-700 dark:text-sand-300" @click="goBack">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                </svg>
            </button>
        </template>

        <div class="mt-10 pb-24">
            <PullToRefreshIndicator :pull-distance="pullDistance" :is-refreshing="isRefreshing" />

            <div>
                <!-- Profile Header -->
                <div class="bg-white px-4 py-6 dark:bg-sand-900">
                    <div class="flex items-center gap-4">
                        <img
                            :src="profile.avatar ?? `https://ui-avatars.com/api/?name=${profile.name}&background=f0dcc6&color=5c3f24&size=128`"
                            :alt="profile.name"
                            class="size-20 rounded-full object-cover ring-2 ring-sand-200 dark:ring-sand-700"
                        />
                        <div class="flex-1">
                            <h2 class="font-display text-lg font-semibold text-sand-800 dark:text-sand-100">{{ profile.name }}</h2>
                            <p class="text-sm text-sand-500 dark:text-sand-400">@{{ profile.username }}</p>
                            <div class="mt-2">
                                <span class="text-sm font-medium text-sand-700 dark:text-sand-200">{{ profile.posts_count }}</span>
                                <span class="ml-1 text-sm text-sand-500 dark:text-sand-400">{{ profile.posts_count === 1 ? t('moment') : t('moments') }}</span>
                            </div>
                        </div>
                    </div>

                    <p v-if="profile.bio" class="mt-3 text-sm text-sand-700 dark:text-sand-300">{{ profile.bio }}</p>
                </div>

                <div class="h-2 bg-sand-100 dark:bg-sand-800" />

                <!-- Skeleton grid while loading -->
                <div v-if="!posts" class="grid grid-cols-3 gap-0.5 bg-sand-100 dark:bg-sand-800">
                    <div v-for="n in 30" :key="n" class="aspect-square animate-pulse bg-sand-200 dark:bg-sand-700" />
                </div>

                <!-- Posts Grid -->
                <InfiniteScroll v-else data="posts" only-next :buffer="0" preserve-url items-element="#profile-posts-grid" class="no-scrollbar">
                    <div id="profile-posts-grid" class="grid grid-cols-3 gap-0.5 bg-sand-100 dark:bg-sand-800">
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

                <div v-if="posts && posts.data.length === 0" class="flex flex-col items-center justify-center px-8 py-20">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" class="mb-4 size-16 text-sand-300 dark:text-sand-600">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0Z" />
                    </svg>
                    <h3 class="font-display text-lg font-semibold text-sand-800 dark:text-sand-200">{{ t('No moments yet') }}</h3>
                </div>

            </div>
        </div>
    </AppLayout>
</template>
