<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Paramètres globaux clé/valeur (ex. modèle de conditions de contrat).
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->longText('value')->nullable();
            $table->timestamps();
        });

        // Contrats rattachés à un client.
        Schema::create('contrats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prospect_id')->constrained()->cascadeOnDelete();
            $table->string('reference')->nullable();
            $table->string('objet');
            $table->decimal('montant_ht', 10, 2)->nullable();
            $table->longText('conditions');               // copiées du modèle, éditables
            $table->string('statut')->default('brouillon');
            $table->date('date_contrat')->nullable();
            $table->timestamp('envoye_le')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contrats');
        Schema::dropIfExists('settings');
    }
};
