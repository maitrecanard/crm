<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Un projet peut être lié à plusieurs partenaires (et inversement).
 * Table pivot + reprise du partenaire unique existant (projects.partenaire_id).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partenaire_project', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partenaire_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['partenaire_id', 'project_id']);
        });

        // Reprise de l'existant : chaque projet déjà rattaché à un partenaire.
        DB::table('projects')->whereNotNull('partenaire_id')->orderBy('id')
            ->each(function ($p) {
                DB::table('partenaire_project')->insertOrIgnore([
                    'partenaire_id' => $p->partenaire_id,
                    'project_id'    => $p->id,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('partenaire_project');
    }
};
