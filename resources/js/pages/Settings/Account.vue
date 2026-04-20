<script setup lang="ts">
import Button from '@/components/Button.vue';
import { useTranslations } from '@/composables/useTranslations';
import AppLayout from '@/layouts/AppLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import { Dialog, Events, Off, On } from '@nativephp/mobile';
import { onMounted, onUnmounted, ref } from 'vue';

const { t } = useTranslations();

const isDeleting = ref(false);

async function confirmDelete() {
    await Dialog.alert()
        .confirm(
            t('Delete account'),
            t('This cannot be undone. Your profile will be anonymized and your photos and videos will be permanently deleted.'),
        )
        .id('delete-account-confirm');
}

function handleButtonPressed(payload: { index: number; id?: string | null }) {
    if (payload.id === 'delete-account-confirm' && payload.index === 1) {
        isDeleting.value = true;
        router.delete('/account', {
            onFinish: () => {
                isDeleting.value = false;
            },
        });
    }
}

onMounted(() => On(Events.Alert.ButtonPressed, handleButtonPressed));
onUnmounted(() => Off(Events.Alert.ButtonPressed, handleButtonPressed));
</script>

<template>
    <AppLayout :title="t('Account')">
        <template #header-left>
            <Link :href="'/settings'" class="text-sm text-teal">{{ t('Back') }}</Link>
        </template>

        <div class="mt-10 px-4 pb-24">
            <div class="bg-white px-4 py-6 dark:bg-sand-900">
                <h2 class="font-display text-lg font-semibold text-sand-800 dark:text-sand-100">
                    {{ t('Delete account') }}
                </h2>

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
                    block
                    class="mt-8"
                    :disabled="isDeleting"
                    @click="confirmDelete"
                >
                    {{ t('Delete my account') }}
                </Button>
            </div>
        </div>
    </AppLayout>
</template>
