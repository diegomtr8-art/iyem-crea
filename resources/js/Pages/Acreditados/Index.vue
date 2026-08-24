<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Search, UserPlus, FileText, MapPin, Users, Filter, ChevronRight, Wallet, CheckCircle2, AlertTriangle, Award, X, FolderX } from 'lucide-vue-next';
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import debounce from 'lodash/debounce';

const props = defineProps({
    acreditados: Object,  // paginated
    filters: Object,
    kpis: Object,
    municipios: Array,
});

const search    = ref(props.filters?.search    ?? '');
const estatus   = ref(props.filters?.estatus   ?? '');
const municipio = ref(props.filters?.municipio ?? '');

const applyFilters = () => {
    router.get(route('acreditados.index'), {
        search:    search.value || undefined,
        estatus:   estatus.value || undefined,
        municipio: municipio.value || undefined,
    }, { preserveState: true, replace: true });
};

const limpiarFiltros = () => { search.value = ''; estatus.value = ''; municipio.value = ''; applyFilters(); };
const hayFiltros = computed(() => !!(search.value || estatus.value || municipio.value));

let debounced = debounce(applyFilters, 300);
const onSearchInput = () => debounced();

const modalidadEstilo = (nombre) => {
    if (!nombre) return { bg: 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400', dot: 'bg-zinc-400' };
    if (nombre.includes('Artesanal'))   return { bg: 'bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400', dot: 'bg-blue-500' };
    if (nombre.includes('Sustentable')) return { bg: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400', dot: 'bg-emerald-500' };
    return { bg: 'bg-purple-50 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400', dot: 'bg-purple-500' };
};

const estatusEstilo = (e) => {
    if (e === 'Activo')    return { bg: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400', bar: 'bg-emerald-500' };
    if (e === 'Moroso')    return { bg: 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400', bar: 'bg-orange-500' };
    if (e === 'Liquidado') return { bg: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400', bar: 'bg-blue-500' };
    return { bg: 'bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400', bar: 'bg-zinc-300' };
};

// Avatar con iniciales + color determinístico por nombre
const avatarPalette = [
    { bg: 'bg-red-100 dark:bg-red-900/30', text: 'text-red-700 dark:text-red-400' },
    { bg: 'bg-amber-100 dark:bg-amber-900/30', text: 'text-amber-700 dark:text-amber-400' },
    { bg: 'bg-emerald-100 dark:bg-emerald-900/30', text: 'text-emerald-700 dark:text-emerald-400' },
    { bg: 'bg-blue-100 dark:bg-blue-900/30', text: 'text-blue-700 dark:text-blue-400' },
    { bg: 'bg-purple-100 dark:bg-purple-900/30', text: 'text-purple-700 dark:text-purple-400' },
    { bg: 'bg-teal-100 dark:bg-teal-900/30', text: 'text-teal-700 dark:text-teal-400' },
];
const iniciales = (nombre) => (nombre ?? '')
    .trim().split(/\s+/).slice(0, 2).map(p => p[0]?.toUpperCase() ?? '').join('') || '—';
const avatarColor = (nombre) => {
    const idx = (nombre ?? '').split('').reduce((s, c) => s + c.charCodeAt(0), 0) % avatarPalette.length;
    return avatarPalette[idx];
};

const money = (v) => new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN', minimumFractionDigits: 0 }).format(v || 0);

const kpiCards = computed(() => [
    { label: 'Total Acreditados', value: props.kpis?.total ?? 0, icon: Users, color: 'text-slate-700 dark:text-white', bg: 'bg-slate-100 dark:bg-zinc-800' },
    { label: 'Activos',    value: props.kpis?.activos ?? 0,    icon: CheckCircle2,  color: 'text-emerald-700 dark:text-emerald-400', bg: 'bg-emerald-50 dark:bg-emerald-900/20' },
    { label: 'Morosos',    value: props.kpis?.morosos ?? 0,    icon: AlertTriangle, color: 'text-orange-700 dark:text-orange-400',  bg: 'bg-orange-50 dark:bg-orange-900/20' },
    { label: 'Liquidados', value: props.kpis?.liquidados ?? 0, icon: Award,         color: 'text-blue-700 dark:text-blue-400',      bg: 'bg-blue-50 dark:bg-blue-900/20' },
]);
</script>

<template>
    <Head title="Acreditados CREA" />

    <AppLayout>
        <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-red-700 flex items-center justify-center text-white shadow-lg shadow-red-900/20 shrink-0">
                        <Users :size="22" />
                    </div>
                    <div>
                        <h1 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white tracking-tight">Cartera de Acreditados</h1>
                        <p class="text-slate-500 dark:text-zinc-400 text-sm mt-0.5">
                            {{ acreditados.total }} acreditados registrados · {{ money(kpis?.cartera) }} en cartera activa
                        </p>
                    </div>
                </div>
                <Link :href="route('acreditados.create')"
                      class="w-full sm:w-auto flex items-center justify-center gap-2 bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white font-bold px-6 py-3 rounded-2xl shadow-lg shadow-red-900/20 transition-all active:scale-95">
                    <UserPlus :size="18" />
                    Nuevo Registro
                </Link>
            </div>

            <!-- KPIs -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <div v-for="kpi in kpiCards" :key="kpi.label" :class="['rounded-2xl p-4 transition-all', kpi.bg]">
                    <component :is="kpi.icon" :size="18" :class="['mb-2', kpi.color]" />
                    <p :class="['text-2xl font-black', kpi.color]">{{ kpi.value }}</p>
                    <p class="text-[11px] font-semibold text-slate-500 dark:text-zinc-400 mt-0.5 uppercase tracking-wide leading-tight">{{ kpi.label }}</p>
                </div>
            </div>

            <!-- Filtros -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 shadow-sm">
                <div class="flex flex-col sm:flex-row gap-3">
                    <div class="relative flex-1">
                        <Search class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400" :size="18" />
                        <input v-model="search" @input="onSearchInput" type="text"
                               placeholder="Buscar por nombre, RFC o contrato..."
                               class="w-full pl-11 pr-4 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white focus:ring-2 focus:ring-red-500/30 focus:border-red-500 transition-all text-sm" />
                    </div>
                    <div class="relative">
                        <Filter class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none" :size="16" />
                        <select v-model="estatus" @change="applyFilters"
                            class="w-full sm:w-auto pl-10 pr-8 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-700 dark:text-white text-sm focus:ring-2 focus:ring-red-500/30 appearance-none">
                            <option value="">Todos los estatus</option>
                            <option value="Activo">Activo</option>
                            <option value="Moroso">Moroso</option>
                            <option value="Liquidado">Liquidado</option>
                        </select>
                    </div>
                    <div class="relative">
                        <MapPin class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none" :size="16" />
                        <select v-model="municipio" @change="applyFilters"
                            class="w-full sm:w-auto pl-10 pr-8 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-700 dark:text-white text-sm focus:ring-2 focus:ring-red-500/30 appearance-none">
                            <option value="">Todos los municipios</option>
                            <option v-for="m in municipios" :key="m" :value="m">{{ m }}</option>
                        </select>
                    </div>
                    <button v-if="hayFiltros" @click="limpiarFiltros"
                        class="flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-xl text-xs font-bold text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-all shrink-0">
                        <X :size="13" /> Limpiar
                    </button>
                </div>
            </div>

            <!-- Cards (mobile) -->
            <div v-if="acreditados.data?.length" class="sm:hidden space-y-3">
                <div v-for="a in acreditados.data" :key="a.id"
                    class="relative overflow-hidden bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 shadow-sm active:scale-[0.99] transition-transform"
                    @click="router.visit(route('acreditados.show', a.id))">
                    <div :class="['absolute left-0 top-0 bottom-0 w-1', estatusEstilo(a.creditos[0]?.estatus).bar]"></div>
                    <div class="flex items-center justify-between gap-2 mb-3 pl-1.5">
                        <div class="flex items-center gap-3 min-w-0">
                            <div :class="['h-10 w-10 rounded-full flex items-center justify-center text-xs font-black shrink-0', avatarColor(a.nombre_completo).bg, avatarColor(a.nombre_completo).text]">
                                {{ iniciales(a.nombre_completo) }}
                            </div>
                            <div class="min-w-0">
                                <p class="font-bold text-slate-900 dark:text-white text-sm truncate">{{ a.nombre_completo }}</p>
                                <p class="text-[10px] text-slate-400 font-mono">{{ a.creditos[0]?.clave_contrato ?? 'Sin contrato' }}</p>
                            </div>
                        </div>
                        <span v-if="a.creditos[0]?.estatus"
                            class="shrink-0 px-2.5 py-1 rounded-full text-[10px] font-bold"
                            :class="estatusEstilo(a.creditos[0]?.estatus).bg">
                            {{ a.creditos[0]?.estatus }}
                        </span>
                    </div>
                    <div class="grid grid-cols-2 gap-2 text-xs text-slate-500 dark:text-slate-400 pl-1.5">
                        <div class="flex items-center gap-1"><MapPin :size="12" class="text-slate-400 shrink-0" /> <span class="truncate">{{ a.municipio }}</span></div>
                        <div v-if="a.creditos[0]?.modalidad" class="flex items-center gap-1.5">
                            <span :class="['w-1.5 h-1.5 rounded-full', modalidadEstilo(a.creditos[0]?.modalidad?.nombre).dot]"></span>
                            {{ a.creditos[0]?.modalidad?.nombre?.split(' ')[0] }}
                        </div>
                        <div class="col-span-2 font-black text-slate-900 dark:text-white text-base mt-1">{{ money(a.creditos[0]?.monto_otorgado) }}</div>
                    </div>
                    <Link :href="route('acreditados.show', a.id)" @click.stop
                        class="mt-3 flex items-center justify-center gap-1.5 w-full py-2.5 bg-slate-100 dark:bg-zinc-800 rounded-xl text-xs font-bold text-slate-700 dark:text-zinc-300">
                        <FileText :size="14" /> Ver expediente
                    </Link>
                </div>
            </div>
            <div v-if="!acreditados.data?.length" class="sm:hidden bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 py-16 px-6 text-center">
                <FolderX :size="32" class="mx-auto text-slate-300 dark:text-zinc-700 mb-3" />
                <p class="text-slate-400 text-sm">No hay acreditados con los filtros seleccionados.</p>
            </div>

            <!-- Tabla (sm+) -->
            <div class="hidden sm:block bg-white dark:bg-slate-900 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-800 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[720px] text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/70 dark:bg-slate-800/50 border-b border-slate-100 dark:border-slate-800 text-slate-500 dark:text-slate-400 text-[11px] uppercase tracking-widest font-bold">
                                <th class="p-5">Acreditado</th>
                                <th class="p-5">Municipio</th>
                                <th class="p-5">Modalidad</th>
                                <th class="p-5 text-right">Monto</th>
                                <th class="p-5 text-center">Estatus</th>
                                <th class="p-5 text-center w-16"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50 dark:divide-slate-800/70">
                            <tr v-for="a in acreditados.data" :key="a.id"
                                class="relative hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors group cursor-pointer"
                                @click="router.visit(route('acreditados.show', a.id))">
                                <td class="p-5">
                                    <div class="flex items-center gap-3">
                                        <span :class="['w-1 h-8 rounded-full shrink-0', estatusEstilo(a.creditos[0]?.estatus).bar]"></span>
                                        <div :class="['h-10 w-10 rounded-full flex items-center justify-center text-xs font-black shrink-0 transition-transform group-hover:scale-105', avatarColor(a.nombre_completo).bg, avatarColor(a.nombre_completo).text]">
                                            {{ iniciales(a.nombre_completo) }}
                                        </div>
                                        <div class="min-w-0">
                                            <p class="font-bold text-slate-900 dark:text-white text-sm leading-tight truncate">{{ a.nombre_completo }}</p>
                                            <p class="text-[10px] text-slate-400 font-mono mt-0.5">{{ a.creditos[0]?.clave_contrato ?? 'Sin contrato' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="p-5 text-sm text-slate-500 dark:text-slate-400">
                                    <div class="flex items-center gap-1.5">
                                        <MapPin :size="13" class="text-slate-400 shrink-0" />
                                        {{ a.municipio }}
                                    </div>
                                </td>
                                <td class="p-5">
                                    <span v-if="a.creditos[0]?.modalidad"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold"
                                        :class="modalidadEstilo(a.creditos[0]?.modalidad?.nombre).bg">
                                        <span :class="['w-1.5 h-1.5 rounded-full', modalidadEstilo(a.creditos[0]?.modalidad?.nombre).dot]"></span>
                                        {{ a.creditos[0]?.modalidad?.nombre?.split(' ')[0] }}
                                    </span>
                                    <span v-else class="text-slate-300 dark:text-zinc-700 text-xs">—</span>
                                </td>
                                <td class="p-5 text-right font-black text-slate-900 dark:text-white text-sm">
                                    {{ money(a.creditos[0]?.monto_otorgado) }}
                                </td>
                                <td class="p-5 text-center">
                                    <span v-if="a.creditos[0]?.estatus"
                                        class="px-2.5 py-1 rounded-full text-[11px] font-bold"
                                        :class="estatusEstilo(a.creditos[0]?.estatus).bg">
                                        {{ a.creditos[0]?.estatus }}
                                    </span>
                                    <span v-else class="text-slate-300 dark:text-zinc-700 text-xs">—</span>
                                </td>
                                <td class="p-5 text-center">
                                    <ChevronRight :size="18" class="text-slate-300 dark:text-zinc-700 group-hover:text-red-600 group-hover:translate-x-0.5 transition-all inline-block" />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="!acreditados.data?.length" class="py-20 text-center">
                    <FolderX :size="36" class="mx-auto text-slate-300 dark:text-zinc-700 mb-3" />
                    <p class="text-slate-400 text-sm">No hay acreditados con los filtros seleccionados.</p>
                    <button v-if="hayFiltros" @click="limpiarFiltros" class="mt-3 text-xs font-bold text-red-600 dark:text-red-400 hover:underline">Limpiar filtros</button>
                </div>
            </div>

            <!-- Paginación -->
            <div v-if="acreditados.data?.length" class="flex flex-col sm:flex-row items-center justify-between gap-3">
                <p class="text-xs text-slate-400">
                    Mostrando {{ acreditados.from }}–{{ acreditados.to }} de {{ acreditados.total }}
                </p>
                <div class="flex flex-wrap justify-center gap-1.5">
                    <Link v-for="link in acreditados.links" :key="link.label"
                        :href="link.url || '#'"
                        v-html="link.label"
                        class="px-3 py-2 rounded-xl text-sm font-bold transition-all"
                        :class="[
                            link.active ? 'bg-red-600 text-white shadow' : 'bg-white dark:bg-slate-900 text-slate-500 hover:bg-slate-50 dark:hover:bg-slate-800 border border-slate-100 dark:border-slate-800',
                            !link.url ? 'opacity-40 pointer-events-none' : ''
                        ]" />
                </div>
            </div>

        </div>
    </AppLayout>
</template>
