<script setup>
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import {
    Search, Filter, Shield, User, Clock, Eye, RefreshCw,
    Database, Edit, Trash2, Plus, Settings, ArrowRight
} from 'lucide-vue-next';
import debounce from 'lodash/debounce';

// Los nombres son los de AuditoriaController (filtros y columnas)
const props = defineProps({
    logs: Object, // paginado
    filters: Object,
    totales: Object,
    entidades: Array,
    usuarios: Array,
});

const buscar     = ref(props.filters?.buscar      ?? '');
const accion     = ref(props.filters?.accion      ?? '');
const entidad    = ref(props.filters?.entidad     ?? '');
const usuarioId  = ref(props.filters?.usuario_id  ?? '');
const fechaDesde = ref(props.filters?.fecha_desde ?? '');
const fechaHasta = ref(props.filters?.fecha_hasta ?? '');

// Los únicos valores que acepta la columna `accion`
const acciones = [
    { value: '', label: 'Todas las acciones' },
    { value: 'created',  label: 'Creado' },
    { value: 'updated',  label: 'Actualizado' },
    { value: 'deleted',  label: 'Eliminado' },
];

const applyFilters = () => {
    router.get(route('auditoria.index'), {
        buscar:      buscar.value     || undefined,
        accion:      accion.value     || undefined,
        entidad:     entidad.value    || undefined,
        usuario_id:  usuarioId.value  || undefined,
        fecha_desde: fechaDesde.value || undefined,
        fecha_hasta: fechaHasta.value || undefined,
    }, { preserveState: true, replace: true });
};

watch([accion, entidad, usuarioId, fechaDesde, fechaHasta], () => applyFilters());
watch(buscar, debounce(() => applyFilters(), 350));

const limpiarFiltros = () => {
    buscar.value     = '';
    accion.value     = '';
    entidad.value    = '';
    usuarioId.value  = '';
    fechaDesde.value = '';
    fechaHasta.value = '';
    applyFilters();
};

const hayFiltros = computed(() =>
    buscar.value || accion.value || entidad.value || usuarioId.value || fechaDesde.value || fechaHasta.value
);

// Colores y configuración por tipo de acción
const accionConfig = {
    created: {
        bg:   'bg-emerald-100 dark:bg-emerald-900/30',
        text: 'text-emerald-700 dark:text-emerald-400',
        icon: Plus,
        label: 'Creado',
    },
    updated: {
        bg:   'bg-blue-100 dark:bg-blue-900/30',
        text: 'text-blue-700 dark:text-blue-400',
        icon: Edit,
        label: 'Actualizado',
    },
    deleted: {
        bg:   'bg-red-100 dark:bg-red-900/30',
        text: 'text-red-700 dark:text-red-400',
        icon: Trash2,
        label: 'Eliminado',
    },
};

const getConfig = (valor) => accionConfig[valor] ?? {
    bg:   'bg-slate-100 dark:bg-slate-800',
    text: 'text-slate-600 dark:text-slate-400',
    icon: Settings,
    label: valor || '—',
};

const hayCambio = (log) => log.campo_modificado || log.valor_anterior !== null || log.valor_nuevo !== null;

// Modal para ver el registro completo
const modalLog   = ref(null);
const verDetalle = (log) => { modalLog.value = log; };
const cerrarModal = () => { modalLog.value = null; };
</script>

