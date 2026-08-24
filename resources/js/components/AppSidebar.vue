<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { Link, usePage } from '@inertiajs/vue3';
import { LayoutGrid, ShieldCheck, Users, ClipboardList, BarChart2, FileText, Calculator, Inbox, TrendingDown, ScrollText, ClipboardCheck } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';
import { computed, watch, onMounted, ref } from 'vue';

// --- NOTIFICACIONES ---
import { Toaster, toast } from 'vue-sonner';

const page = usePage<any>();

// Lógica para mostrar los mensajes que envía Laravel
const notify = () => {
    const flash = page.props.flash;
    if (flash?.success) {
        toast.success('¡Operación Exitosa!', {
            description: flash.success,
        });
    }
    if (flash?.error) {
        toast.error('Atención', {
            description: flash.error,
        });
    }
};

// Observar si cambian los mensajes (cuando navegamos entre páginas)
watch(() => page.props.flash, () => notify(), { deep: true });

// Revisar si hay mensajes al cargar la página por primera vez
onMounted(() => notify());

// --- SISTEMA DE PERMISOS ---
const can = (permission: string) => {
    const user = page.props.auth?.user;
    if (!user) return false;
    
    // Si es Administrador tiene acceso total
    if (user.roles?.includes('Administrador')) return true;

    const permissions = user.permissions || [];
    return permissions.includes(permission);
};

// --- NAVEGACIÓN DINÁMICA ---
const mainNavItems = computed(() => {
    const items: { title: string; url: string; icon: any; group: string; badge?: number }[] = [
        { title: 'Dashboard', url: '/dashboard', icon: LayoutGrid, group: 'Inicio' },
    ];

    // Sección: Solicitudes ciudadanas
    if (can('ver.acreditados')) {
        items.push({
            title: 'Solicitudes CREA',
            url: route('solicitudes.index'),
            icon: Inbox,
            group: 'Solicitudes',
        });
    }

    // Sección: Créditos
    if (can('ver.acreditados')) {
        items.push({
            title: 'Cartera de Créditos',
            url: route('acreditados.index'),
            icon: ClipboardList,
            group: 'Créditos',
        });
    }
    if (can('ver.acreditados') && route().has('comprobaciones.index')) {
        items.push({
            title: 'Comprobación de Uso',
            url: route('comprobaciones.index'),
            icon: ClipboardCheck,
            group: 'Créditos',
            badge: page.props.comprobaciones_pendientes as number | undefined,
        });
    }

    // Sección: Operación
    if (can('ver.acreditados')) {
        items.push({
            title: 'Cobranza',
            url: route('cobranza.index'),
            icon: TrendingDown,
            group: 'Operación',
        });
    }

    // Sección: Reportes
    if (can('ver.reportes')) {
        items.push({
            title: 'Reporte Cartera',
            url: route('reportes.cartera'),
            icon: BarChart2,
            group: 'Reportes',
        });
        items.push({
            title: 'Reporte Pagos',
            url: route('reportes.pagos'),
            icon: FileText,
            group: 'Reportes',
        });
    }
    if (can('ver.simulador')) {
        items.push({
            title: 'Simulador',
            url: route('simulador.index'),
            icon: Calculator,
            group: 'Reportes',
        });
    }

    // Sección: Sistema (Solo Admin)
    if (can('ver.usuarios')) {
        items.push({
            title: 'Bitácora Auditoría',
            url: route('auditoria.index'),
            icon: ScrollText,
            group: 'Sistema',
        });
        items.push({ title: 'Usuarios', url: '/users', icon: Users, group: 'Sistema' });
    }
    if (can('ver.roles')) {
        items.push({ title: 'Roles y Permisos', url: '/roles', icon: ShieldCheck, group: 'Sistema' });
    }

    return items;
});

// Variable para evitar el error de "prop type failed" en NavFooter
const footerNavItems = ref([]);

</script>

<template>
    <div class="relative">
        
        <Toaster 
            position="top-center" 
            richColors 
            closeButton 
            expand
            :toastOptions="{
                style: { 
                    zIndex: 99999,
                    position: 'fixed' 
                }
            }"
        />

        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" as-child>
                            <Link :href="route('dashboard')">
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain :items="mainNavItems" />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter :items="footerNavItems" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
        
        <slot />
    </div>
</template>