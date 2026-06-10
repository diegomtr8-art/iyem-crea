<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowLeft, User, Briefcase, FileText, CreditCard, DollarSign,
    CheckCircle2, XCircle, Clock, ExternalLink, AlertTriangle,
    MapPin, Mail, IdCard, Building2, Leaf, BadgeCheck, TrendingDown,
    CalendarDays, Receipt, Scale, Eye, Download, Printer
} from 'lucide-vue-next';

const props = defineProps<{
    acreditado: {
        id: number;
        nombre_completo: string;
        curp?: string;
        rfc?: string;
        sexo?: string;
        municipio?: string;
        direccion_fiscal?: string;
        correo?: string;
        regimen?: string;
        clave_personalizada?: string;
        created_at: string;
    };
    credito: {
        id: number;
        clave_contrato: string;
        monto_otorgado: number;
        plazo_meses: number;
        fecha_entrega?: string;
        tasa_interes_ordinario: number;
        tasa_interes_moratorio: number;
        estatus: string;
        modalidad?: string;
        tiene_juridico: boolean;
    } | null;
    solicitud: {
        id: number;
        estatus: string;
        fecha_nacimiento?: string;
        telefono?: string;
        giro_comercial?: string;
        destino_credito?: string;
        descripcion_negocio?: string;
        monto_solicitado: number;
        alta_sat: boolean;
        mayahablante: boolean;
        discapacidad: boolean;
        user_email?: string;
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
    }>;
    pagos_recientes: Array<{
        folio?: string;
        fecha_pago?: string;
        monto_recibido: number;
        forma_pago?: string;
    }>;
    stats: {
        cuotas_pagadas: number;
        cuotas_pendientes: number;
        total_cuotas: number;
        total_pagado: number;
        capital_pendiente: number;
        dias_atraso: number;
        proxima_cuota?: { numero: number; fecha: string; monto: number } | null;
    } | null;
}>();

const fmt = (n: number) => Number(n ?? 0).toLocaleString('es-MX', { minimumFractionDigits: 2 });

const docEstilo: Record<string, { bg: string; badge: string; text: string; icon: any }> = {
    Aprobado:  { bg: 'bg-green-50 dark:bg-green-900/10 border-green-200 dark:border-green-800/40',  badge: 'bg-green-100 text-green-700',   text: 'text-green-600', icon: CheckCircle2 },
    Pendiente: { bg: 'bg-amber-50 dark:bg-amber-900/10 border-amber-200 dark:border-amber-800/40',  badge: 'bg-amber-100 text-amber-700',   text: 'text-amber-600', icon: Clock },
    Rechazado: { bg: 'bg-red-50 dark:bg-red-900/10 border-red-200 dark:border-red-800/40',          badge: 'bg-red-100 text-red-700',       text: 'text-red-600',   icon: XCircle },
};

