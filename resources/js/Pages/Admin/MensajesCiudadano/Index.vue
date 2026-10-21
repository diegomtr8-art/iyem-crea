<script setup>
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({ mensajes: Object, filters: Object, pendientes: Number });

const opciones = [
    { valor: null, texto: 'Todos' },
    { valor: 'pendientes', texto: 'Pendientes' },
    { valor: 'atendidos', texto: 'Atendidos' },
];

const filtrar = (estado) => {
    router.get(route('mensajes-ciudadanos.index'), { estado: estado || undefined }, { preserveState: true, replace: true });
};
</script>

<template>
    <Head title="Mensajes de ciudadanos" />
    <AppLayout>
        <div class="max-w-5xl mx-auto py-8 px-4 space-y-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h1 class="text-2xl font-black uppercase tracking-tight text-slate-900 dark:text-white">Mensajes de ciudadanos</h1>
                <span class="text-sm font-bold" :class="pendientes ? 'text-amber-600' : 'text-emerald-600'">
                    {{ pendientes }} pendiente(s)
                </span>
            </div>

            <div class="flex gap-2">
                <button v-for="o in opciones" :key="o.texto" type="button" @click="filtrar(o.valor)"
                    class="px-4 py-2 rounded-xl text-xs font-black uppercase tracking-widest border transition-colors"
                    :class="(filters.estado ?? null) === o.valor
                        ? 'bg-slate-900 text-white border-slate-900'
                        : 'bg-white dark:bg-slate-900 text-slate-500 border-slate-200 dark:border-slate-800 hover:bg-slate-50'">
                    {{ o.texto }}
                </button>
            </div>

            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 dark:bg-slate-800/50 text-xs uppercase text-slate-500">
                        <tr>
                            <th class="text-left p-3">Fecha</th>
                            <th class="text-left p-3">Ciudadano</th>
                            <th class="text-left p-3">Asunto</th>
                            <th class="text-left p-3">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="m in mensajes.data" :key="m.id"
                            class="border-t border-slate-100 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800/40">
                            <td class="p-3 whitespace-nowrap">{{ m.fecha }}</td>
                            <td class="p-3">
                                <div class="font-bold">{{ m.ciudadano ?? '—' }}</div>
                                <div class="text-xs text-slate-500">{{ m.correo }}</div>
                            </td>
                            <td class="p-3">
                                <Link :href="route('mensajes-ciudadanos.show', m.id)" class="font-bold text-slate-900 dark:text-white hover:underline">
                                    {{ m.asunto }}
                                </Link>
                            </td>
                            <td class="p-3">
                                <span v-if="m.atendido" class="font-bold text-emerald-600">Atendido</span>
                                <span v-else class="font-bold text-amber-600">Pendiente</span>
                            </td>
                        </tr>
                        <tr v-if="!mensajes.data.length">
                            <td colspan="4" class="p-6 text-center text-slate-400">No hay mensajes.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="mensajes.last_page > 1" class="flex flex-wrap items-center justify-between gap-3">
                <p class="text-xs text-slate-500">Mostrando {{ mensajes.from }}–{{ mensajes.to }} de {{ mensajes.total }}</p>
                <div class="flex gap-1">
                    <button v-for="(link, i) in mensajes.links" :key="i" type="button" :disabled="!link.url"
                        @click="link.url && router.get(link.url, {}, { preserveState: true })"
                        class="px-3 py-1.5 rounded-lg text-xs font-bold border disabled:opacity-40"
                        :class="link.active ? 'bg-slate-900 text-white border-slate-900' : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800'"
                        v-html="link.label" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
