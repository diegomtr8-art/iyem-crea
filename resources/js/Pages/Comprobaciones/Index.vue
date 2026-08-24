<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { ClipboardCheck, Eye, RefreshCw, AlertTriangle, Clock, CheckCircle2, Filter } from 'lucide-vue-next';

const props = defineProps<{
    comprobaciones: {
        data: Array<{
            id: number;
            acreditado?: string;
            clave_contrato?: string;
            modalidad?: string;
            fecha_desembolso?: string;
            fecha_limite?: string;
            dias_restantes: number;
            estatus: string;
            semaforo: 'verde' | 'amarillo' | 'rojo' | 'vencido';
        }>;
        links: any[];
        meta?: any;
    };
    filters: { estatus?: string; filtro?: string };
    kpis: { pendientes: number; vencidas: number; en_revision: number; aprobadas: number };
}>();

const estatusFiltro = ref(props.filters?.estatus ?? '');
const filtroRapido = ref(props.filters?.filtro ?? '');

const semaforoEstilo: Record<string, { bg: string; text: string; label: string }> = {
    verde:    { bg: 'bg-green-100 dark:bg-green-900/30', text: 'text-green-700 dark:text-green-400', label: 'En plazo' },
    amarillo: { bg: 'bg-amber-100 dark:bg-amber-900/30', text: 'text-amber-700 dark:text-amber-400', label: 'Por vencer' },
    rojo:     { bg: 'bg-orange-100 dark:bg-orange-900/30', text: 'text-orange-700 dark:text-orange-400', label: 'Urgente' },
    vencido:  { bg: 'bg-red-100 dark:bg-red-900/30', text: 'text-red-700 dark:text-red-400', label: 'Vencido' },
};

let timeout: ReturnType<typeof setTimeout>;
watch([estatusFiltro, filtroRapido], () => {
    clearTimeout(timeout);
    timeout = setTimeout(() => {
        router.get(route('comprobaciones.index'), { estatus: estatusFiltro.value, filtro: filtroRapido.value }, { preserveState: true, replace: true });
    }, 300);
});
</script>

