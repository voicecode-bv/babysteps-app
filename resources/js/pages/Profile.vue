<script setup lang="ts">
import PostCard, { type PostData } from '@/components/PostCard.vue';
import { useTranslations } from '@/composables/useTranslations';
import AppLayout from '@/layouts/AppLayout.vue';
import { InfiniteScroll, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

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
const page = usePage();
const authUserId = computed(() => (page.props.auth as { user: { id: number } }).user.id);
const isOwnProfile = computed(() => props.profile.id === authUserId.value);

const isEditingBio = ref(false);
const bioForm = useForm({ bio: props.profile.bio ?? '' });

function saveBio() {
    bioForm.put('/profile/bio', {
        preserveScroll: true,
        onSuccess: () => {
            isEditingBio.value = false;
        },
    });
}

function cancelEditBio() {
    bioForm.bio = props.profile.bio ?? '';
    isEditingBio.value = false;
}

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

                <!-- Bio display / edit -->
                <div class="mt-3">
                    <template v-if="isEditingBio">
                        <form @submit.prevent="saveBio" class="space-y-2">
                            <textarea
                                v-model="bioForm.bio"
                                :placeholder="t('Write something about yourself...')"
                                maxlength="150"
                                rows="2"
                                class="w-full resize-none rounded-lg border border-sand-200 bg-sand-50 px-3 py-2 text-sm text-sand-800 placeholder-sand-400 focus:border-sand-400 focus:outline-none dark:border-sand-700 dark:bg-sand-800 dark:text-sand-100 dark:placeholder-sand-500"
                            />
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-sand-400 dark:text-sand-500">{{ bioForm.bio.length }}/150</span>
                                <div class="flex gap-2">
                                    <button type="button" class="text-sm text-sand-500 dark:text-sand-400" @click="cancelEditBio">{{ t('Cancel') }}</button>
                                    <button
                                        type="submit"
                                        class="rounded-lg bg-sand-500 px-3 py-1 text-sm font-medium text-white disabled:opacity-50 dark:bg-sand-600"
                                        :disabled="bioForm.processing"
                                    >
                                        {{ t('Save') }}
                                    </button>
                                </div>
                            </div>
                        </form>
                    </template>
                    <template v-else>
                        <p v-if="profile.bio" class="text-sm text-sand-700 dark:text-sand-300">{{ profile.bio }}</p>
                        <button
                            v-if="isOwnProfile"
                            class="mt-1 text-xs font-medium text-sand-500 dark:text-sand-400"
                            @click="isEditingBio = true"
                        >
                            {{ profile.bio ? t('Edit bio') : t('Add a bio...') }}
                        </button>
                    </template>
                </div>
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
