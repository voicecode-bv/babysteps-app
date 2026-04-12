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
    <header
        v-if="props.showHeader"
        class="flex items-center justify-between border-b border-sand-200 bg-white px-4 py-3 dark:border-sand-800 dark:bg-sand-900"
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

    <main ref="mainRef" class="flex-1 overflow-y-auto" scroll-region>
        <slot />
    </main>
</template>
