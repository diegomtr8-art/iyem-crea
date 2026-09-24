<script setup lang="ts">
import BeneficiarioLayout from '@/layouts/BeneficiarioLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import {
    Bell, FileText, ArrowRight, CheckCircle2, Clock, AlertTriangle,
    XCircle, BadgeCheck, Sparkles, Info, Banknote, ChevronRight
} from 'lucide-vue-next';

const props = defineProps<{
    solicitud: {
        id: number;
        estatus: string;
        observaciones?: string;
        nombre_completo?: string;
        credito_id?: number;
        modalidad?: string;
        docs_pendientes: number;
        docs_rechazados: number;
    } | null;
    anuncios: Array<{
        id: number;
        titulo: string;
        mensaje: string;
        tipo: string;
        leido: boolean;
        url_accion?: string;
        fecha: string;
    }>;
    credito_activo: {
        id: number;
        estatus: string;
        monto_otorgado: number;
    } | null;
    no_leidos: number;
}>();

const estatusConfig: Record<string, { label: string; color: string; bg: string; icon: any; desc: string }> = {
    Borrador: {
        label: 'Borrador',
        color: 'text-slate-600 dark:text-zinc-400',
        bg: 'bg-slate-100 dark:bg-zinc-800',
        icon: FileText,
        desc: 'Tu solicitud está en borrador. Completa tus documentos y envíala para revisión.',
    },
    Enviada: {
        label: 'Enviada — En espera de revisión',
        color: 'text-blue-700 dark:text-blue-400',
        bg: 'bg-blue-50 dark:bg-blue-900/20',
        icon: Clock,
        desc: 'Recibimos tu solicitud. El equipo CREA la revisará en breve y recibirás una notificación.',
    },
    En_Revision: {
        label: 'En Revisión',
        color: 'text-amber-700 dark:text-amber-400',
        bg: 'bg-amber-50 dark:bg-amber-900/20',
        icon: Clock,
        desc: 'Un asesor CREA está revisando tu solicitud y documentos. Pronto tendrás noticias.',
    },
    Documentacion_Incompleta: {
        label: 'Documentación Incompleta',
        color: 'text-orange-700 dark:text-orange-400',
        bg: 'bg-orange-50 dark:bg-orange-900/20',
        icon: AlertTriangle,
        desc: 'Algunos de tus documentos requieren corrección. Revisa el módulo de Solicitar Crédito.',
    },
    Aprobada: {
        label: '¡Solicitud Aprobada!',
        color: 'text-green-700 dark:text-green-400',
        bg: 'bg-green-50 dark:bg-green-900/20',
        icon: CheckCircle2,
        desc: '¡Felicidades! Tu solicitud fue aprobada. Ya puedes consultar los detalles de tu crédito.',
    },
Rechazada: {
    label: 'Solicitud Rechazada',
    color: 'text-[#6B1938] dark:text-[#f4a8c4]',
    bg: 'bg-[#6B1938]/5 dark:bg-[#6B1938]/10',
    icon: XCircle,
    desc: 'Tu solicitud no pudo ser aprobada en esta ocasión. Revisa las observaciones para más información.',
},
};

const cfg = computed(() => {
    if (!props.solicitud) return null;
    return estatusConfig[props.solicitud.estatus] ?? {
        label: props.solicitud.estatus,
        color: 'text-slate-700 dark:text-zinc-300',
        bg: 'bg-slate-100 dark:bg-zinc-800',
        icon: Info,
        desc: 'Tu solicitud se encuentra en proceso de revisión.',
    };
});

const tipoAnuncio: Record<string, { color: string; icon: any }> = {
    info:   { color: 'bg-blue-50 dark:bg-blue-900/10 border-blue-200 dark:border-blue-800/40', icon: Info },
    alerta: { color: 'bg-amber-50 dark:bg-amber-900/10 border-amber-200 dark:border-amber-800/40', icon: AlertTriangle },
    pago:   { color: 'bg-emerald-50 dark:bg-emerald-900/10 border-emerald-200 dark:border-emerald-800/40', icon: Banknote },
    exito:  { color: 'bg-green-50 dark:bg-green-900/10 border-green-200 dark:border-green-800/40', icon: CheckCircle2 },
    error:  { color: 'bg-red-50 dark:bg-red-900/10 border-red-200 dark:border-red-800/40', icon: XCircle },
};

const tipoIconColor: Record<string, string> = {
    info: 'text-blue-600', alerta: 'text-amber-600', pago: 'text-emerald-600',
    exito: 'text-green-600', error: 'text-red-600',
};
const getIconColor = (tipo: string) => tipoIconColor[tipo] ?? 'text-slate-600 dark:text-zinc-400';

const marcarLeido = (id: number) => router.post(route('portal.anuncios.leer', id), {}, { preserveScroll: true });
const marcarTodos = () => router.post(route('portal.anuncios.leer-todos'), {}, { preserveScroll: true });

