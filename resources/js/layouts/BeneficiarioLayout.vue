<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { Toaster, toast } from 'vue-sonner';
import axios from 'axios';
import {
    Home, FolderOpen, FileText, BadgeCheck,
    Bell, LogOut, ChevronDown, Menu, X, Sun, Moon, User,
    CheckCircle2, Info, AlertTriangle, XCircle, Check
} from 'lucide-vue-next';

const page = usePage<any>();
const mobileOpen    = ref(false);
const userMenuOpen  = ref(false);
const bellOpen      = ref(false);
const theme         = ref(localStorage.getItem('theme') || 'light');
const notifs        = ref<any[]>([]);
const noLeidos      = ref(0);
const loadingNotifs = ref(false);

const applyTheme = () => document.documentElement.classList.toggle('dark', theme.value === 'dark');
const toggleTheme = () => {
    theme.value = theme.value === 'light' ? 'dark' : 'light';
    localStorage.setItem('theme', theme.value);
    applyTheme();
};
onMounted(applyTheme);

const user    = computed(() => page.props.auth?.user);
const portal  = computed(() => page.props.portal);
const tieneCredito = computed(() => portal.value?.tiene_credito ?? false);

// Sync noLeidos desde props globales (se actualiza con cada navegación Inertia)
watch(() => portal.value?.no_leidos, (v) => { if (v !== undefined) noLeidos.value = v; }, { immediate: true });

const navItems = computed(() => [
    { label: 'Inicio',            href: route('portal.dashboard'),       icon: Home,       always: true },
    { label: 'Mi Expediente',     href: route('portal.expediente'),      icon: FolderOpen, always: true },
    { label: 'Solicitar Crédito', href: route('portal.solicitud.index'), icon: FileText,   always: true },
    { label: 'Mi Crédito',        href: route('portal.credito'),         icon: BadgeCheck, always: false, show: tieneCredito.value },
]);

const isActive = (href: string) => page.url.startsWith(new URL(href).pathname);
const logout   = () => router.post(route('logout'));

// ── Notificaciones ────────────────────────────────────────────────────────────
async function fetchNotifs() {
    if (loadingNotifs.value) return;
    loadingNotifs.value = true;
    try {
        const { data } = await axios.get(route('portal.notificaciones.lista'));
        notifs.value    = data.items;
        noLeidos.value  = data.no_leidos;
    } catch { /* silent */ }
    finally  { loadingNotifs.value = false; }
}

function toggleBell() {
    bellOpen.value = !bellOpen.value;
    if (bellOpen.value) fetchNotifs();
    userMenuOpen.value = false;
}

async function marcarLeido(id: number) {
    try {
        const { data } = await axios.post(route('portal.anuncios.leer', id), {}, { headers: { Accept: 'application/json' } });
        const n = notifs.value.find(x => x.id === id);
        if (n) n.leido = true;
        noLeidos.value = data.no_leidos;
    } catch { /* silent */ }
}

async function marcarTodas() {
    try {
        const { data } = await axios.post(route('portal.anuncios.leer-todos'), {}, { headers: { Accept: 'application/json' } });
        notifs.value.forEach(n => { n.leido = true; });
        noLeidos.value = 0;
    } catch { /* silent */ }
}

function clickNotif(n: any) {
    if (!n.leido) marcarLeido(n.id);
    if (n.url_accion) { bellOpen.value = false; router.visit(n.url_accion); }
}

// Cerrar dropdowns al hacer clic fuera
function handleOutsideClick(e: MouseEvent) {
    const target = e.target as HTMLElement;
    if (!target.closest('.bell-dropdown') && !target.closest('.bell-trigger')) bellOpen.value = false;
    if (!target.closest('.user-dropdown') && !target.closest('.user-trigger')) userMenuOpen.value = false;
}
onMounted(() => document.addEventListener('click', handleOutsideClick));
onUnmounted(() => document.removeEventListener('click', handleOutsideClick));

