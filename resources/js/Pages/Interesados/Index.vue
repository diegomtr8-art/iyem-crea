<script setup>
import AppLayout from '@/layouts/app/AppSidebarLayout.vue'; 
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { 
    Search, UserPlus, UserCheck, Phone, MapPin, 
    Edit, Filter, X, CheckCircle2 
} from 'lucide-vue-next';
import debounce from 'lodash/debounce';

const props = defineProps({ 
    
    interesados: Object, 
    filters: Object 
});

// 1. Valores seleccionados (Reactivos)
const search = ref(props.filters.search || '');
const municipioSeleccionado = ref(props.filters.municipio || '');
const sexo = ref(props.filters.sexo || '');
const modalidad = ref(props.filters.modalidad || '');
const seguimientoSeleccionado = ref(props.filters.seguimiento || '');
const showFilters = ref(false);

// 2. Listas de opciones
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

const listaSeguimiento = ["Lizbeth Carrasco", "Yuliana Villalobos", "Cecilia Escalante"];

// Función unificada para filtrar
const filter = () => {
    router.get(route('interesados.index'), { 
        search: search.value,
        municipio: municipioSeleccionado.value,
        sexo: sexo.value,
        modalidad: modalidad.value,
        seguimiento: seguimientoSeleccionado.value
    }, { 
        preserveState: true, 
        replace: true 
    });
};

// Observadores
watch([municipioSeleccionado, sexo, modalidad, seguimientoSeleccionado], () => filter());
watch(search, debounce(() => filter(), 300));

const clearFilters = () => {
    search.value = '';
    municipioSeleccionado.value = '';
    sexo.value = '';
    modalidad.value = '';
    seguimientoSeleccionado.value = '';
    filter();
};

const convertirInteresado = (id) => {
    if (confirm('¿Deseas convertir este prospecto?')) {
        router.post(route('interesados.convertir', id), {}, {
            onError: (errors) => {
                console.error("Error al convertir interesado:", errors);
            },
        });
    }
};
</script>

