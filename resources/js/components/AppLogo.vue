<script setup lang="ts">
interface Props {
    class?: string;
}
defineProps<Props>();
</script>

<template>
    <div class="flex items-center gap-2">
        <!-- Isotipo -->
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" class="size-8 text-[#6B1938]">
            <rect width="32" height="32" rx="7" fill="currentColor"/>
            <rect x="5" y="21" width="5" height="8" rx="1.5" fill="white" fill-opacity="0.5"/>
            <rect x="13" y="17" width="5" height="12" rx="1.5" fill="white" fill-opacity="0.82"/>
            <rect x="21" y="12" width="5" height="17" rx="1.5" fill="#E8A020"/>
        </svg>
        <!-- Logotype -->
        <div class="leading-none">
            <span class="block font-black text-base tracking-tight text-[#6B1938] dark:text-[#f4a8c4]">CREA</span>
            <span class="block text-[8px] font-medium text-slate-400 dark:text-zinc-500 tracking-wide uppercase">IYEM Yucatán</span>
        </div>
    </div>
</template>