const today = new Date().toLocaleDateString('es-MX', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
</script>

<template>
    <BeneficiarioLayout>
        <Head title="Mi Portal — CREA" />

        <!-- CABECERA DE BIENVENIDA -->
        <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="space-y-1">
                <p class="text-xs font-bold uppercase tracking-widest text-[#6B1938] dark:text-[#f4a8c4]">Portal Ciudadano CREA</p>
                <h1 class="text-2xl md:text-3xl font-black text-slate-900 dark:text-white">
                    ¡Bienvenido a tu portal!
                </h1>
                <p class="text-slate-500 dark:text-zinc-400 text-sm capitalize">{{ today }}</p>
            </div>
            <Link v-if="!solicitud" :href="route('portal.solicitud.index')"
                class="w-full sm:w-auto flex sm:inline-flex items-center justify-center gap-2 px-5 py-3 bg-[#6B1938] hover:bg-[#4A0E22] text-white font-bold rounded-2xl shadow-lg shadow-[#6B1938]/20 transition-all active:scale-[0.98] text-sm shrink-0">
                <Sparkles size="16" /> Solicitar mi Crédito
            </Link>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Columna principal -->
            <div class="lg:col-span-2 space-y-6">

                <!-- TARJETA DE ESTADO -->
                <div v-if="solicitud && cfg"
                    :class="['rounded-2xl border p-6 space-y-4', cfg.bg, 'border-current/10']">
                    <div class="flex items-start gap-4">
                        <div :class="['w-12 h-12 rounded-2xl flex items-center justify-center shrink-0', cfg.bg]">
                            <component :is="cfg.icon" :class="['size-6', cfg.color]" />
                        </div>
                        <div class="space-y-1 flex-1 min-w-0">
                            <div class="flex items-center flex-wrap gap-2">
                                <span :class="['text-xs font-black uppercase tracking-wider px-3 py-1 rounded-full', cfg.bg, cfg.color]">
                                    {{ cfg.label }}
                                </span>
                                <span v-if="solicitud.modalidad" class="text-xs font-medium text-slate-500 dark:text-zinc-400">
                                    Modalidad: {{ solicitud.modalidad }}
                                </span>
                            </div>
                            <p class="text-sm text-slate-600 dark:text-zinc-400">{{ cfg.desc }}</p>
                            <p v-if="solicitud.observaciones && (solicitud.estatus === 'Rechazada' || solicitud.estatus === 'Documentacion_Incompleta')"
                                class="text-sm font-medium text-slate-700 dark:text-zinc-300 bg-white dark:bg-zinc-900/50 rounded-xl px-4 py-3 mt-2 border border-slate-200 dark:border-zinc-700">
                                <strong class="text-slate-900 dark:text-white">Nota del asesor:</strong> {{ solicitud.observaciones }}
                            </p>
                        </div>
                    </div>

                    <!-- Acciones según estatus -->
                    <div class="flex flex-wrap gap-3">
                        <Link v-if="['Borrador','Documentacion_Incompleta'].includes(solicitud.estatus)"
                            :href="route('portal.solicitud.index')"
                            class="inline-flex items-center gap-2 px-4 py-2.5 bg-white dark:bg-zinc-900 text-slate-700 dark:text-zinc-300 font-bold rounded-xl border border-slate-200 dark:border-zinc-700 hover:border-red-300 dark:hover:border-red-700 transition-all text-sm">
                            <FileText size="15" />
                            {{ solicitud.estatus === 'Documentacion_Incompleta' ? 'Corregir Documentos' : 'Completar Solicitud' }}
                        </Link>
                        <Link v-if="solicitud.credito_id" :href="route('portal.credito')"
                            class="inline-flex items-center gap-2 px-4 py-2.5 bg-green-700 text-white font-bold rounded-xl hover:bg-green-800 transition-all text-sm">
                            <BadgeCheck size="15" /> Ver mi Crédito
                        </Link>
                        <div v-if="solicitud.estatus === 'Borrador' && (solicitud.docs_rechazados > 0 || solicitud.docs_pendientes > 0)"
                            class="flex items-center gap-2 text-xs text-slate-500 dark:text-zinc-400">
                            <span v-if="solicitud.docs_pendientes > 0" class="px-2 py-1 bg-slate-200 dark:bg-zinc-700 rounded-lg">
                                {{ solicitud.docs_pendientes }} doc(s) pendiente(s)
                            </span>
                            <span v-if="solicitud.docs_rechazados > 0" class="px-2 py-1 bg-red-100 dark:bg-red-900/20 text-[#6B1938] dark:text-[#f4a8c4] rounded-lg">
                                {{ solicitud.docs_rechazados }} doc(s) rechazado(s)
                            </span>
                        </div>
                    </div>
                </div>

                <!-- TARJETA CUANDO NO HAY SOLICITUD -->
                <div v-if="!solicitud"
                    class="rounded-2xl border-2 border-dashed border-slate-200 dark:border-zinc-800 p-10 text-center space-y-4">
                    <div class="w-16 h-16 rounded-2xl bg-[#6B1938]/5 dark:bg-[#6B1938]/10 flex items-center justify-center mx-auto text-[#6B1938] dark:text-[#f4a8c4]">
                        <FileText size="28" />
                    </div>
                    <div class="space-y-2">
                        <h3 class="text-xl font-black text-slate-900 dark:text-white">¿Listo para solicitar tu crédito?</h3>
                        <p class="text-slate-500 dark:text-zinc-400 max-w-sm mx-auto">
                            Aún no tienes una solicitud activa. Inicia tu proceso llenando el formulario digital — es sencillo y toma solo unos minutos.
                        </p>
                    </div>
                    <Link :href="route('portal.solicitud.index')"
                        class="inline-flex items-center gap-2 px-6 py-3 bg-[#6B1938] text-white font-bold rounded-2xl hover:bg-[#4A0E22] transition-all shadow-lg shadow-[#6B1938]/20">
                        <Sparkles size="16" /> Comenzar mi Solicitud
                        <ArrowRight size="16" />
                    </Link>
                </div>

                <!-- CRÉDITO ACTIVO CARD (si existe) -->
                <div v-if="credito_activo" class="rounded-2xl bg-gradient-to-br from-[#6B1938] to-[#4A0E22] p-6 text-white shadow-2xl shadow-[#6B1938]/30">
                    <div class="flex items-center justify-between">
                        <div class="space-y-1">
                            <p class="text-[#f4a8c4] text-xs font-bold uppercase tracking-wider">Crédito Activo</p>
                            <p class="text-3xl font-black">
                                ${{ Number(credito_activo.monto_otorgado).toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}
                            </p>
                            <span class="inline-block px-3 py-1 bg-white/20 rounded-full text-xs font-bold capitalize">{{ credito_activo.estatus }}</span>
                        </div>
                        <Link :href="route('portal.credito')"
                            class="flex items-center gap-2 px-4 py-2.5 bg-white text-[#6B1938] font-bold rounded-xl hover:bg-rose-50 transition-colors text-sm shrink-0">
                            Ver detalle <ChevronRight size="16" />
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Columna de anuncios -->
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="font-black text-slate-900 dark:text-white flex items-center gap-2">
                        <Bell size="18" class="text-[#6B1938] dark:text-[#f4a8c4]" />
                        Anuncios y Alertas
                        <span v-if="no_leidos > 0"
                            class="w-5 h-5 bg-[#6B1938] text-white text-[10px] font-black rounded-full flex items-center justify-center">
                            {{ no_leidos }}
                        </span>
                    </h2>
                    <button v-if="no_leidos > 0" @click="marcarTodos"
                        class="text-xs font-bold text-[#6B1938] dark:text-[#f4a8c4] hover:text-red-800 transition-colors">
                        Marcar todos
                    </button>
                </div>

                <!-- Lista de anuncios -->
                <div v-if="anuncios.length > 0" class="space-y-3">
                    <div v-for="anuncio in anuncios" :key="anuncio.id"
                        :class="[
                            'rounded-2xl border p-4 transition-all',
                            tipoAnuncio[anuncio.tipo]?.color ?? 'bg-slate-50 dark:bg-zinc-800 border-slate-200 dark:border-zinc-700',
                            !anuncio.leido ? 'shadow-sm' : 'opacity-70'
                        ]">
                        <div class="flex items-start gap-3">
                            <component :is="tipoAnuncio[anuncio.tipo]?.icon ?? Info" :class="['shrink-0 mt-0.5 size-4', getIconColor(anuncio.tipo)]" />
                            <div class="space-y-1 flex-1 min-w-0">
                                <p class="text-sm font-bold text-slate-900 dark:text-white">{{ anuncio.titulo }}</p>
                                <p class="text-xs text-slate-500 dark:text-zinc-400 leading-relaxed">{{ anuncio.mensaje }}</p>
                                <div class="flex items-center justify-between pt-1">
                                    <span class="text-[10px] text-slate-400 dark:text-zinc-600">{{ anuncio.fecha }}</span>
                                    <div class="flex items-center gap-2">
                                        <Link v-if="anuncio.url_accion" :href="anuncio.url_accion"
                                            class="text-[10px] font-bold text-[#6B1938] dark:text-[#f4a8c4] hover:underline">
                                            Ver más →
                                    </Link>
                                        <button v-if="!anuncio.leido" @click="marcarLeido(anuncio.id)"
                                            class="text-[10px] font-bold text-slate-400 hover:text-slate-600 dark:hover:text-zinc-300 transition-colors">
                                            ✓ Leído
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Estado vacío -->
                <div v-else class="rounded-2xl bg-white dark:bg-zinc-900 border border-slate-100 dark:border-zinc-800 p-8 text-center space-y-3">
                    <div class="w-12 h-12 rounded-2xl bg-slate-100 dark:bg-zinc-800 flex items-center justify-center mx-auto text-slate-400">
                        <Bell size="22" />
                    </div>
                    <p class="text-sm font-medium text-slate-500 dark:text-zinc-400">Sin anuncios por ahora.</p>
                    <p class="text-xs text-slate-400 dark:text-zinc-600">Las notificaciones de tu crédito y solicitud aparecerán aquí.</p>
                </div>
            </div>
        </div>
    </BeneficiarioLayout>
</template>
