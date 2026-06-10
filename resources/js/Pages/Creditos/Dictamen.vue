<script setup>
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { FileText, ArrowLeft, CheckCircle2, TrendingUp } from 'lucide-vue-next';

const props = defineProps({
    credito:  Object,
    acreditado: Object,
    resumen:  Object,
    pagos:    Array,
});

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
    <Head :title="'Dictamen — ' + credito.clave_contrato" />
    <AppLayout>
        <div class="max-w-4xl mx-auto py-8 px-4">

            <!-- Encabezado -->
            <div class="flex items-center gap-4 mb-8">
                <button
                    @click="history.back()"
                    class="p-3 rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 transition-colors shadow-sm"
                >
                    <ArrowLeft :size="18" class="text-slate-600 dark:text-slate-300" />
                </button>
                <div class="flex-1">
                    <div class="flex items-center gap-2">
                        <CheckCircle2 :size="20" class="text-green-500" />
                        <h1 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Dictamen de Conclusión</h1>
                    </div>
                    <p class="text-slate-500 text-sm">Contrato: {{ credito.clave_contrato }}</p>
                </div>
                <a
                    :href="route('creditos.dictamen.pdf', credito.id)"
                    target="_blank"
                    class="flex items-center gap-2 px-5 py-2.5 bg-slate-900 dark:bg-white text-white dark:text-slate-900 font-bold rounded-xl text-sm hover:bg-slate-700 transition-colors shadow-sm"
                >
                    <FileText :size="15" />
                    Descargar PDF
                </a>
            </div>

            <!-- Datos del acreditado -->
            <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-sm p-6 mb-6">
                <p class="text-[10px] font-black text-slate-400 uppercase mb-4">Datos del Acreditado</p>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                    <div>
                        <p class="text-[10px] text-slate-400 uppercase">Nombre</p>
                        <p class="font-black text-slate-900 dark:text-white">{{ acreditado.nombre_completo }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] text-slate-400 uppercase">Municipio</p>
                        <p class="font-bold text-slate-700 dark:text-slate-300">{{ acreditado.municipio }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] text-slate-400 uppercase">Modalidad</p>
                        <p class="font-bold text-slate-700 dark:text-slate-300">{{ credito.modalidad }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] text-slate-400 uppercase">Monto Otorgado</p>
                        <p class="font-black text-slate-900 dark:text-white">{{ fmt(credito.monto_otorgado) }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] text-slate-400 uppercase">Plazo</p>
                        <p class="font-bold text-slate-700 dark:text-slate-300">{{ credito.plazo_meses }} meses</p>
                    </div>
                    <div>
                        <p class="text-[10px] text-slate-400 uppercase">Fecha Entrega</p>
                        <p class="font-bold text-slate-700 dark:text-slate-300">{{ fmtDate(credito.fecha_entrega) }}</p>
                    </div>
                </div>
            </div>

            <!-- Resumen de recuperación -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-2xl border border-blue-200 dark:border-blue-800 p-4 shadow-sm text-center">
                    <p class="text-[10px] font-black text-blue-500 uppercase mb-1">Capital</p>
                    <p class="text-lg font-black text-blue-700">{{ fmt(resumen.total_capital) }}</p>
                </div>
                <div class="bg-red-50 dark:bg-red-900/20 rounded-2xl border border-red-200 dark:border-red-800 p-4 shadow-sm text-center">
                    <p class="text-[10px] font-black text-red-500 uppercase mb-1">Interés</p>
                    <p class="text-lg font-black text-red-700">{{ fmt(resumen.total_ordinario) }}</p>
                </div>
                <div class="bg-orange-50 dark:bg-orange-900/20 rounded-2xl border border-orange-200 dark:border-orange-800 p-4 shadow-sm text-center">
                    <p class="text-[10px] font-black text-orange-500 uppercase mb-1">Mora</p>
                    <p class="text-lg font-black text-orange-700">{{ fmt(resumen.total_mora) }}</p>
                </div>
                <div class="bg-green-50 dark:bg-green-900/20 rounded-2xl border border-green-200 dark:border-green-800 p-4 shadow-sm text-center">
                    <p class="text-[10px] font-black text-green-500 uppercase mb-1">Total Pagado</p>
                    <p class="text-lg font-black text-green-700">{{ fmt(resumen.total_pagado) }}</p>
                </div>
            </div>

            <!-- Info de cierre -->
            <div class="bg-green-50 dark:bg-green-900/20 rounded-2xl border border-green-200 dark:border-green-800 p-4 mb-6 flex items-center gap-4">
                <CheckCircle2 :size="32" class="text-green-500 shrink-0" />
                <div>
                    <p class="font-black text-green-800 dark:text-green-300">Crédito Liquidado</p>
                    <p class="text-sm text-green-700 dark:text-green-400">
                        {{ resumen.num_pagos }} pagos — Primer pago: {{ fmtDate(resumen.primer_pago) }} — Último pago: {{ fmtDate(resumen.ultimo_pago) }}
                        <span v-if="resumen.cuotas_condonadas > 0"> — {{ resumen.cuotas_condonadas }} cuota(s) condonada(s)</span>
                    </p>
                </div>
            </div>

            <!-- Historial de pagos -->
            <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
                <div class="p-5 border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/50 flex items-center gap-2">
                    <TrendingUp :size="16" class="text-green-600" />
                    <h2 class="font-black text-slate-800 dark:text-white text-xs uppercase tracking-widest">Historial Completo de Pagos</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-100 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-800/50">
                                <th class="px-4 py-3 text-left text-[10px] font-black text-slate-500 uppercase">Folio</th>
                                <th class="px-4 py-3 text-left text-[10px] font-black text-slate-500 uppercase">Fecha</th>
                                <th class="px-4 py-3 text-left text-[10px] font-black text-slate-500 uppercase">Forma</th>
                                <th class="px-4 py-3 text-right text-[10px] font-black text-slate-500 uppercase">Capital</th>
                                <th class="px-4 py-3 text-right text-[10px] font-black text-slate-500 uppercase">Interés</th>
                                <th class="px-4 py-3 text-right text-[10px] font-black text-slate-500 uppercase">Mora</th>
                                <th class="px-4 py-3 text-right text-[10px] font-black text-slate-500 uppercase">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50 dark:divide-slate-800">
                            <tr v-for="p in pagos.filter(x => x.monto_recibido > 0)" :key="p.folio"
                                class="hover:bg-slate-50 dark:hover:bg-slate-800/30">
                                <td class="px-4 py-3 font-mono font-bold text-xs text-slate-700 dark:text-slate-300">{{ p.folio }}</td>
                                <td class="px-4 py-3 text-xs text-slate-500">{{ fmtDate(p.fecha_pago) }}</td>
                                <td class="px-4 py-3 text-xs text-slate-500">{{ p.forma_pago }}</td>
                                <td class="px-4 py-3 text-right text-blue-600 font-medium">{{ fmt(p.aplicado_capital) }}</td>
                                <td class="px-4 py-3 text-right text-red-600 font-medium">{{ fmt(p.aplicado_ordinario) }}</td>
                                <td class="px-4 py-3 text-right text-orange-600 font-medium">{{ fmt(p.aplicado_mora) }}</td>
                                <td class="px-4 py-3 text-right font-black text-slate-900 dark:text-white">{{ fmt(p.monto_recibido) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </AppLayout>
</template>
