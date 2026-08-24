<script setup lang="ts">
import BeneficiarioLayout from '@/layouts/BeneficiarioLayout.vue';
import { Head, router, Link } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import axios from 'axios';
import {
    ShieldCheck, ListChecks, User, Building2, Briefcase,
    Target, BarChart2, TrendingUp, Users, FileUp,
    CheckCircle2, Plus, Trash2, AlertTriangle, Download,
    MapPin, Phone, Mail, Loader2, Lock, ChevronDown, ChevronUp,
    Send, Save, XCircle, Clock
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
    tipos_documentos_post_aprobacion?: Record<string, string>;
    documentos_post_aprobacion?: Record<string, any> | null;
}>();

// ─── Estado de la solicitud ───────────────────────────────────────────────────
const estatusSolicitud = computed(() => props.solicitud?.estatus ?? null);
const puedeEditar = computed(() => !estatusSolicitud.value || ['Borrador', 'Documentacion_Incompleta'].includes(estatusSolicitud.value));
const enRevision  = computed(() => ['Enviada', 'En_Revision'].includes(estatusSolicitud.value ?? ''));
const aprobada    = computed(() => estatusSolicitud.value === 'Aprobada');
const rechazada   = computed(() => estatusSolicitud.value === 'Rechazada');

// ─── Paso 0: CURP gate ────────────────────────────────────────────────────────
const curpInput     = ref(props.solicitud?.curp ?? '');
const curpBloqueada = ref(false);
const curpMensaje   = ref('');
const curpOk        = ref(!!props.solicitud?.curp);
const verificandoCurp = ref(false);
const esRenovacion  = ref(false);
const curpValida = computed(() => /^[A-Z]{4}\d{6}[HM][A-Z]{5}[A-Z\d]\d$/.test(curpInput.value.toUpperCase()));

async function verificarCurp() {
    if (!curpValida.value) return;
    verificandoCurp.value = true; curpMensaje.value = '';
    try {
        const { data } = await axios.post(route('portal.solicitar.verificar-curp'), { curp: curpInput.value.toUpperCase() });
        curpBloqueada.value = data.bloqueado;
        curpMensaje.value   = data.mensaje ?? '';
        esRenovacion.value  = !!data.renovacion;
        if (!data.bloqueado) { curpOk.value = true; datos.value.curp = curpInput.value.toUpperCase(); }
    } catch { curpMensaje.value = 'Error al verificar. Intenta de nuevo.'; }
    finally  { verificandoCurp.value = false; }
}

// Aviso de tasa diferenciada por renovación (Emprendedores/Sustentable +1%)
const tasaRenovacionAviso = computed(() => {
    if (!esRenovacion.value || !modalidadActual.value) return null;
    const nombre = modalidadActual.value.nombre?.toLowerCase() ?? '';
    if (!nombre.includes('emprendedores') && !nombre.includes('sustentable')) return null;
    const tasa = Number(modalidadActual.value.tasa_interes || 0) + 1;
    return `Como beneficiario anterior, tu tasa de interés es ${tasa}% anual.`;
});

// ─── Secciones colapsables ────────────────────────────────────────────────────
const open = ref<Record<string, boolean>>({
    modalidad: true, personales: true, empresa: false,
    negocio: true, destino: true, ficha: true,
    ingresos: true, garantia: true, documentos: true,
});
function toggle(k: string) { open.value[k] = !open.value[k]; }

// ─── Datos del wizard ─────────────────────────────────────────────────────────
const wiz = props.solicitud?.datos_wizard ?? {};

const datos = ref({
    modalidad_id:  props.solicitud?.modalidad_id ?? '',
    tipo_persona:  props.solicitud?.tipo_persona  ?? 'fisica',
    tipo_garantia: props.solicitud?.tipo_garantia ?? 'aval',
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
    colonia:          wiz.datos_personales_ext?.colonia ?? '',
    cp:               wiz.datos_personales_ext?.cp ?? '',
    lugar_nacimiento: wiz.datos_personales_ext?.lugar_nacimiento ?? '',
    telefono_fijo:    wiz.datos_personales_ext?.telefono_fijo ?? '',
    domicilio_propio: wiz.datos_personales_ext?.domicilio_propio ?? true,
    renta_mensual:    wiz.datos_personales_ext?.renta_mensual ?? '',
    empleado_gobierno:    wiz.datos_personales_ext?.empleado_gobierno ?? false,
    dependencia_gobierno: wiz.datos_personales_ext?.dependencia_gobierno ?? '',
    puesto_gobierno:      wiz.datos_personales_ext?.puesto_gobierno ?? '',
    estado_civil:         wiz.datos_personales_ext?.estado_civil ?? '',
    regimen_matrimonial:  wiz.datos_personales_ext?.regimen_matrimonial ?? '',
    nombre_conyuge:       wiz.datos_personales_ext?.nombre_conyuge ?? '',
    curp_conyuge:         wiz.datos_personales_ext?.curp_conyuge ?? '',
    discapacidad_tipo:    wiz.datos_personales_ext?.discapacidad_tipo ?? '',
    referencia_nombre:    wiz.datos_personales_ext?.referencia_nombre ?? '',
    referencia_telefono:  wiz.datos_personales_ext?.referencia_telefono ?? '',
    referencia_cp:        wiz.datos_personales_ext?.referencia_cp ?? '',
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
    antiguedad_sat_anios: wiz.datos_negocio_ext?.antiguedad_sat_anios ?? '',
    productos:            wiz.productos ?? '',
    distribucion:         wiz.distribucion ?? '',
    giro_comercial:       props.solicitud?.giro_comercial ?? '',
    descripcion_negocio:  props.solicitud?.descripcion_negocio ?? '',
    alta_sat:             props.solicitud?.alta_sat ?? false,
    es_emprendimiento:    props.solicitud?.es_emprendimiento ?? wiz.datos_negocio_ext?.es_emprendimiento ?? false,
    monto_solicitado:  props.solicitud?.monto_solicitado ?? '',
    plazo_meses:       props.solicitud?.plazo_meses ?? '',
    destino_credito:   props.solicitud?.destino_credito ?? '',
    garantia_descripcion:  wiz.garantia_datos?.descripcion ?? '',
    garantia_valor:        wiz.garantia_datos?.valor ?? '',
    garantia_fecha_factura:wiz.garantia_datos?.fecha_factura ?? '',
    garantia_valor_factura:wiz.garantia_datos?.valor_factura ?? '',
});

// Tablas repetibles
const destinoTabla  = ref<any[]>(wiz.destino_credito_tabla?.length ? wiz.destino_credito_tabla : [{ concepto: '', importe: '' }]);
const apoyosGob     = ref<any[]>(wiz.apoyos_gobierno ?? []);
const proveedores   = ref<any[]>(wiz.proveedores?.length ? wiz.proveedores : [{ nombre: '', antiguedad: '', insumo: '', compras_mensuales: '', politica: 'Contado' }]);
const clientes      = ref<any[]>(wiz.clientes?.length ? wiz.clientes : [{ nombre: '', sector: '', ventas_mensuales: '', politica: 'Contado' }]);
const deudasNegocio = ref<any[]>(wiz.deudas_negocio ?? []);

// Ingresos/Egresos — histórico (negocio en operación)
const ieHistorico = ref<any>(wiz.ingresos_egresos ?? {
    periodo_del: '', periodo_al: '',
    ventas: '', costo_producto: '',
    gastos_electricidad: '', gastos_agua: '', gastos_telefono: '', gastos_gas: '',
    gastos_mano_obra: '', gastos_nomina: '', gastos_renta_local: '',
    otros_gastos: [], impuestos: '',
});
// Ingresos/Egresos — proyección (siempre)
const ieProyeccion = ref<any>(wiz.proyeccion_ingresos_egresos ?? {
    periodo_del: (() => { const d = new Date(); return d.toISOString().slice(0, 10); })(),
    periodo_al: (() => { const d = new Date(); d.setMonth(d.getMonth() + 6); return d.toISOString().slice(0, 10); })(),
    ventas: '', costo_producto: '',
    gastos_electricidad: '', gastos_agua: '', gastos_telefono: '', gastos_gas: '',
    gastos_mano_obra: '', gastos_nomina: '', gastos_renta_local: '',
    otros_gastos: [], impuestos: '',
});

// Fechas por defecto para histórico (último 6 meses)
watch(() => ieProyeccion.value.periodo_del, (val) => {
    if (!val) return;
    const fin = new Date(val);
    fin.setMonth(fin.getMonth() + 6);
    ieProyeccion.value.periodo_al = fin.toISOString().slice(0, 10);
});

if (!ieHistorico.value.periodo_al) {
    const hoy = new Date();
    ieHistorico.value.periodo_al = hoy.toISOString().slice(0, 10);
    const hace6 = new Date(); hace6.setMonth(hace6.getMonth() - 6);
    ieHistorico.value.periodo_del = hace6.toISOString().slice(0, 10);
}

