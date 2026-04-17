<script setup lang="ts">
import PullToRefreshIndicator from '@/components/PullToRefreshIndicator.vue';
import { usePullToRefresh } from '@/composables/usePullToRefresh';
import { useTranslations } from '@/composables/useTranslations';
import AppLayout from '@/layouts/AppLayout.vue';
import { router } from '@inertiajs/vue3';
import { computed, onMounted, ref, useTemplateRef } from 'vue';

interface Notification {
    id: string;
    type: string;
    data: {
        user_id?: number;
        user_name?: string;
        user_username?: string;
        user_avatar?: string | null;
        post_id?: number;
        post_media_url?: string | null;
        comment_id?: number;
        comment_body?: string;
        circle_id?: number;
        circle_name?: string;
    };
    read_at: string | null;
    created_at: string;
}

interface CircleInvitation {
    id: number;
    status: string;
    created_at: string;
    circle: {
        id: number;
        name: string;
    };
    inviter: {
        id: number;
        name: string;
        username: string;
        avatar: string | null;
    };
}

const props = defineProps<{
    circleInvitations?: CircleInvitation[];
    notifications: Notification[];
}>();

const optimisticallyRead = ref<Set<string>>(new Set());

const { t } = useTranslations();

function goBack() {
    window.history.back();
}

function isRead(notification: Notification): boolean {
    return !!notification.read_at || optimisticallyRead.value.has(notification.id);
}

const hasUnread = computed(() => props.notifications?.some((n) => !isRead(n)) ?? false);

const layoutRef = useTemplateRef<InstanceType<typeof AppLayout>>('layout');
const containerRef = computed(() => layoutRef.value?.mainRef ?? null);
const isLoading = ref(true);

const { pullDistance, isRefreshing } = usePullToRefresh({
    onRefresh: () =>
        new Promise<void>((resolve) => {
            router.reload({
                only: ['notifications', 'circleInvitations'],
                onFinish: () => resolve(),
            });
        }),
    containerRef,
});

onMounted(() => {
    router.reload({
        only: ['notifications', 'circleInvitations'],
        onFinish: () => {
            isLoading.value = false;
        },
    });
});

function markAllAsRead() {
    // Optimistically mark all as read
    const unreadIds = props.notifications.filter((n) => !n.read_at).map((n) => n.id);
    unreadIds.forEach((id) => optimisticallyRead.value.add(id));

    router.post('/notifications/read', {}, {
        preserveScroll: true,
        onError: () => {
            // Rollback on failure
            unreadIds.forEach((id) => optimisticallyRead.value.delete(id));
        },
    });
}

function openNotification(notification: Notification) {
    const url = notificationUrl(notification);

    if (!isRead(notification)) {
        // Optimistically mark as read
        optimisticallyRead.value.add(notification.id);

        router.post('/notifications/read', { ids: [notification.id] }, {
            preserveScroll: true,
            onError: () => {
                optimisticallyRead.value.delete(notification.id);
            },
        });
    }

    if (url !== '#') {
        router.visit(url);
    }
}

function acceptInvitation(invitationId: number) {
    router.post(`/circle-invitations/${invitationId}/accept`, {}, { preserveScroll: true });
}

function declineInvitation(invitationId: number) {
    router.post(`/circle-invitations/${invitationId}/decline`, {}, { preserveScroll: true });
}

function notificationMessage(notification: Notification): string {
    const name = notification.data.user_name ?? '';
    switch (notification.type) {
        case 'post-liked':
            return t(':name liked your post', { name });
        case 'post-commented':
            return t(':name commented: :comment', { name, comment: notification.data.comment_body ?? '' });
        case 'comment-liked':
            return t(':name liked your comment', { name });
        case 'comment-replied':
            return t(':name replied: :comment', { name, comment: notification.data.comment_body ?? '' });
        case 'new-circle-post':
            return t(':name shared a new moment', { name });
        case 'circle-invitation-accepted':
            return t(':name accepted your invitation to :circle', { name, circle: notification.data.circle_name ?? '' });
        default:
            return '';
    }
}

function notificationUrl(notification: Notification): string {
    if (notification.data.post_id) {
        return `/posts/${notification.data.post_id}`;
    }
    if (notification.data.circle_id) {
        return `/circles/${notification.data.circle_id}`;
    }
    return '#';
}

function timeAgo(dateString: string): string {
    const now = new Date();
    const date = new Date(dateString);
    const diffMs = now.getTime() - date.getTime();
    const diffMinutes = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMinutes / 60);
    const diffDays = Math.floor(diffHours / 24);
    const diffWeeks = Math.floor(diffDays / 7);

    if (diffMinutes < 1) return t('just now');
    if (diffMinutes < 60) return t(':count min ago', { count: diffMinutes });
    if (diffHours < 24) return t(':count hours ago', { count: diffHours });
    if (diffDays < 7) return t(':count days ago', { count: diffDays });
    return t(':count weeks ago', { count: diffWeeks });
}
</script>

