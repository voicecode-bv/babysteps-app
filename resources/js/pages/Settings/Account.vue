<script setup lang="ts">
import Button from '@/components/Button.vue';
import IconTile from '@/components/IconTile.vue';
import SurfaceCard from '@/components/SurfaceCard.vue';
import { useTranslations } from '@/composables/useTranslations';
import AppLayout from '@/layouts/AppLayout.vue';
import { router, usePage } from '@inertiajs/vue3';
import { Dialog, Events, Off, On } from '@nativephp/mobile';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import userDeleteIcon from '../../../svg/doodle-icons/user-delete.svg';

const { t } = useTranslations();

const page = usePage<{ errors: Record<string, string> }>();
const accountError = computed(() => page.props.errors?.account ?? null);

const isDeleting = ref(false);

function goBack() {
    router.visit('/settings');
}

async function confirmDelete() {
    await Dialog.alert()
        .confirm(
            t('Delete account'),
            t('This cannot be undone. Your profile will be anonymized and your photos and videos will be permanently deleted.'),
        )
        .id('delete-account-confirm');
}

function handleButtonPressed(payload: { index: number; id?: string | null }) {
    if (payload.id !== 'delete-account-confirm' || payload.index !== 1) {
        return;
    }

    isDeleting.value = true;
    router.delete('/account', {
        preserveScroll: true,
        onFinish: () => {
            isDeleting.value = false;
        },
    });
}

onMounted(() => On(Events.Alert.ButtonPressed, handleButtonPressed));
onUnmounted(() => Off(Events.Alert.ButtonPressed, handleButtonPressed));
</script>

<template>
    <AppLayout :title="t('Account')">
        <template #header-left>
            <button class="flex items-center text-teal dark:text-sand-300" @click="goBack">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                </svg>
            </button>
        </template>

        <div class="relative mt-10 min-h-full bg-warmwhite pb-[calc(theme(spacing.40)+env(safe-area-inset-bottom))] dark:bg-sand-900">
            <!-- Soft blobs -->
            <div aria-hidden="true" class="pointer-events-none absolute inset-x-0 top-0 h-72 overflow-hidden">
                <div class="absolute -left-16 top-0 size-64 rounded-full bg-sage-200/40 blur-3xl dark:bg-sage-700/20"></div>
                <div class="absolute -right-16 top-10 size-64 rounded-full bg-accent-soft/30 blur-3xl dark:bg-accent/10"></div>
            </div>

            <div class="relative space-y-4 px-4 pt-4 pb-24">
                <SurfaceCard>
                    <h3 class="flex items-center gap-3 text-sm font-semibold text-sand-900 dark:text-sand-100">
                        <IconTile :icon="userDeleteIcon" size="sm" tone="sage" />
                        {{ t('Delete account') }}
                    </h3>

                    <p class="mt-3 text-sm text-sand-700 dark:text-sand-300">
                        {{ t('Deleting your account removes your personal data in line with GDPR. The following happens:') }}
                    </p>

                    <ul class="mt-3 list-disc space-y-1 pl-5 text-sm text-sand-700 dark:text-sand-300">
                        <li>{{ t('Your name, username, email, avatar and bio are permanently removed.') }}</li>
                        <li>{{ t('All of your posts, including photos and videos, are permanently deleted.') }}</li>
                        <li>{{ t('All of your comments and likes are permanently deleted.') }}</li>
                        <li>{{ t('Circles you created are deleted along with everything in them.') }}</li>
                        <li>{{ t('This action cannot be undone.') }}</li>
                    </ul>

                    <Button
                        variant="danger"
                        size="lg"
                        block
                        class="mt-6"
                        :disabled="isDeleting"
                        @click="confirmDelete"
                    >
                        {{ t('Delete my account') }}
                    </Button>

                    <p v-if="accountError" class="mt-4 rounded-lg bg-blush-50 p-3 text-sm text-blush-700 dark:bg-blush-900/30 dark:text-blush-200">
                        {{ accountError }}
                    </p>
                </SurfaceCard>
            </div>
        </div>
    </AppLayout>
</template>
