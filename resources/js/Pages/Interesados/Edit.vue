<script setup>
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { 
    User, Phone, Mail, Briefcase, Save, ArrowLeft,
    CheckCircle2, Info, Trash2, MapPin, DollarSign
} from 'lucide-vue-next';

const props = defineProps({
    interesado: Object
});

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

const girosComerciales = [
    "Alimentos y Bebidas",
    "Artesanías",
    "Comercio al por menor",
    "Servicios Profesionales",
    "Tecnología e Innovación",
    "Textil y Moda",
    "Turismo",
    "Salud y Belleza",
    "Construcción / Carpintería",
    "Agroindustria",
    "Otro"
];

const destinosCredito = [
    "Maquinaria y Equipo",
    "Herramientas de Trabajo",
    "Materia Prima e Insumos",
    "Remodelación de Local",
    "Mobiliario de Oficina/Venta",
    "Certificaciones y Registros",
    "Equipamiento Tecnológico",
    "Marketing y Publicidad",
    "Capital de Trabajo"
];
const personalIYEM = [
    "Lizbeth Carrasco",
    "Yuliana Villalobos",
    "Cecilia Escalante",
    
];

const form = useForm({
    medio_ingreso: props.interesado.medio_ingreso,
    nombre_ciudadano: props.interesado.nombre_ciudadano,
    empresa: props.interesado.empresa,
    correo_electronico: props.interesado.correo_electronico,
    telefono: props.interesado.telefono,
    sexo: props.interesado.sexo,
    municipio: props.interesado.municipio,
    fecha_nacimiento: props.interesado.fecha_nacimiento,
    mayahablante: !!props.interesado.mayahablante,
    discapacidad: !!props.interesado.discapacidad,
    giro_comercial: props.interesado.giro_comercial,
    alta_sat: !!props.interesado.alta_sat,
    destino_credito: props.interesado.destino_credito,
    modalidad: props.interesado.modalidad,
    atendio: props.interesado.atendio,
    seguimiento: props.interesado.seguimiento,
    estatus: props.interesado.estatus,
});

const submit = () => {
    form.put(route('interesados.update', props.interesado.id));
};

const confirmDelete = () => {
    if (confirm('¿Estás seguro de que deseas eliminar este registro? Esta acción no se puede deshacer.')) {
        form.delete(route('interesados.destroy', props.interesado.id));
    }
};

const inputClass = "w-full px-4 py-3 rounded-xl border-0 bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-slate-100 ring-1 ring-inset ring-slate-200 dark:ring-slate-700 focus:ring-2 focus:ring-inset focus:ring-red-600 transition-all duration-200";
const labelClass = "block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2 ml-1 uppercase tracking-tight";
</script>

