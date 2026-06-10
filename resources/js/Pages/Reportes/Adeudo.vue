<script setup>
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    credito: Object,
    acreditado: Object,
    amortizaciones: Array,
    resumen: Object,
    pagos_realizados: Array,
    fecha_calculo: String,
});

const fechaSeleccionada = ref(props.fecha_calculo);

function recalcular() {
    router.get(route('reportes.adeudo', props.credito.id), {
        fecha_calculo: fechaSeleccionada.value,
    }, { preserveScroll: true, replace: true });
}

const money = (v) => new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN', minimumFractionDigits: 2 }).format(v || 0);

function fmtFecha(f) {
    if (!f) return '—';
    return new Date(f + 'T12:00:00').toLocaleDateString('es-MX');
}

function estadoClass(estado) {
    if (estado === 'Pagado')    return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400';
    if (estado === 'Condonado') return 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400';
    if (estado === 'Parcial')   return 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400';
    return 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400';
}

function printPage() { window.print(); }
</script>

<template>
    <AppLayout title="Reporte de Adeudo">
        <div class="p-6 max-w-5xl mx-auto">

            <!-- Encabezado -->
            <div class="flex flex-wrap justify-between items-start gap-4 mb-6">
                <div>
                    <p class="text-[10px] font-bold tracking-widest text-zinc-400 uppercase">IYEM · CREA · Reporte de Adeudo</p>
                    <h1 class="text-2xl font-medium dark:text-white">{{ acreditado?.nombre_completo }}</h1>
                    <p class="text-sm text-zinc-400 mt-0.5">
                        Contrato: <strong class="text-zinc-700 dark:text-zinc-300">{{ credito?.clave_contrato }}</strong> ·
                        {{ credito?.modalidad?.nombre }} ·
                        <span :class="{
                            'text-emerald-600': credito?.estatus === 'Activo',
                            'text-red-500': credito?.estatus === 'Moroso',
                            'text-blue-500': credito?.estatus === 'Liquidado',
                        }" class="font-semibold">{{ credito?.estatus }}</span>
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <!-- Fecha de cálculo -->
                    <div class="flex items-center gap-2 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-xl px-3 py-1.5">
                        <span class="text-[10px] font-bold text-zinc-500 uppercase tracking-widest whitespace-nowrap">Calcular al</span>
                        <input v-model="fechaSeleccionada" type="date"
                            class="text-sm font-semibold dark:text-white bg-transparent border-none focus:outline-none focus:ring-0 w-36" />
                        <button @click="recalcular"
                            class="px-2.5 py-1 rounded-lg text-[10px] font-bold bg-zinc-900 dark:bg-zinc-100 text-white dark:text-zinc-900 hover:opacity-80 transition">
                            ↻
                        </button>
                    </div>
                    <button @click="printPage"
                        class="px-4 py-2 rounded-xl text-sm font-medium bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-700 transition">
                        Imprimir
                    </button>
                    <Link :href="route('acreditados.show', credito?.acreditado_id)"
                        class="px-4 py-2 rounded-xl text-sm font-medium bg-zinc-900 dark:bg-zinc-100 text-white dark:text-zinc-900 hover:opacity-80 transition">
                        Ver Expediente
                    </Link>
                </div>
            </div>

            <!-- Resumen -->
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-6">
                <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-100 dark:border-zinc-800 p-5">
                    <p class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest">Capital Pendiente</p>
                    <p class="text-xl font-light dark:text-white mt-1">{{ money(resumen?.capital_pendiente) }}</p>
                </div>
                <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-100 dark:border-zinc-800 p-5">
                    <p class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest">Interés Pendiente</p>
                    <p class="text-xl font-light text-amber-500 mt-1">{{ money(resumen?.interes_pendiente) }}</p>
                </div>
                <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-100 dark:border-zinc-800 p-5">
                    <p class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest">Mora Acumulada</p>
                    <p class="text-xl font-light text-red-500 mt-1">{{ money(resumen?.mora_acumulada) }}</p>
                </div>
                <div class="col-span-2 md:col-span-2 bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-100 dark:border-zinc-800 p-5">
                    <p class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest">Total Adeudado</p>
                    <p class="text-3xl font-light dark:text-white mt-1">{{ money(resumen?.total_adeudo) }}</p>
                    <p class="text-[10px] text-zinc-400 mt-1">Capital + Interés + Mora al día de hoy</p>
                </div>
                <div class="bg-emerald-50 dark:bg-emerald-950/40 rounded-2xl border border-emerald-100 dark:border-emerald-900/40 p-5">
                    <p class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-widest">Monto Liquidación</p>
                    <p class="text-3xl font-light text-emerald-600 dark:text-emerald-400 mt-1">{{ money(resumen?.monto_liquidacion) }}</p>
                    <p class="text-[10px] text-emerald-600/70 dark:text-emerald-500/70 mt-1">Interés futuro se condona (RO)</p>
                </div>
            </div>

            <div v-if="resumen?.nota_liquidacion"
                class="bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-900/40 rounded-xl px-4 py-3 text-xs text-amber-700 dark:text-amber-400 mb-6">
                ⚠ {{ resumen.nota_liquidacion }}
            </div>

            <!-- Tabla de amortización -->
            <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-100 dark:border-zinc-800 overflow-hidden mb-6">
                <div class="px-6 py-4 border-b border-zinc-100 dark:border-zinc-800">
                    <p class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest">Estado de Cuotas</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                            <tr>
                                <th class="px-3 py-3 text-[10px] font-bold text-zinc-400 uppercase text-center">#</th>
                                <th class="px-3 py-3 text-[10px] font-bold text-zinc-400 uppercase text-left">Vencimiento</th>
                                <th class="px-3 py-3 text-[10px] font-bold text-zinc-400 uppercase text-right">Capital Pend.</th>
                                <th class="px-3 py-3 text-[10px] font-bold text-zinc-400 uppercase text-right">Interés Pend.</th>
                                <th class="px-3 py-3 text-[10px] font-bold text-zinc-400 uppercase text-right">Mora</th>
                                <th class="px-3 py-3 text-[10px] font-bold text-zinc-400 uppercase text-right">Total</th>
                                <th class="px-3 py-3 text-[10px] font-bold text-zinc-400 uppercase text-center">Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="r in amortizaciones" :key="r.numero_cuota"
                                class="border-t border-zinc-50 dark:border-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-800/30 transition-colors"
                                :class="{ 'opacity-50': r.estado === 'Pagado' || r.estado === 'Condonado' }">
                                <td class="px-3 py-2 text-center text-zinc-400">{{ r.numero_cuota }}</td>
                                <td class="px-3 py-2 text-zinc-600 dark:text-zinc-300">{{ fmtFecha(r.fecha_vencimiento) }}</td>
                                <td class="px-3 py-2 text-right font-mono dark:text-white">
                                    <template v-if="r.estado !== 'Pagado' && r.estado !== 'Condonado'">{{ money(r.capital_pend) }}</template>
                                    <template v-else>—</template>
                                </td>
                                <td class="px-3 py-2 text-right font-mono text-amber-600 dark:text-amber-400">
                                    <template v-if="r.estado !== 'Pagado' && r.estado !== 'Condonado'">{{ money(r.interes_pend) }}</template>
                                    <template v-else>—</template>
                                </td>
                                <td class="px-3 py-2 text-right font-mono text-red-500">
                                    <template v-if="r.mora_al_dia > 0">{{ money(r.mora_al_dia) }}</template>
                                    <template v-else>—</template>
                                </td>
                                <td class="px-3 py-2 text-right font-bold font-mono"
                                    :class="r.total_a_pagar > 0 ? 'dark:text-white' : 'text-zinc-400'">
                                    {{ r.total_a_pagar > 0 ? money(r.total_a_pagar) : '—' }}
                                </td>
                                <td class="px-3 py-2 text-center">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                                          :class="estadoClass(r.estado)">
                                        {{ r.estado }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Historial de pagos -->
            <div v-if="pagos_realizados?.length" class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-100 dark:border-zinc-800 overflow-hidden">
                <div class="px-6 py-4 border-b border-zinc-100 dark:border-zinc-800">
                    <p class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest">Historial de Pagos Aplicados</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                            <tr>
                                <th class="px-4 py-3 text-[10px] font-bold text-zinc-400 uppercase text-left">Folio</th>
                                <th class="px-4 py-3 text-[10px] font-bold text-zinc-400 uppercase text-left">Fecha</th>
                                <th class="px-4 py-3 text-[10px] font-bold text-zinc-400 uppercase text-right">Recibido</th>
                                <th class="px-4 py-3 text-[10px] font-bold text-zinc-400 uppercase text-right hidden md:table-cell">Capital</th>
                                <th class="px-4 py-3 text-[10px] font-bold text-zinc-400 uppercase text-right hidden md:table-cell">Interés</th>
                                <th class="px-4 py-3 text-[10px] font-bold text-zinc-400 uppercase text-right hidden md:table-cell">Mora</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="p in pagos_realizados" :key="p.folio"
                                class="border-t border-zinc-50 dark:border-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-800/30">
                                <td class="px-4 py-2 font-mono text-zinc-400">{{ p.folio }}</td>
                                <td class="px-4 py-2 text-zinc-600 dark:text-zinc-300">{{ fmtFecha(p.fecha_pago) }}</td>
                                <td class="px-4 py-2 text-right font-bold font-mono dark:text-white">{{ money(p.monto_recibido) }}</td>
                                <td class="px-4 py-2 text-right font-mono dark:text-zinc-300 hidden md:table-cell">{{ money(p.aplicado_capital) }}</td>
                                <td class="px-4 py-2 text-right font-mono text-amber-500 hidden md:table-cell">{{ money(p.aplicado_ordinario) }}</td>
                                <td class="px-4 py-2 text-right font-mono text-red-400 hidden md:table-cell">{{ money(p.aplicado_mora) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </AppLayout>
</template>

<style>
@media print {
    nav, header, aside, footer, button { display: none !important; }
    .p-6 { padding: 0 !important; }
}
</style>
