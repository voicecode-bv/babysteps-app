<script setup lang="ts">
import { computed, defineAsyncComponent, watch } from 'vue';
import BottomSheet from '@/components/BottomSheet.vue';
import CirclePicker from '@/components/CirclePicker.vue';
import PersonPicker from '@/components/PersonPicker.vue';

const TagSelector = defineAsyncComponent(() => import('@/spa/components/TagSelector.vue'));
import { useTranslations } from '@/spa/composables/useTranslations';
import { useApiForm } from '@/spa/composables/useApiForm';
import { externalApi } from '@/spa/http/externalApi';

interface Circle {
    id: number;
    name: string;
    photo?: string | null;
    members_count?: number;
    members_can_invite?: boolean;
    is_owner?: boolean;
}

interface Tag {
    id: number;
    name: string;
    usage_count?: number;
}

interface Person {
    id: number;
    name: string;
    avatar?: string | null;
    avatar_thumbnail?: string | null;
    user_id?: number | null;
}

const props = withDefaults(
    defineProps<{
        open: boolean;
        postId: number;
        caption: string | null;
        circles: Circle[];
        availableCircles?: Circle[] | null;
        tags?: Tag[] | null;
        persons?: Person[] | null;
        availableTags?: Tag[] | null;
        availablePersons?: Person[] | null;
    }>(),
    {
        availableCircles: () => [],
        tags: () => [],
        persons: () => [],
        availableTags: () => [],
        availablePersons: () => [],
    },
);

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
    (e: 'updated'): void;
}>();

const { t } = useTranslations();

const initialTagIds = (props.tags ?? []).map((tag) => tag.id);
const initialPersonIds = (props.persons ?? []).map((person) => person.id);

const form = useApiForm({
    caption: props.caption ?? '',
    circle_ids: props.circles.map((c) => c.id),
    tag_ids: initialTagIds,
    person_ids: initialPersonIds,
}, externalApi);

const availableCircles = computed<Circle[]>(() => props.availableCircles ?? []);
const availableTags = computed<Tag[]>(() => props.availableTags ?? []);
const availablePersons = computed<Person[]>(() => props.availablePersons ?? []);

function sameIds(a: number[], b: number[]): boolean {
    if (a.length !== b.length) return false;
    const sortedA = [...a].sort();
    const sortedB = [...b].sort();
    return sortedA.every((id, i) => id === sortedB[i]);
}

const hasChanges = computed(() => {
    if ((form.data.caption ?? '') !== (props.caption ?? '')) return true;
    if (!sameIds(form.data.circle_ids, props.circles.map((c) => c.id))) return true;
    if (!sameIds(form.data.tag_ids, initialTagIds)) return true;
    if (!sameIds(form.data.person_ids, initialPersonIds)) return true;
    return false;
});

const canSave = computed(() => form.data.circle_ids.length > 0 && hasChanges.value && !form.processing);

watch(
    () => props.open,
    (isOpen) => {
        if (!isOpen) return;

        form.errors = {};
        form.data.caption = props.caption ?? '';
        form.data.circle_ids = props.circles.map((c) => c.id);
        form.data.tag_ids = (props.tags ?? []).map((tag) => tag.id);
        form.data.person_ids = (props.persons ?? []).map((person) => person.id);
    },
);

function close(): void {
    emit('update:open', false);
}

function onSheetUpdate(value: boolean): void {
    if (!value) {
        close();
    } else {
        emit('update:open', true);
    }
}

async function submit(): Promise<void> {
    const payload = {
        caption: form.data.caption,
        circle_ids: form.data.circle_ids,
        tag_ids: form.data.tag_ids,
        person_ids: form.data.person_ids,
    };

    form.processing = true;
    form.errors = {};

    try {
        await externalApi.put(`/posts/${props.postId}`, payload);
        emit('updated');
        close();
    } catch (error) {
        const apiError = error as { status?: number; errors?: Record<string, string[]>; message?: string };
        if (apiError.status === 422) {
            form.errors = Object.fromEntries(
                Object.entries(apiError.errors ?? {}).map(([k, v]) => [k, v[0] ?? '']),
            );
        }
    } finally {
        form.processing = false;
    }
}
</script>

<template>
    <BottomSheet :open="open" @update:open="onSheetUpdate">
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-semibold text-sand-700 dark:text-sand-300">{{ t('Edit post') }}</h2>
                <button class="text-sand-500 dark:text-sand-400" :aria-label="t('Close')" @click="close">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </template>

        <div class="space-y-5 px-4 py-4">
            <section>
                <label for="edit-post-caption" class="text-xs font-medium uppercase tracking-wider text-sand-500 dark:text-sand-400">
                    {{ t('Caption') }}
                </label>
                <textarea
                    id="edit-post-caption"
                    v-model="form.data.caption"
                    :placeholder="t('Write a caption...')"
                    rows="4"
                    maxlength="2200"
                    class="mt-2 w-full resize-none border-0 bg-transparent p-0 text-base text-sand-800 placeholder-sand-400 focus:outline-none focus:ring-0 dark:text-sand-100 dark:placeholder-sand-500"
                />
                <p v-if="form.errors.caption" class="mt-1 text-xs text-blush-500">{{ form.errors.caption }}</p>
            </section>

            <section v-if="availableCircles.length > 0">
                <CirclePicker
                    :circles="availableCircles"
                    :selected-ids="form.data.circle_ids"
                    :error="form.errors.circle_ids"
                    @update:selected-ids="form.data.circle_ids = $event"
                />
            </section>

            <section v-if="availablePersons.length > 0">
                <PersonPicker
                    :persons="availablePersons"
                    :selected-ids="form.data.person_ids"
                    :error="form.errors.person_ids"
                    @update:selected-ids="form.data.person_ids = $event"
                />
            </section>

            <section class="relative z-20">
                <TagSelector
                    :available-tags="availableTags"
                    :selected-ids="form.data.tag_ids"
                    :error="form.errors.tag_ids"
                    @update:selected-ids="form.data.tag_ids = $event"
                />
            </section>
        </div>

        <template #footer>
            <div class="px-4 py-3">
                <button
                    :disabled="!canSave"
                    class="flex w-full items-center justify-center gap-2 rounded-full bg-teal py-3 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-teal-light disabled:opacity-40"
                    @click="submit"
                >
                    <svg v-if="form.processing" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" class="size-4 animate-spin">
                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-opacity="0.25" />
                        <path d="M22 12a10 10 0 0 1-10 10" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
                    </svg>
                    {{ form.processing ? t('Saving...') : t('Save') }}
                </button>
            </div>
        </template>
    </BottomSheet>
</template>
