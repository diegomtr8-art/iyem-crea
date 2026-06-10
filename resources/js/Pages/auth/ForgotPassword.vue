<script setup lang="ts">
import { ref, onMounted } from 'vue';
import InputError from '@/components/InputError.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { LoaderCircle, Mail, ArrowLeft, CreditCard, Sun, Moon } from 'lucide-vue-next';

defineProps<{
    status?: string;
}>();

const form = useForm({
    email: '',
});

const theme = ref(localStorage.getItem('theme') || 'light');

const applyTheme = () => {
    if (theme.value === 'dark') {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }
};

onMounted(() => applyTheme());

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <Head title="Recuperar Contraseña" />

    <div class="min-h-screen bg-[#FDFCFB] dark:bg-zinc-950 text-slate-900 dark:text-zinc-100 transition-colors duration-500 flex flex-col justify-center py-12 sm:px-6 lg:px-8 relative overflow-hidden">
        
        <div class="absolute -top-24 -left-24 w-96 h-96 bg-red-50 dark:bg-red-950/20 rounded-full blur-3xl opacity-50"></div>
        <div class="absolute -bottom-24 -right-24 w-96 h-96 bg-red-50 dark:bg-red-950/20 rounded-full blur-3xl opacity-50"></div>

        <div class="sm:mx-auto sm:w-full sm:max-w-md relative z-10">
            <div class="flex justify-center mb-6">
                <div class="w-16 h-16 rounded-2xl bg-red-700 flex items-center justify-center shadow-xl shadow-red-900/20 text-white">
                    <CreditCard size="32" />
                </div>
            </div>
            
            <h2 class="text-center text-4xl font-black tracking-tight text-slate-900 dark:text-white">
                ¿Olvidaste tu <span class="text-red-700">clave?</span>
            </h2>
            <p class="mt-3 text-center text-sm text-slate-500 dark:text-zinc-400 max-w-xs mx-auto">
                Introduce tu correo electrónico y te enviaremos un enlace para restablecer tu acceso.
            </p>
        </div>

        <div class="mt-10 sm:mx-auto sm:w-full sm:max-w-[440px] relative z-10">
            <div class="bg-white dark:bg-zinc-900 py-10 px-8 md:px-12 shadow-2xl shadow-red-900/5 border border-slate-100 dark:border-zinc-800 sm:rounded-[2.5rem]">
                
                <div v-if="status" class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-100 dark:border-green-800 rounded-2xl text-center text-sm font-bold text-green-600 dark:text-green-400 animate-in fade-in zoom-in duration-300">
                    {{ status }}
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                    <div class="space-y-2">
                        <label for="email" class="text-xs font-bold uppercase tracking-widest text-slate-400 ml-1">Correo Electrónico</label>
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400 group-focus-within:text-red-600 transition-colors">
                                <Mail size="18" />
                            </div>
                            <input 
                                id="email" 
                                type="email" 
                                v-model="form.email" 
                                required 
                                autofocus 
                                placeholder="ejemplo@correo.com"
                                class="w-full pl-11 pr-4 py-4 rounded-2xl border-slate-200 dark:border-zinc-800 dark:bg-zinc-800/50 focus:ring-4 focus:ring-red-500/10 focus:border-red-600 transition-all outline-none text-slate-900 dark:text-white"
                            />
                        </div>
                        <InputError :message="form.errors.email" class="mt-2 text-xs font-bold uppercase tracking-tighter" />
                    </div>

                    <div>
                        <button 
                            type="submit" 
                            :disabled="form.processing"
                            class="w-full bg-red-700 hover:bg-red-800 text-white font-bold py-4 rounded-2xl shadow-xl shadow-red-900/20 transition-all active:scale-95 flex justify-center items-center gap-3 text-lg disabled:opacity-50"
                        >
                            <LoaderCircle v-if="form.processing" class="h-5 w-5 animate-spin" />
                            <span>{{ form.processing ? 'Enviando...' : 'Enviar Enlace' }}</span>
                        </button>
                    </div>
                </form>

                <div class="mt-8 pt-8 border-t border-slate-50 dark:border-zinc-800">
                    <Link 
                        :href="route('login')" 
                        class="flex items-center justify-center gap-2 text-sm font-bold text-slate-500 hover:text-red-700 transition-colors group"
                    >
                        <ArrowLeft size="16" class="group-hover:-translate-x-1 transition-transform" />
                        Volver al inicio de sesión
                    </Link>
                </div>
                <div class="text-center pt-6 border-t border-slate-100 dark:border-zinc-800">
                <p class="text-sm text-slate-400">
                    Solo personal administrativo CREA. <br/>
                    © 2026 IYEM - Todos los derechos reservados. <br/>
                    Software Desarrollado por Diego Martínez
                </p>
            </div>
            </div>

           
        </div>
    </div>
</template>

<style scoped>
/* Consistencia en las transiciones */
* {
    transition: background-color 0.5s ease, border-color 0.5s ease, color 0.3s ease;
}
</style>