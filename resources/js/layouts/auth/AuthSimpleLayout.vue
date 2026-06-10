<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { Link } from '@inertiajs/vue3';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div class="flex min-h-svh flex-col items-center justify-center gap-6 bg-[#FDFCFB] dark:bg-zinc-950 p-6 md:p-10">
        <div class="w-full max-w-sm">
            <div class="flex flex-col gap-8">
                <div class="flex flex-col items-center gap-4">
                    <Link :href="route('welcome')" class="flex flex-col items-center gap-2 font-medium group">
                        <div class="mb-1 flex h-12 w-12 items-center justify-center rounded-xl bg-guinda-700 shadow-lg shadow-guinda-900/20 group-hover:scale-110 transition-transform duration-300">
                            <AppLogoIcon class="size-8 fill-current text-white" />
                        </div>
                        <span class="sr-only">{{ title }}</span>
                    </Link>
                    
                    <div class="space-y-2 text-center">
                        <h1 class="text-2xl font-black tracking-tight text-slate-950 dark:text-white">{{ title }}</h1>
                        <p class="text-center text-sm text-slate-500 dark:text-zinc-400 leading-relaxed">{{ description }}</p>
                    </div>
                </div>

                <div class="bg-white dark:bg-zinc-900 p-2 rounded-3xl shadow-sm border border-slate-100 dark:border-zinc-800">
                     <slot />
                </div>
            </div>
        </div>
    </div>
</template>