<script setup lang="ts">
import PostCard, { type PostData } from '@/components/PostCard.vue';
import PullToRefreshIndicator from '@/components/PullToRefreshIndicator.vue';
import { usePullToRefresh } from '@/composables/usePullToRefresh';
import { useTranslations } from '@/composables/useTranslations';
import AppLayout from '@/layouts/AppLayout.vue';
import { InfiniteScroll, Link, router } from '@inertiajs/vue3';
import { computed, onUnmounted, useTemplateRef } from 'vue';
import userIcon from '../../svg/doodle-icons/user.svg';

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

interface Circle {
    id: number;
    name: string;
    photo: string | null;
    members_count: number;
    members_can_invite: boolean;
    is_owner: boolean;
}

defineProps<{
    posts: {
        data: PostData[];
    };
    circles?: Circle[];
}>();

const { t } = useTranslations();

const infiniteScrollBuffer = 0;
console.log('[Feed] InfiniteScroll buffer:', infiniteScrollBuffer);

const layoutRef = useTemplateRef<InstanceType<typeof AppLayout>>('layout');
const containerRef = computed(() => layoutRef.value?.mainRef ?? null);

// Scroll to top when tapping the Home tab while already on the feed.
// Partial reloads (infinite scroll, pull-to-refresh) have visit.only populated,
// so we only intercept full-page visits where only is empty.
const removeBeforeListener = router.on('before', (event) => {
    const visit = event.detail.visit;

    // Let partial reloads (infinite scroll, pull-to-refresh) pass through
    if (visit.only.length > 0) return;

    const targetPath = visit.url.pathname;
    const currentPath = window.location.pathname;

    if (targetPath === currentPath && containerRef.value && containerRef.value.scrollTop > 0) {
        event.preventDefault();
        containerRef.value.scrollTo({ top: 0, behavior: 'smooth' });
    }
});

onUnmounted(() => removeBeforeListener());

const { pullDistance, isRefreshing } = usePullToRefresh({
    onRefresh: () =>
        new Promise<void>((resolve) => {
            router.reload({
                only: ['posts', 'circles'],
                reset: ['posts'],
                data: { page: 1 },
                onFinish: () => resolve(),
            });
        }),
    containerRef,
});
</script>

