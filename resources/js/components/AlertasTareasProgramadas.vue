<script setup>
import { computed, ref } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { ServerCrash, X } from 'lucide-vue-next';

const page = usePage();
// null cuando el usuario no es Administrador: entonces el ícono no se muestra
const fallos = computed(() => page.props.fallos_tareas);

const abierto = ref(false);
const mostradas = ref([]);

const abrir = () => {
    abierto.value = !abierto.value;
    if (!abierto.value) return;

    // Se guarda lo que el administrador está viendo y se marca como leído
    mostradas.value = fallos.value.recientes;
    if (mostradas.value.length === 0) return;

    router.post(route('notificaciones-tareas.leidas'), { ids: mostradas.value.map((n) => n.id) }, {
        preserveScroll: true,
        preserveState: true,
    });
};

const fecha = (iso) => new Date(iso).toLocaleString('es-MX', { dateStyle: 'medium', timeStyle: 'short', timeZone: 'America/Merida' });
</script>

<template>
    <div v-if="fallos" class="relative">
        <button @click="abrir" title="Fallos de tareas programadas"
            class="relative p-2 rounded-xl hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors">
            <ServerCrash :size="18" class="text-zinc-500 dark:text-zinc-400" />
            <span v-if="fallos.total > 0"
                class="absolute -top-0.5 -right-0.5 w-4 h-4 bg-red-500 text-white rounded-full text-[9px] font-bold flex items-center justify-center leading-none">
                {{ fallos.total > 9 ? '9+' : fallos.total }}
            </span>
        </button>

        <div v-if="abierto"
            class="absolute right-0 top-12 w-[calc(100vw-1rem)] max-w-xs sm:w-80 bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-700 shadow-2xl z-50 overflow-hidden">
            <div class="flex items-center justify-between px-4 py-3 border-b border-zinc-100 dark:border-zinc-800">
                <p class="text-xs font-bold text-zinc-700 dark:text-zinc-300 uppercase tracking-wide">Tareas programadas con fallo</p>
                <button @click="abierto = false" class="p-1 rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-800">
                    <X :size="13" class="text-zinc-400" />
                </button>
            </div>

            <p v-if="mostradas.length === 0" class="px-4 py-8 text-center text-xs text-zinc-400">Sin fallos nuevos.</p>

            <div v-else class="max-h-80 overflow-y-auto divide-y divide-zinc-100 dark:divide-zinc-800">
                <div v-for="n in mostradas" :key="n.id" class="px-4 py-3">
                    <p class="text-xs font-semibold font-mono text-red-600">{{ n.tarea }}</p>
                    <p class="text-[11px] text-zinc-500 dark:text-zinc-400 mt-0.5 break-words">{{ n.mensaje }}</p>
                    <p class="text-[10px] text-zinc-400 mt-0.5">{{ fecha(n.fecha) }}</p>
                </div>
            </div>

            <Link :href="route('bitacora-tareas.index')" @click="abierto = false"
                class="block px-4 py-2.5 border-t border-zinc-100 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-800/50 text-center text-[11px] font-semibold text-zinc-600 dark:text-zinc-300 hover:underline">
                Ver todas en Tareas programadas
            </Link>
        </div>

        <!-- Clic fuera para cerrar -->
        <div v-if="abierto" class="fixed inset-0 z-40" @click="abierto = false"></div>
    </div>
</template>