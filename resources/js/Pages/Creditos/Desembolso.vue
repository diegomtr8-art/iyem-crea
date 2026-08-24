<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Banknote, CheckCircle2, Upload, AlertCircle, Building2 } from 'lucide-vue-next';

const props = defineProps<{
    credito: {
        id: number;
        clave_contrato: string;
        monto_otorgado: number;
        fecha_entrega: string;
        estatus: string;
        acreditado_nombre: string;
        acreditado_id: number;
        modalidad: string;
    };
}>();

const form = useForm({
    fecha_desembolso:   props.credito.fecha_entrega ?? new Date().toISOString().split('T')[0],
    monto_desembolsado: props.credito.monto_otorgado ?? '',
    forma_desembolso:   'Transferencia',
    banco_destino:      '',
    cuenta_destino:     '',
    folio_cheque:       '',
    referencia:         '',
    observaciones:      '',
    comprobante:        null as File | null,
});

const fmt = (val: number | string) =>
    new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN', minimumFractionDigits: 2 })
        .format(parseFloat(String(val)) || 0);

const onFile = (e: Event) => {
    const target = e.target as HTMLInputElement;
    form.comprobante = target.files?.[0] ?? null;
};

const submit = () =>
    form.post(route('creditos.desembolso.store', props.credito.id), {
        forceFormData: true,
    });

const inputCls = 'w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-zinc-700 dark:bg-zinc-800 text-sm focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-all text-slate-900 dark:text-white';
const labelCls = 'block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5';
</script>

