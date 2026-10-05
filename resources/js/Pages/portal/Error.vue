<script setup lang="ts">
import BeneficiarioLayout from '@/layouts/BeneficiarioLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { AlertTriangle } from 'lucide-vue-next';

const props = defineProps<{ status: number }>();

const mensajes: Record<number, { titulo: string; detalle: string }> = {
    403: { titulo: 'No tienes permiso para ver esta página', detalle: 'Si crees que es un error, comunícate con el IYEM al 999 941 2170.' },
    404: { titulo: 'No encontramos esta página', detalle: 'Es posible que la dirección esté mal escrita o que la página ya no exista.' },
    419: { titulo: 'Tu sesión expiró', detalle: 'Por seguridad cerramos tu sesión después de un tiempo sin actividad. Inicia sesión de nuevo para continuar.' },
    500: { titulo: 'Algo salió mal de nuestro lado', detalle: 'Ya estamos al tanto. Inténtalo de nuevo en unos minutos; si sigue pasando, llama al 999 941 2170.' },
};

const mensaje = computed(() => mensajes[props.status] ?? mensajes[500]);
</script>

<template>
    <BeneficiarioLayout>
        <Head :title="`${mensaje.titulo} — CREA`" />
        <div class="max-w-lg mx-auto py-16 text-center space-y-4">
            <div class="mx-auto w-14 h-14 rounded-2xl bg-red-50 dark:bg-red-900/20 flex items-center justify-center">
                <AlertTriangle size="28" class="text-red-700" />
            </div>
            <p class="text-xs font-bold tracking-widest text-slate-400">ERROR {{ status }}</p>
            <h1 class="text-2xl font-black text-slate-900 dark:text-white">{{ mensaje.titulo }}</h1>
            <p class="text-sm text-slate-500 dark:text-zinc-400">{{ mensaje.detalle }}</p>
            <div class="pt-2">
                <Link v-if="status === 419" :href="route('login')"
                    class="inline-block px-6 py-3 rounded-2xl bg-red-700 text-white text-sm font-bold hover:bg-red-800 transition-colors">
                    Iniciar sesión
                </Link>
                <Link v-else :href="route('portal.dashboard')"
                    class="inline-block px-6 py-3 rounded-2xl bg-red-700 text-white text-sm font-bold hover:bg-red-800 transition-colors">
                    Volver al inicio
                </Link>
            </div>
        </div>
    </BeneficiarioLayout>
</template>
