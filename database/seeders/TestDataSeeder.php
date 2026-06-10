<?php

namespace Database\Seeders;

use App\Models\Acreditado;
use App\Models\Amortizacion;
use App\Models\AnuncioCiudadano;
use App\Models\Credito;
use App\Models\DocumentoSolicitud;
use App\Models\GestionCobranza;
use App\Models\ExpedienteJuridico;
use App\Models\ModalidadCrea;
use App\Models\SolicitudCredito;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make('password123');
        $modalidades = ModalidadCrea::all()->keyBy('nombre');
        $artesanal    = $modalidades['Artesanal']->id ?? 1;
        $sustentable  = $modalidades['Sustentable']->id ?? 2;
        $emprendedores= $modalidades['Emprendedores']->id ?? 3;

        // =====================================================================
        // CIUDADANO 1: Felipe Dzul Poot — Borrador (sin documentos)
        // =====================================================================
        $u1 = User::create([
            'name'              => 'Felipe Dzul Poot',
            'email'             => 'felipe.dzul@ejemplo.com',
            'password'          => $password,
            'tipo'              => 'ciudadano',
            'email_verified_at' => now(),
        ]);
        SolicitudCredito::create([
            'user_id'           => $u1->id,
            'nombre_completo'   => 'Felipe Dzul Poot',
            'curp'              => 'DUPE850312HYMZLT03',
            'rfc'               => 'DUPE850312AB1',
            'fecha_nacimiento'  => '1985-03-12',
            'sexo'              => 'M',
            'municipio'         => 'Valladolid',
            'direccion'         => 'Calle 40 No. 210 x 45 y 47, Centro',
            'telefono'          => '9851234567',
            'correo'            => 'felipe.dzul@ejemplo.com',
            'modalidad_id'      => $artesanal,
            'giro_comercial'    => 'Artesanías de henequén',
            'destino_credito'   => 'Compra de materiales y taller',
            'descripcion_negocio'=> 'Producción de hamacas y artesanías de henequén para venta local y turistas.',
            'monto_solicitado'  => 15000,
            'alta_sat'          => false,
            'mayahablante'      => true,
            'discapacidad'      => false,
            'estatus'           => 'Borrador',
        ]);

        // =====================================================================
        // CIUDADANO 2: Carmen Xiu Balam — Documentacion_Incompleta (docs con rechazo)
        // =====================================================================
        $u2 = User::create([
            'name'              => 'Carmen Xiu Balam',
            'email'             => 'carmen.xiu@ejemplo.com',
            'password'          => $password,
            'tipo'              => 'ciudadano',
            'email_verified_at' => now(),
        ]);
        $s2 = SolicitudCredito::create([
            'user_id'           => $u2->id,
            'nombre_completo'   => 'Carmen Xiu Balam',
            'curp'              => 'XIBC900615MYMXLR08',
            'rfc'               => 'XIBC900615MX1',
            'fecha_nacimiento'  => '1990-06-15',
            'sexo'              => 'F',
            'municipio'         => 'Tizimín',
            'direccion'         => 'Calle 50 No. 320, Col. Las Flores',
            'telefono'          => '9869876543',
            'correo'            => 'carmen.xiu@ejemplo.com',
            'modalidad_id'      => $sustentable,
            'giro_comercial'    => 'Producción orgánica de chile habanero',
            'destino_credito'   => 'Instalación de invernadero y riego por goteo',
            'descripcion_negocio'=> 'Cultivo y comercialización de chile habanero orgánico certificado, venta a mercados locales y hoteles.',
            'monto_solicitado'  => 28000,
            'alta_sat'          => true,
            'mayahablante'      => true,
            'discapacidad'      => false,
            'estatus'           => 'Documentacion_Incompleta',
            'observaciones'     => 'INE ilegible y comprobante de domicilio mayor a 3 meses. Favor de resubir documentos.',
        ]);
        // Documentos: algunos aprobados, algunos rechazados
        $docs2 = [
            ['tipo_documento' => 'ine_frente',            'estatus' => 'Rechazado', 'observacion' => 'Imagen borrosa, no se lee la información.'],
            ['tipo_documento' => 'ine_reverso',           'estatus' => 'Pendiente', 'observacion' => null],
            ['tipo_documento' => 'curp',                  'estatus' => 'Aprobado',  'observacion' => null],
            ['tipo_documento' => 'comprobante_domicilio', 'estatus' => 'Rechazado', 'observacion' => 'Comprobante mayor a 3 meses de antigüedad.'],
            ['tipo_documento' => 'foto_negocio',          'estatus' => 'Aprobado',  'observacion' => null],
        ];
        foreach ($docs2 as $d) {
            DocumentoSolicitud::create([
                'solicitud_id'    => $s2->id,
                'tipo_documento'  => $d['tipo_documento'],
                'nombre_original' => strtolower(str_replace('_', '-', $d['tipo_documento'])) . '-carmen.pdf',
                'ruta_archivo'    => 'solicitudes/placeholder.pdf',
                'estatus'         => $d['estatus'],
                'observacion'     => $d['observacion'],
            ]);
        }
        AnuncioCiudadano::create([
            'user_id' => $u2->id,
            'titulo'  => 'Documentación incompleta',
            'mensaje' => 'Algunos documentos requieren corrección. Tu INE está ilegible y el comprobante de domicilio está vencido.',
            'tipo'    => 'alerta',
        ]);

        // =====================================================================
        // CIUDADANO 3: José Cab Tun — En_Revision (docs: 3 aprobados, 2 pendientes)
        // =====================================================================
        $u3 = User::create([
            'name'              => 'José Cab Tun',
            'email'             => 'jose.cab@ejemplo.com',
            'password'          => $password,
            'tipo'              => 'ciudadano',
            'email_verified_at' => now(),
        ]);
        $s3 = SolicitudCredito::create([
            'user_id'           => $u3->id,
            'nombre_completo'   => 'José Cab Tun',
            'curp'              => 'CATJ780920HYMBNB05',
            'rfc'               => 'CATJ780920RT2',
            'fecha_nacimiento'  => '1978-09-20',
            'sexo'              => 'M',
            'municipio'         => 'Mérida',
            'direccion'         => 'Calle 25 No. 189 x 28 y 30, García Ginerés',
            'telefono'          => '9991234567',
            'correo'            => 'jose.cab@ejemplo.com',
            'modalidad_id'      => $emprendedores,
            'giro_comercial'    => 'Taquería y antojitos',
            'destino_credito'   => 'Compra de equipo de cocina y mobiliario',
            'descripcion_negocio'=> 'Taquería con 5 años de operación, ampliación del local y adquisición de equipo de refrigeración.',
            'monto_solicitado'  => 35000,
            'alta_sat'          => true,
            'mayahablante'      => false,
            'discapacidad'      => false,
            'estatus'           => 'En_Revision',
        ]);
        $docs3 = [
            ['tipo_documento' => 'ine_frente',            'estatus' => 'Aprobado',  'observacion' => null],
            ['tipo_documento' => 'ine_reverso',           'estatus' => 'Aprobado',  'observacion' => null],
            ['tipo_documento' => 'curp',                  'estatus' => 'Aprobado',  'observacion' => null],
            ['tipo_documento' => 'comprobante_domicilio', 'estatus' => 'Pendiente', 'observacion' => null],
            ['tipo_documento' => 'foto_negocio',          'estatus' => 'Pendiente', 'observacion' => null],
        ];
        foreach ($docs3 as $d) {
            DocumentoSolicitud::create([
                'solicitud_id'    => $s3->id,
                'tipo_documento'  => $d['tipo_documento'],
                'nombre_original' => strtolower(str_replace('_', '-', $d['tipo_documento'])) . '-jose.pdf',
                'ruta_archivo'    => 'solicitudes/placeholder.pdf',
                'estatus'         => $d['estatus'],
                'observacion'     => $d['observacion'],
            ]);
        }
        AnuncioCiudadano::create([
            'user_id' => $u3->id,
            'titulo'  => 'Tu solicitud está en revisión',
            'mensaje' => 'Un asesor CREA está revisando tu solicitud y documentos. Te notificaremos cualquier novedad.',
            'tipo'    => 'info',
        ]);

        // =====================================================================
        // CIUDADANO 4: Sofía Pech Ek — Aprobada (SIN crédito, para probar "Registrar Crédito")
        // =====================================================================
        $u4 = User::create([
            'name'              => 'Sofía Pech Ek',
            'email'             => 'sofia.pech@ejemplo.com',
            'password'          => $password,
            'tipo'              => 'ciudadano',
            'email_verified_at' => now(),
        ]);
        $s4 = SolicitudCredito::create([
            'user_id'           => $u4->id,
            'nombre_completo'   => 'Sofía Pech Ek',
            'curp'              => 'PEES950310MYMCKF01',
            'rfc'               => 'PEES950310HB3',
            'fecha_nacimiento'  => '1995-03-10',
            'sexo'              => 'F',
            'municipio'         => 'Izamal',
            'direccion'         => 'Calle 33 No. 450 x 34 y 36, Centro',
            'telefono'          => '9885551234',
            'correo'            => 'sofia.pech@ejemplo.com',
            'modalidad_id'      => $artesanal,
            'giro_comercial'    => 'Bordados y textiles mayas',
            'destino_credito'   => 'Compra de tela, hilos y máquinas de coser',
            'descripcion_negocio'=> 'Taller de bordados y textiles de diseño maya, venta en línea y ferias artesanales.',
            'monto_solicitado'  => 20000,
            'alta_sat'          => false,
            'mayahablante'      => true,
            'discapacidad'      => false,
            'estatus'           => 'Aprobada',
        ]);
        $docs4 = [
            'ine_frente', 'ine_reverso', 'curp', 'comprobante_domicilio', 'foto_negocio'
        ];
        foreach ($docs4 as $tipo) {
            DocumentoSolicitud::create([
                'solicitud_id'    => $s4->id,
                'tipo_documento'  => $tipo,
                'nombre_original' => strtolower(str_replace('_', '-', $tipo)) . '-sofia.pdf',
                'ruta_archivo'    => 'solicitudes/placeholder.pdf',
                'estatus'         => 'Aprobado',
            ]);
        }
        AnuncioCiudadano::create([
            'user_id' => $u4->id,
            'titulo'  => '¡Tu solicitud fue aprobada!',
            'mensaje' => '¡Felicidades Sofía! Tu solicitud de crédito ha sido aprobada. Pronto un asesor CREA te contactará.',
            'tipo'    => 'exito',
        ]);

        // =====================================================================
        // CIUDADANO 5: Miguel Caamal Dzib — Aprobada + Crédito Activo (portal Mi Crédito)
        // =====================================================================
        $u5 = User::create([
            'name'              => 'Miguel Caamal Dzib',
            'email'             => 'miguel.caamal@ejemplo.com',
            'password'          => $password,
            'tipo'              => 'ciudadano',
            'email_verified_at' => now(),
        ]);
        $acr5 = Acreditado::create([
            'nombre_completo'  => 'Miguel Caamal Dzib',
            'curp'             => 'CADM880520HYMPGB07',
            'rfc'              => 'CADM880520RX4',
            'sexo'             => 'H',
            'municipio'        => 'Mérida',
            'direccion_fiscal' => 'Calle 60 No. 430 x 53 y 55, Col. Centro',
            'correo'           => 'miguel.caamal@ejemplo.com',
        ]);
        $modalidadEmp = ModalidadCrea::findOrFail($emprendedores);
        $tasaOrd = 7.0;
        $monto5  = 30000;
        $plazo5  = 24;
        $credito5 = $acr5->creditos()->create([
            'modalidad_id'           => $emprendedores,
            'clave_contrato'         => 'CREA-2026-101',
            'monto_otorgado'         => $monto5,
            'plazo_meses'            => $plazo5,
            'fecha_entrega'          => '2026-01-15',
            'tasa_interes_ordinario' => $tasaOrd,
            'tasa_interes_moratorio' => 17.5,
            'estatus'                => 'Activo',
        ]);
        // Generar amortización
        $tasaMensual5 = ($tasaOrd / 100) / 12;
        $cuota5 = $monto5 * ($tasaMensual5 / (1 - pow(1 + $tasaMensual5, -$plazo5)));
        $saldo5 = $monto5;
        $fecha5 = Carbon::parse('2026-01-15');
        for ($i = 1; $i <= $plazo5; $i++) {
            $interes5 = round($saldo5 * $tasaMensual5, 2);
            $capital5 = ($i === $plazo5) ? round($saldo5, 2) : round($cuota5 - $interes5, 2);
            $cuotaMes5 = round($capital5 + $interes5, 2);
            $credito5->amortizaciones()->create([
                'numero_cuota'               => $i,
                'fecha_vencimiento'          => $fecha5->copy()->addMonths($i)->format('Y-m-d'),
                'saldo_insoluto'             => round($saldo5, 2),
                'capital_esperado'           => $capital5,
                'interes_ordinario_esperado' => $interes5,
                'cuota_fija'                 => $cuotaMes5,
                'pago_restante'              => ($i <= 3) ? 0 : $cuotaMes5,
                'estado'                     => ($i <= 3) ? 'Pagado' : 'Pendiente',
                'capital_pagado'             => ($i <= 3) ? $capital5 : 0,
                'interes_ordinario_pagado'   => ($i <= 3) ? $interes5 : 0,
                'interes_moratorio_pagado'   => 0,
                'interes_moratorio_generado' => 0,
            ]);
            $saldo5 -= $capital5;
        }
        $s5 = SolicitudCredito::create([
            'user_id'           => $u5->id,
            'nombre_completo'   => 'Miguel Caamal Dzib',
            'curp'              => 'CADM880520HYMPGB07',
            'rfc'               => 'CADM880520RX4',
            'fecha_nacimiento'  => '1988-05-20',
            'sexo'              => 'M',
            'municipio'         => 'Mérida',
            'direccion'         => 'Calle 60 No. 430 x 53 y 55, Col. Centro',
            'telefono'          => '9991122334',
            'correo'            => 'miguel.caamal@ejemplo.com',
            'modalidad_id'      => $emprendedores,
            'giro_comercial'    => 'Servicios de TI y soporte técnico',
            'destino_credito'   => 'Equipo de cómputo y herramientas de trabajo',
            'descripcion_negocio'=> 'Empresa de servicios de tecnología e informática para pequeñas empresas de Mérida.',
            'monto_solicitado'  => 30000,
            'alta_sat'          => true,
            'mayahablante'      => false,
            'discapacidad'      => false,
            'estatus'           => 'Aprobada',
            'acreditado_id'     => $acr5->id,
            'credito_id'        => $credito5->id,
        ]);
        AnuncioCiudadano::create([
            'user_id' => $u5->id,
            'titulo'  => '¡Tu crédito está activo!',
            'mensaje' => 'Hemos registrado tu crédito CREA (CREA-2026-101) por $30,000.00. Ya puedes ver tu tabla de amortización en Mi Crédito.',
            'tipo'    => 'exito',
        ]);
        AnuncioCiudadano::create([
            'user_id' => $u5->id,
            'titulo'  => 'Próximo pago',
            'mensaje' => 'Recuerda que tu próxima cuota vence el 15 de mayo de 2026. Mantén al día tu crédito.',
            'tipo'    => 'pago',
        ]);

        // =====================================================================
        // CIUDADANO 6: Diana Couoh Tzab — Rechazada
        // =====================================================================
        $u6 = User::create([
            'name'              => 'Diana Couoh Tzab',
            'email'             => 'diana.couoh@ejemplo.com',
            'password'          => $password,
            'tipo'              => 'ciudadano',
            'email_verified_at' => now(),
        ]);
        $s6 = SolicitudCredito::create([
            'user_id'           => $u6->id,
            'nombre_completo'   => 'Diana Couoh Tzab',
            'curp'              => 'COTD011205MMYNZNA5',
            'rfc'               => 'COTD011205AB5',
            'fecha_nacimiento'  => '2001-12-05',
            'sexo'              => 'F',
            'municipio'         => 'Progreso',
            'direccion'         => 'Calle 80 No. 150 x 29 y 31',
            'telefono'          => '9694567890',
            'correo'            => 'diana.couoh@ejemplo.com',
            'modalidad_id'      => $sustentable,
            'giro_comercial'    => 'Venta de artículos de segunda mano',
            'destino_credito'   => 'Capital de trabajo',
            'descripcion_negocio'=> 'Venta de ropa y electrodomésticos de segunda mano mediante plataformas digitales.',
            'monto_solicitado'  => 8000,
            'alta_sat'          => false,
            'mayahablante'      => false,
            'discapacidad'      => false,
            'estatus'           => 'Rechazada',
            'observaciones'     => 'El proyecto no cumple con los criterios de la modalidad Sustentable (debe tener impacto ambiental positivo). Se recomienda reevaluar la modalidad y reenviar la solicitud.',
        ]);
        $docs6 = ['ine_frente', 'ine_reverso', 'curp', 'comprobante_domicilio'];
        foreach ($docs6 as $tipo) {
            DocumentoSolicitud::create([
                'solicitud_id'    => $s6->id,
                'tipo_documento'  => $tipo,
                'nombre_original' => strtolower(str_replace('_', '-', $tipo)) . '-diana.pdf',
                'ruta_archivo'    => 'solicitudes/placeholder.pdf',
                'estatus'         => 'Aprobado',
            ]);
        }
        AnuncioCiudadano::create([
            'user_id' => $u6->id,
            'titulo'  => 'Tu solicitud no fue aprobada',
            'mensaje' => 'Lamentablemente tu solicitud no fue aprobada. El proyecto no cumple con los criterios de la modalidad Sustentable. Contáctanos para más información.',
            'tipo'    => 'error',
        ]);

        // =====================================================================
        // CIUDADANO 7: Elena Puc Chan — Portal sin solicitud
        // =====================================================================
        $u7 = User::create([
            'name'              => 'Elena Puc Chan',
            'email'             => 'elena.puc@ejemplo.com',
            'password'          => $password,
            'tipo'              => 'ciudadano',
            'email_verified_at' => now(),
        ]);
        AnuncioCiudadano::create([
            'user_id' => $u7->id,
            'titulo'  => 'Bienvenida al Portal CREA',
            'mensaje' => 'Bienvenida Elena. Puedes iniciar tu solicitud de crédito en la sección "Mi Solicitud". Revisa los requisitos y comienza hoy.',
            'tipo'    => 'info',
        ]);

        // =====================================================================
        // GESTIONES DE COBRANZA — múltiples por crédito moroso
        // =====================================================================
        $creditosMorosos = \App\Models\Credito::where('estatus', 'Moroso')
            ->with('acreditado')
            ->limit(5)
            ->get();

        $adminId = User::where('tipo', 'operativo')->first()?->id ?? 1;

        $tiposGestion = ['Llamada', 'WhatsApp', 'Visita', 'Email', 'Carta'];
        $resultados   = ['Contactado', 'No_Contactado', 'Promesa_Pago', 'Sin_Respuesta', 'Acuerdo_Alcanzado'];

        $gestionesEjemplo = [
            [
                'tipo_gestion'     => 'Llamada',
                'descripcion'      => 'Primer contacto telefónico para informar sobre cuotas vencidas.',
                'resultado'        => 'Contactado',
                'fecha_gestion'    => now()->subDays(30)->format('Y-m-d'),
            ],
            [
                'tipo_gestion'       => 'WhatsApp',
                'descripcion'        => 'Envío de estado de cuenta por WhatsApp, solicitud de pago urgente.',
                'resultado'          => 'Promesa_Pago',
                'fecha_gestion'      => now()->subDays(20)->format('Y-m-d'),
                'fecha_compromiso_pago' => now()->subDays(10)->format('Y-m-d'),
                'monto_comprometido' => null,
            ],
            [
                'tipo_gestion'     => 'Visita',
                'descripcion'      => 'Visita al domicilio del acreditado. No se encontró al titular.',
                'resultado'        => 'No_Contactado',
                'fecha_gestion'    => now()->subDays(12)->format('Y-m-d'),
            ],
            [
                'tipo_gestion'       => 'Llamada',
                'descripcion'        => 'Segunda llamada. Acreditado indica dificultades económicas, propone pago parcial.',
                'resultado'          => 'Acuerdo_Alcanzado',
                'fecha_gestion'      => now()->subDays(5)->format('Y-m-d'),
                'fecha_compromiso_pago' => now()->addDays(5)->format('Y-m-d'),
                'monto_comprometido' => null,
            ],
        ];

        foreach ($creditosMorosos as $idx => $credito) {
            $gestionesToCreate = ($idx === 0) ? $gestionesEjemplo : array_slice($gestionesEjemplo, 0, 2);
            foreach ($gestionesToCreate as $g) {
                $monto = isset($g['monto_comprometido']) ? ($g['resultado'] === 'Promesa_Pago' ? $credito->cuota_fija ?? 1200 : null) : null;
                GestionCobranza::create([
                    'credito_id'            => $credito->id,
                    'acreditado_id'         => $credito->acreditado_id,
                    'usuario_id'            => $adminId,
                    'tipo_gestion'          => $g['tipo_gestion'],
                    'descripcion'           => $g['descripcion'],
                    'resultado'             => $g['resultado'],
                    'fecha_gestion'         => $g['fecha_gestion'],
                    'fecha_compromiso_pago' => $g['fecha_compromiso_pago'] ?? null,
                    'monto_comprometido'    => $monto,
                ]);
            }
        }

        // =====================================================================
        // EXPEDIENTES JURÍDICOS — créditos con más de 90 días de mora
        // =====================================================================
        $masAtraso = \App\Models\Credito::where('estatus', 'Moroso')
            ->whereDoesntHave('expedienteJuridico')
            ->limit(4)
            ->get();

        $estatusJuridicos = ['En_Demanda', 'En_Platicas', 'Reestructurada', 'En_Demanda'];
        $tribunales = ['Juzgado Primero Civil', 'Juzgado Segundo Mercantil', 'Juzgado Cuarto Familiar', 'Juzgado Tercero Civil'];
        $juzgados   = ['Mérida, Yucatán', 'Mérida, Yucatán', 'Valladolid, Yucatán', 'Mérida, Yucatán'];

        foreach ($masAtraso as $idx => $credito) {
            ExpedienteJuridico::create([
                'credito_id'              => $credito->id,
                'acreditado_id'           => $credito->acreditado_id,
                'usuario_id'              => $adminId,
                'fecha_envio_juridico'    => now()->subDays(rand(30, 90))->format('Y-m-d'),
                'estatus'                 => $estatusJuridicos[$idx] ?? 'Pendiente',
                'tribunal'                => $tribunales[$idx] ?? 'Juzgado Civil',
                'juzgado'                 => $juzgados[$idx] ?? 'Mérida, Yucatán',
                'numero_expediente_judicial' => '2026-JM-' . str_pad(100 + $idx, 4, '0', STR_PAD_LEFT),
                'monto_reclamado'         => $credito->monto_otorgado * 1.3,
                'observaciones'           => 'Expediente enviado a jurídico por más de 90 días de cartera vencida. Se procedió a iniciar proceso de cobro judicial.',
            ]);
        }

        $this->command->info('Test data created successfully!');
        $this->command->info('Citizens: 7 users created (password: password123)');
        $this->command->info('Solicitudes: Borrador, Doc.Incompleta, En_Revision, Aprobada, Aprobada+Credito, Rechazada, Sin solicitud');
        $this->command->info('Gestiones de cobranza: Added to first 5 morosos');
        $this->command->info('Expedientes jurídicos: 4 new expedientes created');
    }
}
