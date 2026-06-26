<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Partenaires : apporteurs d'affaires / sous-traitants à qui l'on rattache des
 * projets et qui peuvent transmettre des tâches à réaliser (via un compte dédié).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partenaires', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('contact_nom')->nullable();
            $table->string('email')->unique();
            $table->string('telephone', 60)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('actif')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partenaires');
    }
};
