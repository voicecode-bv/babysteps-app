<script setup lang="ts">
import CommentsSheet from '@/components/CommentsSheet.vue';
import EditPostModal from '@/components/EditPostModal.vue';
import LikesSheet from '@/components/LikesSheet.vue';
import PullToRefreshIndicator from '@/components/PullToRefreshIndicator.vue';
import { usePullToRefresh } from '@/composables/usePullToRefresh';
import { useTranslations } from '@/composables/useTranslations';
import AppLayout from '@/layouts/AppLayout.vue';
import type { Comment } from '@/types/comment';
import { Link, router, usePage } from '@inertiajs/vue3';
import { Dialog, Events, Off, On } from '@nativephp/mobile';
import mapboxgl from 'mapbox-gl';
import 'mapbox-gl/dist/mapbox-gl.css';
import { computed, onBeforeUnmount, onMounted, onUnmounted, ref, useTemplateRef, watch } from 'vue';
import heartIcon from '../../svg/doodle-icons/heart.svg';
import heartFilledIcon from '../../svg/doodle-icons/heart-filled.svg';
import messageIcon from '../../svg/doodle-icons/message.svg';
import pencilIcon from '../../svg/doodle-icons/pencil-3.svg';
import tagIcon from '../../svg/doodle-icons/tag.svg';

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

interface User {
    id: number;
    name: string;
    username: string;
    avatar: string | null;
}

interface Circle {
    id: number;
    name: string;
    photo: string | null;
}

interface Tag {
    id: number;
    name: string;
    usage_count?: number;
}

interface Post {
    id: number;
    media_url: string;
    media_type: string;
    thumbnail_url: string | null;
    media_status: 'processing' | 'ready' | 'failed';
    caption: string | null;
    location: string | null;
    latitude: number | null;
    longitude: number | null;
    created_at: string;
    user: User;
    comments: Comment[];
    is_liked: boolean;
    likes_count: number;
    comments_count: number;
    circles?: Circle[];
    tags?: Tag[];
}

const props = withDefaults(
    defineProps<{
        post: Post;
        mapboxToken?: string | null;
        availableCircles?: Circle[] | null;
        availableTags?: Tag[] | null;
    }>(),
    {
        mapboxToken: null,
        availableCircles: () => [],
        availableTags: () => [],
    },
);

const { t } = useTranslations();

const page = usePage();
const authUserId = computed(() => (page.props.auth as { user?: { id: number } })?.user?.id ?? null);
const likesCount = ref(props.post.likes_count);
const commentsCount = ref(props.post.comments_count);
const isOwner = computed(() => props.post.user.id === authUserId.value);
const isLikedByUser = computed(() => props.post.is_liked ?? false);

const isSheetOpen = ref(false);
const isLikesSheetOpen = ref(false);
const isEditModalOpen = ref(false);
const showFullCaption = ref(false);

const isMuted = ref(true);
const isFullscreen = ref(false);
const videoRef = ref<HTMLVideoElement>();
const mediaLoaded = ref(false);

const isCaptionTruncatable = computed(() => {
    if (!props.post.caption) return false;
    return props.post.caption.length > 100 || props.post.caption.includes('\n');
});

function toggleMute() {
    isMuted.value = !isMuted.value;
    if (videoRef.value) {
        videoRef.value.muted = isMuted.value;
    }
}

function toggleFullscreen() {
    isFullscreen.value = !isFullscreen.value;
}

watch(isFullscreen, (val) => {
    const scrollContainer = document.querySelector('main[scroll-region]') as HTMLElement | null;
    if (scrollContainer) {
        scrollContainer.style.overflow = val ? 'hidden' : '';
    }
});

const hasLocation = computed(
    () => props.post.latitude != null && props.post.longitude != null && !!props.mapboxToken,
);

const mapContainer = useTemplateRef<HTMLDivElement>('mapContainer');
let map: mapboxgl.Map | null = null;

function initLocationMap() {
    if (!hasLocation.value || !mapContainer.value || map) {
        return;
    }

    mapboxgl.accessToken = props.mapboxToken as string;

    const center: [number, number] = [props.post.longitude as number, props.post.latitude as number];

    map = new mapboxgl.Map({
        container: mapContainer.value,
        style: 'mapbox://styles/mapbox/streets-v12',
        center,
        zoom: 13,
        interactive: false,
        attributionControl: false,
    });

    new mapboxgl.Marker({ color: '#14b8a6' }).setLngLat(center).addTo(map);
}

onBeforeUnmount(() => {
    map?.remove();
    map = null;
});

const layoutRef = useTemplateRef<InstanceType<typeof AppLayout>>('layout');
const containerRef = computed(() => layoutRef.value?.mainRef ?? null);

