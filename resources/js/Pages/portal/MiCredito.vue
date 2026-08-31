<script setup lang="ts">
import BeneficiarioLayout from '@/layouts/BeneficiarioLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import {
    BadgeCheck, CalendarDays, CreditCard, DollarSign, TrendingUp,
    FileText, Clock, CheckCircle2, AlertCircle, Minus,
    Download, Calculator, X, ClipboardCheck, Plus, Trash2, ExternalLink
} from 'lucide-vue-next';
import { router, useForm } from '@inertiajs/vue3';

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
        proxima_cuota: null | {
            numero: number;
            fecha_vencimiento: string;
            capital: number;
            interes: number;
            mora: number;
            total: number;
            dias_mora: number;
            vencida: boolean;
        };
    };
    datos_pago: {
        cie_bbva: string;
        cie_descripcion: string;
        clabe: string;
        banco: string;
        beneficiario: string;
        rfc: string;
        concepto: string;
        caja_horario: string;
        correo: string;
        whatsapp: string;
    };
    comprobacion: null | {
        id: number;
        estatus: 'Pendiente' | 'En_Revision' | 'Aprobada' | 'Rechazada';
        fecha_desembolso: string;
        fecha_limite: string;
        dias_restantes: number;
        semaforo: 'verde' | 'amarillo' | 'rojo' | 'vencido';
        documentos: Array<{ id: number; tipo: string; descripcion?: string; monto?: number; proveedor?: string; nombre_original: string; url: string }>;
        observaciones_operativo?: string;
    };
}>();

const activeTab = ref<'tabla' | 'pagos'>('tabla');

