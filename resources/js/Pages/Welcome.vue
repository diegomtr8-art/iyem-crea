<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import {
    CreditCard, Users, Leaf, ChevronRight, CheckCircle2,
    Sun, Moon, MapPin, Phone, ArrowRight, Sparkles,
    FileText, Upload, Clock, ThumbsUp, Star, Menu, X
} from 'lucide-vue-next';

const theme = ref(localStorage.getItem('theme') || 'light');
const mobileOpen = ref(false);

const toggleTheme = () => {
    theme.value = theme.value === 'light' ? 'dark' : 'light';
    localStorage.setItem('theme', theme.value);
    applyTheme();
};

const applyTheme = () => {
    document.documentElement.classList.toggle('dark', theme.value === 'dark');
};

onMounted(applyTheme);

const modalidades = [
    {
        nombre: 'Artesanal',
        icono: Star,
        color: 'amber',
        descripcion: 'Para artesanos tradicionales de Yucatán que requieren financiamiento para adquirir materias primas, herramientas y potenciar su producción cultural.',
        beneficios: ['Adquisición de materias primas', 'Herramientas y equipos', 'Capital de trabajo', 'Mejoras al taller'],
        tasa: 'Tasa preferencial',
    },
    {
        nombre: 'Emprendedores',
        icono: CreditCard,
        color: 'red',
        descripcion: 'Para personas que están iniciando o consolidando un negocio formal, con necesidad de capital de trabajo o inversión en activos productivos.',
        beneficios: ['Inventario inicial', 'Equipo de oficina', 'Marketing y publicidad', 'Capital operativo'],
        tasa: 'Tasa competitiva',
    },
    {
        nombre: 'Sustentable',
        icono: Leaf,
        color: 'green',
        descripcion: 'Enfocado en proyectos con impacto ambiental positivo, apoyando iniciativas de energía renovable, reciclaje y economía circular.',
        beneficios: ['Paneles solares', 'Equipos de reciclaje', 'Proyectos verdes', 'Economía circular'],
        tasa: 'Tasa especial',
    },
];

const pasos = [
    { num: '01', titulo: 'Crea tu cuenta', desc: 'Regístrate en el portal ciudadano con tu correo y contraseña.', icono: Users },
    { num: '02', titulo: 'Completa tu expediente', desc: 'Llena el formulario con tus datos personales y del negocio.', icono: FileText },
    { num: '03', titulo: 'Sube tus documentos', desc: 'Adjunta tu INE, CURP, comprobante de domicilio y foto del negocio.', icono: Upload },
    { num: '04', titulo: 'Espera la revisión', desc: 'El equipo CREA revisará tu solicitud y documentos en días hábiles.', icono: Clock },
    { num: '05', titulo: '¡Recibe tu crédito!', desc: 'Una vez aprobada tu solicitud, el monto es depositado directamente.', icono: ThumbsUp },
];

const requisitos = [
    'Ser mayor de 18 años',
    'Vivir en el estado de Yucatán',
    'Contar con CURP vigente',
    'Identificación oficial vigente (INE)',
    'Comprobante de domicilio (máximo 3 meses)',
    'Tener un proyecto o negocio establecido o en formación',
    'No contar con adeudos vencidos con el Gobierno del Estado',
];

const colorMap: Record<string, Record<string, string>> = {
    amber: {
        bg: 'bg-amber-50 dark:bg-amber-900/10',
        border: 'border-amber-200 dark:border-amber-800/40',
        icon: 'text-amber-600 bg-amber-100 dark:bg-amber-900/30',
        badge: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
    },
    red: {
        bg: 'bg-red-50 dark:bg-red-900/10',
        border: 'border-red-200 dark:border-red-800/40',
        icon: 'text-red-600 bg-red-100 dark:bg-red-900/30',
        badge: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
    },
    green: {
        bg: 'bg-green-50 dark:bg-green-900/10',
        border: 'border-green-200 dark:border-green-800/40',
        icon: 'text-green-600 bg-green-100 dark:bg-green-900/30',
        badge: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
    },
};
</script>

