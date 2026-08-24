<script setup>
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import { computed, ref, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import { Doughnut, Bar, Line } from 'vue-chartjs';
import {
    Chart as ChartJS, Title, Tooltip, Legend, ArcElement,
    CategoryScale, LinearScale, BarElement, LineElement, PointElement, Filler
} from 'chart.js';

ChartJS.register(Title, Tooltip, Legend, ArcElement, CategoryScale, LinearScale, BarElement, LineElement, PointElement, Filler);

const props = defineProps({
    stats: Object,
    modalidades: Array,
    modalidad_activa: Number,
});

function filtrarModalidad(id) {
    router.visit(route('dashboard'), {
        data: id ? { modalidad_id: id } : {},
        preserveScroll: true,
        replace: true,
    });
}

// ─── Formatting ────────────────────────────────────────────────────────────
const money = (v) => new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN', minimumFractionDigits: 0 }).format(v || 0);
const today = new Date().toLocaleDateString('es-MX', { year: 'numeric', month: 'long', day: 'numeric' });

// ─── Dark mode reactivity ───────────────────────────────────────────────────
const isDark = ref(false);
onMounted(() => {
    const update = () => { isDark.value = document.documentElement.classList.contains('dark'); };
    update();
    new MutationObserver(update).observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
});

// ─── Chart options (reusable) ───────────────────────────────────────────────
const doughnutOpts = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
        tooltip: { enabled: true }
    },
    // Forces re-render when dark mode changes
    _dark: isDark.value,
}));

// ─── Retorno de capital ─────────────────────────────────────────────────────
const retornoData = computed(() => ({
    labels: ['Recuperado', 'Pendiente'],
    datasets: [{
        data: [props.stats?.capital?.recuperado || 0, props.stats?.capital?.pendiente || 0],
        backgroundColor: ['#10b981', isDark.value ? '#3f3f46' : '#e4e4e7'],
        borderWidth: 0,
        cutout: '82%'
    }]
}));

// ─── Género ─────────────────────────────────────────────────────────────────
const generoData = computed(() => ({
    labels: ['Hombres', 'Mujeres'],
    datasets: [{
        data: [props.stats?.genero?.H || 0, props.stats?.genero?.M || 0],
        backgroundColor: isDark.value ? ['#e4e4e7', '#52525b'] : ['#18181b', '#a1a1aa'],
        borderWidth: 0,
        cutout: '80%'
    }]
}));

const pctMujeres = computed(() => {
    const { H = 0, M = 0 } = props.stats?.genero || {};
    return H + M > 0 ? Math.round((M / (H + M)) * 100) : 0;
});

// ─── Modalidades ────────────────────────────────────────────────────────────
const MODAL_COLORS_LIGHT = ['#18181b', '#52525b', '#a1a1aa', '#d4d4d8'];
const MODAL_COLORS_DARK  = ['#e4e4e7', '#a1a1aa', '#71717a', '#3f3f46'];

const modalidadData = computed(() => {
    const items = props.stats?.por_modalidad || [];
    const colors = isDark.value ? MODAL_COLORS_DARK : MODAL_COLORS_LIGHT;
    return {
        labels: items.map(m => m.nombre),
        datasets: [{ data: items.map(m => m.total), backgroundColor: colors, borderWidth: 0, cutout: '75%' }]
    };
});
const modalidadColors = ['bg-zinc-900 dark:bg-zinc-100', 'bg-zinc-500 dark:bg-zinc-400', 'bg-zinc-300 dark:bg-zinc-500', 'bg-zinc-200 dark:bg-zinc-600'];

// ─── Evolución mensual ───────────────────────────────────────────────────────
const evolucionData = computed(() => {
    const items = props.stats?.evolucion_mensual || [];
    return {
        labels: items.map(m => m.label),
        datasets: [{
            label: 'Cobrado MXN',
            data: items.map(m => m.monto),
            borderColor: '#10b981',
            backgroundColor: 'rgba(16,185,129,0.08)',
            fill: true,
            tension: 0.4,
            pointRadius: 4,
            pointBackgroundColor: '#10b981',
            borderWidth: 2,
        }]
    };
});

