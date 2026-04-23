<script setup lang="ts">
import { onMounted, onUnmounted, ref, useTemplateRef, watch } from 'vue';

const props = defineProps<{
    open: boolean;
}>();

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
}>();

const sheetRef = useTemplateRef<HTMLDivElement>('sheetRef');

const isIos = typeof navigator !== 'undefined' && /iP(hone|ad|od)/.test(navigator.userAgent);

let focusOutTimer: number | null = null;
let savedBodyOverflow = '';
let savedBodyTouchAction = '';
let savedHtmlOverflow = '';

const keyboardOpen = ref(false);
const dragOffset = ref(0);
const isDragging = ref(false);
const mounted = ref(false);

let dragStartY = 0;
let dragPointerId: number | null = null;

const DISMISS_THRESHOLD = 80;

function lockBodyScroll() {
    const body = document.body;
    const html = document.documentElement;

    savedBodyOverflow = body.style.overflow;
    savedBodyTouchAction = body.style.touchAction;
    savedHtmlOverflow = html.style.overflow;
    body.style.overflow = 'hidden';
    body.style.touchAction = 'none';
    html.style.overflow = 'hidden';
}

function unlockBodyScroll() {
    document.body.style.overflow = savedBodyOverflow;
    document.body.style.touchAction = savedBodyTouchAction;
    document.documentElement.style.overflow = savedHtmlOverflow;
}

function setKeyboardInset(offset: number) {
    document.documentElement.style.setProperty('--kb-inset', `${offset}px`);
    keyboardOpen.value = offset > 0;
}

function readOffset(): number {
    if (!isIos) {
        return 0;
    }

    const vv = window.visualViewport;

    if (!vv) {
        return 0;
    }

    return Math.max(0, window.innerHeight - vv.height);
}

function updateKeyboardOffset() {
    setKeyboardInset(readOffset());
}

function sampleRepeatedly() {
    // iOS WKWebView sometimes fires visualViewport.resize 100–400ms after focus.
    requestAnimationFrame(updateKeyboardOffset);
    window.setTimeout(updateKeyboardOffset, 150);
    window.setTimeout(updateKeyboardOffset, 400);
}

function onFocusIn() {
    if (focusOutTimer !== null) {
        window.clearTimeout(focusOutTimer);
        focusOutTimer = null;
    }

    sampleRepeatedly();
}

function onFocusOut(event: FocusEvent) {
    const next = event.relatedTarget as Node | null;

    if (next && sheetRef.value?.contains(next)) {
        return;
    }

    if (focusOutTimer !== null) {
        window.clearTimeout(focusOutTimer);
    }

    focusOutTimer = window.setTimeout(() => {
        setKeyboardInset(0);
        focusOutTimer = null;
    }, 50);
}

function close() {
    emit('update:open', false);
}

function onHandlePointerDown(event: PointerEvent) {
    if (event.pointerType === 'mouse' && event.button !== 0) {
        return;
    }

    dragPointerId = event.pointerId;
    dragStartY = event.clientY;
    isDragging.value = true;
    dragOffset.value = 0;
    (event.currentTarget as HTMLElement).setPointerCapture(event.pointerId);
}

function onHandlePointerMove(event: PointerEvent) {
    if (!isDragging.value || event.pointerId !== dragPointerId) {
        return;
    }

    dragOffset.value = Math.max(0, event.clientY - dragStartY);
}

function endDrag(event: PointerEvent) {
    if (!isDragging.value || event.pointerId !== dragPointerId) {
        return;
    }

    const shouldClose = dragOffset.value > DISMISS_THRESHOLD;

    isDragging.value = false;
    dragPointerId = null;
    dragOffset.value = 0;

    if (shouldClose) {
        close();
    }
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
            lockBodyScroll();
            document.addEventListener('keydown', handleKeydown);
            updateKeyboardOffset();
        } else {
            unlockBodyScroll();
            document.removeEventListener('keydown', handleKeydown);
            setKeyboardInset(0);
        }
    },
);

onMounted(() => {
    mounted.value = true;
    setKeyboardInset(0);
    window.visualViewport?.addEventListener('resize', updateKeyboardOffset);
    window.visualViewport?.addEventListener('scroll', updateKeyboardOffset);
});

onUnmounted(() => {
    document.removeEventListener('keydown', handleKeydown);
    window.visualViewport?.removeEventListener('resize', updateKeyboardOffset);
    window.visualViewport?.removeEventListener('scroll', updateKeyboardOffset);

    if (focusOutTimer !== null) {
        window.clearTimeout(focusOutTimer);
        focusOutTimer = null;
    }

    setKeyboardInset(0);
    const scrollContainer = document.querySelector('main[scroll-region]') as HTMLElement | null;

    if (scrollContainer) {
        scrollContainer.style.overflow = '';
    }

    if (props.open) {
        unlockBodyScroll();
    }
});
</script>

<template>
    <teleport v-if="mounted" to="body">
        <div
            :class="[
                'fixed inset-0 z-9999 bg-black/50 transition-opacity duration-300',
                open ? 'pointer-events-auto opacity-100' : 'pointer-events-none opacity-0',
            ]"
            @click="close"
        />
        <div
            ref="sheetRef"
            :class="[
                'fixed inset-x-0 bottom-0 z-9999 flex flex-col rounded-2xl bg-white shadow-2xl dark:bg-sand-900',
                isDragging ? '' : 'transition-transform duration-300 ease-out',
                open
                    ? 'translate-y-[calc(var(--drag-offset,0px)+var(--kb-inset,0px)*-1)]'
                    : 'translate-y-full',
            ]"
            :style="{
                maxHeight: 'calc(85dvh - var(--kb-inset, 0px))',
                '--drag-offset': `${dragOffset}px`,
            }"
            role="dialog"
            aria-modal="true"
            @focusin="onFocusIn"
            @focusout="onFocusOut"
        >
            <div
                class="flex cursor-grab touch-none justify-center pb-1 pt-3 active:cursor-grabbing"
                @pointerdown="onHandlePointerDown"
                @pointermove="onHandlePointerMove"
                @pointerup="endDrag"
                @pointercancel="endDrag"
            >
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
                :class="[
                    'flex-shrink-0 border-t border-sand-200 bg-white dark:border-sand-800 dark:bg-sand-900',
                    open && !keyboardOpen ? 'pb-24' : 'pb-[env(safe-area-inset-bottom)]',
                ]"
            >
                <slot name="footer" />
            </div>
        </div>
    </teleport>
</template>
