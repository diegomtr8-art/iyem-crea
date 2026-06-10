<script setup>
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import { useForm, Head } from '@inertiajs/vue3';
import { computed, onMounted } from 'vue'; // Añadimos onMounted para debugging
import { 
    User, CreditCard, Calendar, DollarSign, 
    CheckCircle2, AlertCircle, Building2, Wallet, 
    Mail, Phone
} from 'lucide-vue-next';

const props = defineProps({
    modalidades: Array,
    regimenes_sat: Array,
    nombre: String,
    municipio: String,
    modalidad: String,
    sexo: String,
    telefono: String,
    correo: String,
    interesado_id: [Number, String],
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
    interesado_id: idUrl || props.interesado_id || null, 
    nombre_completo: props.nombre || '',
    municipio: props.municipio || '',
    sexo: props.sexo === 'M' || props.sexo === 'Mujer' ? 'M' : 'H',
    telefono: props.telefono || '',
    correo: props.correo || '',
    rfc: '',
    curp: '',
    domicilio_fiscal: '',
    regimen_fiscal: '',
    clave_pago: '',
    modalidad_id: props.modalidades.find(m => m.nombre === props.modalidad)?.id || '',
    monto_otorgado: '',
    plazo_meses: 12,
    fecha_entrega: new Date().toISOString().substr(0, 10),
    clave_contrato: '',
});

const infoModalidad = computed(() => {
    const seleccionada = props.modalidades.find(m => m.id == form.modalidad_id);
    if (!seleccionada) return null;
    
    let datos = { min: 0, max: 0, tasa: 0, moratoria: 0 };

    if (seleccionada.nombre.includes('Emprendedores')) {
        datos = { min: 50001, max: 150000, tasa: 7, moratoria: 17.5 };
    } else if (seleccionada.nombre.includes('Sustentable') || seleccionada.nombre.includes('Fortalecimiento')) {
        datos = { min: 150001, max: 500000, tasa: 5, moratoria: 12.5 };
    } else if (seleccionada.nombre.includes('Artesanal')) {
        datos = { min: 5000, max: 50000, tasa: 0, moratoria: 0 };
    }

    return { ...seleccionada, ...datos };
});

