<script setup lang="ts">
import { ref, computed } from 'vue';
import BeneficiarioLayout from '@/layouts/BeneficiarioLayout.vue';
import { Head } from '@inertiajs/vue3';
import { HelpCircle, Plus } from 'lucide-vue-next';
import { faqPortal } from '@/data/faqPortal';

const openKey = ref<string | null>(null);
const toggle = (key: string) => { openKey.value = openKey.value === key ? null : key; };

const categorias = computed(() => {
    const orden: string[] = [];
    const grupos = new Map<string, typeof faqPortal>();

    for (const item of faqPortal) {
        if (!grupos.has(item.categoria)) {
            grupos.set(item.categoria, []);
            orden.push(item.categoria);
        }
        grupos.get(item.categoria)!.push(item);
    }

    return orden.map((categoria) => ({ categoria, items: grupos.get(categoria)! }));
});
</script>

<template>
    <BeneficiarioLayout>
        <Head title="Ayuda — CREA" />

        <div class="space-y-8">
            <!-- Encabezado -->
            <div class="space-y-1">
                <p class="text-xs font-bold uppercase tracking-widest text-red-700">Portal Ciudadano CREA</p>
                <h1 class="text-2xl md:text-3xl font-black text-slate-900 dark:text-white flex items-center gap-3">
                    <HelpCircle size="28" class="text-red-700" /> Ayuda y Preguntas Frecuentes
                </h1>
                <p class="text-slate-500 dark:text-zinc-400 max-w-2xl">
                    Encuentra respuestas rápidas sobre CREA, tu cuota, tus pagos y cómo contactar a tu asesor.
                </p>
            </div>

            <!-- Categorías -->
            <div class="space-y-10">
                <section v-for="grupo in categorias" :key="grupo.categoria" class="space-y-3">
                    <h2 class="text-sm font-black uppercase tracking-wider text-red-700 dark:text-[#f4a8c4]">
                        {{ grupo.categoria }}
                    </h2>

                    <div class="space-y-3">
                        <div v-for="(faq, i) in grupo.items" :key="`${grupo.categoria}-${i}`"
                            class="rounded-2xl border bg-white dark:bg-zinc-900 overflow-hidden transition-colors"
                            :class="openKey === `${grupo.categoria}-${i}` ? 'border-red-200 dark:border-red-800/40' : 'border-slate-100 dark:border-zinc-800'">
                            <button
                                @click="toggle(`${grupo.categoria}-${i}`)"
                                class="w-full flex items-center justify-between gap-4 px-5 sm:px-6 py-4 sm:py-5 text-left"
                                :aria-expanded="openKey === `${grupo.categoria}-${i}`">
                                <span class="font-bold text-slate-900 dark:text-white text-sm sm:text-base">{{ faq.pregunta }}</span>
                                <span class="shrink-0 w-7 h-7 rounded-lg flex items-center justify-center transition-all duration-300"
                                    :class="openKey === `${grupo.categoria}-${i}`
                                        ? 'bg-red-700 text-white rotate-45'
                                        : 'bg-slate-100 dark:bg-zinc-800 text-slate-500 dark:text-zinc-400'">
                                    <Plus size="16" />
                                </span>
                            </button>
                            <div class="faq-body" :style="{ maxHeight: openKey === `${grupo.categoria}-${i}` ? '600px' : '0px' }">
                                <div class="px-5 sm:px-6 pb-5 sm:pb-6 space-y-3">
                                    <p v-for="(parrafo, p) in faq.respuesta.split('\n\n')" :key="p"
                                        class="text-sm text-slate-600 dark:text-zinc-400 leading-relaxed">
                                        {{ parrafo }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </BeneficiarioLayout>
</template>

<style scoped>
.faq-body {
    overflow: hidden;
    transition: max-height 0.3s ease;
}
</style>
