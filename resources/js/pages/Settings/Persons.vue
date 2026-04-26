<script setup lang="ts">
import { Deferred, router, useForm } from '@inertiajs/vue3';
import { Camera, Dialog, Events, Off, On } from '@nativephp/mobile';
import { computed, onMounted, onUnmounted, ref, useTemplateRef, watch } from 'vue';
import BottomSheet from '@/components/BottomSheet.vue';
import Button from '@/components/Button.vue';
import IconTile from '@/components/IconTile.vue';
import PullToRefreshIndicator from '@/components/PullToRefreshIndicator.vue';
import SurfaceCard from '@/components/SurfaceCard.vue';
import { usePullToRefresh } from '@/composables/usePullToRefresh';
import { useTranslations } from '@/composables/useTranslations';
import AppLayout from '@/layouts/AppLayout.vue';
import cakeIcon from '../../../svg/doodle-icons/cake.svg';
import cameraIcon from '../../../svg/doodle-icons/camera.svg';
import userIcon from '../../../svg/doodle-icons/user.svg';

interface Person {
    id: number;
    type: string;
    name: string;
    birthdate: string | null;
    avatar: string | null;
    avatar_thumbnail: string | null;
    usage_count: number;
}

const props = defineProps<{
    persons?: Person[];
}>();

const { t } = useTranslations();

function goBack() {
    router.visit('/settings');
}

const layoutRef = useTemplateRef<InstanceType<typeof AppLayout>>('layout');
const containerRef = computed(() => layoutRef.value?.mainRef ?? null);

const { pullDistance, isRefreshing } = usePullToRefresh({
    onRefresh: () =>
        new Promise<void>((resolve) => {
            router.reload({
                only: ['persons'],
                onFinish: () => resolve(),
            });
        }),
    containerRef,
});

const editingPersonId = ref<number | null>(null);
const editingPerson = computed<Person | null>(() => {
    if (editingPersonId.value === null) {
        return null;
    }

    return (props.persons ?? []).find((p) => p.id === editingPersonId.value) ?? null;
});
const sheetOpen = ref(false);
const photoUploading = ref(false);
const pendingPhotoPath = ref<string | null>(null);
const pendingPhotoPreview = ref<string | null>(null);

const editForm = useForm({
    name: '',
    birthdate: '' as string | null,
});

const createError = ref<string | null>(null);
const isCreating = ref(false);

function getCookie(name: string): string | null {
    const match = document.cookie.match(new RegExp('(^|;\\s*)(' + name + ')=([^;]*)'));

    return match ? decodeURIComponent(match[3]) : null;
}

function openCreate() {
    editingPersonId.value = null;
    pendingPhotoPath.value = null;
    pendingPhotoPreview.value = null;
    createError.value = null;
    editForm.clearErrors();
    editForm.defaults({ name: '', birthdate: '' });
    editForm.reset();
    sheetOpen.value = true;
}

function openEdit(person: Person) {
    editingPersonId.value = person.id;
    pendingPhotoPath.value = null;
    pendingPhotoPreview.value = null;
    createError.value = null;
    editForm.clearErrors();
    editForm.defaults({
        name: person.name,
        birthdate: person.birthdate ?? '',
    });
    editForm.reset();
    sheetOpen.value = true;
}

function closeSheet() {
    sheetOpen.value = false;
}

