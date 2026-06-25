<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Images jointes à un ticket d'assistance (4 max), stockées sur disque privé. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bug_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bug_id')->constrained()->cascadeOnDelete();
            $table->string('chemin');           // chemin relatif sur le disque privé
            $table->string('nom')->nullable();  // nom d'origine fourni par le client
            $table->string('mime', 100)->nullable();
            $table->unsignedInteger('taille')->default(0);  // octets
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bug_images');
    }
};
