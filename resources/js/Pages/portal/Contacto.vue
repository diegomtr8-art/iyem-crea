<script setup lang="ts">
import { computed } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { Send } from 'lucide-vue-next';
import BeneficiarioLayout from '@/layouts/BeneficiarioLayout.vue';

const page = usePage<any>();
const user = computed(() => page.props.auth?.user);

const form = useForm({ asunto: '', mensaje: '' });

const enviar = () =>
    form.post(route('portal.contacto.enviar'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });

const inputClass = (error?: string) => [
    'w-full rounded-xl border px-4 py-3 text-sm bg-white dark:bg-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#6B1938]',
    error ? 'border-red-400' : 'border-gray-200 dark:border-zinc-700',
];
</script>

<template>
    <Head title="Contacto — CREA" />
    <BeneficiarioLayout>
        <div class="mx-auto max-w-2xl space-y-6">
            <div>
                <p class="text-xs font-bold uppercase tracking-widest text-red-700">Soporte</p>
                <h1 class="text-2xl font-black">Contacta al equipo IYEM</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-zinc-400">
                    Envía tus dudas o reporta un problema.
                    <span v-if="user">Enviarás el mensaje como <strong>{{ user.name }}</strong> ({{ user.email }}).</span>
                </p>
            </div>

            <form @submit.prevent="enviar" class="space-y-5 rounded-3xl border border-gray-100 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <div>
                    <label for="asunto" class="mb-1 block text-xs font-black uppercase tracking-wider">Asunto</label>
                    <input id="asunto" v-model="form.asunto" type="text" maxlength="255" :class="inputClass(form.errors.asunto)" />
                    <p v-if="form.errors.asunto" class="mt-1 text-xs text-red-500">{{ form.errors.asunto }}</p>
                </div>

                <div>
                    <label for="mensaje" class="mb-1 block text-xs font-black uppercase tracking-wider">Mensaje</label>
                    <textarea id="mensaje" v-model="form.mensaje" rows="6" maxlength="2000" :class="inputClass(form.errors.mensaje)" />
                    <div class="mt-1 flex justify-between text-xs">
                        <p class="text-red-500">{{ form.errors.mensaje }}</p>
                        <p class="text-gray-400">{{ form.mensaje.length }}/2000</p>
                    </div>
                </div>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="inline-flex items-center gap-2 rounded-xl bg-[#6B1938] px-5 py-3 text-sm font-bold text-white hover:bg-[#4E1029] disabled:opacity-60"
                >
                    <Send class="h-4 w-4" />
                    {{ form.processing ? 'Enviando…' : 'Enviar mensaje' }}
                </button>
            </form>
        </div>
    </BeneficiarioLayout>
</template>
