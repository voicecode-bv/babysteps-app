<script setup lang="ts">
import {
    computed,
    nextTick,
    onBeforeUnmount,
    onMounted,
    ref,
    watch,
} from 'vue';
import { useTranslations } from '@/spa/composables/useTranslations';
import heartIcon from '../../../svg/doodle-icons/heart-filled.svg';
import messageIcon from '../../../svg/doodle-icons/message.svg';
import photoIcon from '../../../svg/doodle-icons/photo.svg';

const { t } = useTranslations();

// A scripted "mock feed" that fills itself one moment at a time, then loops.
// The point it makes visually: an empty feed looks bare, but every added photo
// makes it warmer and livelier. No real images needed, just brand gradients
// standing in for snapshots so the preview stays light and auto-translates.
interface MockPost {
    id: number;
    author: string;
    when: string;
    photo: string;
    likes: number;
    comments: number;
    tilt: string;
}

const posts: MockPost[] = [
    {
        id: 1,
        author: 'Mama',
        when: t('Just now'),
        photo: 'from-sage-200 to-sand-100',
        likes: 3,
        comments: 1,
        tilt: '-1.4deg',
    },
    {
        id: 2,
        author: 'Opa',
        when: t('2 min'),
        photo: 'from-brand-yellow/40 to-brand-orange/30',
        likes: 5,
        comments: 2,
        tilt: '1.1deg',
    },
    {
        id: 3,
        author: 'Tante Lot',
        when: t('10 min'),
        photo: 'from-accent-soft/40 to-sage-200',
        likes: 8,
        comments: 4,
        tilt: '-0.7deg',
    },
    {
        id: 4,
        author: 'Papa',
        when: t('1 h'),
        photo: 'from-brand-green/25 to-sage-200',
        likes: 12,
        comments: 6,
        tilt: '1.3deg',
    },
];

const revealed = ref(0);
const visiblePosts = computed(() => posts.slice(0, revealed.value));

// The window is shorter than the full stack on purpose: once it overflows we
// auto-scroll to the freshest card, so cards three and four glide into view
// the way a real feed scrolls to its newest moment.
const feedWindow = ref<HTMLElement | null>(null);

watch(revealed, async (count) => {
    await nextTick();

    const el = feedWindow.value;

    if (!el) {
        return;
    }

    // Jump back to the top on the loop reset; smooth-scroll to the latest
    // card on every reveal. overflow-hidden still scrolls programmatically.
    el.scrollTo({
        top: count === 0 ? 0 : el.scrollHeight,
        behavior: count === 0 ? 'auto' : 'smooth',
    });
});

// Caption escalates with the count: bare at first, celebratory once it is full.
const caption = computed(() => {
    if (revealed.value <= 1) {
        return t('A quiet start.');
    }

    if (revealed.value < posts.length) {
        return t('Watch your feed come to life.');
    }

    return t('The more you share, the warmer it gets.');
});

let timer: ReturnType<typeof setTimeout> | null = null;

function prefersReducedMotion(): boolean {
    return (
        typeof window !== 'undefined' &&
        window.matchMedia('(prefers-reduced-motion: reduce)').matches
    );
}

// Hand-rolled loop instead of setInterval: the pause on a full feed and the
// reset to empty need different delays, and chained timeouts read clearer.
function scheduleNext(): void {
    const full = revealed.value >= posts.length;
    const delay = full ? 2200 : revealed.value === 0 ? 600 : 850;

    timer = setTimeout(() => {
        revealed.value = full ? 0 : revealed.value + 1;
        scheduleNext();
    }, delay);
}

onMounted(() => {
    if (prefersReducedMotion()) {
        revealed.value = posts.length;

        return;
    }

    scheduleNext();
});

onBeforeUnmount(() => {
    if (timer) {
        clearTimeout(timer);
    }
});
</script>