<template>
    <Head :title="'Editar: ' + interesado.nombre_ciudadano" />

    <AppLayout>
        <div class="max-w-7xl mx-auto p-4 md:p-8">
            <div class="flex items-center justify-between mb-10">
                <div class="flex items-center gap-5">
                    <Link :href="route('interesados.index')" class="p-3 rounded-2xl bg-white dark:bg-slate-800 shadow-sm border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400 hover:text-red-600 transition-all">
                        <ArrowLeft :size="24" />
                    </Link>
                    <div>
                        <h1 class="text-3xl font-black text-slate-900 dark:text-white uppercase tracking-tighter">
                            Editar <span class="text-red-600">Interesado</span>
                        </h1>
                        <p class="text-slate-500 dark:text-slate-400 text-sm font-medium">Actualizando expediente de: <b>{{ interesado.nombre_ciudadano }}</b></p>
                    </div>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-10">
                
                <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] p-8 border-2 border-red-100 dark:border-red-900/30 shadow-sm">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                        <div class="flex items-center gap-4">
                            <div class="p-3 bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400 rounded-2xl">
                                <CheckCircle2 :size="24" />
                            </div>
                            <h2 class="text-xl font-bold text-slate-800 dark:text-slate-200 uppercase tracking-tight">Estatus Actual</h2>
                        </div>
                        <div class="w-full md:w-80">
                            <select v-model="form.estatus" :class="[inputClass, 'font-black text-red-600']">
                                <option value="Pendiente">Pendiente</option>
                                <option value="Aprobado">Aprobado</option>
                                <option value="Rechazado">Rechazado</option>
                                <option value="Convertido" disabled>Convertido (Cerrado)</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] p-8 md:p-12 shadow-2xl shadow-slate-200/50 dark:shadow-none border border-slate-100 dark:border-slate-800">
                    <div class="flex items-center gap-4 mb-10 border-b border-slate-100 dark:border-slate-800 pb-6">
                        <div class="p-3 bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400 rounded-2xl">
                            <User :size="24" />
                        </div>
                        <h2 class="text-xl font-bold text-slate-800 dark:text-slate-200">Información del Ciudadano</h2>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                        <div class="lg:col-span-2">
                            <label :class="labelClass">Nombre Completo *</label>
                            <input v-model="form.nombre_ciudadano" type="text" :class="inputClass" required />
                        </div>
                        <div>
                            <label :class="labelClass">Sexo *</label>
                            <select v-model="form.sexo" :class="inputClass" required>
                                <option value="Hombre">Hombre</option>
                                <option value="Mujer">Mujer</option>
                                <option value="Otro">Otro</option>
                            </select>
                        </div>
                        <div>
                            <label :class="labelClass">Municipio *</label>
                            <div class="relative">
                                <MapPin class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400" :size="18" />
                                <select v-model="form.municipio" :class="[inputClass, 'pl-12']" required>
                                    <option v-for="m in municipiosYucatan" :key="m" :value="m">{{ m }}</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label :class="labelClass">Fecha de Nacimiento</label>
                            <input v-model="form.fecha_nacimiento" type="date" :class="inputClass" />
                        </div>
                        <div>
                            <label :class="labelClass">Teléfono *</label>
                            <div class="relative">
                                <Phone class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400" :size="18" />
                                <input v-model="form.telefono" type="tel" :class="[inputClass, 'pl-12']" required />
                            </div>
                        </div>
                        <div class="lg:col-span-3">
                            <label :class="labelClass">Correo Electrónico</label>
                            <div class="relative">
                                <Mail class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400" :size="18" />
                                <input v-model="form.correo_electronico" type="email" :class="[inputClass, 'pl-12']" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] p-8 md:p-12 shadow-2xl shadow-slate-200/50 dark:shadow-none border border-slate-100 dark:border-slate-800">
                    <div class="flex items-center gap-4 mb-10 border-b border-slate-100 dark:border-slate-800 pb-6">
                        <div class="p-3 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 rounded-2xl">
                            <Briefcase :size="24" />
                        </div>
                        <h2 class="text-xl font-bold text-slate-800 dark:text-slate-200">Detalles del Proyecto</h2>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                        <div class="lg:col-span-2">
                            <label :class="labelClass">Modalidad Solicitada *</label>
                            <select v-model="form.modalidad" :class="inputClass" required>
                                <option value="Artesanal">Artesanal (0% Interés)</option>
                                <option value="Sustentable">Sustentable (5% Interés)</option>
                                <option value="Emprendedores">Emprendedores (7% Interés)</option>
                            </select>
                        </div>
                        <div>
                            <label :class="labelClass">Medio de Ingreso *</label>
                            <select v-model="form.medio_ingreso" :class="inputClass" required>
                                <option value="Oficina">Oficina</option>
                                <option value="Teléfono">Teléfono</option>
                                <option value="Correo">Correo</option>
                                <option value="Redes Sociales">Redes Sociales</option>
                                <option value="Otro">Otro</option>
                            </select>
                        </div>
                        <div class="lg:col-span-2">
                            <label :class="labelClass">Nombre de la Empresa</label>
                            <input v-model="form.empresa" type="text" :class="inputClass" />
                        </div>
                        <div>
                            <label :class="labelClass">Giro Comercial *</label>
                            <select v-model="form.giro_comercial" :class="inputClass" required>
                                <option v-for="g in girosComerciales" :key="g" :value="g">{{ g }}</option>
                            </select>
                        </div>
                        <div class="lg:col-span-3">
                            <label :class="labelClass">Destino del Crédito *</label>
                            <div class="relative">
                                <DollarSign class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400" :size="18" />
                                <select v-model="form.destino_credito" :class="[inputClass, 'pl-12']" required>
                                    <option v-for="d in destinosCredito" :key="d" :value="d">{{ d }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="lg:col-span-3 flex flex-wrap justify-center items-center gap-12 bg-slate-50 dark:bg-slate-800/50 p-8 rounded-3xl border border-slate-100 dark:border-slate-800">
                            <label class="flex items-center gap-3 cursor-pointer group">
                                <input v-model="form.mayahablante" type="checkbox" class="w-6 h-6 rounded text-red-600 focus:ring-red-500" />
                                <span class="text-sm font-bold text-slate-600 uppercase tracking-widest">Mayahablante</span>
                            </label>
                            <label class="flex items-center gap-3 cursor-pointer group">
                                <input v-model="form.discapacidad" type="checkbox" class="w-6 h-6 rounded text-red-600 focus:ring-red-500" />
                                <span class="text-sm font-bold text-slate-600 uppercase tracking-widest">Discapacidad</span>
                            </label>
                            <label class="flex items-center gap-3 cursor-pointer group">
                                <input v-model="form.alta_sat" type="checkbox" class="w-6 h-6 rounded text-red-600 focus:ring-red-500" />
                                <span class="text-sm font-bold text-slate-600 uppercase tracking-widest">Alta en SAT</span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="bg-red-900 dark:bg-red-950 rounded-[2.5rem] p-8 md:p-12 text-white shadow-2xl">
                    <div class="flex items-center gap-4 mb-8 border-b border-red-800/50 pb-6">
                        <div class="p-3 bg-white/10 rounded-2xl">
                            <Info :size="24" class="text-white" />
                        </div>
                        <h2 class="text-xl font-bold tracking-tight">Control y Seguimiento</h2>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                        <div>
                            <label class="block text-[10px] font-black uppercase mb-3 opacity-80 tracking-widest">Atendió</label>
                            <select v-model="form.atendio" class="w-full bg-red-800/40 border-none rounded-2xl py-4 px-5 text-white focus:ring-2 focus:ring-white" required>
                                <option v-for="p in personalIYEM" :key="p" :value="p" class="text-slate-900">{{ p }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-black uppercase mb-3 opacity-80 tracking-widest">Seguimiento</label>
                            <select v-model="form.seguimiento" class="w-full bg-red-800/40 border-none rounded-2xl py-4 px-5 text-white focus:ring-2 focus:ring-white" required>
                                <option v-for="p in personalIYEM" :key="p" :value="p" class="text-slate-900">{{ p }}</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-between pb-12">
                    <button type="button" @click="confirmDelete" class="flex items-center gap-2 text-red-500 hover:text-red-700 font-black text-xs uppercase tracking-[0.2em] transition-all">
                        <Trash2 :size="18" /> Eliminar Expediente
                    </button>

                    <div class="flex items-center gap-6">
                        <Link :href="route('interesados.index')" class="text-sm font-black text-slate-400 hover:text-slate-600 uppercase tracking-widest">
                            Cancelar
                        </Link>
                        <button type="submit" :disabled="form.processing" class="bg-red-600 hover:bg-red-700 disabled:opacity-50 text-white px-12 py-5 rounded-2xl font-black flex items-center gap-3 transition-all shadow-xl shadow-red-200 uppercase tracking-[0.2em] text-xs">
                            <Save :size="20" v-if="!form.processing" />
                            <span>{{ form.processing ? 'Actualizando...' : 'Guardar Cambios' }}</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </AppLayout>
</template>