### Corrección en el archivo del componente Vue (estatusConfig.Rechazada)
### Estuve revisando el código y encontré un detalle en el objeto estatusConfig.Rechazada:

// Antes
Rechazada: {
    label: 'Solicitud Rechazada',
    color: 'text-[#6B1938] dark:text-[#f4a8c4] dark:text-red-400',
    bg: 'bg-[#6B1938]/5 dark:bg-[#6B1938]/10',
    icon: XCircle,
    desc: 'Tu solicitud no pudo ser aprobada en esta ocasión. Revisa las observaciones para más información.',
},

// Después
Rechazada: {
    label: 'Solicitud Rechazada',
    color: 'text-[#6B1938] dark:text-[#f4a8c4]',
    bg: 'bg-[#6B1938]/5 dark:bg-[#6B1938]/10',
    icon: XCircle,
    desc: 'Tu solicitud no pudo ser aprobada en esta ocasión. Revisa las observaciones para más información.',
},

Básicamente había dos clases de color repetidas para el modo oscuro (dark:text-[#f4a8c4] y dark:text-red-400). Cuando dejamos dos clases que hacen lo mismo, Tailwind se confunde y solo aplica una, así que quité la de red-400 para asegurarme de que se mantenga el tono rosa/vino personalizado que estamos usando en toda la interfaz.

#########################################################################################################

### Optimización de Enlaces en Acciones de Anuncios
### Estuve revisando el marcado del template y encontré esto en la sección donde se renderizan las acciones de los anuncios:

<!-- Antes -->
<a v-if="anuncio.url_accion" :href="anuncio.url_accion"
    class="text-[10px] font-bold text-[#6B1938] dark:text-[#f4a8c4] hover:underline">
    Ver más →
</a>

<!-- Después -->
<Link :href="anuncio.url_accion" class="text-[10px] font-bold text-[#6B1938] dark:text-[#f4a8c4] hover:underline" v-if="anuncio.url_accion">
    Ver más →
</Link>

Estábamos usando la etiqueta nativa <a>, lo que hacía que el navegador recargara la página completa al hacer clic en una acción de anuncio. Al cambiarlo a <Link> de Inertia.js (el cual ya teníamos importado en el script), la navegación dentro del portal se vuelve SPA, siendo instantánea y sin parpadeos.

#########################################################################################################

### Manejo de Respaldo para Colores de Iconos en Anuncios
### Estuve revisando el manejo de tipos de anuncios y noté este detalle al aplicar clases al icono:

<!-- Antes -->
<component :is="tipoAnuncio[anuncio.tipo]?.icon ?? Info" :class="['shrink-0 mt-0.5 size-4', tipoIconColor[anuncio.tipo]]" />

// En <script setup> agregué un helper para valor por defecto:
const getIconColor = (tipo: string) => tipoIconColor[tipo] ?? 'text-slate-600 dark:text-zinc-400';

<!-- Después en el template -->
<component :is="tipoAnuncio[anuncio.tipo]?.icon ?? Info" :class="['shrink-0 mt-0.5 size-4', getIconColor(anuncio.tipo)]" />

Si el backend envía un anuncio con un tipo nuevo o no contemplado en el objeto tipoIconColor (por ejemplo 'recordatorio'), la propiedad devuelta era undefined. Esto hacía que el icono se mostrara sin color ni contraste. Con la función getIconColor, nos aseguramos de que siempre tenga un color neutral de respaldo si el tipo no coincide.

#########################################################################################################

### Manejo Seguro de Estados Desconocidos en Solicitudes
### Estuve revisando la variable computada que obtiene la configuración del estado de la solicitud y encontré esto:

// Antes
const cfg = computed(() => props.solicitud ? estatusConfig[props.solicitud.estatus] : null);

// Después
const cfg = computed(() => {
    if (!props.solicitud) return null;
    return estatusConfig[props.solicitud.estatus] ?? {
        label: props.solicitud.estatus,
        color: 'text-slate-700 dark:text-zinc-300',
        bg: 'bg-slate-100 dark:bg-zinc-800',
        icon: Info,
        desc: 'Tu solicitud se encuentra en proceso de revisión.',
    };
});

Si el backend llega a enviar un valor en solicitud.estatus que no esté explícitamente definido en nuestro diccionario estatusConfig (por ejemplo Cancelada o En_Espera), cfg.value devolvía undefined. Esto hacía que la aplicación colapsara al intentar renderizar <component :is="cfg.icon"> o acceder a cfg.bg. Con este cambio nos aseguramos de que, si llega un estado desconocido, el sistema muestre una tarjeta con un estilo neutral en lugar de tirar un error en pantalla.

#########################################################################################################

### Ajuste de Contraste y Paleta en la Tarjeta de Crédito Activo

<!-- Antes -->
<Link :href="route('portal.credito')" class="flex items-center gap-2 px-4 py-2.5 bg-white text-[#6B1938] dark:text-[#f4a8c4] font-bold rounded-xl hover:bg-red-50 transition-colors text-sm shrink-0">
    Ver detalle <ChevronRight size="16"/>
</Link>

<!-- Después -->
<Link :href="route('portal.credito')" class="flex items-center gap-2 px-4 py-2.5 bg-white text-[#6B1938] font-bold rounded-xl hover:bg-rose-50 transition-colors text-sm shrink-0">
    Ver detalle <ChevronRight size="16"/>
</Link>

Dado que el contenedor del botón es una tarjeta con fondo degradado oscuro, el botón blanco no requiere la clase dark:text-[#f4a8c4] (ya que el texto sobre fondo blanco siempre debe ser oscuro #6B1938 para mantener buen contraste). Además, cambié el efecto hover a hover:bg-rose-50 para armonizar con la paleta guinda/rosa del proyecto.

#########################################################################################################

### Actualizaciones en Expediente.vue (Columna Derecha / Tarjeta de Documentos)
Renderizado dinámico de archivos subidos (v-for="doc in documentos"): Se vinculó cada tarjeta al estilo de su estado usando docEstilo[doc.estatus] (Aprobado, Pendiente, Rechazado). Se agregó enlace externo (<a :href="doc.url">) para abrir el archivo subido en una nueva pestaña. Se condicionó el mensaje de observación (doc.observacion) para que solo se muestre si el documento fue marcado como Rechazado.

Listado de documentos pendientes (<template v-for="...">): Se comparan los tipos_documentos requeridos contra los que ya están subidos (documentos). Si un tipo no existe en la lista de subidos, se muestra una tarjeta tenue (opacidad reducida) marcándolo como "Pendiente de subir".

Botón de acción condicional (<Link>): Se agregó la validación solicitud && ... para mostrar el botón "Gestionar Documentos" únicamente si la solicitud está en estado Borrador o Documentacion_Incompleta.

#########################################################################################################

### Adaptación Móvil en Tablas de Amortización y Pagos (MiCredito.vue)
Dispositivos probados: iPhone SE (375px), iPhone 14 Pro Max (430px)

Gravedad: Alta

Problema Detectado
La tabla de amortización y el historial de pagos mantenían su formato de tabla tradicional HTML en pantallas pequeñas. En móviles, esto obligaba al usuario a hacer un scroll horizontal incómodo dentro de la vista, haciendo que fuera muy fácil perder la noción de a qué cuota o fecha pertenecía cada monto. Además, los botones de los Tabs no contaban con la altura mínima recomendada para interacción táctil, dificultando la navegación con el pulgar.

Solución Aplicada
Se implementó un patrón de Renderizado Dual Responsivo mediante las clases utilitarias de Tailwind (block sm:hidden y hidden sm:block):

Transformación a Tarjetas en Móvil (< sm): En lugar de forzar la tabla horizontal, se oculta el elemento <table> y se desglosa cada cuota/pago en tarjetas (cards) verticales. Esto acomoda los datos (capital, interés, vencimiento y total) en un layout limpio de arriba a abajo que no requiere scroll lateral.

Preservación de Tabla en Escritorio (>= sm): Se conserva la estructura de tabla con overflow-x-auto únicamente para tablets y pantallas de escritorio donde la anchura lo permite.

Optimización de Interacción Táctil (Touch Targets): Se agregó min-h-[44px] a los botones de selección de pestañas para cumplir con la guía de accesibilidad e interfaz humana de iOS/Android.

Implementación clave en template:

<!-- 1. Botones de Tab con área de toque adecuada para móvil -->
<button @click="activeTab = 'tabla'"
    :class="[..., 'min-h-[44px] flex items-center justify-center']">
    Tabla de Amortización
</button>

<!-- 2. Tarjetas Móviles (< sm) -->
<div class="block sm:hidden divide-y divide-slate-100 dark:divide-zinc-800">
    <div v-for="item in credito.tabla" :key="item.numero_cuota" class="p-4 space-y-3">
        <!-- Desglose vertical en formato card -->
    </div>
</div>

<!-- 3. Tabla tradicional visible solo en pantallas medianas o superiores (>= sm) -->
<div class="hidden sm:block overflow-x-auto">
    <table class="w-full text-left border-collapse text-sm min-w-[640px]">
        <!-- Cabecera y cuerpo de la tabla -->
    </table>
</div>

#########################################################################################################

### Corrección del Modal de Liquidación Anticipada para Dispositivos Móviles
Estuve revisando el comportamiento del modal en pantallas móviles y encontré que se desbordaba verticalmente y el fondo oscuro no cubría la parte superior de la interfaz:

HTML

<!-- Antes -->
<div v-if="mostrarLiquidacion" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-zinc-900 rounded-3xl max-w-md w-full p-8 shadow-2xl">
        <!-- Contenido del modal -->
    </div>
</div>

<!-- Después -->
<Teleport to="body">
    <div 
        v-if="mostrarLiquidacion" 
        class="fixed inset-0 z-[9999] bg-black/80 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto"
    >
        <div class="relative w-full max-w-md my-auto rounded-3xl bg-zinc-900 border border-zinc-800 p-6 shadow-2xl overflow-hidden flex flex-col max-h-[85vh]">
            <!-- Header Fijo -->
            <div class="flex items-center justify-between pb-4 mb-4 border-b border-zinc-800 shrink-0">
                <h3 class="font-black text-white flex items-center gap-2 text-base sm:text-lg">
                    <Calculator size="20" class="text-blue-500" /> Liquidación Anticipada
                </h3>
                <button 
                    @click="mostrarLiquidacion = false" 
                    class="text-zinc-400 hover:text-white transition-colors p-1"
                >
                    <X size="20" />
                </button>
            </div>

            <!-- Cuerpo de datos con Scroll Interno -->
            <div class="overflow-y-auto pr-1 space-y-4">
                <!-- Contenido del modal -->
            </div>
        </div>
    </div>
</Teleport>

Causa y Solución
Anteriormente, el modal se renderizaba dentro de un contenedor local con restricciones de posicionamiento y transformaciones, lo que provocaba que el fondo y el cuerpo se solaparan de forma extraña con el header superior en dispositivos móviles, además de salirse de la pantalla por la parte inferior. Al implementar <Teleport to="body">, elevamos el contexto de apilamiento a z-[9999], aplicamos una altura máxima estricta de max-h-[85vh] y habilitamos scroll interno (overflow-y-auto). Con esto aseguramos que el modal se posicione correctamente cubriendo todo el viewport de manera fluida y sin perder información del desglose financiero.

#########################################################################################################

### Estilo Dinámico y Compatibilidad Dark Mode en Tarjetas de Documentos (Expediente.vue)
Problema Detectado
Las tarjetas de los documentos subidos no contaban con soporte completo de adaptabilidad y bordes para el modo oscuro (dark). Esto provocaba que al alternar el tema visual, los fondos perdieran contraste o los bordes resaltaran de forma agresiva rompiendo la estética limpia del portal.

Solución Aplicada
Se actualizó el diccionario de estilos docEstilo incorporando clases específicas para fondos translúcidos oscuros (dark:bg-red-900/10, dark:bg-green-900/10, etc.) y sus respectivos bordes sutiles:

// Antes
const docEstilo: Record<string, { bg: string; text: string; icon: any }> = {
    Aprobado:  { bg: 'bg-green-50', text: 'text-green-700', icon: CheckCircle2 },
    Pendiente: { bg: 'bg-amber-50', text: 'text-amber-700', icon: Clock },
    Rechazado: { bg: 'bg-red-50', text: 'text-red-700', icon: XCircle },
};

// Después
const docEstilo: Record<string, { bg: string; text: string; icon: any }> = {
    Aprobado:  { bg: 'bg-green-50 dark:bg-green-900/10 border-green-200 dark:border-green-800/40', text: 'text-green-700 dark:text-green-400', icon: CheckCircle2 },
    Pendiente: { bg: 'bg-amber-50 dark:bg-amber-900/10 border-amber-200 dark:border-amber-800/40', text: 'text-amber-700 dark:text-amber-400', icon: Clock },
    Rechazado: { bg: 'bg-red-50 dark:bg-red-900/10 border-red-200 dark:border-red-800/40', text: 'text-red-700 dark:text-red-400', icon: XCircle },
};

#########################################################################################################

### Estuve revisando el componente de la solicitud y encontré este detalle en los textos dinámicos largos:

<!-- Antes -->
<div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-5">
    <div>
        <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Giro Comercial</p>
        <p class="font-semibold text-slate-900 dark:text-white">{{ solicitud.giro_comercial || '—' }}</p>
    </div>
    <div class="sm:col-span-2">
        <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Descripción del Negocio / Proyecto</p>
        <p class="font-semibold text-slate-900 dark:text-white leading-relaxed">{{ solicitud.descripcion_negocio || '—' }}</p>
    </div>
    <div>
        <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Alta SAT</p>
        <span :class="['px-3 py-1.5 rounded-xl text-xs font-bold border', solicitud.alta_sat ? 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 border-emerald-200 dark:border-emerald-800/40' : 'bg-slate-100 dark:bg-zinc-800 text-slate-500 border-slate-200 dark:border-zinc-700']">
            {{ solicitud.alta_sat ? '✓ Dado de alta en SAT' : 'No dado de alta en SAT' }}
        </span>
    </div>
    <div>
        <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Solicitud registrada el</p>
        <p class="font-semibold text-slate-900 dark:text-white">{{ solicitud.created_at }}</p>
    </div>
</div>

<!-- Después -->
<div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-5">
    <div>
        <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Modalidad</p>
        <p class="font-semibold text-slate-900 dark:text-white break-words">{{ solicitud.modalidad || '—' }}</p>
    </div>
    <div>
        <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Giro Comercial</p>
        <p class="font-semibold text-slate-900 dark:text-white break-words">{{ solicitud.giro_comercial || '—' }}</p>
    </div>
    <div class="sm:col-span-2">
        <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Destino del Crédito</p>
        <p class="font-semibold text-slate-900 dark:text-white break-words">{{ solicitud.destino_credito || '—' }}</p>
    </div>
    <div class="sm:col-span-2">
        <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Descripción del Negocio / Proyecto</p>
        <p class="font-semibold text-slate-900 dark:text-white leading-relaxed break-words">{{ solicitud.descripcion_negocio || '—' }}</p>
    </div>
    <div>
        <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Alta SAT</p>
        <span :class="['px-3 py-1.5 rounded-xl text-xs font-bold border inline-block', solicitud.alta_sat ? 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 border-emerald-200 dark:border-emerald-800/40' : 'bg-slate-100 dark:bg-zinc-800 text-slate-500 border-slate-200 dark:border-zinc-700']">
            {{ solicitud.alta_sat ? '✓ Dado de alta en SAT' : 'No dado de alta en SAT' }}
        </span>
    </div>
    <div>
        <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Solicitud registrada el</p>
        <p class="font-semibold text-slate-900 dark:text-white break-words">{{ solicitud.created_at }}</p>
    </div>
</div>

En viewports móviles o contenedores estrechos, las cadenas de texto largas o sin espacios intermedios (como las entradas continuas en los campos de texto) ignoraban los límites de la tarjeta y provocaban un desbordamiento horizontal (overflow). Al agregar la clase de utilidad break-words a los elementos de texto (<p>) y asegurar un comportamiento en bloque (inline-block) para las etiquetas de estado, forzamos al navegador a romper las palabras largas correctamente, manteniendo el contenido perfectamente contenido dentro de los márgenes de la tarjeta.

#########################################################################################################

### Estuve revisando el componente de la solicitud y encontré este detalle en los datos personales de la vista de expediente digital:
Causa y Solución
En pantallas medianas y grandes (sm:grid-cols-2), la estructura estaba limitada a dos columnas fijas. Si un usuario cuenta con un nombre completo muy extenso (incluyendo tres nombres y apellidos compuestos), el texto corría el riesgo de comprimirse de más o provocar desbordamientos (overflow), al igual que cadenas continuas y rígidas como la CURP. Para solucionarlo, ampliamos el contenedor del nombre completo a todo el ancho (sm:col-span-2), aplicamos break-words para permitir saltos de línea limpios en textos largos, y agregamos break-all en la CURP para asegurar un ajuste perfecto sin romper las tarjetas de la interfaz.