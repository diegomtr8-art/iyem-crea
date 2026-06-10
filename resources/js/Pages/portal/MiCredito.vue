<script setup lang="ts">
import BeneficiarioLayout from '@/layouts/BeneficiarioLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import {
    BadgeCheck, CalendarDays, CreditCard, DollarSign, TrendingUp,
    FileText, Clock, CheckCircle2, AlertCircle, Minus,
    Download, Calculator, X
} from 'lucide-vue-next';
import { router } from '@inertiajs/vue3';

const props = defineProps<{
    credito: {
        id: number;
        clave_contrato?: string;
        monto_otorgado: number;
        plazo_meses: number;
        fecha_entrega?: string;
        tasa_interes_ordinario: number;
        estatus: string;
        modalidad?: string;
        acreditado: { nombre_completo: string; municipio: string };
        tabla: Array<{
            numero_cuota: number;
            fecha_vencimiento: string;
            saldo_insoluto: number;
            capital: number;
            ordinario: number;
            moratorio: number;
            capital_pagado: number;
            ordinario_pagado: number;
            total_pagado: number;
            estado: string;
        }>;
        pagos: Array<{
            id: number;
            folio?: string;
            fecha_pago: string;
            monto_recibido: number;
            forma_pago?: string;
        }>;
    };
}>();

const activeTab = ref<'tabla' | 'pagos'>('tabla');

