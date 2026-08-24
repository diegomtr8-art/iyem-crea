<script setup lang="ts">
import { ListChecks, ChevronDown, ChevronUp } from 'lucide-vue-next';
import { card, lbl, sHead, sIcon } from './wizardStyles';

const props = defineProps<{
    datos: Record<string, any>;
    modalidades: Array<{ id: number; nombre: string; tasa_interes: string; monto_minimo: number; monto_maximo: number }>;
    isArtesanal: boolean | undefined;
    modalidadActual: { nombre?: string } | undefined;
    isOpen: boolean;
}>();
defineEmits<{ toggle: [] }>();
</script>

<template>
    <div :class="card">
        <div :class="sHead" @click="$emit('toggle')">
            <div :class="sIcon"><ListChecks size="18" /></div>
            <div class="flex-1"><h2 class="font-black text-slate-900 dark:text-white text-sm">Tipo de crédito</h2><p class="text-xs text-slate-400">Modalidad, tipo de persona y garantía</p></div>
            <component :is="isOpen ? ChevronUp : ChevronDown" size="18" class="text-slate-400 shrink-0" />
        </div>
        <div v-if="isOpen" class="p-5 space-y-5">
            <div>
                <label :class="lbl">Modalidad *</label>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mt-1">
                    <button v-for="m in modalidades" :key="m.id" type="button"
                        @click="datos.modalidad_id = m.id; if(m.nombre?.toLowerCase().includes('artesanal')) datos.tipo_persona = 'fisica'"
                        :class="['rounded-xl border-2 p-4 text-left transition-all',
                            datos.modalidad_id == m.id ? 'border-[#6B1938] bg-[#6B1938]/5 dark:bg-[#6B1938]/10' : 'border-slate-200 dark:border-zinc-700 hover:border-[#6B1938]/60']">
                        <div class="font-black text-sm text-slate-900 dark:text-white">CREA {{ m.nombre }}</div>
                        <div class="text-xs text-slate-500 mt-1">{{ new Intl.NumberFormat('es-MX',{style:'currency',currency:'MXN',maximumFractionDigits:0}).format(m.monto_minimo) }} – {{ new Intl.NumberFormat('es-MX',{style:'currency',currency:'MXN',maximumFractionDigits:0}).format(m.monto_maximo) }}</div>
                        <div class="text-[10px] text-[#6B1938] dark:text-[#f4a8c4] font-bold mt-1">{{ m.tasa_interes }}% anual</div>
                    </button>
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label :class="lbl">Tipo de persona *</label>
                    <div class="flex gap-3 mt-1">
                        <button type="button" @click="datos.tipo_persona = 'fisica'" :disabled="isArtesanal"
                            :class="['flex-1 rounded-xl border-2 py-3 px-4 font-bold text-sm transition-all min-h-[44px]', datos.tipo_persona === 'fisica' ? 'border-[#6B1938] bg-[#6B1938]/5 dark:bg-[#6B1938]/10 text-[#6B1938] dark:text-[#f4a8c4]' : 'border-slate-200 dark:border-zinc-700 text-slate-600 dark:text-zinc-400 hover:border-[#6B1938]/60 disabled:opacity-40']">Física</button>
                        <button type="button" @click="datos.tipo_persona = 'moral'" :disabled="isArtesanal"
                            :class="['flex-1 rounded-xl border-2 py-3 px-4 font-bold text-sm transition-all min-h-[44px]', datos.tipo_persona === 'moral' ? 'border-[#6B1938] bg-[#6B1938]/5 dark:bg-[#6B1938]/10 text-[#6B1938] dark:text-[#f4a8c4]' : 'border-slate-200 dark:border-zinc-700 text-slate-600 dark:text-zinc-400 hover:border-[#6B1938]/60 disabled:opacity-40']">Moral</button>
                    </div>
                </div>
                <div>
                    <label :class="lbl">Tipo de garantía *</label>
                    <div class="grid grid-cols-3 gap-2 mt-1">
                        <button v-for="g in ['aval','prendaria','hipotecaria']" :key="g" type="button"
                            :disabled="g === 'hipotecaria' && !modalidadActual?.nombre?.toLowerCase().includes('sustentable')"
                            @click="datos.tipo_garantia = g"
                            :class="['rounded-xl border-2 py-2.5 px-2 font-bold text-xs transition-all capitalize min-h-[44px]', datos.tipo_garantia === g ? 'border-[#6B1938] bg-[#6B1938]/5 dark:bg-[#6B1938]/10 text-[#6B1938] dark:text-[#f4a8c4]' : 'border-slate-200 dark:border-zinc-700 text-slate-600 dark:text-zinc-400 hover:border-[#6B1938]/60 disabled:opacity-40']">
                            {{ g === 'aval' ? 'Aval' : g === 'prendaria' ? 'Prendaria' : 'Hipotecaria' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
