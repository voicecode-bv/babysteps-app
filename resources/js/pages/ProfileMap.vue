<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import PhotoMap from '@/components/PhotoMap.vue';
import AppLayout from '@/layouts/AppLayout.vue';

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
    mapboxToken: string | null;
    profile: Profile;
}>();

function goBack() {
    if (window.history.length > 1) {
        window.history.back();
    } else {
        router.visit(`/profiles/${props.profile.username}`);
    }
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

        <div class="relative mt-10 flex-1">
            <PhotoMap :mapbox-token="mapboxToken" :fetch-url="`/profiles/${profile.username}/photos/map`" />
        </div>
    </AppLayout>
</template>
