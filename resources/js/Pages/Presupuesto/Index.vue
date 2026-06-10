<script setup>
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import {
    DollarSign, TrendingUp, PlusCircle, BarChart2,
    CheckCircle2, AlertTriangle, ChevronDown, ChevronUp,
    Calendar, Target, Save, RefreshCw, Layers
} from 'lucide-vue-next';

const props = defineProps({
    presupuestos: Array,   // [{ id, modalidad, nombre_modalidad, anio, monto_asignado, monto_ejercido, monto_disponible, porcentaje }]
    totales: Object,       // { asignado, ejercido, disponible, porcentaje_global }
    anios: Array,          // años disponibles para filtrar
    filters: Object,       // { anio }
    modalidades: Array,    // para el formulario
});

// Filtro año
const anioFiltro = ref(props.filters?.anio ?? new Date().getFullYear());

const cambiarAnio = (val) => {
    anioFiltro.value = val;
    router.get(route('presupuesto.index'), { anio: val }, { preserveState: true, replace: true });
};

// Formulario nuevo presupuesto
const mostrarFormulario = ref(false);
const form = useForm({
    modalidad_id: '',
    anio: new Date().getFullYear(),
    monto_asignado: '',
});

const guardar = () => {
    form.post(route('presupuesto.store'), {
        onSuccess: () => {
            mostrarFormulario.value = false;
            form.reset();
        },
    });
};

// Formateo
const fmt = (n) => {
    const num = parseFloat(n) || 0;
    return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN', minimumFractionDigits: 0 }).format(num);
};

const fmtPct = (n) => `${parseFloat(n || 0).toFixed(1)}%`;

// Color de la barra de progreso
const barColor = (pct) => {
    const p = parseFloat(pct) || 0;
    if (p >= 90) return 'bg-red-500';
    if (p >= 70) return 'bg-amber-500';
    if (p >= 40) return 'bg-blue-500';
    return 'bg-emerald-500';
};

const badgeEjercido = (pct) => {
    const p = parseFloat(pct) || 0;
    if (p >= 90) return { bg: 'bg-red-100 dark:bg-red-900/30', text: 'text-red-700 dark:text-red-400', icon: AlertTriangle };
    if (p >= 70) return { bg: 'bg-amber-100 dark:bg-amber-900/30', text: 'text-amber-700 dark:text-amber-400', icon: TrendingUp };
    return { bg: 'bg-emerald-100 dark:bg-emerald-900/30', text: 'text-emerald-700 dark:text-emerald-400', icon: CheckCircle2 };
};

const aniosDisponibles = computed(() => {
    if (props.anios?.length) return props.anios;
    const year = new Date().getFullYear();
    return [year - 1, year, year + 1];
});
</script>

