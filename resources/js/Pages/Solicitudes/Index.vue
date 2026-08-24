<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import {
    Search, Eye, Filter, FileText, Clock, CheckCircle2, XCircle,
    AlertTriangle, RefreshCw, Users, TrendingUp, CalendarDays, Inbox, CreditCard
} from 'lucide-vue-next';

const props = defineProps<{
    solicitudes: {
        data: Array<{
            id: number;
            nombre_completo?: string;
            curp?: string;
            municipio?: string;
            modalidad?: string;
            estatus: string;
            correo?: string;
            telefono?: string;
            created_at: string;
            updated_at: string;
            user_email?: string;
            credito_id?: number;
            acreditado_id?: number;
        }>;
        links: any[];
        meta: any;
    };
    filters: { estatus?: string; buscar?: string };
    kpis: {
        total: number;
        enviadas: number;
        en_revision: number;
        documentacion_incompleta: number;
        aprobadas: number;
        rechazadas: number;
        hoy: number;
    };
}>();

const estatusFiltro = ref(props.filters?.estatus ?? '');
const buscar = ref(props.filters?.buscar ?? '');

const estatusOpciones = [
    { value: '', label: 'Todos los estatus' },
    { value: 'Enviada', label: 'Enviadas' },
    { value: 'En_Revision', label: 'En Revisión' },
    { value: 'Documentacion_Incompleta', label: 'Doc. Incompleta' },
    { value: 'Aprobada', label: 'Aprobadas' },
    { value: 'Rechazada', label: 'Rechazadas' },
    { value: 'Borrador', label: 'Borradores' },
];

const estatusEstilo: Record<string, { bg: string; text: string; icon: any }> = {
    Borrador:                { bg: 'bg-slate-100 dark:bg-zinc-800', text: 'text-slate-600 dark:text-zinc-400', icon: FileText },
    Enviada:                 { bg: 'bg-blue-100 dark:bg-blue-900/30', text: 'text-blue-700 dark:text-blue-400', icon: Clock },
    En_Revision:             { bg: 'bg-amber-100 dark:bg-amber-900/30', text: 'text-amber-700 dark:text-amber-400', icon: RefreshCw },
    Documentacion_Incompleta:{ bg: 'bg-orange-100 dark:bg-orange-900/30', text: 'text-orange-700 dark:text-orange-400', icon: AlertTriangle },
    Aprobada:                { bg: 'bg-green-100 dark:bg-green-900/30', text: 'text-green-700 dark:text-green-400', icon: CheckCircle2 },
    Rechazada:               { bg: 'bg-red-100 dark:bg-red-900/30', text: 'text-red-700 dark:text-red-400', icon: XCircle },
};

let timeout: ReturnType<typeof setTimeout>;
watch([estatusFiltro, buscar], () => {
    clearTimeout(timeout);
    timeout = setTimeout(() => {
        router.get(route('solicitudes.index'), { estatus: estatusFiltro.value, buscar: buscar.value }, { preserveState: true, replace: true });
    }, 400);
});

const kpiCards = [
    { label: 'Total Recibidas',    value: () => props.kpis.total,                   color: 'text-slate-700', bg: 'bg-slate-100 dark:bg-zinc-800', icon: Inbox },
    { label: 'Nuevas Hoy',         value: () => props.kpis.hoy,                     color: 'text-purple-700', bg: 'bg-purple-50 dark:bg-purple-900/20', icon: CalendarDays },
    { label: 'En Espera Revisión', value: () => props.kpis.enviadas,                color: 'text-blue-700', bg: 'bg-blue-50 dark:bg-blue-900/20', icon: Clock },
    { label: 'En Revisión',        value: () => props.kpis.en_revision,             color: 'text-amber-700', bg: 'bg-amber-50 dark:bg-amber-900/20', icon: RefreshCw },
    { label: 'Doc. Incompleta',    value: () => props.kpis.documentacion_incompleta, color: 'text-orange-700', bg: 'bg-orange-50 dark:bg-orange-900/20', icon: AlertTriangle },
    { label: 'Aprobadas',          value: () => props.kpis.aprobadas,               color: 'text-green-700', bg: 'bg-green-50 dark:bg-green-900/20', icon: CheckCircle2 },
    { label: 'Rechazadas',         value: () => props.kpis.rechazadas,              color: 'text-red-700', bg: 'bg-red-50 dark:bg-red-900/20', icon: XCircle },
    { label: 'Tasa Aprobación',
      value: () => props.kpis.total > 0 ? Math.round((props.kpis.aprobadas / props.kpis.total) * 100) + '%' : '0%',
      color: 'text-emerald-700', bg: 'bg-emerald-50 dark:bg-emerald-900/20', icon: TrendingUp },
];
</script>

