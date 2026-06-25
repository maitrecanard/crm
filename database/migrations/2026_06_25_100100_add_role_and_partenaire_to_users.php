<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rôle utilisateur + rattachement à un partenaire.
 * - role : 'admin' (toi, accès complet) ou 'partenaire' (portail restreint).
 * - partenaire_id : compte d'un partenaire (NULL pour les comptes admin).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('admin')->after('email');
            $table->foreignId('partenaire_id')->nullable()->after('role')
                ->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('partenaire_id');
            $table->dropColumn('role');
        });
    }
};
