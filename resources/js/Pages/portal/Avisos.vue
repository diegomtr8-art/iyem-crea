<script setup lang="ts">
import BeneficiarioLayout from '@/layouts/BeneficiarioLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { CalendarClock, CalendarCheck, CheckCircle2, Info } from 'lucide-vue-next';

type Aviso = 'proxima' | 'gracia' | 'vencida' | 'pendiente';

const props = defineProps<{
    credito: null | { clave_contrato?: string };
    cuotas: Array<{
        numero_cuota: number;
        fecha_vencimiento: string;
        monto: number;
        dias: number;
        aviso: Aviso;
    }>;
    dias_gracia: number;
    ventana_dias: number;
}>();

const proximas = computed(() => props.cuotas.filter((c) => c.aviso === 'proxima'));

const fmt = (n: number) => Number(n ?? 0).toLocaleString('es-MX', { minimumFractionDigits: 2 });

const cuandoVence = (dias: number) => (dias === 0 ? 'Vence hoy' : dias === 1 ? 'Vence mañana' : `Vence en ${dias} días`);

const etiqueta = (c: (typeof props.cuotas)[0]) => {
    if (c.aviso === 'vencida') return { texto: 'VENCIDA', clase: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' };
    if (c.aviso === 'gracia')
        return {
            texto: `EN GRACIA · ${-c.dias} de ${props.dias_gracia} días`,
            clase: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
        };
    if (c.aviso === 'proxima') return { texto: 'PRÓXIMA', clase: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400' };
    return { texto: 'PENDIENTE', clase: 'bg-slate-100 text-slate-600 dark:bg-zinc-800 dark:text-zinc-400' };
};
</script>

<template>
    <BeneficiarioLayout>
        <Head title="Avisos — CREA" />

        <div class="space-y-8">
            <!-- Encabezado -->
            <div class="space-y-1">
                <p class="text-xs font-bold uppercase tracking-widest text-red-700">Portal Ciudadano CREA</p>
                <h1 class="flex items-center gap-3 text-2xl font-black text-slate-900 md:text-3xl dark:text-white">
                    <CalendarClock size="28" class="text-red-700" /> Avisos de vencimiento
                </h1>
                <p v-if="credito?.clave_contrato" class="text-sm text-slate-500 dark:text-zinc-400">Contrato {{ credito.clave_contrato }}</p>
            </div>

            <!-- Sin crédito -->
            <div v-if="!credito" class="rounded-3xl border border-slate-100 bg-white p-8 text-center shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <Info size="32" class="mx-auto mb-3 text-slate-400" />
                <p class="font-bold text-slate-900 dark:text-white">Aún no tienes un crédito activo.</p>
                <p class="mt-1 text-sm text-slate-500 dark:text-zinc-400">Cuando tu crédito esté activo, aquí verás tus próximos vencimientos.</p>
                <Link :href="route('portal.dashboard')" class="mt-4 inline-block text-sm font-bold text-red-700 hover:underline">Volver al inicio</Link>
            </div>

            <template v-else>
                <!-- Próximos 15 días -->
                <section class="rounded-3xl border border-slate-100 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                    <h2 class="mb-4 text-xs font-bold uppercase tracking-wider text-slate-400">Vencen en los próximos {{ ventana_dias }} días</h2>

                    <ul v-if="proximas.length" class="space-y-3">
                        <li
                            v-for="c in proximas"
                            :key="c.numero_cuota"
                            class="flex flex-wrap items-center justify-between gap-2 rounded-2xl border-l-4 border-blue-500 bg-blue-50 p-4 dark:bg-blue-950/30"
                        >
                            <div>
                                <p class="font-bold text-slate-900 dark:text-white">Cuota #{{ c.numero_cuota }} · {{ c.fecha_vencimiento }}</p>
                                <p class="text-sm text-blue-700 dark:text-blue-400">{{ cuandoVence(c.dias) }}</p>
                            </div>
                            <p class="text-lg font-black text-slate-900 dark:text-white">${{ fmt(c.monto) }}</p>
                        </li>
                    </ul>

                    <div v-else class="flex items-center gap-3 rounded-2xl bg-emerald-50 p-4 dark:bg-emerald-950/30">
                        <CalendarCheck size="22" class="shrink-0 text-emerald-600" />
                        <p class="text-sm font-semibold text-emerald-800 dark:text-emerald-300">
                            No tienes cuotas que venzan en los próximos {{ ventana_dias }} días.
                        </p>
                    </div>
                </section>

                <!-- Todas las pendientes -->
                <section class="rounded-3xl border border-slate-100 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                    <h2 class="mb-4 text-xs font-bold uppercase tracking-wider text-slate-400">Tus cuotas pendientes</h2>

                    <ul v-if="cuotas.length" class="divide-y divide-slate-100 dark:divide-zinc-800">
                        <li v-for="c in cuotas" :key="c.numero_cuota" class="flex flex-wrap items-center justify-between gap-2 py-3">
                            <div class="flex items-center gap-3">
                                <span class="w-10 text-sm font-bold text-slate-400">#{{ c.numero_cuota }}</span>
                                <span class="text-sm font-semibold text-slate-900 dark:text-white">{{ c.fecha_vencimiento }}</span>
                                <span :class="['rounded-full px-2.5 py-0.5 text-[11px] font-bold', etiqueta(c).clase]">{{ etiqueta(c).texto }}</span>
                            </div>
                            <span class="text-sm font-bold text-slate-900 dark:text-white">${{ fmt(c.monto) }}</span>
                        </li>
                    </ul>

                    <div v-else class="flex items-center gap-3 rounded-2xl bg-emerald-50 p-4 dark:bg-emerald-950/30">
                        <CheckCircle2 size="22" class="shrink-0 text-emerald-600" />
                        <p class="text-sm font-semibold text-emerald-800 dark:text-emerald-300">No tienes cuotas pendientes.</p>
                    </div>

                    <p class="mt-4 text-xs text-slate-400 dark:text-zinc-500">
                        Cuentas con {{ dias_gracia }} días de gracia después de cada fecha de vencimiento.
                    </p>
                </section>
            </template>
        </div>
    </BeneficiarioLayout>
</template>