const estadoCuota = (item: typeof props.credito.tabla[0]) => {
    if (item.estado === 'Gracia')   return { texto: 'GRACIA',    clase: 'bg-slate-100 text-slate-400 dark:bg-zinc-800 dark:text-zinc-500' };
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

// Comprobación de uso del crédito
const semaforoComprobacion: Record<string, { bg: string; text: string; border: string; label: string }> = {
    verde:    { bg: 'bg-emerald-50 dark:bg-emerald-950/30', text: 'text-emerald-700 dark:text-emerald-400', border: 'border-emerald-500', label: 'En plazo' },
    amarillo: { bg: 'bg-amber-50 dark:bg-amber-950/30',     text: 'text-amber-700 dark:text-amber-400',     border: 'border-amber-500', label: 'Por vencer' },
    rojo:     { bg: 'bg-orange-50 dark:bg-orange-950/30',   text: 'text-orange-700 dark:text-orange-400',   border: 'border-orange-500', label: 'Urgente' },
    vencido:  { bg: 'bg-red-50 dark:bg-red-950/30',         text: 'text-red-700 dark:text-red-400',         border: 'border-red-500', label: 'Plazo vencido' },
};

const tipoComprobanteOpciones = [
    { value: 'factura', label: 'Factura' },
    { value: 'nota_venta', label: 'Nota de venta' },
    { value: 'foto_bien', label: 'Foto del bien' },
    { value: 'otro', label: 'Otro' },
];

const formComprobacion = useForm<{
    observaciones_acreditado: string;
    comprobantes: Array<{ archivo: File | null; tipo: string; descripcion: string; monto: string; proveedor: string }>;
}>({
    observaciones_acreditado: '',
    comprobantes: [{ archivo: null, tipo: 'factura', descripcion: '', monto: '', proveedor: '' }],
});

const totalComprobado = computed(() =>
    formComprobacion.comprobantes.reduce((s, c) => s + (Number(c.monto) || 0), 0)
);

const agregarComprobante = () => {
    formComprobacion.comprobantes.push({ archivo: null, tipo: 'factura', descripcion: '', monto: '', proveedor: '' });
};
const quitarComprobante = (i: number) => {
    if (formComprobacion.comprobantes.length > 1) formComprobacion.comprobantes.splice(i, 1);
};
const onArchivoChange = (i: number, e: Event) => {
    const input = e.target as HTMLInputElement;
    formComprobacion.comprobantes[i].archivo = input.files?.[0] ?? null;
};

const enviarComprobacion = () => {
    if (!props.comprobacion) return;
    formComprobacion.post(route('portal.comprobacion.enviar', props.comprobacion.id), {
        forceFormData: true,
    });
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

            <!-- Próxima cuota -->
            <div v-if="credito.proxima_cuota" :class="[
                'rounded-2xl p-5 border-l-4',
                credito.proxima_cuota.vencida
                    ? 'bg-red-50 border-red-500 dark:bg-red-950/30'
                    : 'bg-emerald-50 border-emerald-500 dark:bg-emerald-950/30'
            ]">
                <h3 class="font-black text-lg mb-3 flex items-center gap-2 text-slate-900 dark:text-white">
                    <AlertCircle v-if="credito.proxima_cuota.vencida" size="20" class="text-red-600" />
                    <CalendarDays v-else size="20" class="text-emerald-600" />
                    {{ credito.proxima_cuota.vencida ? 'Cuota vencida' : 'Próxima cuota' }} #{{ credito.proxima_cuota.numero }}
                </h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div>
                        <p class="text-xs text-slate-500 dark:text-zinc-400">Vencimiento</p>
                        <p class="font-bold text-slate-900 dark:text-white">{{ credito.proxima_cuota.fecha_vencimiento }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500 dark:text-zinc-400">Capital</p>
                        <p class="font-bold text-slate-900 dark:text-white">${{ fmt(credito.proxima_cuota.capital) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500 dark:text-zinc-400">Interés</p>
                        <p class="font-bold text-slate-900 dark:text-white">${{ fmt(credito.proxima_cuota.interes) }}</p>
                    </div>
                    <div v-if="credito.proxima_cuota.mora > 0">
                        <p class="text-xs text-red-500">Mora acumulada</p>
                        <p class="font-bold text-red-600">${{ fmt(credito.proxima_cuota.mora) }}</p>
                    </div>
                </div>
                <div class="mt-4 pt-3 border-t border-slate-200/60 dark:border-zinc-700/60 flex flex-wrap items-center justify-between gap-2">
                    <span class="text-xs text-slate-500 dark:text-zinc-400">
                        {{ credito.proxima_cuota.dias_mora > 0 ? `${credito.proxima_cuota.dias_mora} días de atraso` : '' }}
                    </span>
                    <span class="text-xl font-black text-slate-900 dark:text-white">
                        Total a pagar: ${{ fmt(credito.proxima_cuota.total) }}
                    </span>
                </div>
                <p v-if="credito.proxima_cuota.vencida" class="text-xs text-red-500 mt-2">
                    Contáctanos para regularizar tu crédito: 999 941 2170
                </p>
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
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
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

            <!-- Comprobación de uso del crédito -->
            <div v-if="comprobacion" class="bg-white dark:bg-zinc-900 rounded-3xl border border-slate-100 dark:border-zinc-800 p-6 shadow-sm space-y-5">
                <h2 class="font-black text-slate-900 dark:text-white flex items-center gap-2">
                    <ClipboardCheck size="20" class="text-red-700" /> Comprobación de Uso del Crédito
                </h2>

                <div :class="['rounded-2xl p-4 border-l-4 flex flex-wrap items-center justify-between gap-3', semaforoComprobacion[comprobacion.semaforo].bg, semaforoComprobacion[comprobacion.semaforo].border]">
                    <div>
                        <p :class="['font-bold', semaforoComprobacion[comprobacion.semaforo].text]">
                            <template v-if="comprobacion.semaforo === 'vencido'">Tu plazo de comprobación ha vencido</template>
                            <template v-else-if="comprobacion.semaforo === 'rojo'">Te quedan {{ comprobacion.dias_restantes }} días para comprobar el uso de tu crédito</template>
                            <template v-else>{{ semaforoComprobacion[comprobacion.semaforo].label }} — {{ comprobacion.dias_restantes }} días restantes</template>
                        </p>
                        <p class="text-xs text-slate-500 dark:text-zinc-400 mt-0.5">
                            Desembolso: {{ comprobacion.fecha_desembolso }} · Fecha límite: {{ comprobacion.fecha_limite }} · Estatus: {{ comprobacion.estatus.replace('_', ' ') }}
                        </p>
                    </div>
                </div>

                <div v-if="comprobacion.observaciones_operativo" class="rounded-xl bg-amber-50 dark:bg-amber-900/20 p-4 text-sm text-amber-800 dark:text-amber-300">
                    <strong>Observaciones de IYEM:</strong> {{ comprobacion.observaciones_operativo }}
                </div>

                <!-- Documentos ya enviados -->
                <div v-if="comprobacion.documentos.length > 0" class="space-y-2">
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Comprobantes enviados</p>
                    <div v-for="d in comprobacion.documentos" :key="d.id" class="flex items-center justify-between text-sm bg-slate-50 dark:bg-zinc-800/50 rounded-xl px-4 py-2.5">
                        <span>{{ d.descripcion || d.nombre_original }} <span v-if="d.monto" class="text-slate-400">— ${{ fmt(d.monto) }}</span></span>
                        <a :href="d.url" target="_blank" class="text-red-700 dark:text-red-400 hover:underline inline-flex items-center gap-1 text-xs font-bold"><ExternalLink size="12" /> Ver</a>
                    </div>
                    <p class="text-sm font-bold text-right">Total comprobado: ${{ fmt(comprobacion.documentos.reduce((s, d) => s + (Number(d.monto) || 0), 0)) }}</p>
                </div>

                <!-- Formulario de envío (solo si Pendiente o Rechazada) -->
                <form v-if="['Pendiente', 'Rechazada'].includes(comprobacion.estatus)" @submit.prevent="enviarComprobacion" class="space-y-4">
                    <div v-for="(c, i) in formComprobacion.comprobantes" :key="i"
                        class="grid grid-cols-1 sm:grid-cols-5 gap-3 items-start bg-slate-50 dark:bg-zinc-800/50 rounded-2xl p-4">
                        <div class="sm:col-span-1">
                            <label class="text-xs font-semibold text-slate-500">Tipo</label>
                            <select v-model="c.tipo" class="w-full mt-1 px-3 py-2 rounded-lg border border-slate-200 dark:border-zinc-700 dark:bg-zinc-900 text-sm">
                                <option v-for="op in tipoComprobanteOpciones" :key="op.value" :value="op.value">{{ op.label }}</option>
                            </select>
                        </div>
                        <div class="sm:col-span-1">
                            <label class="text-xs font-semibold text-slate-500">Archivo</label>
                            <input type="file" accept=".pdf,.jpg,.jpeg,.png" @change="onArchivoChange(i, $event)"
                                class="w-full mt-1 text-xs file:mr-2 file:py-1.5 file:px-2 file:rounded-lg file:border-0 file:bg-red-50 file:text-red-700 dark:file:bg-red-900/30 dark:file:text-red-400" />
                        </div>
                        <div class="sm:col-span-1">
                            <label class="text-xs font-semibold text-slate-500">Descripción</label>
                            <input v-model="c.descripcion" type="text" placeholder="Ej. Compra de máquina"
                                class="w-full mt-1 px-3 py-2 rounded-lg border border-slate-200 dark:border-zinc-700 dark:bg-zinc-900 text-sm" />
                        </div>
                        <div class="sm:col-span-1">
                            <label class="text-xs font-semibold text-slate-500">Monto</label>
                            <input v-model="c.monto" type="number" step="0.01" min="0" placeholder="0.00"
                                class="w-full mt-1 px-3 py-2 rounded-lg border border-slate-200 dark:border-zinc-700 dark:bg-zinc-900 text-sm" />
                        </div>
                        <div class="sm:col-span-1 flex items-end gap-2">
                            <div class="flex-1">
                                <label class="text-xs font-semibold text-slate-500">Proveedor</label>
                                <input v-model="c.proveedor" type="text" placeholder="Nombre del proveedor"
                                    class="w-full mt-1 px-3 py-2 rounded-lg border border-slate-200 dark:border-zinc-700 dark:bg-zinc-900 text-sm" />
                            </div>
                            <button type="button" @click="quitarComprobante(i)" v-if="formComprobacion.comprobantes.length > 1"
                                class="mt-1 p-2 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg">
                                <Trash2 size="16" />
                            </button>
                        </div>
                    </div>

                    <button type="button" @click="agregarComprobante"
                        class="inline-flex items-center gap-2 text-sm font-bold text-red-700 dark:text-red-400 hover:underline">
                        <Plus size="15" /> Agregar otro comprobante
                    </button>

                    <div>
                        <label class="text-xs font-semibold text-slate-500">Observaciones (opcional)</label>
                        <textarea v-model="formComprobacion.observaciones_acreditado" rows="2"
                            class="w-full mt-1 px-3 py-2 rounded-lg border border-slate-200 dark:border-zinc-700 dark:bg-zinc-900 text-sm"></textarea>
                    </div>

                    <div class="flex items-center justify-between pt-2 border-t border-slate-100 dark:border-zinc-800">
                        <p class="text-sm font-bold">Total capturado: ${{ fmt(totalComprobado) }}</p>
                        <button type="submit" :disabled="formComprobacion.processing"
                            class="px-5 py-2.5 bg-red-700 hover:bg-red-800 text-white rounded-xl text-sm font-bold shadow-sm disabled:opacity-50">
                            Enviar comprobación
                        </button>
                    </div>
                </form>
            </div>

            <!-- ¿Cómo pagar mi crédito? -->
            <div class="bg-white dark:bg-zinc-900 rounded-3xl border border-slate-100 dark:border-zinc-800 p-6 shadow-sm space-y-4">
                <h2 class="font-black text-slate-900 dark:text-white flex items-center gap-2">
                    <DollarSign size="20" class="text-red-700" /> ¿Cómo pagar mi crédito?
                </h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div class="rounded-2xl bg-slate-50 dark:bg-zinc-800/50 p-4 space-y-2">
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Pago por transferencia</p>
                        <p class="text-slate-600 dark:text-zinc-300">Banco: <strong class="text-slate-900 dark:text-white">{{ datos_pago.banco }}</strong></p>
                        <p class="text-slate-600 dark:text-zinc-300">CIE BBVA: <strong class="font-mono text-slate-900 dark:text-white">{{ datos_pago.cie_bbva }}</strong> ({{ datos_pago.cie_descripcion }})</p>
                        <p class="text-slate-600 dark:text-zinc-300">CLABE: <strong class="font-mono text-sm sm:text-base break-all text-slate-900 dark:text-white">{{ datos_pago.clabe }}</strong></p>
                        <p class="text-slate-600 dark:text-zinc-300">Beneficiario: <strong class="text-slate-900 dark:text-white">{{ datos_pago.beneficiario }}</strong></p>
                        <p class="text-slate-600 dark:text-zinc-300">RFC: <strong class="font-mono text-slate-900 dark:text-white">{{ datos_pago.rfc }}</strong></p>
                        <p class="text-slate-600 dark:text-zinc-300">Concepto: <strong class="text-slate-900 dark:text-white">{{ datos_pago.concepto }}</strong></p>
                    </div>
                    <div class="rounded-2xl bg-slate-50 dark:bg-zinc-800/50 p-4 space-y-2">
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Pago en oficinas IYEM</p>
                        <p class="text-slate-600 dark:text-zinc-300">Horario de caja: <strong class="text-slate-900 dark:text-white">{{ datos_pago.caja_horario }}</strong></p>
                        <p class="text-slate-600 dark:text-zinc-300">Correo: <strong class="text-slate-900 dark:text-white">{{ datos_pago.correo }}</strong></p>
                        <p class="text-slate-600 dark:text-zinc-300">WhatsApp: <strong class="text-slate-900 dark:text-white">{{ datos_pago.whatsapp }}</strong></p>
                    </div>
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
            <Teleport to="body">
                <div 
                    v-if="mostrarLiquidacion" 
                    class="fixed inset-0 z-[9999] bg-black/80 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto"
                >
                    <div class="relative w-full max-w-md my-auto rounded-3xl bg-zinc-900 border border-zinc-800 p-6 shadow-2xl overflow-hidden flex flex-col max-h-[85vh]">
                        
                        <!-- Header Fijo -->
                        <div class="flex items-center justify-between pb-4 mb-4 border-b border-zinc-800 shrink-0">
                            <h3 class="font-black text-white flex items-center gap-2 text-base sm:text-lg">
                                <Calculator size="20" class="text-blue-500" /> Liquidación Anticipada
                            </h3>
                            <button 
                                @click="mostrarLiquidacion = false" 
                                class="text-zinc-400 hover:text-white transition-colors p-1"
                            >
                                <X size="20" />
                            </button>
                        </div>

                        <!-- Cuerpos de datos con Scroll Interno -->
                        <div class="overflow-y-auto pr-1 space-y-4">
                            
                            <div v-if="cargandoLiquidacion" class="text-center py-8 text-zinc-400">
                                <div class="animate-spin w-8 h-8 border-2 border-blue-500 border-t-transparent rounded-full mx-auto mb-3"></div>
                                <p class="text-sm">Calculando...</p>
                            </div>

                            <div v-else-if="liquidacion" class="space-y-4">
                                <p class="text-xs text-zinc-400">Monto calculado al día de hoy ({{ liquidacion.fecha_calculo }})</p>

                                <div class="space-y-1">
                                    <div class="flex justify-between py-2 border-b border-zinc-800">
                                        <span class="text-sm text-zinc-300">Capital pendiente</span>
                                        <span class="font-bold text-white">${{ fmt(liquidacion.capital_pendiente) }}</span>
                                    </div>
                                    <div class="flex justify-between py-2 border-b border-zinc-800">
                                        <span class="text-sm text-zinc-300">Interés proyectado</span>
                                        <span class="font-bold text-amber-500">${{ fmt(liquidacion.interes_proyectado) }}</span>
                                    </div>
                                    <div class="flex justify-between py-2 border-b border-zinc-800">
                                        <span class="text-sm text-zinc-300">Mora acumulada</span>
                                        <span class="font-bold text-red-500">${{ fmt(liquidacion.mora_acumulada) }}</span>
                                    </div>
                                </div>

                                <div class="bg-zinc-800/80 border border-zinc-700/50 text-white rounded-xl p-4 text-center">
                                    <p class="text-[10px] text-zinc-400 uppercase tracking-wider mb-1">Total a Liquidar Hoy</p>
                                    <p class="text-2xl sm:text-3xl font-black text-white">${{ fmt(liquidacion.total_liquidacion) }}</p>
                                    <p class="text-[11px] text-zinc-400 mt-1">Este cálculo puede variar si no liquidas el mismo día.</p>
                                </div>

                                <p class="text-xs text-center text-zinc-400 pt-1">
                                    Para liquidar, acude a nuestras oficinas o llama al <strong class="text-zinc-200">999 941 2170</strong>
                                </p>
                            </div>

                        </div>
                    </div>
                </div>
            </Teleport>

<!-- Tabs -->
<div class="bg-white dark:bg-zinc-900 rounded-3xl border border-slate-100 dark:border-zinc-800 overflow-hidden shadow-sm">
    <div class="p-4 border-b border-slate-100 dark:border-zinc-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <h2 class="font-black text-slate-900 dark:text-white flex items-center gap-2">
            <FileText size="20" class="text-red-700" /> Movimientos
        </h2>
        <div class="flex bg-slate-100 dark:bg-zinc-800 p-1 rounded-2xl gap-1 w-full sm:w-auto">
            <button @click="activeTab = 'tabla'"
                :class="['flex-1 sm:flex-none px-4 py-3 sm:py-2 rounded-xl text-sm font-bold transition-all min-h-[44px] flex items-center justify-center', activeTab === 'tabla' ? 'bg-white dark:bg-zinc-700 shadow-sm text-red-700 dark:text-white' : 'text-slate-500 dark:text-zinc-400']">
                Tabla de Amortización
            </button>
            <button @click="activeTab = 'pagos'"
                :class="['flex-1 sm:flex-none px-4 py-3 sm:py-2 rounded-xl text-sm font-bold transition-all min-h-[44px] flex items-center justify-center', activeTab === 'pagos' ? 'bg-white dark:bg-zinc-700 shadow-sm text-red-700 dark:text-white' : 'text-slate-500 dark:text-zinc-400']">
                Pagos Realizados
            </button>
        </div>
    </div>

    <!-- TABLA DE AMORTIZACIÓN -->
    <div v-if="activeTab === 'tabla'">
        <div v-if="credito.tabla.length > 0">
            <!-- VISTA MÓVIL: TARJETAS (< sm) -->
            <div class="block sm:hidden divide-y divide-slate-100 dark:divide-zinc-800">
                <div v-for="item in credito.tabla" :key="item.numero_cuota" 
                     :class="['p-4 space-y-3', item.estado === 'Gracia' ? 'opacity-50 italic bg-slate-50/50 dark:bg-zinc-800/30' : '', estadoCuota(item).texto === 'VENCIDO' ? 'bg-red-50/30 dark:bg-red-900/10' : '']">
                    
                    <div class="flex justify-between items-center">
                        <span class="font-bold text-xs text-slate-400 dark:text-zinc-500 uppercase tracking-wider">
                            <span v-if="item.estado === 'Gracia'">Cuota de Gracia</span>
                            <span v-else>Cuota #{{ item.numero_cuota }}</span>
                        </span>
                        <span :class="['px-2.5 py-1 rounded-full text-[10px] font-black', estadoCuota(item).clase]">
                            {{ estadoCuota(item).texto }}
                        </span>
                    </div>

                    <div class="flex justify-between items-baseline">
                        <span class="text-xs text-slate-500 dark:text-zinc-400">Vencimiento: <strong class="text-slate-700 dark:text-zinc-200">{{ item.fecha_vencimiento }}</strong></span>
                        <div class="text-right">
                            <span class="text-xs text-slate-400 block">Total Cuota</span>
                            <span class="text-base font-black text-slate-900 dark:text-white">
                                ${{ fmt(Number(item.capital) + Number(item.ordinario) + Number(item.moratorio)) }}
                            </span>
                        </div>
                    </div>

                    <!-- Desglose de importes -->
                    <div class="grid grid-cols-3 gap-2 pt-2 border-t border-slate-100 dark:border-zinc-800/60 text-xs">
                        <div>
                            <span class="text-slate-400 block text-[10px]">Capital</span>
                            <span class="font-medium text-slate-700 dark:text-zinc-300">${{ fmt(item.capital) }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 block text-[10px]">Interés Ord.</span>
                            <span class="font-medium text-slate-700 dark:text-zinc-300">${{ fmt(item.ordinario) }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 block text-[10px]">Mora</span>
                            <span v-if="Number(item.moratorio) > 0" class="font-bold text-red-600">${{ fmt(item.moratorio) }}</span>
                            <span v-else class="text-slate-300 dark:text-zinc-600">—</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- VISTA ESCRITORIO/TABLET: TABLA CON SCROLL (>= sm) -->
            <div class="hidden sm:block overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm min-w-[640px]">
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
                            :class="['hover:bg-slate-50/50 dark:hover:bg-zinc-800/50 transition-colors',
                                item.estado === 'Gracia' ? 'opacity-50 italic' : '',
                                estadoCuota(item).texto === 'VENCIDO' ? 'bg-red-50/30 dark:bg-red-900/5' : '']">
                            <td class="px-5 py-4 font-bold text-slate-500 dark:text-zinc-500">
                                <span v-if="item.estado === 'Gracia'" class="text-xs text-slate-400">Gracia</span>
                                <span v-else>#{{ item.numero_cuota }}</span>
                            </td>
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
            </div>
        </div>

        <div v-else class="p-12 sm:p-16 text-center space-y-3">
            <CalendarDays size="40" class="mx-auto text-slate-300 dark:text-zinc-700" />
            <p class="text-slate-500 dark:text-zinc-400 font-medium">No hay tabla de amortización disponible.</p>
        </div>
    </div>

    <!-- HISTORIAL DE PAGOS -->
    <div v-if="activeTab === 'pagos'">
        <div v-if="credito.pagos.length > 0">
            <!-- VISTA MÓVIL: TARJETAS (< sm) -->
            <div class="block sm:hidden divide-y divide-slate-100 dark:divide-zinc-800">
                <div v-for="pago in credito.pagos" :key="pago.id" class="p-4 space-y-2">
                    <div class="flex justify-between items-center">
                        <span class="font-mono text-xs bg-slate-100 dark:bg-zinc-800 px-2.5 py-1 rounded-lg font-bold">
                            {{ pago.folio ?? `#${pago.id}` }}
                        </span>
                        <span class="text-sm font-black text-emerald-600">${{ fmt(pago.monto_recibido) }}</span>
                    </div>
                    <div class="flex justify-between text-xs text-slate-500 dark:text-zinc-400 pt-1">
                        <span>Fecha: <strong class="text-slate-700 dark:text-zinc-300">{{ pago.fecha_pago }}</strong></span>
                        <span class="capitalize">{{ pago.forma_pago ?? '—' }}</span>
                    </div>
                </div>
            </div>

            <!-- VISTA ESCRITORIO/TABLET: TABLA CON SCROLL (>= sm) -->
            <div class="hidden sm:block overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm min-w-[480px]">
                    <thead>
                        <tr class="bg-slate-50/80 dark:bg-zinc-800/80 text-slate-400 dark:text-zinc-500 text-[11px] uppercase tracking-widest font-bold">
                            <th class="px-5 py-4">Folio</th>
                            <th class="px-5 py-4">Fecha</th>
                            <th class="px-5 py-4 text-right">Monto</th>
                            <th class="px-5 py-4">Forma de Pago</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 dark:divide-zinc-800">
                        <tr v-for="pago in credito.pagos" :key="pago.id" class="hover:bg-slate-50/50 dark:hover:bg-zinc-800/50 transition-colors">
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
            </div>
        </div>

        <div v-else class="p-12 sm:p-16 text-center space-y-4">
            <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-3xl bg-slate-100 dark:bg-zinc-800 flex items-center justify-center mx-auto text-slate-400">
                <CreditCard size="32" />
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
