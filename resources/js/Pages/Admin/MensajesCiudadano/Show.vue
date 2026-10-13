<script setup>
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ mensaje: Object });

const enviando = ref(false);

const marcarAtendido = () => {
    enviando.value = true;
    router.post(route('mensajes-ciudadanos.atendido', props.mensaje.id), {}, {
        preserveScroll: true,
        onFinish: () => { enviando.value = false; },
    });
};
</script>

<template>
    <Head :title="mensaje.asunto" />
    <AppLayout>
        <div class="max-w-3xl mx-auto py-8 px-4 space-y-6">
            <Link :href="route('mensajes-ciudadanos.index')" class="text-xs font-black uppercase tracking-widest text-slate-500 hover:underline">
                ← Volver a la lista
            </Link>

            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-6 space-y-5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <h1 class="text-xl font-black tracking-tight text-slate-900 dark:text-white break-words">{{ mensaje.asunto }}</h1>
                    <span v-if="mensaje.atendido" class="font-bold text-sm text-emerald-600">Atendido</span>
                    <span v-else class="font-bold text-sm text-amber-600">Pendiente</span>
                </div>

                <dl class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
                    <div>
                        <dt class="text-xs uppercase text-slate-500">Ciudadano</dt>
                        <dd class="font-bold">{{ mensaje.ciudadano ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase text-slate-500">Correo</dt>
                        <dd class="font-bold break-all">{{ mensaje.correo ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase text-slate-500">Fecha</dt>
                        <dd class="font-bold">{{ mensaje.fecha }}</dd>
                    </div>
                </dl>

                <p class="whitespace-pre-wrap break-words text-sm leading-relaxed border-t border-slate-100 dark:border-slate-800 pt-5">{{ mensaje.mensaje }}</p>

                <div class="border-t border-slate-100 dark:border-slate-800 pt-5">
                    <p v-if="mensaje.atendido" class="text-sm text-slate-500">
                        Atendido por <span class="font-bold">{{ mensaje.atendido_por ?? 'un usuario eliminado' }}</span>
                        el {{ mensaje.atendido_at }}.
                    </p>
                    <button v-else type="button" :disabled="enviando" @click="marcarAtendido"
                        class="px-5 py-2.5 rounded-xl bg-slate-900 text-white text-xs font-black uppercase tracking-widest disabled:opacity-50">
                        Marcar como atendido
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
