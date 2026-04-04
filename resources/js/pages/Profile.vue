<script setup lang="ts">
import PostCard, { type PostData } from '@/components/PostCard.vue';
import { useTranslations } from '@/composables/useTranslations';
import AppLayout from '@/layouts/AppLayout.vue';
import { router } from '@inertiajs/vue3';

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
    window.history.length > 1 ? window.history.back() : router.visit('/');
}
</script>

<template>
    <AppLayout :title="profile.name">
        <template #header-left>
            <button class="text-sand-700 dark:text-sand-300" @click="goBack">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                </svg>
            </button>
        </template>

        <!-- Profile Info -->
        <div class="bg-white px-4 py-5 dark:bg-sand-900">
            <div class="flex items-center gap-4">
                <img
                    :src="profile.avatar ?? `https://ui-avatars.com/api/?name=${profile.name}&background=f0dcc6&color=5c3f24&size=128`"
                    :alt="profile.name"
                    class="size-20 rounded-full object-cover ring-2 ring-sand-200 dark:ring-sand-700"
                />
                <div class="flex-1">
                    <h2 class="text-lg font-semibold text-sand-800 dark:text-sand-100">{{ profile.name }}</h2>
                    <p class="text-sm text-sand-500 dark:text-sand-400">@{{ profile.username }}</p>
                    <p class="mt-1 text-sm text-sand-600 dark:text-sand-300">
                        {{ profile.posts_count === 1 ? t(':count moment', { count: profile.posts_count }) : t(':count moments', { count: profile.posts_count }) }}
                    </p>
                </div>
            </div>
            <p v-if="profile.bio" class="mt-3 text-sm text-sand-600 dark:text-sand-400">{{ profile.bio }}</p>
        </div>

        <!-- Posts -->
        <div class="mt-2">
            <PostCard v-for="post in posts.data" :key="post.id" :post="post" />

            <div v-if="posts.data.length === 0" class="px-4 py-8 text-center">
                <p class="text-sm font-medium text-sand-600 dark:text-sand-300">{{ t('No moments yet') }}</p>
            </div>
        </div>
    </AppLayout>
</template>
