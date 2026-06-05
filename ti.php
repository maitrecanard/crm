<?php
use App\Models\Prospect;
use Illuminate\Http\Request;
$p = Prospect::first();
$old = ['e'=>$p->entreprise,'m'=>$p->email];
$ctrl = app(\App\Http\Controllers\ProspectController::class);
// envoie SEULEMENT les coordonnées (pas de statut)
$ctrl->update(Request::create('/x','PUT',[
  'entreprise'=>'Nouveau Nom SARL','email'=>'nouveau@client.fr','telephone'=>'0102030405',
]), $p);
$p->refresh();
echo "entreprise: {$old['e']} -> {$p->entreprise}\n";
echo "email: ".($old['m']?:'(vide)')." -> {$p->email}\n";
echo "statut inchangé: {$p->statut} | est_client=".($p->est_client?'oui':'non')." (pas de promotion déclenchée)\n";
