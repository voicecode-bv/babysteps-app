<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useTranslations } from '@/composables/useTranslations';
import Button from '@/components/Button.vue';

const { t } = useTranslations();

const form = useForm({
    email: '',
    name: '',
    username: '',
    password: '',
});

const showPassword = ref(false);

function submit() {
    form.post('/register', {
        onFinish: () => form.reset('password'),
    });
}
</script>

<template>
    <div class="flex min-h-dvh flex-col bg-sand-50 px-8 text-sand-900 dark:bg-sand-900 dark:text-sand-100">
        <div class="flex flex-1 flex-col items-center justify-center">
            <div class="mb-4 text-center">
                <h1 class="font-display text-4xl font-bold tracking-tight text-sand-800 dark:text-sand-100">{{ t('Innerr') }}</h1>
            </div>

            <p class="mb-6 max-w-xs text-center text-sm text-sand-500 dark:text-sand-400">
                {{ t('Share the most beautiful moments with your family and friends.') }}
            </p>

            <form class="w-full max-w-sm space-y-3" @submit.prevent="submit">
                <div>
                    <input
                        v-model="form.name"
                        type="text"
                        name="name"
                        :placeholder="t('Your name')"
                        autocomplete="name"
                        class="field"
                        :class="form.errors.name ? 'border-blush-400 focus:border-blush-400 focus:ring-1 focus:ring-blush-400' : 'border-sand-200 focus:border-sand-400 focus:ring-1 focus:ring-sand-400 dark:border-sand-700'"
                    />
                    <p v-if="form.errors.name" class="mt-1 text-xs text-blush-500">{{ form.errors.name }}</p>
                </div>

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

                <div>
                    <input
                        v-model="form.username"
                        type="text"
                        name="username"
                        :placeholder="t('Username')"
                        autocomplete="username"
                        class="field"
                        :class="form.errors.username ? 'border-blush-400 focus:border-blush-400 focus:ring-1 focus:ring-blush-400' : 'border-sand-200 focus:border-sand-400 focus:ring-1 focus:ring-sand-400 dark:border-sand-700'"
                        @input="form.username = ($event.target as HTMLInputElement).value.toLowerCase()"
                    />
                    <p v-if="form.errors.username" class="mt-1 text-xs text-blush-500">{{ form.errors.username }}</p>
                </div>

                <div class="relative">
                    <input
                        v-model="form.password"
                        :type="showPassword ? 'text' : 'password'"
                        name="password"
                        :placeholder="t('Password')"
                        autocomplete="new-password"
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
                    :disabled="form.processing || !form.email || !form.name || !form.username || !form.password"
                >
                    {{ form.processing ? '...' : t('Create account') }}
                </Button>
            </form>

            <p class="mt-5 max-w-xs text-center text-xs leading-relaxed text-sand-400 dark:text-sand-500">
                {{ t('By registering you agree to our Terms and Privacy Policy. Your data is safe with us.') }}
            </p>
        </div>

        <div class="border-t border-sand-200 pb-8 pt-4 dark:border-sand-800">
            <p class="text-center text-sm text-sand-500 dark:text-sand-400">
                {{ t('Already have an account?') }}
                <Link href="/login" class="font-semibold text-sand-600 dark:text-sand-400">{{ t('Log in') }}</Link>
            </p>
        </div>
    </div>
</template>
