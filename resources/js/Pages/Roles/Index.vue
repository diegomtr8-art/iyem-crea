<script setup lang="ts">
import { ref } from 'vue';
import { useForm, Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import type { BreadcrumbItemType } from '@/types';
import { ShieldCheck, ShieldPlus, Pencil, Trash2, CheckCircle2, Loader2, X, Lock } from 'lucide-vue-next';

const props = defineProps<{
    roles: any[];
    allPermissions: any[];
}>();

const breadcrumbs: BreadcrumbItemType[] = [
    { title: 'Panel Principal', url: '/dashboard' },
    { title: 'Roles y Permisos', url: '/roles' },
];

const isEditing = ref(false);

const form = useForm({
    id: null as number | null,
    name: '',
    permissions: [] as string[]
});

const resetForm = () => {
    form.reset();
    form.clearErrors();
    isEditing.value = false;
};

const prepareEdit = (role: any) => {
    form.id = role.id;
    form.name = role.name;
    // Extraemos solo los nombres de los permisos que ya tiene el rol
    form.permissions = role.permissions.map((p: any) => p.name);
    isEditing.value = true;
};

const submit = () => {
    if (isEditing.value && form.id) {
        form.put(route('roles.update', form.id), {
            onSuccess: () => resetForm(),
        });
    } else {
        form.post(route('roles.store'), {
            onSuccess: () => resetForm(),
        });
    }
};

const deleteRole = (role: any) => {
    if (confirm(`¿Eliminar el rol "${role.name}"? Esto podría afectar los accesos de varios usuarios.`)) {
        form.delete(route('roles.destroy', role.id));
    }
};
</script>

<template>
    <Head title="Roles y Permisos" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4 md:p-8 space-y-8 bg-zinc-50 dark:bg-zinc-950 min-h-screen transition-colors duration-300 text-zinc-900 dark:text-zinc-100">
            
            <div class="flex items-center gap-4">
                <div class="p-3 bg-white dark:bg-zinc-900 rounded-2xl shadow-sm border border-zinc-200 dark:border-zinc-800">
                    <ShieldCheck class="h-8 w-8 text-red-600 dark:text-red-400" />
                </div>
                <div>
                    <h1 class="text-2xl font-black uppercase tracking-tight">Roles del Sistema</h1>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400 font-medium">Define qué puede y qué no puede hacer cada equipo en CREA.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <div class="lg:col-span-1">
                    <div class="sticky top-8 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-3xl p-6 shadow-xl shadow-zinc-200/50 dark:shadow-none">
                        <div class="flex items-center gap-2 mb-6 text-red-600 dark:text-red-400">
                            <ShieldPlus class="h-5 w-5" />
                            <h2 class="font-bold uppercase text-sm tracking-widest">Nuevo Perfil</h2>
                        </div>

                        <form @submit.prevent="submit" class="space-y-6">
                            <div class="space-y-2">
                                <label class="text-[10px] font-black text-zinc-400 uppercase tracking-widest">Nombre del Rol</label>
                                <input v-model="form.name" type="text" placeholder="Ej: Auditor de Cobranza" 
                                    class="w-full bg-zinc-50 dark:bg-zinc-800 border-zinc-200 dark:border-zinc-700 rounded-xl p-3 text-sm focus:ring-2 focus:ring-red-500 outline-none transition-all" />
                                <p v-if="form.errors.name" class="text-red-500 text-xs font-bold">{{ form.errors.name }}</p>
                            </div>

                            <div class="space-y-3">
                                <label class="text-[10px] font-black text-zinc-400 uppercase tracking-widest">Permisos de Acceso</label>
                                <div class="space-y-2 max-h-[300px] overflow-y-auto pr-2 custom-scrollbar">
                                    <div v-for="perm in allPermissions" :key="perm.id" 
                                        class="flex items-center gap-3 p-2 rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors cursor-pointer group">
                                        <input type="checkbox" :id="'perm-' + perm.id" :value="perm.name" v-model="form.permissions"
                                            class="w-4 h-4 rounded border-zinc-300 dark:border-zinc-600 text-red-600 focus:ring-red-500 bg-transparent" />
                                        <label :for="'perm-' + perm.id" class="text-xs font-medium cursor-pointer group-hover:text-red-500 transition-colors capitalize">
                                            {{ perm.name.replace('.', ' ') }}
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" :disabled="form.processing" 
                                class="w-full bg-red-600 text-white font-black py-4 rounded-xl hover:bg-red-700 transition-all shadow-lg shadow-red-500/20 flex items-center justify-center gap-2 uppercase text-[10px] tracking-[0.2em]">
                                <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin" />
                                Guardar Perfil
                            </button>
                        </form>
                    </div>
                </div>

                <div class="lg:col-span-2 space-y-4">
                    <div v-for="role in roles" :key="role.id" 
                        class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-3xl p-6 shadow-sm hover:shadow-md transition-all group">
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <h3 class="text-lg font-black uppercase italic tracking-tight text-zinc-800 dark:text-zinc-100">{{ role.name }}</h3>
                                    <Lock v-if="role.name === 'admin'" class="h-3 w-3 text-zinc-400" />
                                </div>
                                <div class="flex flex-wrap gap-1.5">
                                    <span v-for="p in role.permissions" :key="p.id" 
                                        class="px-2 py-0.5 rounded-md bg-red-50 dark:bg-red-500/10 text-red-600 dark:text-red-400 text-[9px] font-bold border border-red-100 dark:border-red-500/20">
                                        {{ p.name }}
                                    </span>
                                    <span v-if="role.permissions.length === 0" class="text-[10px] text-zinc-400 italic">Sin permisos asignados</span>
                                </div>
                            </div>

                            <div class="flex items-center gap-2 self-end md:self-center">
                                <button @click="prepareEdit(role)" 
                                    class="p-2.5 bg-zinc-50 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400 hover:text-red-500 dark:hover:text-red-400 rounded-xl border border-zinc-200 dark:border-zinc-700 transition-all shadow-sm">
                                    <Pencil class="h-4 w-4" />
                                </button>
                                <button v-if="role.name !== 'admin'" @click="deleteRole(role)" 
                                    class="p-2.5 bg-zinc-50 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400 hover:text-red-500 rounded-xl border border-zinc-200 dark:border-zinc-700 transition-all shadow-sm">
                                    <Trash2 class="h-4 w-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="isEditing" class="fixed inset-0 bg-zinc-950/80 backdrop-blur-md flex items-center justify-center p-4 z-[100]">
                <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 p-8 rounded-[2.5rem] shadow-2xl w-full max-w-2xl relative animate-in fade-in zoom-in duration-200">
                    <button @click="resetForm" class="absolute top-8 right-8 text-zinc-400 hover:text-zinc-900 dark:hover:text-white">
                        <X class="h-6 w-6" />
                    </button>
                    
                    <h2 class="text-2xl font-black mb-1 uppercase italic">Editar Jerarquía</h2>
                    <p class="text-zinc-500 text-xs mb-8">Actualizando el rol: <span class="text-red-500 font-bold uppercase">{{ form.name }}</span></p>
                    
                    <form @submit.prevent="submit" class="space-y-6">
                        <div class="space-y-2">
                            <label class="text-[10px] font-black text-zinc-400 uppercase tracking-widest">Nombre del Rol</label>
                            <input v-model="form.name" type="text" class="w-full bg-zinc-50 dark:bg-zinc-800 border-zinc-200 dark:border-zinc-700 rounded-xl p-3 text-sm" />
                        </div>
                        
                        <div class="space-y-2">
                            <label class="text-[10px] font-black text-zinc-400 uppercase tracking-widest">Permisos del Perfil</label>
                            <div class="grid grid-cols-2 md:grid-cols-3 gap-3 max-h-[250px] overflow-y-auto pr-2 custom-scrollbar">
                                <div v-for="perm in allPermissions" :key="perm.id" class="flex items-center gap-2 p-2 bg-zinc-50 dark:bg-zinc-800 rounded-lg border border-zinc-100 dark:border-zinc-700">
                                    <input type="checkbox" :id="'edit-perm-' + perm.id" :value="perm.name" v-model="form.permissions" class="rounded text-red-600" />
                                    <label :for="'edit-perm-' + perm.id" class="text-[10px] font-bold uppercase text-zinc-600 dark:text-zinc-400 truncate">{{ perm.name }}</label>
                                </div>
                            </div>
                        </div>

                        <div class="flex gap-4 pt-4">
                            <button type="button" @click="resetForm" class="flex-1 bg-zinc-100 dark:bg-zinc-800 text-zinc-500 font-bold py-4 rounded-2xl uppercase text-[10px] tracking-widest">Cancelar</button>
                            <button type="submit" :disabled="form.processing" class="flex-1 bg-red-600 text-white font-black py-4 rounded-2xl shadow-lg shadow-red-500/20 uppercase text-[10px] tracking-widest">Guardar Cambios</button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </AppLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    /* Cambiado a Rojo para hacer match con tus botones */
    background: #dc2626; 
    border-radius: 10px;
}
</style>