// Aval
const aval = ref({
    nombre_completo:         props.solicitud?.aval?.nombre_completo ?? '',
    parentesco:              props.solicitud?.aval?.parentesco ?? '',
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
const avBienesMuebles = ref<any[]>(props.solicitud?.aval?.bienes_muebles ?? []);
const avHipotecas = ref<any[]>(props.solicitud?.aval?.hipotecas_creditos ?? []);
const avOtrasDeudas = ref<any[]>(props.solicitud?.aval?.otras_deudas ?? []);
const avIngresos  = ref<any[]>(props.solicitud?.aval?.ingresos ?? [{ fuente: '', monto: '' }]);
const avEgresos   = ref<any[]>(props.solicitud?.aval?.egresos ?? []);
const avReferencias = ref<any[]>(props.solicitud?.aval?.referencias_personales ?? [{ nombre: '', telefono: '', cp: '' }]);

// Documentos
const docSubidos  = ref<Record<string, any>>(props.solicitud?.documentos ?? {});
const docSubiendo = ref<Record<string, boolean>>({});
const docArchivos = ref<Record<string, File | null>>({});

// ─── Computed ─────────────────────────────────────────────────────────────────
const solicitudId = ref<number | null>(props.solicitud?.id ?? null);
const enviando    = ref(false);
const guardando   = ref(false);
const errorMsg    = ref('');
const successMsg  = ref('');
const camposError = ref<Record<string, string>>({});

function errClass(campo: string) {
    return camposError.value[campo] ? 'border-red-500 focus:ring-red-200 focus:border-red-500' : '';
}

const fechaMax18 = computed(() => {
    const d = new Date();
    d.setFullYear(d.getFullYear() - 18);
    return d.toISOString().split('T')[0];
});
const hoy = new Date().toISOString().split('T')[0];

const rfcCurpWarning = computed(() => {
    const rfc = datos.value.rfc ?? '';
    const curp = curpInput.value ?? '';
    if (rfc.length >= 4 && curp.length >= 4) {
        return rfc.slice(0, 4).toUpperCase() !== curp.slice(0, 4).toUpperCase();
    }
    return false;
});

const modalidadActual = computed(() => props.modalidades.find(m => m.id == datos.value.modalidad_id));
const isArtesanal     = computed(() => modalidadActual.value?.nombre?.toLowerCase().includes('artesanal'));
const isSustentable   = computed(() => modalidadActual.value?.nombre?.toLowerCase().includes('sustentable'));
const plazosDisponibles = computed(() => isArtesanal.value ? [6, 12, 18] : [12, 18, 24]);

// Importe total del proyecto (auto-calculado)
const importeTotal = computed(() => destinoTabla.value.reduce((s: number, r: any) => s + (+(r.importe || 0)), 0));
watch(importeTotal, (v) => { datos.value.importe_total_proyecto = v; });

// Cálculos ingresos/egresos
function calcIE(ie: any) {
    const ventas = +(ie.ventas || 0);
    const costo  = +(ie.costo_producto || 0);
    const utilBruta = ventas - costo;
    const gastosFijos = ['gastos_electricidad','gastos_agua','gastos_telefono','gastos_gas','gastos_mano_obra','gastos_nomina','gastos_renta_local']
        .reduce((s, k) => s + (+(ie[k] || 0)), 0);
    const otrosGastosTotal = (ie.otros_gastos ?? []).reduce((s: number, g: any) => s + (+(g.importe || 0)), 0);
    const totalGastos  = gastosFijos + otrosGastosTotal;
    const utilAnteImp  = utilBruta - totalGastos;
    const utilNeta     = utilAnteImp - (+(ie.impuestos || 0));
    return { utilBruta, totalGastos, utilAnteImp, utilNeta };
}

// Tipos de documentos requeridos (debe reflejar DocumentoSolicitud::tiposRequeridos())
const tiposDocRequeridos = computed((): Record<string, string> => {
    const base: Record<string, string> = {
        ine_frente:            'INE / Credencial (Frente)',
        ine_reverso:           'INE / Credencial (Reverso)',
        curp:                  'Documento CURP oficial',
        comprobante_domicilio: 'Comprobante de Domicilio',
        foto_negocio:          'Fotografía del Negocio o Proyecto',
        acta_nacimiento:       'Acta de nacimiento',
        propiedad_negocio:     'Documento de propiedad/posesión del negocio',
        carta_no_servidor:     'Carta de no ser servidor público',
    };

    if (datos.value.estado_civil === 'Casado(a)') base.acta_matrimonio = 'Acta de matrimonio';

    const mod = modalidadActual.value;
    const nombreMod = mod?.nombre?.toLowerCase() ?? '';
    const esEmprendedores = nombreMod.includes('emprendedores');
    const esSustentable   = nombreMod.includes('sustentable');

    if (mod) {
        if (isArtesanal.value) {
            base.constancia_artesano = 'Constancia de Artesano';
            base.cotizaciones_proveedor = '2 cotizaciones de proveedores';
        }
        if (esEmprendedores || esSustentable) {
            base.cotizaciones_proveedor = '3 cotizaciones de proveedores';
            base.buro_credito = 'Reporte buró de crédito vigente (≤180 días)';
        }
        if (esEmprendedores) base.constancia_situacion = 'Constancia de Situación Fiscal';
        if (esSustentable) {
            base.opinion_cumplimiento = 'Opinión de Cumplimiento SAT';
            base.plan_trabajo_sostenible = 'Plan de trabajo sostenible con impacto ambiental';
            if (Number(datos.value.monto_solicitado || 0) >= 200000) {
                base.escritura_hipotecaria = 'Escritura de garantía hipotecaria';
            }
        }
    }

    if (datos.value.tipo_persona === 'moral') {
        base.acta_constitutiva = 'Acta Constitutiva';
        base.poder_rep_legal = 'Poder del Representante Legal';
        base.id_rep_legal = 'Identificación del Representante Legal';
        if (esEmprendedores || esSustentable) base.balance_general = 'Balance general + estado de resultados';
    }

    if (datos.value.tipo_garantia === 'aval') {
        base.id_aval = 'Identificación del Aval';
        base.comprobante_domicilio_aval = 'Comprobante de Domicilio del Aval';
        base.acta_nacimiento_aval = 'Acta de nacimiento del aval';
        if (aval.value.estado_civil === 'Casado(a)') base.acta_matrimonio_aval = 'Acta de matrimonio del aval';
    }
    if (datos.value.tipo_garantia === 'prendaria')   base.factura_bien_mueble = 'Factura del Bien Mueble en Garantía';
    if (datos.value.tipo_garantia === 'hipotecaria') base.doc_propiedad_inmueble = 'Documento de Propiedad del Inmueble';

    return base;
});

const totalDocsReq     = computed(() => Object.keys(tiposDocRequeridos.value).length);
const totalDocsSubidos = computed(() => Object.keys(tiposDocRequeridos.value).filter(t => docSubidos.value[t]).length);
const todosDocsCompletos = computed(() => totalDocsSubidos.value >= totalDocsReq.value);

const puedeEnviar = computed(() =>
    !!datos.value.nombre_completo && !!datos.value.modalidad_id &&
    !!datos.value.monto_solicitado && !!datos.value.plazo_meses &&
    todosDocsCompletos.value
);

// ─── Helpers ──────────────────────────────────────────────────────────────────
const addRow = (arr: any[], tpl: any) => arr.push({ ...tpl });
const removeRow = (arr: any[], i: number) => arr.splice(i, 1);

const fmt = (v: any) => new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(+(v || 0));

function onMoneyInput(e: Event, obj: any, key: string) {
    const raw = (e.target as HTMLInputElement).value.replace(/[^0-9.]/g, '');
    obj[key] = raw;
    (e.target as HTMLInputElement).value = raw ? fmt(raw) : '';
}

// ─── Guardar paso individual ──────────────────────────────────────────────────
async function postPaso(paso: number, extra: Record<string, any> = {}, modo: 'borrador' | 'envio' = 'borrador') {
    const { data } = await axios.post(route('portal.solicitar.paso'), { paso, modo, datos: { ...datos.value, ...extra } });
    if (data.solicitud_id) solicitudId.value = data.solicitud_id;
}

// ─── Guardar borrador (todos los pasos) ───────────────────────────────────────
// modo 'borrador' (por defecto): permite guardar con campos obligatorios vacíos.
// modo 'envio': validación estricta, usado como paso previo a generar los formatos.
async function guardarBorrador(modo: 'borrador' | 'envio' = 'borrador') {
    guardando.value = true; errorMsg.value = ''; successMsg.value = ''; camposError.value = {};
    try {
        await postPaso(1, {}, modo);
        await postPaso(2, {}, modo);
        if (datos.value.tipo_persona === 'moral') await postPaso(3, {}, modo);
        await postPaso(4, {}, modo);
        await postPaso(5, { destino_credito_tabla: destinoTabla.value, apoyos_gobierno: apoyosGob.value, importe_total_proyecto: importeTotal.value }, modo);
        await postPaso(6, { proveedores: proveedores.value, clientes: clientes.value, deudas_negocio: deudasNegocio.value, productos: datos.value.productos, distribucion: datos.value.distribucion }, modo);
        await postPaso(7, { historico: datos.value.es_emprendimiento ? null : ieHistorico.value, proyeccion: ieProyeccion.value }, modo);
        if (datos.value.tipo_garantia === 'aval') {
            await postPaso(8, {
                ...aval.value,
                bienes_inmuebles: avBienes.value,
                bienes_muebles: avBienesMuebles.value,
                hipotecas_creditos: avHipotecas.value,
                otras_deudas: avOtrasDeudas.value,
                ingresos: avIngresos.value,
                egresos: avEgresos.value,
                referencias_personales: avReferencias.value,
            }, modo);
        } else {
            await postPaso(8, { descripcion: datos.value.garantia_descripcion, valor: datos.value.garantia_valor, fecha_factura: datos.value.garantia_fecha_factura, valor_factura: datos.value.garantia_valor_factura }, modo);
        }
        successMsg.value = 'Borrador guardado correctamente.';
        setTimeout(() => { successMsg.value = ''; }, 4000);
    } catch (e: any) {
        const errs = e.response?.data?.errors;
        errorMsg.value = errs ? Object.values(errs).flat().join(' ') : (e.response?.data?.message ?? 'Error al guardar.');
        if (errs) {
            const mapeado: Record<string, string> = {};
            for (const [campo, mensajes] of Object.entries(errs)) {
                mapeado[campo.replace(/^datos\./, '')] = (mensajes as string[])[0];
            }
            camposError.value = mapeado;
            requestAnimationFrame(() => {
                document.querySelector('[data-campo-error]')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            });
        }
    } finally { guardando.value = false; }
}

// ─── Enviar solicitud ─────────────────────────────────────────────────────────
async function enviarSolicitud() {
    enviando.value = true; errorMsg.value = '';
    try {
        await guardarBorrador('envio');
        if (errorMsg.value) { enviando.value = false; return; }
        await axios.post(route('portal.solicitar.generar-formatos'));
        router.reload({ only: ['solicitud'] });
    } catch (e: any) {
        const d = e.response?.data;
        if (d?.faltantes?.length) errorMsg.value = 'Faltan documentos: ' + d.faltantes.join(', ');
        else errorMsg.value = d?.error ?? 'Error al enviar.';
    } finally { enviando.value = false; }
}

// ─── Documentos ───────────────────────────────────────────────────────────────
const DOC_MAX_BYTES = 10 * 1024 * 1024; // 10 MB, igual al límite del servidor
const docErrores = ref<Record<string, string>>({});

function seleccionarDoc(tipo: string, e: Event) {
    const input = e.target as HTMLInputElement;
    const f = input.files?.[0];
    if (!f) return;
    docErrores.value[tipo] = '';
    const extension = f.name.split('.').pop()?.toLowerCase() ?? '';
    if (!['pdf', 'jpg', 'jpeg', 'png'].includes(extension)) {
        docErrores.value[tipo] = 'Formato no permitido. Usa PDF, JPG o PNG.';
        input.value = '';
        return;
    }
    if (f.size > DOC_MAX_BYTES) {
        docErrores.value[tipo] = `El archivo pesa ${(f.size / 1024 / 1024).toFixed(1)} MB. El máximo permitido es 10 MB.`;
        input.value = '';
        return;
    }
    docArchivos.value[tipo] = f;
}
async function subirDoc(tipo: string) {
    const archivo = docArchivos.value[tipo];
    if (!archivo) return;
    docSubiendo.value[tipo] = true;
    docErrores.value[tipo] = '';
    const fd = new FormData();
    fd.append('tipo_documento', tipo); fd.append('archivo', archivo);
    try {
        const { data } = await axios.post(route('portal.solicitar.documento'), fd);
        if (data.ok) { docSubidos.value[tipo] = data; docArchivos.value[tipo] = null; }
    } catch (e: any) {
        const msg = e.response?.data?.message ?? 'No se pudo subir el archivo. Intenta de nuevo.';
        docErrores.value[tipo] = msg;
        errorMsg.value = msg;
    }
    finally { docSubiendo.value[tipo] = false; }
}

function descargarFormatos() {
    if (solicitudId.value) window.location.href = route('portal.solicitar.descargar-formatos', solicitudId.value);
}

// ─── Documentos post-aprobación ─────────────────────────────────────────────
const docsPostSubidos  = ref<Record<string, any>>(props.documentos_post_aprobacion ?? {});
const docsPostArchivos = ref<Record<string, File | null>>({});
const docsPostSubiendo = ref<Record<string, boolean>>({});
const mapsUrlInput     = ref(props.documentos_post_aprobacion?.google_maps_negocio?.nombre_original ?? '');

function seleccionarDocPost(tipo: string, e: Event) {
    const f = (e.target as HTMLInputElement).files?.[0];
    if (f) docsPostArchivos.value[tipo] = f;
}
async function subirDocPost(tipo: string) {
    docsPostSubiendo.value[tipo] = true;
    try {
        let data;
        if (tipo === 'google_maps_negocio') {
            ({ data } = await axios.post(route('portal.solicitar.documento-post-aprobacion'), { tipo_documento: tipo, url: mapsUrlInput.value }));
        } else {
            const archivo = docsPostArchivos.value[tipo];
            if (!archivo) return;
            const fd = new FormData();
            fd.append('tipo_documento', tipo); fd.append('archivo', archivo);
            ({ data } = await axios.post(route('portal.solicitar.documento-post-aprobacion'), fd));
        }
        if (data.ok) { docsPostSubidos.value[tipo] = data; docsPostArchivos.value[tipo] = null; }
    } catch (e: any) { errorMsg.value = e.response?.data?.message ?? 'Error al subir.'; }
    finally { docsPostSubiendo.value[tipo] = false; }
}

// ─── Catálogos ────────────────────────────────────────────────────────────────
const municipios = [
    'Abalá','Acanceh','Akil','Baca','Bokobá','Buctzotz','Cacalchén','Calotmul',
    'Cansahcab','Cantamayec','Celestún','Cenotillo','Chacsinkín','Chankom',
    'Chapab','Chemax','Chichimilá','Chicxulub Pueblo','Chikindzonot','Chocholá',
    'Chumayel','Conkal','Cuncunul','Cuzamá','Dzán','Dzemul','Dzidzantún',
    'Dzilam de Bravo','Dzilam González','Dzitás','Dzoncauich','Espita',
    'Halachó','Hocabá','Hoctún','Homún','Huhí','Hunucmá','Ixil','Izamal',
    'Kanasín','Kantunil','Kaua','Kinchil','Kopomá','Mama','Maní','Maxcanú',
    'Mayapán','Mérida','Mocochá','Motul','Muna','Muxupip','Opichén','Oxkutzcab',
    'Panabá','Peto','Progreso','Quintana Roo','Río Lagartos','Sacalum','Samahil',
    'San Felipe','Sanahcat','Santa Elena','Seyé','Sinanché','Sotuta','Sucilá',
    'Sudzal','Suma','Tahdziú','Tahmek','Teabo','Tecoh','Tekal de Venegas',
    'Tekantó','Tekax','Tekit','Tekom','Telchac Pueblo','Telchac Puerto','Temax',
    'Temozón','Tepakán','Tetiz','Teya','Ticul','Timucuy','Tinum','Tixcacalcupul',
    'Tixkokob','Tixmehuac','Tixpéhual','Tizimín','Tunkás','Tzucacab','Uayma',
    'Ucú','Umán','Valladolid','Xocchel','Yaxcabá','Yaxkukul','Yobaín',
];

const regimenesFiscales = [
    '601 - General de Ley Personas Morales',
    '603 - Personas Morales con Fines no Lucrativos',
    '605 - Sueldos y Salarios e Ingresos Asimilados',
    '606 - Arrendamiento',
    '607 - Enajenación o Adquisición de Bienes',
    '608 - Demás Ingresos',
    '610 - Residentes en el Extranjero sin Estab. Permanente',
    '611 - Ingresos por Dividendos',
    '612 - Personas Físicas con Actividades Empresariales y Profesionales',
    '614 - Ingresos por Intereses',
    '616 - Sin Obligaciones Fiscales',
    '620 - Sociedades Cooperativas de Producción',
    '621 - Incorporación Fiscal',
    '622 - Actividades Agrícolas, Ganaderas, Silvícolas y Pesqueras',
    '623 - Opcional para Grupos de Sociedades',
    '624 - Coordinados',
    '625 - Actividades Empresariales vía Plataformas Tecnológicas',
    '626 - Régimen Simplificado de Confianza (RESICO)',
    '628 - Hidrocarburos',
    '629 - Regímenes Fiscales Preferentes y Multinacionales',
    '630 - Enajenación de acciones en bolsa de valores',
    'Público en General',
    'Sin registro / No aplica',
];

const estadosCiviles = ['Soltero(a)','Casado(a)','Unión libre','Divorciado(a)','Viudo(a)'];
const regMatrimonial = ['Sociedad conyugal','Separación de bienes'];

const parentescosProhibidosArtesanal = ['padre','madre','hijo','hija','hermano','hermana','abuelo','abuela','nieto','nieta','tío','tía','sobrino','sobrina'];
const parentescosAval = ['Cónyuge','Amigo(a)','Socio(a) de negocio','Vecino(a)', ...parentescosProhibidosArtesanal.map(p => p[0].toUpperCase() + p.slice(1)), 'Otro'];

// Clases reutilizables
const inp  = 'w-full px-3 py-2.5 rounded-xl border border-slate-200 dark:border-zinc-700 dark:bg-zinc-800/50 focus:ring-2 focus:ring-[#6B1938]/20 focus:border-[#6B1938] transition-all text-slate-900 dark:text-white placeholder:text-slate-400 text-sm bg-white min-h-[44px]';
const lbl  = 'block text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-zinc-400 mb-1';
const card = 'bg-white dark:bg-zinc-900 rounded-2xl border border-slate-100 dark:border-zinc-800 shadow-sm overflow-hidden';
const sHead= 'flex items-center gap-3 px-5 py-4 cursor-pointer select-none border-b border-slate-100 dark:border-zinc-800';
const sIcon= 'w-9 h-9 rounded-xl flex items-center justify-center shrink-0 bg-[#6B1938]/10 dark:bg-[#6B1938]/20 text-[#6B1938] dark:text-[#f4a8c4]';
</script>

<template>
<BeneficiarioLayout>
<Head title="Solicitar Crédito CREA" />

<!-- ═══════════════ VISTA: EN REVISIÓN ═══════════════ -->
<div v-if="enRevision" class="max-w-2xl mx-auto">
    <div class="bg-white dark:bg-zinc-900 rounded-3xl border border-blue-200 dark:border-blue-800/50 p-10 text-center space-y-5 shadow-xl">
        <div class="w-20 h-20 rounded-3xl bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center mx-auto">
            <Clock size="40" class="text-blue-600 dark:text-blue-400" />
        </div>
        <h1 class="text-2xl font-black text-slate-900 dark:text-white">¡Solicitud enviada!</h1>
        <p v-if="solicitudId" class="inline-block px-3 py-1 rounded-full bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400 text-xs font-bold font-mono">
            Folio: SOL-{{ String(solicitudId).padStart(6, '0') }}
        </p>
        <p class="text-slate-500 dark:text-zinc-400 leading-relaxed max-w-md mx-auto">
            El equipo de CREA está revisando tu información y documentos. Te notificaremos cuando haya una actualización.
            Puedes consultar el estado de tu solicitud en cualquier momento desde este portal.
        </p>

        <button v-if="props.solicitud?.formatos_zip_ruta" @click="descargarFormatos"
            class="inline-flex items-center gap-3 px-8 py-4 bg-gradient-to-r from-[#6B1938] to-[#4A0E22] hover:from-[#4A0E22] hover:to-[#2E0816] text-white font-bold rounded-2xl shadow-lg transition-all text-base">
            <Download size="20" /> Descargar mis formatos PDF (ZIP)
        </button>

        <div class="rounded-xl bg-slate-50 dark:bg-zinc-800 p-4 text-left text-sm text-slate-600 dark:text-zinc-400 space-y-1 max-w-md mx-auto">
            <p class="font-bold text-slate-800 dark:text-white">Imprime y lleva estos formatos firmados a las oficinas IYEM:</p>
            <p class="flex items-start gap-2"><MapPin size="14" class="shrink-0 mt-0.5" /> Av. Principal Industrias No Contaminantes, Tablaje 13613, Sodzil Norte, CP 97110, Mérida</p>
            <p class="flex items-center gap-2"><Mail size="14" class="shrink-0" /> crea@iyemyucatan.com</p>
            <p class="flex items-center gap-2"><Phone size="14" class="shrink-0" /> WhatsApp 999 234 2693 · (999) 469.53.06 Ext. 29263/29264</p>
        </div>

        <Link :href="route('portal.dashboard')"
            class="inline-flex items-center gap-2 px-6 py-3 bg-slate-100 dark:bg-zinc-800 text-slate-700 dark:text-zinc-300 font-bold rounded-xl hover:bg-slate-200 dark:hover:bg-zinc-700 transition-all text-sm">
            Ir al inicio
        </Link>
    </div>
</div>

<!-- ═══════════════ VISTA: APROBADA ═══════════════ -->
<div v-else-if="aprobada" class="max-w-2xl mx-auto space-y-6">
    <div class="bg-white dark:bg-zinc-900 rounded-3xl border border-emerald-200 dark:border-emerald-800/50 p-10 text-center space-y-6 shadow-xl">
        <div class="w-20 h-20 rounded-3xl bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center mx-auto">
            <CheckCircle2 size="40" class="text-[#6B1938] dark:text-[#f4a8c4]" />
        </div>
        <div class="space-y-2">
            <h1 class="text-2xl font-black text-slate-900 dark:text-white">¡Felicidades! Tu solicitud fue aprobada y va a pasar a Comité.</h1>
            <p class="text-slate-500 dark:text-zinc-400 leading-relaxed max-w-md mx-auto">
                Descarga tus formatos, fírmalos y entrégalos en las oficinas del IYEM para continuar con el proceso.
            </p>
        </div>
        <button @click="descargarFormatos"
            class="inline-flex items-center gap-3 px-8 py-4 bg-gradient-to-r from-[#6B1938] to-[#4A0E22] hover:from-[#4A0E22] hover:to-[#2E0816] text-white font-bold rounded-2xl shadow-lg transition-all text-base">
            <Download size="20" /> Descargar formatos (ZIP)
        </button>
    </div>
    <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-slate-100 dark:border-zinc-800 p-6 space-y-4">
        <h2 class="font-black text-slate-900 dark:text-white">¿Dónde entregar mis formatos?</h2>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm text-slate-600 dark:text-zinc-400">
            <div class="flex items-start gap-2"><MapPin size="16" class="text-[#6B1938] shrink-0 mt-0.5" />
                <span>Av. Industrias No Contaminantes Tablaje 13613, Col. Sodzil Norte, Mérida, Yucatán</span>
            </div>
            <div class="flex items-center gap-2"><Phone size="16" class="text-[#6B1938] shrink-0" /><span>999 941 2170</span></div>
            <div class="flex items-center gap-2"><Mail size="16" class="text-[#6B1938] shrink-0" /><span>crea@iyemyucatan.com</span></div>
        </div>
        <div class="rounded-xl overflow-hidden border border-slate-200 dark:border-zinc-700 h-48">
            <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3724.2!2d-89.6435!3d21.0217!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zMjHCsDAxJzE3LjgiTiA4OcKwMzgnMzYuNiJX!5e0!3m2!1ses!2smx!4v1620000000"
                width="100%" height="100%" style="border:0" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
        </div>
        <p class="text-xs text-slate-400">Horario de atención: Lunes a Viernes, 8:00 a 15:00 hrs.</p>
    </div>

    <!-- Próximos pasos: documentos post-aprobación -->
    <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-slate-100 dark:border-zinc-800 p-6 space-y-5">
        <div>
            <h2 class="font-black text-slate-900 dark:text-white">Próximos pasos para recibir tu crédito</h2>
            <p class="text-sm text-slate-500 dark:text-zinc-400 mt-1">
                Sube estos documentos desde aquí y acude a firmar tu pagaré, carta compromiso, contrato e instructivo de pago en las oficinas del IYEM.
            </p>
        </div>
        <div class="space-y-3">
            <div v-for="(label, tipo) in (tipos_documentos_post_aprobacion ?? {})" :key="tipo"
                class="flex flex-col sm:flex-row sm:items-center gap-3 p-4 rounded-xl border border-slate-100 dark:border-zinc-800">
                <div class="flex items-center gap-3 flex-1 min-w-0">
                    <CheckCircle2 v-if="docsPostSubidos[tipo]" size="20" class="text-emerald-500 shrink-0" />
                    <FileUp v-else size="20" class="text-slate-300 shrink-0" />
                    <div class="min-w-0">
                        <p class="text-sm font-bold text-slate-800 dark:text-white truncate">{{ label }}</p>
                        <p v-if="docsPostSubidos[tipo]" class="text-xs text-emerald-600 truncate">{{ docsPostSubidos[tipo].nombre_original }}</p>
                    </div>
                </div>

                <template v-if="tipo === 'google_maps_negocio'">
                    <input v-model="mapsUrlInput" type="url" placeholder="https://maps.google.com/..." :class="[inp, 'sm:w-64']" />
                    <button @click="subirDocPost(tipo)" :disabled="docsPostSubiendo[tipo] || !mapsUrlInput"
                        class="px-4 py-2 rounded-xl bg-[#6B1938] text-white text-xs font-bold disabled:opacity-40 shrink-0">
                        {{ docsPostSubiendo[tipo] ? 'Guardando...' : 'Guardar enlace' }}
                    </button>
                </template>
                <template v-else>
                    <input type="file" accept=".pdf,.jpg,.jpeg,.png" @change="(e) => seleccionarDocPost(tipo, e)" class="text-xs sm:w-56" />
                    <button @click="subirDocPost(tipo)" :disabled="docsPostSubiendo[tipo] || !docsPostArchivos[tipo]"
                        class="px-4 py-2 rounded-xl bg-[#6B1938] text-white text-xs font-bold disabled:opacity-40 shrink-0">
                        {{ docsPostSubiendo[tipo] ? 'Subiendo...' : 'Subir' }}
                    </button>
                </template>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════ VISTA: RECHAZADA ═══════════════ -->
<div v-else-if="rechazada" class="max-w-2xl mx-auto">
    <div class="bg-white dark:bg-zinc-900 rounded-3xl border border-red-200 dark:border-red-800/50 p-10 text-center space-y-5 shadow-xl">
        <div class="w-20 h-20 rounded-3xl bg-red-100 dark:bg-red-900/30 flex items-center justify-center mx-auto">
            <XCircle size="40" class="text-red-600 dark:text-red-400" />
        </div>
        <h1 class="text-2xl font-black text-slate-900 dark:text-white">Tu solicitud no fue aprobada</h1>
        <p v-if="props.solicitud?.observaciones" class="text-slate-600 dark:text-zinc-400 bg-slate-50 dark:bg-zinc-800 rounded-xl p-4 text-sm text-left">
            {{ props.solicitud.observaciones }}
        </p>
        <p class="text-slate-500 dark:text-zinc-400 text-sm">
            Puedes comunicarte con el equipo CREA al <strong>999 941 2170</strong> para más información.
        </p>
    </div>
</div>

<!-- ═══════════════ CURP GATE ═══════════════ -->
<div v-else-if="!curpOk" class="max-w-md mx-auto">
    <div :class="card">
        <div :class="sHead" style="cursor:default">
            <div :class="sIcon"><ShieldCheck size="18" /></div>
            <div>
                <h2 class="font-black text-slate-900 dark:text-white">Verificación de identidad</h2>
                <p class="text-xs text-slate-400">Introduce tu CURP para iniciar la solicitud</p>
            </div>
        </div>
        <div class="p-6 space-y-5">
            <div>
                <label :class="lbl">CURP (18 caracteres) *</label>
                <input :value="curpInput" @input="e => curpInput = (e.target as HTMLInputElement).value.toUpperCase().replace(/[^A-Z0-9]/g,'').slice(0,18)" type="text" maxlength="18" placeholder="AAAA000000AAAAAA00"
                    :class="[inp, 'uppercase font-mono tracking-widest text-base']"
                    @keyup.enter="verificarCurp" />
                <p v-if="curpInput && !curpValida" class="text-xs text-red-500 mt-1">Formato de CURP inválido</p>
            </div>
            <div v-if="curpBloqueada" class="rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800/40 p-4 flex items-start gap-2">
                <Lock size="16" class="text-red-600 mt-0.5 shrink-0" />
                <p class="text-sm text-red-700 dark:text-red-400">{{ curpMensaje }}</p>
            </div>
            <button @click="verificarCurp" :disabled="!curpValida || verificandoCurp"
                class="flex items-center gap-2 px-6 py-3 bg-[#6B1938] hover:bg-[#4A0E22] disabled:opacity-50 text-white font-bold rounded-xl transition-all">
                <Loader2 v-if="verificandoCurp" size="16" class="animate-spin" />
                <ShieldCheck v-else size="16" />
                {{ verificandoCurp ? 'Verificando...' : 'Verificar CURP' }}
            </button>
        </div>
    </div>
</div>

<!-- ═══════════════ FORMULARIO PRINCIPAL (Borrador / Doc. Incompleta) ═══════════════ -->
<div v-else class="max-w-4xl mx-auto space-y-4 pb-28">

    <!-- Encabezado -->
    <div>
        <p class="text-xs font-bold uppercase tracking-widest text-[#6B1938] dark:text-[#f4a8c4]">Solicitud de Crédito CREA</p>
        <h1 class="text-2xl font-black text-slate-900 dark:text-white mt-0.5">Formulario de Solicitud</h1>
        <p class="text-sm text-slate-400 mt-1">Completa todas las secciones y adjunta tus documentos. Puedes guardar un borrador en cualquier momento.</p>
    </div>

    <!-- Aviso doc. incompleta -->
    <div v-if="estatusSolicitud === 'Documentacion_Incompleta'" class="rounded-xl bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800/40 p-4 flex items-start gap-3">
        <AlertTriangle size="18" class="text-amber-600 shrink-0 mt-0.5" />
        <div>
            <p class="text-sm font-bold text-amber-800 dark:text-amber-400">Documentación incompleta</p>
            <p class="text-sm text-amber-700 dark:text-amber-500 mt-0.5">{{ props.solicitud?.observaciones }}</p>
        </div>
    </div>

    <!-- Aviso renovación: tasa diferenciada -->
    <div v-if="esRenovacion" class="rounded-xl bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800/40 p-4 flex items-start gap-3">
        <AlertTriangle size="18" class="text-blue-600 shrink-0 mt-0.5" />
        <div>
            <p class="text-sm font-bold text-blue-800 dark:text-blue-400">Beneficiario anterior detectado</p>
            <p class="text-sm text-blue-700 dark:text-blue-500 mt-0.5">
                {{ tasaRenovacionAviso ?? 'Ya tuviste un crédito CREA liquidado. En Emprendedores y Sustentable aplica una tasa diferenciada (+1%); en Artesanal no hay cambio.' }}
            </p>
        </div>
    </div>

    <!-- Error / Éxito globales -->
    <div v-if="errorMsg" class="rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800/40 p-4 flex items-start gap-3">
        <AlertTriangle size="18" class="text-red-600 shrink-0 mt-0.5" />
        <p class="text-sm text-red-700 dark:text-red-400">{{ errorMsg }}</p>
    </div>
    <div v-if="successMsg" class="rounded-xl bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800/40 p-4 flex items-start gap-3">
        <CheckCircle2 size="18" class="text-emerald-600 shrink-0 mt-0.5" />
        <p class="text-sm text-emerald-700 dark:text-emerald-400">{{ successMsg }}</p>
    </div>

    <!-- ══════════ SECCIÓN 1: TIPO DE CRÉDITO ══════════ -->
    <div :class="card">
        <div :class="sHead" @click="toggle('modalidad')">
            <div :class="sIcon"><ListChecks size="18" /></div>
            <div class="flex-1"><h2 class="font-black text-slate-900 dark:text-white text-sm">Tipo de crédito</h2><p class="text-xs text-slate-400">Modalidad, tipo de persona y garantía</p></div>
            <component :is="open.modalidad ? ChevronUp : ChevronDown" size="18" class="text-slate-400 shrink-0" />
        </div>
        <div v-if="open.modalidad" class="p-5 space-y-5">
            <div>
                <label :class="lbl">Modalidad *</label>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mt-1">
                    <button v-for="m in modalidades" :key="m.id" type="button"
                        @click="datos.modalidad_id = m.id; if(m.nombre?.toLowerCase().includes('artesanal')) datos.tipo_persona = 'fisica'"
                        :class="['rounded-xl border-2 p-4 text-left transition-all',
                            datos.modalidad_id == m.id ? 'border-[#6B1938] bg-[#6B1938]/5 dark:bg-[#6B1938]/10' : 'border-slate-200 dark:border-zinc-700 hover:border-[#6B1938]/60']">
                        <div class="font-black text-sm text-slate-900 dark:text-white">CREA {{ m.nombre }}</div>
                        <div class="text-xs text-slate-500 mt-1">{{ new Intl.NumberFormat('es-MX',{style:'currency',currency:'MXN',maximumFractionDigits:0}).format(m.monto_minimo) }} – {{ new Intl.NumberFormat('es-MX',{style:'currency',currency:'MXN',maximumFractionDigits:0}).format(m.monto_maximo) }}</div>
                        <div class="text-[10px] text-[#6B1938] dark:text-[#f4a8c4] font-bold mt-1">{{ m.tasa_interes }}% anual</div>
                    </button>
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label :class="lbl">Tipo de persona *</label>
                    <div class="flex gap-3 mt-1">
                        <button type="button" @click="datos.tipo_persona = 'fisica'" :disabled="isArtesanal"
                            :class="['flex-1 rounded-xl border-2 py-3 px-4 font-bold text-sm transition-all min-h-[44px]', datos.tipo_persona === 'fisica' ? 'border-[#6B1938] bg-[#6B1938]/5 dark:bg-[#6B1938]/10 text-[#6B1938] dark:text-[#f4a8c4]' : 'border-slate-200 dark:border-zinc-700 text-slate-600 dark:text-zinc-400 hover:border-[#6B1938]/60 disabled:opacity-40']">Física</button>
                        <button type="button" @click="datos.tipo_persona = 'moral'" :disabled="isArtesanal"
                            :class="['flex-1 rounded-xl border-2 py-3 px-4 font-bold text-sm transition-all min-h-[44px]', datos.tipo_persona === 'moral' ? 'border-[#6B1938] bg-[#6B1938]/5 dark:bg-[#6B1938]/10 text-[#6B1938] dark:text-[#f4a8c4]' : 'border-slate-200 dark:border-zinc-700 text-slate-600 dark:text-zinc-400 hover:border-[#6B1938]/60 disabled:opacity-40']">Moral</button>
                    </div>
                </div>
                <div>
                    <label :class="lbl">Tipo de garantía *</label>
                    <div class="grid grid-cols-3 gap-2 mt-1">
                        <button v-for="g in ['aval','prendaria','hipotecaria']" :key="g" type="button"
                            :disabled="g === 'hipotecaria' && !modalidadActual?.nombre?.toLowerCase().includes('sustentable')"
                            @click="datos.tipo_garantia = g"
                            :class="['rounded-xl border-2 py-2.5 px-2 font-bold text-xs transition-all capitalize min-h-[44px]', datos.tipo_garantia === g ? 'border-[#6B1938] bg-[#6B1938]/5 dark:bg-[#6B1938]/10 text-[#6B1938] dark:text-[#f4a8c4]' : 'border-slate-200 dark:border-zinc-700 text-slate-600 dark:text-zinc-400 hover:border-[#6B1938]/60 disabled:opacity-40']">
                            {{ g === 'aval' ? 'Aval' : g === 'prendaria' ? 'Prendaria' : 'Hipotecaria' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ══════════ SECCIÓN 2: DATOS PERSONALES ══════════ -->
    <div :class="card">
        <div :class="sHead" @click="toggle('personales')">
            <div :class="sIcon"><User size="18" /></div>
            <div class="flex-1"><h2 class="font-black text-slate-900 dark:text-white text-sm">Datos personales del solicitante</h2></div>
            <component :is="open.personales ? ChevronUp : ChevronDown" size="18" class="text-slate-400 shrink-0" />
        </div>
        <div v-if="open.personales" class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="sm:col-span-2" :data-campo-error="camposError.nombre_completo || undefined"><label :class="lbl">Nombre completo *</label><input v-model="datos.nombre_completo" type="text" maxlength="150" placeholder="Como aparece en tu INE" :class="[inp, errClass('nombre_completo')]" /><p v-if="camposError.nombre_completo" class="text-xs text-red-500 mt-1">{{ camposError.nombre_completo }}</p></div>
            <div><label :class="lbl">CURP *</label><input :value="curpInput.toUpperCase()" readonly :class="[inp,'font-mono tracking-widest bg-slate-50 dark:bg-zinc-800']" /></div>
            <div><label :class="lbl">RFC (con homoclave)</label><input :value="datos.rfc" @input="e => datos.rfc = (e.target as HTMLInputElement).value.toUpperCase().replace(/[^A-Z0-9]/g,'').slice(0,13)" type="text" maxlength="13" :class="[inp,'uppercase font-mono']" /><p v-if="rfcCurpWarning" class="text-xs text-amber-600 mt-1">⚠️ El RFC no parece corresponder al CURP ingresado. Verifica los datos.</p></div>
            <div :data-campo-error="camposError.fecha_nacimiento || undefined"><label :class="lbl">Fecha de nacimiento *</label><input v-model="datos.fecha_nacimiento" type="date" :max="fechaMax18" :class="[inp, errClass('fecha_nacimiento')]" /><p v-if="camposError.fecha_nacimiento" class="text-xs text-red-500 mt-1">{{ camposError.fecha_nacimiento }}</p></div>
            <div :data-campo-error="camposError.sexo || undefined"><label :class="lbl">Sexo *</label>
                <select v-model="datos.sexo" :class="[inp, errClass('sexo')]"><option value="" disabled>Seleccionar</option><option value="M">Masculino</option><option value="F">Femenino</option></select>
                <p v-if="camposError.sexo" class="text-xs text-red-500 mt-1">{{ camposError.sexo }}</p>
            </div>
            <div><label :class="lbl">Lugar de nacimiento</label><input v-model="datos.lugar_nacimiento" type="text" :class="inp" /></div>
            <div><label :class="lbl">Estado civil</label>
                <select v-model="datos.estado_civil" :class="inp"><option value="" disabled>Seleccionar</option><option v-for="e in estadosCiviles" :key="e" :value="e">{{ e }}</option></select>
            </div>
            <div v-if="['Casado(a)','Unión libre'].includes(datos.estado_civil)"><label :class="lbl">Régimen matrimonial</label>
                <select v-model="datos.regimen_matrimonial" :class="inp"><option value="" disabled>Seleccionar</option><option v-for="r in regMatrimonial" :key="r" :value="r">{{ r }}</option></select>
            </div>
            <div v-if="['Casado(a)','Unión libre'].includes(datos.estado_civil)"><label :class="lbl">Nombre del cónyuge</label><input v-model="datos.nombre_conyuge" type="text" :class="inp" /></div>
            <div v-if="['Casado(a)','Unión libre'].includes(datos.estado_civil)"><label :class="lbl">CURP del cónyuge</label><input :value="datos.curp_conyuge" @input="e => datos.curp_conyuge = (e.target as HTMLInputElement).value.toUpperCase().replace(/[^A-Z0-9]/g,'').slice(0,18)" type="text" maxlength="18" :class="[inp,'uppercase font-mono']" /></div>
            <div class="sm:col-span-2" :data-campo-error="camposError.direccion || undefined"><label :class="lbl">Dirección (calle y número) *</label><input v-model="datos.direccion" type="text" maxlength="200" :class="[inp, errClass('direccion')]" /><p v-if="camposError.direccion" class="text-xs text-red-500 mt-1">{{ camposError.direccion }}</p></div>
            <div><label :class="lbl">Colonia</label><input v-model="datos.colonia" type="text" maxlength="100" :class="inp" /></div>
            <div><label :class="lbl">C.P.</label><input v-model="datos.cp" type="text" maxlength="5" :class="inp" /></div>
            <div :data-campo-error="camposError.municipio || undefined"><label :class="lbl">Municipio *</label>
                <select v-model="datos.municipio" :class="[inp, errClass('municipio')]"><option value="" disabled>Seleccionar</option><option v-for="m in municipios" :key="m" :value="m">{{ m }}</option></select>
                <p v-if="camposError.municipio" class="text-xs text-red-500 mt-1">{{ camposError.municipio }}</p>
            </div>
            <div>
                <label :class="lbl">Domicilio</label>
                <div class="flex gap-4 mt-2">
                    <label class="flex items-center gap-2 cursor-pointer text-sm min-h-[44px]"><input type="radio" :value="true" v-model="datos.domicilio_propio" class="text-[#6B1938]" /> Propio</label>
                    <label class="flex items-center gap-2 cursor-pointer text-sm min-h-[44px]"><input type="radio" :value="false" v-model="datos.domicilio_propio" class="text-[#6B1938]" /> Rentado</label>
                </div>
            </div>
            <div v-if="!datos.domicilio_propio"><label :class="lbl">Renta mensual ($)</label><input v-model="datos.renta_mensual" type="number" min="0" :class="inp" /></div>
            <div :data-campo-error="camposError.telefono || undefined"><label :class="lbl">Teléfono celular *</label><input :value="datos.telefono" @input="e => datos.telefono = (e.target as HTMLInputElement).value.replace(/\D/g,'').slice(0,10)" type="tel" maxlength="10" inputmode="numeric" placeholder="10 dígitos" :class="[inp, errClass('telefono')]" /><p v-if="camposError.telefono" class="text-xs text-red-500 mt-1">{{ camposError.telefono }}</p></div>
            <div><label :class="lbl">Teléfono fijo</label><input :value="datos.telefono_fijo" @input="e => datos.telefono_fijo = (e.target as HTMLInputElement).value.replace(/\D/g,'').slice(0,10)" type="tel" maxlength="10" inputmode="numeric" :class="inp" /></div>
            <div class="sm:col-span-2" :data-campo-error="camposError.correo || undefined"><label :class="lbl">Correo electrónico *</label><input v-model="datos.correo" type="email" :class="[inp, errClass('correo')]" /><p v-if="camposError.correo" class="text-xs text-red-500 mt-1">{{ camposError.correo }}</p></div>
            <div class="sm:col-span-2 flex flex-wrap gap-5">
                <label class="flex items-center gap-3 cursor-pointer">
                    <div class="relative"><input type="checkbox" v-model="datos.mayahablante" class="sr-only peer" /><div class="w-9 h-5 bg-slate-200 dark:bg-zinc-700 peer-checked:bg-emerald-600 rounded-full transition-colors"></div><div class="absolute left-1 top-1 w-3 h-3 bg-white rounded-full transition-transform peer-checked:translate-x-4 shadow-sm"></div></div>
                    <span class="text-sm font-medium text-slate-700 dark:text-zinc-300">¿Habla Maya?</span>
                </label>
                <label class="flex items-center gap-3 cursor-pointer">
                    <div class="relative"><input type="checkbox" v-model="datos.discapacidad" class="sr-only peer" /><div class="w-9 h-5 bg-slate-200 dark:bg-zinc-700 peer-checked:bg-emerald-600 rounded-full transition-colors"></div><div class="absolute left-1 top-1 w-3 h-3 bg-white rounded-full transition-transform peer-checked:translate-x-4 shadow-sm"></div></div>
                    <span class="text-sm font-medium text-slate-700 dark:text-zinc-300">¿Tiene discapacidad?</span>
                </label>
                <label class="flex items-center gap-3 cursor-pointer">
                    <div class="relative"><input type="checkbox" v-model="datos.empleado_gobierno" class="sr-only peer" /><div class="w-9 h-5 bg-slate-200 dark:bg-zinc-700 peer-checked:bg-emerald-600 rounded-full transition-colors"></div><div class="absolute left-1 top-1 w-3 h-3 bg-white rounded-full transition-transform peer-checked:translate-x-4 shadow-sm"></div></div>
                    <span class="text-sm font-medium text-slate-700 dark:text-zinc-300">¿Empleado gobierno?</span>
                </label>
            </div>
            <div v-if="datos.discapacidad"><label :class="lbl">Tipo de discapacidad</label><input v-model="datos.discapacidad_tipo" type="text" :class="inp" /></div>
            <div v-if="datos.empleado_gobierno"><label :class="lbl">Dependencia</label><input v-model="datos.dependencia_gobierno" type="text" :class="inp" /></div>
            <div v-if="datos.empleado_gobierno"><label :class="lbl">Puesto</label><input v-model="datos.puesto_gobierno" type="text" :class="inp" /></div>
            <div class="sm:col-span-2 pt-3 border-t border-slate-100 dark:border-zinc-800">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3">Referencia personal (no familiar)</p>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div><label :class="lbl">Nombre</label><input v-model="datos.referencia_nombre" type="text" :class="inp" /></div>
                    <div><label :class="lbl">Teléfono</label><input :value="datos.referencia_telefono" @input="e => datos.referencia_telefono = (e.target as HTMLInputElement).value.replace(/\D/g,'').slice(0,10)" type="tel" maxlength="10" inputmode="numeric" :class="inp" /></div>
                    <div><label :class="lbl">C.P.</label><input v-model="datos.referencia_cp" type="text" maxlength="5" :class="inp" /></div>
                </div>
            </div>
        </div>
    </div>

    <!-- ══════════ SECCIÓN 3: PERSONA MORAL (condicional) ══════════ -->
    <div v-if="datos.tipo_persona === 'moral'" :class="card">
        <div :class="sHead" @click="toggle('empresa')">
            <div :class="sIcon"><Building2 size="18" /></div>
            <div class="flex-1"><h2 class="font-black text-slate-900 dark:text-white text-sm">Datos de la empresa</h2><p class="text-xs text-slate-400">Información de la persona moral</p></div>
            <component :is="open.empresa ? ChevronUp : ChevronDown" size="18" class="text-slate-400 shrink-0" />
        </div>
        <div v-if="open.empresa" class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="sm:col-span-2"><label :class="lbl">Razón social *</label><input v-model="datos.razon_social" type="text" :class="inp" /></div>
            <div><label :class="lbl">RFC de la empresa *</label><input v-model="datos.rfc_moral" type="text" maxlength="13" :class="[inp,'uppercase font-mono']" /></div>
            <div><label :class="lbl">Fecha de constitución</label><input v-model="datos.fecha_constitucion" type="date" :max="hoy" :class="inp" /></div>
            <div class="sm:col-span-2"><label :class="lbl">Domicilio fiscal</label><input v-model="datos.domicilio_moral" type="text" :class="inp" /></div>
            <div><label :class="lbl">Colonia</label><input v-model="datos.colonia_moral" type="text" :class="inp" /></div>
            <div><label :class="lbl">C.P.</label><input v-model="datos.cp_moral" type="text" maxlength="5" :class="inp" /></div>
            <div><label :class="lbl">Municipio</label>
                <select v-model="datos.municipio_moral" :class="inp"><option value="" disabled>Seleccionar</option><option v-for="m in municipios" :key="m" :value="m">{{ m }}</option></select>
            </div>
            <div><label :class="lbl">Teléfono empresa</label><input :value="datos.telefono_moral" @input="e => datos.telefono_moral = (e.target as HTMLInputElement).value.replace(/\D/g,'').slice(0,10)" type="tel" maxlength="10" inputmode="numeric" :class="inp" /></div>
            <div class="sm:col-span-2"><label :class="lbl">Correo empresa</label><input v-model="datos.correo_moral" type="email" :class="inp" /></div>
            <div><label :class="lbl">Nombre del representante legal *</label><input v-model="datos.rep_legal" type="text" :class="inp" /></div>
            <div><label :class="lbl">CURP del representante</label><input :value="datos.curp_rep" @input="e => datos.curp_rep = (e.target as HTMLInputElement).value.toUpperCase().replace(/[^A-Z0-9]/g,'').slice(0,18)" type="text" maxlength="18" :class="[inp,'uppercase font-mono']" /></div>
        </div>
    </div>

    <!-- ══════════ SECCIÓN 4: DATOS DEL NEGOCIO ══════════ -->
    <div :class="card">
        <div :class="sHead" @click="toggle('negocio')">
            <div :class="sIcon"><Briefcase size="18" /></div>
            <div class="flex-1"><h2 class="font-black text-slate-900 dark:text-white text-sm">Datos del negocio</h2></div>
            <component :is="open.negocio ? ChevronUp : ChevronDown" size="18" class="text-slate-400 shrink-0" />
        </div>
        <div v-if="open.negocio" class="p-5 space-y-5">
            <!-- Tipo de negocio -->
            <div class="bg-slate-50 dark:bg-zinc-800/60 rounded-xl p-4">
                <p class="text-sm font-bold text-slate-700 dark:text-zinc-200 mb-3">¿Tu negocio es un emprendimiento o ya lleva tiempo operando?</p>
                <div class="flex flex-col sm:flex-row gap-3">
                    <button type="button" @click="datos.es_emprendimiento = true"
                        :class="['flex-1 rounded-xl border-2 p-4 text-left transition-all min-h-[44px]', datos.es_emprendimiento ? 'border-[#6B1938] bg-[#6B1938]/5 dark:bg-[#6B1938]/10' : 'border-slate-200 dark:border-zinc-700 hover:border-[#6B1938]/40']">
                        <p class="font-black text-sm text-slate-900 dark:text-white">Emprendimiento</p>
                        <p class="text-xs text-slate-500 mt-0.5">Idea de negocio nueva, sin historial de operación</p>
                    </button>
                    <button type="button" @click="datos.es_emprendimiento = false"
                        :class="['flex-1 rounded-xl border-2 p-4 text-left transition-all min-h-[44px]', !datos.es_emprendimiento ? 'border-[#6B1938] bg-[#6B1938]/5 dark:bg-[#6B1938]/10' : 'border-slate-200 dark:border-zinc-700 hover:border-[#6B1938]/40']">
                        <p class="font-black text-sm text-slate-900 dark:text-white">Negocio en operación</p>
                        <p class="text-xs text-slate-500 mt-0.5">Ya lleva tiempo operando, tiene historial de ventas</p>
                    </button>
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div :data-campo-error="camposError.nombre_comercial || undefined"><label :class="lbl">Nombre comercial *</label><input v-model="datos.nombre_comercial" type="text" maxlength="150" :class="[inp, errClass('nombre_comercial')]" /><p v-if="camposError.nombre_comercial" class="text-xs text-red-500 mt-1">{{ camposError.nombre_comercial }}</p></div>
                <div :data-campo-error="camposError.giro_comercial || undefined"><label :class="lbl">Giro / Actividad *</label><input v-model="datos.giro_comercial" type="text" :class="[inp, errClass('giro_comercial')]" /><p v-if="camposError.giro_comercial" class="text-xs text-red-500 mt-1">{{ camposError.giro_comercial }}</p></div>
                <div><label :class="lbl">Municipio del negocio</label>
                    <select v-model="datos.municipio_empresa" :class="inp"><option value="" disabled>Seleccionar</option><option v-for="m in municipios" :key="m" :value="m">{{ m }}</option></select>
                </div>
                <div><label :class="lbl">Fecha de inicio del negocio</label><input v-model="datos.fecha_inicio_negocio" type="date" :max="hoy" :class="inp" /></div>
                <div><label :class="lbl">Régimen fiscal</label>
                    <select v-model="datos.regimen_fiscal" :class="inp"><option value="" disabled>Seleccionar</option><option v-for="r in regimenesFiscales" :key="r" :value="r">{{ r }}</option></select>
                </div>
                <div><label :class="lbl">Ventas mensuales aprox. ($)</label><input v-model="datos.ventas_mensuales" type="number" min="0" :class="inp" /></div>
                <div class="sm:col-span-2"><label :class="lbl">Descripción del negocio *</label><textarea v-model="datos.descripcion_negocio" rows="3" maxlength="1000" :class="[inp,'resize-none']"></textarea></div>
                <div class="sm:col-span-2"><label :class="lbl">Proceso de operación</label><textarea v-model="datos.proceso_operacion" rows="2" :class="[inp,'resize-none']"></textarea></div>
                <div><label :class="lbl">Mobiliario / maquinaria</label><textarea v-model="datos.mobiliario" rows="2" :class="[inp,'resize-none']"></textarea></div>
                <div><label :class="lbl">Recursos humanos</label><textarea v-model="datos.recursos_humanos" rows="2" :class="[inp,'resize-none']"></textarea></div>
            </div>
            <div class="flex flex-wrap gap-5">
                <label class="flex items-center gap-3 cursor-pointer">
                    <div class="relative"><input type="checkbox" v-model="datos.alta_sat" class="sr-only peer" /><div class="w-9 h-5 bg-slate-200 dark:bg-zinc-700 peer-checked:bg-emerald-600 rounded-full transition-colors"></div><div class="absolute left-1 top-1 w-3 h-3 bg-white rounded-full transition-transform peer-checked:translate-x-4 shadow-sm"></div></div>
                    <span class="text-sm font-medium text-slate-700 dark:text-zinc-300">Dado de alta en el SAT</span>
                </label>
                <label class="flex items-center gap-3 cursor-pointer">
                    <div class="relative"><input type="checkbox" v-model="datos.propiedad_intelectual" class="sr-only peer" /><div class="w-9 h-5 bg-slate-200 dark:bg-zinc-700 peer-checked:bg-emerald-600 rounded-full transition-colors"></div><div class="absolute left-1 top-1 w-3 h-3 bg-white rounded-full transition-transform peer-checked:translate-x-4 shadow-sm"></div></div>
                    <span class="text-sm font-medium text-slate-700 dark:text-zinc-300">Propiedad intelectual / Patente</span>
                </label>
            </div>
            <div v-if="datos.propiedad_intelectual"><label :class="lbl">Detalle de la propiedad intelectual</label><input v-model="datos.detalle_pi" type="text" :class="inp" /></div>
            <div v-if="isSustentable" class="sm:max-w-xs">
                <label :class="lbl">Años de antigüedad en el SAT *</label>
                <input v-model="datos.antiguedad_sat_anios" type="number" min="1" :class="inp" />
                <p class="text-[10px] text-slate-400 mt-1">Modalidad Sustentable: se requiere al menos 1 año de antigüedad.</p>
            </div>
        </div>
    </div>

    <!-- ══════════ SECCIÓN 5: DESTINO DEL CRÉDITO ══════════ -->
    <div :class="card">
        <div :class="sHead" @click="toggle('destino')">
            <div :class="sIcon"><Target size="18" /></div>
            <div class="flex-1"><h2 class="font-black text-slate-900 dark:text-white text-sm">Destino del crédito</h2></div>
            <component :is="open.destino ? ChevronUp : ChevronDown" size="18" class="text-slate-400 shrink-0" />
        </div>
        <div v-if="open.destino" class="p-5 space-y-5">
            <!-- Tabla de conceptos -->
            <div>
                <label :class="lbl">Conceptos del destino *</label>
                <!-- Mobile: cards apiladas -->
                <div class="sm:hidden space-y-3 mt-2">
                    <div v-for="(item, i) in destinoTabla" :key="i" class="bg-slate-50 dark:bg-zinc-800 rounded-xl p-3 space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-500">Concepto {{ i+1 }}</span>
                            <button v-if="destinoTabla.length > 1" @click="removeRow(destinoTabla, i)" class="text-red-500 p-1 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20"><Trash2 size="14" /></button>
                        </div>
                        <div><label :class="lbl">Concepto</label><input v-model="item.concepto" type="text" placeholder="Ej: Materia prima, Maquinaria..." :class="inp" /></div>
                        <div><label :class="lbl">Importe cotizado ($)</label><input v-model="item.importe" type="number" min="0" placeholder="0.00" :class="inp" /></div>
                        <div class="text-xs text-slate-500 text-right font-bold">{{ fmt(item.importe) }}</div>
                    </div>
                </div>
                <!-- Desktop: tabla -->
                <div class="hidden sm:block mt-2 overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-100 dark:bg-zinc-800">
                            <tr><th class="px-3 py-2 text-left text-xs font-bold text-slate-600 dark:text-zinc-300">Concepto</th><th class="px-3 py-2 text-left text-xs font-bold text-slate-600 dark:text-zinc-300 w-40">Importe cotizado</th><th class="w-8"></th></tr>
                        </thead>
                        <tbody>
                            <tr v-for="(item, i) in destinoTabla" :key="i" class="border-b border-slate-100 dark:border-zinc-800">
                                <td class="px-2 py-1.5"><input v-model="item.concepto" type="text" placeholder="Concepto" :class="[inp,'!py-2']" /></td>
                                <td class="px-2 py-1.5"><input v-model="item.importe" type="number" min="0" placeholder="0" :class="[inp,'!py-2 w-36']" /></td>
                                <td class="px-1 py-1.5"><button v-if="destinoTabla.length > 1" @click="removeRow(destinoTabla, i)" class="p-1.5 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg"><Trash2 size="14" /></button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <button @click="addRow(destinoTabla,{concepto:'',importe:''})" :disabled="destinoTabla.length >= 20" class="mt-2 flex items-center gap-1.5 text-xs text-[#6B1938] dark:text-[#f4a8c4] font-bold hover:underline disabled:opacity-40">
                    <Plus size="13" /> Agregar concepto
                </button>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label :class="lbl">Importe total del proyecto</label>
                    <div class="w-full px-3 py-2.5 rounded-xl border border-slate-200 dark:border-zinc-700 bg-slate-50 dark:bg-zinc-800 text-sm font-bold text-slate-900 dark:text-white min-h-[44px] flex items-center">
                        {{ fmt(importeTotal) }}
                    </div>
                    <p class="text-[10px] text-slate-400 mt-1">Suma automática de los conceptos</p>
                </div>
                <div :data-campo-error="camposError.monto_solicitado || undefined">
                    <label :class="lbl">Monto a solicitar ($) *</label>
                    <input v-model="datos.monto_solicitado" type="number" :min="modalidadActual?.monto_minimo" :max="modalidadActual?.monto_maximo" :class="[inp, errClass('monto_solicitado')]" />
                    <p v-if="modalidadActual" class="text-[10px] text-slate-400 mt-1">Rango: {{ fmt(modalidadActual.monto_minimo) }} – {{ fmt(modalidadActual.monto_maximo) }}</p>
                    <p v-if="datos.monto_solicitado && importeTotal > 0 && +datos.monto_solicitado > importeTotal" class="text-xs text-red-500 mt-1">El monto no puede superar el total del proyecto ({{ fmt(importeTotal) }})</p>
                    <p v-if="camposError.monto_solicitado" class="text-xs text-red-500 mt-1">{{ camposError.monto_solicitado }}</p>
                </div>
                <div :data-campo-error="camposError.plazo_meses || undefined">
                    <label :class="lbl">Plazo solicitado *</label>
                    <select v-model="datos.plazo_meses" :class="[inp, errClass('plazo_meses')]"><option value="" disabled>Seleccionar</option><option v-for="p in plazosDisponibles" :key="p" :value="p">{{ p }} meses</option></select>
                    <p v-if="camposError.plazo_meses" class="text-xs text-red-500 mt-1">{{ camposError.plazo_meses }}</p>
                </div>
            </div>

            <!-- Apoyos gobierno -->
            <div>
                <label :class="lbl">Créditos/apoyos de gobierno previos (opcional)</label>
                <div class="space-y-2 mt-1">
                    <div v-for="(a, i) in apoyosGob" :key="i" class="flex flex-col sm:flex-row gap-2">
                        <input v-model="a.dependencia" type="text" placeholder="Dependencia" :class="[inp,'flex-1']" />
                        <input v-model="a.destino" type="text" placeholder="Destino" :class="[inp,'flex-1']" />
                        <input v-model="a.monto" type="number" min="0" placeholder="Monto" :class="[inp,'sm:w-28']" />
                        <button @click="removeRow(apoyosGob, i)" aria-label="Eliminar apoyo de gobierno" class="p-2 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg min-h-[44px]"><Trash2 size="15" /></button>
                    </div>
                </div>
                <button @click="addRow(apoyosGob,{dependencia:'',destino:'',monto:''})" class="mt-2 flex items-center gap-1.5 text-xs text-[#6B1938] dark:text-[#f4a8c4] font-bold hover:underline">
                    <Plus size="13" /> Agregar apoyo
                </button>
            </div>
        </div>
    </div>

    <!-- ══════════ SECCIÓN 6: FICHA TÉCNICA ══════════ -->
    <div :class="card">
        <div :class="sHead" @click="toggle('ficha')">
            <div :class="sIcon"><BarChart2 size="18" /></div>
            <div class="flex-1"><h2 class="font-black text-slate-900 dark:text-white text-sm">Ficha técnica</h2><p class="text-xs text-slate-400">Proveedores, clientes y deudas</p></div>
            <component :is="open.ficha ? ChevronUp : ChevronDown" size="18" class="text-slate-400 shrink-0" />
        </div>
        <div v-if="open.ficha" class="p-5 space-y-6">
            <!-- Proveedores — mobile cards -->
            <div>
                <p :class="[lbl,'mb-2']">Principales proveedores</p>
                <div class="sm:hidden space-y-3">
                    <div v-for="(p, i) in proveedores" :key="i" class="bg-slate-50 dark:bg-zinc-800 rounded-xl p-3 space-y-2">
                        <div class="flex justify-between"><span class="text-xs font-bold text-slate-500">Proveedor {{ i+1 }}</span>
                            <button v-if="proveedores.length > 1" @click="removeRow(proveedores,i)" class="text-red-500 p-1 rounded-lg hover:bg-red-50"><Trash2 size="14" /></button></div>
                        <div class="grid grid-cols-2 gap-2">
                            <div><label :class="lbl">Nombre</label><input v-model="p.nombre" type="text" :class="inp" /></div>
                            <div><label :class="lbl">Antigüedad</label><input v-model="p.antiguedad" type="text" placeholder="1 año" :class="inp" /></div>
                            <div><label :class="lbl">Insumo</label><input v-model="p.insumo" type="text" :class="inp" /></div>
                            <div><label :class="lbl">Compras/mes $</label><input v-model="p.compras_mensuales" type="number" min="0" :class="inp" /></div>
                        </div>
                        <div><label :class="lbl">Política de pago</label>
                            <select v-model="p.politica" :class="inp"><option>Contado</option><option>Crédito</option></select></div>
                    </div>
                </div>
                <!-- Desktop tabla -->
                <div class="hidden sm:block overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead class="bg-slate-100 dark:bg-zinc-800"><tr>
                            <th class="px-2 py-2 text-left font-bold text-slate-600 dark:text-zinc-300">Nombre</th>
                            <th class="px-2 py-2 text-left font-bold text-slate-600 dark:text-zinc-300">Antigüedad</th>
                            <th class="px-2 py-2 text-left font-bold text-slate-600 dark:text-zinc-300">Insumo</th>
                            <th class="px-2 py-2 text-left font-bold text-slate-600 dark:text-zinc-300">Compras/mes $</th>
                            <th class="px-2 py-2 text-left font-bold text-slate-600 dark:text-zinc-300">Política</th>
                            <th class="w-8"></th>
                        </tr></thead>
                        <tbody>
                            <tr v-for="(p, i) in proveedores" :key="i" class="border-b border-slate-100 dark:border-zinc-800">
                                <td class="px-1 py-1"><input v-model="p.nombre" type="text" :class="[inp,'!py-1.5 !px-2 text-xs']" /></td>
                                <td class="px-1 py-1"><input v-model="p.antiguedad" type="text" placeholder="1 año" :class="[inp,'!py-1.5 !px-2 text-xs']" /></td>
                                <td class="px-1 py-1"><input v-model="p.insumo" type="text" :class="[inp,'!py-1.5 !px-2 text-xs']" /></td>
                                <td class="px-1 py-1"><input v-model="p.compras_mensuales" type="number" min="0" :class="[inp,'!py-1.5 !px-2 text-xs w-24']" /></td>
                                <td class="px-1 py-1"><select v-model="p.politica" :class="[inp,'!py-1.5 !px-2 text-xs']"><option>Contado</option><option>Crédito</option></select></td>
                                <td class="px-1 py-1"><button v-if="proveedores.length > 1" @click="removeRow(proveedores,i)" class="text-red-500 p-1 rounded hover:bg-red-50 dark:hover:bg-red-900/20"><Trash2 size="12" /></button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <button @click="addRow(proveedores,{nombre:'',antiguedad:'',insumo:'',compras_mensuales:'',politica:'Contado'})" class="mt-2 flex items-center gap-1.5 text-xs text-[#6B1938] dark:text-[#f4a8c4] font-bold hover:underline"><Plus size="13" /> Agregar proveedor</button>
            </div>

            <!-- Clientes — mobile cards -->
            <div>
                <p :class="[lbl,'mb-2']">Principales clientes</p>
                <div class="sm:hidden space-y-3">
                    <div v-for="(c, i) in clientes" :key="i" class="bg-slate-50 dark:bg-zinc-800 rounded-xl p-3 space-y-2">
                        <div class="flex justify-between"><span class="text-xs font-bold text-slate-500">Cliente {{ i+1 }}</span>
                            <button v-if="clientes.length > 1" @click="removeRow(clientes,i)" class="text-red-500 p-1 rounded-lg hover:bg-red-50"><Trash2 size="14" /></button></div>
                        <div class="grid grid-cols-2 gap-2">
                            <div><label :class="lbl">Nombre</label><input v-model="c.nombre" type="text" :class="inp" /></div>
                            <div><label :class="lbl">Sector</label><input v-model="c.sector" type="text" :class="inp" /></div>
                            <div><label :class="lbl">Ventas/mes $</label><input v-model="c.ventas_mensuales" type="number" min="0" :class="inp" /></div>
                            <div><label :class="lbl">Política</label><select v-model="c.politica" :class="inp"><option>Contado</option><option>Crédito</option></select></div>
                        </div>
                    </div>
                </div>
                <div class="hidden sm:block overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead class="bg-slate-100 dark:bg-zinc-800"><tr>
                            <th class="px-2 py-2 text-left font-bold text-slate-600 dark:text-zinc-300">Nombre</th>
                            <th class="px-2 py-2 text-left font-bold text-slate-600 dark:text-zinc-300">Sector</th>
                            <th class="px-2 py-2 text-left font-bold text-slate-600 dark:text-zinc-300">Ventas/mes $</th>
                            <th class="px-2 py-2 text-left font-bold text-slate-600 dark:text-zinc-300">Política</th>
                            <th class="w-8"></th>
                        </tr></thead>
                        <tbody>
                            <tr v-for="(c, i) in clientes" :key="i" class="border-b border-slate-100 dark:border-zinc-800">
                                <td class="px-1 py-1"><input v-model="c.nombre" type="text" :class="[inp,'!py-1.5 !px-2 text-xs']" /></td>
                                <td class="px-1 py-1"><input v-model="c.sector" type="text" :class="[inp,'!py-1.5 !px-2 text-xs']" /></td>
                                <td class="px-1 py-1"><input v-model="c.ventas_mensuales" type="number" min="0" :class="[inp,'!py-1.5 !px-2 text-xs w-24']" /></td>
                                <td class="px-1 py-1"><select v-model="c.politica" :class="[inp,'!py-1.5 !px-2 text-xs']"><option>Contado</option><option>Crédito</option></select></td>
                                <td class="px-1 py-1"><button v-if="clientes.length > 1" @click="removeRow(clientes,i)" class="text-red-500 p-1 rounded hover:bg-red-50 dark:hover:bg-red-900/20"><Trash2 size="12" /></button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <button @click="addRow(clientes,{nombre:'',sector:'',ventas_mensuales:'',politica:'Contado'})" class="mt-2 flex items-center gap-1.5 text-xs text-[#6B1938] dark:text-[#f4a8c4] font-bold hover:underline"><Plus size="13" /> Agregar cliente</button>
            </div>

            <!-- Deudas -->
            <div>
                <p :class="[lbl,'mb-2']">Deudas y obligaciones del negocio (opcional)</p>
                <div class="space-y-2">
                    <div v-for="(d, i) in deudasNegocio" :key="i" class="flex flex-col sm:flex-row gap-2">
                        <input v-model="d.nombre" type="text" placeholder="Institución" :class="[inp,'flex-1']" />
                        <input v-model="d.monto" type="number" min="0" placeholder="Monto" :class="[inp,'sm:w-28']" />
                        <input v-model="d.vencimiento" type="text" placeholder="Vencimiento" :class="[inp,'sm:w-28']" />
                        <input v-model="d.garantia" type="text" placeholder="Garantía" :class="[inp,'sm:w-28']" />
                        <button @click="removeRow(deudasNegocio,i)" aria-label="Eliminar deuda del negocio" class="p-2 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg min-h-[44px]"><Trash2 size="15" /></button>
                    </div>
                </div>
                <button @click="addRow(deudasNegocio,{nombre:'',monto:'',vencimiento:'',garantia:''})" class="mt-2 flex items-center gap-1.5 text-xs text-[#6B1938] dark:text-[#f4a8c4] font-bold hover:underline"><Plus size="13" /> Agregar deuda</button>
            </div>

            <!-- Productos/servicios y distribución -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-4 border-t border-slate-100 dark:border-zinc-800">
                <div><label :class="lbl">Productos o servicios que ofrece</label><textarea v-model="datos.productos" rows="3" placeholder="Describe los principales productos o servicios" :class="[inp,'resize-none']"></textarea></div>
                <div><label :class="lbl">¿Tiene distribución en otros municipios?</label><textarea v-model="datos.distribucion" rows="3" placeholder="Indica en qué municipios distribuye, si aplica" :class="[inp,'resize-none']"></textarea></div>
            </div>
        </div>
    </div>

    <!-- ══════════ SECCIÓN 7: INGRESOS Y EGRESOS ══════════ -->
    <div :class="card">
        <div :class="sHead" @click="toggle('ingresos')">
            <div :class="sIcon"><TrendingUp size="18" /></div>
            <div class="flex-1">
                <h2 class="font-black text-slate-900 dark:text-white text-sm">Estado de ingresos y egresos</h2>
                <p class="text-xs text-slate-400">{{ datos.es_emprendimiento ? 'Proyección de los próximos 6 meses' : 'Historial de los últimos 6 meses + proyección' }}</p>
            </div>
            <component :is="open.ingresos ? ChevronUp : ChevronDown" size="18" class="text-slate-400 shrink-0" />
        </div>
        <div v-if="open.ingresos" class="p-5 space-y-6">
            <!-- Tabla histórica (solo negocio en operación) -->
            <template v-if="!datos.es_emprendimiento">
                <div class="rounded-xl border border-slate-200 dark:border-zinc-700 overflow-hidden">
                    <div class="bg-slate-100 dark:bg-zinc-800 px-4 py-2.5 flex items-center justify-between">
                        <span class="text-xs font-black uppercase tracking-wider text-slate-600 dark:text-zinc-300">Historial — últimos 6 meses</span>
                    </div>
                    <div class="p-4 space-y-4">
                        <div class="grid grid-cols-2 gap-2 sm:gap-4">
                            <div><label :class="lbl">Periodo del</label><input v-model="ieHistorico.periodo_del" type="date" :max="hoy" :class="inp" /></div>
                            <div><label :class="lbl">al</label><input v-model="ieHistorico.periodo_al" type="date" :max="hoy" :class="inp" /></div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div><label :class="lbl">Ventas / Servicios ($)</label><input v-model="ieHistorico.ventas" type="number" min="0" :class="inp" /></div>
                            <div><label :class="lbl">Costo del producto/servicio ($)</label><input v-model="ieHistorico.costo_producto" type="number" min="0" :class="inp" /></div>
                        </div>
                        <div class="bg-emerald-50 dark:bg-emerald-900/20 rounded-xl px-4 py-2 flex justify-between text-sm">
                            <span class="font-bold text-emerald-800 dark:text-emerald-400">Utilidad bruta</span>
                            <span class="font-black text-[#6B1938] dark:text-[#f4a8c4]">{{ fmt(calcIE(ieHistorico).utilBruta) }}</span>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                            <div v-for="[k,l] in [['gastos_electricidad','Energía eléctrica'],['gastos_agua','Agua'],['gastos_telefono','Teléfono'],['gastos_gas','Gas'],['gastos_mano_obra','Mano de obra'],['gastos_nomina','Nómina'],['gastos_renta_local','Renta del local']]" :key="k">
                                <label :class="lbl">{{ l }} ($)</label><input v-model="ieHistorico[k]" type="number" min="0" :class="inp" />
                            </div>
                        </div>
                        <div>
                            <label :class="lbl">Otros gastos</label>
                            <div class="space-y-2 mt-1">
                                <div v-for="(g, i) in (ieHistorico.otros_gastos ?? (ieHistorico.otros_gastos = []))" :key="i" class="flex gap-2">
                                    <input v-model="g.concepto" type="text" placeholder="Concepto" :class="[inp,'flex-1']" />
                                    <input v-model="g.importe" type="number" min="0" placeholder="Monto $" :class="[inp,'w-32']" />
                                    <button @click="removeRow(ieHistorico.otros_gastos, i)" aria-label="Eliminar gasto" class="p-2 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg min-h-[44px]"><Trash2 size="15" /></button>
                                </div>
                            </div>
                            <button @click="addRow(ieHistorico.otros_gastos, {concepto:'',importe:''})" class="mt-2 flex items-center gap-1.5 text-xs text-[#6B1938] dark:text-[#f4a8c4] font-bold hover:underline"><Plus size="13" /> Agregar otro gasto</button>
                        </div>
                        <div><label :class="lbl">Impuestos ($)</label><input v-model="ieHistorico.impuestos" type="number" min="0" :class="inp" /></div>
                        <div class="bg-[#6B1938] rounded-xl px-4 py-3 flex justify-between">
                            <span class="text-sm font-black text-white">Utilidad neta mensual</span>
                            <span class="font-black text-white">{{ fmt(calcIE(ieHistorico).utilNeta) }}</span>
                        </div>
                    </div>
                </div>
            </template>

            <!-- Tabla proyección (siempre) -->
            <div class="rounded-xl border border-[#6B1938]/30 dark:border-[#6B1938]/20 overflow-hidden">
                <div class="bg-[#6B1938]/10 dark:bg-[#6B1938]/20 px-4 py-2.5">
                    <span class="text-xs font-black uppercase tracking-wider text-[#6B1938] dark:text-[#f4a8c4]">Proyección — próximos 6 meses</span>
                </div>
                <div class="p-4 space-y-4">
                    <div class="grid grid-cols-2 gap-2 sm:gap-4">
                        <div><label :class="lbl">Periodo del</label><input v-model="ieProyeccion.periodo_del" type="date" :class="inp" /></div>
                        <div><label :class="lbl">al</label><input v-model="ieProyeccion.periodo_al" type="date" :class="inp" /></div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div><label :class="lbl">Ventas / Servicios proyectadas ($)</label><input v-model="ieProyeccion.ventas" type="number" min="0" :class="inp" /></div>
                        <div><label :class="lbl">Costo del producto/servicio ($)</label><input v-model="ieProyeccion.costo_producto" type="number" min="0" :class="inp" /></div>
                    </div>
                    <div class="bg-emerald-50 dark:bg-emerald-900/20 rounded-xl px-4 py-2 flex justify-between text-sm">
                        <span class="font-bold text-emerald-800 dark:text-emerald-400">Utilidad bruta proyectada</span>
                        <span class="font-black text-[#6B1938] dark:text-[#f4a8c4]">{{ fmt(calcIE(ieProyeccion).utilBruta) }}</span>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                        <div v-for="[k,l] in [['gastos_electricidad','Energía eléctrica'],['gastos_agua','Agua'],['gastos_telefono','Teléfono'],['gastos_gas','Gas'],['gastos_mano_obra','Mano de obra'],['gastos_nomina','Nómina'],['gastos_renta_local','Renta del local']]" :key="k">
                            <label :class="lbl">{{ l }} ($)</label><input v-model="ieProyeccion[k]" type="number" min="0" :class="inp" />
                        </div>
                    </div>
                    <div>
                        <label :class="lbl">Otros gastos</label>
                        <div class="space-y-2 mt-1">
                            <div v-for="(g, i) in (ieProyeccion.otros_gastos ?? (ieProyeccion.otros_gastos = []))" :key="i" class="flex gap-2">
                                <input v-model="g.concepto" type="text" placeholder="Concepto" :class="[inp,'flex-1']" />
                                <input v-model="g.importe" type="number" min="0" placeholder="Monto $" :class="[inp,'w-32']" />
                                <button @click="removeRow(ieProyeccion.otros_gastos, i)" aria-label="Eliminar gasto proyectado" class="p-2 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg min-h-[44px]"><Trash2 size="15" /></button>
                            </div>
                        </div>
                        <button @click="addRow(ieProyeccion.otros_gastos, {concepto:'',importe:''})" class="mt-2 flex items-center gap-1.5 text-xs text-[#6B1938] dark:text-[#f4a8c4] font-bold hover:underline"><Plus size="13" /> Agregar otro gasto</button>
                    </div>
                    <div><label :class="lbl">Impuestos proyectados ($)</label><input v-model="ieProyeccion.impuestos" type="number" min="0" :class="inp" /></div>
                    <div class="bg-[#6B1938] rounded-xl px-4 py-3 flex justify-between">
                        <span class="text-sm font-black text-white">Utilidad neta proyectada</span>
                        <span class="font-black text-white">{{ fmt(calcIE(ieProyeccion).utilNeta) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ══════════ SECCIÓN 8: AVAL / GARANTÍA ══════════ -->
    <div :class="card">
        <div :class="sHead" @click="toggle('garantia')">
            <div :class="sIcon"><Users size="18" /></div>
            <div class="flex-1">
                <h2 class="font-black text-slate-900 dark:text-white text-sm">{{ datos.tipo_garantia === 'aval' ? 'Datos del aval solidario' : 'Datos de la garantía' }}</h2>
            </div>
            <component :is="open.garantia ? ChevronUp : ChevronDown" size="18" class="text-slate-400 shrink-0" />
        </div>
        <div v-if="open.garantia">
            <!-- Aval -->
            <div v-if="datos.tipo_garantia === 'aval'" class="p-5 space-y-5">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2"><label :class="lbl">Nombre completo del aval *</label><input v-model="aval.nombre_completo" type="text" :class="inp" /></div>
                    <div>
                        <label :class="lbl">Parentesco con el solicitante *</label>
                        <select v-model="aval.parentesco" :class="inp">
                            <option value="" disabled>Seleccionar</option>
                            <option v-for="p in parentescosAval" :key="p" :value="p">{{ p }}</option>
                        </select>
                        <p v-if="isArtesanal && parentescosProhibidosArtesanal.includes(aval.parentesco.toLowerCase())" class="text-red-600 text-xs mt-1">
                            En la modalidad Artesanal el aval no puede ser familiar hasta 2do grado del solicitante.
                        </p>
                    </div>
                    <div><label :class="lbl">CURP del aval *</label><input :value="aval.curp" @input="e => aval.curp = (e.target as HTMLInputElement).value.toUpperCase().replace(/[^A-Z0-9]/g,'').slice(0,18)" type="text" maxlength="18" :class="[inp,'uppercase font-mono']" /></div>
                    <div><label :class="lbl">RFC del aval</label><input v-model="aval.rfc" type="text" maxlength="13" :class="[inp,'uppercase font-mono']" /></div>
                    <div><label :class="lbl">Fecha de nacimiento *</label><input v-model="aval.fecha_nacimiento" type="date" :max="fechaMax18" :class="inp" /></div>
                    <div><label :class="lbl">Edad</label><input v-model="aval.edad" type="number" min="18" max="99" :class="inp" /></div>
                    <div><label :class="lbl">Sexo</label><select v-model="aval.sexo" :class="inp"><option value="" disabled>Seleccionar</option><option value="M">Masculino</option><option value="F">Femenino</option></select></div>
                    <div><label :class="lbl">Estado civil</label><select v-model="aval.estado_civil" :class="inp"><option value="" disabled>Seleccionar</option><option v-for="e in estadosCiviles" :key="e" :value="e">{{ e }}</option></select></div>
                    <div><label :class="lbl">Correo *</label><input v-model="aval.correo" type="email" :class="inp" /></div>
                    <div><label :class="lbl">Teléfono celular *</label><input :value="aval.telefono_celular" @input="e => aval.telefono_celular = (e.target as HTMLInputElement).value.replace(/\D/g,'').slice(0,10)" type="tel" maxlength="10" inputmode="numeric" :class="inp" /></div>
                    <div><label :class="lbl">Teléfono fijo</label><input :value="aval.telefono_fijo" @input="e => aval.telefono_fijo = (e.target as HTMLInputElement).value.replace(/\D/g,'').slice(0,10)" type="tel" maxlength="10" inputmode="numeric" :class="inp" /></div>
                    <div class="sm:col-span-2"><label :class="lbl">Dirección</label><input v-model="aval.domicilio" type="text" :class="inp" /></div>
                    <div><label :class="lbl">Colonia</label><input v-model="aval.colonia" type="text" :class="inp" /></div>
                    <div><label :class="lbl">C.P.</label><input v-model="aval.cp" type="text" maxlength="5" :class="inp" /></div>
                    <div><label :class="lbl">Municipio de residencia</label><select v-model="aval.municipio_residencia" :class="inp"><option value="" disabled>Seleccionar</option><option v-for="m in municipios" :key="m" :value="m">{{ m }}</option></select></div>
                    <div><label :class="lbl">Municipio de nacimiento</label><select v-model="aval.municipio_nacimiento" :class="inp"><option value="" disabled>Seleccionar</option><option v-for="m in municipios" :key="m" :value="m">{{ m }}</option></select></div>
                    <div><label :class="lbl">Ocupación</label><input v-model="aval.ocupacion" type="text" :class="inp" /></div>
                    <div><label :class="lbl">Lugar de trabajo</label><input v-model="aval.lugar_laboral" type="text" :class="inp" /></div>
                    <div><label :class="lbl">Antigüedad laboral</label><input v-model="aval.antiguedad_laboral" type="text" placeholder="Ej: 3 años" :class="inp" /></div>
                    <div><label :class="lbl">Dependientes económicos</label><input v-model="aval.dependientes_economicos" type="number" min="0" :class="inp" /></div>
                    <div>
                        <label :class="lbl">Domicilio del aval</label>
                        <div class="flex gap-4 mt-2">
                            <label class="flex items-center gap-2 cursor-pointer text-sm min-h-[44px]"><input type="radio" :value="true" v-model="aval.domicilio_propio" class="text-[#6B1938]" /> Propio</label>
                            <label class="flex items-center gap-2 cursor-pointer text-sm min-h-[44px]"><input type="radio" :value="false" v-model="aval.domicilio_propio" class="text-[#6B1938]" /> Rentado</label>
                        </div>
                    </div>
                    <div v-if="!aval.domicilio_propio"><label :class="lbl">Renta mensual del aval ($)</label><input v-model="aval.renta_mensual" type="number" min="0" :class="inp" /></div>
                    <div v-if="['Casado(a)','Unión libre'].includes(aval.estado_civil)"><label :class="lbl">Régimen matrimonial del aval</label>
                        <select v-model="aval.regimen_matrimonial" :class="inp"><option value="" disabled>Seleccionar</option><option v-for="r in regMatrimonial" :key="r" :value="r">{{ r }}</option></select>
                    </div>
                    <div v-if="['Casado(a)','Unión libre'].includes(aval.estado_civil)"><label :class="lbl">Nombre del cónyuge del aval</label><input v-model="aval.nombre_conyuge" type="text" :class="inp" /></div>
                </div>

                <!-- Bienes inmuebles del aval -->
                <div class="pt-4 border-t border-slate-100 dark:border-zinc-800">
                    <p :class="[lbl,'mb-2']">Propiedades del aval</p>
                    <div class="space-y-2">
                        <div v-for="(b, i) in avBienes" :key="i" class="flex flex-col sm:flex-row gap-2">
                            <input v-model="b.descripcion" type="text" placeholder="Descripción" :class="[inp,'flex-1']" />
                            <input v-model="b.ubicacion" type="text" placeholder="Ubicación" :class="[inp,'flex-1']" />
                            <input v-model="b.valor" type="number" min="0" placeholder="Valor $" :class="[inp,'sm:w-32']" />
                            <button v-if="avBienes.length > 1" @click="removeRow(avBienes,i)" class="p-2 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg min-h-[44px]"><Trash2 size="15" /></button>
                        </div>
                    </div>
                    <button @click="addRow(avBienes,{descripcion:'',ubicacion:'',valor:''})" class="mt-2 flex items-center gap-1.5 text-xs text-[#6B1938] dark:text-[#f4a8c4] font-bold hover:underline"><Plus size="13" /> Agregar inmueble</button>
                </div>
                <!-- Bienes muebles del aval -->
                <div class="pt-4 border-t border-slate-100 dark:border-zinc-800">
                    <p :class="[lbl,'mb-2']">Bienes muebles del aval (opcional)</p>
                    <div class="space-y-2">
                        <div v-for="(b, i) in avBienesMuebles" :key="i" class="flex flex-col sm:flex-row gap-2">
                            <input v-model="b.descripcion" type="text" placeholder="Descripción" :class="[inp,'flex-1']" />
                            <input v-model="b.valor" type="number" min="0" placeholder="Valor $" :class="[inp,'sm:w-32']" />
                            <button @click="removeRow(avBienesMuebles,i)" aria-label="Eliminar bien mueble" class="p-2 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg min-h-[44px]"><Trash2 size="15" /></button>
                        </div>
                    </div>
                    <button @click="addRow(avBienesMuebles,{descripcion:'',valor:''})" class="mt-2 flex items-center gap-1.5 text-xs text-[#6B1938] dark:text-[#f4a8c4] font-bold hover:underline"><Plus size="13" /> Agregar bien mueble</button>
                </div>
                <!-- Hipotecas / créditos y otras deudas del aval -->
                <div class="pt-4 border-t border-slate-100 dark:border-zinc-800">
                    <p :class="[lbl,'mb-2']">Hipotecas o créditos vigentes del aval (opcional)</p>
                    <div class="space-y-2">
                        <div v-for="(h, i) in avHipotecas" :key="i" class="flex flex-col sm:flex-row gap-2">
                            <input v-model="h.institucion" type="text" placeholder="Institución" :class="[inp,'flex-1']" />
                            <input v-model="h.saldo" type="number" min="0" placeholder="Saldo $" :class="[inp,'sm:w-28']" />
                            <input v-model="h.mensualidad" type="number" min="0" placeholder="Mensualidad $" :class="[inp,'sm:w-28']" />
                            <button @click="removeRow(avHipotecas,i)" aria-label="Eliminar hipoteca o crédito del aval" class="p-2 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg min-h-[44px]"><Trash2 size="15" /></button>
                        </div>
                    </div>
                    <button @click="addRow(avHipotecas,{institucion:'',saldo:'',mensualidad:''})" class="mt-2 flex items-center gap-1.5 text-xs text-[#6B1938] dark:text-[#f4a8c4] font-bold hover:underline"><Plus size="13" /> Agregar hipoteca/crédito</button>
                </div>
                <div class="pt-4 border-t border-slate-100 dark:border-zinc-800">
                    <p :class="[lbl,'mb-2']">Otras deudas del aval (opcional)</p>
                    <div class="space-y-2">
                        <div v-for="(d, i) in avOtrasDeudas" :key="i" class="flex flex-col sm:flex-row gap-2">
                            <input v-model="d.concepto" type="text" placeholder="Concepto" :class="[inp,'flex-1']" />
                            <input v-model="d.monto" type="number" min="0" placeholder="Monto $" :class="[inp,'sm:w-28']" />
                            <button @click="removeRow(avOtrasDeudas,i)" aria-label="Eliminar deuda del aval" class="p-2 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg min-h-[44px]"><Trash2 size="15" /></button>
                        </div>
                    </div>
                    <button @click="addRow(avOtrasDeudas,{concepto:'',monto:''})" class="mt-2 flex items-center gap-1.5 text-xs text-[#6B1938] dark:text-[#f4a8c4] font-bold hover:underline"><Plus size="13" /> Agregar deuda</button>
                </div>
                <!-- Ingresos y egresos del aval -->
                <div class="pt-4 border-t border-slate-100 dark:border-zinc-800">
                    <p :class="[lbl,'mb-2']">Ingresos del aval</p>
                    <div class="space-y-2">
                        <div v-for="(ig, i) in avIngresos" :key="i" class="flex flex-col sm:flex-row gap-2">
                            <input v-model="ig.fuente" type="text" placeholder="Fuente de ingreso" :class="[inp,'flex-1']" />
                            <input v-model="ig.monto" type="number" min="0" placeholder="Monto mensual $" :class="[inp,'sm:w-36']" />
                            <button v-if="avIngresos.length > 1" @click="removeRow(avIngresos,i)" class="p-2 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg min-h-[44px]"><Trash2 size="15" /></button>
                        </div>
                    </div>
                    <button @click="addRow(avIngresos,{fuente:'',monto:''})" class="mt-2 flex items-center gap-1.5 text-xs text-[#6B1938] dark:text-[#f4a8c4] font-bold hover:underline"><Plus size="13" /> Agregar ingreso</button>
                </div>
                <div class="pt-4 border-t border-slate-100 dark:border-zinc-800">
                    <p :class="[lbl,'mb-2']">Egresos del aval (opcional)</p>
                    <div class="space-y-2">
                        <div v-for="(eg, i) in avEgresos" :key="i" class="flex flex-col sm:flex-row gap-2">
                            <input v-model="eg.concepto" type="text" placeholder="Concepto de gasto" :class="[inp,'flex-1']" />
                            <input v-model="eg.monto" type="number" min="0" placeholder="Monto mensual $" :class="[inp,'sm:w-36']" />
                            <button @click="removeRow(avEgresos,i)" aria-label="Eliminar egreso del aval" class="p-2 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg min-h-[44px]"><Trash2 size="15" /></button>
                        </div>
                    </div>
                    <button @click="addRow(avEgresos,{concepto:'',monto:''})" class="mt-2 flex items-center gap-1.5 text-xs text-[#6B1938] dark:text-[#f4a8c4] font-bold hover:underline"><Plus size="13" /> Agregar egreso</button>
                </div>
                <!-- Referencias del aval -->
                <div class="pt-4 border-t border-slate-100 dark:border-zinc-800">
                    <p :class="[lbl,'mb-2']">Referencias personales del aval (no familiares)</p>
                    <div class="space-y-2">
                        <div v-for="(r, i) in avReferencias" :key="i" class="flex flex-col sm:flex-row gap-2">
                            <input v-model="r.nombre" type="text" placeholder="Nombre" :class="[inp,'flex-1']" />
                            <input :value="r.telefono" @input="e => r.telefono = (e.target as HTMLInputElement).value.replace(/\D/g,'').slice(0,10)" type="tel" maxlength="10" inputmode="numeric" placeholder="Teléfono" :class="[inp,'sm:w-36']" />
                            <input v-model="r.cp" type="text" placeholder="C.P." maxlength="5" :class="[inp,'sm:w-20']" />
                            <button v-if="avReferencias.length > 1" @click="removeRow(avReferencias,i)" class="p-2 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg min-h-[44px]"><Trash2 size="15" /></button>
                        </div>
                    </div>
                    <button @click="addRow(avReferencias,{nombre:'',telefono:'',cp:''})" class="mt-2 flex items-center gap-1.5 text-xs text-[#6B1938] dark:text-[#f4a8c4] font-bold hover:underline"><Plus size="13" /> Agregar referencia</button>
                </div>
            </div>
            <!-- Garantía prendaria / hipotecaria -->
            <div v-else class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2"><label :class="lbl">Descripción del {{ datos.tipo_garantia === 'prendaria' ? 'bien mueble' : 'bien inmueble' }} *</label><textarea v-model="datos.garantia_descripcion" rows="2" :class="[inp,'resize-none']"></textarea></div>
                <div><label :class="lbl">Valor estimado ($) *</label><input v-model="datos.garantia_valor" type="number" min="0" :class="inp" /></div>
                <div v-if="datos.tipo_garantia === 'prendaria'"><label :class="lbl">Fecha de factura</label><input v-model="datos.garantia_fecha_factura" type="date" :max="hoy" :class="inp" /></div>
                <div v-if="datos.tipo_garantia === 'prendaria'"><label :class="lbl">Valor de factura ($)</label><input v-model="datos.garantia_valor_factura" type="number" min="0" :class="inp" /></div>
            </div>
        </div>
    </div>

    <!-- ══════════ SECCIÓN 9: DOCUMENTOS ══════════ -->
    <div :class="card">
        <div :class="sHead" @click="toggle('documentos')">
            <div :class="sIcon"><FileUp size="18" /></div>
            <div class="flex-1">
                <h2 class="font-black text-slate-900 dark:text-white text-sm">Documentos requeridos</h2>
                <p class="text-xs text-slate-400">PDF, JPG o PNG — máx. 10 MB c/u</p>
            </div>
            <span :class="['text-xs font-bold px-2.5 py-1 rounded-full mr-2', todosDocsCompletos ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400']">
                {{ totalDocsSubidos }}/{{ totalDocsReq }}
            </span>
            <component :is="open.documentos ? ChevronUp : ChevronDown" size="18" class="text-slate-400 shrink-0" />
        </div>
        <div v-if="open.documentos" class="p-5 space-y-3">
            <div v-for="(label, tipo) in tiposDocRequeridos" :key="tipo">
                <div :class="['rounded-xl border p-4 space-y-2',
                    docSubidos[tipo]
                        ? (docSubidos[tipo].estatus === 'Aprobado' ? 'bg-green-50 dark:bg-green-900/10 border-green-200 dark:border-green-800/40'
                           : docSubidos[tipo].estatus === 'Rechazado' ? 'bg-red-50 dark:bg-red-900/10 border-red-200 dark:border-red-800/40'
                           : 'bg-amber-50 dark:bg-amber-900/10 border-amber-200 dark:border-amber-800/40')
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
                        <label :for="`doc-${tipo}`" class="flex-1 flex items-center justify-center gap-2 px-3 py-2.5 border-2 border-dashed border-slate-300 dark:border-zinc-600 rounded-lg cursor-pointer hover:border-[#6B1938]/60 hover:bg-[#6B1938]/5 transition-all text-xs text-slate-500 dark:text-zinc-400 hover:text-[#6B1938] min-h-[44px]">
                            <FileUp size="13" />
                            {{ docArchivos[tipo] ? docArchivos[tipo]?.name : (docSubidos[tipo] ? 'Reemplazar' : 'Seleccionar archivo') }}
                            <input :id="`doc-${tipo}`" type="file" accept=".pdf,.jpg,.jpeg,.png" class="sr-only" @change="seleccionarDoc(tipo, $event)" />
                        </label>
                        <button v-if="docArchivos[tipo]" @click="subirDoc(tipo)" :disabled="docSubiendo[tipo]"
                            class="px-4 py-2.5 bg-[#6B1938] hover:bg-[#4A0E22] disabled:opacity-60 text-white font-bold rounded-lg text-xs flex items-center gap-1.5 shrink-0 min-h-[44px]">
                            <Loader2 v-if="docSubiendo[tipo]" size="12" class="animate-spin" />
                            {{ docSubiendo[tipo] ? 'Subiendo...' : 'Subir' }}
                        </button>
                    </div>
                    <p v-if="docSubidos[tipo]?.observacion" class="text-xs text-red-600 dark:text-red-400 mt-1">Observación: {{ docSubidos[tipo].observacion }}</p>
                    <p v-if="docErrores[tipo]" class="text-xs font-bold text-red-600 dark:text-red-400 mt-1">{{ docErrores[tipo] }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- ══════════ SECCIÓN 10: REVISIÓN Y ENVÍO ══════════ -->
    <div :class="card">
        <div :class="sHead" style="cursor:default">
            <div :class="sIcon"><ListChecks size="18" /></div>
            <div class="flex-1"><h2 class="font-black text-slate-900 dark:text-white text-sm">Revisión final</h2><p class="text-xs text-slate-400">Verifica tus datos y documentos antes de enviar</p></div>
        </div>
        <div class="p-5 space-y-5">
            <div class="grid grid-cols-2 sm:grid-cols-5 gap-4 text-sm">
                <div><p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Modalidad</p><p class="font-bold text-slate-900 dark:text-white">{{ modalidadActual ? `CREA ${modalidadActual.nombre}` : '—' }}</p></div>
                <div><p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Solicitante</p><p class="font-bold text-slate-900 dark:text-white truncate">{{ datos.nombre_completo || '—' }}</p></div>
                <div><p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Monto</p><p class="font-bold text-slate-900 dark:text-white">{{ datos.monto_solicitado ? fmt(datos.monto_solicitado) : '—' }}</p></div>
                <div><p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Plazo</p><p class="font-bold text-slate-900 dark:text-white">{{ datos.plazo_meses ? `${datos.plazo_meses} meses` : '—' }}</p></div>
                <div><p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Garantía</p><p class="font-bold text-slate-900 dark:text-white capitalize">{{ datos.tipo_garantia }}</p></div>
            </div>

            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">Checklist de documentos</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-1.5">
                    <div v-for="(label, tipo) in tiposDocRequeridos" :key="tipo" class="flex items-center gap-2 text-sm">
                        <CheckCircle2 v-if="docSubidos[tipo]" size="15" class="text-emerald-500 shrink-0" />
                        <XCircle v-else size="15" class="text-slate-300 dark:text-zinc-600 shrink-0" />
                        <span :class="docSubidos[tipo] ? 'text-slate-700 dark:text-zinc-300' : 'text-slate-400 dark:text-zinc-500'">{{ label }}</span>
                    </div>
                </div>
            </div>

            <div :class="['rounded-xl p-4 flex items-center gap-3', todosDocsCompletos ? 'bg-emerald-50 dark:bg-emerald-900/20' : 'bg-amber-50 dark:bg-amber-900/20']">
                <component :is="todosDocsCompletos ? CheckCircle2 : AlertTriangle" size="18" :class="todosDocsCompletos ? 'text-emerald-600' : 'text-amber-600'" />
                <p :class="['text-sm font-bold', todosDocsCompletos ? 'text-emerald-700 dark:text-emerald-400' : 'text-amber-700 dark:text-amber-400']">
                    {{ todosDocsCompletos ? 'Todos los documentos están completos. Ya puedes enviar tu solicitud.' : `Faltan ${totalDocsReq - totalDocsSubidos} documento(s) por subir.` }}
                </p>
            </div>
        </div>
    </div>

    <!-- ══════════ BARRA STICKY INFERIOR ══════════ -->
    <div class="fixed bottom-0 left-0 right-0 z-30 bg-white/95 dark:bg-[#1A0B11]/95 backdrop-blur-md border-t border-slate-200 dark:border-zinc-800 shadow-lg shadow-black/10">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 sm:gap-3">
            <div class="text-xs text-slate-500 dark:text-zinc-400 hidden sm:block">
                Documentos: <span :class="todosDocsCompletos ? 'text-emerald-600 font-bold' : 'text-amber-600 font-bold'">{{ totalDocsSubidos }}/{{ totalDocsReq }}</span>
            </div>
            <div class="flex flex-col-reverse sm:flex-row items-stretch sm:items-center gap-2 sm:gap-3 sm:ml-auto">
                <button @click="() => guardarBorrador()" :disabled="guardando || enviando"
                    class="flex items-center justify-center gap-2 px-5 py-2.5 bg-slate-100 dark:bg-zinc-800 text-slate-700 dark:text-zinc-300 font-bold rounded-xl hover:bg-slate-200 dark:hover:bg-zinc-700 disabled:opacity-50 transition-all text-sm min-h-[44px]">
                    <Loader2 v-if="guardando" size="15" class="animate-spin" />
                    <Save v-else size="15" />
                    Guardar borrador
                </button>
                <button @click="enviarSolicitud" :disabled="!puedeEnviar || enviando || guardando"
                    class="flex items-center justify-center gap-2 px-6 py-2.5 bg-gradient-to-r from-[#6B1938] to-[#4A0E22] hover:from-[#4A0E22] hover:to-[#2E0816] disabled:opacity-50 text-white font-bold rounded-xl shadow-lg shadow-[#6B1938]/25 transition-all text-sm min-h-[44px]">
                    <Loader2 v-if="enviando" size="15" class="animate-spin" />
                    <Send v-else size="15" />
                    Enviar solicitud
                </button>
            </div>
        </div>
        <p v-if="!puedeEnviar && !todosDocsCompletos" class="text-center text-[10px] text-amber-600 pb-1.5">Completa todos los documentos requeridos para poder enviar</p>
    </div>

</div>

</BeneficiarioLayout>
</template>
