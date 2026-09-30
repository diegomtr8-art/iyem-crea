<script setup lang="ts">
import BeneficiarioLayout from '@/layouts/BeneficiarioLayout.vue';
import { inp, lbl } from '@/Pages/portal/wizard/wizardStyles';
import { Head, useForm } from '@inertiajs/vue3';
import { User, Phone, Mail, Info } from 'lucide-vue-next';

const props = defineProps<{
    perfil: {
        telefono: string | null;
        correo: string | null;
    };
}>();

const form = useForm({
    telefono: props.perfil.telefono ?? '',
    correo: props.perfil.correo ?? '',
});

const guardar = () => form.patch(route('portal.perfil.update'), { preserveScroll: true });
</script>

<template>
    <BeneficiarioLayout>
        <Head title="Mi Perfil — CREA" />

        <div class="space-y-8 max-w-2xl">
            <div class="space-y-1">
                <p class="text-xs font-bold uppercase tracking-widest text-red-700">Portal Ciudadano CREA</p>
                <h1 class="text-2xl md:text-3xl font-black text-slate-900 dark:text-white flex items-center gap-3">
                    <User size="28" class="text-red-700" /> Mi Perfil
                </h1>
            </div>

            <form @submit.prevent="guardar"
                class="bg-white dark:bg-zinc-900 border border-slate-100 dark:border-zinc-800 rounded-2xl p-5 shadow-sm space-y-5">
                <div>
                    <label for="telefono" :class="lbl">Teléfono celular *</label>
                    <div class="relative">
                        <Phone size="16" class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                        <input id="telefono" :value="form.telefono"
                            @input="e => form.telefono = (e.target as HTMLInputElement).value.replace(/\D/g,'').slice(0,10)"
                            type="tel" maxlength="10" inputmode="numeric" autocomplete="tel" placeholder="10 dígitos"
                            :class="[inp, 'pl-10', form.errors.telefono ? 'border-red-400' : '']" />
                    </div>
                    <p v-if="form.errors.telefono" class="text-xs text-red-500 mt-1">{{ form.errors.telefono }}</p>
                </div>

                <div>
                    <label for="correo" :class="lbl">Correo electrónico *</label>
                    <div class="relative">
                        <Mail size="16" class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                        <input id="correo" v-model="form.correo" type="email" inputmode="email" autocomplete="email"
                            :class="[inp, 'pl-10', form.errors.correo ? 'border-red-400' : '']" />
                    </div>
                    <p v-if="form.errors.correo" class="text-xs text-red-500 mt-1">{{ form.errors.correo }}</p>
                </div>

                <p class="flex items-start gap-2 text-xs text-slate-500 dark:text-zinc-400">
                    <Info size="14" class="mt-0.5 shrink-0" />
                    Tu CURP, RFC y domicilio solo pueden ser modificados por la dirección de CREA.
                </p>

                <button type="submit" :disabled="form.processing"
                    class="px-6 py-3 bg-red-700 hover:bg-red-800 disabled:opacity-60 text-white font-bold rounded-2xl transition-all shadow-lg shadow-red-900/20">
                    {{ form.processing ? 'Guardando…' : 'Guardar cambios' }}
                </button>
            </form>
        </div>
    </BeneficiarioLayout>
</template>
