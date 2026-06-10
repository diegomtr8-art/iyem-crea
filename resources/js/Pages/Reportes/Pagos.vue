<script setup>
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Download, Search } from 'lucide-vue-next';

const props = defineProps({
    pagos:   Array,
    totales: Object,
    filtros: Object,
});

const desde = ref(props.filtros?.desde ?? '');
const hasta = ref(props.filtros?.hasta ?? '');

const aplicarFiltro = () => {
    router.get(route('reportes.pagos'), { desde: desde.value, hasta: hasta.value }, { preserveScroll: true });
};

const exportUrl = () => {
    const p = new URLSearchParams({ desde: desde.value, hasta: hasta.value }).toString();
    return route('exportar.pagos') + '?' + p;
};

const fmt = (val) => new Intl.NumberFormat('es-MX', {
    style: 'currency', currency: 'MXN', minimumFractionDigits: 2
}).format(parseFloat(val) || 0);

const fmtDate = (d) => {
    if (!d) return '—';
    const [y, m, dia] = d.split('-');
    return `${dia}/${m}/${y}`;
};
</script>

<template>
    <Head title="Reporte de Pagos" />
    <AppLayout>
        <div class="max-w-7xl mx-auto py-8 px-4">

            <div class="flex items-center justify-between mb-8 flex-wrap gap-4">
                <div>
                    <h1 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Reporte de Pagos</h1>
                    <p class="text-slate-500 text-sm">Historial de pagos recibidos</p>
                </div>
                <a :href="exportUrl()"
                   class="flex items-center gap-2 px-5 py-2.5 bg-green-600 hover:bg-green-700 text-white font-bold rounded-xl text-sm transition-colors">
                    <Download :size="15" />
                    Exportar Excel
                </a>
            </div>

            <!-- TOTALES -->
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 text-center shadow-sm">
                    <p class="text-[10px] font-black text-slate-400 uppercase mb-1">Pagos</p>
                    <p class="text-2xl font-black text-slate-900 dark:text-white">{{ totales.count }}</p>
                </div>
                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 text-center shadow-sm">
                    <p class="text-[10px] font-black text-slate-400 uppercase mb-1">Total</p>
                    <p class="text-lg font-black text-slate-900 dark:text-white">{{ fmt(totales.monto) }}</p>
                </div>
                <div class="bg-orange-50 dark:bg-orange-900/20 rounded-2xl border border-orange-200 dark:border-orange-800 p-4 text-center shadow-sm">
                    <p class="text-[10px] font-black text-orange-500 uppercase mb-1">Mora</p>
                    <p class="text-lg font-black text-orange-600">{{ fmt(totales.mora) }}</p>
                </div>
                <div class="bg-red-50 dark:bg-red-900/20 rounded-2xl border border-red-200 dark:border-red-800 p-4 text-center shadow-sm">
                    <p class="text-[10px] font-black text-red-500 uppercase mb-1">Ordinario</p>
                    <p class="text-lg font-black text-red-600">{{ fmt(totales.ordinario) }}</p>
                </div>
                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-2xl border border-blue-200 dark:border-blue-800 p-4 text-center shadow-sm">
                    <p class="text-[10px] font-black text-blue-500 uppercase mb-1">Capital</p>
                    <p class="text-lg font-black text-blue-600">{{ fmt(totales.capital) }}</p>
                </div>
            </div>

            <!-- FILTROS -->
            <div class="flex items-center gap-3 mb-4 flex-wrap">
                <div class="flex items-center gap-2">
                    <label class="text-xs font-bold text-slate-500">Desde</label>
                    <input type="date" v-model="desde"
                        class="text-sm border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500" />
                </div>
                <div class="flex items-center gap-2">
                    <label class="text-xs font-bold text-slate-500">Hasta</label>
                    <input type="date" v-model="hasta"
                        class="text-sm border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500" />
                </div>
                <button @click="aplicarFiltro"
                    class="flex items-center gap-2 px-4 py-2 bg-slate-900 dark:bg-white text-white dark:text-slate-900 font-bold rounded-xl text-sm hover:bg-slate-700 transition-colors">
                    <Search :size="14" />
                    Buscar
                </button>
            </div>

            <!-- TABLA -->
            <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-100 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-800/50">
                                <th class="px-4 py-3 text-left text-[10px] font-black text-slate-500 uppercase">Folio</th>
                                <th class="px-4 py-3 text-left text-[10px] font-black text-slate-500 uppercase">Fecha</th>
                                <th class="px-4 py-3 text-left text-[10px] font-black text-slate-500 uppercase">Acreditado</th>
                                <th class="px-4 py-3 text-left text-[10px] font-black text-slate-500 uppercase">Contrato</th>
                                <th class="px-4 py-3 text-left text-[10px] font-black text-slate-500 uppercase">Forma</th>
                                <th class="px-4 py-3 text-right text-[10px] font-black text-slate-500 uppercase">Monto</th>
                                <th class="px-4 py-3 text-right text-[10px] font-black text-slate-500 uppercase">Mora</th>
                                <th class="px-4 py-3 text-right text-[10px] font-black text-slate-500 uppercase">Ordinario</th>
                                <th class="px-4 py-3 text-right text-[10px] font-black text-slate-500 uppercase">Capital</th>
                                <th class="px-4 py-3 text-left text-[10px] font-black text-slate-500 uppercase">Cajero</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50 dark:divide-slate-800">
                            <tr v-for="p in pagos" :key="p.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                                <td class="px-4 py-3 font-mono font-bold text-slate-700 dark:text-slate-300 text-xs">{{ p.folio }}</td>
                                <td class="px-4 py-3 text-slate-500 text-xs">{{ fmtDate(p.fecha_pago) }}</td>
                                <td class="px-4 py-3 font-medium text-slate-900 dark:text-white">{{ p.acreditado }}</td>
                                <td class="px-4 py-3 font-mono text-xs text-slate-500">{{ p.contrato }}</td>
                                <td class="px-4 py-3 text-xs text-slate-500">{{ p.forma_pago }}</td>
                                <td class="px-4 py-3 text-right font-bold text-slate-900 dark:text-white">{{ fmt(p.monto_recibido) }}</td>
                                <td class="px-4 py-3 text-right text-orange-600">{{ fmt(p.aplicado_mora) }}</td>
                                <td class="px-4 py-3 text-right text-red-600">{{ fmt(p.aplicado_ordinario) }}</td>
                                <td class="px-4 py-3 text-right text-blue-600">{{ fmt(p.aplicado_capital) }}</td>
                                <td class="px-4 py-3 text-xs text-slate-400">{{ p.cajero }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-if="!pagos.length" class="py-16 text-center">
                    <p class="text-slate-400 text-sm">No hay pagos en el período seleccionado.</p>
                </div>
            </div>

        </div>
    </AppLayout>
</template>
