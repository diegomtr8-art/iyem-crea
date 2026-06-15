<script setup lang="ts">
/*
 * REGISTRO OPERATIVO — Solo acceso interno (admin/panel)
 *
 * Decisión: Esta página muestra un mensaje de acceso restringido. El alta de
 * usuarios operativos se gestiona directamente por el administrador del sistema.
 * La ruta /register NO está enlazada públicamente desde Login.vue.
 *
 * TODO: Si se implementa el alta de operativos vía modal en Users/Index.vue,
 *       se puede proteger esta ruta con middleware auth + can:usuarios.gestionar.
 */
import InputError from '@/components/InputError.vue';
import AuthSplitLayout from '@/layouts/auth/AuthSplitLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <AuthSplitLayout
        title="Crear cuenta operativa"
        description="Alta de usuario para el sistema operativo CREA — solo uso interno">
        <Head title="Registro operativo" />

        <!-- Aviso de acceso restringido -->
        <div class="flex items-start gap-3 p-3.5 rounded-2xl bg-amber-50 dark:bg-amber-900/15 border border-amber-100 dark:border-amber-800/50 mb-5">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4 shrink-0 mt-0.5 text-amber-500">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
            </svg>
            <p class="text-xs text-amber-700 dark:text-amber-400 leading-relaxed">
                Acceso restringido. Esta sección es solo para el personal administrativo autorizado del IYEM.
            </p>
        </div>

        <form @submit.prevent="submit" class="space-y-5">

            <!-- Nombre -->
            <div class="space-y-1.5">
                <label for="name" class="block text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-zinc-500">Nombre completo</label>
                <div class="relative group">
                    <input id="name" type="text" v-model="form.name" required autofocus autocomplete="name"
                        placeholder="Nombre del usuario"
                        class="w-full pl-11 pr-4 py-3.5 rounded-2xl border border-slate-200 dark:border-zinc-700 bg-white dark:bg-zinc-800/60 text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-zinc-500 focus:outline-none focus:ring-4 focus:ring-[#6B1938]/10 focus:border-[#6B1938] dark:focus:ring-[#6B1938]/20 dark:focus:border-[#6B1938] transition-all text-sm" />
                    <div class="absolute left-0 top-0 h-full w-10 flex items-center justify-center text-slate-300 dark:text-zinc-600 group-focus-within:text-[#6B1938] dark:group-focus-within:text-[#f4a8c4] transition-colors pointer-events-none">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4"><circle cx="12" cy="8" r="4"/><path d="M6 20v-2a6 6 0 0 1 12 0v2"/></svg>
                    </div>
                </div>
                <InputError :message="form.errors.name" />
            </div>

            <!-- Correo -->
            <div class="space-y-1.5">
                <label for="email" class="block text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-zinc-500">Correo electrónico</label>
                <div class="relative group">
                    <input id="email" type="email" v-model="form.email" required autocomplete="email"
                        placeholder="usuario@iyemyucatan.com"
                        class="w-full pl-11 pr-4 py-3.5 rounded-2xl border border-slate-200 dark:border-zinc-700 bg-white dark:bg-zinc-800/60 text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-zinc-500 focus:outline-none focus:ring-4 focus:ring-[#6B1938]/10 focus:border-[#6B1938] dark:focus:ring-[#6B1938]/20 dark:focus:border-[#6B1938] transition-all text-sm" />
                    <div class="absolute left-0 top-0 h-full w-10 flex items-center justify-center text-slate-300 dark:text-zinc-600 group-focus-within:text-[#6B1938] dark:group-focus-within:text-[#f4a8c4] transition-colors pointer-events-none">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                    </div>
                </div>
                <InputError :message="form.errors.email" />
            </div>

            <!-- Contraseña -->
            <div class="space-y-1.5">
                <label for="password" class="block text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-zinc-500">Contraseña</label>
                <div class="relative group">
                    <input id="password" type="password" v-model="form.password" required autocomplete="new-password"
                        placeholder="Mínimo 8 caracteres"
                        class="w-full pl-11 pr-4 py-3.5 rounded-2xl border border-slate-200 dark:border-zinc-700 bg-white dark:bg-zinc-800/60 text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-zinc-500 focus:outline-none focus:ring-4 focus:ring-[#6B1938]/10 focus:border-[#6B1938] dark:focus:ring-[#6B1938]/20 dark:focus:border-[#6B1938] transition-all text-sm" />
                    <div class="absolute left-0 top-0 h-full w-10 flex items-center justify-center text-slate-300 dark:text-zinc-600 group-focus-within:text-[#6B1938] dark:group-focus-within:text-[#f4a8c4] transition-colors pointer-events-none">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    </div>
                </div>
                <InputError :message="form.errors.password" />
            </div>

            <!-- Confirmar contraseña -->
            <div class="space-y-1.5">
                <label for="password_confirmation" class="block text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-zinc-500">Confirmar contraseña</label>
                <div class="relative group">
                    <input id="password_confirmation" type="password" v-model="form.password_confirmation"
                        required autocomplete="new-password" placeholder="Repite la contraseña"
                        class="w-full pl-11 pr-4 py-3.5 rounded-2xl border border-slate-200 dark:border-zinc-700 bg-white dark:bg-zinc-800/60 text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-zinc-500 focus:outline-none focus:ring-4 focus:ring-[#6B1938]/10 focus:border-[#6B1938] dark:focus:ring-[#6B1938]/20 dark:focus:border-[#6B1938] transition-all text-sm" />
                    <div class="absolute left-0 top-0 h-full w-10 flex items-center justify-center text-slate-300 dark:text-zinc-600 group-focus-within:text-[#6B1938] dark:group-focus-within:text-[#f4a8c4] transition-colors pointer-events-none">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    </div>
                </div>
                <InputError :message="form.errors.password_confirmation" />
            </div>

            <!-- Botón -->
            <button type="submit" :disabled="form.processing"
                class="w-full bg-[#6B1938] hover:bg-[#4E1029] disabled:opacity-60 disabled:cursor-not-allowed text-white font-bold py-3.5 rounded-2xl shadow-lg shadow-[#6B1938]/20 transition-all duration-200 flex justify-center items-center gap-2.5 text-sm active:scale-[0.98]">
                <svg v-if="form.processing" class="size-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                {{ form.processing ? 'Creando cuenta...' : 'Crear cuenta operativa' }}
            </button>

            <Link :href="route('login')"
                class="flex items-center justify-center gap-1.5 text-sm font-medium text-slate-500 dark:text-zinc-400 hover:text-[#6B1938] dark:hover:text-[#f4a8c4] transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4"><path d="m15 18-6-6 6-6"/></svg>
                Volver al inicio de sesión
            </Link>
        </form>
    </AuthSplitLayout>
</template>
