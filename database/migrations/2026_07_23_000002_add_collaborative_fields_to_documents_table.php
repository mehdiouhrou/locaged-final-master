<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE documents MODIFY status ENUM(
            'pending',
            'declined',
            'approved',
            'archived',
            'destroyed',
            'brouillon',
            'en_relecture',
            'valide'
        ) NOT NULL DEFAULT 'pending'");

        Schema::table('documents', function (Blueprint $table) {
            $table->enum('entry_type', ['direct_archive', 'collaborative'])
                ->default('direct_archive')
                ->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn('entry_type');
        });

        DB::statement("ALTER TABLE documents MODIFY status ENUM(
            'pending',
            'declined',
            'approved',
            'locked',
            'unlocked',
            'moved',
            'archived',
            'destroyed'
        ) NOT NULL DEFAULT 'pending'");
    }
};
