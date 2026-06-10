<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import {
    Search, Eye, Filter, Gavel, Scale, Clock, CheckCircle2,
    AlertTriangle, RefreshCw, DollarSign, TrendingDown, Users, ArrowLeft
} from 'lucide-vue-next';

interface Expediente {
    id: number;
    estatus: string;
    fecha_envio_juridico: string;
    tribunal?: string;
    juzgado?: string;
    numero_expediente_judicial?: string;
    monto_reclamado: number;
    dias_en_juridico: number;
    credito: { id: number; clave_contrato: string; modalidad?: string };
    acreditado: { nombre_completo: string; municipio: string };
    usuario: string;
}

const props = defineProps<{
    expedientes: Expediente[];
    kpis: {
        total: number;
        pendiente: number;
        en_demanda: number;
        en_platicas: number;
        reestructurada: number;
        recuperada: number;
        monto_total: number;
    };
    filters: { estatus?: string; buscar?: string };
}>();

const buscar = ref(props.filters?.buscar ?? '');
const estatus = ref(props.filters?.estatus ?? '');

const estatusOpciones = [
    { value: '', label: 'Todos los estatus' },
    { value: 'Pendiente', label: 'Pendiente' },
    { value: 'En_Demanda', label: 'En Demanda' },
    { value: 'En_Platicas', label: 'En Pláticas' },
    { value: 'Reestructurada', label: 'Reestructurada' },
    { value: 'Recuperada', label: 'Recuperada' },
];

let timeout: ReturnType<typeof setTimeout>;
watch([buscar, estatus], () => {
    clearTimeout(timeout);
    timeout = setTimeout(() => {
        router.get(route('juridico.index'), { buscar: buscar.value, estatus: estatus.value }, { preserveState: true, replace: true });
    }, 400);
});

const estatusConfig: Record<string, { bg: string; text: string; icon: any }> = {
    Pendiente:     { bg: 'bg-slate-100 dark:bg-zinc-800', text: 'text-slate-600 dark:text-zinc-400', icon: Clock },
    En_Demanda:    { bg: 'bg-red-100 dark:bg-red-900/30', text: 'text-red-700 dark:text-red-400', icon: Gavel },
    En_Platicas:   { bg: 'bg-blue-100 dark:bg-blue-900/30', text: 'text-blue-700 dark:text-blue-400', icon: Scale },
    Reestructurada:{ bg: 'bg-amber-100 dark:bg-amber-900/30', text: 'text-amber-700 dark:text-amber-400', icon: AlertTriangle },
    Recuperada:    { bg: 'bg-green-100 dark:bg-green-900/30', text: 'text-green-700 dark:text-green-400', icon: CheckCircle2 },
};

const fmt = (n: number) => '$' + n.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
</script>

