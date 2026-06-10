<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import {
    ArrowLeft, RefreshCw, AlertTriangle, History, User,
    Calendar, DollarSign, Percent, FileText
} from 'lucide-vue-next';

const props = defineProps<{
    credito: {
        id: number;
        clave_contrato: string;
        estatus: string;
        monto_otorgado: number;
        tasa_ordinaria: number;
        acreditado: string;
        acreditado_id: number;
        saldo_pendiente: number;
        mora_acumulada: number;
    };
    reestructuraciones_previas: Array<{
        fecha: string;
        motivo: string;
        saldo: number;
        nuevo_plazo: number;
        autorizado_por: string;
        numero_resolutivo: string;
    }>;
}>();

const form = useForm({
    fecha_reestructura:       new Date().toISOString().split('T')[0],
    motivo:                   'Dificultad_Economica',
    mora_condonada:           props.credito.mora_acumulada ?? 0,
    interes_condonado:        0,
    nuevo_plazo_meses:        12,
    nueva_tasa_interes:       props.credito.tasa_ordinaria ?? '',
    nueva_fecha_inicio_pagos: '',
    numero_resolutivo:        '',
    observaciones:            '',
});

const fmt = (val: number | string) =>
    new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN', minimumFractionDigits: 2 })
        .format(parseFloat(String(val)) || 0);

const nuevoCapital = computed(() => {
    const saldo       = props.credito.saldo_pendiente || 0;
    const moraCondon  = parseFloat(String(form.mora_condonada)) || 0;
    const intCondon   = parseFloat(String(form.interes_condonado)) || 0;
    return Math.max(0, saldo - moraCondon - intCondon);
});

const cuotaEstimada = computed(() => {
    const monto = nuevoCapital.value;
    const tasa  = (parseFloat(String(form.nueva_tasa_interes)) / 100) / 12;
    const plazo = parseInt(String(form.nuevo_plazo_meses)) || 1;
    if (monto <= 0) return 0;
    if (tasa > 0) return monto * (tasa / (1 - Math.pow(1 + tasa, -plazo)));
    return monto / plazo;
});

const motivoLabels: Record<string, string> = {
    Dificultad_Economica: 'Dificultad Económica',
    Desastre_Natural:     'Desastre Natural',
    Pandemia:             'Pandemia / Emergencia Sanitaria',
    Cambio_Actividad:     'Cambio de Actividad Económica',
    Otro:                 'Otro',
};

const submit = () =>
    form.post(route('creditos.reestructurar.store', props.credito.id));

const inputCls = 'w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-zinc-700 dark:bg-zinc-800 text-sm focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-all text-slate-900 dark:text-white';
const labelCls = 'block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5';
</script>