<template>
    <AppLayout ref="layout" :title="t('Notifications')">
        <template #header-left>
            <button class="flex items-center text-sand-700 dark:text-sand-300" @click="goBack">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                </svg>
            </button>
        </template>
        <template #header-right>
            <button
                v-if="hasUnread"
                class="text-sand-600 dark:text-sand-300"
                @click="markAllAsRead"
            >
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                </svg>
            </button>
        </template>

        <div class="mt-10 pb-24">
        <PullToRefreshIndicator :pull-distance="pullDistance" :is-refreshing="isRefreshing" />

        <!-- Circle Invitations -->
        <template v-if="circleInvitations && circleInvitations.length > 0">
            <div class="border-b border-sand-100 bg-sand-50 px-4 py-2 dark:border-sand-800 dark:bg-sand-800/50">
                <h4 class="text-xs font-semibold uppercase tracking-wide text-sand-400 dark:text-sand-500">{{ t('Circle invitations') }}</h4>
            </div>
            <div
                v-for="invitation in circleInvitations"
                :key="`invitation-${invitation.id}`"
                class="flex items-start gap-3 border-b border-sand-100 bg-sand-50/50 px-4 py-3 dark:border-sand-800 dark:bg-sand-800/30"
            >
                <!-- Avatar -->
                <div class="flex-shrink-0">
                    <img
                        v-if="invitation.inviter.avatar"
                        :src="invitation.inviter.avatar"
                        :alt="invitation.inviter.name"
                        class="size-10 rounded-full object-cover"
                    />
                    <div v-else class="flex size-10 items-center justify-center rounded-full bg-sage-100 dark:bg-sage-800">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5 text-sage-600 dark:text-sage-400">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                        </svg>
                    </div>
                </div>

                <!-- Content -->
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold text-sand-800 dark:text-sand-200">
                        {{ t(':name has invited you', { name: invitation.inviter.name }) }}
                    </p>
                    <p class="mt-0.5 text-xs text-sand-500 dark:text-sand-400">
                        {{ invitation.circle.name }} · {{ timeAgo(invitation.created_at) }}
                    </p>
                    <div class="mt-2 flex gap-2">
                        <button
                            class="rounded-lg bg-sage-600 px-3 py-1.5 text-xs font-medium text-white dark:bg-sage-700"
                            @click="acceptInvitation(invitation.id)"
                        >
                            {{ t('Accept') }}
                        </button>
                        <button
                            class="rounded-lg bg-sand-300 px-3 py-1.5 text-xs font-medium text-sand-800 dark:bg-sand-700 dark:text-sand-300"
                            @click="declineInvitation(invitation.id)"
                        >
                            {{ t('Decline') }}
                        </button>
                    </div>
                </div>
            </div>
        </template>

        <!-- Notification skeletons while loading -->
        <template v-if="isLoading">
            <div v-for="n in 6" :key="n" class="flex items-start gap-3 border-b border-sand-100 px-4 py-3 dark:border-sand-800">
                <div class="size-10 flex-shrink-0 animate-pulse rounded-full bg-sand-200 dark:bg-sand-700" />
                <div class="min-w-0 flex-1 space-y-2">
                    <div class="h-3.5 animate-pulse rounded bg-sand-200 dark:bg-sand-700" :style="{ width: `${60 + n * 5}%` }" />
                    <div class="h-3 w-16 animate-pulse rounded bg-sand-200 dark:bg-sand-700" />
                </div>
                <div v-if="n % 2 === 0" class="size-10 flex-shrink-0 animate-pulse rounded bg-sand-200 dark:bg-sand-700" />
            </div>
        </template>

        <!-- Notifications list -->
        <div v-else-if="notifications">
            <button
                v-for="notification in notifications"
                :key="notification.id"
                class="flex w-full items-start gap-3 border-b border-sand-100 px-4 py-3 text-left dark:border-sand-800"
                :class="{ 'bg-sand-50 dark:bg-sand-800/50': !isRead(notification) }"
                @click="openNotification(notification)"
            >
                <!-- Avatar -->
                <div class="flex-shrink-0">
                    <img
                        v-if="notification.data.user_avatar"
                        :src="notification.data.user_avatar"
                        :alt="notification.data.user_name"
                        class="size-10 rounded-full object-cover"
                    />
                    <div v-else class="flex size-10 items-center justify-center rounded-full bg-sand-200 dark:bg-sand-700">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5 text-sand-500 dark:text-sand-400">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                        </svg>
                    </div>
                </div>

                <!-- Content -->
                <div class="min-w-0 flex-1">
                    <p class="text-sm text-sand-800 dark:text-sand-200" :class="{ 'font-semibold': !isRead(notification) }">
                        {{ notificationMessage(notification) }}
                    </p>
                    <p class="mt-0.5 text-xs text-sand-500 dark:text-sand-400">
                        {{ timeAgo(notification.created_at) }}
                    </p>
                </div>

                <!-- Post thumbnail -->
                <img
                    v-if="notification.data.post_media_url"
                    :src="notification.data.post_media_url"
                    class="size-10 flex-shrink-0 rounded object-cover"
                />

                <!-- Unread indicator -->
                <div v-if="!isRead(notification)" class="mt-2 size-2 flex-shrink-0 rounded-full bg-blue-500" />
            </button>
        </div>

        <!-- Empty state -->
        <div v-if="!isLoading && notifications && notifications.length === 0 && (!circleInvitations || circleInvitations.length === 0)" class="flex flex-col items-center justify-center px-8 py-20">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" class="mb-4 size-16 text-sand-300 dark:text-sand-600">
                <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
            </svg>
            <h3 class="font-display text-lg font-semibold text-sand-800 dark:text-sand-200">{{ t('No notifications yet') }}</h3>
            <p class="mt-1 text-center text-sm text-sand-500 dark:text-sand-400">
                {{ t("When someone interacts with your posts, you'll see it here.") }}
            </p>
        </div>
        </div>
    </AppLayout>
</template>
