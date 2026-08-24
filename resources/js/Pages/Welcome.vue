<script setup lang="ts">
import { ref, computed, reactive, watch, onMounted, onUnmounted } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';

// ─── THEME ─────────────────────────────────────────────────────────────────
const theme = ref(localStorage.getItem('theme') || 'light');
const applyTheme = () => document.documentElement.classList.toggle('dark', theme.value === 'dark');
const toggleTheme = () => {
    theme.value = theme.value === 'light' ? 'dark' : 'light';
    localStorage.setItem('theme', theme.value);
    applyTheme();
};

// ─── NAVBAR ────────────────────────────────────────────────────────────────
const navScrolled = ref(false);
const mobileOpen = ref(false);
const handleScroll = () => { navScrolled.value = window.scrollY > 60; };

// ─── COUNTER ANIMATION ─────────────────────────────────────────────────────
const animateCount = (target: number, duration: number, setter: (v: number) => void) => {
    const start = performance.now();
    const tick = (t: number) => {
        const progress = Math.min((t - start) / duration, 1);
        const ease = 1 - Math.pow(1 - progress, 3);
        setter(Math.round(ease * target));
        if (progress < 1) requestAnimationFrame(tick);
    };
    requestAnimationFrame(tick);
};

const statsRef = ref<HTMLElement | null>(null);
const statsAnimated = ref(false);
const statsCount = ref({ creditos: 0, digital: 0, meses: 0, modal: 0 });

// ─── TIMELINE & REQUIREMENTS observers ─────────────────────────────────────
const timelineRef = ref<HTMLElement | null>(null);
const timelineVisible = ref(false);
const reqRef = ref<HTMLElement | null>(null);
const reqVisible = ref(false);

// ─── CAROUSEL ──────────────────────────────────────────────────────────────
const currentSlide = ref(0);
const windowWidth = ref(typeof window !== 'undefined' ? window.innerWidth : 1024);
const isMobile = computed(() => windowWidth.value < 768);
let touchStartX = 0;
const onTouchStart = (e: TouchEvent) => { touchStartX = e.touches[0].clientX; };
const onTouchEnd = (e: TouchEvent) => {
    const diff = touchStartX - e.changedTouches[0].clientX;
    if (Math.abs(diff) > 50) diff > 0 ? nextSlide() : prevSlide();
};
const prevSlide = () => { if (currentSlide.value > 0) currentSlide.value--; };
const nextSlide = () => { if (currentSlide.value < 2) currentSlide.value++; };

// ─── FAQ ───────────────────────────────────────────────────────────────────
const openFaq = ref<number | null>(null);
const toggleFaq = (i: number) => { openFaq.value = openFaq.value === i ? null : i; };

// ─── CALCULADORA DE CRÉDITO ────────────────────────────────────────────────
const calc = reactive({ monto: 50000, plazo: 12, modalidad: 'CREA Emprendedores' });

const CALC_CONFIG: Record<string, { tasa: number; min: number; max: number; prorroga: number }> = {
    'CREA Artesanal':     { tasa: 0.00, min: 5000,   max: 25000,  prorroga: 0 },
    'CREA Emprendedores': { tasa: 0.07, min: 25000,  max: 150000, prorroga: 0 },
    'CREA Sustentable':   { tasa: 0.05, min: 50000,  max: 500000, prorroga: 3 },
};

const configActual  = computed(() => CALC_CONFIG[calc.modalidad] ?? CALC_CONFIG['CREA Emprendedores']);
const tasaActual    = computed(() => configActual.value.tasa);
const montoMin      = computed(() => configActual.value.min);
const montoMax      = computed(() => configActual.value.max);
const prorrogaMeses = computed(() => configActual.value.prorroga);
const montoValido   = computed(() => calc.monto >= montoMin.value && calc.monto <= montoMax.value);
const plazosDisponibles = computed(() => calc.modalidad === 'CREA Artesanal' ? [6, 12, 18] : [12, 18, 24]);
watch(plazosDisponibles, (opciones) => {
    if (!opciones.includes(calc.plazo)) calc.plazo = opciones[opciones.length - 1];
});

const calcResult = computed(() => {
    const { monto, plazo } = calc;
    if (!monto || !plazo || !montoValido.value) return null;
    const r = tasaActual.value / 12;
    const n = plazo;
    const mensual = r === 0 ? monto / n : monto * (r * Math.pow(1 + r, n)) / (Math.pow(1 + r, n) - 1);
    const total = mensual * n;
    const intereses = total - monto;
    return { mensual, total, intereses };
});

watch(() => calc.modalidad, () => {
    const { min, max } = configActual.value;
    if (calc.monto < min) calc.monto = min;
    else if (calc.monto > max) calc.monto = max;
});

// ─── CONTACTO ──────────────────────────────────────────────────────────────
const page = usePage();
const contactoFlash = computed(() => (page.props.flash as Record<string, string> | null | undefined)?.success ?? null);

const contactForm = useForm({
    nombre: '',
    email: '',
    asunto: '',
    mensaje: '',
});

const submitContacto = () => {
    contactForm.post(route('contacto.enviar'), {
        onSuccess: () => contactForm.reset(),
        preserveScroll: true,
    });
};

// ─── HELPERS ───────────────────────────────────────────────────────────────
const formatCurrency = (n: number) =>
    new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(n);

// ─── STATIC DATA ───────────────────────────────────────────────────────────
const MODALIDADES = [
    {
        nombre: 'CREA Artesanal',
        subtitulo: 'Para artesanos de Yucatán · Sin intereses ordinarios',
        descripcion: 'Para artesanos mayores de 18 años con actividad artesanal en el estado. Financia adquisición de activos fijos (excepto vehículos), materia prima, herramientas y equipamiento para producción, transformación, comercialización y distribución artesanal.',
        beneficios: ['Activos fijos para producción artesanal', 'Materia prima e insumos', 'Herramientas y equipamiento', 'Sin intereses ordinarios (0%)'],
        tasa: '0% interés anual',
        monto: '$5,000 – $25,000',
        plazo: '6, 12 o 18 meses',
        accent: '#E8A020',
        bg: 'bg-amber-50 dark:bg-amber-900/10',
        border: 'border-amber-200/70 dark:border-amber-700/30',
        badge: 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400',
        dot: 'bg-amber-500',
    },
    {
        nombre: 'CREA Emprendedores',
        subtitulo: 'Para micro y pequeñas empresas con domicilio fiscal en Yucatán',
        descripcion: 'Para emprendedores mayores de 18 años con micro o pequeña empresa con domicilio fiscal en el estado. Financia capital de trabajo, activos productivos, equipamiento, inventario y expansión del negocio.',
        beneficios: ['Capital de trabajo operativo', 'Activos productivos y equipo', 'Inventario y mercancía', 'Expansión e infraestructura'],
        tasa: '7% interés anual',
        monto: '$25,000 – $150,000',
        plazo: '12, 18 o 24 meses',
        accent: '#6B1938',
        bg: 'bg-rose-50 dark:bg-rose-900/10',
        border: 'border-rose-200/70 dark:border-rose-800/30',
        badge: 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-400',
        dot: 'bg-[#6B1938]',
    },
    {
        nombre: 'CREA Sustentable',
        subtitulo: 'Proyectos con impacto ambiental positivo · 3 meses de gracia',
        descripcion: 'Para proyectos con enfoque sustentable y beneficio ambiental en Yucatán: biodiversidad, energía renovable, eficiencia energética, manejo de recursos hídricos, gestión de residuos y economía circular.',
        beneficios: ['Sistemas de energía renovable', 'Biodiversidad y medio ambiente', 'Gestión de residuos y reciclaje', '3 meses de prórroga (primer pago al mes 4)'],
        tasa: '5% interés anual',
        monto: '$50,000 – $500,000',
        plazo: '12, 18 o 24 meses',
        accent: '#059669',
        bg: 'bg-teal-50 dark:bg-teal-900/10',
        border: 'border-teal-200/70 dark:border-teal-700/30',
        badge: 'bg-teal-100 text-teal-700 dark:bg-teal-900/40 dark:text-teal-400',
        dot: 'bg-teal-500',
    },
];

const PASOS = [
    { num: '01', titulo: 'Regístrate en el portal', desc: 'Crea tu cuenta en el portal ciudadano CREA con tu correo electrónico y contraseña.' },
    { num: '02', titulo: 'Completa tu solicitud', desc: 'Llena el formulario con tus datos personales e información de tu negocio o proyecto productivo.' },
    { num: '03', titulo: 'Sube tus documentos', desc: 'Adjunta tu identificación oficial, CURP, comprobante de domicilio y documentos del negocio.' },
    { num: '04', titulo: 'Análisis crediticio', desc: 'El equipo CREA revisa tu expediente y realiza el análisis de viabilidad de tu solicitud.' },
    { num: '05', titulo: 'Dictamen del comité', desc: 'Un comité especializado emite el dictamen final sobre la aprobación de tu solicitud.' },
    { num: '06', titulo: 'Firma de contrato', desc: 'Una vez aprobada tu solicitud, se formaliza la operación mediante la firma del contrato.' },
    { num: '07', titulo: 'Desembolso del crédito', desc: 'El monto aprobado se deposita directamente en tu cuenta. ¡Empieza tu proyecto!' },
];

const REQUISITOS = [
    'Ser mexicano(a) mayor de 18 años con plena capacidad jurídica para contraer obligaciones',
    'Tener actividad económica en el estado de Yucatán',
    'Presentar proyecto productivo viable (Ficha Técnica — Anexo 2 del programa)',
    'No ser empleado(a) de la Administración Pública durante la vigencia del crédito',
    'Contar con aval solidario con domicilio comprobable en Yucatán (no familiar hasta segundo grado)',
    'Identificación oficial vigente (INE o pasaporte mexicano)',
    'Comprobante de domicilio no mayor a 90 días de antigüedad',
    'No contar con adeudos vencidos con el Gobierno del Estado de Yucatán',
];

