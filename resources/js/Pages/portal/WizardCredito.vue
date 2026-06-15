<script setup lang="ts">
import BeneficiarioLayout from '@/layouts/BeneficiarioLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import axios from 'axios';
import {
    ShieldCheck, ListChecks, User, Building2, Briefcase,
    Target, BarChart2, TrendingUp, Users, FileUp, ClipboardCheck,
    CheckCircle2, ChevronRight, ChevronLeft, Plus, Trash2,
    AlertTriangle, Download, MapPin, Phone, Mail, Loader2, Lock
} from 'lucide-vue-next';

const props = defineProps<{
    solicitud: (Record<string, any> & {
        documentos?: Record<string, any>;
        aval?: Record<string, any> | null;
        datos_wizard?: Record<string, any> | null;
    }) | null;
    modalidades: Array<{
        id: number; nombre: string; tasa_interes: string;
        monto_minimo: number; monto_maximo: number;
        plazo_min_meses: number; plazo_max_meses: number;
        documentos_requeridos: string[];
    }>;
}>();

// ─── Estado general ───────────────────────────────────────────────────────────
const paso        = ref(0);
const enviando    = ref(false);
const exitoso     = ref(props.solicitud?.estatus === 'Enviada' && !!props.solicitud?.formatos_zip_ruta);
const errorMsg    = ref('');
const solicitudId = ref<number | null>(props.solicitud?.id ?? null);

const TOTAL_PASOS = 11;

// ─── Paso 0: CURP ─────────────────────────────────────────────────────────────
const curpInput     = ref(props.solicitud?.curp ?? '');
const curpBloqueada = ref(false);
const curpMensaje   = ref('');
const curpOk        = ref(!!props.solicitud?.curp);
const verificandoCurp = ref(false);

const curpValida = computed(() => /^[A-Z]{4}\d{6}[HM][A-Z]{5}[A-Z\d]\d$/.test(curpInput.value.toUpperCase()));

async function verificarCurp() {
    if (!curpValida.value) return;
    verificandoCurp.value = true;
    curpMensaje.value = '';
    try {
        const { data } = await axios.post(route('portal.solicitar.verificar-curp'), { curp: curpInput.value.toUpperCase() });
        curpBloqueada.value = data.bloqueado;
        curpMensaje.value   = data.mensaje ?? '';
        if (!data.bloqueado) { curpOk.value = true; datos.value.curp = curpInput.value.toUpperCase(); avanzar(); }
    } catch { curpMensaje.value = 'Error al verificar. Intenta de nuevo.'; }
    finally  { verificandoCurp.value = false; }
}

// ─── Datos del wizard (acumulados) ────────────────────────────────────────────
const wiz = props.solicitud?.datos_wizard ?? {};

const datos = ref({
    // Paso 1
    modalidad_id:  props.solicitud?.modalidad_id ?? '',
    tipo_persona:  props.solicitud?.tipo_persona  ?? 'fisica',
    tipo_garantia: props.solicitud?.tipo_garantia ?? 'aval',
    // Paso 2 — datos personales básicos
    nombre_completo:  props.solicitud?.nombre_completo ?? '',
    curp:             props.solicitud?.curp ?? '',
    rfc:              props.solicitud?.rfc  ?? '',
    fecha_nacimiento: props.solicitud?.fecha_nacimiento ?? '',
    sexo:             props.solicitud?.sexo ?? '',
    mayahablante:     props.solicitud?.mayahablante ?? false,
    discapacidad:     props.solicitud?.discapacidad ?? false,
    municipio:        props.solicitud?.municipio ?? '',
    direccion:        props.solicitud?.direccion ?? '',
    telefono:         props.solicitud?.telefono ?? '',
    correo:           props.solicitud?.correo ?? '',
    // extendidos personales
    colonia:             wiz.datos_personales_ext?.colonia ?? '',
    cp:                  wiz.datos_personales_ext?.cp ?? '',
    lugar_nacimiento:    wiz.datos_personales_ext?.lugar_nacimiento ?? '',
    telefono_fijo:       wiz.datos_personales_ext?.telefono_fijo ?? '',
    domicilio_propio:    wiz.datos_personales_ext?.domicilio_propio ?? true,
    renta_mensual:       wiz.datos_personales_ext?.renta_mensual ?? '',
    empleado_gobierno:   wiz.datos_personales_ext?.empleado_gobierno ?? false,
    dependencia_gobierno:wiz.datos_personales_ext?.dependencia_gobierno ?? '',
    puesto_gobierno:     wiz.datos_personales_ext?.puesto_gobierno ?? '',
    estado_civil:        wiz.datos_personales_ext?.estado_civil ?? '',
    regimen_matrimonial: wiz.datos_personales_ext?.regimen_matrimonial ?? '',
    nombre_conyuge:      wiz.datos_personales_ext?.nombre_conyuge ?? '',
    curp_conyuge:        wiz.datos_personales_ext?.curp_conyuge ?? '',
    discapacidad_tipo:   wiz.datos_personales_ext?.discapacidad_tipo ?? '',
    referencia_nombre:   wiz.datos_personales_ext?.referencia_nombre ?? '',
    referencia_telefono: wiz.datos_personales_ext?.referencia_telefono ?? '',
    referencia_cp:       wiz.datos_personales_ext?.referencia_cp ?? '',
    // Paso 3 — persona moral
    razon_social:   wiz.datos_persona_moral?.razon_social ?? '',
    rfc_moral:      wiz.datos_persona_moral?.rfc ?? '',
    domicilio_moral:wiz.datos_persona_moral?.domicilio ?? '',
    colonia_moral:  wiz.datos_persona_moral?.colonia ?? '',
    municipio_moral:wiz.datos_persona_moral?.municipio ?? '',
    cp_moral:       wiz.datos_persona_moral?.cp ?? '',
    telefono_moral: wiz.datos_persona_moral?.telefono ?? '',
    correo_moral:   wiz.datos_persona_moral?.correo ?? '',
    rep_legal:      wiz.datos_persona_moral?.rep_legal ?? '',
    curp_rep:       wiz.datos_persona_moral?.curp_rep ?? '',
    fecha_constitucion: wiz.datos_persona_moral?.fecha_constitucion ?? '',
    // Paso 4 — negocio
    nombre_comercial:     wiz.datos_negocio_ext?.nombre_comercial ?? '',
    municipio_empresa:    wiz.datos_negocio_ext?.municipio_empresa ?? '',
    fecha_inicio_negocio: wiz.datos_negocio_ext?.fecha_inicio_negocio ?? '',
    regimen_fiscal:       wiz.datos_negocio_ext?.regimen_fiscal ?? '',
    propiedad_intelectual:wiz.datos_negocio_ext?.propiedad_intelectual ?? false,
    detalle_pi:           wiz.datos_negocio_ext?.detalle_pi ?? '',
    ventas_mensuales:     wiz.datos_negocio_ext?.ventas_mensuales ?? '',
    proceso_operacion:    wiz.datos_negocio_ext?.proceso_operacion ?? '',
    mobiliario:           wiz.datos_negocio_ext?.mobiliario ?? '',
    recursos_humanos:     wiz.datos_negocio_ext?.recursos_humanos ?? '',
    giro_comercial:       props.solicitud?.giro_comercial ?? '',
    descripcion_negocio:  props.solicitud?.descripcion_negocio ?? '',
    alta_sat:             props.solicitud?.alta_sat ?? false,
    // Paso 5 — destino
    monto_solicitado:     props.solicitud?.monto_solicitado ?? '',
    plazo_meses:          props.solicitud?.plazo_meses ?? '',
    destino_credito:      props.solicitud?.destino_credito ?? '',
    importe_total_proyecto: wiz.importe_total_proyecto ?? '',
    // Paso 7 — ingresos/egresos
    periodo_del: wiz.ingresos_egresos?.periodo_del ?? '',
    periodo_al:  wiz.ingresos_egresos?.periodo_al ?? '',
    ventas:               wiz.ingresos_egresos?.ventas ?? '',
    costo_producto:       wiz.ingresos_egresos?.costo_producto ?? '',
    gastos_electricidad:  wiz.ingresos_egresos?.gastos_electricidad ?? '',
    gastos_agua:          wiz.ingresos_egresos?.gastos_agua ?? '',
    gastos_telefono:      wiz.ingresos_egresos?.gastos_telefono ?? '',
    gastos_gas:           wiz.ingresos_egresos?.gastos_gas ?? '',
    gastos_mano_obra:     wiz.ingresos_egresos?.gastos_mano_obra ?? '',
    gastos_nomina:        wiz.ingresos_egresos?.gastos_nomina ?? '',
    gastos_renta_local:   wiz.ingresos_egresos?.gastos_renta_local ?? '',
    impuestos:            wiz.ingresos_egresos?.impuestos ?? '',
    // garantía prendaria/hipotecaria
    garantia_descripcion: wiz.garantia_datos?.descripcion ?? '',
    garantia_valor:       wiz.garantia_datos?.valor ?? '',
    garantia_fecha_factura: wiz.garantia_datos?.fecha_factura ?? '',
    garantia_valor_factura: wiz.garantia_datos?.valor_factura ?? '',
});

// Tablas repetibles
const destinoTabla  = ref<any[]>(wiz.destino_credito_tabla?.length ? wiz.destino_credito_tabla : [{ concepto: '', importe: '' }]);
const apoyosGob     = ref<any[]>(wiz.apoyos_gobierno?.length ? wiz.apoyos_gobierno : []);
const proveedores   = ref<any[]>(wiz.proveedores?.length ? wiz.proveedores : [{ nombre: '', antiguedad: '', insumo: '', compras_mensuales: '', politica: 'Contado' }]);
const clientes      = ref<any[]>(wiz.clientes?.length ? wiz.clientes : [{ nombre: '', sector: '', ventas_mensuales: '', politica: 'Contado' }]);
const deudasNegocio = ref<any[]>(wiz.deudas_negocio?.length ? wiz.deudas_negocio : []);
const otrosGastos   = ref<any[]>(wiz.ingresos_egresos?.otros_gastos?.length ? wiz.ingresos_egresos.otros_gastos : []);