const lineOpts = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    scales: {
        x: { grid: { display: false }, ticks: { font: { size: 10, weight: '600' }, color: isDark.value ? '#71717a' : '#a1a1aa' } },
        y: {
            grid: { color: isDark.value ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.04)' },
            ticks: { font: { size: 10 }, color: isDark.value ? '#71717a' : '#a1a1aa', callback: (v) => '$' + (v / 1000).toFixed(0) + 'k' }
        }
    },
    plugins: { legend: { display: false }, tooltip: { callbacks: { label: (ctx) => ' ' + money(ctx.raw) } } }
}));

// ─── Mapa Yucatán ────────────────────────────────────────────────────────────
// Coordenadas calculadas desde lat/lon real → viewBox 0 0 400 280
// bbox: lat[19.5–21.6], lon[-90.4 – -87.4]
// x = (lon + 90.4) / 3.0 * 400 | y = (21.6 - lat) / 2.1 * 280
const COORDS = {
    'Mérida':           { x: 104, y: 84  },
    'Progreso':         { x: 98,  y: 42  },
    'Valladolid':       { x: 293, y: 121 },
    'Tizimín':          { x: 298, y: 61  },
    'Tekax':            { x: 149, y: 187 },
    'Izamal':           { x: 184, y: 89  },
    'Ticul':            { x: 116, y: 160 },
    'Motul':            { x: 149, y: 67  },
    'Umán':             { x: 87,  y: 96  },
    'Maxcanú':          { x: 53,  y: 136 },
    'Hunucmá':          { x: 71,  y: 80  },
    'Espita':           { x: 279, y: 78  },
    'Oxkutzcab':        { x: 131, y: 173 },
    'Peto':             { x: 198, y: 196 },
    'Acanceh':          { x: 127, y: 104 },
    'Sotuta':           { x: 191, y: 131 },
    'Panabá':           { x: 284, y: 42  },
    'Temozon':          { x: 253, y: 89  },
    'Kanasín':          { x: 118, y: 90  },
    'Conkal':           { x: 120, y: 72  },
    'Kinchil':          { x: 70,  y: 105 },
    'Halachó':          { x: 40,  y: 150 },
    'Celestún':         { x: 10,  y: 105 },
    'Muna':             { x: 110, y: 148 },
    'Calotmul':         { x: 250, y: 60  },
    'Chemax':           { x: 310, y: 105 },
    'Telchac Puerto':   { x: 155, y: 38  },
    'Dzidzantún':       { x: 160, y: 52  },
    'Buctzotz':         { x: 200, y: 52  },
    'Tinum':            { x: 255, y: 100 },
    'Chacsinkín':       { x: 200, y: 225 },
    'Tzucacab':         { x: 175, y: 240 },
    'Teabo':            { x: 160, y: 175 },
    'Maní':             { x: 145, y: 160 },
    'Dzan':             { x: 130, y: 155 },
    'Sacalum':          { x: 125, y: 145 },
    'Opichén':          { x: 75,  y: 135 },
    'Samahil':          { x: 85,  y: 110 },
    'Abalá':            { x: 100, y: 120 },
    'Homún':            { x: 155, y: 108 },
    'Hocabá':           { x: 158, y: 98  },
    'Seye':             { x: 148, y: 100 },
    'Mocochá':          { x: 112, y: 62  },
    'Ucú':              { x: 88,  y: 74  },
    'Tetiz':            { x: 74,  y: 68  },
};

// Contorno simplificado del estado de Yucatán (path SVG aproximado)
const YUCATAN_PATH = 'M 0,97 L 33,33 L 63,57 L 98,43 L 149,33 L 200,29 L 297,1 L 393,9 L 398,80 L 387,173 L 360,240 L 320,267 L 280,280 L 173,280 L 93,253 L 40,213 L 13,147 Z';

function heatColor(t) {
    // Blue → Amber → Red (classic heatmap 3-stop)
    if (t < 0.5) {
        const s = t * 2;
        return `rgb(${Math.round(59 + s * (245 - 59))},${Math.round(130 + s * (158 - 130))},${Math.round(246 + s * (11 - 246))})`;
    }
    const s = (t - 0.5) * 2;
    return `rgb(${Math.round(245 + s * (239 - 245))},${Math.round(158 + s * (68 - 158))},${Math.round(11 + s * (68 - 11))})`;
}

