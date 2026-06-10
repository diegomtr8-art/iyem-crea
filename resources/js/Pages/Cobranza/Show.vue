<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import {
    ArrowLeft, User, DollarSign, Clock, Phone, Mail, MapPin,
    Gavel, Plus, CheckCircle2, AlertTriangle, PhoneCall, MessageSquare,
    Send, Calendar, FileText, ChevronDown, ChevronUp, Building2
} from 'lucide-vue-next';

const props = defineProps<{
    credito: {
        id: number;
        clave_contrato: string;
        monto_otorgado: number;
        plazo_meses: number;
        fecha_entrega: string;
        tasa_moratorio: number;
        deuda_total: number;
        cuotas_pendientes: number;
        dias_atraso: number;
        ultimo_pago?: string;
        modalidad?: string;
        acreditado: { id: number; nombre_completo: string; curp: string; rfc: string; municipio: string; correo: string };
        expediente_juridico?: { id: number; estatus: string } | null;
    };
    gestiones: Array<{
        id: number;
        tipo_gestion: string;
        descripcion: string;
        resultado: string;
        fecha_gestion: string;
        fecha_compromiso_pago?: string;
        monto_comprometido?: number;
        usuario: string;
    }>;
    cuotas_proximas: Array<{ numero_cuota: number; fecha_vencimiento: string; pago_restante: number; cuota_fija: number }>;
}>();

const showGestionForm  = ref(false);
const showJuridicoForm = ref(false);

const gestionForm = useForm({
    tipo_gestion: 'Llamada',
    descripcion: '',
    resultado: 'Contactado',
    fecha_gestion: new Date().toISOString().split('T')[0],
    fecha_compromiso_pago: '',
    monto_comprometido: '',
});

const juridicoForm = useForm({
    fecha_envio_juridico: new Date().toISOString().split('T')[0],
    monto_reclamado: props.credito.deuda_total,
    tribunal: '',
    juzgado: '',
    numero_expediente_judicial: '',
    observaciones: '',
});

const submitGestion = () => {
    gestionForm.post(route('cobranza.gestion', props.credito.id), {
        onSuccess: () => {
            showGestionForm.value = false;
            gestionForm.reset('descripcion', 'fecha_compromiso_pago', 'monto_comprometido');
        },
    });
};

const submitJuridico = () => {
    juridicoForm.post(route('cobranza.juridico', props.credito.id), {
        onSuccess: () => { showJuridicoForm.value = false; },
    });
};

const tiposGestion = ['Llamada', 'Visita', 'WhatsApp', 'Email', 'Carta', 'Acuerdo', 'Otro'];
const resultados = ['Contactado', 'No_Contactado', 'Promesa_Pago', 'Sin_Respuesta', 'Acuerdo_Alcanzado', 'Otro'];

const gestionIcon: Record<string, any> = {
    Llamada: PhoneCall, Visita: User, WhatsApp: MessageSquare,
    Email: Mail, Carta: FileText, Acuerdo: CheckCircle2, Otro: Clock,
};

const resultadoColor: Record<string, string> = {
    Contactado: 'text-green-700 bg-green-50 dark:bg-green-900/20 dark:text-green-400',
    No_Contactado: 'text-slate-600 bg-slate-100 dark:bg-zinc-800 dark:text-zinc-400',
    Promesa_Pago: 'text-blue-700 bg-blue-50 dark:bg-blue-900/20 dark:text-blue-400',
    Sin_Respuesta: 'text-orange-700 bg-orange-50 dark:bg-orange-900/20 dark:text-orange-400',
    Acuerdo_Alcanzado: 'text-emerald-700 bg-emerald-50 dark:bg-emerald-900/20 dark:text-emerald-400',
    Otro: 'text-slate-500 bg-slate-100 dark:bg-zinc-800',
};

