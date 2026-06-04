<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // google_id sera CHIFFRÉ -> pas d'index unique (ciphertext non
            // déterministe). La liaison se fait par email (déjà unique).
            $table->text('google_id')->nullable()->after('email');
            $table->string('avatar')->nullable()->after('google_id');
            // Les comptes Google n'ont pas de mot de passe local.
            $table->string('password')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['google_id', 'avatar']);
        });
    }
};
