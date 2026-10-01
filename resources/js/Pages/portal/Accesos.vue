<script setup lang="ts">
import BeneficiarioLayout from '@/layouts/BeneficiarioLayout.vue';
import { Head } from '@inertiajs/vue3';
import { History, Info } from 'lucide-vue-next';

defineProps<{
    accesos: Array<{
        id: number;
        fecha: string;
        hora: string;
        ip_address: string | null;
        dispositivo: string | null;
    }>;
    limite: number;
}>();
</script>

<template>
    <BeneficiarioLayout>
        <Head title="Mis accesos — CREA" />

        <div class="space-y-8">
            <!-- Encabezado -->
            <div class="space-y-1">
                <p class="text-xs font-bold uppercase tracking-widest text-red-700">Portal Ciudadano CREA</p>
                <h1 class="flex items-center gap-3 text-2xl font-black text-slate-900 md:text-3xl dark:text-white">
                    <History size="28" class="text-red-700" /> Mis accesos
                </h1>
                <p class="text-sm text-slate-500 dark:text-zinc-400">
                    Tus últimos {{ limite }} inicios de sesión (hora de Mérida). Si no reconoces alguno, contacta a CREA.
                </p>
            </div>

            <!-- Sin accesos -->
            <div v-if="!accesos.length" class="rounded-3xl border border-slate-100 bg-white p-8 text-center shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <Info size="32" class="mx-auto mb-3 text-slate-400" />
                <p class="font-bold text-slate-900 dark:text-white">Aún no hay accesos registrados.</p>
            </div>

            <!-- Tabla -->
            <div v-else class="overflow-x-auto rounded-3xl border border-slate-100 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-slate-100 text-xs uppercase tracking-wider text-slate-500 dark:border-zinc-800 dark:text-zinc-400">
                        <tr>
                            <th class="px-5 py-3">Fecha</th>
                            <th class="px-5 py-3">Hora</th>
                            <th class="px-5 py-3">IP</th>
                            <th class="px-5 py-3">Dispositivo</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-zinc-800">
                        <tr v-for="a in accesos" :key="a.id" class="text-slate-700 dark:text-zinc-300">
                            <td class="px-5 py-3 font-semibold">{{ a.fecha }}</td>
                            <td class="px-5 py-3">{{ a.hora }}</td>
                            <td class="px-5 py-3">{{ a.ip_address ?? '—' }}</td>
                            <td class="max-w-xs truncate px-5 py-3" :title="a.dispositivo ?? ''">{{ a.dispositivo ?? '—' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </BeneficiarioLayout>
</template>
