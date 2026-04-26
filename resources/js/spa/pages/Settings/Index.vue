<script setup lang="ts">
import { Camera, Dialog, Events, Off, On } from '@nativephp/mobile';
import { computed, onMounted, onUnmounted, ref, useTemplateRef } from 'vue';
import { useRouter } from 'vue-router';
import Button from '@/components/Button.vue';
import IconTile from '@/components/IconTile.vue';
import PullToRefreshIndicator from '@/components/PullToRefreshIndicator.vue';
import SurfaceCard from '@/components/SurfaceCard.vue';
import AppLayout from '@/spa/layouts/AppLayout.vue';
import { useTranslations } from '@/spa/composables/useTranslations';
import { useApiForm } from '@/spa/composables/useApiForm';
import { usePullToRefresh } from '@/spa/composables/usePullToRefresh';
import { useAuthStore } from '@/spa/stores/auth';
import { useI18nStore } from '@/spa/stores/i18n';
import { useToastsStore } from '@/spa/stores/toasts';
import { api } from '@/spa/http/apiClient';
import { externalApi } from '@/spa/http/externalApi';
import bellIcon from '../../../../svg/doodle-icons/bell.svg';
import globeIcon from '../../../../svg/doodle-icons/globe.svg';
import lockIcon from '../../../../svg/doodle-icons/lock.svg';
import tagIcon from '../../../../svg/doodle-icons/tag.svg';
import usersIcon from '../../../../svg/doodle-icons/user.svg';

interface Profile {
    id: number;
    name: string;
    username: string;
    avatar: string | null;
    bio: string | null;
    created_at: string;
    posts_count: number;
}

const { t } = useTranslations();
const i18n = useI18nStore();
const auth = useAuthStore();
const router = useRouter();
const toasts = useToastsStore();

const profile = ref<Profile | null>(null);

const currentLocale = computed(() => i18n.locale);

const languageIconStyle = computed(() => ({
    maskImage: `url(${globeIcon})`,
    WebkitMaskImage: `url(${globeIcon})`,
    maskSize: 'contain',
    WebkitMaskSize: 'contain',
    maskRepeat: 'no-repeat',
    WebkitMaskRepeat: 'no-repeat',
    maskPosition: 'center',
    WebkitMaskPosition: 'center',
}));

const isEditingBio = ref(false);
const bioForm = useApiForm({ bio: '' }, externalApi);

const layoutRef = useTemplateRef<InstanceType<typeof AppLayout>>('layout');
const containerRef = computed(() => layoutRef.value?.mainRef ?? null);

async function loadProfile(): Promise<void> {
    if (!auth.user) {
        return;
    }
    try {
        const data = await externalApi.get<{ data: Profile }>(`/profiles/${auth.user.username}`);
        profile.value = data.data;
        bioForm.data.bio = data.data.bio ?? '';
    } catch {
        // ignore - retry via pull-to-refresh
    }
}

async function refresh(): Promise<void> {
    // Bij refresh annuleer eventuele open bio-edit zodat we niet de
    // serverwaarde overschrijven met een stale draft.
    isEditingBio.value = false;
    await loadProfile();
}

const { pullDistance, isRefreshing } = usePullToRefresh({
    onRefresh: refresh,
    containerRef,
});

onMounted(loadProfile);

async function saveBio(): Promise<void> {
    await bioForm.put('/profile', {
        onSuccess: () => {
            isEditingBio.value = false;
            if (profile.value) {
                profile.value.bio = bioForm.data.bio;
            }
            if (auth.user) {
                auth.user.bio = bioForm.data.bio;
            }
            toasts.success(t('Bio updated'));
        },
    });
}

function cancelEditBio(): void {
    bioForm.data.bio = profile.value?.bio ?? '';
    isEditingBio.value = false;
}

async function setLocale(locale: string): Promise<void> {
    i18n.set(locale);
    if (auth.user) {
        auth.user.locale = locale;
    }
    try {
        await externalApi.put('/profile', { locale });
        toasts.success(t('Language updated'));
    } catch {
        // i18n is al lokaal toegepast; volgende bootstrap synct met server.
    }
}