<template>
    <AppLayout>
        <Head :title="`Desembolso — ${credito.clave_contrato}`" />

        <div class="p-6 max-w-3xl space-y-6">

            <!-- Header -->
            <div class="flex items-center gap-4">
                <Link :href="route('acreditados.show', credito.acreditado_id)"
                    class="p-2 rounded-xl bg-slate-100 dark:bg-zinc-800 text-slate-600 hover:bg-slate-200 dark:hover:bg-zinc-700 transition-all">
                    <ArrowLeft :size="18" />
                </Link>
                <div>
                    <h1 class="text-2xl font-black text-slate-900 dark:text-white flex items-center gap-3">
                        <Banknote :size="24" class="text-red-700" /> Registrar Desembolso
                    </h1>
                    <p class="text-slate-500 dark:text-zinc-400 text-sm mt-0.5">
                        Contrato {{ credito.clave_contrato }} — {{ credito.acreditado_nombre }}
                    </p>
                </div>
            </div>

            <!-- Banner informativo -->
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800/40 rounded-2xl p-5 flex items-start gap-4">
                <AlertCircle :size="20" class="text-blue-600 shrink-0 mt-0.5" />
                <div>
                    <p class="font-black text-blue-800 dark:text-blue-300">Monto autorizado: {{ fmt(credito.monto_otorgado) }}</p>
                    <p class="text-sm text-blue-700 dark:text-blue-400 mt-0.5">
                        Modalidad: <strong>{{ credito.modalidad }}</strong>. Registra la fecha real en que se entregó el recurso al acreditado.
                    </p>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-6">

                <!-- Datos del desembolso -->
                <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-slate-100 dark:border-zinc-800 overflow-hidden shadow-sm">
                    <div class="px-5 py-4 border-b border-slate-100 dark:border-zinc-800 flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-red-50 dark:bg-red-900/20 flex items-center justify-center text-red-700">
                            <Banknote :size="18" />
                        </div>
                        <h2 class="font-black text-slate-900 dark:text-white">Datos del Desembolso</h2>
                    </div>
                    <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label :class="labelCls">Fecha de Desembolso <span class="text-red-500">*</span></label>
                            <input v-model="form.fecha_desembolso" type="date" required :class="inputCls" />
                            <p v-if="form.errors.fecha_desembolso" class="text-red-600 text-xs mt-1">{{ form.errors.fecha_desembolso }}</p>
                        </div>
                        <div>
                            <label :class="labelCls">Monto Desembolsado ($) <span class="text-red-500">*</span></label>
                            <input v-model="form.monto_desembolsado" type="number" step="0.01" min="1" required :class="inputCls" />
                            <p v-if="form.errors.monto_desembolsado" class="text-red-600 text-xs mt-1">{{ form.errors.monto_desembolsado }}</p>
                        </div>
                        <div class="sm:col-span-2">
                            <label :class="labelCls">Forma de Desembolso <span class="text-red-500">*</span></label>
                            <select v-model="form.forma_desembolso" required :class="inputCls">
                                <option value="Transferencia">Transferencia Bancaria</option>
                                <option value="Cheque">Cheque</option>
                                <option value="Efectivo">Efectivo</option>
                                <option value="Deposito">Depósito</option>
                            </select>
                            <p v-if="form.errors.forma_desembolso" class="text-red-600 text-xs mt-1">{{ form.errors.forma_desembolso }}</p>
                        </div>
                    </div>
                </div>

                <!-- Datos bancarios (condicional) -->
                <div v-if="form.forma_desembolso === 'Transferencia' || form.forma_desembolso === 'Deposito'"
                    class="bg-white dark:bg-zinc-900 rounded-2xl border border-slate-100 dark:border-zinc-800 overflow-hidden shadow-sm">
                    <div class="px-5 py-4 border-b border-slate-100 dark:border-zinc-800 flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-red-50 dark:bg-red-900/20 flex items-center justify-center text-red-700">
                            <Building2 :size="18" />
                        </div>
                        <h2 class="font-black text-slate-900 dark:text-white">Datos Bancarios</h2>
                        <span class="text-xs text-slate-400 ml-auto">Opcional</span>
                    </div>
                    <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label :class="labelCls">Banco Destino</label>
                            <input v-model="form.banco_destino" type="text" placeholder="Ej. BBVA, Banorte..." :class="inputCls" />
                        </div>
                        <div>
                            <label :class="labelCls">Cuenta / CLABE Destino</label>
                            <input v-model="form.cuenta_destino" type="text" maxlength="50" :class="inputCls" class="font-mono" />
                        </div>
                        <div class="sm:col-span-2">
                            <label :class="labelCls">Referencia / Número de Operación</label>
                            <input v-model="form.referencia" type="text" maxlength="100" :class="inputCls" />
                        </div>
                    </div>
                </div>

                <!-- Folio de cheque (solo cheque) -->
                <div v-if="form.forma_desembolso === 'Cheque'"
                    class="bg-white dark:bg-zinc-900 rounded-2xl border border-slate-100 dark:border-zinc-800 overflow-hidden shadow-sm">
                    <div class="px-5 py-4 border-b border-slate-100 dark:border-zinc-800 flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-red-50 dark:bg-red-900/20 flex items-center justify-center text-red-700">
                            <CheckCircle2 :size="18" />
                        </div>
                        <h2 class="font-black text-slate-900 dark:text-white">Datos del Cheque</h2>
                    </div>
                    <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label :class="labelCls">Folio del Cheque</label>
                            <input v-model="form.folio_cheque" type="text" maxlength="50" :class="inputCls" class="font-mono" />
                        </div>
                        <div>
                            <label :class="labelCls">Banco Emisor</label>
                            <input v-model="form.banco_destino" type="text" :class="inputCls" />
                        </div>
                    </div>
                </div>

                <!-- Comprobante y observaciones -->
                <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-slate-100 dark:border-zinc-800 overflow-hidden shadow-sm">
                    <div class="px-5 py-4 border-b border-slate-100 dark:border-zinc-800 flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-red-50 dark:bg-red-900/20 flex items-center justify-center text-red-700">
                            <Upload :size="18" />
                        </div>
                        <h2 class="font-black text-slate-900 dark:text-white">Comprobante y Notas</h2>
                        <span class="text-xs text-slate-400 ml-auto">Opcional</span>
                    </div>
                    <div class="p-5 space-y-5">
                        <div>
                            <label :class="labelCls">Comprobante de Pago (PDF, JPG, PNG — máx. 5 MB)</label>
                            <div class="mt-1 flex items-center gap-3">
                                <label class="cursor-pointer flex items-center gap-2 px-4 py-3 rounded-xl border-2 border-dashed border-slate-200 dark:border-zinc-700 hover:border-red-400 transition-all text-sm text-slate-500 dark:text-zinc-400 w-full">
                                    <Upload :size="16" class="shrink-0" />
                                    <span v-if="form.comprobante">{{ (form.comprobante as File).name }}</span>
                                    <span v-else>Seleccionar archivo...</span>
                                    <input type="file" class="hidden" accept=".pdf,.jpg,.jpeg,.png" @change="onFile" />
                                </label>
                            </div>
                            <p v-if="form.errors.comprobante" class="text-red-600 text-xs mt-1">{{ form.errors.comprobante }}</p>
                        </div>
                        <div>
                            <label :class="labelCls">Observaciones</label>
                            <textarea v-model="form.observaciones" rows="3" :class="inputCls" class="resize-none"
                                placeholder="Notas adicionales sobre el desembolso..."></textarea>
                        </div>
                    </div>
                </div>

                <!-- Acciones -->
                <div class="flex flex-col-reverse sm:flex-row justify-end gap-3 sm:gap-4">
                    <Link :href="route('acreditados.show', credito.acreditado_id)"
                        class="w-full sm:w-auto text-center px-5 py-3 rounded-xl bg-slate-100 dark:bg-zinc-800 text-slate-700 dark:text-zinc-300 font-bold text-sm hover:bg-slate-200 dark:hover:bg-zinc-700 transition-all">
                        Cancelar
                    </Link>
                    <button type="submit" :disabled="form.processing"
                        class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-8 py-3 bg-red-700 hover:bg-red-800 text-white font-black rounded-xl text-sm transition-all shadow-lg shadow-red-900/20 disabled:opacity-60 active:scale-[0.98]">
                        <Banknote :size="16" />
                        {{ form.processing ? 'Registrando...' : 'Confirmar Desembolso' }}
                    </button>
                </div>

            </form>
        </div>
    </AppLayout>
</template>
