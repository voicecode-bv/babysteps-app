<script setup lang="ts">
import { computed } from 'vue';

type Tone = 'default' | 'muted' | 'subtle';

const props = withDefaults(
    defineProps<{
        tone?: Tone;
        padded?: boolean;
    }>(),
    {
        tone: 'default',
        padded: true,
    },
);

const toneClasses = computed(() => {
    const map: Record<Tone, string> = {
        default: 'bg-white/70 dark:bg-sand-800/60',
        muted: 'bg-white/50 dark:bg-sand-800/60',
        subtle: 'bg-white/20 dark:bg-sand-800/60',
    };
    return map[props.tone];
});
</script>

<template>
    <div
        class="rounded-lg shadow-sm backdrop-blur-sm dark:border-sand-700/50"
        :class="[toneClasses, padded ? 'p-5' : '']"
    >
        <slot />
    </div>
</template>
