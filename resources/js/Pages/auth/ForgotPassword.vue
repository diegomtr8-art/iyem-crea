<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import AuthSplitLayout from '@/layouts/auth/AuthSplitLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps<{
    status?: string;
}>();

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <AuthSplitLayout
        title="Recupera tu contraseña"
        description="Ingresa tu correo y te enviaremos un enlace para restablecerla">
        <Head title="Recuperar contraseña" />

        <!-- Confirmación de envío -->
        <div v-if="status"
            class="mb-6 p-4 rounded-2xl bg-green-50 dark:bg-green-900/20 border border-green-100 dark:border-green-800 flex items-start gap-3">
            <div class="shrink-0 mt-0.5">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-5 text-green-500">
                    <rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
                </svg>
            </div>
            <div>
                <p class="text-sm font-bold text-green-700 dark:text-green-400">Enlace enviado</p>
                <p class="text-xs text-green-600 dark:text-green-500 mt-0.5">{{ status }} Revisa tu bandeja de entrada y también la carpeta de spam.</p>
            </div>
        </div>

        <form @submit.prevent="submit" class="space-y-5">

            <!-- Correo electrónico -->
            <div class="space-y-1.5">
                <label for="email" class="block text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-zinc-500">
                    Correo electrónico
                </label>
                <div class="relative group">
                    <input id="email" type="email" v-model="form.email" required autofocus autocomplete="email"
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

            <!-- Botón enviar -->
            <button type="submit" :disabled="form.processing"
                class="w-full bg-[#6B1938] hover:bg-[#4E1029] disabled:opacity-60 disabled:cursor-not-allowed text-white font-bold py-3.5 rounded-2xl shadow-lg shadow-[#6B1938]/20 transition-all duration-200 flex justify-center items-center gap-2.5 text-sm active:scale-[0.98]">
                <svg v-if="form.processing" class="size-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                <svg v-else xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4">
                    <path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/>
                </svg>
                {{ form.processing ? 'Enviando...' : 'Enviar enlace de recuperación' }}
            </button>

            <!-- Volver al login -->
            <Link :href="route('login')"
                class="flex items-center justify-center gap-1.5 text-sm font-medium text-slate-500 dark:text-zinc-400 hover:text-[#6B1938] dark:hover:text-[#f4a8c4] transition-colors group">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4 group-hover:-translate-x-0.5 transition-transform">
                    <path d="m15 18-6-6 6-6"/>
                </svg>
                Volver al inicio de sesión
            </Link>
        </form>
    </AuthSplitLayout>
</template>
