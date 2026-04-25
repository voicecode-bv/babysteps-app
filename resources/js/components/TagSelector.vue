<script setup lang="ts">
import { store as storeTag } from '@/actions/App/Http/Controllers/TagActionController';
import { useTranslations } from '@/composables/useTranslations';
import { computed, ref } from 'vue';

interface Tag {
    id: number;
    name: string;
    usage_count?: number;
}

const props = withDefaults(
    defineProps<{
        availableTags?: Tag[] | null;
        selectedIds: number[];
        error?: string | null;
    }>(),
    {
        availableTags: () => [],
        error: null,
    },
);

const emit = defineEmits<{
    (e: 'update:selectedIds', value: number[]): void;
    (e: 'tag-created', value: Tag): void;
}>();

const { t } = useTranslations();

const newTagName = ref('');
const isCreating = ref(false);
const createError = ref<string | null>(null);
const localTags = ref<Tag[]>([]);

const tags = computed<Tag[]>(() => {
    const seen = new Set<number>();
    const merged: Tag[] = [];

    for (const tag of [...(props.availableTags ?? []), ...localTags.value]) {
        if (seen.has(tag.id)) continue;
        seen.add(tag.id);
        merged.push(tag);
    }

    return merged.sort((a, b) => (b.usage_count ?? 0) - (a.usage_count ?? 0) || a.name.localeCompare(b.name));
});

const trimmedNewTag = computed(() => newTagName.value.trim());

const canCreate = computed(() => {
    if (trimmedNewTag.value === '' || isCreating.value) return false;

    const lower = trimmedNewTag.value.toLowerCase();

    return !tags.value.some((tag) => tag.name.toLowerCase() === lower);
});

function toggleTag(tagId: number) {
    const next = [...props.selectedIds];
    const index = next.indexOf(tagId);

    if (index === -1) {
        next.push(tagId);
    } else {
        next.splice(index, 1);
    }

    emit('update:selectedIds', next);
}

function getCookie(name: string): string | null {
    const match = document.cookie.match(new RegExp('(^|;\\s*)(' + name + ')=([^;]*)'));

    return match ? decodeURIComponent(match[3]) : null;
}

async function createTag() {
    if (!canCreate.value) return;

    isCreating.value = true;
    createError.value = null;

    const xsrf = getCookie('XSRF-TOKEN');

    try {
        const response = await fetch(storeTag.url(), {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                ...(xsrf ? { 'X-XSRF-TOKEN': xsrf } : {}),
            },
            body: JSON.stringify({ name: trimmedNewTag.value }),
        });

        if (!response.ok) {
            const body = await response.json().catch(() => null);
            createError.value = body?.errors?.name?.[0] ?? body?.message ?? t('Failed to create tag');

            return;
        }

        const tag = (await response.json()) as Tag;
        localTags.value.push(tag);
        emit('tag-created', tag);
        emit('update:selectedIds', [...props.selectedIds, tag.id]);
        newTagName.value = '';
    } catch {
        createError.value = t('Failed to create tag');
    } finally {
        isCreating.value = false;
    }
}
</script>

<template>
    <div>
        <p class="mb-3 text-xs font-medium uppercase tracking-wider text-sand-500 dark:text-sand-400">
            {{ t('Tags') }}
        </p>

        <div v-if="tags.length > 0" class="mb-3 flex flex-wrap gap-2">
            <button
                v-for="tag in tags"
                :key="tag.id"
                type="button"
                class="rounded-full px-4 py-2 text-sm font-medium transition-colors"
                :class="selectedIds.includes(tag.id)
                    ? 'bg-teal text-white shadow-sm'
                    : 'bg-sand-100 text-sand-700 dark:bg-sand-800 dark:text-sand-200'"
                @click="toggleTag(tag.id)"
            >
                #{{ tag.name }}
            </button>
        </div>

        <p v-else class="mb-3 text-xs text-sand-500 dark:text-sand-400">
            {{ t('No tags yet. Create your first one below.') }}
        </p>

        <div class="flex items-center gap-2">
            <input
                v-model="newTagName"
                type="text"
                maxlength="50"
                :placeholder="t('Add a tag')"
                class="flex-1 rounded-full border-0 bg-sand-100 px-4 py-2 text-sm text-sand-800 placeholder-sand-400 shadow-sm focus:outline-none focus:ring-2 focus:ring-teal dark:bg-sand-800 dark:text-sand-100 dark:placeholder-sand-500"
                @keydown.enter.prevent="createTag"
            />
            <button
                type="button"
                :disabled="!canCreate"
                class="rounded-full bg-teal px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-teal-light disabled:opacity-40"
                @click="createTag"
            >
                {{ isCreating ? t('Adding...') : t('Add') }}
            </button>
        </div>

        <p v-if="createError" class="mt-2 text-xs text-blush-500">{{ createError }}</p>
        <p v-if="error" class="mt-2 text-xs text-blush-500">{{ error }}</p>
    </div>
</template>
