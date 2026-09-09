<script setup>
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import { ref, computed, watch } from 'vue';
import { useForm, Head } from '@inertiajs/vue3';

const props = defineProps({
    credito: Object,
    acreditado: Object,
    cuotas_pendientes: Array,
    formas_pago: Array,
    tasa_mora: Number // Recibida desde el controlador
});

const form = useForm({
    monto_recibido: 0,
    fecha_pago: new Date().toISOString().substr(0, 10),
    forma_pago: 'Efectivo',
    tipo_abono: '',
    referencia: '',
    observaciones: ''
});

const displayMonto     = ref('');
const cuotaSeleccionada = ref(null);

// Formateador de moneda MXN
const money = (val) => new Intl.NumberFormat('es-MX', {
    style: 'currency',
    currency: 'MXN'
}).format(val || 0);

const formatInput = (e) => {
    let val = e.target.value.replace(/[^0-9.]/g, '');
    displayMonto.value = val;
    form.monto_recibido = parseFloat(val) || 0;
    cuotaSeleccionada.value = null; // deselect on manual input
};

// Clic en una fila de cuota → pre-llenado del monto con total_a_pagar
function seleccionarCuota(c) {
    cuotaSeleccionada.value = c.numero_cuota;
    const monto = parseFloat(c.total_a_pagar) || 0;
    displayMonto.value = monto.toFixed(2);
    form.monto_recibido = monto;
}