const FAQS = [
    { pregunta: '¿Qué significa CREA?', respuesta: 'CREA son las siglas de "Crédito para el Renacimiento de Emprendedores y Artesanos". Es un programa del Instituto Yucateco de Emprendedores (IYEM), enmarcado en el programa "Renacimiento Maya de Yucatán" del Gobierno del Estado, con vigencia hasta el 31 de diciembre de 2030.' },
    { pregunta: '¿Quién puede solicitar un crédito CREA?', respuesta: 'Mexicanos mayores de 18 años con actividad económica en el estado de Yucatán, que no sean empleados de la Administración Pública durante la vigencia del crédito, cuenten con un proyecto productivo viable y un aval solidario con domicilio comprobable en Yucatán (no familiar hasta segundo grado consanguíneo).' },
    { pregunta: '¿Cuáles son las tres modalidades y sus condiciones?', respuesta: 'CREA Artesanal: $5,000 a $25,000 a tasa 0%, para artesanos de Yucatán. CREA Emprendedores: $25,000 a $150,000 al 7% anual, para micro y pequeñas empresas con domicilio fiscal en Yucatán. CREA Sustentable: $50,000 a $500,000 al 5% anual con 3 meses de prórroga, para proyectos con impacto ambiental positivo. CREA Artesanal maneja plazos de 6, 12 o 18 meses; CREA Emprendedores y CREA Sustentable manejan plazos de 12, 18 o 24 meses.' },
    { pregunta: '¿En cuántos municipios opera el programa?', respuesta: 'El programa CREA tiene cobertura en los 106 municipios del estado de Yucatán, conforme al Acuerdo de Reglas de Operación emitido por el IYEM (Acuerdo 03/2025).' },
    { pregunta: '¿Puedo realizar toda la solicitud en línea?', respuesta: 'Sí, el proceso de solicitud es completamente digital a través del portal ciudadano. Puedes registrarte, llenar tu solicitud, subir documentos y dar seguimiento a tu trámite sin acudir físicamente a las oficinas del IYEM.' },
    { pregunta: '¿Qué documentos necesito para mi solicitud?', respuesta: 'Identificación oficial vigente (INE o pasaporte), CURP, comprobante de domicilio no mayor a 90 días, Ficha Técnica del proyecto productivo (Anexo 2), y documentación del aval solidario. Para CREA Artesanal se requiere presentar productos muestra representativos.' },
    { pregunta: '¿Qué pasa si ya tengo un crédito CREA activo?', respuesta: 'Si ya eres acreditado del programa, ingresa a tu portal ciudadano con tus credenciales para consultar el estado de tu crédito, tabla de amortización y próximas cuotas.' },
    { pregunta: '¿Cómo funciona la prórroga de CREA Sustentable?', respuesta: 'La modalidad CREA Sustentable otorga 3 meses de gracia desde el desembolso. El primer pago de capital e intereses se realiza a partir del mes 4, dando tiempo para que el proyecto productivo sustentable comience a generar ingresos.' },
];

// ─── LIFECYCLE ─────────────────────────────────────────────────────────────
onMounted(() => {
    applyTheme();
    window.addEventListener('scroll', handleScroll, { passive: true });
    window.addEventListener('resize', () => { windowWidth.value = window.innerWidth; });

    const sObs = new IntersectionObserver(([e]) => {
        if (e.isIntersecting && !statsAnimated.value) {
            statsAnimated.value = true;
            animateCount(200, 2000, v => statsCount.value.creditos = v);
            animateCount(100, 1500, v => statsCount.value.digital = v);
            animateCount(24, 1200, v => statsCount.value.meses = v);
            animateCount(3, 900, v => statsCount.value.modal = v);
        }
    }, { threshold: 0.3 });
    if (statsRef.value) sObs.observe(statsRef.value);

    const tObs = new IntersectionObserver(([e]) => {
        if (e.isIntersecting) { timelineVisible.value = true; tObs.disconnect(); }
    }, { threshold: 0.1 });
    if (timelineRef.value) tObs.observe(timelineRef.value);

    const rObs = new IntersectionObserver(([e]) => {
        if (e.isIntersecting) { reqVisible.value = true; rObs.disconnect(); }
    }, { threshold: 0.1 });
    if (reqRef.value) rObs.observe(reqRef.value);
});

onUnmounted(() => {
    window.removeEventListener('scroll', handleScroll);
});
</script>