<template>
    <Head title="Interesados CREA" />
    <AppLayout>
        <div class="p-6 max-w-7xl mx-auto">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
                <div>
                    <h1 class="text-2xl font-black text-slate-800 dark:text-white uppercase tracking-tight">Interesados CREA</h1>
                    <p class="text-slate-500 text-sm font-medium">Gestión y seguimiento de prospectos.</p>
                </div>
                <div class="flex gap-2">
                    <button @click="showFilters = !showFilters" :class="showFilters ? 'bg-slate-800 text-white' : 'bg-white text-slate-600'" class="px-4 py-3 rounded-2xl font-bold flex items-center gap-2 border border-slate-200 transition-all shadow-sm">
                        <Filter :size="20" /> Filtros
                    </button>
                    <Link :href="route('interesados.create')" class="bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-2xl font-bold flex items-center gap-2 transition-all shadow-lg shadow-red-100">
                        <UserPlus :size="20" /> Nuevo Registro
                    </Link>
                </div>
            </div>

            <div class="space-y-4 mb-8">
                <div class="relative">
                    <Search class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400" :size="20" />
                    <input 
                        v-model="search" 
                        type="text" 
                        placeholder="Buscar por nombre o empresa..." 
                        class="w-full pl-12 pr-4 py-4 rounded-2xl border-none bg-white dark:bg-slate-900 shadow-sm ring-1 ring-slate-200 dark:ring-slate-800 focus:ring-2 focus:ring-red-500 transition-all"
                    />
                </div>

                <div v-if="showFilters" class="bg-slate-50 dark:bg-slate-900/50 p-6 rounded-[2rem] border border-slate-200 dark:border-slate-800 shadow-inner">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <div>
                            <label class="block text-[10px] font-black uppercase text-slate-500 mb-2 ml-1 tracking-widest">Municipio</label>
                            <select v-model="municipioSeleccionado" class="w-full rounded-xl border-none bg-white dark:bg-slate-800 text-sm font-bold shadow-sm focus:ring-2 focus:ring-red-500">
                                <option value="">Todos los municipios</option>
                                <option v-for="m in municipiosYucatan" :key="m" :value="m">{{ m }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-black uppercase text-slate-500 mb-2 ml-1 tracking-widest">Sexo</label>
                            <select v-model="sexo" class="w-full rounded-xl border-none bg-white dark:bg-slate-800 text-sm font-bold shadow-sm focus:ring-2 focus:ring-red-500">
                                <option value="">Cualquier sexo</option>
                                <option value="Hombre">Hombre</option>
                                <option value="Mujer">Mujer</option>
                                <option value="Otro">Otro</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-black uppercase text-slate-500 mb-2 ml-1 tracking-widest">Modalidad</label>
                            <select v-model="modalidad" class="w-full rounded-xl border-none bg-white dark:bg-slate-800 text-sm font-bold shadow-sm focus:ring-2 focus:ring-red-500">
                                <option value="">Todas las modalidades</option>
                                <option value="Artesanal">Artesanal</option>
                                <option value="Sustentable">Sustentable</option>
                                <option value="Emprendedores">Emprendedores</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-black uppercase text-slate-500 mb-2 ml-1 tracking-widest">Responsable</label>
                            <select v-model="seguimientoSeleccionado" class="w-full rounded-xl border-none bg-white dark:bg-slate-800 text-sm font-bold shadow-sm focus:ring-2 focus:ring-red-500">
                                <option value="">Todos los responsables</option>
                                <option v-for="p in listaSeguimiento" :key="p" :value="p">{{ p }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="flex justify-end mt-6">
                        <button @click="clearFilters" class="text-xs font-black uppercase tracking-widest text-red-600 hover:text-red-700 flex items-center gap-2 transition-colors">
                            <X :size="14" /> Limpiar Filtros
                        </button>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-100 dark:border-slate-800 shadow-xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/50 dark:bg-slate-800/50 border-b border-slate-100 dark:border-slate-800">
                                <th class="px-6 py-4 text-[11px] font-black uppercase text-slate-400">Ciudadano / Empresa</th>
                                <th class="px-6 py-4 text-[11px] font-black uppercase text-slate-400">Contacto</th>
                                <th class="px-6 py-4 text-[11px] font-black uppercase text-slate-400">Detalles</th>
                                <th class="px-6 py-4 text-[11px] font-black uppercase text-slate-400">Estatus / Seguimiento</th>
                                <th class="px-6 py-4 text-[11px] font-black uppercase text-slate-400 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50 dark:divide-slate-800 text-sm">
                            <tr v-for="item in interesados.data" :key="item.id" class="hover:bg-slate-50/80 dark:hover:bg-slate-800/50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="font-bold text-slate-900 dark:text-white uppercase">{{ item.nombre_ciudadano }}</div>
                                    <div class="text-[11px] text-red-500 font-bold uppercase">{{ item.empresa || 'Sin Empresa' }}</div>
                                    <div class="text-[10px] text-slate-400">ID: #{{ item.id }} | Ingreso: {{ item.medio_ingreso }}</div>
                                </td>
                                <td class="px-6 py-4 text-xs font-medium">
                                    <div class="flex items-center gap-1.5 text-slate-600 dark:text-slate-300">
                                        <MapPin :size="14" class="text-red-400" /> {{ item.municipio }}
                                    </div>
                                    <div class="flex items-center gap-1.5 text-slate-500 mt-1 lowercase">
                                        <Phone :size="14" /> {{ item.telefono }}
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-wrap gap-1">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold border" :class="{
                                            'bg-amber-50 text-amber-600 border-amber-100': item.modalidad === 'Artesanal',
                                            'bg-red-50 text-red-600 border-red-100': item.modalidad === 'Sustentable',
                                            'bg-emerald-50 text-emerald-600 border-emerald-100': item.modalidad === 'Emprendedores'
                                        }">{{ item.modalidad }}</span>
                                        <span v-if="item.mayahablante" class="bg-blue-50 text-blue-600 px-2 py-0.5 rounded text-[10px] font-bold border border-blue-100">Maya</span>
                                        <span v-if="item.discapacidad" class="bg-purple-50 text-purple-600 px-2 py-0.5 rounded text-[10px] font-bold border border-purple-100">Discapacidad</span>
                                    </div>
                                    <div class="text-[10px] text-slate-400 mt-1">Giro: {{ item.giro_comercial || 'N/A' }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase" 
                                          :class="item.estatus === 'Convertido' ? 'bg-green-600 text-white' : 'bg-slate-100 text-slate-500'">
                                        {{ item.estatus }}
                                    </span>
                                    <div class="text-[10px] text-slate-500 mt-1 font-bold uppercase">Atendió: {{ item.atendio }}</div>
                                    <div class="text-[9px] text-slate-400 uppercase italic">Seg: {{ item.seguimiento }}</div>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex justify-end gap-1 items-center">
                                        <template v-if="item.estatus !== 'Convertido'">
                                            <Link :href="route('interesados.edit', item.id)" class="p-2 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-all" title="Editar">
                                                <Edit :size="18" />
                                            </Link>
                                            <button 
                                                @click="convertirInteresado(item.id)"
                                                class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                                title="Convertir a Acreditado"
                                            >
                                                <UserCheck :size="20" />
                                            </button>
                                        </template>

                                        <template v-else>
                                            <div class="flex items-center gap-1 px-3 py-1.5 bg-slate-100 text-slate-400 rounded-xl border border-slate-200 cursor-not-allowed">
                                                <CheckCircle2 :size="14" />
                                                <span class="text-[10px] font-black uppercase tracking-tighter">Cliente Activo</span>
                                            </div>
                                        </template>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-6 flex justify-center gap-2">
                <Link v-for="link in interesados.links" :key="link.label" :href="link.url || '#'" v-html="link.label" class="px-4 py-2 rounded-xl text-sm font-bold transition-all" :class="link.active ? 'bg-red-600 text-white shadow-lg' : 'bg-white dark:bg-slate-900 text-slate-500 hover:bg-slate-50 border border-slate-100 dark:border-slate-800'" />
            </div>
        </div>
    </AppLayout>
</template>