<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import {
    ArrowLeft, ClipboardList, CheckCircle2, XCircle, AlertCircle,
    TrendingUp, User, DollarSign, ThumbsUp, ThumbsDown, RefreshCw
} from 'lucide-vue-next';

const props = defineProps<{
    solicitud: {
        id: number;
        nombre_completo: string;
        curp?: string;
        rfc?: string;
        municipio?: string;
        alta_sat: boolean;
        monto_solicitado?: number;
        modalidad?: string;
        giro_comercial?: string;
        estatus: string;
    };
    analisis_existente: {
        id: number;
        ingresos_mensuales: number;
        gastos_mensuales: number;
        capacidad_pago: number;
        relacion_deuda_ingreso: number;
        curp_valida: boolean;
        rfc_activo: boolean;
        municipio_elegible: boolean;
        giro_elegible: boolean;
        beneficiario_duplicado: boolean;
        alta_sat_verificada: boolean;
        documentos_completos: boolean;
        referencias_verificadas: boolean;
        antiguedad_negocio_meses: number;
        score_cualitativo: number;
        observaciones_analisis?: string;
        recomendacion: string;
        monto_recomendado?: number;
        motivo_rechazo?: string;
    } | null;
}>();

const form = useForm({
    ingresos_mensuales:       props.analisis_existente?.ingresos_mensuales ?? '',
    gastos_mensuales:         props.analisis_existente?.gastos_mensuales ?? '',
    curp_valida:              props.analisis_existente?.curp_valida ?? false,
    rfc_activo:               props.analisis_existente?.rfc_activo ?? false,
    municipio_elegible:       props.analisis_existente?.municipio_elegible ?? false,
    giro_elegible:            props.analisis_existente?.giro_elegible ?? false,
    beneficiario_duplicado:   props.analisis_existente?.beneficiario_duplicado ?? false,
    alta_sat_verificada:      props.analisis_existente?.alta_sat_verificada ?? false,
    documentos_completos:     props.analisis_existente?.documentos_completos ?? false,
    referencias_verificadas:  props.analisis_existente?.referencias_verificadas ?? false,
    antiguedad_negocio_meses: props.analisis_existente?.antiguedad_negocio_meses ?? '',
    observaciones_analisis:   props.analisis_existente?.observaciones_analisis ?? '',
    recomendacion:            props.analisis_existente?.recomendacion ?? '',
    monto_recomendado:        props.analisis_existente?.monto_recomendado ?? props.solicitud.monto_solicitado ?? '',
    motivo_rechazo:           props.analisis_existente?.motivo_rechazo ?? '',
});

const fmt = (val: number | string) =>
    new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN', minimumFractionDigits: 2 })
        .format(parseFloat(String(val)) || 0);

const capacidadPago = computed(() => {
    const ing = parseFloat(String(form.ingresos_mensuales)) || 0;
    const gas = parseFloat(String(form.gastos_mensuales)) || 0;
    return ing - gas;
});

const checklistScore = computed(() => {
    const items = [
        form.curp_valida,
        form.rfc_activo,
        form.municipio_elegible,
        form.giro_elegible,
        !form.beneficiario_duplicado,
        form.alta_sat_verificada,
        form.documentos_completos,
        form.referencias_verificadas,
    ];
    return items.filter(Boolean).length;
});

const submit = () =>
    form.post(route('solicitudes.analisis.store', props.solicitud.id));

const inputCls = 'w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-zinc-700 dark:bg-zinc-800 text-sm focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-all text-slate-900 dark:text-white';
const labelCls = 'block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5';

