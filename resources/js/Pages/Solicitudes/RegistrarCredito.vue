<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, User, Briefcase, CreditCard, CheckCircle2, Sparkles } from 'lucide-vue-next';

const props = defineProps<{
    solicitud: {
        id: number;
        nombre_completo: string;
        curp?: string;
        rfc?: string;
        sexo: string;
        municipio?: string;
        direccion?: string;
        correo?: string;
        modalidad_id?: number;
        modalidad_nombre?: string;
        monto_solicitado?: number;
        user_email?: string;
    };
    modalidades: Array<{ id: number; nombre: string; tasa_interes?: number }>;
    clave_sugerida: string;
}>();

const form = useForm({
    nombre_completo: props.solicitud.nombre_completo ?? '',
    curp:            props.solicitud.curp ?? '',
    rfc:             props.solicitud.rfc ?? '',
    sexo:            props.solicitud.sexo ?? 'H',
    municipio:       props.solicitud.municipio ?? '',
    direccion:       props.solicitud.direccion ?? '',
    correo:          props.solicitud.correo ?? '',
    modalidad_id:    props.solicitud.modalidad_id ?? '',
    clave_contrato:  props.clave_sugerida,
    monto_otorgado:  props.solicitud.monto_solicitado ?? '',
    plazo_meses:     12,
    fecha_entrega:   new Date().toISOString().split('T')[0],
});

const submit = () => form.post(route('solicitudes.guardar-credito', props.solicitud.id));

const inputCls = 'w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-zinc-700 dark:bg-zinc-800 text-sm focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-all';
const labelCls = 'block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5';
</script>

