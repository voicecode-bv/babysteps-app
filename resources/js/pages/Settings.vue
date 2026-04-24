<script setup lang="ts">
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import { Camera, Dialog, Events, Off, On } from '@nativephp/mobile';
import { computed, onMounted, onUnmounted, ref, useTemplateRef } from 'vue';
import Button from '@/components/Button.vue';
import IconTile from '@/components/IconTile.vue';
import PullToRefreshIndicator from '@/components/PullToRefreshIndicator.vue';
import SurfaceCard from '@/components/SurfaceCard.vue';
import { usePullToRefresh } from '@/composables/usePullToRefresh';
import { useTranslations } from '@/composables/useTranslations';
import AppLayout from '@/layouts/AppLayout.vue';
import bellIcon from '../../svg/doodle-icons/bell.svg';
import globeIcon from '../../svg/doodle-icons/globe.svg';
import lockIcon from '../../svg/doodle-icons/lock.svg';
import usersIcon from '../../svg/doodle-icons/user.svg';

interface Profile {
    id: number;
    name: string;
    username: string;
    avatar: string | null;
    bio: string | null;
    created_at: string;
    posts_count: number;
}

interface Circle {
    id: number;
    name: string;
    members_count: number;
}

const props = defineProps<{
    profile: Profile;
    circles: Circle[];
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

const defaultCircleIds = ref<number[]>([]);
const loadingDefaultCircles = ref(false);

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

async function loadDefaultCircles() {
    loadingDefaultCircles.value = true;

    try {
        const response = await fetch('/profile/default-circles');

        if (response.ok) {
            defaultCircleIds.value = await response.json();
        }
    } finally {
        loadingDefaultCircles.value = false;
    }
}

function toggleDefaultCircle(circleId: number) {
    const index = defaultCircleIds.value.indexOf(circleId);

    if (index === -1) {
        defaultCircleIds.value.push(circleId);
    } else {
        defaultCircleIds.value.splice(index, 1);
    }

    router.put('/profile/default-circles', { circle_ids: defaultCircleIds.value }, {
        preserveScroll: true,
    });
}

function togglePreference(key: keyof NotificationPreferences) {
    if (!notificationPreferences.value) {
        return;
    }

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
    if (!payload.success || payload.cancelled || !payload.files.length) {
        return;
    }

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
    loadDefaultCircles();
});

onUnmounted(() => {
    Off(Events.Alert.ButtonPressed, handleButtonPressed);
    Off(Events.Gallery.MediaSelected, handleMediaSelected);
});
</script>

