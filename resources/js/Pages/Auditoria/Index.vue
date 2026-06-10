<script setup>
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import {
    Search, Filter, Shield, User, Clock, Eye, RefreshCw,
    AlertTriangle, FileText, Database, ChevronLeft, ChevronRight,
    Edit, Trash2, Plus, LogIn, LogOut, Settings, ToggleLeft
} from 'lucide-vue-next';
import debounce from 'lodash/debounce';

const props = defineProps({
    logs: Object, // paginado
    filters: Object,
    totales: Object,
});

const search    = ref(props.filters?.search    ?? '');
const accion    = ref(props.filters?.accion    ?? '');
const modelo    = ref(props.filters?.modelo    ?? '');
const fecha     = ref(props.filters?.fecha     ?? '');

const acciones = [
    { value: '', label: 'Todas las acciones' },
    { value: 'created',  label: 'Creado' },
    { value: 'updated',  label: 'Actualizado' },
    { value: 'deleted',  label: 'Eliminado' },
    { value: 'login',    label: 'Inicio de sesión' },
    { value: 'logout',   label: 'Cierre de sesión' },
];

const applyFilters = () => {
    router.get(route('auditoria.index'), {
        search:  search.value  || undefined,
        accion:  accion.value  || undefined,
        modelo:  modelo.value  || undefined,
        fecha:   fecha.value   || undefined,
    }, { preserveState: true, replace: true });
};

watch([accion, modelo, fecha], () => applyFilters());
watch(search, debounce(() => applyFilters(), 350));

const limpiarFiltros = () => {
    search.value = '';
    accion.value = '';
    modelo.value = '';
    fecha.value  = '';
    applyFilters();
};

const hayFiltros = computed(() =>
    search.value || accion.value || modelo.value || fecha.value
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
    login: {
        bg:   'bg-purple-100 dark:bg-purple-900/30',
        text: 'text-purple-700 dark:text-purple-400',
        icon: LogIn,
        label: 'Login',
    },
    logout: {
        bg:   'bg-slate-100 dark:bg-slate-800',
        text: 'text-slate-600 dark:text-slate-400',
        icon: LogOut,
        label: 'Logout',
    },
    restored: {
        bg:   'bg-amber-100 dark:bg-amber-900/30',
        text: 'text-amber-700 dark:text-amber-400',
        icon: ToggleLeft,
        label: 'Restaurado',
    },
};

const getConfig = (event) => accionConfig[event] ?? {
    bg:   'bg-slate-100 dark:bg-slate-800',
    text: 'text-slate-600 dark:text-slate-400',
    icon: Settings,
    label: event ?? '—',
};

const formatFecha = (str) => {
    if (!str) return '—';
    const d = new Date(str);
    return d.toLocaleString('es-MX', {
        day: '2-digit', month: 'short', year: 'numeric',
        hour: '2-digit', minute: '2-digit',
    });
};

const modeloLabel = (str) => {
    if (!str) return '—';
    // App\Models\Acreditado → Acreditado
    return str.split('\\').pop();
};

// Modal para ver cambios detallados
const modalLog   = ref(null);
const verDetalle = (log) => { modalLog.value = log; };
const cerrarModal = () => { modalLog.value = null; };

