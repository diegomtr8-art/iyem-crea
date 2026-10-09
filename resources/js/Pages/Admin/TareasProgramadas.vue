<script setup>
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({ tareas: Array, horas_alerta: Number });

const conAlerta = computed(() => props.tareas.filter(t => t.sin_correr || t.ultimo_estado === 'error' || t.errores_7_dias > 0));
</script>

<template>
    <Head title="Tareas programadas" />
    <AppLayout>
        <div class="max-w-5xl mx-auto py-8 px-4 space-y-6">
            <h1 class="text-2xl font-black uppercase tracking-tight text-slate-900 dark:text-white">Tareas programadas</h1>

            <div :class="conAlerta.length
                ? 'bg-red-50 border-red-200 text-red-700'
                : 'bg-emerald-50 border-emerald-200 text-emerald-700'"
                 class="border rounded-2xl p-4 text-sm font-bold">
                {{ conAlerta.length
                    ? `${conAlerta.length} tarea(s) requieren atención`
                    : 'Todas las tareas corrieron a tiempo y sin errores' }}
            </div>

            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 dark:bg-slate-800/50 text-xs uppercase text-slate-500">
                        <tr>
                            <th class="text-left p-3">Tarea</th>
                            <th class="text-left p-3">Última ejecución</th>
                            <th class="text-left p-3">Resultado</th>
                            <th class="text-left p-3">Errores (7 días)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="t in tareas" :key="t.tarea"
                            :class="t.sin_correr ? 'bg-red-50 dark:bg-red-900/20' : ''"
                            class="border-t border-slate-100 dark:border-slate-800">
                            <td class="p-3 font-mono">{{ t.tarea }}</td>
                            <td class="p-3">
                                <span v-if="t.ultima_ejecucion">{{ t.ultima_ejecucion }}</span>
                                <span v-else class="font-bold text-red-600">Nunca ha corrido</span>
                                <div v-if="t.sin_correr && t.ultima_ejecucion" class="text-xs font-bold text-red-600">
                                    Hace {{ t.horas_sin_correr }} h (límite {{ horas_alerta }} h)
                                </div>
                            </td>
                            <td class="p-3">
                                <span v-if="t.ultimo_estado === 'exito'" class="font-bold text-emerald-600">Exitosa</span>
                                <span v-else-if="t.ultimo_estado === 'error'" class="font-bold text-red-600">Error</span>
                                <span v-else-if="t.ultimo_estado === 'en_curso'" class="font-bold text-amber-600">En curso</span>
                                <span v-else>—</span>
                                <div v-if="t.ultimo_mensaje" class="text-xs text-slate-500 max-w-xs truncate" :title="t.ultimo_mensaje">{{ t.ultimo_mensaje }}</div>
                            </td>
                            <td class="p-3 font-bold" :class="t.errores_7_dias ? 'text-red-600' : 'text-slate-500'">{{ t.errores_7_dias }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>