const { pullDistance, isRefreshing } = usePullToRefresh({
    onRefresh: () =>
        new Promise<void>((resolve) => {
            router.reload({
                only: ['post'],
                onFinish: () => resolve(),
            });
        }),
    containerRef,
});

function openComments() {
    isSheetOpen.value = true;
}

function openLikes() {
    isLikesSheetOpen.value = true;
}

function openEditModal() {
    isEditModalOpen.value = true;
}

function goBack() {
    window.history.back();
}

async function deletePost() {
    await Dialog.alert()
        .confirm(t('Delete post'), t('Are you sure you want to delete this post?'))
        .id('delete-post-confirm');
}

function handleButtonPressed(payload: { index: number; id?: string | null }) {
    if (payload.id === 'delete-post-confirm' && payload.index === 1) {
        router.delete(`/posts/${props.post.id}`);
    }
}

function handleKeydown(e: KeyboardEvent) {
    if (e.key === 'Escape' && isFullscreen.value) {
        isFullscreen.value = false;
    }
}

onMounted(() => {
    On(Events.Alert.ButtonPressed, handleButtonPressed);
    document.addEventListener('keydown', handleKeydown);
    initLocationMap();
});
onUnmounted(() => {
    Off(Events.Alert.ButtonPressed, handleButtonPressed);
    document.removeEventListener('keydown', handleKeydown);
    if (isFullscreen.value) {
        const scrollContainer = document.querySelector('main[scroll-region]') as HTMLElement | null;
        if (scrollContainer) {
            scrollContainer.style.overflow = '';
        }
    }
});

function timeAgo(dateString: string): string {
    const date = new Date(dateString);
    const now = new Date();
    const seconds = Math.floor((now.getTime() - date.getTime()) / 1000);

    if (seconds < 60) return t('just now');
    if (seconds < 3600) return t(':count min ago', { count: Math.floor(seconds / 60) });
    if (seconds < 86400) return t(':count hours ago', { count: Math.floor(seconds / 3600) });
    if (seconds < 604800) {
        const days = Math.floor(seconds / 86400);
        return t(days === 1 ? ':count day ago' : ':count days ago', { count: days });
    }
    if (seconds < 2592000) {
        const weeks = Math.floor(seconds / 604800);
        return t(weeks === 1 ? ':count week ago' : ':count weeks ago', { count: weeks });
    }
    if (seconds < 31536000) {
        const months = Math.floor(seconds / 2592000);
        return t(months === 1 ? ':count month ago' : ':count months ago', { count: months });
    }
    const years = Math.floor(seconds / 31536000);
    return t(years === 1 ? ':count year ago' : ':count years ago', { count: years });
}
</script>

