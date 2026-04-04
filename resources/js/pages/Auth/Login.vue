<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useTranslations } from '@/composables/useTranslations';

const { t } = useTranslations();

const form = useForm({
    email: '',
    password: '',
});

const showPassword = ref(false);

function submit() {
    form.post('/login', {
        onFinish: () => form.reset('password'),
    });
}
</script>

<template>
    <div class="flex min-h-dvh flex-col bg-sand-50 px-8 text-sand-900 dark:bg-sand-900 dark:text-sand-100">
        <div class="flex flex-1 flex-col items-center justify-center">
            <div class="mb-8 text-center">
                <h1 class="text-4xl font-bold tracking-tight text-sand-800 dark:text-sand-100">{{ t('Babysteps') }}</h1>
                <p class="mt-2 text-sm text-sand-500 dark:text-sand-400">{{ t('Safely share with those who matter') }}</p>
            </div>

            <form class="w-full max-w-sm space-y-3" @submit.prevent="submit">
                <div>
                    <input
                        v-model="form.email"
                        type="email"
                        name="email"
                        :placeholder="t('Email address')"
                        autocomplete="email"
                        class="w-full rounded-xl border bg-white px-4 py-3.5 text-sm text-sand-800 placeholder-sand-400 shadow-sm focus:outline-none dark:bg-sand-800 dark:text-sand-100 dark:placeholder-sand-500"
                        :class="form.errors.email ? 'border-blush-400 focus:border-blush-400 focus:ring-1 focus:ring-blush-400' : 'border-sand-200 focus:border-sage-400 focus:ring-1 focus:ring-sage-400 dark:border-sand-700'"
                    />
                    <p v-if="form.errors.email" class="mt-1 text-xs text-blush-500">{{ form.errors.email }}</p>
                </div>

                <div class="relative">
                    <input
                        v-model="form.password"
                        :type="showPassword ? 'text' : 'password'"
                        name="password"
                        :placeholder="t('Password')"
                        autocomplete="current-password"
                        class="w-full rounded-xl border bg-white px-4 py-3.5 pr-16 text-sm text-sand-800 placeholder-sand-400 shadow-sm focus:outline-none dark:bg-sand-800 dark:text-sand-100 dark:placeholder-sand-500"
                        :class="form.errors.password ? 'border-blush-400 focus:border-blush-400 focus:ring-1 focus:ring-blush-400' : 'border-sand-200 focus:border-sage-400 focus:ring-1 focus:ring-sage-400 dark:border-sand-700'"
                    />
                    <button
                        type="button"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-sm font-medium text-sand-400 dark:text-sand-500"
                        @click="showPassword = !showPassword"
                    >
                        {{ showPassword ? t('Hide') : t('Show') }}
                    </button>
                    <p v-if="form.errors.password" class="mt-1 text-xs text-blush-500">{{ form.errors.password }}</p>
                </div>

                <button
                    type="submit"
                    class="w-full rounded-xl bg-sage-500 py-3.5 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-sage-600 disabled:opacity-50 dark:bg-sage-400 dark:text-sand-900 dark:hover:bg-sage-300"
                    :disabled="form.processing || !form.email || !form.password"
                >
                    {{ form.processing ? '...' : t('Log in') }}
                </button>
            </form>

            <button class="mt-4 text-xs font-medium text-sage-600 dark:text-sage-400">{{ t('Forgot password?') }}</button>
        </div>

        <div class="border-t border-sand-200 pb-8 pt-4 dark:border-sand-800">
            <p class="text-center text-sm text-sand-500 dark:text-sand-400">
                {{ t('New to Babysteps?') }}
                <Link href="/register" class="font-semibold text-sage-600 dark:text-sage-400">{{ t('Create an account') }}</Link>
            </p>
        </div>
    </div>
</template>
