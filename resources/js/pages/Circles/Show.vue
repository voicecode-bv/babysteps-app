<script setup lang="ts">
import Button from '@/components/Button.vue';
import IconTile from '@/components/IconTile.vue';
import PullToRefreshIndicator from '@/components/PullToRefreshIndicator.vue';
import SurfaceCard from '@/components/SurfaceCard.vue';
import { usePullToRefresh } from '@/composables/usePullToRefresh';
import { useTranslations } from '@/composables/useTranslations';
import AppLayout from '@/layouts/AppLayout.vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import { Camera, Dialog, Events, Off, On } from '@nativephp/mobile';
import { computed, onMounted, onUnmounted, ref, useTemplateRef } from 'vue';
import crownIcon from '../../../svg/doodle-icons/crown.svg';
import mailIcon from '../../../svg/doodle-icons/mail.svg';
import sendIcon from '../../../svg/doodle-icons/send.svg';
import userIcon from '../../../svg/doodle-icons/user.svg';
import userAddIcon from '../../../svg/doodle-icons/user-add.svg';
import userRemoveIcon from '../../../svg/doodle-icons/user-remove.svg';

interface Member {
    id: number;
    name: string;
    username: string;
    avatar: string | null;
    is_owner: boolean;
}

interface Invitation {
    id: number;
    email: string | null;
    username: string | null;
    created_at: string;
}

interface Circle {
    id: number;
    name: string;
    photo: string | null;
    members_count: number;
    members: Member[] | null;
    members_can_invite: boolean;
    is_owner: boolean;
    created_at: string;
}

const props = defineProps<{
    circle: Circle;
    invitations: Invitation[];
}>();

const { t } = useTranslations();

const layoutRef = useTemplateRef<InstanceType<typeof AppLayout>>('layout');
const containerRef = computed(() => layoutRef.value?.mainRef ?? null);

const { pullDistance, isRefreshing } = usePullToRefresh({
    onRefresh: () =>
        new Promise<void>((resolve) => {
            router.reload({
                only: ['circle', 'invitations'],
                onFinish: () => resolve(),
            });
        }),
    containerRef,
});

const isEditing = ref(false);
const inviteSent = ref(false);

const canInvite = computed(() => props.circle.is_owner || props.circle.members_can_invite);
const members = computed(() => props.circle.members ?? []);

const editForm = useForm({ name: props.circle.name });

function toggleMembersCanInvite() {
    router.put(
        `/circles/${props.circle.id}/settings`,
        { members_can_invite: !props.circle.members_can_invite },
        { preserveScroll: true },
    );
}
const memberForm = useForm({ identifier: '' });

function updateCircle() {
    editForm.put(`/circles/${props.circle.id}`, {
        onSuccess: () => {
            isEditing.value = false;
        },
    });
}

async function deleteCircle() {
    await Dialog.alert()
        .confirm(t('Delete circle'), t('Are you sure you want to delete this circle?'))
        .id('delete-circle-confirm');
}

async function leaveCircle() {
    await Dialog.alert()
        .confirm(t('Leave circle'), t('Are you sure you want to leave this circle?'))
        .id('leave-circle-confirm');
}

function addMember() {
    memberForm.post(`/circles/${props.circle.id}/members`, {
        onSuccess: () => {
            memberForm.reset();
            inviteSent.value = true;
            setTimeout(() => {
                inviteSent.value = false;
            }, 3000);
        },
    });
}

async function pickPhoto() {
    await Camera.pickImages().all();
}

function handleMediaSelected(payload: { success: boolean; files: { path: string; mimeType: string }[]; cancelled: boolean }) {
    if (!payload.success || payload.cancelled || !payload.files.length) return;
    router.post(`/circles/${props.circle.id}/photo`, { photo_path: payload.files[0].path }, { preserveScroll: true });
}

let pendingInvitationId: number | null = null;

async function cancelInvitation(invitationId: number) {
    pendingInvitationId = invitationId;
    await Dialog.alert()
        .confirm(t('Cancel invitation'), t('Are you sure you want to cancel this invitation?'))
        .id('cancel-invitation-confirm');
}

let pendingMemberId: number | null = null;

async function removeMember(userId: number) {
    pendingMemberId = userId;
    await Dialog.alert()
        .confirm(t('Remove member'), t('Are you sure you want to remove this member?'))
        .id('remove-member-confirm');
}

