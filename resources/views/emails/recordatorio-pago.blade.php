<p>Hola {{ $nombre }},</p>
<p>Te recordamos que tu cuota <strong>#{{ $cuota }}</strong> del crédito <strong>{{ $contrato }}</strong>
   vence el <strong>{{ $vence }}</strong>.</p>
<p>Monto a pagar: <strong>${{ number_format($monto, 2) }} MXN</strong></p>
<p>Puedes pagar en las oficinas del IYEM o en BBVA CIE con el convenio <strong>001776533</strong>.</p>
<p>Consulta tu crédito en <a href="{{ url('/') }}">{{ url('/') }}</a>.</p>
<p>Si ya realizaste tu pago, ignora este mensaje.</p>
<p>IYEM — Instituto Yucateco de Emprendedores</p>