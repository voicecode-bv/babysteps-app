<script setup lang="ts">
import { onUnmounted, watch } from 'vue';

const props = defineProps<{
    open: boolean;
}>();

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
}>();

function close() {
    emit('update:open', false);
}

function handleKeydown(event: KeyboardEvent) {
    if (event.key === 'Escape' && props.open) {
        close();
    }
}

watch(
    () => props.open,
    (isOpen) => {
        const scrollContainer = document.querySelector('main[scroll-region]') as HTMLElement | null;
        if (scrollContainer) {
            scrollContainer.style.overflow = isOpen ? 'hidden' : '';
        }

        if (isOpen) {
            document.addEventListener('keydown', handleKeydown);
        } else {
            document.removeEventListener('keydown', handleKeydown);
        }
    },
);

onUnmounted(() => {
    document.removeEventListener('keydown', handleKeydown);
    const scrollContainer = document.querySelector('main[scroll-region]') as HTMLElement | null;
    if (scrollContainer) {
        scrollContainer.style.overflow = '';
    }
});
</script>

<template>
    <teleport to="body">
        <div
            :class="[
                'fixed inset-0 z-9999 bg-black/50 transition-opacity duration-300',
                open ? 'pointer-events-auto opacity-100' : 'pointer-events-none opacity-0',
            ]"
            @click="close"
        />
        <div
            :class="[
                'fixed inset-x-0 bottom-0 z-9999 flex max-h-[85vh] flex-col rounded-t-2xl bg-white shadow-2xl transition-transform duration-300 ease-out dark:bg-sand-900',
                open ? 'translate-y-0' : 'translate-y-full',
            ]"
            role="dialog"
            aria-modal="true"
        >
            <div class="flex justify-center pt-2">
                <div class="h-1 w-10 rounded-full bg-sand-200 dark:bg-sand-700" />
            </div>
            <div v-if="$slots.header" class="flex-shrink-0 border-b border-sand-100 px-4 py-3 dark:border-sand-800">
                <slot name="header" />
            </div>
            <div class="min-h-0 flex-1 overflow-y-auto">
                <slot />
            </div>
            <div
                v-if="$slots.footer"
                class="flex-shrink-0 border-t border-sand-200 bg-white pb-[env(safe-area-inset-bottom)] dark:border-sand-800 dark:bg-sand-900"
            >
                <slot name="footer" />
            </div>
        </div>
    </teleport>
</template>
