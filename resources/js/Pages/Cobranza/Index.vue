<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import {
    Search, Eye, Filter, AlertTriangle, DollarSign, Clock,
    Shield, Users, TrendingDown, Gavel, PhoneCall, RefreshCw
} from 'lucide-vue-next';

interface Credito {
    id: number;
    clave_contrato: string;
    monto_otorgado: number;
    deuda_total: number;
    cuotas_pendientes: number;
    dias_atraso: number;
    ultimo_pago?: string;
    gestiones_count: number;
    tiene_juridico: boolean;
    estatus_juridico?: string;
    modalidad?: string;
    acreditado: { nombre_completo: string; municipio: string; correo: string };
}

const props = defineProps<{
    creditos: Credito[];
    kpis: {
        total_morosos: number;
        monto_total_mora: number;
        rango_0_30: number;
        rango_31_60: number;
        rango_61_90: number;
        rango_mas_90: number;
        en_juridico: number;
        gestiones_hoy: number;
    };
    filters: { buscar?: string; rango?: string };
}>();

const buscar = ref(props.filters?.buscar ?? '');
const rango  = ref(props.filters?.rango ?? '');

const rangos = [
    { value: '', label: 'Todos los rangos' },
    { value: '0-30', label: '0–30 días' },
    { value: '31-60', label: '31–60 días' },
    { value: '61-90', label: '61–90 días' },
    { value: '+90', label: '+ 90 días' },
];

let timeout: ReturnType<typeof setTimeout>;
watch([buscar, rango], () => {
    clearTimeout(timeout);
    timeout = setTimeout(() => {
        router.get(route('cobranza.index'), { buscar: buscar.value, rango: rango.value }, { preserveState: true, replace: true });
    }, 400);
});