async function pickAvatar(): Promise<void> {
    await Camera.pickImages().all();
}

async function handleMediaSelected(payload: { success: boolean; files: { path: string; mimeType: string }[]; cancelled: boolean }): Promise<void> {
    if (!payload.success || payload.cancelled || !payload.files.length) {
        return;
    }

    try {
        const data = await api.post<{ avatar: string }>('/api/spa/settings/profile/avatar', {
            avatar_path: payload.files[0].path,
        });
        if (profile.value) {
            profile.value.avatar = data.avatar;
        }
        if (auth.user) {
            auth.user.avatar = data.avatar;
        }
    } catch {
        // ignore
    }
}

async function deleteAvatar(): Promise<void> {
    try {
        await externalApi.delete('/profile/avatar');
        if (profile.value) {
            profile.value.avatar = null;
        }
        if (auth.user) {
            auth.user.avatar = null;
        }
        toasts.success(t('Photo removed'));
    } catch {
        toasts.error(t('Failed to remove photo'));
    }
}

async function logout(): Promise<void> {
    await Dialog.alert()
        .confirm(t('Log out'), t('Are you sure you want to log out?'))
        .id('logout-confirm');
}

async function handleButtonPressed(payload: { index: number; label: string; id?: string | null }): Promise<void> {
    if (payload.id === 'logout-confirm' && payload.index === 1) {
        await auth.logout();
        router.push({ name: 'spa.login' });
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
    <AppLayout ref="layout" :title="t('Settings')">
        <div class="relative mt-10 min-h-full pb-[calc(theme(spacing.40)+env(safe-area-inset-bottom))]">
            <PullToRefreshIndicator :pull-distance="pullDistance" :is-refreshing="isRefreshing" />

            <div class="relative space-y-4 px-4 pt-4 pb-24">
                <SurfaceCard v-if="!profile">
                    <div class="flex items-center gap-4">
                        <div class="size-20 shrink-0 animate-pulse rounded-full bg-sand-200 dark:bg-sand-700" />
                        <div class="min-w-0 flex-1 space-y-2">
                            <div class="h-5 w-2/3 animate-pulse rounded bg-sand-200 dark:bg-sand-700" />
                            <div class="h-4 w-1/2 animate-pulse rounded bg-sand-200/70 dark:bg-sand-700/70" />
                        </div>
                    </div>
                    <div class="mt-5 space-y-2">
                        <div class="h-4 w-3/4 animate-pulse rounded bg-sand-200/70 dark:bg-sand-700/70" />
                        <div class="h-4 w-1/2 animate-pulse rounded bg-sand-200/70 dark:bg-sand-700/70" />
                    </div>
                </SurfaceCard>

                <SurfaceCard v-else>
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
                        <RouterLink :to="{ name: 'spa.profiles.show', params: { username: profile.username } }" class="min-w-0 flex-1">
                            <h2 class="truncate font-sans text-xl font-bold text-teal">{{ profile.name }}</h2>
                            <p class="truncate text-base text-sand-600 dark:text-sand-300">@{{ profile.username }}</p>
                        </RouterLink>
                    </div>

                    <div class="mt-5">
                        <template v-if="isEditingBio">
                            <form class="space-y-3" @submit.prevent="saveBio">
                                <textarea
                                    v-model="bioForm.data.bio"
                                    :placeholder="t('Write something about yourself...')"
                                    maxlength="150"
                                    rows="3"
                                    class="field-area text-base"
                                />
                                <div class="flex items-center justify-between gap-3">
                                    <span class="text-sm text-sand-500 dark:text-sand-400">{{ bioForm.data.bio.length }}/150</span>
                                    <div class="flex gap-2">
                                        <Button type="button" variant="ghost" size="md" @click="cancelEditBio">
                                            {{ t('Cancel') }}
                                        </Button>
                                        <Button type="submit" size="md" :disabled="bioForm.processing">
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
                        <button
                            v-if="profile.avatar"
                            type="button"
                            class="mt-2 ml-2 text-xs text-sand-500 hover:text-blush-500 dark:text-sand-400"
                            @click="deleteAvatar"
                        >
                            {{ t('Remove photo') }}
                        </button>
                    </div>
                </SurfaceCard>

                <div class="flex items-center justify-between gap-3 px-2">
                    <span class="flex items-center gap-2 text-xs font-medium text-sand-500 dark:text-sand-400">
                        <span aria-hidden="true" class="inline-block size-3.5 bg-current" :style="languageIconStyle"></span>
                        {{ t('Language') }}
                    </span>
                    <div class="flex items-center gap-1 rounded-full bg-sand-100/70 p-0.5 text-xs font-medium dark:bg-sand-800/60">
                        <button
                            class="rounded-full px-3 py-1 transition"
                            :class="currentLocale === 'nl' ? 'bg-white text-teal shadow-sm dark:bg-sand-900 dark:text-sand-100' : 'text-sand-500 dark:text-sand-400'"
                            @click="setLocale('nl')"
                        >
                            NL
                        </button>
                        <button
                            class="rounded-full px-3 py-1 transition"
                            :class="currentLocale === 'en' ? 'bg-white text-teal shadow-sm dark:bg-sand-900 dark:text-sand-100' : 'text-sand-500 dark:text-sand-400'"
                            @click="setLocale('en')"
                        >
                            EN
                        </button>
                    </div>
                </div>

                <RouterLink :to="{ name: 'spa.settings.default-circles' }" class="block">
                    <SurfaceCard>
                        <div class="flex items-center justify-between gap-3">
                            <span class="flex items-center gap-3 text-sm font-semibold text-sand-900 dark:text-sand-100">
                                <IconTile :icon="usersIcon" size="sm" tone="sage" />
                                {{ t('Default circles') }}
                            </span>
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-5 text-sand-500 dark:text-sand-400">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                            </svg>
                        </div>
                    </SurfaceCard>
                </RouterLink>

                <RouterLink :to="{ name: 'spa.settings.persons' }" class="block">
                    <SurfaceCard>
                        <div class="flex items-center justify-between gap-3">
                            <span class="flex items-center gap-3 text-sm font-semibold text-sand-900 dark:text-sand-100">
                                <IconTile :icon="usersIcon" size="sm" tone="sage" />
                                {{ t('Persons') }}
                            </span>
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-5 text-sand-500 dark:text-sand-400">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                            </svg>
                        </div>
                    </SurfaceCard>
                </RouterLink>

                <RouterLink :to="{ name: 'spa.settings.tags' }" class="block">
                    <SurfaceCard>
                        <div class="flex items-center justify-between gap-3">
                            <span class="flex items-center gap-3 text-sm font-semibold text-sand-900 dark:text-sand-100">
                                <IconTile :icon="tagIcon" size="sm" tone="sage" />
                                {{ t('Tags') }}
                            </span>
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-5 text-sand-500 dark:text-sand-400">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                            </svg>
                        </div>
                    </SurfaceCard>
                </RouterLink>

                <RouterLink :to="{ name: 'spa.settings.notifications' }" class="block">
                    <SurfaceCard>
                        <div class="flex items-center justify-between gap-3">
                            <span class="flex items-center gap-3 text-sm font-semibold text-sand-900 dark:text-sand-100">
                                <IconTile :icon="bellIcon" size="sm" tone="sage" />
                                {{ t('Push notifications') }}
                            </span>
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-5 text-sand-500 dark:text-sand-400">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                            </svg>
                        </div>
                    </SurfaceCard>
                </RouterLink>

                <RouterLink :to="{ name: 'spa.settings.account' }" class="block">
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
                </RouterLink>

                <Button variant="danger" size="lg" block @click="logout">
                    {{ t('Log out') }}
                </Button>
            </div>
        </div>
    </AppLayout>
</template>
