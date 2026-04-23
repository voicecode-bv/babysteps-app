<script setup lang="ts">
import { useTranslations } from '@/composables/useTranslations';
import { router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import userAddIcon from '../../../svg/doodle-icons/user-add.svg';
import userIcon from '../../../svg/doodle-icons/user.svg';

interface Circle {
    id: number;
    name: string;
}

const props = defineProps<{
    circle: Circle;
}>();

const { t } = useTranslations();

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

const form = useForm({ identifier: '' });
const invited = ref<string[]>([]);

function submit() {
    const id = form.identifier.trim();

    if (!id) {
        return;
    }

    form.post(`/onboarding/circles/${props.circle.id}/invite`, {
        preserveScroll: true,
        onSuccess: () => {
            if (!invited.value.includes(id)) {
                invited.value = [id, ...invited.value];
            }
            form.reset('identifier');
        },
    });
}

function continueOnboarding() {
    router.visit('/onboarding/notifications');
}
</script>

<template>
    <div class="nativephp-safe-area relative flex min-h-dvh flex-col overflow-hidden bg-warmwhite px-6 text-sand-900 dark:bg-sand-900 dark:text-sand-100">
        <!-- Soft colored blobs -->
        <div aria-hidden="true" class="pointer-events-none absolute inset-0 overflow-hidden">
            <div class="absolute -left-24 -top-24 size-72 rounded-full bg-sage-200/60 blur-3xl dark:bg-sage-700/20"></div>
            <div class="absolute -right-28 top-1/3 size-80 rounded-full bg-accent-soft/40 blur-3xl dark:bg-accent/10"></div>
            <div class="absolute -bottom-32 left-1/4 size-96 rounded-full bg-sand-200/50 blur-3xl dark:bg-sand-700/30"></div>
        </div>

        <div class="relative flex flex-1 flex-col items-center justify-center py-12">
            <div class="mb-10 text-center">
                <p class="text-xs font-medium uppercase tracking-widest text-accent">{{ circle.name }}</p>
                <h1 class="mt-3 font-display text-4xl font-black tracking-tight text-teal">
                    {{ t('Invite your people') }}
                </h1>
                <p class="mt-3 text-sm text-sand-600 dark:text-sand-400">
                    {{ t('Add family or friends to this circle. They will be able to see what you share.') }}
                </p>
            </div>

            <div class="w-full max-w-sm space-y-5">
                <form class="relative rounded-lg bg-white/50 p-5 shadow-sm backdrop-blur-sm dark:border-sand-700/50 dark:bg-sand-800/60" @submit.prevent="submit">
                    <label for="invite-identifier" class="text-xs font-medium uppercase tracking-wider text-sand-500 dark:text-sand-400">
                        {{ t('Username or email') }}
                    </label>
                    <div class="mt-3 flex items-center gap-3">
                        <div class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-sage-100 text-teal dark:bg-sage-900/40">
                            <span aria-hidden="true" class="inline-block size-6 bg-current" :style="iconMaskStyle(userAddIcon)"></span>
                        </div>
                        <input
                            id="invite-identifier"
                            v-model="form.identifier"
                            type="text"
                            :placeholder="t('Username or email...')"
                            autocapitalize="none"
                            autocomplete="off"
                            class="min-w-0 flex-1 border-0 bg-transparent p-0 text-base font-medium text-sand-900 placeholder-sand-400 focus:outline-none focus:ring-0 dark:text-sand-100 dark:placeholder-sand-500"
                        />
                        <button
                            type="submit"
                            :aria-label="t('Add')"
                            :disabled="!form.identifier.trim() || form.processing"
                            class="flex size-10 shrink-0 items-center justify-center rounded-full bg-teal text-white shadow-sm transition hover:bg-teal-light disabled:opacity-40"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="size-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                        </button>
                    </div>
                    <p v-if="form.errors.identifier" class="mt-2 text-xs text-blush-500">{{ form.errors.identifier }}</p>
                </form>

                <div v-if="invited.length > 0">
                    <div class="mb-3 flex items-center justify-between">
                        <p class="text-xs font-medium uppercase tracking-wider text-sand-500 dark:text-sand-400">
                            {{ t('Invited') }}
                        </p>
                        <span class="inline-flex size-6 items-center justify-center rounded-full bg-teal text-xs font-semibold leading-none text-white shadow-sm">
                            {{ invited.length }}
                        </span>
                    </div>
                    <ul class="space-y-2">
                        <li
                            v-for="identifier in invited"
                            :key="identifier"
                            class="flex items-center gap-3 rounded-full bg-white/70 px-4 py-2.5 shadow-sm dark:border-sand-700/50 dark:bg-sand-800/60"
                        >
                            <span aria-hidden="true" class="inline-block size-5 shrink-0 bg-teal" :style="iconMaskStyle(userIcon)"></span>
                            <span class="truncate text-sm font-medium text-sand-800 dark:text-sand-100">{{ identifier }}</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="relative pb-8 pt-2">
            <button
                class="flex w-full items-center justify-center gap-2 rounded-full bg-teal py-3.5 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-teal-light"
                @click="continueOnboarding"
            >
                <span>{{ invited.length > 0 ? t('Continue') : t('Invite later') }}</span>
                <span
                    v-if="invited.length > 0"
                    class="inline-flex size-5 items-center justify-center rounded-full bg-white/20 text-xs font-semibold leading-none"
                >
                    {{ invited.length }}
                </span>
            </button>
        </div>
    </div>
</template>
