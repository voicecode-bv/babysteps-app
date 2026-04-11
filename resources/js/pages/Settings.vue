<script setup lang="ts">
import PullToRefreshIndicator from '@/components/PullToRefreshIndicator.vue';
import { usePullToRefresh } from '@/composables/usePullToRefresh';
import { useTranslations } from '@/composables/useTranslations';
import AppLayout from '@/layouts/AppLayout.vue';
import Button from '@/components/Button.vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref, useTemplateRef } from 'vue';
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
}>();

const { t } = useTranslations();
const page = usePage();

const currentLocale = computed(() => page.props.locale as string);

const isEditingBio = ref(false);
const bioForm = useForm({ bio: props.profile.bio ?? '' });

interface NotificationPreferences {
    post_liked: boolean;
    post_commented: boolean;
    comment_liked: boolean;
    comment_replied: boolean;
    new_circle_post: boolean;
    circle_invitation_accepted: boolean;
}

const notificationPreferences = ref<NotificationPreferences | null>(null);
const loadingPreferences = ref(false);

const layoutRef = useTemplateRef<InstanceType<typeof AppLayout>>('layout');
const containerRef = computed(() => layoutRef.value?.mainRef ?? null);

const { pullDistance, isRefreshing } = usePullToRefresh({
    onRefresh: () =>
        new Promise<void>((resolve) => {
            router.reload({
                only: ['profile'],
                onFinish: () => resolve(),
            });
        }),
    containerRef,
});

async function loadNotificationPreferences() {
    loadingPreferences.value = true;
    try {
        const response = await fetch('/profile/notification-preferences');
        if (response.ok) {
            notificationPreferences.value = await response.json();
        }
    } finally {
        loadingPreferences.value = false;
    }
}

function togglePreference(key: keyof NotificationPreferences) {
    if (!notificationPreferences.value) return;

    notificationPreferences.value[key] = !notificationPreferences.value[key];

    router.put('/profile/notification-preferences', notificationPreferences.value, {
        preserveScroll: true,
    });
}

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
    loadNotificationPreferences();
});

onUnmounted(() => {
    Off(Events.Alert.ButtonPressed, handleButtonPressed);
    Off(Events.Gallery.MediaSelected, handleMediaSelected);
});
</script>

<template>
    <AppLayout ref="layout" :title="t('Settings')">

        <PullToRefreshIndicator :pull-distance="pullDistance" :is-refreshing="isRefreshing" />

        <div class="pb-24">
            <div class="bg-white px-4 py-6 dark:bg-sand-900">
                <!-- Avatar & Name -->
                <div class="flex items-center gap-4">
                    <button class="relative" @click="pickAvatar">
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
                    <div class="flex-1">
                        <h2 class="font-display text-lg font-semibold text-sand-800 dark:text-sand-100">{{ profile.name }}</h2>
                        <p class="text-sm text-sand-500 dark:text-sand-400">@{{ profile.username }}</p>
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
                                class="field-area"
                            />
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-sand-400 dark:text-sand-500">{{ bioForm.bio.length }}/150</span>
                                <div class="flex gap-2">
                                    <button type="button" class="text-sm text-sand-500 dark:text-sand-400" @click="cancelEditBio">{{ t('Cancel') }}</button>
                                    <Button
                                        type="submit"
                                        size="sm"
                                        :disabled="bioForm.processing"
                                    >
                                        {{ t('Save') }}
                                    </Button>
                                </div>
                            </div>
                        </form>
                    </template>
                    <template v-else>
                        <p v-if="profile.bio" class="text-sm text-sand-700 dark:text-sand-300">{{ profile.bio }}</p>
                        <button
                            class="mt-1 text-xs font-medium text-sand-500 dark:text-sand-400"
                            @click="isEditingBio = true"
                        >
                            {{ profile.bio ? t('Edit bio') : t('Add a bio...') }}
                        </button>
                    </template>
                </div>

                <div class="mx-0 mt-4 border-b border-sand-100 dark:border-sand-800" />

                <!-- Language -->
                <div class="mt-4">
                    <p class="mb-2 text-xs font-medium text-sand-500 dark:text-sand-400">{{ t('Language') }}</p>
                    <div class="flex gap-2">
                        <Button
                            block
                            :variant="currentLocale === 'nl' ? 'primary' : 'secondary'"
                            @click="setLocale('nl')"
                        >
                            Nederlands
                        </Button>
                        <Button
                            block
                            :variant="currentLocale === 'en' ? 'primary' : 'secondary'"
                            @click="setLocale('en')"
                        >
                            English
                        </Button>
                    </div>
                </div>

                <div v-if="notificationPreferences" class="mt-4 border-b border-sand-100 dark:border-sand-800" />

                <!-- Notification Preferences -->
                <div v-if="notificationPreferences" class="mt-4">
                    <p class="mb-2 text-xs font-medium text-sand-500 dark:text-sand-400">{{ t('Push notifications') }}</p>
                    <div class="space-y-3">
                        <label v-for="(label, key) in {
                            post_liked: t('Post liked'),
                            post_commented: t('Post commented'),
                            comment_liked: t('Comment liked'),
                            comment_replied: t('Comment replied'),
                            new_circle_post: t('New circle post'),
                            circle_invitation_accepted: t('Circle invitation accepted'),
                        } as Record<string, string>" :key="key" class="flex items-center justify-between">
                            <span class="text-sm text-sand-700 dark:text-sand-200">{{ label }}</span>
                            <button
                                type="button"
                                role="switch"
                                :aria-checked="notificationPreferences[key as keyof NotificationPreferences]"
                                :class="notificationPreferences[key as keyof NotificationPreferences] ? 'bg-teal' : 'bg-sand-300 dark:bg-sand-600'"
                                class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full transition-colors"
                                @click="togglePreference(key as keyof NotificationPreferences)"
                            >
                                <span
                                    :class="notificationPreferences[key as keyof NotificationPreferences] ? 'translate-x-5.5' : 'translate-x-0.5'"
                                    class="pointer-events-none mt-0.5 size-5 rounded-full bg-white shadow transition-transform"
                                />
                            </button>
                        </label>
                    </div>
                </div>

                <div class="mt-4 border-b border-sand-100 dark:border-sand-800" />

                <!-- Logout -->
                <Button
                    variant="danger"
                    block
                    class="mt-4"
                    @click="logout"
                >
                    {{ t('Log out') }}
                </Button>
            </div>
        </div>
    </AppLayout>
</template>