<template>
    <div class="mx-auto w-full max-w-[17rem]">
        <!-- Counter pill: the headline metric that ticks up with every photo. -->
        <div class="mb-3 flex items-center justify-center">
            <span
                class="inline-flex items-center gap-1.5 rounded-full bg-surface/80 px-3 py-1 text-xs font-semibold text-ink shadow-sm backdrop-blur-sm"
            >
                <span
                    aria-hidden="true"
                    class="inline-block size-3.5 bg-sage-600"
                    :style="{
                        maskImage: `url(${photoIcon})`,
                        WebkitMaskImage: `url(${photoIcon})`,
                        maskSize: 'contain',
                        WebkitMaskSize: 'contain',
                        maskRepeat: 'no-repeat',
                        WebkitMaskRepeat: 'no-repeat',
                        maskPosition: 'center',
                        WebkitMaskPosition: 'center',
                    }"
                ></span>
                {{ t(':count moments', { count: revealed }) }}
            </span>
        </div>

        <!-- The "screen": a soft framed column the mock posts drop into. The
             inner div is the scroll viewport; the fade sits outside it so it
             stays pinned while the cards scroll underneath. -->
        <div class="relative h-72">
            <div
                ref="feedWindow"
                class="h-full overflow-hidden rounded-2xl bg-sand/70 p-3 shadow-inner ring-1 ring-night/5"
            >
                <div
                    v-if="revealed === 0"
                    class="absolute inset-0 flex items-center justify-center px-6 text-center text-sm text-ink-muted"
                >
                    {{ t('An empty album, waiting for your first photo.') }}
                </div>

                <TransitionGroup name="feed-drop" tag="div" class="space-y-3">
                    <article
                        v-for="post in visiblePosts"
                        :key="post.id"
                        class="rounded-xl bg-surface p-2.5 shadow-sm"
                        :style="{ rotate: post.tilt }"
                    >
                        <div class="flex items-center gap-2">
                            <div
                                class="avatar-ring size-6 shrink-0 bg-gradient-to-br"
                                :class="post.photo"
                            ></div>
                            <span class="text-xs font-semibold text-ink">{{
                                post.author
                            }}</span>
                            <span
                                class="ml-auto text-[0.625rem] text-ink-muted"
                                >{{ post.when }}</span
                            >
                        </div>
                        <div
                            class="mt-2 h-20 rounded-lg bg-gradient-to-br"
                            :class="post.photo"
                        ></div>
                        <div
                            class="mt-2 flex items-center gap-3 text-[0.625rem] text-ink-muted"
                        >
                            <span class="inline-flex items-center gap-1">
                                <span
                                    aria-hidden="true"
                                    class="inline-block size-3 bg-brand-orange"
                                    :style="{
                                        maskImage: `url(${heartIcon})`,
                                        WebkitMaskImage: `url(${heartIcon})`,
                                        maskSize: 'contain',
                                        WebkitMaskSize: 'contain',
                                        maskRepeat: 'no-repeat',
                                        WebkitMaskRepeat: 'no-repeat',
                                    }"
                                ></span>
                                {{ post.likes }}
                            </span>
                            <span class="inline-flex items-center gap-1">
                                <span
                                    aria-hidden="true"
                                    class="inline-block size-3 bg-sage-600"
                                    :style="{
                                        maskImage: `url(${messageIcon})`,
                                        WebkitMaskImage: `url(${messageIcon})`,
                                        maskSize: 'contain',
                                        WebkitMaskSize: 'contain',
                                        maskRepeat: 'no-repeat',
                                        WebkitMaskRepeat: 'no-repeat',
                                    }"
                                ></span>
                                {{ post.comments }}
                            </span>
                        </div>
                    </article>
                </TransitionGroup>
            </div>

            <!-- Bottom fade so cards melt away under the caption as it fills.
                 Sits outside the scroll viewport so it stays pinned. -->
            <div
                aria-hidden="true"
                class="pointer-events-none absolute inset-x-0 bottom-0 h-10 rounded-b-2xl bg-gradient-to-t from-sand/90 to-transparent"
            ></div>
        </div>

        <!-- Escalating caption carries the message in words too. -->
        <p
            class="mt-3 min-h-[2.5rem] text-center text-sm font-medium text-ink-muted"
        >
            {{ caption }}
        </p>
    </div>
</template>

<style scoped>
.feed-drop-enter-active {
    transition:
        transform 420ms var(--ease-spring, ease-out),
        opacity 280ms ease-out;
}

.feed-drop-leave-active {
    transition:
        transform 200ms ease-in,
        opacity 200ms ease-in;
    position: absolute;
}

.feed-drop-enter-from {
    opacity: 0;
    transform: translateY(-14px) scale(0.96);
}

.feed-drop-leave-to {
    opacity: 0;
    transform: translateY(8px);
}

.feed-drop-move {
    transition: transform 320ms var(--ease-spring-soft, ease-out);
}

@media (prefers-reduced-motion: reduce) {
    .feed-drop-enter-active,
    .feed-drop-leave-active,
    .feed-drop-move {
        transition: none;
    }
}
</style>
