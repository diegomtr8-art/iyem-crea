<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { TrendingDown, AlertTriangle, Download } from 'lucide-vue-next';

const props = defineProps<{
    creditos: any[];
    kpis: {
        total_en_mora: number;
        cartera_vencida: number;
        cartera_bruta: number;
        indice_morosidad: number;
        por_semaforo: Record<string, number>;
    };
    modalidades: { id: number; nombre: string }[];
    filtros: { modalidadId: number | null; semaforo: string | null };
}>();

const buscar      = ref('');
const filtroMod   = ref(props.filtros.modalidadId ?? '');
const filtroSem   = ref(props.filtros.semaforo ?? '');

const filtrados = computed(() => {
    const q = buscar.value.toLowerCase();
    return props.creditos.filter(c => {
        const matchBuscar = !q || c.nombre?.toLowerCase().includes(q) || c.clave_contrato?.toLowerCase().includes(q) || c.municipio?.toLowerCase().includes(q);
        const matchMod = !filtroMod.value || c.modalidad === props.modalidades.find(m => m.id == filtroMod.value)?.nombre;
        const matchSem = !filtroSem.value || c.semaforo === filtroSem.value;
        return matchBuscar && matchMod && matchSem;
    });
});

const exportar = () => {
    const params = new URLSearchParams();
    if (filtroMod.value) params.set('modalidad_id', String(filtroMod.value));
    if (filtroSem.value) params.set('semaforo', filtroSem.value);
    window.location.href = `/reportes/informe-cobranza/excel?${params}`;
};

const fmt = (n: number) => new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(n ?? 0);

const semaforoBadge: Record<string, string> = {
    verde:    'bg-green-100 text-green-800',
    amarillo: 'bg-yellow-100 text-yellow-800',
    naranja:  'bg-orange-100 text-orange-800',
    rojo:     'bg-red-100 text-red-800',
    negro:    'bg-gray-900 text-white',
};

const semaforoLabel: Record<string, string> = {
    verde:    'Al corriente',
    amarillo: '1–30 días',
    naranja:  '31–60 días',
    rojo:     '61–90 días',
    negro:    '+90 días',
};

const imColor = computed(() => {
    const im = props.kpis.indice_morosidad;
    if (im <= 3)  return 'text-green-600';
    if (im <= 7)  return 'text-yellow-600';
    if (im <= 15) return 'text-orange-600';
    return 'text-red-600';
});
</script>

