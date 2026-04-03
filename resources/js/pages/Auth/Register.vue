<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

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
    <div class="flex min-h-dvh flex-col items-center justify-center bg-black px-8 text-white">
        <!-- Logo -->
        <div class="mb-6">
            <h1 class="text-center font-serif text-4xl italic tracking-tight">Instagram</h1>
        </div>

        <p class="mb-6 max-w-xs text-center text-sm font-semibold text-neutral-400">
            Meld je aan om foto's en video's van je vrienden te zien.
        </p>

        <!-- Register Form -->
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

            <div>
                <input
                    v-model="form.name"
                    type="text"
                    placeholder="Volledige naam"
                    autocomplete="name"
                    class="w-full rounded-lg border border-neutral-700 bg-neutral-900 px-4 py-3 text-sm text-white placeholder-neutral-500 focus:border-neutral-500 focus:outline-none"
                />
            </div>

            <div>
                <input
                    v-model="form.username"
                    type="text"
                    placeholder="Gebruikersnaam"
                    autocomplete="username"
                    class="w-full rounded-lg border border-neutral-700 bg-neutral-900 px-4 py-3 text-sm text-white placeholder-neutral-500 focus:border-neutral-500 focus:outline-none"
                />
            </div>

            <div class="relative">
                <input
                    v-model="form.password"
                    :type="showPassword ? 'text' : 'password'"
                    placeholder="Wachtwoord"
                    autocomplete="new-password"
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
                :disabled="!form.email || !form.name || !form.username || !form.password"
            >
                Registreren
            </button>
        </form>

        <p class="mt-6 max-w-xs text-center text-xs text-neutral-500">
            Door je te registreren, ga je akkoord met onze Voorwaarden, Privacybeleid en Cookiebeleid.
        </p>

        <!-- Login link -->
        <div class="mt-auto border-t border-neutral-800 pb-8 pt-4">
            <p class="text-center text-sm text-neutral-400">
                Heb je al een account?
                <Link href="/login" class="font-semibold text-blue-400">Inloggen</Link>
            </p>
        </div>
    </div>
</template>
