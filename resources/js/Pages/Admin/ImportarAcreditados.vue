<script setup>
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    columnas: Array,
    vista_previa: Object,
});

const subida = useForm({ archivo: null });
const confirmacion = useForm({});
const cancelacion = useForm({});

const previsualizar = () => {
    subida.post(route('importar-acreditados.previsualizar'), { forceFormData: true });
};

const confirmar = () => {
    const n = props.vista_previa.validas.length;
    if (!confirm(`¿Importar ${n} acreditados? Las filas con errores no se guardarán.`)) return;
    confirmacion.post(route('importar-acreditados.confirmar'));
};

const cancelar = () => cancelacion.post(route('importar-acreditados.cancelar'));
</script>

<template>

    <Head title="Importar acreditados" />
    <AppLayout>
        <div class="max-w-5xl mx-auto py-8 px-4 space-y-6">
            <h1 class="text-2xl font-black uppercase tracking-tight text-slate-900 dark:text-white">Importar acreditados</h1>
            <p class="text-sm text-slate-500">
                Sube un Excel con un acreditado por fila. La primera fila deben ser los encabezados:
                <span class="font-mono">{{ columnas.join(', ') }}</span>.
                Nada se guarda hasta que confirmes la vista previa.
            </p>

            <form @submit.prevent="previsualizar"
                class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-6 space-y-4">
                <input type="file" accept=".xlsx,.xls" @input="subida.archivo = $event.target.files[0]"
                    class="block w-full text-sm dark:text-white" />
                <p v-if="subida.errors.archivo" class="text-red-600 text-sm font-bold">{{ subida.errors.archivo }}</p>
                <button type="submit" :disabled="!subida.archivo || subida.processing"
                    class="bg-red-700 text-white font-black py-3 px-6 rounded-xl uppercase text-xs tracking-widest disabled:opacity-50">
                    {{ subida.processing ? 'Leyendo...' : 'Ver vista previa' }}
                </button>
            </form>

            <section v-if="vista_previa"
                class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-6 space-y-6 dark:text-white">
                <div class="flex flex-wrap gap-6 text-sm">
                    <span class="font-bold">Archivo: {{ vista_previa.archivo }}</span>
                    <span class="text-green-700 font-black">Válidas: {{ vista_previa.validas.length }}</span>
                    <span class="text-red-700 font-black">Con errores: {{ vista_previa.errores.length }}</span>
                </div>

                <div v-if="vista_previa.errores.length">
                    <h2 class="text-xs font-black uppercase text-red-700 mb-2">Filas con errores (no se importarán)</h2>
                    <table class="w-full text-sm">
                        <thead class="text-left text-xs uppercase text-slate-500">
                            <tr><th class="py-2">Fila</th><th>Nombre</th><th>CURP</th><th>Motivo</th></tr>
                        </thead>
                        <tbody>
                            <tr v-for="e in vista_previa.errores" :key="e.fila"
                                class="border-t border-slate-100 dark:border-slate-800 align-top">
                                <td class="py-2 font-mono">{{ e.fila }}</td>
                                <td>{{ e.nombre ?? '—' }}</td>
                                <td class="font-mono">{{ e.curp ?? '—' }}</td>
                                <td class="text-red-700"><div v-for="m in e.errores" :key="m">{{ m }}</div></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="vista_previa.validas.length">
                    <h2 class="text-xs font-black uppercase text-green-700 mb-2">Filas válidas</h2>
                    <table class="w-full text-sm">
                        <thead class="text-left text-xs uppercase text-slate-500">
                            <tr><th class="py-2">Fila</th><th>Nombre</th><th>CURP</th><th>Municipio</th><th>Correo</th></tr>
                        </thead>
                        <tbody>
                            <tr v-for="v in vista_previa.validas" :key="v.fila"
                                class="border-t border-slate-100 dark:border-slate-800">
                                <td class="py-2 font-mono">{{ v.fila }}</td>
                                <td>{{ v.nombre_completo }}</td>
                                <td class="font-mono">{{ v.curp ?? '—' }}</td>
                                <td>{{ v.municipio }}</td>
                                <td>{{ v.correo ?? '—' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="flex gap-3">
                    <button type="button" @click="confirmar"
                        :disabled="!vista_previa.validas.length || confirmacion.processing"
                        class="bg-red-700 text-white font-black py-3 px-6 rounded-xl uppercase text-xs tracking-widest disabled:opacity-50">
                        {{ confirmacion.processing ? 'Importando...' : `Confirmar e importar ${vista_previa.validas.length}` }}
                    </button>
                    <button type="button" @click="cancelar" :disabled="cancelacion.processing"
                        class="border border-slate-300 dark:border-slate-700 font-black py-3 px-6 rounded-xl uppercase text-xs tracking-widest">
                        Cancelar
                    </button>
                </div>
            </section>
        </div>
    </AppLayout>
</template>