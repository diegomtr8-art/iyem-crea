<script setup>
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import { useForm, Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import {
    User, CreditCard, Calendar, DollarSign,
    CheckCircle2, AlertCircle, Building2, Wallet,
    Mail, Phone, IdCard, MapPin, FileSignature, UserPlus
} from 'lucide-vue-next';
import { regimenesFiscales } from '../portal/wizard/wizardCatalogos';

const props = defineProps({
    acreditado: Object,
    regimenes_sat: Array,
    solicitud: Object,
    credito: Object,
});

// --- LÓGICA PARA CAPTURAR EL ID DE LA URL ---
const params = new URLSearchParams(window.location.search);
const idUrl = params.get('interesado_id');

const municipiosYucatan = [
    "Abalá", "Acanceh", "Akil", "Baca", "Bokobá", "Buctzotz", "Cacalchén", "Calotmul", "Cansahcab", "Cantamayec",
    "Celestún", "Cenotillo", "Conkal", "Cuncunul", "Cuzamá", "Chacsinkín", "Chankom", "Chapab", "Chemax", "Chicxulub Pueblo",
    "Chichimilá", "Chikindzonot", "Chocholá", "Chumayel", "Dzan", "Dzemul", "Dzidzantún", "Dzilam de Bravo", "Dzilam González", "Dzitás",
    "Dzoncauich", "Espita", "Halachó", "Hocabá", "Hoctún", "Homún", "Huhí", "Hunucmá", "Ixil", "Izamal",
    "Kanasín", "Kantunil", "Kaua", "Kinchil", "Kopomá", "Mama", "Maní", "Maxcanú", "Mayapán", "Mérida",
    "Mocochá", "Motul", "Muna", "Muxupip", "Opichén", "Oxkutzcab", "Panabá", "Peto", "Progreso", "Quintana Roo",
    "Río Lagartos", "Sacalum", "Samahil", "Sanahcat", "San Felipe", "Santa Elena", "Seyé", "Sinanché", "Sotuta", "Sucilá",
    "Sudzal", "Suma de Hidalgo", "Tahdziú", "Tahmek", "Teabo", "Tecoh", "Tekal de Venegas", "Tekantó", "Tekax", "Tekit",
    "Tekom", "Telchac Pueblo", "Telchac Puerto", "Temax", "Temozón", "Tepakán", "Tetiz", "Teya", "Ticul", "Timucuy",
    "Tinum", "Tixcacalcupul", "Tixkokob", "Tixméhuac", "Tixpéhual", "Tizimín", "Tunkás", "Tzucacab", "Uayma", "Ucú",
    "Umán", "Valladolid", "Xocchel", "Yaxcabá", "Yaxkukul", "Yobaín"
];

const form = useForm({
    // Aquí está el cambio clave: Priorizamos el ID de la URL
    interesado_id: props.acreditado.interesado_id,
    nombre_completo: props.acreditado.nombre_completo,
    curp: props.acreditado.curp,
    rfc: props.acreditado.rfc,
    correo: props.acreditado.correo,
    municipio: props.acreditado.municipio,
    sexo: props.acreditado.sexo,
    domicilio_fiscal: props.acreditado.direccion_fiscal,
    regimen_fiscal: props.acreditado.regimen,
    clave_pago: props.acreditado.clave_personalizada,
    telefono: props.solicitud.telefono,
});

    // Se quita la seleccion de modalidad, debido a que ese campo no se utiliza en el formulario

const submit = () => {
    form.put(route('acreditados.update', props.acreditado.id), {
        preserveScroll: true,
    });
};

// Clases reutilizables para consistencia visual
const inp = 'w-full rounded-2xl border border-slate-200 dark:border-slate-700 dark:bg-slate-950 dark:text-white py-2.5 px-4 text-sm focus:ring-2 focus:ring-red-500/30 focus:border-red-500 transition-all';
const inpIcon = 'w-full rounded-2xl border border-slate-200 dark:border-slate-700 dark:bg-slate-950 dark:text-white py-2.5 pl-10 pr-4 text-sm focus:ring-2 focus:ring-red-500/30 focus:border-red-500 transition-all';
const lbl = 'block text-xs font-black text-slate-500 dark:text-zinc-400 uppercase mb-2 ml-1 tracking-wide';
</script>

<template>
    <Head title="Editar Acreditado" />

    <AppLayout>
        <div class="max-w-5xl mx-auto py-8 sm:py-10 px-4 sm:px-6 lg:px-8">
            <div class="mb-8 sm:mb-10 flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-red-700 flex items-center justify-center text-white shadow-lg shadow-red-900/20 shrink-0">
                    <UserPlus :size="22" />
                </div>
                <div>
                    <h1 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Edición datos de Acreditado</h1>
                    <p class="text-slate-500 dark:text-slate-400 font-medium text-sm mt-0.5">
                        {{ props.nombre ? 'Formalizando a: ' + props.nombre : 'Capture la información para editar el expediente.' }}
                    </p>
                </div>
            </div>

            <div v-if="Object.keys(form.errors).length > 0" class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-2xl flex items-center gap-3 text-red-700 dark:text-red-400 shadow-sm">
                <AlertCircle :size="20" class="shrink-0" />
                <span class="text-sm font-bold">Por favor, revise los campos marcados en rojo.</span>
            </div>

            <form @submit.prevent="submit" class="space-y-6">

                <!-- Paso 1: Beneficiario -->
                <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-slate-200 dark:border-slate-800 overflow-hidden">
                    <div class="px-6 sm:px-8 py-5 bg-slate-50 dark:bg-slate-800/50 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                        <h2 class="font-bold text-slate-800 dark:text-slate-200 flex items-center gap-2 uppercase text-sm tracking-wider">
                            <User class="text-red-600" :size="18" />
                            Información del Beneficiario
                        </h2>
                        <span class="text-[10px] font-black bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-400 px-2.5 py-1 rounded-full uppercase shrink-0">Paso 1</span>
                    </div>

                    <div class="p-6 sm:p-8 grid grid-cols-1 md:grid-cols-3 gap-x-6 gap-y-5">
                        <div class="md:col-span-2">
                            <label :class="lbl">Nombre Completo</label>
                            <input v-model="form.nombre_completo" type="text"
                                :class="[inp, form.errors.nombre_completo ? '!border-red-500 ring-1 ring-red-500' : '']" />
                            <p v-if="form.errors.nombre_completo" class="text-red-500 text-[10px] mt-1 font-bold uppercase">{{ form.errors.nombre_completo }}</p>
                        </div>

                        <div>
                            <label :class="lbl">Municipio</label>
                            <div class="relative">
                                <MapPin class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none" :size="16" />
                                <select v-model="form.municipio"
                                        :class="[inpIcon, 'appearance-none', form.errors.municipio ? '!border-red-500 ring-1 ring-red-500' : '']">
                                    <option value="" disabled>Seleccione...</option>
                                    <option v-for="muni in municipiosYucatan" :key="muni" :value="muni">{{ muni }}</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label :class="lbl">RFC</label>
                            <div class="relative">
                                <IdCard class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none" :size="16" />
                                <input v-model="form.rfc" type="text" maxlength="13" @input="form.rfc = form.rfc.toUpperCase()"
                                    :class="[inpIcon, 'font-mono']" />
                            </div>
                        </div>

                        <div>
                            <label :class="lbl">CURP</label>
                            <div class="relative">
                                <IdCard class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none" :size="16" />
                                <input v-model="form.curp" type="text" maxlength="18" @input="form.curp = form.curp.toUpperCase()"
                                    :class="[inpIcon, 'font-mono']" />
                            </div>
                        </div>

                        <div class="bg-slate-50 dark:bg-slate-800/30 p-4 rounded-2xl border border-slate-100 dark:border-slate-800">
                            <label class="block text-[10px] font-black text-slate-400 uppercase mb-3 tracking-wide">Género</label>
                            <div class="flex gap-8">
                                <label class="flex items-center gap-2 cursor-pointer text-slate-700 dark:text-slate-300 font-bold text-sm">
                                    <input type="radio" v-model="form.sexo" value="H" class="text-red-600 focus:ring-red-500 dark:bg-slate-950 border-slate-300" />
                                    Hombre
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer text-slate-700 dark:text-slate-300 font-bold text-sm">
                                    <input type="radio" v-model="form.sexo" value="M" class="text-red-600 focus:ring-red-500 dark:bg-slate-950 border-slate-300" />
                                    Mujer
                                </label>
                            </div>
                        </div>

                        <div>
                            <label :class="lbl">Teléfono</label>
                            <div class="relative">
                                <Phone class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none" :size="16" />
                                <input v-model="form.telefono" type="text" :class="inpIcon" />
                            </div>
                        </div>

                        <div class="md:col-span-2">
                            <label :class="lbl">Correo Electrónico</label>
                            <div class="relative">
                                <Mail class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none" :size="16" />
                                <input v-model="form.correo" type="email" :class="inpIcon" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Paso 2: Fiscal y pago -->
                <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-slate-200 dark:border-slate-800 overflow-hidden">
                    <div class="px-6 sm:px-8 py-5 bg-slate-50 dark:bg-slate-800/50 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                        <h2 class="font-bold text-slate-800 dark:text-slate-200 flex items-center gap-2 uppercase text-sm tracking-wider">
                            <Building2 class="text-red-600" :size="18" />
                            Información Fiscal y de Pago
                        </h2>
                        <span class="text-[10px] font-black bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-400 px-2.5 py-1 rounded-full uppercase shrink-0">Paso 2</span>
                    </div>

                    <div class="p-6 sm:p-8 grid grid-cols-1 md:grid-cols-3 gap-x-6 gap-y-5">
                        <div class="md:col-span-2">
                            <label :class="lbl">Domicilio Fiscal</label>
                            <input v-model="form.domicilio_fiscal" type="text" placeholder="Calle, Número, Colonia y CP" :class="inp" />
                        </div>

                        <div>
                            <label :class="lbl">Régimen Fiscal (SAT)</label>
                            <select v-model="form.regimen_fiscal"
                                    :class="[inp, 'appearance-none', form.errors.regimen_fiscal ? '!border-red-500 ring-1 ring-red-500' : '']">
                                <option value="" disabled>Seleccione régimen...</option>
                                <option v-for="reg in props.regimenes_sat" :key="reg.id" :value="reg.id">
                                    {{ reg.id }} - {{ reg.nombre }}
                                </option>
                            </select>
                            <p v-if="form.errors.regimen_fiscal" class="text-red-500 text-[10px] mt-1 font-bold uppercase">{{ form.errors.regimen_fiscal }}</p>
                            <p v-else class="text-[10px] text-slate-400 mt-1">{{ props.regimenes_sat?.length ?? 0 }} regímenes disponibles</p>
                        </div>

                        <div class="md:col-span-3">
                            <label :class="lbl">Clave de Pago</label>
                            <div class="relative">
                                <Wallet class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none" :size="16" />
                                <input v-model="form.clave_pago" type="text" placeholder="Clave Personalizada de Pago" :class="inpIcon" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Se quita el paso 3 "Detalles de financiamiento" ya que no debería ser editable-->

                <!-- Resumen + envío -->
                <div class="p-5 sm:p-2 bg-slate-900 dark:bg-red-950 rounded-3xl sm:rounded-[2.5rem] shadow-2xl border-4 border-white dark:border-slate-900 space-y-4 sm:space-y-0">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div class="flex items-center gap-6 sm:pl-6">
                            <div>
                                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Tasa Ordinaria</p>
                                <p class="text-xl sm:text-2xl font-black text-white leading-none mt-1">
                                    {{ infoModalidad ? infoModalidad.tasa : '--' }}<span class="text-red-500 text-sm">%</span>
                                </p>
                            </div>
                            <div class="h-8 w-px bg-slate-700"></div>
                            <div>
                                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Tasa Moratoria</p>
                                <p class="text-xl sm:text-2xl font-black text-white leading-none mt-1">
                                    {{ infoModalidad ? infoModalidad.moratoria : '--' }}<span class="text-red-500 text-sm">%</span>
                                </p>
                            </div>
                            <div class="h-8 w-px bg-slate-700 hidden sm:block"></div>
                            <div class="hidden sm:block">
                                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Estado</p>
                                <p class="text-xs font-bold text-emerald-400 flex items-center gap-1 uppercase mt-1">
                                    <CheckCircle2 :size="12" /> Expediente Activo
                                </p>
                            </div>
                        </div>

                        <div class="flex gap-2">
                            <button type="button" @click="$window.history.back()"
                                    class="flex-1 sm:flex-none px-6 py-3.5 text-xs font-black text-slate-400 hover:text-white uppercase transition-colors">
                                Atrás
                            </button>
                            <button type="submit" :disabled="form.processing"
                                    class="flex-1 sm:flex-none px-10 py-3.5 bg-red-600 hover:bg-red-500 text-white text-xs font-black rounded-2xl sm:rounded-[2rem] shadow-xl transition-all active:scale-95 disabled:opacity-50 flex items-center justify-center gap-2 uppercase">
                                <span v-if="form.processing">Guardando...</span>
                                <span v-else>Guardar cambios</span>
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

<style scoped>
input::-webkit-outer-spin-button,
input::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}
</style>
