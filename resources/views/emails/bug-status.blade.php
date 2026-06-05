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

                    <p style="margin:0 0 14px;font-size:14px;line-height:1.6;">{{ $message }}</p>

                    <table width="100%" cellpadding="0" cellspacing="0" style="margin:18px 0;border:1px solid #e5e7eb;border-radius:6px;">
                        <tr><td style="padding:12px 16px;font-size:13px;color:#6b7280;">Signalement</td>
                            <td style="padding:12px 16px;font-size:14px;font-weight:bold;text-align:right;">{{ $bug->titre }}</td></tr>
                        <tr><td style="padding:12px 16px;font-size:13px;color:#6b7280;border-top:1px solid #f3f4f6;">État</td>
                            <td style="padding:12px 16px;font-size:14px;font-weight:bold;text-align:right;border-top:1px solid #f3f4f6;">{{ $libelle }}</td></tr>
                        @if ($bug->project)
                        <tr><td style="padding:12px 16px;font-size:13px;color:#6b7280;border-top:1px solid #f3f4f6;">Projet</td>
                            <td style="padding:12px 16px;font-size:14px;text-align:right;border-top:1px solid #f3f4f6;">{{ $bug->project->titre }}</td></tr>
                        @endif
                    </table>

                    @if ($bug->description)
                        <p style="margin:0 0 6px;font-size:13px;color:#6b7280;">Détail du signalement :</p>
                        <p style="margin:0 0 16px;font-size:14px;line-height:1.6;white-space:pre-line;">{{ $bug->description }}</p>
                    @endif

                    <p style="margin:18px 0 0;font-size:14px;line-height:1.6;">
                        Nous restons à votre disposition.<br>
                        Bien cordialement,<br>
                        <strong>{{ $societe }}</strong>
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
