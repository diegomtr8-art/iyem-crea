<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { ArrowLeft, FileText, CheckCircle2, XCircle, ExternalLink } from 'lucide-vue-next';

const props = defineProps<{
    comprobacion: {
        id: number;
        estatus: string;
        fecha_desembolso?: string;
        fecha_limite_comprobacion?: string;
        dias_restantes: number;
        semaforo: string;
        observaciones_acreditado?: string;
        observaciones_operativo?: string;
        monto_comprobado?: number;
        fecha_revision?: string;
        revisado_por?: string;
        credito: { clave_contrato?: string; monto_otorgado?: number; modalidad?: string };
        acreditado: { nombre_completo?: string; municipio?: string };
        documentos: Array<{ id: number; tipo: string; descripcion?: string; monto?: number; proveedor?: string; nombre_original: string; url: string }>;
        total_comprobado: number;
    };
}>();

const mostrarAprobar = ref(false);
const mostrarRechazar = ref(false);

const formAprobar = useForm({
    monto_comprobado: props.comprobacion.total_comprobado ?? 0,
    observaciones_operativo: '',
});

const formRechazar = useForm({
    observaciones_operativo: '',
});

const aprobar = () => {
    formAprobar.post(route('comprobaciones.aprobar', props.comprobacion.id), {
        onSuccess: () => (mostrarAprobar.value = false),
    });
};

const rechazar = () => {
    formRechazar.post(route('comprobaciones.rechazar', props.comprobacion.id), {
        onSuccess: () => (mostrarRechazar.value = false),
    });
};

const tipoLabel: Record<string, string> = {
    factura: 'Factura', nota_venta: 'Nota de venta', foto_bien: 'Foto del bien', otro: 'Otro',
};
</script>

