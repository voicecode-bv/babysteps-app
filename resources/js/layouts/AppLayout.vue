<script setup lang="ts">
import { useTranslations } from '@/composables/useTranslations';
import { computed, ref, useSlots } from 'vue';

const props = withDefaults(defineProps<{
    showHeader?: boolean;
    title?: string;
}>(), {
    showHeader: true,
});

const { t } = useTranslations();
const slots = useSlots();

const hasHeaderLeft = computed(() => !!slots['header-left']);

const mainRef = ref<HTMLElement | null>(null);

defineExpose({ mainRef });
</script>

<template>
    <div aria-hidden="true" class="pointer-events-none fixed inset-0 -z-10 bg-warmwhite dark:bg-sand-900" />
    <div aria-hidden="true" class="pointer-events-none fixed inset-0 -z-10 overflow-hidden">
        <div class="absolute -left-24 -top-16 size-72 rounded-full bg-sage-200/40 blur-3xl dark:bg-sage-700/20"></div>
        <div class="absolute -right-24 top-1/3 size-80 rounded-full bg-accent-soft/30 blur-3xl dark:bg-accent/10"></div>
        <div class="absolute -bottom-24 left-1/4 size-96 rounded-full bg-sand-200/40 blur-3xl dark:bg-sand-700/20"></div>
    </div>

    <header
        v-if="props.showHeader"
        class="pt-[var(--inset-top,0)] left-[var(--inset-left,0)] right-[var(--inset-right,0)] fixed z-100 flex items-center justify-between border-b border-sand-200 bg-white px-4 py-3 dark:border-sand-800 dark:bg-sand-900"
    >
        <div class="flex w-16 items-center">
            <slot name="header-left">
                <span v-if="!hasHeaderLeft" />
            </slot>
        </div>
        <h1 class="font-display text-lg font-semibold tracking-tight text-sand-800 dark:text-sand-100">{{ title ?? t('Innerr') }}</h1>
        <div class="flex w-16 items-center justify-end">
            <slot name="header-right" />
        </div>
    </header>

    <slot name="above" />

    <main ref="mainRef" class="pt-[var(--inset-top)] flex h-dvh flex-col flex-1 overflow-y-auto">
        <slot />
    </main>

</template>
