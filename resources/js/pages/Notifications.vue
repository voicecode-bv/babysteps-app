<script setup lang="ts">
import IconTile from '@/components/IconTile.vue';
import PullToRefreshIndicator from '@/components/PullToRefreshIndicator.vue';
import SurfaceCard from '@/components/SurfaceCard.vue';
import { usePullToRefresh } from '@/composables/usePullToRefresh';
import { useTranslations } from '@/composables/useTranslations';
import { fetchNotificationsPage, type PaginationMeta } from '@/http/notifications';
import AppLayout from '@/layouts/AppLayout.vue';
import { router } from '@inertiajs/vue3';
import { computed, onMounted, ref, useTemplateRef, watch } from 'vue';
import bellIcon from '../../svg/doodle-icons/bell.svg';
import crownIcon from '../../svg/doodle-icons/crown.svg';
import heartFilledIcon from '../../svg/doodle-icons/heart-filled.svg';
import mailGiftIcon from '../../svg/doodle-icons/mail-gift.svg';
import mailOpenIcon from '../../svg/doodle-icons/mail-open.svg';
import message2Icon from '../../svg/doodle-icons/message-2.svg';
import messageIcon from '../../svg/doodle-icons/message.svg';
import userAddIcon from '../../svg/doodle-icons/user-add.svg';
import userIcon from '../../svg/doodle-icons/user.svg';

type NotificationType =
    | 'post-liked'
    | 'post-commented'
    | 'comment-liked'
    | 'comment-replied'
    | 'new-circle-post'
    | 'circle-invitation-accepted'
    | 'circle-ownership-transfer-requested'
    | 'circle-ownership-transfer-accepted'
    | 'circle-ownership-transfer-declined';

type IconToneName = 'sage' | 'sand' | 'accent' | 'teal';