<template>
    <AppLayout>
        <Head :title="`Registrar Crédito — ${solicitud.nombre_completo}`" />

        <div class="p-6 max-w-4xl space-y-6">
            <!-- Header -->
            <div class="flex items-center gap-4">
                <Link :href="route('solicitudes.show', solicitud.id)"
                    class="p-2 rounded-xl bg-slate-100 dark:bg-zinc-800 text-slate-600 hover:bg-slate-200 dark:hover:bg-zinc-700 transition-all">
                    <ArrowLeft size="18" />
                </Link>
                <div>
                    <h1 class="text-2xl font-black text-slate-900 dark:text-white flex items-center gap-3">
                        <CreditCard size="24" class="text-red-700" /> Registrar Crédito CREA
                    </h1>
                    <p class="text-slate-500 dark:text-zinc-400 text-sm mt-0.5">
                        Solicitud #{{ solicitud.id }} — {{ solicitud.nombre_completo }}
                    </p>
                </div>
            </div>

            <!-- Banner informativo -->
            <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800/40 rounded-2xl p-5 flex items-start gap-4">
                <CheckCircle2 size="22" class="text-green-600 shrink-0 mt-0.5" />
                <div>
                    <p class="font-black text-green-800 dark:text-green-300">Solicitud Aprobada</p>
                    <p class="text-sm text-green-700 dark:text-green-400 mt-0.5">
                        Los datos del formulario están precargados desde la solicitud del ciudadano. Verifica y completa los datos del crédito.
                        Al guardar se creará el expediente del acreditado, la tabla de amortización y se notificará al ciudadano.
                    </p>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-6">

                <!-- Sección 1: Datos del acreditado -->
                <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-slate-100 dark:border-zinc-800 overflow-hidden shadow-sm">
                    <div class="px-5 py-4 border-b border-slate-100 dark:border-zinc-800 flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-red-50 dark:bg-red-900/20 flex items-center justify-center text-red-700">
                            <User size="18" />
                        </div>
                        <h2 class="font-black text-slate-900 dark:text-white">Datos del Acreditado</h2>
                        <span class="text-xs text-slate-400 ml-auto">Precargado desde la solicitud</span>
                    </div>
                    <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div class="sm:col-span-2">
                            <label :class="labelCls">Nombre Completo</label>
                            <input v-model="form.nombre_completo" type="text" required :class="inputCls" />
                            <p v-if="form.errors.nombre_completo" class="text-red-600 text-xs mt-1">{{ form.errors.nombre_completo }}</p>
                        </div>
                        <div>
                            <label :class="labelCls">CURP</label>
                            <input v-model="form.curp" type="text" maxlength="18" :class="inputCls" class="font-mono" />
                        </div>
                        <div>
                            <label :class="labelCls">RFC</label>
                            <input v-model="form.rfc" type="text" maxlength="13" :class="inputCls" class="font-mono" />
                        </div>
                        <div>
                            <label :class="labelCls">Sexo</label>
                            <select v-model="form.sexo" required :class="inputCls">
                                <option value="H">Hombre</option>
                                <option value="M">Mujer</option>
                            </select>
                        </div>
                        <div>
                            <label :class="labelCls">Municipio</label>
                            <input v-model="form.municipio" type="text" required :class="inputCls" />
                        </div>
                        <div class="sm:col-span-2">
                            <label :class="labelCls">Dirección</label>
                            <input v-model="form.direccion" type="text" :class="inputCls" />
                        </div>
                        <div class="sm:col-span-2">
                            <label :class="labelCls">Correo Electrónico</label>
                            <input v-model="form.correo" type="email" :class="inputCls" />
                        </div>
                    </div>
                </div>

                <!-- Sección 2: Datos del crédito -->
                <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-slate-100 dark:border-zinc-800 overflow-hidden shadow-sm">
                    <div class="px-5 py-4 border-b border-slate-100 dark:border-zinc-800 flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-red-50 dark:bg-red-900/20 flex items-center justify-center text-red-700">
                            <CreditCard size="18" />
                        </div>
                        <h2 class="font-black text-slate-900 dark:text-white">Datos del Crédito</h2>
                    </div>
                    <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label :class="labelCls">Modalidad CREA</label>
                            <select v-model="form.modalidad_id" required :class="inputCls">
                                <option value="" disabled>Seleccionar...</option>
                                <option v-for="m in modalidades" :key="m.id" :value="m.id">{{ m.nombre }}</option>
                            </select>
                            <p v-if="form.errors.modalidad_id" class="text-red-600 text-xs mt-1">{{ form.errors.modalidad_id }}</p>
                        </div>
                        <div>
                            <label :class="labelCls">Clave de Contrato</label>
                            <input v-model="form.clave_contrato" type="text" required :class="inputCls" class="font-mono" />
                            <p v-if="form.errors.clave_contrato" class="text-red-600 text-xs mt-1">{{ form.errors.clave_contrato }}</p>
                        </div>
                        <div>
                            <label :class="labelCls">Monto Otorgado ($)</label>
                            <input v-model="form.monto_otorgado" type="number" step="0.01" min="100" required :class="inputCls" />
                            <p class="text-xs text-slate-400 mt-1">
                                Solicitado: ${{ solicitud.monto_solicitado ? Number(solicitud.monto_solicitado).toLocaleString('es-MX', {minimumFractionDigits:2}) : 'No especificado' }}
                            </p>
                            <p v-if="form.errors.monto_otorgado" class="text-red-600 text-xs mt-1">{{ form.errors.monto_otorgado }}</p>
                        </div>
                        <div>
                            <label :class="labelCls">Plazo (meses)</label>
                            <select v-model="form.plazo_meses" required :class="inputCls">
                                <option v-for="p in [6,12,18,24,30,36,48,60]" :key="p" :value="p">{{ p }} meses</option>
                            </select>
                        </div>
                        <div class="sm:col-span-2">
                            <label :class="labelCls">Fecha de Entrega del Crédito</label>
                            <input v-model="form.fecha_entrega" type="date" required :class="inputCls" />
                            <p class="text-xs text-slate-400 mt-1">La tabla de amortización se generará a partir de esta fecha.</p>
                        </div>
                    </div>
                </div>

                <!-- Botón submit -->
                <div class="flex justify-end gap-4">
                    <Link :href="route('solicitudes.show', solicitud.id)"
                        class="px-5 py-3 rounded-xl bg-slate-100 dark:bg-zinc-800 text-slate-700 dark:text-zinc-300 font-bold text-sm hover:bg-slate-200 dark:hover:bg-zinc-700 transition-all">
                        Cancelar
                    </Link>
                    <button type="submit" :disabled="form.processing"
                        class="inline-flex items-center gap-2 px-8 py-3 bg-red-700 hover:bg-red-800 text-white font-black rounded-xl text-sm transition-all shadow-lg shadow-red-900/20 disabled:opacity-60 active:scale-[0.98]">
                        <Sparkles size="16" />
                        {{ form.processing ? 'Registrando...' : 'Registrar Crédito y Generar Amortización' }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
