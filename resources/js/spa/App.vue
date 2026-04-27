<script setup lang="ts">
import { RouterView } from 'vue-router';
import ToastContainer from '@/spa/components/ToastContainer.vue';
import { useNetworkStatus } from '@/composables/useNetworkStatus';

useNetworkStatus();
</script>

<template>
    <!-- Persistente achtergrond zodat er nooit een wit/leeg-frame zichtbaar is
         tijdens route-transities of het laden van een lazy chunk. -->
    <div aria-hidden="true" class="pointer-events-none fixed inset-0 -z-20 bg-warmwhite dark:bg-sand-900" />
    <div aria-hidden="true" class="pointer-events-none fixed inset-0 -z-20 overflow-hidden">
        <div class="absolute -left-24 -top-16 size-72 rounded-full bg-sage-200/40 blur-3xl dark:bg-sage-700/20"></div>
        <div class="absolute -right-24 top-1/3 size-80 rounded-full bg-accent-soft/30 blur-3xl dark:bg-accent/10"></div>
        <div class="absolute -bottom-24 left-1/4 size-96 rounded-full bg-sand-200/40 blur-3xl dark:bg-sand-700/20"></div>
    </div>

    <RouterView v-slot="{ Component }">
        <Transition
            mode="out-in"
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-100 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <Suspense>
                <component :is="Component" />
            </Suspense>
        </Transition>
    </RouterView>
    <ToastContainer />
</template>
