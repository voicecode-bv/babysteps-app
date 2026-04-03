<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ref } from 'vue';

export interface PostData {
    id: number;
    media_url: string;
    media_type: string;
    caption: string | null;
    location: string | null;
    created_at: string;
    user: {
        id: number;
        name: string;
        username: string;
        avatar: string | null;
    };
    likes_count: number;
    comments_count: number;
}

const props = defineProps<{
    post: PostData;
}>();

const isLiked = ref(false);
const likesCount = ref(props.post.likes_count);
const showFullCaption = ref(false);

function toggleLike() {
    isLiked.value = !isLiked.value;
    likesCount.value += isLiked.value ? 1 : -1;
}

function timeAgo(dateString: string): string {
    const date = new Date(dateString);
    const now = new Date();
    const seconds = Math.floor((now.getTime() - date.getTime()) / 1000);

    if (seconds < 60) return 'nu';
    if (seconds < 3600) return `${Math.floor(seconds / 60)} min`;
    if (seconds < 86400) return `${Math.floor(seconds / 3600)} u`;
    if (seconds < 604800) return `${Math.floor(seconds / 86400)} d`;
    return `${Math.floor(seconds / 604800)} w`;
}
</script>

<template>
    <article class="border-b border-neutral-800">
        <!-- Post Header -->
        <div class="flex items-center gap-3 px-4 py-3">
            <img
                :src="post.user.avatar ?? `https://ui-avatars.com/api/?name=${post.user.name}&background=333&color=fff&size=64`"
                :alt="post.user.username"
                class="size-8 rounded-full object-cover"
            />
            <div class="flex-1">
                <p class="text-sm font-semibold">{{ post.user.username }}</p>
                <p v-if="post.location" class="text-xs text-neutral-400">{{ post.location }}</p>
            </div>
            <button class="text-white">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 12a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0ZM12.75 12a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0ZM18.75 12a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
                </svg>
            </button>
        </div>

        <!-- Post Media -->
        <Link :href="`/posts/${post.id}`" class="block">
            <div class="relative aspect-square w-full bg-neutral-900">
                <img
                    v-if="post.media_type === 'image'"
                    :src="post.media_url"
                    :alt="post.caption ?? 'Post'"
                    class="size-full object-cover"
                    loading="lazy"
                />
                <div v-else class="flex size-full items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-12 text-neutral-600">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.91 11.672a.375.375 0 0 1 0 .656l-5.603 3.113a.375.375 0 0 1-.557-.328V8.887c0-.286.307-.466.557-.327l5.603 3.112Z" />
                    </svg>
                </div>
            </div>
        </Link>

        <!-- Action Buttons -->
        <div class="flex items-center gap-4 px-4 py-3">
            <button @click="toggleLike">
                <svg
                    v-if="!isLiked"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke-width="1.5"
                    stroke="currentColor"
                    class="size-6"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
                </svg>
                <svg
                    v-else
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 24 24"
                    fill="currentColor"
                    class="size-6 text-red-500"
                >
                    <path d="m11.645 20.91-.007-.003-.022-.012a15.247 15.247 0 0 1-.383-.218 25.18 25.18 0 0 1-4.244-3.17C4.688 15.36 2.25 12.174 2.25 8.25 2.25 5.322 4.714 3 7.688 3A5.5 5.5 0 0 1 12 5.052 5.5 5.5 0 0 1 16.313 3c2.973 0 5.437 2.322 5.437 5.25 0 3.925-2.438 7.111-4.739 9.256a25.175 25.175 0 0 1-4.244 3.17 15.247 15.247 0 0 1-.383.219l-.022.012-.007.004-.003.001a.752.752 0 0 1-.704 0l-.003-.001Z" />
                </svg>
            </button>
            <Link :href="`/posts/${post.id}`">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 20.25c4.97 0 9-3.694 9-8.25s-4.03-8.25-9-8.25S3 7.444 3 12c0 2.104.859 4.023 2.273 5.48.432.447.74 1.04.586 1.641a4.483 4.483 0 0 1-.923 1.785A5.969 5.969 0 0 0 6 21c1.282 0 2.47-.402 3.445-1.087.81.22 1.668.337 2.555.337Z" />
                </svg>
            </Link>
            <button>
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
                </svg>
            </button>
            <div class="flex-1" />
            <button>
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0Z" />
                </svg>
            </button>
        </div>

        <!-- Likes Count -->
        <div class="px-4">
            <p v-if="likesCount > 0" class="text-sm font-semibold">
                {{ likesCount }} {{ likesCount === 1 ? 'like' : 'likes' }}
            </p>
        </div>

        <!-- Caption -->
        <div v-if="post.caption" class="px-4 pb-1 pt-1">
            <p class="text-sm">
                <span class="font-semibold">{{ post.user.username }}</span>
                {{ ' ' }}
                <template v-if="!showFullCaption && post.caption.length > 100">
                    {{ post.caption.substring(0, 100) }}...
                    <button class="text-neutral-400" @click="showFullCaption = true">meer</button>
                </template>
                <template v-else>
                    {{ post.caption }}
                </template>
            </p>
        </div>

        <!-- Comments Preview -->
        <div class="px-4 pb-2">
            <Link
                v-if="post.comments_count > 0"
                :href="`/posts/${post.id}`"
                class="text-sm text-neutral-400"
            >
                Bekijk alle {{ post.comments_count }} {{ post.comments_count === 1 ? 'reactie' : 'reacties' }}
            </Link>
            <p class="mt-1 text-[10px] uppercase text-neutral-500">{{ timeAgo(post.created_at) }}</p>
        </div>
    </article>
</template>
