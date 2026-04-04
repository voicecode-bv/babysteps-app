<script setup lang="ts">
import { useTranslations } from '@/composables/useTranslations';
import AppLayout from '@/layouts/AppLayout.vue';
import { router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

interface Member {
    id: number;
    name: string;
    username: string;
    avatar: string | null;
}

interface Invitation {
    id: number;
    email: string;
    created_at: string;
}

interface Circle {
    id: number;
    name: string;
    members_count: number;
    members: Member[];
    created_at: string;
}

const props = defineProps<{
    circle: Circle;
    invitations: Invitation[];
}>();

const { t } = useTranslations();

const isEditing = ref(false);
const showAddMember = ref(false);

const showInviteByEmail = ref(false);
const inviteSent = ref(false);

const editForm = useForm({ name: props.circle.name });
const memberForm = useForm({ username: '' });
const inviteForm = useForm({ email: '' });

function updateCircle() {
    editForm.put(`/circles/${props.circle.id}`, {
        onSuccess: () => {
            isEditing.value = false;
        },
    });
}

function deleteCircle() {
    router.delete(`/circles/${props.circle.id}`);
}

function addMember() {
    memberForm.post(`/circles/${props.circle.id}/members`, {
        onSuccess: () => {
            memberForm.reset();
            showAddMember.value = false;
            showInviteByEmail.value = false;
        },
        onError: () => {
            showInviteByEmail.value = true;
        },
    });
}

function inviteMember() {
    inviteForm.post(`/circles/${props.circle.id}/invitations`, {
        onSuccess: () => {
            inviteForm.reset();
            memberForm.reset();
            showInviteByEmail.value = false;
            inviteSent.value = true;
            setTimeout(() => {
                inviteSent.value = false;
                showAddMember.value = false;
            }, 3000);
        },
    });
}

function resetAddMember() {
    showAddMember.value = !showAddMember.value;
    showInviteByEmail.value = false;
    inviteSent.value = false;
    memberForm.reset();
    memberForm.clearErrors();
    inviteForm.reset();
    inviteForm.clearErrors();
}

function cancelInvitation(invitationId: number) {
    router.delete(`/circles/${props.circle.id}/invitations/${invitationId}`);
}

function removeMember(userId: number) {
    router.delete(`/circles/${props.circle.id}/members/${userId}`);
}

function goBack() {
    router.visit('/circles');
}
</script>

<template>
    <AppLayout :title="circle.name">
        <template #header-left>
            <button class="text-sand-700 dark:text-sand-300" @click="goBack">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                </svg>
            </button>
        </template>
        <template #header-right>
            <button class="text-sand-500 dark:text-sand-400" @click="isEditing = !isEditing">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" />
                </svg>
            </button>
        </template>

        <!-- Edit Circle -->
        <div v-if="isEditing" class="border-b border-sand-200 bg-white px-4 py-3 dark:border-sand-800 dark:bg-sand-900">
            <form class="flex items-center gap-2" @submit.prevent="updateCircle">
                <input
                    v-model="editForm.name"
                    type="text"
                    class="flex-1 rounded-lg border border-sand-200 bg-sand-50 px-3 py-2 text-sm text-sand-800 placeholder-sand-400 focus:border-sand-400 focus:outline-none dark:border-sand-700 dark:bg-sand-800 dark:text-sand-100 dark:placeholder-sand-500"
                />
                <button
                    type="submit"
                    class="rounded-lg bg-sand-500 px-4 py-2 text-sm font-medium text-white disabled:opacity-50 dark:bg-sand-600"
                    :disabled="editForm.processing || !editForm.name.trim()"
                >
                    {{ t('Save') }}
                </button>
            </form>
            <p v-if="editForm.errors.name" class="mt-1 text-xs text-blush-500">{{ editForm.errors.name }}</p>
            <button class="mt-2 text-xs font-medium text-blush-500" @click="deleteCircle">
                {{ t('Delete circle') }}
            </button>
        </div>

        <!-- Circle Info -->
        <div class="bg-white px-4 py-4 dark:bg-sand-900">
            <div class="flex items-center gap-3">
                <div class="flex size-14 items-center justify-center rounded-full bg-sand-200 dark:bg-sand-800">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.3" stroke="currentColor" class="size-7 text-sand-600 dark:text-sand-300">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-lg font-semibold text-sand-800 dark:text-sand-100">{{ circle.name }}</h2>
                    <p class="text-sm text-sand-500 dark:text-sand-400">
                        {{ circle.members_count === 1 ? t(':count member', { count: circle.members_count }) : t(':count members', { count: circle.members_count }) }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Members Section -->
        <div class="mt-2 bg-white dark:bg-sand-900">
            <div class="flex items-center justify-between border-b border-sand-100 px-4 py-3 dark:border-sand-800">
                <h3 class="text-sm font-semibold text-sand-700 dark:text-sand-300">{{ t('Members') }}</h3>
                <button class="text-sand-600 dark:text-sand-400" @click="resetAddMember">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM3 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 9.374 21c-2.331 0-4.512-.645-6.374-1.766Z" />
                    </svg>
                </button>
            </div>

            <!-- Add Member / Invite Form -->
            <div v-if="showAddMember" class="border-b border-sand-100 px-4 py-3 dark:border-sand-800">
                <!-- Invitation sent success -->
                <div v-if="inviteSent" class="flex items-center gap-2 text-sm text-sand-600 dark:text-sand-400">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                    {{ t('Invitation sent!') }}
                </div>

                <template v-else>
                    <!-- Username form -->
                    <form class="flex items-center gap-2" @submit.prevent="addMember">
                        <input
                            v-model="memberForm.username"
                            type="text"
                            :placeholder="t('Username...')"
                            class="flex-1 rounded-lg border border-sand-200 bg-sand-50 px-3 py-2 text-sm text-sand-800 placeholder-sand-400 focus:border-sand-400 focus:outline-none dark:border-sand-700 dark:bg-sand-800 dark:text-sand-100 dark:placeholder-sand-500"
                        />
                        <button
                            type="submit"
                            class="rounded-lg bg-sand-500 px-4 py-2 text-sm font-medium text-white disabled:opacity-50 dark:bg-sand-600"
                            :disabled="memberForm.processing || !memberForm.username.trim()"
                        >
                            {{ t('Add') }}
                        </button>
                    </form>
                    <p v-if="memberForm.errors.username" class="mt-1 text-xs text-blush-500">{{ memberForm.errors.username }}</p>

                    <!-- Invite by email (shown when username not found) -->
                    <div v-if="showInviteByEmail" class="mt-3 rounded-lg border border-sand-200 bg-sand-50 p-3 dark:border-sand-700 dark:bg-sand-800">
                        <p class="mb-2 text-xs text-sand-600 dark:text-sand-400">{{ t('User not found? Invite them by email.') }}</p>
                        <form class="flex items-center gap-2" @submit.prevent="inviteMember">
                            <input
                                v-model="inviteForm.email"
                                type="email"
                                :placeholder="t('Email address...')"
                                class="flex-1 rounded-lg border border-sand-200 bg-white px-3 py-2 text-sm text-sand-800 placeholder-sand-400 focus:border-sand-400 focus:outline-none dark:border-sand-700 dark:bg-sand-900 dark:text-sand-100 dark:placeholder-sand-500"
                            />
                            <button
                                type="submit"
                                class="rounded-lg bg-sand-500 px-4 py-2 text-sm font-medium text-white disabled:opacity-50 dark:bg-sand-600"
                                :disabled="inviteForm.processing || !inviteForm.email.trim()"
                            >
                                {{ t('Invite') }}
                            </button>
                        </form>
                        <p v-if="inviteForm.errors.email" class="mt-1 text-xs text-blush-500">{{ inviteForm.errors.email }}</p>
                    </div>
                </template>
            </div>

            <!-- Members List -->
            <div v-for="member in circle.members" :key="member.id" class="flex items-center gap-3 border-b border-sand-50 px-4 py-3 dark:border-sand-800">
                <img
                    :src="member.avatar ?? `https://ui-avatars.com/api/?name=${member.name}&background=f0dcc6&color=5c3f24&size=64`"
                    :alt="member.name"
                    class="size-10 rounded-full object-cover ring-2 ring-sand-200 dark:ring-sand-700"
                />
                <div class="flex-1">
                    <p class="text-sm font-semibold text-sand-800 dark:text-sand-100">{{ member.name }}</p>
                    <p class="text-xs text-sand-500 dark:text-sand-400">@{{ member.username }}</p>
                </div>
                <button class="text-sand-400 dark:text-sand-500" @click="removeMember(member.id)">
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
                        <p class="text-sm text-sand-800 dark:text-sand-100">{{ invitation.email }}</p>
                        <p class="text-xs text-sand-400 dark:text-sand-500">{{ t('Pending') }}</p>
                    </div>
                    <button class="text-sand-400 dark:text-sand-500" @click="cancelInvitation(invitation.id)">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </template>

            <!-- Empty Members State -->
            <div v-if="circle.members.length === 0 && invitations.length === 0" class="px-4 py-8 text-center">
                <p class="text-sm font-medium text-sand-600 dark:text-sand-300">{{ t('No members yet') }}</p>
                <p class="mt-1 text-sm text-sand-400 dark:text-sand-500">{{ t('Add people by their username or invite them by email.') }}</p>
            </div>
        </div>
    </AppLayout>
</template>
