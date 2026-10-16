<script setup>
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { Line } from 'vue-chartjs';
import { 
    Chart as ChartJS, 
    CategoryScale, 
    LinearScale, 
    PointElement, 
    LineElement, 
    Title, 
    Tooltip, 
    Legend, 
    Filler 
} from 'chart.js';
import { Filter, Calendar, TrendingUp, Download } from 'lucide-vue-next';

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend, Filler);

const props = defineProps({
    reporte: Array,
    modalidades: Array,
    municipios: Array,
    filtros: Object,
    regla_fecha: String
});

const filtroModalidad = ref(props.filtros?.modalidad_id ?? '');
const filtroMunicipio = ref(props.filtros?.municipio ?? '');

const aplicarFiltro = () => {
    router.get(route('reportes.recuperacion'), {
        modalidad_id: filtroModalidad.value || undefined,
        municipio: filtroMunicipio.value || undefined,
    }, { preserveScroll: true });
};

const fmt = (val) => new Intl.NumberFormat('es-MX', {
    style: 'currency', currency: 'MXN', minimumFractionDigits: 2
}).format(parseFloat(val) || 0);

// Configuración de la gráfica adaptada a Chart.js
const chartData = {
    labels: props.reporte.map(item => item.mes),
    datasets: [
        {
            label: 'Esperado',
            data: props.reporte.map(item => item.esperado),
            borderColor: '#3b82f6', // Azul
            backgroundColor: 'rgba(59, 130, 246, 0.05)',
            fill: true,
            tension: 0.2
        },
        {
            label: 'Cobrado',
            data: props.reporte.map(item => item.cobrado),
            borderColor: '#10b981', // Verde
            backgroundColor: 'rgba(16, 185, 129, 0.05)',
            fill: true,
            tension: 0.2
        }
    ]
};

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'top',
        }
    }
};

const exportUrl = computed(() => {
    const params = new URLSearchParams();
    if (filtroModalidad.value) params.append('modalidad_id', filtroModalidad.value);
    if (filtroMunicipio.value) params.append('municipio', filtroMunicipio.value);
    const queryString = params.toString();
    return route('exportar.recuperacion') + (queryString ? `?${queryString}` : '');
});
</script>

<template>
    <Head title="Reporte de Recuperación" />
    <AppLayout>
        <div class="max-w-7xl mx-auto py-8 px-4">

            <!-- ENCABEZADO -->
            <div class="flex items-center justify-between mb-8 flex-wrap gap-4">
                <div>
                    <h1 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Reporte de Recuperación</h1>
                    <p class="text-slate-500 text-sm">Instituto Yucateco de Emprendedores — IYEM</p>
                </div>
                <a :href="exportUrl"
                    class="flex items-center gap-2 px-5 py-2.5 bg-green-600 hover:bg-green-700 text-white font-bold rounded-xl text-sm transition-colors shadow-sm">
                    <Download :size="15" />
                    Exportar Excel
                </a>
            </div>

            <!-- CRITERIO DE FECHAS (NOTA OBLIGATORIA) -->
            <div class="bg-blue-50 dark:bg-blue-900/20 border-l-4 border-blue-400 p-4 mb-6 text-sm text-blue-800 dark:text-blue-300 rounded-r-2xl shadow-sm flex items-start gap-3">
                <Calendar :size="20" class="flex-shrink-0 mt-0.5" />
                <div>
                    <span class="font-bold">Criterio de fechas:</span> {{ regla_fecha }}
                </div>
            </div>

            <!-- FILTROS -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 mb-6 shadow-sm">
                <div class="flex flex-wrap items-center gap-3">
                    <Filter :size="14" class="text-slate-400 flex-shrink-0" />
                    
                    <select v-model="filtroModalidad" @change="aplicarFiltro"
                        class="text-sm border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Todas las modalidades</option>
                        <option v-for="m in modalidades" :key="m.id" :value="m.id">{{ m.nombre }}</option>
                    </select>

                    <select v-model="filtroMunicipio" @change="aplicarFiltro"
                        class="text-sm border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Todos los municipios</option>
                        <option v-for="m in municipios" :key="m" :value="m">{{ m }}</option>
                    </select>

                    <button v-if="filtroModalidad || filtroMunicipio"
                        @click="filtroModalidad=''; filtroMunicipio=''; aplicarFiltro()"
                        class="text-xs text-red-500 font-bold hover:underline ml-auto">
                        Limpiar filtros
                    </button>
                </div>
            </div>

            <!-- GRÁFICA DE LÍNEAS -->
            <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 p-6 mb-6 shadow-sm">
                <h3 class="text-xs font-black text-slate-400 uppercase mb-4 tracking-wider flex items-center gap-1.5">
                    <TrendingUp :size="15" class="text-blue-500" /> Comportamiento de Recuperación (Últimos 12 Meses)
                </h3>
                <div class="h-80 w-full">
                    <Line :data="chartData" :options="chartOptions" />
                </div>
            </div>

            <!-- TABLA DE LOS ÚLTIMOS 12 MESES -->
            <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-100 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-800/50">
                                <th class="px-6 py-3 text-left text-[10px] font-black text-slate-500 uppercase">Mes</th>
                                <th class="px-6 py-3 text-right text-[10px] font-black text-slate-500 uppercase">Esperado</th>
                                <th class="px-6 py-3 text-right text-[10px] font-black text-slate-500 uppercase">Cobrado</th>
                                <th class="px-6 py-3 text-right text-[10px] font-black text-slate-500 uppercase">% Recuperación</th>
                                <th class="px-6 py-3 text-right text-[10px] font-black text-slate-500 uppercase">Diferencia</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50 dark:divide-slate-800">
                            <tr v-for="row in reporte" :key="row.mes" class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap font-bold text-slate-800 dark:text-slate-200">{{ row.mes }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-right font-medium text-slate-700 dark:text-slate-300">{{ fmt(row.esperado) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-right font-medium text-emerald-600 dark:text-emerald-400">{{ fmt(row.cobrado) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-right font-black text-slate-900 dark:text-white">
                                    <span class="px-2.5 py-1 rounded-full text-xs" :class="row.porcentaje_recuperacion >= 80 ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400'">
                                        {{ row.porcentaje_recuperacion }}%
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right font-bold" :class="row.diferencia >= 0 ? 'text-green-600' : 'text-red-600'">
                                    {{ fmt(row.diferencia) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </AppLayout>
</template>