const cambiosJson = computed(() => {
    if (!modalLog.value) return null;
    try {
        const nuevo = modalLog.value.new_values
            ? (typeof modalLog.value.new_values === 'string'
                ? JSON.parse(modalLog.value.new_values) : modalLog.value.new_values)
            : null;
        const viejo = modalLog.value.old_values
            ? (typeof modalLog.value.old_values === 'string'
                ? JSON.parse(modalLog.value.old_values) : modalLog.value.old_values)
            : null;
        return { nuevo, viejo };
    } catch {
        return { nuevo: null, viejo: null };
    }
});
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
                            Registro de todas las acciones realizadas en el sistema.
                        </p>
                    </div>
                </div>
                <button @click="router.reload()"
                    class="p-3 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 hover:bg-slate-200 dark:hover:bg-slate-700 transition-all self-start md:self-auto">
                    <RefreshCw :size="18" />
                </button>
            </div>

            <!-- KPIs -->
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
                    <input v-model="search" type="text" placeholder="Usuario, IP, descripción..."
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

                <!-- Modelo -->
                <div class="relative">
                    <Database :size="14" class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none" />
                    <input v-model="modelo" type="text" placeholder="Modelo (ej. Credito)"
                        class="pl-9 pr-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-sm w-40 focus:ring-2 focus:ring-slate-400 transition-all" />
                </div>

                <!-- Fecha -->
                <div class="relative">
                    <Clock :size="14" class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none" />
                    <input v-model="fecha" type="date"
                        class="pl-9 pr-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-sm focus:ring-2 focus:ring-slate-400 transition-all" />
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
                    <table class="w-full text-sm text-left">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-slate-800/60 text-slate-400 dark:text-slate-500 text-[10px] uppercase tracking-widest font-black border-b border-slate-100 dark:border-slate-800">
                                <th class="px-5 py-4">Fecha / Hora</th>
                                <th class="px-5 py-4">Usuario</th>
                                <th class="px-5 py-4">Acción</th>
                                <th class="px-5 py-4">Modelo</th>
                                <th class="px-5 py-4">Descripción</th>
                                <th class="px-5 py-4">IP</th>
                                <th class="px-5 py-4 text-center">Detalle</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50 dark:divide-slate-800">
                            <tr v-if="!logs?.data?.length">
                                <td colspan="7" class="px-5 py-16 text-center text-slate-400 dark:text-slate-600">
                                    <Shield :size="32" class="mx-auto mb-3 opacity-30" />
                                    <p class="font-semibold">No hay registros de auditoría.</p>
                                </td>
                            </tr>
                            <tr v-for="log in logs?.data" :key="log.id"
                                class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors">

                                <!-- Fecha -->
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <span class="text-xs font-semibold text-slate-600 dark:text-slate-400">
                                        {{ formatFecha(log.created_at) }}
                                    </span>
                                </td>

                                <!-- Usuario -->
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-2">
                                        <div class="w-7 h-7 rounded-full bg-slate-200 dark:bg-slate-700 flex items-center justify-center">
                                            <User :size="13" class="text-slate-500" />
                                        </div>
                                        <div>
                                            <p class="font-bold text-slate-800 dark:text-white text-xs">{{ log.user_name ?? 'Sistema' }}</p>
                                            <p class="text-[10px] text-slate-400">{{ log.user_email ?? '' }}</p>
                                        </div>
                                    </div>
                                </td>

                                <!-- Acción badge -->
                                <td class="px-5 py-4">
                                    <div :class="[
                                        'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-black uppercase',
                                        getConfig(log.event).bg,
                                        getConfig(log.event).text
                                    ]">
                                        <component :is="getConfig(log.event).icon" :size="11" />
                                        {{ getConfig(log.event).label }}
                                    </div>
                                </td>

                                <!-- Modelo -->
                                <td class="px-5 py-4">
                                    <span class="inline-flex items-center gap-1 text-[11px] font-bold text-slate-600 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded-lg">
                                        <Database :size="10" />
                                        {{ modeloLabel(log.auditable_type) }}
                                        <span v-if="log.auditable_id" class="font-mono text-slate-400">#{{ log.auditable_id }}</span>
                                    </span>
                                </td>

                                <!-- Descripción -->
                                <td class="px-5 py-4 max-w-[240px]">
                                    <p class="text-xs text-slate-600 dark:text-slate-400 truncate">
                                        {{ log.description ?? log.url ?? '—' }}
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
                            <div :class="['w-10 h-10 rounded-xl flex items-center justify-center', getConfig(modalLog.event).bg]">
                                <component :is="getConfig(modalLog.event).icon" :size="18" :class="getConfig(modalLog.event).text" />
                            </div>
                            <div>
                                <h3 class="font-black text-slate-900 dark:text-white text-lg">
                                    Detalle del Registro
                                </h3>
                                <p class="text-xs text-slate-400">{{ formatFecha(modalLog.created_at) }}</p>
                            </div>
                        </div>
                        <button @click="cerrarModal"
                            class="p-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-400 transition-all">
                            ✕
                        </button>
                    </div>

                    <!-- Datos generales -->
                    <div class="grid grid-cols-2 gap-3 mb-5">
                        <div class="bg-slate-50 dark:bg-slate-800 rounded-2xl p-4">
                            <p class="text-[10px] font-black text-slate-400 uppercase mb-1">Usuario</p>
                            <p class="font-bold text-slate-800 dark:text-white text-sm">{{ modalLog.user_name ?? 'Sistema' }}</p>
                            <p class="text-xs text-slate-400">{{ modalLog.user_email ?? '' }}</p>
                        </div>
                        <div class="bg-slate-50 dark:bg-slate-800 rounded-2xl p-4">
                            <p class="text-[10px] font-black text-slate-400 uppercase mb-1">Modelo Afectado</p>
                            <p class="font-bold text-slate-800 dark:text-white text-sm">{{ modeloLabel(modalLog.auditable_type) }}</p>
                            <p class="text-xs font-mono text-slate-400">#{{ modalLog.auditable_id }}</p>
                        </div>
                        <div class="bg-slate-50 dark:bg-slate-800 rounded-2xl p-4">
                            <p class="text-[10px] font-black text-slate-400 uppercase mb-1">IP</p>
                            <p class="font-mono text-slate-700 dark:text-slate-300 text-sm">{{ modalLog.ip_address ?? '—' }}</p>
                        </div>
                        <div class="bg-slate-50 dark:bg-slate-800 rounded-2xl p-4">
                            <p class="text-[10px] font-black text-slate-400 uppercase mb-1">Navegador</p>
                            <p class="text-slate-600 dark:text-slate-400 text-xs truncate">{{ modalLog.user_agent ?? '—' }}</p>
                        </div>
                    </div>

                    <!-- Descripción / URL -->
                    <div v-if="modalLog.description || modalLog.url" class="bg-slate-50 dark:bg-slate-800 rounded-2xl p-4 mb-5">
                        <p class="text-[10px] font-black text-slate-400 uppercase mb-1">Descripción / URL</p>
                        <p class="text-sm text-slate-700 dark:text-slate-300">{{ modalLog.description ?? modalLog.url }}</p>
                    </div>

                    <!-- Cambios -->
                    <div v-if="cambiosJson" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Valores anteriores -->
                        <div v-if="cambiosJson.viejo" class="bg-red-50 dark:bg-red-900/20 rounded-2xl p-4">
                            <p class="text-[10px] font-black text-red-500 uppercase mb-3">Valores Anteriores</p>
                            <div class="space-y-1.5">
                                <div v-for="(val, key) in cambiosJson.viejo" :key="key"
                                    class="flex justify-between gap-2 text-xs">
                                    <span class="font-bold text-slate-600 dark:text-slate-400 capitalize">{{ key }}</span>
                                    <span class="text-red-600 dark:text-red-400 font-mono truncate max-w-[120px]">{{ val ?? 'null' }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Valores nuevos -->
                        <div v-if="cambiosJson.nuevo" class="bg-emerald-50 dark:bg-emerald-900/20 rounded-2xl p-4">
                            <p class="text-[10px] font-black text-emerald-600 uppercase mb-3">Valores Nuevos</p>
                            <div class="space-y-1.5">
                                <div v-for="(val, key) in cambiosJson.nuevo" :key="key"
                                    class="flex justify-between gap-2 text-xs">
                                    <span class="font-bold text-slate-600 dark:text-slate-400 capitalize">{{ key }}</span>
                                    <span class="text-emerald-700 dark:text-emerald-400 font-mono truncate max-w-[120px]">{{ val ?? 'null' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-if="!cambiosJson?.viejo && !cambiosJson?.nuevo" class="text-center py-4 text-slate-400 text-sm">
                        Sin cambios detallados disponibles para este registro.
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
