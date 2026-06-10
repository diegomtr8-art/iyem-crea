<script setup>
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import {
    ArrowLeft, Receipt, TrendingUp,
    CheckCircle2, ChevronDown, ChevronUp,
    Banknote, CreditCard, Wallet, XCircle, AlertTriangle,
    Download
} from 'lucide-vue-next';

const props = defineProps({
    acreditado:  Object,
    credito:     Object,
    operaciones: Array,
    totales:     Object,
});

const fmt = (val) => new Intl.NumberFormat('es-MX', {
    style: 'currency', currency: 'MXN', minimumFractionDigits: 2
}).format(parseFloat(val) || 0);

const fmtDate = (d) => {
    if (!d) return '—';
    const [y, m, dia] = d.split('-');
    return `${dia}/${m}/${y}`;
};

const iconoFormaPago = (forma) => {
    const map = {
        Efectivo:      Banknote,
        Transferencia: TrendingUp,
        Cheque:        Receipt,
        Tarjeta:       CreditCard,
    };
    return map[forma] ?? Wallet;
};

const expandida = ref(null);
const toggleDetalle = (id) => {
    expandida.value = expandida.value === id ? null : id;
};

// Cancelación de pago
const modalCancelar = ref(false);
const pagoACancelar = ref(null);
const formCancelar = useForm({ motivo_cancelacion: '' });

const abrirCancelar = (op) => {
    pagoACancelar.value = op;
    formCancelar.reset();
    modalCancelar.value = true;
};

const confirmarCancelar = () => {
    formCancelar.post(route('pagos.cancelar', pagoACancelar.value.id), {
        onSuccess: () => { modalCancelar.value = false; },
    });
};

// Exportar Excel
const exportarExcel = () => {
    window.location.href = route('operaciones.export', acreditado.value?.id ?? props.acreditado.id);
};
</script>