<template>
    <AppLayout>
        <Head title="Detalle de Comprobación — CREA" />

        <div class="p-6 space-y-6 max-w-4xl">
            <Link :href="route('comprobaciones.index')" class="inline-flex items-center gap-2 text-sm text-slate-500 hover:text-slate-800 dark:hover:text-white">
                <ArrowLeft size="15" /> Volver a Comprobaciones
            </Link>

            <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-slate-100 dark:border-zinc-800 p-6 space-y-4 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-xl font-black text-slate-900 dark:text-white">{{ comprobacion.acreditado.nombre_completo }}</h1>
                        <p class="text-sm text-slate-500 dark:text-zinc-400">
                            Contrato {{ comprobacion.credito.clave_contrato }} · {{ comprobacion.credito.modalidad }} · {{ comprobacion.acreditado.municipio }}
                        </p>
                    </div>
                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-slate-100 dark:bg-zinc-800 text-slate-600 dark:text-zinc-300">
                        {{ comprobacion.estatus.replace('_', ' ') }}
                    </span>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
                    <div><p class="text-slate-400 text-xs">Monto otorgado</p><p class="font-bold">${{ Number(comprobacion.credito.monto_otorgado).toLocaleString('es-MX') }}</p></div>
                    <div><p class="text-slate-400 text-xs">Fecha desembolso</p><p class="font-bold">{{ comprobacion.fecha_desembolso }}</p></div>
                    <div><p class="text-slate-400 text-xs">Fecha límite</p><p class="font-bold">{{ comprobacion.fecha_limite_comprobacion }}</p></div>
                    <div><p class="text-slate-400 text-xs">Días restantes</p><p class="font-bold">{{ comprobacion.dias_restantes > 0 ? comprobacion.dias_restantes : 'Vencido' }}</p></div>
                </div>

                <div v-if="comprobacion.observaciones_acreditado" class="rounded-xl bg-slate-50 dark:bg-zinc-800 p-4 text-sm">
                    <p class="text-xs font-bold text-slate-400 mb-1">Observaciones del acreditado</p>
                    <p>{{ comprobacion.observaciones_acreditado }}</p>
                </div>

                <div v-if="comprobacion.observaciones_operativo" class="rounded-xl bg-amber-50 dark:bg-amber-900/20 p-4 text-sm">
                    <p class="text-xs font-bold text-amber-600 mb-1">Observaciones del operativo {{ comprobacion.revisado_por ? `(${comprobacion.revisado_por})` : '' }}</p>
                    <p>{{ comprobacion.observaciones_operativo }}</p>
                </div>
            </div>

            <!-- Documentos -->
            <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-slate-100 dark:border-zinc-800 p-6 shadow-sm">
                <h2 class="font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                    <FileText size="18" /> Comprobantes ({{ comprobacion.documentos.length }})
                </h2>
                <div class="divide-y divide-slate-50 dark:divide-zinc-800">
                    <div v-for="d in comprobacion.documentos" :key="d.id" class="py-3 flex items-center justify-between gap-4 text-sm">
                        <div>
                            <p class="font-semibold text-slate-800 dark:text-zinc-200">{{ tipoLabel[d.tipo] || d.tipo }} — {{ d.descripcion || d.nombre_original }}</p>
                            <p class="text-xs text-slate-400">{{ d.proveedor || '—' }} · {{ d.monto ? '$' + Number(d.monto).toLocaleString('es-MX') : 'Sin monto' }}</p>
                        </div>
                        <a :href="d.url" target="_blank" class="inline-flex items-center gap-1 text-red-700 dark:text-red-400 text-xs font-bold hover:underline">
                            <ExternalLink size="13" /> Ver
                        </a>
                    </div>
                    <p v-if="comprobacion.documentos.length === 0" class="text-slate-400 text-sm py-6 text-center">Aún no se han subido comprobantes.</p>
                </div>
                <div class="mt-4 pt-4 border-t border-slate-100 dark:border-zinc-800 flex justify-between text-sm font-bold">
                    <span>Total comprobado</span>
                    <span>${{ Number(comprobacion.total_comprobado || 0).toLocaleString('es-MX') }}</span>
                </div>
            </div>

            <!-- Acciones -->
            <div v-if="['Pendiente', 'En_Revision'].includes(comprobacion.estatus)" class="flex flex-wrap gap-3">
                <button @click="mostrarAprobar = true; mostrarRechazar = false"
                    class="inline-flex items-center gap-2 px-4 py-2.5 bg-green-600 hover:bg-green-700 text-white rounded-xl text-sm font-bold shadow-sm">
                    <CheckCircle2 size="16" /> Aprobar comprobación
                </button>
                <button @click="mostrarRechazar = true; mostrarAprobar = false"
                    class="inline-flex items-center gap-2 px-4 py-2.5 bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 hover:bg-red-100 rounded-xl text-sm font-bold">
                    <XCircle size="16" /> Rechazar
                </button>
            </div>

            <form v-if="mostrarAprobar" @submit.prevent="aprobar" class="bg-white dark:bg-zinc-900 rounded-2xl border border-slate-100 dark:border-zinc-800 p-6 space-y-3 shadow-sm">
                <label class="block text-sm font-semibold">Monto comprobado</label>
                <input v-model.number="formAprobar.monto_comprobado" type="number" step="0.01" min="0"
                    class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-zinc-700 dark:bg-zinc-800 text-sm" />
                <label class="block text-sm font-semibold">Observaciones (opcional)</label>
                <textarea v-model="formAprobar.observaciones_operativo" rows="3"
                    class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-zinc-700 dark:bg-zinc-800 text-sm"></textarea>
                <button type="submit" :disabled="formAprobar.processing"
                    class="px-5 py-2.5 bg-green-600 hover:bg-green-700 text-white rounded-xl text-sm font-bold disabled:opacity-50">
                    Confirmar aprobación
                </button>
            </form>

            <form v-if="mostrarRechazar" @submit.prevent="rechazar" class="bg-white dark:bg-zinc-900 rounded-2xl border border-slate-100 dark:border-zinc-800 p-6 space-y-3 shadow-sm">
                <label class="block text-sm font-semibold">Motivo del rechazo (obligatorio)</label>
                <textarea v-model="formRechazar.observaciones_operativo" rows="3" required
                    class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-zinc-700 dark:bg-zinc-800 text-sm"></textarea>
                <button type="submit" :disabled="formRechazar.processing"
                    class="px-5 py-2.5 bg-red-700 hover:bg-red-800 text-white rounded-xl text-sm font-bold disabled:opacity-50">
                    Confirmar rechazo
                </button>
            </form>
        </div>
    </AppLayout>
</template>
