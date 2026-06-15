<!DOCTYPE html>
<html lang="es" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="x-apple-disable-message-reformatting" />
    <title>{{ $subject ?? config('app.name') }}</title>
    <!--[if mso]>
    <noscript><xml><o:OfficeDocumentSettings><o:PixelsPerInch>96</o:PixelsPerInch></o:OfficeDocumentSettings></xml></noscript>
    <![endif]-->
    <style>
        * { box-sizing: border-box; }
        body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { -ms-interpolation-mode: bicubic; border: 0; }
        body { margin: 0 !important; padding: 0 !important; width: 100% !important; background-color: #EEF2F7; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, Helvetica, sans-serif; }
        a { color: #6B1938; }
        .wrapper { width: 100%; background-color: #EEF2F7; padding: 40px 16px; }
        .card { max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 8px 32px rgba(107,25,56,0.12), 0 2px 8px rgba(0,0,0,0.06); }
        /* Header */
        .header { background: linear-gradient(145deg, #6B1938 0%, #3D0D1F 60%, #2A0915 100%); padding: 36px 40px 32px; text-align: center; }
        /* Gold bar */
        .gold-bar { height: 4px; background: linear-gradient(90deg, #C47A10, #E8A020, #F5C842, #E8A020, #C47A10); }
        /* Icon */
        .icon-section { background-color: #ffffff; padding: 32px 40px 0; text-align: center; }
        .icon-outer { display: inline-block; background: linear-gradient(145deg, #6B1938, #4E1029); border-radius: 50%; width: 76px; height: 76px; text-align: center; vertical-align: middle; }
        /* Body */
        .body { padding: 20px 40px 40px; background-color: #ffffff; }
        h2.greeting { font-size: 26px; font-weight: 800; color: #0F172A; margin: 18px 0 10px; letter-spacing: -0.3px; }
        p.line { font-size: 15px; line-height: 1.7; color: #4B5563; margin: 0 0 14px; }
        /* Button */
        .btn-outer { text-align: center; margin: 28px 0 24px; }
        .btn { display: inline-block; padding: 16px 44px; background: linear-gradient(135deg, #6B1938 0%, #4E1029 100%); color: #ffffff !important; font-size: 15px; font-weight: 700; border-radius: 12px; text-decoration: none !important; letter-spacing: 0.2px; box-shadow: 0 4px 16px rgba(107,25,56,0.35); }
        /* Divider */
        .divider { border: none; border-top: 1px solid #E5EAF0; margin: 24px 0; }
        /* URL box */
        .url-note { font-size: 12px; color: #94A3B8; margin-bottom: 8px; }
        .url-box { display: block; word-break: break-all; font-size: 12px; color: #64748B; background: #F8FAFC; border: 1px solid #E2E8F0; border-left: 3px solid #6B1938; border-radius: 8px; padding: 10px 14px; margin: 6px 0 20px; }
        /* Outro */
        p.note { font-size: 13px; color: #94A3B8; line-height: 1.6; }
        /* Salutation */
        .salutation { margin-top: 28px; padding-top: 20px; border-top: 1px solid #F1F5F9; font-size: 14px; color: #6B7280; }
        /* Footer */
        .footer { background-color: #F8FAFC; border-top: 1px solid #E5EAF0; padding: 28px 40px; text-align: center; }
        .footer-logo-text { color: #374151; font-size: 13px; font-weight: 700; }
        .footer p { margin: 0; font-size: 12px; color: #9CA3AF; line-height: 1.7; }
        .footer a { color: #6B1938; text-decoration: none; }
        @media (max-width: 600px) {
            .header { padding: 28px 24px 24px; }
            .icon-section { padding: 24px 24px 0; }
            .body { padding: 18px 24px 32px; }
            .footer { padding: 20px 24px; }
            h2.greeting { font-size: 22px; }
        }
    </style>
</head>
<body>
<div class="wrapper">
<div class="card">

    {{-- ═══════════════════════════ HEADER ═══════════════════════════ --}}
    <div class="header">
        <table cellpadding="0" cellspacing="0" border="0" role="presentation" style="margin:0 auto;">
            <tr>
                <td style="padding-right:14px;vertical-align:middle;">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 40 40" width="44" height="44" role="img" aria-label="CREA">
                        <rect width="40" height="40" rx="9" fill="rgba(255,255,255,0.18)"/>
                        <rect x="6" y="27" width="7" height="10" rx="2" fill="rgba(255,255,255,0.45)"/>
                        <rect x="16.5" y="21" width="7" height="16" rx="2" fill="rgba(255,255,255,0.8)"/>
                        <rect x="27" y="14" width="7" height="23" rx="2" fill="#E8A020"/>
                    </svg>
                </td>
                <td style="vertical-align:middle;text-align:left;">
                    <div style="color:#ffffff;font-size:26px;font-weight:900;letter-spacing:-0.5px;line-height:1;">CREA</div>
                    <div style="color:rgba(255,255,255,0.55);font-size:10px;font-weight:600;letter-spacing:2px;text-transform:uppercase;margin-top:3px;">IYEM &nbsp;·&nbsp; Yucatán</div>
                </td>
            </tr>
        </table>
        <div style="margin-top:16px;color:rgba(255,255,255,0.35);font-size:11px;letter-spacing:1px;text-transform:uppercase;">
            Sistema de Crédito para Emprendedores
        </div>
    </div>

    {{-- ═══════════════════════════ GOLD BAR ══════════════════════════ --}}
    <div class="gold-bar"></div>

    {{-- ═══════════════════════════ ICON ═══════════════════════════════ --}}
    @isset($actionText)
    <div class="icon-section">
        <table cellpadding="0" cellspacing="0" border="0" role="presentation" style="margin:0 auto;">
            <tr>
                <td style="width:76px;height:76px;background:linear-gradient(145deg,#6B1938,#4E1029);border-radius:50%;text-align:center;vertical-align:middle;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-top:22px;">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                    </svg>
                </td>
            </tr>
        </table>
    </div>
    @endisset

    {{-- ═══════════════════════════ BODY ═══════════════════════════════ --}}
    <div class="body">

        {{-- Saludo --}}
        <h2 class="greeting">{{ $greeting ?? '¡Hola!' }}</h2>

        {{-- Líneas de introducción --}}
        @foreach ($introLines as $line)
            <p class="line">{{ $line }}</p>
        @endforeach

        {{-- Botón CTA --}}
        @isset($actionText)
            <div class="btn-outer">
                <!--[if mso]>
                <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" href="{{ $actionUrl }}" style="height:50px;v-text-anchor:middle;width:220px;" arcsize="24%" strokecolor="#4E1029" fillcolor="#6B1938">
                    <w:anchorlock/>
                    <center style="color:#ffffff;font-family:sans-serif;font-size:15px;font-weight:bold;">{{ $actionText }}</center>
                </v:roundrect>
                <![endif]-->
                <!--[if !mso]><!-->
                <a href="{{ $actionUrl }}" class="btn" target="_blank">
                    {{ $actionText }}
                </a>
                <!--<![endif]-->
            </div>

            <hr class="divider" />

            <p class="url-note">Si el botón no funciona, copia y pega el siguiente enlace en tu navegador:</p>
            <span class="url-box">{{ $actionUrl }}</span>
        @endisset

        {{-- Líneas de cierre --}}
        @foreach ($outroLines as $line)
            <p class="note">{{ $line }}</p>
        @endforeach

        {{-- Despedida --}}
        <p class="salutation">
            {{ $salutation ?? 'Equipo CREA — Instituto Yucateco de Emprendedores' }}
        </p>

    </div>

    {{-- ═══════════════════════════ FOOTER ════════════════════════════ --}}
    <div class="footer">
        <table cellpadding="0" cellspacing="0" border="0" role="presentation" style="margin:0 auto 14px;">
            <tr>
                <td style="padding-right:8px;vertical-align:middle;">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 28 28" width="28" height="28">
                        <rect width="28" height="28" rx="6" fill="#6B1938" fill-opacity="0.1"/>
                        <rect x="4" y="19" width="5" height="7" rx="1.5" fill="#6B1938" fill-opacity="0.4"/>
                        <rect x="11.5" y="15" width="5" height="11" rx="1.5" fill="#6B1938" fill-opacity="0.7"/>
                        <rect x="19" y="10" width="5" height="16" rx="1.5" fill="#E8A020"/>
                    </svg>
                </td>
                <td style="vertical-align:middle;text-align:left;">
                    <div style="color:#374151;font-size:13px;font-weight:700;line-height:1.2;">Instituto Yucateco de Emprendedores</div>
                    <div style="color:#9CA3AF;font-size:11px;letter-spacing:0.5px;margin-top:2px;">Programa CREA &nbsp;·&nbsp; Gobierno del Estado de Yucatán</div>
                </td>
            </tr>
        </table>
        <p>
            <a href="mailto:crea@iyemyucatan.com">crea@iyemyucatan.com</a>
            &nbsp;&nbsp;·&nbsp;&nbsp;
            <a href="https://crea.iyemyucatan.com">crea.iyemyucatan.com</a>
        </p>
        <p style="margin-top:10px;font-size:11px;color:#D1D5DB;">
            Este correo fue generado automáticamente. Por favor no respondas a este mensaje.
        </p>
    </div>

</div>
</div>
</body>
</html>
