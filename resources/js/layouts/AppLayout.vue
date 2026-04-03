<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed, useSlots } from 'vue';

const props = defineProps<{
    showHeader?: boolean;
    showNav?: boolean;
    title?: string;
}>();

const slots = useSlots();

const hasHeaderLeft = computed(() => !!slots['header-left']);
const hasHeaderRight = computed(() => !!slots['header-right']);

const showHeader = computed(() => props.showHeader !== false);
const showNav = computed(() => props.showNav !== false);
</script>

<template>
    <div class="flex h-dvh flex-col bg-black text-white">
        <!-- Header -->
        <header v-if="showHeader" class="flex items-center justify-between border-b border-neutral-800 px-4 py-3">
            <div class="flex w-16 items-center">
                <slot name="header-left">
                    <span v-if="!hasHeaderLeft" />
                </slot>
            </div>
            <h1 class="text-lg font-semibold">{{ title ?? 'Instagram' }}</h1>
            <div class="flex w-16 items-center justify-end">
                <slot name="header-right" />
            </div>
        </header>

        <!-- Content -->
        <main class="flex-1 overflow-y-auto">
            <slot />
        </main>

        <!-- Bottom Navigation -->
        <nav v-if="showNav" class="flex items-center justify-around border-t border-neutral-800 px-2 pb-6 pt-2">
            <Link href="/" class="flex flex-col items-center gap-1 p-2">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                </svg>
                <span class="text-[10px]">Home</span>
            </Link>
            <button class="flex flex-col items-center gap-1 p-2 text-neutral-400">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
                <span class="text-[10px]">Zoeken</span>
            </button>
            <button class="flex flex-col items-center gap-1 p-2 text-neutral-400">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
                <span class="text-[10px]">Nieuw</span>
            </button>
            <button class="flex flex-col items-center gap-1 p-2 text-neutral-400">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
                </svg>
                <span class="text-[10px]">Activiteit</span>
            </button>
            <button class="flex flex-col items-center gap-1 p-2 text-neutral-400">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                </svg>
                <span class="text-[10px]">Profiel</span>
            </button>
        </nav>
    </div>
</template>