<template>
    <AppLayout ref="layout" :show-header="false">
        <template #above>
            <!-- Family Circles -->
            <div class="pt-[var(--inset-top)] left-[var(--inset-left)] right-[var(--inset-right)] fixed z-100 flex gap-3 overflow-x-auto no-scrollbar border-b border-sand-200 bg-white px-4 py-3 dark:border-sand-800 dark:bg-sand-900">
                <Link href="/circles" class="group flex shrink-0 flex-col items-center gap-1.5">
                    <div class="rounded-full p-0.5">
                        <div class="flex size-14 items-center justify-center rounded-full border-2 border-dashed border-sand-300 transition-transform duration-500 group-hover:rotate-90 dark:border-sand-600">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-6 text-accent">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                        </div>
                    </div>
                    <span class="text-[10px] font-medium text-sand-500 dark:text-sand-400">{{ t('Circles') }}</span>
                </Link>

                <!-- Skeleton while loading -->
                <template v-if="!circles">
                    <div v-for="n in 4" :key="n" class="flex shrink-0 flex-col items-center gap-1.5">
                        <div class="size-15 animate-pulse rounded-full bg-sand-200 dark:bg-sand-700" />
                        <div class="h-3 w-12 animate-pulse rounded bg-sand-200 dark:bg-sand-700" />
                    </div>
                </template>

                <Link
                    v-else
                    v-for="circle in circles"
                    :key="circle.id"
                    :href="`/circles/${circle.id}`"
                    class="flex shrink-0 flex-col items-center gap-1.5"
                >
                    <div class="circle-ring relative rounded-full p-[2px]">
                        <div class="rounded-full bg-white p-0.5 dark:bg-sand-900">
                            <img
                                v-if="circle.photo"
                                :src="circle.photo"
                                :alt="circle.name"
                                class="size-14 rounded-full object-cover"
                            />
                            <div v-else class="flex size-14 items-center justify-center rounded-full bg-sand-100 dark:bg-sand-900">
                                <span aria-hidden="true" class="inline-block size-7 bg-sand-600 dark:bg-sand-300" :style="iconMaskStyle(userIcon)"></span>
                            </div>
                        </div>
                        <div
                            v-if="!circle.is_owner && !circle.members_can_invite"
                            class="absolute bottom-0 right-0 flex size-5 items-center justify-center rounded-full bg-teal ring-2 ring-white dark:ring-sand-900"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="size-3 text-white">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                            </svg>
                        </div>
                    </div>
                    <span class="max-w-16 truncate text-[10px] font-medium text-sand-700 dark:text-sand-300">{{ circle.name }}</span>
                </Link>
            </div>
        </template>

        <div class="pb-24 mt-23.75">
            <PullToRefreshIndicator :pull-distance="pullDistance" :is-refreshing="isRefreshing" />

            <template v-if="!posts">
                <div v-for="n in 3" :key="n" class="animate-pulse">
                    <div class="flex items-center gap-3 px-4 py-3">
                        <div class="size-10 rounded-full bg-sand-200 dark:bg-sand-700" />
                        <div class="h-3 w-32 rounded bg-sand-200 dark:bg-sand-700" />
                    </div>
                    <div class="aspect-square w-full bg-sand-200 dark:bg-sand-700" />
                    <div class="space-y-2 px-4 py-3">
                        <div class="h-3 w-24 rounded bg-sand-200 dark:bg-sand-700" />
                        <div class="h-3 w-48 rounded bg-sand-200 dark:bg-sand-700" />
                    </div>
                </div>
            </template>

            <InfiniteScroll v-else data="posts" only-next :buffer="infiniteScrollBuffer" preserve-url class="no-scrollbar">
                <template #default>
                    <PostCard v-for="post in posts.data" :key="post.id" :post="post" />
                </template>
                <template #loading>
                    <div class="flex items-center justify-center gap-2 py-6 text-sm text-sand-500 dark:text-sand-400">
                        <span class="flex items-center gap-1">
                            <span class="dot dot-1 size-1.5 rounded-full bg-teal"></span>
                            <span class="dot dot-2 size-1.5 rounded-full bg-accent"></span>
                            <span class="dot dot-3 size-1.5 rounded-full bg-sage-500"></span>
                        </span>
                        {{ t('Loading more...') }}
                    </div>
                </template>
            </InfiniteScroll>

            <div v-if="posts && posts.data.length === 0" class="relative flex flex-col items-center justify-center overflow-hidden px-8 py-20">
                <!-- Soft blobs -->
                <div aria-hidden="true" class="pointer-events-none absolute inset-0">
                    <div class="absolute -left-16 top-4 size-56 rounded-full bg-sage-200/50 blur-3xl dark:bg-sage-700/20"></div>
                    <div class="absolute -right-16 bottom-0 size-64 rounded-full bg-accent-soft/30 blur-3xl dark:bg-accent/10"></div>
                </div>

                <!-- Floating doodles -->
                <div aria-hidden="true" class="pointer-events-none absolute inset-0">
                    <svg class="empty-doodle empty-doodle-1 absolute left-10 top-10 size-5 text-accent/70" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2l2.1 6.9L21 11l-6.9 2.1L12 20l-2.1-6.9L3 11l6.9-2.1z" />
                    </svg>
                    <svg class="empty-doodle empty-doodle-2 absolute right-12 top-16 size-4 text-teal/60" viewBox="0 0 24 24" fill="currentColor">
                        <circle cx="12" cy="12" r="6" />
                    </svg>
                    <svg class="empty-doodle empty-doodle-3 absolute bottom-12 left-6 size-5 text-sage-500/70" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 21s-7-4.5-7-10a4 4 0 0 1 7-2.6A4 4 0 0 1 19 11c0 5.5-7 10-7 10z" />
                    </svg>
                </div>

                <div class="relative mb-5 flex size-24 rotate-[-6deg] items-center justify-center rounded-3xl bg-white shadow-lg shadow-sand-900/5 dark:bg-sand-800">
                    <span class="text-5xl">📸</span>
                    <span aria-hidden="true" class="absolute -right-2 -top-2 flex size-8 rotate-12 items-center justify-center rounded-full bg-accent text-sm shadow-md">
                        ✨
                    </span>
                </div>

                <h3 class="relative font-display text-xl font-semibold text-teal">{{ t('Share your first moment') }}</h3>
                <p class="relative mt-2 text-center text-sm text-sand-600 dark:text-sand-400">
                    {{ t('Add a photo and share it with your family and friends.') }}
                </p>
            </div>
        </div>

    </AppLayout>
</template>

<style scoped>
.circle-ring {
    background: conic-gradient(
        from 0deg,
        var(--color-accent),
        var(--color-accent-soft),
        var(--color-sage-400),
        var(--color-teal-muted),
        var(--color-accent)
    );
}

.dot-1 { animation-delay: 0s; }
.dot-2 { animation-delay: 0.15s; }
.dot-3 { animation-delay: 0.3s; }

.empty-doodle-1 { animation-delay: 0s; }
.empty-doodle-2 { animation-delay: 1.2s; animation-duration: 5s; }
.empty-doodle-3 { animation-delay: 2.4s; animation-duration: 7s; }
</style>
