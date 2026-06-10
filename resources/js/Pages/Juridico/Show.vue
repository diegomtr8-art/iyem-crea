<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    ArrowLeft, Gavel, Scale, User, DollarSign, Clock,
    MapPin, Mail, CheckCircle2, AlertTriangle, Building2,
    FileText, Calendar, PhoneCall, Save
} from 'lucide-vue-next';

const props = defineProps<{
    expediente: {
        id: number;
        estatus: string;
        fecha_envio_juridico: string;
        tribunal?: string;
        juzgado?: string;
        numero_expediente_judicial?: string;
        monto_reclamado: number;
        observaciones?: string;
        dias_en_juridico: number;
        usuario: string;
        deuda_actual: number;
        cuotas_pendientes: number;
        ultimo_pago?: string;
        credito: { id: number; clave_contrato: string; monto_otorgado: number; fecha_entrega: string; modalidad?: string };
        acreditado: { id: number; nombre_completo: string; curp: string; rfc: string; municipio: string; correo: string };
    };
    historial_gestiones: Array<{ tipo_gestion: string; descripcion: string; resultado: string; fecha_gestion: string; usuario: string }>;
}>();

const form = useForm({
    estatus:                    props.expediente.estatus,
    tribunal:                   props.expediente.tribunal ?? '',
    juzgado:                    props.expediente.juzgado ?? '',
    numero_expediente_judicial: props.expediente.numero_expediente_judicial ?? '',
    monto_reclamado:            props.expediente.monto_reclamado,
    observaciones:              props.expediente.observaciones ?? '',
    // Campos de reestructuración
    nuevo_monto:                props.expediente.deuda_actual,
    nuevo_plazo_meses:          12,
    nueva_fecha_inicio:         new Date().toISOString().split('T')[0],
});

const submit = () => {
    form.put(route('juridico.update', props.expediente.id));
};

const estatusOpciones = [
    { value: 'Pendiente',      label: 'Pendiente' },
    { value: 'En_Demanda',    label: 'En Demanda' },
    { value: 'En_Platicas',   label: 'En Pláticas con el Acreditado' },
    { value: 'Reestructurada',label: 'Reestructurada (Nueva tabla de amortización)' },
    { value: 'Recuperada',    label: 'Recuperada / Liquidada' },
];

const estatusConfig: Record<string, { bg: string; text: string; border: string; icon: any }> = {
    Pendiente:     { bg: 'bg-slate-50 dark:bg-zinc-800', text: 'text-slate-600', border: 'border-slate-200 dark:border-zinc-700', icon: Clock },
    En_Demanda:    { bg: 'bg-red-50 dark:bg-red-900/10', text: 'text-red-700', border: 'border-red-200 dark:border-red-800/40', icon: Gavel },
    En_Platicas:   { bg: 'bg-blue-50 dark:bg-blue-900/10', text: 'text-blue-700', border: 'border-blue-200 dark:border-blue-800/40', icon: Scale },
    Reestructurada:{ bg: 'bg-amber-50 dark:bg-amber-900/10', text: 'text-amber-700', border: 'border-amber-200 dark:border-amber-800/40', icon: AlertTriangle },
    Recuperada:    { bg: 'bg-green-50 dark:bg-green-900/10', text: 'text-green-700', border: 'border-green-200 dark:border-green-800/40', icon: CheckCircle2 },
};

const fmt = (n: number) => '$' + n.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const cfg = estatusConfig[props.expediente.estatus];
</script>