// Icono por tipo de notificación
const tipoIcono: Record<string, any> = { exito: CheckCircle2, info: Info, alerta: AlertTriangle, error: XCircle };
const tipoCls: Record<string, string> = {
    exito:  'text-emerald-600 dark:text-emerald-400',
    info:   'text-blue-600 dark:text-blue-400',
    alerta: 'text-amber-600 dark:text-amber-400',
    error:  'text-red-600 dark:text-red-400',
};

// ── Flash toasts ──────────────────────────────────────────────────────────────
const notify = () => {
    const flash = page.props.flash as any;
    if (flash?.success) toast.success('¡Listo!',        { description: flash.success });
    if (flash?.error)   toast.error('Atención',          { description: flash.error });
    if (flash?.warning) toast.warning('Aviso',           { description: flash.warning });
    if (flash?.info)    toast.info('Información',        { description: flash.info });
};
watch(() => page.props.flash, notify, { deep: true });
onMounted(notify);
</script>

<template>
    <div class="flex flex-col min-h-screen bg-[#FAFAF8] dark:bg-[#0E0508] text-slate-900 dark:text-zinc-100 transition-colors duration-300 font-sans">
        <Toaster position="top-right" richColors closeButton expand />

        <!-- TOP NAV -->
        <nav class="bg-white/95 dark:bg-[#1A0B11]/95 backdrop-blur-md border-b border-slate-200/60 dark:border-zinc-800/60 sticky top-0 z-40 shadow-sm shadow-black/5">
            <div class="max-w-7xl mx-auto px-4 sm:px-6">
                <div class="flex items-center justify-between h-16">

                    <!-- Logo -->
                    <Link :href="route('portal.dashboard')" class="flex items-center gap-2.5 group shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" class="size-9 transition-transform group-hover:scale-105">
                            <rect width="32" height="32" rx="7" fill="#6B1938"/>
                            <rect x="5" y="21" width="5" height="8" rx="1.5" fill="white" fill-opacity="0.5"/>
                            <rect x="13" y="17" width="5" height="12" rx="1.5" fill="white" fill-opacity="0.82"/>
                            <rect x="21" y="12" width="5" height="17" rx="1.5" fill="#E8A020"/>
                        </svg>
                        <div class="leading-none">
                            <span class="block text-lg font-black tracking-tight text-[#6B1938] dark:text-[#f4a8c4]">CREA</span>
                            <span class="block text-[9px] font-medium text-slate-400 dark:text-zinc-500 uppercase tracking-wider hidden sm:block">Portal Ciudadano</span>
                        </div>
                    </Link>

                    <!-- Nav escritorio -->
                    <div class="hidden md:flex items-center gap-1">
                        <template v-for="item in navItems" :key="item.label">
                            <Link v-if="item.always || item.show"
                                :href="item.href"
                                :class="[
                                    'flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold transition-all duration-200',
                                    isActive(item.href)
                                        ? 'bg-[#6B1938]/10 dark:bg-[#6B1938]/20 text-[#6B1938] dark:text-[#f4a8c4]'
                                        : 'text-slate-600 dark:text-zinc-400 hover:bg-slate-100 dark:hover:bg-zinc-800/60 hover:text-slate-900 dark:hover:text-white'
                                ]">
                                <component :is="item.icon" size="16" />
                                {{ item.label }}
                            </Link>
                        </template>
                    </div>

                    <!-- Acciones derecha -->
                    <div class="flex items-center gap-2">
                        <!-- Tema -->
                        <button @click="toggleTheme" aria-label="Cambiar tema" class="p-2 rounded-xl text-slate-500 dark:text-zinc-400 hover:bg-slate-100 dark:hover:bg-zinc-800/60 transition-all">
                            <Sun v-if="theme === 'dark'" size="18" />
                            <Moon v-else size="18" />
                        </button>

                        <!-- Campanita -->
                        <div class="relative">
                            <button @click.stop="toggleBell" aria-label="Notificaciones" class="bell-trigger relative p-2 rounded-xl text-slate-500 dark:text-zinc-400 hover:bg-slate-100 dark:hover:bg-zinc-800/60 transition-all">
                                <Bell size="18" />
                                <span v-if="noLeidos > 0"
                                    class="absolute top-1 right-1 w-4 h-4 bg-[#6B1938] text-white text-[9px] font-black rounded-full flex items-center justify-center leading-none">
                                    {{ noLeidos > 9 ? '9+' : noLeidos }}
                                </span>
                            </button>

                            <!-- Dropdown notificaciones -->
                            <transition enter-from-class="opacity-0 scale-95 translate-y-1" leave-to-class="opacity-0 scale-95 translate-y-1"
                                enter-active-class="transition-all duration-150 origin-top-right" leave-active-class="transition-all duration-100 origin-top-right">
                                <div v-if="bellOpen" class="bell-dropdown absolute right-0 top-full mt-2 w-[calc(100vw-1rem)] max-w-sm sm:w-96 bg-white dark:bg-[#1A0B11] rounded-2xl border border-slate-100 dark:border-zinc-800/60 shadow-2xl shadow-black/15 z-50 overflow-hidden">
                                    <!-- Header del dropdown -->
                                    <div class="flex items-center justify-between px-4 py-3 border-b border-slate-100 dark:border-zinc-800/60">
                                        <span class="text-sm font-black text-slate-900 dark:text-white">Notificaciones</span>
                                        <button v-if="noLeidos > 0" @click="marcarTodas" class="text-xs text-[#6B1938] dark:text-[#f4a8c4] font-bold hover:underline flex items-center gap-1">
                                            <Check size="12" /> Marcar todas leídas
                                        </button>
                                    </div>

                                    <!-- Lista -->
                                    <div class="max-h-80 overflow-y-auto divide-y divide-slate-100 dark:divide-zinc-800/60">
                                        <div v-if="loadingNotifs" class="py-8 text-center text-sm text-slate-400">Cargando...</div>
                                        <div v-else-if="notifs.length === 0" class="py-8 text-center text-sm text-slate-400">Sin notificaciones</div>
                                        <button v-for="n in notifs" :key="n.id" @click="clickNotif(n)"
                                            :class="['w-full text-left px-4 py-3 transition-colors flex items-start gap-3',
                                                n.leido ? 'hover:bg-slate-50 dark:hover:bg-zinc-800/40' : 'bg-[#6B1938]/5 dark:bg-[#6B1938]/10 hover:bg-[#6B1938]/10 dark:hover:bg-[#6B1938]/20']">
                                            <component :is="tipoIcono[n.tipo] ?? Info" size="16" :class="['mt-0.5 shrink-0', tipoCls[n.tipo] ?? 'text-slate-400']" />
                                            <div class="min-w-0 flex-1">
                                                <div class="flex items-center gap-2">
                                                    <p :class="['text-xs font-bold truncate', n.leido ? 'text-slate-700 dark:text-zinc-300' : 'text-slate-900 dark:text-white']">{{ n.titulo }}</p>
                                                    <span v-if="!n.leido" class="w-2 h-2 rounded-full bg-[#6B1938] shrink-0"></span>
                                                </div>
                                                <p class="text-[11px] text-slate-500 dark:text-zinc-400 mt-0.5 line-clamp-2">{{ n.mensaje }}</p>
                                                <p class="text-[10px] text-slate-400 dark:text-zinc-600 mt-1">{{ n.fecha }}</p>
                                            </div>
                                        </button>
                                    </div>
                                </div>
                            </transition>
                        </div>

                        <!-- Usuario escritorio -->
                        <div class="relative hidden md:block">
                            <button @click.stop="userMenuOpen = !userMenuOpen; bellOpen = false" class="user-trigger flex items-center gap-2 pl-3 pr-2 py-2 rounded-xl text-sm font-semibold text-slate-700 dark:text-zinc-300 hover:bg-slate-100 dark:hover:bg-zinc-800/60 transition-all">
                                <div class="w-7 h-7 rounded-lg bg-[#6B1938]/10 dark:bg-[#6B1938]/20 flex items-center justify-center text-[#6B1938] dark:text-[#f4a8c4]">
                                    <User size="14" />
                                </div>
                                <span class="max-w-[120px] truncate">{{ user?.name }}</span>
                                <ChevronDown size="14" :class="userMenuOpen ? 'rotate-180' : ''" class="transition-transform text-slate-400" />
                            </button>
                            <transition enter-from-class="opacity-0 translate-y-1" leave-to-class="opacity-0 translate-y-1"
                                enter-active-class="transition-all duration-150" leave-active-class="transition-all duration-100">
                                <div v-if="userMenuOpen" class="user-dropdown absolute right-0 top-full mt-2 w-52 bg-white dark:bg-[#1A0B11] rounded-2xl border border-slate-100 dark:border-zinc-800/60 shadow-xl shadow-slate-900/10 p-1 z-50">
                                    <div class="px-3 py-2 border-b border-slate-100 dark:border-zinc-800/60 mb-1">
                                        <p class="text-xs font-bold text-slate-900 dark:text-white truncate">{{ user?.name }}</p>
                                        <p class="text-xs text-slate-400 dark:text-zinc-500 truncate">{{ user?.email }}</p>
                                    </div>
                                    <button @click="logout"
                                        class="w-full flex items-center gap-2 px-3 py-2.5 text-sm font-medium text-[#6B1938] dark:text-[#f4a8c4] hover:bg-[#6B1938]/5 dark:hover:bg-[#6B1938]/10 rounded-xl transition-colors">
                                        <LogOut size="15" /> Cerrar Sesión
                                    </button>
                                </div>
                            </transition>
                        </div>

                        <!-- Hamburger móvil -->
                        <button @click="mobileOpen = !mobileOpen; bellOpen = false" :aria-label="mobileOpen ? 'Cerrar menú' : 'Abrir menú'" class="md:hidden p-2 rounded-xl text-slate-500 hover:bg-slate-100 dark:hover:bg-zinc-800/60 transition-all">
                            <Menu v-if="!mobileOpen" size="20" />
                            <X v-else size="20" />
                        </button>
                    </div>
                </div>
            </div>

            <!-- Menú móvil -->
            <transition enter-from-class="opacity-0 -translate-y-2" leave-to-class="opacity-0 -translate-y-2"
                enter-active-class="transition-all duration-200" leave-active-class="transition-all duration-150">
                <div v-if="mobileOpen" class="md:hidden border-t border-slate-100 dark:border-zinc-800/60 bg-white dark:bg-[#1A0B11] px-4 pb-4 pt-2 space-y-1">
                    <template v-for="item in navItems" :key="item.label">
                        <Link v-if="item.always || item.show"
                            :href="item.href"
                            @click="mobileOpen = false"
                            :class="[
                                'flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold transition-all',
                                isActive(item.href)
                                    ? 'bg-[#6B1938]/10 dark:bg-[#6B1938]/20 text-[#6B1938] dark:text-[#f4a8c4]'
                                    : 'text-slate-600 dark:text-zinc-400 hover:bg-slate-50 dark:hover:bg-zinc-800/60'
                            ]">
                            <component :is="item.icon" size="18" />
                            {{ item.label }}
                        </Link>
                    </template>
                    <div class="pt-3 border-t border-slate-100 dark:border-zinc-800/60 mt-2">
                        <div class="flex items-center gap-3 px-4 py-2 mb-2">
                            <div class="w-8 h-8 rounded-xl bg-[#6B1938]/10 dark:bg-[#6B1938]/20 flex items-center justify-center text-[#6B1938] dark:text-[#f4a8c4]">
                                <User size="16" />
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-bold text-slate-900 dark:text-white truncate">{{ user?.name }}</p>
                                <p class="text-xs text-slate-400 dark:text-zinc-500 truncate">{{ user?.email }}</p>
                            </div>
                        </div>
                        <button @click="logout"
                            class="w-full flex items-center gap-2 px-4 py-3 text-sm font-semibold text-[#6B1938] dark:text-[#f4a8c4] hover:bg-[#6B1938]/5 dark:hover:bg-[#6B1938]/10 rounded-xl transition-colors">
                            <LogOut size="16" /> Cerrar Sesión
                        </button>
                    </div>
                </div>
            </transition>
        </nav>

        <!-- CONTENIDO PRINCIPAL -->
        <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 py-8">
            <slot />
        </main>

        <!-- FOOTER — siempre al fondo gracias a flex-col min-h-screen -->
        <footer class="bg-white dark:bg-[#1A0B11] border-t border-slate-200/60 dark:border-zinc-800/60">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 py-10">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div class="space-y-3">
                        <div class="flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" class="size-8">
                                <rect width="32" height="32" rx="7" fill="#6B1938"/>
                                <rect x="5" y="21" width="5" height="8" rx="1.5" fill="white" fill-opacity="0.5"/>
                                <rect x="13" y="17" width="5" height="12" rx="1.5" fill="white" fill-opacity="0.82"/>
                                <rect x="21" y="12" width="5" height="17" rx="1.5" fill="#E8A020"/>
                            </svg>
                            <span class="font-black text-[#6B1938] dark:text-[#f4a8c4]">CREA</span>
                        </div>
                        <p class="text-sm text-slate-500 dark:text-zinc-400 leading-relaxed">
                            Impulsando el ecosistema emprendedor de Yucatán. Instituto Yucateco de Emprendedores.
                        </p>
                    </div>
                    <div class="space-y-3">
                        <h4 class="text-xs font-black uppercase tracking-widest text-[#6B1938] dark:text-[#f4a8c4]">Sitios Aliados</h4>
                        <ul class="space-y-2">
                            <li>
                                <a href="https://www.nodico.com.mx" target="_blank" rel="noopener" class="group flex items-center gap-2 text-sm text-slate-500 dark:text-zinc-400 hover:text-[#6B1938] dark:hover:text-[#f4a8c4] transition-colors">
                                    <div class="w-6 h-6 rounded bg-slate-100 dark:bg-zinc-800 flex items-center justify-center text-[9px] font-bold group-hover:bg-[#6B1938]/10 group-hover:text-[#6B1938] transition-colors">N</div>
                                    Nodico
                                </a>
                            </li>
                            <li>
                                <a href="https://www.herenciaviva.com" target="_blank" rel="noopener" class="group flex items-center gap-2 text-sm text-slate-500 dark:text-zinc-400 hover:text-[#6B1938] dark:hover:text-[#f4a8c4] transition-colors">
                                    <div class="w-6 h-6 rounded bg-slate-100 dark:bg-zinc-800 flex items-center justify-center text-[9px] font-bold group-hover:bg-[#6B1938]/10 group-hover:text-[#6B1938] transition-colors">HV</div>
                                    Herencia Viva
                                </a>
                            </li>
                        </ul>
                    </div>
                    <div class="space-y-3">
                        <h4 class="text-xs font-black uppercase tracking-widest text-[#6B1938] dark:text-[#f4a8c4]">Contacto IYEM</h4>
                        <div class="space-y-1 text-sm text-slate-500 dark:text-zinc-400">
                            <p>Av. Industrias No Contaminantes Tablaje 13613</p>
                            <p>Col. Sodzil Norte, Mérida, Yucatán</p>
                            <p class="font-medium text-slate-700 dark:text-zinc-300">999 941 2170</p>
                        </div>
                    </div>
                </div>
                <div class="mt-8 pt-6 border-t border-slate-100 dark:border-zinc-800/60 text-center">
                    <p class="text-xs text-slate-400 dark:text-zinc-600 uppercase tracking-widest">
                        © 2026 Instituto Yucateco de Emprendedores — Software desarrollado por Diego Martínez
                    </p>
                </div>
            </div>
        </footer>
    </div>
</template>
