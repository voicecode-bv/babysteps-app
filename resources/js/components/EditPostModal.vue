<script setup lang="ts">
import BottomSheet from '@/components/BottomSheet.vue';
import { update } from '@/actions/App/Http/Controllers/PostActionController';
import { useTranslations } from '@/composables/useTranslations';
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

interface Circle {
    id: number;
    name: string;
}

const props = withDefaults(
    defineProps<{
        open: boolean;
        postId: number;
        caption: string | null;
        circles: Circle[];
        availableCircles?: Circle[] | null;
    }>(),
    {
        availableCircles: () => [],
    },
);

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
}>();

const { t } = useTranslations();

const form = useForm({
    caption: props.caption ?? '',
    circle_ids: props.circles.map((c) => c.id),
});

const availableCircles = computed<Circle[]>(() => props.availableCircles ?? []);

const allCirclesSelected = computed(
    () => availableCircles.value.length > 0 && form.circle_ids.length === availableCircles.value.length,
);

const hasChanges = computed(() => {
    if ((form.caption ?? '') !== (props.caption ?? '')) return true;
    const currentIds = [...props.circles.map((c) => c.id)].sort();
    const nextIds = [...form.circle_ids].sort();
    if (currentIds.length !== nextIds.length) return true;
    return currentIds.some((id, i) => id !== nextIds[i]);
});

const canSave = computed(() => form.circle_ids.length > 0 && hasChanges.value && !form.processing);

watch(
    () => props.open,
    (isOpen) => {
        if (!isOpen) return;

        form.clearErrors();
        form.defaults({
            caption: props.caption ?? '',
            circle_ids: props.circles.map((c) => c.id),
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

function toggleCircle(circleId: number) {
    const index = form.circle_ids.indexOf(circleId);
    if (index === -1) {
        form.circle_ids.push(circleId);
    } else {
        form.circle_ids.splice(index, 1);
    }
}

function toggleAllCircles() {
    if (allCirclesSelected.value) {
        form.circle_ids = [];
    } else {
        form.circle_ids = availableCircles.value.map((c) => c.id);
    }
}

function submit() {
    form.put(update.url(props.postId), {
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

            <section>
                <div class="mb-3 flex items-center justify-between">
                    <p class="text-xs font-medium uppercase tracking-wider text-sand-500 dark:text-sand-400">
                        {{ t('Share with circles') }}
                    </p>
                    <button
                        v-if="availableCircles.length > 0"
                        class="text-xs font-medium text-teal hover:text-teal-light"
                        @click="toggleAllCircles"
                    >
                        {{ allCirclesSelected ? t('Deselect all') : t('Select all') }}
                    </button>
                </div>

                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="circle in availableCircles"
                        :key="circle.id"
                        class="rounded-full px-4 py-2 text-sm font-medium transition-colors"
                        :class="form.circle_ids.includes(circle.id)
                            ? 'bg-teal text-white shadow-sm'
                            : 'bg-sand-100 text-sand-700 dark:bg-sand-800 dark:text-sand-200'"
                        @click="toggleCircle(circle.id)"
                    >
                        {{ circle.name }}
                    </button>
                </div>
                <p v-if="form.errors.circle_ids" class="mt-2 text-xs text-blush-500">{{ form.errors.circle_ids }}</p>
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
