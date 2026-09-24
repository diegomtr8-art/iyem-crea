<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { Clock, Download, BarChart2 } from 'lucide-vue-next';
import { Bar } from 'vue-chartjs';
import {
    Chart as ChartJS,
    Title,
    Tooltip,
    Legend,
    BarElement,
    CategoryScale,
    LinearScale,
} from 'chart.js';

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale);

const props = defineProps<{
    reporte: {
        rango: string;
        creditos: number;
        capital: number;
        interes: number;
        mora: number;
        total: number;
        porcentaje: number;
    }[];
    gran_total: number;
    modalidades: { id: number; nombre: string }[];
    municipios: string[];
    filtros: {
        estatus: string | null;
        modalidad_id: number | null;
        sexo: string | null;
        municipio: string | null;
    };
}>();

// Estado de Filtros
const filtroEst = ref(props.filtros.estatus ?? '');
const filtroMod = ref(props.filtros.modalidad_id ?? '');
const filtroSexo = ref(props.filtros.sexo ?? '');
const filtroMun = ref(props.filtros.municipio ?? '');

const aplicarFiltros = () => {
    router.get(
        '/reportes/antiguedad',
        {
            estatus: filtroEst.value || null,
            modalidad_id: filtroMod.value || null,
            sexo: filtroSexo.value || null,
            municipio: filtroMun.value || null,
        },
        { preserveState: true, preserveScroll: true }
    );
};

const exportar = () => {
    const params = new URLSearchParams();
    if (filtroEst.value) params.set('estatus', filtroEst.value);
    if (filtroMod.value) params.set('modalidad_id', String(filtroMod.value));
    if (filtroSexo.value) params.set('sexo', filtroSexo.value);
    if (filtroMun.value) params.set('municipio', filtroMun.value);
    window.location.href = `/exportar/antiguedad?${params}`;
};

const fmt = (n: number) =>
    new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(n ?? 0);

// Configuración de la Gráfica
const chartData = computed(() => ({
    labels: props.reporte.map((i) => i.rango),
    datasets: [
        {
            label: 'Capital Vencido',
            data: props.reporte.map((i) => i.total),
            backgroundColor: '#10b981',
            borderRadius: 6,
        },
    ],
}));

const chartOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
        tooltip: {
            callbacks: {
                label: (ctx: any) => ` ${fmt(ctx.raw)}`,
            },
        },
    },
    scales: {
        x: {
            ticks: { color: '#9ca3af', font: { size: 10 } },
            grid: { display: false },
        },
        y: {
            ticks: {
                color: '#9ca3af',
                font: { size: 10 },
                callback: (val: any) => fmt(Number(val)),
            },
            grid: { color: 'rgba(156, 163, 175, 0.1)' },
        },
    },
}));

const totalCreditos = computed(() => props.reporte.reduce((acc, i) => acc + i.creditos, 0));
</script>

