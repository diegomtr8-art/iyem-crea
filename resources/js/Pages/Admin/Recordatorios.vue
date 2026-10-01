<script setup>
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({ dias_default: Number });
const form = useForm({ dias: props.dias_default });

const enviar = () => {
    if (!confirm(`¿Enviar recordatorios a las cuotas que vencen en los próximos ${form.dias} días?`)) return;
    form.post(route('recordatorios.enviar'), { preserveScroll: true });
};
</script>

<template>

    <Head title="Recordatorios de pago" />
    <AppLayout>
        <div class="max-w-xl mx-auto py-8 px-4 space-y-6">
            <h1 class="text-2xl font-black uppercase tracking-tight text-slate-900 dark:text-white">Recordatorios de
                pago</h1>
            <p class="text-sm text-slate-500">Encola un correo por cada cuota que vence en los próximos días. A cada
                cuota solo se le avisa una vez.</p>

            <form @submit.prevent="enviar"
                class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-6 space-y-4">
                <label class="block text-xs font-black uppercase text-slate-500">Días por vencer</label>
                <input v-model.number="form.dias" type="number" min="1" max="30"
                    class="w-full rounded-2xl border border-slate-200 dark:border-slate-700 dark:bg-slate-950 dark:text-white py-2.5 px-4 text-sm" />
                <p v-if="form.errors.dias" class="text-red-600 text-sm font-bold">{{ form.errors.dias }}</p>
                <button type="submit" :disabled="form.processing"
                    class="bg-red-700 text-white font-black py-3 px-6 rounded-xl uppercase text-xs tracking-widest disabled:opacity-50">
                    {{ form.processing ? 'Encolando...' : 'Enviar recordatorios' }}
                </button>
            </form>
        </div>
    </AppLayout>
</template>