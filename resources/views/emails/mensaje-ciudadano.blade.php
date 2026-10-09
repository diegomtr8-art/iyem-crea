<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mensaje de ciudadano CREA</title>
<style>
  body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 20px; color: #333; }
  .wrapper { max-width: 600px; margin: 0 auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
  .header { background: #6B1938; padding: 24px 32px; }
  .header h1 { color: #fff; margin: 0; font-size: 20px; font-weight: 700; }
  .header p { color: #F4BAC8; margin: 4px 0 0; font-size: 13px; }
  .body { padding: 32px; }
  .field { margin-bottom: 20px; }
  .field label { display: block; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #6B1938; margin-bottom: 4px; }
  .field p { margin: 0; font-size: 15px; color: #1a1a1a; line-height: 1.5; }
  .mensaje-box { background: #fafaf8; border-left: 3px solid #6B1938; padding: 16px; border-radius: 0 4px 4px 0; white-space: pre-wrap; }
  .footer { background: #f5f0f2; padding: 16px 32px; font-size: 12px; color: #888; border-top: 1px solid #ede0e5; }
</style>
</head>
<body>
<div class="wrapper">
  <div class="header">
    <h1>Nuevo mensaje desde el portal</h1>
    <p>Programa CREA — IYEM Yucatán</p>
  </div>
  <div class="body">
    <div class="field">
      <label>Nombre</label>
      <p>{{ $mensaje->user?->name }}</p>
    </div>
    <div class="field">
      <label>Correo electrónico</label>
      <p><a href="mailto:{{ $mensaje->user?->email }}" style="color:#6B1938;">{{ $mensaje->user?->email }}</a></p>
    </div>
    <div class="field">
      <label>Asunto</label>
      <p>{{ $mensaje->asunto }}</p>
    </div>
    <div class="field">
      <label>Mensaje</label>
      <div class="mensaje-box">{{ $mensaje->mensaje }}</div>
    </div>
  </div>
  <div class="footer">
    Mensaje recibido el {{ $mensaje->created_at->setTimezone('America/Merida')->format('d/m/Y \a \l\a\s H:i') }} hrs (hora Mérida).
    Para responder, usa la dirección de reply-to: {{ $mensaje->user?->email }}.
  </div>
</div>
</body>
</html>
