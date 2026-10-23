<script setup>
import { computed } from 'vue';

const props = defineProps({ cartera: Object });

const money = (v) => new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(v || 0);

const fechaCalculo = computed(() => props.cartera
    ? new Date(props.cartera.calculado_en).toLocaleString('es-MX', { dateStyle: 'long', timeStyle: 'short', timeZone: 'America/Merida' })
    : null);
</script>

<template>
    <div class="bg-white dark:bg-zinc-900 p-6 rounded-2xl border border-zinc-100 dark:border-zinc-800 flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest mb-1">Cartera activa · todas las modalidades</p>
            <template v-if="cartera">
                <p class="text-4xl font-light tracking-tighter dark:text-white">{{ money(cartera.total) }}</p>
                <p class="text-[10px] text-zinc-400 mt-1">Lo que falta por pagar de {{ cartera.creditos }} créditos activos y morosos</p>
            </template>
            <p v-else class="text-sm text-zinc-500">Aún no se ha calculado. Se calcula todos los días a las 7:00.</p>
        </div>
        <div v-if="cartera" class="text-right">
            <p class="text-[10px] text-zinc-400 uppercase tracking-widest">Último cálculo</p>
            <p class="text-sm font-semibold dark:text-zinc-300">{{ fechaCalculo }}</p>
            <p class="text-[10px] text-zinc-400">Se actualiza una vez al día, no en tiempo real</p>
        </div>
    </div>
</template>
