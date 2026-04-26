<script setup lang="ts">
import { computed, ref } from 'vue';
import { useTranslations } from '@/composables/useTranslations';
import userIcon from '../../svg/doodle-icons/user.svg';

interface Person {
    id: number;
    name: string;
    avatar_thumbnail?: string | null;
    avatar?: string | null;
}

const props = withDefaults(
    defineProps<{
        persons: Person[];
        selectedIds: number[];
        error?: string | null;
        defaultCollapsed?: boolean;
    }>(),
    {
        error: null,
        defaultCollapsed: false,
    },
);

const emit = defineEmits<{
    (e: 'update:selectedIds', value: number[]): void;
}>();

const { t } = useTranslations();

const isCollapsed = ref(props.defaultCollapsed);

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

const summaryText = computed(() => {
    if (props.selectedIds.length === 0) {
        return t('No persons selected');
    }

    return t(':count selected', { count: String(props.selectedIds.length) });
});

function toggle(personId: number) {
    if (props.selectedIds.includes(personId)) {
        emit(
            'update:selectedIds',
            props.selectedIds.filter((id) => id !== personId),
        );
    } else {
        emit('update:selectedIds', [...props.selectedIds, personId]);
    }
}
</script>

<template>
    <div>
        <div class="mb-3 flex items-center justify-between gap-2">
            <button
                type="button"
                class="flex items-center gap-1.5 text-xs font-medium uppercase tracking-wider text-sand-500 dark:text-sand-400"
                @click="isCollapsed = !isCollapsed"
            >
                {{ t('Tag persons') }}
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke-width="2"
                    stroke="currentColor"
                    class="size-3.5 transition-transform"
                    :class="isCollapsed ? '' : 'rotate-180'"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                </svg>
            </button>

            <span v-if="isCollapsed" class="truncate text-xs text-sand-500 dark:text-sand-400">{{ summaryText }}</span>
        </div>

        <div v-if="!isCollapsed && persons.length === 0" class="text-xs text-sand-500 dark:text-sand-400">
            {{ t('No persons yet. Add them in Settings → Persons.') }}
        </div>

        <div v-else-if="!isCollapsed" class="-mx-1 flex gap-3 overflow-x-auto px-1 pb-1 no-scrollbar">
            <button
                v-for="person in persons"
                :key="person.id"
                type="button"
                class="flex shrink-0 flex-col items-center gap-1.5"
                @click="toggle(person.id)"
            >
                <div
                    class="relative rounded-full p-[2px] transition-colors"
                    :class="selectedIds.includes(person.id) ? 'person-ring' : 'bg-sand-200 dark:bg-sand-700'"
                >
                    <div class="rounded-full bg-white p-0.5 dark:bg-sand-900">
                        <img
                            v-if="person.avatar_thumbnail || person.avatar"
                            :src="person.avatar_thumbnail ?? person.avatar ?? ''"
                            :alt="person.name"
                            class="size-14 rounded-full object-cover transition-opacity"
                            :class="selectedIds.includes(person.id) ? '' : 'opacity-60'"
                        />
                        <div
                            v-else
                            class="flex size-14 items-center justify-center rounded-full bg-sand-100 transition-opacity dark:bg-sand-900"
                            :class="selectedIds.includes(person.id) ? '' : 'opacity-60'"
                        >
                            <span aria-hidden="true" class="inline-block size-7 bg-sand-600 dark:bg-sand-300" :style="iconMaskStyle(userIcon)"></span>
                        </div>
                    </div>

                    <div
                        v-if="selectedIds.includes(person.id)"
                        class="absolute bottom-0 right-0 flex size-5 items-center justify-center rounded-full bg-teal ring-2 ring-white dark:ring-sand-900"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor" class="size-3 text-white">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                    </div>
                </div>
                <span
                    class="max-w-16 truncate text-[10px] font-medium"
                    :class="selectedIds.includes(person.id) ? 'text-sand-900 dark:text-sand-100' : 'text-sand-500 dark:text-sand-400'"
                >
                    {{ person.name }}
                </span>
            </button>
        </div>

        <p v-if="error" class="mt-2 text-xs text-blush-500">{{ error }}</p>
    </div>
</template>

<style scoped>
.person-ring {
    background: conic-gradient(
        from 0deg,
        var(--color-accent),
        var(--color-accent-soft),
        var(--color-sage-400),
        var(--color-teal-muted),
        var(--color-accent)
    );
}

.no-scrollbar::-webkit-scrollbar {
    display: none;
}

.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
