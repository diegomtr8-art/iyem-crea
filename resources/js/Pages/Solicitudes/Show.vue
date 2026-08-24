<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import {
    ArrowLeft, User, Briefcase, FileText, CheckCircle2, XCircle,
    Clock, ExternalLink, Send, Bell, ChevronDown, CreditCard, Sparkles,
    AlertCircle, Eye, ClipboardList, UserCog
} from 'lucide-vue-next';

const props = defineProps<{
    solicitud: {
        id: number;
        estatus: string;
        observaciones?: string;
        nombre_completo?: string;
        curp?: string;
        rfc?: string;
        fecha_nacimiento?: string;
        sexo?: string;
        municipio?: string;
        direccion?: string;
        telefono?: string;
        correo?: string;
        mayahablante: boolean;
        discapacidad: boolean;
        modalidad?: string;
        giro_comercial?: string;
        destino_credito?: string;
        descripcion_negocio?: string;
        monto_solicitado?: number;
        alta_sat: boolean;
        created_at: string;
        updated_at: string;
        user_email?: string;
        user_id: number;
        acreditado_id?: number;
        credito_id?: number;
        documentos: Array<{
            id: number;
            tipo_documento: string;
            label: string;
            nombre_original: string;
            estatus: string;
            observacion?: string;
            url: string;
            updated_at: string;
        }>;
        asignado_a?: string;
        fecha_asignacion?: string;
        analisis?: {
            recomendacion?: string;
            score_cualitativo?: number;
            puntaje_total?: number;
            analista?: string;
            fecha_analisis?: string;
            monto_recomendado?: number;
        } | null;
    };
    operativos: Array<{ id: number; name: string }>;
}>();

const formAsignar = useForm({ asignado_a: '' });
const asignar = () => formAsignar.post(route('solicitudes.asignar', props.solicitud.id));

const formEstatus = useForm({ estatus: props.solicitud.estatus, observaciones: props.solicitud.observaciones ?? '' });
const cambiarEstatus = () => formEstatus.post(route('solicitudes.cambiar-estatus', props.solicitud.id));

const formDoc = useForm({ estatus: '', observacion: '' });
const docActivo = ref<number | null>(null);
const revisarDoc = (docId: number) => {
    formDoc.post(route('solicitudes.revisar-documento', docId), {
        onSuccess: () => { docActivo.value = null; formDoc.reset(); }
    });
};

const showAnuncio = ref(false);
const formAnuncio = useForm({ titulo: '', mensaje: '', tipo: 'info' });
const enviarAnuncio = () => formAnuncio.post(route('solicitudes.enviar-anuncio', props.solicitud.id), {
    onSuccess: () => { showAnuncio.value = false; formAnuncio.reset(); }
});

