<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';
</script>

<template>
    <Head title="Aviso de Privacidad — Programa CREA" />

    <div class="min-h-screen bg-[#FAFAF8] dark:bg-[#0E0508] text-slate-700 dark:text-zinc-300">
        <header class="border-b border-slate-200 dark:border-zinc-800 bg-white dark:bg-zinc-900/60">
            <div class="max-w-3xl mx-auto px-6 py-5 flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-[#6B1938] dark:text-[#f4a8c4]">IYEM — Gobierno del Estado de Yucatán</p>
                    <h1 class="text-lg font-black text-slate-900 dark:text-white">Aviso de Privacidad — Programa CREA</h1>
                </div>
                <Link href="/" class="shrink-0 flex items-center gap-1.5 text-xs font-bold text-[#6B1938] dark:text-[#f4a8c4] hover:underline">
                    <ArrowLeft size="14" /> Volver
                </Link>
            </div>
        </header>

        <main class="max-w-3xl mx-auto px-6 py-10 space-y-8 text-sm leading-relaxed">
            <p class="text-xs text-slate-400 dark:text-zinc-500">Última actualización: agosto de 2026</p>

            <section class="space-y-2">
                <h2 class="text-base font-black text-slate-900 dark:text-white">1. Responsable del tratamiento de datos personales</h2>
                <p>
                    El Instituto Yucateco de Emprendedores (IYEM), con domicilio en la ciudad de Mérida, Yucatán, es
                    responsable del tratamiento de los datos personales que nos proporcionas como solicitante o
                    beneficiario del Programa CREA (Crédito para el Emprendimiento, Artesanía y Sustentabilidad),
                    de conformidad con la Ley de Protección de Datos Personales en Posesión de Sujetos Obligados del
                    Estado de Yucatán y demás normatividad aplicable.
                </p>
            </section>

            <section class="space-y-2">
                <h2 class="text-base font-black text-slate-900 dark:text-white">2. Datos personales que recabamos</h2>
                <p>Para evaluar y, en su caso, formalizar tu solicitud de crédito recabamos, entre otros:</p>
                <ul class="list-disc pl-5 space-y-1">
                    <li>Datos de identificación: nombre completo, CURP, RFC, fecha de nacimiento, INE.</li>
                    <li>Datos de contacto: domicilio, teléfono, correo electrónico.</li>
                    <li>Datos patrimoniales y financieros: ingresos, egresos, bienes, deudas, historial crediticio.</li>
                    <li>Datos del negocio o proyecto: giro, ubicación, fotografías, comprobantes fiscales.</li>
                    <li>Datos del aval o garante, cuando aplique.</li>
                </ul>
                <p>No recabamos datos personales sensibles salvo aquellos necesarios para acreditar pertenencia a
                    grupos prioritarios del programa (por ejemplo, autoadscripción indígena o discapacidad), los
                    cuales son proporcionados de forma voluntaria por el solicitante.</p>
            </section>

            <section class="space-y-2">
                <h2 class="text-base font-black text-slate-900 dark:text-white">3. Finalidades del tratamiento</h2>
                <p>Tus datos personales serán utilizados para las siguientes finalidades necesarias:</p>
                <ul class="list-disc pl-5 space-y-1">
                    <li>Verificar tu identidad y elegibilidad para el Programa CREA.</li>
                    <li>Evaluar, dictaminar y, en su caso, formalizar tu solicitud de crédito.</li>
                    <li>Administrar el crédito otorgado: cobranza, seguimiento y comprobación de uso de recursos.</li>
                    <li>Generar reportes internos y estadísticas del programa ante instancias de fiscalización.</li>
                </ul>
                <p>De forma adicional, y solo si lo autorizas, podremos usar tu correo electrónico y teléfono para
                    enviarte información sobre otros programas y convocatorias del IYEM.</p>
            </section>

            <section class="space-y-2">
                <h2 class="text-base font-black text-slate-900 dark:text-white">4. Transferencia de datos</h2>
                <p>
                    Tus datos podrán ser compartidos con otras dependencias del Gobierno del Estado de Yucatán y
                    autoridades fiscalizadoras, únicamente cuando exista fundamento legal para ello. El IYEM no
                    vende, renta ni comercializa tus datos personales con terceros.
                </p>
            </section>

            <section class="space-y-2">
                <h2 class="text-base font-black text-slate-900 dark:text-white">5. Derechos ARCO</h2>
                <p>
                    Tienes derecho a acceder, rectificar y cancelar tus datos personales, así como a oponerte al
                    tratamiento de los mismos o revocar el consentimiento que para tal fin nos hayas otorgado, a
                    través de la Unidad de Transparencia del IYEM.
                </p>
            </section>

            <section class="space-y-2">
                <h2 class="text-base font-black text-slate-900 dark:text-white">6. Cambios al aviso de privacidad</h2>
                <p>
                    Este aviso de privacidad puede sufrir modificaciones derivadas de nuevos requerimientos legales
                    o mejoras al Programa CREA. Cualquier cambio será publicado en este mismo espacio.
                </p>
            </section>
        </main>
    </div>
</template>
