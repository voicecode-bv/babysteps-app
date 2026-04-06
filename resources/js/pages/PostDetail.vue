<script setup lang="ts">
import { useTranslations } from '@/composables/useTranslations';
import AppLayout from '@/layouts/AppLayout.vue';
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface User {
    id: number;
    name: string;
    username: string;
    avatar: string | null;
}

interface Comment {
    id: number;
    body: string;
    created_at: string;
    user: User;
    likes_count: number;
    is_liked: boolean;
}

interface Like {
    id: number;
    user_id: number;
}

interface Post {
    id: number;
    media_url: string;
    media_type: string;
    caption: string | null;
    location: string | null;
    created_at: string;
    user: User;
    comments: Comment[];
    likes: Like[];
    likes_count: number;
    comments_count: number;
}

const props = defineProps<{
    post: Post;
}>();

const { t } = useTranslations();

const page = usePage();
const authUserId = computed(() => (page.props.auth as { user?: { id: number } })?.user?.id ?? null);
const isLiked = computed(() => props.post.is_liked ?? false);
const commentForm = useForm({ body: '' });
const commentInput = ref<HTMLInputElement>();

function focusComment() {
    commentInput.value?.focus();
}

function toggleLike() {
    if (isLiked.value) {
        router.delete(`/posts/${props.post.id}/like`, { preserveScroll: true });
    } else {
        router.post(`/posts/${props.post.id}/like`, {}, { preserveScroll: true });
    }
}

function toggleCommentLike(comment: Comment) {
    if (comment.is_liked) {
        router.delete(`/comments/${comment.id}/like`, { preserveScroll: true });
    } else {
        router.post(`/comments/${comment.id}/like`, {}, { preserveScroll: true });
    }
}

function submitComment() {
    if (!commentForm.body.trim()) return;

    commentForm.post(`/posts/${props.post.id}/comments`, {
        preserveScroll: true,
        onSuccess: () => commentForm.reset(),
    });
}

function goBack() {
    window.history.back();
}

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
    <AppLayout :title="t('Moment')">
        <template #header-left>
            <button class="flex items-center text-sand-700 dark:text-sand-300" @click="goBack">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                </svg>
            </button>
        </template>

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
            <div class="relative aspect-square w-full bg-sand-100 dark:bg-sand-800">
                <img
                    v-if="post.media_type === 'image'"
                    :src="post.media_url"
                    :alt="post.caption ?? t('Photo')"
                    class="size-full object-cover"
                />
                <div v-else class="flex size-full items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-16 text-sand-300 dark:text-sand-600">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.91 11.672a.375.375 0 0 1 0 .656l-5.603 3.113a.375.375 0 0 1-.557-.328V8.887c0-.286.307-.466.557-.327l5.603 3.112Z" />
                    </svg>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center gap-4 bg-white px-4 py-3 dark:bg-sand-900">
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
                <button class="text-sand-600 dark:text-sand-300" @click="focusComment">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 20.25c4.97 0 9-3.694 9-8.25s-4.03-8.25-9-8.25S3 7.444 3 12c0 2.104.859 4.023 2.273 5.48.432.447.74 1.04.586 1.641a4.483 4.483 0 0 1-.923 1.785A5.969 5.969 0 0 0 6 21c1.282 0 2.47-.402 3.445-1.087.81.22 1.668.337 2.555.337Z" />
                    </svg>
                </button>
            </div>

            <!-- Likes -->
            <div class="bg-white px-4 pb-2 dark:bg-sand-900">
                <p v-if="post.likes_count > 0" class="text-sm font-medium text-sand-700 dark:text-sand-200">
                    {{ post.likes_count === 1 ? t(':count person likes this', { count: post.likes_count }) : t(':count people like this', { count: post.likes_count }) }}
                </p>
            </div>

            <!-- Caption -->
            <div v-if="post.caption" class="bg-white px-4 pb-3 dark:bg-sand-900">
                <p class="text-sm text-sand-800 dark:text-sand-200">
                    <span class="font-semibold">{{ post.user.name }}</span>
                    {{ ' ' + post.caption }}
                </p>
                <p class="mt-1 text-xs text-sand-400 dark:text-sand-500">{{ timeAgo(post.created_at) }}</p>
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

                <div v-for="comment in post.comments" :key="comment.id" class="flex gap-3 border-b border-sand-50 px-4 py-3 dark:border-sand-800">
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
                            <button class="text-xs font-medium text-sand-500 dark:text-sand-400">{{ t('Reply') }}</button>
                        </div>
                    </div>
                    <div class="mt-1 flex flex-shrink-0 flex-col items-center gap-0.5">
                        <button v-if="comment.user.id !== authUserId" @click="toggleCommentLike(comment)">
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
            </div>
        </div>

        <!-- Comment Input -->
        <div class="flex items-center gap-3 border-t border-sand-200 bg-white px-4 py-3 dark:border-sand-800 dark:bg-sand-900">
            <div class="size-8 flex-shrink-0 rounded-full bg-sand-200 dark:bg-sand-800" />
            <form class="flex flex-1 items-center gap-2" @submit.prevent="submitComment">
                <input
                    ref="commentInput"
                    v-model="commentForm.body"
                    type="text"
                    :placeholder="t('Write a comment...')"
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
    </AppLayout>
</template>