const submit = () => {
    form.post(route('acreditados.store'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Formalizar Acreditado" />

    <AppLayout>
        <div class="max-w-5xl mx-auto py-10 px-4 sm:px-6 lg:px-8">
            <div class="mb-10 flex items-center justify-between">
                <div class="border-l-4 border-red-600 pl-5">
                    <h1 class="text-3xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Registro de Acreditado</h1>
                    <p class="text-slate-500 dark:text-slate-400 font-medium">
                        {{ props.nombre ? 'Formalizando a: ' + props.nombre : 'Capture la información para generar el nuevo expediente.' }}
                    </p>
                </div>
            </div>

            <div v-if="Object.keys(form.errors).length > 0" class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl flex items-center gap-3 text-red-700 dark:text-red-400 shadow-sm">
                <AlertCircle :size="20" />
                <span class="text-sm font-bold">Por favor, revise los campos marcados en rojo.</span>
            </div>

            <form @submit.prevent="submit" class="space-y-8">
                
                <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-xl shadow-slate-200/50 dark:shadow-none border border-slate-200 dark:border-slate-800 overflow-hidden">
                    <div class="px-8 py-5 bg-slate-50 dark:bg-slate-800/50 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                        <h2 class="font-bold text-slate-800 dark:text-slate-200 flex items-center gap-2 uppercase text-sm tracking-wider">
                            <User class="text-red-600" :size="18" />
                            Información del Beneficiario
                        </h2>
                        <span class="text-[10px] font-black bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-400 px-2 py-1 rounded-md uppercase">Paso 1</span>
                    </div>

                    <div class="p-8 grid grid-cols-1 md:grid-cols-3 gap-x-8 gap-y-6">
                        <div class="md:col-span-2">
                            <label class="block text-xs font-black text-slate-500 uppercase mb-2 ml-1">Nombre Completo</label>
                            <input v-model="form.nombre_completo" type="text" 
                                :class="{'border-red-500 ring-red-500': form.errors.nombre_completo}"
                                class="w-full rounded-2xl border-slate-200 dark:border-slate-700 dark:bg-slate-950 dark:text-white focus:ring-red-500 focus:border-red-500 transition-all" />
                        </div>

                        <div>
                            <label class="block text-xs font-black text-slate-500 uppercase mb-2 ml-1">Municipio</label>
                            <select v-model="form.municipio" 
                                    :class="{'border-red-500': form.errors.municipio}"
                                    class="w-full rounded-2xl border-slate-200 dark:border-slate-700 dark:bg-slate-950 dark:text-white focus:ring-red-500">
                                <option value="" disabled>Seleccione...</option>
                                <option v-for="muni in municipiosYucatan" :key="muni" :value="muni">{{ muni }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-black text-slate-500 uppercase mb-2 ml-1">RFC</label>
                            <input v-model="form.rfc" type="text" maxlength="13" @input="form.rfc = form.rfc.toUpperCase()"
                                class="w-full rounded-2xl border-slate-200 dark:border-slate-700 dark:bg-slate-950 dark:text-white font-mono" />
                        </div>

                        <div>
                            <label class="block text-xs font-black text-slate-500 uppercase mb-2 ml-1">CURP</label>
                            <input v-model="form.curp" type="text" maxlength="18" @input="form.curp = form.curp.toUpperCase()"
                                class="w-full rounded-2xl border-slate-200 dark:border-slate-700 dark:bg-slate-950 dark:text-white font-mono" />
                        </div>

                        <div class="bg-slate-50 dark:bg-slate-800/30 p-4 rounded-2xl border border-slate-100 dark:border-slate-800">
                            <label class="block text-[10px] font-black text-slate-400 uppercase mb-3">Género</label>
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
                            <label class="block text-xs font-black text-slate-500 uppercase mb-2 ml-1">Teléfono</label>
                            <div class="relative">
                                <Phone class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400" :size="16" />
                                <input v-model="form.telefono" type="text" class="w-full pl-10 rounded-2xl border-slate-200 dark:border-slate-700 dark:bg-slate-950 dark:text-white" />
                            </div>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-xs font-black text-slate-500 uppercase mb-2 ml-1">Correo Electrónico</label>
                            <div class="relative">
                                <Mail class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400" :size="16" />
                                <input v-model="form.correo" type="email" class="w-full pl-10 rounded-2xl border-slate-200 dark:border-slate-700 dark:bg-slate-950 dark:text-white" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-xl shadow-slate-200/50 dark:shadow-none border border-slate-200 dark:border-slate-800 overflow-hidden">
                    <div class="px-8 py-5 bg-slate-50 dark:bg-slate-800/50 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                        <h2 class="font-bold text-slate-800 dark:text-slate-200 flex items-center gap-2 uppercase text-sm tracking-wider">
                            <Building2 class="text-red-600" :size="18" />
                            Información Fiscal y de Pago
                        </h2>
                        <span class="text-[10px] font-black bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-400 px-2 py-1 rounded-md uppercase">Paso 2</span>
                    </div>

                    <div class="p-8 grid grid-cols-1 md:grid-cols-3 gap-x-8 gap-y-6">
                        <div class="md:col-span-2">
                            <label class="block text-xs font-black text-slate-500 uppercase mb-2 ml-1">Domicilio Fiscal</label>
                            <input v-model="form.domicilio_fiscal" type="text" placeholder="Calle, Número, Colonia y CP"
                                class="w-full rounded-2xl border-slate-200 dark:border-slate-700 dark:bg-slate-950 dark:text-white" />
                        </div>

                        <div>
                                <label class="block text-xs font-black text-slate-500 uppercase mb-2 ml-1">Régimen Fiscal (SAT)</label>
                                <select v-model="form.regimen_fiscal" 
                                        :class="{'border-red-500': form.errors.regimen_fiscal}"
                                        class="w-full rounded-2xl border-slate-200 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                                    <option value="" disabled>Seleccione régimen...</option>
                                    <option v-for="reg in props.regimenes_sat" :key="reg.id" :value="reg.id">
                                        {{ reg.id }} - {{ reg.nombre }}
                                    </option>
                                </select>
                                <p v-if="form.errors.regimen_fiscal" class="text-red-500 text-[10px] mt-1 font-bold uppercase">{{ form.errors.regimen_fiscal }}</p>
                            </div>

                             <div class="md:col-span-3">
                            <label class="block text-xs font-black text-slate-500 uppercase mb-2 ml-1">Clave de Pago</label>
                            <div class="relative">
                                <Wallet class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400" :size="16" />
                                <input v-model="form.clave_pago" type="text" placeholder="Clave Personalizada de Pago"
                                    class="w-full pl-10 rounded-2xl border-slate-200 dark:border-slate-700 dark:bg-slate-950 dark:text-white" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-xl shadow-slate-200/50 dark:shadow-none border border-slate-200 dark:border-slate-800 overflow-hidden">
                    <div class="px-8 py-5 bg-slate-50 dark:bg-slate-800/50 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                        <h2 class="font-bold text-slate-800 dark:text-slate-200 flex items-center gap-2 uppercase text-sm tracking-wider">
                            <CreditCard class="text-red-600" :size="18" />
                            Detalles del Financiamiento
                        </h2>
                        <span class="text-[10px] font-black bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-400 px-2 py-1 rounded-md uppercase">Paso 3</span>
                    </div>

                    <div class="p-8 grid grid-cols-1 md:grid-cols-4 gap-x-8 gap-y-6">
                        <div class="md:col-span-2">
                            <label class="block text-xs font-black text-slate-500 uppercase mb-2 ml-1">Modalidad de Apoyo</label>
                            <select v-model="form.modalidad_id" 
                                    :class="{'border-red-500': form.errors.modalidad_id}"
                                    class="w-full rounded-2xl border-slate-200 dark:border-slate-700 dark:bg-slate-950 dark:text-white focus:ring-red-500">
                                <option value="" disabled>Seleccione modalidad...</option>
                                <option v-for="mod in modalidades" :key="mod.id" :value="mod.id">{{ mod.nombre }}</option>
                            </select>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-xs font-black text-slate-500 uppercase mb-2 ml-1">Clave de Contrato</label>
                            <input v-model="form.clave_contrato" type="text" placeholder="Ej: CREA-2026-001"
                                class="w-full rounded-2xl border-slate-200 dark:border-slate-700 dark:bg-slate-950 dark:text-white font-mono uppercase" />
                        </div>

                        <div>
                            <label class="block text-xs font-black text-slate-500 uppercase mb-2 ml-1">Monto Otorgado</label>
                            <div class="relative">
                                <DollarSign class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400" :size="16" />
                                <input v-model="form.monto_otorgado" type="number" step="0.01" 
                                    class="w-full pl-10 rounded-2xl border-slate-200 dark:border-slate-700 dark:bg-slate-950 dark:text-white" />
                            </div>
                            <p v-if="infoModalidad" class="text-[9px] font-black text-slate-400 mt-2 uppercase tracking-tighter">
                                Rango Sugerido: ${{ infoModalidad.min.toLocaleString() }} - ${{ infoModalidad.max.toLocaleString() }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-xs font-black text-slate-500 uppercase mb-2 ml-1">Plazo (Meses)</label>
                            <select v-model="form.plazo_meses" class="w-full rounded-2xl border-slate-200 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                                <option :value="12">12 Meses</option>
                                <option :value="18">18 Meses</option>
                                <option :value="24">24 Meses</option>
                            </select>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-xs font-black text-slate-500 uppercase mb-2 ml-1">Fecha de Entrega / Firma</label>
                            <div class="relative">
                                <Calendar class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400" :size="16" />
                                <input v-model="form.fecha_entrega" type="date" 
                                    class="w-full pl-10 rounded-2xl border-slate-200 dark:border-slate-700 dark:bg-slate-950 dark:text-white" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-between p-2 bg-slate-900 dark:bg-red-950 rounded-[2.5rem] shadow-2xl border-4 border-white dark:border-slate-900">
                    <div class="flex items-center gap-6 pl-8">
                        <div class="flex items-center gap-6">
                                <div>
                                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Tasa Ordinaria</p>
                                    <p class="text-2xl font-black text-white leading-none">
                                        {{ infoModalidad ? infoModalidad.tasa : '--' }}<span class="text-red-500 text-sm">%</span>
                                    </p>
                                </div>

                                <div class="h-8 w-px bg-slate-700"></div>

                                <div>
                                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Tasa Moratoria</p>
                                    <p class="text-2xl font-black text-white leading-none">
                                        {{ infoModalidad ? infoModalidad.moratoria : '--' }}<span class="text-red-500 text-sm">%</span>
                                    </p>
                                </div>
                            </div>
                        <div class="h-8 w-px bg-slate-700"></div>
                        <div class="hidden sm:block">
                            <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Estado</p>
                            <p class="text-xs font-bold text-emerald-400 flex items-center gap-1 uppercase">
                                <CheckCircle2 :size="12" /> Expediente Activo
                            </p>
                        </div>
                    </div>

                    <div class="flex gap-2 p-1">
                        <button type="button" @click="$window.history.back()"
                                class="px-6 py-4 text-xs font-black text-slate-400 hover:text-white uppercase transition-colors">
                            Atrás
                        </button>
                        <button type="submit" :disabled="form.processing"
                                class="px-10 py-4 bg-red-600 hover:bg-red-500 text-white text-xs font-black rounded-[2rem] shadow-xl transition-all active:scale-95 disabled:opacity-50 flex items-center gap-2 uppercase">
                            <span v-if="form.processing">Guardando...</span>
                            <span v-else>Finalizar Registro</span>
                        </button>
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