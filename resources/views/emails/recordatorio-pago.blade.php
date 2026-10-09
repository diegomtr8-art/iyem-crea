<p>Hola, {{ $nombre }}:</p>
<p>Te recordamos que la cuota <strong>{{ $cuota }}</strong> de tu crédito CREA <strong>{{ $contrato }}</strong>
   vence el <strong>{{ $vence }}</strong>.</p>
<p>Monto a pagar: <strong>${{ number_format($monto, 2) }} MXN</strong></p>
<p>Puedes pagar:</p>
<ul>
    <li>En BBVA, con el convenio CIE <strong>001776533</strong>. Como concepto escribe tu número de contrato: <strong>{{ $contrato }}</strong>.</li>
    <li>En la caja de las oficinas del IYEM, de lunes a viernes de 9:00 a 14:00 h.</li>
</ul>
<p>Si tienes cuenta en el portal CREA, puedes ver el detalle de tu crédito en <a href="{{ url('/') }}">{{ url('/') }}</a>.</p>
<p>Si ya realizaste tu pago, no es necesario que hagas nada.</p>
<p>¿Dudas? Escríbenos a <a href="mailto:crea@iyemyucatan.com">crea@iyemyucatan.com</a> o llámanos al 999 941 2170.</p>
<p>Programa CREA · Instituto Yucateco de Emprendedores</p>