<template>
    <Head title="Antigüedad de Saldos" />
    <AppLayout>
        <div class="p-3 sm:p-6 space-y-4 sm:space-y-6 w-full max-w-full overflow-hidden box-border">
            
            <!-- Encabezado adaptable -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 w-full">
                <div class="flex items-start gap-3 min-w-0">
                    <Clock class="h-6 w-6 sm:h-7 sm:w-7 text-blue-600 dark:text-blue-400 shrink-0 mt-0.5" />
                    <div class="min-w-0">
                        <h1 class="text-lg sm:text-2xl font-bold text-gray-900 dark:text-gray-100 break-words leading-tight">
                            Reporte de Antigüedad de Saldos (Aging)
                        </h1>
                        <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mt-1 break-words">
                            Clasificación de cartera vencida por rangos de morosidad
                        </p>
                    </div>
                </div>
                <button
                    @click="exportar"
                    class="w-full md:w-auto flex items-center justify-center gap-2 rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700 transition-colors shrink-0"
                >
                    <Download class="h-4 w-4" /> Exportar Excel
                </button>
            </div>

            <!-- KPI Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div class="rounded-xl border border-gray-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-3.5 sm:p-4 shadow-sm min-w-0">
                    <p class="text-[10px] sm:text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide truncate">Total Cartera Vencida</p>
                    <p class="mt-1 text-lg sm:text-2xl font-bold text-green-700 dark:text-green-400 truncate">
                        {{ fmt(gran_total) }}
                    </p>
                </div>
                <div class="rounded-xl border border-gray-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-3.5 sm:p-4 shadow-sm min-w-0">
                    <p class="text-[10px] sm:text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide truncate">Créditos con Adeudo</p>
                    <p class="mt-1 text-xl sm:text-3xl font-bold text-gray-900 dark:text-gray-100 truncate">
                        {{ totalCreditos }}
                    </p>
                </div>
                <div class="rounded-xl border border-gray-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-3.5 sm:p-4 shadow-sm min-w-0">
                    <p class="text-[10px] sm:text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide truncate">Rangos de Morosidad</p>
                    <p class="mt-1 text-xl sm:text-3xl font-bold text-blue-600 dark:text-blue-400 truncate">
                        {{ reporte.length }}
                    </p>
                </div>
            </div>

            <!-- Gráfica de Barras -->
            <div class="rounded-xl border border-gray-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-3.5 sm:p-4 shadow-sm w-full min-w-0">
                <h2 class="mb-3 flex items-center gap-2 text-xs sm:text-sm font-semibold text-gray-700 dark:text-gray-300">
                    <BarChart2 class="h-4 w-4 shrink-0" /> Distribución de Cartera por Antigüedad
                </h2>
                <div class="w-full overflow-x-auto">
                    <div class="h-56 sm:h-64 min-w-[450px]">
                        <Bar :data="chartData" :options="chartOptions" />
                    </div>
                </div>
            </div>

            <!-- Filtros -->
            <div class="rounded-xl border border-gray-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-3.5 sm:p-4 shadow-sm w-full min-w-0">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2.5 sm:gap-3">
                    <select
                        v-model="filtroEst"
                        @change="aplicarFiltros"
                        class="w-full rounded-lg border border-gray-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-gray-900 dark:text-gray-100 px-3 py-2 text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 truncate"
                    >
                        <option value="">Todos los estatus</option>
                        <option value="Activo">Activo</option>
                        <option value="Vencido">Vencido</option>
                        <option value="Liquidado">Liquidado</option>
                    </select>

                    <select
                        v-model="filtroMod"
                        @change="aplicarFiltros"
                        class="w-full rounded-lg border border-gray-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-gray-900 dark:text-gray-100 px-3 py-2 text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 truncate"
                    >
                        <option value="">Todas las modalidades</option>
                        <option v-for="m in modalidades" :key="m.id" :value="m.id">{{ m.nombre }}</option>
                    </select>

                    <select
                        v-model="filtroMun"
                        @change="aplicarFiltros"
                        class="w-full rounded-lg border border-gray-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-gray-900 dark:text-gray-100 px-3 py-2 text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 truncate"
                    >
                        <option value="">Todos los municipios</option>
                        <option v-for="m in municipios" :key="m" :value="m">{{ m }}</option>
                    </select>

                    <select
                        v-model="filtroSexo"
                        @change="aplicarFiltros"
                        class="w-full rounded-lg border border-gray-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-gray-900 dark:text-gray-100 px-3 py-2 text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 truncate"
                    >
                        <option value="">Todos los géneros</option>
                        <option value="M">Masculino</option>
                        <option value="F">Femenino</option>
                    </select>
                </div>
            </div>

            <!-- Tabla responsiva -->
            <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-sm w-full min-w-0">
                <div class="overflow-x-auto w-full">
                    <table class="w-full min-w-[580px] text-xs sm:text-sm">
                        <thead class="bg-gray-50 dark:bg-zinc-800/60 text-[10px] sm:text-xs uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            <tr>
                                <th class="px-3 py-2.5 sm:px-4 sm:py-3 text-left">Rango</th>
                                <!-- 1. Nombre cambiado a Cuotas -->
                                <th class="px-3 py-2.5 sm:px-4 sm:py-3 text-center">Cuotas</th>
                                <th class="px-3 py-2.5 sm:px-4 sm:py-3 text-right">Capital Vencido</th>
                                <th class="px-3 py-2.5 sm:px-4 sm:py-3 text-right">Interés Vencido</th>
                                <th class="px-3 py-2.5 sm:px-4 sm:py-3 text-right">Mora</th>
                                <th class="px-3 py-2.5 sm:px-4 sm:py-3 text-right">Total</th>
                                <th class="px-3 py-2.5 sm:px-4 sm:py-3 text-right">% Cartera</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-zinc-800">
                            <tr v-for="row in reporte" :key="row.rango" class="hover:bg-gray-50 dark:hover:bg-zinc-800/40">
                                <td class="px-3 py-2.5 sm:px-4 sm:py-3 font-medium text-gray-900 dark:text-gray-100 whitespace-nowrap">{{ row.rango }}</td>
                                <!-- 2. Propiedad cuotas -->
                                <td class="px-3 py-2.5 sm:px-4 sm:py-3 text-center text-gray-600 dark:text-gray-300">{{ row.cuotas }}</td>
                                <td class="px-3 py-2.5 sm:px-4 sm:py-3 text-right text-gray-600 dark:text-gray-300 whitespace-nowrap">{{ fmt(row.capital) }}</td>
                                <td class="px-3 py-2.5 sm:px-4 sm:py-3 text-right text-gray-600 dark:text-gray-300 whitespace-nowrap">{{ fmt(row.interes) }}</td>
                                <td class="px-3 py-2.5 sm:px-4 sm:py-3 text-right text-gray-600 dark:text-gray-300 whitespace-nowrap">{{ fmt(row.mora) }}</td>
                                <td class="px-3 py-2.5 sm:px-4 sm:py-3 text-right font-medium text-gray-900 dark:text-gray-100 whitespace-nowrap">{{ fmt(row.total) }}</td>
                                <td class="px-3 py-2.5 sm:px-4 sm:py-3 text-right font-mono text-[10px] sm:text-xs text-blue-700 dark:text-blue-400 whitespace-nowrap">{{ row.porcentaje }}%</td>
                            </tr>
                            <tr v-if="reporte.length === 0">
                                <td colspan="7" class="px-4 py-8 text-center text-gray-400">Sin registros con los filtros aplicados</td>
                            </tr>
                        </tbody>
                        <tfoot v-if="reporte.length > 0" class="bg-gray-50 dark:bg-zinc-800/60 font-semibold text-gray-900 dark:text-gray-100 border-t border-gray-200 dark:border-zinc-800">
                            <tr>
                                <td class="px-3 py-2.5 sm:px-4 sm:py-3 whitespace-nowrap">Total</td>
                                <!-- 3. Suma de cuotas -->
                                <td class="px-3 py-2.5 sm:px-4 sm:py-3 text-center">{{ reporte.reduce((a, b) => a + b.cuotas, 0) }}</td>
                                <td class="px-3 py-2.5 sm:px-4 sm:py-3 text-right whitespace-nowrap">{{ fmt(reporte.reduce((a, b) => a + b.capital, 0)) }}</td>
                                <td class="px-3 py-2.5 sm:px-4 sm:py-3 text-right whitespace-nowrap">{{ fmt(reporte.reduce((a, b) => a + b.interes, 0)) }}</td>
                                <td class="px-3 py-2.5 sm:px-4 sm:py-3 text-right whitespace-nowrap">{{ fmt(reporte.reduce((a, b) => a + b.mora, 0)) }}</td>
                                <td class="px-3 py-2.5 sm:px-4 sm:py-3 text-right text-green-700 dark:text-green-400 font-bold whitespace-nowrap">{{ fmt(gran_total) }}</td>
                                <td class="px-3 py-2.5 sm:px-4 sm:py-3 text-right font-mono text-[10px] sm:text-xs text-blue-700 dark:text-blue-400 whitespace-nowrap">100%</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- 4. Notas al pie solicitadas en el ticket -->
                <div class="px-4 py-3 bg-gray-50/50 dark:bg-zinc-800/30 border-t border-gray-200 dark:border-zinc-800 text-[11px] text-gray-500 dark:text-gray-400 space-y-1">
                    <p>* <strong>Cuotas:</strong> Cantidad de amortizaciones o cuotas pendientes agrupadas por rango de morosidad (un crédito puede tener cuotas en distintos rangos).</p>
                    <p>** <strong>Días de gracia:</strong> Las cuotas vencidas dentro del periodo de gracia (&le; 5 días) se incluyen en "1 a 30 días" con mora en $0.00.</p>
                </div>
            </div>

        </div>
    </AppLayout>
</template>