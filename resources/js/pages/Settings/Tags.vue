<script setup lang="ts">
import Button from '@/components/Button.vue';
import IconTile from '@/components/IconTile.vue';
import PullToRefreshIndicator from '@/components/PullToRefreshIndicator.vue';
import SurfaceCard from '@/components/SurfaceCard.vue';
import { usePullToRefresh } from '@/composables/usePullToRefresh';
import { useTranslations } from '@/composables/useTranslations';
import AppLayout from '@/layouts/AppLayout.vue';
import { Deferred, router, useForm } from '@inertiajs/vue3';
import { Dialog, Events, Off, On } from '@nativephp/mobile';
import { computed, onMounted, onUnmounted, ref, useTemplateRef } from 'vue';
import tagIcon from '../../../svg/doodle-icons/tag.svg';

interface Tag {
    id: number;
    name: string;
    usage_count: number;
}

defineProps<{
    tags?: Tag[];
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
                only: ['tags'],
                onFinish: () => resolve(),
            });
        }),
    containerRef,
});

const showCreateForm = ref(false);
const createForm = useForm({ name: '' });

function createTag() {
    createForm.post('/tags', {
        preserveScroll: true,
        onSuccess: () => {
            createForm.reset();
            showCreateForm.value = false;
            router.reload({ only: ['tags'] });
        },
    });
}

const editingTagId = ref<number | null>(null);
const editForm = useForm({ name: '' });

function startEdit(tag: Tag) {
    editingTagId.value = tag.id;
    editForm.name = tag.name;
    editForm.clearErrors();
}

function cancelEdit() {
    editingTagId.value = null;
    editForm.reset();
    editForm.clearErrors();
}

