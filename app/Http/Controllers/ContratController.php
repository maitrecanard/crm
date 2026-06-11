<?php

namespace App\Http\Controllers;

use App\Models\Contrat;
use App\Models\Prospect;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ContratController extends Controller
{
    /** Génère un contrat à partir du modèle de conditions courant. */
    public function store(Request $request, Prospect $client)
    {
        $data = $request->validate([
            'objet'        => ['required', 'string', 'max:1000'],
            'montant_ht'   => ['nullable', 'numeric', 'min:0'],
            'reference'    => ['nullable', 'string', 'max:255'],
            'date_contrat' => ['nullable', 'date'],
        ]);

        $client->contrats()->create($data + [
            'reference'    => $data['reference'] ?? 'C-'.now()->format('Ymd').'-'.Str::upper(Str::random(4)),
            'date_contrat' => $data['date_contrat'] ?? now()->toDateString(),
            'conditions'   => Setting::get('contrat_conditions', config('crm.contrat.conditions_defaut')),
            'statut'       => 'brouillon',
        ]);

        return back()->with('success', 'Contrat généré — relis-le puis envoie-le.');
    }

    public function update(Request $request, Contrat $contrat)
    {
        $contrat->update($request->validate([
            'objet'        => ['sometimes', 'required', 'string', 'max:1000'],
            'montant_ht'   => ['nullable', 'numeric', 'min:0'],
            'reference'    => ['nullable', 'string', 'max:255'],
            'conditions'   => ['sometimes', 'required', 'string'],
            'statut'       => ['sometimes', 'in:'.implode(',', array_keys(Contrat::STATUTS))],
            'date_contrat' => ['nullable', 'date'],
        ]));

        return back()->with('success', 'Contrat mis à jour.');
    }

    public function destroy(Contrat $contrat)
    {
        $contrat->delete();

        return back()->with('success', 'Contrat supprimé.');
    }

    /** Télécharge le PDF du contrat (aperçu). */
    public function pdf(Contrat $contrat)
    {
        return $this->buildPdf($contrat)->download($this->filename($contrat));
    }

    /** Envoie le contrat en PDF par e-mail au client. */
    public function send(Contrat $contrat)
    {
        $client = $contrat->prospect;
        if (! $client->email) {
            return back()->with('error', 'Ce client n’a pas d’adresse e-mail.');
        }

        $vendeur = config('crm.vendeur');
        $fromName = trim($vendeur['prenom'].' — '.$vendeur['societe']);
        $fromEmail = $vendeur['email'] ?: config('crm.support.email');
        $pdf = $this->buildPdf($contrat)->output();
        $subject = 'Votre contrat de prestation — '.$vendeur['societe'];
        $body = "Bonjour,\n\nVeuillez trouver ci-joint votre contrat de prestation"
            .($contrat->reference ? " (réf. {$contrat->reference})" : '')
            .".\nN'hésitez pas à revenir vers moi pour toute question.\n\n"
            ."Cordialement,\n{$vendeur['prenom']}\n{$vendeur['societe']}";

        try {
            Mail::raw($body, function ($mail) use ($client, $subject, $fromName, $fromEmail, $pdf, $contrat) {
                $mail->to($client->email)->subject($subject)
                    ->from($fromEmail, $fromName)->replyTo($fromEmail)
                    ->attachData($pdf, $this->filename($contrat), ['mime' => 'application/pdf']);
            });
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'Contrat NON envoyé : '.$e->getMessage());
        }

        $contrat->update(['statut' => 'envoye', 'envoye_le' => now()]);
        $client->interactions()->create([
            'type' => 'email',
            'note' => "📄 Contrat envoyé par e-mail : « {$contrat->objet} »",
            'date' => now(),
        ]);

        return back()->with('success', 'Contrat envoyé à '.$client->email.'.');
    }

    private function buildPdf(Contrat $contrat)
    {
        $contrat->loadMissing('prospect');

        return Pdf::loadView('contrats.pdf', [
            'contrat' => $contrat,
            'client'  => $contrat->prospect,
            'vendeur' => config('crm.vendeur'),
        ])->setPaper('a4');
    }

    private function filename(Contrat $contrat): string
    {
        return 'contrat-'.Str::slug($contrat->reference ?: $contrat->id).'.pdf';
    }
}
