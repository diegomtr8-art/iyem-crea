<script setup lang="ts">
import BeneficiarioLayout from '@/layouts/BeneficiarioLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import {
    FileText, User, Briefcase, Upload, Send, CheckCircle2,
    XCircle, Clock, AlertTriangle, ChevronRight, ChevronLeft,
    BadgeCheck, Sparkles, ExternalLink, RefreshCw, RotateCcw
} from 'lucide-vue-next';

const props = defineProps<{
    solicitud: (Record<string, any> & { documentos?: Record<string, any>; estatus: string }) | null;
    modalidades: Array<{ id: number; nombre: string; tasa_interes: string }>;
    tipos_documentos: Record<string, string>;
}>();

// ─── Multi-paso (solo cuando no hay solicitud) ───
const paso = ref(1);
const totalPasos = 3;

const pasos = [
    { n: 1, label: 'Datos Personales', icon: User },
    { n: 2, label: 'Negocio / Proyecto', icon: Briefcase },
    { n: 3, label: 'Revisión', icon: CheckCircle2 },
];

// ─── Formulario principal ───
const form = useForm({
    nombre_completo: props.solicitud?.nombre_completo ?? '',
    curp: props.solicitud?.curp ?? '',
    rfc: props.solicitud?.rfc ?? '',
    fecha_nacimiento: props.solicitud?.fecha_nacimiento ?? '',
    sexo: props.solicitud?.sexo ?? '',
    mayahablante: props.solicitud?.mayahablante ?? false,
    discapacidad: props.solicitud?.discapacidad ?? false,
    municipio: props.solicitud?.municipio ?? '',
    direccion: props.solicitud?.direccion ?? '',
    telefono: props.solicitud?.telefono ?? '',
    correo: props.solicitud?.correo ?? '',
    modalidad_id: props.solicitud?.modalidad_id ?? '',
    giro_comercial: props.solicitud?.giro_comercial ?? '',
    destino_credito: props.solicitud?.destino_credito ?? '',
    descripcion_negocio: props.solicitud?.descripcion_negocio ?? '',
    monto_solicitado: props.solicitud?.monto_solicitado ?? '',
    alta_sat: props.solicitud?.alta_sat ?? false,
});

const guardarForm = () => {
    if (props.solicitud) {
        form.put(route('portal.solicitud.update'), { preserveScroll: true });
    } else {
        form.post(route('portal.solicitud.store'));
    }
};

const enviarSolicitud = () => {
    router.post(route('portal.solicitud.enviar'), {}, { preserveScroll: true });
};

// ─── Subida de documentos ───
const archivos = ref<Record<string, File | null>>({});
const subiendo = ref<Record<string, boolean>>({});

const seleccionarArchivo = (tipo: string, event: Event) => {
    const el = event.target as HTMLInputElement;
    if (el.files?.[0]) {
        archivos.value[tipo] = el.files[0];
    }
};

const subirDoc = (tipo: string) => {
    const archivo = archivos.value[tipo];
    if (!archivo) return;
    subiendo.value[tipo] = true;
    const fd = new FormData();
    fd.append('tipo_documento', tipo);
    fd.append('archivo', archivo);
    router.post(route('portal.solicitud.documento'), fd, {
        preserveScroll: true,
        onFinish: () => { subiendo.value[tipo] = false; archivos.value[tipo] = null; },
    });
};

// ─── Estados de documentos ───
const docPorTipo = computed(() => props.solicitud?.documentos ?? {});

const allDocsSubidos = computed(() => {
    const tipos = Object.keys(props.tipos_documentos);
    return tipos.every(t => docPorTipo.value[t]);
});

