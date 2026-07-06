<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Journal d'historique d'un ticket : changements de statut (et autres événements
 * de cycle de vie) horodatés, pour reconstituer la timeline sur la page ticket.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bug_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bug_id')->constrained()->cascadeOnDelete();
            $table->string('type');                 // creation | statut
            $table->string('ancien_statut')->nullable();
            $table->string('nouveau_statut')->nullable();
            $table->string('auteur')->nullable();   // « client » | « support » | nom éventuel
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bug_events');
    }
};
