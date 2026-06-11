<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 2.2cm 2cm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; line-height: 1.5; }
        h1 { font-size: 18px; margin: 0 0 4px; }
        h2 { font-size: 12px; margin: 18px 0 6px; border-bottom: 1px solid #d1d5db; padding-bottom: 3px; }
        .muted { color: #6b7280; }
        .parties { width: 100%; margin-top: 12px; }
        .parties td { vertical-align: top; width: 50%; padding-right: 12px; }
        .box { border: 1px solid #e5e7eb; border-radius: 6px; padding: 10px; }
        .label { font-size: 9px; text-transform: uppercase; letter-spacing: .04em; color: #9ca3af; }
        .objet { background: #f9fafb; border-radius: 6px; padding: 10px; margin-top: 10px; }
        .montant { font-size: 14px; font-weight: bold; }
        .conditions { white-space: pre-wrap; margin-top: 6px; }
        .sign { width: 100%; margin-top: 36px; }
        .sign td { width: 50%; vertical-align: top; padding-top: 30px; }
        .sign .line { border-top: 1px solid #9ca3af; padding-top: 4px; width: 80%; }
        .foot { margin-top: 24px; font-size: 9px; color: #9ca3af; text-align: center; }
    </style>
</head>
<body>
    <h1>Contrat de prestation de services</h1>
    <div class="muted">
        Référence : {{ $contrat->reference ?: '—' }} ·
        Date : {{ optional($contrat->date_contrat)->format('d/m/Y') ?: now()->format('d/m/Y') }}
    </div>

    <table class="parties">
        <tr>
            <td>
                <div class="box">
                    <div class="label">Le Prestataire</div>
                    <strong>{{ $vendeur['societe'] }}</strong> — {{ $vendeur['prenom'] }}<br>
                    {{ $vendeur['forme'] }}<br>
                    {{ $vendeur['adresse'] }}<br>
                    SIRET {{ $vendeur['siret'] }}<br>
                    {{ $vendeur['tva'] }}<br>
                    {{ $vendeur['email'] }} · {{ $vendeur['contact'] }}
                </div>
            </td>
            <td>
                <div class="box">
                    <div class="label">Le Client</div>
                    <strong>{{ $client->entreprise }}</strong><br>
                    @if($client->localite){{ $client->localite }}<br>@endif
                    @if($client->email){{ $client->email }}<br>@endif
                    @if($client->telephone){{ $client->telephone }}@endif
                </div>
            </td>
        </tr>
    </table>

    <div class="objet">
        <div class="label">Objet</div>
        {{ $contrat->objet }}
        @if($contrat->montant_ht !== null)
            <div class="montant" style="margin-top:6px;">
                Montant : {{ number_format((float) $contrat->montant_ht, 2, ',', ' ') }} € HT
            </div>
        @endif
    </div>

    <h2>Conditions</h2>
    <div class="conditions">{{ $contrat->conditions }}</div>

    <table class="sign">
        <tr>
            <td><div class="line">Le Prestataire<br><span class="muted">{{ $vendeur['prenom'] }}</span></div></td>
            <td><div class="line">Le Client<br><span class="muted">{{ $client->entreprise }}</span></div></td>
        </tr>
    </table>

    <div class="foot">{{ $vendeur['societe'] }} — SIRET {{ $vendeur['siret'] }} — {{ $vendeur['email'] }}</div>
</body>
</html>
