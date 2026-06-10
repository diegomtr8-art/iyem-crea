<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { Users, Download, TrendingUp, MapPin } from 'lucide-vue-next';

const props = defineProps<{
    beneficiarios: any[];
    por_modalidad: Record<string, { cantidad: number; monto_total: number; monto_promedio: number }>;
    totales: { total: number; mujeres: number; hombres: number; monto_total: number; monto_promedio: number };
    modalidades: { id: number; nombre: string }[];
    municipios: string[];
    filtros: { modalidadId: number | null; municipio: string | null; sexo: string | null; estatus: string | null };
}>();

const buscar = ref('');
const filtroMod = ref(props.filtros.modalidadId ?? '');
const filtroMun = ref(props.filtros.municipio ?? '');
const filtroSexo = ref(props.filtros.sexo ?? '');
const filtroEst = ref(props.filtros.estatus ?? '');

const filtrados = computed(() => {
    const q = buscar.value.toLowerCase();
    return props.beneficiarios.filter(b => {
        const matchBuscar = !q || b.nombre?.toLowerCase().includes(q) || b.clave_contrato?.toLowerCase().includes(q) || b.municipio?.toLowerCase().includes(q);
        const matchMod  = !filtroMod.value  || b.modalidad === props.modalidades.find(m => m.id == filtroMod.value)?.nombre;
        const matchMun  = !filtroMun.value  || b.municipio === filtroMun.value;
        const matchSexo = !filtroSexo.value || b.sexo === filtroSexo.value;
        const matchEst  = !filtroEst.value  || b.estatus === filtroEst.value;
        return matchBuscar && matchMod && matchMun && matchSexo && matchEst;
    });
});

const exportar = () => {
    const params = new URLSearchParams();
    if (filtroMod.value)  params.set('modalidad_id', String(filtroMod.value));
    if (filtroMun.value)  params.set('municipio', filtroMun.value);
    if (filtroSexo.value) params.set('sexo', filtroSexo.value);
    if (filtroEst.value)  params.set('estatus', filtroEst.value);
    window.location.href = `/reportes/beneficiarios/excel?${params}`;
};

const fmt = (n: number) => new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(n ?? 0);

const estatusBadge: Record<string, string> = {
    Activo:    'bg-green-100 text-green-800',
    Liquidado: 'bg-blue-100 text-blue-800',
    Moroso:    'bg-red-100 text-red-800',
    Vencido:   'bg-orange-100 text-orange-800',
    Juridico:  'bg-purple-100 text-purple-800',
};
</script>

