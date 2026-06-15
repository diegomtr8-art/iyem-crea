<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import GoogleLoginButton from '@/components/GoogleLoginButton.vue';
import AuthSplitLayout from '@/layouts/auth/AuthSplitLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps<{
    status?: string;
    canResetPassword?: boolean;
}>();

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const showPassword = ref(false);

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <AuthSplitLayout title="Bienvenido de nuevo" description="Ingresa a tu cuenta CREA para continuar">
        <Head title="Iniciar sesión" />

        <!-- Status flash (ej. contraseña restablecida) -->
        <div v-if="status"
            class="mb-6 flex items-center gap-2.5 p-3.5 rounded-2xl bg-green-50 dark:bg-green-900/20 border border-green-100 dark:border-green-800 text-sm font-medium text-green-700 dark:text-green-400">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4 shrink-0">
                <path d="M20 6L9 17l-5-5"/>
            </svg>
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="space-y-5">

            <!-- Correo electrónico -->
            <div class="space-y-1.5">
                <label for="email" class="block text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-zinc-500">
                    Correo electrónico
                </label>
                <div class="relative group">
                    <input id="email" type="email" v-model="form.email" required autofocus autocomplete="username"
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
                <div class="flex items-center justify-between">
                    <label for="password" class="block text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-zinc-500">
                        Contraseña
                    </label>
                    <Link v-if="canResetPassword" :href="route('password.request')"
                        class="text-xs font-semibold text-[#6B1938] dark:text-[#f4a8c4] hover:underline transition-colors">
                        ¿Olvidaste tu contraseña?
                    </Link>
                </div>
                <div class="relative group">
                    <input id="password" :type="showPassword ? 'text' : 'password'" v-model="form.password"
                        required autocomplete="current-password" placeholder="••••••••"
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
                <InputError :message="form.errors.password" />
            </div>

            <!-- Recordarme -->
            <label class="flex items-center gap-3 cursor-pointer group">
                <input type="checkbox" v-model="form.remember"
                    class="rounded-md border-slate-300 dark:border-zinc-600 text-[#6B1938] focus:ring-[#6B1938]/30 dark:bg-zinc-800 transition-all" />
                <span class="text-sm text-slate-500 dark:text-zinc-400 group-hover:text-slate-700 dark:group-hover:text-zinc-300 transition-colors">
                    Mantener sesión activa
                </span>
            </label>

            <!-- Botón login -->
            <button type="submit" :disabled="form.processing"
                class="w-full bg-[#6B1938] hover:bg-[#4E1029] disabled:opacity-60 disabled:cursor-not-allowed text-white font-bold py-3.5 rounded-2xl shadow-lg shadow-[#6B1938]/20 transition-all duration-200 flex justify-center items-center gap-2.5 text-sm active:scale-[0.98]">
                <svg v-if="form.processing" class="size-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                <svg v-else xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="size-4">
                    <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/>
                </svg>
                {{ form.processing ? 'Verificando...' : 'Iniciar sesión' }}
            </button>

            <!-- Separador -->
            <div class="relative">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-slate-100 dark:border-zinc-800"></div>
                </div>
                <div class="relative flex justify-center text-xs">
                    <span class="px-3 bg-[#FAFAF8] dark:bg-[#0E0508] text-slate-400 dark:text-zinc-500 font-medium">
                        o continúa con
                    </span>
                </div>
            </div>

            <!-- Google -->
            <GoogleLoginButton />

            <!-- Footer -->
            <p class="text-center text-sm text-slate-500 dark:text-zinc-400 pt-2">
                ¿No tienes cuenta?
                <Link :href="route('ciudadano.register')"
                    class="font-bold text-[#6B1938] dark:text-[#f4a8c4] hover:underline ml-1">
                    Regístrate aquí
                </Link>
            </p>
        </form>
    </AuthSplitLayout>
</template>