const recomendacionConfig: Record<string, { bg: string; text: string; icon: any; label: string }> = {
    Aprobar:        { bg: 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800/40', text: 'text-green-700 dark:text-green-400', icon: ThumbsUp, label: 'Aprobar Solicitud' },
    Rechazar:       { bg: 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800/40',         text: 'text-red-700 dark:text-red-400',    icon: ThumbsDown, label: 'Rechazar Solicitud' },
    Modificar_Monto:{ bg: 'bg-amber-50 dark:bg-amber-900/20 border-amber-200 dark:border-amber-800/40', text: 'text-amber-700 dark:text-amber-400', icon: RefreshCw, label: 'Modificar Monto' },
};
</script>

<template>
    <AppLayout>
        <Head :title="`Análisis Crediticio — Solicitud #${solicitud.id}`" />

        <div class="p-6 max-w-4xl space-y-6">

            <!-- Header -->
            <div class="flex items-center gap-4">
                <Link :href="route('solicitudes.show', solicitud.id)"
                    class="p-2 rounded-xl bg-slate-100 dark:bg-zinc-800 text-slate-600 hover:bg-slate-200 dark:hover:bg-zinc-700 transition-all">
                    <ArrowLeft :size="18" />
                </Link>
                <div>
                    <h1 class="text-2xl font-black text-slate-900 dark:text-white flex items-center gap-3">
                        <ClipboardList :size="24" class="text-red-700" /> Análisis Crediticio
                    </h1>
                    <p class="text-slate-500 dark:text-zinc-400 text-sm mt-0.5">
                        Solicitud #{{ solicitud.id }} — {{ solicitud.nombre_completo }}
                    </p>
                </div>
                <div v-if="analisis_existente" class="ml-auto">
                    <span class="px-3 py-1.5 rounded-full text-xs font-black bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">
                        Actualizar Análisis
                    </span>
                </div>
            </div>

            <!-- Resumen del solicitante -->
            <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-slate-100 dark:border-zinc-800 overflow-hidden shadow-sm">
                <div class="px-5 py-4 border-b border-slate-100 dark:border-zinc-800 flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-red-50 dark:bg-red-900/20 flex items-center justify-center text-red-700">
                        <User :size="18" />
                    </div>
                    <h2 class="font-black text-slate-900 dark:text-white">Datos del Solicitante</h2>
                </div>
                <div class="p-5 grid grid-cols-2 sm:grid-cols-3 gap-4 text-sm">
                    <div>
                        <p class="text-[10px] font-black text-slate-400 uppercase mb-0.5">CURP</p>
                        <p class="font-mono text-slate-800 dark:text-white text-xs">{{ solicitud.curp || '—' }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-black text-slate-400 uppercase mb-0.5">RFC</p>
                        <p class="font-mono text-slate-800 dark:text-white text-xs">{{ solicitud.rfc || '—' }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-black text-slate-400 uppercase mb-0.5">Municipio</p>
                        <p class="text-slate-800 dark:text-white">{{ solicitud.municipio || '—' }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-black text-slate-400 uppercase mb-0.5">Modalidad</p>
                        <p class="text-slate-800 dark:text-white">{{ solicitud.modalidad || '—' }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-black text-slate-400 uppercase mb-0.5">Giro Comercial</p>
                        <p class="text-slate-800 dark:text-white">{{ solicitud.giro_comercial || '—' }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-black text-slate-400 uppercase mb-0.5">Monto Solicitado</p>
                        <p class="font-black text-slate-900 dark:text-white">{{ solicitud.monto_solicitado ? fmt(solicitud.monto_solicitado) : '—' }}</p>
                    </div>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-6">

                <!-- Análisis financiero -->
                <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-slate-100 dark:border-zinc-800 overflow-hidden shadow-sm">
                    <div class="px-5 py-4 border-b border-slate-100 dark:border-zinc-800 flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-red-50 dark:bg-red-900/20 flex items-center justify-center text-red-700">
                            <DollarSign :size="18" />
                        </div>
                        <h2 class="font-black text-slate-900 dark:text-white">Análisis Financiero</h2>
                    </div>
                    <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label :class="labelCls">Ingresos Mensuales Declarados ($)</label>
                            <input v-model="form.ingresos_mensuales" type="number" step="0.01" min="0" :class="inputCls" />
                            <p v-if="form.errors.ingresos_mensuales" class="text-red-600 text-xs mt-1">{{ form.errors.ingresos_mensuales }}</p>
                        </div>
                        <div>
                            <label :class="labelCls">Gastos Mensuales Declarados ($)</label>
                            <input v-model="form.gastos_mensuales" type="number" step="0.01" min="0" :class="inputCls" />
                            <p v-if="form.errors.gastos_mensuales" class="text-red-600 text-xs mt-1">{{ form.errors.gastos_mensuales }}</p>
                        </div>
                        <div>
                            <label :class="labelCls">Antigüedad del Negocio (meses)</label>
                            <input v-model="form.antiguedad_negocio_meses" type="number" min="0" :class="inputCls" />
                        </div>
                        <!-- Capacidad de pago calculada -->
                        <div class="sm:col-span-1">
                            <label :class="labelCls">Capacidad de Pago (calculada)</label>
                            <div :class="['px-4 py-3 rounded-xl border text-sm font-black', capacidadPago >= 0 ? 'bg-green-50 border-green-200 text-green-700 dark:bg-green-900/20 dark:border-green-800/40 dark:text-green-400' : 'bg-red-50 border-red-200 text-red-700 dark:bg-red-900/20 dark:border-red-800/40 dark:text-red-400']">
                                {{ fmt(capacidadPago) }} / mes
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Checklist de elegibilidad -->
                <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-slate-100 dark:border-zinc-800 overflow-hidden shadow-sm">
                    <div class="px-5 py-4 border-b border-slate-100 dark:border-zinc-800 flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-red-50 dark:bg-red-900/20 flex items-center justify-center text-red-700">
                            <TrendingUp :size="18" />
                        </div>
                        <h2 class="font-black text-slate-900 dark:text-white">Checklist de Elegibilidad</h2>
                        <div class="ml-auto flex items-center gap-2">
                            <span :class="['text-xs font-black px-2.5 py-1 rounded-full', checklistScore >= 6 ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : checklistScore >= 4 ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400' : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400']">
                                {{ checklistScore }}/8 criterios
                            </span>
                        </div>
                    </div>
                    <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-4">

                        <label v-for="(item, key) in [
                            { field: 'curp_valida',             label: 'CURP Válida y Verificada' },
                            { field: 'rfc_activo',              label: 'RFC Activo en SAT' },
                            { field: 'municipio_elegible',      label: 'Municipio Elegible CREA' },
                            { field: 'giro_elegible',           label: 'Giro Comercial Elegible' },
                            { field: 'alta_sat_verificada',     label: 'Alta SAT Verificada' },
                            { field: 'documentos_completos',    label: 'Documentación Completa' },
                            { field: 'referencias_verificadas', label: 'Referencias Verificadas' },
                        ]" :key="key"
                            class="flex items-center gap-3 p-3 rounded-xl border border-slate-100 dark:border-zinc-800 cursor-pointer hover:bg-slate-50 dark:hover:bg-zinc-800/50 transition-all">
                            <input type="checkbox" v-model="(form as any)[item.field]"
                                class="w-4 h-4 rounded text-red-700 border-slate-300 dark:border-zinc-600 focus:ring-red-500 cursor-pointer" />
                            <span class="text-sm font-semibold text-slate-700 dark:text-zinc-200">{{ item.label }}</span>
                            <component :is="(form as any)[item.field] ? CheckCircle2 : XCircle"
                                :size="16" :class="(form as any)[item.field] ? 'text-green-500 ml-auto' : 'text-slate-300 ml-auto'" />
                        </label>

                        <!-- Beneficiario duplicado (invertido: checked = malo) -->
                        <label class="flex items-center gap-3 p-3 rounded-xl border border-slate-100 dark:border-zinc-800 cursor-pointer hover:bg-slate-50 dark:hover:bg-zinc-800/50 transition-all"
                            :class="form.beneficiario_duplicado ? 'border-red-200 bg-red-50/50 dark:bg-red-900/10' : ''">
                            <input type="checkbox" v-model="form.beneficiario_duplicado"
                                class="w-4 h-4 rounded text-red-700 border-slate-300 dark:border-zinc-600 focus:ring-red-500 cursor-pointer" />
                            <span class="text-sm font-semibold text-slate-700 dark:text-zinc-200">Beneficiario Duplicado</span>
                            <AlertCircle v-if="form.beneficiario_duplicado" :size="16" class="text-red-500 ml-auto" />
                            <CheckCircle2 v-else :size="16" class="text-green-500 ml-auto" />
                        </label>

                    </div>
                </div>

                <!-- Recomendación -->
                <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-slate-100 dark:border-zinc-800 overflow-hidden shadow-sm">
                    <div class="px-5 py-4 border-b border-slate-100 dark:border-zinc-800 flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-red-50 dark:bg-red-900/20 flex items-center justify-center text-red-700">
                            <ClipboardList :size="18" />
                        </div>
                        <h2 class="font-black text-slate-900 dark:text-white">Recomendación del Analista</h2>
                    </div>
                    <div class="p-5 space-y-5">
                        <div>
                            <label :class="labelCls">Recomendación <span class="text-red-500">*</span></label>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mt-1">
                                <button v-for="(config, key) in recomendacionConfig" :key="key" type="button"
                                    @click="form.recomendacion = key"
                                    :class="['p-4 rounded-xl border-2 text-sm font-bold flex items-center gap-2 transition-all', form.recomendacion === key ? `${config.bg} border-current ${config.text}` : 'border-slate-200 dark:border-zinc-700 text-slate-400 hover:border-slate-300 dark:hover:border-zinc-600']">
                                    <component :is="config.icon" :size="18" />
                                    {{ config.label }}
                                </button>
                            </div>
                            <p v-if="form.errors.recomendacion" class="text-red-600 text-xs mt-1">{{ form.errors.recomendacion }}</p>
                        </div>

                        <div v-if="form.recomendacion === 'Modificar_Monto'">
                            <label :class="labelCls">Monto Recomendado ($)</label>
                            <input v-model="form.monto_recomendado" type="number" step="0.01" min="100" :class="inputCls" />
                            <p v-if="form.errors.monto_recomendado" class="text-red-600 text-xs mt-1">{{ form.errors.monto_recomendado }}</p>
                        </div>

                        <div v-if="form.recomendacion === 'Rechazar'">
                            <label :class="labelCls">Motivo de Rechazo <span class="text-red-500">*</span></label>
                            <textarea v-model="form.motivo_rechazo" rows="3" :class="inputCls" class="resize-none"
                                placeholder="Describe el motivo del rechazo..."></textarea>
                            <p v-if="form.errors.motivo_rechazo" class="text-red-600 text-xs mt-1">{{ form.errors.motivo_rechazo }}</p>
                        </div>

                        <div>
                            <label :class="labelCls">Observaciones del Análisis</label>
                            <textarea v-model="form.observaciones_analisis" rows="4" :class="inputCls" class="resize-none"
                                placeholder="Observaciones técnicas, riesgos identificados, condiciones especiales..."></textarea>
                        </div>
                    </div>
                </div>

                <!-- Acciones -->
                <div class="flex justify-end gap-4">
                    <Link :href="route('solicitudes.show', solicitud.id)"
                        class="px-5 py-3 rounded-xl bg-slate-100 dark:bg-zinc-800 text-slate-700 dark:text-zinc-300 font-bold text-sm hover:bg-slate-200 dark:hover:bg-zinc-700 transition-all">
                        Cancelar
                    </Link>
                    <button type="submit" :disabled="form.processing || !form.recomendacion"
                        class="inline-flex items-center gap-2 px-8 py-3 bg-red-700 hover:bg-red-800 text-white font-black rounded-xl text-sm transition-all shadow-lg shadow-red-900/20 disabled:opacity-60 active:scale-[0.98]">
                        <ClipboardList :size="16" />
                        {{ form.processing ? 'Guardando...' : (analisis_existente ? 'Actualizar Análisis' : 'Guardar Análisis') }}
                    </button>
                </div>

            </form>
        </div>
    </AppLayout>
</template>