// Aval
const aval = ref({
    nombre_completo:         props.solicitud?.aval?.nombre_completo ?? '',
    correo:                  props.solicitud?.aval?.correo ?? '',
    sexo:                    props.solicitud?.aval?.sexo ?? '',
    rfc:                     props.solicitud?.aval?.rfc ?? '',
    curp:                    props.solicitud?.aval?.curp ?? '',
    telefono_celular:        props.solicitud?.aval?.telefono_celular ?? '',
    telefono_fijo:           props.solicitud?.aval?.telefono_fijo ?? '',
    edad:                    props.solicitud?.aval?.edad ?? '',
    fecha_nacimiento:        props.solicitud?.aval?.fecha_nacimiento ?? '',
    municipio_nacimiento:    props.solicitud?.aval?.municipio_nacimiento ?? '',
    municipio_residencia:    props.solicitud?.aval?.municipio_residencia ?? '',
    domicilio:               props.solicitud?.aval?.domicilio ?? '',
    colonia:                 props.solicitud?.aval?.colonia ?? '',
    cp:                      props.solicitud?.aval?.cp ?? '',
    domicilio_propio:        props.solicitud?.aval?.domicilio_propio ?? true,
    renta_mensual:           props.solicitud?.aval?.renta_mensual ?? '',
    lugar_laboral:           props.solicitud?.aval?.lugar_laboral ?? '',
    antiguedad_laboral:      props.solicitud?.aval?.antiguedad_laboral ?? '',
    ocupacion:               props.solicitud?.aval?.ocupacion ?? '',
    fecha_inicio_actividades:props.solicitud?.aval?.fecha_inicio_actividades ?? '',
    dependientes_economicos: props.solicitud?.aval?.dependientes_economicos ?? '',
    estado_civil:            props.solicitud?.aval?.estado_civil ?? '',
    regimen_matrimonial:     props.solicitud?.aval?.regimen_matrimonial ?? '',
    nombre_conyuge:          props.solicitud?.aval?.nombre_conyuge ?? '',
});
const avBienes    = ref<any[]>(props.solicitud?.aval?.bienes_inmuebles ?? [{ descripcion: '', ubicacion: '', valor: '' }]);
const avHipotecas = ref<any[]>(props.solicitud?.aval?.hipotecas_creditos ?? []);
const avOtrasDeudas = ref<any[]>(props.solicitud?.aval?.otras_deudas ?? []);
const avReferencias = ref<any[]>(props.solicitud?.aval?.referencias_personales ?? [{ nombre: '', telefono: '', cp: '' }]);

// Documentos
const docSubidos = ref<Record<string, any>>(props.solicitud?.documentos ?? {});
const docSubiendo = ref<Record<string, boolean>>({});
const docArchivos = ref<Record<string, File | null>>({});

// ─── Modalidad seleccionada ────────────────────────────────────────────────────
const modalidadActual = computed(() => props.modalidades.find(m => m.id == datos.value.modalidad_id));
const isArtesanal = computed(() => modalidadActual.value?.nombre?.toLowerCase().includes('artesanal'));
const plazosDisponibles = computed(() => isArtesanal.value ? [6, 12, 18] : [12, 18, 24]);

// ─── Cálculos ingresos/egresos ─────────────────────────────────────────────────
const utilBruta    = computed(() => (+(datos.value.ventas || 0)) - (+(datos.value.costo_producto || 0)));
const totalGastosBase = computed(() =>
    [datos.value.gastos_electricidad, datos.value.gastos_agua, datos.value.gastos_telefono,
     datos.value.gastos_gas, datos.value.gastos_mano_obra, datos.value.gastos_nomina, datos.value.gastos_renta_local]
    .reduce((s, v) => s + (+(v || 0)), 0)
);
const totalOtrosGastos = computed(() => otrosGastos.value.reduce((s: number, g: any) => s + (+(g.importe || 0)), 0));
const totalGastos   = computed(() => totalGastosBase.value + totalOtrosGastos.value);
const utilAnteImp   = computed(() => utilBruta.value - totalGastos.value);
const utilNeta      = computed(() => utilAnteImp.value - (+(datos.value.impuestos || 0)));

// ─── Tipos de documentos requeridos (dinámico) ────────────────────────────────
const tiposDocRequeridos = computed((): Record<string, string> => {
    const base: Record<string, string> = {
        ine_frente:            'INE / Credencial (Frente)',
        ine_reverso:           'INE / Credencial (Reverso)',
        curp:                  'Documento CURP oficial',
        comprobante_domicilio: 'Comprobante de Domicilio',
        foto_negocio:          'Fotografía del Negocio o Proyecto',
    };
    const mod = modalidadActual.value;
    if (!mod) return base;
    if (isArtesanal.value)                                          base.constancia_artesano = 'Constancia de Artesano';
    if (mod.nombre?.toLowerCase().includes('sustentable'))         { base.opinion_cumplimiento = 'Opinión de Cumplimiento SAT'; base.plan_negocio = 'Plan de Negocio'; }
    if (mod.nombre?.toLowerCase().includes('emprendedores'))       base.constancia_situacion = 'Constancia de Situación Fiscal';
    if (datos.value.tipo_persona === 'moral')                      { base.acta_constitutiva = 'Acta Constitutiva'; base.poder_rep_legal = 'Poder del Representante Legal'; base.id_rep_legal = 'Identificación del Representante'; }
    if (datos.value.tipo_garantia === 'aval')                      { base.id_aval = 'Identificación del Aval'; base.comprobante_domicilio_aval = 'Comprobante de Domicilio del Aval'; }
    if (datos.value.tipo_garantia === 'prendaria')                 base.factura_bien_mueble = 'Factura del Bien Mueble';
    if (datos.value.tipo_garantia === 'hipotecaria')               base.doc_propiedad_inmueble = 'Documento de Propiedad del Inmueble';
    base.cotizaciones = 'Cotizaciones del Destino del Crédito (mínimo 2)';
    return base;
});

const totalDocsReq    = computed(() => Object.keys(tiposDocRequeridos.value).length);
const totalDocsSubidos = computed(() => Object.keys(tiposDocRequeridos.value).filter(t => docSubidos.value[t]).length);
const todosDocsCompletos = computed(() => totalDocsSubidos.value >= totalDocsReq.value);

// ─── Navegación ───────────────────────────────────────────────────────────────
const pasoVisible = computed(() => {
    // Si persona física y artesanal, saltar paso 3
    if (paso.value === 3 && datos.value.tipo_persona === 'fisica') return false;
    return true;
});

function avanzar() {
    errorMsg.value = '';
    let siguiente = paso.value + 1;
    // Saltar paso moral si persona física
    if (siguiente === 3 && datos.value.tipo_persona === 'fisica') siguiente = 4;
    if (siguiente <= TOTAL_PASOS) paso.value = siguiente;
}

function retroceder() {
    errorMsg.value = '';
    let anterior = paso.value - 1;
    if (anterior === 3 && datos.value.tipo_persona === 'fisica') anterior = 2;
    if (anterior >= 0) paso.value = anterior;
}

// ─── Guardar paso en el backend ────────────────────────────────────────────────
async function guardarPaso(numeroPaso: number, datosExtra: Record<string, any> = {}) {
    enviando.value = true;
    errorMsg.value = '';
    try {
        const { data } = await axios.post(route('portal.solicitar.paso'), { paso: numeroPaso, datos: { ...datos.value, ...datosExtra } });
        if (data.solicitud_id) solicitudId.value = data.solicitud_id;
        avanzar();
    } catch (e: any) {
        const errs = e.response?.data?.errors;
        errorMsg.value = errs ? Object.values(errs).flat().join(' ') : (e.response?.data?.message ?? 'Error al guardar. Intenta de nuevo.');
    } finally { enviando.value = false; }
}

// ─── Handlers por paso ────────────────────────────────────────────────────────
async function guardarPaso1() {
    await guardarPaso(1);
}
async function guardarPaso2() {
    await guardarPaso(2, { ...wiz.datos_personales_ext });
}
async function guardarPaso3() {
    await guardarPaso(3);
}
async function guardarPaso4() {
    await guardarPaso(4);
}
async function guardarPaso5() {
    await guardarPaso(5, {
        destino_credito_tabla:  destinoTabla.value,
        apoyos_gobierno:        apoyosGob.value,
        importe_total_proyecto: datos.value.importe_total_proyecto,
    });
}
async function guardarPaso6() {
    await guardarPaso(6, {
        proveedores:    proveedores.value,
        clientes:       clientes.value,
        deudas_negocio: deudasNegocio.value,
        productos:      datos.value.descripcion_negocio,
    });
}
async function guardarPaso7() {
    await guardarPaso(7, { otros_gastos: otrosGastos.value });
}
async function guardarPaso8() {
    if (datos.value.tipo_garantia === 'aval') {
        await guardarPaso(8, {
            ...aval.value,
            bienes_inmuebles:     avBienes.value,
            hipotecas_creditos:   avHipotecas.value,
            otras_deudas:         avOtrasDeudas.value,
            referencias_personales: avReferencias.value,
        });
    } else {
        await guardarPaso(8, {
            descripcion:    datos.value.garantia_descripcion,
            valor:          datos.value.garantia_valor,
            fecha_factura:  datos.value.garantia_fecha_factura,
            valor_factura:  datos.value.garantia_valor_factura,
        });
    }
}

// ─── Subida de documentos ─────────────────────────────────────────────────────
function seleccionarDoc(tipo: string, e: Event) {
    const f = (e.target as HTMLInputElement).files?.[0];
    if (f) docArchivos.value[tipo] = f;
}

async function subirDoc(tipo: string) {
    const archivo = docArchivos.value[tipo];
    if (!archivo) return;
    docSubiendo.value[tipo] = true;
    const fd = new FormData();
    fd.append('tipo_documento', tipo);
    fd.append('archivo', archivo);
    try {
        const { data } = await axios.post(route('portal.solicitar.documento'), fd);
        if (data.ok) {
            docSubidos.value[tipo] = data;
            docArchivos.value[tipo] = null;
        }
    } catch (e: any) {
        errorMsg.value = e.response?.data?.message ?? 'Error al subir el documento.';
    } finally { docSubiendo.value[tipo] = false; }
}

// ─── Generar formatos ─────────────────────────────────────────────────────────
async function generarFormatos() {
    enviando.value = true;
    errorMsg.value = '';
    try {
        await axios.post(route('portal.solicitar.generar-formatos'));
        exitoso.value = true;
        paso.value = TOTAL_PASOS + 1;
    } catch (e: any) {
        const data = e.response?.data;
        if (data?.faltantes?.length) errorMsg.value = 'Faltan documentos: ' + data.faltantes.join(', ');
        else errorMsg.value = data?.error ?? 'Error al generar formatos.';
    } finally { enviando.value = false; }
}

function descargarFormatos() {
    if (solicitudId.value) window.location.href = route('portal.solicitar.descargar-formatos', solicitudId.value);
}

// ─── Helpers de UI ────────────────────────────────────────────────────────────
function addRow(arr: any[], template: any) { arr.push({ ...template }); }
function removeRow(arr: any[], idx: number) { arr.splice(idx, 1); }
const fmt = (v: any) => new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(+(v || 0));

const municipios = ['Mérida','Valladolid','Tizimín','Progreso','Umán','Kanasín','Motul','Tekax','Ticul','Hunucmá','Izamal','Espita','Oxkutzcab','Peto','Maxcanú','Acanceh','Baca','Conkal','Dzidzantún','Otro'];
const estadosCiviles = ['Soltero(a)','Casado(a)','Unión libre','Divorciado(a)','Viudo(a)'];
const regimenMatrimonial = ['Sociedad conyugal','Separación de bienes'];
const regimenesFiscales = ['Régimen Simplificado de Confianza','Régimen de Actividad Empresarial','Régimen General de Ley','Sin registro'];

