<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const form = useForm({
    email: '',
    password: '',
});

const showPassword = ref(false);

function submit() {
    // Backend volgt later
}
</script>

<template>
    <div class="flex min-h-dvh flex-col items-center justify-center bg-black px-8 text-white">
        <!-- Logo -->
        <div class="mb-10">
            <h1 class="text-center font-serif text-4xl italic tracking-tight">Instagram</h1>
        </div>

        <!-- Login Form -->
        <form class="w-full max-w-sm space-y-3" @submit.prevent="submit">
            <div>
                <input
                    v-model="form.email"
                    type="email"
                    placeholder="E-mailadres"
                    autocomplete="email"
                    class="w-full rounded-lg border border-neutral-700 bg-neutral-900 px-4 py-3 text-sm text-white placeholder-neutral-500 focus:border-neutral-500 focus:outline-none"
                />
            </div>

            <div class="relative">
                <input
                    v-model="form.password"
                    :type="showPassword ? 'text' : 'password'"
                    placeholder="Wachtwoord"
                    autocomplete="current-password"
                    class="w-full rounded-lg border border-neutral-700 bg-neutral-900 px-4 py-3 pr-16 text-sm text-white placeholder-neutral-500 focus:border-neutral-500 focus:outline-none"
                />
                <button
                    type="button"
                    class="absolute right-3 top-1/2 -translate-y-1/2 text-sm font-semibold text-neutral-400"
                    @click="showPassword = !showPassword"
                >
                    {{ showPassword ? 'Verberg' : 'Toon' }}
                </button>
            </div>

            <button
                type="submit"
                class="w-full rounded-lg bg-blue-500 py-3 text-sm font-semibold text-white transition-colors hover:bg-blue-600 disabled:opacity-50"
                :disabled="!form.email || !form.password"
            >
                Inloggen
            </button>
        </form>

        <!-- Divider -->
        <div class="my-6 flex w-full max-w-sm items-center gap-4">
            <div class="h-px flex-1 bg-neutral-700" />
            <span class="text-xs font-semibold uppercase text-neutral-500">of</span>
            <div class="h-px flex-1 bg-neutral-700" />
        </div>

        <!-- Forgot password -->
        <button class="text-xs text-blue-400">Wachtwoord vergeten?</button>

        <!-- Register link -->
        <div class="mt-auto border-t border-neutral-800 pb-8 pt-4">
            <p class="text-center text-sm text-neutral-400">
                Heb je geen account?
                <Link href="/register" class="font-semibold text-blue-400">Registreer</Link>
            </p>
        </div>
    </div>
</template>
