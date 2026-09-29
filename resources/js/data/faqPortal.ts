
export interface FaqPortalItem {
    categoria: string;
    pregunta: string;
    respuesta: string;
}

export const faqPortal: FaqPortalItem[] = [
    // Categoría 1 · Sobre CREA
    {
        categoria: 'Sobre CREA',
        pregunta: '¿Qué es CREA?',
        respuesta:
            'CREA (Créditos para el Renacimiento de Emprendedores y Artesanos) es un programa del Instituto Yucateco de Emprendedores (IYEM) que ofrece financiamiento a personas emprendedoras, artesanas y con proyectos sustentables de Yucatán, para impulsar y fortalecer sus negocios y talleres.',
    },
    {
        categoria: 'Sobre CREA',
        pregunta: '¿Qué modalidades de crédito existen?',
        respuesta:
            'Hay tres: Artesanal, Sustentable y Emprendedores. Cada una tiene su propia tasa de interés, monto y requisitos. Al iniciar tu solicitud en el portal, el sistema te muestra las condiciones de cada modalidad antes de elegir.',
    },
    {
        categoria: 'Sobre CREA',
        pregunta: '¿Cómo solicito un crédito?',
        respuesta:
            'Entra a tu portal, ve a Solicitar crédito y completa el formulario paso por paso: datos personales, datos de tu negocio, modalidad y documentos. Cuando termines, tu solicitud pasa a revisión y puedes seguir su estado en Mi expediente.',
    },
    {
        categoria: 'Sobre CREA',
        pregunta: '¿Cómo sé en qué estado está mi solicitud?',
        respuesta:
            'En la sección Mi expediente del portal puedes ver el estado de tu solicitud y los datos que capturaste. Si falta algún documento o dato, el IYEM se pondrá en contacto contigo.',
    },

    // Categoría 2 · Tu cuota y tus pagos
    {
        categoria: 'Tu cuota y tus pagos',
        pregunta: '¿Cómo se calcula mi cuota?',
        respuesta:
            'Tu cuota se calcula con el monto del crédito, el plazo en meses y la tasa de interés anual de tu modalidad. Cada cuota incluye una parte que abona a tu capital (lo que pediste prestado) y una parte de interés. Puedes ver el desglose cuota por cuota en Mi crédito, en la tabla de amortización.',
    },
    {
        categoria: 'Tu cuota y tus pagos',
        pregunta: '¿Dónde y cómo pago mi cuota?',
        respuesta:
            'En Mi crédito aparecen los datos para pagar: horario de caja, correo y WhatsApp de contacto. Conserva siempre tu recibo de pago.',
    },
    {
        categoria: 'Tu cuota y tus pagos',
        pregunta: '¿Puedo pagar más de lo que me toca en mi cuota?',
        respuesta:
            'Sí. Si pagas más que tu cuota, el excedente se aplica como abono a capital. Según el caso, puedes elegir cómo se aplica: adelantar cuotas, reducir el monto de tus cuotas o reducir el plazo del crédito. Al final recibes un recibo que indica qué opción se aplicó.',
    },
    {
        categoria: 'Tu cuota y tus pagos',
        pregunta: '¿Puedo liquidar mi crédito antes de tiempo?',
        respuesta:
            'Sí. Acércate a tu asesor para que te indique el monto exacto de liquidación a la fecha.',
    },
    {
        categoria: 'Tu cuota y tus pagos',
        pregunta: '¿Dónde veo mi historial de pagos y mi estado de cuenta?',
        respuesta:
            'En tu portal puedes consultar tus pagos registrados y descargar tu estado de cuenta y tus recibos en PDF.',
    },

    // Categoría 3 · Si me atraso
    {
        categoria: 'Si me atraso',
        pregunta: '¿Qué pasa si no pago a tiempo?',
        respuesta:
            'Tienes 5 días de gracia después de la fecha de vencimiento de tu cuota, sin ningún cargo. Pasado ese plazo, se genera un interés moratorio sobre lo que te falta por pagar de esa cuota, contando los días de atraso. Además, si tienes una cuota vencida sin cubrir, tu crédito pasa a estado Moroso.',
    },
    {
        categoria: 'Si me atraso',
        pregunta: '¿Cómo se calcula el interés moratorio?',
        respuesta:
            'Se calcula sobre el saldo de la cuota que no pagaste, con la tasa moratoria de tu modalidad y por cada día de atraso. Mientras más tardes en pagar, más crece. Puedes ver la mora acumulada de tu crédito en Mi crédito.',
    },
    {
        categoria: 'Si me atraso',
        pregunta: '¿Qué significa "Moroso" y cómo dejo de estarlo?',
        respuesta:
            'Tu crédito se marca como Moroso cuando tienes al menos una cuota vencida sin pagar. Deja de estarlo cuando regularizas tus pagos vencidos, incluida la mora generada.',
    },
    {
        categoria: 'Si me atraso',
        pregunta: 'No puedo pagar, ¿qué hago?',
        respuesta:
            'Acércate a tu asesor antes de que se venza la cuota para platicar tu situación y ver qué opciones existen.',
    },

    // Categoría 4 · Contacto y ayuda
    {
        categoria: 'Contacto y ayuda',
        pregunta: '¿Cómo contacto a mi asesor o al IYEM?',
        respuesta:
            'Puedes escribir al correo y al WhatsApp que aparecen en Mi crédito, o acudir en el horario de atención de caja. Ten a la mano tu nombre completo y tu número de crédito.',
    },
    {
        categoria: 'Contacto y ayuda',
        pregunta: 'Olvidé mi contraseña o no puedo entrar a mi portal.',
        respuesta:
            'En la pantalla de inicio de sesión usa "¿Olvidaste tu contraseña?" para restablecerla.',
    },
];
