<script setup>
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { Building2, DollarSign, AlertTriangle, ArrowUpDown, Download } from 'lucide-vue-next';

const props = defineProps({
    colocacion: Array,
    municipiosSinColocacion: Array,
    totalGeneralMonto: Number
});

// Ordenamiento de la tabla
const sortKey = ref('monto_colocado');
const sortOrder = ref('desc');

const ordenar = (key) => {
    if (sortKey.value === key) {
        sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortKey.value = key;
        sortOrder.value = 'desc';
    }
};

const colocacionOrdenada = computed(() => {
    return [...props.colocacion].sort((a, b) => {
        const modifier = sortOrder.value === 'asc' ? 1 : -1;
        const aVal = a[sortKey.value];
        const bVal = b[sortKey.value];

        if (typeof aVal === 'string') {
            return aVal.localeCompare(bVal) * modifier;
        }
        return (aVal - bVal) * modifier;
    });
});

const fmt = (val) => new Intl.NumberFormat('es-MX', {
    style: 'currency', currency: 'MXN', minimumFractionDigits: 2
}).format(parseFloat(val) || 0);
</script>

<template>
    <Head title="Colocación por Municipio" />
    <AppLayout>
        <div class="max-w-7xl mx-auto py-8 px-4">

            <!-- ENCABEZADO -->
            <div class="flex items-center justify-between mb-8 flex-wrap gap-4">
                <div>
                    <h1 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Colocación por Municipio</h1>
                    <p class="text-slate-500 text-sm">Instituto Yucateco de Emprendedores — IYEM</p>
                </div>
                <!-- BOTÓN DE EXPORTAR -->
                <a 
                    href="/reportes/colocacion-municipio/exportar" 
                    class="flex items-center gap-2 px-5 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-2xl font-bold transition-all shadow-sm text-sm"
                >
                    <Download :size="18" />
                    Exportar a Excel
                </a>
            </div>

            <!-- TARJETAS DE RESUMEN -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                <div class="bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-sm flex items-center gap-4">
                    <div class="p-4 bg-blue-50 dark:bg-blue-900/30 text-blue-600 rounded-2xl">
                        <DollarSign :size="24" />
                    </div>
                    <div>
                        <p class="text-xs font-black text-slate-400 uppercase">Monto Total Colocado</p>
                        <h4 class="text-xl font-black text-slate-800 dark:text-white">{{ fmt(totalGeneralMonto) }}</h4>
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-sm flex items-center gap-4">
                    <div class="p-4 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 rounded-2xl">
                        <Building2 :size="24" />
                    </div>
                    <div>
                        <p class="text-xs font-black text-slate-400 uppercase">Municipios con Colocación</p>
                        <h4 class="text-xl font-black text-slate-800 dark:text-white">{{ colocacion.length }} / 106</h4>
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-sm flex items-center gap-4">
                    <div class="p-4 bg-orange-50 dark:bg-orange-900/30 text-orange-600 rounded-2xl">
                        <AlertTriangle :size="24" />
                    </div>
                    <div>
                        <p class="text-xs font-black text-slate-400 uppercase">Municipios sin Cobertura</p>
                        <h4 class="text-xl font-black text-slate-800 dark:text-white">{{ municipiosSinColocacion.length }}</h4>
                    </div>
                </div>
            </div>

            <!-- TABLA PRINCIPAL -->
            <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden mb-8">
                <div class="p-6 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center">
                    <h3 class="text-sm font-black text-slate-700 dark:text-slate-300 uppercase tracking-wider">Desglose por Municipio</h3>
                    <span class="text-xs text-slate-400">Haz clic en los encabezados para ordenar</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-100 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-800/50 cursor-pointer select-none">
                                <th @click="ordenar('municipio')" class="px-6 py-3 text-left text-[10px] font-black text-slate-500 uppercase">
                                    <div class="flex items-center gap-1">Municipio <ArrowUpDown :size="12" /></div>
                                </th>
                                <th @click="ordenar('total_creditos')" class="px-6 py-3 text-right text-[10px] font-black text-slate-500 uppercase">
                                    <div class="flex items-center justify-end gap-1">Créditos <ArrowUpDown :size="12" /></div>
                                </th>
                                <th @click="ordenar('monto_colocado')" class="px-6 py-3 text-right text-[10px] font-black text-slate-500 uppercase">
                                    <div class="flex items-center justify-end gap-1">Monto Colocado <ArrowUpDown :size="12" /></div>
                                </th>
                                <th @click="ordenar('recuperado')" class="px-6 py-3 text-right text-[10px] font-black text-slate-500 uppercase">
                                    <div class="flex items-center justify-end gap-1">Recuperado <ArrowUpDown :size="12" /></div>
                                </th>
                                <th @click="ordenar('vencido')" class="px-6 py-3 text-right text-[10px] font-black text-slate-500 uppercase">
                                    <div class="flex items-center justify-end gap-1">Vencido <ArrowUpDown :size="12" /></div>
                                </th>
                                <th @click="ordenar('porcentaje_morosidad')" class="px-6 py-3 text-right text-[10px] font-black text-slate-500 uppercase">
                                    <div class="flex items-center justify-end gap-1">% Morosidad <ArrowUpDown :size="12" /></div>
                                </th>
                                <th @click="ordenar('beneficiarios')" class="px-6 py-3 text-right text-[10px] font-black text-slate-500 uppercase">
                                    <div class="flex items-center justify-end gap-1">Beneficiarios <ArrowUpDown :size="12" /></div>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50 dark:divide-slate-800">
                            <tr v-for="row in colocacionOrdenada" :key="row.municipio" class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap font-bold text-slate-800 dark:text-slate-200">{{ row.municipio }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-slate-600 dark:text-slate-400">{{ row.total_creditos }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-right font-medium text-slate-900 dark:text-white">{{ fmt(row.monto_colocado) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-right font-medium text-emerald-600 dark:text-emerald-400">{{ fmt(row.recuperado) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-right font-medium text-orange-600 dark:text-orange-400">{{ fmt(row.vencido) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-right font-black">
                                    <span class="px-2.5 py-1 rounded-full text-xs" :class="row.porcentaje_morosidad <= 10 ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'">
                                        {{ row.porcentaje_morosidad }}%
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-slate-600 dark:text-slate-400">{{ row.beneficiarios }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- SECCIÓN EXTRA: MUNICIPIOS SIN COLOCACIÓN -->
            <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 p-6 shadow-sm">
                <h3 class="text-xs font-black text-slate-400 uppercase mb-3 tracking-wider flex items-center gap-1.5">
                    <AlertTriangle :size="15" class="text-orange-500" /> Municipios de Yucatán sin Colocación Actual
                </h3>
                <p class="text-xs text-slate-500 mb-4">Lista de municipios clave donde CREA aún no cuenta con registros de créditos otorgados:</p>
                <div class="flex flex-wrap gap-2">
                    <span v-for="mun in municipiosSinColocacion" :key="mun" class="px-3 py-1 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 rounded-xl text-xs font-medium">
                        {{ mun }}
                    </span>
                </div>
            </div>

        </div>
    </AppLayout>
</template>