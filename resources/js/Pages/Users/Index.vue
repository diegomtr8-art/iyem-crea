<script setup lang="ts">
import { ref } from 'vue';
import { useForm, Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import type { BreadcrumbItemType } from '@/types';
import { Pencil, Trash2, UsersIcon, UserPlus, KeyRound, AtSign, Loader2, X, Shield } from 'lucide-vue-next';

const props = defineProps<{
    users: any[];
    roles: any[];
}>();

const breadcrumbs: BreadcrumbItemType[] = [
    { title: 'Panel Principal', url: '/dashboard' },
    { title: 'Gestión de Usuarios', url: '/users' },
];

// Estado para controlar el modal de edición
const isEditing = ref(false);

const form = useForm({
    id: null as number | null,
    name: '',
    email: '',
    password: '',
    role: '',
});

const resetForm = () => {
    form.reset();
    form.clearErrors();
    isEditing.value = false;
};

const prepareEdit = (user: any) => {
    form.id = user.id;
    form.name = user.name;
    form.email = user.email;
    form.password = ''; // Opcional en edición
    form.role = user.roles[0]?.name || ''; 
    isEditing.value = true;
};

const submitForm = () => {
    if (isEditing.value && form.id) {
        form.put(route('users.update', form.id), {
            onSuccess: () => resetForm(),
        });
    } else {
        form.post(route('users.store'), {
            onSuccess: () => resetForm(),
        });
    }
};

const deleteUser = (userId: number, userName: string) => {
    if (confirm(`¿Estás seguro de que deseas eliminar a ${userName}?`)) {
        form.delete(route('users.destroy', userId), {
            preserveScroll: true
        });
    }
};
</script>

<template>
    <Head title="Usuarios" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4 md:p-8 space-y-8 bg-zinc-50 dark:bg-zinc-950 min-h-screen transition-colors duration-300">
            
            <div class="flex items-center gap-4">
                <div class="p-3 bg-white dark:bg-zinc-900 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-800">
                    <UsersIcon class="h-8 w-8 text-red-600 dark:text-red-400" />
                </div>
                <div>
                    <h1 class="text-2xl font-black text-zinc-900 dark:text-white uppercase tracking-tight">Personal del Sistema</h1>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400 font-medium">Administra los accesos y roles de CREA.</p>
                </div>
            </div>

            <div class="overflow-hidden bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-zinc-50 dark:bg-zinc-800/50 text-zinc-500 dark:text-zinc-400 text-[10px] uppercase tracking-widest border-b border-zinc-200 dark:border-zinc-800">
                            <tr>
                                <th class="p-4 font-bold">Usuario</th>
                                <th class="p-4 font-bold">Email</th>
                                <th class="p-4 font-bold">Rol Asignado</th>
                                <th class="p-4 font-bold text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            <tr v-for="user in users" :key="user.id" class="group hover:bg-zinc-50 dark:hover:bg-zinc-800/30 transition-all">
                                <td class="p-4">
                                    <div class="flex items-center gap-3">
                                        <div class="h-9 w-9 rounded-full bg-red-600 flex items-center justify-center text-white font-bold text-xs shadow-md shadow-red-500/20">
                                            {{ user.name.charAt(0) }}
                                        </div>
                                        <span class="text-sm font-semibold text-zinc-800 dark:text-zinc-100">{{ user.name }}</span>
                                    </div>
                                </td>
                                <td class="p-4 text-sm text-zinc-600 dark:text-zinc-400 font-medium italic">{{ user.email }}</td>
                                <td class="p-4">
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-black uppercase bg-white dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 border border-zinc-200 dark:border-zinc-700 shadow-sm">
                                        <Shield class="h-3 w-3 text-red-500" />
                                        {{ user.roles[0]?.name || 'Sin Rol' }}
                                    </span>
                                </td>
                                <td class="p-4 text-right">
                                    <div class="flex justify-end gap-1 opacity-100 md:opacity-0 group-hover:opacity-100 transition-opacity">
                                        <button @click="prepareEdit(user)" class="p-2 text-zinc-400 hover:text-red-600 dark:hover:text-red-400 hover:bg-white dark:hover:bg-zinc-700 rounded-lg border border-transparent hover:border-zinc-200 dark:hover:border-zinc-600 transition-all shadow-sm">
                                            <Pencil class="h-4 w-4" />
                                        </button>
                                        <button @click="deleteUser(user.id, user.name)" class="p-2 text-zinc-400 hover:text-red-500 hover:bg-white dark:hover:bg-zinc-700 rounded-lg border border-transparent hover:border-zinc-200 dark:hover:border-zinc-600 transition-all shadow-sm">
                                            <Trash2 class="h-4 w-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-if="!isEditing" class="max-w-3xl bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 p-8 rounded-2xl shadow-lg">
                <div class="flex items-center gap-3 mb-6">
                    <UserPlus class="h-6 w-6 text-red-600 dark:text-red-400" />
                    <h2 class="text-lg font-bold text-zinc-900 dark:text-white uppercase">Registrar Nuevo Ingreso</h2>
                </div>
                
                <form @submit.prevent="submitForm" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-1.5 md:col-span-2">
                        <label class="text-[10px] font-black text-zinc-400 uppercase tracking-tighter">Nombre Completo</label>
                        <input v-model="form.name" type="text" placeholder="Ej. Juan Perez" class="w-full bg-zinc-50 dark:bg-zinc-800 border-zinc-200 dark:border-zinc-700 rounded-xl p-3 text-sm text-zinc-900 dark:text-white focus:ring-2 focus:ring-red-500 outline-none transition-all" />
                        <p v-if="form.errors.name" class="text-red-500 text-xs font-bold">{{ form.errors.name }}</p>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-[10px] font-black text-zinc-400 uppercase tracking-tighter">Correo Institucional</label>
                        <input v-model="form.email" type="email" placeholder="email@crea.com" class="w-full bg-zinc-50 dark:bg-zinc-800 border-zinc-200 dark:border-zinc-700 rounded-xl p-3 text-sm text-zinc-900 dark:text-white focus:ring-2 focus:ring-red-500 outline-none transition-all" />
                        <p v-if="form.errors.email" class="text-red-500 text-xs font-bold">{{ form.errors.email }}</p>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-[10px] font-black text-zinc-400 uppercase tracking-tighter">Contraseña de Acceso</label>
                        <input v-model="form.password" type="password" placeholder="••••••••" class="w-full bg-zinc-50 dark:bg-zinc-800 border-zinc-200 dark:border-zinc-700 rounded-xl p-3 text-sm text-zinc-900 dark:text-white focus:ring-2 focus:ring-red-500 outline-none transition-all" />
                        <p v-if="form.errors.password" class="text-red-500 text-xs font-bold">{{ form.errors.password }}</p>
                    </div>

                    <div class="space-y-1.5 md:col-span-2">
                        <label class="text-[10px] font-black text-zinc-400 uppercase tracking-tighter">Perfil / Rol</label>
                        <select v-model="form.role" class="w-full bg-zinc-50 dark:bg-zinc-800 border-zinc-200 dark:border-zinc-700 rounded-xl p-3 text-sm text-zinc-900 dark:text-white focus:ring-2 focus:ring-red-500 outline-none transition-all appearance-none">
                            <option value="">Selecciona una jerarquía</option>
                            <option v-for="role in roles" :key="role.id" :value="role.name">{{ role.name }}</option>
                        </select>
                        <p v-if="form.errors.role" class="text-red-500 text-xs font-bold">{{ form.errors.role }}</p>
                    </div>

                    <button type="submit" :disabled="form.processing" class="md:col-span-2 bg-red-600 dark:bg-red-500 text-white font-black py-4 rounded-xl hover:bg-red-700 dark:hover:bg-red-400 transition-all shadow-lg shadow-red-500/20 active:scale-[0.98] disabled:opacity-50 flex items-center justify-center gap-2 uppercase text-xs tracking-widest">
                        <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin" />
                        <UserPlus v-else class="h-4 w-4" />
                        Finalizar Registro
                    </button>
                </form>
            </div>

            <div v-if="isEditing" class="fixed inset-0 bg-zinc-950/80 backdrop-blur-md flex items-center justify-center p-4 z-[100]">
                <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 p-8 rounded-3xl shadow-2xl w-full max-w-lg relative animate-in fade-in zoom-in duration-200">
                    <button @click="resetForm" class="absolute top-6 right-6 text-zinc-400 hover:text-zinc-900 dark:hover:text-white transition-colors">
                        <X class="h-6 w-6" />
                    </button>
                    
                    <h2 class="text-2xl font-black mb-2 text-zinc-900 dark:text-white uppercase italic">Modificar Usuario</h2>
                    <p class="text-zinc-500 dark:text-zinc-400 text-xs mb-8 font-medium">Estás editando la cuenta de <span class="text-red-500 underline">{{ form.name }}</span></p>
                    
                    <form @submit.prevent="submitForm" class="space-y-5">
                        <div class="space-y-1.5">
                            <label class="text-[10px] font-black text-zinc-400 uppercase tracking-tighter">Nombre</label>
                            <input v-model="form.name" type="text" class="w-full bg-zinc-50 dark:bg-zinc-800 border-zinc-200 dark:border-zinc-700 rounded-xl p-3 text-sm text-zinc-900 dark:text-white" />
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[10px] font-black text-zinc-400 uppercase tracking-tighter">Email</label>
                            <input v-model="form.email" type="email" class="w-full bg-zinc-50 dark:bg-zinc-800 border-zinc-200 dark:border-zinc-700 rounded-xl p-3 text-sm text-zinc-900 dark:text-white" />
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[10px] font-black text-zinc-400 uppercase tracking-tighter">Actualizar Contraseña (Opcional)</label>
                            <input v-model="form.password" type="password" placeholder="Dejar en blanco para mantener" class="w-full bg-zinc-50 dark:bg-zinc-800 border-zinc-200 dark:border-zinc-700 rounded-xl p-3 text-sm text-zinc-900 dark:text-white" />
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[10px] font-black text-zinc-400 uppercase tracking-tighter">Rol</label>
                            <select v-model="form.role" class="w-full bg-zinc-50 dark:bg-zinc-800 border-zinc-200 dark:border-zinc-700 rounded-xl p-3 text-sm text-zinc-900 dark:text-white">
                                <option v-for="role in roles" :key="role.id" :value="role.name">{{ role.name }}</option>
                            </select>
                        </div>
                        <div class="flex gap-4 pt-6">
                            <button type="button" @click="resetForm" class="flex-1 bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300 font-bold py-3 rounded-xl hover:bg-zinc-200 dark:hover:bg-zinc-700 transition-all uppercase text-[10px] tracking-widest">Descartar</button>
                            <button type="submit" :disabled="form.processing" class="flex-1 bg-red-600 dark:bg-red-500 text-white font-black py-3 rounded-xl hover:bg-red-700 dark:hover:bg-red-400 transition-all shadow-lg shadow-red-500/20 uppercase text-[10px] tracking-widest">Actualizar Datos</button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </AppLayout>
</template>