<template>
    <Head :title="'Movimientos — ' + acreditado.nombre_completo" />

    <AppLayout>
        <div class="max-w-5xl mx-auto py-8 px-4">

            <!-- ENCABEZADO -->
            <div class="flex items-center gap-4 mb-8">
                <button
                    @click="() => router.visit(route('acreditados.show', acreditado.id))"
                    class="p-3 rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 transition-colors shadow-sm"
                >
                    <ArrowLeft :size="20" class="text-slate-600 dark:text-slate-300" />
                </button>
                <div class="flex-1">
                    <h1 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-tight">
                        Movimientos
                    </h1>
                    <p class="text-slate-500 text-sm font-medium">
                        {{ acreditado.nombre_completo }} — Contrato: {{ credito?.clave_contrato ?? '—' }}
                    </p>
                </div>
                <a
                    :href="route('operaciones.export', acreditado.id)"
                    class="flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-bold rounded-xl transition-colors shadow-sm"
                >
                    <Download :size="15" />
                    Exportar Excel
                </a>
            </div>

            <!-- TARJETAS DE TOTALES -->
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 shadow-sm text-center">
                    <p class="text-[10px] font-black text-slate-400 uppercase mb-1">Pagos</p>
                    <p class="text-2xl font-black text-slate-900 dark:text-white">{{ totales.num_pagos }}</p>
                </div>
                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 shadow-sm text-center">
                    <p class="text-[10px] font-black text-slate-400 uppercase mb-1">Total Recibido</p>
                    <p class="text-lg font-black text-slate-900 dark:text-white">{{ fmt(totales.monto_total) }}</p>
                </div>
                <div class="bg-orange-50 dark:bg-orange-900/20 rounded-2xl border border-orange-200 dark:border-orange-800 p-4 shadow-sm text-center">
                    <p class="text-[10px] font-black text-orange-500 uppercase mb-1">Mora</p>
                    <p class="text-lg font-black text-orange-600">{{ fmt(totales.total_mora) }}</p>
                </div>
                <div class="bg-red-50 dark:bg-red-900/20 rounded-2xl border border-red-200 dark:border-red-800 p-4 shadow-sm text-center">
                    <p class="text-[10px] font-black text-red-500 uppercase mb-1">Ordinario</p>
                    <p class="text-lg font-black text-red-600">{{ fmt(totales.total_ordinario) }}</p>
                </div>
                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-2xl border border-blue-200 dark:border-blue-800 p-4 shadow-sm text-center">
                    <p class="text-[10px] font-black text-blue-500 uppercase mb-1">Capital</p>
                    <p class="text-lg font-black text-blue-600">{{ fmt(totales.total_capital) }}</p>
                </div>
            </div>

            <!-- LISTA DE OPERACIONES -->
            <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">

                <div class="p-5 border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/50 flex items-center gap-2">
                    <Receipt :size="16" class="text-red-600" />
                    <h2 class="font-black text-slate-800 dark:text-white text-xs uppercase tracking-widest">
                        Pagos Registrados
                    </h2>
                </div>

                <div v-if="!operaciones.length" class="py-16 text-center">
                    <Receipt :size="40" class="mx-auto text-slate-200 mb-3" />
                    <p class="text-slate-400 text-sm font-medium">Aún no hay pagos registrados.</p>
                </div>

                <div v-else class="divide-y divide-slate-100 dark:divide-slate-800">
                    <div v-for="op in operaciones" :key="op.id" class="group">

                        <!-- Fila principal -->
                        <div
                            class="flex items-center gap-4 px-5 py-4 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors"
                            :class="op.cancelado ? 'opacity-50' : ''"
                            @click="toggleDetalle(op.id)"
                        >
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0"
                                :class="op.cancelado ? 'bg-red-50 dark:bg-red-900/20' : 'bg-slate-100 dark:bg-slate-800'">
                                <XCircle v-if="op.cancelado" :size="18" class="text-red-400" />
                                <component v-else :is="iconoFormaPago(op.forma_pago)" :size="18" class="text-slate-500 dark:text-slate-400" />
                            </div>

                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="font-black text-slate-900 dark:text-white text-sm" :class="op.cancelado ? 'line-through' : ''">
                                        {{ op.folio }}
                                    </span>
                                    <span v-if="op.cancelado" class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-red-100 dark:bg-red-900/30 text-red-500 uppercase">
                                        Cancelado
                                    </span>
                                    <span v-else class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-500 uppercase">
                                        {{ op.forma_pago }}
                                    </span>
                                    <span v-if="op.referencia && !op.cancelado" class="text-[10px] text-slate-400 font-mono">
                                        Ref: {{ op.referencia }}
                                    </span>
                                </div>
                                <div class="text-xs text-slate-400 mt-0.5 flex flex-wrap items-center gap-3">
                                    <span>{{ fmtDate(op.fecha_pago) }}</span>
                                    <span>Por: {{ op.registrado_por }}</span>
                                    <span v-if="op.cancelado" class="text-red-400">
                                        Cancelado {{ op.cancelado_at }} por {{ op.cancelado_por }}
                                    </span>
                                </div>
                            </div>

                            <div class="text-right shrink-0">
                                <p class="font-black text-base" :class="op.cancelado ? 'text-slate-400 line-through' : 'text-slate-900 dark:text-white'">
                                    {{ fmt(op.monto_recibido) }}
                                </p>
                                <p class="text-[10px] text-slate-400">{{ op.cuotas_cubiertas?.length ?? 0 }} cuota(s)</p>
                            </div>

                            <component
                                :is="expandida === op.id ? ChevronUp : ChevronDown"
                                :size="16"
                                class="text-slate-400 shrink-0"
                            />
                        </div>

                        <!-- DETALLE EXPANDIBLE -->
                        <Transition
                            enter-active-class="transition duration-200 ease-out"
                            enter-from-class="opacity-0 -translate-y-2"
                            enter-to-class="opacity-100 translate-y-0"
                            leave-active-class="transition duration-150 ease-in"
                            leave-from-class="opacity-100 translate-y-0"
                            leave-to-class="opacity-0 -translate-y-2"
                        >
                            <div v-if="expandida === op.id" class="px-5 pb-5 bg-slate-50/60 dark:bg-slate-800/30">

                                <!-- Motivo cancelación -->
                                <div v-if="op.cancelado" class="mt-3 px-4 py-3 bg-red-50 dark:bg-red-900/20 rounded-xl border border-red-200 dark:border-red-800 text-xs text-red-600">
                                    <span class="font-black uppercase">Motivo:</span> {{ op.motivo_cancelacion }}
                                </div>

                                <template v-else>
                                    <!-- Distribución del pago -->
                                    <div class="grid grid-cols-3 gap-3 mb-4 mt-3">
                                        <div class="bg-white dark:bg-slate-900 rounded-2xl p-3 border border-orange-200 dark:border-orange-800 text-center">
                                            <p class="text-[10px] font-black text-orange-500 uppercase mb-1">Mora</p>
                                            <p class="font-black text-orange-600">{{ fmt(op.aplicado_mora) }}</p>
                                        </div>
                                        <div class="bg-white dark:bg-slate-900 rounded-2xl p-3 border border-red-200 dark:border-red-800 text-center">
                                            <p class="text-[10px] font-black text-red-500 uppercase mb-1">Ordinario</p>
                                            <p class="font-black text-red-600">{{ fmt(op.aplicado_ordinario) }}</p>
                                        </div>
                                        <div class="bg-white dark:bg-slate-900 rounded-2xl p-3 border border-blue-200 dark:border-blue-800 text-center">
                                            <p class="text-[10px] font-black text-blue-500 uppercase mb-1">Capital</p>
                                            <p class="font-black text-blue-600">{{ fmt(op.aplicado_capital) }}</p>
                                        </div>
                                    </div>

                                    <!-- Detalle por cuota -->
                                    <div v-if="op.cuotas_cubiertas?.length" class="space-y-2">
                                        <p class="text-[10px] font-black text-slate-400 uppercase mb-2">Detalle por cuota</p>
                                        <div
                                            v-for="c in op.cuotas_cubiertas"
                                            :key="c.cuota"
                                            class="flex items-center justify-between bg-white dark:bg-slate-900 rounded-xl px-4 py-2.5 border border-slate-200 dark:border-slate-700 text-xs"
                                        >
                                            <div class="flex items-center gap-3">
                                                <CheckCircle2 :size="14" class="text-green-500" />
                                                <span class="font-black text-slate-700 dark:text-slate-200">
                                                    Cuota {{ c.cuota }}
                                                </span>
                                            </div>
                                            <div class="flex gap-4 text-[10px] text-slate-400">
                                                <span v-if="c.aplicado_mora > 0" class="text-orange-500 font-bold">
                                                    Mora: {{ fmt(c.aplicado_mora) }}
                                                </span>
                                                <span v-if="c.aplicado_ordinario > 0" class="text-red-500 font-bold">
                                                    Ord: {{ fmt(c.aplicado_ordinario) }}
                                                </span>
                                                <span v-if="c.aplicado_capital > 0" class="text-blue-500 font-bold">
                                                    Cap: {{ fmt(c.aplicado_capital) }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Observaciones -->
                                    <div v-if="op.observaciones" class="mt-3 px-4 py-3 bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-700 text-xs text-slate-500 italic">
                                        "{{ op.observaciones }}"
                                    </div>

                                    <!-- Botón cancelar -->
                                    <div class="mt-4 flex justify-end">
                                        <button
                                            @click.stop="abrirCancelar(op)"
                                            class="flex items-center gap-2 px-4 py-2 text-xs font-bold text-red-600 border border-red-200 rounded-xl hover:bg-red-50 transition-colors"
                                        >
                                            <XCircle :size="13" />
                                            Cancelar pago
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </Transition>
                    </div>
                </div>
            </div>

        </div>

        <!-- MODAL CANCELAR PAGO -->
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="modalCancelar" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
                <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 w-full max-w-md shadow-2xl border border-slate-200 dark:border-slate-700">
                    <div class="flex items-center gap-3 mb-5">
                        <div class="w-10 h-10 rounded-xl bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                            <AlertTriangle :size="20" class="text-red-600" />
                        </div>
                        <div>
                            <h3 class="font-black text-slate-900 dark:text-white">Cancelar Pago</h3>
                            <p class="text-xs text-slate-400">{{ pagoACancelar?.folio }}</p>
                        </div>
                    </div>

                    <p class="text-sm text-slate-600 dark:text-slate-400 mb-4">
                        Esta acción revertirá los abonos aplicados a la tabla de amortización.
                        Escribe el motivo de cancelación.
                    </p>

                    <textarea
                        v-model="formCancelar.motivo_cancelacion"
                        rows="3"
                        placeholder="Motivo de cancelación..."
                        class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-red-500 resize-none"
                    />
                    <p v-if="formCancelar.errors.motivo_cancelacion" class="text-xs text-red-500 mt-1">
                        {{ formCancelar.errors.motivo_cancelacion }}
                    </p>

                    <div class="flex gap-3 mt-5">
                        <button
                            @click="modalCancelar = false"
                            class="flex-1 px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 text-sm font-bold text-slate-600 hover:bg-slate-50 transition-colors"
                        >
                            Regresar
                        </button>
                        <button
                            @click="confirmarCancelar"
                            :disabled="formCancelar.processing || !formCancelar.motivo_cancelacion"
                            class="flex-1 px-4 py-3 rounded-xl bg-red-600 text-white text-sm font-bold hover:bg-red-700 disabled:opacity-50 transition-colors"
                        >
                            Confirmar Cancelación
                        </button>
                    </div>
                </div>
            </div>
        </Transition>

    </AppLayout>
</template>
