<script setup lang="ts">
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { useTranslations } from '@/composables/useTranslations';
import Button from '@/components/Button.vue';
import AppleAuthButton from '@/components/auth/AppleAuthButton.vue';
import GoogleAuthButton from '@/components/auth/GoogleAuthButton.vue';
import { start as socialStart } from '@/routes/auth/social';

const { t } = useTranslations();
const page = usePage();
const currentLocale = computed(() => page.props.locale as string);
const flashError = computed(() => (page.props.errors as Record<string, string> | undefined)?.email);

const termsUrl = computed(() =>
    currentLocale.value === 'nl'
        ? 'https://innerr.app/nl/voorwaarden/'
        : 'https://innerr.app/en/terms/',
);

const form = useForm({
    email: '',
    name: '',
    username: '',
    password: '',
    terms_accepted: false,
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
        <div class="flex justify-start pt-4">
            <Link href="/login" class="p-2 text-sand-600 dark:text-sand-400">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                </svg>
            </Link>
        </div>

        <div class="flex flex-1 flex-col items-center justify-center">
            <div class="mb-8 text-center">
                <h1 class="font-display text-5xl font-semibold tracking-tight text-teal">innerr<span class="text-accent">.</span></h1>
                <p class="mt-2 text-sm text-sand-500 dark:text-sand-400">{{ t('Safely share with those who matter') }}</p>
            </div>

            <form class="w-full max-w-sm space-y-3" @submit.prevent="submit">
                <p v-if="flashError" class="rounded-lg bg-blush-50 px-3 py-2 text-center text-xs text-blush-600 dark:bg-blush-900/20">
                    {{ flashError }}
                </p>

                <AppleAuthButton
                    :href="socialStart('apple').url"
                    :label="t('Sign up with Apple')"
                />
                <GoogleAuthButton
                    :href="socialStart('google').url"
                    :label="t('Sign up with Google')"
                />

                <div class="flex items-center gap-3 py-2">
                    <div class="h-px flex-1 bg-sand-200 dark:bg-sand-700" />
                    <span class="text-xs uppercase tracking-wider text-sand-400 dark:text-sand-500">{{ t('or') }}</span>
                    <div class="h-px flex-1 bg-sand-200 dark:bg-sand-700" />
                </div>

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

                <div class="flex items-start gap-3">
                    <input
                        id="terms"
                        v-model="form.terms_accepted"
                        type="checkbox"
                        class="mt-0.5 size-5 shrink-0 rounded border-sand-300 text-teal accent-teal focus:ring-teal dark:border-sand-600"
                    />
                    <label for="terms" class="text-sm leading-relaxed text-sand-500 dark:text-sand-400">
                        {{ t('I agree to the') }}
                        <a :href="termsUrl" target="_blank" class="font-semibold text-teal underline">{{ t('Terms and Conditions') }}</a>
                    </label>
                </div>
                <p v-if="form.errors.terms_accepted" class="mt-1 text-xs text-blush-500">{{ form.errors.terms_accepted }}</p>

                <Button
                    type="submit"
                    size="lg"
                    block
                    :disabled="form.processing || !form.email || !form.name || !form.username || !form.password || !form.terms_accepted"
                >
                    {{ form.processing ? '...' : t('Create account') }}
                </Button>
            </form>
        </div>

        <div class="border-t border-sand-200 pb-8 pt-4 dark:border-sand-800">
            <p class="text-center text-sm text-sand-500 dark:text-sand-400">
                {{ t('Already have an account?') }}
                <Link href="/login" class="font-semibold text-sand-600 dark:text-sand-400">{{ t('Log in') }}</Link>
            </p>
        </div>
    </div>
</template>