<template>
    <Head title="Informe de Cobranza" />
    <AppLayout>
        <div class="p-6 space-y-6">
            <!-- Encabezado -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <TrendingDown class="h-7 w-7 text-red-600" />
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Informe de Cobranza</h1>
                        <p class="text-sm text-gray-500">Cartera en mora — CREA IYEM</p>
                    </div>
                </div>
                <button @click="exportar" class="flex items-center gap-2 rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700">
                    <Download class="h-4 w-4" /> Exportar Excel
                </button>
            </div>

            <!-- KPI Cards -->
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                <div class="rounded-xl border bg-white p-4 shadow-sm">
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Créditos en mora</p>
                    <p class="mt-1 text-3xl font-bold text-red-600">{{ kpis.total_en_mora }}</p>
                </div>
                <div class="rounded-xl border bg-white p-4 shadow-sm">
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Cartera vencida</p>
                    <p class="mt-1 text-2xl font-bold text-red-700">{{ fmt(kpis.cartera_vencida) }}</p>
                </div>
                <div class="rounded-xl border bg-white p-4 shadow-sm">
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Índice de Morosidad (IM)</p>
                    <p class="mt-1 text-3xl font-bold" :class="imColor">{{ kpis.indice_morosidad }}%</p>
                    <p class="text-xs text-gray-400">Cartera bruta {{ fmt(kpis.cartera_bruta) }}</p>
                </div>
                <!-- Semáforo resumen -->
                <div class="rounded-xl border bg-white p-4 shadow-sm">
                    <p class="text-xs text-gray-500 uppercase tracking-wide mb-2">Semáforo</p>
                    <div class="space-y-1">
                        <div v-for="(count, color) in kpis.por_semaforo" :key="color" class="flex items-center justify-between">
                            <span :class="['rounded px-1.5 py-0.5 text-xs font-medium', semaforoBadge[color]]">
                                {{ semaforoLabel[color] ?? color }}
                            </span>
                            <span class="text-sm font-bold text-gray-700">{{ count }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Alerta IM -->
            <div v-if="kpis.indice_morosidad > 7"
                class="flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 p-4">
                <AlertTriangle class="h-5 w-5 text-red-600 mt-0.5 shrink-0" />
                <p class="text-sm text-red-800">
                    <strong>Alerta:</strong> El índice de morosidad ({{ kpis.indice_morosidad }}%) supera el umbral recomendado del 7%.
                    Se recomienda revisión urgente de la estrategia de recuperación.
                </p>
            </div>

            <!-- Filtros -->
            <div class="rounded-xl border bg-white p-4 shadow-sm">
                <div class="grid gap-3 sm:grid-cols-4">
                    <input v-model="buscar" placeholder="Buscar nombre, contrato, municipio..."
                        class="col-span-2 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
                    <select v-model="filtroMod" class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Todas las modalidades</option>
                        <option v-for="m in modalidades" :key="m.id" :value="m.id">{{ m.nombre }}</option>
                    </select>
                    <select v-model="filtroSem" class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Todos los semáforos</option>
                        <option v-for="(label, color) in semaforoLabel" :key="color" :value="color">{{ label }}</option>
                    </select>
                </div>
                <p class="mt-2 text-xs text-gray-400">Mostrando {{ filtrados.length }} de {{ creditos.length }} registros</p>
            </div>

            <!-- Tabla -->
            <div class="overflow-hidden rounded-xl border bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-xs uppercase tracking-wider text-gray-500">
                            <tr>
                                <th class="px-4 py-3 text-left">Contrato</th>
                                <th class="px-4 py-3 text-left">Acreditado</th>
                                <th class="px-4 py-3 text-left">Municipio</th>
                                <th class="px-4 py-3 text-left">Teléfono</th>
                                <th class="px-4 py-3 text-left">Modalidad</th>
                                <th class="px-4 py-3 text-right">Monto</th>
                                <th class="px-4 py-3 text-right">Vencido</th>
                                <th class="px-4 py-3 text-center">Cuotas</th>
                                <th class="px-4 py-3 text-center">Días mora</th>
                                <th class="px-4 py-3 text-center">Semáforo</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="c in filtrados" :key="c.id" class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-mono text-xs text-blue-700">{{ c.clave_contrato }}</td>
                                <td class="px-4 py-3 font-medium text-gray-900">{{ c.nombre }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ c.municipio }}</td>
                                <td class="px-4 py-3 text-gray-500 text-xs">{{ c.telefono }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ c.modalidad }}</td>
                                <td class="px-4 py-3 text-right font-medium text-gray-900">{{ fmt(c.monto_otorgado) }}</td>
                                <td class="px-4 py-3 text-right font-bold text-red-600">{{ fmt(c.monto_vencido) }}</td>
                                <td class="px-4 py-3 text-center text-gray-600">{{ c.cuotas_vencidas }}</td>
                                <td class="px-4 py-3 text-center font-bold" :class="c.dias_mora > 90 ? 'text-gray-900' : c.dias_mora > 60 ? 'text-red-600' : c.dias_mora > 30 ? 'text-orange-600' : 'text-yellow-600'">
                                    {{ c.dias_mora }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span :class="['rounded-full px-2 py-0.5 text-xs font-medium', semaforoBadge[c.semaforo]]">
                                        {{ semaforoLabel[c.semaforo] ?? c.semaforo }}
                                    </span>
                                </td>
                            </tr>
                            <tr v-if="filtrados.length === 0">
                                <td colspan="10" class="px-4 py-8 text-center text-gray-400">Sin créditos en mora con los filtros aplicados</td>
                            </tr>
                        </tbody>
                        <tfoot v-if="filtrados.length > 0" class="border-t-2 bg-gray-50 font-semibold">
                            <tr>
                                <td colspan="6" class="px-4 py-3 text-right text-gray-700">Totales:</td>
                                <td class="px-4 py-3 text-right text-red-700">
                                    {{ fmt(filtrados.reduce((s, c) => s + c.monto_vencido, 0)) }}
                                </td>
                                <td class="px-4 py-3 text-center text-gray-700">
                                    {{ filtrados.reduce((s, c) => s + c.cuotas_vencidas, 0) }}
                                </td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
