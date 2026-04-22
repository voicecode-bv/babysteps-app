<script setup lang="ts">
import { computed, onUnmounted, ref, watch } from 'vue';

const props = defineProps<{
    open: boolean;
}>();

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
}>();

const keyboardOffset = ref(0);

const sheetStyle = computed(() => ({
    bottom: `${keyboardOffset.value}px`,
    maxHeight: `calc(85vh - ${keyboardOffset.value}px)`,
}));

function updateKeyboardOffset() {
    const vv = window.visualViewport;
    if (!vv) {
        keyboardOffset.value = 0;
        return;
    }
    const offset = Math.max(0, window.innerHeight - vv.height - vv.offsetTop);
    keyboardOffset.value = offset;
}

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
            updateKeyboardOffset();
            window.visualViewport?.addEventListener('resize', updateKeyboardOffset);
            window.visualViewport?.addEventListener('scroll', updateKeyboardOffset);
        } else {
            document.removeEventListener('keydown', handleKeydown);
            window.visualViewport?.removeEventListener('resize', updateKeyboardOffset);
            window.visualViewport?.removeEventListener('scroll', updateKeyboardOffset);
            keyboardOffset.value = 0;
        }
    },
);

onUnmounted(() => {
    document.removeEventListener('keydown', handleKeydown);
    window.visualViewport?.removeEventListener('resize', updateKeyboardOffset);
    window.visualViewport?.removeEventListener('scroll', updateKeyboardOffset);
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
                'fixed inset-x-0 z-9999 flex flex-col rounded-t-2xl bg-white shadow-2xl transition-transform duration-300 ease-out dark:bg-sand-900',
                open ? 'translate-y-0' : 'translate-y-full',
            ]"
            :style="sheetStyle"
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
