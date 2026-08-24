<script setup>
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import { ref, computed, watch } from 'vue';

const props = defineProps({ modalidades: Array });

const monto    = ref('');
const plazo    = ref('');
const modId    = ref('');
const tabla    = ref([]);
const generado = ref(false);

const modSeleccionada = computed(() => props.modalidades?.find(m => m.id === parseInt(modId.value)));

function getTasa(nombre) {
    if (!nombre) return 0;
    if (nombre.includes('Emprendedores')) return 7.0;
    if (nombre.includes('Sustentable') || nombre.includes('Fortalecimiento')) return 5.0;
    return 0;
}

const tasa = computed(() => getTasa(modSeleccionada.value?.nombre));
const prorroga = computed(() => modSeleccionada.value?.nombre?.includes('Sustentable') ? 3 : 0);

const money = (v) => new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN', minimumFractionDigits: 2 }).format(v || 0);

function simular() {
    const m = parseFloat(monto.value);
    const p = parseInt(plazo.value);
    if (!m || !p || m <= 0 || p <= 0) return;

    const tm = (tasa.value / 100) / 12;
    const cuotaFija = tm > 0 ? m * (tm / (1 - Math.pow(1 + tm, -p))) : m / p;
    let saldo = m;
    const rows = [];
    const hoy = new Date();

    for (let i = 1; i <= p; i++) {
        const int = Math.round(saldo * tm * 100) / 100;
        const cap = i === p ? Math.round(saldo * 100) / 100 : Math.round((cuotaFija - int) * 100) / 100;
        const total = Math.round((cap + int) * 100) / 100;
        const venc = new Date(hoy);
        venc.setMonth(venc.getMonth() + i + prorroga.value);
        rows.push({
            n: i,
            fecha: venc.toLocaleDateString('es-MX', { year: 'numeric', month: '2-digit', day: '2-digit' }),
            saldo: Math.round(saldo * 100) / 100,
            capital: cap,
            interes: int,
            cuota: total,
        });
        saldo = Math.round((saldo - cap) * 100) / 100;
    }

    tabla.value = rows;
    generado.value = true;
}

function limpiar() {
    monto.value = '';
    plazo.value = '';
    modId.value = '';
    tabla.value = [];
    generado.value = false;
}

const totalCapital  = computed(() => tabla.value.reduce((s, r) => s + r.capital, 0));
const totalInteres  = computed(() => tabla.value.reduce((s, r) => s + r.interes, 0));
const totalCuotas   = computed(() => tabla.value.reduce((s, r) => s + r.cuota, 0));
</script>