const mapBubbles = computed(() => {
    const munis = props.stats?.por_municipio || [];
    const max = Math.max(...munis.map(m => m.total), 1);
    return munis
        .map(m => {
            const c = COORDS[m.municipio];
            if (!c) return null;
            const t = m.total / max;
            return { municipio: m.municipio, total: m.total, monto: m.monto, x: c.x, y: c.y, r: Math.max(5, Math.sqrt(t) * 22), color: heatColor(t) };
        })
        .filter(Boolean)
        .sort((a, b) => a.total - b.total); // small behind, large on top
});

const mapContainer = ref(null);
const tooltip = ref(null);

function showTooltip(event, bubble) {
    if (!mapContainer.value) return;
    const rect = mapContainer.value.getBoundingClientRect();
    tooltip.value = { domX: event.clientX - rect.left, domY: event.clientY - rect.top, ...bubble };
}
function hideTooltip() { tooltip.value = null; }

// Top municipios para tabla
const topMunicipios = computed(() => (props.stats?.por_municipio || []).slice(0, 8));
const maxMunicipio  = computed(() => Math.max(...(props.stats?.por_municipio || []).map(m => m.total), 1));

// Municipios sin coordenadas (para mostrar al usuario cuáles no están mapeados)
const municipiosSinMapa = computed(() =>
    (props.stats?.por_municipio || []).filter(m => !COORDS[m.municipio]).map(m => m.municipio)
);
</script>

