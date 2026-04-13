<script setup lang="ts">
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { useTranslations } from '@/composables/useTranslations';
import Button from '@/components/Button.vue';

const { t } = useTranslations();
const page = usePage();
const currentLocale = computed(() => page.props.locale as string);

const form = useForm({
    email: '',
    password: '',
});

function setLocale(locale: string) {
    router.put('/locale', { locale }, { preserveScroll: true });
}

const showPassword = ref(false);

function submit() {
    form.post('/login', {
        onFinish: () => form.reset('password'),
    });
}
</script>

<template>
    <div class="nativephp-safe-area flex h-dvh flex-col px-8">
        <div class="flex justify-end pt-4">
            <button
                class="p-2 text-2xl"
                @click="setLocale(currentLocale === 'nl' ? 'en' : 'nl')"
            >
                {{ currentLocale === 'nl' ? '🇳🇱' : '🇬🇧' }}
            </button>
        </div>

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

                <Link href="/register">
                    <Button variant="secondary" size="lg" block>
                        {{ t('Create an account') }}
                    </Button>
                </Link>
            </form>
        </div>
    </div>
</template>
