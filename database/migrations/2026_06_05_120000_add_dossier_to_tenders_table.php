<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenders', function (Blueprint $table) {
            // Dossier de réponse pré-rempli : { resume, memoire, dpgf, acte, checklist[], generated_at }.
            $table->json('dossier')->nullable()->after('notes');
            // Montant chiffré de l'offre (€ HT) — pour le suivi / totaux pipeline.
            $table->integer('montant_ht')->nullable()->after('dossier');
        });
    }

    public function down(): void
    {
        Schema::table('tenders', function (Blueprint $table) {
            $table->dropColumn(['dossier', 'montant_ht']);
        });
    }
};