const docEstilo: Record<string, { bg: string; badge: string; icon: any }> = {
    Aprobado:  { bg: 'bg-green-50 dark:bg-green-900/10 border-green-200 dark:border-green-800/40', badge: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400', icon: CheckCircle2 },
    Pendiente: { bg: 'bg-amber-50 dark:bg-amber-900/10 border-amber-200 dark:border-amber-800/40', badge: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400', icon: Clock },
    Rechazado: { bg: 'bg-red-50 dark:bg-red-900/10 border-red-200 dark:border-red-800/40', badge: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400', icon: XCircle },
};

const estatusConfig: Record<string, { label: string; bg: string; text: string; border: string }> = {
    Borrador:                { label: 'Borrador',                bg: 'bg-slate-100 dark:bg-zinc-800',         text: 'text-slate-600 dark:text-zinc-400',   border: 'border-slate-200 dark:border-zinc-700' },
    Enviada:                 { label: 'Enviada',                 bg: 'bg-blue-50 dark:bg-blue-900/20',        text: 'text-blue-700 dark:text-blue-400',    border: 'border-blue-200 dark:border-blue-800/40' },
    En_Revision:             { label: 'En Revisión',             bg: 'bg-amber-50 dark:bg-amber-900/20',      text: 'text-amber-700 dark:text-amber-400',  border: 'border-amber-200 dark:border-amber-800/40' },
    Documentacion_Incompleta:{ label: 'Doc. Incompleta',         bg: 'bg-orange-50 dark:bg-orange-900/20',   text: 'text-orange-700 dark:text-orange-400',border: 'border-orange-200 dark:border-orange-800/40' },
    Aprobada:                { label: 'Aprobada',                bg: 'bg-green-50 dark:bg-green-900/20',      text: 'text-green-700 dark:text-green-400',  border: 'border-green-200 dark:border-green-800/40' },
    Rechazada:               { label: 'Rechazada',               bg: 'bg-red-50 dark:bg-red-900/20',         text: 'text-red-700 dark:text-red-400',      border: 'border-red-200 dark:border-red-800/40' },
};

const estatusOpciones = [
    { value: 'En_Revision', label: 'En Revisión' },
    { value: 'Documentacion_Incompleta', label: 'Documentación Incompleta' },
    { value: 'Aprobada', label: 'Aprobada ✓' },
    { value: 'Rechazada', label: 'Rechazada ✗' },
];

const puedeRevisarDocs = ['Enviada', 'En_Revision', 'Documentacion_Incompleta'].includes(props.solicitud.estatus);

const docsAprobados = props.solicitud.documentos.filter(d => d.estatus === 'Aprobado').length;
const docsRechazados = props.solicitud.documentos.filter(d => d.estatus === 'Rechazado').length;
const docsPendientes = props.solicitud.documentos.filter(d => d.estatus === 'Pendiente').length;

const inputClass = 'w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-zinc-700 dark:bg-zinc-800 text-sm focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-all text-slate-900 dark:text-white';
const labelClass = 'block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5';
</script>

<template>
    <AppLayout>
        <Head :title="`Solicitud #${solicitud.id} — CREA`" />

        <div class="p-6 space-y-5 max-w-5xl">

            <!-- Breadcrumb -->
            <div class="flex items-center gap-3">
                <Link :href="route('solicitudes.index')"
                    class="flex items-center gap-1.5 text-sm font-medium text-slate-500 hover:text-red-700 transition-colors">
                    <ArrowLeft size="16" /> Solicitudes
                </Link>
                <span class="text-slate-300 dark:text-zinc-700">/</span>
                <span class="text-sm font-bold text-slate-900 dark:text-white">#{{ solicitud.id }} — {{ solicitud.nombre_completo || 'Sin nombre' }}</span>
                <span :class="['ml-auto text-xs font-black px-3 py-1.5 rounded-full border', estatusConfig[solicitud.estatus]?.bg, estatusConfig[solicitud.estatus]?.text, estatusConfig[solicitud.estatus]?.border]">
                    {{ estatusConfig[solicitud.estatus]?.label ?? solicitud.estatus }}
                </span>
            </div>

            <!-- Banner: Registrar Crédito (solo cuando Aprobada y sin crédito) -->
            <div v-if="solicitud.estatus === 'Aprobada' && !solicitud.credito_id"
                class="bg-green-50 dark:bg-green-900/20 border border-green-300 dark:border-green-700/50 rounded-2xl p-5 flex items-center justify-between gap-4">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-green-100 dark:bg-green-900/40 flex items-center justify-center text-green-600 shrink-0">
                        <Sparkles size="24" />
                    </div>
                    <div>
                        <p class="font-black text-green-800 dark:text-green-200 text-lg">¡Solicitud Aprobada!</p>
                        <p class="text-sm text-green-700 dark:text-green-400 mt-0.5">
                            Esta solicitud fue aprobada. Haz clic en "Registrar Crédito" para crear el expediente, la tabla de amortización y notificar al ciudadano.
                        </p>
                    </div>
                </div>
                <Link :href="route('solicitudes.registrar-credito', solicitud.id)"
                    class="inline-flex items-center gap-2 px-6 py-3 bg-green-700 hover:bg-green-800 text-white font-black rounded-xl text-sm transition-all shadow-lg shadow-green-900/20 shrink-0 whitespace-nowrap">
                    <CreditCard size="16" />
                    Registrar Crédito
                </Link>
            </div>

            <!-- Banner: crédito ya registrado -->
            <div v-else-if="solicitud.estatus === 'Aprobada' && solicitud.credito_id"
                class="bg-blue-50 dark:bg-blue-900/10 border border-blue-200 dark:border-blue-800/40 rounded-2xl p-4 flex items-center gap-4">
                <CreditCard size="20" class="text-blue-600 shrink-0" />
                <div class="flex-1">
                    <p class="font-bold text-blue-800 dark:text-blue-200 text-sm">Crédito Registrado</p>
                    <p class="text-xs text-blue-600 dark:text-blue-400">Esta solicitud ya tiene un crédito activo asignado.</p>
                </div>
                <Link :href="route('operaciones.index', solicitud.acreditado_id)"
                    class="flex items-center gap-1.5 text-sm font-bold text-blue-700 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 transition-colors">
                    Ver expediente <ExternalLink size="14" />
                </Link>
            </div>

            <!-- Banner: rechazada -->
            <div v-if="solicitud.estatus === 'Rechazada' && solicitud.observaciones"
                class="bg-red-50 dark:bg-red-900/10 border border-red-200 dark:border-red-800/40 rounded-2xl p-4 flex items-start gap-3">
                <AlertCircle size="18" class="text-red-600 shrink-0 mt-0.5" />
                <div>
                    <p class="font-bold text-red-800 dark:text-red-200 text-sm">Motivo de rechazo</p>
                    <p class="text-sm text-red-700 dark:text-red-400 mt-0.5">{{ solicitud.observaciones }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- Columna principal -->
                <div class="lg:col-span-2 space-y-5">

                    <!-- Datos personales -->
                    <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-slate-100 dark:border-zinc-800 overflow-hidden shadow-sm">
                        <div class="px-5 py-4 border-b border-slate-100 dark:border-zinc-800 flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-red-50 dark:bg-red-900/20 flex items-center justify-center text-red-700"><User size="18" /></div>
                            <h2 class="font-black text-slate-900 dark:text-white">Datos Personales</h2>
                        </div>
                        <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                            <div><p class="text-xs font-bold text-slate-400 uppercase mb-0.5">Nombre</p><p class="font-semibold text-slate-900 dark:text-white">{{ solicitud.nombre_completo || '—' }}</p></div>
                            <div><p class="text-xs font-bold text-slate-400 uppercase mb-0.5">CURP</p><p class="font-mono text-slate-900 dark:text-white text-xs">{{ solicitud.curp || '—' }}</p></div>
                            <div><p class="text-xs font-bold text-slate-400 uppercase mb-0.5">RFC</p><p class="font-mono text-slate-900 dark:text-white text-xs">{{ solicitud.rfc || '—' }}</p></div>
                            <div><p class="text-xs font-bold text-slate-400 uppercase mb-0.5">F. Nacimiento</p><p class="text-slate-900 dark:text-white">{{ solicitud.fecha_nacimiento || '—' }}</p></div>
                            <div><p class="text-xs font-bold text-slate-400 uppercase mb-0.5">Sexo</p><p class="text-slate-900 dark:text-white">{{ solicitud.sexo === 'M' ? 'Masculino' : solicitud.sexo === 'F' ? 'Femenino' : '—' }}</p></div>
                            <div><p class="text-xs font-bold text-slate-400 uppercase mb-0.5">Municipio</p><p class="text-slate-900 dark:text-white">{{ solicitud.municipio || '—' }}</p></div>
                            <div><p class="text-xs font-bold text-slate-400 uppercase mb-0.5">Teléfono</p><p class="text-slate-900 dark:text-white">{{ solicitud.telefono || '—' }}</p></div>
                            <div><p class="text-xs font-bold text-slate-400 uppercase mb-0.5">Correo</p><p class="text-slate-900 dark:text-white">{{ solicitud.correo || '—' }}</p></div>
                            <div class="col-span-2"><p class="text-xs font-bold text-slate-400 uppercase mb-0.5">Dirección</p><p class="text-slate-900 dark:text-white">{{ solicitud.direccion || '—' }}</p></div>
                            <div class="col-span-2 flex gap-3">
                                <span :class="['px-2.5 py-1 rounded-lg text-xs font-bold', solicitud.mayahablante ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-400' : 'bg-slate-100 text-slate-500 dark:bg-zinc-800']">
                                    {{ solicitud.mayahablante ? '✓ Maya-hablante' : 'No maya-hablante' }}
                                </span>
                                <span :class="['px-2.5 py-1 rounded-lg text-xs font-bold', solicitud.discapacidad ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/20 dark:text-blue-400' : 'bg-slate-100 text-slate-500 dark:bg-zinc-800']">
                                    {{ solicitud.discapacidad ? '✓ Discapacidad' : 'Sin discapacidad' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Datos del negocio -->
                    <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-slate-100 dark:border-zinc-800 overflow-hidden shadow-sm">
                        <div class="px-5 py-4 border-b border-slate-100 dark:border-zinc-800 flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-red-50 dark:bg-red-900/20 flex items-center justify-center text-red-700"><Briefcase size="18" /></div>
                            <h2 class="font-black text-slate-900 dark:text-white">Negocio / Proyecto</h2>
                        </div>
                        <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                            <div><p class="text-xs font-bold text-slate-400 uppercase mb-0.5">Modalidad</p><p class="font-semibold text-slate-900 dark:text-white">{{ solicitud.modalidad || '—' }}</p></div>
                            <div><p class="text-xs font-bold text-slate-400 uppercase mb-0.5">Giro Comercial</p><p class="text-slate-900 dark:text-white">{{ solicitud.giro_comercial || '—' }}</p></div>
                            <div class="col-span-2"><p class="text-xs font-bold text-slate-400 uppercase mb-0.5">Destino del Crédito</p><p class="text-slate-900 dark:text-white">{{ solicitud.destino_credito || '—' }}</p></div>
                            <div class="col-span-2"><p class="text-xs font-bold text-slate-400 uppercase mb-0.5">Descripción del Negocio</p><p class="text-slate-900 dark:text-white leading-relaxed">{{ solicitud.descripcion_negocio || '—' }}</p></div>
                            <div>
                                <p class="text-xs font-bold text-slate-400 uppercase mb-0.5">Monto Solicitado</p>
                                <p class="font-black text-lg text-slate-900 dark:text-white">${{ solicitud.monto_solicitado ? Number(solicitud.monto_solicitado).toLocaleString('es-MX', {minimumFractionDigits: 2}) : '—' }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-slate-400 uppercase mb-0.5">Alta SAT</p>
                                <span :class="['px-2.5 py-1 rounded-lg text-xs font-bold', solicitud.alta_sat ? 'bg-green-100 text-green-700 dark:bg-green-900/20 dark:text-green-400' : 'bg-slate-100 text-slate-500 dark:bg-zinc-800']">
                                    {{ solicitud.alta_sat ? '✓ Sí, dado de alta' : 'No dado de alta' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Documentos -->
                    <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-slate-100 dark:border-zinc-800 overflow-hidden shadow-sm">
                        <div class="px-5 py-4 border-b border-slate-100 dark:border-zinc-800 flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-red-50 dark:bg-red-900/20 flex items-center justify-center text-red-700"><FileText size="18" /></div>
                            <h2 class="font-black text-slate-900 dark:text-white">Documentos</h2>
                            <div v-if="solicitud.documentos.length > 0" class="ml-auto flex items-center gap-2">
                                <span v-if="docsAprobados > 0" class="text-[10px] font-black px-2 py-0.5 rounded-full bg-green-100 text-green-700">{{ docsAprobados }} Aprobados</span>
                                <span v-if="docsPendientes > 0" class="text-[10px] font-black px-2 py-0.5 rounded-full bg-amber-100 text-amber-700">{{ docsPendientes }} Pendientes</span>
                                <span v-if="docsRechazados > 0" class="text-[10px] font-black px-2 py-0.5 rounded-full bg-red-100 text-red-700">{{ docsRechazados }} Rechazados</span>
                            </div>
                        </div>

                        <!-- Instrucción cuando se puede revisar -->
                        <div v-if="puedeRevisarDocs && solicitud.documentos.length > 0"
                            class="px-5 py-3 bg-amber-50 dark:bg-amber-900/10 border-b border-amber-100 dark:border-amber-800/30 flex items-center gap-2 text-xs text-amber-700 dark:text-amber-400">
                            <Eye size="14" class="shrink-0" />
                            Revisa cada documento haciendo clic en el ícono de vista previa y luego aprueba o rechaza.
                        </div>

                        <div class="p-5 space-y-3">
                            <div v-if="solicitud.documentos.length === 0" class="py-10 text-center">
                                <FileText size="32" class="mx-auto text-slate-300 dark:text-zinc-600 mb-2" />
                                <p class="text-slate-400 text-sm">El ciudadano aún no ha subido documentos.</p>
                            </div>

                            <div v-for="doc in solicitud.documentos" :key="doc.id"
                                :class="['rounded-2xl border p-4 space-y-3 transition-all', docEstilo[doc.estatus]?.bg]">

                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex items-start gap-3">
                                        <div :class="['w-8 h-8 rounded-xl flex items-center justify-center shrink-0 mt-0.5', doc.estatus === 'Aprobado' ? 'bg-green-100' : doc.estatus === 'Rechazado' ? 'bg-red-100' : 'bg-amber-100']">
                                            <component :is="docEstilo[doc.estatus]?.icon" size="15"
                                                :class="doc.estatus === 'Aprobado' ? 'text-green-600' : doc.estatus === 'Rechazado' ? 'text-red-600' : 'text-amber-600'" />
                                        </div>
                                        <div>
                                            <p class="text-sm font-bold text-slate-900 dark:text-white">{{ doc.label }}</p>
                                            <p class="text-xs text-slate-400 dark:text-zinc-500 mt-0.5">{{ doc.nombre_original }}</p>
                                            <p class="text-xs text-slate-400 dark:text-zinc-500">Subido {{ doc.updated_at }}</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2 shrink-0">
                                        <span :class="['text-[10px] font-black px-2.5 py-1 rounded-full border', docEstilo[doc.estatus]?.badge]">{{ doc.estatus }}</span>
                                        <a :href="doc.url" target="_blank"
                                            class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-white dark:bg-zinc-800 rounded-xl border border-slate-200 dark:border-zinc-700 text-slate-500 hover:text-red-700 hover:border-red-300 transition-all text-xs font-bold">
                                            <Eye size="12" /> Ver
                                        </a>
                                    </div>
                                </div>

                                <p v-if="doc.observacion" class="text-xs text-orange-700 dark:text-orange-400 bg-orange-50 dark:bg-orange-900/10 px-3 py-2 rounded-xl border border-orange-200 dark:border-orange-800/30">
                                    <strong>Motivo:</strong> {{ doc.observacion }}
                                </p>

                                <!-- Panel de revisión -->
                                <div v-if="docActivo === doc.id" class="pt-3 space-y-3 border-t border-slate-200 dark:border-zinc-700">
                                    <select v-model="formDoc.estatus" :class="inputClass">
                                        <option value="" disabled>Seleccionar decisión...</option>
                                        <option value="Aprobado">✓ Aprobar documento</option>
                                        <option value="Rechazado">✗ Rechazar documento</option>
                                    </select>
                                    <textarea v-if="formDoc.estatus === 'Rechazado'" v-model="formDoc.observacion"
                                        placeholder="Motivo del rechazo (requerido)" rows="2"
                                        :class="[inputClass, 'resize-none']"></textarea>
                                    <div class="flex flex-col sm:flex-row gap-2">
                                        <button @click="revisarDoc(doc.id)" :disabled="formDoc.processing || (formDoc.estatus === 'Rechazado' && !formDoc.observacion)"
                                            class="w-full sm:w-auto px-4 py-2.5 bg-red-700 hover:bg-red-800 disabled:opacity-50 text-white font-bold rounded-xl text-sm transition-all">
                                            Guardar Decisión
                                        </button>
                                        <button @click="docActivo = null"
                                            class="w-full sm:w-auto px-4 py-2.5 bg-slate-100 dark:bg-zinc-800 text-slate-700 dark:text-zinc-300 font-bold rounded-xl text-sm hover:bg-slate-200 dark:hover:bg-zinc-700 transition-all">
                                            Cancelar
                                        </button>
                                    </div>
                                </div>
                                <button v-else-if="puedeRevisarDocs"
                                    @click="docActivo = doc.id; formDoc.estatus = ''; formDoc.observacion = ''"
                                    :class="['text-xs font-bold transition-colors', doc.estatus === 'Aprobado' ? 'text-green-600 hover:text-green-700' : doc.estatus === 'Rechazado' ? 'text-red-600 hover:text-red-700' : 'text-amber-600 hover:text-amber-700']">
                                    {{ doc.estatus === 'Aprobado' ? 'Re-evaluar documento →' : doc.estatus === 'Rechazado' ? 'Cambiar decisión →' : 'Revisar documento →' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Columna lateral: Gestión -->
                <div class="space-y-5">

                    <!-- Meta info -->
                    <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-slate-100 dark:border-zinc-800 p-5 space-y-3 shadow-sm">
                        <h3 class="font-black text-slate-900 dark:text-white text-sm">Información</h3>
                        <div class="space-y-2.5 text-xs text-slate-500 dark:text-zinc-400">
                            <div class="flex flex-col gap-0.5">
                                <span class="font-bold text-slate-400 uppercase tracking-wide text-[10px]">Cuenta ciudadano</span>
                                <span class="text-slate-700 dark:text-zinc-200 break-all">{{ solicitud.user_email }}</span>
                            </div>
                            <div class="flex flex-col gap-0.5">
                                <span class="font-bold text-slate-400 uppercase tracking-wide text-[10px]">Registrada</span>
                                <span class="text-slate-700 dark:text-zinc-200">{{ solicitud.created_at }}</span>
                            </div>
                            <div class="flex flex-col gap-0.5">
                                <span class="font-bold text-slate-400 uppercase tracking-wide text-[10px]">Última actualización</span>
                                <span class="text-slate-700 dark:text-zinc-200">{{ solicitud.updated_at }}</span>
                            </div>
                            <div v-if="solicitud.credito_id" class="pt-2 border-t border-slate-100 dark:border-zinc-800">
                                <span class="font-bold text-slate-400 uppercase tracking-wide text-[10px]">Crédito vinculado</span>
                                <Link :href="route('operaciones.index', solicitud.acreditado_id)"
                                    class="flex items-center gap-1.5 mt-1 text-red-700 hover:text-red-800 font-bold">
                                    <CreditCard size="13" /> Ver expediente #{{ solicitud.credito_id }}
                                </Link>
                            </div>
                        </div>
                    </div>

                    <!-- Botón Registrar Crédito en sidebar (cuando Aprobada y sin crédito) -->
                    <Link v-if="solicitud.estatus === 'Aprobada' && !solicitud.credito_id"
                        :href="route('solicitudes.registrar-credito', solicitud.id)"
                        class="flex items-center justify-center gap-2 w-full py-4 bg-green-700 hover:bg-green-800 text-white font-black rounded-2xl text-sm transition-all shadow-lg shadow-green-900/20">
                        <CreditCard size="18" />
                        Registrar Crédito
                    </Link>

                    <!-- Análisis crediticio -->
                    <div v-if="['En_Revision', 'Documentacion_Incompleta'].includes(solicitud.estatus) || solicitud.analisis"
                        class="bg-white dark:bg-zinc-900 rounded-2xl border border-slate-100 dark:border-zinc-800 p-5 space-y-3 shadow-sm">
                        <h3 class="font-black text-slate-900 dark:text-white text-sm flex items-center gap-2">
                            <ClipboardList size="16" class="text-red-700" /> Análisis Crediticio
                        </h3>
                        <div v-if="solicitud.analisis" class="text-xs text-slate-500 dark:text-zinc-400 space-y-1">
                            <p>Puntaje: <strong class="text-slate-800 dark:text-white">{{ solicitud.analisis.puntaje_total ?? '—' }}/100</strong></p>
                            <p>Recomendación: <strong class="text-slate-800 dark:text-white">{{ solicitud.analisis.recomendacion ?? '—' }}</strong></p>
                            <p>Analista: {{ solicitud.analisis.analista ?? '—' }} · {{ solicitud.analisis.fecha_analisis ?? '' }}</p>
                        </div>
                        <p v-else class="text-xs text-slate-400">Aún no se ha registrado un análisis para esta solicitud.</p>
                        <Link :href="route('solicitudes.analisis.create', solicitud.id)"
                            class="flex items-center justify-center gap-2 w-full py-2.5 bg-slate-100 dark:bg-zinc-800 text-slate-700 dark:text-zinc-300 font-bold rounded-xl text-sm hover:bg-slate-200 dark:hover:bg-zinc-700 transition-all">
                            {{ solicitud.analisis ? 'Editar análisis' : 'Registrar análisis' }}
                        </Link>
                    </div>

                    <!-- Asignar analista -->
                    <div v-if="!['Aprobada', 'Rechazada'].includes(solicitud.estatus)"
                        class="bg-white dark:bg-zinc-900 rounded-2xl border border-slate-100 dark:border-zinc-800 p-5 space-y-3 shadow-sm">
                        <h3 class="font-black text-slate-900 dark:text-white text-sm flex items-center gap-2">
                            <UserCog size="16" class="text-red-700" /> Asignar analista
                        </h3>
                        <p v-if="solicitud.asignado_a" class="text-xs text-slate-500 dark:text-zinc-400">
                            Actualmente asignada a <strong class="text-slate-800 dark:text-white">{{ solicitud.asignado_a }}</strong> ({{ solicitud.fecha_asignacion }})
                        </p>
                        <select v-model="formAsignar.asignado_a" :class="inputClass">
                            <option value="" disabled>Seleccionar operativo...</option>
                            <option v-for="op in operativos" :key="op.id" :value="op.id">{{ op.name }}</option>
                        </select>
                        <button @click="asignar" :disabled="formAsignar.processing || !formAsignar.asignado_a"
                            class="w-full py-2.5 bg-slate-800 dark:bg-zinc-700 hover:bg-slate-900 disabled:opacity-50 text-white font-bold rounded-xl text-sm transition-all">
                            {{ formAsignar.processing ? 'Asignando...' : 'Asignar' }}
                        </button>
                    </div>

                    <!-- Cambiar estatus -->
                    <div v-if="!['Aprobada'].includes(solicitud.estatus) || !solicitud.credito_id"
                        class="bg-white dark:bg-zinc-900 rounded-2xl border border-slate-100 dark:border-zinc-800 p-5 space-y-4 shadow-sm">
                        <h3 class="font-black text-slate-900 dark:text-white text-sm flex items-center gap-2">
                            <ChevronDown size="16" class="text-red-700" /> Cambiar Estatus
                        </h3>
                        <select v-model="formEstatus.estatus" :class="inputClass">
                            <option v-for="op in estatusOpciones" :key="op.value" :value="op.value">{{ op.label }}</option>
                        </select>
                        <textarea v-model="formEstatus.observaciones" placeholder="Observaciones para el ciudadano (opcional)"
                            rows="3" :class="[inputClass, 'resize-none']"></textarea>
                        <button @click="cambiarEstatus" :disabled="formEstatus.processing"
                            class="w-full py-3 bg-red-700 hover:bg-red-800 disabled:opacity-60 text-white font-bold rounded-xl transition-all text-sm flex items-center justify-center gap-2">
                            <Send size="15" /> {{ formEstatus.processing ? 'Guardando...' : 'Actualizar y Notificar' }}
                        </button>
                    </div>

                    <!-- Enviar anuncio -->
                    <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-slate-100 dark:border-zinc-800 p-5 shadow-sm">
                        <button @click="showAnuncio = !showAnuncio"
                            class="w-full flex items-center gap-2 font-black text-slate-900 dark:text-white text-sm">
                            <Bell size="16" class="text-red-700" /> Enviar Mensaje al Ciudadano
                            <ChevronDown size="14" :class="['ml-auto transition-transform', showAnuncio ? 'rotate-180' : '']" />
                        </button>
                        <div v-if="showAnuncio" class="mt-4 space-y-3">
                            <input v-model="formAnuncio.titulo" type="text" placeholder="Título del mensaje" :class="inputClass" />
                            <textarea v-model="formAnuncio.mensaje" placeholder="Mensaje para el ciudadano..." rows="3" :class="[inputClass, 'resize-none']"></textarea>
                            <select v-model="formAnuncio.tipo" :class="inputClass">
                                <option value="info">ℹ️ Información</option>
                                <option value="alerta">⚠️ Alerta</option>
                                <option value="pago">💰 Pago</option>
                                <option value="exito">✅ Éxito</option>
                                <option value="error">❌ Error</option>
                            </select>
                            <button @click="enviarAnuncio" :disabled="formAnuncio.processing"
                                class="w-full py-3 bg-slate-800 dark:bg-zinc-700 hover:bg-slate-900 dark:hover:bg-zinc-600 disabled:opacity-60 text-white font-bold rounded-xl transition-all text-sm flex items-center justify-center gap-2">
                                <Bell size="15" /> {{ formAnuncio.processing ? 'Enviando...' : 'Enviar Notificación' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