<template>
    <AppLayout title="Dashboard">
        <div class="min-h-screen p-5 md:p-8 bg-[#F7F7F8] dark:bg-[#0A0A0A] font-sans">

            <!-- Header -->
            <div class="flex flex-wrap justify-between items-end gap-3 mb-8">
                <div>
                    <p class="text-[10px] font-bold tracking-[0.4em] text-zinc-400 uppercase">IYEM · CREA</p>
                    <h1 class="text-2xl font-medium dark:text-white mt-0.5">Panel de Control</h1>
                </div>
                <!-- Filtro de Modalidad -->
                <div class="flex items-center gap-2">
                    <span class="text-[10px] text-zinc-400 uppercase font-bold tracking-widest hidden sm:block">Modalidad</span>
                    <div class="flex flex-wrap gap-1.5">
                        <button @click="filtrarModalidad(null)"
                            class="px-3 py-1.5 rounded-lg text-[11px] font-semibold transition-all"
                            :class="!modalidad_activa
                                ? 'bg-zinc-900 dark:bg-zinc-100 text-white dark:text-zinc-900'
                                : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400 hover:bg-zinc-200 dark:hover:bg-zinc-700'">
                            Todos
                        </button>
                        <button v-for="m in modalidades" :key="m.id"
                            @click="filtrarModalidad(m.id)"
                            class="px-3 py-1.5 rounded-lg text-[11px] font-semibold transition-all"
                            :class="modalidad_activa === m.id
                                ? 'bg-zinc-900 dark:bg-zinc-100 text-white dark:text-zinc-900'
                                : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400 hover:bg-zinc-200 dark:hover:bg-zinc-700'">
                            {{ m.nombre.split(' ')[0] }}
                        </button>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-[10px] text-zinc-400 uppercase tracking-widest">Hoy</p>
                    <p class="text-sm font-semibold dark:text-zinc-300">{{ today }}</p>
                </div>
            </div>

            <!-- ── FILA 1: Capital + Portafolio ─────────────────────────────── -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 mb-4">

                <!-- Capital hero -->
                <div class="col-span-12 lg:col-span-5 bg-white dark:bg-zinc-900 p-7 rounded-2xl border border-zinc-100 dark:border-zinc-800">
                    <p class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest mb-1">Capital Total Colocado</p>
                    <p class="text-5xl font-light tracking-tighter dark:text-white mb-5">{{ money(stats?.capital?.colocado) }}</p>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <p class="text-[10px] text-zinc-400 uppercase font-bold">Recuperado</p>
                            <p class="text-base font-semibold text-emerald-600">{{ money(stats?.capital?.recuperado) }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] text-zinc-400 uppercase font-bold">Pendiente</p>
                            <p class="text-base font-semibold dark:text-zinc-200">{{ money(stats?.capital?.pendiente) }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] text-zinc-400 uppercase font-bold">Retorno</p>
                            <p class="text-base font-semibold text-emerald-600">{{ stats?.kpis?.tasa_recuperacion }}%</p>
                        </div>
                    </div>
                </div>

                <!-- Donut retorno -->
                <div class="col-span-6 lg:col-span-3 bg-white dark:bg-zinc-900 p-6 rounded-2xl border border-zinc-100 dark:border-zinc-800 flex flex-col items-center justify-center gap-3">
                    <p class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest">Retorno Capital</p>
                    <div class="w-32 h-32 relative">
                        <Doughnut :data="retornoData" :options="doughnutOpts" />
                        <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                            <p class="text-xl font-light dark:text-white">{{ stats?.kpis?.tasa_recuperacion }}%</p>
                        </div>
                    </div>
                    <div class="flex gap-3 text-xs text-zinc-500">
                        <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-emerald-500 inline-block"></span>Recup.</span>
                        <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-zinc-200 inline-block"></span>Pend.</span>
                    </div>
                </div>

                <!-- Portafolio por estatus -->
                <div class="col-span-6 lg:col-span-4 bg-zinc-950 dark:bg-zinc-800 p-6 rounded-2xl">
                    <p class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest mb-4">Portafolio Crediticio</p>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="bg-zinc-900 dark:bg-zinc-700 rounded-xl p-4">
                            <p class="text-3xl font-light text-white">{{ stats?.conteo?.activos }}</p>
                            <p class="text-[10px] text-zinc-400 uppercase mt-1">Activos</p>
                        </div>
                        <div class="bg-red-950/60 rounded-xl p-4">
                            <p class="text-3xl font-light text-red-400">{{ stats?.conteo?.morosos }}</p>
                            <p class="text-[10px] text-zinc-400 uppercase mt-1">Morosos</p>
                        </div>
                        <div class="bg-emerald-950/40 rounded-xl p-4">
                            <p class="text-3xl font-light text-emerald-400">{{ stats?.conteo?.liquidados }}</p>
                            <p class="text-[10px] text-zinc-400 uppercase mt-1">Liquidados</p>
                        </div>
                        <div class="bg-zinc-900 dark:bg-zinc-700 rounded-xl p-4">
                            <p class="text-3xl font-light text-white">{{ stats?.conteo?.acreditados }}</p>
                            <p class="text-[10px] text-zinc-400 uppercase mt-1">Acreditados</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── FILA 2: KPIs clave ─────────────────────────────────────── -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                <div class="bg-white dark:bg-zinc-900 p-5 rounded-2xl border border-zinc-100 dark:border-zinc-800">
                    <p class="text-[10px] text-zinc-400 uppercase font-bold tracking-widest">Ticket Promedio</p>
                    <p class="text-2xl font-light dark:text-white mt-1">{{ money(stats?.kpis?.ticket_promedio) }}</p>
                    <p class="text-[10px] text-zinc-400 mt-1">{{ stats?.conteo?.total }} créditos</p>
                </div>
                <div class="bg-white dark:bg-zinc-900 p-5 rounded-2xl border border-zinc-100 dark:border-zinc-800">
                    <p class="text-[10px] text-zinc-400 uppercase font-bold tracking-widest">Cartera Vencida</p>
                    <p class="text-2xl font-light mt-1" :class="(stats?.kpis?.cartera_vencida_pct || 0) > 10 ? 'text-red-500' : 'text-amber-500'">
                        {{ stats?.kpis?.cartera_vencida_pct }}%
                    </p>
                    <p class="text-[10px] text-zinc-400 mt-1">{{ money(stats?.kpis?.cartera_vencida_monto) }}</p>
                </div>
                <div class="bg-white dark:bg-zinc-900 p-5 rounded-2xl border border-zinc-100 dark:border-zinc-800">
                    <p class="text-[10px] text-zinc-400 uppercase font-bold tracking-widest">Mora Pendiente</p>
                    <p class="text-2xl font-light text-amber-500 mt-1">{{ money(stats?.intereses?.mora_pendiente) }}</p>
                    <p class="text-[10px] text-zinc-400 mt-1">{{ stats?.conteo?.vencidos }} cuotas vencidas</p>
                </div>
                <div class="bg-white dark:bg-zinc-900 p-5 rounded-2xl border border-zinc-100 dark:border-zinc-800">
                    <p class="text-[10px] text-zinc-400 uppercase font-bold tracking-widest">Utilidad Cobrada</p>
                    <p class="text-2xl font-light text-emerald-600 mt-1">{{ money((stats?.intereses?.ordinario_cobrado || 0) + (stats?.intereses?.mora_cobrada || 0)) }}</p>
                    <p class="text-[10px] text-zinc-400 mt-1">Rend. {{ stats?.kpis?.rendimiento_total }}% sobre colocado</p>
                </div>
            </div>

            <!-- ── FILA 3: Mapa + Género + Top Municipios ────────────────── -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 mb-4">

                <!-- Mapa de calor Yucatán -->
                <div class="col-span-12 lg:col-span-7 bg-white dark:bg-zinc-900 p-6 rounded-2xl border border-zinc-100 dark:border-zinc-800">
                    <div class="flex justify-between items-center mb-4">
                        <p class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest">Distribución Geográfica — Yucatán</p>
                        <span v-if="municipiosSinMapa.length" class="text-[10px] text-zinc-400">
                            +{{ municipiosSinMapa.length }} sin coordenadas
                        </span>
                    </div>

                    <div ref="mapContainer" class="relative w-full" style="padding-bottom: 70%">
                        <svg viewBox="0 0 400 280" class="absolute inset-0 w-full h-full" @mouseleave="hideTooltip">
                            <!-- Contorno del estado -->
                            <path :d="YUCATAN_PATH"
                                  :fill="isDark ? '#27272a' : '#f4f4f5'"
                                  :stroke="isDark ? '#3f3f46' : '#d4d4d8'"
                                  stroke-width="1.5" />

                            <!-- Leyenda geográfica -->
                            <text x="200" y="19" text-anchor="middle" font-size="7"
                                  :fill="isDark ? '#52525b' : '#a1a1aa'" font-family="sans-serif">
                                GOLFO DE MÉXICO
                            </text>
                            <text x="360" y="200" text-anchor="middle" font-size="6"
                                  :fill="isDark ? '#52525b' : '#a1a1aa'" font-family="sans-serif" transform="rotate(-90, 370, 170)">
                                QUINTANA ROO
                            </text>
                            <text x="20" y="200" text-anchor="middle" font-size="6"
                                  :fill="isDark ? '#52525b' : '#a1a1aa'" font-family="sans-serif" transform="rotate(90, 20, 170)">
                                CAMPECHE
                            </text>

                            <!-- Burbujas por municipio -->
                            <g v-for="b in mapBubbles" :key="b.municipio">
                                <circle
                                    :cx="b.x" :cy="b.y" :r="b.r"
                                    :fill="b.color" fill-opacity="0.82"
                                    stroke="white" stroke-width="0.8"
                                    class="cursor-pointer"
                                    style="transition: r 0.2s, fill-opacity 0.2s"
                                    @mousemove="showTooltip($event, b)"
                                    @mouseleave="hideTooltip"
                                />
                            </g>

                            <!-- Etiquetas para municipios con r > 11 -->
                            <text
                                v-for="b in mapBubbles.filter(b => b.r > 11)"
                                :key="'lbl-' + b.municipio"
                                :x="b.x" :y="b.y + b.r + 9"
                                text-anchor="middle" font-size="6"
                                :fill="isDark ? '#a1a1aa' : '#52525b'"
                                font-family="sans-serif"
                                pointer-events="none"
                            >{{ b.municipio.split(' ')[0] }}</text>
                        </svg>

                        <!-- Tooltip HTML overlay -->
                        <div v-if="tooltip"
                             class="absolute pointer-events-none z-10 bg-zinc-900 text-white text-xs rounded-xl px-3 py-2 shadow-xl whitespace-nowrap"
                             :style="{ left: tooltip.domX + 'px', top: tooltip.domY + 'px', transform: 'translate(10px, -50%)' }">
                            <p class="font-semibold">{{ tooltip.municipio }}</p>
                            <p class="text-zinc-400">{{ tooltip.total }} crédito{{ tooltip.total !== 1 ? 's' : '' }}</p>
                            <p class="text-emerald-400">{{ money(tooltip.monto) }}</p>
                        </div>
                    </div>

                    <!-- Escala de color -->
                    <div class="flex items-center gap-2 mt-3">
                        <span class="text-[10px] text-zinc-400">Menos</span>
                        <div class="flex-1 h-2 rounded-full"
                             style="background: linear-gradient(to right, rgb(59,130,246), rgb(245,158,11), rgb(239,68,68))">
                        </div>
                        <span class="text-[10px] text-zinc-400">Más</span>
                    </div>
                </div>

                <!-- Género + Top Municipios -->
                <div class="col-span-12 lg:col-span-5 flex flex-col gap-4">

                    <!-- Género -->
                    <div class="bg-white dark:bg-zinc-900 p-6 rounded-2xl border border-zinc-100 dark:border-zinc-800">
                        <p class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest mb-4">Créditos por Género</p>
                        <div class="flex items-center gap-5">
                            <div class="w-24 h-24 relative flex-shrink-0">
                                <Doughnut :data="generoData" :options="doughnutOpts" />
                            </div>
                            <div class="flex flex-col gap-3 flex-1">
                                <div class="flex items-center gap-2">
                                    <span class="w-2.5 h-2.5 rounded-full bg-zinc-900 dark:bg-zinc-100 flex-shrink-0"></span>
                                    <div>
                                        <p class="text-[10px] text-zinc-500 uppercase">Hombres</p>
                                        <p class="text-xl font-semibold dark:text-white">{{ stats?.genero?.H }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="w-2.5 h-2.5 rounded-full bg-zinc-400 flex-shrink-0"></span>
                                    <div>
                                        <p class="text-[10px] text-zinc-500 uppercase">Mujeres</p>
                                        <p class="text-xl font-semibold dark:text-white">{{ stats?.genero?.M }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="text-right border-l border-zinc-100 dark:border-zinc-800 pl-4">
                                <p class="text-[10px] text-zinc-400 uppercase">Paridad</p>
                                <p class="text-2xl font-light dark:text-white">{{ pctMujeres }}<span class="text-sm text-zinc-400">%M</span></p>
                            </div>
                        </div>
                    </div>

                    <!-- Top Municipios -->
                    <div class="bg-white dark:bg-zinc-900 p-6 rounded-2xl border border-zinc-100 dark:border-zinc-800 flex-1">
                        <p class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest mb-4">Top Municipios</p>
                        <div class="space-y-2.5">
                            <div v-for="m in topMunicipios" :key="m.municipio" class="flex items-center gap-3">
                                <p class="text-xs text-zinc-600 dark:text-zinc-400 w-24 truncate flex-shrink-0">{{ m.municipio }}</p>
                                <div class="flex-1 bg-zinc-100 dark:bg-zinc-800 rounded-full h-1.5">
                                    <div class="bg-zinc-900 dark:bg-zinc-200 h-1.5 rounded-full transition-all duration-500"
                                         :style="{ width: (m.total / maxMunicipio * 100) + '%' }"></div>
                                </div>
                                <p class="text-xs font-bold dark:text-white w-6 text-right flex-shrink-0">{{ m.total }}</p>
                            </div>
                            <p v-if="!topMunicipios.length" class="text-xs text-zinc-400">Sin datos</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── FILA 4: Evolución mensual + Modalidades ──────────────── -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 mb-4">

                <!-- Evolución mensual -->
                <div class="col-span-12 lg:col-span-7 bg-white dark:bg-zinc-900 p-6 rounded-2xl border border-zinc-100 dark:border-zinc-800">
                    <div class="flex justify-between items-center mb-4">
                        <p class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest">Cobro Mensual — Últimos 6 Meses</p>
                        <span class="text-xs text-zinc-400">
                            {{ stats?.conteo?.pagos_mes }} pagos este mes
                        </span>
                    </div>
                    <div class="h-48 sm:h-52 lg:h-56">
                        <Line :data="evolucionData" :options="lineOpts" />
                    </div>
                </div>

                <!-- Modalidades -->
                <div class="col-span-12 lg:col-span-5 bg-white dark:bg-zinc-900 p-6 rounded-2xl border border-zinc-100 dark:border-zinc-800">
                    <p class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest mb-4">Por Modalidad</p>
                    <div class="flex items-start gap-5">
                        <div class="w-32 h-32 relative flex-shrink-0">
                            <Doughnut :data="modalidadData" :options="doughnutOpts" />
                        </div>
                        <div class="flex flex-col gap-3 flex-1">
                            <div v-for="(m, i) in (stats?.por_modalidad || [])" :key="m.nombre" class="flex items-start gap-2">
                                <span class="w-2 h-2 rounded-full mt-1.5 flex-shrink-0"
                                      :class="['bg-zinc-900 dark:bg-zinc-100', 'bg-zinc-500', 'bg-zinc-300', 'bg-zinc-200'][i]">
                                </span>
                                <div>
                                    <p class="text-[10px] text-zinc-500 leading-tight break-words">{{ m.nombre }}</p>
                                    <p class="text-sm font-semibold dark:text-white">
                                        {{ m.total }} <span class="text-xs font-normal text-zinc-400">cred.</span>
                                    </p>
                                    <p class="text-[10px] text-zinc-400">{{ money(m.monto) }}</p>
                                </div>
                            </div>
                            <p v-if="!stats?.por_modalidad?.length" class="text-xs text-zinc-400">Sin datos</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── FILA 5: Últimos pagos ──────────────────────────────────── -->
            <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-100 dark:border-zinc-800 overflow-hidden">
                <div class="px-6 py-4 border-b border-zinc-100 dark:border-zinc-800 flex justify-between items-center">
                    <p class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest">Últimos Pagos Registrados</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                            <tr>
                                <th class="px-5 py-3 text-[10px] font-bold text-zinc-400 uppercase text-left">Folio</th>
                                <th class="px-5 py-3 text-[10px] font-bold text-zinc-400 uppercase text-left">Acreditado</th>
                                <th class="px-5 py-3 text-[10px] font-bold text-zinc-400 uppercase text-left hidden md:table-cell">Forma Pago</th>
                                <th class="px-5 py-3 text-[10px] font-bold text-zinc-400 uppercase text-right">Monto</th>
                                <th class="px-5 py-3 text-[10px] font-bold text-zinc-400 uppercase text-right hidden lg:table-cell">Fecha</th>
                                <th class="px-5 py-3 text-[10px] font-bold text-zinc-400 uppercase text-right hidden lg:table-cell">Cajero</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="p in (stats?.pagos_recientes || [])" :key="p.folio"
                                class="border-t border-zinc-50 dark:border-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-800/30 transition-colors">
                                <td class="px-5 py-3 font-mono text-xs text-zinc-400">{{ p.folio }}</td>
                                <td class="px-5 py-3 font-medium dark:text-white">{{ p.nombre_completo }}</td>
                                <td class="px-5 py-3 text-zinc-500 hidden md:table-cell">{{ p.forma_pago }}</td>
                                <td class="px-5 py-3 text-right font-semibold dark:text-white">{{ money(p.monto_recibido) }}</td>
                                <td class="px-5 py-3 text-right text-zinc-500 hidden lg:table-cell">
                                    {{ new Date(p.fecha_pago + 'T12:00:00').toLocaleDateString('es-MX') }}
                                </td>
                                <td class="px-5 py-3 text-right text-zinc-500 hidden lg:table-cell">{{ p.cajero }}</td>
                            </tr>
                            <tr v-if="!stats?.pagos_recientes?.length">
                                <td colspan="6" class="px-5 py-10 text-center text-zinc-400">Sin pagos registrados aún</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </AppLayout>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@200;300;400;600;700&display=swap');
div { font-family: 'Outfit', sans-serif; }
</style>
