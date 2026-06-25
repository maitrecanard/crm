<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Token d'assistance (150 caractères) propre à chaque client : il identifie le
 * client lors des appels API émis depuis SON site, et cloisonne ses tickets.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prospects', function (Blueprint $table) {
            $table->string('support_token', 150)->nullable()->unique()->after('est_client');
        });
    }

    public function down(): void
    {
        Schema::table('prospects', function (Blueprint $table) {
            $table->dropColumn('support_token');
        });
    }
};
