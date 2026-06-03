<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('prospects', function (Blueprint $table) {
            $table->id();
            $table->string('cle')->unique();              // clé de déduplication
            $table->string('entreprise');
            $table->string('localite')->nullable();
            $table->string('telephone')->nullable();
            $table->string('email')->nullable();
            $table->string('categorie')->nullable();
            $table->string('secteur')->nullable();
            $table->text('signal_alerte')->nullable();
            $table->string('source_url')->nullable();
            $table->string('requete')->nullable();
            $table->string('source_fichier')->nullable(); // clients_tech, grands_comptes, besoins, pme
            $table->string('statut')->default('a_contacter');
            $table->date('prochaine_relance')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('statut');
            $table->index('source_fichier');
            $table->index('secteur');
            $table->index('localite');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prospects');
    }
};
