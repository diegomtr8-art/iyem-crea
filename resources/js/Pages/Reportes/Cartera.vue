<script setup>
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { Download, TrendingUp, AlertTriangle, CheckCircle2, Clock, Filter } from 'lucide-vue-next';

const props = defineProps({
    creditos:    Array,
    resumen:     Object,
    filtros:     Object,
    modalidades: Array,
    municipios:  Array,
});

const filtroEstatus   = ref(props.filtros?.estatus      ?? '');
const filtroModalidad = ref(props.filtros?.modalidad_id ?? '');
const filtroSexo      = ref(props.filtros?.sexo         ?? '');
const filtroMunicipio = ref(props.filtros?.municipio    ?? '');

const aplicarFiltro = () => {
    router.get(route('reportes.cartera'), {
        estatus:      filtroEstatus.value   || undefined,
        modalidad_id: filtroModalidad.value || undefined,
        sexo:         filtroSexo.value      || undefined,
        municipio:    filtroMunicipio.value || undefined,
    }, { preserveScroll: true });
};

const fmt = (val) => new Intl.NumberFormat('es-MX', {
    style: 'currency', currency: 'MXN', minimumFractionDigits: 2
}).format(parseFloat(val) || 0);

const fmtDate = (d) => {
    if (!d) return '—';
    const [y, m, dia] = d.split('-');
    return `${dia}/${m}/${y}`;
};

const colorEstatus = (e) => ({
    Activo:    'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400',
    Moroso:    'bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-400',
    Liquidado: 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400',
    Cancelado: 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400',
}[e] ?? 'bg-slate-100 text-slate-600');

const exportUrl = computed(() => {
    const params = filtroEstatus.value ? `?estatus=${filtroEstatus.value}` : '';
    return route('exportar.cartera') + params;
});
</script>

