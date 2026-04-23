<script setup lang="ts">
import BottomSheet from '@/components/BottomSheet.vue';
import { useTranslations } from '@/composables/useTranslations';
import { fetchPostLikes, type LikeUser } from '@/http/likes';
import { Link } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps<{
    open: boolean;
    postId: number;
    initialCount?: number;
}>();

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
}>();

const { t } = useTranslations();

const users = ref<LikeUser[]>([]);
const isLoading = ref(false);
const isLoadingMore = ref(false);
const loadError = ref<string | null>(null);
const hasLoaded = ref(false);
const total = ref(props.initialCount ?? 0);
const currentPage = ref(0);
const lastPage = ref(1);

async function loadPage(page: number) {
    const isFirst = page === 1;

    if (isFirst) {
        isLoading.value = true;
    } else {
        isLoadingMore.value = true;
    }
    loadError.value = null;

    try {
        const result = await fetchPostLikes(props.postId, page);
        users.value = isFirst ? result.data : [...users.value, ...result.data];
        currentPage.value = result.meta.current_page;
        lastPage.value = result.meta.last_page;
        total.value = result.meta.total;
        hasLoaded.value = true;
    } catch {
        loadError.value = t('Failed to load likes');
    } finally {
        isLoading.value = false;
        isLoadingMore.value = false;
    }
}

watch(
    () => props.open,
    (isOpen) => {
        if (!isOpen) return;
        if (!hasLoaded.value) {
            void loadPage(1);
        }
    },
);

watch(
    () => props.postId,
    () => {
        users.value = [];
        hasLoaded.value = false;
        currentPage.value = 0;
        lastPage.value = 1;
    },
);

function close() {
    emit('update:open', false);
}

function onSheetUpdate(value: boolean) {
    if (!value) {
        close();
    } else {
        emit('update:open', true);
    }
}

function loadMore() {
    if (isLoadingMore.value || currentPage.value >= lastPage.value) return;
    void loadPage(currentPage.value + 1);
}
</script>

<template>
    <BottomSheet :open="open" @update:open="onSheetUpdate">
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-semibold text-sand-700 dark:text-sand-300">
                    {{ t('Likes') }}
                    <span v-if="total > 0" class="font-normal text-sand-400 dark:text-sand-500">({{ total }})</span>
                </h2>
                <button class="text-sand-500 dark:text-sand-400" :aria-label="t('Close')" @click="close">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </template>

        <div v-if="isLoading" class="flex items-center justify-center px-4 py-10 pb-24">
            <svg class="size-6 animate-spin text-sand-400 dark:text-sand-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
            </svg>
        </div>

        <div v-else-if="loadError" class="px-4 py-10 pb-24 text-center">
            <p class="text-sm text-blush-500">{{ loadError }}</p>
            <button class="mt-2 text-xs font-medium text-sand-500 dark:text-sand-400" @click="loadPage(1)">{{ t('Try again') }}</button>
        </div>

        <div v-else-if="users.length === 0" class="px-4 py-10 pb-24 text-center">
            <p class="text-sm font-medium text-sand-600 dark:text-sand-300">{{ t('No likes yet') }}</p>
        </div>

        <div v-else class="pb-24">
            <Link
                v-for="user in users"
                :key="user.id"
                :href="`/profiles/${user.username}`"
                class="flex items-center gap-3 border-b border-sand-50 px-4 py-3 dark:border-sand-800"
            >
                <img
                    :src="user.avatar ?? `https://ui-avatars.com/api/?name=${user.name}&background=f0dcc6&color=5c3f24&size=64`"
                    :alt="user.name"
                    class="size-10 rounded-full object-cover"
                />
                <div class="flex-1 min-w-0">
                    <p class="truncate text-sm font-semibold text-sand-800 dark:text-sand-100">{{ user.name }}</p>
                    <p class="truncate text-xs text-sand-500 dark:text-sand-400">@{{ user.username }}</p>
                </div>
            </Link>

            <div v-if="currentPage < lastPage" class="flex justify-center px-4 py-4">
                <button
                    class="text-sm font-medium text-sand-500 disabled:opacity-50 dark:text-sand-400"
                    :disabled="isLoadingMore"
                    @click="loadMore"
                >
                    {{ isLoadingMore ? t('Loading more...') : t('Load more') }}
                </button>
            </div>
        </div>
    </BottomSheet>
</template>
