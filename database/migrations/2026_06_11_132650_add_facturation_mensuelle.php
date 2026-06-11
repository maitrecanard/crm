<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Surveillance de facturation mensuelle, programmée par client.
        Schema::table('prospects', function (Blueprint $table) {
            $table->boolean('facturation_active')->default(false);
            $table->unsignedTinyInteger('facturation_jour')->default(5); // jour d'échéance (mois suivant)
            $table->date('facturation_debut')->nullable();               // 1er mois concerné
            $table->decimal('facturation_montant_ht', 10, 2)->nullable();
            $table->string('facturation_libelle')->nullable();
        });

        // Une ligne par (client, mois) : porte la référence de facture saisie
        // (= preuve d'envoi) ; son absence passé l'échéance déclenche l'alerte.
        Schema::create('factures_mensuelles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prospect_id')->constrained()->cascadeOnDelete();
            $table->date('periode');                       // 1er jour du mois concerné
            $table->string('reference')->nullable();       // n° de facture (vide = non envoyée)
            $table->decimal('montant_ht', 10, 2)->nullable();
            $table->date('envoyee_le')->nullable();
            $table->timestamp('alerte_envoyee_le')->nullable(); // anti-spam de l'alerte e-mail
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['prospect_id', 'periode']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('factures_mensuelles');
        Schema::table('prospects', function (Blueprint $table) {
            $table->dropColumn([
                'facturation_active', 'facturation_jour', 'facturation_debut',
                'facturation_montant_ht', 'facturation_libelle',
            ]);
        });
    }
};
