<script setup lang="ts">
import { usePullToRefresh } from '@/composables/usePullToRefresh';
import { useTranslations } from '@/composables/useTranslations';
import AppLayout from '@/layouts/AppLayout.vue';
import Button from '@/components/Button.vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import { Camera, Dialog, On, Off, Events } from '@nativephp/mobile';
import { computed, onMounted, onUnmounted, ref, useTemplateRef } from 'vue';

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
        `/circles/${props.circle.id}`,
        { name: props.circle.name, members_can_invite: !props.circle.members_can_invite },
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
            <button class="text-sand-700 dark:text-sand-300" @click="goBack">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                </svg>
            </button>
        </template>
        <template #header-right>
            <button v-if="circle.is_owner" class="text-sand-500 dark:text-sand-400" @click="isEditing = !isEditing">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" />
                </svg>
            </button>
        </template>

        <!-- Pull to refresh indicator -->
        <div
            class="flex items-center justify-center overflow-hidden transition-[height] duration-200"
            :class="{ 'duration-0': pullDistance > 0 && !isRefreshing }"
            :style="{ height: `${pullDistance}px` }"
        >
            <svg
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="2"
                stroke="currentColor"
                class="size-6 text-sand-400 dark:text-sand-500"
                :class="{ 'animate-spin': isRefreshing }"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.992 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182M2.985 14.652"
                />
            </svg>
        </div>

        <!-- Edit Circle -->
        <div v-if="isEditing" class="space-y-4 border-b border-sand-200 bg-white px-4 py-4 dark:border-sand-800 dark:bg-sand-900">
            <form class="flex items-center gap-2" @submit.prevent="updateCircle">
                <input
                    v-model="editForm.name"
                    type="text"
                    class="field flex-1"
                />
                <Button
                    type="submit"
                    size="sm"
                    :disabled="editForm.processing || !editForm.name.trim()"
                >
                    {{ t('Save') }}
                </Button>
            </form>
            <p v-if="editForm.errors.name" class="-mt-2 text-xs text-blush-500">{{ editForm.errors.name }}</p>

            <label class="flex items-center justify-between gap-3">
                <span class="text-sm text-sand-700 dark:text-sand-200">{{ t('Members can invite others') }}</span>
                <button
                    type="button"
                    role="switch"
                    :aria-checked="circle.members_can_invite"
                    class="relative inline-flex h-6 w-11 flex-shrink-0 items-center rounded-full transition-colors"
                    :class="circle.members_can_invite ? 'bg-sage-600 dark:bg-sage-500' : 'bg-sand-300 dark:bg-sand-700'"
                    @click="toggleMembersCanInvite"
                >
                    <span
                        class="inline-block size-5 transform rounded-full bg-white shadow transition-transform"
                        :class="circle.members_can_invite ? 'translate-x-5' : 'translate-x-0.5'"
                    />
                </button>
            </label>
        </div>

        <!-- Circle Info -->
        <div class="bg-white px-4 py-4 dark:bg-sand-900">
            <div class="flex items-center gap-3">
                <button class="relative" :disabled="!circle.is_owner" @click="circle.is_owner && pickPhoto()">
                    <img
                        v-if="circle.photo"
                        :src="circle.photo"
                        :alt="circle.name"
                        class="size-14 rounded-full object-cover"
                    />
                    <div v-else class="flex size-14 items-center justify-center rounded-full bg-sage-100 dark:bg-sage-800">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.3" stroke="currentColor" class="size-7 text-sage-600 dark:text-sage-300">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                        </svg>
                    </div>
                    <div v-if="circle.is_owner" class="absolute bottom-0 right-0 flex size-5 items-center justify-center rounded-full bg-sage-600 ring-2 ring-white dark:ring-sand-900">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="size-3 text-white">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0Z" />
                        </svg>
                    </div>
                </button>
                <div>
                    <h2 class="font-display text-lg font-semibold text-sand-800 dark:text-sand-100">{{ circle.name }}</h2>
                    <p class="text-sm text-sand-500 dark:text-sand-400">
                        {{ circle.members_count === 1 ? t(':count member', { count: circle.members_count }) : t(':count members', { count: circle.members_count }) }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Members Section -->
        <div class="mt-2 bg-white dark:bg-sand-900">
            <div v-if="canInvite" class="border-b border-sand-100 px-4 py-3 dark:border-sand-800">
                <!-- Invitation sent success -->
                <div v-if="inviteSent" class="flex items-center gap-2 text-sm text-sage-600 dark:text-sage-400">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                    {{ t('Invitation sent!') }}
                </div>

                <template v-else>
                    <form class="flex items-center gap-2" @submit.prevent="addMember">
                        <input
                            v-model="memberForm.identifier"
                            type="text"
                            :placeholder="t('Username or email...')"
                            class="field flex-1"
                        />
                        <Button
                            type="submit"
                            size="sm"
                            :disabled="memberForm.processing || !memberForm.identifier.trim()"
                        >
                            {{ t('Invite') }}
                        </Button>
                    </form>
                    <p v-if="memberForm.errors.identifier" class="mt-1 text-xs text-blush-500">{{ memberForm.errors.identifier }}</p>
                </template>
            </div>

            <!-- Members List -->
            <div v-if="members.length > 0" class="border-b border-sand-100 px-4 py-2 dark:border-sand-800">
                <h3 class="text-sm font-semibold text-sand-700 dark:text-sand-300">{{ t('Members') }}</h3>
            </div>
            <div v-for="member in members" :key="member.id" class="flex items-center gap-3 border-b border-sand-50 px-4 py-3 dark:border-sand-800">
                <Link :href="`/profiles/${member.username}`" class="flex flex-1 items-center gap-3">
                    <img
                        :src="member.avatar ?? `https://ui-avatars.com/api/?name=${member.name}&background=e5ece5&color=3a573a&size=64`"
                        :alt="member.name"
                        class="size-10 rounded-full object-cover ring-2 ring-sage-200 dark:ring-sage-700"
                    />
                    <div class="flex-1">
                        <p class="text-sm font-semibold text-sand-800 dark:text-sand-100">{{ member.name }}</p>
                        <p class="text-xs text-sand-500 dark:text-sand-400">@{{ member.username }}</p>
                    </div>
                </Link>
                <span v-if="member.is_owner" :title="t('Owner')" class="text-sage-600 dark:text-sage-400">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-5">
                        <path fill-rule="evenodd" d="M2.25 6.75a.75.75 0 0 1 1.183-.611l4.794 3.424 3.045-6.45a.75.75 0 0 1 1.356 0l3.045 6.45 4.794-3.424a.75.75 0 0 1 1.176.795l-2.26 9.04A2.25 2.25 0 0 1 17.25 18H6.75a2.25 2.25 0 0 1-2.183-1.726l-2.26-9.04a.75.75 0 0 1-.057-.484Zm4.5 13.5a.75.75 0 0 1 .75-.75h9a.75.75 0 0 1 0 1.5h-9a.75.75 0 0 1-.75-.75Z" clip-rule="evenodd" />
                    </svg>
                </span>
                <button v-else-if="circle.is_owner" class="text-sand-400 dark:text-sand-500" @click="removeMember(member.id)">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M22 10.5h-6m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM4 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 10.374 21c-2.331 0-4.512-.645-6.374-1.766Z" />
                    </svg>
                </button>
            </div>

            <!-- Pending Invitations -->
            <template v-if="invitations.length > 0">
                <div class="border-b border-sand-100 px-4 py-2 dark:border-sand-800">
                    <h4 class="text-xs font-semibold uppercase tracking-wide text-sand-400 dark:text-sand-500">{{ t('Pending invitations') }}</h4>
                </div>
                <div v-for="invitation in invitations" :key="invitation.id" class="flex items-center gap-3 border-b border-sand-50 px-4 py-3 dark:border-sand-800">
                    <div class="flex size-10 items-center justify-center rounded-full bg-sand-100 dark:bg-sand-800">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5 text-sand-400 dark:text-sand-500">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm text-sand-800 dark:text-sand-100">{{ invitation.username ? `@${invitation.username}` : invitation.email }}</p>
                        <p class="text-xs text-sand-400 dark:text-sand-500">{{ t('Pending') }}</p>
                    </div>
                    <button class="text-sand-400 dark:text-sand-500" @click="cancelInvitation(invitation.id)">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </template>

            <!-- Leave Circle -->
            <div v-if="!circle.is_owner" class="border-t border-sand-100 px-4 py-4 dark:border-sand-800">
                <button class="text-sm font-medium text-blush-500" @click="leaveCircle">
                    {{ t('Leave circle') }}
                </button>
            </div>

            <!-- Delete Circle -->
            <div v-if="circle.is_owner" class="border-t border-sand-100 px-4 py-4 dark:border-sand-800">
                <button class="text-sm font-medium text-blush-500" @click="deleteCircle">
                    {{ t('Delete circle') }}
                </button>
            </div>

            <!-- Empty Members State -->
            <div v-if="members.length === 0 && invitations.length === 0" class="px-4 py-8 text-center">
                <p class="text-sm font-medium text-sand-600 dark:text-sand-300">{{ t('No members yet') }}</p>
                <p class="mt-1 text-sm text-sand-400 dark:text-sand-500">{{ t('Add people by their username or invite them by email.') }}</p>
            </div>
        </div>
    </AppLayout>
</template>
