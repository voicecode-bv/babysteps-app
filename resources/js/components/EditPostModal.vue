<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import { update } from '@/actions/App/Http/Controllers/PostActionController';
import BottomSheet from '@/components/BottomSheet.vue';
import CirclePicker from '@/components/CirclePicker.vue';
import PersonPicker from '@/components/PersonPicker.vue';
import TagSelector from '@/components/TagSelector.vue';
import { useTranslations } from '@/composables/useTranslations';

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
    type?: string;
    name: string;
    usage_count?: number;
    avatar_thumbnail?: string | null;
    avatar?: string | null;
}

const props = withDefaults(
    defineProps<{
        open: boolean;
        postId: number;
        caption: string | null;
        circles: Circle[];
        availableCircles?: Circle[] | null;
        tags?: Tag[] | null;
        availableTags?: Tag[] | null;
        availablePersons?: Tag[] | null;
    }>(),
    {
        availableCircles: () => [],
        tags: () => [],
        availableTags: () => [],
        availablePersons: () => [],
    },
);

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
}>();

const { t } = useTranslations();

const initialTagIds = (props.tags ?? []).filter((t) => (t.type ?? 'tag') === 'tag').map((t) => t.id);
const initialPersonIds = (props.tags ?? []).filter((t) => t.type === 'person').map((t) => t.id);

const form = useForm({
    caption: props.caption ?? '',
    circle_ids: props.circles.map((c) => c.id),
    tag_ids: initialTagIds,
    person_ids: initialPersonIds,
});

const availableCircles = computed<Circle[]>(() => props.availableCircles ?? []);
const availableTags = computed<Tag[]>(() => props.availableTags ?? []);
const availablePersons = computed<Tag[]>(() => props.availablePersons ?? []);

function sameIds(a: number[], b: number[]): boolean {
    if (a.length !== b.length) {
        return false;
    }

    const sortedA = [...a].sort();
    const sortedB = [...b].sort();

    return sortedA.every((id, i) => id === sortedB[i]);
}

const hasChanges = computed(() => {
    if ((form.caption ?? '') !== (props.caption ?? '')) {
        return true;
    }

    if (!sameIds(form.circle_ids, props.circles.map((c) => c.id))) {
        return true;
    }

    if (!sameIds(form.tag_ids, initialTagIds)) {
        return true;
    }

    if (!sameIds(form.person_ids, initialPersonIds)) {
        return true;
    }

    return false;
});

const canSave = computed(() => form.circle_ids.length > 0 && hasChanges.value && !form.processing);

watch(
    () => props.open,
    (isOpen) => {
        if (!isOpen) {
            return;
        }

        const tagIds = (props.tags ?? []).filter((t) => (t.type ?? 'tag') === 'tag').map((t) => t.id);
        const personIds = (props.tags ?? []).filter((t) => t.type === 'person').map((t) => t.id);

        form.clearErrors();
        form.defaults({
            caption: props.caption ?? '',
            circle_ids: props.circles.map((c) => c.id),
            tag_ids: tagIds,
            person_ids: personIds,
        });
        form.reset();
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

function submit() {
    form
        .transform((data) => ({
            caption: data.caption,
            circle_ids: data.circle_ids,
            tag_ids: [...data.tag_ids, ...data.person_ids],
        }))
        .put(update.url(props.postId), {
            preserveScroll: true,
            onSuccess: () => close(),
        });
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
                    v-model="form.caption"
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
                    :selected-ids="form.circle_ids"
                    :error="form.errors.circle_ids"
                    @update:selected-ids="form.circle_ids = $event"
                />
            </section>

            <section class="relative z-20">
                <TagSelector
                    :available-tags="availableTags"
                    :selected-ids="form.tag_ids"
                    :error="form.errors.tag_ids"
                    @update:selected-ids="form.tag_ids = $event"
                />
            </section>

            <section v-if="availablePersons.length > 0">
                <PersonPicker
                    :persons="availablePersons"
                    :selected-ids="form.person_ids"
                    @update:selected-ids="form.person_ids = $event"
                />
            </section>
        </div>

        <template #footer>
            <div class="px-4 py-3">
                <button
                    :disabled="!canSave"
                    class="w-full rounded-full bg-teal py-3 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-teal-light disabled:opacity-40"
                    @click="submit"
                >
                    {{ form.processing ? t('Saving...') : t('Save') }}
                </button>
            </div>
        </template>
    </BottomSheet>
</template>
