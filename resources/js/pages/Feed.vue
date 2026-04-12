<script setup lang="ts">
import PostCard, { type PostData } from '@/components/PostCard.vue';
import PullToRefreshIndicator from '@/components/PullToRefreshIndicator.vue';
import { usePullToRefresh } from '@/composables/usePullToRefresh';
import { useTranslations } from '@/composables/useTranslations';
import AppLayout from '@/layouts/AppLayout.vue';
import { InfiniteScroll, Link, router } from '@inertiajs/vue3';
import { computed, useTemplateRef } from 'vue';

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

const layoutRef = useTemplateRef<InstanceType<typeof AppLayout>>('layout');
const containerRef = computed(() => layoutRef.value?.mainRef ?? null);

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
            <div class="flex gap-3 overflow-x-auto scrollbar-none border-b border-sand-200 bg-white px-4 py-3 dark:border-sand-800 dark:bg-sand-900">
                <Link href="/circles" class="flex flex-shrink-0 flex-col items-center gap-1.5">
                    <div class="rounded-full p-0.5">
                        <div class="flex size-14 items-center justify-center rounded-full border-2 border-dashed border-sand-300 dark:border-sand-600">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 text-sand-400 dark:text-sand-500">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                        </div>
                    </div>
                    <span class="text-[10px] font-medium text-sand-500 dark:text-sand-400">{{ t('Circles') }}</span>
                </Link>

                <!-- Skeleton while loading -->
                <template v-if="!circles">
                    <div v-for="n in 4" :key="n" class="flex flex-shrink-0 flex-col items-center gap-1.5">
                        <div class="size-[60px] animate-pulse rounded-full bg-sand-200 dark:bg-sand-700" />
                        <div class="h-3 w-12 animate-pulse rounded bg-sand-200 dark:bg-sand-700" />
                    </div>
                </template>

                <Link
                    v-else
                    v-for="circle in circles"
                    :key="circle.id"
                    :href="`/circles/${circle.id}`"
                    class="flex flex-shrink-0 flex-col items-center gap-1.5"
                >
                    <div class="relative rounded-full bg-sand-200 p-0.5 dark:bg-sand-800">
                        <img
                            v-if="circle.photo"
                            :src="circle.photo"
                            :alt="circle.name"
                            class="size-14 rounded-full object-cover"
                        />
                        <div v-else class="flex size-14 items-center justify-center rounded-full bg-sand-100 dark:bg-sand-900">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.3" stroke="currentColor" class="size-7 text-sand-600 dark:text-sand-300">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                            </svg>
                        </div>
                        <div
                            v-if="!circle.is_owner && !circle.members_can_invite"
                            class="absolute bottom-0 right-0 flex size-5 items-center justify-center rounded-full bg-sand-500 ring-2 ring-white dark:bg-sand-600 dark:ring-sand-900"
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

        <PullToRefreshIndicator :pull-distance="pullDistance" :is-refreshing="isRefreshing" />

        <InfiniteScroll v-if="posts" data="posts" only-next :buffer="1500" preserve-url class="pb-24">
            <PostCard v-for="post in posts.data" :key="post.id" :post="post" />
        </InfiniteScroll>

        <div v-if="posts && posts.data.length === 0" class="flex flex-col items-center justify-center px-8 py-20">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" class="mb-4 size-16 text-sand-300 dark:text-sand-600">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316Z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0Z" />
            </svg>
            <h3 class="font-display text-lg font-semibold text-sand-800 dark:text-sand-200">{{ t('Share your first moment') }}</h3>
            <p class="mt-1 text-center text-sm text-sand-500 dark:text-sand-400">
                {{ t('Add a photo and share it with your family and friends.') }}
            </p>
        </div>
    </AppLayout>
</template>