<template>
    <AppLayout>
        <Head title="Comprobación de Uso — CREA" />

        <div class="p-6 space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-black text-slate-900 dark:text-white flex items-center gap-3">
                        <ClipboardCheck size="26" class="text-red-700" /> Comprobación de Uso del Crédito
                    </h1>
                    <p class="text-slate-500 dark:text-zinc-400 text-sm mt-1">
                        Seguimiento del plazo de comprobación de acreditados con desembolso realizado
                    </p>
                </div>
                <button @click="router.reload({ preserveScroll: true })"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-slate-100 dark:bg-zinc-800 text-slate-700 dark:text-zinc-300 hover:bg-slate-200 dark:hover:bg-zinc-700 rounded-xl text-sm font-semibold transition-all">
                    <RefreshCw size="15" /> Actualizar
                </button>
            </div>

            <!-- KPIs -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <button @click="filtroRapido = ''; estatusFiltro = 'Pendiente'"
                    class="rounded-2xl p-4 text-left bg-blue-50 dark:bg-blue-900/20 hover:scale-[1.02] transition-all">
                    <Clock class="size-5 mb-2 text-blue-700" />
                    <p class="text-2xl font-black text-blue-700">{{ kpis.pendientes }}</p>
                    <p class="text-[11px] font-semibold text-slate-500 dark:text-zinc-400 mt-0.5">Pendientes</p>
                </button>
                <button @click="filtroRapido = 'vencidas'; estatusFiltro = ''"
                    class="rounded-2xl p-4 text-left bg-red-50 dark:bg-red-900/20 hover:scale-[1.02] transition-all">
                    <AlertTriangle class="size-5 mb-2 text-red-700" />
                    <p class="text-2xl font-black text-red-700">{{ kpis.vencidas }}</p>
                    <p class="text-[11px] font-semibold text-slate-500 dark:text-zinc-400 mt-0.5">Vencidas</p>
                </button>
                <button @click="filtroRapido = ''; estatusFiltro = 'En_Revision'"
                    class="rounded-2xl p-4 text-left bg-amber-50 dark:bg-amber-900/20 hover:scale-[1.02] transition-all">
                    <RefreshCw class="size-5 mb-2 text-amber-700" />
                    <p class="text-2xl font-black text-amber-700">{{ kpis.en_revision }}</p>
                    <p class="text-[11px] font-semibold text-slate-500 dark:text-zinc-400 mt-0.5">En revisión</p>
                </button>
                <button @click="filtroRapido = ''; estatusFiltro = 'Aprobada'"
                    class="rounded-2xl p-4 text-left bg-green-50 dark:bg-green-900/20 hover:scale-[1.02] transition-all">
                    <CheckCircle2 class="size-5 mb-2 text-green-700" />
                    <p class="text-2xl font-black text-green-700">{{ kpis.aprobadas }}</p>
                    <p class="text-[11px] font-semibold text-slate-500 dark:text-zinc-400 mt-0.5">Aprobadas</p>
                </button>
            </div>

            <!-- Filtros -->
            <div class="flex flex-wrap gap-3">
                <div class="relative">
                    <Filter size="16" class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none" />
                    <select v-model="estatusFiltro"
                        class="pl-10 pr-8 py-2.5 rounded-xl border border-slate-200 dark:border-zinc-700 dark:bg-zinc-800 text-sm appearance-none">
                        <option value="">Todos los estatus</option>
                        <option value="Pendiente">Pendiente</option>
                        <option value="En_Revision">En revisión</option>
                        <option value="Aprobada">Aprobada</option>
                        <option value="Rechazada">Rechazada</option>
                    </select>
                </div>
                <select v-model="filtroRapido" class="px-4 py-2.5 rounded-xl border border-slate-200 dark:border-zinc-700 dark:bg-zinc-800 text-sm">
                    <option value="">Todas</option>
                    <option value="vencidas">Vencidas</option>
                    <option value="proximas">Próximas a vencer (≤7 días)</option>
                </select>
            </div>

            <!-- Vacío -->
            <div v-if="comprobaciones.data.length === 0" class="bg-white dark:bg-zinc-900 rounded-2xl border border-slate-100 dark:border-zinc-800 py-16 text-center text-slate-400 dark:text-zinc-600 text-sm shadow-sm">
                No hay comprobaciones con los filtros seleccionados.
            </div>

            <!-- Cards (mobile) -->
            <div v-if="comprobaciones.data.length > 0" class="sm:hidden space-y-3">
                <div v-for="c in comprobaciones.data" :key="c.id"
                    class="bg-white dark:bg-zinc-900 rounded-2xl border border-slate-100 dark:border-zinc-800 p-4 shadow-sm">
                    <div class="flex items-start justify-between gap-2 mb-2">
                        <div class="min-w-0">
                            <p class="font-bold text-sm text-slate-900 dark:text-white truncate">{{ c.acreditado || '—' }}</p>
                            <p class="text-xs text-slate-400 dark:text-zinc-500 font-mono">{{ c.clave_contrato || '—' }}</p>
                        </div>
                        <span :class="['shrink-0 inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold', semaforoEstilo[c.semaforo]?.bg, semaforoEstilo[c.semaforo]?.text]">
                            {{ c.dias_restantes > 0 ? `${c.dias_restantes} días` : 'Vencido' }}
                        </span>
                    </div>
                    <div class="grid grid-cols-2 gap-2 text-xs text-slate-500 dark:text-zinc-400">
                        <div><span class="font-medium text-slate-400 dark:text-zinc-500">Modalidad:</span> {{ c.modalidad || '—' }}</div>
                        <div><span class="font-medium text-slate-400 dark:text-zinc-500">Estatus:</span> {{ c.estatus.replace('_', ' ') }}</div>
                        <div><span class="font-medium text-slate-400 dark:text-zinc-500">Desembolso:</span> {{ c.fecha_desembolso }}</div>
                        <div><span class="font-medium text-slate-400 dark:text-zinc-500">Límite:</span> {{ c.fecha_limite }}</div>
                    </div>
                    <Link :href="route('comprobaciones.show', c.id)"
                        class="mt-3 flex items-center justify-center gap-1.5 w-full py-2.5 bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 rounded-xl text-xs font-bold">
                        <Eye size="13" /> Revisar
                    </Link>
                </div>
            </div>

            <!-- Tabla (sm+) -->
            <div v-if="comprobaciones.data.length > 0" class="hidden sm:block bg-white dark:bg-zinc-900 rounded-2xl border border-slate-100 dark:border-zinc-800 overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[700px] text-sm text-left">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-zinc-800 text-slate-400 dark:text-zinc-500 text-[11px] uppercase tracking-widest font-bold">
                                <th class="px-5 py-4">Acreditado</th>
                                <th class="px-5 py-4">Contrato</th>
                                <th class="px-5 py-4">Modalidad</th>
                                <th class="px-5 py-4">Fecha desembolso</th>
                                <th class="px-5 py-4">Fecha límite</th>
                                <th class="px-5 py-4">Días restantes</th>
                                <th class="px-5 py-4">Estatus</th>
                                <th class="px-5 py-4 text-center">Acción</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50 dark:divide-zinc-800">
                            <tr v-for="c in comprobaciones.data" :key="c.id" class="hover:bg-slate-50/80 dark:hover:bg-zinc-800/50 transition-colors">
                                <td class="px-5 py-4 font-bold text-slate-900 dark:text-white">{{ c.acreditado || '—' }}</td>
                                <td class="px-5 py-4 font-mono text-xs text-slate-600 dark:text-zinc-400">{{ c.clave_contrato || '—' }}</td>
                                <td class="px-5 py-4 text-slate-600 dark:text-zinc-400">{{ c.modalidad || '—' }}</td>
                                <td class="px-5 py-4 text-slate-600 dark:text-zinc-400">{{ c.fecha_desembolso }}</td>
                                <td class="px-5 py-4 text-slate-600 dark:text-zinc-400">{{ c.fecha_limite }}</td>
                                <td class="px-5 py-4">
                                    <span :class="['inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold', semaforoEstilo[c.semaforo]?.bg, semaforoEstilo[c.semaforo]?.text]">
                                        {{ c.dias_restantes > 0 ? `${c.dias_restantes} días` : 'Vencido' }} · {{ semaforoEstilo[c.semaforo]?.label }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-slate-600 dark:text-zinc-400">{{ c.estatus.replace('_', ' ') }}</td>
                                <td class="px-5 py-4 text-center">
                                    <Link :href="route('comprobaciones.show', c.id)"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/40 rounded-lg text-xs font-bold transition-colors">
                                        <Eye size="13" /> Revisar
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Paginación -->
            <div v-if="comprobaciones.meta?.last_page > 1" class="bg-white dark:bg-zinc-900 rounded-2xl border border-slate-100 dark:border-zinc-800 px-5 py-4 flex flex-wrap items-center justify-between gap-2 shadow-sm">
                <p class="text-xs text-slate-400">Mostrando {{ comprobaciones.meta.from }}–{{ comprobaciones.meta.to }} de {{ comprobaciones.meta.total }}</p>
            </div>
            <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-slate-100 dark:border-zinc-800 px-5 py-4 flex flex-wrap gap-1" v-if="comprobaciones.links?.length > 3">
                <Link v-for="link in comprobaciones.links" :key="link.label" :href="link.url ?? '#'"
                    :class="['px-3 py-1.5 rounded-lg text-xs font-bold transition-colors',
                        link.active ? 'bg-red-700 text-white' : 'text-slate-500 hover:bg-slate-100 dark:hover:bg-zinc-800',
                        !link.url ? 'opacity-40 pointer-events-none' : '']"
                    v-html="link.label" />
            </div>
        </div>
    </AppLayout>
</template>
