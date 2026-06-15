<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import AppLogo from '@/components/AppLogo.vue';
import { Link } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';

defineProps<{
    title?: string;
    description?: string;
}>();

const theme = ref(typeof localStorage !== 'undefined' ? localStorage.getItem('theme') || 'light' : 'light');
const applyTheme = () => document.documentElement.classList.toggle('dark', theme.value === 'dark');
const toggleTheme = () => {
    theme.value = theme.value === 'light' ? 'dark' : 'light';
    localStorage.setItem('theme', theme.value);
    applyTheme();
};
onMounted(applyTheme);
</script>

<template>
    <div class="min-h-screen bg-[#FAFAF8] dark:bg-[#0E0508] transition-colors duration-300 lg:grid lg:grid-cols-[1fr_52%]">

        <!-- ── Form column (left) ─────────────────────────────────── -->
        <div class="flex flex-col min-h-screen lg:min-h-0">

            <!-- Mobile: gradient header strip -->
            <div class="lg:hidden bg-[#6B1938] px-4 py-3 flex items-center justify-between">
                <Link :href="route('welcome')" class="flex items-center gap-2.5">
                    <AppLogoIcon class="size-8 text-white" />
                    <div class="leading-none">
                        <span class="block font-black text-base tracking-tight text-white">CREA</span>
                        <span class="block text-[9px] font-medium text-white/60 tracking-wide uppercase">IYEM Yucatán</span>
                    </div>
                </Link>
                <button @click="toggleTheme" aria-label="Cambiar tema"
                    class="p-1.5 rounded-lg bg-white/10 hover:bg-white/20 transition-colors text-white">
                    <svg v-if="theme === 'dark'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4">
                        <circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/>
                    </svg>
                    <svg v-else xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4">
                        <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
                    </svg>
                </button>
            </div>

            <!-- Desktop header -->
            <div class="hidden lg:flex items-center justify-between px-10 pt-8 pb-0">
                <Link :href="route('welcome')">
                    <AppLogo />
                </Link>
                <div class="flex items-center gap-3">
                    <button @click="toggleTheme" aria-label="Cambiar tema"
                        class="p-2 rounded-xl bg-slate-100 dark:bg-zinc-800 text-slate-500 dark:text-zinc-400 hover:bg-slate-200 dark:hover:bg-zinc-700 transition-colors">
                        <svg v-if="theme === 'dark'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4">
                            <circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/>
                        </svg>
                        <svg v-else xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4">
                            <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
                        </svg>
                    </button>
                    <Link :href="route('welcome')"
                        class="flex items-center gap-1.5 text-sm font-medium text-slate-500 dark:text-zinc-400 hover:text-[#6B1938] dark:hover:text-[#f4a8c4] transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4">
                            <path d="m15 18-6-6 6-6"/>
                        </svg>
                        Volver al inicio
                    </Link>
                </div>
            </div>

            <!-- Form area -->
            <div class="flex-1 flex items-center justify-center px-6 py-8 lg:px-16 lg:py-12">
                <div class="w-full max-w-sm animate-in fade-in slide-in-from-bottom-4 duration-500">
                    <div class="mb-8 space-y-1.5">
                        <h1 class="text-2xl font-black text-slate-950 dark:text-white tracking-tight">{{ title }}</h1>
                        <p class="text-sm text-slate-500 dark:text-zinc-400 leading-relaxed">{{ description }}</p>
                    </div>
                    <slot />
                </div>
            </div>
        </div>

        <!-- ── Decorative panel (right) ───────────────────────────── -->
        <div class="hidden lg:flex flex-col relative overflow-hidden" style="background: linear-gradient(145deg, #6B1938 0%, #4E1029 45%, #1A0509 100%);">

            <!-- Dot grid overlay -->
            <div class="absolute inset-0 opacity-[0.06]"
                style="background-image: radial-gradient(#ffffff 1px, transparent 1px); background-size: 28px 28px;"></div>

            <!-- Animated floating shapes -->
            <div class="float-1 absolute -top-16 -right-16 w-80 h-80 rounded-full border border-white/10 bg-white/5"></div>
            <div class="float-2 absolute top-1/3 -left-24 w-64 h-64 rounded-full border border-[#E8A020]/20 bg-[#E8A020]/5"></div>
            <div class="float-3 absolute bottom-20 right-20 w-48 h-48 rounded-full border border-white/10 bg-white/5"></div>
            <div class="float-4 absolute top-16 left-1/3 w-32 h-32 rounded-full border border-[#E8A020]/15 bg-[#E8A020]/5"></div>

            <!-- Hexagon shapes -->
            <svg class="float-5 absolute top-1/4 right-8 opacity-10" width="80" height="92" viewBox="0 0 80 92" fill="none">
                <path d="M40 2L78 23V69L40 90L2 69V23L40 2Z" stroke="white" stroke-width="1.5"/>
            </svg>
            <svg class="float-2 absolute bottom-1/3 left-12 opacity-8" width="60" height="69" viewBox="0 0 60 69" fill="none">
                <path d="M30 2L58 17.5V48.5L30 64L2 48.5V17.5L30 2Z" stroke="#E8A020" stroke-width="1.5"/>
            </svg>

            <!-- Central content -->
            <div class="relative z-10 flex flex-col items-center justify-center flex-1 px-12 py-16 text-center">

                <!-- Illustration: Abstract entrepreneur growth -->
                <div class="mb-10">
                    <svg viewBox="0 0 200 180" width="200" height="180" xmlns="http://www.w3.org/2000/svg">
                        <!-- Base platform -->
                        <rect x="20" y="155" width="160" height="4" rx="2" fill="rgba(255,255,255,0.15)"/>

                        <!-- Growth bars -->
                        <rect x="38" y="115" width="22" height="40" rx="4" fill="rgba(255,255,255,0.25)"/>
                        <rect x="70" y="95" width="22" height="60" rx="4" fill="rgba(255,255,255,0.35)"/>
                        <rect x="102" y="70" width="22" height="85" rx="4" fill="rgba(255,255,255,0.45)"/>
                        <rect x="134" y="45" width="22" height="110" rx="4" fill="#E8A020" fill-opacity="0.85"/>

                        <!-- Person silhouette -->
                        <circle cx="100" cy="28" r="14" fill="rgba(255,255,255,0.9)"/>
                        <path d="M78 75 Q78 55 100 52 Q122 55 122 75 L118 90 L82 90 Z" fill="rgba(255,255,255,0.85)"/>
                        <!-- Arms raised in celebration -->
                        <path d="M82 65 Q65 52 58 42" stroke="rgba(255,255,255,0.85)" stroke-width="5" stroke-linecap="round" fill="none"/>
                        <path d="M118 65 Q135 52 142 42" stroke="rgba(255,255,255,0.85)" stroke-width="5" stroke-linecap="round" fill="none"/>

                        <!-- Stars -->
                        <circle cx="55" cy="38" r="3" fill="#E8A020" fill-opacity="0.9"/>
                        <circle cx="145" cy="38" r="3" fill="#E8A020" fill-opacity="0.9"/>
                        <circle cx="30" cy="60" r="2" fill="rgba(255,255,255,0.6)"/>
                        <circle cx="170" cy="55" r="2" fill="rgba(255,255,255,0.6)"/>
                        <circle cx="100" cy="10" r="2.5" fill="#E8A020" fill-opacity="0.7"/>

                        <!-- Upward arrow -->
                        <path d="M162 108 L162 90 L170 98 M162 90 L154 98" stroke="#E8A020" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" fill="none" opacity="0.8"/>
                    </svg>
                </div>

                <!-- Motivational message -->
                <div class="space-y-3 mb-10">
                    <h2 class="text-2xl font-black text-white leading-tight tracking-tight">
                        Impulsa tu negocio<br>
                        <span style="color: #E8A020;">con el crédito que mereces.</span>
                    </h2>
                    <p class="text-white/65 text-sm leading-relaxed max-w-xs mx-auto">
                        Programa CREA del Instituto Yucateco de Emprendedores.<br>
                        Cobertura en los 106 municipios de Yucatán.
                    </p>
                </div>

                <!-- Trust indicators -->
                <div class="space-y-3 w-full max-w-xs">
                    <div v-for="item in [
                        { text: 'Tasas desde 0% anual' },
                        { text: 'Proceso 100% digital' },
                        { text: 'Sin comisiones ocultas' },
                    ]" :key="item.text"
                        class="flex items-center gap-3 bg-white/8 rounded-xl px-4 py-3 backdrop-blur-sm border border-white/10">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#E8A020" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="size-4 shrink-0">
                            <path d="M20 6L9 17l-5-5"/>
                        </svg>
                        <span class="text-white/85 text-sm font-medium">{{ item.text }}</span>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="relative z-10 px-12 pb-8 text-center">
                <p class="text-white/35 text-xs">© 2026 IYEM · Gobierno del Estado de Yucatán</p>
            </div>
        </div>
    </div>
</template>

<style scoped>
@keyframes float1 {
    0%, 100% { transform: translateY(0) scale(1); }
    50%       { transform: translateY(-22px) scale(1.04); }
}
@keyframes float2 {
    0%, 100% { transform: translateY(0) rotate(0deg); }
    50%       { transform: translateY(18px) rotate(8deg); }
}
@keyframes float3 {
    0%, 100% { transform: translate(0, 0) scale(1); }
    50%       { transform: translate(-12px, 14px) scale(1.06); }
}
@keyframes float4 {
    0%, 100% { transform: translateY(0); }
    50%       { transform: translateY(16px); }
}
@keyframes float5 {
    0%, 100% { transform: rotate(0deg); }
    50%       { transform: rotate(15deg) translateY(-10px); }
}
.float-1 { animation: float1 10s ease-in-out infinite; }
.float-2 { animation: float2 13s ease-in-out infinite; }
.float-3 { animation: float3 16s ease-in-out infinite; }
.float-4 { animation: float4 9s ease-in-out infinite; }
.float-5 { animation: float5 12s ease-in-out infinite; }
</style>