<template>
    <AppLayout>
        <Head title="Cobranza Jurídica — CREA" />

        <div class="p-6 space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center gap-4">
                    <Link :href="route('cobranza.index')"
                        class="p-2 rounded-xl bg-slate-100 dark:bg-zinc-800 text-slate-600 dark:text-zinc-400 hover:bg-slate-200 dark:hover:bg-zinc-700 transition-all">
                        <ArrowLeft size="18" />
                    </Link>
                    <div>
                        <h1 class="text-2xl font-black text-slate-900 dark:text-white flex items-center gap-3">
                            <Scale size="26" class="text-purple-700" /> Cobranza Jurídica
                        </h1>
                        <p class="text-slate-500 dark:text-zinc-400 text-sm mt-1">
                            Expedientes de créditos en proceso legal
                        </p>
                    </div>
                </div>
                <button @click="router.reload()" class="p-2.5 rounded-xl bg-slate-100 dark:bg-zinc-800 text-slate-600 dark:text-zinc-400 hover:bg-slate-200 dark:hover:bg-zinc-700 transition-all self-start sm:self-auto">
                    <RefreshCw size="16" />
                </button>
            </div>

            <!-- KPIs -->
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-3">
                <div class="bg-purple-50 dark:bg-purple-900/20 rounded-2xl p-4">
                    <Gavel size="18" class="text-purple-700 mb-2" />
                    <p class="text-2xl font-black text-purple-700">{{ kpis.total }}</p>
                    <p class="text-[11px] font-semibold text-slate-500 dark:text-zinc-400">Total Expedientes</p>
                </div>
                <button @click="estatus = 'Pendiente'" :class="['rounded-2xl p-4 text-left transition-all hover:scale-[1.02]', estatus === 'Pendiente' ? 'ring-2 ring-slate-400' : '', 'bg-slate-100 dark:bg-zinc-800']">
                    <Clock size="18" class="text-slate-600 dark:text-zinc-400 mb-2" />
                    <p class="text-2xl font-black text-slate-700 dark:text-zinc-200">{{ kpis.pendiente }}</p>
                    <p class="text-[11px] font-semibold text-slate-500 dark:text-zinc-400">Pendiente</p>
                </button>
                <button @click="estatus = 'En_Demanda'" :class="['rounded-2xl p-4 text-left transition-all hover:scale-[1.02]', estatus === 'En_Demanda' ? 'ring-2 ring-red-500' : '', 'bg-red-50 dark:bg-red-900/20']">
                    <Gavel size="18" class="text-red-700 mb-2" />
                    <p class="text-2xl font-black text-red-700">{{ kpis.en_demanda }}</p>
                    <p class="text-[11px] font-semibold text-slate-500 dark:text-zinc-400">En Demanda</p>
                </button>
                <button @click="estatus = 'En_Platicas'" :class="['rounded-2xl p-4 text-left transition-all hover:scale-[1.02]', estatus === 'En_Platicas' ? 'ring-2 ring-blue-500' : '', 'bg-blue-50 dark:bg-blue-900/20']">
                    <Scale size="18" class="text-blue-700 mb-2" />
                    <p class="text-2xl font-black text-blue-700">{{ kpis.en_platicas }}</p>
                    <p class="text-[11px] font-semibold text-slate-500 dark:text-zinc-400">En Pláticas</p>
                </button>
                <button @click="estatus = 'Reestructurada'" :class="['rounded-2xl p-4 text-left transition-all hover:scale-[1.02]', estatus === 'Reestructurada' ? 'ring-2 ring-amber-500' : '', 'bg-amber-50 dark:bg-amber-900/20']">
                    <AlertTriangle size="18" class="text-amber-700 mb-2" />
                    <p class="text-2xl font-black text-amber-700">{{ kpis.reestructurada }}</p>
                    <p class="text-[11px] font-semibold text-slate-500 dark:text-zinc-400">Reestructurada</p>
                </button>
                <button @click="estatus = 'Recuperada'" :class="['rounded-2xl p-4 text-left transition-all hover:scale-[1.02]', estatus === 'Recuperada' ? 'ring-2 ring-green-500' : '', 'bg-green-50 dark:bg-green-900/20']">
                    <CheckCircle2 size="18" class="text-green-700 mb-2" />
                    <p class="text-2xl font-black text-green-700">{{ kpis.recuperada }}</p>
                    <p class="text-[11px] font-semibold text-slate-500 dark:text-zinc-400">Recuperada</p>
                </button>
                <div class="bg-slate-800 dark:bg-zinc-700 rounded-2xl p-4">
                    <DollarSign size="18" class="text-white mb-2" />
                    <p class="text-base font-black text-white leading-tight">{{ fmt(kpis.monto_total) }}</p>
                    <p class="text-[11px] font-semibold text-slate-300">Monto en Proceso</p>
                </div>
            </div>

            <!-- Filtros -->
            <div class="flex flex-col sm:flex-row gap-3">
                <div class="relative flex-1 max-w-sm">
                    <Search size="16" class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400" />
                    <input v-model="buscar" type="text" placeholder="Buscar por nombre, contrato o tribunal..."
                        class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 dark:border-zinc-700 dark:bg-zinc-800 text-sm focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 transition-all" />
                </div>
                <div class="relative">
                    <Filter size="16" class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none" />
                    <select v-model="estatus"
                        class="pl-10 pr-8 py-2.5 rounded-xl border border-slate-200 dark:border-zinc-700 dark:bg-zinc-800 text-sm focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 transition-all appearance-none">
                        <option v-for="op in estatusOpciones" :key="op.value" :value="op.value">{{ op.label }}</option>
                    </select>
                </div>
            </div>

            <!-- Tabla -->
            <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-slate-100 dark:border-zinc-800 overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-zinc-800 text-slate-400 dark:text-zinc-500 text-[11px] uppercase tracking-widest font-bold">
                                <th class="px-5 py-4">Acreditado</th>
                                <th class="px-5 py-4">Contrato</th>
                                <th class="px-5 py-4">Estatus</th>
                                <th class="px-5 py-4">Tribunal / Juzgado</th>
                                <th class="px-5 py-4">No. Expediente</th>
                                <th class="px-5 py-4">Monto Reclamado</th>
                                <th class="px-5 py-4">Días en Jurídico</th>
                                <th class="px-5 py-4 text-center">Acción</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50 dark:divide-zinc-800">
                            <tr v-if="expedientes.length === 0">
                                <td colspan="8" class="px-5 py-16 text-center text-slate-400 dark:text-zinc-600">
                                    <Scale size="32" class="mx-auto mb-3 opacity-40" />
                                    <p class="font-medium">No hay expedientes jurídicos.</p>
                                </td>
                            </tr>
                            <tr v-for="e in expedientes" :key="e.id"
                                class="hover:bg-slate-50/80 dark:hover:bg-zinc-800/50 transition-colors">
                                <td class="px-5 py-4">
                                    <p class="font-bold text-slate-900 dark:text-white">{{ e.acreditado.nombre_completo }}</p>
                                    <p class="text-xs text-slate-400 mt-0.5">{{ e.acreditado.municipio }}</p>
                                </td>
                                <td class="px-5 py-4">
                                    <p class="font-mono text-xs font-bold text-slate-700 dark:text-zinc-300">{{ e.credito.clave_contrato }}</p>
                                    <p class="text-xs text-slate-400">{{ e.credito.modalidad }}</p>
                                </td>
                                <td class="px-5 py-4">
                                    <div :class="['inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold', estatusConfig[e.estatus]?.bg, estatusConfig[e.estatus]?.text]">
                                        <component :is="estatusConfig[e.estatus]?.icon" size="12" />
                                        {{ e.estatus.replace('_', ' ') }}
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <p class="text-xs text-slate-700 dark:text-zinc-300 font-medium">{{ e.tribunal || '—' }}</p>
                                    <p class="text-xs text-slate-400">{{ e.juzgado || '' }}</p>
                                </td>
                                <td class="px-5 py-4 font-mono text-xs text-slate-600 dark:text-zinc-400">{{ e.numero_expediente_judicial || '—' }}</td>
                                <td class="px-5 py-4 font-bold text-purple-700 dark:text-purple-400">{{ fmt(e.monto_reclamado) }}</td>
                                <td class="px-5 py-4">
                                    <span :class="['font-bold text-sm px-2 py-1 rounded-lg',
                                        e.dias_en_juridico > 180 ? 'bg-red-100 text-red-700 dark:bg-red-900/30' :
                                        e.dias_en_juridico > 90  ? 'bg-orange-100 text-orange-700 dark:bg-orange-900/30' :
                                        'bg-slate-100 text-slate-600 dark:bg-zinc-800 dark:text-zinc-400']">
                                        {{ e.dias_en_juridico }} días
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-center">
                                    <Link :href="route('juridico.show', e.id)"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-purple-50 dark:bg-purple-900/20 text-purple-700 dark:text-purple-400 hover:bg-purple-100 rounded-lg text-xs font-bold transition-colors">
                                        <Eye size="13" /> Ver
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
