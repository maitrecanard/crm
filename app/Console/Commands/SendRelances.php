<?php

namespace App\Console\Commands;

use App\Mail\RelanceMail;
use App\Models\Prospect;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

/**
 * Envoie les e-mails de relance dus (prospects dont la prochaine_relance est échue),
 * écrit un rapport horodaté et envoie une confirmation au support.
 * À planifier en cron (ex. quotidien) : php artisan crm:relances
 */
class SendRelances extends Command
{
    protected $signature = 'crm:relances {--dry-run : Simule sans envoyer} {--limit=500 : Nb max de relances}';

    protected $description = 'Envoie les e-mails de relance dus et produit un rapport.';

    public function handle(): int
    {
        $today = now()->toDateString();
        $dry = (bool) $this->option('dry-run');

        $due = Prospect::whereNotNull('email')->where('email', '<>', '')
            ->whereNotNull('prochaine_relance')
            ->whereDate('prochaine_relance', '<=', $today)
            ->whereNotIn('statut', ['gagne', 'perdu', 'ignore'])
            ->limit((int) $this->option('limit'))
            ->get();

        $report = [];
        $ok = $err = 0;

        foreach ($due as $p) {
            if ($dry) {
                $report[] = "[DRY] {$p->entreprise} <{$p->email}> (relance prévue le ".$p->prochaine_relance->format('d/m/Y').')';
                continue;
            }
            try {
                Mail::to($p->email)->send(new RelanceMail($p));
                $p->interactions()->create(['type' => 'email', 'note' => '🔁 Relance automatique envoyée', 'date' => now()]);
                $p->update([
                    'statut'            => $p->statut === 'contacte' ? 'relance' : $p->statut,
                    'prochaine_relance' => null,   // relance faite : on ne reboucle pas
                ]);
                $report[] = "OK   {$p->entreprise} <{$p->email}>";
                $ok++;
            } catch (\Throwable $e) {
                report($e);
                $report[] = "ERR  {$p->entreprise} <{$p->email}> : ".$e->getMessage();
                $err++;
            }
        }

        $summary = "Relances du {$today} — ".($dry ? 'DRY-RUN, ' : '')."{$due->count()} due(s), {$ok} envoyée(s), {$err} erreur(s).";
        $content = $summary."\n\n".(implode("\n", $report) ?: '(aucune relance due)')."\n";

        $path = "relances/relance-{$today}.txt";
        Storage::put($path, $content);

        $this->info($summary);
        $this->line('Rapport : '.Storage::path($path));

        // Confirmation au support (sauf dry-run et si rien à envoyer).
        $copy = config('crm.support.copy');
        if (! $dry && $copy && ($ok || $err)) {
            try {
                Mail::raw($content, fn ($m) => $m->to($copy)
                    ->subject("[CRM] Rapport relances {$today} — {$ok} envoyée(s)")
                    ->from(config('crm.support.email'), config('crm.support.name')));
            } catch (\Throwable $e) {
                $this->warn('Rapport non envoyé par e-mail : '.$e->getMessage());
            }
        }

        return self::SUCCESS;
    }
}