const estadoCuota = (item: typeof props.credito.tabla[0]) => {
    if (item.estado === 'Condonado') return { texto: 'CONDONADO', clase: 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400' };

    const hoy = new Date();
    const venc = new Date(item.fecha_vencimiento);
    const diff = Math.floor((hoy.getTime() - venc.getTime()) / 86400000);
    const total = (item.capital ?? 0) + (item.ordinario ?? 0);
    const pagado = item.total_pagado ?? 0;

    if (item.estado === 'Pagado' || pagado >= total)
                           return { texto: 'PAGADO',    clase: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' };
    if (diff > 5)          return { texto: 'VENCIDO',   clase: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' };
    if (diff > 0)          return { texto: 'GRACIA',    clase: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400' };
    return                        { texto: 'PENDIENTE', clase: 'bg-slate-100 text-slate-600 dark:bg-zinc-800 dark:text-zinc-400' };
};

const fmt = (n: number) => Number(n ?? 0).toLocaleString('es-MX', { minimumFractionDigits: 2 });

const estadoCredito: Record<string, { color: string; bg: string }> = {
    Activo:     { color: 'text-emerald-700 dark:text-emerald-400', bg: 'bg-emerald-100 dark:bg-emerald-900/30' },
    Moroso:     { color: 'text-red-700 dark:text-red-400',         bg: 'bg-red-100 dark:bg-red-900/30' },
    Liquidado:  { color: 'text-blue-700 dark:text-blue-400',       bg: 'bg-blue-100 dark:bg-blue-900/30' },
    Cancelado:  { color: 'text-slate-600 dark:text-zinc-400',      bg: 'bg-slate-100 dark:bg-zinc-800' },
};

const cfg = computed(() => estadoCredito[props.credito.estatus] ?? estadoCredito.Activo);

const totalPagado = computed(() =>
    props.credito.pagos.reduce((s, p) => s + Number(p.monto_recibido ?? 0), 0)
);
const cuotasActivas = computed(() => props.credito.tabla.filter(c => c.estado !== 'Condonado'));
const cuotasPagadas = computed(() => cuotasActivas.value.filter(c => estadoCuota(c).texto === 'PAGADO').length);
const cuotasVencidas = computed(() => cuotasActivas.value.filter(c => estadoCuota(c).texto === 'VENCIDO').length);
const cuotasPendientes = computed(() => cuotasActivas.value.filter(c => estadoCuota(c).texto === 'PENDIENTE').length);

// Liquidación anticipada
const mostrarLiquidacion = ref(false);
const liquidacion = ref<null | { capital_pendiente: number; interes_proyectado: number; mora_acumulada: number; total_liquidacion: number; fecha_calculo: string }>(null);
const cargandoLiquidacion = ref(false);

const calcularLiquidacion = async () => {
    cargandoLiquidacion.value = true;
    mostrarLiquidacion.value = true;
    try {
        const resp = await fetch(route('portal.credito.liquidacion'));
        liquidacion.value = await resp.json();
    } catch (e) {
        liquidacion.value = null;
    } finally {
        cargandoLiquidacion.value = false;
    }
};
</script>

<template>
    <BeneficiarioLayout>
        <Head title="Mi Crédito — CREA" />

        <div class="space-y-8">

            <!-- Encabezado -->
            <div class="space-y-1">
                <p class="text-xs font-bold uppercase tracking-widest text-red-700">Portal Ciudadano CREA</p>
                <h1 class="text-2xl md:text-3xl font-black text-slate-900 dark:text-white flex items-center gap-3">
                    <BadgeCheck size="28" class="text-red-700" /> Mi Crédito
                </h1>
            </div>

            <!-- Cards de resumen -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Monto -->
                <div class="bg-gradient-to-br from-red-700 to-red-900 rounded-3xl p-6 text-white shadow-2xl shadow-red-900/30 col-span-1 sm:col-span-2">
                    <p class="text-red-200 text-xs font-bold uppercase tracking-wider mb-2">Crédito Otorgado</p>
                    <p class="text-4xl font-black">${{ fmt(credito.monto_otorgado) }}</p>
                    <div class="flex items-center gap-3 mt-4">
                        <span :class="['px-3 py-1 rounded-full text-xs font-bold', cfg.bg, cfg.color]">{{ credito.estatus }}</span>
                        <span v-if="credito.modalidad" class="text-red-200 text-xs">{{ credito.modalidad }}</span>
                    </div>
                </div>

                <!-- Plazo -->
                <div class="bg-white dark:bg-zinc-900 rounded-3xl border border-slate-100 dark:border-zinc-800 p-6 shadow-sm">
                    <div class="flex items-center gap-2 mb-3">
                        <CalendarDays size="18" class="text-red-700" />
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Plazo</p>
                    </div>
                    <p class="text-3xl font-black text-slate-900 dark:text-white">{{ credito.plazo_meses }}</p>
                    <p class="text-slate-500 dark:text-zinc-400 text-sm mt-1">meses</p>
                    <p class="text-xs text-slate-400 dark:text-zinc-500 mt-3">Inicio: {{ credito.fecha_entrega ?? '—' }}</p>
                </div>

                <!-- Tasa -->
                <div class="bg-white dark:bg-zinc-900 rounded-3xl border border-slate-100 dark:border-zinc-800 p-6 shadow-sm">
                    <div class="flex items-center gap-2 mb-3">
                        <TrendingUp size="18" class="text-red-700" />
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Tasa Ordinaria</p>
                    </div>
                    <p class="text-3xl font-black text-slate-900 dark:text-white">{{ credito.tasa_interes_ordinario }}%</p>
                    <p class="text-slate-500 dark:text-zinc-400 text-sm mt-1">mensual</p>
                </div>
            </div>

            <!-- Barra de progreso -->
            <div class="bg-white dark:bg-zinc-900 rounded-3xl border border-slate-100 dark:border-zinc-800 p-6 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-4">Progreso del Crédito</p>
                <div class="grid grid-cols-3 gap-4 mb-4">
                    <div class="text-center">
                        <p class="text-2xl font-black text-green-600">{{ cuotasPagadas }}</p>
                        <p class="text-xs text-slate-500 dark:text-zinc-400 mt-1">Cuotas pagadas</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-black text-slate-900 dark:text-white">{{ cuotasPendientes }}</p>
                        <p class="text-xs text-slate-500 dark:text-zinc-400 mt-1">Pendientes</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-black text-red-600">{{ cuotasVencidas }}</p>
                        <p class="text-xs text-slate-500 dark:text-zinc-400 mt-1">Vencidas</p>
                    </div>
                </div>
                <div class="h-3 bg-slate-100 dark:bg-zinc-800 rounded-full overflow-hidden">
                    <div class="h-full bg-gradient-to-r from-red-600 to-red-700 rounded-full transition-all duration-700"
                        :style="{ width: `${cuotasActivas.length > 0 ? (cuotasPagadas / cuotasActivas.length) * 100 : 0}%` }"></div>
                </div>
                <div class="flex items-center justify-between mt-2">
                    <p class="text-xs text-slate-400">{{ cuotasActivas.length > 0 ? ((cuotasPagadas / cuotasActivas.length) * 100).toFixed(0) : 0 }}% completado</p>
                    <p class="text-xs text-slate-400">Total pagado: <strong class="text-slate-700 dark:text-zinc-200">${{ fmt(totalPagado) }}</strong></p>
                </div>
            </div>

            <!-- Acciones del Portal -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <a :href="route('portal.credito.estado-cuenta.pdf')" target="_blank"
                    class="flex items-center gap-3 bg-white dark:bg-zinc-900 border border-slate-100 dark:border-zinc-800 rounded-2xl p-5 shadow-sm hover:border-red-300 dark:hover:border-red-800 transition-all group">
                    <div class="w-10 h-10 rounded-xl bg-red-50 dark:bg-red-900/30 flex items-center justify-center group-hover:bg-red-100 transition-colors">
                        <Download size="20" class="text-red-700" />
                    </div>
                    <div>
                        <p class="font-bold text-slate-900 dark:text-white text-sm">Estado de Cuenta</p>
                        <p class="text-xs text-slate-500 dark:text-zinc-400">Descargar PDF con historial</p>
                    </div>
                </a>

                <button @click="calcularLiquidacion"
                    class="flex items-center gap-3 bg-white dark:bg-zinc-900 border border-slate-100 dark:border-zinc-800 rounded-2xl p-5 shadow-sm hover:border-blue-300 dark:hover:border-blue-800 transition-all group text-left">
                    <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center group-hover:bg-blue-100 transition-colors">
                        <Calculator size="20" class="text-blue-600" />
                    </div>
                    <div>
                        <p class="font-bold text-slate-900 dark:text-white text-sm">Liquidación Anticipada</p>
                        <p class="text-xs text-slate-500 dark:text-zinc-400">Calcula cuánto debes hoy</p>
                    </div>
                </button>
            </div>

            <!-- Modal Liquidación Anticipada -->
            <div v-if="mostrarLiquidacion" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
                <div class="bg-white dark:bg-zinc-900 rounded-3xl max-w-md w-full p-8 shadow-2xl">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="font-black text-slate-900 dark:text-white flex items-center gap-2">
                            <Calculator size="20" class="text-blue-600" /> Liquidación Anticipada
                        </h3>
                        <button @click="mostrarLiquidacion = false" class="text-slate-400 hover:text-slate-600 transition-colors">
                            <X size="20" />
                        </button>
                    </div>

                    <div v-if="cargandoLiquidacion" class="text-center py-8 text-slate-400">
                        <div class="animate-spin w-8 h-8 border-2 border-blue-600 border-t-transparent rounded-full mx-auto mb-3"></div>
                        <p class="text-sm">Calculando...</p>
                    </div>

                    <div v-else-if="liquidacion" class="space-y-4">
                        <p class="text-xs text-slate-500 dark:text-zinc-400">Monto calculado al día de hoy ({{ liquidacion.fecha_calculo }})</p>

                        <div class="space-y-2">
                            <div class="flex justify-between py-2 border-b border-slate-100 dark:border-zinc-800">
                                <span class="text-sm text-slate-600 dark:text-zinc-300">Capital pendiente</span>
                                <span class="font-bold">${{ fmt(liquidacion.capital_pendiente) }}</span>
                            </div>
                            <div class="flex justify-between py-2 border-b border-slate-100 dark:border-zinc-800">
                                <span class="text-sm text-slate-600 dark:text-zinc-300">Interés proyectado</span>
                                <span class="font-bold text-amber-600">${{ fmt(liquidacion.interes_proyectado) }}</span>
                            </div>
                            <div class="flex justify-between py-2 border-b border-slate-100 dark:border-zinc-800">
                                <span class="text-sm text-slate-600 dark:text-zinc-300">Mora acumulada</span>
                                <span class="font-bold text-red-600">${{ fmt(liquidacion.mora_acumulada) }}</span>
                            </div>
                        </div>

                        <div class="bg-slate-900 dark:bg-zinc-800 text-white rounded-2xl p-5 text-center">
                            <p class="text-xs text-slate-400 uppercase tracking-wider mb-1">Total a Liquidar Hoy</p>
                            <p class="text-3xl font-black">${{ fmt(liquidacion.total_liquidacion) }}</p>
                            <p class="text-xs text-slate-400 mt-2">Este cálculo puede variar si no liquidas el mismo día.</p>
                        </div>

                        <p class="text-xs text-center text-slate-400">Para liquidar, acude a nuestras oficinas o llama al <strong>999 941 2170</strong></p>
                    </div>
                </div>
            </div>

            <!-- Tabs -->
            <div class="bg-white dark:bg-zinc-900 rounded-3xl border border-slate-100 dark:border-zinc-800 overflow-hidden shadow-sm">
                <div class="p-4 border-b border-slate-100 dark:border-zinc-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <h2 class="font-black text-slate-900 dark:text-white flex items-center gap-2">
                        <FileText size="20" class="text-red-700" /> Movimientos
                    </h2>
                    <div class="flex bg-slate-100 dark:bg-zinc-800 p-1 rounded-2xl gap-1 w-full sm:w-auto">
                        <button @click="activeTab = 'tabla'"
                            :class="['flex-1 sm:flex-none px-5 py-2 rounded-xl text-sm font-bold transition-all', activeTab === 'tabla' ? 'bg-white dark:bg-zinc-700 shadow-sm text-red-700 dark:text-white' : 'text-slate-500 dark:text-zinc-400']">
                            Tabla de Amortización
                        </button>
                        <button @click="activeTab = 'pagos'"
                            :class="['flex-1 sm:flex-none px-5 py-2 rounded-xl text-sm font-bold transition-all', activeTab === 'pagos' ? 'bg-white dark:bg-zinc-700 shadow-sm text-red-700 dark:text-white' : 'text-slate-500 dark:text-zinc-400']">
                            Pagos Realizados
                        </button>
                    </div>
                </div>

                <!-- Tabla de amortización -->
                <div v-if="activeTab === 'tabla'" class="overflow-x-auto">
                    <table v-if="credito.tabla.length > 0" class="w-full text-left border-collapse text-sm">
                        <thead>
                            <tr class="bg-slate-50/80 dark:bg-zinc-800/80 text-slate-400 dark:text-zinc-500 text-[11px] uppercase tracking-widest font-bold">
                                <th class="px-5 py-4">No.</th>
                                <th class="px-5 py-4">Vencimiento</th>
                                <th class="px-5 py-4 text-right">Capital</th>
                                <th class="px-5 py-4 text-right">Interés Ord.</th>
                                <th class="px-5 py-4 text-right">Mora</th>
                                <th class="px-5 py-4 text-right">Total Cuota</th>
                                <th class="px-5 py-4 text-center">Estado</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50 dark:divide-zinc-800">
                            <tr v-for="item in credito.tabla" :key="item.numero_cuota"
                                :class="['hover:bg-slate-50/50 dark:hover:bg-zinc-800/50 transition-colors', estadoCuota(item).texto === 'VENCIDO' ? 'bg-red-50/30 dark:bg-red-900/5' : '']">
                                <td class="px-5 py-4 font-bold text-slate-500 dark:text-zinc-500">#{{ item.numero_cuota }}</td>
                                <td class="px-5 py-4 font-medium">{{ item.fecha_vencimiento }}</td>
                                <td class="px-5 py-4 text-right">${{ fmt(item.capital) }}</td>
                                <td class="px-5 py-4 text-right">${{ fmt(item.ordinario) }}</td>
                                <td class="px-5 py-4 text-right">
                                    <span v-if="Number(item.moratorio) > 0" class="text-red-600">${{ fmt(item.moratorio) }}</span>
                                    <Minus v-else size="14" class="mx-auto text-slate-300" />
                                </td>
                                <td class="px-5 py-4 text-right font-bold">
                                    ${{ fmt(Number(item.capital) + Number(item.ordinario) + Number(item.moratorio)) }}
                                </td>
                                <td class="px-5 py-4 text-center">
                                    <span :class="['px-2.5 py-1 rounded-full text-[10px] font-black', estadoCuota(item).clase]">
                                        {{ estadoCuota(item).texto }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <div v-else class="p-16 text-center space-y-3">
                        <CalendarDays size="40" class="mx-auto text-slate-300 dark:text-zinc-700" />
                        <p class="text-slate-500 dark:text-zinc-400 font-medium">No hay tabla de amortización disponible.</p>
                    </div>
                </div>

                <!-- Historial de pagos -->
                <div v-if="activeTab === 'pagos'" class="overflow-x-auto">
                    <table v-if="credito.pagos.length > 0" class="w-full text-left border-collapse text-sm">
                        <thead>
                            <tr class="bg-slate-50/80 dark:bg-zinc-800/80 text-slate-400 dark:text-zinc-500 text-[11px] uppercase tracking-widest font-bold">
                                <th class="px-5 py-4">Folio</th>
                                <th class="px-5 py-4">Fecha</th>
                                <th class="px-5 py-4 text-right">Monto</th>
                                <th class="px-5 py-4">Forma de Pago</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50 dark:divide-zinc-800">
                            <tr v-for="pago in credito.pagos" :key="pago.id"
                                class="hover:bg-slate-50/50 dark:hover:bg-zinc-800/50 transition-colors">
                                <td class="px-5 py-4">
                                    <span class="font-mono text-xs bg-slate-100 dark:bg-zinc-800 px-2 py-1 rounded-lg">
                                        {{ pago.folio ?? `#${pago.id}` }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 font-medium">{{ pago.fecha_pago }}</td>
                                <td class="px-5 py-4 text-right font-bold text-emerald-600">${{ fmt(pago.monto_recibido) }}</td>
                                <td class="px-5 py-4 text-slate-500 dark:text-zinc-400 capitalize">{{ pago.forma_pago ?? '—' }}</td>
                            </tr>
                        </tbody>
                    </table>
                    <div v-else class="p-16 text-center space-y-4">
                        <div class="w-20 h-20 rounded-3xl bg-slate-100 dark:bg-zinc-800 flex items-center justify-center mx-auto text-slate-400">
                            <CreditCard size="36" />
                        </div>
                        <div class="space-y-2 max-w-xs mx-auto">
                            <h4 class="font-black text-slate-900 dark:text-white">Sin pagos registrados</h4>
                            <p class="text-slate-500 dark:text-zinc-400 text-sm leading-relaxed">
                                Tu historial de pagos se actualizará automáticamente conforme realices tus aportaciones en ventanilla o transferencia.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Nota informativa -->
                <div class="border-t border-slate-100 dark:border-zinc-800 px-6 py-4 bg-slate-50 dark:bg-zinc-800/50">
                    <p class="text-xs text-slate-400 dark:text-zinc-500 text-center">
                        Para realizar tus pagos visita nuestras oficinas o comunícate al <strong class="text-slate-600 dark:text-zinc-300">999 941 2170</strong>.
                        Los pagos se reflejan en 24-48 horas hábiles.
                    </p>
                </div>
            </div>
        </div>
    </BeneficiarioLayout>
</template>
