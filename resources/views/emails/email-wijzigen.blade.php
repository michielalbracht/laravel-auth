@php($appName = config('auth-module.app_name'))
<!DOCTYPE html>
<html lang="nl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Bevestig je nieuwe e-mailadres</title>
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
                Je hebt gevraagd om je e-mailadres te wijzigen naar <strong>{{ $nieuwEmail }}</strong>. Klik op de knop hieronder om dit te bevestigen.
              </p>
              <table cellpadding="0" cellspacing="0" style="margin:0 auto 24px;">
                <tr>
                  <td style="background:#6366f1;border-radius:8px;">
                    <a href="{{ $bevestigUrl }}" style="display:inline-block;padding:12px 28px;font-size:15px;font-weight:600;color:#ffffff;text-decoration:none;">
                      E-mailadres bevestigen
                    </a>
                  </td>
                </tr>
              </table>
              <p style="margin:0 0 8px;font-size:13px;color:#9ca3af;line-height:1.6;">
                Deze link is 60 minuten geldig. Als je dit niet hebt aangevraagd, kun je deze e-mail negeren — je huidige e-mailadres blijft ongewijzigd.
              </p>
              <p style="margin:0;font-size:12px;color:#d1d5db;word-break:break-all;">
                Of kopieer deze URL: {{ $bevestigUrl }}
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
