<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useTranslations } from '@/composables/useTranslations';
import Button from '@/components/Button.vue';

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
                <h1 class="font-display text-5xl font-semibold tracking-tight text-teal">innerr<span class="text-accent">.</span></h1>
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
                        class="field"
                        :class="form.errors.email ? 'border-blush-400 focus:border-blush-400 focus:ring-1 focus:ring-blush-400' : 'border-sand-200 focus:border-sand-400 focus:ring-1 focus:ring-sand-400 dark:border-sand-700'"
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
                        class="field pr-16"
                        :class="form.errors.password ? 'border-blush-400 focus:border-blush-400 focus:ring-1 focus:ring-blush-400' : 'border-sand-200 focus:border-sand-400 focus:ring-1 focus:ring-sand-400 dark:border-sand-700'"
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

                <Button
                    type="submit"
                    size="lg"
                    block
                    :disabled="form.processing || !form.email || !form.password"
                >
                    {{ form.processing ? '...' : t('Log in') }}
                </Button>
            </form>

            <button class="mt-4 text-xs font-medium text-sand-600 dark:text-sand-400">{{ t('Forgot password?') }}</button>
        </div>

        <div class="border-t border-sand-200 pb-8 pt-4 dark:border-sand-800">
            <p class="text-center text-sm text-sand-500 dark:text-sand-400">
                {{ t('New to Innerr?') }}
                <Link href="/register" class="font-semibold text-sand-600 dark:text-sand-400">{{ t('Create an account') }}</Link>
            </p>
        </div>
    </div>
</template>