<template>
    <Head title="CREA – Crédito para Emprendedores y Artesanos de Yucatán" />

    <div class="min-h-screen bg-[#FDFCFB] dark:bg-zinc-950 text-slate-900 dark:text-zinc-100 transition-colors duration-500 font-sans">

        <!-- NAVBAR -->
        <nav class="bg-white/80 dark:bg-zinc-900/80 backdrop-blur-md sticky top-0 z-50 border-b border-slate-200/60 dark:border-zinc-800/60">
            <div class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-red-700 flex items-center justify-center shadow-lg shadow-red-900/20 text-white">
                        <CreditCard size="20" />
                    </div>
                    <span class="text-xl font-black tracking-tight dark:text-white">
                        CREA <span class="hidden md:inline text-red-700 text-base font-semibold">| IYEM Yucatán</span>
                    </span>
                </div>

                <!-- Links escritorio -->
                <div class="hidden md:flex items-center gap-8 text-sm font-medium text-slate-600 dark:text-zinc-400">
                    <a href="#que-es" class="hover:text-red-700 transition-colors">¿Qué es CREA?</a>
                    <a href="#modalidades" class="hover:text-red-700 transition-colors">Modalidades</a>
                    <a href="#como-funciona" class="hover:text-red-700 transition-colors">¿Cómo funciona?</a>
                    <a href="#requisitos" class="hover:text-red-700 transition-colors">Requisitos</a>
                </div>

                <div class="flex items-center gap-3">
                    <button @click="toggleTheme" class="p-2 rounded-xl bg-slate-100 dark:bg-zinc-800 text-slate-500 dark:text-zinc-400 hover:ring-2 ring-red-500/30 transition-all">
                        <Sun v-if="theme === 'dark'" size="18" />
                        <Moon v-else size="18" />
                    </button>
                    <Link :href="route('login')" class="hidden md:inline-flex items-center px-4 py-2 rounded-xl text-sm font-bold text-slate-700 dark:text-zinc-300 hover:bg-slate-100 dark:hover:bg-zinc-800 transition-all">
                        Iniciar Sesión
                    </Link>
                    <Link :href="route('ciudadano.register')" class="hidden md:inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-bold bg-red-700 text-white hover:bg-red-800 shadow-lg shadow-red-900/20 transition-all">
                        <Sparkles size="16" /> Registrarme
                    </Link>
                    <button @click="mobileOpen = !mobileOpen" class="md:hidden p-2 rounded-xl bg-slate-100 dark:bg-zinc-800 text-slate-600 dark:text-zinc-400">
                        <Menu v-if="!mobileOpen" size="20" />
                        <X v-else size="20" />
                    </button>
                </div>
            </div>

            <!-- Menú móvil -->
            <div v-if="mobileOpen" class="md:hidden border-t border-slate-100 dark:border-zinc-800 bg-white dark:bg-zinc-900 px-6 py-4 space-y-4">
                <a href="#que-es" @click="mobileOpen=false" class="block text-sm font-medium text-slate-600 dark:text-zinc-400 py-2">¿Qué es CREA?</a>
                <a href="#modalidades" @click="mobileOpen=false" class="block text-sm font-medium text-slate-600 dark:text-zinc-400 py-2">Modalidades</a>
                <a href="#como-funciona" @click="mobileOpen=false" class="block text-sm font-medium text-slate-600 dark:text-zinc-400 py-2">¿Cómo funciona?</a>
                <a href="#requisitos" @click="mobileOpen=false" class="block text-sm font-medium text-slate-600 dark:text-zinc-400 py-2">Requisitos</a>
                <div class="flex flex-col gap-3 pt-4 border-t border-slate-100 dark:border-zinc-800">
                    <Link :href="route('login')" class="py-3 text-center rounded-xl border border-slate-200 dark:border-zinc-700 text-sm font-bold text-slate-700 dark:text-zinc-300">
                        Iniciar Sesión
                    </Link>
                    <Link :href="route('ciudadano.register')" class="py-3 text-center rounded-xl bg-red-700 text-white text-sm font-bold shadow-lg shadow-red-900/20">
                        Registrarme
                    </Link>
                </div>
            </div>
        </nav>

        <!-- HERO -->
        <section class="relative overflow-hidden pt-20 pb-28">
            <div class="absolute inset-0 pointer-events-none">
                <div class="absolute top-0 left-1/4 w-96 h-96 bg-red-500/5 rounded-full blur-3xl"></div>
                <div class="absolute bottom-0 right-1/4 w-80 h-80 bg-amber-500/5 rounded-full blur-3xl"></div>
            </div>
            <div class="max-w-7xl mx-auto px-6 relative">
                <div class="text-center max-w-4xl mx-auto space-y-8">
                    <span class="inline-flex items-center gap-2 text-xs font-bold tracking-widest uppercase text-red-700 bg-red-50 dark:bg-red-900/20 px-4 py-2 rounded-full border border-red-100 dark:border-red-900/40">
                        Instituto Yucateco de Emprendedores — Gobierno de Yucatán
                    </span>

                    <h1 class="text-5xl md:text-7xl font-black text-slate-950 dark:text-white leading-tight tracking-tight">
                        Tu negocio merece<br/>
                        <span class="text-red-700">crecer.</span>
                    </h1>

                    <p class="text-xl md:text-2xl text-slate-500 dark:text-zinc-400 leading-relaxed max-w-2xl mx-auto">
                        CREA es el programa de crédito del Gobierno del Estado de Yucatán para
                        <strong class="text-slate-700 dark:text-zinc-200">emprendedores y artesanos</strong> que quieren impulsar sus proyectos de vida.
                    </p>

                    <div class="flex flex-col sm:flex-row items-center justify-center gap-4 pt-4">
                        <Link :href="route('ciudadano.register')"
                            class="w-full sm:w-auto inline-flex items-center justify-center gap-3 px-8 py-5 rounded-2xl bg-red-700 text-white font-bold text-lg shadow-2xl shadow-red-900/25 hover:bg-red-800 transition-all active:scale-[0.98]">
                            <Sparkles size="22" /> Solicitar mi Crédito
                            <ArrowRight size="20" />
                        </Link>
                        <a href="#que-es"
                            class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-8 py-5 rounded-2xl border-2 border-slate-200 dark:border-zinc-700 text-slate-700 dark:text-zinc-300 font-bold text-lg hover:border-red-300 dark:hover:border-red-700 transition-all">
                            Conocer más <ChevronRight size="20" />
                        </a>
                    </div>

                    <!-- Badges de modalidades -->
                    <div class="flex flex-wrap items-center justify-center gap-3 pt-4">
                        <span class="flex items-center gap-2 px-4 py-2 bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400 rounded-full text-sm font-bold border border-amber-100 dark:border-amber-900/40">
                            <Star size="14" /> Artesanal
                        </span>
                        <span class="flex items-center gap-2 px-4 py-2 bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 rounded-full text-sm font-bold border border-red-100 dark:border-red-900/40">
                            <CreditCard size="14" /> Emprendedores
                        </span>
                        <span class="flex items-center gap-2 px-4 py-2 bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 rounded-full text-sm font-bold border border-green-100 dark:border-green-900/40">
                            <Leaf size="14" /> Sustentable
                        </span>
                    </div>
                </div>
            </div>
        </section>

        <!-- ¿QUÉ ES CREA? -->
        <section id="que-es" class="py-24 bg-white dark:bg-zinc-900 border-y border-slate-100 dark:border-zinc-800">
            <div class="max-w-7xl mx-auto px-6">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
                    <div class="space-y-8">
                        <div class="space-y-4">
                            <span class="text-xs font-black uppercase tracking-[0.2em] text-red-700">¿Qué es CREA?</span>
                            <h2 class="text-4xl md:text-5xl font-black text-slate-950 dark:text-white leading-tight">
                                Crédito con propósito, para quienes construyen Yucatán.
                            </h2>
                        </div>
                        <p class="text-lg text-slate-500 dark:text-zinc-400 leading-relaxed">
                            <strong class="text-slate-700 dark:text-zinc-200">CREA (Crédito para Emprendedores y Artesanos)</strong> es un programa financiero del Gobierno del Estado de Yucatán, operado por el <strong class="text-slate-700 dark:text-zinc-200">Instituto Yucateco de Emprendedores (IYEM)</strong>, diseñado para brindar acceso a financiamiento accesible a quienes más lo necesitan.
                        </p>
                        <p class="text-lg text-slate-500 dark:text-zinc-400 leading-relaxed">
                            Desde artesanos que preservan la identidad cultural de Yucatán, hasta emprendedores que están construyendo el tejido económico de nuestra región — CREA los acompaña con créditos transparentes, tasas preferenciales y un proceso completamente digital.
                        </p>
                        <Link :href="route('ciudadano.register')" class="inline-flex items-center gap-2 text-red-700 font-bold hover:gap-4 transition-all">
                            Comenzar mi solicitud <ArrowRight size="18" />
                        </Link>
                    </div>

                    <div class="grid grid-cols-2 gap-6">
                        <div class="bg-red-700 text-white p-8 rounded-[2rem] shadow-2xl shadow-red-900/20 col-span-2 sm:col-span-1">
                            <p class="text-5xl font-black">500+</p>
                            <p class="text-red-200 mt-2 font-medium">Emprendedores y artesanos beneficiados</p>
                        </div>
                        <div class="bg-slate-50 dark:bg-zinc-800 p-8 rounded-[2rem] border border-slate-100 dark:border-zinc-700 col-span-2 sm:col-span-1">
                            <p class="text-5xl font-black text-slate-950 dark:text-white">3</p>
                            <p class="text-slate-500 dark:text-zinc-400 mt-2 font-medium">Modalidades de crédito disponibles</p>
                        </div>
                        <div class="bg-slate-50 dark:bg-zinc-800 p-8 rounded-[2rem] border border-slate-100 dark:border-zinc-700 col-span-2">
                            <p class="text-3xl font-black text-slate-950 dark:text-white">100% Digital</p>
                            <p class="text-slate-500 dark:text-zinc-400 mt-2 font-medium">Proceso de solicitud completamente en línea — desde cualquier dispositivo, en cualquier momento.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- MODALIDADES -->
        <section id="modalidades" class="py-24">
            <div class="max-w-7xl mx-auto px-6">
                <div class="text-center max-w-2xl mx-auto mb-16 space-y-4">
                    <span class="text-xs font-black uppercase tracking-[0.2em] text-red-700">Modalidades</span>
                    <h2 class="text-4xl md:text-5xl font-black text-slate-950 dark:text-white">
                        Encuentra la modalidad que se adapta a ti.
                    </h2>
                    <p class="text-lg text-slate-500 dark:text-zinc-400">
                        Cada modalidad está diseñada para necesidades específicas, con condiciones que te permiten crecer a tu ritmo.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div v-for="m in modalidades" :key="m.nombre"
                        :class="['rounded-[2rem] border p-8 space-y-6 hover:shadow-xl transition-all duration-300', colorMap[m.color].bg, colorMap[m.color].border]">
                        <div :class="['w-14 h-14 rounded-2xl flex items-center justify-center', colorMap[m.color].icon]">
                            <component :is="m.icono" size="28" />
                        </div>
                        <div class="space-y-3">
                            <div class="flex items-center gap-3">
                                <h3 class="text-2xl font-black text-slate-950 dark:text-white">{{ m.nombre }}</h3>
                                <span :class="['text-xs font-bold px-3 py-1 rounded-full', colorMap[m.color].badge]">{{ m.tasa }}</span>
                            </div>
                            <p class="text-slate-500 dark:text-zinc-400 leading-relaxed">{{ m.descripcion }}</p>
                        </div>
                        <ul class="space-y-2">
                            <li v-for="b in m.beneficios" :key="b" class="flex items-center gap-3 text-sm text-slate-600 dark:text-zinc-400">
                                <CheckCircle2 size="16" class="text-green-500 shrink-0" />
                                {{ b }}
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <!-- ¿CÓMO FUNCIONA? -->
        <section id="como-funciona" class="py-24 bg-white dark:bg-zinc-900 border-y border-slate-100 dark:border-zinc-800">
            <div class="max-w-7xl mx-auto px-6">
                <div class="text-center max-w-2xl mx-auto mb-16 space-y-4">
                    <span class="text-xs font-black uppercase tracking-[0.2em] text-red-700">¿Cómo funciona?</span>
                    <h2 class="text-4xl md:text-5xl font-black text-slate-950 dark:text-white">
                        Simple, rápido y completamente digital.
                    </h2>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-6">
                    <div v-for="(paso, i) in pasos" :key="paso.num"
                        class="relative text-center space-y-4 p-6">
                        <div class="w-16 h-16 rounded-2xl bg-red-50 dark:bg-red-900/20 flex items-center justify-center mx-auto text-red-700">
                            <component :is="paso.icono" size="28" />
                        </div>
                        <div class="text-4xl font-black text-slate-100 dark:text-zinc-800 absolute top-4 left-4">{{ paso.num }}</div>
                        <h3 class="text-lg font-black text-slate-900 dark:text-white">{{ paso.titulo }}</h3>
                        <p class="text-sm text-slate-500 dark:text-zinc-400 leading-relaxed">{{ paso.desc }}</p>
                        <div v-if="i < pasos.length - 1" class="hidden lg:block absolute top-12 -right-3 text-slate-200 dark:text-zinc-700">
                            <ChevronRight size="24" />
                        </div>
                    </div>
                </div>

                <div class="mt-16 text-center">
                    <Link :href="route('ciudadano.register')"
                        class="inline-flex items-center gap-3 px-8 py-5 rounded-2xl bg-red-700 text-white font-bold text-lg shadow-2xl shadow-red-900/25 hover:bg-red-800 transition-all active:scale-[0.98]">
                        <Sparkles size="22" /> Empezar ahora
                    </Link>
                </div>
            </div>
        </section>

        <!-- REQUISITOS -->
        <section id="requisitos" class="py-24">
            <div class="max-w-7xl mx-auto px-6">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-start">
                    <div class="space-y-8">
                        <div class="space-y-4">
                            <span class="text-xs font-black uppercase tracking-[0.2em] text-red-700">Requisitos</span>
                            <h2 class="text-4xl md:text-5xl font-black text-slate-950 dark:text-white leading-tight">
                                ¿Qué necesito para solicitar?
                            </h2>
                            <p class="text-lg text-slate-500 dark:text-zinc-400">
                                Los requisitos son sencillos. Si tienes un proyecto o negocio en Yucatán, probablemente ya los cumples.
                            </p>
                        </div>

                        <ul class="space-y-4">
                            <li v-for="req in requisitos" :key="req"
                                class="flex items-start gap-4 p-4 bg-white dark:bg-zinc-900 rounded-2xl border border-slate-100 dark:border-zinc-800">
                                <div class="w-8 h-8 rounded-xl bg-green-50 dark:bg-green-900/20 flex items-center justify-center shrink-0 text-green-600">
                                    <CheckCircle2 size="18" />
                                </div>
                                <span class="text-slate-700 dark:text-zinc-300 font-medium">{{ req }}</span>
                            </li>
                        </ul>
                    </div>

                    <div class="bg-gradient-to-br from-red-700 to-red-900 rounded-[2.5rem] p-10 text-white space-y-8 shadow-2xl shadow-red-900/30">
                        <div class="space-y-4">
                            <h3 class="text-3xl font-black">¿Listo para comenzar?</h3>
                            <p class="text-red-200 leading-relaxed">
                                El proceso es 100% en línea. Crea tu cuenta hoy y da el primer paso hacia el crédito que necesitas para crecer.
                            </p>
                        </div>
                        <div class="space-y-4">
                            <Link :href="route('ciudadano.register')"
                                class="w-full flex items-center justify-center gap-3 py-4 bg-white text-red-700 rounded-2xl font-bold text-lg hover:bg-red-50 transition-colors">
                                <Sparkles size="20" /> Crear mi cuenta
                            </Link>
                            <Link :href="route('login')"
                                class="w-full flex items-center justify-center gap-2 py-4 border-2 border-white/30 text-white rounded-2xl font-bold hover:border-white/60 transition-colors">
                                Ya tengo cuenta — Iniciar Sesión
                            </Link>
                        </div>
                        <p class="text-xs text-red-300 text-center">
                            Si eres personal administrativo del IYEM,<br/>
                            <Link :href="route('login')" class="underline hover:text-white">accede al sistema operativo aquí.</Link>
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- FOOTER -->
        <footer class="border-t border-slate-200/60 dark:border-zinc-800/60 bg-white dark:bg-zinc-900 transition-colors duration-500">
            <div class="max-w-7xl mx-auto px-6 py-16">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-12">
                    <div class="space-y-6">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-red-700 flex items-center justify-center text-white">
                                <CreditCard size="20" />
                            </div>
                            <span class="text-xl font-black dark:text-white">CREA</span>
                        </div>
                        <p class="text-sm text-slate-500 dark:text-zinc-400 leading-relaxed">
                            Impulsando el ecosistema emprendedor de Yucatán. Transformamos ideas en proyectos de vida.
                        </p>
                        <div class="flex items-center gap-4">
                            <a href="https://www.facebook.com/IyemYucatan" target="_blank" class="p-2 rounded-full bg-slate-100 dark:bg-zinc-800 text-slate-600 dark:text-zinc-400 hover:bg-red-700 hover:text-white transition-all">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path></svg>
                            </a>
                            <a href="https://www.instagram.com/iyemyucatan" target="_blank" class="p-2 rounded-full bg-slate-100 dark:bg-zinc-800 text-slate-600 dark:text-zinc-400 hover:bg-red-700 hover:text-white transition-all">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="20" x="2" y="2" rx="5" ry="5"></rect><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path><line x1="17.5" x2="17.51" y1="6.5" y2="6.5"></line></svg>
                            </a>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <h4 class="text-sm font-black uppercase tracking-[0.2em] text-red-700">Acceso Rápido</h4>
                        <ul class="space-y-3 text-sm text-slate-600 dark:text-zinc-400">
                            <li><a href="#que-es" class="hover:text-red-700 transition-colors">¿Qué es CREA?</a></li>
                            <li><a href="#modalidades" class="hover:text-red-700 transition-colors">Modalidades</a></li>
                            <li><a href="#como-funciona" class="hover:text-red-700 transition-colors">¿Cómo funciona?</a></li>
                            <li><a href="#requisitos" class="hover:text-red-700 transition-colors">Requisitos</a></li>
                            <li>
                                <Link :href="route('ciudadano.register')" class="hover:text-red-700 transition-colors font-medium">
                                    Solicitar mi crédito →
                                </Link>
                            </li>
                            <li>
                                <Link :href="route('login')" class="hover:text-red-700 transition-colors">
                                    Iniciar sesión →
                                </Link>
                            </li>
                        </ul>
                    </div>

                    <div class="space-y-6">
                        <h4 class="text-sm font-black uppercase tracking-[0.2em] text-red-700">Sitios Aliados</h4>
                        <ul class="space-y-4">
                            <li>
                                <a href="https://www.nodico.com.mx" target="_blank" class="group flex items-center gap-3">
                                    <div class="w-8 h-8 rounded bg-slate-50 dark:bg-zinc-800 flex items-center justify-center border border-slate-200 dark:border-zinc-700 group-hover:border-red-500 transition-colors text-[8px] font-bold text-slate-500">N</div>
                                    <span class="text-sm text-slate-600 dark:text-zinc-400 group-hover:text-red-700 transition-colors">Nodico</span>
                                </a>
                            </li>
                            <li>
                                <a href="https://www.herenciaviva.com" target="_blank" class="group flex items-center gap-3">
                                    <div class="w-8 h-8 rounded bg-slate-50 dark:bg-zinc-800 flex items-center justify-center border border-slate-200 dark:border-zinc-700 group-hover:border-red-500 transition-colors text-[8px] font-bold text-slate-500">HV</div>
                                    <span class="text-sm text-slate-600 dark:text-zinc-400 group-hover:text-red-700 transition-colors">Herencia Viva</span>
                                </a>
                            </li>
                        </ul>
                    </div>

                    <div class="space-y-6">
                        <h4 class="text-sm font-black uppercase tracking-[0.2em] text-red-700">Contacto</h4>
                        <div class="space-y-4 text-sm text-slate-600 dark:text-zinc-400">
                            <p class="flex items-start gap-3">
                                <MapPin size="18" class="text-red-700 shrink-0 mt-0.5" />
                                Av. Industrias No Contaminantes Tablaje 13613 Col. Sodzil Norte, Mérida, Yucatán.
                            </p>
                            <p class="flex items-center gap-3">
                                <Phone size="16" class="text-red-700 shrink-0" />
                                999 941 2170
                            </p>
                        </div>
                    </div>
                </div>

                <div class="mt-16 pt-8 border-t border-slate-100 dark:border-zinc-800 text-center">
                    <p class="text-[10px] md:text-xs text-slate-400 dark:text-zinc-500 uppercase tracking-widest leading-relaxed">
                        © 2026 Instituto Yucateco de Emprendedores — Gobierno del Estado de Yucatán.<br class="md:hidden"/>
                        Software Desarrollado por Diego Martínez
                    </p>
                </div>
            </div>
        </footer>

    </div>

    <!-- WhatsApp flotante -->
    <a href="https://wa.me/529999412170?text=Hola,%20vengo%20de%20la%20plataforma%20CREA%20y%20tengo%20una%20pregunta"
        target="_blank"
        class="fixed bottom-6 right-6 w-14 h-14 bg-green-500 hover:bg-green-600 text-white rounded-full flex items-center justify-center shadow-2xl shadow-green-900/30 z-50 transition-all hover:scale-110"
        title="¿Necesitas ayuda?">
        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="currentColor" viewBox="0 0 16 16">
            <path d="M13.601 2.326A7.854 7.854 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.933 7.933 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.898 7.898 0 0 0 13.6 2.326zM7.994 14.521a6.573 6.573 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.557 6.557 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592zm3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.065-.315-.099-.445.099-.133.197-.513.646-.627.775-.114.133-.232.148-.43.05-.197-.1-.836-.308-1.592-.985-.59-.525-.985-1.175-1.103-1.372-.114-.198-.011-.304.088-.403.087-.088.197-.232.296-.346.1-.114.133-.198.198-.33.065-.134.034-.248-.015-.347-.05-.099-.445-1.076-.612-1.47-.16-.389-.323-.335-.445-.34-.114-.007-.247-.007-.38-.007a.729.729 0 0 0-.529.247c-.182.198-.691.677-.691 1.654 0 .977.71 1.916.81 2.049.098.133 1.394 2.132 3.383 2.992.47.205.84.326 1.129.418.475.152.904.129 1.246.08.38-.058 1.171-.48 1.338-.943.164-.464.164-.86.114-.943-.049-.084-.182-.133-.38-.232z"/>
        </svg>
    </a>
</template>

<style scoped>
* { transition: background-color 0.4s ease, border-color 0.4s ease; }
</style>