<template>
    <Head title="Auditoría — CREA" />

    <AppLayout>
        <div class="max-w-[98%] mx-auto py-8 px-4">

            <!-- Encabezado -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-slate-900 dark:bg-slate-700 rounded-2xl flex items-center justify-center shadow-md">
                        <Shield :size="22" class="text-white" />
                    </div>
                    <div>
                        <h1 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-tight">
                            Bitácora de Auditoría
                        </h1>
                        <p class="text-slate-500 dark:text-slate-400 text-sm font-medium">
                            Quién hizo cada operación, cuándo (hora de Mérida) y desde qué IP. Los registros no se pueden editar ni borrar.
                        </p>
                    </div>
                </div>
                <button @click="router.reload()"
                    class="p-3 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 hover:bg-slate-200 dark:hover:bg-slate-700 transition-all self-start md:self-auto">
                    <RefreshCw :size="18" />
                </button>
            </div>

            <!-- KPIs (con los filtros aplicados) -->
            <div v-if="totales" class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-800 p-5 shadow-sm">
                    <Database :size="18" class="text-slate-500 mb-2" />
                    <p class="text-2xl font-black text-slate-900 dark:text-white">{{ totales.total?.toLocaleString('es-MX') ?? 0 }}</p>
                    <p class="text-[11px] font-semibold text-slate-400 uppercase">Total Registros</p>
                </div>
                <div class="bg-emerald-50 dark:bg-emerald-900/20 rounded-2xl p-5">
                    <Plus :size="18" class="text-emerald-600 mb-2" />
                    <p class="text-2xl font-black text-emerald-700 dark:text-emerald-400">{{ totales.creados?.toLocaleString('es-MX') ?? 0 }}</p>
                    <p class="text-[11px] font-semibold text-slate-400 uppercase">Creaciones</p>
                </div>
                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-2xl p-5">
                    <Edit :size="18" class="text-blue-600 mb-2" />
                    <p class="text-2xl font-black text-blue-700 dark:text-blue-400">{{ totales.actualizados?.toLocaleString('es-MX') ?? 0 }}</p>
                    <p class="text-[11px] font-semibold text-slate-400 uppercase">Modificaciones</p>
                </div>
                <div class="bg-red-50 dark:bg-red-900/20 rounded-2xl p-5">
                    <Trash2 :size="18" class="text-red-600 mb-2" />
                    <p class="text-2xl font-black text-red-700 dark:text-red-400">{{ totales.eliminados?.toLocaleString('es-MX') ?? 0 }}</p>
                    <p class="text-[11px] font-semibold text-slate-400 uppercase">Eliminaciones</p>
                </div>
            </div>

            <!-- Filtros -->
            <div class="flex flex-wrap gap-3 mb-6">
                <!-- Buscar -->
                <div class="relative flex-1 min-w-[200px] max-w-sm">
                    <Search :size="15" class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400" />
                    <input v-model="buscar" type="text" placeholder="Clave de contrato, folio, usuario, IP..."
                        class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-sm focus:ring-2 focus:ring-slate-400 focus:border-slate-400 transition-all" />
                </div>

                <!-- Acción -->
                <div class="relative">
                    <Filter :size="14" class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none" />
                    <select v-model="accion"
                        class="pl-9 pr-8 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-sm focus:ring-2 focus:ring-slate-400 transition-all appearance-none">
                        <option v-for="op in acciones" :key="op.value" :value="op.value">{{ op.label }}</option>
                    </select>
                </div>

                <!-- Entidad -->
                <div class="relative">
                    <Database :size="14" class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none" />
                    <select v-model="entidad"
                        class="pl-9 pr-8 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-sm focus:ring-2 focus:ring-slate-400 transition-all appearance-none">
                        <option value="">Todas las entidades</option>
                        <option v-for="e in entidades" :key="e" :value="e">{{ e }}</option>
                    </select>
                </div>

                <!-- Usuario -->
                <div class="relative">
                    <User :size="14" class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none" />
                    <select v-model="usuarioId"
                        class="pl-9 pr-8 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-sm focus:ring-2 focus:ring-slate-400 transition-all appearance-none">
                        <option value="">Todos los usuarios</option>
                        <option v-for="u in usuarios" :key="u.id" :value="String(u.id)">{{ u.name }}</option>
                    </select>
                </div>

                <!-- Fechas -->
                <div class="relative flex items-center gap-2">
                    <Clock :size="14" class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none" />
                    <input v-model="fechaDesde" type="date" title="Desde"
                        class="pl-9 pr-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-sm focus:ring-2 focus:ring-slate-400 transition-all" />
                    <span class="text-xs text-slate-400">a</span>
                    <input v-model="fechaHasta" type="date" title="Hasta"
                        class="px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-sm focus:ring-2 focus:ring-slate-400 transition-all" />
                </div>

                <!-- Limpiar -->
                <button v-if="hayFiltros" @click="limpiarFiltros"
                    class="px-4 py-2.5 text-xs font-bold text-red-500 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-xl transition-all border border-transparent hover:border-red-200">
                    Limpiar filtros
                </button>
            </div>

            <!-- Tabla -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-800 overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[900px] text-sm text-left">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-slate-800/60 text-slate-400 dark:text-slate-500 text-[10px] uppercase tracking-widest font-black border-b border-slate-100 dark:border-slate-800">
                                <th class="px-5 py-4">Fecha / Hora</th>
                                <th class="px-5 py-4">Usuario</th>
                                <th class="px-5 py-4">Acción</th>
                                <th class="px-5 py-4">Entidad</th>
                                <th class="px-5 py-4">Cambio</th>
                                <th class="px-5 py-4">Descripción</th>
                                <th class="px-5 py-4">IP</th>
                                <th class="px-5 py-4 text-center">Detalle</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50 dark:divide-slate-800">
                            <tr v-if="!logs?.data?.length">
                                <td colspan="8" class="px-5 py-16 text-center text-slate-400 dark:text-slate-600">
                                    <Shield :size="32" class="mx-auto mb-3 opacity-30" />
                                    <p class="font-semibold">No hay registros de auditoría.</p>
                                </td>
                            </tr>
                            <tr v-for="log in logs?.data" :key="log.id"
                                class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors">

                                <!-- Fecha -->
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <span class="text-xs font-semibold text-slate-600 dark:text-slate-400">
                                        {{ log.fecha }}
                                    </span>
                                </td>

                                <!-- Usuario -->
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-2">
                                        <div class="w-7 h-7 rounded-full bg-slate-200 dark:bg-slate-700 flex items-center justify-center">
                                            <User :size="13" class="text-slate-500" />
                                        </div>
                                        <p class="font-bold text-slate-800 dark:text-white text-xs">{{ log.usuario ?? 'Sistema' }}</p>
                                    </div>
                                </td>

                                <!-- Acción badge -->
                                <td class="px-5 py-4">
                                    <div :class="[
                                        'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-black uppercase',
                                        getConfig(log.accion).bg,
                                        getConfig(log.accion).text
                                    ]">
                                        <component :is="getConfig(log.accion).icon" :size="11" />
                                        {{ getConfig(log.accion).label }}
                                    </div>
                                </td>

                                <!-- Entidad -->
                                <td class="px-5 py-4">
                                    <span class="inline-flex items-center gap-1 text-[11px] font-bold text-slate-600 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded-lg">
                                        <Database :size="10" />
                                        {{ log.entidad }}
                                        <span class="font-mono text-slate-400">#{{ log.entidad_id }}</span>
                                    </span>
                                </td>

                                <!-- Cambio: campo, antes → después -->
                                <td class="px-5 py-4 max-w-[260px]">
                                    <template v-if="hayCambio(log)">
                                        <p class="text-[11px] font-bold text-slate-500 dark:text-slate-400">{{ log.campo_modificado ?? '—' }}</p>
                                        <p class="flex items-center gap-1 text-xs font-mono">
                                            <span class="text-red-600 dark:text-red-400 truncate">{{ log.valor_anterior ?? '∅' }}</span>
                                            <ArrowRight :size="11" class="text-slate-400 shrink-0" />
                                            <span class="text-emerald-700 dark:text-emerald-400 truncate">{{ log.valor_nuevo ?? '∅' }}</span>
                                        </p>
                                    </template>
                                    <span v-else class="text-xs text-slate-400">—</span>
                                </td>

                                <!-- Descripción -->
                                <td class="px-5 py-4 max-w-[320px]">
                                    <p class="text-xs text-slate-600 dark:text-slate-400 line-clamp-2">
                                        {{ log.descripcion ?? '—' }}
                                    </p>
                                </td>

                                <!-- IP -->
                                <td class="px-5 py-4">
                                    <span class="font-mono text-[11px] text-slate-500 dark:text-slate-400">
                                        {{ log.ip_address ?? '—' }}
                                    </span>
                                </td>

                                <!-- Ver detalle -->
                                <td class="px-5 py-4 text-center">
                                    <button @click="verDetalle(log)"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-lg text-[11px] font-bold transition-colors">
                                        <Eye :size="12" /> Ver
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Paginación -->
            <div v-if="logs?.links?.length" class="mt-5 flex justify-center gap-1.5 flex-wrap">
                <button
                    v-for="link in logs.links" :key="link.label"
                    :disabled="!link.url"
                    @click="link.url && router.get(link.url, {}, { preserveState: true })"
                    v-html="link.label"
                    :class="[
                        'px-3 py-2 rounded-xl text-sm font-bold transition-all',
                        link.active
                            ? 'bg-slate-900 dark:bg-slate-700 text-white shadow'
                            : 'bg-white dark:bg-slate-900 text-slate-500 hover:bg-slate-50 dark:hover:bg-slate-800 border border-slate-100 dark:border-slate-800',
                        !link.url ? 'opacity-40 cursor-not-allowed' : 'cursor-pointer'
                    ]"
                />
            </div>

            <!-- Info de paginación -->
            <div v-if="logs?.total" class="mt-3 text-center text-xs text-slate-400">
                Mostrando {{ logs.from ?? 0 }}–{{ logs.to ?? 0 }} de {{ logs.total.toLocaleString('es-MX') }} registros
            </div>

        </div>

        <!-- MODAL DETALLE -->
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="modalLog" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4"
                @click.self="cerrarModal">
                <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 w-full max-w-2xl shadow-2xl border border-slate-200 dark:border-slate-700 max-h-[85vh] overflow-y-auto">

                    <!-- Header modal -->
                    <div class="flex items-center justify-between mb-5">
                        <div class="flex items-center gap-3">
                            <div :class="['w-10 h-10 rounded-xl flex items-center justify-center', getConfig(modalLog.accion).bg]">
                                <component :is="getConfig(modalLog.accion).icon" :size="18" :class="getConfig(modalLog.accion).text" />
                            </div>
                            <div>
                                <h3 class="font-black text-slate-900 dark:text-white text-lg">
                                    Detalle del Registro
                                </h3>
                                <p class="text-xs text-slate-400">{{ modalLog.fecha }} (hora de Mérida)</p>
                            </div>
                        </div>
                        <button @click="cerrarModal"
                            class="p-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-400 transition-all">
                            ✕
                        </button>
                    </div>

                    <!-- Datos generales -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-5">
                        <div class="bg-slate-50 dark:bg-slate-800 rounded-2xl p-4">
                            <p class="text-[10px] font-black text-slate-400 uppercase mb-1">Usuario</p>
                            <p class="font-bold text-slate-800 dark:text-white text-sm">{{ modalLog.usuario ?? 'Sistema' }}</p>
                        </div>
                        <div class="bg-slate-50 dark:bg-slate-800 rounded-2xl p-4">
                            <p class="text-[10px] font-black text-slate-400 uppercase mb-1">Entidad</p>
                            <p class="font-bold text-slate-800 dark:text-white text-sm">{{ modalLog.entidad }}</p>
                            <p class="text-xs font-mono text-slate-400">#{{ modalLog.entidad_id }}</p>
                        </div>
                        <div class="bg-slate-50 dark:bg-slate-800 rounded-2xl p-4">
                            <p class="text-[10px] font-black text-slate-400 uppercase mb-1">IP</p>
                            <p class="font-mono text-slate-700 dark:text-slate-300 text-sm">{{ modalLog.ip_address ?? '—' }}</p>
                        </div>
                    </div>

                    <!-- Descripción -->
                    <div v-if="modalLog.descripcion" class="bg-slate-50 dark:bg-slate-800 rounded-2xl p-4 mb-5">
                        <p class="text-[10px] font-black text-slate-400 uppercase mb-1">Descripción</p>
                        <p class="text-sm text-slate-700 dark:text-slate-300 whitespace-pre-line">{{ modalLog.descripcion }}</p>
                    </div>

                    <!-- Cambio -->
                    <div v-if="hayCambio(modalLog)">
                        <p class="text-[10px] font-black text-slate-400 uppercase mb-2">Campo: {{ modalLog.campo_modificado ?? '—' }}</p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="bg-red-50 dark:bg-red-900/20 rounded-2xl p-4">
                                <p class="text-[10px] font-black text-red-500 uppercase mb-2">Valor anterior</p>
                                <p class="text-sm font-mono text-red-600 dark:text-red-400 break-words">{{ modalLog.valor_anterior ?? '∅' }}</p>
                            </div>
                            <div class="bg-emerald-50 dark:bg-emerald-900/20 rounded-2xl p-4">
                                <p class="text-[10px] font-black text-emerald-600 uppercase mb-2">Valor nuevo</p>
                                <p class="text-sm font-mono text-emerald-700 dark:text-emerald-400 break-words">{{ modalLog.valor_nuevo ?? '∅' }}</p>
                            </div>
                        </div>
                    </div>

                    <div v-else class="text-center py-4 text-slate-400 text-sm">
                        Este registro no guarda un valor anterior ni uno nuevo.
                    </div>

                </div>
            </div>
        </Transition>

    </AppLayout>
</template>

<style scoped>
.overflow-x-auto::-webkit-scrollbar { height: 6px; }
.overflow-x-auto::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
</style>
