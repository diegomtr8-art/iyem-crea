<script setup lang="ts">
import BeneficiarioLayout from '@/layouts/BeneficiarioLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import {
    FolderOpen, User, Briefcase, FileText, CheckCircle2,
    XCircle, Clock, ExternalLink, Sparkles, MapPin, Phone, Mail, Calendar
} from 'lucide-vue-next';

const props = defineProps<{
    solicitud: {
        id: number;
        estatus: string;
        nombre_completo: string;
        curp: string;
        rfc?: string;
        fecha_nacimiento?: string;
        sexo?: string;
        mayahablante: boolean;
        discapacidad: boolean;
        municipio?: string;
        direccion?: string;
        telefono?: string;
        correo?: string;
        modalidad?: string;
        giro_comercial?: string;
        destino_credito?: string;
        descripcion_negocio?: string;
        alta_sat: boolean;
        created_at: string;
    } | null;
    documentos: Array<{
        id: number;
        tipo_documento: string;
        label: string;
        nombre_original: string;
        estatus: string;
        observacion?: string;
        url: string;
        updated_at: string;
    }> | null;
    tipos_documentos: Record<string, string>;
    usuario: { name: string; email: string };
}>();

const docEstilo: Record<string, { bg: string; text: string; icon: any }> = {
    Aprobado:  { bg: 'bg-green-50 dark:bg-green-900/10 border-green-200 dark:border-green-800/40', text: 'text-green-700 dark:text-green-400', icon: CheckCircle2 },
    Pendiente: { bg: 'bg-amber-50 dark:bg-amber-900/10 border-amber-200 dark:border-amber-800/40', text: 'text-amber-700 dark:text-amber-400', icon: Clock },
    Rechazado: { bg: 'bg-red-50 dark:bg-red-900/10 border-red-200 dark:border-red-800/40', text: 'text-red-700 dark:text-red-400', icon: XCircle },
};
</script>