<template>
    <AppLayout>
        <Head :title="`Jurídico — ${expediente.credito.clave_contrato}`" />

        <div class="p-6 space-y-6">
            <!-- Header -->
            <div class="flex items-center gap-4">
                <Link :href="route('juridico.index')"
                    class="p-2 rounded-xl bg-slate-100 dark:bg-zinc-800 text-slate-600 dark:text-zinc-400 hover:bg-slate-200 dark:hover:bg-zinc-700 transition-all">
                    <ArrowLeft size="18" />
                </Link>
                <div class="flex-1">
                    <h1 class="text-2xl font-black text-slate-900 dark:text-white flex items-center gap-3">
                        <Scale size="24" class="text-purple-700" /> Expediente Jurídico
                    </h1>
                    <p class="text-slate-500 dark:text-zinc-400 text-sm mt-0.5">
                        {{ expediente.acreditado.nombre_completo }} · {{ expediente.credito.clave_contrato }}
                        · <span class="font-semibold">{{ expediente.dias_en_juridico }} días en jurídico</span>
                    </p>
                </div>
            </div>

            <!-- Banner de estatus -->
            <div :class="['rounded-2xl border p-5 flex items-center gap-4', cfg.bg, cfg.border]">
                <div :class="['w-12 h-12 rounded-2xl flex items-center justify-center shrink-0', cfg.bg]">
                    <component :is="cfg.icon" :class="['size-6', cfg.text]" />
                </div>
                <div>
                    <p :class="['text-sm font-black uppercase tracking-wider', cfg.text]">{{ expediente.estatus.replace('_', ' ') }}</p>
                    <p class="text-xs text-slate-500 dark:text-zinc-400">
                        Enviado a jurídico el {{ expediente.fecha_envio_juridico }} por {{ expediente.usuario }}
                    </p>
                </div>
                <div class="ml-auto text-right">
                    <p :class="['text-2xl font-black', cfg.text]">{{ fmt(expediente.monto_reclamado) }}</p>
                    <p class="text-xs text-slate-500 dark:text-zinc-400">Monto reclamado</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- Formulario de actualización -->
                <div class="lg:col-span-2 space-y-5">
                    <form @submit.prevent="submit"
                        class="bg-white dark:bg-zinc-900 rounded-2xl border border-slate-100 dark:border-zinc-800 overflow-hidden shadow-sm">
                        <div class="px-5 py-4 border-b border-slate-100 dark:border-zinc-800">
                            <h3 class="font-black text-slate-900 dark:text-white flex items-center gap-2">
                                <FileText size="15" class="text-purple-700" /> Actualizar Expediente Jurídico
                            </h3>
                        </div>
                        <div class="p-5 space-y-5">
                            <!-- Estatus -->
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">Estatus del Proceso</label>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                    <label v-for="op in estatusOpciones" :key="op.value"
                                        :class="['flex items-center gap-3 p-3 rounded-xl border-2 cursor-pointer transition-all',
                                            form.estatus === op.value
                                                ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/20'
                                                : 'border-slate-200 dark:border-zinc-700 hover:border-slate-300 dark:hover:border-zinc-600']">
                                        <input type="radio" :value="op.value" v-model="form.estatus" class="accent-purple-600" />
                                        <span :class="['text-sm font-semibold', form.estatus === op.value ? 'text-purple-700 dark:text-purple-300' : 'text-slate-700 dark:text-zinc-300']">
                                            {{ op.label }}
                                        </span>
                                    </label>
                                </div>
                            </div>

                            <!-- Tribunal y juzgado -->
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">Tribunal</label>
                                    <input v-model="form.tribunal" type="text" placeholder="Tribunal Superior de Justicia de Yucatán"
                                        class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-zinc-700 dark:bg-zinc-800 text-sm focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 transition-all" />
                                </div>
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">Juzgado</label>
                                    <input v-model="form.juzgado" type="text" placeholder="Juzgado 3ro Civil"
                                        class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-zinc-700 dark:bg-zinc-800 text-sm focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 transition-all" />
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">No. Expediente Judicial</label>
                                    <input v-model="form.numero_expediente_judicial" type="text"
                                        class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-zinc-700 dark:bg-zinc-800 text-sm focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 transition-all" />
                                </div>
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">Monto Reclamado</label>
                                    <input v-model="form.monto_reclamado" type="number" step="0.01"
                                        class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-zinc-700 dark:bg-zinc-800 text-sm focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 transition-all" />
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">Observaciones / Notas del Proceso</label>
                                <textarea v-model="form.observaciones" rows="4"
                                    placeholder="Anota el avance del proceso legal, acuerdos alcanzados, próximas audiencias..."
                                    class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-zinc-700 dark:bg-zinc-800 text-sm focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 transition-all resize-none"></textarea>
                            </div>

                            <!-- Formulario de reestructuración -->
                            <div v-if="form.estatus === 'Reestructurada'"
                                class="bg-amber-50 dark:bg-amber-900/10 border border-amber-300 dark:border-amber-800/50 rounded-2xl p-5 space-y-4">
                                <div class="flex items-center gap-2">
                                    <AlertTriangle size="18" class="text-amber-600 shrink-0" />
                                    <p class="text-sm font-black text-amber-800 dark:text-amber-300">Términos de la Reestructuración</p>
                                </div>
                                <p class="text-xs text-amber-700 dark:text-amber-400">
                                    Al guardar, se condonarán las cuotas pendientes actuales y se generará una nueva tabla de amortización con los términos negociados. El crédito se reactivará automáticamente.
                                </p>
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-xs font-bold uppercase tracking-wider text-amber-700 dark:text-amber-400 mb-1.5">Nuevo Monto ($)</label>
                                        <input v-model="form.nuevo_monto" type="number" step="0.01" min="1"
                                            class="w-full px-3 py-2.5 rounded-xl border border-amber-300 dark:border-amber-700 dark:bg-zinc-800 text-sm focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 transition-all" />
                                        <p v-if="form.errors.nuevo_monto" class="text-red-600 text-xs mt-1">{{ form.errors.nuevo_monto }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold uppercase tracking-wider text-amber-700 dark:text-amber-400 mb-1.5">Nuevo Plazo (meses)</label>
                                        <select v-model="form.nuevo_plazo_meses"
                                            class="w-full px-3 py-2.5 rounded-xl border border-amber-300 dark:border-amber-700 dark:bg-zinc-800 text-sm focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 transition-all">
                                            <option v-for="p in [6,12,18,24,30,36,48,60]" :key="p" :value="p">{{ p }} meses</option>
                                        </select>
                                        <p v-if="form.errors.nuevo_plazo_meses" class="text-red-600 text-xs mt-1">{{ form.errors.nuevo_plazo_meses }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold uppercase tracking-wider text-amber-700 dark:text-amber-400 mb-1.5">Inicio de Pagos</label>
                                        <input v-model="form.nueva_fecha_inicio" type="date"
                                            class="w-full px-3 py-2.5 rounded-xl border border-amber-300 dark:border-amber-700 dark:bg-zinc-800 text-sm focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 transition-all" />
                                        <p v-if="form.errors.nueva_fecha_inicio" class="text-red-600 text-xs mt-1">{{ form.errors.nueva_fecha_inicio }}</p>
                                    </div>
                                </div>
                                <div class="bg-white dark:bg-zinc-900 rounded-xl border border-amber-200 dark:border-amber-800/30 p-3 text-xs text-slate-600 dark:text-zinc-400">
                                    <strong class="text-slate-800 dark:text-zinc-200">Deuda actual:</strong>
                                    ${{ expediente.deuda_actual.toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}
                                    · <strong class="text-slate-800 dark:text-zinc-200">Cuotas pendientes:</strong>
                                    {{ expediente.cuotas_pendientes }}
                                </div>
                            </div>

                            <div class="flex justify-end pt-2">
                                <button type="submit" :disabled="form.processing"
                                    class="inline-flex items-center gap-2 px-6 py-2.5 bg-purple-700 hover:bg-purple-800 text-white font-bold rounded-xl text-sm transition-all disabled:opacity-50">
                                    <Save size="15" /> Guardar Cambios
                                </button>
                            </div>
                        </div>
                    </form>

                    <!-- Historial de gestiones de cobranza previas -->
                    <div v-if="historial_gestiones.length > 0"
                        class="bg-white dark:bg-zinc-900 rounded-2xl border border-slate-100 dark:border-zinc-800 overflow-hidden shadow-sm">
                        <div class="px-5 py-4 border-b border-slate-100 dark:border-zinc-800">
                            <h3 class="font-black text-slate-900 dark:text-white flex items-center gap-2">
                                <PhoneCall size="15" class="text-slate-500" /> Últimas Gestiones de Cobranza
                            </h3>
                        </div>
                        <div class="divide-y divide-slate-50 dark:divide-zinc-800">
                            <div v-for="g in historial_gestiones" :key="g.fecha_gestion + g.tipo_gestion" class="p-4 flex gap-3">
                                <div class="w-8 h-8 rounded-xl bg-slate-100 dark:bg-zinc-800 flex items-center justify-center shrink-0 text-xs font-bold text-slate-500">
                                    {{ g.tipo_gestion[0] }}
                                </div>
                                <div class="min-w-0">
                                    <p class="text-sm font-bold text-slate-900 dark:text-white">{{ g.tipo_gestion }} · <span class="font-normal text-slate-500">{{ g.resultado.replace('_', ' ') }}</span></p>
                                    <p class="text-xs text-slate-500 dark:text-zinc-400 mt-0.5">{{ g.descripcion }}</p>
                                    <p class="text-[10px] text-slate-400 mt-1">{{ g.fecha_gestion }} · {{ g.usuario }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Columna derecha -->
                <div class="space-y-5">
                    <!-- Datos acreditado -->
                    <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-slate-100 dark:border-zinc-800 overflow-hidden shadow-sm">
                        <div class="px-5 py-4 border-b border-slate-100 dark:border-zinc-800">
                            <h3 class="font-black text-slate-900 dark:text-white flex items-center gap-2">
                                <User size="15" class="text-purple-700" /> Acreditado
                            </h3>
                        </div>
                        <div class="p-5 space-y-3">
                            <p class="font-bold text-slate-900 dark:text-white">{{ expediente.acreditado.nombre_completo }}</p>
                            <div class="space-y-1.5 text-xs">
                                <div class="flex items-center gap-2 text-slate-600 dark:text-zinc-400">
                                    <MapPin size="13" class="text-slate-400 shrink-0" /> {{ expediente.acreditado.municipio }}
                                </div>
                                <div class="flex items-center gap-2 text-slate-600 dark:text-zinc-400">
                                    <Mail size="13" class="text-slate-400 shrink-0" /> {{ expediente.acreditado.correo }}
                                </div>
                                <div class="font-mono text-slate-500">CURP: {{ expediente.acreditado.curp }}</div>
                                <div class="font-mono text-slate-500">RFC: {{ expediente.acreditado.rfc || '—' }}</div>
                            </div>
                            <Link :href="route('acreditados.show', expediente.acreditado.id)"
                                class="flex items-center justify-center gap-2 w-full py-2 border border-slate-200 dark:border-zinc-700 rounded-xl text-xs font-bold text-slate-600 hover:border-purple-300 hover:text-purple-700 transition-colors mt-2">
                                Ver en módulo operativo
                            </Link>
                        </div>
                    </div>

                    <!-- Datos del crédito -->
                    <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-slate-100 dark:border-zinc-800 overflow-hidden shadow-sm">
                        <div class="px-5 py-4 border-b border-slate-100 dark:border-zinc-800">
                            <h3 class="font-black text-slate-900 dark:text-white flex items-center gap-2">
                                <DollarSign size="15" class="text-purple-700" /> Crédito
                            </h3>
                        </div>
                        <div class="p-5 space-y-3 text-sm">
                            <div>
                                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Contrato</p>
                                <p class="font-mono font-bold text-slate-900 dark:text-white">{{ expediente.credito.clave_contrato }}</p>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Monto Original</p>
                                    <p class="font-bold text-slate-900 dark:text-white">{{ fmt(expediente.credito.monto_otorgado) }}</p>
                                </div>
                                <div>
                                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Deuda Actual</p>
                                    <p class="font-bold text-red-700">{{ fmt(expediente.deuda_actual) }}</p>
                                </div>
                            </div>
                            <div>
                                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Modalidad</p>
                                <p class="font-semibold text-slate-700 dark:text-zinc-300">{{ expediente.credito.modalidad }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Fecha Entrega</p>
                                <p class="font-semibold text-slate-700 dark:text-zinc-300">{{ expediente.credito.fecha_entrega }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Último Pago</p>
                                <p class="font-semibold text-slate-700 dark:text-zinc-300">{{ expediente.ultimo_pago ?? 'Sin pagos' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