const docEstilo: Record<string, { bg: string; badgeColor: string; icon: any }> = {
    Aprobado:  { bg: 'bg-green-50 dark:bg-green-900/10 border-green-200 dark:border-green-800/40', badgeColor: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400', icon: CheckCircle2 },
    Pendiente: { bg: 'bg-amber-50 dark:bg-amber-900/10 border-amber-200 dark:border-amber-800/40', badgeColor: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400', icon: Clock },
    Rechazado: { bg: 'bg-red-50 dark:bg-red-900/10 border-red-200 dark:border-red-800/40', badgeColor: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400', icon: XCircle },
};

const municipios = [
    'Mérida','Valladolid','Tizimín','Progreso','Uman','Kanasín','Motul','Tekax','Ticul','Hunucmá',
    'Izamal','Espita','Oxkutzcab','Peto','Maxcanú','Acanceh','Baca','Conkal','Dzidzantún','Otro'
];

const inputClass = 'w-full px-4 py-3.5 rounded-2xl border border-slate-200 dark:border-zinc-700 dark:bg-zinc-800/50 focus:ring-4 focus:ring-red-500/10 focus:border-red-600 transition-all text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-zinc-500 bg-white text-sm';
const labelClass = 'block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-zinc-400 mb-1.5 ml-0.5';
const errorClass = 'text-xs text-red-600 mt-1 ml-1';
</script>

<template>
    <BeneficiarioLayout>
        <Head title="Solicitar Crédito — CREA" />

        <div class="space-y-6 max-w-3xl mx-auto">

            <!-- Encabezado -->
            <div class="space-y-1">
                <p class="text-xs font-bold uppercase tracking-widest text-red-700">Portal Ciudadano CREA</p>
                <h1 class="text-2xl md:text-3xl font-black text-slate-900 dark:text-white flex items-center gap-3">
                    <FileText size="26" class="text-red-700" /> Solicitar Crédito
                </h1>
            </div>

            <!-- ─── ESTADO: APROBADA ─── -->
            <div v-if="solicitud?.estatus === 'Aprobada'"
                class="rounded-3xl bg-gradient-to-br from-green-600 to-emerald-700 p-10 text-white text-center space-y-6 shadow-2xl shadow-green-900/30">
                <div class="w-20 h-20 rounded-3xl bg-white/20 flex items-center justify-center mx-auto">
                    <BadgeCheck size="40" />
                </div>
                <div class="space-y-2">
                    <h2 class="text-3xl font-black">¡Solicitud Aprobada!</h2>
                    <p class="text-green-200 leading-relaxed max-w-md mx-auto">
                        ¡Felicidades! Tu solicitud fue aprobada. El equipo CREA se pondrá en contacto contigo para los siguientes pasos.
                    </p>
                </div>
                <a :href="route('portal.credito')"
                    class="inline-flex items-center gap-2 px-6 py-3.5 bg-white text-green-700 font-bold rounded-2xl hover:bg-green-50 transition-colors">
                    <BadgeCheck size="18" /> Ver mi Crédito
                </a>
            </div>

            <!-- ─── ESTADO: RECHAZADA ─── -->
            <div v-else-if="solicitud?.estatus === 'Rechazada'"
                class="rounded-3xl bg-red-50 dark:bg-red-900/10 border border-red-200 dark:border-red-800/40 p-8 space-y-4">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 rounded-2xl bg-red-100 dark:bg-red-900/30 flex items-center justify-center text-red-700 shrink-0">
                        <XCircle size="28" />
                    </div>
                    <div>
                        <h2 class="text-xl font-black text-slate-900 dark:text-white">Solicitud No Aprobada</h2>
                        <p class="text-slate-500 dark:text-zinc-400 text-sm">Tu solicitud no pudo ser aprobada en esta ocasión.</p>
                    </div>
                </div>
                <div v-if="solicitud.observaciones" class="bg-white dark:bg-zinc-900 rounded-2xl border border-red-200 dark:border-red-800/40 p-4">
                    <p class="text-xs font-bold uppercase text-red-700 mb-2">Motivo del rechazo:</p>
                    <p class="text-sm text-slate-700 dark:text-zinc-300">{{ solicitud.observaciones }}</p>
                </div>
                <p class="text-sm text-slate-500 dark:text-zinc-400">
                    Para más información comunícate al <strong class="text-slate-700 dark:text-zinc-200">999 941 2170</strong> o visítanos en nuestras oficinas.
                </p>
            </div>

            <!-- ─── ESTADO: ENVIADA / EN REVISIÓN ─── -->
            <div v-else-if="['Enviada','En_Revision'].includes(solicitud?.estatus ?? '')"
                class="rounded-3xl bg-blue-50 dark:bg-blue-900/10 border border-blue-200 dark:border-blue-800/40 p-10 text-center space-y-5">
                <div class="w-20 h-20 rounded-3xl bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center mx-auto text-blue-600 animate-pulse">
                    <Clock size="36" />
                </div>
                <div class="space-y-2">
                    <h2 class="text-2xl font-black text-slate-900 dark:text-white">
                        {{ solicitud?.estatus === 'Enviada' ? '¡Solicitud Enviada!' : 'En Revisión' }}
                    </h2>
                    <p class="text-slate-500 dark:text-zinc-400 max-w-md mx-auto leading-relaxed">
                        {{ solicitud?.estatus === 'Enviada'
                            ? 'Recibimos tu solicitud y documentos. El equipo CREA los revisará en breve (días hábiles).'
                            : 'Un asesor CREA está revisando tu solicitud y documentos. Recibirás una notificación con el resultado.' }}
                    </p>
                </div>
                <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-blue-100 dark:border-blue-800/40 p-4 text-left max-w-sm mx-auto">
                    <p class="text-xs font-bold uppercase text-blue-700 dark:text-blue-400 mb-2">¿Qué sigue?</p>
                    <ul class="space-y-1.5 text-sm text-slate-600 dark:text-zinc-400">
                        <li class="flex items-center gap-2"><CheckCircle2 size="14" class="text-green-500 shrink-0" /> Tu solicitud está registrada</li>
                        <li class="flex items-center gap-2"><Clock size="14" class="text-blue-500 shrink-0" /> El equipo revisa tus documentos</li>
                        <li class="flex items-center gap-2"><Clock size="14" class="text-slate-400 shrink-0" /> Recibirás una alerta con el resultado</li>
                    </ul>
                </div>
            </div>

            <!-- ─── FORMULARIO NUEVO / BORRADOR / DOC INCOMPLETA ─── -->
            <template v-else>

                <!-- ── AVISO DOC INCOMPLETA ── -->
                <div v-if="solicitud?.estatus === 'Documentacion_Incompleta'"
                    class="rounded-2xl bg-orange-50 dark:bg-orange-900/10 border border-orange-200 dark:border-orange-800/40 p-5 flex items-start gap-4">
                    <AlertTriangle size="22" class="text-orange-600 shrink-0 mt-0.5" />
                    <div>
                        <p class="font-bold text-orange-900 dark:text-orange-300">Documentación requiere corrección</p>
                        <p class="text-sm text-orange-700 dark:text-orange-400 mt-0.5">
                            {{ solicitud.observaciones || 'Revisa los documentos marcados en rojo, corrígelos y re-envía tu solicitud.' }}
                        </p>
                    </div>
                </div>

                <!-- ── STEPPER (solo cuando NO hay solicitud) ── -->
                <div v-if="!solicitud" class="flex items-center gap-0">
                    <template v-for="(p, idx) in pasos" :key="p.n">
                        <button type="button" @click="paso >= p.n ? paso = p.n : null"
                            :class="[
                                'flex items-center gap-2 px-4 py-2 rounded-2xl text-sm font-bold transition-all flex-shrink-0',
                                paso === p.n ? 'bg-red-700 text-white shadow-lg shadow-red-900/20'
                                    : paso > p.n ? 'bg-green-100 dark:bg-green-900/20 text-green-700 dark:text-green-400 cursor-pointer'
                                    : 'bg-slate-100 dark:bg-zinc-800 text-slate-400 cursor-default'
                            ]">
                            <component :is="paso > p.n ? CheckCircle2 : p.icon" size="15" />
                            <span class="hidden sm:inline">{{ p.label }}</span>
                            <span class="sm:hidden">{{ p.n }}</span>
                        </button>
                        <div v-if="idx < pasos.length - 1" class="flex-1 h-0.5 bg-slate-200 dark:bg-zinc-700 mx-1"></div>
                    </template>
                </div>

                <!-- ═══════════════════════════════════════════════
                     PASO 1 / FORM COMPLETO: DATOS PERSONALES
                ═══════════════════════════════════════════════ -->
                <div v-if="!solicitud ? paso === 1 : true"
                    class="bg-white dark:bg-zinc-900 rounded-3xl border border-slate-100 dark:border-zinc-800 overflow-hidden shadow-sm">
                    <div class="px-6 py-5 border-b border-slate-100 dark:border-zinc-800 flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-red-50 dark:bg-red-900/20 flex items-center justify-center text-red-700"><User size="20" /></div>
                        <div>
                            <h2 class="text-lg font-black text-slate-900 dark:text-white">Datos Personales</h2>
                            <p class="text-xs text-slate-400 dark:text-zinc-500">Información personal del solicitante</p>
                        </div>
                    </div>
                    <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div class="sm:col-span-2">
                            <label :class="labelClass">Nombre completo *</label>
                            <input v-model="form.nombre_completo" type="text" placeholder="Como aparece en tu INE" :class="inputClass" />
                            <p v-if="form.errors.nombre_completo" :class="errorClass">{{ form.errors.nombre_completo }}</p>
                        </div>
                        <div>
                            <label :class="labelClass">CURP *</label>
                            <input v-model="form.curp" type="text" maxlength="18" placeholder="18 caracteres" :class="[inputClass, 'uppercase font-mono tracking-widest']" />
                            <p v-if="form.errors.curp" :class="errorClass">{{ form.errors.curp }}</p>
                        </div>
                        <div>
                            <label :class="labelClass">RFC</label>
                            <input v-model="form.rfc" type="text" maxlength="13" placeholder="Opcional" :class="[inputClass, 'uppercase font-mono']" />
                            <p v-if="form.errors.rfc" :class="errorClass">{{ form.errors.rfc }}</p>
                        </div>
                        <div>
                            <label :class="labelClass">Fecha de nacimiento *</label>
                            <input v-model="form.fecha_nacimiento" type="date" :class="inputClass" />
                            <p v-if="form.errors.fecha_nacimiento" :class="errorClass">{{ form.errors.fecha_nacimiento }}</p>
                        </div>
                        <div>
                            <label :class="labelClass">Sexo *</label>
                            <select v-model="form.sexo" :class="inputClass">
                                <option value="" disabled>Seleccionar...</option>
                                <option value="M">Masculino</option>
                                <option value="F">Femenino</option>
                            </select>
                            <p v-if="form.errors.sexo" :class="errorClass">{{ form.errors.sexo }}</p>
                        </div>
                        <div>
                            <label :class="labelClass">Municipio *</label>
                            <select v-model="form.municipio" :class="inputClass">
                                <option value="" disabled>Seleccionar municipio</option>
                                <option v-for="m in municipios" :key="m" :value="m">{{ m }}</option>
                            </select>
                            <p v-if="form.errors.municipio" :class="errorClass">{{ form.errors.municipio }}</p>
                        </div>
                        <div>
                            <label :class="labelClass">Teléfono *</label>
                            <input v-model="form.telefono" type="tel" placeholder="10 dígitos" :class="inputClass" />
                            <p v-if="form.errors.telefono" :class="errorClass">{{ form.errors.telefono }}</p>
                        </div>
                        <div class="sm:col-span-2">
                            <label :class="labelClass">Correo electrónico de contacto *</label>
                            <input v-model="form.correo" type="email" placeholder="tucorreo@ejemplo.com" :class="inputClass" />
                            <p v-if="form.errors.correo" :class="errorClass">{{ form.errors.correo }}</p>
                        </div>
                        <div class="sm:col-span-2">
                            <label :class="labelClass">Dirección completa *</label>
                            <textarea v-model="form.direccion" rows="2" placeholder="Calle, número, colonia, municipio" :class="[inputClass, 'resize-none']"></textarea>
                            <p v-if="form.errors.direccion" :class="errorClass">{{ form.errors.direccion }}</p>
                        </div>
                        <div class="sm:col-span-2 flex flex-wrap gap-6">
                            <label class="flex items-center gap-3 cursor-pointer group">
                                <div class="relative">
                                    <input type="checkbox" v-model="form.mayahablante" class="sr-only peer" />
                                    <div class="w-10 h-6 bg-slate-200 dark:bg-zinc-700 peer-checked:bg-red-600 rounded-full transition-colors"></div>
                                    <div class="absolute left-1 top-1 w-4 h-4 bg-white rounded-full transition-transform peer-checked:translate-x-4 shadow-sm"></div>
                                </div>
                                <span class="text-sm font-medium text-slate-700 dark:text-zinc-300">¿Habla Maya?</span>
                            </label>
                            <label class="flex items-center gap-3 cursor-pointer group">
                                <div class="relative">
                                    <input type="checkbox" v-model="form.discapacidad" class="sr-only peer" />
                                    <div class="w-10 h-6 bg-slate-200 dark:bg-zinc-700 peer-checked:bg-red-600 rounded-full transition-colors"></div>
                                    <div class="absolute left-1 top-1 w-4 h-4 bg-white rounded-full transition-transform peer-checked:translate-x-4 shadow-sm"></div>
                                </div>
                                <span class="text-sm font-medium text-slate-700 dark:text-zinc-300">¿Tiene alguna discapacidad?</span>
                            </label>
                        </div>
                    </div>
                    <!-- Botón paso 1 -->
                    <div v-if="!solicitud" class="px-6 pb-6 flex justify-end">
                        <button type="button" @click="paso = 2"
                            class="flex items-center gap-2 px-6 py-3.5 bg-red-700 hover:bg-red-800 text-white font-bold rounded-2xl transition-all shadow-lg shadow-red-900/20">
                            Siguiente <ChevronRight size="18" />
                        </button>
                    </div>
                </div>

                <!-- ═══════════════════════════════════════════════
                     PASO 2: DATOS DEL NEGOCIO
                ═══════════════════════════════════════════════ -->
                <div v-if="!solicitud ? paso === 2 : true"
                    class="bg-white dark:bg-zinc-900 rounded-3xl border border-slate-100 dark:border-zinc-800 overflow-hidden shadow-sm">
                    <div class="px-6 py-5 border-b border-slate-100 dark:border-zinc-800 flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-red-50 dark:bg-red-900/20 flex items-center justify-center text-red-700"><Briefcase size="20" /></div>
                        <div>
                            <h2 class="text-lg font-black text-slate-900 dark:text-white">Datos del Negocio / Proyecto</h2>
                            <p class="text-xs text-slate-400 dark:text-zinc-500">Información de tu emprendimiento</p>
                        </div>
                    </div>
                    <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label :class="labelClass">Modalidad de crédito *</label>
                            <select v-model="form.modalidad_id" :class="inputClass">
                                <option value="" disabled>Seleccionar modalidad</option>
                                <option v-for="m in modalidades" :key="m.id" :value="m.id">{{ m.nombre }}</option>
                            </select>
                            <p v-if="form.errors.modalidad_id" :class="errorClass">{{ form.errors.modalidad_id }}</p>
                        </div>
                        <div>
                            <label :class="labelClass">Giro comercial *</label>
                            <input v-model="form.giro_comercial" type="text" placeholder="Ej: Artesanías, Panadería, Ropa..." :class="inputClass" />
                            <p v-if="form.errors.giro_comercial" :class="errorClass">{{ form.errors.giro_comercial }}</p>
                        </div>
                        <div class="sm:col-span-2">
                            <label :class="labelClass">Destino del crédito *</label>
                            <textarea v-model="form.destino_credito" rows="2" placeholder="¿En qué utilizarás el crédito?" :class="[inputClass, 'resize-none']"></textarea>
                            <p v-if="form.errors.destino_credito" :class="errorClass">{{ form.errors.destino_credito }}</p>
                        </div>
                        <div class="sm:col-span-2">
                            <label :class="labelClass">Descripción del negocio o proyecto *</label>
                            <textarea v-model="form.descripcion_negocio" rows="3" placeholder="Describe tu negocio: qué vendes, a quién, hace cuánto opera..." :class="[inputClass, 'resize-none']"></textarea>
                            <p v-if="form.errors.descripcion_negocio" :class="errorClass">{{ form.errors.descripcion_negocio }}</p>
                        </div>
                        <div>
                            <label :class="labelClass">Monto solicitado (MXN)</label>
                            <input v-model="form.monto_solicitado" type="number" min="100" step="100" placeholder="Opcional" :class="inputClass" />
                            <p class="text-[10px] text-slate-400 mt-1 ml-1">El monto final es determinado por CREA.</p>
                            <p v-if="form.errors.monto_solicitado" :class="errorClass">{{ form.errors.monto_solicitado }}</p>
                        </div>
                        <div class="flex items-end pb-1">
                            <label class="flex items-center gap-3 cursor-pointer">
                                <div class="relative">
                                    <input type="checkbox" v-model="form.alta_sat" class="sr-only peer" />
                                    <div class="w-10 h-6 bg-slate-200 dark:bg-zinc-700 peer-checked:bg-red-600 rounded-full transition-colors"></div>
                                    <div class="absolute left-1 top-1 w-4 h-4 bg-white rounded-full transition-transform peer-checked:translate-x-4 shadow-sm"></div>
                                </div>
                                <div>
                                    <span class="text-sm font-medium text-slate-700 dark:text-zinc-300">¿Estoy dado de alta en el SAT?</span>
                                    <p class="text-[10px] text-slate-400">Régimen fiscal / RFC activo</p>
                                </div>
                            </label>
                        </div>
                    </div>
                    <div v-if="!solicitud" class="px-6 pb-6 flex items-center justify-between">
                        <button type="button" @click="paso = 1"
                            class="flex items-center gap-2 px-5 py-3 bg-slate-100 dark:bg-zinc-800 text-slate-700 dark:text-zinc-300 font-bold rounded-2xl hover:bg-slate-200 dark:hover:bg-zinc-700 transition-all">
                            <ChevronLeft size="18" /> Anterior
                        </button>
                        <button type="button" @click="paso = 3"
                            class="flex items-center gap-2 px-6 py-3.5 bg-red-700 hover:bg-red-800 text-white font-bold rounded-2xl transition-all shadow-lg shadow-red-900/20">
                            Siguiente <ChevronRight size="18" />
                        </button>
                    </div>
                </div>

                <!-- ═══════════════════════════════════════════════
                     PASO 3: REVISIÓN + ENVÍO (sin solicitud)
                     DOCUMENTOS (con solicitud en borrador)
                ═══════════════════════════════════════════════ -->

                <!-- Revisión antes de crear solicitud -->
                <div v-if="!solicitud && paso === 3"
                    class="bg-white dark:bg-zinc-900 rounded-3xl border border-slate-100 dark:border-zinc-800 p-6 space-y-6 shadow-sm">
                    <div class="flex items-center gap-3 border-b border-slate-100 dark:border-zinc-800 pb-5">
                        <div class="w-10 h-10 rounded-2xl bg-red-50 dark:bg-red-900/20 flex items-center justify-center text-red-700"><CheckCircle2 size="20" /></div>
                        <div>
                            <h2 class="text-lg font-black text-slate-900 dark:text-white">Revisión Final</h2>
                            <p class="text-xs text-slate-400">Confirma que tus datos son correctos antes de guardar</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div><span class="text-slate-400 text-xs uppercase font-bold">Nombre</span><p class="font-semibold text-slate-800 dark:text-white mt-0.5">{{ form.nombre_completo || '—' }}</p></div>
                        <div><span class="text-slate-400 text-xs uppercase font-bold">CURP</span><p class="font-mono font-semibold text-slate-800 dark:text-white mt-0.5">{{ form.curp || '—' }}</p></div>
                        <div><span class="text-slate-400 text-xs uppercase font-bold">Municipio</span><p class="font-semibold text-slate-800 dark:text-white mt-0.5">{{ form.municipio || '—' }}</p></div>
                        <div><span class="text-slate-400 text-xs uppercase font-bold">Teléfono</span><p class="font-semibold text-slate-800 dark:text-white mt-0.5">{{ form.telefono || '—' }}</p></div>
                        <div><span class="text-slate-400 text-xs uppercase font-bold">Giro comercial</span><p class="font-semibold text-slate-800 dark:text-white mt-0.5">{{ form.giro_comercial || '—' }}</p></div>
                        <div><span class="text-slate-400 text-xs uppercase font-bold">Modalidad ID</span><p class="font-semibold text-slate-800 dark:text-white mt-0.5">{{ modalidades.find(m => m.id == form.modalidad_id)?.nombre || '—' }}</p></div>
                    </div>
                    <div class="bg-blue-50 dark:bg-blue-900/10 border border-blue-200 dark:border-blue-800/40 rounded-2xl p-4 text-sm text-blue-700 dark:text-blue-400">
                        <strong>Nota:</strong> Después de guardar, podrás subir tus documentos. La solicitud solo se enviará cuando hayas subido todos los archivos requeridos.
                    </div>
                    <div class="flex items-center justify-between pt-2">
                        <button type="button" @click="paso = 2"
                            class="flex items-center gap-2 px-5 py-3 bg-slate-100 dark:bg-zinc-800 text-slate-700 dark:text-zinc-300 font-bold rounded-2xl hover:bg-slate-200 dark:hover:bg-zinc-700 transition-all">
                            <ChevronLeft size="18" /> Anterior
                        </button>
                        <button type="button" @click="guardarForm" :disabled="form.processing"
                            class="flex items-center gap-2 px-6 py-3.5 bg-red-700 hover:bg-red-800 disabled:opacity-60 text-white font-bold rounded-2xl transition-all shadow-lg shadow-red-900/20">
                            <Sparkles size="18" /> {{ form.processing ? 'Guardando...' : 'Guardar Solicitud' }}
                        </button>
                    </div>
                </div>

                <!-- Actualizar datos (tiene solicitud en borrador) -->
                <div v-if="solicitud && ['Borrador','Documentacion_Incompleta'].includes(solicitud.estatus)" class="flex justify-end pt-2">
                    <button type="button" @click="guardarForm" :disabled="form.processing"
                        class="flex items-center gap-2 px-6 py-3.5 bg-slate-700 hover:bg-slate-800 dark:bg-zinc-700 dark:hover:bg-zinc-600 text-white font-bold rounded-2xl transition-all text-sm">
                        <RefreshCw size="16" /> {{ form.processing ? 'Guardando...' : 'Guardar Cambios' }}
                    </button>
                </div>

                <!-- ─── SECCIÓN DOCUMENTOS (solo cuando hay solicitud) ─── -->
                <div v-if="solicitud && ['Borrador','Documentacion_Incompleta'].includes(solicitud.estatus)"
                    class="bg-white dark:bg-zinc-900 rounded-3xl border border-slate-100 dark:border-zinc-800 overflow-hidden shadow-sm">
                    <div class="px-6 py-5 border-b border-slate-100 dark:border-zinc-800 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl bg-red-50 dark:bg-red-900/20 flex items-center justify-center text-red-700"><Upload size="20" /></div>
                            <div>
                                <h2 class="text-lg font-black text-slate-900 dark:text-white">Documentos Requeridos</h2>
                                <p class="text-xs text-slate-400">PDF, JPG o PNG — máx. 5 MB por archivo</p>
                            </div>
                        </div>
                        <span :class="['text-xs font-bold px-3 py-1 rounded-full', allDocsSubidos ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400']">
                            {{ Object.values(docPorTipo).length }} / {{ Object.keys(tipos_documentos).length }} subidos
                        </span>
                    </div>
                    <div class="p-6 space-y-4">
                        <div v-for="(label, tipo) in tipos_documentos" :key="tipo">
                            <div :class="['rounded-2xl border p-4 space-y-3', docPorTipo[tipo] ? docEstilo[docPorTipo[tipo].estatus]?.bg : 'bg-slate-50 dark:bg-zinc-800 border-slate-200 dark:border-zinc-700']">
                                <!-- Encabezado del documento -->
                                <div class="flex items-center justify-between gap-2 flex-wrap">
                                    <div class="flex items-center gap-2">
                                        <component :is="docPorTipo[tipo] ? (docEstilo[docPorTipo[tipo].estatus]?.icon ?? Clock) : Upload"
                                            :class="['size-4 shrink-0', docPorTipo[tipo] ? (docEstilo[docPorTipo[tipo].estatus]?.badgeColor?.split(' ')[1] ?? 'text-slate-400') : 'text-slate-400']" />
                                        <p class="text-sm font-bold text-slate-900 dark:text-white">{{ label }}</p>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span v-if="docPorTipo[tipo]"
                                            :class="['text-[10px] font-black px-2.5 py-1 rounded-full', docEstilo[docPorTipo[tipo].estatus]?.badgeColor]">
                                            {{ docPorTipo[tipo].estatus }}
                                        </span>
                                        <a v-if="docPorTipo[tipo]" :href="docPorTipo[tipo].url" target="_blank"
                                            class="p-1.5 rounded-lg bg-white dark:bg-zinc-700 text-slate-500 hover:text-red-700 border border-slate-200 dark:border-zinc-600 transition-colors">
                                            <ExternalLink size="12" />
                                        </a>
                                    </div>
                                </div>

                                <!-- Archivo actual -->
                                <p v-if="docPorTipo[tipo]" class="text-[11px] text-slate-500 dark:text-zinc-400 truncate">
                                    📎 {{ docPorTipo[tipo].nombre_original }}
                                </p>

                                <!-- Observación si rechazado -->
                                <p v-if="docPorTipo[tipo]?.estatus === 'Rechazado' && docPorTipo[tipo]?.observacion"
                                    class="text-xs text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/10 rounded-xl px-3 py-2">
                                    <strong>Corrección requerida:</strong> {{ docPorTipo[tipo].observacion }}
                                </p>

                                <!-- Zona de carga -->
                                <div class="flex items-center gap-3">
                                    <label :for="`doc-${tipo}`"
                                        class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 border-2 border-dashed border-slate-300 dark:border-zinc-600 rounded-xl cursor-pointer hover:border-red-400 hover:bg-red-50 dark:hover:bg-red-900/10 transition-all text-sm text-slate-500 dark:text-zinc-400 hover:text-red-700">
                                        <Upload size="15" />
                                        {{ archivos[tipo] ? archivos[tipo]?.name : (docPorTipo[tipo] ? 'Reemplazar archivo' : 'Seleccionar archivo') }}
                                        <input :id="`doc-${tipo}`" type="file" accept=".pdf,.jpg,.jpeg,.png" class="sr-only"
                                            @change="seleccionarArchivo(tipo, $event)" />
                                    </label>
                                    <button v-if="archivos[tipo]" type="button" @click="subirDoc(tipo)"
                                        :disabled="subiendo[tipo]"
                                        class="px-4 py-2.5 bg-red-700 hover:bg-red-800 disabled:opacity-60 text-white font-bold rounded-xl transition-all text-sm shrink-0 flex items-center gap-2">
                                        <RotateCcw v-if="subiendo[tipo]" size="14" class="animate-spin" />
                                        {{ subiendo[tipo] ? 'Subiendo...' : 'Subir' }}
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- BOTÓN ENVIAR -->
                        <div class="pt-4 border-t border-slate-100 dark:border-zinc-800">
                            <button v-if="allDocsSubidos" type="button" @click="enviarSolicitud"
                                class="w-full flex items-center justify-center gap-3 py-4 bg-gradient-to-r from-red-700 to-red-800 hover:from-red-800 hover:to-red-900 text-white font-bold rounded-2xl shadow-xl shadow-red-900/25 transition-all active:scale-[0.98] text-base">
                                <Send size="20" />
                                {{ solicitud?.estatus === 'Documentacion_Incompleta' ? 'Re-enviar Solicitud' : 'Enviar Solicitud para Revisión' }}
                            </button>
                            <div v-else class="text-center p-4 bg-slate-50 dark:bg-zinc-800 rounded-2xl border border-dashed border-slate-200 dark:border-zinc-700">
                                <p class="text-sm text-slate-500 dark:text-zinc-400 font-medium">
                                    Sube todos los documentos requeridos para poder enviar tu solicitud.
                                </p>
                                <p class="text-xs text-slate-400 dark:text-zinc-500 mt-1">
                                    Faltan: {{ Object.keys(tipos_documentos).filter(t => !docPorTipo[t]).length }} documento(s)
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </BeneficiarioLayout>
</template>
