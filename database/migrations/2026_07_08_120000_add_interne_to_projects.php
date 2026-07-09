<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Projets internes : un projet peut ne pas être rattaché à un client.
 * On ajoute un flag `interne` et on rend `prospect_id` nullable.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->boolean('interne')->default(false)->after('prospect_id');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->unsignedBigInteger('prospect_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('interne');
        });
    }
};
