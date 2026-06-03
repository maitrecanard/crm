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
        Schema::create('tenders', function (Blueprint $table) {
            $table->id();
            $table->string('idweb')->unique();           // identifiant BOAMP
            $table->text('objet');
            $table->string('acheteur')->nullable();
            $table->string('departement')->nullable();
            $table->string('procedure')->nullable();
            $table->date('date_parution')->nullable();
            $table->dateTime('date_limite')->nullable();
            $table->string('url')->nullable();
            $table->string('statut')->default('a_etudier'); // pipeline candidature
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('statut');
            $table->index('date_limite');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenders');
    }
};
