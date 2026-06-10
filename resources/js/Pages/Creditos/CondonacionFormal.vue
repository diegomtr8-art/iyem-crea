<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import {
    ArrowLeft, XOctagon, AlertTriangle, History, DollarSign, FileText
} from 'lucide-vue-next';

const props = defineProps<{
    credito: {
        id: number;
        clave_contrato: string;
        estatus: string;
        acreditado: string;
        acreditado_id: number;
        saldo_capital: number;
        saldo_intereses: number;
        saldo_mora: number;
    };
    condonaciones_previas: Array<{
        fecha: string;
        tipo: string;
        monto_total: number;
        numero_acuerdo: string;
        autorizado_por: string;
    }>;
}>();

const form = useForm({
    tipo:                       'Parcial_Mora',
    monto_condonado_capital:    0,
    monto_condonado_intereses:  0,
    monto_condonado_mora:       props.credito.saldo_mora ?? 0,
    motivo:                     '',
    numero_acuerdo:             '',
    fecha_autorizacion:         new Date().toISOString().split('T')[0],
    observaciones:              '',
});

const fmt = (val: number | string) =>
    new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN', minimumFractionDigits: 2 })
        .format(parseFloat(String(val)) || 0);

const montoTotal = computed(() => {
    const cap  = parseFloat(String(form.monto_condonado_capital)) || 0;
    const int  = parseFloat(String(form.monto_condonado_intereses)) || 0;
    const mora = parseFloat(String(form.monto_condonado_mora)) || 0;
    return cap + int + mora;
});

const tipoLabels: Record<string, string> = {
    Parcial_Capital:   'Parcial — Capital',
    Parcial_Intereses: 'Parcial — Intereses',
    Parcial_Mora:      'Parcial — Mora',
    Total:             'Condonación Total',
};

const onTipoChange = () => {
    if (form.tipo === 'Total') {
        form.monto_condonado_capital   = props.credito.saldo_capital;
        form.monto_condonado_intereses = props.credito.saldo_intereses;
        form.monto_condonado_mora      = props.credito.saldo_mora;
    } else if (form.tipo === 'Parcial_Capital') {
        form.monto_condonado_intereses = 0;
        form.monto_condonado_mora      = 0;
    } else if (form.tipo === 'Parcial_Intereses') {
        form.monto_condonado_capital   = 0;
        form.monto_condonado_mora      = 0;
    } else if (form.tipo === 'Parcial_Mora') {
        form.monto_condonado_capital   = 0;
        form.monto_condonado_intereses = 0;
        form.monto_condonado_mora      = props.credito.saldo_mora;
    }
};

const submit = () =>
    form.post(route('creditos.condonacion-formal.store', props.credito.id));

const inputCls = 'w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-zinc-700 dark:bg-zinc-800 text-sm focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-all text-slate-900 dark:text-white';
const labelCls = 'block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5';
</script>

