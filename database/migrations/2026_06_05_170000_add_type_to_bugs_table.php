<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bugs', function (Blueprint $table) {
            // Nature de l'intervention : bug / maintenance / évolution.
            $table->string('type')->default('bug')->after('project_id');
            // Pour les maintenances périodiques.
            $table->string('recurrence')->nullable()->after('gravite');
            $table->date('prochaine_echeance')->nullable()->after('recurrence');
        });
    }

    public function down(): void
    {
        Schema::table('bugs', function (Blueprint $table) {
            $table->dropColumn(['type', 'recurrence', 'prochaine_echeance']);
        });
    }
};
