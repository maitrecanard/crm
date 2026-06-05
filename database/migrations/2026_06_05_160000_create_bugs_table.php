<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bugs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('titre');
            $table->text('description')->nullable();
            $table->string('statut')->default('nouveau');
            $table->string('gravite')->default('majeur');   // mineur / majeur / bloquant
            $table->string('issue_git')->nullable();        // URL ou réf. de l'issue git (suivi interne)
            $table->timestamp('notifie_le')->nullable();    // dernière notification client envoyée
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bugs');
    }
};
