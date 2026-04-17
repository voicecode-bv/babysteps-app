<script setup lang="ts">
import PullToRefreshIndicator from '@/components/PullToRefreshIndicator.vue';
import { usePullToRefresh } from '@/composables/usePullToRefresh';
import { useTranslations } from '@/composables/useTranslations';
import AppLayout from '@/layouts/AppLayout.vue';
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import { Dialog, On, Off, Events } from '@nativephp/mobile';
import { computed, onMounted, onUnmounted, ref, useTemplateRef, watch } from 'vue';

interface User {
    id: number;
    name: string;
    username: string;
    avatar: string | null;
}

interface Comment {
    id: number;
    parent_comment_id: number | null;
    body: string;
    created_at: string;
    user: User;
    likes_count: number;
    is_liked: boolean;
    replies: Comment[];
}

interface Like {
    id: number;
    user_id: number;
}

interface Circle {
    id: number;
    name: string;
    photo: string | null;
}

interface Post {
    id: number;
    media_url: string;
    media_type: string;
    thumbnail_url: string | null;
    media_status: 'processing' | 'ready' | 'failed';
    caption: string | null;
    location: string | null;
    created_at: string;
    user: User;
    comments: Comment[];
    likes: Like[];
    is_liked: boolean;
    likes_count: number;
    comments_count: number;
    circles?: Circle[];
}

const props = defineProps<{
    post: Post;
}>();

const { t } = useTranslations();

const page = usePage();
const authUserId = computed(() => (page.props.auth as { user?: { id: number } })?.user?.id ?? null);
const isLiked = ref(props.post.is_liked ?? false);
const likesCount = ref(props.post.likes_count);
const isOwner = computed(() => props.post.user.id === authUserId.value);
const commentForm = useForm({ body: '', parent_comment_id: null as number | null });
const commentInput = ref<HTMLInputElement>();
const replyingTo = ref<Comment | null>(null);

const isMuted = ref(true);
const isFullscreen = ref(false);
const videoRef = ref<HTMLVideoElement>();

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

function focusComment() {
    commentInput.value?.focus();
}

function replyTo(comment: Comment) {
    replyingTo.value = comment;
    commentForm.parent_comment_id = comment.id;
    commentInput.value?.focus();
}

function cancelReply() {
    replyingTo.value = null;
    commentForm.parent_comment_id = null;
}

function toggleLike() {
    if (isLiked.value) {
        isLiked.value = false;
        likesCount.value--;
        router.delete(`/posts/${props.post.id}/like`, {
            preserveScroll: true,
            preserveState: true,
            onError: () => {
                isLiked.value = true;
                likesCount.value++;
            },
        });
    } else {
        isLiked.value = true;
        likesCount.value++;
        router.post(`/posts/${props.post.id}/like`, {}, {
            preserveScroll: true,
            preserveState: true,
            onError: () => {
                isLiked.value = false;
                likesCount.value--;
            },
        });
    }
}

function toggleCommentLike(comment: Comment) {
    const previousIsLiked = comment.is_liked;
    const previousLikesCount = comment.likes_count;

    if (comment.is_liked) {
        comment.is_liked = false;
        comment.likes_count--;
        router.delete(`/comments/${comment.id}/like`, {
            preserveScroll: true,
            preserveState: true,
            onError: () => {
                comment.is_liked = previousIsLiked;
                comment.likes_count = previousLikesCount;
            },
        });
    } else {
        comment.is_liked = true;
        comment.likes_count++;
        router.post(`/comments/${comment.id}/like`, {}, {
            preserveScroll: true,
            preserveState: true,
            onError: () => {
                comment.is_liked = previousIsLiked;
                comment.likes_count = previousLikesCount;
            },
        });
    }
}

