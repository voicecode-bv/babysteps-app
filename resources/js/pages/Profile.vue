<script setup lang="ts">
import PostCard, { type PostData } from '@/components/PostCard.vue';
import { useTranslations } from '@/composables/useTranslations';
import AppLayout from '@/layouts/AppLayout.vue';
import { InfiniteScroll, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { Camera, Dialog, On, Off, Events } from '@nativephp/mobile';

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
const authUsername = computed(() => (page.props.auth as { user?: { username: string } })?.user?.username ?? null);
const isOwnProfile = computed(() => props.profile.username === authUsername.value);

const currentLocale = computed(() => page.props.locale as string);

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

function setLocale(locale: string) {
    router.put('/profile/locale', { locale }, { preserveScroll: true });
}

async function pickAvatar() {
    await Camera.pickImages().all();
}

function handleMediaSelected(payload: { success: boolean; files: { path: string; mimeType: string }[]; cancelled: boolean }) {
    if (!payload.success || payload.cancelled || !payload.files.length) return;
    router.post('/profile/avatar', { avatar_path: payload.files[0].path }, { preserveScroll: true });
}

function deleteAvatar() {
    router.delete('/profile/avatar', { preserveScroll: true });
}

function goBack() {
    window.history.length > 1 ? window.history.back() : router.visit('/');
}

async function logout() {
    await Dialog.alert()
        .confirm(t('Log out'), t('Are you sure you want to log out?'))
        .id('logout-confirm');
}

function handleButtonPressed(payload: { index: number; label: string; id?: string | null }) {
    if (payload.id === 'logout-confirm' && payload.index === 1) {
        router.post('/logout');
    }
}

onMounted(() => {
    On(Events.Alert.ButtonPressed, handleButtonPressed);
    On(Events.Gallery.MediaSelected, handleMediaSelected);
});

onUnmounted(() => {
    Off(Events.Alert.ButtonPressed, handleButtonPressed);
    Off(Events.Gallery.MediaSelected, handleMediaSelected);
});
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
                    <button v-if="isOwnProfile" class="relative" @click="pickAvatar">
                        <img
                            :src="profile.avatar ?? `https://ui-avatars.com/api/?name=${profile.name}&background=f0dcc6&color=5c3f24&size=128`"
                            :alt="profile.name"
                            class="size-20 rounded-full object-cover ring-2 ring-sand-200 dark:ring-sand-700"
                        />
                        <div class="absolute bottom-0 right-0 flex size-6 items-center justify-center rounded-full bg-sage-600 ring-2 ring-white dark:ring-sand-900">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-3.5 text-white">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0Z" />
                            </svg>
                        </div>
                    </button>
                    <img
                        v-else
                        :src="profile.avatar ?? `https://ui-avatars.com/api/?name=${profile.name}&background=f0dcc6&color=5c3f24&size=128`"
                        :alt="profile.name"
                        class="size-20 rounded-full object-cover ring-2 ring-sand-200 dark:ring-sand-700"
                    />
                    <div class="flex-1">
                        <h2 class="font-display text-lg font-semibold text-sand-800 dark:text-sand-100">{{ profile.name }}</h2>
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

                <!-- Language -->
                <div v-if="isOwnProfile" class="mt-4">
                    <p class="mb-2 text-xs font-medium text-sand-500 dark:text-sand-400">{{ t('Language') }}</p>
                    <div class="flex gap-2">
                        <button
                            class="flex-1 rounded-lg border py-2.5 text-sm font-medium transition-colors"
                            :class="currentLocale === 'nl'
                                ? 'border-sand-500 bg-sand-500 text-white dark:border-sand-600 dark:bg-sand-600'
                                : 'border-sand-200 text-sand-700 dark:border-sand-700 dark:text-sand-300'"
                            @click="setLocale('nl')"
                        >
                            Nederlands
                        </button>
                        <button
                            class="flex-1 rounded-lg border py-2.5 text-sm font-medium transition-colors"
                            :class="currentLocale === 'en'
                                ? 'border-sand-500 bg-sand-500 text-white dark:border-sand-600 dark:bg-sand-600'
                                : 'border-sand-200 text-sand-700 dark:border-sand-700 dark:text-sand-300'"
                            @click="setLocale('en')"
                        >
                            English
                        </button>
                    </div>
                </div>

                <!-- Logout -->
                <button
                    v-if="isOwnProfile"
                    class="mt-4 w-full rounded-lg border border-red-200 py-3 text-sm font-medium text-red-600 dark:border-red-800 dark:text-red-400"
                    @click="logout"
                >
                    {{ t('Log out') }}
                </button>
            </div>

            <div class="h-2 bg-sand-100 dark:bg-sand-800" />

            <!-- Posts -->
            <InfiniteScroll v-if="posts" data="posts" only-next>
                <PostCard v-for="post in posts.data" :key="post.id" :post="post" />
            </InfiniteScroll>

            <div v-if="posts && posts.data.length === 0" class="flex flex-col items-center justify-center px-8 py-20">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" class="mb-4 size-16 text-sand-300 dark:text-sand-600">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316Z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0Z" />
                </svg>
                <h3 class="font-display text-lg font-semibold text-sand-800 dark:text-sand-200">{{ t('No moments yet') }}</h3>
            </div>

        </div>
    </AppLayout>
</template>
