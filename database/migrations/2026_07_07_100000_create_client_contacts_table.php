<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Contacts d'un client : plusieurs interlocuteurs par entreprise, formant la
 * liste de diffusion des e-mails (notamment le suivi des tickets).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prospect_id')->constrained()->cascadeOnDelete();
            $table->string('nom')->nullable();
            $table->string('email');
            $table->string('fonction')->nullable();
            $table->boolean('notifie_tickets')->default(true);   // reçoit le suivi des tickets
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_contacts');
    }
};
