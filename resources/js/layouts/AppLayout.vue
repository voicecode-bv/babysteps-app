<script setup lang="ts">
import BottomNav from '@/components/BottomNav.vue';
import { useTranslations } from '@/composables/useTranslations';
import { usePage } from '@inertiajs/vue3';
import { computed, useSlots } from 'vue';

const props = withDefaults(defineProps<{
    showHeader?: boolean;
    title?: string;
}>(), {
    showHeader: true,
});

const { t } = useTranslations();
const slots = useSlots();
const page = usePage();

const hasHeaderLeft = computed(() => !!slots['header-left']);
const isWeb = computed(() => page.props.platform === 'web');
</script>

<template>
    <div class="flex h-dvh flex-col bg-sand-50 text-sand-900 dark:bg-sand-900 dark:text-sand-100">
        <header
            v-if="props.showHeader"
            class="flex items-center justify-between border-b border-sand-200 bg-white px-4 pb-3 dark:border-sand-800 dark:bg-sand-900"
            style="padding-top: max(0.75rem, var(--inset-top))"
        >
            <div class="flex w-16 items-center">
                <slot name="header-left">
                    <span v-if="!hasHeaderLeft" />
                </slot>
            </div>
            <h1 class="text-lg font-semibold tracking-tight text-sand-800 dark:text-sand-100">{{ title ?? t('Babysteps') }}</h1>
            <div class="flex w-16 items-center justify-end">
                <slot name="header-right" />
            </div>
        </header>

        <main class="flex-1 overflow-y-auto">
            <slot />
        </main>

        <BottomNav v-if="isWeb" />
    </div>
</template>
