<script setup lang="ts">
import { Breadcrumb, BreadcrumbItem, BreadcrumbLink, BreadcrumbList, BreadcrumbPage, BreadcrumbSeparator } from '@/components/ui/breadcrumb';
import { SidebarTrigger } from '@/components/ui/sidebar';
import type { BreadcrumbItemType } from '@/types';
import { ref, onMounted, computed } from 'vue';
import { Bell, X, CheckCheck, AlertCircle } from 'lucide-vue-next';

defineProps<{
    breadcrumbs?: BreadcrumbItemType[];
}>();

// ── Notificaciones ────────────────────────────────────────────────────────────
const panelAbierto   = ref(false);
const notificaciones = ref<any[]>([]);
const cargando       = ref(false);
const dismissedIds   = ref<string[]>(JSON.parse(localStorage.getItem('notif_dismissed') ?? '[]'));

const visibles = computed(() =>
    notificaciones.value.filter(n => !dismissedIds.value.includes(notifKey(n)))
);
const sinLeer = computed(() => visibles.value.length);

function notifKey(n: any) {
    return `${n.credito_id}_c${n.numero_cuota}_${n.tipo}`;
}

function dismiss(n: any) {
    const key = notifKey(n);
    dismissedIds.value.push(key);
    localStorage.setItem('notif_dismissed', JSON.stringify(dismissedIds.value));
}

function listo(n: any) { dismiss(n); }
function cerrar(n: any) { dismiss(n); }

async function cargarNotificaciones() {
    if (cargando.value) return;
    cargando.value = true;
    try {
        const r = await fetch('/api/notificaciones/cuotas', { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        const data = await r.json();
        notificaciones.value = data.notificaciones ?? [];
    } catch {
        notificaciones.value = [];
    } finally {
        cargando.value = false;
    }
}

function togglePanel() {
    panelAbierto.value = !panelAbierto.value;
    if (panelAbierto.value && notificaciones.value.length === 0) cargarNotificaciones();
}

onMounted(() => { cargarNotificaciones(); });

const money = (v: number) => new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN', minimumFractionDigits: 0 }).format(v || 0);
</script>

<template>
    <header
        class="flex h-16 shrink-0 items-center justify-between gap-2 border-b border-sidebar-border/70 px-6 transition-[width,height] ease-linear group-has-[[data-collapsible=icon]]/sidebar-wrapper:h-12 md:px-4"
    >
        <div class="flex items-center gap-2">
            <SidebarTrigger class="-ml-1" />
            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <Breadcrumb>
                    <BreadcrumbList>
                        <template v-for="(item, index) in breadcrumbs" :key="index">
                            <BreadcrumbItem>
                                <template v-if="index === breadcrumbs.length - 1">
                                    <BreadcrumbPage>{{ item.title }}</BreadcrumbPage>
                                </template>
                                <template v-else>
                                    <BreadcrumbLink :href="item.href">{{ item.title }}</BreadcrumbLink>
                                </template>
                            </BreadcrumbItem>
                            <BreadcrumbSeparator v-if="index !== breadcrumbs.length - 1" />
                        </template>
                    </BreadcrumbList>
                </Breadcrumb>
            </template>
        </div>

        <!-- Campana de notificaciones -->
        <div class="relative">
            <button @click="togglePanel"
                class="relative p-2 rounded-xl hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors">
                <Bell :size="18" class="text-zinc-500 dark:text-zinc-400" />
                <span v-if="sinLeer > 0"
                    class="absolute -top-0.5 -right-0.5 w-4 h-4 bg-red-500 text-white rounded-full text-[9px] font-bold flex items-center justify-center leading-none">
                    {{ sinLeer > 9 ? '9+' : sinLeer }}
                </span>
            </button>

            <!-- Panel flotante -->
            <div v-if="panelAbierto"
                class="absolute right-0 top-12 w-80 bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-700 shadow-2xl z-50 overflow-hidden">

                <!-- Header del panel -->
                <div class="flex items-center justify-between px-4 py-3 border-b border-zinc-100 dark:border-zinc-800">
                    <div class="flex items-center gap-2">
                        <Bell :size="14" class="text-zinc-500" />
                        <p class="text-xs font-bold text-zinc-700 dark:text-zinc-300 uppercase tracking-wide">Alertas Operativas</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button v-if="visibles.length > 0" @click="visibles.forEach(listo)"
                            class="text-[10px] text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 transition-colors font-semibold">
                            Todas listas
                        </button>
                        <button @click="panelAbierto = false"
                            class="p-1 rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors">
                            <X :size="13" class="text-zinc-400" />
                        </button>
                    </div>
                </div>

                <!-- Cargando -->
                <div v-if="cargando" class="px-4 py-8 text-center">
                    <p class="text-xs text-zinc-400 animate-pulse">Verificando vencimientos...</p>
                </div>

                <!-- Sin notificaciones -->
                <div v-else-if="visibles.length === 0" class="px-4 py-8 text-center">
                    <CheckCheck :size="24" class="text-emerald-400 mx-auto mb-2" />
                    <p class="text-xs text-zinc-400 font-medium">Todo al día — sin vencimientos pendientes</p>
                </div>

                <!-- Lista de notificaciones -->
                <div v-else class="max-h-80 overflow-y-auto divide-y divide-zinc-50 dark:divide-zinc-800">
                    <div v-for="n in visibles" :key="notifKey(n)"
                        class="px-4 py-3 hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors">
                        <div class="flex items-start gap-3">
                            <div class="mt-0.5 flex-shrink-0">
                                <AlertCircle :size="14"
                                    :class="n.tipo === 'hoy' ? 'text-red-500' : 'text-amber-500'" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-semibold text-zinc-800 dark:text-zinc-200 leading-tight truncate">
                                    {{ n.nombre_completo }}
                                </p>
                                <p class="text-[11px] text-zinc-500 dark:text-zinc-400 mt-0.5 leading-tight">
                                    <template v-if="n.tipo === 'hoy'">
                                        Su pago <strong class="text-red-500">vence HOY</strong>, favor de recordarlo.
                                    </template>
                                    <template v-else>
                                        Su pago <strong class="text-amber-500">vence mañana</strong>, favor de recordarlo.
                                    </template>
                                </p>
                                <p class="text-[10px] text-zinc-400 mt-0.5">
                                    Cuota {{ n.numero_cuota }} · {{ money(n.pago_restante) }} · {{ n.contrato }}
                                </p>
                            </div>
                        </div>
                        <div class="flex gap-2 mt-2 ml-5">
                            <button @click="listo(n)"
                                class="px-3 py-1 rounded-lg text-[10px] font-bold bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 hover:bg-emerald-100 dark:hover:bg-emerald-900/50 transition-colors">
                                ✓ Listo
                            </button>
                            <button @click="cerrar(n)"
                                class="px-3 py-1 rounded-lg text-[10px] font-medium text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors">
                                Cerrar
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Footer del panel -->
                <div class="px-4 py-2.5 border-t border-zinc-100 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-800/50">
                    <p class="text-[10px] text-zinc-400 text-center">
                        Vencimientos hoy y mañana · Se renueva al recargar
                    </p>
                </div>
            </div>

            <!-- Overlay para cerrar al click fuera -->
            <div v-if="panelAbierto" class="fixed inset-0 z-40" @click="panelAbierto = false"></div>
        </div>
    </header>
</template>
