<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { useTranslations } from '@/composables/useTranslations';

export interface PostData {
    id: number;
    media_url: string;
    media_type: string;
    thumbnail_url: string | null;
    caption: string | null;
    location: string | null;
    created_at: string;
    user: {
        id: number;
        name: string;
        username: string;
        avatar: string | null;
    };
    circles?: {
        id: number;
        name: string;
        photo: string | null;
    }[];
    is_liked: boolean;
    likes_count: number;
    comments_count: number;
}

const props = defineProps<{
    post: PostData;
}>();

const { t } = useTranslations();

const page = usePage();
const authUserId = computed(() => (page.props.auth as { user?: { id: number } })?.user?.id ?? null);
const isLiked = ref(props.post.is_liked);
const likesCount = ref(props.post.likes_count);
const showFullCaption = ref(false);

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
    <article class="bg-white dark:bg-sand-900">
        <!-- Post Header -->
        <div class="flex items-center gap-3 px-4 py-3">
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
        <Link v-if="post.media_type === 'image'" :href="`/posts/${post.id}`" class="block">
            <div class="relative aspect-square w-full bg-sand-100 dark:bg-sand-800">
                <img
                    :src="post.media_url"
                    :alt="post.caption ?? t('Photo')"
                    class="size-full object-cover"
                    loading="lazy"
                />
            </div>
        </Link>
        <div v-else-if="post.media_type === 'video'" class="relative aspect-square w-full bg-sand-100 dark:bg-sand-800">
            <video
                :src="post.media_url"
                :poster="post.thumbnail_url ?? undefined"
                class="size-full object-cover"
                playsinline
                muted
                autoplay
                loop
                preload="metadata"
            />
        </div>
        <Link v-else :href="`/posts/${post.id}`" class="block">
            <div class="relative aspect-square w-full bg-sand-100 dark:bg-sand-800">
                <div class="flex size-full items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-12 text-sand-300 dark:text-sand-600">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.91 11.672a.375.375 0 0 1 0 .656l-5.603 3.113a.375.375 0 0 1-.557-.328V8.887c0-.286.307-.466.557-.327l5.603 3.112Z" />
                    </svg>
                </div>
            </div>
        </Link>

        <!-- Action Buttons -->
        <div class="flex items-center gap-4 px-4 py-3">
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
            <Link :href="`/posts/${post.id}`" class="flex items-center gap-1 text-sand-600 dark:text-sand-300">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 20.25c4.97 0 9-3.694 9-8.25s-4.03-8.25-9-8.25S3 7.444 3 12c0 2.104.859 4.023 2.273 5.48.432.447.74 1.04.586 1.641a4.483 4.483 0 0 1-.923 1.785A5.969 5.969 0 0 0 6 21c1.282 0 2.47-.402 3.445-1.087.81.22 1.668.337 2.555.337Z" />
                </svg>
                <span v-if="post.comments_count > 0" class="text-sm font-medium text-sand-700 dark:text-sand-200">{{ post.comments_count }}</span>
            </Link>
        </div>

        <div class="px-4 space-y-2">
            <!-- Caption -->
            <p v-if="post.caption" class="text-sm text-sand-800 dark:text-sand-200">
                <template v-if="!showFullCaption && post.caption.length > 100">
                    {{ post.caption.substring(0, 100) }}...
                    <button class="text-sand-400 dark:text-sand-500" @click="showFullCaption = true">{{ t('more') }}</button>
                </template>
                <template v-else>
                    {{ post.caption }}
                </template>
            </p>

            <!-- Timestamp -->
            <p class="text-xs text-sand-400 dark:text-sand-500">{{ timeAgo(post.created_at) }}</p>
        </div>

        <!-- Circles -->
        <div v-if="post.circles && post.circles.length > 0" class="px-4 py-3">
            <div class="flex flex-wrap gap-1.5">
                <Link
                    v-for="circle in post.circles"
                    :key="circle.id"
                    :href="`/circles/${circle.id}`"
                    class="flex items-center gap-1.5 rounded-full bg-sand-100 py-0.5 pl-0.5 pr-2.5 dark:bg-sand-800"
                >
                    <img
                        v-if="circle.photo"
                        :src="circle.photo"
                        :alt="circle.name"
                        class="size-5 rounded-full object-cover"
                    />
                    <div v-else class="flex size-5 items-center justify-center rounded-full bg-sand-200 dark:bg-sand-700">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-3 text-sand-500 dark:text-sand-400">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                        </svg>
                    </div>
                    <span class="text-xs font-medium text-sand-700 dark:text-sand-200">{{ circle.name }}</span>
                </Link>
            </div>
        </div>

        <!-- Separator -->
        <div class="my-2 mx-4 border-b border-sand-100 dark:border-sand-800" />
    </article>
</template>