// Clases reutilizables
const inp = 'w-full px-3 py-2.5 rounded-xl border border-slate-200 dark:border-zinc-700 dark:bg-zinc-800/50 focus:focus:ring-2 focus:ring-[#6B1938]/20 focus:border-[#6B1938] transition-all text-slate-900 dark:text-white placeholder:text-slate-400 text-sm bg-white';
const lbl = 'block text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-zinc-400 mb-1';
const err = 'text-xs text-red-600 mt-1';
const cardCls = 'bg-white dark:bg-zinc-900 rounded-2xl border border-slate-100 dark:border-zinc-800 shadow-sm overflow-hidden';
const secH = 'px-5 py-4 border-b border-slate-100 dark:border-zinc-800 flex items-center gap-3';
const secIcon = 'w-9 h-9 rounded-xl flex items-center justify-center shrink-0';

const pasos = [
    { n: 0, label: 'CURP',         icon: ShieldCheck },
    { n: 1, label: 'Modalidad',    icon: ListChecks },
    { n: 2, label: 'Persona',      icon: User },
    { n: 3, label: 'Empresa',      icon: Building2 },
    { n: 4, label: 'Negocio',      icon: Briefcase },
    { n: 5, label: 'Destino',      icon: Target },
    { n: 6, label: 'Ficha',        icon: BarChart2 },
    { n: 7, label: 'I/E',          icon: TrendingUp },
    { n: 8, label: 'Garantía',     icon: Users },
    { n: 9, label: 'Documentos',   icon: FileUp },
    { n: 10, label: 'Revisión',    icon: ClipboardCheck },
];
</script>

