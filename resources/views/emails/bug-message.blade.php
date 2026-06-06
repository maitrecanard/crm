<!DOCTYPE html>
<html lang="fr">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"></head>
<body style="margin:0;background:#f4f4f5;font-family:Arial,Helvetica,sans-serif;color:#1a1a1a;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f5;padding:24px 0;">
        <tr><td align="center">
            <table width="560" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:8px;overflow:hidden;">
                <tr><td style="background:#4f46e5;padding:18px 28px;color:#ffffff;font-size:16px;font-weight:bold;">
                    {{ $societe }}
                </td></tr>
                <tr><td style="padding:28px;">
                    <p style="margin:0 0 14px;font-size:15px;">Bonjour,</p>
                    <p style="margin:0 0 14px;font-size:14px;line-height:1.6;">
                        Une mise à jour concernant votre demande <strong>« {{ $bug->titre }} »</strong> :
                    </p>
                    <div style="margin:0 0 16px;padding:14px 16px;background:#f8fafc;border-left:3px solid #4f46e5;border-radius:4px;font-size:14px;line-height:1.6;white-space:pre-line;">{{ $corps }}</div>
                    <p style="margin:18px 0 0;font-size:14px;line-height:1.6;">
                        Bien cordialement,<br><strong>{{ $societe }}</strong>
                    </p>
                </td></tr>
                <tr><td style="padding:16px 28px;background:#fafafa;color:#9ca3af;font-size:11px;">
                    Cet e-mail vous est adressé dans le cadre du suivi de votre projet. Merci de ne pas y répondre directement.
                </td></tr>
            </table>
        </td></tr>
    </table>
</body>
</html>
