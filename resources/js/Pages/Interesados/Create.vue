<script setup>
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
// Asegúrate de incluir 'useForm' dentro de las llaves
import { Head, useForm, Link } from '@inertiajs/vue3';
import { 
    User, 
    Phone, 
    Mail, 
    MapPin, 
    Briefcase, 
    Save, 
    ArrowLeft,
    CheckCircle2,
    Info
} from 'lucide-vue-next';

const form = useForm({
    medio_ingreso: '',
    nombre_ciudadano: '',
    empresa: '',
    correo_electronico: '',
    telefono: '',
    sexo: '',
    municipio: '',
    fecha_nacimiento: '', // <--- Agregar
    mayahablante: false,
    discapacidad: false,
    giro_comercial: '',   // <--- Agregar
    alta_sat: false,
    destino_credito: '',  // <--- Agregar
    modalidad: '',
    atendio: '',
    seguimiento: '',
    estatus: 'Pendiente', // <--- Agregar valor inicial
});

const submit = () => {
    form.post(route('interesados.store'), {
        onFinish: () => form.reset('password'),
    });
};

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

const inputClass = "w-full px-4 py-3 rounded-xl border-0 bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-slate-100 ring-1 ring-inset ring-slate-200 dark:ring-slate-700 focus:ring-2 focus:ring-inset focus:ring-red-600 transition-all duration-200";
const labelClass = "block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2 ml-1 uppercase tracking-tight";
</script>

<template>
    <Head title="Nuevo Interesado" />

    <AppLayout>
        <div class="max-w-7xl mx-auto p-4 md:p-8">
            <div class="flex items-center justify-between mb-10">
                <div class="flex items-center gap-5">
                    <Link :href="route('interesados.index')" class="p-3 rounded-2xl bg-white dark:bg-slate-800 shadow-sm border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400 hover:text-red-600 transition-all">
                        <ArrowLeft :size="24" />
                    </Link>
                    <div>
                        <h1 class="text-3xl font-black text-slate-900 dark:text-white uppercase tracking-tighter">
    Nuevo <span class="text-red-600">Interesado</span>
