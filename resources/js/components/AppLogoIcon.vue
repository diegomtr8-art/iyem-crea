<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({ inheritAttrs: false });

interface Props {
    className?: HTMLAttributes['class'];
}
defineProps<Props>();
</script>

<template>
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" :class="className" v-bind="$attrs">
        <rect width="32" height="32" rx="7" fill="currentColor"/>
        <rect x="5" y="21" width="5" height="8" rx="1.5" fill="white" fill-opacity="0.5"/>
        <rect x="13" y="17" width="5" height="12" rx="1.5" fill="white" fill-opacity="0.82"/>
        <rect x="21" y="12" width="5" height="17" rx="1.5" fill="#E8A020"/>
    </svg>
</template>
