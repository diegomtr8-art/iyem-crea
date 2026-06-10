<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { Link, usePage } from '@inertiajs/vue3';
import { LayoutGrid, ShieldCheck, Users, UserPlus, ClipboardList, BarChart2, FileText, Calculator, Receipt, Inbox, TrendingDown, Scale, ScrollText, Wallet, Building2 } from 'lucide-vue-next';
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
    const items = [
        { title: 'Dashboard', url: '/dashboard', icon: LayoutGrid },
    ];

    // Sección: Interesados (Prospectos)
    if (can('ver.interesados')) {
        items.push({ 
            title: 'Interesados CREA', 
            url: route('interesados.index'), 
            icon: Users 
        });
        items.push({ 
            title: 'Captar Interesado', 
            url: route('interesados.create'), 
            icon: UserPlus 
        });
    }

    // Sección: Acreditados (Operación)
    if (can('ver.acreditados')) {
        items.push({ 
            title: 'Acreditados CREA', 
            url: route('acreditados.index'), 
            icon: ClipboardList 
        });
    }

    // Sección: Reportes
    if (can('ver.reportes')) {
        items.push({
            title: 'Reporte Cartera',
            url: route('reportes.cartera'),
            icon: BarChart2,
        });
        items.push({
            title: 'Reporte Pagos',
            url: route('reportes.pagos'),
            icon: FileText,
        });
    }

    if (can('ver.simulador')) {
        items.push({
            title: 'Simulador',
            url: route('simulador.index'),
            icon: Calculator,
        });
    }

    // Sección: Solicitudes ciudadanas
    if (can('ver.acreditados')) {
        items.push({
            title: 'Solicitudes CREA',
            url: route('solicitudes.index'),
            icon: Inbox,
        });
    }

    // Sección: Cobranza
    if (can('ver.acreditados')) {
        items.push({
            title: 'Cobranza',
            url: route('cobranza.index'),
            icon: TrendingDown,
        });
        items.push({
            title: 'Cobranza Jurídica',
            url: route('juridico.index'),
            icon: Scale,
        });
    }

    // Sección: Presupuesto y Finanzas
    if (can('ver.reportes')) {
        items.push({
            title: 'Presupuesto',
            url: route('presupuesto.index'),
            icon: Wallet,
        });
    }

    // Sección: Auditoría
    if (can('ver.usuarios')) {
        items.push({
            title: 'Bitácora Auditoría',
            url: route('auditoria.index'),
            icon: ScrollText,
        });
    }

    // Sección: Configuración (Solo Admin)
    if (can('ver.usuarios')) {
        items.push({ title: 'Usuarios', url: '/users', icon: Users });
    }

    if (can('ver.roles')) {
        items.push({ title: 'Roles y Permisos', url: '/roles', icon: ShieldCheck });
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