<template>
    <AppLayout ref="layout" :title="t('Moment')">
        <template #header-left>
            <button class="flex items-center text-sand-700 dark:text-sand-300" @click="goBack">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                </svg>
            </button>
        </template>
        <template v-if="isOwner" #header-right>
            <button class="text-blush-500" :aria-label="t('Delete post')" @click="deletePost">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                </svg>
            </button>
        </template>

        <div class="mt-10 pb-10">
            <PullToRefreshIndicator :pull-distance="pullDistance" :is-refreshing="isRefreshing" />

            <div>
                <!-- Post Header -->
                <div class="flex items-center gap-3 bg-white px-4 py-3 dark:bg-sand-900">
                    <Link :href="`/profiles/${post.user.username}`">
                        <img
                            :src="post.user.avatar ?? `https://ui-avatars.com/api/?name=${post.user.name}&background=f0dcc6&color=5c3f24&size=64`"
                            :alt="post.user.name"
                            class="size-10 rounded-full object-cover ring-2 ring-sand-200 dark:ring-sand-700"
                        />
                    </Link>
                    <div class="flex-1">
                        <Link :href="`/profiles/${post.user.username}`" class="text-sm font-semibold text-sand-800 dark:text-sand-100">{{ post.user.name }}</Link>
                        <p v-if="post.location" class="text-xs text-sand-500 dark:text-sand-400">{{ post.location }}</p>
                    </div>
                </div>

                <!-- Caption -->
                <div v-if="post.caption" class="bg-white px-4 pb-3 dark:bg-sand-900">
                    <p
                        class="whitespace-pre-line text-sm leading-relaxed text-sand-800 dark:text-sand-200"
                        :class="{ 'line-clamp-2': !showFullCaption }"
                    >
                        {{ post.caption }}
                    </p>
                    <button
                        v-if="isCaptionTruncatable"
                        class="mt-1 text-sm font-medium text-sand-500 dark:text-sand-400"
                        @click="showFullCaption = !showFullCaption"
                    >
                        {{ showFullCaption ? t('less') : t('more') }}
                    </button>
                </div>

                <!-- Post Media -->
                <div
                    :class="[
                        isFullscreen
                            ? 'fixed inset-0 z-50 flex items-center justify-center bg-black'
                            : 'relative aspect-square w-full overflow-hidden bg-sand-100 dark:bg-sand-800',
                    ]"
                >
                    <div v-if="!mediaLoaded && !isFullscreen && (post.media_type === 'image' || (post.media_type === 'video' && post.media_status === 'ready'))" class="shimmer absolute inset-0" />
                    <img
                        v-if="post.media_type === 'image'"
                        :src="post.media_url"
                        :alt="post.caption ?? t('Photo')"
                        class="size-full object-cover transition-opacity duration-500"
                        :class="mediaLoaded ? 'opacity-100' : 'opacity-0'"
                        @load="mediaLoaded = true"
                    />
                    <template v-else-if="post.media_type === 'video'">
                        <video
                            v-if="post.media_status === 'ready'"
                            ref="videoRef"
                            :src="post.media_url"
                            :poster="post.thumbnail_url ?? undefined"
                            :class="[
                                isFullscreen ? 'max-h-full max-w-full object-contain' : 'size-full object-cover',
                                'transition-opacity duration-500',
                                mediaLoaded ? 'opacity-100' : 'opacity-0',
                            ]"
                            playsinline
                            muted
                            autoplay
                            loop
                            preload="metadata"
                            @loadeddata="mediaLoaded = true"
                        />
                        <!-- Video controls: mute + fullscreen -->
                        <div
                            v-if="post.media_status === 'ready'"
                            :class="[
                                'absolute z-20 flex gap-2',
                                isFullscreen ? 'right-3' : 'left-3 top-3',
                                isFullscreen ? 'top-[calc(env(safe-area-inset-top)+1.5rem)]' : '',
                            ]"
                        >
                            <button
                                class="flex size-8 items-center justify-center rounded-full bg-black/50 text-white backdrop-blur-sm"
                                @click="toggleMute"
                            >
                                <svg v-if="isMuted" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.25 9.75 19.5 12m0 0 2.25 2.25M19.5 12l2.25-2.25M19.5 12l-2.25 2.25m-10.5-6 4.72-4.72a.75.75 0 0 1 1.28.53v15.88a.75.75 0 0 1-1.28.53l-4.72-4.72H4.51c-.88 0-1.704-.507-1.938-1.354A9.009 9.009 0 0 1 2.25 12c0-.83.112-1.633.322-2.396C2.806 8.756 3.63 8.25 4.51 8.25H6.75Z" />
                                </svg>
                                <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.114 5.636a9 9 0 0 1 0 12.728M16.463 8.288a5.25 5.25 0 0 1 0 7.424M6.75 8.25l4.72-4.72a.75.75 0 0 1 1.28.53v15.88a.75.75 0 0 1-1.28.53l-4.72-4.72H4.51c-.88 0-1.704-.507-1.938-1.354A9.009 9.009 0 0 1 2.25 12c0-.83.112-1.633.322-2.396C2.806 8.756 3.63 8.25 4.51 8.25H6.75Z" />
                                </svg>
                            </button>
                            <button
                                class="flex size-8 items-center justify-center rounded-full bg-black/50 text-white backdrop-blur-sm"
                                @click="toggleFullscreen"
                            >
                                <svg v-if="!isFullscreen" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3.75v4.5m0-4.5h4.5m-4.5 0L9 9M3.75 20.25v-4.5m0 4.5h4.5m-4.5 0L9 15M20.25 3.75h-4.5m4.5 0v4.5m0-4.5L15 9m5.25 11.25h-4.5m4.5 0v-4.5m0 4.5L15 15" />
                                </svg>
                                <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 9V4.5M9 9H4.5M9 9 3.75 3.75M9 15v4.5M9 15H4.5M9 15l-5.25 5.25M15 9h4.5M15 9V4.5M15 9l5.25-5.25M15 15h4.5M15 15v4.5m0-4.5 5.25 5.25" />
                                </svg>
                            </button>
                        </div>
                        <template v-else>
                            <img
                                v-if="post.thumbnail_url"
                                :src="post.thumbnail_url"
                                :alt="post.caption ?? t('Moment')"
                                class="size-full object-cover"
                            />
                            <div class="absolute inset-0 flex items-center justify-center bg-black/20">
                                <div class="flex flex-col items-center gap-2">
                                    <svg class="size-8 animate-spin text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                                    </svg>
                                    <span class="text-xs font-medium text-white">{{ t('Processing video...') }}</span>
                                </div>
                            </div>
                        </template>
                    </template>
                    <div v-else class="flex size-full items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-16 text-sand-300 dark:text-sand-600">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.91 11.672a.375.375 0 0 1 0 .656l-5.603 3.113a.375.375 0 0 1-.557-.328V8.887c0-.286.307-.466.557-.327l5.603 3.112Z" />
                        </svg>
                    </div>
                    <div
                        v-if="!isFullscreen && post.circles && post.circles.length > 0"
                        class="absolute right-3 top-3 z-10 flex max-w-[70%] flex-wrap justify-end gap-1.5"
                    >
                        <Link
                            v-for="circle in post.circles"
                            :key="circle.id"
                            :href="`/circles/${circle.id}`"
                            class="flex items-center gap-1.5 rounded-full bg-black/50 py-0.5 pl-0.5 pr-2.5 backdrop-blur-sm"
                        >
                            <img
                                v-if="circle.photo"
                                :src="circle.photo"
                                :alt="circle.name"
                                class="size-5 rounded-full object-cover"
                            />
                            <div v-else class="flex size-5 items-center justify-center rounded-full bg-white/20">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-3 text-white">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                                </svg>
                            </div>
                            <span class="text-xs font-medium text-white">{{ circle.name }}</span>
                        </Link>
                    </div>
                    <div
                        v-if="!isFullscreen"
                        class="absolute inset-x-0 bottom-0 z-10 flex items-center gap-4 bg-gradient-to-t from-black/70 via-black/30 to-transparent px-4 pb-3 pt-12"
                    >
                        <button class="flex items-center gap-1" :aria-label="t('Show likes')" @click="openLikes">
                            <span
                                aria-hidden="true"
                                class="inline-block size-6 drop-shadow"
                                :class="isLikedByUser ? 'bg-blush-400' : 'bg-white'"
                                :style="iconMaskStyle(isLikedByUser ? heartFilledIcon : heartIcon)"
                            ></span>
                            <span v-if="likesCount > 0" class="text-sm font-medium text-white drop-shadow">{{ likesCount }}</span>
                        </button>
                        <button class="flex items-center gap-1 text-white drop-shadow" @click="openComments">
                            <span aria-hidden="true" class="inline-block size-6 bg-current" :style="iconMaskStyle(messageIcon)"></span>
                            <span v-if="commentsCount > 0" class="text-sm font-medium">{{ commentsCount }}</span>
                        </button>
                        <button
                            v-if="isOwner"
                            class="text-white drop-shadow"
                            :aria-label="t('Edit post')"
                            @click="openEditModal"
                        >
                            <span aria-hidden="true" class="inline-block size-6 bg-current" :style="iconMaskStyle(pencilIcon)"></span>
                        </button>
                        <span class="ml-auto text-xs text-white/80 drop-shadow">{{ timeAgo(post.created_at) }}</span>
                    </div>
                </div>

                <!-- Tags -->
                <div v-if="post.tags && post.tags.length > 0" class="bg-white px-4 pt-4 dark:bg-sand-900">
                    <div class="flex flex-wrap items-center gap-2">
                        <span
                            aria-hidden="true"
                            class="inline-block size-4 bg-teal/70"
                            :style="iconMaskStyle(tagIcon)"
                        ></span>
                        <span
                            v-for="tag in post.tags"
                            :key="tag.id"
                            class="rounded-full bg-linear-to-r from-sage-100 to-teal-muted/30 px-3 py-1 text-xs font-semibold text-teal ring-1 ring-inset ring-teal/15 dark:from-sage-900/40 dark:to-teal-muted/15 dark:text-sage-200 dark:ring-sage-700/40"
                        >
                            {{ tag.name }}
                        </span>
                    </div>
                </div>

                <!-- Location map -->
                <div v-if="hasLocation" class="bg-white px-4 pt-3 dark:bg-sand-900">
                    <div class="overflow-hidden rounded-2xl ring-1 ring-sand-200 dark:ring-sand-800">
                        <div ref="mapContainer" class="h-44 w-full" />
                        <div v-if="post.location" class="flex items-center gap-1.5 bg-white px-3 py-2 dark:bg-sand-900">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4 text-sand-500 dark:text-sand-400">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                            </svg>
                            <span class="text-xs text-sand-700 dark:text-sand-300">{{ post.location }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <CommentsSheet
            :open="isSheetOpen"
            :post-id="post.id"
            :initial-comments="post.comments"
            @update:open="isSheetOpen = $event"
            @comment-added="commentsCount++"
        />

        <LikesSheet
            :open="isLikesSheetOpen"
            :post-id="post.id"
            :initial-count="likesCount"
            @update:open="isLikesSheetOpen = $event"
        />

        <EditPostModal
            v-if="isOwner"
            :open="isEditModalOpen"
            :post-id="post.id"
            :caption="post.caption"
            :circles="post.circles ?? []"
            :available-circles="availableCircles"
            :tags="post.tags ?? []"
            :available-tags="availableTags"
            @update:open="isEditModalOpen = $event"
        />
    </AppLayout>
</template>
