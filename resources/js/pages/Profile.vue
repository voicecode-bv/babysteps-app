<script setup lang="ts">
import PostCard, { type PostData } from '@/components/PostCard.vue';
import { useTranslations } from '@/composables/useTranslations';
import AppLayout from '@/layouts/AppLayout.vue';
import { InfiniteScroll } from '@inertiajs/vue3';

interface Profile {
    id: number;
    name: string;
    username: string;
    avatar: string | null;
    bio: string | null;
    created_at: string;
    posts_count: number;
}

defineProps<{
    profile: Profile;
    posts: {
        data: PostData[];
    };
}>();

const { t } = useTranslations();

function goBack() {
    window.history.back();
}
</script>

<template>
    <AppLayout :title="profile.name">
        <template #header-left>
            <button class="flex items-center text-sand-700 dark:text-sand-300" @click="goBack">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                </svg>
            </button>
        </template>

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
                        <h2 class="text-lg font-semibold text-sand-800 dark:text-sand-100">{{ profile.name }}</h2>
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

            <!-- Posts -->
            <InfiniteScroll data="posts" only-next>
                <PostCard v-for="post in posts.data" :key="post.id" :post="post" />
            </InfiniteScroll>

            <div v-if="posts.data.length === 0" class="flex flex-col items-center justify-center px-8 py-20">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" class="mb-4 size-16 text-sand-300 dark:text-sand-600">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316Z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0Z" />
                </svg>
                <h3 class="text-lg font-semibold text-sand-800 dark:text-sand-200">{{ t('No moments yet') }}</h3>
            </div>
        </div>
    </AppLayout>
</template>
