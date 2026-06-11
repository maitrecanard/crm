<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Besoins exprimés par le client (peuvent devenir un devis / projet).
        Schema::create('besoins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prospect_id')->constrained()->cascadeOnDelete();
            $table->string('titre');
            $table->text('description')->nullable();
            $table->string('statut')->default('a_traiter');
            $table->timestamps();
        });

        // Devis (suivi léger : référence, montant, statut, lien vers le PDF).
        Schema::create('devis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prospect_id')->constrained()->cascadeOnDelete();
            $table->string('reference')->nullable();
            $table->decimal('montant_ht', 10, 2)->nullable();
            $table->string('statut')->default('envoye');
            $table->date('date_devis')->nullable();
            $table->string('lien')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Factures ponctuelles (à l'unité), distinctes de la facturation mensuelle.
        Schema::create('factures_ponctuelles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prospect_id')->constrained()->cascadeOnDelete();
            $table->string('reference')->nullable();
            $table->decimal('montant_ht', 10, 2)->nullable();
            $table->string('statut')->default('envoyee');
            $table->date('date_facture')->nullable();
            $table->string('lien')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('factures_ponctuelles');
        Schema::dropIfExists('devis');
        Schema::dropIfExists('besoins');
    }
};
