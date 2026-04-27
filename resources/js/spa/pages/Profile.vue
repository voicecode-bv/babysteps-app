<script setup lang="ts">
import { computed, onMounted, reactive, ref, useTemplateRef } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import { type PostData } from '@/spa/components/PostCard.vue';
import PullToRefreshIndicator from '@/components/PullToRefreshIndicator.vue';
import AppLayout from '@/spa/layouts/AppLayout.vue';
import { useTranslations } from '@/spa/composables/useTranslations';
import { useInfiniteScroll, type PaginatedResponse } from '@/spa/composables/useInfiniteScroll';
import { usePullToRefresh } from '@/spa/composables/usePullToRefresh';
import { externalApi } from '@/spa/http/externalApi';
import { useAuthStore } from '@/spa/stores/auth';

interface Profile {
    id: number;
    name: string;
    username: string;
    avatar: string | null;
    bio: string | null;
    created_at: string;
    posts_count: number;
}

const { t } = useTranslations();
const route = useRoute();
const router = useRouter();
const auth = useAuthStore();

const username = computed(() => String(route.params.username));
const isOwnProfile = computed(() => auth.user?.username === username.value);

const layoutRef = useTemplateRef<InstanceType<typeof AppLayout>>('layout');
const containerRef = computed(() => layoutRef.value?.mainRef ?? null);
const sentinelRef = ref<HTMLElement | null>(null);

const profile = ref<Profile | null>(null);

async function loadProfile(): Promise<void> {
    try {
        const data = await externalApi.get<{ data: Profile }>(`/profiles/${username.value}`);
        profile.value = data.data;
    } catch {
        router.push({ name: 'spa.home' });
    }
}

const feed = useInfiniteScroll<PostData>(
    (page) => externalApi.get<PaginatedResponse<PostData>>(`/profiles/${username.value}/posts?page=${page}`),
    sentinelRef,
);

// Loaded-state per media-URL i.p.v. per post.id zodat een wijziging in
// media_url (refresh, edit) automatisch opnieuw een skeleton triggert.
const loadedMedia = reactive<Record<string, boolean>>({});

async function refresh(): Promise<void> {
    await Promise.all([loadProfile(), feed.reset()]);
}

const { pullDistance, isRefreshing } = usePullToRefresh({
    onRefresh: refresh,
    containerRef,
});

onMounted(loadProfile);

function goBack(): void {
    if (window.history.length > 1) {
        router.back();
    } else {
        router.push({ name: 'spa.home' });
    }
}
function markLoaded(url: string): void {
    loadedMedia[url] = true;
}

function mediaKey(post: PostData): string {
    return post.media_type === 'video' ? (post.thumbnail_url ?? '') : post.media_url;
}

</script>

<template>
    <AppLayout ref="layout" :title="t('Profile')">
        <template #header-left>
            <button class="flex items-center text-sand-700 dark:text-sand-300" @click="goBack">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                </svg>
            </button>
        </template>

        <template v-if="profile && isOwnProfile" #header-right>
            <RouterLink
                :to="{ name: 'spa.settings' }"
                class="flex items-center text-sand-700 dark:text-sand-300"
                :aria-label="t('Open settings')"
            >
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.28Z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                </svg>
            </RouterLink>
        </template>

        <div class="mt-10 pb-24">
            <PullToRefreshIndicator :pull-distance="pullDistance" :is-refreshing="isRefreshing" />

            <div>
                <div v-if="profile" class="bg-white px-4 py-6 dark:bg-sand-900">
                    <div class="flex items-center gap-4">
                        <img
                            :src="profile.avatar ?? `https://ui-avatars.com/api/?name=${profile.name}&background=f0dcc6&color=5c3f24&size=128`"
                            :alt="profile.name"
                            class="size-20 rounded-full object-cover ring-2 ring-sand-200 dark:ring-sand-700"
                        />
                        <div class="flex-1">
                            <h2 class="truncate font-sans text-xl font-bold text-teal">{{ profile.name }}</h2>
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

                <div v-if="feed.items.length === 0 && feed.loading" class="grid grid-cols-3 gap-0.5 bg-sand-100 dark:bg-sand-800">
                    <div v-for="n in 30" :key="n" class="aspect-square animate-pulse bg-sand-200 dark:bg-sand-700" />
                </div>

                <div id="profile-posts-grid" class="grid grid-cols-3 gap-0.5 bg-sand-100 dark:bg-sand-800">
                    <RouterLink
                        v-for="post in feed.items"
                        :key="post.id"
                        :to="{ name: 'spa.posts.show', params: { post: post.id } }"
                        class="relative block aspect-square overflow-hidden bg-sand-100 dark:bg-sand-800"
                    >
                        <div v-if="!loadedMedia[mediaKey(post)] && post.media_type !== 'unknown'" class="absolute inset-0 animate-pulse bg-sand-200 dark:bg-sand-700" />
                        <img
                            v-if="post.media_type === 'image'"
                            :src="post.media_url"
                            :alt="post.caption ?? t('Photo')"
                            class="relative size-full object-cover transition-opacity duration-300"
                            :class="loadedMedia[mediaKey(post)] ? 'opacity-100' : 'opacity-0'"
                            loading="lazy"
                            decoding="async"
                            @load="markLoaded(mediaKey(post))"
                        />
                        <img
                            v-else-if="post.media_type === 'video' && post.thumbnail_url"
                            :src="post.thumbnail_url"
                            :alt="post.caption ?? t('Moment')"
                            class="relative size-full object-cover transition-opacity duration-300"
                            :class="loadedMedia[mediaKey(post)] ? 'opacity-100' : 'opacity-0'"
                            loading="lazy"
                            decoding="async"
                            @load="markLoaded(mediaKey(post))"
                        />
                        <div v-if="post.media_type === 'video'" class="absolute right-1.5 top-1.5 z-10">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-4 text-white drop-shadow">
                                <path d="M4.5 5.653c0-1.426 1.529-2.33 2.779-1.643l11.54 6.348c1.295.712 1.295 2.573 0 3.285L7.28 19.991c-1.25.687-2.779-.217-2.779-1.643V5.653Z" />
                            </svg>
                        </div>
                    </RouterLink>
                </div>

                <div v-if="feed.loading && feed.items.length > 0" class="flex items-center justify-center gap-2 py-6 text-sm text-sand-500 dark:text-sand-400">
                    {{ t('Loading more...') }}
                </div>

                <div ref="sentinelRef" class="h-1" />

                <div v-if="!feed.loading && feed.items.length === 0" class="flex flex-col items-center justify-center px-8 py-20 text-center">
                    <div aria-hidden="true" class="mb-4 flex size-16 items-center justify-center rounded-2xl bg-sage-100 text-teal dark:bg-sage-900/40">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0Z" />
                        </svg>
                    </div>
                    <h3 class="font-display text-lg font-semibold text-sand-800 dark:text-sand-200">{{ t('No moments yet') }}</h3>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