<template>
<BeneficiarioLayout>
    <Head title="Solicitar Crédito CREA — Wizard" />

    <!-- ÉXITO -->
    <div v-if="exitoso" class="max-w-2xl mx-auto space-y-6">
        <div class="bg-white dark:bg-zinc-900 rounded-3xl border border-emerald-200 dark:border-emerald-800/50 p-10 text-center space-y-6 shadow-xl">
            <div class="w-20 h-20 rounded-3xl bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center mx-auto">
                <CheckCircle2 size="40" class="text-[#6B1938] dark:text-[#f4a8c4]" />
            </div>
            <div class="space-y-2">
                <h1 class="text-2xl font-black text-slate-900 dark:text-white">¡Felicidades! Tu solicitud va a pasar a Comité.</h1>
                <p class="text-slate-500 dark:text-zinc-400 leading-relaxed max-w-md mx-auto">
                    Descarga tus formatos, fírmalos y entrégalos físicamente en las oficinas del Instituto Yucateco de Emprendedores (IYEM) para continuar con el proceso.
                </p>
            </div>
            <button @click="descargarFormatos"
                class="inline-flex items-center gap-3 px-8 py-4 bg-gradient-to-r from-[#6B1938] to-[#4A0E22] hover:from-[#4A0E22] hover:to-[#2E0816] text-white font-bold rounded-2xl shadow-lg shadow-emerald-900/25 transition-all text-base">
                <Download size="20" /> Descargar formatos (ZIP)
            </button>
            <a :href="route('portal.expediente')"
                class="block text-sm text-slate-500 dark:text-zinc-400 hover:text-[#6B1938] dark:text-[#f4a8c4] transition-colors mt-2">
                Ir a mi expediente →
            </a>
        </div>
        <!-- Ubicación IYEM -->
        <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-slate-100 dark:border-zinc-800 p-6 space-y-4">
            <h2 class="font-black text-slate-900 dark:text-white text-lg">¿Dónde entregar mis formatos?</h2>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm text-slate-600 dark:text-zinc-400">
                <div class="flex items-start gap-2"><MapPin size="16" class="text-[#6B1938] dark:text-[#f4a8c4] mt-0.5 shrink-0" />
                    <span>Av. Industrias No Contaminantes Tablaje 13613, Col. Sodzil Norte, Mérida, Yucatán</span>
                </div>
                <div class="flex items-center gap-2"><Phone size="16" class="text-[#6B1938] dark:text-[#f4a8c4] shrink-0" /><span>999 941 2170</span></div>
                <div class="flex items-center gap-2"><Mail size="16" class="text-[#6B1938] dark:text-[#f4a8c4] shrink-0" /><span>crea@iyemyucatan.com</span></div>
            </div>
            <div class="rounded-xl overflow-hidden border border-slate-200 dark:border-zinc-700 h-48">
                <iframe
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3724.2!2d-89.6435!3d21.0217!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zMjHCsDAxJzE3LjgiTiA4OcKwMzgnMzYuNiJX!5e0!3m2!1ses!2smx!4v1620000000"
                    width="100%" height="100%" style="border:0" loading="lazy" referrerpolicy="no-referrer-when-downgrade">
                </iframe>
            </div>
            <p class="text-xs text-slate-400">Horario de atención: Lunes a Viernes, 8:00 a 15:00 hrs.</p>
        </div>
    </div>

    <!-- WIZARD PRINCIPAL -->
    <div v-else class="max-w-4xl mx-auto space-y-5">

        <!-- Encabezado -->
        <div>
            <p class="text-xs font-bold uppercase tracking-widest text-[#6B1938] dark:text-[#f4a8c4]">Solicitud de Crédito CREA</p>
            <h1 class="text-2xl font-black text-slate-900 dark:text-white mt-0.5">Wizard de Solicitud</h1>
        </div>

        <!-- Stepper (desktop) -->
        <div class="hidden lg:flex items-center gap-0 overflow-x-auto pb-1">
            <template v-for="(p, i) in pasos" :key="p.n">
                <button type="button" @click="paso > p.n ? paso = p.n : null"
                    :class="['flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-bold transition-all shrink-0',
                        paso === p.n ? 'bg-[#6B1938] text-white shadow-md shadow-[#6B1938]/20'
                        : paso > p.n ? 'bg-emerald-50 dark:bg-emerald-900/20 text-[#6B1938] dark:text-[#f4a8c4] dark:text-emerald-400 cursor-pointer'
                        : 'bg-slate-100 dark:bg-zinc-800 text-slate-400 cursor-default'
                    ]">
                    <component :is="paso > p.n ? CheckCircle2 : p.icon" size="13" />
                    {{ p.label }}
                </button>
                <div v-if="i < pasos.length - 1" class="flex-1 h-px bg-slate-200 dark:bg-zinc-700 mx-1 min-w-[4px]"></div>
            </template>
        </div>

        <!-- Stepper compacto (mobile) -->
        <div class="lg:hidden flex items-center justify-between">
            <span class="text-sm font-bold text-[#6B1938] dark:text-[#f4a8c4]">Paso {{ paso + 1 }} / {{ TOTAL_PASOS + 1 }}</span>
            <div class="flex-1 mx-3 h-1.5 bg-slate-200 dark:bg-zinc-700 rounded-full overflow-hidden">
                <div class="h-full bg-[#6B1938] transition-all duration-300 rounded-full" :style="{ width: ((paso / TOTAL_PASOS) * 100) + '%' }"></div>
            </div>
            <span class="text-xs text-slate-400">{{ pasos[paso]?.label }}</span>
        </div>

        <!-- Error global -->
        <div v-if="errorMsg" class="rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800/40 p-4 flex items-start gap-3">
            <AlertTriangle size="18" class="text-red-600 shrink-0 mt-0.5" />
            <p class="text-sm text-red-700 dark:text-red-400">{{ errorMsg }}</p>
        </div>

        <!-- ════════════════════════════════════════════
             PASO 0 — VERIFICACIÓN CURP
        ════════════════════════════════════════════ -->
        <div v-if="paso === 0" :class="cardCls">
            <div :class="secH">
                <div :class="[secIcon, 'bg-emerald-50 dark:bg-emerald-900/20 text-[#6B1938] dark:text-[#f4a8c4]']"><ShieldCheck size="18" /></div>
                <div>
                    <h2 class="font-black text-slate-900 dark:text-white">Verificación de identidad</h2>
                    <p class="text-xs text-slate-400">Introduce tu CURP para iniciar la solicitud</p>
                </div>
            </div>
            <div class="p-6 space-y-5 max-w-sm">
                <div>
                    <label :class="lbl">CURP (18 caracteres) *</label>
                    <input v-model="curpInput" type="text" maxlength="18" placeholder="AAAA000000AAAAAA00"
                        :class="[inp, 'uppercase font-mono tracking-widest text-base']"
                        @keyup.enter="verificarCurp" />
                    <p v-if="curpInput && !curpValida" class="text-xs text-red-500 mt-1">Formato de CURP inválido</p>
                </div>
                <div v-if="curpBloqueada" class="rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800/40 p-4">
                    <div class="flex items-start gap-2">
                        <Lock size="16" class="text-red-600 mt-0.5 shrink-0" />
                        <p class="text-sm text-red-700 dark:text-red-400">{{ curpMensaje }}</p>
                    </div>
                </div>
                <button @click="verificarCurp" :disabled="!curpValida || verificandoCurp"
                    class="flex items-center gap-2 px-6 py-3 bg-[#6B1938] hover:bg-[#4A0E22] disabled:opacity-50 text-white font-bold rounded-xl transition-all">
                    <Loader2 v-if="verificandoCurp" size="16" class="animate-spin" />
                    <ShieldCheck v-else size="16" />
                    {{ verificandoCurp ? 'Verificando...' : 'Verificar CURP' }}
                </button>
            </div>
        </div>

        <!-- ════════════════════════════════════════════
             PASO 1 — MODALIDAD + TIPO PERSONA + GARANTÍA
        ════════════════════════════════════════════ -->
        <div v-if="paso === 1" :class="cardCls">
            <div :class="secH">
                <div :class="[secIcon, 'bg-emerald-50 dark:bg-emerald-900/20 text-[#6B1938] dark:text-[#f4a8c4]']"><ListChecks size="18" /></div>
                <div>
                    <h2 class="font-black text-slate-900 dark:text-white">Tipo de crédito</h2>
                    <p class="text-xs text-slate-400">Selecciona modalidad, tipo de persona y garantía</p>
                </div>
            </div>
            <div class="p-6 space-y-6">
                <!-- Modalidades -->
                <div>
                    <label :class="lbl">Modalidad de crédito *</label>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mt-2">
                        <button v-for="m in modalidades" :key="m.id" type="button"
                            @click="datos.modalidad_id = m.id; if(m.nombre?.toLowerCase().includes('artesanal')) datos.tipo_persona = 'fisica'"
                            :class="['rounded-xl border-2 p-4 text-left transition-all',
                                datos.modalidad_id == m.id
                                    ? 'border-[#6B1938] bg-[#6B1938]/5 dark:bg-[#6B1938]/10'
                                    : 'border-slate-200 dark:border-zinc-700 hover:border-[#6B1938]/60']">
                            <div class="font-black text-sm text-slate-900 dark:text-white">CREA {{ m.nombre }}</div>
                            <div class="text-xs text-slate-500 dark:text-zinc-400 mt-1">
                                {{ new Intl.NumberFormat('es-MX',{style:'currency',currency:'MXN',maximumFractionDigits:0}).format(m.monto_minimo) }} –
                                {{ new Intl.NumberFormat('es-MX',{style:'currency',currency:'MXN',maximumFractionDigits:0}).format(m.monto_maximo) }}
                            </div>
                            <div class="text-[10px] text-[#6B1938] dark:text-[#f4a8c4] dark:text-emerald-400 font-bold mt-1">{{ m.tasa_interes }}% interés anual</div>
                        </button>
                    </div>
                    <p v-if="isArtesanal" class="text-xs text-amber-600 mt-2 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800/40 rounded-lg p-2">
                        La modalidad Artesanal es exclusiva para Persona Física.
                    </p>
                </div>

                <!-- Tipo persona -->
                <div>
                    <label :class="lbl">Tipo de persona *</label>
                    <div class="flex gap-3 mt-2">
                        <button type="button" @click="datos.tipo_persona = 'fisica'"
                            :disabled="isArtesanal"
                            :class="['flex-1 rounded-xl border-2 py-3 px-4 font-bold text-sm transition-all',
                                datos.tipo_persona === 'fisica' ? 'border-[#6B1938] bg-[#6B1938]/5 dark:bg-[#6B1938]/10 text-[#6B1938] dark:text-[#f4a8c4]' : 'border-slate-200 dark:border-zinc-700 text-slate-600 dark:text-zinc-400 hover:border-[#6B1938]/60 disabled:opacity-40']">
                            Persona Física
                        </button>
                        <button type="button" @click="datos.tipo_persona = 'moral'"
                            :disabled="isArtesanal"
                            :class="['flex-1 rounded-xl border-2 py-3 px-4 font-bold text-sm transition-all',
                                datos.tipo_persona === 'moral' ? 'border-[#6B1938] bg-[#6B1938]/5 dark:bg-[#6B1938]/10 text-[#6B1938] dark:text-[#f4a8c4]' : 'border-slate-200 dark:border-zinc-700 text-slate-600 dark:text-zinc-400 hover:border-[#6B1938]/60 disabled:opacity-40']">
                            Persona Moral
                        </button>
                    </div>
                </div>

                <!-- Tipo garantía -->
                <div>
                    <label :class="lbl">Tipo de garantía *</label>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mt-2">
                        <button v-for="g in ['aval','prendaria','hipotecaria']" :key="g" type="button"
                            :disabled="g === 'hipotecaria' && !modalidadActual?.nombre?.toLowerCase().includes('sustentable')"
                            @click="datos.tipo_garantia = g"
                            :class="['rounded-xl border-2 py-3 px-4 font-bold text-sm text-left transition-all capitalize',
                                datos.tipo_garantia === g ? 'border-[#6B1938] bg-[#6B1938]/5 dark:bg-[#6B1938]/10 text-[#6B1938] dark:text-[#f4a8c4]' : 'border-slate-200 dark:border-zinc-700 text-slate-600 dark:text-zinc-400 hover:border-[#6B1938]/60 disabled:opacity-40']">
                            {{ g === 'aval' ? 'Aval solidario' : g === 'prendaria' ? 'Garantía prendaria' : 'Garantía hipotecaria' }}
                            <span v-if="g === 'hipotecaria'" class="block text-[10px] font-normal text-slate-400 mt-0.5">Solo Sustentable</span>
                        </button>
                    </div>
                </div>

                <div class="flex justify-end pt-2">
                    <button @click="guardarPaso1" :disabled="!datos.modalidad_id || enviando"
                        class="flex items-center gap-2 px-6 py-3 bg-[#6B1938] hover:bg-[#4A0E22] disabled:opacity-50 text-white font-bold rounded-xl transition-all">
                        <Loader2 v-if="enviando" size="16" class="animate-spin" />
                        <span>Siguiente</span><ChevronRight size="16" />
                    </button>
                </div>
            </div>
        </div>

        <!-- ════════════════════════════════════════════
             PASO 2 — DATOS PERSONALES
        ════════════════════════════════════════════ -->
        <div v-if="paso === 2" :class="cardCls">
            <div :class="secH">
                <div :class="[secIcon, 'bg-emerald-50 dark:bg-emerald-900/20 text-[#6B1938] dark:text-[#f4a8c4]']"><User size="18" /></div>
                <div><h2 class="font-black text-slate-900 dark:text-white">Datos personales</h2><p class="text-xs text-slate-400">Información del solicitante</p></div>
            </div>
            <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2"><label :class="lbl">Nombre completo *</label><input v-model="datos.nombre_completo" type="text" placeholder="Como aparece en tu INE" :class="inp" /></div>
                <div><label :class="lbl">CURP *</label><input :value="curpInput.toUpperCase()" readonly :class="[inp, 'font-mono tracking-widest bg-slate-50 dark:bg-zinc-800']" /></div>
                <div><label :class="lbl">RFC (con homoclave)</label><input v-model="datos.rfc" type="text" maxlength="13" :class="[inp, 'uppercase font-mono']" /></div>
                <div><label :class="lbl">Fecha de nacimiento *</label><input v-model="datos.fecha_nacimiento" type="date" :class="inp" /></div>
                <div><label :class="lbl">Sexo *</label>
                    <select v-model="datos.sexo" :class="inp">
                        <option value="" disabled>Seleccionar</option>
                        <option value="M">Masculino</option>
                        <option value="F">Femenino</option>
                    </select>
                </div>
                <div><label :class="lbl">Lugar de nacimiento</label><input v-model="datos.lugar_nacimiento" type="text" :class="inp" /></div>
                <div><label :class="lbl">Estado civil</label>
                    <select v-model="datos.estado_civil" :class="inp">
                        <option value="" disabled>Seleccionar</option>
                        <option v-for="e in estadosCiviles" :key="e" :value="e">{{ e }}</option>
                    </select>
                </div>
                <div v-if="['Casado(a)','Unión libre'].includes(datos.estado_civil)">
                    <label :class="lbl">Régimen matrimonial</label>
                    <select v-model="datos.regimen_matrimonial" :class="inp">
                        <option value="" disabled>Seleccionar</option>
                        <option v-for="r in regimenMatrimonial" :key="r" :value="r">{{ r }}</option>
                    </select>
                </div>
                <div v-if="['Casado(a)','Unión libre'].includes(datos.estado_civil)">
                    <label :class="lbl">Nombre del cónyuge</label><input v-model="datos.nombre_conyuge" type="text" :class="inp" />
                </div>
                <div v-if="['Casado(a)','Unión libre'].includes(datos.estado_civil)">
                    <label :class="lbl">CURP del cónyuge</label><input v-model="datos.curp_conyuge" type="text" maxlength="18" :class="[inp, 'uppercase font-mono']" />
                </div>
                <div class="sm:col-span-2"><label :class="lbl">Dirección (calle y número) *</label><input v-model="datos.direccion" type="text" :class="inp" /></div>
                <div><label :class="lbl">Colonia</label><input v-model="datos.colonia" type="text" :class="inp" /></div>
                <div><label :class="lbl">C.P.</label><input v-model="datos.cp" type="text" maxlength="5" :class="inp" /></div>
                <div><label :class="lbl">Municipio *</label>
                    <select v-model="datos.municipio" :class="inp">
                        <option value="" disabled>Seleccionar</option>
                        <option v-for="m in municipios" :key="m" :value="m">{{ m }}</option>
                    </select>
                </div>
                <div><label :class="lbl">Domicilio</label>
                    <div class="flex gap-3 mt-1">
                        <label class="flex items-center gap-2 cursor-pointer text-sm">
                            <input type="radio" :value="true" v-model="datos.domicilio_propio" class="text-[#6B1938] dark:text-[#f4a8c4]" /> Propio
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer text-sm">
                            <input type="radio" :value="false" v-model="datos.domicilio_propio" class="text-[#6B1938] dark:text-[#f4a8c4]" /> Rentado
                        </label>
                    </div>
                </div>
                <div v-if="!datos.domicilio_propio"><label :class="lbl">Renta mensual ($)</label><input v-model="datos.renta_mensual" type="number" min="0" :class="inp" /></div>
                <div><label :class="lbl">Teléfono celular *</label><input v-model="datos.telefono" type="tel" placeholder="10 dígitos" :class="inp" /></div>
                <div><label :class="lbl">Teléfono fijo</label><input v-model="datos.telefono_fijo" type="tel" :class="inp" /></div>
                <div class="sm:col-span-2"><label :class="lbl">Correo electrónico *</label><input v-model="datos.correo" type="email" :class="inp" /></div>
                <!-- Toggles -->
                <div class="sm:col-span-2 flex flex-wrap gap-5">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <div class="relative"><input type="checkbox" v-model="datos.mayahablante" class="sr-only peer" />
                            <div class="w-9 h-5 bg-slate-200 dark:bg-zinc-700 peer-checked:bg-emerald-600 rounded-full transition-colors"></div>
                            <div class="absolute left-1 top-1 w-3 h-3 bg-white rounded-full transition-transform peer-checked:translate-x-4 shadow-sm"></div>
                        </div>
                        <span class="text-sm font-medium text-slate-700 dark:text-zinc-300">¿Habla Maya?</span>
                    </label>
                    <label class="flex items-center gap-3 cursor-pointer">
                        <div class="relative"><input type="checkbox" v-model="datos.discapacidad" class="sr-only peer" />
                            <div class="w-9 h-5 bg-slate-200 dark:bg-zinc-700 peer-checked:bg-emerald-600 rounded-full transition-colors"></div>
                            <div class="absolute left-1 top-1 w-3 h-3 bg-white rounded-full transition-transform peer-checked:translate-x-4 shadow-sm"></div>
                        </div>
                        <span class="text-sm font-medium text-slate-700 dark:text-zinc-300">¿Tiene discapacidad?</span>
                    </label>
                    <label class="flex items-center gap-3 cursor-pointer">
                        <div class="relative"><input type="checkbox" v-model="datos.empleado_gobierno" class="sr-only peer" />
                            <div class="w-9 h-5 bg-slate-200 dark:bg-zinc-700 peer-checked:bg-emerald-600 rounded-full transition-colors"></div>
                            <div class="absolute left-1 top-1 w-3 h-3 bg-white rounded-full transition-transform peer-checked:translate-x-4 shadow-sm"></div>
                        </div>
                        <span class="text-sm font-medium text-slate-700 dark:text-zinc-300">¿Empleado gobierno?</span>
                    </label>
                </div>
                <div v-if="datos.discapacidad"><label :class="lbl">Tipo de discapacidad</label><input v-model="datos.discapacidad_tipo" type="text" :class="inp" /></div>
                <div v-if="datos.empleado_gobierno"><label :class="lbl">Dependencia</label><input v-model="datos.dependencia_gobierno" type="text" :class="inp" /></div>
                <div v-if="datos.empleado_gobierno"><label :class="lbl">Puesto</label><input v-model="datos.puesto_gobierno" type="text" :class="inp" /></div>
                <!-- Referencia personal -->
                <div class="sm:col-span-2 mt-2 pt-4 border-t border-slate-100 dark:border-zinc-800">
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-3">Referencia personal (no familiar)</p>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div><label :class="lbl">Nombre</label><input v-model="datos.referencia_nombre" type="text" :class="inp" /></div>
                        <div><label :class="lbl">Teléfono</label><input v-model="datos.referencia_telefono" type="tel" :class="inp" /></div>
                        <div><label :class="lbl">C.P.</label><input v-model="datos.referencia_cp" type="text" maxlength="5" :class="inp" /></div>
                    </div>
                </div>
            </div>
            <div class="px-6 pb-6 flex items-center justify-between">
                <button @click="retroceder" class="flex items-center gap-2 px-5 py-2.5 bg-slate-100 dark:bg-zinc-800 text-slate-700 dark:text-zinc-300 font-bold rounded-xl hover:bg-slate-200 dark:hover:bg-zinc-700 transition-all text-sm">
                    <ChevronLeft size="16" /> Anterior
                </button>
                <button @click="guardarPaso2" :disabled="!datos.nombre_completo || !datos.fecha_nacimiento || !datos.sexo || !datos.municipio || !datos.direccion || !datos.telefono || !datos.correo || enviando"
                    class="flex items-center gap-2 px-6 py-3 bg-[#6B1938] hover:bg-[#4A0E22] disabled:opacity-50 text-white font-bold rounded-xl transition-all text-sm">
                    <Loader2 v-if="enviando" size="15" class="animate-spin" />
                    <span>Siguiente</span><ChevronRight size="15" />
                </button>
            </div>
        </div>

        <!-- ════════════════════════════════════════════
             PASO 3 — PERSONA MORAL (condicional)
        ════════════════════════════════════════════ -->
        <div v-if="paso === 3 && datos.tipo_persona === 'moral'" :class="cardCls">
            <div :class="secH">
                <div :class="[secIcon, 'bg-emerald-50 dark:bg-emerald-900/20 text-[#6B1938] dark:text-[#f4a8c4]']"><Building2 size="18" /></div>
                <div><h2 class="font-black text-slate-900 dark:text-white">Datos de la empresa</h2><p class="text-xs text-slate-400">Información de la persona moral</p></div>
            </div>
            <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2"><label :class="lbl">Razón social *</label><input v-model="datos.razon_social" type="text" :class="inp" /></div>
                <div><label :class="lbl">RFC de la empresa *</label><input v-model="datos.rfc_moral" type="text" maxlength="13" :class="[inp,'uppercase font-mono']" /></div>
                <div><label :class="lbl">Fecha de constitución</label><input v-model="datos.fecha_constitucion" type="date" :class="inp" /></div>
                <div class="sm:col-span-2"><label :class="lbl">Domicilio fiscal</label><input v-model="datos.domicilio_moral" type="text" :class="inp" /></div>
                <div><label :class="lbl">Colonia</label><input v-model="datos.colonia_moral" type="text" :class="inp" /></div>
                <div><label :class="lbl">C.P.</label><input v-model="datos.cp_moral" type="text" maxlength="5" :class="inp" /></div>
                <div><label :class="lbl">Municipio</label>
                    <select v-model="datos.municipio_moral" :class="inp">
                        <option value="" disabled>Seleccionar</option>
                        <option v-for="m in municipios" :key="m" :value="m">{{ m }}</option>
                    </select>
                </div>
                <div><label :class="lbl">Teléfono empresa</label><input v-model="datos.telefono_moral" type="tel" :class="inp" /></div>
                <div class="sm:col-span-2"><label :class="lbl">Correo empresa</label><input v-model="datos.correo_moral" type="email" :class="inp" /></div>
                <div><label :class="lbl">Nombre del representante legal *</label><input v-model="datos.rep_legal" type="text" :class="inp" /></div>
                <div><label :class="lbl">CURP del representante</label><input v-model="datos.curp_rep" type="text" maxlength="18" :class="[inp,'uppercase font-mono']" /></div>
            </div>
            <div class="px-6 pb-6 flex items-center justify-between">
                <button @click="retroceder" class="flex items-center gap-2 px-5 py-2.5 bg-slate-100 dark:bg-zinc-800 text-slate-700 dark:text-zinc-300 font-bold rounded-xl hover:bg-slate-200 dark:hover:bg-zinc-700 transition-all text-sm">
                    <ChevronLeft size="16" /> Anterior
                </button>
                <button @click="guardarPaso3" :disabled="!datos.razon_social || !datos.rfc_moral || enviando"
                    class="flex items-center gap-2 px-6 py-3 bg-[#6B1938] hover:bg-[#4A0E22] disabled:opacity-50 text-white font-bold rounded-xl transition-all text-sm">
                    <Loader2 v-if="enviando" size="15" class="animate-spin" />
                    <span>Siguiente</span><ChevronRight size="15" />
                </button>
            </div>
        </div>

        <!-- ════════════════════════════════════════════
             PASO 4 — DATOS DEL NEGOCIO
        ════════════════════════════════════════════ -->
        <div v-if="paso === 4" :class="cardCls">
            <div :class="secH">
                <div :class="[secIcon, 'bg-emerald-50 dark:bg-emerald-900/20 text-[#6B1938] dark:text-[#f4a8c4]']"><Briefcase size="18" /></div>
                <div><h2 class="font-black text-slate-900 dark:text-white">Datos del negocio</h2><p class="text-xs text-slate-400">Información de tu emprendimiento</p></div>
            </div>
            <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div><label :class="lbl">Nombre comercial *</label><input v-model="datos.nombre_comercial" type="text" :class="inp" /></div>
                <div><label :class="lbl">Giro / Actividad *</label><input v-model="datos.giro_comercial" type="text" :class="inp" /></div>
                <div><label :class="lbl">Municipio del negocio</label>
                    <select v-model="datos.municipio_empresa" :class="inp">
                        <option value="" disabled>Seleccionar</option>
                        <option v-for="m in municipios" :key="m" :value="m">{{ m }}</option>
                    </select>
                </div>
                <div><label :class="lbl">Fecha de inicio del negocio</label><input v-model="datos.fecha_inicio_negocio" type="date" :class="inp" /></div>
                <div><label :class="lbl">Régimen fiscal</label>
                    <select v-model="datos.regimen_fiscal" :class="inp">
                        <option value="" disabled>Seleccionar</option>
                        <option v-for="r in regimenesFiscales" :key="r" :value="r">{{ r }}</option>
                    </select>
                </div>
                <div><label :class="lbl">Ventas mensuales aprox. ($)</label><input v-model="datos.ventas_mensuales" type="number" min="0" :class="inp" /></div>
                <div class="sm:col-span-2"><label :class="lbl">Descripción del negocio *</label><textarea v-model="datos.descripcion_negocio" rows="3" :class="[inp,'resize-none']"></textarea></div>
                <div class="sm:col-span-2"><label :class="lbl">Proceso de operación</label><textarea v-model="datos.proceso_operacion" rows="2" :class="[inp,'resize-none']"></textarea></div>
                <div><label :class="lbl">Mobiliario / maquinaria</label><textarea v-model="datos.mobiliario" rows="2" :class="[inp,'resize-none']"></textarea></div>
                <div><label :class="lbl">Recursos humanos (funciones)</label><textarea v-model="datos.recursos_humanos" rows="2" :class="[inp,'resize-none']"></textarea></div>
                <div class="sm:col-span-2 flex flex-wrap gap-5">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <div class="relative"><input type="checkbox" v-model="datos.alta_sat" class="sr-only peer" />
                            <div class="w-9 h-5 bg-slate-200 dark:bg-zinc-700 peer-checked:bg-emerald-600 rounded-full transition-colors"></div>
                            <div class="absolute left-1 top-1 w-3 h-3 bg-white rounded-full transition-transform peer-checked:translate-x-4 shadow-sm"></div>
                        </div>
                        <span class="text-sm font-medium text-slate-700 dark:text-zinc-300">Dado de alta en el SAT</span>
                    </label>
                    <label class="flex items-center gap-3 cursor-pointer">
                        <div class="relative"><input type="checkbox" v-model="datos.propiedad_intelectual" class="sr-only peer" />
                            <div class="w-9 h-5 bg-slate-200 dark:bg-zinc-700 peer-checked:bg-emerald-600 rounded-full transition-colors"></div>
                            <div class="absolute left-1 top-1 w-3 h-3 bg-white rounded-full transition-transform peer-checked:translate-x-4 shadow-sm"></div>
                        </div>
                        <span class="text-sm font-medium text-slate-700 dark:text-zinc-300">Propiedad intelectual / Patente</span>
                    </label>
                </div>
                <div v-if="datos.propiedad_intelectual" class="sm:col-span-2">
                    <label :class="lbl">Detalle de la propiedad intelectual</label><input v-model="datos.detalle_pi" type="text" :class="inp" />
                </div>
            </div>
            <div class="px-6 pb-6 flex items-center justify-between">
                <button @click="retroceder" class="flex items-center gap-2 px-5 py-2.5 bg-slate-100 dark:bg-zinc-800 text-slate-700 dark:text-zinc-300 font-bold rounded-xl hover:bg-slate-200 dark:hover:bg-zinc-700 transition-all text-sm"><ChevronLeft size="16" /> Anterior</button>
                <button @click="guardarPaso4" :disabled="!datos.nombre_comercial || !datos.giro_comercial || !datos.descripcion_negocio || enviando"
                    class="flex items-center gap-2 px-6 py-3 bg-[#6B1938] hover:bg-[#4A0E22] disabled:opacity-50 text-white font-bold rounded-xl transition-all text-sm">
                    <Loader2 v-if="enviando" size="15" class="animate-spin" /><span>Siguiente</span><ChevronRight size="15" />
                </button>
            </div>
        </div>

        <!-- ════════════════════════════════════════════
             PASO 5 — DESTINO DEL CRÉDITO
        ════════════════════════════════════════════ -->
        <div v-if="paso === 5" :class="cardCls">
            <div :class="secH">
                <div :class="[secIcon, 'bg-emerald-50 dark:bg-emerald-900/20 text-[#6B1938] dark:text-[#f4a8c4]']"><Target size="18" /></div>
                <div><h2 class="font-black text-slate-900 dark:text-white">Destino del crédito</h2></div>
            </div>
            <div class="p-6 space-y-5">
                <!-- Tabla destino -->
                <div>
                    <label :class="lbl">Conceptos del destino *</label>
                    <div class="space-y-2 mt-2">
                        <div v-for="(item, i) in destinoTabla" :key="i" class="flex gap-2">
                            <input v-model="item.concepto" type="text" placeholder="Concepto" :class="[inp,'flex-1']" />
                            <input v-model="item.importe" type="number" min="0" placeholder="Importe" :class="[inp,'w-32']" />
                            <button v-if="destinoTabla.length > 1" @click="removeRow(destinoTabla, i)" class="p-2 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"><Trash2 size="15" /></button>
                        </div>
                    </div>
                    <button @click="addRow(destinoTabla, {concepto:'',importe:''})" class="mt-2 flex items-center gap-1.5 text-xs text-[#6B1938] dark:text-[#f4a8c4] hover:text-emerald-800 font-bold">
                        <Plus size="13" /> Agregar concepto
                    </button>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div><label :class="lbl">Importe total del proyecto ($)</label><input v-model="datos.importe_total_proyecto" type="number" min="0" :class="inp" /></div>
                    <div>
                        <label :class="lbl">Monto a solicitar ($) *</label>
                        <input v-model="datos.monto_solicitado" type="number" :min="modalidadActual?.monto_minimo" :max="modalidadActual?.monto_maximo" :class="inp" />
                        <p v-if="modalidadActual" class="text-[10px] text-slate-400 mt-1">
                            Rango: {{ fmt(modalidadActual.monto_minimo) }} – {{ fmt(modalidadActual.monto_maximo) }}
                        </p>
                    </div>
                    <div>
                        <label :class="lbl">Plazo solicitado *</label>
                        <select v-model="datos.plazo_meses" :class="inp">
                            <option value="" disabled>Seleccionar</option>
                            <option v-for="p in plazosDisponibles" :key="p" :value="p">{{ p }} meses</option>
                        </select>
                        <p v-if="modalidadActual?.nombre?.toLowerCase().includes('sustentable')" class="text-[10px] text-amber-600 mt-1">Incluye 3 meses de prórroga para el primer pago.</p>
                    </div>
                </div>
                <!-- Apoyos gobierno -->
                <div>
                    <label :class="lbl">Créditos/apoyos de gobierno previos (opcional)</label>
                    <div class="space-y-2 mt-2">
                        <div v-for="(a, i) in apoyosGob" :key="i" class="flex gap-2">
                            <input v-model="a.dependencia" type="text" placeholder="Dependencia" :class="[inp,'flex-1']" />
                            <input v-model="a.destino" type="text" placeholder="Destino" :class="[inp,'flex-1']" />
                            <input v-model="a.monto" type="number" min="0" placeholder="Monto" :class="[inp,'w-28']" />
                            <button @click="removeRow(apoyosGob, i)" class="p-2 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg"><Trash2 size="15" /></button>
                        </div>
                    </div>
                    <button @click="addRow(apoyosGob,{dependencia:'',destino:'',monto:''})" class="mt-2 flex items-center gap-1.5 text-xs text-[#6B1938] dark:text-[#f4a8c4] font-bold">
                        <Plus size="13" /> Agregar apoyo
                    </button>
                </div>
            </div>
            <div class="px-6 pb-6 flex items-center justify-between">
                <button @click="retroceder" class="flex items-center gap-2 px-5 py-2.5 bg-slate-100 dark:bg-zinc-800 text-slate-700 dark:text-zinc-300 font-bold rounded-xl hover:bg-slate-200 dark:hover:bg-zinc-700 transition-all text-sm"><ChevronLeft size="16" /> Anterior</button>
                <button @click="guardarPaso5" :disabled="!datos.monto_solicitado || !datos.plazo_meses || enviando"
                    class="flex items-center gap-2 px-6 py-3 bg-[#6B1938] hover:bg-[#4A0E22] disabled:opacity-50 text-white font-bold rounded-xl transition-all text-sm">
                    <Loader2 v-if="enviando" size="15" class="animate-spin" /><span>Siguiente</span><ChevronRight size="15" />
                </button>
            </div>
        </div>

        <!-- ════════════════════════════════════════════
             PASO 6 — FICHA TÉCNICA
        ════════════════════════════════════════════ -->
        <div v-if="paso === 6" :class="cardCls">
            <div :class="secH">
                <div :class="[secIcon, 'bg-emerald-50 dark:bg-emerald-900/20 text-[#6B1938] dark:text-[#f4a8c4]']"><BarChart2 size="18" /></div>
                <div><h2 class="font-black text-slate-900 dark:text-white">Ficha técnica</h2><p class="text-xs text-slate-400">Proveedores, clientes y deudas del negocio</p></div>
            </div>
            <div class="p-6 space-y-6">
                <!-- Proveedores -->
                <div>
                    <p :class="[lbl,'mb-2']">Principales proveedores</p>
                    <div class="overflow-x-auto">
                        <table class="w-full text-xs">
                            <thead class="bg-slate-100 dark:bg-zinc-800">
                                <tr>
                                    <th class="px-2 py-2 text-left font-bold text-slate-600 dark:text-zinc-300">Nombre</th>
                                    <th class="px-2 py-2 text-left font-bold text-slate-600 dark:text-zinc-300">Antigüedad</th>
                                    <th class="px-2 py-2 text-left font-bold text-slate-600 dark:text-zinc-300">Insumo</th>
                                    <th class="px-2 py-2 text-left font-bold text-slate-600 dark:text-zinc-300">Compras/mes $</th>
                                    <th class="px-2 py-2 text-left font-bold text-slate-600 dark:text-zinc-300">Política</th>
                                    <th class="px-1 py-2"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(p, i) in proveedores" :key="i" class="border-b border-slate-100 dark:border-zinc-800">
                                    <td class="px-1 py-1"><input v-model="p.nombre" type="text" :class="[inp,'!py-1.5 !px-2 text-xs']" /></td>
                                    <td class="px-1 py-1"><input v-model="p.antiguedad" type="text" placeholder="1 año" :class="[inp,'!py-1.5 !px-2 text-xs']" /></td>
                                    <td class="px-1 py-1"><input v-model="p.insumo" type="text" :class="[inp,'!py-1.5 !px-2 text-xs']" /></td>
                                    <td class="px-1 py-1"><input v-model="p.compras_mensuales" type="number" min="0" :class="[inp,'!py-1.5 !px-2 text-xs w-24']" /></td>
                                    <td class="px-1 py-1">
                                        <select v-model="p.politica" :class="[inp,'!py-1.5 !px-2 text-xs']">
                                            <option>Contado</option><option>Crédito</option>
                                        </select>
                                    </td>
                                    <td class="px-1 py-1"><button v-if="proveedores.length > 1" @click="removeRow(proveedores,i)" class="text-red-500 p-1 rounded hover:bg-red-50 dark:hover:bg-red-900/20"><Trash2 size="12" /></button></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <button @click="addRow(proveedores,{nombre:'',antiguedad:'',insumo:'',compras_mensuales:'',politica:'Contado'})" class="mt-2 flex items-center gap-1.5 text-xs text-[#6B1938] dark:text-[#f4a8c4] font-bold"><Plus size="13" /> Agregar proveedor</button>
                </div>

                <!-- Clientes -->
                <div>
                    <p :class="[lbl,'mb-2']">Principales clientes</p>
                    <div class="overflow-x-auto">
                        <table class="w-full text-xs">
                            <thead class="bg-slate-100 dark:bg-zinc-800">
                                <tr>
                                    <th class="px-2 py-2 text-left font-bold text-slate-600 dark:text-zinc-300">Nombre</th>
                                    <th class="px-2 py-2 text-left font-bold text-slate-600 dark:text-zinc-300">Sector</th>
                                    <th class="px-2 py-2 text-left font-bold text-slate-600 dark:text-zinc-300">Ventas/mes $</th>
                                    <th class="px-2 py-2 text-left font-bold text-slate-600 dark:text-zinc-300">Política</th>
                                    <th class="px-1 py-2"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(c, i) in clientes" :key="i" class="border-b border-slate-100 dark:border-zinc-800">
                                    <td class="px-1 py-1"><input v-model="c.nombre" type="text" :class="[inp,'!py-1.5 !px-2 text-xs']" /></td>
                                    <td class="px-1 py-1"><input v-model="c.sector" type="text" :class="[inp,'!py-1.5 !px-2 text-xs']" /></td>
                                    <td class="px-1 py-1"><input v-model="c.ventas_mensuales" type="number" min="0" :class="[inp,'!py-1.5 !px-2 text-xs w-24']" /></td>
                                    <td class="px-1 py-1">
                                        <select v-model="c.politica" :class="[inp,'!py-1.5 !px-2 text-xs']">
                                            <option>Contado</option><option>Crédito</option>
                                        </select>
                                    </td>
                                    <td class="px-1 py-1"><button v-if="clientes.length > 1" @click="removeRow(clientes,i)" class="text-red-500 p-1 rounded hover:bg-red-50 dark:hover:bg-red-900/20"><Trash2 size="12" /></button></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <button @click="addRow(clientes,{nombre:'',sector:'',ventas_mensuales:'',politica:'Contado'})" class="mt-2 flex items-center gap-1.5 text-xs text-[#6B1938] dark:text-[#f4a8c4] font-bold"><Plus size="13" /> Agregar cliente</button>
                </div>

                <!-- Deudas del negocio -->
                <div>
                    <p :class="[lbl,'mb-2']">Deudas y obligaciones del negocio (opcional)</p>
                    <div class="space-y-2">
                        <div v-for="(d, i) in deudasNegocio" :key="i" class="flex gap-2">
                            <input v-model="d.nombre" type="text" placeholder="Institución" :class="[inp,'flex-1']" />
                            <input v-model="d.monto" type="number" min="0" placeholder="Monto" :class="[inp,'w-28']" />
                            <input v-model="d.vencimiento" type="text" placeholder="Vencimiento" :class="[inp,'w-28']" />
                            <input v-model="d.garantia" type="text" placeholder="Garantía" :class="[inp,'w-28']" />
                            <button @click="removeRow(deudasNegocio,i)" class="p-2 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg"><Trash2 size="15" /></button>
                        </div>
                    </div>
                    <button @click="addRow(deudasNegocio,{nombre:'',monto:'',vencimiento:'',garantia:''})" class="mt-2 flex items-center gap-1.5 text-xs text-[#6B1938] dark:text-[#f4a8c4] font-bold"><Plus size="13" /> Agregar deuda</button>
                </div>
            </div>
            <div class="px-6 pb-6 flex items-center justify-between">
                <button @click="retroceder" class="flex items-center gap-2 px-5 py-2.5 bg-slate-100 dark:bg-zinc-800 text-slate-700 dark:text-zinc-300 font-bold rounded-xl hover:bg-slate-200 dark:hover:bg-zinc-700 transition-all text-sm"><ChevronLeft size="16" /> Anterior</button>
                <button @click="guardarPaso6" :disabled="enviando" class="flex items-center gap-2 px-6 py-3 bg-[#6B1938] hover:bg-[#4A0E22] disabled:opacity-50 text-white font-bold rounded-xl transition-all text-sm">
                    <Loader2 v-if="enviando" size="15" class="animate-spin" /><span>Siguiente</span><ChevronRight size="15" />
                </button>
            </div>
        </div>

        <!-- ════════════════════════════════════════════
             PASO 7 — INGRESOS Y EGRESOS
        ════════════════════════════════════════════ -->
        <div v-if="paso === 7" :class="cardCls">
            <div :class="secH">
                <div :class="[secIcon, 'bg-emerald-50 dark:bg-emerald-900/20 text-[#6B1938] dark:text-[#f4a8c4]']"><TrendingUp size="18" /></div>
                <div><h2 class="font-black text-slate-900 dark:text-white">Estado de ingresos y egresos</h2><p class="text-xs text-slate-400">Información financiera mensual del negocio</p></div>
            </div>
            <div class="p-6 space-y-5">
                <div class="grid grid-cols-2 gap-4">
                    <div><label :class="lbl">Periodo del</label><input v-model="datos.periodo_del" type="date" :class="inp" /></div>
                    <div><label :class="lbl">al</label><input v-model="datos.periodo_al" type="date" :class="inp" /></div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div><label :class="lbl">Ventas / Servicios ($) *</label><input v-model="datos.ventas" type="number" min="0" :class="inp" /></div>
                    <div><label :class="lbl">Costo del producto / servicio ($)</label><input v-model="datos.costo_producto" type="number" min="0" :class="inp" /></div>
                </div>
                <!-- Cálculo utilidad bruta -->
                <div class="bg-emerald-50 dark:bg-emerald-900/20 rounded-xl px-4 py-2 flex items-center justify-between">
                    <span class="text-sm font-bold text-emerald-800 dark:text-emerald-400">Utilidad bruta</span>
                    <span class="font-black text-[#6B1938] dark:text-[#f4a8c4]">{{ fmt(utilBruta) }}</span>
                </div>
                <p :class="[lbl]">Gastos de operación mensuales</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div v-for="[k,l] in [['gastos_electricidad','Energía eléctrica'],['gastos_agua','Agua'],['gastos_telefono','Teléfono'],['gastos_gas','Gas'],['gastos_mano_obra','Mano de obra (solicitante)'],['gastos_nomina','Nómina empleados'],['gastos_renta_local','Renta del local']]" :key="k">
                        <label :class="lbl">{{ l }} ($)</label>
                        <input v-model="datos[k]" type="number" min="0" :class="inp" />
                    </div>
                </div>
                <!-- Otros gastos -->
                <div>
                    <label :class="lbl">Otros gastos</label>
                    <div class="space-y-2 mt-1">
                        <div v-for="(g, i) in otrosGastos" :key="i" class="flex gap-2">
                            <input v-model="g.concepto" type="text" placeholder="Concepto" :class="[inp,'flex-1']" />
                            <input v-model="g.importe" type="number" min="0" placeholder="Importe" :class="[inp,'w-32']" />
                            <button @click="removeRow(otrosGastos,i)" class="p-2 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg"><Trash2 size="15" /></button>
                        </div>
                    </div>
                    <button @click="addRow(otrosGastos,{concepto:'',importe:''})" class="mt-1 flex items-center gap-1.5 text-xs text-[#6B1938] dark:text-[#f4a8c4] font-bold"><Plus size="13" /> Agregar otro gasto</button>
                </div>
                <!-- Resumen calculado -->
                <div class="bg-slate-50 dark:bg-zinc-800 rounded-xl p-4 space-y-1.5 text-sm">
                    <div class="flex justify-between"><span class="text-slate-500">Total gastos:</span><span class="font-bold">{{ fmt(totalGastos) }}</span></div>
                    <div class="flex justify-between"><span class="text-slate-500">Utilidad antes de impuestos:</span><span class="font-bold">{{ fmt(utilAnteImp) }}</span></div>
                </div>
                <div><label :class="lbl">Impuestos ($)</label><input v-model="datos.impuestos" type="number" min="0" :class="inp" /></div>
                <div class="bg-[#6B1938] rounded-xl px-4 py-3 flex items-center justify-between">
                    <span class="text-sm font-black text-white">Utilidad neta mensual</span>
                    <span class="font-black text-white text-lg">{{ fmt(utilNeta) }}</span>
                </div>
            </div>
            <div class="px-6 pb-6 flex items-center justify-between">
                <button @click="retroceder" class="flex items-center gap-2 px-5 py-2.5 bg-slate-100 dark:bg-zinc-800 text-slate-700 dark:text-zinc-300 font-bold rounded-xl hover:bg-slate-200 dark:hover:bg-zinc-700 transition-all text-sm"><ChevronLeft size="16" /> Anterior</button>
                <button @click="guardarPaso7" :disabled="!datos.ventas || enviando" class="flex items-center gap-2 px-6 py-3 bg-[#6B1938] hover:bg-[#4A0E22] disabled:opacity-50 text-white font-bold rounded-xl transition-all text-sm">
                    <Loader2 v-if="enviando" size="15" class="animate-spin" /><span>Siguiente</span><ChevronRight size="15" />
                </button>
            </div>
        </div>

        <!-- ════════════════════════════════════════════
             PASO 8 — AVAL / GARANTÍA
        ════════════════════════════════════════════ -->
        <div v-if="paso === 8" :class="cardCls">
            <div :class="secH">
                <div :class="[secIcon, 'bg-emerald-50 dark:bg-emerald-900/20 text-[#6B1938] dark:text-[#f4a8c4]']"><Users size="18" /></div>
                <div>
                    <h2 class="font-black text-slate-900 dark:text-white">{{ datos.tipo_garantia === 'aval' ? 'Datos del Aval' : 'Datos de la Garantía' }}</h2>
                    <p class="text-xs text-slate-400">{{ datos.tipo_garantia === 'aval' ? 'Información del aval solidario' : datos.tipo_garantia === 'prendaria' ? 'Bien mueble en garantía' : 'Bien inmueble en garantía' }}</p>
                </div>
            </div>

            <!-- Aval -->
            <div v-if="datos.tipo_garantia === 'aval'" class="p-6 space-y-5">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2"><label :class="lbl">Nombre completo del aval *</label><input v-model="aval.nombre_completo" type="text" :class="inp" /></div>
                    <div><label :class="lbl">CURP del aval</label><input v-model="aval.curp" type="text" maxlength="18" :class="[inp,'uppercase font-mono']" /></div>
                    <div><label :class="lbl">RFC del aval</label><input v-model="aval.rfc" type="text" maxlength="13" :class="[inp,'uppercase font-mono']" /></div>
                    <div><label :class="lbl">Fecha de nacimiento</label><input v-model="aval.fecha_nacimiento" type="date" :class="inp" /></div>
                    <div><label :class="lbl">Edad</label><input v-model="aval.edad" type="number" min="18" max="99" :class="inp" /></div>
                    <div><label :class="lbl">Sexo</label>
                        <select v-model="aval.sexo" :class="inp"><option value="" disabled>Seleccionar</option><option value="M">Masculino</option><option value="F">Femenino</option></select>
                    </div>
                    <div><label :class="lbl">Estado civil</label>
                        <select v-model="aval.estado_civil" :class="inp"><option value="" disabled>Seleccionar</option><option v-for="e in estadosCiviles" :key="e" :value="e">{{ e }}</option></select>
                    </div>
                    <div><label :class="lbl">Correo electrónico</label><input v-model="aval.correo" type="email" :class="inp" /></div>
                    <div><label :class="lbl">Teléfono celular</label><input v-model="aval.telefono_celular" type="tel" :class="inp" /></div>
                    <div><label :class="lbl">Teléfono fijo</label><input v-model="aval.telefono_fijo" type="tel" :class="inp" /></div>
                    <div class="sm:col-span-2"><label :class="lbl">Dirección</label><input v-model="aval.domicilio" type="text" :class="inp" /></div>
                    <div><label :class="lbl">Colonia</label><input v-model="aval.colonia" type="text" :class="inp" /></div>
                    <div><label :class="lbl">C.P.</label><input v-model="aval.cp" type="text" maxlength="5" :class="inp" /></div>
                    <div><label :class="lbl">Municipio de residencia</label>
                        <select v-model="aval.municipio_residencia" :class="inp"><option value="" disabled>Seleccionar</option><option v-for="m in municipios" :key="m" :value="m">{{ m }}</option></select>
                    </div>
                    <div><label :class="lbl">Municipio de nacimiento</label>
                        <select v-model="aval.municipio_nacimiento" :class="inp"><option value="" disabled>Seleccionar</option><option v-for="m in municipios" :key="m" :value="m">{{ m }}</option></select>
                    </div>
                    <div><label :class="lbl">Ocupación</label><input v-model="aval.ocupacion" type="text" :class="inp" /></div>
                    <div><label :class="lbl">Lugar de trabajo</label><input v-model="aval.lugar_laboral" type="text" :class="inp" /></div>
                    <div><label :class="lbl">Antigüedad laboral</label><input v-model="aval.antiguedad_laboral" type="text" placeholder="Ej: 3 años" :class="inp" /></div>
                    <div><label :class="lbl">Dependientes económicos</label><input v-model="aval.dependientes_economicos" type="number" min="0" :class="inp" /></div>
                </div>
                <!-- Bienes inmuebles -->
                <div class="pt-4 border-t border-slate-100 dark:border-zinc-800">
                    <p :class="[lbl,'mb-2']">Propiedades inmobiliarias del aval</p>
                    <div class="space-y-2">
                        <div v-for="(b, i) in avBienes" :key="i" class="flex gap-2">
                            <input v-model="b.descripcion" type="text" placeholder="Descripción del inmueble" :class="[inp,'flex-1']" />
                            <input v-model="b.ubicacion" type="text" placeholder="Ubicación" :class="[inp,'flex-1']" />
                            <input v-model="b.valor" type="number" min="0" placeholder="Valor $" :class="[inp,'w-32']" />
                            <button v-if="avBienes.length > 1" @click="removeRow(avBienes,i)" class="p-2 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg"><Trash2 size="15" /></button>
                        </div>
                    </div>
                    <button @click="addRow(avBienes,{descripcion:'',ubicacion:'',valor:''})" class="mt-1 flex items-center gap-1.5 text-xs text-[#6B1938] dark:text-[#f4a8c4] font-bold"><Plus size="13" /> Agregar inmueble</button>
                </div>
                <!-- Referencias del aval -->
                <div class="pt-4 border-t border-slate-100 dark:border-zinc-800">
                    <p :class="[lbl,'mb-2']">Referencias personales del aval (no familiares)</p>
                    <div class="space-y-2">
                        <div v-for="(r, i) in avReferencias" :key="i" class="flex gap-2">
                            <input v-model="r.nombre" type="text" placeholder="Nombre" :class="[inp,'flex-1']" />
                            <input v-model="r.telefono" type="tel" placeholder="Teléfono" :class="[inp,'w-36']" />
                            <input v-model="r.cp" type="text" placeholder="C.P." maxlength="5" :class="[inp,'w-20']" />
                            <button v-if="avReferencias.length > 1" @click="removeRow(avReferencias,i)" class="p-2 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg"><Trash2 size="15" /></button>
                        </div>
                    </div>
                    <button @click="addRow(avReferencias,{nombre:'',telefono:'',cp:''})" class="mt-1 flex items-center gap-1.5 text-xs text-[#6B1938] dark:text-[#f4a8c4] font-bold"><Plus size="13" /> Agregar referencia</button>
                </div>
            </div>

            <!-- Garantía prendaria / hipotecaria -->
            <div v-else class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2"><label :class="lbl">Descripción del {{ datos.tipo_garantia === 'prendaria' ? 'bien mueble' : 'bien inmueble' }} *</label><textarea v-model="datos.garantia_descripcion" rows="2" :class="[inp,'resize-none']"></textarea></div>
                <div><label :class="lbl">Valor estimado ($) *</label><input v-model="datos.garantia_valor" type="number" min="0" :class="inp" /></div>
                <div v-if="datos.tipo_garantia === 'prendaria'"><label :class="lbl">Fecha de factura</label><input v-model="datos.garantia_fecha_factura" type="date" :class="inp" /></div>
                <div v-if="datos.tipo_garantia === 'prendaria'"><label :class="lbl">Valor de factura ($)</label><input v-model="datos.garantia_valor_factura" type="number" min="0" :class="inp" /></div>
            </div>

            <div class="px-6 pb-6 flex items-center justify-between">
                <button @click="retroceder" class="flex items-center gap-2 px-5 py-2.5 bg-slate-100 dark:bg-zinc-800 text-slate-700 dark:text-zinc-300 font-bold rounded-xl hover:bg-slate-200 dark:hover:bg-zinc-700 transition-all text-sm"><ChevronLeft size="16" /> Anterior</button>
                <button @click="guardarPaso8" :disabled="(datos.tipo_garantia === 'aval' && !aval.nombre_completo) || enviando" class="flex items-center gap-2 px-6 py-3 bg-[#6B1938] hover:bg-[#4A0E22] disabled:opacity-50 text-white font-bold rounded-xl transition-all text-sm">
                    <Loader2 v-if="enviando" size="15" class="animate-spin" /><span>Siguiente</span><ChevronRight size="15" />
                </button>
            </div>
        </div>

        <!-- ════════════════════════════════════════════
             PASO 9 — DOCUMENTOS
        ════════════════════════════════════════════ -->
        <div v-if="paso === 9" :class="cardCls">
            <div :class="secH">
                <div :class="[secIcon, 'bg-emerald-50 dark:bg-emerald-900/20 text-[#6B1938] dark:text-[#f4a8c4]']"><FileUp size="18" /></div>
                <div class="flex-1">
                    <h2 class="font-black text-slate-900 dark:text-white">Documentos requeridos</h2>
                    <p class="text-xs text-slate-400">PDF, JPG o PNG — máx. 5 MB</p>
                </div>
                <span :class="['text-xs font-bold px-3 py-1 rounded-full', todosDocsCompletos ? 'bg-emerald-100 text-[#6B1938] dark:text-[#f4a8c4] dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400']">
                    {{ totalDocsSubidos }} / {{ totalDocsReq }} subidos
                </span>
            </div>
            <div class="p-6 space-y-3">
                <div v-for="(label, tipo) in tiposDocRequeridos" :key="tipo">
                    <div :class="['rounded-xl border p-4 space-y-2',
                        docSubidos[tipo] ? (docSubidos[tipo].estatus === 'Aprobado' ? 'bg-green-50 dark:bg-green-900/10 border-green-200 dark:border-green-800/40' : docSubidos[tipo].estatus === 'Rechazado' ? 'bg-red-50 dark:bg-red-900/10 border-red-200 dark:border-red-800/40' : 'bg-amber-50 dark:bg-amber-900/10 border-amber-200 dark:border-amber-800/40')
                        : 'bg-slate-50 dark:bg-zinc-800 border-slate-200 dark:border-zinc-700']">
                        <div class="flex items-center justify-between gap-2 flex-wrap">
                            <p class="text-sm font-bold text-slate-900 dark:text-white">{{ label }}</p>
                            <span v-if="docSubidos[tipo]" :class="['text-[10px] font-black px-2 py-0.5 rounded-full',
                                docSubidos[tipo].estatus === 'Aprobado' ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                                : docSubidos[tipo].estatus === 'Rechazado' ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
                                : 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400']">
                                {{ docSubidos[tipo].estatus ?? 'Pendiente' }}
                            </span>
                        </div>
                        <p v-if="docSubidos[tipo]" class="text-[11px] text-slate-500 dark:text-zinc-400 truncate">📎 {{ docSubidos[tipo].nombre_original }}</p>
                        <div class="flex items-center gap-2">
                            <label :for="`doc-${tipo}`" class="flex-1 flex items-center justify-center gap-2 px-3 py-2 border-2 border-dashed border-slate-300 dark:border-zinc-600 rounded-lg cursor-pointer hover:border-[#6B1938]/60 hover:bg-emerald-50 dark:hover:bg-emerald-900/10 transition-all text-xs text-slate-500 dark:text-zinc-400 hover:text-[#6B1938] dark:text-[#f4a8c4]">
                                <FileUp size="13" />
                                {{ docArchivos[tipo] ? docArchivos[tipo]?.name : (docSubidos[tipo] ? 'Reemplazar' : 'Seleccionar archivo') }}
                                <input :id="`doc-${tipo}`" type="file" accept=".pdf,.jpg,.jpeg,.png" class="sr-only" @change="seleccionarDoc(tipo, $event)" />
                            </label>
                            <button v-if="docArchivos[tipo]" @click="subirDoc(tipo)" :disabled="docSubiendo[tipo]"
                                class="px-4 py-2 bg-[#6B1938] hover:bg-[#4A0E22] disabled:opacity-60 text-white font-bold rounded-lg text-xs flex items-center gap-1.5 shrink-0">
                                <Loader2 v-if="docSubiendo[tipo]" size="12" class="animate-spin" />
                                {{ docSubiendo[tipo] ? 'Subiendo...' : 'Subir' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="px-6 pb-6 flex items-center justify-between">
                <button @click="retroceder" class="flex items-center gap-2 px-5 py-2.5 bg-slate-100 dark:bg-zinc-800 text-slate-700 dark:text-zinc-300 font-bold rounded-xl hover:bg-slate-200 dark:hover:bg-zinc-700 transition-all text-sm"><ChevronLeft size="16" /> Anterior</button>
                <button @click="avanzar" :disabled="!todosDocsCompletos"
                    class="flex items-center gap-2 px-6 py-3 bg-[#6B1938] hover:bg-[#4A0E22] disabled:opacity-50 text-white font-bold rounded-xl transition-all text-sm">
                    <span>Revisar solicitud</span><ChevronRight size="15" />
                </button>
            </div>
        </div>

        <!-- ════════════════════════════════════════════
             PASO 10 — REVISIÓN FINAL
        ════════════════════════════════════════════ -->
        <div v-if="paso === 10" :class="cardCls">
            <div :class="secH">
                <div :class="[secIcon, 'bg-emerald-50 dark:bg-emerald-900/20 text-[#6B1938] dark:text-[#f4a8c4]']"><ClipboardCheck size="18" /></div>
                <div><h2 class="font-black text-slate-900 dark:text-white">Revisión final</h2><p class="text-xs text-slate-400">Verifica que toda la información sea correcta</p></div>
            </div>
            <div class="p-6 space-y-5">
                <!-- Resumen por sección -->
                <div v-for="(sec, i) in [
                    { titulo: 'Modalidad y tipo', items: [['Modalidad', modalidadActual?.nombre],['Tipo de persona', datos.tipo_persona === 'fisica' ? 'Persona Física' : 'Persona Moral'],['Tipo de garantía', datos.tipo_garantia],['Paso', i+1]] },
                    { titulo: 'Datos personales', items: [['Nombre', datos.nombre_completo],['CURP', datos.curp],['RFC', datos.rfc||'—'],['Fecha nac.', datos.fecha_nacimiento],['Sexo', datos.sexo === 'M' ? 'Masculino' : 'Femenino'],['Municipio', datos.municipio],['Teléfono', datos.telefono],['Correo', datos.correo]] },
                    { titulo: 'Negocio', items: [['Nombre comercial', datos.nombre_comercial],['Giro', datos.giro_comercial],['Municipio negocio', datos.municipio_empresa],['SAT', datos.alta_sat ? 'Sí' : 'No']] },
                    { titulo: 'Crédito solicitado', items: [['Monto', fmt(datos.monto_solicitado)],['Plazo', datos.plazo_meses ? datos.plazo_meses + ' meses' : '—']] },
                    { titulo: 'Documentos', items: [[`Subidos`, `${totalDocsSubidos} de ${totalDocsReq}`]] },
                ]" :key="i">
                    <div class="rounded-xl border border-slate-100 dark:border-zinc-800 overflow-hidden">
                        <div class="flex items-center justify-between px-4 py-2.5 bg-slate-50 dark:bg-zinc-800">
                            <span class="text-xs font-black uppercase tracking-wider text-slate-600 dark:text-zinc-300">{{ sec.titulo }}</span>
                            <button @click="paso = Math.min(i, 10)" class="text-xs text-[#6B1938] dark:text-[#f4a8c4] hover:underline font-bold">Editar</button>
                        </div>
                        <div class="px-4 py-3 grid grid-cols-2 sm:grid-cols-3 gap-3">
                            <div v-for="([label, val]) in sec.items.filter(([,v]) => v !== undefined)" :key="label">
                                <p class="text-[10px] text-slate-400 uppercase font-bold">{{ label }}</p>
                                <p class="text-sm font-semibold text-slate-800 dark:text-white truncate">{{ val || '—' }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-blue-50 dark:bg-blue-900/10 border border-blue-200 dark:border-blue-800/40 rounded-xl p-4 text-sm text-blue-700 dark:text-blue-400">
                    Al confirmar, se generarán los formatos oficiales en PDF y se empaquetarán en un ZIP para que los descargues, firmes y entregues en las oficinas del IYEM.
                </div>
            </div>
            <div class="px-6 pb-6 flex items-center justify-between">
                <button @click="retroceder" class="flex items-center gap-2 px-5 py-2.5 bg-slate-100 dark:bg-zinc-800 text-slate-700 dark:text-zinc-300 font-bold rounded-xl hover:bg-slate-200 dark:hover:bg-zinc-700 transition-all text-sm"><ChevronLeft size="16" /> Anterior</button>
                <button @click="generarFormatos" :disabled="enviando"
                    class="flex items-center gap-3 px-8 py-3.5 bg-gradient-to-r from-[#6B1938] to-[#4A0E22] hover:from-[#4A0E22] hover:to-[#2E0816] disabled:opacity-50 text-white font-bold rounded-xl shadow-lg shadow-emerald-900/20 transition-all text-sm">
                    <Loader2 v-if="enviando" size="16" class="animate-spin" />
                    <CheckCircle2 v-else size="16" />
                    {{ enviando ? 'Generando formatos...' : 'Finalizar y generar mis formatos' }}
                </button>
            </div>
        </div>

    </div>
</BeneficiarioLayout>
</template>