function submitComment() {
    if (!commentForm.body.trim()) return;

    commentForm.post(`/posts/${props.post.id}/comments`, {
        preserveScroll: true,
        onSuccess: () => {
            commentForm.reset();
            replyingTo.value = null;
        },
    });
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
    if (seconds < 604800) return t(':count days ago', { count: Math.floor(seconds / 86400) });
    return t(':count weeks ago', { count: Math.floor(seconds / 604800) });
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
            <button class="text-blush-500" @click="deletePost">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                </svg>
            </button>
        </template>

        <div class="mt-10 pb-24">
        <PullToRefreshIndicator :pull-distance="pullDistance" :is-refreshing="isRefreshing" />

        <div>
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

                <!-- Post Media -->
                <div
                    :class="[
                        isFullscreen
                            ? 'fixed inset-0 z-50 flex items-center justify-center bg-black'
                            : 'relative aspect-square w-full bg-sand-100 dark:bg-sand-800',
                    ]"
                >
                    <img
                        v-if="post.media_type === 'image'"
                        :src="post.media_url"
                        :alt="post.caption ?? t('Photo')"
                        class="size-full object-cover"
                    />
                    <template v-else-if="post.media_type === 'video'">
                        <video
                            v-if="post.media_status === 'ready'"
                            ref="videoRef"
                            :src="post.media_url"
                            :poster="post.thumbnail_url ?? undefined"
                            :class="isFullscreen ? 'max-h-full max-w-full object-contain' : 'size-full object-cover'"
                            playsinline
                            muted
                            autoplay
                            loop
                            preload="metadata"
                        />
                        <!-- Video controls: mute + fullscreen -->
                        <div
                            v-if="post.media_status === 'ready'"
                            :class="[
                                'absolute z-10 flex gap-2',
                                isFullscreen ? 'right-3' : 'bottom-3 right-3',
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
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center gap-4 bg-white px-4 py-3 dark:bg-sand-900">
                    <div class="flex items-center gap-1">
                        <button v-if="post.user.id !== authUserId" @click="toggleLike">
                            <svg
                                v-if="!isLiked"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="1.5"
                                stroke="currentColor"
                                class="size-6 text-sand-600 dark:text-sand-300"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
                            </svg>
                            <svg
                                v-else
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 24 24"
                                fill="currentColor"
                                class="size-6 text-blush-400"
                            >
                                <path d="m11.645 20.91-.007-.003-.022-.012a15.247 15.247 0 0 1-.383-.218 25.18 25.18 0 0 1-4.244-3.17C4.688 15.36 2.25 12.174 2.25 8.25 2.25 5.322 4.714 3 7.688 3A5.5 5.5 0 0 1 12 5.052 5.5 5.5 0 0 1 16.313 3c2.973 0 5.437 2.322 5.437 5.25 0 3.925-2.438 7.111-4.739 9.256a25.175 25.175 0 0 1-4.244 3.17 15.247 15.247 0 0 1-.383.219l-.022.012-.007.004-.003.001a.752.752 0 0 1-.704 0l-.003-.001Z" />
                            </svg>
                        </button>
                        <svg
                            v-else
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="1.5"
                            stroke="currentColor"
                            class="size-6 text-sand-600 dark:text-sand-300"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
                        </svg>
                        <span v-if="likesCount > 0" class="text-sm font-medium text-sand-700 dark:text-sand-200">{{ likesCount }}</span>
                    </div>
                    <button class="flex items-center gap-1 text-sand-600 dark:text-sand-300" @click="focusComment">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 20.25c4.97 0 9-3.694 9-8.25s-4.03-8.25-9-8.25S3 7.444 3 12c0 2.104.859 4.023 2.273 5.48.432.447.74 1.04.586 1.641a4.483 4.483 0 0 1-.923 1.785A5.969 5.969 0 0 0 6 21c1.282 0 2.47-.402 3.445-1.087.81.22 1.668.337 2.555.337Z" />
                        </svg>
                        <span v-if="post.comments_count > 0" class="text-sm font-medium text-sand-700 dark:text-sand-200">{{ post.comments_count }}</span>
                    </button>
                </div>

                <!-- Caption -->
                <div v-if="post.caption" class="bg-white px-4 pb-3 dark:bg-sand-900">
                    <p class="text-sm text-sand-800 dark:text-sand-200">
                        <span class="font-semibold">{{ post.user.name }}</span>
                        {{ ' ' + post.caption }}
                    </p>
                    <p class="mt-1 text-xs text-sand-400 dark:text-sand-500">{{ timeAgo(post.created_at) }}</p>
                </div>

                <!-- Shared in circles (owner only) -->
                <div v-if="isOwner && post.circles && post.circles.length > 0" class="bg-white px-4 pb-3 dark:bg-sand-900">
                    <div class="flex flex-wrap gap-2">
                        <Link
                            v-for="circle in post.circles"
                            :key="circle.id"
                            :href="`/circles/${circle.id}`"
                            class="flex items-center gap-2 rounded-full bg-sand-100 py-1 pl-1 pr-3 dark:bg-sand-800"
                        >
                            <img
                                v-if="circle.photo"
                                :src="circle.photo"
                                :alt="circle.name"
                                class="size-6 rounded-full object-cover"
                            />
                            <div v-else class="flex size-6 items-center justify-center rounded-full bg-sand-200 dark:bg-sand-700">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-3.5 text-sand-500 dark:text-sand-400">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                                </svg>
                            </div>
                            <span class="text-xs font-medium text-sand-700 dark:text-sand-200">{{ circle.name }}</span>
                        </Link>
                    </div>
                </div>

                <!-- Comments Section -->
                <div class="mt-2 bg-white dark:bg-sand-900">
                    <div class="border-b border-sand-100 px-4 py-3 dark:border-sand-800">
                        <h2 class="text-sm font-semibold text-sand-700 dark:text-sand-300">
                            {{ t('Comments') }}
                            <span v-if="post.comments_count > 0" class="font-normal text-sand-400 dark:text-sand-500">({{ post.comments_count }})</span>
                        </h2>
                    </div>

                    <div v-if="post.comments.length === 0" class="px-4 py-8 text-center">
                        <p class="text-sm font-medium text-sand-600 dark:text-sand-300">{{ t('No comments yet') }}</p>
                        <p class="mt-1 text-sm text-sand-400 dark:text-sand-500">{{ t('Share what you think!') }}</p>
                    </div>

                    <div v-for="comment in post.comments" :key="comment.id">
                        <!-- Top-level comment -->
                        <div class="flex gap-3 border-b border-sand-50 px-4 py-3 dark:border-sand-800">
                            <Link :href="`/profiles/${comment.user.username}`" class="mt-0.5 flex-shrink-0">
                                <img
                                    :src="comment.user.avatar ?? `https://ui-avatars.com/api/?name=${comment.user.name}&background=f0dcc6&color=5c3f24&size=64`"
                                    :alt="comment.user.name"
                                    class="size-8 rounded-full object-cover"
                                />
                            </Link>
                            <div class="flex-1">
                                <p class="text-sm text-sand-800 dark:text-sand-200">
                                    <Link :href="`/profiles/${comment.user.username}`" class="font-semibold">{{ comment.user.name }}</Link>
                                    {{ ' ' + comment.body }}
                                </p>
                                <div class="mt-1 flex items-center gap-3">
                                    <span class="text-xs text-sand-400 dark:text-sand-500">{{ timeAgo(comment.created_at) }}</span>
                                    <button class="text-xs font-medium text-sand-500 dark:text-sand-400" @click="replyTo(comment)">{{ t('Reply') }}</button>
                                </div>
                            </div>
                            <div class="mt-1 flex flex-shrink-0 flex-col items-center gap-0.5">
                                <button @click="toggleCommentLike(comment)">
                                    <svg
                                        v-if="!comment.is_liked"
                                        xmlns="http://www.w3.org/2000/svg"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke-width="1.5"
                                        stroke="currentColor"
                                        class="size-4 text-sand-400 dark:text-sand-500"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
                                    </svg>
                                    <svg
                                        v-else
                                        xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 24 24"
                                        fill="currentColor"
                                        class="size-4 text-blush-400"
                                    >
                                        <path d="m11.645 20.91-.007-.003-.022-.012a15.247 15.247 0 0 1-.383-.218 25.18 25.18 0 0 1-4.244-3.17C4.688 15.36 2.25 12.174 2.25 8.25 2.25 5.322 4.714 3 7.688 3A5.5 5.5 0 0 1 12 5.052 5.5 5.5 0 0 1 16.313 3c2.973 0 5.437 2.322 5.437 5.25 0 3.925-2.438 7.111-4.739 9.256a25.175 25.175 0 0 1-4.244 3.17 15.247 15.247 0 0 1-.383.219l-.022.012-.007.004-.003.001a.752.752 0 0 1-.704 0l-.003-.001Z" />
                                    </svg>
                                </button>
                                <span v-if="comment.likes_count > 0" class="text-[10px] text-sand-400 dark:text-sand-500">{{ comment.likes_count }}</span>
                            </div>
                        </div>

                        <!-- Replies -->
                        <div v-for="reply in comment.replies" :key="reply.id" class="flex gap-3 border-b border-sand-50 py-3 pl-14 pr-4 dark:border-sand-800">
                            <Link :href="`/profiles/${reply.user.username}`" class="mt-0.5 flex-shrink-0">
                                <img
                                    :src="reply.user.avatar ?? `https://ui-avatars.com/api/?name=${reply.user.name}&background=f0dcc6&color=5c3f24&size=64`"
                                    :alt="reply.user.name"
                                    class="size-6 rounded-full object-cover"
                                />
                            </Link>
                            <div class="flex-1">
                                <p class="text-sm text-sand-800 dark:text-sand-200">
                                    <Link :href="`/profiles/${reply.user.username}`" class="font-semibold">{{ reply.user.name }}</Link>
                                    {{ ' ' + reply.body }}
                                </p>
                                <div class="mt-1 flex items-center gap-3">
                                    <span class="text-xs text-sand-400 dark:text-sand-500">{{ timeAgo(reply.created_at) }}</span>
                                    <button class="text-xs font-medium text-sand-500 dark:text-sand-400" @click="replyTo(comment)">{{ t('Reply') }}</button>
                                </div>
                            </div>
                            <div class="mt-1 flex flex-shrink-0 flex-col items-center gap-0.5">
                                <button @click="toggleCommentLike(reply)">
                                    <svg
                                        v-if="!reply.is_liked"
                                        xmlns="http://www.w3.org/2000/svg"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke-width="1.5"
                                        stroke="currentColor"
                                        class="size-4 text-sand-400 dark:text-sand-500"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
                                    </svg>
                                    <svg
                                        v-else
                                        xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 24 24"
                                        fill="currentColor"
                                        class="size-4 text-blush-400"
                                    >
                                        <path d="m11.645 20.91-.007-.003-.022-.012a15.247 15.247 0 0 1-.383-.218 25.18 25.18 0 0 1-4.244-3.17C4.688 15.36 2.25 12.174 2.25 8.25 2.25 5.322 4.714 3 7.688 3A5.5 5.5 0 0 1 12 5.052 5.5 5.5 0 0 1 16.313 3c2.973 0 5.437 2.322 5.437 5.25 0 3.925-2.438 7.111-4.739 9.256a25.175 25.175 0 0 1-4.244 3.17 15.247 15.247 0 0 1-.383.219l-.022.012-.007.004-.003.001a.752.752 0 0 1-.704 0l-.003-.001Z" />
                                    </svg>
                                </button>
                                <span v-if="reply.likes_count > 0" class="text-[10px] text-sand-400 dark:text-sand-500">{{ reply.likes_count }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Comment Input -->
            <div class="border-t border-sand-200 bg-white dark:border-sand-800 dark:bg-sand-900">
                <div v-if="replyingTo" class="flex items-center justify-between border-b border-sand-100 px-4 py-2 dark:border-sand-800">
                    <span class="text-xs text-sand-500 dark:text-sand-400">
                        {{ t('Replying to') }} <span class="font-semibold">{{ replyingTo.user.name }}</span>
                    </span>
                    <button class="text-xs font-medium text-sand-500 dark:text-sand-400" @click="cancelReply">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div class="flex items-center gap-3 px-4 py-3">
                    <div class="size-8 flex-shrink-0 rounded-full bg-sand-200 dark:bg-sand-800" />
                    <form class="flex flex-1 items-center gap-2" @submit.prevent="submitComment">
                        <input
                            ref="commentInput"
                            v-model="commentForm.body"
                            type="text"
                            :placeholder="replyingTo ? t('Write a reply...') : t('Write a comment...')"
                            class="flex-1 bg-transparent text-sm text-sand-800 placeholder-sand-400 focus:outline-none dark:text-sand-100 dark:placeholder-sand-500"
                        />
                        <button
                            type="submit"
                            class="text-sm font-semibold text-sand-600 disabled:opacity-30 dark:text-sand-400"
                            :disabled="!commentForm.body.trim() || commentForm.processing"
                        >
                            {{ t('Post') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
        </div>

    </AppLayout>
</template>
