<p>Hola {{ $nombre }},</p>
<p>Te recordamos que tu cuota <strong>#{{ $cuota }}</strong> del crédito <strong>{{ $contrato }}</strong>
   vence el <strong>{{ $vence }}</strong>.</p>
<p>Monto a pagar: <strong>${{ number_format($monto, 2) }} MXN</strong></p>
<p>Si ya realizaste tu pago, ignora este mensaje.</p>
<p>IYEM — Instituto Yucateco de Emprendedores</p>