<template>
    <AppLayout title="Simulador de Tabla de Amortización">
        <div class="p-6 max-w-5xl mx-auto">

            <div class="mb-6">
                <p class="text-[10px] font-bold tracking-widest text-zinc-400 uppercase">IYEM · CREA</p>
                <h1 class="text-2xl font-medium dark:text-white">Simulador de Tabla de Amortización</h1>
                <p class="text-sm text-zinc-400 mt-1">Genera una tabla de pagos hipotética. No se guarda ningún dato.</p>
            </div>

            <!-- Formulario -->
            <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-100 dark:border-zinc-800 p-6 mb-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                    <!-- Monto -->
                    <div>
                        <label class="text-[11px] font-bold text-zinc-500 uppercase tracking-widest block mb-1.5">Monto del Crédito (MXN)</label>
                        <input v-model="monto" type="number" min="1" step="500" placeholder="Ej. 25000"
                            class="w-full px-4 py-2.5 rounded-xl border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800 text-sm dark:text-white focus:outline-none focus:ring-2 focus:ring-zinc-400" />
                    </div>

                    <!-- Modalidad -->
                    <div>
                        <label class="text-[11px] font-bold text-zinc-500 uppercase tracking-widest block mb-1.5">Modalidad</label>
                        <select v-model="modId"
                            class="w-full px-4 py-2.5 rounded-xl border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800 text-sm dark:text-white focus:outline-none focus:ring-2 focus:ring-zinc-400">
                            <option value="">Seleccionar...</option>
                            <option v-for="m in modalidades" :key="m.id" :value="m.id">{{ m.nombre }}</option>
                        </select>
                        <p v-if="modId" class="text-[10px] text-zinc-400 mt-1">
                            Tasa ordinaria: <strong>{{ tasa }}%</strong> anual · Mora: <strong>{{ Math.round(tasa * 2.5 * 100) / 100 }}%</strong>
                            <span v-if="prorroga > 0"> · {{ prorroga }} meses de prórroga</span>
                        </p>
                    </div>

                    <!-- Plazo -->
                    <div>
                        <label class="text-[11px] font-bold text-zinc-500 uppercase tracking-widest block mb-1.5">Plazo (meses)</label>
                        <input v-model="plazo" type="number" min="1" max="60" placeholder="Ej. 12"
                            class="w-full px-4 py-2.5 rounded-xl border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800 text-sm dark:text-white focus:outline-none focus:ring-2 focus:ring-zinc-400" />
                    </div>
                </div>

                <div class="flex gap-3 mt-5">
                    <button @click="simular" :disabled="!monto || !plazo || !modId"
                        class="px-6 py-2.5 bg-zinc-900 dark:bg-zinc-100 text-white dark:text-zinc-900 rounded-xl text-sm font-semibold transition hover:opacity-80 disabled:opacity-40 disabled:cursor-not-allowed">
                        Generar Tabla
                    </button>
                    <button v-if="generado" @click="limpiar"
                        class="px-4 py-2.5 rounded-xl text-sm font-medium text-zinc-500 dark:text-zinc-400 bg-zinc-100 dark:bg-zinc-800 hover:bg-zinc-200 dark:hover:bg-zinc-700 transition">
                        Limpiar
                    </button>
                </div>
            </div>

            <!-- Resumen -->
            <div v-if="generado" class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-100 dark:border-zinc-800 p-5">
                    <p class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest">Capital</p>
                    <p class="text-xl font-light dark:text-white mt-1">{{ money(parseFloat(monto)) }}</p>
                </div>
                <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-100 dark:border-zinc-800 p-5">
                    <p class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest">Total Interés</p>
                    <p class="text-xl font-light text-amber-500 mt-1">{{ money(totalInteres) }}</p>
                </div>
                <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-100 dark:border-zinc-800 p-5">
                    <p class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest">Total a Pagar</p>
                    <p class="text-xl font-light text-emerald-600 mt-1">{{ money(totalCuotas) }}</p>
                </div>
                <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-100 dark:border-zinc-800 p-5">
                    <p class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest">Cuota Mensual</p>
                    <p class="text-xl font-light dark:text-white mt-1">{{ money(tabla[0]?.cuota) }}</p>
                    <p class="text-[10px] text-zinc-400">aprox.</p>
                </div>
            </div>

            <!-- Tabla -->
            <div v-if="generado" class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-100 dark:border-zinc-800 overflow-hidden">
                <div class="px-6 py-4 border-b border-zinc-100 dark:border-zinc-800 flex justify-between items-center">
                    <p class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest">
                        Tabla de Amortización · {{ modSeleccionada?.nombre }} · {{ plazo }} meses
                    </p>
                    <span v-if="prorroga > 0" class="text-[10px] bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400 px-2 py-0.5 rounded-full font-bold">
                        Incluye {{ prorroga }} meses de prórroga
                    </span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[500px] text-sm">
                        <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                            <tr>
                                <th class="px-4 py-3 text-[10px] font-bold text-zinc-400 uppercase text-center w-10">#</th>
                                <th class="px-4 py-3 text-[10px] font-bold text-zinc-400 uppercase text-left">Vencimiento</th>
                                <th class="px-4 py-3 text-[10px] font-bold text-zinc-400 uppercase text-right">Saldo Inicial</th>
                                <th class="px-4 py-3 text-[10px] font-bold text-zinc-400 uppercase text-right">Capital</th>
                                <th class="px-4 py-3 text-[10px] font-bold text-zinc-400 uppercase text-right">Interés</th>
                                <th class="px-4 py-3 text-[10px] font-bold text-zinc-400 uppercase text-right">Cuota Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="r in tabla" :key="r.n"
                                class="border-t border-zinc-50 dark:border-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-800/30 transition-colors">
                                <td class="px-4 py-2.5 text-center text-zinc-400 text-xs">{{ r.n }}</td>
                                <td class="px-4 py-2.5 text-zinc-600 dark:text-zinc-300 text-xs">{{ r.fecha }}</td>
                                <td class="px-4 py-2.5 text-right text-zinc-500 text-xs font-mono">{{ money(r.saldo) }}</td>
                                <td class="px-4 py-2.5 text-right font-semibold dark:text-white text-xs font-mono">{{ money(r.capital) }}</td>
                                <td class="px-4 py-2.5 text-right text-amber-600 dark:text-amber-400 text-xs font-mono">{{ money(r.interes) }}</td>
                                <td class="px-4 py-2.5 text-right font-bold text-emerald-600 dark:text-emerald-400 text-xs font-mono">{{ money(r.cuota) }}</td>
                            </tr>
                        </tbody>
                        <tfoot class="bg-zinc-50 dark:bg-zinc-800/50 border-t-2 border-zinc-200 dark:border-zinc-700">
                            <tr>
                                <td colspan="3" class="px-4 py-3 text-[10px] font-bold text-zinc-400 uppercase">Totales</td>
                                <td class="px-4 py-3 text-right font-bold dark:text-white text-xs font-mono">{{ money(totalCapital) }}</td>
                                <td class="px-4 py-3 text-right font-bold text-amber-600 dark:text-amber-400 text-xs font-mono">{{ money(totalInteres) }}</td>
                                <td class="px-4 py-3 text-right font-bold text-emerald-600 dark:text-emerald-400 text-xs font-mono">{{ money(totalCuotas) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <div class="px-6 py-3 text-[10px] text-zinc-400 bg-zinc-50 dark:bg-zinc-800/30">
                    * Simulación con fines ilustrativos. Las fechas exactas pueden variar según la fecha oficial de entrega.
                    Tasa moratoria aplicable: {{ Math.round(tasa * 2.5 * 100) / 100 }}% anual · 5 días naturales de gracia (RO Cláusula Séptima).
                </div>
            </div>

        </div>
    </AppLayout>
</template>
