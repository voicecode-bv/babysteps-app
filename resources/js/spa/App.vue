<script setup lang="ts">
import { RouterView } from 'vue-router';
import ToastContainer from '@/spa/components/ToastContainer.vue';
import { useNetworkStatus } from '@/composables/useNetworkStatus';

useNetworkStatus();
</script>

<template>
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
            <component :is="Component" />
        </Transition>
    </RouterView>
    <ToastContainer />
</template>
