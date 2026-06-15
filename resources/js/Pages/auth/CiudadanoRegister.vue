<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import GoogleLoginButton from '@/components/GoogleLoginButton.vue';
import AuthSplitLayout from '@/layouts/auth/AuthSplitLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    privacy: false,
});

const showPassword = ref(false);
const showConfirmPassword = ref(false);

const passwordStrength = computed(() => {
    const p = form.password;
    if (!p) return 0;
    let score = 0;
    if (p.length >= 8) score++;
    if (p.length >= 12) score++;
    if (/[A-Z]/.test(p)) score++;
    if (/[0-9]/.test(p)) score++;
    if (/[^A-Za-z0-9]/.test(p)) score++;
    return Math.min(score, 4);
});

const strengthMeta = computed(() => {
    const map = [
        { label: '', color: 'bg-slate-200 dark:bg-zinc-700', text: '' },
        { label: 'Débil',   color: 'bg-red-500',    text: 'text-red-500' },
        { label: 'Regular', color: 'bg-orange-400',  text: 'text-orange-500' },
        { label: 'Buena',   color: 'bg-amber-400',   text: 'text-amber-500' },
        { label: 'Fuerte',  color: 'bg-green-500',   text: 'text-green-600 dark:text-green-400' },
    ];
    return map[passwordStrength.value];
});

