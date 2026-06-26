<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Un ticket peut provenir du site du client (assistance) :
 * - source : 'interne' (créé dans le CRM) ou 'client_site' (déclaré via l'API).
 * - motif  : motif de la demande choisi par le client.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bugs', function (Blueprint $table) {
            $table->string('source')->default('interne')->after('type');
            $table->string('motif')->nullable()->after('source');
        });
    }

    public function down(): void
    {
        Schema::table('bugs', function (Blueprint $table) {
            $table->dropColumn(['source', 'motif']);
        });
    }
};