<template>
    <Head title="Reporte de Beneficiarios" />
    <AppLayout>
        <div class="p-6 space-y-6">
            <!-- Encabezado -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <Users class="h-7 w-7 text-blue-600" />
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Reporte de Beneficiarios</h1>
                        <p class="text-sm text-gray-500">Acreditados del programa CREA — IYEM</p>
                    </div>
                </div>
                <button @click="exportar" class="flex items-center gap-2 rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700">
                    <Download class="h-4 w-4" /> Exportar Excel
                </button>
            </div>

            <!-- KPI Cards -->
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                <div class="rounded-xl border bg-white p-4 shadow-sm">
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Total beneficiarios</p>
                    <p class="mt-1 text-3xl font-bold text-gray-900">{{ totales.total }}</p>
                </div>
                <div class="rounded-xl border bg-white p-4 shadow-sm">
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Mujeres</p>
                    <p class="mt-1 text-3xl font-bold text-pink-600">{{ totales.mujeres }}</p>
                    <p class="text-xs text-gray-400">{{ totales.total > 0 ? Math.round((totales.mujeres / totales.total) * 100) : 0 }}%</p>
                </div>
                <div class="rounded-xl border bg-white p-4 shadow-sm">
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Monto total colocado</p>
                    <p class="mt-1 text-2xl font-bold text-green-700">{{ fmt(totales.monto_total) }}</p>
                </div>
                <div class="rounded-xl border bg-white p-4 shadow-sm">
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Crédito promedio</p>
                    <p class="mt-1 text-2xl font-bold text-gray-900">{{ fmt(totales.monto_promedio) }}</p>
                </div>
            </div>

            <!-- Totales por modalidad -->
            <div class="rounded-xl border bg-white p-4 shadow-sm">
                <h2 class="mb-3 flex items-center gap-2 text-sm font-semibold text-gray-700">
                    <TrendingUp class="h-4 w-4" /> Por Modalidad
                </h2>
                <div class="grid gap-3 sm:grid-cols-3">
                    <div v-for="(datos, nombre) in por_modalidad" :key="nombre"
                        class="rounded-lg bg-gray-50 p-3">
                        <p class="font-medium text-gray-900">{{ nombre }}</p>
                        <p class="text-xs text-gray-500">{{ datos.cantidad }} beneficiarios</p>
                        <p class="text-sm font-bold text-blue-700">{{ fmt(datos.monto_total) }}</p>
                        <p class="text-xs text-gray-400">Promedio {{ fmt(datos.monto_promedio) }}</p>
                    </div>
                </div>
            </div>

            <!-- Filtros -->
            <div class="rounded-xl border bg-white p-4 shadow-sm">
                <div class="grid gap-3 sm:grid-cols-5">
                    <input v-model="buscar" placeholder="Buscar nombre, contrato, municipio..."
                        class="col-span-2 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
                    <select v-model="filtroMod" class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Todas las modalidades</option>
                        <option v-for="m in modalidades" :key="m.id" :value="m.id">{{ m.nombre }}</option>
                    </select>
                    <select v-model="filtroMun" class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Todos los municipios</option>
                        <option v-for="m in municipios" :key="m" :value="m">{{ m }}</option>
                    </select>
                    <select v-model="filtroSexo" class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Todos los géneros</option>
                        <option value="Femenino">Femenino</option>
                        <option value="Masculino">Masculino</option>
                    </select>
                </div>
                <p class="mt-2 text-xs text-gray-400">Mostrando {{ filtrados.length }} de {{ beneficiarios.length }} registros</p>
            </div>

            <!-- Tabla -->
            <div class="overflow-hidden rounded-xl border bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-xs uppercase tracking-wider text-gray-500">
                            <tr>
                                <th class="px-4 py-3 text-left">Contrato</th>
                                <th class="px-4 py-3 text-left">Beneficiario</th>
                                <th class="px-4 py-3 text-left">
                                    <span class="flex items-center gap-1"><MapPin class="h-3 w-3" /> Municipio</span>
                                </th>
                                <th class="px-4 py-3 text-left">Modalidad</th>
                                <th class="px-4 py-3 text-right">Monto</th>
                                <th class="px-4 py-3 text-center">Plazo</th>
                                <th class="px-4 py-3 text-center">Desembolso</th>
                                <th class="px-4 py-3 text-center">Estatus</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="b in filtrados" :key="b.id" class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-mono text-xs text-blue-700">{{ b.clave_contrato }}</td>
                                <td class="px-4 py-3 font-medium text-gray-900">{{ b.nombre }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ b.municipio }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ b.modalidad }}</td>
                                <td class="px-4 py-3 text-right font-medium text-gray-900">{{ fmt(b.monto_otorgado) }}</td>
                                <td class="px-4 py-3 text-center text-gray-600">{{ b.plazo_meses }}m</td>
                                <td class="px-4 py-3 text-center text-gray-500 text-xs">{{ b.fecha_entrega }}</td>
                                <td class="px-4 py-3 text-center">
                                    <span :class="['rounded-full px-2 py-0.5 text-xs font-medium', estatusBadge[b.estatus] ?? 'bg-gray-100 text-gray-700']">
                                        {{ b.estatus }}
                                    </span>
                                </td>
                            </tr>
                            <tr v-if="filtrados.length === 0">
                                <td colspan="8" class="px-4 py-8 text-center text-gray-400">Sin registros con los filtros aplicados</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