interface Notification {
    id: string;
    type: NotificationType | string;
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
        to_user_name?: string;
        from_user_name?: string;
        recipient_name?: string;
        decliner_name?: string;
        [key: string]: unknown;
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

interface OwnershipTransfer {
    id: number;
    created_at: string;
    circle: {
        id: number;
        name: string;
    };
    from_user: {
        id: number;
        name: string;
        username: string;
        avatar: string | null;
    };
    to_user: {
        id: number;
        name: string;
        username: string;
        avatar: string | null;
    };
}

interface NotificationsPage {
    data: Notification[];
    meta: PaginationMeta;
}

const props = defineProps<{
    circleInvitations?: CircleInvitation[];
    ownershipTransfers?: OwnershipTransfer[];
    notifications: NotificationsPage;
}>();

const optimisticallyRead = ref<Set<string>>(new Set());

const hiddenNotificationTypes = new Set<string>(['circle-ownership-transfer-requested']);

const { t } = useTranslations();

const items = ref<Notification[]>([]);
const currentPage = ref(1);
const lastPage = ref(1);
const isLoadingMore = ref(false);
const loadMoreError = ref<string | null>(null);

function syncFromProps() {
    items.value = props.notifications?.data ?? [];
    currentPage.value = props.notifications?.meta?.current_page ?? 1;
    lastPage.value = props.notifications?.meta?.last_page ?? 1;
    loadMoreError.value = null;
}

watch(
    () => props.notifications,
    () => {
        syncFromProps();
    },
    { immediate: true },
);

function goBack() {
    window.history.back();
}

function isRead(notification: Notification): boolean {
    return !!notification.read_at || optimisticallyRead.value.has(notification.id);
}

const visibleNotifications = computed(() =>
    items.value.filter((n) => !hiddenNotificationTypes.has(n.type)),
);

const hasUnread = computed(() => visibleNotifications.value.some((n) => !isRead(n)));
const hasMore = computed(() => currentPage.value < lastPage.value);

const layoutRef = useTemplateRef<InstanceType<typeof AppLayout>>('layout');
const containerRef = computed(() => layoutRef.value?.mainRef ?? null);
const isLoading = ref(true);

const { pullDistance, isRefreshing } = usePullToRefresh({
    onRefresh: () =>
        new Promise<void>((resolve) => {
            router.reload({
                only: ['notifications', 'circleInvitations', 'ownershipTransfers', 'translations', 'locale'],
                onFinish: () => {
                    if (hasUnread.value) markAllAsRead();
                    resolve();
                },
            });
        }),
    containerRef,
});

onMounted(() => {
    router.reload({
        only: ['notifications', 'circleInvitations', 'ownershipTransfers', 'translations', 'locale'],
        onFinish: () => {
            isLoading.value = false;
        },
    });
});

async function loadMore() {
    if (isLoadingMore.value || !hasMore.value) return;

    isLoadingMore.value = true;
    loadMoreError.value = null;

    try {
        const result = await fetchNotificationsPage<Notification>(currentPage.value + 1);
        const seen = new Set(items.value.map((n) => n.id));
        const incoming = result.data.filter((n) => !seen.has(n.id));
        items.value = [...items.value, ...incoming];
        currentPage.value = result.meta.current_page;
        lastPage.value = result.meta.last_page;
    } catch {
        loadMoreError.value = t('Failed to load notifications');
    } finally {
        isLoadingMore.value = false;
    }
}

function markAllAsRead() {
    const unreadIds = items.value.filter((n) => !n.read_at).map((n) => n.id);
    unreadIds.forEach((id) => optimisticallyRead.value.add(id));

    router.post(
        '/notifications/read',
        {},
        {
            preserveScroll: true,
            onError: () => {
                unreadIds.forEach((id) => optimisticallyRead.value.delete(id));
            },
        },
    );
}

function openNotification(notification: Notification) {
    const url = notificationUrl(notification);

    if (!isRead(notification)) {
        optimisticallyRead.value.add(notification.id);

        router.post(
            '/notifications/read',
            { ids: [notification.id] },
            {
                preserveScroll: true,
                onError: () => {
                    optimisticallyRead.value.delete(notification.id);
                },
            },
        );
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

function acceptTransfer(transferId: number) {
    router.post(`/circle-ownership-transfers/${transferId}/accept`, {}, { preserveScroll: true });
}

function declineTransfer(transferId: number) {
    router.post(`/circle-ownership-transfers/${transferId}/decline`, {}, { preserveScroll: true });
}

function notificationMessage(notification: Notification): string {
    const name =
        notification.data.user_name ??
        notification.data.to_user_name ??
        notification.data.from_user_name ??
        notification.data.recipient_name ??
        notification.data.decliner_name ??
        '';
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
        case 'circle-ownership-transfer-requested':
            return t(':name wants to transfer ownership of :circle to you', { name, circle: notification.data.circle_name ?? '' });
        case 'circle-ownership-transfer-accepted':
            return t(':name accepted ownership of :circle', { name, circle: notification.data.circle_name ?? '' });
        case 'circle-ownership-transfer-declined':
            return t(':name declined ownership of :circle', { name, circle: notification.data.circle_name ?? '' });
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

interface BadgeConfig {
    icon: string;
    tone: IconToneName;
    bare?: boolean;
}

const typeIconMap: Record<string, BadgeConfig> = {
    'post-liked': { icon: heartFilledIcon, tone: 'accent' },
    'comment-liked': { icon: heartFilledIcon, tone: 'accent' },
    'post-commented': { icon: messageIcon, tone: 'sage' },
    'comment-replied': { icon: message2Icon, tone: 'sage' },
    'new-circle-post': { icon: bellIcon, tone: 'teal' },
    'circle-invitation-accepted': { icon: userAddIcon, tone: 'sage' },
    'circle-ownership-transfer-requested': { icon: crownIcon, tone: 'accent' },
    'circle-ownership-transfer-accepted': { icon: crownIcon, tone: 'sage' },
    'circle-ownership-transfer-declined': { icon: crownIcon, tone: 'sand' },
};

const bareToneClass: Record<IconToneName, string> = {
    accent: 'text-accent dark:text-accent-soft',
    sage: 'text-teal dark:text-sage-100',
    sand: 'text-sand-600 dark:text-sand-300',
    teal: 'text-teal dark:text-sage-100',
};

const filledToneClass: Record<IconToneName, string> = {
    sage: 'bg-sage-200 text-teal dark:bg-sage-800 dark:text-sage-100',
    sand: 'bg-sand-200 text-sand-700 dark:bg-sand-700 dark:text-sand-200',
    accent: 'bg-accent text-white dark:bg-accent dark:text-white',
    teal: 'bg-teal text-white',
};

function iconForType(type: string): BadgeConfig {
    return typeIconMap[type] ?? { icon: bellIcon, tone: 'sand' };
}

function maskStyleFor(icon: string) {
    return {
        maskImage: `url(${icon})`,
        WebkitMaskImage: `url(${icon})`,
        maskSize: 'contain',
        WebkitMaskSize: 'contain',
        maskRepeat: 'no-repeat',
        WebkitMaskRepeat: 'no-repeat',
        maskPosition: 'center',
        WebkitMaskPosition: 'center',
    };
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

interface NotificationGroup {
    key: 'today' | 'week' | 'earlier';
    label: string;
    items: Notification[];
}

const groupedNotifications = computed<NotificationGroup[]>(() => {
    if (!items.value.length) {
        return [];
    }

    const now = Date.now();
    const dayMs = 24 * 60 * 60 * 1000;
    const groups: Record<NotificationGroup['key'], NotificationGroup> = {
        today: { key: 'today', label: t('Today'), items: [] },
        week: { key: 'week', label: t('This week'), items: [] },
        earlier: { key: 'earlier', label: t('Earlier'), items: [] },
    };

    for (const notification of items.value) {
        if (hiddenNotificationTypes.has(notification.type)) continue;
        const age = now - new Date(notification.created_at).getTime();

        if (age < dayMs) {
            groups.today.items.push(notification);
        } else if (age < 7 * dayMs) {
            groups.week.items.push(notification);
        } else {
            groups.earlier.items.push(notification);
        }
    }

    return Object.values(groups).filter((group) => group.items.length > 0);
});
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

        <div class="relative mt-10 min-h-full pb-[calc(theme(spacing.32)+env(safe-area-inset-bottom))]">
            <PullToRefreshIndicator :pull-distance="pullDistance" :is-refreshing="isRefreshing" />

            <div class="relative space-y-4 px-4 pt-4">
                <!-- Mark all read -->
                <div v-if="hasUnread" class="flex justify-end px-1">
                    <button
                        class="inline-flex items-center gap-1.5 rounded-full bg-white/70 px-3 py-1.5 text-xs font-medium text-teal shadow-sm backdrop-blur-sm transition hover:bg-white dark:bg-sand-800/60 dark:text-sage-100 dark:hover:bg-sand-800"
                        @click="markAllAsRead"
                    >
                        <span aria-hidden="true" class="inline-block size-3.5 bg-current" :style="maskStyleFor(mailOpenIcon)"></span>
                        {{ t('Mark all read') }}
                    </button>
                </div>

                <!-- Ownership transfers -->
                <SurfaceCard v-if="ownershipTransfers && ownershipTransfers.length > 0" :padded="false">
                    <div class="flex items-center gap-3 px-5 pt-5">
                        <IconTile :icon="crownIcon" size="sm" tone="accent" />
                        <div class="min-w-0">
                            <h3 class="text-sm font-semibold text-sand-900 dark:text-sand-100">{{ t('Ownership transfers') }}</h3>
                            <p class="text-xs text-sand-500 dark:text-sand-400">
                                {{ t(':count pending', { count: ownershipTransfers.length }) }}
                            </p>
                        </div>
                    </div>
                    <ul class="mt-4 divide-y divide-sand-100/80 dark:divide-sand-700/50">
                        <li
                            v-for="transfer in ownershipTransfers"
                            :key="`transfer-${transfer.id}`"
                            class="flex items-start gap-3 px-5 py-4"
                        >
                            <div class="flex-shrink-0">
                                <img
                                    v-if="transfer.from_user.avatar"
                                    :src="transfer.from_user.avatar"
                                    :alt="transfer.from_user.name"
                                    class="size-11 rounded-full object-cover shadow-sm ring-2 ring-white dark:ring-sand-800"
                                />
                                <div v-else class="flex size-11 items-center justify-center rounded-full bg-sand-100 ring-2 ring-white dark:bg-sand-700 dark:ring-sand-800">
                                    <IconTile :icon="userIcon" size="sm" tone="sand" />
                                </div>
                            </div>

                            <div class="min-w-0 flex-1">
                                <p class="text-sm text-sand-900 dark:text-sand-100">
                                    {{  t(':name wants to transfer ownership of :circle to you', { name: transfer.from_user.name,  circle: transfer.circle.name }) }}
                                </p>
                                <p class="mt-0.5 text-xs text-sand-500 dark:text-sand-400">
                                    {{ timeAgo(transfer.created_at) }}
                                </p>
                                <div class="mt-3 flex gap-2">
                                    <button
                                        class="rounded-full bg-teal px-4 py-1.5 text-xs font-semibold text-white shadow-sm transition hover:bg-teal/90"
                                        @click="acceptTransfer(transfer.id)"
                                    >
                                        {{ t('Accept') }}
                                    </button>
                                    <button
                                        class="rounded-full bg-sand-100 px-4 py-1.5 text-xs font-semibold text-sand-700 transition hover:bg-sand-200 dark:bg-sand-700/60 dark:text-sand-200 dark:hover:bg-sand-700"
                                        @click="declineTransfer(transfer.id)"
                                    >
                                        {{ t('Decline') }}
                                    </button>
                                </div>
                            </div>
                        </li>
                    </ul>
                </SurfaceCard>

                <!-- Circle invitations -->
                <SurfaceCard v-if="circleInvitations && circleInvitations.length > 0" :padded="false">
                    <div class="flex items-center gap-3 px-5 pt-5">
                        <IconTile :icon="mailGiftIcon" size="sm" tone="accent" />
                        <div class="min-w-0">
                            <h3 class="text-sm font-semibold text-sand-900 dark:text-sand-100">{{ t('Circle invitations') }}</h3>
                            <p class="text-xs text-sand-500 dark:text-sand-400">
                                {{ t(':count pending', { count: circleInvitations.length }) }}
                            </p>
                        </div>
                    </div>
                    <ul class="mt-4 divide-y divide-sand-100/80 dark:divide-sand-700/50">
                        <li
                            v-for="invitation in circleInvitations"
                            :key="`invitation-${invitation.id}`"
                            class="flex items-start gap-3 px-5 py-4"
                        >
                            <div class="flex-shrink-0">
                                <img
                                    v-if="invitation.inviter.avatar"
                                    :src="invitation.inviter.avatar"
                                    :alt="invitation.inviter.name"
                                    class="size-11 rounded-full object-cover shadow-sm ring-2 ring-white dark:ring-sand-800"
                                />
                                <div v-else class="flex size-11 items-center justify-center rounded-full bg-sand-100 ring-2 ring-white dark:bg-sand-700 dark:ring-sand-800">
                                    <IconTile :icon="userIcon" size="sm" tone="sand" />
                                </div>
                            </div>

                            <div class="min-w-0 flex-1">
                                <p class="text-sm text-sand-900 dark:text-sand-100">
                                    <span class="font-semibold">{{ invitation.inviter.name }}</span>
                                    <span class="text-sand-600 dark:text-sand-400"> {{ t('invited you to') }} </span>
                                    <span class="font-semibold text-teal dark:text-sage-100">{{ invitation.circle.name }}</span>
                                </p>
                                <p class="mt-0.5 text-xs text-sand-500 dark:text-sand-400">
                                    {{ timeAgo(invitation.created_at) }}
                                </p>
                                <div class="mt-3 flex gap-2">
                                    <button
                                        class="rounded-full bg-teal px-4 py-1.5 text-xs font-semibold text-white shadow-sm transition hover:bg-teal/90"
                                        @click="acceptInvitation(invitation.id)"
                                    >
                                        {{ t('Accept') }}
                                    </button>
                                    <button
                                        class="rounded-full bg-sand-100 px-4 py-1.5 text-xs font-semibold text-sand-700 transition hover:bg-sand-200 dark:bg-sand-700/60 dark:text-sand-200 dark:hover:bg-sand-700"
                                        @click="declineInvitation(invitation.id)"
                                    >
                                        {{ t('Decline') }}
                                    </button>
                                </div>
                            </div>
                        </li>
                    </ul>
                </SurfaceCard>

                <!-- Loading skeletons -->
                <SurfaceCard v-if="isLoading" :padded="false">
                    <div class="divide-y divide-sand-100/80 dark:divide-sand-700/50">
                        <div v-for="n in 5" :key="n" class="flex items-start gap-3 px-5 py-4">
                            <div class="size-11 flex-shrink-0 animate-pulse rounded-full bg-sand-200 dark:bg-sand-700" />
                            <div class="min-w-0 flex-1 space-y-2">
                                <div class="h-3.5 animate-pulse rounded-full bg-sand-200 dark:bg-sand-700" :style="{ width: `${55 + n * 6}%` }" />
                                <div class="h-3 w-20 animate-pulse rounded-full bg-sand-200/70 dark:bg-sand-700/70" />
                            </div>
                        </div>
                    </div>
                </SurfaceCard>

                <!-- Grouped notifications -->
                <template v-else-if="groupedNotifications.length > 0">
                    <div v-for="group in groupedNotifications" :key="group.key" class="space-y-2">
                        <h2 class="px-2 text-xs font-semibold uppercase tracking-[0.15em] text-sand-500 dark:text-sand-400">
                            {{ group.label }}
                        </h2>
                        <SurfaceCard :padded="false">
                            <ul class="divide-y divide-sand-100/80 dark:divide-sand-700/50">
                                <li v-for="notification in group.items" :key="notification.id">
                                    <button
                                        class="flex w-full items-start gap-3 px-5 py-4 text-left transition"
                                        :class="{
                                            'bg-sage-50/60 dark:bg-sage-900/20': !isRead(notification),
                                        }"
                                        @click="openNotification(notification)"
                                    >
                                        <!-- Avatar with icon badge -->
                                        <div class="relative flex-shrink-0">
                                            <img
                                                v-if="notification.data.user_avatar"
                                                :src="notification.data.user_avatar"
                                                :alt="notification.data.user_name"
                                                class="size-11 rounded-full object-cover shadow-sm ring-2 ring-white dark:ring-sand-800"
                                            />
                                            <div v-else class="flex size-11 items-center justify-center rounded-full bg-sand-100 ring-2 ring-white dark:bg-sand-700 dark:ring-sand-800">
                                                <IconTile :icon="userIcon" size="sm" tone="sand" />
                                            </div>
                                            <span
                                                v-if="iconForType(notification.type).bare"
                                                class="absolute -bottom-0.5 -right-0.5 flex size-5 items-center justify-center"
                                                :class="bareToneClass[iconForType(notification.type).tone]"
                                            >
                                                <span aria-hidden="true" class="inline-block size-5 bg-current drop-shadow" :style="maskStyleFor(iconForType(notification.type).icon)"></span>
                                            </span>
                                            <span
                                                v-else
                                                class="absolute -bottom-1 -right-1 flex size-5 items-center justify-center rounded-md shadow-sm ring-2 ring-white dark:ring-sand-800"
                                                :class="filledToneClass[iconForType(notification.type).tone]"
                                            >
                                                <span aria-hidden="true" class="inline-block size-3 bg-current" :style="maskStyleFor(iconForType(notification.type).icon)"></span>
                                            </span>
                                        </div>

                                        <!-- Content -->
                                        <div class="min-w-0 flex-1">
                                            <p
                                                class="text-sm leading-snug text-sand-800 dark:text-sand-100"
                                                :class="{ 'font-semibold': !isRead(notification) }"
                                            >
                                                {{ notificationMessage(notification) }}
                                            </p>
                                            <p class="mt-1 text-xs text-sand-500 dark:text-sand-400">
                                                {{ timeAgo(notification.created_at) }}
                                            </p>
                                        </div>

                                        <!-- Thumbnail or unread dot -->
                                        <div class="flex flex-shrink-0 items-start gap-2 pt-1">
                                            <img
                                                v-if="notification.data.post_media_url"
                                                :src="notification.data.post_media_url"
                                                class="size-12 rounded-md object-cover shadow-sm"
                                                alt=""
                                            />
                                            <span
                                                v-if="!isRead(notification)"
                                                class="mt-1 inline-block size-2 rounded-full bg-teal"
                                                aria-hidden="true"
                                            />
                                        </div>
                                    </button>
                                </li>
                            </ul>
                        </SurfaceCard>
                    </div>
                </template>

                <!-- Load more / pagination footer -->
                <div v-if="!isLoading && hasMore" class="flex flex-col items-center gap-2 px-4 py-4">
                    <button
                        class="text-sm font-medium text-sand-500 disabled:opacity-50 dark:text-sand-400"
                        :disabled="isLoadingMore"
                        @click="loadMore"
                    >
                        {{ isLoadingMore ? t('Loading more...') : t('Load more') }}
                    </button>
                    <p v-if="loadMoreError" class="text-xs text-blush-500">{{ loadMoreError }}</p>
                </div>

                <!-- Empty state -->
                <SurfaceCard v-if="!isLoading && groupedNotifications.length === 0 && (!circleInvitations || circleInvitations.length === 0) && (!ownershipTransfers || ownershipTransfers.length === 0)" class="mt-4">
                    <div class="flex flex-col items-center justify-center py-10 text-center">
                        <IconTile :icon="bellIcon" size="lg" tone="sage" />
                        <h3 class="mt-4 font-display text-lg font-semibold text-teal dark:text-sage-100">
                            {{ t('No notifications yet') }}
                        </h3>
                        <p class="mt-1 max-w-xs text-sm text-sand-600 dark:text-sand-400">
                            {{ t("When someone interacts with your posts, you'll see it here.") }}
                        </p>
                    </div>
                </SurfaceCard>
            </div>
        </div>
    </AppLayout>
</template>
