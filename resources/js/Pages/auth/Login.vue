<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import AuthSimpleLayout from '@/Layouts/auth/AuthSimpleLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Mail, Lock, LogIn, Sparkles, Building2, UserCog, UserCheck } from 'lucide-vue-next'; // Iconos elegantes

defineProps<{
    status?: string;
    canResetPassword?: boolean;
}>();

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <AuthSimpleLayout title="Acceso al Sistema Operativo CREA" description="Ingresa tus credenciales autorizadas">
        <Head title="Log in" />

        <div v-if="status" class="mb-4 font-medium text-sm text-green-600 dark:text-green-400">
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="space-y-8 animate-in fade-in duration-500">
            
            <div class="block md:hidden text-center space-y-3 pb-6 border-b border-slate-100 dark:border-zinc-800">
                <div class="mx-auto w-14 h-14 rounded-2xl bg-red-700 flex items-center justify-center shadow-lg text-white">
                    <UserCog size="28" />
                </div>
                <h1 class="text-3xl font-black text-slate-950 dark:text-white leading-tight">Módulo <span class="text-red-600">Operativo</span></h1>
            </div>

            <div class="space-y-6">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-2 ml-1">Correo Electrónico</label>
                    <div class="relative group">
                        <input
                            id="email"
                            type="email"
                            v-model="form.email"
                            required
                            autofocus
                            placeholder="usuario@crea.com"
                            class="w-full pl-14 pr-6 py-4 rounded-2xl border border-slate-200 dark:border-zinc-800 dark:bg-zinc-800/50 focus:ring-4 focus:ring-red-500/10 focus:border-red-600 transition-all placeholder:text-slate-400 dark:placeholder:text-zinc-600"
                        />
                        <div class="absolute left-0 top-0 h-full w-12 flex items-center justify-center border-r border-slate-100 dark:border-zinc-800/50 text-slate-300 group-focus-within:text-red-600 transition-colors">
                            <Mail size="20" stroke-width="2" />
                        </div>
                    </div>
                    <InputError class="mt-2" :message="form.errors.email" />
                </div>

                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 ml-1">Contraseña</label>
                        <Link
                            v-if="canResetPassword"
                            :href="route('password.request')"
                            class="text-xs font-semibold text-red-600 hover:text-red-700 transition-colors"
                        >
                            ¿Olvidaste tu contraseña?
                        </Link>
                    </div>
                    <div class="relative group">
                        <input
                            id="password"
                            type="password"
                            v-model="form.password"
                            required
                            placeholder="••••••••"
                            class="w-full pl-14 pr-6 py-4 rounded-2xl border border-slate-200 dark:border-zinc-800 dark:bg-zinc-800/50 focus:ring-4 focus:ring-red-500/10 focus:border-red-600 transition-all placeholder:text-slate-400 dark:placeholder:text-zinc-600"
                        />
                        <div class="absolute left-0 top-0 h-full w-12 flex items-center justify-center border-r border-slate-100 dark:border-zinc-800/50 text-slate-300 group-focus-within:text-red-600 transition-colors">
                            <Lock size="20" stroke-width="2" />
                        </div>
                    </div>
                    <InputError class="mt-2" :message="form.errors.password" />
                </div>
            </div>

            <div class="flex items-center justify-between">
                    <label class="flex items-center gap-3 text-sm cursor-pointer group">
                        <input
                            type="checkbox"
                            name="remember"
                            v-model="form.remember" 
                            class="rounded-md border-slate-200 dark:border-zinc-800 text-guinda-600 shadow-sm focus:ring-guinda-500 focus:ring-offset-background transition-all"
                        />
                        <span class="text-slate-500 dark:text-zinc-400 group-hover:text-slate-900 transition-colors">
                            Mantener sesión activa
                        </span>
                    </label>
                </div>

            <div class="pt-2">
                <button
                    :disabled="form.processing"
                    class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-5 rounded-2xl shadow-xl shadow-red-900/10 transition-all duration-300 flex justify-center items-center gap-3.5 text-lg group active:scale-[0.98]"
                >
                    <UserCheck size="22" class="group-hover:scale-110 group-hover:rotate-6 transition-transform" />
                    {{ form.processing ? 'Verificando credenciales...' : 'Iniciar Sesión Segura' }}
                </button>
            </div>

            <div class="text-center pt-6 border-t border-slate-100 dark:border-zinc-800 space-y-4">
                <div class="bg-slate-50 dark:bg-zinc-800 rounded-2xl p-4 text-center">
                    <p class="text-sm text-slate-500 dark:text-zinc-400 mb-2">
                        ¿Eres emprendedor o artesano?
                    </p>
                    <Link :href="route('ciudadano.register')"
                        class="inline-flex items-center gap-2 text-red-600 hover:text-red-700 font-bold text-sm transition-colors">
                        <Sparkles size="15" /> Accede al Portal Ciudadano CREA
                    </Link>
                </div>
                <p class="text-xs text-slate-400">
                    Solo personal administrativo CREA. <br/>
                    © 2026 IYEM - Todos los derechos reservados. <br/>
                    Software Desarrollado por Diego Martínez
                </p>
            </div>
        </form>
    </AuthSimpleLayout>
</template>