// --- LÓGICA DE SIMULACIÓN MEJORADA ---
const simulacion = computed(() => {
    // Aseguramos que fondo sea un número real
    let fondo = parseFloat(form.monto_recibido) || 0;
    const fechaValor = new Date(form.fecha_pago + 'T00:00:00');
    // Usamos props.tasa_mora o props.credito.tasa_interes_moratorio según disponibilidad
    const tasaAnual = props.tasa_mora || props.credito?.tasa_interes_moratorio || 0;
    const tasaDiaria = (tasaAnual / 100) / 360;
    
    let resumen = { 
        moraTotal: 0, 
        ordinarioTotal: 0, 
        capitalTotal: 0, 
        cuotasAfectadas: [],
        capitalPendienteRestante: props.cuotas_pendientes.reduce((acc, c) => acc + (parseFloat(c.capital_pendiente) || 0), 0)
    };

    // Un pago se considera liquidación si cubre el capital pendiente (con margen de error de 1 centavo)
    const esLiquidacionTotal = fondo >= (resumen.capitalPendienteRestante - 0.01);

    // --- Reglas de sobrepago (espejo del backend, Ticket 4.3) ---
    const round2 = (v) => Math.round((v + Number.EPSILON) * 100) / 100;
    const montoRecibido = parseFloat(form.monto_recibido) || 0;
    const cuotas = props.cuotas_pendientes || [];

    const moraReal  = cuotas.reduce((acc, c) => acc + (parseFloat(c.mora_al_dia) || 0), 0);
    const tieneMora = moraReal > 0.01;
    const maximo    = round2(resumen.capitalPendienteRestante + moraReal);
    const exceso    = montoRecibido > maximo + 0.01;

    const corriente = cuotas[0];
    const siguiente = cuotas[1];
    const sobranteCorriente = corriente
        ? round2(montoRecibido - (parseFloat(corriente.pago_restante) || 0))
        : 0;
    const siguienteCosto = siguiente ? round2(parseFloat(siguiente.pago_restante) || 0) : 0;

    const hayEscenario = !!corriente && !!siguiente
        && !tieneMora && !esLiquidacionTotal
        && sobranteCorriente >= siguienteCosto - 0.01;

    const sobranteParcial = !!corriente && !!siguiente
        && !tieneMora && !esLiquidacionTotal
        && sobranteCorriente > 0.01 && sobranteCorriente < siguienteCosto - 0.01;

    // Reducir cuota/plazo: igual que el backend, la cascada se detiene en la cuota
    // corriente y el sobrante queda disponible como abono a capital.
    const modoAbono = hayEscenario
        && (form.tipo_abono === 'Reducir Cuota' || form.tipo_abono === 'Reducir Plazo');
    const numCorriente = corriente ? corriente.numero_cuota : null;

    for (const c of props.cuotas_pendientes) {
        // Si no hay dinero y no es liquidación total (donde el interés se vuelve 0), saltamos
        if (fondo <= 0 && !esLiquidacionTotal) break;

        const vencimiento = new Date(c.fecha_vencimiento + 'T00:00:00');

        // 1. Cálculo de Mora (sobre saldo insoluto vencido, RO Cláusula Séptima)
        let moraFila = 0;
        if (fechaValor > vencimiento) {
            const dias = Math.floor((fechaValor - vencimiento) / (1000 * 60 * 60 * 24));
            if (dias > 5) {
                const saldoVencido = Math.max(0, parseFloat(c.saldo_vencido) || 0);
                moraFila = Math.round(saldoVencido * tasaDiaria * dias * 100) / 100;
            }
        }

        // 2. Interés (Condonar si es liquidación total y la cuota no ha vencido)
        let interesFila = parseFloat(c.interes_pendiente) || 0;
        if (esLiquidacionTotal && vencimiento > fechaValor) {
            interesFila = 0;
        }

        // 3. Aplicación en Cascada (Consumiendo el "fondo")
        let pMora = Math.min(fondo, moraFila);
        fondo = Math.max(0, fondo - pMora);

        let pOrd = Math.min(fondo, interesFila);
        fondo = Math.max(0, fondo - pOrd);

        let pCap = Math.min(fondo, parseFloat(c.capital_pendiente) || 0);
        fondo = Math.max(0, fondo - pCap);

        // Registrar solo si hubo movimiento o si la cuota fue alterada por la liquidación
        if (pMora > 0 || pOrd > 0 || pCap > 0 || (esLiquidacionTotal && vencimiento > fechaValor)) {
            resumen.cuotasAfectadas.push({
                n: c.numero_cuota,
                mora: pMora,
                ordinario: pOrd,
                capital: pCap,
                liquidada: (pOrd + pCap) >= (interesFila + (parseFloat(c.capital_pendiente) || 0))
            });
            resumen.moraTotal += pMora;
            resumen.ordinarioTotal += pOrd;
            resumen.capitalTotal += pCap;
        }

        // En Reducir cuota/plazo no se pre-pagan cuotas siguientes.
        if (modoAbono && numCorriente !== null && c.numero_cuota === numCorriente) break;
    }

    // Preview de las opciones B/C (orientativo; el backend es la fuente de verdad)
    const preview = { reducirCuota: null, plazoFull: null, plazoFinal: null };
    if (hayEscenario) {
        const tasaOrd = (parseFloat(props.credito?.tasa_interes_ordinario) || 0) / 100 / 12;
        const C       = round2(parseFloat(corriente.pago_restante) || 0);
        const capitalRestante = resumen.capitalPendienteRestante - (parseFloat(corriente.capital_pendiente) || 0);
        const S       = round2(capitalRestante - sobranteCorriente);
        const n       = Math.max(1, cuotas.length - 1);

        if (S > 0 && C > 0) {
            preview.reducirCuota = round2(
                tasaOrd > 0
                    ? S * (tasaOrd / (1 - Math.pow(1 + tasaOrd, -n)))
                    : S / n
            );

            if (tasaOrd > 0 && (1 - S * tasaOrd / C) > 1e-9) {
                const meses = -Math.log(1 - S * tasaOrd / C) / Math.log(1 + tasaOrd);
                preview.plazoFull = Math.floor(meses + 1e-9);
                const rem = S * Math.pow(1 + tasaOrd, preview.plazoFull)
                    - C * ((Math.pow(1 + tasaOrd, preview.plazoFull) - 1) / tasaOrd);
                preview.plazoFinal = round2(rem * (1 + tasaOrd));
            } else {
                preview.plazoFull  = Math.floor(S / C + 1e-9);
                preview.plazoFinal = round2(S - preview.plazoFull * C);
            }
        }
    }

    // En Reducir cuota/plazo el sobrante que quedó (fondo) va a capital.
    const sobranteACapital = modoAbono ? Math.max(0, round2(fondo)) : 0;

    // El desglose ya contiene solo la cuota corriente en modo abono (el bucle se detiene ahí).
    const desgloseItems = resumen.cuotasAfectadas;

    // Totales: en modo abono el "sobrante a capital" se suma al capital aplicado.
    const displayCapital  = modoAbono ? round2(resumen.capitalTotal + sobranteACapital) : resumen.capitalTotal;
    const displayOrdinario = resumen.ordinarioTotal;
    const displayMora     = resumen.moraTotal;
    const displayBase     = modoAbono ? round2(resumen.capitalTotal + resumen.ordinarioTotal) : round2(displayCapital + displayOrdinario);

    return { 
        ...resumen, 
        cambio: fondo,
        moraReal, tieneMora, maximo, exceso,
        hayEscenario, sobranteParcial, sobranteCorriente,
        preview,
        modoAbono, sobranteACapital,
        desgloseItems,
        displayCapital, displayOrdinario, displayMora, displayBase,
        esLiquidacion: esLiquidacionTotal 
    };
});

