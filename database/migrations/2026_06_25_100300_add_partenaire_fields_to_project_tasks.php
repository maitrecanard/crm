<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Une tâche peut être transmise par un partenaire.
 * - description : détail de la tâche à réaliser fourni par le partenaire.
 * - source : 'interne' (créée par toi) ou 'partenaire' (transmise via le portail).
 * - partenaire_id : le partenaire émetteur (NULL si interne).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_tasks', function (Blueprint $table) {
            $table->text('description')->nullable()->after('titre');
            $table->string('source')->default('interne')->after('statut');
            $table->foreignId('partenaire_id')->nullable()->after('source')
                ->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('project_tasks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('partenaire_id');
            $table->dropColumn(['description', 'source']);
        });
    }
};
