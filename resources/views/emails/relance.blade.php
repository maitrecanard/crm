<!DOCTYPE html>
<html lang="fr">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"></head>
<body style="margin:0;background:#ffffff;font-family:Arial,Helvetica,sans-serif;color:#1a1a1a;">
    <div style="max-width:560px;margin:0 auto;padding:24px;font-size:14px;line-height:1.6;">
        <p>Bonjour,</p>

        <p>
            Je me permets de revenir vers vous suite à mon message de la semaine dernière.
            Si le sujet est toujours d'actualité du côté de
            <strong>{{ $prospect->entreprise }}</strong>, je serais ravi d'en échanger
            quelques minutes — sans engagement.
        </p>

        <p>
            Dites-moi simplement ce qui vous arrange (un appel rapide ou un échange par e-mail),
            et je m'adapte à votre agenda.
        </p>

        <p>Bien cordialement,<br>
            <strong>{{ $vendeur['prenom'] }}</strong> — {{ $vendeur['societe'] }}
            @if (!empty($vendeur['contact']))<br><span style="color:#6b7280;">{{ $vendeur['contact'] }}</span>@endif
        </p>
    </div>
</body>
</html>
