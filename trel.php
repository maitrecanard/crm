<?php
use App\Models\Prospect; use Illuminate\Http\Request; use Illuminate\Support\Facades\Mail;
$p = Prospect::first();
$p->update(['email'=>'prospect@example.fr','statut'=>'a_contacter','prochaine_relance'=>null]);
$p->interactions()->where('type','email')->delete();
Mail::fake();
app(\App\Http\Controllers\ProspectController::class)->sendEmail(Request::create('/x','POST',[
  'corps'=>"Objet : Test\n\nBonjour, corps du mail de test.\n\nMathieu",
]), $p);
$p->refresh();
echo "success: ".json_encode(session('success'))."\n";
echo "prochaine_relance: ".$p->prochaine_relance?->format('Y-m-d')." (attendu J+7)\n";
$i = $p->interactions()->where('type','email')->first();
echo "historique contient le corps ? ".(str_contains($i->note,'corps du mail de test')?'OUI':'non')."\n";
$p->interactions()->where('type','email')->delete();
