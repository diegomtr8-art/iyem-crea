<script setup lang="ts">
import TextLink from '@/components/TextLink.vue';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Mail, Info } from 'lucide-vue-next'; // Importamos iconos para el mensaje
</script>

<template>
    <AuthBase title="Registro Restringido" description="Acceso exclusivo para personal autorizado">
        <Head title="Registro" />

        <div class="space-y-8 animate-in fade-in duration-500">
            <div class="bg-blue-50 dark:bg-zinc-800/50 border border-blue-100 dark:border-zinc-700 rounded-2xl p-6 text-center space-y-4">
                <div class="mx-auto w-12 h-12 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-blue-600">
                    <Info size="24" />
                </div>
                
                <div class="space-y-2">
                    <p class="text-slate-700 dark:text-zinc-300 font-medium leading-relaxed">
                        Para obtener una cuenta en el Sistema Operativo CREA, por favor póngase en contacto con el administrador:
                    </p>
                    
                    <a 
                        href="mailto:iyem.informatica@iyemyucatan.com" 
                        class="inline-flex items-center gap-2 text-red-600 hover:text-red-700 font-bold text-lg transition-colors break-all"
                    >
                        <Mail size="20" />
                        iyem.informatica@iyemyucatan.com
                    </a>
                </div>
            </div>

            <div class="text-center pt-4 border-t border-slate-100 dark:border-zinc-800">
                <p class="text-sm text-slate-500 dark:text-zinc-400 mb-4">
                    ¿Ya tienes una cuenta asignada?
                </p>
                <TextLink :href="route('login')" class="font-bold text-red-600 underline underline-offset-4" tabindex="1">
                    Volver al Inicio de Sesión
                </TextLink>
            </div>

            <div class="text-center pt-6 border-t border-slate-100 dark:border-zinc-800">
                <p class="text-sm text-slate-400">
                    Solo personal administrativo CREA. <br/>
                    © 2026 IYEM - Todos los derechos reservados. <br/>
                    Software Desarrollado por Diego Martínez
                </p>
            </div>
        </div>
    </AuthBase>
</template>