<template>
    <BeneficiarioLayout>
        <Head title="Mi Expediente — CREA" />

        <div class="space-y-8">
            <!-- Encabezado -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="space-y-1">
                    <p class="text-xs font-bold uppercase tracking-widest text-red-700">Portal Ciudadano CREA</p>
                    <h1 class="text-2xl md:text-3xl font-black text-slate-900 dark:text-white flex items-center gap-3">
                        <FolderOpen size="28" class="text-red-700" /> Mi Expediente Digital
                    </h1>
                </div>
            </div>

            <!-- Estado: sin expediente -->
            <div v-if="!solicitud" class="rounded-3xl border-2 border-dashed border-slate-200 dark:border-zinc-800 p-16 text-center space-y-6">
                <div class="w-20 h-20 rounded-3xl bg-slate-100 dark:bg-zinc-800 flex items-center justify-center mx-auto text-slate-400">
                    <FolderOpen size="36" />
                </div>
                <div class="space-y-2">
                    <h3 class="text-2xl font-black text-slate-900 dark:text-white">Tu expediente está vacío</h3>
                    <p class="text-slate-500 dark:text-zinc-400 max-w-md mx-auto">
                        Cuando inicies tu solicitud de crédito, toda tu información personal y documentos aparecerán aquí de forma organizada.
                    </p>
                </div>
                <Link :href="route('portal.solicitud.index')"
                    class="inline-flex items-center gap-2 px-6 py-3.5 bg-red-700 hover:bg-red-800 text-white font-bold rounded-2xl transition-all shadow-lg shadow-red-900/20">
                    <Sparkles size="18" /> Iniciar mi Solicitud
                </Link>
            </div>

            <!-- Expediente completo -->
            <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- Columna izquierda: Datos personales + Negocio -->
                <div class="lg:col-span-2 space-y-6">

                    <!-- Datos personales -->
                    <div class="bg-white dark:bg-zinc-900 rounded-3xl border border-slate-100 dark:border-zinc-800 overflow-hidden shadow-sm">
                        <div class="px-6 py-5 border-b border-slate-100 dark:border-zinc-800 flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl bg-red-50 dark:bg-red-900/20 flex items-center justify-center text-red-700">
                                <User size="20" />
                            </div>
                            <h2 class="text-lg font-black text-slate-900 dark:text-white">Datos Personales</h2>
                        </div>
                        <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-5">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Nombre Completo</p>
                                <p class="font-semibold text-slate-900 dark:text-white">{{ solicitud.nombre_completo || '—' }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">CURP</p>
                                <p class="font-mono font-semibold text-slate-900 dark:text-white tracking-wider">{{ solicitud.curp || '—' }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">RFC</p>
                                <p class="font-mono font-semibold text-slate-900 dark:text-white">{{ solicitud.rfc || 'No registrado' }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Fecha de Nacimiento</p>
                                <p class="font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                                    <Calendar size="14" class="text-slate-400" /> {{ solicitud.fecha_nacimiento || '—' }}
                                </p>
                            </div>
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Sexo</p>
                                <p class="font-semibold text-slate-900 dark:text-white">
                                    {{ solicitud.sexo === 'M' ? 'Masculino' : solicitud.sexo === 'F' ? 'Femenino' : '—' }}
                                </p>
                            </div>
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Municipio</p>
                                <p class="font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                                    <MapPin size="14" class="text-slate-400" /> {{ solicitud.municipio || '—' }}
                                </p>
                            </div>
                            <div class="sm:col-span-2">
                                <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Dirección</p>
                                <p class="font-semibold text-slate-900 dark:text-white">{{ solicitud.direccion || '—' }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Teléfono</p>
                                <p class="font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                                    <Phone size="14" class="text-slate-400" /> {{ solicitud.telefono || '—' }}
                                </p>
                            </div>
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Correo de contacto</p>
                                <p class="font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                                    <Mail size="14" class="text-slate-400" /> {{ solicitud.correo || '—' }}
                                </p>
                            </div>
                            <div class="sm:col-span-2 flex flex-wrap gap-3 pt-2">
                                <span :class="['px-3 py-1.5 rounded-xl text-xs font-bold border', solicitud.mayahablante ? 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 border-emerald-200 dark:border-emerald-800/40' : 'bg-slate-100 dark:bg-zinc-800 text-slate-500 border-slate-200 dark:border-zinc-700']">
                                    {{ solicitud.mayahablante ? '✓ Habla Maya' : 'No maya-hablante' }}
                                </span>
                                <span :class="['px-3 py-1.5 rounded-xl text-xs font-bold border', solicitud.discapacidad ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 border-blue-200 dark:border-blue-800/40' : 'bg-slate-100 dark:bg-zinc-800 text-slate-500 border-slate-200 dark:border-zinc-700']">
                                    {{ solicitud.discapacidad ? '✓ Discapacidad declarada' : 'Sin discapacidad declarada' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Datos del negocio -->
                    <div class="bg-white dark:bg-zinc-900 rounded-3xl border border-slate-100 dark:border-zinc-800 overflow-hidden shadow-sm">
                        <div class="px-6 py-5 border-b border-slate-100 dark:border-zinc-800 flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl bg-red-50 dark:bg-red-900/20 flex items-center justify-center text-red-700">
                                <Briefcase size="20" />
                            </div>
                            <h2 class="text-lg font-black text-slate-900 dark:text-white">Datos del Proyecto / Negocio</h2>
                        </div>
                        <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-5">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Modalidad</p>
                                <p class="font-semibold text-slate-900 dark:text-white">{{ solicitud.modalidad || '—' }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Giro Comercial</p>
                                <p class="font-semibold text-slate-900 dark:text-white">{{ solicitud.giro_comercial || '—' }}</p>
                            </div>
                            <div class="sm:col-span-2">
                                <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Destino del Crédito</p>
                                <p class="font-semibold text-slate-900 dark:text-white">{{ solicitud.destino_credito || '—' }}</p>
                            </div>
                            <div class="sm:col-span-2">
                                <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Descripción del Negocio / Proyecto</p>
                                <p class="font-semibold text-slate-900 dark:text-white leading-relaxed">{{ solicitud.descripcion_negocio || '—' }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Alta SAT</p>
                                <span :class="['px-3 py-1.5 rounded-xl text-xs font-bold border', solicitud.alta_sat ? 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 border-emerald-200 dark:border-emerald-800/40' : 'bg-slate-100 dark:bg-zinc-800 text-slate-500 border-slate-200 dark:border-zinc-700']">
                                    {{ solicitud.alta_sat ? '✓ Dado de alta en SAT' : 'No dado de alta en SAT' }}
                                </span>
                            </div>
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Solicitud registrada el</p>
                                <p class="font-semibold text-slate-900 dark:text-white">{{ solicitud.created_at }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Columna derecha: Documentos -->
                <div class="space-y-4">
                    <div class="bg-white dark:bg-zinc-900 rounded-3xl border border-slate-100 dark:border-zinc-800 overflow-hidden shadow-sm">
                        <div class="px-6 py-5 border-b border-slate-100 dark:border-zinc-800 flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl bg-red-50 dark:bg-red-900/20 flex items-center justify-center text-red-700">
                                <FileText size="20" />
                            </div>
                            <h2 class="text-lg font-black text-slate-900 dark:text-white">Documentos</h2>
                        </div>
                        <div class="p-4 space-y-3">
                            <!-- Documentos subidos -->
                            <template v-if="documentos && documentos.length > 0">
                                <div v-for="doc in documentos" :key="doc.id"
                                    :class="['rounded-2xl border p-4 space-y-2', docEstilo[doc.estatus]?.bg ?? 'bg-slate-50 dark:bg-zinc-800 border-slate-200 dark:border-zinc-700']">
                                    <div class="flex items-center justify-between gap-2">
                                        <div class="flex items-center gap-2 min-w-0">
                                            <component :is="docEstilo[doc.estatus]?.icon ?? Clock" :class="['size-4 shrink-0', docEstilo[doc.estatus]?.text]" />
                                            <p class="text-xs font-bold text-slate-900 dark:text-white truncate">{{ doc.label }}</p>
                                        </div>
                                        <a :href="doc.url" target="_blank"
                                            class="shrink-0 p-1.5 rounded-lg bg-white dark:bg-zinc-800 text-slate-500 hover:text-red-700 border border-slate-200 dark:border-zinc-700 transition-colors">
                                            <ExternalLink size="12" />
                                        </a>
                                    </div>
                                    <p class="text-[10px] text-slate-500 dark:text-zinc-500 truncate">{{ doc.nombre_original }}</p>
                                    <span :class="['text-[10px] font-bold px-2 py-0.5 rounded-full', docEstilo[doc.estatus]?.text]">
                                        {{ doc.estatus }}
                                    </span>
                                    <p v-if="doc.observacion && doc.estatus === 'Rechazado'"
                                        class="text-xs text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/10 px-3 py-2 rounded-xl">
                                        {{ doc.observacion }}
                                    </p>
                                </div>
                            </template>

                            <!-- Documentos faltantes -->
                            <template v-for="(label, tipo) in tipos_documentos" :key="tipo">
                                <div v-if="!documentos?.find(d => d.tipo_documento === tipo)"
                                    class="rounded-2xl border border-dashed border-slate-200 dark:border-zinc-700 p-4 flex items-center gap-3 opacity-60">
                                    <Clock size="16" class="text-slate-400 shrink-0" />
                                    <div class="min-w-0">
                                        <p class="text-xs font-bold text-slate-600 dark:text-zinc-400">{{ label }}</p>
                                        <p class="text-[10px] text-slate-400 dark:text-zinc-600">Pendiente de subir</p>
                                    </div>
                                </div>
                            </template>

                            <Link v-if="['Borrador','Documentacion_Incompleta'].includes(solicitud.estatus)"
                                :href="route('portal.solicitud.index')"
                                class="flex items-center justify-center gap-2 w-full py-3 bg-red-700 hover:bg-red-800 text-white font-bold rounded-2xl transition-all text-sm mt-2">
                                <FileText size="15" /> Gestionar Documentos
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </BeneficiarioLayout>
</template>