function saveEdit(tag: Tag) {
    editForm.put(`/tags/${tag.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            editingTagId.value = null;
            router.reload({ only: ['tags'] });
        },
    });
}

let pendingDeleteTagId: number | null = null;

async function confirmDelete(tag: Tag) {
    pendingDeleteTagId = tag.id;

    const message = tag.usage_count > 0
        ? t('":name" is used on :count posts. Deleting it will remove the tag from those posts.', {
              name: tag.name,
              count: tag.usage_count,
          })
        : t('Are you sure you want to delete ":name"?', { name: tag.name });

    await Dialog.alert()
        .confirm(t('Delete tag'), message)
        .id('delete-tag-confirm');
}

function handleButtonPressed(payload: { index: number; id?: string | null }) {
    if (payload.id !== 'delete-tag-confirm' || payload.index !== 1 || pendingDeleteTagId === null) {
        return;
    }

    const tagId = pendingDeleteTagId;
    pendingDeleteTagId = null;

    router.delete(`/tags/${tagId}`, {
        preserveScroll: true,
        onSuccess: () => {
            router.reload({ only: ['tags'] });
        },
    });
}

onMounted(() => On(Events.Alert.ButtonPressed, handleButtonPressed));
onUnmounted(() => Off(Events.Alert.ButtonPressed, handleButtonPressed));
</script>

<template>
    <AppLayout ref="layout" :title="t('Tags')">
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
                :aria-label="t('Add tag')"
                @click="showCreateForm = !showCreateForm"
            >
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-5">
                    <path v-if="!showCreateForm" stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    <path v-else stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                </svg>
            </button>
        </template>

        <div class="relative mt-10 min-h-full pb-[calc(theme(spacing.40)+env(safe-area-inset-bottom))]">
            <PullToRefreshIndicator :pull-distance="pullDistance" :is-refreshing="isRefreshing" />

            <div class="relative space-y-4 px-4 pt-4 pb-24">
                <Transition
                    enter-active-class="transition duration-200 ease-out"
                    enter-from-class="-translate-y-2 opacity-0"
                    enter-to-class="translate-y-0 opacity-100"
                    leave-active-class="transition duration-150 ease-in"
                    leave-from-class="translate-y-0 opacity-100"
                    leave-to-class="-translate-y-2 opacity-0"
                >
                    <SurfaceCard v-if="showCreateForm">
                        <form class="space-y-3" @submit.prevent="createTag">
                            <label class="text-xs font-medium uppercase tracking-wider text-sand-500 dark:text-sand-400">
                                {{ t('New tag') }}
                            </label>
                            <input
                                v-model="createForm.name"
                                type="text"
                                :placeholder="t('Tag name...')"
                                class="field"
                                maxlength="50"
                                autofocus
                            />
                            <p v-if="createForm.errors.name" class="text-xs text-accent">{{ createForm.errors.name }}</p>
                            <div class="flex justify-end gap-2">
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="md"
                                    @click="showCreateForm = false; createForm.reset(); createForm.clearErrors();"
                                >
                                    {{ t('Cancel') }}
                                </Button>
                                <Button
                                    type="submit"
                                    size="md"
                                    :disabled="createForm.processing || !createForm.name.trim()"
                                >
                                    {{ t('Create') }}
                                </Button>
                            </div>
                        </form>
                    </SurfaceCard>
                </Transition>

                <Deferred data="tags">
                    <template #fallback>
                        <div />
                    </template>

                    <ul v-if="tags && tags.length > 0" class="space-y-2">
                        <li v-for="tag in tags" :key="tag.id">
                            <SurfaceCard>
                                <template v-if="editingTagId === tag.id">
                                    <form class="space-y-3" @submit.prevent="saveEdit(tag)">
                                        <input
                                            v-model="editForm.name"
                                            type="text"
                                            class="field"
                                            maxlength="50"
                                            autofocus
                                        />
                                        <p v-if="editForm.errors.name" class="text-xs text-accent">{{ editForm.errors.name }}</p>
                                        <div class="flex justify-end gap-2">
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="md"
                                                @click="cancelEdit"
                                            >
                                                {{ t('Cancel') }}
                                            </Button>
                                            <Button
                                                type="submit"
                                                size="md"
                                                :disabled="editForm.processing || !editForm.name.trim()"
                                            >
                                                {{ t('Save') }}
                                            </Button>
                                        </div>
                                    </form>
                                </template>
                                <div v-else class="flex items-center gap-3">
                                    <IconTile :icon="tagIcon" size="sm" tone="sage" />
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-base font-semibold text-sand-900 dark:text-sand-100">{{ tag.name }}</p>
                                        <p class="text-xs text-sand-500 dark:text-sand-400">
                                            {{ tag.usage_count === 1 ? t(':count post', { count: tag.usage_count }) : t(':count posts', { count: tag.usage_count }) }}
                                        </p>
                                    </div>
                                    <button
                                        class="flex size-9 items-center justify-center rounded-lg text-sand-500 transition hover:bg-sand-100 hover:text-teal dark:text-sand-400 dark:hover:bg-sand-700/60"
                                        :aria-label="t('Edit tag')"
                                        @click="startEdit(tag)"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-4">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" />
                                        </svg>
                                    </button>
                                    <button
                                        class="flex size-9 items-center justify-center rounded-lg text-sand-500 transition hover:bg-blush-50 hover:text-blush-500 dark:text-sand-400 dark:hover:bg-blush-900/30"
                                        :aria-label="t('Delete tag')"
                                        @click="confirmDelete(tag)"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-4">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                        </svg>
                                    </button>
                                </div>
                            </SurfaceCard>
                        </li>
                    </ul>

                    <SurfaceCard v-else-if="tags && tags.length === 0">
                        <div class="flex flex-col items-center px-2 py-4 text-center">
                            <IconTile :icon="tagIcon" size="lg" tone="sage" class="mb-4" />
                            <h3 class="font-sans text-lg font-semibold text-teal dark:text-sand-100">{{ t('No tags yet') }}</h3>
                            <p class="mt-1 text-sm text-sand-600 dark:text-sand-400">
                                {{ t('Create tags to organize and filter your posts.') }}
                            </p>
                            <div class="mt-5">
                                <Button size="md" @click="showCreateForm = true">
                                    {{ t('Create your first tag') }}
                                </Button>
                            </div>
                        </div>
                    </SurfaceCard>
                </Deferred>
            </div>
        </div>
    </AppLayout>
</template>