async function submit() {
    if (editingPerson.value === null) {
        await createPerson();

        return;
    }

    editForm
        .transform((data) => ({
            name: data.name.trim(),
            birthdate: data.birthdate === '' ? null : data.birthdate,
        }))
        .put(`/persons/${editingPerson.value.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                sheetOpen.value = false;
                router.reload({ only: ['persons'] });
            },
        });
}

async function createPerson() {
    if (!editForm.name.trim() || isCreating.value) {
        return;
    }

    isCreating.value = true;
    createError.value = null;
    editForm.clearErrors();

    const xsrf = getCookie('XSRF-TOKEN');

    try {
        const response = await fetch('/persons', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                ...(xsrf ? { 'X-XSRF-TOKEN': xsrf } : {}),
            },
            body: JSON.stringify({
                name: editForm.name.trim(),
                birthdate: editForm.birthdate === '' ? null : editForm.birthdate,
            }),
        });

        if (!response.ok) {
            const body = await response.json().catch(() => null);
            const errors = body?.errors ?? {};

            editForm.setError({
                name: errors.name?.[0] ?? '',
                birthdate: errors.birthdate?.[0] ?? '',
            });

            if (!errors.name && !errors.birthdate) {
                createError.value = body?.message ?? t('Failed to add person');
            }

            return;
        }

        const person = (await response.json()) as Person;

        if (pendingPhotoPath.value) {
            photoUploading.value = true;

            try {
                await uploadPendingPhoto(person.id);
            } finally {
                photoUploading.value = false;
            }

            pendingPhotoPath.value = null;
            pendingPhotoPreview.value = null;
        }

        editingPersonId.value = person.id;
        sheetOpen.value = false;
        router.reload({ only: ['persons'] });
    } catch {
        createError.value = t('Failed to add person');
    } finally {
        isCreating.value = false;
    }
}

let pendingDeletePersonId: number | null = null;

async function confirmDelete(person: Person) {
    pendingDeletePersonId = person.id;

    const message =
        person.usage_count > 0
            ? t('":name" is linked to :count posts. Removing this person also removes the link from those posts.', {
                  name: person.name,
                  count: person.usage_count,
              })
            : t('Are you sure you want to remove ":name"?', { name: person.name });

    await Dialog.alert().confirm(t('Remove person'), message).id('delete-person-confirm');
}

function handleButtonPressed(payload: { index: number; id?: string | null }) {
    if (payload.id !== 'delete-person-confirm' || payload.index !== 1 || pendingDeletePersonId === null) {
        return;
    }

    const personId = pendingDeletePersonId;
    pendingDeletePersonId = null;

    router.delete(`/persons/${personId}`, {
        preserveScroll: true,
        onSuccess: () => {
            if (editingPersonId.value === personId) {
                sheetOpen.value = false;
            }

            router.reload({ only: ['persons'] });
        },
    });
}

async function pickPhoto() {
    await Camera.pickImages().all();
}

function deletePhoto() {
    pendingPhotoPath.value = null;
    pendingPhotoPreview.value = null;

    if (editingPerson.value === null) {
        return;
    }

    router.delete(`/persons/${editingPerson.value.id}/photo`, {
        preserveScroll: true,
        onSuccess: () => router.reload({ only: ['persons'] }),
    });
}

async function loadPreview(path: string): Promise<string | null> {
    try {
        const response = await fetch(`/native-media?path=${encodeURIComponent(path)}`);

        if (!response.ok) {
            return null;
        }

        const { data_url } = await response.json();

        return data_url;
    } catch {
        return null;
    }
}

async function handleMediaSelected(payload: { success: boolean; files: { path: string; mimeType: string }[]; cancelled: boolean }) {
    if (!payload.success || payload.cancelled || !payload.files.length) {
        return;
    }

    const path = payload.files[0].path;

    if (editingPerson.value === null) {
        pendingPhotoPath.value = path;
        pendingPhotoPreview.value = await loadPreview(path);

        return;
    }

    pendingPhotoPath.value = path;
    pendingPhotoPreview.value = await loadPreview(path);
    photoUploading.value = true;

    router.post(
        `/persons/${editingPerson.value.id}/photo`,
        { photo_path: path },
        {
            preserveScroll: true,
            onFinish: () => {
                photoUploading.value = false;
            },
            onSuccess: () => {
                router.reload({
                    only: ['persons'],
                    onSuccess: () => {
                        pendingPhotoPath.value = null;
                        pendingPhotoPreview.value = null;
                    },
                });
            },
        },
    );
}

async function uploadPendingPhoto(personId: number) {
    if (!pendingPhotoPath.value) {
        return;
    }

    const xsrf = getCookie('XSRF-TOKEN');

    await fetch(`/persons/${personId}/photo`, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            ...(xsrf ? { 'X-XSRF-TOKEN': xsrf } : {}),
        },
        body: JSON.stringify({ photo_path: pendingPhotoPath.value }),
    });
}

function formatBirthdate(date: string | null): string {
    if (!date) {
        return '';
    }

    const parsed = new Date(date);

    if (Number.isNaN(parsed.getTime())) {
        return date;
    }

    return parsed.toLocaleDateString(undefined, { year: 'numeric', month: 'long', day: 'numeric' });
}

const todayIso = new Date().toISOString().slice(0, 10);

watch(sheetOpen, (open) => {
    if (!open) {
        editingPersonId.value = null;
        pendingPhotoPath.value = null;
        pendingPhotoPreview.value = null;
        createError.value = null;
    }
});

onMounted(() => {
    On(Events.Alert.ButtonPressed, handleButtonPressed);
    On(Events.Gallery.MediaSelected, handleMediaSelected);
});

onUnmounted(() => {
    Off(Events.Alert.ButtonPressed, handleButtonPressed);
    Off(Events.Gallery.MediaSelected, handleMediaSelected);
});

function iconMaskStyle(url: string) {
    return {
        maskImage: `url(${url})`,
        WebkitMaskImage: `url(${url})`,
        maskSize: 'contain',
        WebkitMaskSize: 'contain',
        maskRepeat: 'no-repeat',
        WebkitMaskRepeat: 'no-repeat',
        maskPosition: 'center',
        WebkitMaskPosition: 'center',
    };
}
</script>

<template>
    <AppLayout ref="layout" :title="t('Persons')">
        <template #header-left>
            <button class="flex items-center text-teal dark:text-sand-300" @click="goBack">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                </svg>
            </button>
        </template>
        <template #header-right>
            <button
                class="flex size-9 items-center justify-center rounded-full bg-teal text-white shadow-sm transition hover:bg-teal-light"
                :aria-label="t('Add person')"
                @click="openCreate"
            >
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
            </button>
        </template>

        <div class="relative mt-10 min-h-full pb-[calc(theme(spacing.40)+env(safe-area-inset-bottom))]">
            <PullToRefreshIndicator :pull-distance="pullDistance" :is-refreshing="isRefreshing" />

            <div class="relative space-y-4 px-4 pt-4 pb-24">
                <Deferred data="persons">
                    <template #fallback>
                        <div />
                    </template>

                    <ul v-if="persons && persons.length > 0" class="space-y-2">
                        <li v-for="person in persons" :key="person.id">
                            <SurfaceCard>
                                <div class="flex items-center gap-3">
                                    <button class="shrink-0" :aria-label="t('Edit person')" @click="openEdit(person)">
                                        <img
                                            v-if="person.avatar_thumbnail"
                                            :src="person.avatar_thumbnail"
                                            :alt="person.name"
                                            class="size-12 rounded-full object-cover"
                                        />
                                        <span
                                            v-else
                                            class="flex size-12 items-center justify-center rounded-full bg-sage-100 text-teal dark:bg-sage-900/40"
                                        >
                                            <span aria-hidden="true" class="inline-block size-7 bg-current" :style="iconMaskStyle(userIcon)"></span>
                                        </span>
                                    </button>
                                    <button class="min-w-0 flex-1 text-left" @click="openEdit(person)">
                                        <p class="truncate text-base font-semibold text-sand-900 dark:text-sand-100">{{ person.name }}</p>
                                        <p v-if="person.birthdate" class="flex items-center gap-1.5 text-xs text-sand-500 dark:text-sand-400">
                                            <span aria-hidden="true" class="inline-block size-3.5 bg-current" :style="iconMaskStyle(cakeIcon)"></span>
                                            {{ formatBirthdate(person.birthdate) }}
                                        </p>
                                        <p v-else class="text-xs text-sand-500 dark:text-sand-400">
                                            {{ person.usage_count === 1 ? t(':count post', { count: person.usage_count }) : t(':count posts', { count: person.usage_count }) }}
                                        </p>
                                    </button>
                                    <button
                                        class="flex size-9 items-center justify-center rounded-lg text-sand-500 transition hover:bg-blush-50 hover:text-blush-500 dark:text-sand-400 dark:hover:bg-blush-900/30"
                                        :aria-label="t('Remove person')"
                                        @click="confirmDelete(person)"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-4">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                        </svg>
                                    </button>
                                </div>
                            </SurfaceCard>
                        </li>
                    </ul>

                    <SurfaceCard v-else-if="persons && persons.length === 0">
                        <div class="flex flex-col items-center px-2 py-4 text-center">
                            <IconTile :icon="userIcon" size="lg" tone="sage" class="mb-4" />
                            <h3 class="font-sans text-lg font-semibold text-teal dark:text-sand-100">{{ t('No persons yet') }}</h3>
                            <p class="mt-1 text-sm text-sand-600 dark:text-sand-400">
                                {{ t('Add the people closest to you to tag them in your posts.') }}
                            </p>
                            <div class="mt-5">
                                <Button size="md" @click="openCreate">
                                    {{ t('Add your first person') }}
                                </Button>
                            </div>
                        </div>
                    </SurfaceCard>
                </Deferred>
            </div>
        </div>

        <BottomSheet :open="sheetOpen" @update:open="(value) => (sheetOpen = value)">
            <template #header>
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-sand-700 dark:text-sand-300">
                        {{ editingPerson ? t('Edit person') : t('Add person') }}
                    </h2>
                    <button class="text-sand-500 dark:text-sand-400" :aria-label="t('Close')" @click="closeSheet">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </template>

            <div class="space-y-5 px-4 py-5">
                <div class="flex flex-col items-center gap-3">
                    <button
                        class="relative"
                        :aria-label="t('Change photo')"
                        :disabled="photoUploading"
                        @click="pickPhoto"
                    >
                        <img
                            v-if="pendingPhotoPreview ?? editingPerson?.avatar"
                            :src="pendingPhotoPreview ?? editingPerson?.avatar ?? ''"
                            :alt="editingPerson?.name ?? ''"
                            class="size-24 rounded-full object-cover shadow-sm"
                            :class="photoUploading ? 'opacity-50' : ''"
                        />
                        <span
                            v-else
                            class="flex size-24 items-center justify-center rounded-full bg-sage-100 text-teal dark:bg-sage-900/40"
                            :class="photoUploading ? 'opacity-50' : ''"
                        >
                            <span aria-hidden="true" class="inline-block size-12 bg-current" :style="iconMaskStyle(userIcon)"></span>
                        </span>
                        <span class="absolute -bottom-1 -right-1 flex size-8 items-center justify-center rounded-full bg-teal shadow-md ring-4 ring-white/70 dark:ring-sand-900">
                            <span aria-hidden="true" class="inline-block size-4 bg-white" :style="iconMaskStyle(cameraIcon)"></span>
                        </span>
                    </button>
                    <button
                        v-if="pendingPhotoPreview || editingPerson?.avatar"
                        type="button"
                        class="text-xs font-medium text-sand-500 hover:text-blush-500 dark:text-sand-400"
                        @click="deletePhoto"
                    >
                        {{ t('Remove photo') }}
                    </button>
                </div>

                <form class="space-y-4" @submit.prevent="submit">
                    <div>
                        <label for="person-name" class="text-xs font-medium uppercase tracking-wider text-sand-500 dark:text-sand-400">
                            {{ t('Name') }}
                        </label>
                        <input
                            id="person-name"
                            v-model="editForm.name"
                            type="text"
                            class="field mt-2 box-border min-w-0"
                            maxlength="50"
                            autofocus
                        />
                        <p v-if="editForm.errors.name" class="mt-1 text-xs text-blush-500">{{ editForm.errors.name }}</p>
                    </div>

                    <div>
                        <label for="person-birthdate" class="text-xs font-medium uppercase tracking-wider text-sand-500 dark:text-sand-400">
                            {{ t('Birthdate') }}
                        </label>
                        <input
                            id="person-birthdate"
                            v-model="editForm.birthdate"
                            type="date"
                            :max="todayIso"
                            min="1900-01-02"
                            class="field mt-2 box-border block w-full min-w-0 appearance-none"
                        />
                        <p v-if="editForm.errors.birthdate" class="mt-1 text-xs text-blush-500">{{ editForm.errors.birthdate }}</p>
                    </div>

                    <p v-if="createError" class="text-xs text-blush-500">{{ createError }}</p>
                </form>
            </div>

            <template #footer>
                <div class="flex items-center justify-between gap-3 px-4 py-3">
                    <Button
                        v-if="editingPerson"
                        type="button"
                        variant="danger"
                        size="md"
                        @click="confirmDelete(editingPerson)"
                    >
                        {{ t('Remove') }}
                    </Button>
                    <span v-else />
                    <div class="flex gap-2">
                        <Button type="button" variant="ghost" size="md" @click="closeSheet">
                            {{ t('Cancel') }}
                        </Button>
                        <Button
                            type="button"
                            size="md"
                            :disabled="editForm.processing || isCreating || !editForm.name.trim()"
                            @click="submit"
                        >
                            {{ editingPerson ? t('Save') : t('Add') }}
                        </Button>
                    </div>
                </div>
            </template>
        </BottomSheet>
    </AppLayout>
</template>
