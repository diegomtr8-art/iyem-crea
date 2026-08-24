export const municipios = [
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

export const regimenesFiscales = [
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

export const estadosCiviles = ['Soltero(a)','Casado(a)','Unión libre','Divorciado(a)','Viudo(a)'];
export const regMatrimonial = ['Sociedad conyugal','Separación de bienes'];

export const parentescosProhibidosArtesanal = ['padre','madre','hijo','hija','hermano','hermana','abuelo','abuela','nieto','nieta','tío','tía','sobrino','sobrina'];
export const parentescosAval = ['Cónyuge','Amigo(a)','Socio(a) de negocio','Vecino(a)', ...parentescosProhibidosArtesanal.map(p => p[0].toUpperCase() + p.slice(1)), 'Otro'];
