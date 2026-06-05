<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Un prospect gagné devient client.
        Schema::table('prospects', function (Blueprint $table) {
            $table->boolean('est_client')->default(false)->after('statut');
            $table->date('client_depuis')->nullable()->after('est_client');
        });

        // Sa demande devient un projet.
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prospect_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tender_id')->nullable()->constrained()->nullOnDelete();
            $table->string('titre');
            $table->text('description')->nullable();
            $table->string('statut')->default('cadrage');
            $table->integer('budget')->nullable();          // € HT
            $table->date('date_debut')->nullable();
            $table->date('date_fin_prevue')->nullable();
            $table->date('date_livraison')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Tâches / jalons de gestion du projet.
        Schema::create('project_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('titre');
            $table->string('statut')->default('a_faire');
            $table->date('echeance')->nullable();
            $table->integer('ordre')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_tasks');
        Schema::dropIfExists('projects');
        Schema::table('prospects', function (Blueprint $table) {
            $table->dropColumn(['est_client', 'client_depuis']);
        });
    }
};