const creditoEstilo: Record<string, { badge: string; dot: string }> = {
    Activo:    { badge: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400', dot: 'bg-emerald-500' },
    Moroso:    { badge: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',                dot: 'bg-red-500' },
    Liquidado: { badge: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',            dot: 'bg-blue-500' },
    Cancelado: { badge: 'bg-slate-100 text-slate-600 dark:bg-zinc-800 dark:text-zinc-400',             dot: 'bg-slate-400' },
};

const progreso = props.stats && props.stats.total_cuotas > 0
    ? Math.round((props.stats.cuotas_pagadas / props.stats.total_cuotas) * 100)
    : 0;
</script>

<template>
    <AppLayout>
        <Head :title="`Expediente — ${acreditado.nombre_completo}`" />

        <div class="p-6 space-y-6 max-w-6xl">

            <!-- Breadcrumb + acciones -->
            <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                <div class="flex items-center gap-3">
                    <Link :href="route('acreditados.show', acreditado.id)"
                        class="flex items-center gap-1.5 text-sm font-medium text-slate-500 hover:text-red-700 transition-colors">
                        <ArrowLeft size="16" /> Expediente Operativo
                    </Link>
                    <span class="text-slate-300 dark:text-zinc-700">/</span>
                    <span class="text-sm font-bold text-slate-900 dark:text-white">Expediente Digital</span>
                </div>
                <div class="sm:ml-auto flex items-center gap-2">
                    <Link v-if="credito" :href="route('operaciones.index', acreditado.id)"
                        class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-bold text-slate-600 dark:text-zinc-300 bg-slate-100 dark:bg-zinc-800 hover:bg-slate-200 dark:hover:bg-zinc-700 rounded-xl transition-all">
                        <CreditCard size="13" /> Ver Amortización
                    </Link>
                    <Link v-if="credito" :href="route('pagos.create', credito.id)"
                        class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-bold text-white bg-red-700 hover:bg-red-800 rounded-xl transition-all shadow-sm">
                        <DollarSign size="13" /> Registrar Pago
                    </Link>
                </div>
            </div>

            <!-- Encabezado del expediente -->
            <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-slate-100 dark:border-zinc-800 shadow-sm overflow-hidden">
                <div class="bg-gradient-to-r from-red-700 to-red-900 px-6 py-5 flex items-center gap-5">
                    <!-- Avatar placeholder -->
                    <div class="w-16 h-16 rounded-2xl bg-white/20 flex items-center justify-center text-white shrink-0">
                        <User size="28" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <h1 class="text-2xl font-black text-white truncate">{{ acreditado.nombre_completo }}</h1>
                        <div class="flex flex-wrap items-center gap-3 mt-1">
                            <span class="text-red-200 text-sm font-mono">{{ acreditado.curp || 'Sin CURP' }}</span>
                            <span v-if="acreditado.municipio" class="flex items-center gap-1 text-red-200 text-sm">
                                <MapPin size="12" /> {{ acreditado.municipio }}
                            </span>
                            <span class="text-red-300 text-xs">Alta: {{ acreditado.created_at }}</span>
                        </div>
                    </div>
                    <div v-if="credito" class="shrink-0 text-right">
                        <span :class="['inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-black', creditoEstilo[credito.estatus]?.badge ?? 'bg-white/20 text-white']">
                            <span :class="['w-1.5 h-1.5 rounded-full', creditoEstilo[credito.estatus]?.dot ?? 'bg-white']"></span>
                            {{ credito.estatus }}
                        </span>
                        <p class="text-red-200 text-xs mt-1 font-mono">{{ credito.clave_contrato }}</p>
                        <div v-if="credito.tiene_juridico" class="flex items-center gap-1 justify-end mt-1 text-yellow-300 text-xs font-bold">
                            <Scale size="11" /> En Jurídico
                        </div>
                    </div>
                </div>

                <!-- KPIs financieros -->
                <div v-if="stats && credito" class="grid grid-cols-2 sm:grid-cols-4 divide-x divide-y sm:divide-y-0 divide-slate-100 dark:divide-zinc-800">
                    <div class="px-5 py-4">
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Monto Otorgado</p>
                        <p class="text-xl font-black text-slate-900 dark:text-white">${{ fmt(credito.monto_otorgado) }}</p>
                        <p class="text-xs text-slate-400">{{ credito.modalidad }}</p>
                    </div>
                    <div class="px-5 py-4">
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Total Pagado</p>
                        <p class="text-xl font-black text-emerald-600">${{ fmt(stats.total_pagado) }}</p>
                        <p class="text-xs text-slate-400">{{ progreso }}% completado</p>
                    </div>
                    <div class="px-5 py-4">
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Saldo Pendiente</p>
                        <p :class="['text-xl font-black', stats.dias_atraso > 0 ? 'text-red-700' : 'text-slate-900 dark:text-white']">${{ fmt(stats.capital_pendiente) }}</p>
                        <p v-if="stats.dias_atraso > 0" class="text-xs text-red-500 font-bold">{{ stats.dias_atraso }} días de atraso</p>
                        <p v-else class="text-xs text-slate-400">Al corriente</p>
                    </div>
                    <div class="px-5 py-4">
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Avance</p>
                        <p class="text-xl font-black text-slate-900 dark:text-white">{{ stats.cuotas_pagadas }}/{{ stats.total_cuotas }}</p>
                        <div class="mt-1.5 bg-slate-100 dark:bg-zinc-800 rounded-full h-1.5">
                            <div class="bg-emerald-500 h-1.5 rounded-full transition-all" :style="{ width: `${progreso}%` }"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- Columna principal (2/3) -->
                <div class="lg:col-span-2 space-y-5">

                    <!-- Datos Personales -->
                    <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-slate-100 dark:border-zinc-800 overflow-hidden shadow-sm">
                        <div class="px-5 py-4 border-b border-slate-100 dark:border-zinc-800 flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-red-50 dark:bg-red-900/20 flex items-center justify-center text-red-700">
                                <IdCard size="18" />
                            </div>
                            <h2 class="font-black text-slate-900 dark:text-white">Datos Personales</h2>
                        </div>
                        <div class="p-5 grid grid-cols-2 sm:grid-cols-3 gap-x-6 gap-y-4 text-sm">
                            <div>
                                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-0.5">Nombre Completo</p>
                                <p class="font-semibold text-slate-900 dark:text-white">{{ acreditado.nombre_completo }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-0.5">CURP</p>
                                <p class="font-mono text-xs text-slate-700 dark:text-zinc-300">{{ acreditado.curp || '—' }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-0.5">RFC</p>
                                <p class="font-mono text-xs text-slate-700 dark:text-zinc-300">{{ acreditado.rfc || '—' }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-0.5">Sexo</p>
                                <p class="text-slate-700 dark:text-zinc-300">{{ acreditado.sexo === 'H' ? 'Hombre' : acreditado.sexo === 'M' ? 'Mujer' : '—' }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-0.5">Municipio</p>
                                <p class="text-slate-700 dark:text-zinc-300">{{ acreditado.municipio || '—' }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-0.5">Correo</p>
                                <p class="text-slate-700 dark:text-zinc-300 break-all">{{ acreditado.correo || '—' }}</p>
                            </div>
                            <div v-if="solicitud?.fecha_nacimiento">
                                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-0.5">F. Nacimiento</p>
                                <p class="text-slate-700 dark:text-zinc-300">{{ solicitud.fecha_nacimiento }}</p>
                            </div>
                            <div v-if="solicitud?.telefono">
                                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-0.5">Teléfono</p>
                                <p class="text-slate-700 dark:text-zinc-300">{{ solicitud.telefono }}</p>
                            </div>
                            <div class="col-span-2 sm:col-span-3">
                                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-0.5">Dirección Fiscal</p>
                                <p class="text-slate-700 dark:text-zinc-300">{{ acreditado.direccion_fiscal || '—' }}</p>
                            </div>
                            <div v-if="acreditado.regimen">
                                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-0.5">Régimen Fiscal</p>
                                <p class="text-slate-700 dark:text-zinc-300">{{ acreditado.regimen }}</p>
                            </div>
                            <!-- Indicadores de solicitud -->
                            <div v-if="solicitud" class="col-span-2 sm:col-span-3 flex flex-wrap gap-2 pt-1 border-t border-slate-50 dark:border-zinc-800">
                                <span :class="['px-2.5 py-1 rounded-lg text-xs font-bold', solicitud.mayahablante ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500']">
                                    {{ solicitud.mayahablante ? '✓ Maya-hablante' : 'No maya-hablante' }}
                                </span>
                                <span :class="['px-2.5 py-1 rounded-lg text-xs font-bold', solicitud.discapacidad ? 'bg-blue-100 text-blue-700' : 'bg-slate-100 text-slate-500']">
                                    {{ solicitud.discapacidad ? '✓ Con discapacidad' : 'Sin discapacidad' }}
                                </span>
                                <span :class="['px-2.5 py-1 rounded-lg text-xs font-bold', solicitud.alta_sat ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-500']">
                                    {{ solicitud.alta_sat ? '✓ Alta SAT' : 'Sin alta SAT' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Datos del Negocio (solo si hay solicitud vinculada) -->
                    <div v-if="solicitud" class="bg-white dark:bg-zinc-900 rounded-2xl border border-slate-100 dark:border-zinc-800 overflow-hidden shadow-sm">
                        <div class="px-5 py-4 border-b border-slate-100 dark:border-zinc-800 flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-red-50 dark:bg-red-900/20 flex items-center justify-center text-red-700">
                                <Briefcase size="18" />
                            </div>
                            <h2 class="font-black text-slate-900 dark:text-white">Negocio / Proyecto</h2>
                            <span class="ml-auto text-xs text-slate-400">De la solicitud #{{ solicitud.id }}</span>
                        </div>
                        <div class="p-5 grid grid-cols-2 gap-x-6 gap-y-4 text-sm">
                            <div>
                                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-0.5">Giro Comercial</p>
                                <p class="font-semibold text-slate-900 dark:text-white">{{ solicitud.giro_comercial || '—' }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-0.5">Monto Solicitado</p>
                                <p class="font-black text-slate-900 dark:text-white">${{ fmt(solicitud.monto_solicitado) }}</p>
                            </div>
                            <div class="col-span-2">
                                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-0.5">Destino del Crédito</p>
                                <p class="text-slate-700 dark:text-zinc-300">{{ solicitud.destino_credito || '—' }}</p>
                            </div>
                            <div class="col-span-2">
                                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-0.5">Descripción del Negocio</p>
                                <p class="text-slate-700 dark:text-zinc-300 leading-relaxed">{{ solicitud.descripcion_negocio || '—' }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Documentos -->
                    <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-slate-100 dark:border-zinc-800 overflow-hidden shadow-sm">
                        <div class="px-5 py-4 border-b border-slate-100 dark:border-zinc-800 flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-red-50 dark:bg-red-900/20 flex items-center justify-center text-red-700">
                                <FileText size="18" />
                            </div>
                            <h2 class="font-black text-slate-900 dark:text-white">Documentos</h2>
                            <div v-if="documentos.length > 0" class="ml-auto flex gap-2">
                                <span class="text-[10px] font-black px-2 py-0.5 rounded-full bg-green-100 text-green-700">{{ documentos.filter(d => d.estatus === 'Aprobado').length }} ✓</span>
                                <span v-if="documentos.filter(d => d.estatus === 'Pendiente').length > 0" class="text-[10px] font-black px-2 py-0.5 rounded-full bg-amber-100 text-amber-700">{{ documentos.filter(d => d.estatus === 'Pendiente').length }} pendientes</span>
                                <span v-if="documentos.filter(d => d.estatus === 'Rechazado').length > 0" class="text-[10px] font-black px-2 py-0.5 rounded-full bg-red-100 text-red-700">{{ documentos.filter(d => d.estatus === 'Rechazado').length }} rechazados</span>
                            </div>
                        </div>

                        <!-- Sin solicitud vinculada -->
                        <div v-if="!solicitud" class="p-8 text-center">
                            <div class="w-14 h-14 rounded-2xl bg-slate-50 dark:bg-zinc-800 flex items-center justify-center mx-auto mb-3">
                                <FileText size="24" class="text-slate-300 dark:text-zinc-600" />
                            </div>
                            <p class="text-slate-500 dark:text-zinc-400 text-sm font-semibold">Sin expediente digital</p>
                            <p class="text-slate-400 dark:text-zinc-500 text-xs mt-1">Este acreditado fue registrado manualmente. No cuenta con documentos digitales en el portal.</p>
                        </div>

                        <!-- Sin documentos subidos -->
                        <div v-else-if="documentos.length === 0" class="p-8 text-center">
                            <div class="w-14 h-14 rounded-2xl bg-amber-50 dark:bg-amber-900/10 flex items-center justify-center mx-auto mb-3">
                                <AlertTriangle size="24" class="text-amber-400" />
                            </div>
                            <p class="text-slate-500 dark:text-zinc-400 text-sm font-semibold">Sin documentos subidos</p>
                            <p class="text-slate-400 dark:text-zinc-500 text-xs mt-1">El ciudadano aún no ha cargado ningún documento en el portal.</p>
                        </div>

                        <!-- Grid de documentos -->
                        <div v-else class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div v-for="doc in documentos" :key="doc.id"
                                :class="['rounded-2xl border p-4 transition-all hover:shadow-sm', docEstilo[doc.estatus]?.bg]">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="flex items-start gap-2.5 min-w-0">
                                        <div :class="['w-8 h-8 rounded-xl flex items-center justify-center shrink-0 mt-0.5',
                                            doc.estatus === 'Aprobado' ? 'bg-green-100' : doc.estatus === 'Rechazado' ? 'bg-red-100' : 'bg-amber-100']">
                                            <component :is="docEstilo[doc.estatus]?.icon" size="14"
                                                :class="docEstilo[doc.estatus]?.text" />
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-sm font-bold text-slate-900 dark:text-white leading-tight">{{ doc.label }}</p>
                                            <p class="text-xs text-slate-400 dark:text-zinc-500 mt-0.5 truncate">{{ doc.nombre_original }}</p>
                                            <p class="text-[10px] text-slate-400 mt-0.5">{{ doc.updated_at }}</p>
                                        </div>
                                    </div>
                                    <div class="flex flex-col items-end gap-1.5 shrink-0">
                                        <span :class="['text-[10px] font-black px-2 py-0.5 rounded-full', docEstilo[doc.estatus]?.badge]">
                                            {{ doc.estatus }}
                                        </span>
                                        <a :href="doc.url" target="_blank"
                                            class="inline-flex items-center gap-1 px-2 py-1 bg-white dark:bg-zinc-800 border border-slate-200 dark:border-zinc-700 rounded-lg text-[10px] font-bold text-slate-500 hover:text-red-700 hover:border-red-300 transition-all">
                                            <Eye size="10" /> Ver
                                        </a>
                                    </div>
                                </div>
                                <div v-if="doc.observacion" class="mt-2 text-xs text-orange-700 dark:text-orange-400 bg-orange-50 dark:bg-orange-900/10 px-2.5 py-1.5 rounded-lg border border-orange-200 dark:border-orange-800/30">
                                    {{ doc.observacion }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Columna derecha (1/3) -->
                <div class="space-y-5">

                    <!-- Resumen del crédito -->
                    <div v-if="credito" class="bg-white dark:bg-zinc-900 rounded-2xl border border-slate-100 dark:border-zinc-800 overflow-hidden shadow-sm">
                        <div class="px-5 py-4 border-b border-slate-100 dark:border-zinc-800 flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-red-50 dark:bg-red-900/20 flex items-center justify-center text-red-700">
                                <CreditCard size="18" />
                            </div>
                            <h3 class="font-black text-slate-900 dark:text-white text-sm">Crédito CREA</h3>
                        </div>
                        <div class="p-5 space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="text-xs text-slate-400">Contrato</span>
                                <span class="font-mono text-sm font-bold text-slate-900 dark:text-white">{{ credito.clave_contrato }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-xs text-slate-400">Modalidad</span>
                                <span class="text-sm font-semibold text-slate-700 dark:text-zinc-300">{{ credito.modalidad }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-xs text-slate-400">Monto</span>
                                <span class="text-sm font-black text-slate-900 dark:text-white">${{ fmt(credito.monto_otorgado) }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-xs text-slate-400">Plazo</span>
                                <span class="text-sm text-slate-700 dark:text-zinc-300">{{ credito.plazo_meses }} meses</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-xs text-slate-400">Fecha entrega</span>
                                <span class="text-sm text-slate-700 dark:text-zinc-300">{{ credito.fecha_entrega }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-xs text-slate-400">Tasa ordinaria</span>
                                <span class="text-sm font-semibold text-slate-700 dark:text-zinc-300">{{ credito.tasa_interes_ordinario }}% anual</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-xs text-slate-400">Tasa moratoria</span>
                                <span class="text-sm text-slate-700 dark:text-zinc-300">{{ credito.tasa_interes_moratorio }}% anual</span>
                            </div>

                            <!-- Próxima cuota -->
                            <div v-if="stats?.proxima_cuota" class="pt-3 mt-1 border-t border-slate-100 dark:border-zinc-800">
                                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-2">Próxima Cuota</p>
                                <div :class="['rounded-xl p-3 flex items-center justify-between', stats.dias_atraso > 0 ? 'bg-red-50 dark:bg-red-900/10 border border-red-200 dark:border-red-800/30' : 'bg-slate-50 dark:bg-zinc-800']">
                                    <div>
                                        <p class="text-xs text-slate-500 dark:text-zinc-400">Cuota #{{ stats.proxima_cuota.numero }}</p>
                                        <p class="text-sm font-bold text-slate-900 dark:text-white">{{ stats.proxima_cuota.fecha }}</p>
                                    </div>
                                    <p :class="['text-sm font-black', stats.dias_atraso > 0 ? 'text-red-700' : 'text-emerald-600']">
                                        ${{ fmt(stats.proxima_cuota.monto) }}
                                    </p>
                                </div>
                                <p v-if="stats.dias_atraso > 0" class="text-xs text-red-600 font-bold mt-1.5 flex items-center gap-1">
                                    <AlertTriangle size="11" /> {{ stats.dias_atraso }} días de atraso
                                </p>
                            </div>

                            <!-- Links rápidos -->
                            <div class="pt-3 border-t border-slate-100 dark:border-zinc-800 space-y-2">
                                <Link :href="route('operaciones.index', acreditado.id)"
                                    class="flex items-center gap-2 w-full py-2.5 px-3 bg-red-700 hover:bg-red-800 text-white font-bold rounded-xl text-xs transition-all text-center justify-center">
                                    <CreditCard size="13" /> Ver Tabla de Amortización
                                </Link>
                                <Link :href="route('pagos.create', credito.id)"
                                    class="flex items-center gap-2 w-full py-2.5 px-3 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs transition-all text-center justify-center">
                                    <DollarSign size="13" /> Registrar Pago
                                </Link>
                                <Link v-if="credito.tiene_juridico" :href="route('cobranza.show', credito.id)"
                                    class="flex items-center gap-2 w-full py-2.5 px-3 bg-purple-600 hover:bg-purple-700 text-white font-bold rounded-xl text-xs transition-all text-center justify-center">
                                    <Scale size="13" /> Ver Expediente Jurídico
                                </Link>
                                <Link v-else-if="credito.estatus === 'Moroso'" :href="route('cobranza.show', credito.id)"
                                    class="flex items-center gap-2 w-full py-2.5 px-3 border border-red-200 dark:border-red-800/40 text-red-700 dark:text-red-400 font-bold rounded-xl text-xs transition-all text-center justify-center hover:bg-red-50">
                                    <TrendingDown size="13" /> Ver en Cobranza
                                </Link>
                            </div>
                        </div>
                    </div>

                    <!-- Sin crédito -->
                    <div v-else class="bg-slate-50 dark:bg-zinc-800 rounded-2xl border border-slate-100 dark:border-zinc-700 p-6 text-center">
                        <CreditCard size="28" class="mx-auto text-slate-300 dark:text-zinc-600 mb-2" />
                        <p class="text-sm font-bold text-slate-500 dark:text-zinc-400">Sin crédito activo</p>
                        <p class="text-xs text-slate-400 mt-1">Este acreditado no tiene crédito registrado.</p>
                    </div>

                    <!-- Últimos pagos -->
                    <div v-if="pagos_recientes.length > 0" class="bg-white dark:bg-zinc-900 rounded-2xl border border-slate-100 dark:border-zinc-800 overflow-hidden shadow-sm">
                        <div class="px-5 py-4 border-b border-slate-100 dark:border-zinc-800 flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 flex items-center justify-center text-emerald-600">
                                <Receipt size="16" />
                            </div>
                            <h3 class="font-black text-slate-900 dark:text-white text-sm">Últimos Pagos</h3>
                        </div>
                        <div class="divide-y divide-slate-50 dark:divide-zinc-800">
                            <div v-for="pago in pagos_recientes" :key="pago.folio" class="px-5 py-3 flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-sm font-bold text-slate-900 dark:text-white">${{ fmt(pago.monto_recibido) }}</p>
                                    <p class="text-xs text-slate-400">{{ pago.fecha_pago }} · {{ pago.forma_pago }}</p>
                                </div>
                                <span class="text-[10px] font-mono text-slate-400 dark:text-zinc-500">{{ pago.folio }}</span>
                            </div>
                        </div>
                        <div class="px-5 py-3 border-t border-slate-50 dark:border-zinc-800">
                            <Link v-if="credito" :href="route('operaciones.index', acreditado.id)"
                                class="text-xs font-bold text-red-700 hover:text-red-800 transition-colors">
                                Ver historial completo →
                            </Link>
                        </div>
                    </div>

                    <!-- Info solicitud original -->
                    <div v-if="solicitud" class="bg-white dark:bg-zinc-900 rounded-2xl border border-slate-100 dark:border-zinc-800 p-5 shadow-sm space-y-2">
                        <h3 class="font-black text-slate-900 dark:text-white text-sm flex items-center gap-2">
                            <BadgeCheck size="15" class="text-red-700" /> Solicitud Original
                        </h3>
                        <div class="space-y-1.5 text-xs text-slate-500 dark:text-zinc-400">
                            <div class="flex justify-between">
                                <span>Solicitud #</span><span class="font-bold text-slate-700 dark:text-zinc-200">{{ solicitud.id }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span>Estatus</span>
                                <span :class="['font-bold', solicitud.estatus === 'Aprobada' ? 'text-green-600' : solicitud.estatus === 'Rechazada' ? 'text-red-600' : 'text-slate-600']">
                                    {{ solicitud.estatus }}
                                </span>
                            </div>
                            <div v-if="solicitud.user_email" class="flex justify-between gap-2">
                                <span>Cuenta</span><span class="font-semibold text-slate-700 dark:text-zinc-200 break-all text-right">{{ solicitud.user_email }}</span>
                            </div>
                        </div>
                        <Link :href="route('solicitudes.show', solicitud.id)"
                            class="flex items-center justify-center gap-1.5 w-full py-2 mt-2 border border-slate-200 dark:border-zinc-700 rounded-xl text-xs font-bold text-slate-600 hover:border-red-300 hover:text-red-700 transition-colors">
                            Ver solicitud original →
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