<template>
    <Head title="CREA — Programa de Crédito para Emprendedores y Artesanos de Yucatán" />

    <div class="min-h-screen bg-[#FAFAF8] dark:bg-[#0E0508] text-slate-900 dark:text-zinc-100 font-sans transition-colors duration-300">

        <!-- ══════════════════════════════════════════════ NAVBAR ══ -->
        <nav :class="[
            'fixed top-0 inset-x-0 z-50 transition-all duration-300',
            navScrolled
                ? 'bg-white/95 dark:bg-[#1A0B11]/95 backdrop-blur-md shadow-sm shadow-black/5 border-b border-slate-200/60 dark:border-zinc-800/60'
                : 'bg-white/70 dark:bg-[#0E0508]/70 backdrop-blur-sm'
        ]">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 py-3.5 flex items-center justify-between">
                <!-- Logo -->
                <a href="#inicio" class="flex items-center gap-2.5 group">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" class="size-9 transition-transform group-hover:scale-105">
                        <rect width="32" height="32" rx="7" fill="#6B1938"/>
                        <rect x="5" y="21" width="5" height="8" rx="1.5" fill="white" fill-opacity="0.5"/>
                        <rect x="13" y="17" width="5" height="12" rx="1.5" fill="white" fill-opacity="0.82"/>
                        <rect x="21" y="12" width="5" height="17" rx="1.5" fill="#E8A020"/>
                    </svg>
                    <div class="leading-none">
                        <span class="block text-lg font-black tracking-tight text-[#6B1938] dark:text-[#f4a8c4]">CREA</span>
                        <span class="block text-[9px] font-medium text-slate-400 dark:text-zinc-500 uppercase tracking-wider hidden sm:block">IYEM · Gobierno de Yucatán</span>
                    </div>
                </a>

                <!-- Desktop links -->
                <div class="hidden lg:flex items-center gap-7 text-sm font-medium text-slate-600 dark:text-zinc-400">
                    <a href="#modalidades" class="hover:text-[#6B1938] dark:hover:text-[#f4a8c4] transition-colors">Modalidades</a>
                    <a href="#como-funciona" class="hover:text-[#6B1938] dark:hover:text-[#f4a8c4] transition-colors">¿Cómo funciona?</a>
                    <a href="#requisitos" class="hover:text-[#6B1938] dark:hover:text-[#f4a8c4] transition-colors">Requisitos</a>
                    <a href="#calculadora" class="hover:text-[#6B1938] dark:hover:text-[#f4a8c4] transition-colors">Calculadora</a>
                    <a href="#faq" class="hover:text-[#6B1938] dark:hover:text-[#f4a8c4] transition-colors">Preguntas frecuentes</a>
                    <a href="#contacto" class="hover:text-[#6B1938] dark:hover:text-[#f4a8c4] transition-colors">Contacto</a>
                </div>

                <!-- Right actions -->
                <div class="flex items-center gap-2">
                    <button @click="toggleTheme" class="p-2 rounded-xl bg-slate-100 dark:bg-zinc-800 text-slate-500 dark:text-zinc-400 hover:bg-slate-200 dark:hover:bg-zinc-700 transition-colors" aria-label="Cambiar tema">
                        <svg v-if="theme === 'dark'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4">
                            <circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/>
                        </svg>
                        <svg v-else xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4">
                            <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
                        </svg>
                    </button>

                    <Link :href="route('login')" class="hidden md:inline-flex items-center px-4 py-2 rounded-xl text-sm font-semibold text-slate-700 dark:text-zinc-300 hover:bg-slate-100 dark:hover:bg-zinc-800 transition-colors">
                        Mi Portal
                    </Link>
                    <Link :href="route('ciudadano.register')" class="hidden md:inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-bold bg-[#6B1938] text-white hover:bg-[#4E1029] shadow-lg shadow-[#6B1938]/20 transition-all active:scale-95">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="size-4"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                        Solicitar Crédito
                    </Link>

                    <button @click="mobileOpen = !mobileOpen" class="lg:hidden p-2 rounded-xl bg-slate-100 dark:bg-zinc-800 text-slate-600 dark:text-zinc-400 transition-colors" aria-label="Menú">
                        <svg v-if="!mobileOpen" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="size-5"><path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
                        <svg v-else xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="size-5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>

            <!-- Mobile menu -->
            <div v-if="mobileOpen" class="lg:hidden border-t border-slate-100 dark:border-zinc-800 bg-white dark:bg-[#1A0B11] px-4 pb-6 pt-4 space-y-1">
                <a href="#modalidades" @click="mobileOpen=false" class="block px-4 py-3 text-sm font-medium text-slate-600 dark:text-zinc-400 rounded-xl hover:bg-slate-50 dark:hover:bg-zinc-800 transition-colors">Modalidades</a>
                <a href="#como-funciona" @click="mobileOpen=false" class="block px-4 py-3 text-sm font-medium text-slate-600 dark:text-zinc-400 rounded-xl hover:bg-slate-50 dark:hover:bg-zinc-800 transition-colors">¿Cómo funciona?</a>
                <a href="#requisitos" @click="mobileOpen=false" class="block px-4 py-3 text-sm font-medium text-slate-600 dark:text-zinc-400 rounded-xl hover:bg-slate-50 dark:hover:bg-zinc-800 transition-colors">Requisitos</a>
                <a href="#calculadora" @click="mobileOpen=false" class="block px-4 py-3 text-sm font-medium text-slate-600 dark:text-zinc-400 rounded-xl hover:bg-slate-50 dark:hover:bg-zinc-800 transition-colors">Calculadora</a>
                <a href="#faq" @click="mobileOpen=false" class="block px-4 py-3 text-sm font-medium text-slate-600 dark:text-zinc-400 rounded-xl hover:bg-slate-50 dark:hover:bg-zinc-800 transition-colors">Preguntas frecuentes</a>
                <a href="#contacto" @click="mobileOpen=false" class="block px-4 py-3 text-sm font-medium text-slate-600 dark:text-zinc-400 rounded-xl hover:bg-slate-50 dark:hover:bg-zinc-800 transition-colors">Contacto</a>
                <div class="pt-4 space-y-2.5 border-t border-slate-100 dark:border-zinc-800">
                    <Link :href="route('login')" class="flex items-center justify-center w-full py-3 rounded-xl border-2 border-slate-200 dark:border-zinc-700 text-sm font-bold text-slate-700 dark:text-zinc-300 hover:border-[#6B1938] transition-colors">
                        Iniciar sesión
                    </Link>
                    <Link :href="route('ciudadano.register')" class="flex items-center justify-center w-full py-3 rounded-xl bg-[#6B1938] text-white text-sm font-bold shadow-lg shadow-[#6B1938]/20">
                        Solicitar mi crédito
                    </Link>
                </div>
            </div>
        </nav>

        <!-- ══════════════════════════════════════════════ HERO ══ -->
        <section id="inicio" class="relative overflow-hidden min-h-screen flex items-center pt-24 pb-20">
            <div class="absolute inset-0 pointer-events-none" aria-hidden="true">
                <div class="absolute -top-20 right-0 w-[600px] h-[600px] rounded-full bg-[#6B1938]/5 dark:bg-[#6B1938]/10 blur-3xl"></div>
                <div class="absolute bottom-0 -left-20 w-96 h-96 rounded-full bg-[#E8A020]/5 dark:bg-[#E8A020]/8 blur-3xl"></div>
                <div class="absolute inset-0 opacity-[0.025] dark:opacity-[0.04]" style="background-image: radial-gradient(#6B1938 1px, transparent 1px); background-size: 32px 32px;"></div>
            </div>

            <div class="relative max-w-7xl mx-auto px-4 sm:px-6 w-full">
                <div class="grid lg:grid-cols-[1fr_480px] gap-12 xl:gap-16 items-center">

                    <div class="space-y-8 max-w-xl">
                        <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-[#6B1938]/8 dark:bg-[#6B1938]/15 border border-[#6B1938]/20 dark:border-[#6B1938]/30 hero-badge">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" class="size-4 text-[#6B1938] dark:text-[#f4a8c4]"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                            <span class="text-xs font-bold tracking-wider uppercase text-[#6B1938] dark:text-[#f4a8c4]">Programa del Gobierno de Yucatán · IYEM</span>
                        </div>

                        <div class="space-y-3">
                            <h1 class="text-5xl sm:text-6xl xl:text-7xl font-black text-slate-950 dark:text-white leading-[1.05] tracking-tight">
                                Impulsa tu negocio con el crédito que
                                <span class="text-[#6B1938] dark:text-[#f4a8c4]"> mereces.</span>
                            </h1>
                        </div>

                        <p class="text-lg sm:text-xl text-slate-500 dark:text-zinc-400 leading-relaxed">
                            <strong class="text-slate-700 dark:text-zinc-200 font-semibold">Crédito para el Renacimiento de Emprendedores y Artesanos</strong> — programa del IYEM con cobertura en los 106 municipios de Yucatán. Tasas de 0%, 5% y 7% según modalidad, plazos hasta 24 meses.
                        </p>

                        <div class="flex flex-col sm:flex-row gap-3 pt-2">
                            <Link :href="route('ciudadano.register')"
                                class="inline-flex items-center justify-center gap-2 px-8 py-4 rounded-2xl bg-[#6B1938] text-white font-bold text-base shadow-xl shadow-[#6B1938]/25 hover:bg-[#4E1029] transition-all active:scale-[0.97]">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="size-5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                                Solicitar mi crédito
                            </Link>
                            <a href="#calculadora"
                                class="inline-flex items-center justify-center gap-2 px-8 py-4 rounded-2xl border-2 border-slate-200 dark:border-zinc-700 text-slate-700 dark:text-zinc-300 font-bold text-base hover:border-[#6B1938] dark:hover:border-[#f4a8c4] transition-all">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-5"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/><path d="M7 8h.01M12 8h.01M17 8h.01M7 12h.01M12 12h.01M17 12h.01"/></svg>
                                Calcular mi crédito
                            </a>
                        </div>

                        <div class="flex flex-wrap gap-x-8 gap-y-3 pt-4 border-t border-slate-100 dark:border-zinc-800 text-xs text-slate-400 dark:text-zinc-500 font-medium">
                            <span>106 municipios de Yucatán</span>
                            <span>·</span>
                            <span>Vigencia hasta 2030</span>
                            <span>·</span>
                            <span>Proceso 100% digital</span>
                        </div>
                    </div>

                    <!-- SVG Illustration -->
                    <div class="hidden lg:flex items-center justify-center">
                        <svg viewBox="0 0 480 500" xmlns="http://www.w3.org/2000/svg" class="w-full max-w-md" aria-hidden="true">
                            <circle cx="240" cy="250" r="210" fill="#6B1938" fill-opacity="0.04"/>
                            <circle cx="240" cy="250" r="170" fill="#6B1938" fill-opacity="0.03"/>
                            <circle cx="240" cy="250" r="130" fill="#E8A020" fill-opacity="0.03"/>

                            <rect x="80" y="130" width="310" height="198" rx="14" fill="#1A0B11" class="ill-shadow"/>
                            <rect x="88" y="138" width="294" height="182" rx="9" fill="#FDF6F8"/>
                            <rect x="88" y="138" width="294" height="26" rx="9" fill="#6B1938"/>
                            <rect x="88" y="152" width="294" height="12" fill="#6B1938"/>
                            <rect x="97" y="143" width="32" height="12" rx="3" fill="#E8A020" fill-opacity="0.8"/>
                            <text x="101" y="153" font-family="Arial" font-weight="700" font-size="8" fill="white">CREA</text>
                            <circle cx="344" cy="151" r="3" fill="white" fill-opacity="0.35"/>
                            <circle cx="358" cy="151" r="3" fill="white" fill-opacity="0.35"/>
                            <circle cx="372" cy="151" r="3" fill="white" fill-opacity="0.35"/>

                            <rect x="100" y="176" width="110" height="7" rx="3" fill="#6B1938" fill-opacity="0.25"/>
                            <rect x="100" y="188" width="75" height="5" rx="2" fill="#9CA3AF" fill-opacity="0.4"/>

                            <rect x="248" y="178" width="22" height="52" rx="4" fill="#6B1938" fill-opacity="0.25" class="ill-bar1"/>
                            <rect x="275" y="162" width="22" height="68" rx="4" fill="#6B1938" fill-opacity="0.45" class="ill-bar2"/>
                            <rect x="302" y="148" width="22" height="82" rx="4" fill="#E8A020" fill-opacity="0.7" class="ill-bar3"/>
                            <line x1="245" y1="232" x2="327" y2="232" stroke="#6B1938" stroke-width="1" stroke-opacity="0.2"/>
                            <path d="M259 205 Q287 183 315 150" stroke="#E8A020" stroke-width="2" fill="none" stroke-dasharray="4 3" stroke-linecap="round" fill-opacity="0.8"/>

                            <circle cx="108" cy="208" r="4" fill="#6B1938" fill-opacity="0.6"/>
                            <rect x="118" y="204" width="80" height="5" rx="2" fill="#9CA3AF" fill-opacity="0.35"/>
                            <circle cx="108" cy="222" r="4" fill="#E8A020" fill-opacity="0.7"/>
                            <rect x="118" y="218" width="60" height="5" rx="2" fill="#9CA3AF" fill-opacity="0.3"/>
                            <circle cx="108" cy="236" r="4" fill="#6B1938" fill-opacity="0.4"/>
                            <rect x="118" y="232" width="70" height="5" rx="2" fill="#9CA3AF" fill-opacity="0.25"/>

                            <rect x="100" y="298" width="118" height="14" rx="7" fill="#6B1938" fill-opacity="0.85"/>
                            <text x="117" y="309" font-family="Arial" font-weight="600" font-size="7" fill="white">Solicitar Crédito</text>

                            <path d="M80 328 L68 352 L412 352 L400 328Z" fill="#1A0B11"/>
                            <rect x="64" y="350" width="352" height="14" rx="5" fill="#1E0D14"/>
                            <rect x="188" y="354" width="104" height="7" rx="3" fill="#0E0508" fill-opacity="0.5"/>

                            <rect x="340" y="68" width="20" height="62" rx="5" fill="#6B1938" fill-opacity="0.2" class="float-a"/>
                            <rect x="368" y="44" width="20" height="86" rx="5" fill="#6B1938" fill-opacity="0.35" class="float-a"/>
                            <rect x="396" y="18" width="20" height="112" rx="5" fill="#E8A020" fill-opacity="0.65" class="float-b"/>
                            <path d="M350 65 Q380 44 406 20" stroke="#E8A020" stroke-width="2.5" fill="none" stroke-dasharray="5 3" stroke-linecap="round" fill-opacity="0.8"/>
                            <polygon points="401,12 416,22 404,32" fill="#E8A020" fill-opacity="0.8"/>

                            <circle cx="62" cy="165" r="26" fill="#E8A020" fill-opacity="0.85" class="float-c"/>
                            <text x="51" y="174" font-family="Arial" font-weight="700" font-size="22" fill="white">$</text>

                            <circle cx="440" cy="108" r="18" fill="#E8A020" fill-opacity="0.65" class="float-b"/>
                            <text x="432" y="115" font-family="Arial" font-weight="700" font-size="15" fill="white">$</text>

                            <g class="float-a" fill-opacity="0.7">
                                <path d="M52 474 Q58 445 52 418" stroke="#6B1938" stroke-width="3.5" fill="none" stroke-linecap="round"/>
                                <path d="M52 455 Q76 440 85 420 Q63 436 52 455" fill="#6B1938" fill-opacity="0.5"/>
                                <path d="M52 438 Q28 424 20 404 Q42 420 52 438" fill="#6B1938" fill-opacity="0.4"/>
                                <path d="M52 420 Q74 406 82 384 Q58 400 52 420" fill="#6B1938" fill-opacity="0.55"/>
                                <circle cx="52" cy="418" r="4.5" fill="#6B1938" fill-opacity="0.55"/>
                            </g>

                            <circle cx="430" cy="390" r="28" fill="white" stroke="#6B1938" stroke-width="2.5" fill-opacity="0.95"/>
                            <path d="M419 390 L428 400 L444 378" stroke="#6B1938" stroke-width="3.5" fill="none" stroke-linecap="round" stroke-linejoin="round"/>

                            <path d="M440 250 L443 243 L446 250 L453 253 L446 256 L443 263 L440 256 L433 253 Z" fill="#E8A020" fill-opacity="0.65"/>
                            <path d="M38 295 L41 289 L44 295 L50 298 L44 301 L41 307 L38 301 L32 298 Z" fill="#E8A020" fill-opacity="0.5"/>
                            <circle cx="455" cy="340" r="5" fill="#6B1938" fill-opacity="0.2"/>
                            <circle cx="30" cy="400" r="6" fill="#E8A020" fill-opacity="0.25"/>
                            <circle cx="450" cy="460" r="8" fill="#6B1938" fill-opacity="0.1"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="absolute bottom-8 left-1/2 -translate-x-1/2 hidden sm:flex flex-col items-center gap-2 animate-bounce">
                <span class="text-xs text-slate-400 dark:text-zinc-600 font-medium tracking-wider uppercase">Descubre más</span>
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" class="size-5 text-slate-300 dark:text-zinc-700"><path d="m6 9 6 6 6-6"/></svg>
            </div>
        </section>

        <!-- ══════════════════════════════════════════════ STATS BAND ══ -->
        <section ref="statsRef" class="py-16 bg-[#6B1938] dark:bg-[#4E1029]">
            <div class="max-w-7xl mx-auto px-4 sm:px-6">
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-8 text-white text-center">
                    <div class="space-y-1">
                        <p class="text-4xl sm:text-5xl font-black">{{ statsCount.creditos.toLocaleString('es-MX') }}+</p>
                        <p class="text-sm font-medium text-[#F4BAC8] uppercase tracking-wider">Emprendedores beneficiados</p>
                    </div>
                    <div class="space-y-1">
                        <p class="text-4xl sm:text-5xl font-black">{{ statsCount.modal }}</p>
                        <p class="text-sm font-medium text-[#F4BAC8] uppercase tracking-wider">Modalidades de crédito</p>
                    </div>
                    <div class="space-y-1">
                        <p class="text-4xl sm:text-5xl font-black">{{ statsCount.meses }}<span class="text-2xl font-bold text-[#F4BAC8]"> meses</span></p>
                        <p class="text-sm font-medium text-[#F4BAC8] uppercase tracking-wider">Plazo disponible</p>
                    </div>
                    <div class="space-y-1">
                        <p class="text-4xl sm:text-5xl font-black">{{ statsCount.digital }}%</p>
                        <p class="text-sm font-medium text-[#F4BAC8] uppercase tracking-wider">Proceso totalmente digital</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ══════════════════════════════════════════════ MODALIDADES ══ -->
        <section id="modalidades" class="py-24 bg-[#FAFAF8] dark:bg-[#0E0508]">
            <div class="max-w-7xl mx-auto px-4 sm:px-6">
                <div class="text-center max-w-2xl mx-auto mb-14 space-y-3">
                    <span class="text-xs font-black uppercase tracking-[0.2em] text-[#6B1938] dark:text-[#f4a8c4]">Modalidades del programa</span>
                    <h2 class="text-4xl sm:text-5xl font-black text-slate-950 dark:text-white leading-tight">
                        Encuentra el crédito diseñado para ti.
                    </h2>
                    <p class="text-lg text-slate-500 dark:text-zinc-400">
                        Tres modalidades con condiciones específicas para cada tipo de proyecto.
                    </p>
                </div>

                <div class="overflow-hidden" @touchstart="onTouchStart" @touchend="onTouchEnd">
                    <div
                        class="flex md:grid md:grid-cols-3 gap-6"
                        :style="isMobile ? { transform: `translateX(calc(-${currentSlide * 100}% - ${currentSlide * 24}px))`, transition: 'transform 0.4s cubic-bezier(0.4,0,0.2,1)' } : {}">
                        <div v-for="(m, i) in MODALIDADES" :key="m.nombre"
                            class="modal-card w-full flex-shrink-0 md:flex-shrink md:w-auto rounded-3xl border p-8 space-y-6 cursor-default"
                            :class="[m.bg, m.border]">
                            <div class="w-14 h-14 rounded-2xl flex items-center justify-center" :style="{ backgroundColor: m.accent + '18' }">
                                <svg v-if="i === 0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="size-7" :style="{ color: m.accent }">
                                    <path d="M12 2a10 10 0 0 1 10 10c0 5.52-4.48 10-10 10S2 17.52 2 12"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><line x1="9" y1="9" x2="9.01" y2="9"/><line x1="15" y1="9" x2="15.01" y2="9"/>
                                </svg>
                                <svg v-if="i === 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="size-7" :style="{ color: m.accent }">
                                    <rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/><line x1="12" y1="12" x2="12" y2="16"/><line x1="10" y1="14" x2="14" y2="14"/>
                                </svg>
                                <svg v-if="i === 2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="size-7" :style="{ color: m.accent }">
                                    <path d="M2 22a10 10 0 0 1 18-6"/><path d="M12 2a10 10 0 0 1 6 18"/><path d="M12 12a5 5 0 0 0-5 5"/><circle cx="12" cy="7" r="2"/>
                                </svg>
                            </div>

                            <div class="space-y-2">
                                <div class="flex items-start justify-between gap-2">
                                    <h3 class="text-xl font-black text-slate-950 dark:text-white">{{ m.nombre }}</h3>
                                    <span class="shrink-0 text-xs font-bold px-2.5 py-1 rounded-full" :class="m.badge">{{ m.tasa }}</span>
                                </div>
                                <p class="text-xs font-semibold" :style="{ color: m.accent }">{{ m.subtitulo }}</p>
                                <p class="text-sm text-slate-500 dark:text-zinc-400 leading-relaxed">{{ m.descripcion }}</p>
                            </div>

                            <ul class="space-y-2.5">
                                <li v-for="b in m.beneficios" :key="b" class="flex items-center gap-3 text-sm text-slate-700 dark:text-zinc-300">
                                    <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="m.dot"></span>
                                    {{ b }}
                                </li>
                            </ul>

                            <div class="pt-2 border-t border-black/5 dark:border-white/5 flex items-center justify-between">
                                <div class="space-y-1">
                                    <div>
                                        <p class="text-xs text-slate-400 dark:text-zinc-500 uppercase tracking-wider">Monto</p>
                                        <p class="text-sm font-bold text-slate-700 dark:text-zinc-300">{{ m.monto }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-slate-400 dark:text-zinc-500 uppercase tracking-wider">Plazo</p>
                                        <p class="text-sm font-bold text-slate-700 dark:text-zinc-300">{{ m.plazo }}</p>
                                    </div>
                                </div>
                                <Link :href="route('ciudadano.register')" class="inline-flex items-center gap-1.5 text-sm font-bold px-4 py-2 rounded-xl transition-colors" :style="{ color: m.accent, backgroundColor: m.accent + '12' }">
                                    Solicitar
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" class="size-3.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Mobile carousel controls -->
                <div class="flex items-center justify-center gap-6 mt-8 md:hidden">
                    <button @click="prevSlide" :disabled="currentSlide === 0"
                        class="w-10 h-10 rounded-full border-2 border-slate-200 dark:border-zinc-700 flex items-center justify-center text-slate-500 dark:text-zinc-400 disabled:opacity-30 hover:border-[#6B1938] hover:text-[#6B1938] transition-all">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" class="size-4"><path d="m15 18-6-6 6-6"/></svg>
                    </button>
                    <div class="flex gap-2">
                        <button v-for="(_, i) in MODALIDADES" :key="i" @click="currentSlide = i"
                            :class="['w-2.5 h-2.5 rounded-full transition-all', i === currentSlide ? 'bg-[#6B1938] w-6' : 'bg-slate-200 dark:bg-zinc-700']">
                        </button>
                    </div>
                    <button @click="nextSlide" :disabled="currentSlide === 2"
                        class="w-10 h-10 rounded-full border-2 border-slate-200 dark:border-zinc-700 flex items-center justify-center text-slate-500 dark:text-zinc-400 disabled:opacity-30 hover:border-[#6B1938] hover:text-[#6B1938] transition-all">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" class="size-4"><path d="m9 18 6-6-6-6"/></svg>
                    </button>
                </div>
            </div>
        </section>

        <!-- ══════════════════════════════════════════════ CÓMO FUNCIONA ══ -->
        <section id="como-funciona" class="py-24 bg-white dark:bg-[#160910] border-y border-slate-100/60 dark:border-zinc-800/60">
            <div class="max-w-7xl mx-auto px-4 sm:px-6">
                <div class="text-center max-w-2xl mx-auto mb-16 space-y-3">
                    <span class="text-xs font-black uppercase tracking-[0.2em] text-[#6B1938] dark:text-[#f4a8c4]">¿Cómo funciona?</span>
                    <h2 class="text-4xl sm:text-5xl font-black text-slate-950 dark:text-white">
                        Simple, rápido y 100% digital.
                    </h2>
                    <p class="text-lg text-slate-500 dark:text-zinc-400">
                        Sigue estos siete pasos para obtener tu crédito CREA desde cualquier dispositivo.
                    </p>
                </div>

                <div ref="timelineRef" class="relative">
                    <div class="hidden lg:block absolute top-8 left-0 right-0 h-0.5 bg-slate-100 dark:bg-zinc-800 mx-16" aria-hidden="true">
                        <div class="h-full bg-[#6B1938]/30 dark:bg-[#6B1938]/50 transition-all duration-[2000ms] ease-out"
                            :style="{ width: timelineVisible ? '100%' : '0%' }"></div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-7 gap-6 lg:gap-4">
                        <div v-for="(paso, i) in PASOS" :key="paso.num"
                            class="step-card relative flex flex-col items-center text-center px-3 pt-0 pb-4 space-y-3"
                            :class="timelineVisible ? 'step-visible' : ''"
                            :style="{ transitionDelay: timelineVisible ? `${i * 80}ms` : '0ms' }">
                            <div class="relative z-10 w-16 h-16 rounded-2xl flex items-center justify-center font-black text-lg transition-all duration-500"
                                :class="timelineVisible ? 'bg-[#6B1938] text-white shadow-lg shadow-[#6B1938]/25' : 'bg-slate-100 dark:bg-zinc-800 text-slate-400 dark:text-zinc-600'">
                                {{ paso.num }}
                            </div>
                            <h3 class="text-sm font-black text-slate-900 dark:text-white leading-snug">{{ paso.titulo }}</h3>
                            <p class="text-xs text-slate-500 dark:text-zinc-400 leading-relaxed">{{ paso.desc }}</p>
                        </div>
                    </div>
                </div>

                <div class="mt-14 text-center">
                    <Link :href="route('ciudadano.register')"
                        class="inline-flex items-center gap-2 px-8 py-4 rounded-2xl bg-[#6B1938] text-white font-bold text-base shadow-xl shadow-[#6B1938]/25 hover:bg-[#4E1029] transition-all active:scale-[0.97]">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="size-5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                        Comenzar ahora
                    </Link>
                </div>
            </div>
        </section>

        <!-- ══════════════════════════════════════════════ REQUISITOS ══ -->
        <section id="requisitos" class="py-24 bg-[#FAFAF8] dark:bg-[#0E0508]">
            <div class="max-w-7xl mx-auto px-4 sm:px-6">
                <div class="grid lg:grid-cols-2 gap-16 items-start">
                    <div ref="reqRef" class="space-y-8">
                        <div class="space-y-3">
                            <span class="text-xs font-black uppercase tracking-[0.2em] text-[#6B1938] dark:text-[#f4a8c4]">Requisitos</span>
                            <h2 class="text-4xl sm:text-5xl font-black text-slate-950 dark:text-white leading-tight">
                                ¿Qué necesito para solicitar?
                            </h2>
                            <p class="text-lg text-slate-500 dark:text-zinc-400">
                                Los requisitos son sencillos. Si tienes un proyecto en Yucatán, es probable que ya los cumplas.
                            </p>
                        </div>

                        <ul class="space-y-3">
                            <li v-for="(req, i) in REQUISITOS" :key="req"
                                class="flex items-start gap-4 p-4 bg-white dark:bg-[#1A0B11] rounded-2xl border border-slate-100/80 dark:border-zinc-800/80 req-item"
                                :class="reqVisible ? 'req-visible' : ''"
                                :style="{ transitionDelay: reqVisible ? `${i * 60}ms` : '0ms' }">
                                <div class="w-8 h-8 rounded-xl bg-[#6B1938]/10 dark:bg-[#6B1938]/20 flex items-center justify-center shrink-0 mt-0.5">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke-linecap="round" stroke-linejoin="round" class="size-4">
                                        <path class="req-check" :class="reqVisible ? 'req-draw' : ''" d="M5 13l4 4L19 7" stroke="#6B1938" stroke-width="2.5" fill="none" :style="{ transitionDelay: reqVisible ? `${i * 60 + 200}ms` : '0ms' }"/>
                                    </svg>
                                </div>
                                <span class="text-sm font-medium text-slate-700 dark:text-zinc-300 leading-relaxed">{{ req }}</span>
                            </li>
                        </ul>
                    </div>

                    <!-- Right: CTA card -->
                    <div class="lg:sticky lg:top-24">
                        <div class="relative overflow-hidden rounded-3xl p-10 space-y-8 bg-gradient-to-br from-[#6B1938] to-[#4E1029] shadow-2xl shadow-[#6B1938]/25">
                            <div class="absolute -top-12 -right-12 w-48 h-48 rounded-full bg-white/5"></div>
                            <div class="absolute -bottom-8 -left-8 w-36 h-36 rounded-full bg-[#E8A020]/10"></div>

                            <div class="relative space-y-3">
                                <div class="w-12 h-12 rounded-2xl bg-white/10 flex items-center justify-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-6 text-[#E8A020]"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                </div>
                                <h3 class="text-3xl font-black text-white">¿Listo para comenzar?</h3>
                                <p class="text-[#F4BAC8] leading-relaxed text-sm">
                                    El proceso es completamente en línea. Crea tu cuenta hoy y da el primer paso hacia el crédito que tu proyecto necesita.
                                </p>
                            </div>

                            <div class="relative space-y-3">
                                <Link :href="route('ciudadano.register')"
                                    class="flex items-center justify-center gap-2 w-full py-4 bg-white text-[#6B1938] rounded-2xl font-bold text-base hover:bg-rose-50 transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="size-5"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>
                                    Crear mi cuenta
                                </Link>
                                <Link :href="route('login')"
                                    class="flex items-center justify-center gap-2 w-full py-4 border-2 border-white/25 text-white rounded-2xl font-bold text-sm hover:border-white/50 transition-colors">
                                    Ya tengo cuenta — Iniciar sesión
                                </Link>
                            </div>

                            <p class="relative text-center text-xs text-[#F0A8C0] leading-relaxed">
                                Programa sujeto a disponibilidad presupuestal.<br>
                                <Link :href="route('login')" class="underline hover:text-white transition-colors">Acceso personal operativo del IYEM</Link>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ══════════════════════════════════════════════ CALCULADORA ══ -->
        <section id="calculadora" class="py-24 bg-white dark:bg-[#160910] border-y border-slate-100/60 dark:border-zinc-800/60">
            <div class="max-w-6xl mx-auto px-4 sm:px-6">
                <div class="text-center max-w-xl mx-auto mb-14 space-y-3">
                    <span class="text-xs font-black uppercase tracking-[0.2em] text-[#6B1938] dark:text-[#f4a8c4]">Simulador de crédito</span>
                    <h2 class="text-4xl sm:text-5xl font-black text-slate-950 dark:text-white">Calcula tu crédito CREA</h2>
                    <p class="text-lg text-slate-500 dark:text-zinc-400">Elige monto, plazo y modalidad para estimar tu pago mensual al instante.</p>
                </div>

                <div class="grid lg:grid-cols-[1fr_420px] gap-8 items-start">

                    <!-- Panel de inputs -->
                    <div class="bg-[#FAFAF8] dark:bg-[#1A0B11] rounded-3xl border border-slate-200/80 dark:border-zinc-800/80 p-8 space-y-8">

                        <!-- Monto -->
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <label class="text-xs font-black uppercase tracking-wider text-slate-500 dark:text-zinc-400">Monto solicitado</label>
                                <span class="text-lg font-black text-[#6B1938] dark:text-[#f4a8c4]">{{ formatCurrency(calc.monto) }}</span>
                            </div>
                            <input v-model.number="calc.monto" type="range" :min="montoMin" :max="montoMax" :step="montoMin <= 25000 ? 500 : 1000"
                                class="w-full h-2 rounded-full appearance-none cursor-pointer slider-guinda"/>
                            <div class="flex justify-between text-xs text-slate-400 dark:text-zinc-500 font-medium">
                                <span>{{ formatCurrency(montoMin) }}</span>
                                <span>{{ formatCurrency(montoMax) }}</span>
                            </div>
                            <div class="relative mt-1">
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 font-bold text-sm">$</span>
                                <input v-model.number="calc.monto" type="number" :min="montoMin" :max="montoMax" step="500"
                                    :class="['w-full pl-8 pr-4 py-3 rounded-xl border bg-white dark:bg-zinc-800/50 text-slate-900 dark:text-white font-bold text-base focus:outline-none focus:ring-2 transition-all',
                                        montoValido ? 'border-slate-200 dark:border-zinc-700 focus:ring-[#6B1938] focus:border-[#6B1938]' : 'border-red-400 focus:ring-red-400 focus:border-red-400']"/>
                            </div>
                            <p v-if="!montoValido" class="text-xs text-red-500 font-medium">
                                El monto debe estar entre {{ formatCurrency(montoMin) }} y {{ formatCurrency(montoMax) }} para la modalidad seleccionada.
                            </p>
                        </div>

                        <!-- Plazo -->
                        <div class="space-y-3">
                            <label class="block text-xs font-black uppercase tracking-wider text-slate-500 dark:text-zinc-400">Plazo de pago</label>
                            <div class="grid grid-cols-3 gap-2">
                                <button v-for="p in plazosDisponibles" :key="p"
                                    @click="calc.plazo = p"
                                    :class="['py-3.5 rounded-xl text-sm transition-all font-bold', calc.plazo === p
                                        ? 'bg-[#6B1938] text-white shadow-lg shadow-[#6B1938]/30 scale-[1.02]'
                                        : 'bg-white dark:bg-zinc-800 border border-slate-200 dark:border-zinc-700 text-slate-600 dark:text-zinc-300 hover:border-[#6B1938] hover:text-[#6B1938]']">
                                    {{ p }}<span class="block text-[10px] font-medium mt-0.5 opacity-70">meses</span>
                                </button>
                            </div>
                        </div>

                        <!-- Modalidad -->
                        <div class="space-y-3">
                            <label class="block text-xs font-black uppercase tracking-wider text-slate-500 dark:text-zinc-400">Modalidad</label>
                            <div class="space-y-2">
                                <button v-for="m in MODALIDADES" :key="m.nombre"
                                    @click="calc.modalidad = m.nombre"
                                    :class="['w-full flex items-center gap-3 p-4 rounded-xl text-sm font-semibold transition-all text-left border-2', calc.modalidad === m.nombre
                                        ? 'border-[#6B1938] bg-[#6B1938]/5 dark:bg-[#6B1938]/10'
                                        : 'border-slate-200 dark:border-zinc-700 bg-white dark:bg-zinc-800/50 hover:border-[#6B1938]/40']">
                                    <span class="w-3 h-3 rounded-full shrink-0" :class="m.dot"></span>
                                    <span class="flex-1 text-slate-800 dark:text-zinc-200">{{ m.nombre }}</span>
                                    <span class="text-xs px-2 py-0.5 rounded-full font-medium" :class="m.badge">{{ m.tasa }}</span>
                                    <svg v-if="calc.modalidad === m.nombre" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" class="size-4 text-[#6B1938] shrink-0"><path d="M20 6L9 17l-5-5"/></svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Panel de resultados -->
                    <div class="space-y-4 lg:sticky lg:top-24">

                        <!-- Resultado principal -->
                        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-[#6B1938] to-[#4E1029] p-8 text-white shadow-2xl shadow-[#6B1938]/30">
                            <div class="absolute -top-10 -right-10 w-40 h-40 rounded-full bg-white/5 pointer-events-none"></div>
                            <div class="absolute -bottom-8 -left-8 w-32 h-32 rounded-full bg-[#E8A020]/10 pointer-events-none"></div>
                            <div class="relative space-y-6">
                                <div>
                                    <p class="text-[#F4BAC8] text-xs font-bold uppercase tracking-widest mb-2">Pago mensual estimado</p>
                                    <p class="text-5xl sm:text-6xl font-black tracking-tight transition-all duration-300">
                                        {{ (calcResult && montoValido) ? formatCurrency(calcResult.mensual) : '—' }}
                                    </p>
                                    <p v-if="prorrogaMeses > 0" class="mt-2 text-[10px] text-[#F4BAC8] font-medium">
                                        ★ Primer pago a partir del mes {{ prorrogaMeses + 1 }} ({{ prorrogaMeses }} meses de prórroga)
                                    </p>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-5 border-t border-white/15">
                                    <div>
                                        <p class="text-[#F4BAC8] text-[10px] uppercase tracking-widest mb-1 font-bold">Total a pagar</p>
                                        <p class="text-xl font-black">{{ (calcResult && montoValido) ? formatCurrency(calcResult.total) : '—' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-[#F4BAC8] text-[10px] uppercase tracking-widest mb-1 font-bold">Total intereses</p>
                                        <p class="text-xl font-black text-[#E8A020]">{{ (calcResult && montoValido) ? formatCurrency(calcResult.intereses) : '—' }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Desglose -->
                        <div v-if="calcResult" class="bg-[#FAFAF8] dark:bg-[#1A0B11] rounded-3xl border border-slate-200/80 dark:border-zinc-800/80 p-6 space-y-4">
                            <h4 class="text-sm font-black text-slate-700 dark:text-zinc-200 uppercase tracking-wide">Desglose</h4>

                            <div class="space-y-2.5 text-sm">
                                <div class="flex justify-between items-center">
                                    <span class="text-slate-500 dark:text-zinc-400">Monto del crédito</span>
                                    <span class="font-bold text-slate-800 dark:text-zinc-200">{{ formatCurrency(calc.monto) }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-slate-500 dark:text-zinc-400">Plazo</span>
                                    <span class="font-bold text-slate-800 dark:text-zinc-200">{{ calc.plazo }} meses</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-slate-500 dark:text-zinc-400">Tasa anual referencial</span>
                                    <span class="font-bold text-[#6B1938] dark:text-[#f4a8c4]">
                                        {{ tasaActual === 0 ? '0% — Sin intereses' : `${(tasaActual * 100).toFixed(0)}% anual` }}
                                    </span>
                                </div>
                            </div>

                            <!-- Barra capital vs intereses -->
                            <div v-if="tasaActual > 0" class="pt-2 space-y-2">
                                <div class="flex h-3 rounded-full overflow-hidden">
                                    <div class="bg-[#6B1938] transition-all duration-500 ease-out" :style="{ width: `${(calc.monto / calcResult.total) * 100}%` }"></div>
                                    <div class="bg-[#E8A020] flex-1 transition-all duration-500"></div>
                                </div>
                                <div class="flex justify-between text-xs text-slate-500 dark:text-zinc-400">
                                    <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-sm bg-[#6B1938] inline-block"></span>Capital ({{ (calc.monto / calcResult.total * 100).toFixed(0) }}%)</span>
                                    <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-sm bg-[#E8A020] inline-block"></span>Intereses ({{ (calcResult.intereses / calcResult.total * 100).toFixed(0) }}%)</span>
                                </div>
                            </div>
                            <div v-else class="pt-2">
                                <div class="flex h-3 rounded-full overflow-hidden bg-[#6B1938]"></div>
                                <p class="mt-1.5 text-xs text-slate-500 dark:text-zinc-400 text-center">100% capital — sin intereses ordinarios</p>
                            </div>
                        </div>

                        <!-- Disclaimer + CTA -->
                        <div class="space-y-3">
                            <p class="text-center text-xs text-slate-400 dark:text-zinc-500 leading-relaxed">
                                Valores estimados con fines informativos. El monto, tasa y condiciones definitivos se determinan durante el proceso de solicitud oficial.
                            </p>
                            <Link :href="route('ciudadano.register')"
                                class="flex items-center justify-center gap-2 w-full py-4 rounded-2xl bg-[#6B1938] text-white font-bold text-base shadow-xl shadow-[#6B1938]/25 hover:bg-[#4E1029] transition-all active:scale-[0.97]">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="size-5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                                Solicitar mi crédito ahora
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ══════════════════════════════════════════════ FAQ ══ -->
        <section id="faq" class="py-24 bg-[#FAFAF8] dark:bg-[#0E0508]">
            <div class="max-w-3xl mx-auto px-4 sm:px-6">
                <div class="text-center mb-14 space-y-3">
                    <span class="text-xs font-black uppercase tracking-[0.2em] text-[#6B1938] dark:text-[#f4a8c4]">Preguntas frecuentes</span>
                    <h2 class="text-4xl sm:text-5xl font-black text-slate-950 dark:text-white">Todo lo que necesitas saber.</h2>
                </div>

                <div class="space-y-3">
                    <div v-for="(faq, i) in FAQS" :key="i"
                        class="rounded-2xl border bg-white dark:bg-[#1A0B11] overflow-hidden transition-colors"
                        :class="openFaq === i ? 'border-[#6B1938]/30 dark:border-[#6B1938]/40' : 'border-slate-100/80 dark:border-zinc-800/80'">
                        <button
                            @click="toggleFaq(i)"
                            class="w-full flex items-center justify-between gap-4 px-6 py-5 text-left"
                            :aria-expanded="openFaq === i">
                            <span class="font-bold text-slate-900 dark:text-white text-sm sm:text-base">{{ faq.pregunta }}</span>
                            <span class="shrink-0 w-7 h-7 rounded-lg flex items-center justify-center transition-all duration-300"
                                :class="openFaq === i ? 'bg-[#6B1938] text-white rotate-45' : 'bg-slate-100 dark:bg-zinc-800 text-slate-500 dark:text-zinc-400'">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" class="size-4"><path d="M12 5v14M5 12h14"/></svg>
                            </span>
                        </button>
                        <div class="faq-body" :style="{ maxHeight: openFaq === i ? '400px' : '0px' }">
                            <p class="px-6 pb-6 text-sm text-slate-600 dark:text-zinc-400 leading-relaxed">{{ faq.respuesta }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ══════════════════════════════════════════════ CONTACTO ══ -->
        <section id="contacto" class="py-24 bg-white dark:bg-[#160910] border-y border-slate-100/60 dark:border-zinc-800/60">
            <div class="max-w-7xl mx-auto px-4 sm:px-6">
                <div class="text-center max-w-2xl mx-auto mb-14 space-y-3">
                    <span class="text-xs font-black uppercase tracking-[0.2em] text-[#6B1938] dark:text-[#f4a8c4]">Contacto</span>
                    <h2 class="text-4xl sm:text-5xl font-black text-slate-950 dark:text-white">¿Tienes preguntas sobre CREA?</h2>
                    <p class="text-lg text-slate-500 dark:text-zinc-400">Escríbenos y te responderemos a la brevedad al correo que nos indiques.</p>
                </div>

                <!-- Info rápida -->
                <div class="flex flex-wrap justify-center gap-6 mb-12">
                    <a href="mailto:crea@iyemyucatan.com" class="flex items-center gap-3 px-5 py-3 rounded-2xl bg-[#FAFAF8] dark:bg-[#1A0B11] border border-slate-200/80 dark:border-zinc-800/80 text-sm font-semibold text-slate-700 dark:text-zinc-300 hover:border-[#6B1938] transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" class="size-4 text-[#6B1938] dark:text-[#f4a8c4]"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                        crea@iyemyucatan.com
                    </a>
                    <a href="tel:+529999412170" class="flex items-center gap-3 px-5 py-3 rounded-2xl bg-[#FAFAF8] dark:bg-[#1A0B11] border border-slate-200/80 dark:border-zinc-800/80 text-sm font-semibold text-slate-700 dark:text-zinc-300 hover:border-[#6B1938] transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" class="size-4 text-[#6B1938] dark:text-[#f4a8c4]"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.6 1.07h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 8a16 16 0 0 0 6 6l.27-.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 21 16.92z"/></svg>
                        (999) 941 2170
                    </a>
                    <div class="flex items-center gap-3 px-5 py-3 rounded-2xl bg-[#FAFAF8] dark:bg-[#1A0B11] border border-slate-200/80 dark:border-zinc-800/80 text-sm font-semibold text-slate-700 dark:text-zinc-300">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" class="size-4 text-[#6B1938] dark:text-[#f4a8c4]"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        Lun — Vie, 9:00 – 15:00 hrs
                    </div>
                </div>

                <!-- Two-column: form + map -->
                <div class="grid lg:grid-cols-2 gap-8">

                    <!-- Formulario -->
                    <div class="bg-[#FAFAF8] dark:bg-[#1A0B11] rounded-3xl border border-slate-200/80 dark:border-zinc-800/80 p-8 space-y-6">
                        <h3 class="text-xl font-black text-slate-900 dark:text-white">Envíanos un mensaje</h3>

                        <!-- Flash success -->
                        <div v-if="contactoFlash" class="flex items-start gap-3 p-4 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-700/40 rounded-2xl text-sm text-emerald-700 dark:text-emerald-400">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" class="size-4 shrink-0 mt-0.5"><path d="M20 6L9 17l-5-5"/></svg>
                            <span class="font-medium">{{ contactoFlash }}</span>
                        </div>

                        <form @submit.prevent="submitContacto" class="space-y-5" novalidate>
                            <div class="grid sm:grid-cols-2 gap-4">
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-black uppercase tracking-wider text-slate-500 dark:text-zinc-400">Nombre <span class="text-[#6B1938]">*</span></label>
                                    <input v-model="contactForm.nombre" type="text" required autocomplete="name"
                                        placeholder="Tu nombre completo"
                                        :class="['w-full px-4 py-3 rounded-xl border text-slate-900 dark:text-white bg-white dark:bg-zinc-800/50 text-sm focus:outline-none focus:ring-2 transition-all', contactForm.errors.nombre ? 'border-red-400 focus:ring-red-400' : 'border-slate-200 dark:border-zinc-700 focus:ring-[#6B1938] focus:border-[#6B1938]']"/>
                                    <p v-if="contactForm.errors.nombre" class="text-xs text-red-500">{{ contactForm.errors.nombre }}</p>
                                </div>
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-black uppercase tracking-wider text-slate-500 dark:text-zinc-400">Correo electrónico <span class="text-[#6B1938]">*</span></label>
                                    <input v-model="contactForm.email" type="email" required autocomplete="email"
                                        placeholder="tu@correo.com"
                                        :class="['w-full px-4 py-3 rounded-xl border text-slate-900 dark:text-white bg-white dark:bg-zinc-800/50 text-sm focus:outline-none focus:ring-2 transition-all', contactForm.errors.email ? 'border-red-400 focus:ring-red-400' : 'border-slate-200 dark:border-zinc-700 focus:ring-[#6B1938] focus:border-[#6B1938]']"/>
                                    <p v-if="contactForm.errors.email" class="text-xs text-red-500">{{ contactForm.errors.email }}</p>
                                </div>
                            </div>
                            <div class="space-y-1.5">
                                <label class="block text-xs font-black uppercase tracking-wider text-slate-500 dark:text-zinc-400">Asunto <span class="text-[#6B1938]">*</span></label>
                                <input v-model="contactForm.asunto" type="text" required
                                    placeholder="¿En qué podemos ayudarte?"
                                    :class="['w-full px-4 py-3 rounded-xl border text-slate-900 dark:text-white bg-white dark:bg-zinc-800/50 text-sm focus:outline-none focus:ring-2 transition-all', contactForm.errors.asunto ? 'border-red-400 focus:ring-red-400' : 'border-slate-200 dark:border-zinc-700 focus:ring-[#6B1938] focus:border-[#6B1938]']"/>
                                <p v-if="contactForm.errors.asunto" class="text-xs text-red-500">{{ contactForm.errors.asunto }}</p>
                            </div>
                            <div class="space-y-1.5">
                                <label class="block text-xs font-black uppercase tracking-wider text-slate-500 dark:text-zinc-400">Mensaje <span class="text-[#6B1938]">*</span></label>
                                <textarea v-model="contactForm.mensaje" required rows="5"
                                    placeholder="Describe tu pregunta o comentario con el mayor detalle posible..."
                                    :class="['w-full px-4 py-3 rounded-xl border text-slate-900 dark:text-white bg-white dark:bg-zinc-800/50 text-sm focus:outline-none focus:ring-2 transition-all resize-none', contactForm.errors.mensaje ? 'border-red-400 focus:ring-red-400' : 'border-slate-200 dark:border-zinc-700 focus:ring-[#6B1938] focus:border-[#6B1938]']"></textarea>
                                <div class="flex justify-between">
                                    <p v-if="contactForm.errors.mensaje" class="text-xs text-red-500">{{ contactForm.errors.mensaje }}</p>
                                    <p class="text-xs text-slate-400 dark:text-zinc-500 ml-auto">{{ contactForm.mensaje.length }}/2000</p>
                                </div>
                            </div>
                            <button type="submit" :disabled="contactForm.processing"
                                class="w-full py-4 rounded-2xl bg-[#6B1938] text-white font-bold text-base shadow-xl shadow-[#6B1938]/25 hover:bg-[#4E1029] disabled:opacity-60 disabled:cursor-not-allowed transition-all active:scale-[0.97] flex items-center justify-center gap-2">
                                <svg v-if="contactForm.processing" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" class="size-4 animate-spin"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
                                <svg v-else xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" class="size-4"><path d="M22 2L11 13M22 2 15 22l-4-9-9-4 20-7z"/></svg>
                                {{ contactForm.processing ? 'Enviando…' : 'Enviar mensaje' }}
                            </button>
                            <p class="flex items-center justify-center gap-2 text-xs text-slate-400 dark:text-zinc-500">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" class="size-3.5"><rect width="18" height="11" x="3" y="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                Tu información es confidencial
                            </p>
                        </form>
                    </div>

                    <!-- Mapa + info IYEM -->
                    <div class="space-y-4">
                        <div class="relative overflow-hidden rounded-3xl border border-slate-200/80 dark:border-zinc-800/80 bg-[#FAFAF8] dark:bg-[#1A0B11]">
                            <!-- Info card overlay -->
                            <div class="p-5 border-b border-slate-200/80 dark:border-zinc-800/80">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <h4 class="font-black text-slate-900 dark:text-white text-sm">Instituto Yucateco de Emprendedores (IYEM)</h4>
                                        <p class="text-xs text-slate-500 dark:text-zinc-400 mt-1 leading-relaxed">Av. Industrias No Contaminantes, Tablaje 13613<br>Col. Sodzil Norte, Mérida, Yucatán</p>
                                    </div>
                                    <div class="flex gap-2 shrink-0">
                                        <a href="https://maps.google.com/maps?q=Instituto+Yucateco+del+Emprendedor+Merida+Yucatan" target="_blank" rel="noopener noreferrer"
                                            class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-[#6B1938]/8 text-[#6B1938] dark:bg-[#6B1938]/15 dark:text-[#f4a8c4] hover:bg-[#6B1938]/15 transition-colors">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" class="size-3"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                                            Abrir
                                        </a>
                                        <a href="https://www.google.com/maps/dir/?api=1&destination=Instituto+Yucateco+del+Emprendedor+Merida+Yucatan" target="_blank" rel="noopener noreferrer"
                                            class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-slate-100 dark:bg-zinc-800 text-slate-600 dark:text-zinc-300 hover:bg-slate-200 dark:hover:bg-zinc-700 transition-colors">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" class="size-3"><polygon points="3 11 22 2 13 21 11 13 3 11"/></svg>
                                            Cómo llegar
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <!-- Map iframe -->
                            <div class="aspect-[4/3]">
                                <iframe
                                    src="https://maps.google.com/maps?q=Instituto+Yucateco+del+Emprendedor+Merida+Yucatan&output=embed&z=15"
                                    width="100%" height="100%"
                                    style="border:0;"
                                    loading="lazy"
                                    referrerpolicy="no-referrer-when-downgrade"
                                    title="Ubicación del IYEM en Mérida, Yucatán"
                                    class="w-full h-full"
                                    allowfullscreen>
                                </iframe>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ══════════════════════════════════════════════ FOOTER ══ -->
        <footer class="bg-[#4E1029] dark:bg-[#2A0818]">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 py-16">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-10 lg:gap-8">
                    <!-- Col 1: Brand -->
                    <div class="space-y-5">
                        <div class="flex items-center gap-3">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" class="size-9">
                                <rect width="32" height="32" rx="7" fill="#E8A020"/>
                                <rect x="5" y="21" width="5" height="8" rx="1.5" fill="white" fill-opacity="0.5"/>
                                <rect x="13" y="17" width="5" height="12" rx="1.5" fill="white" fill-opacity="0.82"/>
                                <rect x="21" y="12" width="5" height="17" rx="1.5" fill="white"/>
                            </svg>
                            <div>
                                <span class="block text-xl font-black text-white tracking-tight">CREA</span>
                                <span class="block text-[9px] font-medium text-[#F4BAC8] uppercase tracking-widest">IYEM Yucatán</span>
                            </div>
                        </div>
                        <p class="text-sm text-[#F4BAC8] leading-relaxed">
                            Programa de crédito para emprendedores y artesanos del estado de Yucatán, operado por el Instituto Yucateco de Emprendedores.
                        </p>
                        <div class="flex gap-3">
                            <a href="https://www.facebook.com/IyemYucatan" target="_blank" rel="noopener noreferrer"
                                class="w-9 h-9 rounded-lg bg-white/10 hover:bg-white/20 flex items-center justify-center text-[#F4BAC8] hover:text-white transition-all" aria-label="Facebook">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" class="size-4"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
                            </a>
                            <a href="https://www.instagram.com/iyemyucatan" target="_blank" rel="noopener noreferrer"
                                class="w-9 h-9 rounded-lg bg-white/10 hover:bg-white/20 flex items-center justify-center text-[#F4BAC8] hover:text-white transition-all" aria-label="Instagram">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" class="size-4"><rect width="20" height="20" x="2" y="2" rx="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>
                            </a>
                        </div>
                    </div>

                    <!-- Col 2: Programa -->
                    <div class="space-y-4">
                        <h4 class="text-xs font-black uppercase tracking-[0.2em] text-[#E8A020]">Programa</h4>
                        <ul class="space-y-2.5 text-sm text-[#F4BAC8]">
                            <li><a href="#modalidades" class="hover:text-white transition-colors">Modalidades</a></li>
                            <li><a href="#como-funciona" class="hover:text-white transition-colors">¿Cómo funciona?</a></li>
                            <li><a href="#requisitos" class="hover:text-white transition-colors">Requisitos</a></li>
                            <li><a href="#calculadora" class="hover:text-white transition-colors">Calculadora de crédito</a></li>
                            <li><a href="#faq" class="hover:text-white transition-colors">Preguntas frecuentes</a></li>
                        </ul>
                    </div>

                    <!-- Col 3: Portal -->
                    <div class="space-y-4">
                        <h4 class="text-xs font-black uppercase tracking-[0.2em] text-[#E8A020]">Portal</h4>
                        <ul class="space-y-2.5 text-sm text-[#F4BAC8]">
                            <li>
                                <Link :href="route('ciudadano.register')" class="hover:text-white transition-colors font-medium">
                                    Solicitar crédito
                                </Link>
                            </li>
                            <li>
                                <Link :href="route('login')" class="hover:text-white transition-colors">
                                    Portal ciudadano
                                </Link>
                            </li>
                            <li>
                                <Link :href="route('login')" class="hover:text-white transition-colors">
                                    Acceso operativo IYEM
                                </Link>
                            </li>
                        </ul>
                    </div>

                    <!-- Col 4: Contacto -->
                    <div class="space-y-4">
                        <h4 class="text-xs font-black uppercase tracking-[0.2em] text-[#E8A020]">Contacto IYEM</h4>
                        <div class="space-y-3 text-sm text-[#F4BAC8]">
                            <a href="mailto:crea@iyemyucatan.com" class="flex items-center gap-3 hover:text-white transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" class="size-4 text-[#E8A020] shrink-0"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                                crea@iyemyucatan.com
                            </a>
                            <a href="tel:+529999412170" class="flex items-center gap-3 hover:text-white transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" class="size-4 text-[#E8A020] shrink-0"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.6 1.07h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 8a16 16 0 0 0 6 6l.27-.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 21 16.92z"/></svg>
                                (999) 941 2170
                            </a>
                            <p class="flex items-start gap-3 leading-relaxed">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" class="size-4 text-[#E8A020] shrink-0 mt-0.5"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                                Av. Industrias No Contaminantes, Tablaje 13613, Col. Sodzil Norte, Mérida, Yucatán.
                            </p>
                            <a href="#contacto" class="inline-flex items-center gap-2 text-[#E8A020] hover:text-white font-semibold transition-colors text-xs uppercase tracking-wider">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" class="size-3"><path d="M22 2L11 13M22 2 15 22l-4-9-9-4 20-7z"/></svg>
                                Enviar mensaje
                            </a>
                        </div>
                    </div>
                </div>

                <div class="mt-14 pt-8 border-t border-white/10 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <p class="text-xs text-[#F0A8C0]/70 text-center sm:text-left">
                        © 2026 Instituto Yucateco de Emprendedores — Gobierno del Estado de Yucatán.
                    </p>
                    <p class="text-xs text-[#F0A8C0]/50 text-center sm:text-right">
                        Programa sujeto a disponibilidad presupuestal.
                    </p>
                </div>
            </div>
        </footer>

    </div>

    <!-- ══════════════════════════════════════════════ WHATSAPP BUTTON ══ -->
    <a href="https://wa.me/529999412170?text=Hola%2C%20vengo%20de%20la%20plataforma%20CREA%20y%20tengo%20una%20pregunta"
        target="_blank"
        rel="noopener noreferrer"
        class="fixed bottom-6 right-6 w-14 h-14 bg-green-500 hover:bg-green-600 text-white rounded-full flex items-center justify-center shadow-2xl shadow-green-900/30 z-50 transition-all hover:scale-110 active:scale-95"
        aria-label="Contactar por WhatsApp">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-7">
            <path d="M13.601 2.326A7.854 7.854 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.933 7.933 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.898 7.898 0 0 0 13.6 2.326zM7.994 14.521a6.573 6.573 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.557 6.557 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592zm3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.065-.315-.099-.445.099-.133.197-.513.646-.627.775-.114.133-.232.148-.43.05-.197-.1-.836-.308-1.592-.985-.59-.525-.985-1.175-1.103-1.372-.114-.198-.011-.304.088-.403.087-.088.197-.232.296-.346.1-.114.133-.198.198-.33.065-.134.034-.248-.015-.347-.05-.099-.445-1.076-.612-1.47-.16-.389-.323-.335-.445-.34-.114-.007-.247-.007-.38-.007a.729.729 0 0 0-.529.247c-.182.198-.691.677-.691 1.654 0 .977.71 1.916.81 2.049.098.133 1.394 2.132 3.383 2.992.47.205.84.326 1.129.418.475.152.904.129 1.246.08.38-.058 1.171-.48 1.338-.943.164-.464.164-.86.114-.943-.049-.084-.182-.133-.38-.232z"/>
        </svg>
    </a>
</template>

<style scoped>
* { transition: background-color 0.3s ease, border-color 0.3s ease, color 0.3s ease; }

@keyframes float-badge {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-4px); }
}
.hero-badge { animation: float-badge 4s ease-in-out infinite; }

@keyframes float-a {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}
@keyframes float-b {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-7px); }
}
@keyframes float-c {
    0%, 100% { transform: translateY(0) rotate(0deg); }
    50% { transform: translateY(-12px) rotate(5deg); }
}
.float-a { animation: float-a 4s ease-in-out infinite; }
.float-b { animation: float-b 5s ease-in-out infinite 0.8s; }
.float-c { animation: float-c 6s ease-in-out infinite 0.4s; }
.ill-shadow { filter: drop-shadow(0 12px 24px rgba(0,0,0,0.3)); }

.modal-card { transition: transform 0.3s ease, box-shadow 0.3s ease; }
.modal-card:hover { transform: translateY(-5px); box-shadow: 0 20px 40px rgba(107,25,56,0.12); }

.faq-body { overflow: hidden; transition: max-height 0.4s cubic-bezier(0.4, 0, 0.2, 1); }

.step-card { opacity: 0; transform: translateY(16px); transition: opacity 0.5s ease, transform 0.5s ease; }
.step-card.step-visible { opacity: 1; transform: translateY(0); }

.req-item { opacity: 0; transform: translateX(-12px); transition: opacity 0.4s ease, transform 0.4s ease; }
.req-item.req-visible { opacity: 1; transform: translateX(0); }

.req-check {
    stroke-dasharray: 25;
    stroke-dashoffset: 25;
    transition: stroke-dashoffset 0.5s ease-out;
}
.req-check.req-draw { stroke-dashoffset: 0; }

@keyframes shake {
    0%, 100% { transform: translateX(0); }
    20%, 60% { transform: translateX(-6px); }
    40%, 80% { transform: translateX(6px); }
}
.shake-anim { animation: shake 0.4s ease; }

@keyframes bar-rise {
    from { transform: scaleY(0); transform-origin: bottom; }
    to { transform: scaleY(1); transform-origin: bottom; }
}
.ill-bar1 { animation: bar-rise 0.8s ease-out 0.3s both; }
.ill-bar2 { animation: bar-rise 0.8s ease-out 0.5s both; }
.ill-bar3 { animation: bar-rise 0.8s ease-out 0.7s both; }

/* Slider guinda */
.slider-guinda {
    background: linear-gradient(to right, #6B1938 0%, #6B1938 var(--val, 50%), #e2e8f0 var(--val, 50%), #e2e8f0 100%);
}
.dark .slider-guinda {
    background: linear-gradient(to right, #6B1938 0%, #6B1938 var(--val, 50%), #3f3f46 var(--val, 50%), #3f3f46 100%);
}
.slider-guinda::-webkit-slider-thumb {
    appearance: none;
    width: 22px;
    height: 22px;
    border-radius: 50%;
    background: #6B1938;
    border: 3px solid white;
    box-shadow: 0 2px 8px rgba(107,25,56,0.4);
    cursor: pointer;
}
.slider-guinda::-moz-range-thumb {
    width: 22px;
    height: 22px;
    border-radius: 50%;
    background: #6B1938;
    border: 3px solid white;
    box-shadow: 0 2px 8px rgba(107,25,56,0.4);
    cursor: pointer;
}
</style>
