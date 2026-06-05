<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('url_prod')->nullable()->after('description');     // site en ligne
            $table->string('url_preprod')->nullable()->after('url_prod');     // préproduction / staging
            $table->string('repo_git')->nullable()->after('url_preprod');     // dépôt Git
            $table->string('hebergeur')->nullable()->after('repo_git');       // hébergeur
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['url_prod', 'url_preprod', 'repo_git', 'hebergeur']);
        });
    }
};
