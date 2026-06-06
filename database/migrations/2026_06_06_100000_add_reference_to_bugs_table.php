<?php

use App\Models\Bug;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bugs', function (Blueprint $table) {
            $table->string('reference')->nullable()->unique()->after('id');
        });

        // Backfill des tickets déjà créés.
        Bug::whereNull('reference')->get()->each(function (Bug $bug) {
            $annee = $bug->created_at?->year ?? now()->year;
            $bug->forceFill(['reference' => sprintf('TIC-%d-%04d', $annee, $bug->id)])->saveQuietly();
        });
    }

    public function down(): void
    {
        Schema::table('bugs', function (Blueprint $table) {
            $table->dropColumn('reference');
        });
    }
};
