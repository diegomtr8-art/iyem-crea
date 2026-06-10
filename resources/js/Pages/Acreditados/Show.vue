<script setup>
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import {
    User, MapPin, CreditCard, FileText, Printer,
    ArrowLeft, DollarSign, Scale, CheckCircle2,
    Calendar, AlertCircle, Timer, TrendingUp, Clock,
    XOctagon, Award, FolderOpen, RefreshCw, FileCheck,
    Banknote, Receipt, RotateCcw, FileX
} from 'lucide-vue-next';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    acreditado: Object,
    credito: Object,
    amortizaciones: Array
});

// --- ESTADO LOCAL ---
const filaSeleccionada = ref(null);

// --- LÓGICA DE SELECCIÓN ---
// Solo se pueden seleccionar cuotas no pagadas
const seleccionarFila = (fila) => {
    if (fila.estado === 'Pagado' || fila.estado === 'Condonado') return;
    filaSeleccionada.value = filaSeleccionada.value?.id === fila.id ? null : fila;
};

// --- FORMATEADORES ---
const formatCurrency = (value) => {
    const num = parseFloat(value) || 0;
    return new Intl.NumberFormat('es-MX', {
        style: 'currency',
        currency: 'MXN',
        minimumFractionDigits: 2
    }).format(num);
};
const irAPagar = (id) => {
    router.get(route('pagos.create', id));
};

const formatDate = (dateStr) => {
    if (!dateStr) return '—';
    const [y, m, d] = dateStr.split('-');
    return `${d}/${m}/${y}`;
};

const imprimirExpediente = () => window.print();

// Condonación
const modalCondonar = ref(false);
const formCondonar = useForm({
    motivo: '',
    tipo_causa: 'Fallecimiento',
});
const confirmarCondonar = () => {
    formCondonar.post(route('creditos.condonar', props.credito?.id), {
        onSuccess: () => { modalCondonar.value = false; },
    });
};

// --- CLASE DE FILA según estado ---
// Pagado: opaco verde
// En mora real (>5 días): fondo rojo suave
// En periodo de gracia (1-5 días): fondo amarillo suave
// Pendiente futuro: normal
const clasesFila = (fila) => {
    if (fila.estado === 'Condonado')   return 'opacity-40 cursor-not-allowed bg-purple-50/30 dark:bg-purple-900/10';
    if (fila.estado === 'Pagado')      return 'opacity-50 cursor-not-allowed bg-green-50/30 dark:bg-green-900/10';
    if (fila.mora_al_dia > 0)         return 'bg-red-50/60 dark:bg-red-900/20 cursor-pointer hover:bg-red-50';
    if (fila.en_periodo_gracia)       return 'bg-yellow-50/60 dark:bg-yellow-900/10 cursor-pointer hover:bg-yellow-50';
    return 'cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800/40';
};

// --- TOTALES GLOBALES ---
// Calculados directamente sobre los campos que vienen del backend
const totalesCalculados = computed(() => {
    if (!props.amortizaciones?.length) {
        return { capital: 0, interes: 0, moratorio: 0, pagado: 0, restante: 0 };
    }

    return props.amortizaciones.reduce((acc, f) => {
        const capitalEsperado  = parseFloat(f.capital_esperado)           || 0;
        const interesEsperado  = parseFloat(f.interes_ordinario_esperado)  || 0;
        const moraAlDia        = parseFloat(f.mora_al_dia)                 || 0;
        const capitalPagado    = parseFloat(f.capital_pagado)              || 0;
        const interesPagado    = parseFloat(f.interes_ordinario_pagado)    || 0;
        const moratorioPagado  = parseFloat(f.interes_moratorio_pagado)    || 0;
        const pagoRestante     = parseFloat(f.pago_restante)               || 0;

        return {
            capital:   acc.capital   + capitalEsperado,
            interes:   acc.interes   + interesEsperado,
            moratorio: acc.moratorio + moraAlDia,
            pagado:    acc.pagado    + capitalPagado + interesPagado + moratorioPagado,
            restante:  acc.restante  + pagoRestante  + moraAlDia,
        };
    }, { capital: 0, interes: 0, moratorio: 0, pagado: 0, restante: 0 });
});

