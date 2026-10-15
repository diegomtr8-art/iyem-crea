<script setup>
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({ fallos: Array });

const page = usePage();
// Los éxitos ya salen como notificación; los avisos se quedan en pantalla para poder leerlos
const aviso = computed(() => page.props.flash?.warning);
const procesando = ref(false);

const confirmarYEnviar = (url, pregunta) => {
    if (!confirm(pregunta)) return;
    router.post(url, {}, {
        preserveScroll: true,
        onStart: () => (procesando.value = true),
        onFinish: () => (procesando.value = false),
    });
};

const descripcion = (f) => (f.cuota ? `la cuota ${f.cuota} del crédito ${f.credito}` : 'una cuota que ya no existe');

const reintentar = (f) => confirmarYEnviar(route('correos-fallidos.reintentar', f.id),
    `¿Reintentar el recordatorio de ${descripcion(f)}? Antes de enviarlo se revisa que la cuota siga sin pagar y que no se le haya vuelto a avisar.`);

const descartar = (f) => confirmarYEnviar(route('correos-fallidos.descartar', f.id),
    `¿Descartar el recordatorio de ${descripcion(f)}? No se enviará y se quitará de la lista.`);

const reintentarTodos = () => confirmarYEnviar(route('correos-fallidos.reintentar-todos'),
    `¿Reintentar los ${props.fallos.length} correos? Los que ya no apliquen se descartan sin enviarse.`);
</script>

<template>

    <Head title="Correos fallidos" />
    <AppLayout>
        <div class="max-w-6xl mx-auto py-8 px-4 space-y-6">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-black uppercase tracking-tight text-slate-900 dark:text-white">Correos fallidos</h1>
                    <p class="text-sm text-slate-500 max-w-2xl">
                        Correos que no se pudieron enviar después de 3 intentos. Al reintentar, primero se revisa que
                        todavía aplique; si la cuota ya se pagó, ya venció o ya se le volvió a avisar, se descarta sin enviar.
                    </p>
                </div>
                <button v-if="fallos.length" type="button" :disabled="procesando" @click="reintentarTodos"
                    class="bg-red-700 text-white font-black py-3 px-6 rounded-xl uppercase text-xs tracking-widest disabled:opacity-50">
                    Reintentar todos
                </button>
            </div>

            <div v-if="aviso" class="bg-amber-50 border border-amber-200 text-amber-800 rounded-2xl p-4 text-sm font-bold">
                {{ aviso }}
            </div>

            <div v-if="!fallos.length"
                class="bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-2xl p-4 text-sm font-bold">
                No hay correos fallidos pendientes.
            </div>

            <div v-else class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 overflow-x-auto">
                <table class="w-full text-sm dark:text-white">
                    <thead class="bg-slate-50 dark:bg-slate-800/50 text-xs uppercase text-slate-500">
                        <tr>
                            <th class="text-left p-3">Falló</th>
                            <th class="text-left p-3">Tipo</th>
                            <th class="text-left p-3">Destinatario</th>
                            <th class="text-left p-3">Crédito / cuota</th>
                            <th class="text-left p-3">Error</th>
                            <th class="p-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="f in fallos" :key="f.id" class="border-t border-slate-100 dark:border-slate-800 align-top">
                            <td class="p-3 whitespace-nowrap">{{ f.fecha_fallo }}</td>
                            <td class="p-3">{{ f.tipo }}</td>
                            <td class="p-3 font-mono">{{ f.destinatario }}</td>
                            <td class="p-3">
                                <template v-if="f.cuota">
                                    <div class="font-bold">{{ f.credito }}</div>
                                    <div class="text-xs text-slate-500">Cuota {{ f.cuota }} · vence {{ f.vence }}</div>
                                </template>
                                <span v-else>—</span>
                                <div v-if="f.no_aplica" class="mt-1 text-xs font-bold text-amber-700">
                                    Ya no aplica: {{ f.no_aplica }}
                                </div>
                            </td>
                            <td class="p-3 text-xs text-slate-500 max-w-xs">{{ f.error }}</td>
                            <td class="p-3">
                                <div class="flex gap-2 justify-end">
                                    <button type="button" :disabled="procesando" @click="reintentar(f)"
                                        class="px-3 py-2 rounded-xl text-xs font-bold bg-slate-900 text-white dark:bg-white dark:text-slate-900 disabled:opacity-50">
                                        Reintentar
                                    </button>
                                    <button type="button" :disabled="procesando" @click="descartar(f)"
                                        class="px-3 py-2 rounded-xl text-xs font-bold border border-red-200 text-red-600 hover:bg-red-50 disabled:opacity-50">
                                        Descartar
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
