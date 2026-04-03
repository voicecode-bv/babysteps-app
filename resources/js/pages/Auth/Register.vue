<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useTranslations } from '@/composables/useTranslations';

const { t } = useTranslations();

const form = useForm({
    email: '',
    name: '',
    username: '',
    password: '',
});

const showPassword = ref(false);

function submit() {
    // Backend volgt later
}
</script>

<template>
    <div class="flex min-h-dvh flex-col bg-sand-50 px-8 text-sand-900 dark:bg-sand-900 dark:text-sand-100">
        <div class="flex flex-1 flex-col items-center justify-center">
            <div class="mb-4 text-center">
                <h1 class="text-4xl font-bold tracking-tight text-sand-800 dark:text-sand-100">{{ t('Babysteps') }}</h1>
            </div>

            <p class="mb-6 max-w-xs text-center text-sm text-sand-500 dark:text-sand-400">
                {{ t('Share the most beautiful moments with your family and friends.') }}
            </p>

            <form class="w-full max-w-sm space-y-3" @submit.prevent="submit">
                <div>
                    <input
                        v-model="form.name"
                        type="text"
                        :placeholder="t('Your name')"
                        autocomplete="name"
                        class="w-full rounded-xl border border-sand-200 bg-white px-4 py-3.5 text-sm text-sand-800 placeholder-sand-400 shadow-sm focus:border-sage-400 focus:outline-none focus:ring-1 focus:ring-sage-400 dark:border-sand-700 dark:bg-sand-800 dark:text-sand-100 dark:placeholder-sand-500"
                    />
                </div>

                <div>
                    <input
                        v-model="form.email"
                        type="email"
                        :placeholder="t('Email address')"
                        autocomplete="email"
                        class="w-full rounded-xl border border-sand-200 bg-white px-4 py-3.5 text-sm text-sand-800 placeholder-sand-400 shadow-sm focus:border-sage-400 focus:outline-none focus:ring-1 focus:ring-sage-400 dark:border-sand-700 dark:bg-sand-800 dark:text-sand-100 dark:placeholder-sand-500"
                    />
                </div>

                <div>
                    <input
                        v-model="form.username"
                        type="text"
                        :placeholder="t('Username')"
                        autocomplete="username"
                        class="w-full rounded-xl border border-sand-200 bg-white px-4 py-3.5 text-sm text-sand-800 placeholder-sand-400 shadow-sm focus:border-sage-400 focus:outline-none focus:ring-1 focus:ring-sage-400 dark:border-sand-700 dark:bg-sand-800 dark:text-sand-100 dark:placeholder-sand-500"
                    />
                </div>

                <div class="relative">
                    <input
                        v-model="form.password"
                        :type="showPassword ? 'text' : 'password'"
                        :placeholder="t('Password')"
                        autocomplete="new-password"
                        class="w-full rounded-xl border border-sand-200 bg-white px-4 py-3.5 pr-16 text-sm text-sand-800 placeholder-sand-400 shadow-sm focus:border-sage-400 focus:outline-none focus:ring-1 focus:ring-sage-400 dark:border-sand-700 dark:bg-sand-800 dark:text-sand-100 dark:placeholder-sand-500"
                    />
                    <button
                        type="button"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-sm font-medium text-sand-400 dark:text-sand-500"
                        @click="showPassword = !showPassword"
                    >
                        {{ showPassword ? t('Hide') : t('Show') }}
                    </button>
                </div>

                <button
                    type="submit"
                    class="w-full rounded-xl bg-sage-500 py-3.5 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-sage-600 disabled:opacity-50 dark:bg-sage-400 dark:text-sand-900 dark:hover:bg-sage-300"
                    :disabled="!form.email || !form.name || !form.username || !form.password"
                >
                    {{ t('Create account') }}
                </button>
            </form>

            <p class="mt-5 max-w-xs text-center text-xs leading-relaxed text-sand-400 dark:text-sand-500">
                {{ t('By registering you agree to our Terms and Privacy Policy. Your data is safe with us.') }}
            </p>
        </div>

        <div class="border-t border-sand-200 pb-8 pt-4 dark:border-sand-800">
            <p class="text-center text-sm text-sand-500 dark:text-sand-400">
                {{ t('Already have an account?') }}
                <Link href="/login" class="font-semibold text-sage-600 dark:text-sage-400">{{ t('Log in') }}</Link>
            </p>
        </div>
    </div>
</template>
