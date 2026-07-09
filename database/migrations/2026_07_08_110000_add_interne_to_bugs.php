<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Flag « interne » d'un ticket : indépendant du projet. Un ticket interne
 * n'envoie aucune notification au client, qu'il soit rattaché à un projet ou non.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bugs', function (Blueprint $table) {
            $table->boolean('interne')->default(false)->after('source');
        });
    }

    public function down(): void
    {
        Schema::table('bugs', function (Blueprint $table) {
            $table->dropColumn('interne');
        });
    }
};
