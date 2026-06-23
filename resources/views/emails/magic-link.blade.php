@php($appName = config('auth-module.app_name'))
<!DOCTYPE html>
<html lang="nl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Je inloglink</title>
</head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:system-ui,-apple-system,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="padding:40px 16px;">
    <tr>
      <td align="center">
        <table width="100%" cellpadding="0" cellspacing="0" style="max-width:520px;background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.1);">
          <!-- Header -->
          <tr>
            <td style="background:#6366f1;padding:32px;text-align:center;">
              <span style="font-size:22px;font-weight:700;color:#ffffff;">{{ $appName }}</span>
            </td>
          </tr>
          <!-- Body -->
          <tr>
            <td style="padding:32px;">
              <p style="margin:0 0 8px;font-size:16px;color:#111827;">Hoi {{ $naam }},</p>
              <p style="margin:0 0 24px;font-size:15px;color:#6b7280;line-height:1.6;">
                Klik op de knop hieronder om direct in te loggen — je hebt geen wachtwoord nodig.
              </p>
              <table cellpadding="0" cellspacing="0" style="margin:0 auto 24px;">
                <tr>
                  <td style="background:#6366f1;border-radius:8px;">
                    <a href="{{ $magicUrl }}" style="display:inline-block;padding:12px 28px;font-size:15px;font-weight:600;color:#ffffff;text-decoration:none;">
                      Inloggen
                    </a>
                  </td>
                </tr>
              </table>
              <!-- Code-alternatief -->
              <p style="margin:0 0 12px;font-size:14px;color:#6b7280;line-height:1.6;text-align:center;">
                Of voer deze code in op de inlogpagina:
              </p>
              <table cellpadding="0" cellspacing="0" style="margin:0 auto 24px;">
                <tr>
                  <td style="background:#f3f4f6;border-radius:8px;padding:14px 28px;">
                    <span style="font-size:30px;font-weight:700;letter-spacing:8px;color:#111827;font-family:'Courier New',monospace;">{{ $code }}</span>
                  </td>
                </tr>
              </table>
              <p style="margin:0 0 8px;font-size:13px;color:#9ca3af;line-height:1.6;">
                De link en code zijn 15 minuten geldig en kunnen één keer gebruikt worden. Als je geen inloglink hebt aangevraagd, kun je deze e-mail negeren.
              </p>
              <p style="margin:0;font-size:12px;color:#d1d5db;word-break:break-all;">
                Of kopieer deze URL: {{ $magicUrl }}
              </p>
            </td>
          </tr>
          <!-- Footer -->
          <tr>
            <td style="padding:16px 32px;border-top:1px solid #f3f4f6;text-align:center;">
              <p style="margin:0;font-size:12px;color:#d1d5db;">© {{ date('Y') }} {{ $appName }}</p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