<template>
    <AppLayout>
        <Head :title="`Condonación Formal — ${credito.clave_contrato}`" />

        <div class="p-6 max-w-3xl space-y-6">

            <!-- Header -->
            <div class="flex items-center gap-4">
                <Link :href="route('acreditados.show', credito.acreditado_id)"
                    class="p-2 rounded-xl bg-slate-100 dark:bg-zinc-800 text-slate-600 hover:bg-slate-200 dark:hover:bg-zinc-700 transition-all">
                    <ArrowLeft :size="18" />
                </Link>
                <div>
                    <h1 class="text-2xl font-black text-slate-900 dark:text-white flex items-center gap-3">
                        <XOctagon :size="24" class="text-red-700" /> Condonación Formal
                    </h1>
                    <p class="text-slate-500 dark:text-zinc-400 text-sm mt-0.5">
                        Contrato {{ credito.clave_contrato }} — {{ credito.acreditado }}
                    </p>
                </div>
            </div>

            <!-- Advertencia -->
            <div class="bg-red-50 dark:bg-red-900/20 border border-red-300 dark:border-red-700/50 rounded-2xl p-5 flex items-start gap-4">
                <AlertTriangle :size="20" class="text-red-600 shrink-0 mt-0.5" />
                <div>
                    <p class="font-black text-red-800 dark:text-red-300">Operación sensible — Requiere acuerdo formal</p>
                    <p class="text-sm text-red-700 dark:text-red-400 mt-0.5">
                        La condonación total liquidará todas las cuotas pendientes y cerrará el crédito.
                        Esta acción queda registrada en la auditoría. Asegúrate de contar con el número de acuerdo antes de continuar.
                    </p>
                </div>
            </div>

            <!-- Saldos actuales -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-2xl border border-blue-200 dark:border-blue-800/40 p-4 text-center shadow-sm">
                    <p class="text-[10px] font-black text-blue-500 uppercase mb-1">Saldo Capital</p>
                    <p class="text-lg font-black text-blue-700 dark:text-blue-400">{{ fmt(credito.saldo_capital) }}</p>
                </div>
                <div class="bg-red-50 dark:bg-red-900/20 rounded-2xl border border-red-200 dark:border-red-800/40 p-4 text-center shadow-sm">
                    <p class="text-[10px] font-black text-red-500 uppercase mb-1">Saldo Intereses</p>
                    <p class="text-lg font-black text-red-700 dark:text-red-400">{{ fmt(credito.saldo_intereses) }}</p>
                </div>
                <div class="bg-orange-50 dark:bg-orange-900/20 rounded-2xl border border-orange-200 dark:border-orange-800/40 p-4 text-center shadow-sm">
                    <p class="text-[10px] font-black text-orange-500 uppercase mb-1">Saldo Mora</p>
                    <p class="text-lg font-black text-orange-700 dark:text-orange-400">{{ fmt(credito.saldo_mora) }}</p>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-6">

                <!-- Tipo de condonación -->
                <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-slate-100 dark:border-zinc-800 overflow-hidden shadow-sm">
                    <div class="px-5 py-4 border-b border-slate-100 dark:border-zinc-800 flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-red-50 dark:bg-red-900/20 flex items-center justify-center text-red-700">
                            <XOctagon :size="18" />
                        </div>
                        <h2 class="font-black text-slate-900 dark:text-white">Tipo de Condonación</h2>
                    </div>
                    <div class="p-5">
                        <label :class="labelCls">Seleccionar Tipo <span class="text-red-500">*</span></label>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-1">
                            <button v-for="(label, val) in tipoLabels" :key="val" type="button"
                                @click="form.tipo = val; onTipoChange()"
                                :class="['p-3 rounded-xl border-2 text-xs font-bold transition-all text-center', form.tipo === val
                                    ? 'border-red-600 bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400'
                                    : 'border-slate-200 dark:border-zinc-700 text-slate-400 hover:border-slate-300 dark:hover:border-zinc-600']">
                                {{ label }}
                            </button>
                        </div>
                        <p v-if="form.errors.tipo" class="text-red-600 text-xs mt-1">{{ form.errors.tipo }}</p>
                    </div>
                </div>

                <!-- Montos a condonar -->
                <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-slate-100 dark:border-zinc-800 overflow-hidden shadow-sm">
                    <div class="px-5 py-4 border-b border-slate-100 dark:border-zinc-800 flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-red-50 dark:bg-red-900/20 flex items-center justify-center text-red-700">
                            <DollarSign :size="18" />
                        </div>
                        <h2 class="font-black text-slate-900 dark:text-white">Montos a Condonar</h2>
                    </div>
                    <div class="p-5 grid grid-cols-1 sm:grid-cols-3 gap-5">
                        <div>
                            <label :class="labelCls">Capital ($)</label>
                            <input v-model="form.monto_condonado_capital" type="number" step="0.01" min="0"
                                :max="credito.saldo_capital"
                                :disabled="form.tipo === 'Parcial_Intereses' || form.tipo === 'Parcial_Mora'"
                                :class="[inputCls, (form.tipo === 'Parcial_Intereses' || form.tipo === 'Parcial_Mora') ? 'opacity-50 cursor-not-allowed' : '']" />
                            <p class="text-xs text-slate-400 mt-1">Máx: {{ fmt(credito.saldo_capital) }}</p>
                            <p v-if="form.errors.monto_condonado_capital" class="text-red-600 text-xs mt-1">{{ form.errors.monto_condonado_capital }}</p>
                        </div>
                        <div>
                            <label :class="labelCls">Intereses ($)</label>
                            <input v-model="form.monto_condonado_intereses" type="number" step="0.01" min="0"
                                :max="credito.saldo_intereses"
                                :disabled="form.tipo === 'Parcial_Capital' || form.tipo === 'Parcial_Mora'"
                                :class="[inputCls, (form.tipo === 'Parcial_Capital' || form.tipo === 'Parcial_Mora') ? 'opacity-50 cursor-not-allowed' : '']" />
                            <p class="text-xs text-slate-400 mt-1">Máx: {{ fmt(credito.saldo_intereses) }}</p>
                            <p v-if="form.errors.monto_condonado_intereses" class="text-red-600 text-xs mt-1">{{ form.errors.monto_condonado_intereses }}</p>
                        </div>
                        <div>
                            <label :class="labelCls">Mora ($)</label>
                            <input v-model="form.monto_condonado_mora" type="number" step="0.01" min="0"
                                :max="credito.saldo_mora"
                                :disabled="form.tipo === 'Parcial_Capital' || form.tipo === 'Parcial_Intereses'"
                                :class="[inputCls, (form.tipo === 'Parcial_Capital' || form.tipo === 'Parcial_Intereses') ? 'opacity-50 cursor-not-allowed' : '']" />
                            <p class="text-xs text-slate-400 mt-1">Máx: {{ fmt(credito.saldo_mora) }}</p>
                            <p v-if="form.errors.monto_condonado_mora" class="text-red-600 text-xs mt-1">{{ form.errors.monto_condonado_mora }}</p>
                        </div>

                        <!-- Total -->
                        <div class="sm:col-span-3">
                            <div class="bg-slate-50 dark:bg-zinc-800/50 rounded-xl border border-slate-200 dark:border-zinc-700 p-4 flex items-center justify-between">
                                <p class="text-sm font-black text-slate-700 dark:text-zinc-200">Total a Condonar</p>
                                <p class="text-2xl font-black" :class="montoTotal > 0 ? 'text-red-700 dark:text-red-400' : 'text-slate-400'">
                                    {{ fmt(montoTotal) }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Datos del acuerdo -->
                <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-slate-100 dark:border-zinc-800 overflow-hidden shadow-sm">
                    <div class="px-5 py-4 border-b border-slate-100 dark:border-zinc-800 flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-red-50 dark:bg-red-900/20 flex items-center justify-center text-red-700">
                            <FileText :size="18" />
                        </div>
                        <h2 class="font-black text-slate-900 dark:text-white">Datos del Acuerdo Formal</h2>
                    </div>
                    <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label :class="labelCls">Número de Acuerdo / Resolutivo</label>
                            <input v-model="form.numero_acuerdo" type="text" maxlength="100" :class="inputCls" class="font-mono"
                                placeholder="Ej. CREA-ACU-2026-012" />
                        </div>
                        <div>
                            <label :class="labelCls">Fecha de Autorización <span class="text-red-500">*</span></label>
                            <input v-model="form.fecha_autorizacion" type="date" required :class="inputCls" />
                            <p v-if="form.errors.fecha_autorizacion" class="text-red-600 text-xs mt-1">{{ form.errors.fecha_autorizacion }}</p>
                        </div>
                        <div class="sm:col-span-2">
                            <label :class="labelCls">Motivo de la Condonación <span class="text-red-500">*</span></label>
                            <textarea v-model="form.motivo" rows="3" required :class="inputCls" class="resize-none"
                                placeholder="Describe el fundamento legal o social de la condonación..."></textarea>
                            <p v-if="form.errors.motivo" class="text-red-600 text-xs mt-1">{{ form.errors.motivo }}</p>
                        </div>
                        <div class="sm:col-span-2">
                            <label :class="labelCls">Observaciones Adicionales</label>
                            <textarea v-model="form.observaciones" rows="3" :class="inputCls" class="resize-none"
                                placeholder="Notas internas, condicionantes, seguimiento..."></textarea>
                        </div>
                    </div>
                </div>

                <!-- Historial de condonaciones previas -->
                <div v-if="condonaciones_previas.length > 0"
                    class="bg-white dark:bg-zinc-900 rounded-2xl border border-slate-100 dark:border-zinc-800 overflow-hidden shadow-sm">
                    <div class="px-5 py-4 border-b border-slate-100 dark:border-zinc-800 flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-slate-50 dark:bg-zinc-800 flex items-center justify-center text-slate-500">
                            <History :size="18" />
                        </div>
                        <h2 class="font-black text-slate-900 dark:text-white">Historial de Condonaciones</h2>
                        <span class="ml-auto text-xs font-black px-2 py-0.5 rounded-full bg-slate-100 text-slate-500 dark:bg-zinc-700 dark:text-zinc-400">
                            {{ condonaciones_previas.length }} previa(s)
                        </span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-slate-100 dark:border-zinc-800 bg-slate-50/60 dark:bg-zinc-800/50">
                                    <th class="px-4 py-3 text-left text-[10px] font-black text-slate-500 uppercase">Fecha</th>
                                    <th class="px-4 py-3 text-left text-[10px] font-black text-slate-500 uppercase">Tipo</th>
                                    <th class="px-4 py-3 text-right text-[10px] font-black text-slate-500 uppercase">Monto</th>
                                    <th class="px-4 py-3 text-left text-[10px] font-black text-slate-500 uppercase">Acuerdo</th>
                                    <th class="px-4 py-3 text-left text-[10px] font-black text-slate-500 uppercase">Autorizó</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50 dark:divide-zinc-800">
                                <tr v-for="(c, i) in condonaciones_previas" :key="i"
                                    class="hover:bg-slate-50 dark:hover:bg-zinc-800/30">
                                    <td class="px-4 py-3 text-xs text-slate-500">{{ c.fecha }}</td>
                                    <td class="px-4 py-3 text-xs">
                                        <span :class="['px-2 py-0.5 rounded-full text-[10px] font-black', c.tipo === 'Total' ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' : 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400']">
                                            {{ tipoLabels[c.tipo] ?? c.tipo }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right text-xs font-black text-slate-700 dark:text-zinc-200">{{ fmt(c.monto_total) }}</td>
                                    <td class="px-4 py-3 text-xs font-mono text-slate-500">{{ c.numero_acuerdo || '—' }}</td>
                                    <td class="px-4 py-3 text-xs text-slate-500">{{ c.autorizado_por || '—' }}</td>
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
                    <button type="submit" :disabled="form.processing || montoTotal <= 0"
                        class="inline-flex items-center gap-2 px-8 py-3 bg-red-700 hover:bg-red-800 text-white font-black rounded-xl text-sm transition-all shadow-lg shadow-red-900/20 disabled:opacity-60 active:scale-[0.98]">
                        <XOctagon :size="16" />
                        {{ form.processing ? 'Procesando...' : 'Confirmar Condonación' }}
                    </button>
                </div>

            </form>
        </div>
    </AppLayout>
</template>