<template>
    <AppLayout>
        <Head :title="`Reestructuración — ${credito.clave_contrato}`" />

        <div class="p-6 max-w-4xl space-y-6">

            <!-- Header -->
            <div class="flex items-center gap-4">
                <Link :href="route('acreditados.show', credito.acreditado_id)"
                    class="p-2 rounded-xl bg-slate-100 dark:bg-zinc-800 text-slate-600 hover:bg-slate-200 dark:hover:bg-zinc-700 transition-all">
                    <ArrowLeft :size="18" />
                </Link>
                <div>
                    <h1 class="text-2xl font-black text-slate-900 dark:text-white flex items-center gap-3">
                        <RefreshCw :size="24" class="text-red-700" /> Reestructuración de Crédito
                    </h1>
                    <p class="text-slate-500 dark:text-zinc-400 text-sm mt-0.5">
                        Contrato {{ credito.clave_contrato }} — {{ credito.acreditado }}
                    </p>
                </div>
            </div>

            <!-- Alerta importante -->
            <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-300 dark:border-amber-700/50 rounded-2xl p-5 flex items-start gap-4">
                <AlertTriangle :size="20" class="text-amber-600 shrink-0 mt-0.5" />
                <div>
                    <p class="font-black text-amber-800 dark:text-amber-300">Operación irreversible</p>
                    <p class="text-sm text-amber-700 dark:text-amber-400 mt-0.5">
                        Al guardar, se cancelarán todas las cuotas pendientes y se generará una nueva tabla de amortización.
                        Esta acción requiere número de resolutivo y queda registrada en la auditoría.
                    </p>
                </div>
            </div>

            <!-- Resumen del saldo actual -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-slate-100 dark:border-zinc-800 p-5 text-center shadow-sm">
                    <p class="text-[10px] font-black text-slate-400 uppercase mb-1">Saldo Pendiente</p>
                    <p class="text-xl font-black text-slate-900 dark:text-white">{{ fmt(credito.saldo_pendiente) }}</p>
                </div>
                <div class="bg-orange-50 dark:bg-orange-900/20 rounded-2xl border border-orange-200 dark:border-orange-800/40 p-5 text-center shadow-sm">
                    <p class="text-[10px] font-black text-orange-500 uppercase mb-1">Mora Acumulada</p>
                    <p class="text-xl font-black text-orange-700 dark:text-orange-400">{{ fmt(credito.mora_acumulada) }}</p>
                </div>
                <div :class="['rounded-2xl border p-5 text-center shadow-sm', nuevoCapital > 0 ? 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800/40' : 'bg-slate-50 dark:bg-zinc-800/50 border-slate-200 dark:border-zinc-700']">
                    <p class="text-[10px] font-black text-blue-500 uppercase mb-1">Nuevo Capital a Reestructurar</p>
                    <p class="text-xl font-black text-blue-700 dark:text-blue-400">{{ fmt(nuevoCapital) }}</p>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-6">

                <!-- Datos de la reestructuración -->
                <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-slate-100 dark:border-zinc-800 overflow-hidden shadow-sm">
                    <div class="px-5 py-4 border-b border-slate-100 dark:border-zinc-800 flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-red-50 dark:bg-red-900/20 flex items-center justify-center text-red-700">
                            <Calendar :size="18" />
                        </div>
                        <h2 class="font-black text-slate-900 dark:text-white">Datos de la Reestructuración</h2>
                    </div>
                    <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label :class="labelCls">Fecha de Reestructuración <span class="text-red-500">*</span></label>
                            <input v-model="form.fecha_reestructura" type="date" required :class="inputCls" />
                            <p v-if="form.errors.fecha_reestructura" class="text-red-600 text-xs mt-1">{{ form.errors.fecha_reestructura }}</p>
                        </div>
                        <div>
                            <label :class="labelCls">Motivo <span class="text-red-500">*</span></label>
                            <select v-model="form.motivo" required :class="inputCls">
                                <option v-for="(label, val) in motivoLabels" :key="val" :value="val">{{ label }}</option>
                            </select>
                            <p v-if="form.errors.motivo" class="text-red-600 text-xs mt-1">{{ form.errors.motivo }}</p>
                        </div>
                        <div>
                            <label :class="labelCls">Número de Resolutivo</label>
                            <input v-model="form.numero_resolutivo" type="text" maxlength="100" :class="inputCls" class="font-mono"
                                placeholder="Ej. CREA-RES-2026-001" />
                        </div>
                        <div>
                            <label :class="labelCls">Nueva Fecha de Inicio de Pagos <span class="text-red-500">*</span></label>
                            <input v-model="form.nueva_fecha_inicio_pagos" type="date" required :class="inputCls" />
                            <p v-if="form.errors.nueva_fecha_inicio_pagos" class="text-red-600 text-xs mt-1">{{ form.errors.nueva_fecha_inicio_pagos }}</p>
                        </div>
                    </div>
                </div>

                <!-- Condonaciones previas a reestructurar -->
                <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-slate-100 dark:border-zinc-800 overflow-hidden shadow-sm">
                    <div class="px-5 py-4 border-b border-slate-100 dark:border-zinc-800 flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-red-50 dark:bg-red-900/20 flex items-center justify-center text-red-700">
                            <DollarSign :size="18" />
                        </div>
                        <h2 class="font-black text-slate-900 dark:text-white">Condonaciones Aplicadas en esta Reestructuración</h2>
                        <span class="text-xs text-slate-400 ml-auto">Opcional</span>
                    </div>
                    <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label :class="labelCls">Mora Condonada ($)</label>
                            <input v-model="form.mora_condonada" type="number" step="0.01" min="0"
                                :max="credito.mora_acumulada" :class="inputCls" />
                            <p class="text-xs text-slate-400 mt-1">Máx: {{ fmt(credito.mora_acumulada) }}</p>
                            <p v-if="form.errors.mora_condonada" class="text-red-600 text-xs mt-1">{{ form.errors.mora_condonada }}</p>
                        </div>
                        <div>
                            <label :class="labelCls">Interés Condonado ($)</label>
                            <input v-model="form.interes_condonado" type="number" step="0.01" min="0" :class="inputCls" />
                            <p v-if="form.errors.interes_condonado" class="text-red-600 text-xs mt-1">{{ form.errors.interes_condonado }}</p>
                        </div>
                    </div>
                </div>

                <!-- Nuevas condiciones -->
                <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-slate-100 dark:border-zinc-800 overflow-hidden shadow-sm">
                    <div class="px-5 py-4 border-b border-slate-100 dark:border-zinc-800 flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-red-50 dark:bg-red-900/20 flex items-center justify-center text-red-700">
                            <Percent :size="18" />
                        </div>
                        <h2 class="font-black text-slate-900 dark:text-white">Nuevas Condiciones del Crédito</h2>
                    </div>
                    <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label :class="labelCls">Nuevo Plazo (meses) <span class="text-red-500">*</span></label>
                            <select v-model="form.nuevo_plazo_meses" required :class="inputCls">
                                <option v-for="p in [3,6,9,12,18,24,30,36,48,60]" :key="p" :value="p">{{ p }} meses</option>
                            </select>
                            <p v-if="form.errors.nuevo_plazo_meses" class="text-red-600 text-xs mt-1">{{ form.errors.nuevo_plazo_meses }}</p>
                        </div>
                        <div>
                            <label :class="labelCls">Nueva Tasa de Interés Anual (%) <span class="text-red-500">*</span></label>
                            <input v-model="form.nueva_tasa_interes" type="number" step="0.01" min="0" max="100" required :class="inputCls" />
                            <p v-if="form.errors.nueva_tasa_interes" class="text-red-600 text-xs mt-1">{{ form.errors.nueva_tasa_interes }}</p>
                        </div>

                        <!-- Vista previa de cuota -->
                        <div class="sm:col-span-2">
                            <div class="bg-slate-50 dark:bg-zinc-800/50 rounded-xl border border-slate-200 dark:border-zinc-700 p-4 flex items-center justify-between">
                                <div>
                                    <p class="text-[10px] font-black text-slate-400 uppercase">Cuota Mensual Estimada</p>
                                    <p class="text-2xl font-black text-slate-900 dark:text-white mt-1">{{ fmt(cuotaEstimada) }}</p>
                                </div>
                                <div class="text-right text-xs text-slate-400 space-y-0.5">
                                    <p>Capital: {{ fmt(nuevoCapital) }}</p>
                                    <p>Tasa: {{ form.nueva_tasa_interes }}% anual</p>
                                    <p>Plazo: {{ form.nuevo_plazo_meses }} meses</p>
                                </div>
                            </div>
                        </div>

                        <div class="sm:col-span-2">
                            <label :class="labelCls">Observaciones</label>
                            <textarea v-model="form.observaciones" rows="3" :class="inputCls" class="resize-none"
                                placeholder="Justificación y condiciones adicionales de la reestructuración..."></textarea>
                        </div>
                    </div>
                </div>

                <!-- Reestructuraciones previas -->
                <div v-if="reestructuraciones_previas.length > 0"
                    class="bg-white dark:bg-zinc-900 rounded-2xl border border-slate-100 dark:border-zinc-800 overflow-hidden shadow-sm">
                    <div class="px-5 py-4 border-b border-slate-100 dark:border-zinc-800 flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-slate-50 dark:bg-zinc-800 flex items-center justify-center text-slate-500">
                            <History :size="18" />
                        </div>
                        <h2 class="font-black text-slate-900 dark:text-white">Historial de Reestructuraciones</h2>
                        <span class="ml-auto text-xs font-black px-2 py-0.5 rounded-full bg-slate-100 text-slate-500 dark:bg-zinc-700 dark:text-zinc-400">
                            {{ reestructuraciones_previas.length }} previa(s)
                        </span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-slate-100 dark:border-zinc-800 bg-slate-50/60 dark:bg-zinc-800/50">
                                    <th class="px-4 py-3 text-left text-[10px] font-black text-slate-500 uppercase">Fecha</th>
                                    <th class="px-4 py-3 text-left text-[10px] font-black text-slate-500 uppercase">Motivo</th>
                                    <th class="px-4 py-3 text-right text-[10px] font-black text-slate-500 uppercase">Saldo</th>
                                    <th class="px-4 py-3 text-right text-[10px] font-black text-slate-500 uppercase">Nuevo Plazo</th>
                                    <th class="px-4 py-3 text-left text-[10px] font-black text-slate-500 uppercase">Resolutivo</th>
                                    <th class="px-4 py-3 text-left text-[10px] font-black text-slate-500 uppercase">Autorizó</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50 dark:divide-zinc-800">
                                <tr v-for="(r, i) in reestructuraciones_previas" :key="i"
                                    class="hover:bg-slate-50 dark:hover:bg-zinc-800/30">
                                    <td class="px-4 py-3 text-xs text-slate-500">{{ r.fecha }}</td>
                                    <td class="px-4 py-3 text-xs text-slate-700 dark:text-zinc-200">{{ motivoLabels[r.motivo] ?? r.motivo }}</td>
                                    <td class="px-4 py-3 text-right text-xs font-semibold text-slate-700 dark:text-zinc-200">{{ fmt(r.saldo) }}</td>
                                    <td class="px-4 py-3 text-right text-xs text-slate-500">{{ r.nuevo_plazo }} meses</td>
                                    <td class="px-4 py-3 text-xs font-mono text-slate-500">{{ r.numero_resolutivo || '—' }}</td>
                                    <td class="px-4 py-3 text-xs text-slate-500">{{ r.autorizado_por || '—' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Acciones -->
                <div class="flex justify-end gap-4">
                    <Link :href="route('acreditados.show', credito.acreditado_id)"
                        class="px-5 py-3 rounded-xl bg-slate-100 dark:bg-zinc-800 text-slate-700 dark:text-zinc-300 font-bold text-sm hover:bg-slate-200 dark:hover:bg-zinc-700 transition-all">
                        Cancelar
                    </Link>
                    <button type="submit" :disabled="form.processing"
                        class="inline-flex items-center gap-2 px-8 py-3 bg-red-700 hover:bg-red-800 text-white font-black rounded-xl text-sm transition-all shadow-lg shadow-red-900/20 disabled:opacity-60 active:scale-[0.98]">
                        <RefreshCw :size="16" />
                        {{ form.processing ? 'Procesando...' : 'Confirmar Reestructuración' }}
                    </button>
                </div>

            </form>
        </div>
    </AppLayout>
</template>