<template>
    <Head title="Presupuesto — CREA" />

    <AppLayout>
        <div class="max-w-[98%] mx-auto py-8 px-4">

            <!-- Encabezado -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-[#701a27] rounded-2xl flex items-center justify-center shadow-md">
                        <BarChart2 :size="22" class="text-white" />
                    </div>
                    <div>
                        <h1 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-tight">
                            Control de Presupuesto
                        </h1>
                        <p class="text-slate-500 dark:text-slate-400 text-sm font-medium">
                            Asignación y ejercicio por modalidad de crédito.
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <!-- Selector de año -->
                    <div class="flex items-center gap-1 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl p-1 shadow-sm">
                        <button v-for="a in aniosDisponibles" :key="a"
                            @click="cambiarAnio(a)"
                            :class="[
                                'px-4 py-2 rounded-lg text-sm font-bold transition-all',
                                anioFiltro == a
                                    ? 'bg-[#701a27] text-white shadow-sm'
                                    : 'text-slate-500 hover:text-slate-800 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800'
                            ]">
                            {{ a }}
                        </button>
                    </div>

                    <button @click="mostrarFormulario = !mostrarFormulario"
                        class="flex items-center gap-2 px-5 py-2.5 bg-[#701a27] hover:bg-red-800 text-white font-bold rounded-xl text-sm transition-all shadow-sm active:scale-95">
                        <component :is="mostrarFormulario ? ChevronUp : PlusCircle" :size="16" />
                        {{ mostrarFormulario ? 'Cerrar' : 'Nuevo Presupuesto' }}
                    </button>

                    <button @click="router.reload()"
                        class="p-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 hover:bg-slate-200 dark:hover:bg-slate-700 transition-all">
                        <RefreshCw :size="18" />
                    </button>
                </div>
            </div>

            <!-- Formulario nuevo presupuesto -->
            <Transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="-translate-y-3 opacity-0"
                enter-to-class="translate-y-0 opacity-100"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="translate-y-0 opacity-100"
                leave-to-class="-translate-y-3 opacity-0"
            >
                <div v-if="mostrarFormulario"
                    class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-700 p-6 mb-8 shadow-sm">
                    <h3 class="font-black text-slate-900 dark:text-white text-base mb-5 flex items-center gap-2 uppercase tracking-tight">
                        <PlusCircle :size="18" class="text-[#701a27]" /> Asignar Nuevo Presupuesto
                    </h3>
                    <form @submit.prevent="guardar">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <!-- Modalidad -->
                            <div>
                                <label class="block text-[10px] font-black text-slate-400 uppercase mb-1.5">Modalidad</label>
                                <div class="relative">
                                    <Layers :size="14" class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none" />
                                    <select v-model="form.modalidad_id" required
                                        class="w-full pl-9 pr-4 py-3 rounded-xl border dark:bg-slate-800 dark:text-white text-sm focus:ring-2 focus:ring-red-500 transition-all appearance-none"
                                        :class="form.errors.modalidad_id ? 'border-red-400' : 'border-slate-200 dark:border-slate-700'">
                                        <option value="">Seleccionar modalidad</option>
                                        <option v-for="m in modalidades" :key="m.id" :value="m.id">{{ m.nombre }}</option>
                                    </select>
                                </div>
                                <p v-if="form.errors.modalidad_id" class="text-xs text-red-500 mt-1">{{ form.errors.modalidad_id }}</p>
                            </div>

                            <!-- Año -->
                            <div>
                                <label class="block text-[10px] font-black text-slate-400 uppercase mb-1.5">Año</label>
                                <div class="relative">
                                    <Calendar :size="14" class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none" />
                                    <input v-model="form.anio" type="number" min="2020" max="2040" required
                                        class="w-full pl-9 pr-4 py-3 rounded-xl border dark:bg-slate-800 dark:text-white text-sm focus:ring-2 focus:ring-red-500 transition-all"
                                        :class="form.errors.anio ? 'border-red-400' : 'border-slate-200 dark:border-slate-700'" />
                                </div>
                                <p v-if="form.errors.anio" class="text-xs text-red-500 mt-1">{{ form.errors.anio }}</p>
                            </div>

                            <!-- Monto -->
                            <div>
                                <label class="block text-[10px] font-black text-slate-400 uppercase mb-1.5">Monto Asignado (MXN)</label>
                                <div class="relative">
                                    <DollarSign :size="14" class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none" />
                                    <input v-model="form.monto_asignado" type="number" step="0.01" min="1" required
                                        placeholder="0.00"
                                        class="w-full pl-9 pr-4 py-3 rounded-xl border dark:bg-slate-800 dark:text-white text-sm focus:ring-2 focus:ring-red-500 transition-all"
                                        :class="form.errors.monto_asignado ? 'border-red-400' : 'border-slate-200 dark:border-slate-700'" />
                                </div>
                                <p v-if="form.errors.monto_asignado" class="text-xs text-red-500 mt-1">{{ form.errors.monto_asignado }}</p>
                            </div>
                        </div>

                        <div class="flex gap-3 mt-5 justify-end">
                            <button type="button" @click="mostrarFormulario = false; form.reset()"
                                class="px-5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-sm font-bold text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                                Cancelar
                            </button>
                            <button type="submit" :disabled="form.processing"
                                class="flex items-center gap-2 px-6 py-2.5 bg-[#701a27] hover:bg-red-800 text-white text-sm font-bold rounded-xl disabled:opacity-50 transition-all active:scale-95">
                                <Save :size="15" />
                                {{ form.processing ? 'Guardando...' : 'Guardar Presupuesto' }}
                            </button>
                        </div>
                    </form>
                </div>
            </Transition>

            <!-- Cards de totales globales -->
            <div v-if="totales" class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
                <div class="bg-[#701a27] rounded-3xl p-5 text-white shadow-xl relative overflow-hidden col-span-1">
                    <DollarSign class="absolute -right-3 -bottom-3 text-white/10" :size="80" />
                    <p class="text-[10px] font-black uppercase tracking-widest opacity-70 mb-1">Total Asignado {{ anioFiltro }}</p>
                    <p class="text-2xl font-black">{{ fmt(totales.asignado) }}</p>
                </div>
                <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-100 dark:border-slate-800 p-5 shadow-sm">
                    <div class="flex items-center justify-between mb-1">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Monto Ejercido</p>
                        <TrendingUp :size="16" class="text-blue-500" />
                    </div>
                    <p class="text-2xl font-black text-slate-900 dark:text-white">{{ fmt(totales.ejercido) }}</p>
                </div>
                <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-100 dark:border-slate-800 p-5 shadow-sm">
                    <div class="flex items-center justify-between mb-1">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Disponible</p>
                        <CheckCircle2 :size="16" class="text-emerald-500" />
                    </div>
                    <p class="text-2xl font-black text-emerald-600 dark:text-emerald-400">{{ fmt(totales.disponible) }}</p>
                </div>
                <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-100 dark:border-slate-800 p-5 shadow-sm">
                    <div class="flex items-center justify-between mb-1">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">% Global Ejercido</p>
                        <Target :size="16" class="text-amber-500" />
                    </div>
                    <p class="text-2xl font-black text-slate-900 dark:text-white">{{ fmtPct(totales.porcentaje_global) }}</p>
                    <!-- Barra global -->
                    <div class="mt-2 h-2 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                        <div :class="['h-full rounded-full transition-all duration-500', barColor(totales.porcentaje_global)]"
                            :style="{ width: Math.min(100, parseFloat(totales.porcentaje_global) || 0) + '%' }"></div>
                    </div>
                </div>
            </div>

            <!-- Cards por modalidad -->
            <div v-if="presupuestos?.length" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5 mb-10">
                <div v-for="p in presupuestos" :key="p.id"
                    class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-100 dark:border-slate-800 p-6 shadow-sm hover:shadow-md transition-shadow">

                    <!-- Nombre modalidad + año -->
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <p class="font-black text-slate-900 dark:text-white text-base leading-tight">{{ p.nombre_modalidad }}</p>
                            <p class="text-[11px] text-slate-400 font-semibold flex items-center gap-1 mt-0.5">
                                <Calendar :size="11" /> Año {{ p.anio }}
                            </p>
                        </div>
                        <div :class="['inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-black', badgeEjercido(p.porcentaje).bg, badgeEjercido(p.porcentaje).text]">
                            <component :is="badgeEjercido(p.porcentaje).icon" :size="11" />
                            {{ fmtPct(p.porcentaje) }}
                        </div>
                    </div>

                    <!-- Barra de progreso -->
                    <div class="mb-4">
                        <div class="flex justify-between text-[10px] font-bold text-slate-400 mb-1.5">
                            <span>Ejercido</span>
                            <span>{{ fmtPct(p.porcentaje) }}</span>
                        </div>
                        <div class="h-3 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                            <div :class="['h-full rounded-full transition-all duration-700', barColor(p.porcentaje)]"
                                :style="{ width: Math.min(100, parseFloat(p.porcentaje) || 0) + '%' }"></div>
                        </div>
                    </div>

                    <!-- Montos -->
                    <div class="grid grid-cols-3 gap-2 pt-4 border-t border-slate-50 dark:border-slate-800">
                        <div>
                            <p class="text-[9px] font-black text-slate-400 uppercase">Asignado</p>
                            <p class="text-sm font-black text-slate-800 dark:text-white">{{ fmt(p.monto_asignado) }}</p>
                        </div>
                        <div>
                            <p class="text-[9px] font-black text-slate-400 uppercase">Ejercido</p>
                            <p class="text-sm font-black text-blue-600 dark:text-blue-400">{{ fmt(p.monto_ejercido) }}</p>
                        </div>
                        <div>
                            <p class="text-[9px] font-black text-slate-400 uppercase">Disponible</p>
                            <p class="text-sm font-black"
                                :class="parseFloat(p.monto_disponible) > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400'">
                                {{ fmt(p.monto_disponible) }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Estado vacío -->
            <div v-if="!presupuestos?.length" class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-100 dark:border-slate-800 p-16 text-center shadow-sm">
                <BarChart2 :size="40" class="mx-auto mb-4 text-slate-300 dark:text-slate-600" />
                <p class="font-black text-slate-500 dark:text-slate-400 text-lg mb-2">Sin presupuesto asignado</p>
                <p class="text-slate-400 text-sm mb-5">No hay presupuesto registrado para el año {{ anioFiltro }}.</p>
                <button @click="mostrarFormulario = true"
                    class="inline-flex items-center gap-2 px-6 py-3 bg-[#701a27] hover:bg-red-800 text-white font-bold rounded-xl text-sm transition-all active:scale-95">
                    <PlusCircle :size="16" /> Asignar Presupuesto
                </button>
            </div>

            <!-- Tabla resumen -->
            <div v-if="presupuestos?.length" class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-100 dark:border-slate-800 overflow-hidden shadow-sm">
                <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/40">
                    <h2 class="font-black text-slate-800 dark:text-white text-xs uppercase tracking-widest flex items-center gap-2">
                        <BarChart2 :size="16" class="text-[#701a27]" /> Resumen por Modalidad — {{ anioFiltro }}
                    </h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-slate-800/60 text-slate-400 text-[10px] uppercase tracking-widest font-black border-b border-slate-100 dark:border-slate-800">
                                <th class="px-5 py-4">Modalidad</th>
                                <th class="px-5 py-4 text-right">Asignado</th>
                                <th class="px-5 py-4 text-right">Ejercido</th>
                                <th class="px-5 py-4 text-right">Disponible</th>
                                <th class="px-5 py-4 text-center">% Ejercido</th>
                                <th class="px-5 py-4">Progreso</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50 dark:divide-slate-800">
                            <tr v-for="p in presupuestos" :key="'t-' + p.id"
                                class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors">
                                <td class="px-5 py-4">
                                    <p class="font-bold text-slate-900 dark:text-white">{{ p.nombre_modalidad }}</p>
                                    <p class="text-[10px] text-slate-400">Año {{ p.anio }}</p>
                                </td>
                                <td class="px-5 py-4 text-right font-bold text-slate-700 dark:text-slate-300">
                                    {{ fmt(p.monto_asignado) }}
                                </td>
                                <td class="px-5 py-4 text-right font-bold text-blue-600 dark:text-blue-400">
                                    {{ fmt(p.monto_ejercido) }}
                                </td>
                                <td class="px-5 py-4 text-right font-bold"
                                    :class="parseFloat(p.monto_disponible) > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400'">
                                    {{ fmt(p.monto_disponible) }}
                                </td>
                                <td class="px-5 py-4 text-center">
                                    <div :class="['inline-flex items-center gap-1 text-[11px] font-black px-2.5 py-1 rounded-full', badgeEjercido(p.porcentaje).bg, badgeEjercido(p.porcentaje).text]">
                                        {{ fmtPct(p.porcentaje) }}
                                    </div>
                                </td>
                                <td class="px-5 py-4 min-w-[120px]">
                                    <div class="h-2 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                                        <div :class="['h-full rounded-full transition-all duration-500', barColor(p.porcentaje)]"
                                            :style="{ width: Math.min(100, parseFloat(p.porcentaje) || 0) + '%' }"></div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                        <!-- Total -->
                        <tfoot v-if="totales" class="bg-slate-900 dark:bg-black text-white">
                            <tr>
                                <td class="px-5 py-4 text-xs uppercase font-black tracking-widest text-slate-300">
                                    Total General
                                </td>
                                <td class="px-5 py-4 text-right font-black text-white">{{ fmt(totales.asignado) }}</td>
                                <td class="px-5 py-4 text-right font-black text-blue-300">{{ fmt(totales.ejercido) }}</td>
                                <td class="px-5 py-4 text-right font-black text-emerald-300">{{ fmt(totales.disponible) }}</td>
                                <td class="px-5 py-4 text-center font-black text-amber-300">{{ fmtPct(totales.porcentaje_global) }}</td>
                                <td class="px-5 py-4">
                                    <div class="h-2 bg-slate-700 rounded-full overflow-hidden">
                                        <div :class="['h-full rounded-full', barColor(totales.porcentaje_global)]"
                                            :style="{ width: Math.min(100, parseFloat(totales.porcentaje_global) || 0) + '%' }"></div>
                                    </div>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

        </div>
    </AppLayout>
</template>

<style scoped>
.overflow-x-auto::-webkit-scrollbar { height: 6px; }
.overflow-x-auto::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
</style>