// Si el sobrepago deja de calificar como escenario (cambió el monto/cuota), se limpia
// la opción elegida para no enviar un tipo_abono obsoleto al backend.
watch(() => simulacion.value.hayEscenario, (activo) => {
    if (!activo) form.tipo_abono = '';
});

const submit = () => {
    if (form.monto_recibido <= 0) return;
    form.post(route('pagos.store', props.credito.id));
};
</script>

<template>
    <AppLayout>
        <Head title="Recepción de Pago" />

        <div class="min-h-screen bg-slate-50 dark:bg-[#0a0a0a] p-4 lg:p-8 transition-colors duration-500">
            <div class="max-w-[1400px] mx-auto grid grid-cols-1 lg:grid-cols-12 gap-8">
                
                <div class="col-span-12 lg:col-span-8 space-y-6">
                    <div class="flex flex-col gap-1">
                        <h2 class="text-[10px] font-black uppercase tracking-[0.3em] text-red-600">Operaciones / Caja</h2>
                        <h1 class="text-4xl font-black text-slate-900 dark:text-white tracking-tighter">
                            Registrar <span class="text-red-600 underline decoration-2 underline-offset-8">Pago</span>
                        </h1>
                        <p class="text-slate-500 dark:text-zinc-400 font-medium mt-2">Acreditado: {{ acreditado.nombre_completo }}</p>
                    </div>

                    <div class="bg-white dark:bg-zinc-900 rounded-[2.5rem] p-8 shadow-2xl border border-slate-200 dark:border-zinc-800">
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-10">
                            <div class="md:col-span-7 space-y-4">
                                <label class="text-[10px] font-bold uppercase text-slate-400 tracking-widest">Importe Recibido (MXN)</label>
                                <div class="relative group">
                                    <span class="absolute left-0 top-0 text-3xl sm:text-4xl lg:text-5xl font-light text-slate-300 dark:text-zinc-700">$</span>
                                    <input
                                        type="text"
                                        :value="displayMonto"
                                        @input="formatInput"
                                        placeholder="0.00"
                                        class="w-full pl-8 text-4xl sm:text-6xl lg:text-7xl font-black bg-transparent border-none focus:ring-0 text-slate-900 dark:text-white"
                                    />
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="h-2 w-2 rounded-full bg-red-600 animate-pulse"></div>
                                    <span class="text-xs font-bold text-slate-400 uppercase tracking-tighter">{{ money(form.monto_recibido) }}</span>
                                </div>
                            </div>

                            <div class="md:col-span-5 space-y-4 border-l border-slate-100 dark:border-zinc-800 pl-0 md:pl-10">
                                <div>
                                    <label class="text-[10px] font-bold uppercase text-slate-400">Fecha Valor</label>
                                    <input v-model="form.fecha_pago" type="date" class="w-full mt-1 bg-slate-50 dark:bg-zinc-800 border-none rounded-2xl font-bold dark:text-white" />
                                </div>
                                <div>
                                    <label class="text-[10px] font-bold uppercase text-slate-400">Método de Pago</label>
                                    <select v-model="form.forma_pago" class="w-full mt-1 bg-slate-50 dark:bg-zinc-800 border-none rounded-2xl font-bold dark:text-white">
                                        <option v-for="f in formas_pago" :key="f" :value="f">{{ f }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="mt-8 flex flex-col gap-3">
                            <div v-if="simulacion.exceso" class="bg-red-600 text-white px-6 py-3 rounded-2xl shadow-lg shadow-red-600/20">
                                <p class="text-xs font-black uppercase tracking-widest">
                                    El importe excede el máximo a pagar ({{ money(simulacion.maximo) }}). Verifique la cantidad.
                                </p>
                            </div>

                            <div v-if="simulacion.tieneMora" class="bg-orange-50 dark:bg-orange-900/20 border border-orange-300 dark:border-orange-700 rounded-2xl px-4 py-3">
                                <p class="text-xs font-bold text-orange-700 dark:text-orange-400">
                                    El crédito tiene mora. Primero debe cubrir las cuotas vencidas para ponerse al día.
                                </p>
                            </div>

                            <div v-if="simulacion.esLiquidacion && !simulacion.exceso" class="bg-green-600 text-white px-6 py-3 rounded-2xl flex items-center gap-3 animate-bounce shadow-lg shadow-green-600/20">
                                <span class="text-xs font-black uppercase tracking-widest">✓ Liquidación Total — Interés futuro condonado (RO)</span>
                            </div>

                            <div v-if="simulacion.hayEscenario" class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-2xl p-4">
                                <p class="text-xs font-black uppercase text-amber-700 dark:text-amber-400 tracking-widest mb-1">
                                    Sobrepago de una cuota o más detectado
                                </p>
                                <p class="text-[11px] text-amber-600 dark:text-amber-500 mb-3">
                                    Después de cubrir la cuota corriente hay un sobrante de
                                    <strong>{{ money(simulacion.sobranteCorriente) }}</strong>. Seleccione cómo aplicarlo:
                                </p>
                                <div class="bg-white dark:bg-zinc-800 p-1.5 rounded-xl flex items-center border border-amber-200 dark:border-amber-800 w-fit flex-wrap gap-1">
                                    <button @click="form.tipo_abono = 'Adelantado'"
                                        :class="form.tipo_abono === 'Adelantado' ? 'bg-red-600 text-white' : 'text-slate-400'"
                                        class="px-4 py-2 rounded-lg text-[10px] font-bold uppercase transition-all">
                                        Adelantado
                                    </button>
                                    <button @click="form.tipo_abono = 'Reducir Cuota'"
                                        :class="form.tipo_abono === 'Reducir Cuota' ? 'bg-red-600 text-white' : 'text-slate-400'"
                                        class="px-4 py-2 rounded-lg text-[10px] font-bold uppercase transition-all">
                                        Reducir Cuota
                                    </button>
                                    <button @click="form.tipo_abono = 'Reducir Plazo'"
                                        :class="form.tipo_abono === 'Reducir Plazo' ? 'bg-red-600 text-white' : 'text-slate-400'"
                                        class="px-4 py-2 rounded-lg text-[10px] font-bold uppercase transition-all">
                                        Reducir Plazo
                                    </button>
                                </div>
                                <div v-if="form.tipo_abono === 'Reducir Cuota' && simulacion.preview.reducirCuota" class="mt-3 text-[11px] text-amber-700 dark:text-amber-400">
                                    La nueva cuota sería de aproximadamente <strong>{{ money(simulacion.preview.reducirCuota) }}</strong>.
                                </div>
                                <div v-else-if="form.tipo_abono === 'Reducir Plazo' && simulacion.preview.plazoFull !== null" class="mt-3 text-[11px] text-amber-700 dark:text-amber-400">
                                    Te quedarían <strong>{{ simulacion.preview.plazoFull }}</strong> cuotas completas
                                    <template v-if="simulacion.preview.plazoFinal">más una final de {{ money(simulacion.preview.plazoFinal) }}</template>.
                                </div>
                                <p class="text-[10px] text-amber-500 mt-2">
                                    <strong>Adelantado:</strong> cubre cuotas siguientes, no cambia tu cuota. |
                                    <strong>Reducir Cuota:</strong> mismo plazo, cuota más baja. |
                                    <strong>Reducir Plazo:</strong> misma cuota, terminas antes.
                                </p>
                                <p v-if="!form.tipo_abono" class="mt-2 text-xs font-black text-red-600">
                                    Seleccione una opción para el sobrante antes de confirmar.
                                </p>
                            </div>

                            <div v-else-if="simulacion.sobranteParcial" class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-2xl px-4 py-3">
                                <p class="text-[11px] text-blue-700 dark:text-blue-400">
                                    El sobrante ({{ money(simulacion.sobranteCorriente) }}) se aplicará automáticamente a la siguiente cuota.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Instrucción de selección -->
                    <p class="text-xs text-slate-400 -mt-2 mb-1">
                        👆 Haz clic en una fila para pre-llenar el importe con el monto exigible de esa cuota.
                    </p>

                    <div class="bg-white dark:bg-zinc-900 rounded-[2.5rem] shadow-sm border border-slate-200 dark:border-zinc-800 overflow-hidden">
                        <div class="overflow-x-auto">
                        <table class="w-full min-w-[480px] text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50 dark:bg-zinc-800/50 text-[10px] font-black uppercase text-slate-400 tracking-widest">
                                    <th class="p-6">No.</th>
                                    <th class="p-6">Vencimiento</th>
                                    <th class="p-6">Capital + Int.</th>
                                    <th class="p-6">Mora Est.</th>
                                    <th class="p-6 text-right">Exigible</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-zinc-800">
                                <tr v-for="c in cuotas_pendientes" :key="c.id"
                                    class="cursor-pointer hover:bg-red-50/40 dark:hover:bg-red-900/10 transition-colors"
                                    :class="[
                                        simulacion.desgloseItems.find(s => s.n === c.numero_cuota) ? 'bg-red-50/50 dark:bg-red-900/10' : '',
                                        cuotaSeleccionada === c.numero_cuota ? 'ring-2 ring-inset ring-red-500' : ''
                                    ]"
                                    @click="seleccionarCuota(c)">
                                    <td class="p-6 font-bold text-xs dark:text-white">{{ c.numero_cuota }}</td>
                                    <td class="p-6 text-xs text-slate-500">{{ c.fecha_vencimiento }}</td>
                                    <td class="p-6 text-xs font-bold">{{ money(c.pago_restante) }}</td>
                                    <td class="p-6 text-xs font-black text-orange-500">
                                        {{ money(simulacion.desgloseItems.find(s => s.n === c.numero_cuota)?.mora || c.mora_al_dia || 0) }}
                                    </td>
                                    <td class="p-6 text-right font-black text-sm dark:text-white">
                                        {{ money(c.total_a_pagar) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        </div>
                    </div>
                </div>

                <div class="col-span-12 lg:col-span-4">
                    <div class="sticky top-8 space-y-6">
                        <div class="bg-zinc-900 p-10 rounded-[3rem] shadow-2xl text-white relative overflow-hidden">
                            <div class="absolute -top-10 -right-10 w-32 h-32 bg-red-600/20 rounded-full blur-3xl"></div>
                            <h2 class="text-[10px] font-black uppercase tracking-[0.3em] text-red-500 mb-10">Desglose de Aplicación</h2>
                            
                            <div class="space-y-6 max-h-[400px] overflow-y-auto pr-2 custom-scrollbar">
                                <div v-for="item in simulacion.desgloseItems" :key="item.n" class="border-b border-zinc-800 pb-4">
                                    <div class="flex justify-between items-center mb-2">
                                        <span class="text-xs font-bold">Cuota #{{ item.n }}</span>
                                        <span v-if="item.liquidada" class="text-[8px] bg-red-600 px-2 py-0.5 rounded-full uppercase font-black">Liquidada</span>
                                    </div>
                                    <div class="flex flex-wrap gap-x-4 gap-y-1 opacity-50 text-[10px] italic">
                                        <span>Cap: {{ money(item.capital) }}</span>
                                        <span>Int: {{ money(item.ordinario) }}</span>
                                        <span class="text-red-400 font-bold">Mor: {{ money(item.mora) }}</span>
                                    </div>
                                </div>
                                <div v-if="simulacion.modoAbono && simulacion.sobranteACapital > 0" class="border-b border-zinc-800 pb-4 pt-2">
                                    <div class="flex justify-between items-center mb-2">
                                        <span class="text-xs font-bold text-amber-400">Abono a capital</span>
                                        <span class="text-[8px] bg-amber-500/20 px-2 py-0.5 rounded-full uppercase font-black text-amber-300">Sobrante</span>
                                    </div>
                                    <div class="flex flex-wrap gap-x-4 gap-y-1 opacity-70 text-[10px] italic">
                                        <span class="text-amber-300">Cap: {{ money(simulacion.sobranteACapital) }}</span>
                                    </div>
                                </div>
                                <div v-if="simulacion.desgloseItems.length === 0" class="py-10 text-center opacity-20">
                                    <p class="text-xs uppercase font-black tracking-widest text-white">Esperando Monto...</p>
                                </div>
                            </div>

                            <div class="mt-12 pt-8 border-t border-zinc-800 space-y-4">
                                <div class="flex justify-between text-xs font-medium opacity-50">
                                    <span>Base (Cap + Int)</span>
                                    <span>{{ money(simulacion.displayBase) }}</span>
                                </div>
                                <div v-if="simulacion.modoAbono && simulacion.sobranteACapital > 0" class="flex justify-between text-xs font-bold text-amber-400">
                                    <span>Sobrante a capital</span>
                                    <span>+ {{ money(simulacion.sobranteACapital) }}</span>
                                </div>
                                <div class="flex justify-between text-xs font-medium text-orange-400">
                                    <span>Total Mora</span>
                                    <span>+ {{ money(simulacion.displayMora) }}</span>
                                </div>
                                <div class="flex justify-between items-end pt-4">
                                    <span class="text-xs font-black uppercase tracking-widest">Total Aplicado</span>
                                    <span class="text-4xl font-black text-red-500 tracking-tighter">
                                        {{ money(simulacion.displayCapital + simulacion.displayOrdinario + simulacion.displayMora) }}
                                    </span>
                                </div>
                            </div>

                            <div v-if="Object.keys(form.errors).length" class="mt-6 space-y-2">
                                <p v-for="(msg, campo) in form.errors" :key="campo"
                                    class="text-xs font-bold text-red-300 bg-red-600/15 border border-red-500/40 rounded-xl px-3 py-2">
                                    {{ msg }}
                                </p>
                            </div>

                            <button @click="submit" :disabled="form.monto_recibido <= 0 || form.processing || simulacion.exceso || (simulacion.hayEscenario && !form.tipo_abono)"
                                class="w-full mt-10 py-5 bg-red-600 hover:bg-red-700 disabled:bg-zinc-800 text-white rounded-2xl font-black uppercase text-xs tracking-widest transition-all active:scale-95 shadow-xl shadow-red-600/20">
                                {{ form.processing ? 'Procesando...' : 'Confirmar Registro' }}
                            </button>
                        </div>

                        <div v-if="simulacion.cambio > 0.01 && !simulacion.modoAbono" class="bg-blue-600 p-8 rounded-[2rem] text-white shadow-xl shadow-blue-600/20 animate-in fade-in slide-in-from-bottom-4">
                            <p class="text-[10px] font-black uppercase opacity-60 tracking-widest">
                                {{ simulacion.hayEscenario ? 'Sobrante a aplicar' : (simulacion.sobranteParcial ? 'Se aplicará a la siguiente cuota' : 'Sobrante / Cambio') }}
                            </p>
                            <p class="text-4xl font-black tracking-tighter">{{ money(simulacion.cambio) }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #3f3f46; border-radius: 10px; }
* { transition: background-color 0.3s ease, border-color 0.3s ease, color 0.3s ease; }
</style>