const diasColor = (d: number) => {
    if (d === 0)   return { badge: 'bg-slate-100 text-slate-600 dark:bg-zinc-800 dark:text-zinc-400', dot: 'bg-slate-400' };
    if (d <= 30)   return { badge: 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400', dot: 'bg-amber-500' };
    if (d <= 60)   return { badge: 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400', dot: 'bg-orange-500' };
    if (d <= 90)   return { badge: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400', dot: 'bg-red-600' };
    return { badge: 'bg-red-900/20 text-red-900 dark:text-red-300', dot: 'bg-red-900' };
};

const fmt = (n: number) => '$' + n.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
</script>

<template>
    <AppLayout>
        <Head title="Cobranza — CREA" />

        <div class="p-6 space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-black text-slate-900 dark:text-white flex items-center gap-3">
                        <TrendingDown size="26" class="text-red-700" /> Módulo de Cobranza
                    </h1>
                    <p class="text-slate-500 dark:text-zinc-400 text-sm mt-1">
                        Seguimiento de créditos con pagos pendientes y gestión de cobros
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <button @click="router.reload()" class="p-2.5 rounded-xl bg-slate-100 dark:bg-zinc-800 text-slate-600 dark:text-zinc-400 hover:bg-slate-200 dark:hover:bg-zinc-700 transition-all">
                        <RefreshCw size="16" />
                    </button>
                </div>
            </div>

            <!-- KPIs -->
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-8 gap-3">
                <div class="bg-red-50 dark:bg-red-900/20 rounded-2xl p-4">
                    <Users size="18" class="text-red-700 mb-2" />
                    <p class="text-2xl font-black text-red-700">{{ kpis.total_morosos }}</p>
                    <p class="text-[11px] font-semibold text-slate-500 dark:text-zinc-400">Créditos en Mora</p>
                </div>
                <div class="bg-slate-100 dark:bg-zinc-800 rounded-2xl p-4">
                    <DollarSign size="18" class="text-slate-600 dark:text-zinc-400 mb-2" />
                    <p class="text-lg font-black text-slate-800 dark:text-white leading-tight">{{ fmt(kpis.monto_total_mora) }}</p>
                    <p class="text-[11px] font-semibold text-slate-500 dark:text-zinc-400">Total en Mora</p>
                </div>
                <button @click="rango = '0-30'"
                    :class="['rounded-2xl p-4 text-left transition-all hover:scale-[1.02]', rango === '0-30' ? 'ring-2 ring-amber-500' : '', 'bg-amber-50 dark:bg-amber-900/20']">
                    <Clock size="18" class="text-amber-600 mb-2" />
                    <p class="text-2xl font-black text-amber-700">{{ kpis.rango_0_30 }}</p>
                    <p class="text-[11px] font-semibold text-slate-500 dark:text-zinc-400">0–30 días</p>
                </button>
                <button @click="rango = '31-60'"
                    :class="['rounded-2xl p-4 text-left transition-all hover:scale-[1.02]', rango === '31-60' ? 'ring-2 ring-orange-500' : '', 'bg-orange-50 dark:bg-orange-900/20']">
                    <AlertTriangle size="18" class="text-orange-600 mb-2" />
                    <p class="text-2xl font-black text-orange-700">{{ kpis.rango_31_60 }}</p>
                    <p class="text-[11px] font-semibold text-slate-500 dark:text-zinc-400">31–60 días</p>
                </button>
                <button @click="rango = '61-90'"
                    :class="['rounded-2xl p-4 text-left transition-all hover:scale-[1.02]', rango === '61-90' ? 'ring-2 ring-red-500' : '', 'bg-red-50 dark:bg-red-900/20']">
                    <AlertTriangle size="18" class="text-red-600 mb-2" />
                    <p class="text-2xl font-black text-red-700">{{ kpis.rango_61_90 }}</p>
                    <p class="text-[11px] font-semibold text-slate-500 dark:text-zinc-400">61–90 días</p>
                </button>
                <button @click="rango = '+90'"
                    :class="['rounded-2xl p-4 text-left transition-all hover:scale-[1.02]', rango === '+90' ? 'ring-2 ring-red-900' : '', 'bg-red-900/10 dark:bg-red-900/30']">
                    <AlertTriangle size="18" class="text-red-900 dark:text-red-400 mb-2" />
                    <p class="text-2xl font-black text-red-900 dark:text-red-400">{{ kpis.rango_mas_90 }}</p>
                    <p class="text-[11px] font-semibold text-slate-500 dark:text-zinc-400">+90 días (crítico)</p>
                </button>
                <div class="bg-purple-50 dark:bg-purple-900/20 rounded-2xl p-4">
                    <Gavel size="18" class="text-purple-700 mb-2" />
                    <p class="text-2xl font-black text-purple-700">{{ kpis.en_juridico }}</p>
                    <p class="text-[11px] font-semibold text-slate-500 dark:text-zinc-400">En Jurídico</p>
                </div>
                <div class="bg-green-50 dark:bg-green-900/20 rounded-2xl p-4">
                    <PhoneCall size="18" class="text-green-700 mb-2" />
                    <p class="text-2xl font-black text-green-700">{{ kpis.gestiones_hoy }}</p>
                    <p class="text-[11px] font-semibold text-slate-500 dark:text-zinc-400">Gestiones Hoy</p>
                </div>
            </div>

            <!-- Filtros -->
            <div class="flex flex-col sm:flex-row gap-3">
                <div class="relative flex-1 max-w-sm">
                    <Search size="16" class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400" />
                    <input v-model="buscar" type="text" placeholder="Buscar por nombre, contrato o CURP..."
                        class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 dark:border-zinc-700 dark:bg-zinc-800 text-sm focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-all" />
                </div>
                <div class="relative">
                    <Filter size="16" class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none" />
                    <select v-model="rango"
                        class="pl-10 pr-8 py-2.5 rounded-xl border border-slate-200 dark:border-zinc-700 dark:bg-zinc-800 text-sm focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-all appearance-none">
                        <option v-for="r in rangos" :key="r.value" :value="r.value">{{ r.label }}</option>
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
                                <th class="px-5 py-4">Días Atraso</th>
                                <th class="px-5 py-4">Deuda Total</th>
                                <th class="px-5 py-4">Cuotas Pend.</th>
                                <th class="px-5 py-4">Último Pago</th>
                                <th class="px-5 py-4">Gestiones</th>
                                <th class="px-5 py-4 text-center">Acción</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50 dark:divide-zinc-800">
                            <tr v-if="creditos.length === 0">
                                <td colspan="8" class="px-5 py-16 text-center text-slate-400 dark:text-zinc-600">
                                    No hay créditos en mora con los filtros seleccionados.
                                </td>
                            </tr>
                            <tr v-for="c in creditos" :key="c.id"
                                class="hover:bg-slate-50/80 dark:hover:bg-zinc-800/50 transition-colors">
                                <td class="px-5 py-4">
                                    <p class="font-bold text-slate-900 dark:text-white">{{ c.acreditado.nombre_completo }}</p>
                                    <p class="text-xs text-slate-400 mt-0.5">{{ c.acreditado.municipio }}</p>
                                </td>
                                <td class="px-5 py-4">
                                    <p class="font-mono text-xs font-bold text-slate-700 dark:text-zinc-300">{{ c.clave_contrato }}</p>
                                    <p class="text-xs text-slate-400">{{ c.modalidad }}</p>
                                </td>
                                <td class="px-5 py-4">
                                    <div :class="['inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold', diasColor(c.dias_atraso).badge]">
                                        <span :class="['w-1.5 h-1.5 rounded-full', diasColor(c.dias_atraso).dot]"></span>
                                        {{ c.dias_atraso === 0 ? 'Al corriente' : c.dias_atraso + ' días' }}
                                    </div>
                                </td>
                                <td class="px-5 py-4 font-bold text-red-700 dark:text-red-400">{{ fmt(c.deuda_total) }}</td>
                                <td class="px-5 py-4 text-slate-600 dark:text-zinc-400 font-semibold">{{ c.cuotas_pendientes }}</td>
                                <td class="px-5 py-4 text-slate-500 dark:text-zinc-400 text-xs">{{ c.ultimo_pago ?? 'Sin pagos' }}</td>
                                <td class="px-5 py-4">
                                    <span :class="['inline-flex items-center gap-1 text-xs font-bold px-2 py-0.5 rounded-lg',
                                        c.gestiones_count > 0 ? 'bg-green-100 text-green-700 dark:bg-green-900/20 dark:text-green-400' : 'bg-slate-100 text-slate-500 dark:bg-zinc-800 dark:text-zinc-400']">
                                        <PhoneCall size="11" /> {{ c.gestiones_count }}
                                    </span>
                                    <span v-if="c.tiene_juridico"
                                        :class="['ml-1 inline-flex items-center gap-1 text-xs font-bold px-2 py-0.5 rounded-lg bg-purple-100 text-purple-700 dark:bg-purple-900/20 dark:text-purple-400']">
                                        <Gavel size="11" /> Jurídico
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-center">
                                    <Link :href="route('cobranza.show', c.id)"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 hover:bg-red-100 rounded-lg text-xs font-bold transition-colors">
                                        <Eye size="13" /> Gestionar
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
