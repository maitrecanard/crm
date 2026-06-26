<!DOCTYPE html>
<html lang="fr">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"></head>
<body style="margin:0;background:#ffffff;font-family:Arial,Helvetica,sans-serif;color:#1a1a1a;">
    <div style="max-width:560px;margin:0 auto;padding:24px;font-size:14px;line-height:1.6;">
        <p>Bonjour {{ $user->name }},</p>

        <p>
            Un accès partenaire vient d'être créé pour vous sur le CRM de
            <strong>{{ $societe }}</strong>. Il vous permet de suivre vos projets
            et de me transmettre les tâches à réaliser.
        </p>

        <p>Pour activer votre compte, définissez votre mot de passe :</p>

        <p style="text-align:center;margin:28px 0;">
            <a href="{{ $url }}" style="display:inline-block;background:#2563eb;color:#ffffff;text-decoration:none;padding:12px 24px;border-radius:6px;font-weight:bold;">
                Activer mon compte
            </a>
        </p>

        <p style="color:#6b7280;font-size:13px;">
            Ce lien est valable 7 jours. S'il a expiré, demandez-moi un nouvel envoi.
            Si vous n'êtes pas concerné, ignorez simplement cet e-mail.
        </p>

        <p>Bien cordialement,<br>
            <strong>{{ $societe }}</strong>
        </p>
    </div>
</body>
</html>