const diasColor = computed(() => {
    const d = props.credito.dias_atraso;
    if (d === 0)  return 'text-slate-600 bg-slate-100 dark:bg-zinc-800 dark:text-zinc-400';
    if (d <= 30)  return 'text-amber-700 bg-amber-100 dark:bg-amber-900/30 dark:text-amber-400';
    if (d <= 60)  return 'text-orange-700 bg-orange-100 dark:bg-orange-900/30 dark:text-orange-400';
    if (d <= 90)  return 'text-red-700 bg-red-100 dark:bg-red-900/30 dark:text-red-400';
    return 'text-red-900 bg-red-200 dark:bg-red-900/50 dark:text-red-300';
});

const fmt = (n: number) => '$' + n.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
</script>

<template>
    <AppLayout>
        <Head :title="`Cobranza — ${credito.clave_contrato}`" />

        <div class="p-6 space-y-6">
            <!-- Header -->
            <div class="flex items-center gap-4">
                <Link :href="route('cobranza.index')"
                    class="p-2 rounded-xl bg-slate-100 dark:bg-zinc-800 text-slate-600 dark:text-zinc-400 hover:bg-slate-200 dark:hover:bg-zinc-700 transition-all">
                    <ArrowLeft size="18" />
                </Link>
                <div class="flex-1">
                    <h1 class="text-2xl font-black text-slate-900 dark:text-white">
                        Gestión de Cobranza
                    </h1>
                    <p class="text-slate-500 dark:text-zinc-400 text-sm mt-0.5">
                        {{ credito.acreditado.nombre_completo }} · {{ credito.clave_contrato }}
                    </p>
                </div>
                <div v-if="!credito.expediente_juridico" class="flex gap-2">
                    <button @click="showGestionForm = !showGestionForm"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-red-700 hover:bg-red-800 text-white font-bold rounded-xl text-sm transition-all">
                        <Plus size="15" /> Nueva Gestión
                    </button>
                    <button @click="showJuridicoForm = !showJuridicoForm"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-purple-700 hover:bg-purple-800 text-white font-bold rounded-xl text-sm transition-all">
                        <Gavel size="15" /> Enviar a Jurídico
                    </button>
                </div>
                <div v-else>
                    <Link :href="route('juridico.show', credito.expediente_juridico.id)"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400 font-bold rounded-xl text-sm transition-all">
                        <Gavel size="15" /> Ver Expediente Jurídico
                    </Link>
                </div>
            </div>

            <!-- Métricas clave -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div :class="['rounded-2xl p-5', diasColor]">
                    <p class="text-[11px] font-bold uppercase tracking-widest opacity-70 mb-1">Días de Atraso</p>
                    <p class="text-4xl font-black">{{ credito.dias_atraso }}</p>
                    <p class="text-xs font-semibold mt-1 opacity-80">{{ credito.dias_atraso === 0 ? 'Al corriente' : 'días sin pagar' }}</p>
                </div>
                <div class="rounded-2xl p-5 bg-red-50 dark:bg-red-900/20">
                    <p class="text-[11px] font-bold uppercase tracking-widest text-red-700 mb-1">Deuda Total</p>
                    <p class="text-2xl font-black text-red-700 leading-tight">{{ fmt(credito.deuda_total) }}</p>
                    <p class="text-xs font-semibold text-red-600/70 mt-1">{{ credito.cuotas_pendientes }} cuotas pendientes</p>
                </div>
                <div class="rounded-2xl p-5 bg-slate-100 dark:bg-zinc-800">
                    <p class="text-[11px] font-bold uppercase tracking-widest text-slate-500 mb-1">Último Pago</p>
                    <p class="text-xl font-black text-slate-800 dark:text-white">{{ credito.ultimo_pago ?? '—' }}</p>
                    <p class="text-xs font-semibold text-slate-500 mt-1">{{ credito.modalidad }}</p>
                </div>
                <div class="rounded-2xl p-5 bg-blue-50 dark:bg-blue-900/20">
                    <p class="text-[11px] font-bold uppercase tracking-widest text-blue-700 mb-1">Monto Original</p>
                    <p class="text-xl font-black text-blue-700 leading-tight">{{ fmt(credito.monto_otorgado) }}</p>
                    <p class="text-xs font-semibold text-blue-600/70 mt-1">{{ credito.plazo_meses }} meses · Entrega {{ credito.fecha_entrega }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- Columna izquierda -->
                <div class="lg:col-span-2 space-y-5">

                    <!-- Form nueva gestión -->
                    <div v-if="showGestionForm"
                        class="bg-white dark:bg-zinc-900 rounded-2xl border border-slate-200 dark:border-zinc-700 overflow-hidden shadow-sm">
                        <div class="px-5 py-4 border-b border-slate-100 dark:border-zinc-800 flex items-center justify-between">
                            <h3 class="font-black text-slate-900 dark:text-white flex items-center gap-2">
                                <Plus size="16" class="text-red-700" /> Nueva Gestión de Cobranza
                            </h3>
                            <button @click="showGestionForm = false" class="text-slate-400 hover:text-slate-600">
                                <ChevronUp size="18" />
                            </button>
                        </div>
                        <form @submit.prevent="submitGestion" class="p-5 space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">Tipo de Gestión</label>
                                    <select v-model="gestionForm.tipo_gestion" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-zinc-700 dark:bg-zinc-800 text-sm focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-all">
                                        <option v-for="t in tiposGestion" :key="t" :value="t">{{ t }}</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">Resultado</label>
                                    <select v-model="gestionForm.resultado" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-zinc-700 dark:bg-zinc-800 text-sm focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-all">
                                        <option v-for="r in resultados" :key="r" :value="r">{{ r.replace('_', ' ') }}</option>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">Fecha de Gestión</label>
                                <input v-model="gestionForm.fecha_gestion" type="date"
                                    class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-zinc-700 dark:bg-zinc-800 text-sm focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-all" />
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">Descripción / Notas</label>
                                <textarea v-model="gestionForm.descripcion" rows="3" placeholder="Detalla lo que ocurrió en la gestión..."
                                    class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-zinc-700 dark:bg-zinc-800 text-sm focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-all resize-none"></textarea>
                            </div>
                            <div v-if="['Promesa_Pago','Acuerdo_Alcanzado'].includes(gestionForm.resultado)"
                                class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">Fecha Compromiso de Pago</label>
                                    <input v-model="gestionForm.fecha_compromiso_pago" type="date"
                                        class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-zinc-700 dark:bg-zinc-800 text-sm focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-all" />
                                </div>
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">Monto Comprometido</label>
                                    <input v-model="gestionForm.monto_comprometido" type="number" step="0.01" placeholder="0.00"
                                        class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-zinc-700 dark:bg-zinc-800 text-sm focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-all" />
                                </div>
                            </div>
                            <div class="flex justify-end gap-3 pt-2">
                                <button type="button" @click="showGestionForm = false" class="px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100 dark:hover:bg-zinc-800 rounded-xl transition-colors">Cancelar</button>
                                <button type="submit" :disabled="gestionForm.processing"
                                    class="inline-flex items-center gap-2 px-5 py-2 bg-red-700 hover:bg-red-800 text-white font-bold rounded-xl text-sm transition-all disabled:opacity-50">
                                    <Send size="14" /> Registrar Gestión
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Form enviar a jurídico -->
                    <div v-if="showJuridicoForm"
                        class="bg-white dark:bg-zinc-900 rounded-2xl border-2 border-purple-200 dark:border-purple-800/50 overflow-hidden shadow-sm">
                        <div class="px-5 py-4 border-b border-purple-100 dark:border-purple-900/50 flex items-center justify-between bg-purple-50 dark:bg-purple-900/20">
                            <h3 class="font-black text-purple-900 dark:text-purple-300 flex items-center gap-2">
                                <Gavel size="16" /> Enviar a Cobranza Jurídica
                            </h3>
                            <button @click="showJuridicoForm = false" class="text-purple-400 hover:text-purple-600">
                                <ChevronUp size="18" />
                            </button>
                        </div>
                        <form @submit.prevent="submitJuridico" class="p-5 space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">Fecha de Envío a Jurídico</label>
                                    <input v-model="juridicoForm.fecha_envio_juridico" type="date"
                                        class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-zinc-700 dark:bg-zinc-800 text-sm focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 transition-all" />
                                </div>
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">Monto Reclamado</label>
                                    <input v-model="juridicoForm.monto_reclamado" type="number" step="0.01"
                                        class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-zinc-700 dark:bg-zinc-800 text-sm focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 transition-all" />
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">Tribunal</label>
                                    <input v-model="juridicoForm.tribunal" type="text" placeholder="Ej. Tribunal Superior de Justicia de Yucatán"
                                        class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-zinc-700 dark:bg-zinc-800 text-sm focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 transition-all" />
                                </div>
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">Juzgado</label>
                                    <input v-model="juridicoForm.juzgado" type="text" placeholder="Ej. Juzgado 3ro Civil"
                                        class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-zinc-700 dark:bg-zinc-800 text-sm focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 transition-all" />
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">No. Expediente Judicial</label>
                                <input v-model="juridicoForm.numero_expediente_judicial" type="text" placeholder="Opcional"
                                    class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-zinc-700 dark:bg-zinc-800 text-sm focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 transition-all" />
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">Observaciones</label>
                                <textarea v-model="juridicoForm.observaciones" rows="2"
                                    class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-zinc-700 dark:bg-zinc-800 text-sm focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 transition-all resize-none"></textarea>
                            </div>
                            <div class="flex justify-end gap-3 pt-2">
                                <button type="button" @click="showJuridicoForm = false" class="px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100 dark:hover:bg-zinc-800 rounded-xl transition-colors">Cancelar</button>
                                <button type="submit" :disabled="juridicoForm.processing"
                                    class="inline-flex items-center gap-2 px-5 py-2 bg-purple-700 hover:bg-purple-800 text-white font-bold rounded-xl text-sm transition-all disabled:opacity-50">
                                    <Gavel size="14" /> Enviar a Jurídico
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Historial de gestiones -->
                    <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-slate-100 dark:border-zinc-800 overflow-hidden shadow-sm">
                        <div class="px-5 py-4 border-b border-slate-100 dark:border-zinc-800 flex items-center justify-between">
                            <h3 class="font-black text-slate-900 dark:text-white flex items-center gap-2">
                                <PhoneCall size="16" class="text-red-700" /> Historial de Gestiones
                                <span class="text-xs font-bold px-2 py-0.5 bg-red-50 dark:bg-red-900/20 text-red-700 rounded-full">{{ gestiones.length }}</span>
                            </h3>
                        </div>

                        <div v-if="gestiones.length === 0" class="p-10 text-center text-slate-400 dark:text-zinc-600">
                            <PhoneCall size="32" class="mx-auto mb-3 opacity-40" />
                            <p class="text-sm font-medium">Sin gestiones registradas aún.</p>
                            <p class="text-xs mt-1">Usa el botón "Nueva Gestión" para registrar acciones de cobranza.</p>
                        </div>

                        <div v-else class="divide-y divide-slate-50 dark:divide-zinc-800">
                            <div v-for="g in gestiones" :key="g.id" class="p-5 flex gap-4">
                                <div class="w-9 h-9 rounded-xl bg-slate-100 dark:bg-zinc-800 flex items-center justify-center shrink-0 text-slate-600 dark:text-zinc-400">
                                    <component :is="gestionIcon[g.tipo_gestion] ?? Clock" size="16" />
                                </div>
                                <div class="flex-1 space-y-1 min-w-0">
                                    <div class="flex items-center flex-wrap gap-2">
                                        <span class="text-sm font-bold text-slate-900 dark:text-white">{{ g.tipo_gestion }}</span>
                                        <span :class="['text-[11px] font-bold px-2 py-0.5 rounded-full', resultadoColor[g.resultado]]">
                                            {{ g.resultado.replace('_', ' ') }}
                                        </span>
                                    </div>
                                    <p class="text-sm text-slate-600 dark:text-zinc-400 leading-relaxed">{{ g.descripcion }}</p>
                                    <div v-if="g.monto_comprometido" class="text-xs font-semibold text-blue-700 dark:text-blue-400">
                                        Compromiso: {{ fmt(g.monto_comprometido) }} para {{ g.fecha_compromiso_pago }}
                                    </div>
                                    <div class="flex items-center gap-3 text-[11px] text-slate-400 dark:text-zinc-600 pt-1">
                                        <span class="flex items-center gap-1"><Calendar size="11" /> {{ g.fecha_gestion }}</span>
                                        <span class="flex items-center gap-1"><User size="11" /> {{ g.usuario }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Columna derecha: datos acreditado + cuotas -->
                <div class="space-y-5">
                    <!-- Datos del acreditado -->
                    <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-slate-100 dark:border-zinc-800 overflow-hidden shadow-sm">
                        <div class="px-5 py-4 border-b border-slate-100 dark:border-zinc-800">
                            <h3 class="font-black text-slate-900 dark:text-white flex items-center gap-2">
                                <User size="15" class="text-red-700" /> Acreditado
                            </h3>
                        </div>
                        <div class="p-5 space-y-3">
                            <div>
                                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Nombre</p>
                                <p class="font-semibold text-slate-900 dark:text-white text-sm">{{ credito.acreditado.nombre_completo }}</p>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">CURP</p>
                                    <p class="font-mono text-xs text-slate-700 dark:text-zinc-300">{{ credito.acreditado.curp }}</p>
                                </div>
                                <div>
                                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">RFC</p>
                                    <p class="font-mono text-xs text-slate-700 dark:text-zinc-300">{{ credito.acreditado.rfc || '—' }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 text-xs text-slate-600 dark:text-zinc-400">
                                <MapPin size="13" class="shrink-0 text-slate-400" /> {{ credito.acreditado.municipio }}
                            </div>
                            <div class="flex items-center gap-2 text-xs text-slate-600 dark:text-zinc-400">
                                <Mail size="13" class="shrink-0 text-slate-400" /> {{ credito.acreditado.correo }}
                            </div>
                            <Link :href="route('acreditados.show', credito.acreditado.id)"
                                class="flex items-center justify-center gap-2 w-full py-2 border border-slate-200 dark:border-zinc-700 rounded-xl text-xs font-bold text-slate-600 dark:text-zinc-400 hover:border-red-300 hover:text-red-700 transition-colors mt-2">
                                Ver Expediente Completo
                            </Link>
                        </div>
                    </div>

                    <!-- Próximas cuotas -->
                    <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-slate-100 dark:border-zinc-800 overflow-hidden shadow-sm">
                        <div class="px-5 py-4 border-b border-slate-100 dark:border-zinc-800">
                            <h3 class="font-black text-slate-900 dark:text-white flex items-center gap-2">
                                <Calendar size="15" class="text-red-700" /> Cuotas Pendientes
                            </h3>
                        </div>
                        <div class="p-4 space-y-2">
                            <div v-for="c in cuotas_proximas" :key="c.numero_cuota"
                                class="flex items-center justify-between p-3 rounded-xl bg-slate-50 dark:bg-zinc-800">
                                <div>
                                    <p class="text-xs font-bold text-slate-700 dark:text-zinc-300">Cuota #{{ c.numero_cuota }}</p>
                                    <p class="text-[11px] text-slate-400">Vence {{ c.fecha_vencimiento }}</p>
                                </div>
                                <p class="text-sm font-bold text-red-700 dark:text-red-400">{{ fmt(c.pago_restante) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
