<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bug_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bug_id')->constrained()->cascadeOnDelete();
            $table->text('corps');
            $table->boolean('interne')->default(false);   // true = non transmis au client
            $table->timestamp('notifie_le')->nullable();   // si transmis : date d'envoi
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bug_messages');
    }
};