// --- PAGO SUGERIDO PARA LA BARRA FLOTANTE ---
// total_a_pagar viene precalculado del backend: pago_restante + mora_al_dia
const pagoSugerido = computed(() => {
    if (!filaSeleccionada.value) return 0;
    return parseFloat(filaSeleccionada.value.total_a_pagar) || 0;
});

// --- DESGLOSE del pago sugerido para mostrar en la barra ---
const desglosePago = computed(() => {
    if (!filaSeleccionada.value) return null;
    const f = filaSeleccionada.value;
    return {
        cuotaBase: parseFloat(f.pago_restante) || 0,
        mora:      parseFloat(f.mora_al_dia)   || 0,
        diasAtraso: f.dias_atraso              || 0,
    };
});
</script>

<template>
    <Head :title="'Expediente - ' + (acreditado?.nombre_completo || 'Cargando...')" />

    <AppLayout v-if="acreditado && credito">
        <div class="max-w-[98%] mx-auto py-8 px-4 print:p-0">

            <!-- ENCABEZADO -->
            <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4 print:hidden">
                <div class="flex items-center gap-4">
                    <button
                        @click="() => router.visit(route('acreditados.index'))"
                        class="p-3 rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 transition-colors shadow-sm"
                    >
                        <ArrowLeft :size="20" class="text-slate-600 dark:text-slate-300" />
                    </button>
                    <div>
                        <h1 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Expediente Digital</h1>
                        <p class="text-slate-500 dark:text-slate-400 font-medium text-sm">Control de amortizaciones y seguimiento de pagos.</p>
                    </div>
                </div>

                <button
                    @click="() => router.visit(route('acreditados.expediente', acreditado.id))"
                    class="px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-bold text-xs uppercase flex items-center gap-2 transition-all shadow-sm"
                >
                    <FolderOpen :size="16" /> Expediente Digital
                </button>
                <button
                    @click="() => router.visit(route('operaciones.index', acreditado.id))"
                    class="px-6 py-3 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 rounded-xl font-bold text-xs uppercase flex items-center gap-2 hover:bg-slate-50 transition-all shadow-sm"
                >
                    Ver Movimientos
                </button>
                <button
                    v-if="credito?.estatus !== 'Liquidado'"
                    @click="() => router.visit(route('reportes.adeudo', credito.id))"
                    class="px-6 py-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 text-amber-700 dark:text-amber-400 rounded-xl font-bold text-xs uppercase flex items-center gap-2 hover:bg-amber-100 transition-all shadow-sm"
                >
                    Reporte Adeudo
                </button>
                <button
                    v-if="credito?.estatus === 'Liquidado'"
                    @click="() => router.visit(route('creditos.dictamen', credito.id))"
                    class="px-6 py-3 bg-green-600 hover:bg-green-700 text-white rounded-xl font-bold text-xs uppercase flex items-center gap-2 transition-all shadow-sm"
                >
                    <Award :size="16" /> Dictamen
                </button>
                <button
                    v-if="credito?.estatus !== 'Liquidado' && credito?.estatus !== 'Cancelado'"
                    @click="modalCondonar = true"
                    class="px-6 py-3 bg-red-100 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-600 dark:text-red-400 rounded-xl font-bold text-xs uppercase flex items-center gap-2 hover:bg-red-200 transition-all shadow-sm"
                >
                    <XOctagon :size="16" /> Condonar
                </button>
                <!-- Registrar Pago rápido -->
                <button
                    v-if="credito?.estatus !== 'Liquidado' && credito?.estatus !== 'Cancelado'"
                    @click="() => router.visit(route('pagos.create', credito.id))"
                    class="px-6 py-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-bold text-xs uppercase flex items-center gap-2 transition-all shadow-sm"
                >
                    <Banknote :size="16" /> Registrar Pago
                </button>

                <!-- Reestructurar crédito -->
                <button
                    v-if="credito?.estatus !== 'Liquidado' && credito?.estatus !== 'Cancelado'"
                    @click="() => router.visit(route('creditos.reestructurar.create', credito.id))"
                    class="px-6 py-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 text-blue-700 dark:text-blue-400 rounded-xl font-bold text-xs uppercase flex items-center gap-2 hover:bg-blue-100 transition-all shadow-sm"
                >
                    <RotateCcw :size="16" /> Reestructurar
                </button>

                <!-- Condonación Formal -->
                <button
                    v-if="credito?.estatus !== 'Liquidado' && credito?.estatus !== 'Cancelado'"
                    @click="() => router.visit(route('creditos.condonacion-formal.create', credito.id))"
                    class="px-6 py-3 bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 text-purple-700 dark:text-purple-400 rounded-xl font-bold text-xs uppercase flex items-center gap-2 hover:bg-purple-100 transition-all shadow-sm"
                >
                    <FileX :size="16" /> Condonación Formal
                </button>

                <!-- Contrato PDF -->
                <a
                    :href="route('creditos.contrato.pdf', credito.id)"
                    target="_blank"
                    class="px-6 py-3 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 rounded-xl font-bold text-xs uppercase flex items-center gap-2 hover:bg-slate-100 transition-all shadow-sm"
                >
                    <FileText :size="16" /> Contrato PDF
                </a>

                <!-- Estado de Cuenta PDF -->
                <a
                    :href="route('creditos.estado-cuenta.pdf', credito.id)"
                    target="_blank"
                    class="px-6 py-3 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 rounded-xl font-bold text-xs uppercase flex items-center gap-2 hover:bg-slate-100 transition-all shadow-sm"
                >
                    <Receipt :size="16" /> Estado de Cuenta
                </a>

                <!-- Constancia de No Adeudo (solo Liquidado) -->
                <a
                    v-if="credito?.estatus === 'Liquidado'"
                    :href="route('creditos.constancia.pdf', credito.id)"
                    target="_blank"
                    class="px-6 py-3 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-400 rounded-xl font-bold text-xs uppercase flex items-center gap-2 hover:bg-emerald-100 transition-all shadow-sm"
                >
                    <FileCheck :size="16" /> Constancia
                </a>

                <button
                    @click="imprimirExpediente"
                    class="px-6 py-3 bg-slate-900 dark:bg-red-700 text-white rounded-xl font-bold text-xs uppercase flex items-center gap-2 hover:opacity-90 transition-all shadow-lg"
                >
                    <Printer :size="16" /> Imprimir
                </button>
            </div>

            <!-- TARJETAS SUPERIORES -->
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 mb-8">

                <!-- Info del acreditado -->
                <div class="lg:col-span-3 bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 p-6 shadow-sm flex flex-col md:flex-row gap-6">
                    <div class="w-20 h-20 bg-slate-100 dark:bg-slate-800 rounded-2xl flex items-center justify-center text-slate-400 shrink-0">
                        <User :size="40" />
                    </div>
                    <div class="flex-1">
                        <div class="mb-4">
                            <h2 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight uppercase">
                                {{ acreditado.nombre_completo }}
                            </h2>
                            <p class="text-red-600 dark:text-red-400 font-bold text-xs flex items-center gap-2 mt-1 uppercase">
                                <FileText :size="14" /> Contrato: {{ credito.clave_contrato }}
                            </p>
                        </div>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-4 pt-4 border-t border-slate-100 dark:border-slate-800">
                            <div>
                                <p class="text-[10px] font-black text-slate-400 uppercase">Identificación</p>
                                <p class="text-xs font-bold text-slate-700 dark:text-slate-300 font-mono">{{ acreditado.rfc }}</p>
                                <p class="text-[10px] text-slate-500 font-mono">{{ acreditado.curp }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-black text-slate-400 uppercase">Ubicación</p>
                                <p class="text-xs font-bold text-slate-700 dark:text-slate-300 flex items-center gap-1 uppercase">
                                    <MapPin :size="12" class="text-red-500" /> {{ acreditado.municipio }}
                                </p>
                            </div>
                            <div>
                                <p class="text-[10px] font-black text-slate-400 uppercase">Modalidad</p>
                                <p class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase">
                                    {{ credito.modalidad?.nombre || 'General' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tarjeta monto -->
                <div class="bg-[#701a27] rounded-3xl p-6 text-white relative overflow-hidden shadow-xl">
                    <DollarSign class="absolute -right-4 -bottom-4 text-white/10" :size="100" />
                    <p class="text-[10px] font-black uppercase tracking-widest opacity-80 mb-2">Monto Otorgado</p>
                    <h3 class="text-3xl font-black mb-4">{{ formatCurrency(credito.monto_otorgado) }}</h3>
                    <div class="space-y-2 text-[11px] border-t border-white/20 pt-4">
                        <div class="flex justify-between">
                            <span>Tasa Ordinaria:</span>
                            <span class="font-bold">{{ credito.tasa_interes_ordinario }}% Anual</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Tasa Moratoria:</span>
                            <span class="font-bold text-orange-300">{{ credito.tasa_interes_moratorio }}% Anual</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Plazo:</span>
                            <span class="font-bold">{{ credito.plazo_meses }} Meses</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TABLA DE AMORTIZACIÓN -->
            <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden mb-24">

                <!-- Encabezado tabla -->
                <div class="p-5 border-b border-slate-100 dark:border-slate-800 flex flex-wrap items-center justify-between gap-3 bg-slate-50/50 dark:bg-slate-800/50">
                    <h2 class="font-black text-slate-800 dark:text-white flex items-center gap-2 uppercase text-xs tracking-widest">
                        <Scale class="text-red-700" :size="18" /> Tabla de Amortización
                    </h2>
                    <div class="flex flex-wrap gap-4 text-[10px] font-bold uppercase">
                        <span class="flex items-center gap-1 text-slate-400">
                            <AlertCircle :size="12"/> MXN
                        </span>
                        <span class="flex items-center gap-1 text-yellow-500">
                            <Clock :size="12"/> Gracia: 5 días
                        </span>
                        <span class="flex items-center gap-1 text-orange-500">
                            <Timer :size="12"/> Año: 360 días
                        </span>
                        <!-- Leyenda de colores -->
                        <span class="flex items-center gap-1 text-red-500">
                            <span class="w-2 h-2 rounded-full bg-red-400 inline-block"></span> Con mora
                        </span>
                        <span class="flex items-center gap-1 text-yellow-600">
                            <span class="w-2 h-2 rounded-full bg-yellow-400 inline-block"></span> Periodo gracia
                        </span>
                        <span class="flex items-center gap-1 text-green-600">
                            <span class="w-2 h-2 rounded-full bg-green-400 inline-block"></span> Pagado
                        </span>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-left text-sm">
                        <thead>
                            <tr class="bg-slate-100 dark:bg-slate-800/80 text-slate-500 dark:text-slate-400 uppercase text-[10px] font-black">
                                <th class="px-4 py-4 border-b dark:border-slate-700">#</th>
                                <th class="px-4 py-4 border-b dark:border-slate-700">Vencimiento</th>
                                <th class="px-4 py-4 border-b dark:border-slate-700">Últ. Pago</th>
                                <th class="px-4 py-4 border-b dark:border-slate-700 text-right">Saldo</th>
                                <th class="px-4 py-4 border-b dark:border-slate-700 text-right">Capital</th>
                                <th class="px-4 py-4 border-b dark:border-slate-700 text-right">Ordinario</th>
                                <th class="px-4 py-4 border-b dark:border-slate-700 text-right">
                                    Mora
                                    <span class="block text-[8px] text-slate-400 normal-case font-normal">Dinámica / Cobrada</span>
                                </th>
                                <th class="px-4 py-4 border-b dark:border-slate-700 text-right">Cuota Base</th>
                                <th class="px-4 py-4 border-b dark:border-slate-700 text-right">Total a Pagar</th>
                                <th class="px-4 py-4 border-b dark:border-slate-700 text-right">Pagado</th>
                                <th class="px-4 py-4 border-b dark:border-slate-700 text-center">Estado</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr
                                v-for="fila in amortizaciones"
                                :key="fila.id"
                                @click="seleccionarFila(fila)"
                                :class="[
                                    clasesFila(fila),
                                    filaSeleccionada?.id === fila.id
                                        ? 'ring-2 ring-inset ring-red-400 dark:ring-red-600'
                                        : ''
                                ]"
                                class="transition-all duration-150"
                            >
                                <!-- # cuota -->
                                <td class="px-4 py-4 font-black text-slate-900 dark:text-white">
                                    {{ fila.numero_cuota }}
                                </td>

                                <!-- Vencimiento -->
                                <td class="px-4 py-4 text-slate-600 dark:text-slate-400 text-xs">
                                    {{ formatDate(fila.fecha_vencimiento) }}
                                </td>

                                <!-- Último pago -->
                                <td class="px-4 py-4 text-slate-500 dark:text-slate-500 text-xs">
                                    {{ formatDate(fila.fecha_ultimo_pago) }}
                                </td>

                                <!-- Saldo insoluto -->
                                <td class="px-4 py-4 text-right font-mono text-slate-600 dark:text-slate-400 text-xs">
                                    {{ formatCurrency(fila.saldo_insoluto) }}
                                </td>

                                <!-- Capital esperado -->
                                <td class="px-4 py-4 text-right font-semibold text-slate-700 dark:text-slate-200 text-xs">
                                    {{ formatCurrency(fila.capital_esperado) }}
                                </td>

                                <!-- Interés ordinario -->
                                <td class="px-4 py-4 text-right text-red-500 font-medium text-xs">
                                    {{ formatCurrency(fila.interes_ordinario_esperado) }}
                                </td>

                                <!-- Mora al día de hoy con días de atraso / Mora cobrada si ya pagó -->
                                <td class="px-4 py-4 text-right text-xs">
                                    <template v-if="fila.estado === 'Pagado'">
                                        <span v-if="(fila.mora_al_dia || 0) > 0" class="text-orange-500 font-bold">
                                            {{ formatCurrency(fila.mora_al_dia) }}
                                            <span class="block text-[9px] text-orange-400 font-normal">cobrada</span>
                                        </span>
                                        <span v-else class="text-slate-300">—</span>
                                    </template>
                                    <template v-else-if="fila.en_periodo_gracia">
                                        <span class="text-yellow-600 font-bold">
                                            {{ formatCurrency(0) }}
                                        </span>
                                        <span class="block text-[9px] text-yellow-500">
                                            {{ fila.dias_atraso }}d gracia
                                        </span>
                                    </template>
                                    <template v-else-if="fila.mora_al_dia > 0">
                                        <span class="text-orange-600 font-black">
                                            {{ formatCurrency(fila.mora_al_dia) }}
                                        </span>
                                        <span class="block text-[9px] text-orange-400">
                                            {{ fila.dias_atraso }} días
                                        </span>
                                    </template>
                                    <template v-else>
                                        <span class="text-slate-300 text-[10px]">—</span>
                                    </template>
                                </td>

                                <!-- Cuota base pendiente (pago_restante, ya descontados pagos parciales) -->
                                <td class="px-4 py-4 text-right font-bold text-xs"
                                    :class="fila.pago_restante > 0 ? 'text-slate-700 dark:text-slate-200' : 'text-green-500'">
                                    {{ formatCurrency(fila.pago_restante) }}
                                </td>

                                <!-- Total a pagar hoy (cuota + mora) — viene precalculado del backend -->
                                <td class="px-4 py-4 text-right font-black text-xs"
                                    :class="fila.estado === 'Pagado' ? 'text-green-500' : (fila.total_a_pagar > 0 ? 'text-red-600' : 'text-slate-400')">
                                    {{ fila.estado === 'Pagado' ? '✓' : formatCurrency(fila.total_a_pagar) }}
                                </td>

                                <!-- Pagado acumulado -->
                                <td class="px-4 py-4 text-right text-green-600 font-bold text-xs">
                                    {{ formatCurrency(
                                        (parseFloat(fila.capital_pagado) || 0)
                                        + (parseFloat(fila.interes_ordinario_pagado) || 0)
                                        + (parseFloat(fila.interes_moratorio_pagado) || 0)
                                    ) }}
                                </td>

                                <!-- Estado badge -->
                                <td class="px-4 py-4 text-center">
                                    <span :class="[
                                        fila.estado === 'Condonado'
                                            ? 'bg-purple-100 text-purple-700 dark:bg-purple-900/40'
                                            : fila.estado === 'Pagado'
                                                ? 'bg-green-100 text-green-700 dark:bg-green-900/40'
                                                : fila.mora_al_dia > 0
                                                    ? 'bg-red-100 text-red-700 dark:bg-red-900/40'
                                                    : fila.en_periodo_gracia
                                                        ? 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40'
                                                        : 'bg-slate-100 text-slate-600 dark:bg-slate-800',
                                        'px-2 py-1 rounded-lg text-[10px] font-black uppercase tracking-tighter'
                                    ]">
                                        {{ fila.estado === 'Condonado' ? 'Condonado' : fila.estado === 'Pagado' ? 'Pagado' : fila.mora_al_dia > 0 ? 'Vencido' : fila.en_periodo_gracia ? 'Gracia' : 'Pendiente' }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>

                        <!-- TOTALES -->
                        <tfoot class="bg-slate-900 dark:bg-black text-white font-bold">
                            <tr>
                                <td colspan="3" class="px-4 py-4 text-xs uppercase tracking-widest text-slate-400 text-center">
                                    Resumen de Cartera
                                </td>
                                <td class="px-4 py-4"></td>
                                <td class="px-4 py-4 text-right text-xs">
                                    {{ formatCurrency(totalesCalculados.capital) }}
                                </td>
                                <td class="px-4 py-4 text-right text-xs text-red-400">
                                    {{ formatCurrency(totalesCalculados.interes) }}
                                </td>
                                <td class="px-4 py-4 text-right text-xs text-orange-400">
                                    {{ formatCurrency(totalesCalculados.moratorio) }}
                                </td>
                                <td class="px-4 py-4"></td>
                                <td class="px-4 py-4 text-right text-sm text-red-400">
                                    {{ formatCurrency(totalesCalculados.restante) }}
                                </td>
                                <td class="px-4 py-4 text-right text-sm text-green-400">
                                    {{ formatCurrency(totalesCalculados.pagado) }}
                                </td>
                                <td class="px-4 py-4"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- BARRA FLOTANTE DE PAGO -->
            <Transition
                enter-active-class="transition duration-300 ease-out"
                enter-from-class="transform translate-y-10 opacity-0"
                enter-to-class="transform translate-y-0 opacity-100"
                leave-active-class="transition duration-200 ease-in"
                leave-from-class="transform translate-y-0 opacity-100"
                leave-to-class="transform translate-y-10 opacity-0"
            >
                <div v-if="filaSeleccionada" class="fixed bottom-10 left-1/2 -translate-x-1/2 z-50 print:hidden w-full max-w-2xl px-4">
                    <div class="bg-white dark:bg-slate-800 p-2 rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-700 flex items-center gap-4 pr-4">
                        
                        <!-- Ícono -->
                        <div class="bg-red-600 text-white p-4 rounded-2xl shadow-lg shadow-red-500/30 shrink-0">
                            <CreditCard :size="28" />
                        </div>

                        <!-- Desglose -->
                        <div class="flex-1">
                            <span class="text-[10px] text-slate-400 uppercase font-black tracking-widest">
                                Mes {{ filaSeleccionada.numero_cuota }} — Pago a realizar hoy
                            </span>
                            <div class="flex items-baseline gap-3 mt-0.5">
                                <span class="text-xl font-black text-slate-900 dark:text-white">
                                    {{ formatCurrency(pagoSugerido) }}
                                </span>
                                <!-- Desglose cuota + mora -->
                                <div class="text-[10px] text-slate-400 leading-tight" v-if="desglosePago">
                                    <span>Cuota: {{ formatCurrency(desglosePago.cuotaBase) }}</span>
                                    <span v-if="desglosePago.mora > 0" class="text-orange-500 ml-2">
                                        + Mora {{ desglosePago.diasAtraso }}d: {{ formatCurrency(desglosePago.mora) }}
                                    </span>
                                    <span v-if="desglosePago.mora === 0 && filaSeleccionada.en_periodo_gracia" class="text-yellow-500 ml-2">
                                        (período de gracia)
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Botón -->
                       <button
        @click="irAPagar(credito.id)"
        class="bg-slate-900 dark:bg-red-700 hover:bg-black dark:hover:bg-red-600 text-white px-8 py-4 rounded-2xl font-black uppercase text-[10px] tracking-widest transition-all active:scale-95 shadow-lg shrink-0"
    >
        Registrar Pago
    </button>
                    </div>
                </div>
            </Transition>

        </div>

        <!-- MODAL CONDONACIÓN -->
        <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100"
                    leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="modalCondonar" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
                <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 w-full max-w-md shadow-2xl border border-slate-200 dark:border-slate-700">
                    <div class="flex items-center gap-3 mb-5">
                        <div class="w-10 h-10 rounded-xl bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                            <XOctagon :size="20" class="text-red-600" />
                        </div>
                        <div>
                            <h3 class="font-black text-slate-900 dark:text-white">Condonación de Crédito</h3>
                            <p class="text-xs text-slate-400">Esta acción no se puede deshacer</p>
                        </div>
                    </div>

                    <p class="text-sm text-slate-600 dark:text-slate-400 mb-4">
                        Se condonará el saldo pendiente de todas las cuotas y el crédito quedará marcado como <strong>Liquidado</strong>.
                    </p>

                    <div class="mb-3">
                        <label class="text-[10px] font-black text-slate-500 uppercase">Tipo de Causa</label>
                        <select v-model="formCondonar.tipo_causa"
                            class="w-full mt-1 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-red-500">
                            <option>Fallecimiento</option>
                            <option>Autorización Directiva</option>
                            <option>Caso Social</option>
                            <option>Otro</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="text-[10px] font-black text-slate-500 uppercase">Motivo</label>
                        <textarea v-model="formCondonar.motivo" rows="3" placeholder="Describe el motivo de la condonación..."
                            class="w-full mt-1 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-red-500 resize-none" />
                        <p v-if="formCondonar.errors.motivo" class="text-xs text-red-500 mt-1">{{ formCondonar.errors.motivo }}</p>
                    </div>

                    <div class="flex gap-3">
                        <button @click="modalCondonar = false"
                            class="flex-1 px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 text-sm font-bold text-slate-600 hover:bg-slate-50 transition-colors">
                            Cancelar
                        </button>
                        <button @click="confirmarCondonar"
                            :disabled="formCondonar.processing || !formCondonar.motivo"
                            class="flex-1 px-4 py-3 rounded-xl bg-red-600 text-white text-sm font-bold hover:bg-red-700 disabled:opacity-50 transition-colors">
                            Confirmar Condonación
                        </button>
                    </div>
                </div>
            </div>
        </Transition>

    </AppLayout>
</template>

<style scoped>
@media print {
    .print\:hidden { display: none !important; }
    table { border-collapse: collapse !important; width: 100% !important; }
    th, td { border: 1px solid #ddd !important; padding: 8px !important; }
}

.overflow-x-auto::-webkit-scrollbar { height: 6px; }
.overflow-x-auto::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
</style>