<template>
    <Head title="Reporte de Cartera" />
    <AppLayout>
        <div class="max-w-7xl mx-auto py-8 px-4">

            <!-- ENCABEZADO -->
            <div class="flex items-center justify-between mb-8 flex-wrap gap-4">
                <div>
                    <h1 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Reporte de Cartera</h1>
                    <p class="text-slate-500 text-sm">Instituto Yucateco de Emprendedores — IYEM</p>
                </div>
                <a :href="exportUrl"
                   class="flex items-center gap-2 px-5 py-2.5 bg-green-600 hover:bg-green-700 text-white font-bold rounded-xl text-sm transition-colors shadow-sm">
                    <Download :size="15" />
                    Exportar Excel
                </a>
            </div>

            <!-- TARJETAS DE RESUMEN -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 shadow-sm">
                    <p class="text-[10px] font-black text-slate-400 uppercase mb-1">Monto Colocado</p>
                    <p class="text-xl font-black text-slate-900 dark:text-white">{{ fmt(resumen.monto_colocado) }}</p>
                </div>
                <div class="bg-green-50 dark:bg-green-900/20 rounded-2xl border border-green-200 dark:border-green-800 p-4 shadow-sm">
                    <p class="text-[10px] font-black text-green-500 uppercase mb-1">Capital Recuperado</p>
                    <p class="text-xl font-black text-green-700">{{ fmt(resumen.capital_recuperado) }}</p>
                </div>
                <div class="bg-red-50 dark:bg-red-900/20 rounded-2xl border border-red-200 dark:border-red-800 p-4 shadow-sm">
                    <p class="text-[10px] font-black text-red-500 uppercase mb-1">Capital Pendiente</p>
                    <p class="text-xl font-black text-red-700">{{ fmt(resumen.capital_pendiente) }}</p>
                </div>
                <div class="bg-orange-50 dark:bg-orange-900/20 rounded-2xl border border-orange-200 dark:border-orange-800 p-4 shadow-sm">
                    <p class="text-[10px] font-black text-orange-500 uppercase mb-1">Mora Cobrada</p>
                    <p class="text-xl font-black text-orange-700">{{ fmt(resumen.mora_cobrada) }}</p>
                </div>
            </div>

            <!-- SECCIONES DE CARTERA: Pagado / Devengado / Vencido / Por Vencer -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 shadow-sm">
                    <p class="text-[10px] font-black text-slate-400 uppercase mb-1 flex items-center gap-1">
                        <CheckCircle2 :size="11" class="text-emerald-500" /> Pagado
                    </p>
                    <p class="text-lg font-black text-emerald-600">{{ fmt(resumen.pagado) }}</p>
                    <p class="text-[10px] text-slate-400 mt-0.5">Capital + interés + mora cobrados</p>
                </div>
                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 shadow-sm">
                    <p class="text-[10px] font-black text-slate-400 uppercase mb-1 flex items-center gap-1">
                        <TrendingUp :size="11" class="text-blue-500" /> Devengado
                    </p>
                    <p class="text-lg font-black text-blue-600">{{ fmt(resumen.devengado) }}</p>
                    <p class="text-[10px] text-slate-400 mt-0.5">Interés futuro generado (no cobrado)</p>
                </div>
                <div class="bg-red-50 dark:bg-red-900/20 rounded-2xl border border-red-200 dark:border-red-800 p-4 shadow-sm">
                    <p class="text-[10px] font-black text-red-500 uppercase mb-1 flex items-center gap-1">
                        <AlertTriangle :size="11" /> Vencido
                    </p>
                    <p class="text-lg font-black text-red-700">{{ fmt(resumen.vencido) }}</p>
                    <p class="text-[10px] text-red-400 mt-0.5">Cuotas ya vencidas sin pagar</p>
                </div>
                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 shadow-sm">
                    <p class="text-[10px] font-black text-slate-400 uppercase mb-1 flex items-center gap-1">
                        <Clock :size="11" class="text-amber-500" /> Por Vencer
                    </p>
                    <p class="text-lg font-black text-amber-600">{{ fmt(resumen.por_vencer) }}</p>
                    <p class="text-[10px] text-slate-400 mt-0.5">Cuotas vigentes pendientes</p>
                </div>
            </div>

            <!-- CONTADORES RÁPIDOS -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-6">
                <div class="flex items-center gap-3 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4">
                    <CheckCircle2 :size="20" class="text-green-500" />
                    <div>
                        <p class="text-2xl font-black text-slate-900 dark:text-white">{{ resumen.activos }}</p>
                        <p class="text-[10px] text-slate-400 uppercase font-bold">Activos</p>
                    </div>
                </div>
                <div class="flex items-center gap-3 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4">
                    <AlertTriangle :size="20" class="text-orange-500" />
                    <div>
                        <p class="text-2xl font-black text-slate-900 dark:text-white">{{ resumen.morosos }}</p>
                        <p class="text-[10px] text-slate-400 uppercase font-bold">Morosos</p>
                    </div>
                </div>
                <div class="flex items-center gap-3 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4">
                    <TrendingUp :size="20" class="text-blue-500" />
                    <div>
                        <p class="text-2xl font-black text-slate-900 dark:text-white">{{ resumen.liquidados }}</p>
                        <p class="text-[10px] text-slate-400 uppercase font-bold">Liquidados</p>
                    </div>
                </div>
            </div>

            <!-- FILTROS -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 mb-4 shadow-sm">
                <div class="flex flex-wrap items-center gap-3">
                    <Filter :size="14" class="text-slate-400 flex-shrink-0" />
                    <select v-model="filtroEstatus" @change="aplicarFiltro"
                        class="text-sm border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Todos los estatus</option>
                        <option value="Activo">Activo</option>
                        <option value="Moroso">Moroso</option>
                        <option value="Liquidado">Liquidado</option>
                    </select>
                    <select v-model="filtroModalidad" @change="aplicarFiltro"
                        class="text-sm border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Todas las modalidades</option>
                        <option v-for="m in modalidades" :key="m.id" :value="m.id">{{ m.nombre }}</option>
                    </select>
                    <select v-model="filtroSexo" @change="aplicarFiltro"
                        class="text-sm border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Ambos sexos</option>
                        <option value="H">Hombres</option>
                        <option value="M">Mujeres</option>
                    </select>
                    <select v-model="filtroMunicipio" @change="aplicarFiltro"
                        class="text-sm border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Todos los municipios</option>
                        <option v-for="m in municipios" :key="m" :value="m">{{ m }}</option>
                    </select>
                    <span class="text-xs text-slate-400 ml-auto">{{ creditos.length }} créditos</span>
                    <button v-if="filtroEstatus || filtroModalidad || filtroSexo || filtroMunicipio"
                        @click="filtroEstatus=''; filtroModalidad=''; filtroSexo=''; filtroMunicipio=''; aplicarFiltro()"
                        class="text-xs text-red-500 font-bold hover:underline">
                        Limpiar
                    </button>
                </div>
            </div>

            <!-- TABLA -->
            <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-100 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-800/50">
                                <th class="px-4 py-3 text-left text-[10px] font-black text-slate-500 uppercase">Contrato</th>
                                <th class="px-4 py-3 text-left text-[10px] font-black text-slate-500 uppercase">Acreditado</th>
                                <th class="px-4 py-3 text-left text-[10px] font-black text-slate-500 uppercase">Municipio</th>
                                <th class="px-4 py-3 text-left text-[10px] font-black text-slate-500 uppercase">Modalidad</th>
                                <th class="px-4 py-3 text-right text-[10px] font-black text-slate-500 uppercase">Monto</th>
                                <th class="px-4 py-3 text-right text-[10px] font-black text-slate-500 uppercase">Recuperado</th>
                                <th class="px-4 py-3 text-right text-[10px] font-black text-slate-500 uppercase">Pendiente</th>
                                <th class="px-4 py-3 text-center text-[10px] font-black text-slate-500 uppercase">Estatus</th>
                                <th class="px-4 py-3 text-center text-[10px] font-black text-slate-500 uppercase">Vencidas</th>
                                <th class="px-4 py-3 text-center text-[10px] font-black text-slate-500 uppercase">Entrega</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50 dark:divide-slate-800">
                            <tr v-for="c in creditos" :key="c.id"
                                class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors cursor-pointer"
                                @click="router.visit(route('acreditados.show', c.id))">
                                <td class="px-4 py-3 font-mono font-bold text-slate-700 dark:text-slate-300 text-xs">{{ c.clave_contrato }}</td>
                                <td class="px-4 py-3 font-medium text-slate-900 dark:text-white">{{ c.acreditado }}</td>
                                <td class="px-4 py-3 text-slate-500 text-xs">{{ c.municipio }}</td>
                                <td class="px-4 py-3 text-slate-500 text-xs">{{ c.modalidad }}</td>
                                <td class="px-4 py-3 text-right font-bold text-slate-900 dark:text-white">{{ fmt(c.monto_otorgado) }}</td>
                                <td class="px-4 py-3 text-right text-green-600 font-medium">{{ fmt(c.capital_recuperado) }}</td>
                                <td class="px-4 py-3 text-right text-red-600 font-medium">{{ fmt(c.capital_pendiente) }}</td>
                                <td class="px-4 py-3 text-center">
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full" :class="colorEstatus(c.estatus)">
                                        {{ c.estatus }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span v-if="c.cuotas_vencidas > 0" class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-red-100 dark:bg-red-900/30 text-red-600">
                                        {{ c.cuotas_vencidas }}
                                    </span>
                                    <span v-else class="text-slate-300">—</span>
                                </td>
                                <td class="px-4 py-3 text-center text-xs text-slate-400">{{ fmtDate(c.fecha_entrega) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="!creditos.length" class="py-16 text-center">
                    <p class="text-slate-400 text-sm">No hay créditos con los filtros seleccionados.</p>
                </div>
            </div>

        </div>
    </AppLayout>
</template>
