<script setup lang="ts">
import AuthSplitLayout from '@/layouts/auth/AuthSplitLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps<{
    status?: string;
}>();

const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};
</script>

<template>
    <AuthSplitLayout
        title="Verifica tu correo"
        description="Hemos enviado un enlace de verificación a tu dirección de correo electrónico.">
        <Head title="Verificar correo" />

        <!-- Ilustración / estado -->
        <div class="flex flex-col items-center text-center mb-6 py-4">
            <div class="w-16 h-16 rounded-2xl bg-[#6B1938]/10 dark:bg-[#6B1938]/20 flex items-center justify-center mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="size-8 text-[#6B1938] dark:text-[#f4a8c4]">
                    <rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
                </svg>
            </div>
            <p class="text-sm text-slate-600 dark:text-zinc-400 leading-relaxed max-w-xs">
                Antes de continuar, revisa tu bandeja de entrada y haz clic en el enlace de verificación.
                También revisa la carpeta de <strong class="text-slate-700 dark:text-zinc-300">spam</strong>.
            </p>
        </div>

        <!-- Confirmación de reenvío -->
        <div v-if="status === 'verification-link-sent'"
            class="mb-5 flex items-center gap-2.5 p-3.5 rounded-2xl bg-green-50 dark:bg-green-900/20 border border-green-100 dark:border-green-800 text-sm font-medium text-green-700 dark:text-green-400">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4 shrink-0">
                <path d="M20 6L9 17l-5-5"/>
            </svg>
            Se ha enviado un nuevo enlace de verificación a tu correo.
        </div>

        <form @submit.prevent="submit" class="space-y-4">
            <!-- Botón reenviar -->
            <button type="submit" :disabled="form.processing"
                class="w-full bg-[#6B1938] hover:bg-[#4E1029] disabled:opacity-60 disabled:cursor-not-allowed text-white font-bold py-3.5 rounded-2xl shadow-lg shadow-[#6B1938]/20 transition-all duration-200 flex justify-center items-center gap-2.5 text-sm active:scale-[0.98]">
                <svg v-if="form.processing" class="size-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                <svg v-else xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4">
                    <path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/>
                </svg>
                {{ form.processing ? 'Enviando...' : 'Reenviar correo de verificación' }}
            </button>

            <!-- Cerrar sesión -->
            <Link :href="route('logout')" method="post" as="button"
                class="w-full flex items-center justify-center gap-1.5 text-sm font-medium text-slate-500 dark:text-zinc-400 hover:text-[#6B1938] dark:hover:text-[#f4a8c4] transition-colors py-2.5">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>
                </svg>
                Cerrar sesión
            </Link>
        </form>
    </AuthSplitLayout>
</template>
