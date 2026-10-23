<script setup>
// Estado del último respaldo de la base (crea:respaldo-base); ver docs/RESPALDO.md
defineProps({ respaldo: Object });
</script>

<template>
    <div :class="respaldo.problema
        ? 'bg-red-50 border-red-200 text-red-700'
        : 'bg-emerald-50 border-emerald-200 text-emerald-700'"
         class="border rounded-2xl p-4 text-sm space-y-1">
        <div class="font-bold">
            {{ respaldo.problema ? `Falta respaldo: ${respaldo.problema}` : 'Respaldo de la base al día' }}
        </div>
        <div v-if="respaldo.ultimo" class="text-xs">
            Último respaldo: {{ respaldo.ultimo.fecha }} · {{ respaldo.ultimo.kb }} KB ·
            <span class="font-mono">{{ respaldo.ultimo.archivo }}</span>
        </div>
    </div>
</template>
