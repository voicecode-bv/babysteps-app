<script setup lang="ts">
import { computed } from 'vue';

// Isometric corner of a framed canvas: a light top face split by the mitre
// crease, with two darker side faces whose depth grows with the frame
// thickness, so a 2 cm "classic" frame reads as thin and a 4.5 cm "premium"
// frame as chunky.
const props = defineProps<{ depthCm: number }>();

// Map cm to the on-screen depth of the side faces, clamped so extremes stay
// inside the viewBox.
const depth = computed(() =>
    Math.min(26, Math.max(6, props.depthCm * 5)),
);

// Top face corners: near, left, back (peak), right.
const near = { x: 52, y: 56 };
const left = { x: 10, y: 32 };
const back = { x: 52, y: 12 };
const right = { x: 94, y: 32 };

const topFace = computed(
    () =>
        `M${near.x},${near.y} L${left.x},${left.y} L${back.x},${back.y} L${right.x},${right.y} Z`,
);

const leftFace = computed(
    () =>
        `M${near.x},${near.y} L${left.x},${left.y} L${left.x},${left.y + depth.value} L${near.x},${near.y + depth.value} Z`,
);

const rightFace = computed(
    () =>
        `M${near.x},${near.y} L${right.x},${right.y} L${right.x},${right.y + depth.value} L${near.x},${near.y + depth.value} Z`,
);

const crease = computed(() => `M${near.x},${near.y} L${back.x},${back.y}`);
</script>

<template>
    <svg
        viewBox="0 0 104 90"
        fill="none"
        xmlns="http://www.w3.org/2000/svg"
        class="h-full w-full"
        aria-hidden="true"
    >
        <!-- Darker side faces (the frame depth). -->
        <path :d="leftFace" fill="#C4C4C4" stroke="#3A3A4D" stroke-width="1.5" />
        <path
            :d="rightFace"
            fill="#B5B5B5"
            stroke="#3A3A4D"
            stroke-width="1.5"
        />
        <!-- Light top face plus the mitre crease. -->
        <path :d="topFace" fill="#ECECEC" stroke="#3A3A4D" stroke-width="1.5" />
        <path
            :d="crease"
            stroke="#3A3A4D"
            stroke-width="1.5"
            stroke-linecap="round"
        />
    </svg>
</template>
