<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * LocaGed V2 — Intégrité fichier (SHA-256) après approbation (CDC §4.1).
     */
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->string('file_hash', 64)->nullable()->after('metadata');
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn('file_hash');
        });
    }
};