<template>
    <AppLayout ref="layout" :title="t('Settings')">
        <div class="relative mt-10 min-h-full pb-[calc(theme(spacing.40)+env(safe-area-inset-bottom))]">
            <PullToRefreshIndicator :pull-distance="pullDistance" :is-refreshing="isRefreshing" />

            <div class="relative space-y-4 px-4 pt-4 pb-24">
                <!-- Profile card -->
                <SurfaceCard>
                    <div class="flex items-center gap-4">
                        <button
                            class="relative shrink-0"
                            :aria-label="t('Change profile picture')"
                            @click="pickAvatar"
                        >
                            <img
                                :src="profile.avatar ?? `https://ui-avatars.com/api/?name=${profile.name}&background=f0dcc6&color=5c3f24&size=128`"
                                :alt="profile.name"
                                class="size-20 rounded-full object-cover shadow-sm"
                            />
                            <span class="absolute -bottom-1 -right-1 flex size-8 items-center justify-center rounded-full bg-teal shadow-md ring-4 ring-white/70 dark:ring-sand-800/60">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-4 text-white">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0Z" />
                                </svg>
                            </span>
                        </button>
                        <Link :href="`/profiles/${profile.username}`" class="min-w-0 flex-1">
                            <h2 class="truncate font-sans text-xl font-bold text-teal">{{ profile.name }}</h2>
                            <p class="truncate text-base text-sand-600 dark:text-sand-300">@{{ profile.username }}</p>
                        </Link>
                    </div>

                    <div class="mt-5">
                        <template v-if="isEditingBio">
                            <form class="space-y-3" @submit.prevent="saveBio">
                                <textarea
                                    v-model="bioForm.bio"
                                    :placeholder="t('Write something about yourself...')"
                                    maxlength="150"
                                    rows="3"
                                    class="field-area text-base"
                                />
                                <div class="flex items-center justify-between gap-3">
                                    <span class="text-sm text-sand-500 dark:text-sand-400">{{ bioForm.bio.length }}/150</span>
                                    <div class="flex gap-2">
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="md"
                                            @click="cancelEditBio"
                                        >
                                            {{ t('Cancel') }}
                                        </Button>
                                        <Button
                                            type="submit"
                                            size="md"
                                            :disabled="bioForm.processing"
                                        >
                                            {{ t('Save') }}
                                        </Button>
                                    </div>
                                </div>
                            </form>
                        </template>
                        <template v-else>
                            <p v-if="profile.bio" class="text-base leading-relaxed text-sand-800 dark:text-sand-200">{{ profile.bio }}</p>
                            <button
                                class="mt-2 inline-flex items-center gap-1.5 rounded-full bg-sand-100 px-4 py-2 text-sm font-medium text-teal transition hover:bg-sand-200 dark:bg-sand-700/60 dark:text-sand-200 dark:hover:bg-sand-700"
                                @click="isEditingBio = true"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487z" />
                                </svg>
                                {{ profile.bio ? t('Edit bio') : t('Add a bio...') }}
                            </button>
                        </template>
                    </div>
                </SurfaceCard>

                <!-- Language -->
                <SurfaceCard>
                    <h3 class="mb-3 flex items-center gap-3 text-sm font-semibold text-sand-900 dark:text-sand-100">
                        <IconTile :icon="globeIcon" size="sm" tone="sage" />
                        {{ t('Language') }}
                    </h3>
                    <div class="flex gap-2">
                        <Button
                            block
                            size="lg"
                            :variant="currentLocale === 'nl' ? 'primary' : 'secondary'"
                            @click="setLocale('nl')"
                        >
                            Nederlands
                        </Button>
                        <Button
                            block
                            size="lg"
                            :variant="currentLocale === 'en' ? 'primary' : 'secondary'"
                            @click="setLocale('en')"
                        >
                            English
                        </Button>
                    </div>
                </SurfaceCard>

                <!-- Default circles -->
                <SurfaceCard v-if="circles && circles.length > 0">
                    <h3 class="flex items-center gap-3 text-sm font-semibold text-sand-900 dark:text-sand-100">
                        <IconTile :icon="usersIcon" size="sm" tone="sage" />
                        {{ t('Default circles for new posts') }}
                    </h3>
                    <p class="mt-1 text-sm text-sand-600 dark:text-sand-400">
                        {{ t('These circles will be pre-selected when you create a new post.') }}
                    </p>
                    <ul class="mt-3 divide-y divide-sand-100 dark:divide-sand-700/60">
                        <li v-for="circle in circles" :key="circle.id">
                            <label class="flex cursor-pointer items-center justify-between gap-3 py-3">
                                <span class="text-base text-sand-800 dark:text-sand-100">{{ circle.name }}</span>
                                <button
                                    type="button"
                                    role="switch"
                                    :aria-checked="defaultCircleIds.includes(circle.id)"
                                    :class="defaultCircleIds.includes(circle.id) ? 'bg-teal' : 'bg-sand-300 dark:bg-sand-600'"
                                    class="relative inline-flex h-8 w-14 shrink-0 cursor-pointer rounded-full transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-teal/40"
                                    @click="toggleDefaultCircle(circle.id)"
                                >
                                    <span
                                        :class="defaultCircleIds.includes(circle.id) ? 'translate-x-7' : 'translate-x-1'"
                                        class="pointer-events-none mt-1 size-6 rounded-full bg-white shadow transition-transform"
                                    />
                                </button>
                            </label>
                        </li>
                    </ul>
                </SurfaceCard>

                <!-- Notifications -->
                <SurfaceCard v-if="notificationPreferences">
                    <h3 class="flex items-center gap-3 text-sm font-semibold text-sand-900 dark:text-sand-100">
                        <IconTile :icon="bellIcon" size="sm" tone="sage" />
                        {{ t('Push notifications') }}
                    </h3>
                    <ul class="mt-3 divide-y divide-sand-100 dark:divide-sand-700/60">
                        <li v-for="(label, key) in ({
                            post_liked: t('Post liked'),
                            post_commented: t('Post commented'),
                            comment_liked: t('Comment liked'),
                            comment_replied: t('Comment replied'),
                            new_circle_post: t('New circle post'),
                            circle_invitation_accepted: t('Circle invitation accepted'),
                        } as Record<string, string>)" :key="key">
                            <label class="flex cursor-pointer items-center justify-between gap-3 py-3">
                                <span class="text-base text-sand-800 dark:text-sand-100">{{ label }}</span>
                                <button
                                    type="button"
                                    role="switch"
                                    :aria-checked="notificationPreferences[key as keyof NotificationPreferences]"
                                    :class="notificationPreferences[key as keyof NotificationPreferences] ? 'bg-teal' : 'bg-sand-300 dark:bg-sand-600'"
                                    class="relative inline-flex h-8 w-14 shrink-0 cursor-pointer rounded-full transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-teal/40"
                                    @click="togglePreference(key as keyof NotificationPreferences)"
                                >
                                    <span
                                        :class="notificationPreferences[key as keyof NotificationPreferences] ? 'translate-x-7' : 'translate-x-1'"
                                        class="pointer-events-none mt-1 size-6 rounded-full bg-white shadow transition-transform"
                                    />
                                </button>
                            </label>
                        </li>
                    </ul>
                </SurfaceCard>

                <!-- Account link -->
                <Link :href="'/settings/account'" class="block">
                    <SurfaceCard>
                        <div class="flex items-center justify-between gap-3">
                            <span class="flex items-center gap-3 text-sm font-semibold text-sand-900 dark:text-sand-100">
                                <IconTile :icon="lockIcon" size="sm" tone="sage" />
                                {{ t('Account') }}
                            </span>
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-5 text-sand-500 dark:text-sand-400">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                            </svg>
                        </div>
                    </SurfaceCard>
                </Link>

                <!-- Logout -->
                <Button
                    variant="danger"
                    size="lg"
                    block
                    @click="logout"
                >
                    {{ t('Log out') }}
                </Button>
            </div>
        </div>
    </AppLayout>
</template>