const submit = () => {
    form.post(route('ciudadano.register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <AuthSplitLayout title="Crea tu cuenta" description="Regístrate para acceder a tu portal CREA">
        <Head title="Registro — Portal Ciudadano CREA" />

        <form @submit.prevent="submit" class="space-y-5">

            <!-- Nombre completo -->
            <div class="space-y-1.5">
                <label for="name" class="block text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-zinc-500">
                    Nombre completo
                </label>
                <div class="relative group">
                    <input id="name" type="text" v-model="form.name" required autofocus autocomplete="name"
                        placeholder="Tu nombre completo"
                        class="w-full pl-11 pr-4 py-3.5 rounded-2xl border border-slate-200 dark:border-zinc-700 bg-white dark:bg-zinc-800/60 text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-zinc-500 focus:outline-none focus:ring-4 focus:ring-[#6B1938]/10 focus:border-[#6B1938] dark:focus:ring-[#6B1938]/20 dark:focus:border-[#6B1938] transition-all text-sm" />
                    <div class="absolute left-0 top-0 h-full w-10 flex items-center justify-center text-slate-300 dark:text-zinc-600 group-focus-within:text-[#6B1938] dark:group-focus-within:text-[#f4a8c4] transition-colors pointer-events-none">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4">
                            <circle cx="12" cy="8" r="4"/><path d="M6 20v-2a6 6 0 0 1 12 0v2"/>
                        </svg>
                    </div>
                </div>
                <InputError :message="form.errors.name" />
            </div>

            <!-- Correo electrónico -->
            <div class="space-y-1.5">
                <label for="email" class="block text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-zinc-500">
                    Correo electrónico
                </label>
                <div class="relative group">
                    <input id="email" type="email" v-model="form.email" required autocomplete="email"
                        placeholder="tu@correo.com"
                        class="w-full pl-11 pr-4 py-3.5 rounded-2xl border border-slate-200 dark:border-zinc-700 bg-white dark:bg-zinc-800/60 text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-zinc-500 focus:outline-none focus:ring-4 focus:ring-[#6B1938]/10 focus:border-[#6B1938] dark:focus:ring-[#6B1938]/20 dark:focus:border-[#6B1938] transition-all text-sm" />
                    <div class="absolute left-0 top-0 h-full w-10 flex items-center justify-center text-slate-300 dark:text-zinc-600 group-focus-within:text-[#6B1938] dark:group-focus-within:text-[#f4a8c4] transition-colors pointer-events-none">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4">
                            <rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
                        </svg>
                    </div>
                </div>
                <InputError :message="form.errors.email" />
            </div>

            <!-- Contraseña -->
            <div class="space-y-1.5">
                <label for="password" class="block text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-zinc-500">
                    Contraseña
                </label>
                <div class="relative group">
                    <input id="password" :type="showPassword ? 'text' : 'password'" v-model="form.password"
                        required autocomplete="new-password" placeholder="Mínimo 8 caracteres"
                        class="w-full pl-11 pr-11 py-3.5 rounded-2xl border border-slate-200 dark:border-zinc-700 bg-white dark:bg-zinc-800/60 text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-zinc-500 focus:outline-none focus:ring-4 focus:ring-[#6B1938]/10 focus:border-[#6B1938] dark:focus:ring-[#6B1938]/20 dark:focus:border-[#6B1938] transition-all text-sm" />
                    <div class="absolute left-0 top-0 h-full w-10 flex items-center justify-center text-slate-300 dark:text-zinc-600 group-focus-within:text-[#6B1938] dark:group-focus-within:text-[#f4a8c4] transition-colors pointer-events-none">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4">
                            <rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                        </svg>
                    </div>
                    <button type="button" @click="showPassword = !showPassword"
                        class="absolute right-0 top-0 h-full w-10 flex items-center justify-center text-slate-400 dark:text-zinc-500 hover:text-[#6B1938] dark:hover:text-[#f4a8c4] transition-colors">
                        <svg v-if="!showPassword" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4">
                            <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/>
                        </svg>
                        <svg v-else xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4">
                            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/>
                        </svg>
                    </button>
                </div>
                <!-- Indicador de fortaleza -->
                <div v-if="form.password" class="space-y-1.5">
                    <div class="flex gap-1">
                        <div v-for="i in 4" :key="i"
                            class="h-1.5 flex-1 rounded-full transition-all duration-300"
                            :class="i <= passwordStrength ? strengthMeta.color : 'bg-slate-100 dark:bg-zinc-800'">
                        </div>
                    </div>
                    <p class="text-xs font-semibold" :class="strengthMeta.text">
                        {{ strengthMeta.label }}
                    </p>
                </div>
                <InputError :message="form.errors.password" />
            </div>

            <!-- Confirmar contraseña -->
            <div class="space-y-1.5">
                <label for="password_confirmation" class="block text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-zinc-500">
                    Confirmar contraseña
                </label>
                <div class="relative group">
                    <input id="password_confirmation" :type="showConfirmPassword ? 'text' : 'password'"
                        v-model="form.password_confirmation" required autocomplete="new-password"
                        placeholder="Repite tu contraseña"
                        class="w-full pl-11 pr-11 py-3.5 rounded-2xl border border-slate-200 dark:border-zinc-700 bg-white dark:bg-zinc-800/60 text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-zinc-500 focus:outline-none focus:ring-4 focus:ring-[#6B1938]/10 focus:border-[#6B1938] dark:focus:ring-[#6B1938]/20 dark:focus:border-[#6B1938] transition-all text-sm" />
                    <div class="absolute left-0 top-0 h-full w-10 flex items-center justify-center text-slate-300 dark:text-zinc-600 group-focus-within:text-[#6B1938] dark:group-focus-within:text-[#f4a8c4] transition-colors pointer-events-none">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4">
                            <rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                        </svg>
                    </div>
                    <button type="button" @click="showConfirmPassword = !showConfirmPassword"
                        class="absolute right-0 top-0 h-full w-10 flex items-center justify-center text-slate-400 dark:text-zinc-500 hover:text-[#6B1938] dark:hover:text-[#f4a8c4] transition-colors">
                        <svg v-if="!showConfirmPassword" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4">
                            <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/>
                        </svg>
                        <svg v-else xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4">
                            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/>
                        </svg>
                    </button>
                </div>
                <InputError :message="form.errors.password_confirmation" />
            </div>

            <!-- Aviso de privacidad -->
            <label class="flex items-start gap-3 cursor-pointer group">
                <input type="checkbox" v-model="form.privacy" required
                    class="mt-0.5 rounded-md border-slate-300 dark:border-zinc-600 text-[#6B1938] focus:ring-[#6B1938]/30 dark:bg-zinc-800 transition-all shrink-0" />
                <span class="text-xs text-slate-500 dark:text-zinc-400 leading-relaxed group-hover:text-slate-700 dark:group-hover:text-zinc-300 transition-colors">
                    He leído y acepto el
                    <a href="/#aviso-privacidad" target="_blank"
                        class="font-semibold text-[#6B1938] dark:text-[#f4a8c4] hover:underline">
                        Aviso de Privacidad
                    </a>
                    del programa CREA
                </span>
            </label>

            <!-- Botón registro -->
            <button type="submit" :disabled="form.processing || !form.privacy"
                class="w-full bg-[#6B1938] hover:bg-[#4E1029] disabled:opacity-60 disabled:cursor-not-allowed text-white font-bold py-3.5 rounded-2xl shadow-lg shadow-[#6B1938]/20 transition-all duration-200 flex justify-center items-center gap-2.5 text-sm active:scale-[0.98]">
                <svg v-if="form.processing" class="size-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                <svg v-else xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="size-4">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/>
                </svg>
                {{ form.processing ? 'Creando tu cuenta...' : 'Crear cuenta' }}
            </button>

            <!-- Separador -->
            <div class="relative">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-slate-100 dark:border-zinc-800"></div>
                </div>
                <div class="relative flex justify-center text-xs">
                    <span class="px-3 bg-[#FAFAF8] dark:bg-[#0E0508] text-slate-400 dark:text-zinc-500 font-medium">
                        o regístrate con
                    </span>
                </div>
            </div>

            <!-- Google -->
            <GoogleLoginButton />

            <!-- Footer -->
            <p class="text-center text-sm text-slate-500 dark:text-zinc-400 pt-2">
                ¿Ya tienes cuenta?
                <Link :href="route('login')"
                    class="font-bold text-[#6B1938] dark:text-[#f4a8c4] hover:underline ml-1">
                    Iniciar sesión
                </Link>
            </p>
        </form>
    </AuthSplitLayout>
</template>
