<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Search, UserPlus, FileText, MapPin, TrendingUp, Users, Filter } from 'lucide-vue-next';
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import debounce from 'lodash/debounce';

const props = defineProps({
    acreditados: Object,  // paginated
    filters: Object,
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

watch([estatus, municipio], () => applyFilters());
watch(search, debounce(() => applyFilters(), 300));

const getBadgeClass = (nombre) => {
    if (!nombre) return 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400';
    if (nombre.includes('Artesanal')) return 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400';
    if (nombre.includes('Sustentable')) return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400';
    return 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400';
};

const estatusBadge = (e) => {
    if (e === 'Activo')    return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400';
    if (e === 'Moroso')    return 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400';
    if (e === 'Liquidado') return 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400';
    return 'bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400';
};

const money = (v) => new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN', minimumFractionDigits: 0 }).format(v || 0);
</script>

<template>
    <Head title="Acreditados CREA" />

    <AppLayout>
        <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">

            <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-4 mb-8">
                <div class="border-l-4 border-red-700 dark:border-red-500 pl-4">
                    <h1 class="text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight">Cartera de Acreditados</h1>
                    <p class="text-slate-500 dark:text-slate-400 mt-1">
                        {{ acreditados.total }} acreditados registrados
                    </p>
                </div>
                <Link :href="route('acreditados.create')"
                      class="flex items-center gap-2 bg-red-600 hover:bg-red-700 text-white font-bold px-6 py-3 rounded-xl shadow-lg shadow-red-900/20 transition-all active:scale-95">
                    <UserPlus :size="20" />
                    Nuevo Registro
                </Link>
            </div>

            <!-- Filtros -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-6">
                <div class="relative md:col-span-1">
                    <Search class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400" :size="18" />
                    <input v-model="search" type="text"
                           placeholder="Nombre, RFC o contrato..."
                           class="w-full pl-11 pr-4 py-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl text-slate-900 dark:text-white focus:ring-2 focus:ring-red-500 transition-all shadow-sm text-sm" />
                </div>
                <select v-model="estatus"
                    class="py-3 px-4 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl text-slate-700 dark:text-white text-sm focus:ring-2 focus:ring-red-500 shadow-sm">
                    <option value="">Todos los estatus</option>
                    <option value="Activo">Activo</option>
                    <option value="Moroso">Moroso</option>
                    <option value="Liquidado">Liquidado</option>
                </select>
                <div class="flex items-center gap-2">
                    <button v-if="search || estatus || municipio"
                        @click="search=''; estatus=''; municipio=''; applyFilters()"
                        class="text-xs text-red-500 font-bold hover:underline whitespace-nowrap">
                        Limpiar filtros
                    </button>
                </div>
            </div>

            <!-- Tabla -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-800 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/50 dark:bg-slate-800/50 border-b border-slate-100 dark:border-slate-800 text-slate-500 dark:text-slate-400 text-xs uppercase tracking-wider">
                                <th class="p-5 font-bold">Acreditado</th>
                                <th class="p-5 font-bold">Municipio</th>
                                <th class="p-5 font-bold">Modalidad</th>
                                <th class="p-5 font-bold text-right">Monto</th>
                                <th class="p-5 font-bold text-center">Estatus</th>
                                <th class="p-5 font-bold text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr v-for="a in acreditados.data" :key="a.id"
                                class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors group cursor-pointer"
                                @click="router.visit(route('acreditados.show', a.id))">
                                <td class="p-5">
                                    <div class="flex items-center gap-3">
                                        <div class="h-9 w-9 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-500 dark:text-slate-400 group-hover:bg-red-100 dark:group-hover:bg-red-900/30 group-hover:text-red-600 transition-colors flex-shrink-0">
                                            <Users :size="18" />
                                        </div>
                                        <div>
                                            <p class="font-bold text-slate-900 dark:text-white text-sm leading-tight">{{ a.nombre_completo }}</p>
                                            <p class="text-[10px] text-slate-400 font-mono">{{ a.creditos[0]?.clave_contrato }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="p-5 text-sm text-slate-500 dark:text-slate-400">
                                    <div class="flex items-center gap-1">
                                        <MapPin :size="12" class="text-slate-400" />
                                        {{ a.municipio }}
                                    </div>
                                </td>
                                <td class="p-5">
                                    <span v-if="a.creditos[0]?.modalidad"
                                        class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                                        :class="getBadgeClass(a.creditos[0]?.modalidad?.nombre)">
                                        {{ a.creditos[0]?.modalidad?.nombre?.split(' ')[0] }}
                                    </span>
                                </td>
                                <td class="p-5 text-right font-bold text-slate-900 dark:text-white text-sm">
                                    {{ money(a.creditos[0]?.monto_otorgado) }}
                                </td>
                                <td class="p-5 text-center">
                                    <span v-if="a.creditos[0]?.estatus"
                                        class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                                        :class="estatusBadge(a.creditos[0]?.estatus)">
                                        {{ a.creditos[0]?.estatus }}
                                    </span>
                                </td>
                                <td class="p-5 text-center" @click.stop>
                                    <Link :href="route('acreditados.show', a.id)"
                                        class="p-2 text-slate-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-all inline-flex" title="Ver expediente">
                                        <FileText :size="16" />
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="!acreditados.data?.length" class="py-16 text-center">
                    <p class="text-slate-400 text-sm">No hay acreditados con los filtros seleccionados.</p>
                </div>
            </div>

            <!-- Paginación -->
            <div class="mt-5 flex justify-center gap-1.5">
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
    </AppLayout>
</template>