<template>
    <AppLayout>
        <Head title="Recepción de Solicitudes — CREA" />

        <div class="p-6 space-y-6">
            <!-- Encabezado -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-black text-slate-900 dark:text-white flex items-center gap-3">
                        <Inbox size="26" class="text-red-700" /> Recepción de Solicitudes
                    </h1>
                    <p class="text-slate-500 dark:text-zinc-400 text-sm mt-1">
                        Gestión y revisión de solicitudes ciudadanas de crédito CREA
                    </p>
                </div>
                <button @click="router.reload({ preserveScroll: true })"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-slate-100 dark:bg-zinc-800 text-slate-700 dark:text-zinc-300 hover:bg-slate-200 dark:hover:bg-zinc-700 rounded-xl text-sm font-semibold transition-all">
                    <RefreshCw size="15" /> Actualizar
                </button>
            </div>

            <!-- KPIs -->
            <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-3">
                <button v-for="kpi in kpiCards" :key="kpi.label"
                    @click="kpi.label !== 'Tasa Aprobación' && kpi.label !== 'Total Recibidas' && kpi.label !== 'Nuevas Hoy'
                        ? estatusFiltro = estatusOpciones.find(o => kpi.label.includes(o.label.split(' ')[0]))?.value ?? ''
                        : null"
                    :class="['rounded-2xl p-4 text-left transition-all hover:scale-[1.02] border border-transparent hover:border-slate-200 dark:hover:border-zinc-700', kpi.bg]">
                    <component :is="kpi.icon" :class="['size-5 mb-2', kpi.color]" />
                    <p :class="['text-2xl font-black', kpi.color]">{{ kpi.value() }}</p>
                    <p class="text-[11px] font-semibold text-slate-500 dark:text-zinc-400 mt-0.5 leading-tight">{{ kpi.label }}</p>
                </button>
            </div>

            <!-- Filtros -->
            <div class="flex flex-col sm:flex-row gap-3">
                <div class="relative flex-1 max-w-sm">
                    <Search size="16" class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400" />
                    <input v-model="buscar" type="text" placeholder="Buscar por nombre, CURP o municipio..."
                        class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 dark:border-zinc-700 dark:bg-zinc-800 text-sm focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-all" />
                </div>
                <div class="relative w-full sm:w-auto">
                    <Filter size="16" class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none" />
                    <select v-model="estatusFiltro"
                        class="w-full sm:w-auto pl-10 pr-8 py-2.5 rounded-xl border border-slate-200 dark:border-zinc-700 dark:bg-zinc-800 text-sm focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-all appearance-none">
                        <option v-for="op in estatusOpciones" :key="op.value" :value="op.value">{{ op.label }}</option>
                    </select>
                </div>
            </div>

            <!-- Vacío -->
            <div v-if="solicitudes.data.length === 0" class="bg-white dark:bg-zinc-900 rounded-2xl border border-slate-100 dark:border-zinc-800 py-16 text-center text-slate-400 dark:text-zinc-600 text-sm shadow-sm">
                No hay solicitudes con los filtros seleccionados.
            </div>

            <!-- Cards (mobile) -->
            <div v-if="solicitudes.data.length > 0" class="sm:hidden space-y-3">
                <div v-for="s in solicitudes.data" :key="s.id"
                    class="bg-white dark:bg-zinc-900 rounded-2xl border border-slate-100 dark:border-zinc-800 p-4 shadow-sm">
                    <div class="flex items-start justify-between gap-2 mb-2">
                        <div class="min-w-0">
                            <p class="font-bold text-sm text-slate-900 dark:text-white truncate">{{ s.nombre_completo || 'Sin nombre' }}</p>
                            <p class="text-xs text-slate-400 dark:text-zinc-500 truncate">{{ s.user_email }}</p>
                        </div>
                        <div :class="['shrink-0 inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold', estatusEstilo[s.estatus]?.bg, estatusEstilo[s.estatus]?.text]">
                            <component :is="estatusEstilo[s.estatus]?.icon" size="11" />
                            {{ s.estatus.replace('_', ' ') }}
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-2 text-xs text-slate-500 dark:text-zinc-400">
                        <div><span class="font-medium text-slate-400 dark:text-zinc-500">CURP:</span> <span class="font-mono">{{ s.curp || '—' }}</span></div>
                        <div><span class="font-medium text-slate-400 dark:text-zinc-500">Municipio:</span> {{ s.municipio || '—' }}</div>
                        <div><span class="font-medium text-slate-400 dark:text-zinc-500">Modalidad:</span> {{ s.modalidad || '—' }}</div>
                        <div><span class="font-medium text-slate-400 dark:text-zinc-500">Actualizado:</span> {{ s.updated_at }}</div>
                    </div>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <Link :href="route('solicitudes.show', s.id)"
                            class="flex-1 inline-flex items-center justify-center gap-1.5 py-2.5 bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 rounded-xl text-xs font-bold">
                            <Eye size="13" /> Revisar
                        </Link>
                        <Link v-if="s.estatus === 'Aprobada' && !s.credito_id"
                            :href="route('solicitudes.registrar-credito', s.id)"
                            class="flex-1 inline-flex items-center justify-center gap-1.5 py-2.5 bg-green-600 text-white rounded-xl text-xs font-bold shadow-sm">
                            <CreditCard size="13" /> Registrar
                        </Link>
                        <Link v-else-if="s.estatus === 'Aprobada' && s.credito_id"
                            :href="route('operaciones.index', s.acreditado_id)"
                            class="flex-1 inline-flex items-center justify-center gap-1.5 py-2.5 bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400 rounded-xl text-xs font-bold">
                            <CreditCard size="13" /> Ver crédito
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Tabla (sm+) -->
            <div v-if="solicitudes.data.length > 0" class="hidden sm:block bg-white dark:bg-zinc-900 rounded-2xl border border-slate-100 dark:border-zinc-800 overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[640px] text-sm text-left">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-zinc-800 text-slate-400 dark:text-zinc-500 text-[11px] uppercase tracking-widest font-bold">
                                <th class="px-5 py-4">Solicitante</th>
                                <th class="px-5 py-4">CURP</th>
                                <th class="px-5 py-4">Municipio</th>
                                <th class="px-5 py-4">Modalidad</th>
                                <th class="px-5 py-4">Estatus</th>
                                <th class="px-5 py-4">Actualización</th>
                                <th class="px-5 py-4 text-center">Acción</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50 dark:divide-zinc-800">
                            <tr v-for="s in solicitudes.data" :key="s.id"
                                class="hover:bg-slate-50/80 dark:hover:bg-zinc-800/50 transition-colors">
                                <td class="px-5 py-4">
                                    <p class="font-bold text-slate-900 dark:text-white">{{ s.nombre_completo || 'Sin nombre' }}</p>
                                    <p class="text-xs text-slate-400 dark:text-zinc-500 mt-0.5">{{ s.user_email }}</p>
                                </td>
                                <td class="px-5 py-4 font-mono text-xs text-slate-600 dark:text-zinc-400">{{ s.curp || '—' }}</td>
                                <td class="px-5 py-4 text-slate-600 dark:text-zinc-400">{{ s.municipio || '—' }}</td>
                                <td class="px-5 py-4 text-slate-600 dark:text-zinc-400">{{ s.modalidad || '—' }}</td>
                                <td class="px-5 py-4">
                                    <div :class="['inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold', estatusEstilo[s.estatus]?.bg, estatusEstilo[s.estatus]?.text]">
                                        <component :is="estatusEstilo[s.estatus]?.icon" size="12" />
                                        {{ s.estatus.replace('_', ' ') }}
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-slate-500 dark:text-zinc-400 text-xs">{{ s.updated_at }}</td>
                                <td class="px-5 py-4 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <Link :href="route('solicitudes.show', s.id)"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/40 rounded-lg text-xs font-bold transition-colors">
                                            <Eye size="13" /> Revisar
                                        </Link>
                                        <!-- Registrar Crédito: solo solicitudes Aprobadas sin crédito -->
                                        <Link v-if="s.estatus === 'Aprobada' && !s.credito_id"
                                            :href="route('solicitudes.registrar-credito', s.id)"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white rounded-lg text-xs font-bold transition-colors shadow-sm">
                                            <CreditCard size="13" /> Registrar
                                        </Link>
                                        <!-- Ver crédito: solicitudes Aprobadas con crédito ya registrado -->
                                        <Link v-else-if="s.estatus === 'Aprobada' && s.credito_id"
                                            :href="route('operaciones.index', s.acreditado_id)"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400 hover:bg-blue-100 dark:hover:bg-blue-900/40 rounded-lg text-xs font-bold transition-colors">
                                            <CreditCard size="13" /> Ver crédito
                                        </Link>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Paginación -->
            <div v-if="solicitudes.meta?.last_page > 1" class="bg-white dark:bg-zinc-900 rounded-2xl border border-slate-100 dark:border-zinc-800 px-5 py-4 flex flex-wrap items-center justify-between gap-2 shadow-sm">
                <p class="text-xs text-slate-400">
                    Mostrando {{ solicitudes.meta.from }}–{{ solicitudes.meta.to }} de {{ solicitudes.meta.total }}
                </p>
                <div class="flex flex-wrap gap-1">
                    <Link v-for="link in solicitudes.links" :key="link.label"
                        :href="link.url ?? '#'"
                        :class="['px-3 py-1.5 rounded-lg text-xs font-bold transition-colors',
                            link.active ? 'bg-red-700 text-white' : 'text-slate-500 hover:bg-slate-100 dark:hover:bg-zinc-800',
                            !link.url ? 'opacity-40 pointer-events-none' : '']"
                        v-html="link.label" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