</h1>
                        <p class="text-slate-500 dark:text-slate-400 text-sm font-medium">Captura de prospecto para el programa CREA</p>
                    </div>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-10">
                
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
                            <input v-model="form.nombre_ciudadano" type="text" :class="inputClass" placeholder="Ej. Juan Pérez López" required />
                        </div>
                        <div>
                            <label :class="labelClass">Sexo *</label>
                            <select v-model="form.sexo" :class="inputClass" required>
                                <option value="">Seleccione...</option>
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
                                    <option value="" disabled>Seleccione municipio...</option>
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
                                <input v-model="form.telefono" type="tel" :class="[inputClass, 'pl-12']" placeholder="999 000 0000" required />
                            </div>
                        </div>

                        <div class="lg:col-span-3   ">
                            <label :class="labelClass">Correo Electrónico</label>
                            <div class="relative">
                                <Mail class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400" :size="18" />
                                <input v-model="form.correo_electronico" type="email" :class="[inputClass, 'pl-12']" placeholder="usuario@ejemplo.com" />
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
                                <option value="">Seleccione modalidad...</option>
                                <option value="Artesanal">Artesanal (0% Interés)</option>
                                <option value="Sustentable">Sustentable (5% Interés)</option>
                                <option value="Emprendedores">Emprendedores (7% Interés)</option>
                            </select>
                        </div>
                        <div>
                            <label :class="labelClass">Medio de Ingreso *</label>
                            <select v-model="form.medio_ingreso" :class="inputClass" required>
                                <option value="">¿Cómo se enteró?</option>
                                <option value="Oficina">Oficina</option>
                                <option value="Teléfono">Teléfono</option>
                                <option value="Correo">Correo</option>
                                <option value="Redes Sociales">Redes Sociales</option>
                                <option value="Otro">Otro</option>
                            </select>
                        </div>

                        <div class="lg:col-span-2">
                            <label :class="labelClass">Nombre de la Empresa / Negocio</label>
                            <input v-model="form.empresa" type="text" :class="inputClass" placeholder="Nombre del negocio o actividad" />
                        </div>
                        <div>
                            <label :class="labelClass">Giro Comercial *</label>
                            <div class="relative">
                                <Briefcase class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400" :size="18" />
                                <select v-model="form.giro_comercial" :class="[inputClass, 'pl-12']" required>
                                    <option value="" disabled>Seleccione giro...</option>
                                    <option v-for="g in girosComerciales" :key="g" :value="g">{{ g }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="lg:col-span-3">
                            <label :class="labelClass">Destino del Crédito *</label>
                            <div class="relative">
                                <DollarSign class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400" :size="18" />
                                <select v-model="form.destino_credito" :class="[inputClass, 'pl-12']" required>
                                    <option value="" disabled>¿En qué se invertirá el recurso?</option>
                                    <option v-for="d in destinosCredito" :key="d" :value="d">{{ d }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="lg:col-span-3 flex flex-wrap justify-center items-center gap-12 bg-slate-50 dark:bg-slate-800/50 p-8 rounded-3xl border border-slate-100 dark:border-slate-800">
                            <label class="flex items-center gap-3 cursor-pointer group">
                                <input v-model="form.mayahablante" type="checkbox" class="w-6 h-6 rounded border-slate-300 text-red-600 focus:ring-red-500" />
                                <span class="text-sm font-bold text-slate-600 dark:text-slate-400 group-hover:text-red-600 transition-colors uppercase tracking-widest">Mayahablante</span>
                            </label>
                            <label class="flex items-center gap-3 cursor-pointer group">
                                <input v-model="form.discapacidad" type="checkbox" class="w-6 h-6 rounded border-slate-300 text-red-600 focus:ring-red-500" />
                                <span class="text-sm font-bold text-slate-600 dark:text-slate-400 group-hover:text-red-600 transition-colors uppercase tracking-widest">Discapacidad</span>
                            </label>
                            <label class="flex items-center gap-3 cursor-pointer group">
                                <input v-model="form.alta_sat" type="checkbox" class="w-6 h-6 rounded border-slate-300 text-red-600 focus:ring-red-500" />
                                <span class="text-sm font-bold text-slate-600 dark:text-slate-400 group-hover:text-red-600 transition-colors uppercase tracking-widest">Alta en SAT</span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="bg-red-900 dark:bg-red-950 rounded-[2.5rem] p-8 md:p-12 text-white shadow-2xl shadow-red-900/20">
                    <div class="flex items-center gap-4 mb-8 border-b border-red-800/50 pb-6">
                        <div class="p-3 bg-white/10 rounded-2xl">
                            <Info :size="24" class="text-white" />
                        </div>
                        <h2 class="text-xl font-bold tracking-tight">Control y Seguimiento Interno</h2>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                        <div>
                            <label class="block text-[10px] font-black uppercase mb-3 opacity-80 tracking-[0.15em] ml-1">Personal que atendió</label>
                            <select v-model="form.atendio" class="w-full bg-red-800/40 border-none rounded-2xl py-4 px-5 text-white focus:ring-2 focus:ring-white transition-all appearance-none cursor-pointer" required>
                                <option value="" class="text-red-900">Seleccione empleado...</option>
                                <option v-for="p in personalIYEM" :key="p" :value="p" class="text-slate-900">{{ p }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-black uppercase mb-3 opacity-80 tracking-[0.15em] ml-1">Responsable de Seguimiento</label>
                            <select v-model="form.seguimiento" class="w-full bg-red-800/40 border-none rounded-2xl py-4 px-5 text-white focus:ring-2 focus:ring-white transition-all appearance-none cursor-pointer" required>
                                <option value="" class="text-red-900">Seleccione responsable...</option>
                                <option v-for="p in personalIYEM" :key="p" :value="p" class="text-slate-900">{{ p }}</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-6 pb-12">
                    <Link :href="route('interesados.index')" class="text-sm font-black text-slate-400 hover:text-slate-600 transition-all uppercase tracking-widest">
                        Cancelar
                    </Link>
                    <button type="submit" :disabled="form.processing" class="bg-red-600 hover:bg-red-700 disabled:opacity-50 text-white px-12 py-5 rounded-2xl font-black flex items-center gap-3 transition-all shadow-xl shadow-red-200 dark:shadow-none uppercase tracking-[0.2em] text-xs">
                        <Save :size="20" v-if="!form.processing" />
                        <span>{{ form.processing ? 'Guardando...' : 'Finalizar Registro' }}</span>
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>