function handleButtonPressed(payload: { index: number; label: string; id?: string | null }) {
    if (payload.id === 'cancel-invitation-confirm' && payload.index === 1 && pendingInvitationId) {
        router.delete(`/circles/${props.circle.id}/invitations/${pendingInvitationId}`);
        pendingInvitationId = null;
    }
    if (payload.id === 'remove-member-confirm' && payload.index === 1 && pendingMemberId) {
        router.delete(`/circles/${props.circle.id}/members/${pendingMemberId}`);
        pendingMemberId = null;
    }
    if (payload.id === 'leave-circle-confirm' && payload.index === 1) {
        router.post(`/circles/${props.circle.id}/leave`);
    }
    if (payload.id === 'delete-circle-confirm' && payload.index === 1) {
        router.delete(`/circles/${props.circle.id}`);
    }
}

onMounted(() => {
    On(Events.Alert.ButtonPressed, handleButtonPressed);
    On(Events.Gallery.MediaSelected, handleMediaSelected);
});

onUnmounted(() => {
    Off(Events.Alert.ButtonPressed, handleButtonPressed);
    Off(Events.Gallery.MediaSelected, handleMediaSelected);
});

function goBack() {
    router.visit('/circles');
}
</script>

<template>
    <AppLayout ref="layout" :title="circle.name">
        <template #header-left>
            <button class="text-teal dark:text-sand-300" @click="goBack">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                </svg>
            </button>
        </template>
        <template #header-right>
            <button
                v-if="circle.is_owner"
                class="flex size-9 items-center justify-center rounded-full bg-white/80 text-teal shadow-sm transition hover:bg-white dark:bg-sand-800/70 dark:text-sand-200"
                :aria-label="t('Edit circle')"
                @click="isEditing = !isEditing"
            >
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-4">
                    <path v-if="!isEditing" stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" />
                    <path v-else stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                </svg>
            </button>
        </template>

        <div class="relative mt-10 min-h-full bg-warmwhite pb-[calc(theme(spacing.24)+env(safe-area-inset-bottom))] dark:bg-sand-900">
            <!-- Soft blobs -->
            <div aria-hidden="true" class="pointer-events-none absolute inset-x-0 top-0 h-72 overflow-hidden">
                <div class="absolute -left-16 top-0 size-64 rounded-full bg-sage-200/40 blur-3xl dark:bg-sage-700/20"></div>
                <div class="absolute -right-16 top-10 size-64 rounded-full bg-accent-soft/30 blur-3xl dark:bg-accent/10"></div>
            </div>

            <PullToRefreshIndicator :pull-distance="pullDistance" :is-refreshing="isRefreshing" />

            <div class="relative space-y-4 px-4 pt-4">
                <!-- Hero Card -->
                <SurfaceCard>
                    <div class="text-center">
                        <button
                            class="relative mx-auto block"
                            :disabled="!circle.is_owner"
                            :aria-label="circle.is_owner ? t('Change circle photo') : undefined"
                            @click="circle.is_owner && pickPhoto()"
                        >
                            <img
                                v-if="circle.photo"
                                :src="circle.photo"
                                :alt="circle.name"
                                class="size-20 rounded-lg object-cover shadow-sm"
                            />
                            <IconTile v-else :icon="userIcon" size="lg" tone="sage" class="!size-20" />
                            <span
                                v-if="circle.is_owner"
                                class="absolute -bottom-1 -right-1 flex size-8 items-center justify-center rounded-full bg-teal shadow-md ring-4 ring-white/70 dark:ring-sand-800/60"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-4 text-white">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0Z" />
                                </svg>
                            </span>
                        </button>
                        <h2 class="mt-4 font-display text-2xl font-semibold text-teal dark:text-sand-100">{{ circle.name }}</h2>
                        <p class="mt-1 text-sm text-sand-600 dark:text-sand-400">
                            {{ circle.members_count === 1 ? t(':count member', { count: circle.members_count }) : t(':count members', { count: circle.members_count }) }}
                        </p>
                    </div>
                </SurfaceCard>

                <!-- Edit Panel -->
                <Transition
                    enter-active-class="transition duration-200 ease-out"
                    enter-from-class="-translate-y-2 opacity-0"
                    enter-to-class="translate-y-0 opacity-100"
                    leave-active-class="transition duration-150 ease-in"
                    leave-from-class="translate-y-0 opacity-100"
                    leave-to-class="-translate-y-2 opacity-0"
                >
                    <SurfaceCard v-if="isEditing">
                        <form class="space-y-3" @submit.prevent="updateCircle">
                            <label class="text-xs font-medium uppercase tracking-wider text-sand-500 dark:text-sand-400">
                                {{ t('Circle name') }}
                            </label>
                            <input
                                v-model="editForm.name"
                                type="text"
                                class="field"
                            />
                            <p v-if="editForm.errors.name" class="text-xs text-accent">{{ editForm.errors.name }}</p>
                            <div class="flex justify-end">
                                <Button
                                    type="submit"
                                    size="md"
                                    :disabled="editForm.processing || !editForm.name.trim()"
                                >
                                    {{ t('Save') }}
                                </Button>
                            </div>
                        </form>

                        <div class="mt-5 border-t border-sand-100 pt-4 dark:border-sand-700/60">
                            <label class="flex cursor-pointer items-center justify-between gap-3">
                                <span>
                                    <span class="block text-sm font-semibold text-sand-900 dark:text-sand-100">{{ t('Members can invite others') }}</span>
                                    <span class="block text-xs text-sand-500 dark:text-sand-400">{{ t('Allow everyone in this circle to send invitations.') }}</span>
                                </span>
                                <button
                                    type="button"
                                    role="switch"
                                    :aria-checked="circle.members_can_invite"
                                    :class="circle.members_can_invite ? 'bg-teal' : 'bg-sand-300 dark:bg-sand-600'"
                                    class="relative inline-flex h-8 w-14 shrink-0 cursor-pointer rounded-full transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-teal/40"
                                    @click="toggleMembersCanInvite"
                                >
                                    <span
                                        :class="circle.members_can_invite ? 'translate-x-7' : 'translate-x-1'"
                                        class="pointer-events-none mt-1 size-6 rounded-full bg-white shadow transition-transform"
                                    />
                                </button>
                            </label>
                        </div>
                    </SurfaceCard>
                </Transition>

                <!-- Invite Section -->
                <SurfaceCard v-if="canInvite">
                    <h3 class="flex items-center gap-3 text-sm font-semibold text-sand-900 dark:text-sand-100">
                        <IconTile :icon="userAddIcon" size="sm" tone="sage" />
                        {{ t('Invite someone') }}
                    </h3>

                    <Transition
                        enter-active-class="transition duration-200 ease-out"
                        enter-from-class="opacity-0"
                        enter-to-class="opacity-100"
                        leave-active-class="transition duration-150 ease-in"
                        leave-from-class="opacity-100"
                        leave-to-class="opacity-0"
                        mode="out-in"
                    >
                        <div v-if="inviteSent" class="mt-3 flex items-center gap-2 rounded-lg bg-sage-100/70 px-4 py-3 text-sm font-medium text-sage-700 dark:bg-sage-800/40 dark:text-sage-300">
                            <IconTile :icon="sendIcon" size="sm" tone="sage" class="!size-8" />
                            {{ t('Invitation sent!') }}
                        </div>
                        <form v-else class="mt-3 space-y-3" @submit.prevent="addMember">
                            <input
                                :value="memberForm.identifier"
                                type="text"
                                :placeholder="t('Username or email...')"
                                class="field"
                                @input="memberForm.identifier = ($event.target as HTMLInputElement).value.toLowerCase()"
                            />
                            <p v-if="memberForm.errors.identifier" class="text-xs text-accent">{{ memberForm.errors.identifier }}</p>
                            <div class="flex justify-end">
                                <Button
                                    type="submit"
                                    size="md"
                                    :disabled="memberForm.processing || !memberForm.identifier.trim()"
                                >
                                    {{ t('Invite') }}
                                </Button>
                            </div>
                        </form>
                    </Transition>
                </SurfaceCard>

                <!-- Members Card -->
                <SurfaceCard v-if="members.length > 0">
                    <h3 class="flex items-center gap-3 text-sm font-semibold text-sand-900 dark:text-sand-100">
                        <IconTile :icon="userIcon" size="sm" tone="sage" />
                        {{ t('Members') }}
                    </h3>
                    <ul class="mt-3 divide-y divide-sand-100 dark:divide-sand-700/60">
                        <li v-for="member in members" :key="member.id" class="flex items-center gap-3 py-3 first:pt-0 last:pb-0">
                            <Link :href="`/profiles/${member.username}`" class="flex min-w-0 flex-1 items-center gap-3">
                                <img
                                    :src="member.avatar ?? `https://ui-avatars.com/api/?name=${member.name}&background=f0dcc6&color=5c3f24&size=64`"
                                    :alt="member.name"
                                    class="size-11 shrink-0 rounded-lg object-cover"
                                />
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-semibold text-sand-900 dark:text-sand-100">{{ member.name }}</p>
                                    <p class="truncate text-xs text-sand-500 dark:text-sand-400">@{{ member.username }}</p>
                                </div>
                            </Link>
                            <span
                                v-if="member.is_owner"
                                :title="t('Owner')"
                                class="inline-flex items-center gap-1 rounded-full bg-accent-soft/30 px-2.5 py-1 text-xs font-medium text-accent dark:bg-accent/20"
                            >
                                <span aria-hidden="true" class="inline-block size-3.5 bg-current" :style="{
                                    maskImage: `url(${crownIcon})`,
                                    WebkitMaskImage: `url(${crownIcon})`,
                                    maskSize: 'contain',
                                    WebkitMaskSize: 'contain',
                                    maskRepeat: 'no-repeat',
                                    WebkitMaskRepeat: 'no-repeat',
                                    maskPosition: 'center',
                                    WebkitMaskPosition: 'center',
                                }"></span>
                                {{ t('Owner') }}
                            </span>
                            <button
                                v-else-if="circle.is_owner"
                                class="flex size-9 items-center justify-center rounded-lg text-sand-500 transition hover:bg-blush-50 hover:text-blush-500 dark:text-sand-400 dark:hover:bg-blush-900/30"
                                :aria-label="t('Remove member')"
                                @click="removeMember(member.id)"
                            >
                                <span aria-hidden="true" class="inline-block size-5 bg-current" :style="{
                                    maskImage: `url(${userRemoveIcon})`,
                                    WebkitMaskImage: `url(${userRemoveIcon})`,
                                    maskSize: 'contain',
                                    WebkitMaskSize: 'contain',
                                    maskRepeat: 'no-repeat',
                                    WebkitMaskRepeat: 'no-repeat',
                                    maskPosition: 'center',
                                    WebkitMaskPosition: 'center',
                                }"></span>
                            </button>
                        </li>
                    </ul>
                </SurfaceCard>

                <!-- Pending Invitations -->
                <SurfaceCard v-if="invitations.length > 0">
                    <h3 class="flex items-center gap-3 text-sm font-semibold text-sand-900 dark:text-sand-100">
                        <IconTile :icon="mailIcon" size="sm" tone="sage" />
                        {{ t('Pending invitations') }}
                    </h3>
                    <ul class="mt-3 divide-y divide-sand-100 dark:divide-sand-700/60">
                        <li v-for="invitation in invitations" :key="invitation.id" class="flex items-center gap-3 py-3 first:pt-0 last:pb-0">
                            <IconTile :icon="mailIcon" size="sm" tone="sand" />
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold text-sand-900 dark:text-sand-100">
                                    {{ invitation.username ? `@${invitation.username}` : invitation.email }}
                                </p>
                                <p class="text-xs text-sand-500 dark:text-sand-400">{{ t('Pending') }}</p>
                            </div>
                            <button
                                class="flex size-9 items-center justify-center rounded-lg text-sand-500 transition hover:bg-blush-50 hover:text-blush-500 dark:text-sand-400 dark:hover:bg-blush-900/30"
                                :aria-label="t('Cancel invitation')"
                                @click="cancelInvitation(invitation.id)"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </li>
                    </ul>
                </SurfaceCard>

                <!-- Empty Members State -->
                <SurfaceCard v-if="members.length === 0 && invitations.length === 0">
                    <div class="flex flex-col items-center px-2 py-4 text-center">
                        <IconTile :icon="userAddIcon" size="lg" tone="sage" class="mb-4" />
                        <h3 class="font-display text-lg font-semibold text-teal dark:text-sand-100">{{ t('No members yet') }}</h3>
                        <p class="mt-1 text-sm text-sand-600 dark:text-sand-400">
                            {{ t('Add people by their username or invite them by email.') }}
                        </p>
                    </div>
                </SurfaceCard>

                <!-- Danger zone -->
                <div class="pt-2">
                    <Button
                        v-if="!circle.is_owner"
                        variant="danger"
                        size="lg"
                        block
                        @click="leaveCircle"
                    >
                        {{ t('Leave circle') }}
                    </Button>
                    <Button
                        v-else
                        variant="danger"
                        size="lg"
                        block
                        @click="deleteCircle"
                    >
                        {{ t('Delete circle') }}
                    </Button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
