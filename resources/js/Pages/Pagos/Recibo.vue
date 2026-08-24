<script setup>
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { FileText, ArrowLeft, CheckCircle2, Printer } from 'lucide-vue-next';

const props = defineProps({ pago: Object });

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
    <Head :title="'Recibo ' + pago.folio" />
    <AppLayout>
        <div class="max-w-2xl mx-auto py-8 px-4">

            <!-- Encabezado -->
            <div class="flex items-center gap-4 mb-6">
                <button
                    @click="history.back()"
                    class="p-3 rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 transition-colors shadow-sm"
                >
                    <ArrowLeft :size="18" class="text-slate-600 dark:text-slate-300" />
                </button>
                <div class="flex-1">
                    <div class="flex items-center gap-2">
                        <CheckCircle2 :size="20" class="text-green-500" />
                        <h1 class="text-xl font-black text-slate-900 dark:text-white">Pago Registrado</h1>
                    </div>
                    <p class="text-slate-500 text-sm">{{ pago.folio }}</p>
                </div>
                <a
                    :href="route('pagos.recibo.pdf', pago.id)"
                    target="_blank"
                    class="flex items-center gap-2 px-4 py-2.5 bg-slate-900 dark:bg-white text-white dark:text-slate-900 font-bold rounded-xl text-sm hover:bg-slate-700 transition-colors shadow-sm"
                >
                    <FileText :size="15" />
                    Descargar PDF
                </a>
            </div>

            <!-- Tarjeta recibo -->
            <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-lg overflow-hidden">

                <!-- Header institucional -->
                <div class="bg-slate-900 dark:bg-black px-8 py-6 text-white">
                    <p class="text-[10px] font-black tracking-[0.3em] text-slate-400 uppercase mb-1">Instituto Yucateco de Emprendedores</p>
                    <p class="text-sm text-slate-300">Programa CREA — Recibo de Pago</p>
                    <div class="flex items-end justify-between mt-4">
                        <div>
                            <p class="text-[10px] text-slate-400 uppercase">Folio</p>
                            <p class="text-2xl font-black text-red-400 tracking-tight">{{ pago.folio }}</p>
                        </div>
                        <p class="text-sm text-slate-400">{{ fmtDate(pago.fecha_pago) }}</p>
                    </div>
                </div>

                <div class="p-6 space-y-5">

                    <!-- Acreditado -->
                    <div>
                        <p class="text-[10px] font-black text-slate-400 uppercase mb-2">Acreditado</p>
                        <p class="font-black text-slate-900 dark:text-white text-base">{{ pago.acreditado?.nombre_completo }}</p>
                        <p class="text-sm text-slate-500">Contrato: {{ pago.credito?.clave_contrato }} · {{ pago.acreditado?.municipio }}</p>
                    </div>

                    <!-- Monto -->
                    <div class="bg-slate-50 dark:bg-slate-800 rounded-2xl p-4 text-center">
                        <p class="text-[10px] font-black text-slate-400 uppercase mb-1">Monto Recibido</p>
                        <p class="text-4xl font-black text-slate-900 dark:text-white">{{ fmt(pago.monto_recibido) }}</p>
                        <p class="text-sm text-slate-500 mt-1">{{ pago.forma_pago }}
                            <span v-if="pago.referencia"> · Ref: {{ pago.referencia }}</span>
                        </p>
                    </div>

                    <!-- Distribución -->
                    <div class="grid grid-cols-3 gap-3">
                        <div class="bg-orange-50 dark:bg-orange-900/20 rounded-2xl p-3 border border-orange-200 dark:border-orange-800 text-center">
                            <p class="text-[10px] font-black text-orange-500 uppercase mb-1">Mora</p>
                            <p class="font-black text-orange-600">{{ fmt(pago.aplicado_mora) }}</p>
                        </div>
                        <div class="bg-red-50 dark:bg-red-900/20 rounded-2xl p-3 border border-red-200 dark:border-red-800 text-center">
                            <p class="text-[10px] font-black text-red-500 uppercase mb-1">Ordinario</p>
                            <p class="font-black text-red-600">{{ fmt(pago.aplicado_ordinario) }}</p>
                        </div>
                        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-2xl p-3 border border-blue-200 dark:border-blue-800 text-center">
                            <p class="text-[10px] font-black text-blue-500 uppercase mb-1">Capital</p>
                            <p class="font-black text-blue-600">{{ fmt(pago.aplicado_capital) }}</p>
                        </div>
                    </div>

                    <!-- Cuotas -->
                    <div v-if="pago.cuotas_cubiertas?.length">
                        <p class="text-[10px] font-black text-slate-400 uppercase mb-2">Cuotas Afectadas</p>
                        <div class="space-y-1.5">
                            <div
                                v-for="c in pago.cuotas_cubiertas"
                                :key="c.cuota"
                                class="flex items-center justify-between bg-slate-50 dark:bg-slate-800 rounded-xl px-4 py-2.5 text-xs"
                            >
                                <div class="flex items-center gap-2">
                                    <CheckCircle2 :size="13" class="text-green-500" />
                                    <span class="font-black text-slate-700 dark:text-slate-200">Cuota {{ c.cuota }}</span>
                                </div>
                                <div class="flex gap-3 text-[10px]">
                                    <span v-if="(c.mor ?? c.aplicado_mora) > 0" class="text-orange-500 font-bold">Mora {{ fmt(c.mor ?? c.aplicado_mora) }}</span>
                                    <span v-if="(c.int ?? c.aplicado_ordinario) > 0" class="text-red-500 font-bold">Ord {{ fmt(c.int ?? c.aplicado_ordinario) }}</span>
                                    <span v-if="(c.cap ?? c.aplicado_capital) > 0" class="text-blue-500 font-bold">Cap {{ fmt(c.cap ?? c.aplicado_capital) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Registrado por -->
                    <div class="flex items-center justify-between pt-4 border-t border-slate-100 dark:border-slate-800 text-xs text-slate-400">
                        <span>Registrado por: <strong>{{ pago.registrado_por }}</strong></span>
                        <span>{{ pago.created_at }}</span>
                    </div>
                </div>
            </div>

            <!-- Acciones -->
            <div class="mt-4 flex gap-3">
                <button
                    v-if="pago.acreditado?.id"
                    @click="router.visit(route('operaciones.index', pago.acreditado.id))"
                    class="flex-1 py-3 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl text-sm font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-50 transition-colors"
                >
                    Ver todos los movimientos
                </button>
            </div>

        </div>
    </AppLayout>
</template>
