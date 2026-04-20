<script setup lang="ts">
defineProps<{
    href: string;
    label: string;
}>();
</script>

<template>
    <a
        :href="href"
        :aria-label="label"
        class="inline-flex w-full items-center justify-center gap-2 rounded-full bg-black px-6 py-4 text-sm font-medium text-white transition-all hover:-translate-y-0.5 focus:outline-none focus-visible:ring-2 focus-visible:ring-white/40"
    >
        <svg
            class="size-4"
            viewBox="0 0 16 16"
            fill="currentColor"
            aria-hidden="true"
        >
            <path d="M11.182 8.412c-.023-2.345 1.921-3.478 2.01-3.533-1.095-1.597-2.8-1.815-3.407-1.837-1.449-.147-2.834.854-3.57.854-.751 0-1.882-.836-3.1-.812-1.59.023-3.066.93-3.885 2.352-1.668 2.887-.425 7.148 1.202 9.485.797 1.14 1.735 2.41 2.963 2.366 1.199-.049 1.651-.77 3.1-.77 1.436 0 1.86.77 3.11.744 1.29-.023 2.1-1.145 2.887-2.293.935-1.308 1.319-2.59 1.34-2.657-.03-.014-2.573-.988-2.6-3.91zM9.2 2.28c.663-.807 1.112-1.928.99-3.043-.957.039-2.116.638-2.802 1.438-.61.72-1.15 1.87-1.005 2.966 1.066.083 2.154-.541 2.817-1.36z"/>
        </svg>
        {{ label }}
    </a>
</template>
