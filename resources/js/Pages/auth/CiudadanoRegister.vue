<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { CreditCard, User, Mail, Lock, Sparkles, ArrowLeft } from 'lucide-vue-next';
import { ref, onMounted } from 'vue';

const theme = ref(localStorage.getItem('theme') || 'light');
onMounted(() => {
    document.documentElement.classList.toggle('dark', theme.value === 'dark');
});

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('ciudadano.register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Registro de Ciudadano — CREA" />

    <div class="min-h-screen bg-[#FDFCFB] dark:bg-zinc-950 flex items-center justify-center p-6">
        <div class="w-full max-w-md space-y-8">

            <!-- Cabecera -->
            <div class="text-center space-y-4">
                <Link :href="route('welcome')" class="inline-flex items-center gap-2 text-slate-500 dark:text-zinc-400 hover:text-red-700 transition-colors text-sm font-medium mb-4">
                    <ArrowLeft size="16" /> Regresar al inicio
                </Link>
                <div class="mx-auto w-16 h-16 rounded-2xl bg-red-700 flex items-center justify-center shadow-2xl shadow-red-900/30 text-white">
                    <CreditCard size="28" />
                </div>
                <div>
                    <h1 class="text-3xl font-black text-slate-950 dark:text-white">Crea tu cuenta</h1>
                    <p class="text-slate-500 dark:text-zinc-400 mt-2">Portal Ciudadano CREA — IYEM Yucatán</p>
                </div>
            </div>

            <!-- Formulario -->
            <div class="bg-white dark:bg-zinc-900 rounded-[2.5rem] border border-slate-100 dark:border-zinc-800 shadow-2xl shadow-slate-900/5 p-8 space-y-6">
                <form @submit.prevent="submit" class="space-y-5">

                    <div class="space-y-2">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 ml-1">Nombre completo</label>
                        <div class="relative group">
                            <input v-model="form.name" type="text" required autofocus placeholder="Tu nombre completo"
                                class="w-full pl-14 pr-6 py-4 rounded-2xl border border-slate-200 dark:border-zinc-700 dark:bg-zinc-800/50 focus:ring-4 focus:ring-red-500/10 focus:border-red-600 transition-all text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-zinc-500" />
                            <div class="absolute left-0 top-0 h-full w-12 flex items-center justify-center border-r border-slate-100 dark:border-zinc-700 text-slate-300 group-focus-within:text-red-600 transition-colors">
                                <User size="18" />
                            </div>
                        </div>
                        <InputError :message="form.errors.name" />
                    </div>

                    <div class="space-y-2">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 ml-1">Correo electrónico</label>
                        <div class="relative group">
                            <input v-model="form.email" type="email" required placeholder="tucorreo@ejemplo.com"
                                class="w-full pl-14 pr-6 py-4 rounded-2xl border border-slate-200 dark:border-zinc-700 dark:bg-zinc-800/50 focus:ring-4 focus:ring-red-500/10 focus:border-red-600 transition-all text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-zinc-500" />
                            <div class="absolute left-0 top-0 h-full w-12 flex items-center justify-center border-r border-slate-100 dark:border-zinc-700 text-slate-300 group-focus-within:text-red-600 transition-colors">
                                <Mail size="18" />
                            </div>
                        </div>
                        <InputError :message="form.errors.email" />
                    </div>

                    <div class="space-y-2">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 ml-1">Contraseña</label>
                        <div class="relative group">
                            <input v-model="form.password" type="password" required placeholder="Mínimo 8 caracteres"
                                class="w-full pl-14 pr-6 py-4 rounded-2xl border border-slate-200 dark:border-zinc-700 dark:bg-zinc-800/50 focus:ring-4 focus:ring-red-500/10 focus:border-red-600 transition-all text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-zinc-500" />
                            <div class="absolute left-0 top-0 h-full w-12 flex items-center justify-center border-r border-slate-100 dark:border-zinc-700 text-slate-300 group-focus-within:text-red-600 transition-colors">
                                <Lock size="18" />
                            </div>
                        </div>
                        <InputError :message="form.errors.password" />
                    </div>

                    <div class="space-y-2">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 ml-1">Confirmar contraseña</label>
                        <div class="relative group">
                            <input v-model="form.password_confirmation" type="password" required placeholder="Repite tu contraseña"
                                class="w-full pl-14 pr-6 py-4 rounded-2xl border border-slate-200 dark:border-zinc-700 dark:bg-zinc-800/50 focus:ring-4 focus:ring-red-500/10 focus:border-red-600 transition-all text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-zinc-500" />
                            <div class="absolute left-0 top-0 h-full w-12 flex items-center justify-center border-r border-slate-100 dark:border-zinc-700 text-slate-300 group-focus-within:text-red-600 transition-colors">
                                <Lock size="18" />
                            </div>
                        </div>
                        <InputError :message="form.errors.password_confirmation" />
                    </div>

                    <button :disabled="form.processing"
                        class="w-full bg-red-700 hover:bg-red-800 disabled:opacity-60 text-white font-bold py-5 rounded-2xl shadow-xl shadow-red-900/20 transition-all active:scale-[0.98] flex justify-center items-center gap-3 text-lg mt-2">
                        <Sparkles v-if="!form.processing" size="20" />
                        {{ form.processing ? 'Creando tu cuenta...' : 'Crear mi cuenta' }}
                    </button>
                </form>

                <div class="text-center pt-4 border-t border-slate-100 dark:border-zinc-800 space-y-3">
                    <p class="text-sm text-slate-500 dark:text-zinc-400">
                        ¿Ya tienes una cuenta?
                        <Link :href="route('login')" class="text-red-700 font-bold hover:underline ml-1">Iniciar Sesión</Link>
                    </p>
                </div>
            </div>

            <p class="text-center text-xs text-slate-400 dark:text-zinc-600">
                © 2026 Instituto Yucateco de Emprendedores — Gobierno de Yucatán
            </p>
        </div>
    </div>
</template>
