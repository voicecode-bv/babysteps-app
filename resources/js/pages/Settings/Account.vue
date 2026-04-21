<script setup lang="ts">
import Button from '@/components/Button.vue';
import { useTranslations } from '@/composables/useTranslations';
import AppLayout from '@/layouts/AppLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import { Dialog, Events, Off, On } from '@nativephp/mobile';
import { onMounted, onUnmounted, ref } from 'vue';
import userDeleteIcon from '../../../svg/doodle-icons/user-delete.svg';

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

        <div class="relative mt-10 min-h-full bg-warmwhite pb-[calc(theme(spacing.40)+env(safe-area-inset-bottom))] dark:bg-sand-900">
            <!-- Soft blobs -->
            <div aria-hidden="true" class="pointer-events-none absolute inset-x-0 top-0 h-72 overflow-hidden">
                <div class="absolute -left-16 top-0 size-64 rounded-full bg-sage-200/40 blur-3xl dark:bg-sage-700/20"></div>
                <div class="absolute -right-16 top-10 size-64 rounded-full bg-accent-soft/30 blur-3xl dark:bg-accent/10"></div>
            </div>

            <div class="relative space-y-5 px-4 pt-4 pb-24">
                <section class="rounded-[2rem] bg-white/90 p-5 shadow-sm backdrop-blur-sm dark:border-sand-700/50 dark:bg-sand-800/70">
                    <h3 class="flex items-center gap-2 text-base font-semibold text-sand-900 dark:text-sand-100">
                        <span aria-hidden="true" class="inline-block size-6 bg-current" :style="iconMaskStyle(userDeleteIcon)"></span>
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
                </section>
            </div>
        </div>
    </AppLayout>
</template>
